<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("php/dbconnect.php");

// Add this helper function at the top of upload_csv.php (after includes)
/**
 * ENHANCED: Trigger prediction with watcher fallback
 */
function trigger_prediction_auto($year, $semester, $tableName, $success_count, $filename, $uploaded_by_name, $uploaded_by_username, $uploaded_by_user_id) {
    $script_dir = dirname(__FILE__);
    
    $result = [
        'success' => false,
        'message' => '',
        'method' => 'none'
    ];
    
    // Determine semester display
    $semester_display = ($semester == '1') ? '1st Semester' : '2nd Semester';
    
    // Create trigger data
    $trigger_data = [
        'trigger_timestamp' => date('Y-m-d H:i:s'),
        'trigger_type' => 'auto_upload',
        'year' => intval($year),
        'semester' => $semester,
        'semester_display' => $semester_display,
        'table_name' => $tableName,
        'records_uploaded' => intval($success_count),
        'filename' => $filename,
        'uploaded_by' => $uploaded_by_name,
        'uploaded_by_username' => $uploaded_by_username,
        'uploaded_by_user_id' => $uploaded_by_user_id,
        'status' => 'pending',
        'message' => "Automatic prediction trigger after upload to {$tableName}"
    ];
    
    // Detect OS
    $is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    
    // Find Python executable
    $python_exe = find_python_executable($is_windows);
    
    if (!$python_exe) {
        error_log("Python executable not found");
        $result['message'] = "Python executable not found. Please install Python 3.6+";
        return $result;
    }
    
    error_log("Python found: $python_exe");
    
    // STEP 1: Check if watcher is running
    error_log("Checking if watcher is running...");
    $watcher_running = is_watcher_running($is_windows);
    
    if (!$watcher_running) {
        error_log("Watcher not running, attempting to start...");
        
        // Try to start the watcher
        $watcher_started = start_watcher_background($python_exe, $script_dir, $is_windows);
        
        if ($watcher_started) {
            error_log("Watcher started successfully");
            // Wait a moment for it to initialize
            sleep(2);
        } else {
            error_log("Could not start watcher, will try direct execution");
        }
    }
    
    // STEP 2: Write trigger file LAST (after watcher is confirmed running)
    $trigger_file = $script_dir . DIRECTORY_SEPARATOR . "prediction_trigger.json";
    
    error_log("Writing trigger file: $trigger_file");
    
    // Write with exclusive lock and proper error handling
    $json_content = json_encode($trigger_data, JSON_PRETTY_PRINT);
    $write_result = file_put_contents($trigger_file, $json_content, LOCK_EX);
    
    if ($write_result === false) {
        error_log("Failed to write trigger file");
        $result['message'] = "Failed to create trigger file";
        return $result;
    }
    
    // Verify file was written
    if (!file_exists($trigger_file) || filesize($trigger_file) == 0) {
        error_log("Trigger file not created or empty");
        $result['message'] = "Trigger file creation failed";
        return $result;
    }
    
    error_log("Trigger file created successfully (" . filesize($trigger_file) . " bytes)");
    
    // STEP 3: Determine method and return result
    if (is_watcher_running($is_windows)) {
        $result['success'] = true;
        $result['method'] = 'watcher_detected';
        $result['message'] = "Prediction watcher is running - trigger file will be processed within 5 seconds";
        error_log("Watcher active - prediction will start automatically");
    } else {
        // Last resort: try direct execution
        error_log("Watcher not running, attempting direct prediction...");
        $predict_script = $script_dir . DIRECTORY_SEPARATOR . "predict.py";
        
        if (file_exists($predict_script)) {
            $direct_run = run_prediction_direct($python_exe, $predict_script, $script_dir, $is_windows);
            
            if ($direct_run) {
                $result['success'] = true;
                $result['method'] = 'direct_execution';
                $result['message'] = "Prediction started directly (watcher not available)";
                error_log("Direct prediction started");
            } else {
                $result['success'] = true; // Partial success
                $result['method'] = 'trigger_file_only';
                $result['message'] = "Trigger file created. Start watcher manually: python prediction_watcher.py";
                error_log("Manual intervention required");
            }
        } else {
            $result['success'] = true; // Partial success
            $result['method'] = 'trigger_file_only';
            $result['message'] = "Trigger file created. Please run: python prediction_watcher.py";
            error_log("predict.py not found, manual start required");
        }
    }
    
    return $result;
}


/**
 * FIXED: Check if prediction watcher is already running
 * Now uses heartbeat file for reliable detection
 */
function is_watcher_running($is_windows) {
    $script_dir = dirname(__FILE__);
    $heartbeat_file = $script_dir . DIRECTORY_SEPARATOR . "watcher_heartbeat.txt";
    
    // Method 1: Check heartbeat file (most reliable)
    if (file_exists($heartbeat_file)) {
        $last_heartbeat = @file_get_contents($heartbeat_file);
        if ($last_heartbeat) {
            $heartbeat_time = strtotime($last_heartbeat);
            $current_time = time();
            
            // If heartbeat is less than 10 seconds old, watcher is running
            if (($current_time - $heartbeat_time) < 10) {
                error_log("Watcher detected via heartbeat file (age: " . ($current_time - $heartbeat_time) . "s)");
                return true;
            } else {
                error_log("Stale heartbeat detected (age: " . ($current_time - $heartbeat_time) . "s) - watcher may be stuck");
            }
        }
    }
    
    // Method 2: Fallback to process check
    try {
        if ($is_windows) {
            // Windows: Check for python.exe running prediction_watcher
            $output = shell_exec('tasklist /FI "IMAGENAME eq python.exe" /FO CSV 2>nul');
            if ($output && stripos($output, 'python.exe') !== false) {
                // Also check command line
                $wmic_output = shell_exec('wmic process where "name=\'python.exe\'" get commandline 2>nul');
                if ($wmic_output && stripos($wmic_output, 'predict') !== false) {
                    error_log("Watcher detected via Windows process list");
                    return true;
                }
            }
        } else {
            // Linux/Mac: Check for prediction_watcher.py process
            $output = shell_exec('ps aux | grep "predict.py" | grep -v grep 2>/dev/null');
            if (!empty($output)) {
                error_log("Watcher detected via Unix process list");
                return true;
            }
        }
    } catch (Exception $e) {
        error_log("Process check failed: " . $e->getMessage());
    }
    
    error_log("No running watcher detected");
    return false;
}

/**
 * Start watcher in background
 */
function start_watcher_background($python_exe, $script_dir, $is_windows) {
    $watcher_script = $script_dir . DIRECTORY_SEPARATOR . "predict.py";
    
    if (!file_exists($watcher_script)) {
        error_log("Watcher script not found: $watcher_script");
        return false;
    }
    
    try {
        if ($is_windows) {
            // Windows: Start watcher as background process with proper output redirection
            $log_file = $script_dir . DIRECTORY_SEPARATOR . "watcher_startup.log";
            $command = "start /B \"Prediction Watcher\" \"$python_exe\" \"$watcher_script\" > \"$log_file\" 2>&1";
            
            error_log("Starting watcher (Windows): $command");
            pclose(popen($command, "r"));
        } else {
            // Linux/Mac: Start watcher as daemon with nohup
            $log_file = $script_dir . DIRECTORY_SEPARATOR . "watcher_startup.log";
            $command = "nohup '$python_exe' '$watcher_script' > '$log_file' 2>&1 &";
            
            error_log("Starting watcher (Unix): $command");
            exec($command);
        }
        
        // Wait longer for startup
        error_log("Waiting for watcher to start...");
        sleep(3);
        
        // Verify it started
        $is_running = is_watcher_running($is_windows);
        
        if ($is_running) {
            error_log("Watcher started successfully!");
        } else {
            error_log("Watcher may not have started - check logs");
        }
        
        return $is_running;
        
    } catch (Exception $e) {
        error_log("Failed to start watcher: " . $e->getMessage());
        return false;
    }
}

/**
 * Run prediction directly (fallback method)
 */
function run_prediction_direct($python_exe, $predict_script, $script_dir, $is_windows) {
    try {
        $log_file = $script_dir . DIRECTORY_SEPARATOR . "predict_direct.log";
        
        if ($is_windows) {
            $command = "start /B \"Direct Prediction\" \"$python_exe\" \"$predict_script\" > \"$log_file\" 2>&1";
            error_log("Running prediction directly (Windows): $command");
            pclose(popen($command, "r"));
        } else {
            $command = "cd '$script_dir' && nohup '$python_exe' '$predict_script' > '$log_file' 2>&1 &";
            error_log("Running prediction directly (Unix): $command");
            exec($command);
        }
        
        sleep(1); // Give it a moment to start
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to run prediction directly: " . $e->getMessage());
        return false;
    }
}


/**
 * Find Python executable - UPDATED for 'py' launcher
 */
function find_python_executable($is_windows) {
    $candidates = [];
    
    if ($is_windows) {
        $candidates = [
            'py',           // Windows Python Launcher (BEST for Windows)
            'python',
            'python3',
            'C:\\Python312\\python.exe',
            'C:\\Python311\\python.exe',
            'C:\\Python310\\python.exe',
        ];
    } else {
        $candidates = [
            'python3',
            'python',
            '/usr/bin/python3',
            '/usr/local/bin/python3',
        ];
    }
    
    foreach ($candidates as $python) {
        $version_check = $is_windows ? 
            "where $python >nul 2>nul && $python --version 2>&1" :
            "which $python >/dev/null 2>&1 && $python --version 2>&1";
        
        $output = [];
        $return_code = 0;
        exec($version_check, $output, $return_code);
        
        if ($return_code === 0 && !empty($output)) {
            $version_line = implode(' ', $output);
            if (preg_match('/Python\s+3\.([6-9]|\d{2})/', $version_line)) {
                error_log("Found Python executable: $python (Version: $version_line)");
                return $python;
            }
        }
    }
    
    error_log("No suitable Python 3.6+ executable found");
    return null;
}
/**
 * Extract year from filename with STRICT validation
 * Returns array with year info and validation status
 */
function extractYearFromFilename($filename) {
    // Remove file extension
    $name = pathinfo($filename, PATHINFO_FILENAME);
    
    // Find ALL 4-digit numbers that could be years
    preg_match_all('/\d{4}/', $name, $all_matches);
    
    $found_years = [];
    
    if (!empty($all_matches[0])) {
        foreach ($all_matches[0] as $match) {
            $year = intval($match);
            // Only accept years in reasonable range
            if ($year >= 2020 && $year <= 2030) {
                if (!in_array($year, $found_years)) {
                    $found_years[] = $year;
                }
            }
        }
    }
    
    // Return detailed information
    return [
        'year' => !empty($found_years) ? $found_years[0] : null,
        'all_years' => $found_years,
        'year_count' => count($found_years),
        'has_multiple_years' => count($found_years) > 1,
        'has_no_year' => empty($found_years)
    ];
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csvFile"]) && isset($_POST["year"]) && isset($_POST["semester"])) {
    $year = $conn->real_escape_string($_POST["year"]);
    $semester = $conn->real_escape_string($_POST["semester"]);
    
    // Get current user info for tracking
    $uploaded_by_user_id = isset($_SESSION['rainbow_uid']) ? $_SESSION['rainbow_uid'] : null;
    $uploaded_by_username = isset($_SESSION['rainbow_username']) ? $conn->real_escape_string($_SESSION['rainbow_username']) : 'unknown';
    $uploaded_by_name = isset($_SESSION['rainbow_name']) ? $conn->real_escape_string($_SESSION['rainbow_name']) : 'Unknown User';
    
    // Create dynamic table name with year and semester
    $tableName = "student_" . $year . "_sem_" . $semester;
    $file = $_FILES["csvFile"]["tmp_name"];
    $filename = $_FILES["csvFile"]["name"];
    $upload_success = false;
    $error_messages = [];
    $success_count = 0;
    $error_count = 0;
    $validation_errors = [];

    try {
        // Validate semester input (only 1st and 2nd semester allowed)
        $valid_semesters = ['1', '2'];
        if (!in_array($semester, $valid_semesters)) {
            throw new Exception("Invalid semester selected. Only 1st and 2nd semester are allowed.");
        }

        // Validate year (2020-2025 only)
        if (!is_numeric($year) || $year < 2020 || $year > 2025) {
            throw new Exception("Invalid year selected. Only years 2020-2025 are allowed.");
        }
        
        // STRICT: Validate filename year
        $year_info = extractYearFromFilename($filename);
        $selected_year = intval($year);
        
        // STRICT CHECK 1: No year in filename - REJECT
        if ($year_info['has_no_year']) {
            $error_msg = "UPLOAD REJECTED - Missing Year in Filename\n\n";
            $error_msg .= "REASON: Filename does not contain any year information\n\n";
            $error_msg .= "Current filename: {$filename}\n";
            $error_msg .= "Selected year: {$selected_year}\n\n";
            $error_msg .= "REQUIRED ACTION:\n";
            $error_msg .= "Rename your file to include the year. Examples:\n";
            $error_msg .= "students_{$selected_year}.csv\n";
            $error_msg .= "{$selected_year}_semester_{$semester}_data.csv\n";
            $error_msg .= "student_data_{$selected_year}.csv\n\n";
            $error_msg .= "WHY THIS IS REQUIRED:\n";
            $error_msg .= "Year in filename ensures you're uploading data to the correct academic year table.\n";
            $error_msg .= "This prevents accidentally mixing data from different years.";
            
            throw new Exception($error_msg);
        }
        
        // STRICT CHECK 2: Multiple years in filename - REJECT
        if ($year_info['has_multiple_years']) {
            $years_list = implode(', ', $year_info['all_years']);
            
            $error_msg = "UPLOAD REJECTED - Multiple Years Detected in Filename\n\n";
            $error_msg .= "REASON: Filename contains multiple years which creates ambiguity\n\n";
            $error_msg .= "Current filename: {$filename}\n";
            $error_msg .= "Years found: {$years_list}\n";
            $error_msg .= "Selected year: {$selected_year}\n\n";
            $error_msg .= "REQUIRED ACTION:\n";
            $error_msg .= "Rename your file to include ONLY ONE year. Examples:\n";
            $error_msg .= "students_{$selected_year}.csv\n";
            $error_msg .= "{$selected_year}_semester_{$semester}.csv\n";
            $error_msg .= "enrollment_{$selected_year}.csv\n\n";
            $error_msg .= "WHY THIS IS REQUIRED:\n";
            $error_msg .= "Files like '2023_to_2024_migration.csv' are ambiguous.\n";
            $error_msg .= "Each upload must be clearly labeled with ONE specific year to prevent confusion.";
            
            throw new Exception($error_msg);
        }
        
        // STRICT CHECK 3: Year mismatch - REJECT
        if ($year_info['year'] !== $selected_year) {
            $filename_year = $year_info['year'];
            
            $error_msg = "UPLOAD REJECTED - Year Mismatch\n\n";
            $error_msg .= "REASON: Selected year does not match the year in filename\n\n";
            $error_msg .= "Current filename: {$filename}\n";
            $error_msg .= "Filename year: {$filename_year}\n";
            $error_msg .= "Selected year: {$selected_year}\n";
            $error_msg .= "Target table: {$tableName}\n\n";
            $error_msg .= "REQUIRED ACTION - Choose ONE:\n";
            $error_msg .= "OPTION 1: Change year dropdown to '{$filename_year}'\n";
            $error_msg .= "This will upload to: student_{$filename_year}_sem_{$semester}\n\n";
            $error_msg .= "OPTION 2: Rename file to include year '{$selected_year}'\n";
            $error_msg .= "Example: students_{$selected_year}.csv\n";
            $error_msg .= "This will upload to: {$tableName}\n\n";
            $error_msg .= "WHY THIS IS REQUIRED:\n";
            $error_msg .= "This strict validation prevents uploading {$filename_year} data into {$selected_year} table,\n";
            $error_msg .= "which would corrupt your database and affect model training accuracy.";
            
            throw new Exception($error_msg);
        }
        
        error_log("STRICT Year Validation PASSED: Selected year {$selected_year} matches filename year exactly for: {$filename}");
        error_log("   - Year count in filename: 1 (exactly one year found)");
        error_log("   - No ambiguity detected");
        error_log("   - Uploaded by: {$uploaded_by_name} ({$uploaded_by_username})");

        // Check file upload errors
        if ($_FILES["csvFile"]["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error occurred.");
        }

        // Validate file type
        $file_info = pathinfo($_FILES["csvFile"]["name"]);
        if (strtolower($file_info['extension']) !== 'csv') {
            throw new Exception("Only CSV files are allowed.");
        }
        
        // Check if this exact file was already uploaded for this year/semester
        $check_duplicate_query = "SELECT log_id, filename, upload_date, records_success, uploaded_by_name 
                                  FROM upload_logs 
                                  WHERE filename = ? 
                                  AND year = ? 
                                  AND semester = ? 
                                  AND status = 'success'
                                  ORDER BY upload_date DESC 
                                  LIMIT 1";
        
        $stmt_check = $conn->prepare($check_duplicate_query);
        $stmt_check->bind_param("sss", $filename, $year, $semester);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $existing_upload = $result_check->fetch_assoc();
            $existing_date = date('F j, Y \a\t g:i A', strtotime($existing_upload['upload_date']));
            
            $error_msg = "UPLOAD REJECTED - Duplicate File Detected\n\n";
            $error_msg .= "REASON: This exact file has already been uploaded for this year and semester\n\n";
            $error_msg .= "UPLOAD DETAILS:\n";
            $error_msg .= "Current attempt:\n";
            $error_msg .= "  - Filename: {$filename}\n";
            $error_msg .= "  - Year: {$year}\n";
            $error_msg .= "  - Semester: " . ($semester == '1' ? '1st' : '2nd') . "\n";
            $error_msg .= "  - Attempting to upload to: {$tableName}\n\n";
            $error_msg .= "Previous upload:\n";
            $error_msg .= "  - Upload Date: {$existing_date}\n";
            $error_msg .= "  - Uploaded By: {$existing_upload['uploaded_by_name']}\n";
            $error_msg .= "  - Records Imported: {$existing_upload['records_success']}\n";
            $error_msg .= "  - Log ID: #{$existing_upload['log_id']}\n\n";
            $error_msg .= "WHY THIS IS BLOCKED:\n";
            $error_msg .= "Prevents accidental duplicate data imports\n";
            $error_msg .= "Protects database integrity\n";
            $error_msg .= "Avoids overwriting existing valid data\n\n";
            $error_msg .= "WHAT YOU CAN DO:\n";
            $error_msg .= "Option 1: If this is updated data\n";
            $error_msg .= "Rename the file to indicate it's an update\n";
            $error_msg .= "Example: {$year}_semester_{$semester}_updated.csv\n";
            $error_msg .= "Example: {$year}_semester_{$semester}_v2.csv\n\n";
            $error_msg .= "Option 2: If you need to replace the data\n";
            $error_msg .= "Contact system administrator to remove old data\n";
            $error_msg .= "Or use a different filename for the new upload\n\n";
            $error_msg .= "Option 3: If uploading to different semester\n";
            $error_msg .= "Select the correct semester from dropdown\n";
            $error_msg .= "Ensure filename matches the target semester\n";
            
            $stmt_check->close();
            throw new Exception($error_msg);
        }
        
        $stmt_check->close();
        
        error_log("Duplicate Check PASSED: File '{$filename}' is unique for Year {$year}, Semester {$semester}");

        // Extract semester indicators from filename
        $filename_lower = strtolower($filename);
        $detected_semesters = [];
        
        // Check for explicit semester patterns
        if (preg_match('/sem(?:ester)?[\s_-]*1|1st[\s_-]*sem(?:ester)?/i', $filename)) {
            $detected_semesters[] = '1';
        }
        if (preg_match('/sem(?:ester)?[\s_-]*2|2nd[\s_-]*sem(?:ester)?/i', $filename)) {
            $detected_semesters[] = '2';
        }
        
        // Additional patterns for semester detection
        if (preg_match('/_s1[_\.]|[\s_-]s1[_\.]|_1st[_\.]/', $filename_lower)) {
            if (!in_array('1', $detected_semesters)) {
                $detected_semesters[] = '1';
            }
        }
        if (preg_match('/_s2[_\.]|[\s_-]s2[_\.]|_2nd[_\.]/', $filename_lower)) {
            if (!in_array('2', $detected_semesters)) {
                $detected_semesters[] = '2';
            }
        }
        
        // STRICT VALIDATION: If semester is detected in filename, it MUST match selected semester
        if (!empty($detected_semesters)) {
            // Multiple semester indicators found - REJECT
            if (count($detected_semesters) > 1) {
                $semesters_list = implode(' and ', array_map(function($s) {
                    return $s == '1' ? '1st' : '2nd';
                }, $detected_semesters));
                
                $error_msg = "UPLOAD REJECTED - Multiple Semester Indicators in Filename\n\n";
                $error_msg .= "REASON: Filename contains indicators for multiple semesters\n\n";
                $error_msg .= "Current filename: {$filename}\n";
                $error_msg .= "Detected semesters: {$semesters_list}\n";
                $error_msg .= "Selected semester: " . ($semester == '1' ? '1st' : '2nd') . "\n";
                $error_msg .= "Target table: {$tableName}\n\n";
                $error_msg .= "REQUIRED ACTION:\n";
                $error_msg .= "Rename your file to include ONLY ONE semester indicator.\n\n";
                $error_msg .= "Correct naming examples:\n";
                $error_msg .= "students_{$year}_sem1.csv (for 1st semester)\n";
                $error_msg .= "students_{$year}_sem2.csv (for 2nd semester)\n";
                $error_msg .= "{$year}_1st_semester.csv\n";
                $error_msg .= "{$year}_2nd_semester.csv\n\n";
                $error_msg .= "WHY THIS IS REQUIRED:\n";
                $error_msg .= "Files like '1st_to_2nd_semester_migration.csv' are ambiguous.\n";
                $error_msg .= "Each upload must be clearly labeled for ONE specific semester.";
                
                throw new Exception($error_msg);
            }
            
            // Single semester detected - check if it matches selection
            $detected_semester = $detected_semesters[0];
            
            if ($detected_semester !== $semester) {
                $filename_sem_display = $detected_semester == '1' ? '1st' : '2nd';
                $selected_sem_display = $semester == '1' ? '1st' : '2nd';
                
                $error_msg = "UPLOAD REJECTED - Semester Mismatch\n\n";
                $error_msg .= "REASON: Selected semester does not match the semester in filename\n\n";
                $error_msg .= "Current filename: {$filename}\n";
                $error_msg .= "Filename semester: {$filename_sem_display} Semester\n";
                $error_msg .= "Selected semester: {$selected_sem_display} Semester\n";
                $error_msg .= "Target table: {$tableName}\n\n";
                $error_msg .= "REQUIRED ACTION - Choose ONE:\n\n";
                $error_msg .= "Option 1: Change semester dropdown to '{$filename_sem_display} Semester'\n";
                $error_msg .= "  This will upload to: student_{$year}_sem_{$detected_semester}\n\n";
                $error_msg .= "Option 2: Rename file to indicate '{$selected_sem_display} Semester'\n";
                $error_msg .= "  Example: students_{$year}_sem{$semester}.csv\n";
                $error_msg .= "  Example: {$year}_{$selected_sem_display}_semester.csv\n";
                $error_msg .= "  This will upload to: {$tableName}\n\n";
                $error_msg .= "WHY THIS IS REQUIRED:\n";
                $error_msg .= "This strict validation prevents uploading {$filename_sem_display} semester data\n";
                $error_msg .= "into {$selected_sem_display} semester table, which would corrupt your database\n";
                $error_msg .= "and affect prediction accuracy across semesters.";
                
                throw new Exception($error_msg);
            }
            
            error_log("Semester Validation PASSED: Filename semester '{$detected_semester}' matches selected semester");
        } else {
            // No semester indicator in filename - WARN but allow (soft validation)
            error_log("WARNING: No semester indicator found in filename '{$filename}' - proceeding with selected semester '{$semester}'");
            
            echo "<div class='alert alert-warning' style='margin-top: 10px;'>";
            echo "<i class='fa fa-exclamation-triangle'></i> <strong>Warning: Missing Semester Indicator</strong><br>";
            echo "Your filename does not contain a clear semester indicator (e.g., 'sem1', 'sem2', '1st_semester').<br>";
            echo "While this upload will proceed, we recommend naming files clearly to avoid confusion.<br><br>";
            echo "<strong>Recommended naming:</strong><br>";
            echo "students_{$year}_sem{$semester}.csv<br>";
            echo "{$year}_" . ($semester == '1' ? '1st' : '2nd') . "_semester.csv<br>";
            echo "</div>";
        }

        // Validate file size (10MB limit)
        $max_size = 10 * 1024 * 1024; // 10MB
        if ($_FILES["csvFile"]["size"] > $max_size) {
            throw new Exception("File size must be less than 10MB.");
        }

        // Create upload log table if it doesn't exist
        $createLogTableSQL = "CREATE TABLE IF NOT EXISTS upload_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            year INT,
            semester VARCHAR(10),
            table_name VARCHAR(100),
            filename VARCHAR(255),
            records_processed INT DEFAULT 0,
            records_success INT DEFAULT 0,
            records_error INT DEFAULT 0,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20),
            error_message TEXT NULL,
            uploaded_by_user_id INT NULL,
            uploaded_by_username VARCHAR(100) NULL,
            uploaded_by_name VARCHAR(255) NULL,
            user_ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            INDEX idx_uploaded_by (uploaded_by_user_id),
            INDEX idx_upload_date (upload_date)
        )";
        $conn->query($createLogTableSQL);

        // Enhanced CSV validation with content checking - STRICT MODE
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Read and validate headers
            $headers = fgetcsv($handle, 1000, ",");
            if (!$headers) {
                throw new Exception("CSV file is empty or has invalid headers.");
            }
        
            // Clean headers (remove BOM, trim whitespace)
            $headers = array_map(function($header) {
                return trim(str_replace("\xEF\xBB\xBF", '', $header));
            }, $headers);
        
            // Expected headers based on your CSV structure
            $expected_headers = ['id', 'StudentID', 'emailid', 'sname', 'about', 'contact', 'fees', 'year', 'balance', 'delete_status', 'course', 'Attendance', 'GPA'];
            
            // Check headers match
            $header_issues = [];
            if (count($headers) !== count($expected_headers)) {
                $header_issues[] = "Expected " . count($expected_headers) . " columns, found " . count($headers);
            }
            
            foreach ($expected_headers as $index => $expected) {
                if (!isset($headers[$index]) || strtolower(trim($headers[$index])) !== strtolower($expected)) {
                    $header_issues[] = "Column " . ($index + 1) . " should be '$expected', found '" . (isset($headers[$index]) ? $headers[$index] : 'missing') . "'";
                }
            }
            
            if (!empty($header_issues)) {
                throw new Exception("Header validation failed: " . implode('; ', $header_issues));
            }
        
            // ENHANCED STRICT Content validation with immediate rejection
            $row_count = 0;
            $content_issues = 0;
            $critical_issues = 0;
            $sample_issues = [];
            $critical_sample_issues = [];
            
            // ZERO tolerance for any data issues
            $max_allowed_critical_issues = 0;
            $max_allowed_content_issues = 0;
            
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (empty(array_filter($row))) continue;
                
                $row_count++;
                $row_issues = [];
                $critical_row_issues = [];
                $has_any_issue = false;
                
                // Validate each column with STRICT rules
                
                // Column 0: id (should be numeric if provided)
                $id_value = trim($row[0] ?? '');
                if (!empty($id_value) && !is_numeric($id_value)) {
                    $row_issues[] = "ID should be numeric, found: '$id_value'";
                    $has_any_issue = true;
                }
                
                // Column 1: StudentID (CRITICAL - must be present and follow format)
                $studentID = trim($row[1] ?? '');
                if (empty($studentID)) {
                    $critical_row_issues[] = "StudentID is required but empty";
                    $row_issues[] = "StudentID is required";
                    $has_any_issue = true;
                } elseif (!preg_match('/^OLFU\d{4}/', $studentID)) {
                    $critical_row_issues[] = "StudentID format invalid: '$studentID' (should be OLFU followed by 4 digits)";
                    $row_issues[] = "StudentID format invalid: '$studentID'";
                    $has_any_issue = true;
                }
                
                // Column 2: emailid (should be valid email format if not empty)
                $email = trim($row[2] ?? '');
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $row_issues[] = "Invalid email format: '$email'";
                    $has_any_issue = true;
                }
                
                // Column 3: sname (CRITICAL - student name required)
                $sname = trim($row[3] ?? '');
                if (empty($sname)) {
                    $critical_row_issues[] = "Student name is required but empty";
                    $row_issues[] = "Student name is required";
                    $has_any_issue = true;
                } elseif (strlen($sname) < 2) {
                    $critical_row_issues[] = "Student name too short: '$sname' (minimum 2 characters)";
                    $row_issues[] = "Student name too short: '$sname'";
                    $has_any_issue = true;
                } elseif (!preg_match('/^[a-zA-Z\s\.\-]+$/', $sname)) {
                    $row_issues[] = "Student name contains invalid characters: '$sname'";
                    $has_any_issue = true;
                }
                
                // Column 5: contact (should be numeric if not empty)
                $contact = trim($row[5] ?? '');
                if (!empty($contact)) {
                    $clean_contact = preg_replace('/[^0-9]/', '', $contact);
                    if (strlen($clean_contact) < 10 || strlen($clean_contact) > 15) {
                        $row_issues[] = "Contact number invalid length: '$contact' (should be 10-15 digits)";
                        $has_any_issue = true;
                    }
                }
                
                // Column 6: fees (should be numeric and reasonable if not empty)
                $fees = trim($row[6] ?? '');
                if (!empty($fees)) {
                    if (!is_numeric($fees)) {
                        $row_issues[] = "Fees should be numeric, found: '$fees'";
                        $has_any_issue = true;
                    } else {
                        $fees_value = floatval($fees);
                        if ($fees_value < 0) {
                            $critical_row_issues[] = "Fees cannot be negative: '$fees'";
                            $row_issues[] = "Fees cannot be negative: '$fees'";
                            $has_any_issue = true;
                        } elseif ($fees_value > 200000) {
                            $row_issues[] = "Fees value seems too high: '$fees' (over 200,000)";
                            $has_any_issue = true;
                        }
                    }
                }
                
                // Column 7: year (should be numeric, 1-6 for grade levels)
                $year_level = trim($row[7] ?? '');
                if (!empty($year_level)) {
                    if (!is_numeric($year_level)) {
                        $row_issues[] = "Year level should be numeric, found: '$year_level'";
                        $has_any_issue = true;
                    } else {
                        $year_value = intval($year_level);
                        if ($year_value < 1 || $year_value > 6) {
                            $row_issues[] = "Year level out of range: '$year_level' (should be 1-6)";
                            $has_any_issue = true;
                        }
                    }
                }
                
                // Column 8: balance (should be numeric if not empty)
                $balance = trim($row[8] ?? '');
                if (!empty($balance) && !is_numeric($balance)) {
                    $row_issues[] = "Balance should be numeric, found: '$balance'";
                    $has_any_issue = true;
                }
                
                // Column 9: delete_status (should be 0 or 1)
                $delete_status = trim($row[9] ?? '');
                if (!empty($delete_status) && !in_array($delete_status, ['0', '1'])) {
                    $row_issues[] = "Delete status should be 0 or 1, found: '$delete_status'";
                    $has_any_issue = true;
                }
                
                // Column 10: course (CRITICAL - required)
                $course = trim($row[10] ?? '');
                if (empty($course)) {
                    $critical_row_issues[] = "Course is required but empty";
                    $row_issues[] = "Course is required";
                    $has_any_issue = true;
                } elseif (strlen($course) < 2) {
                    $critical_row_issues[] = "Course name too short: '$course' (minimum 2 characters)";
                    $row_issues[] = "Course name too short: '$course'";
                    $has_any_issue = true;
                }
                
                // Column 11: Attendance (should be 0-100 if numeric)
                $attendance = trim($row[11] ?? '');
                if (!empty($attendance)) {
                    if (!is_numeric($attendance)) {
                        $row_issues[] = "Attendance should be numeric, found: '$attendance'";
                        $has_any_issue = true;
                    } else {
                        $attendance_value = floatval($attendance);
                        if ($attendance_value < 0 || $attendance_value > 100) {
                            $row_issues[] = "Attendance out of range: '$attendance' (should be 0-100)";
                            $has_any_issue = true;
                        }
                    }
                }
                
                // Column 12: GPA (should be 1.0-5.0 if numeric)
                $gpa = trim($row[12] ?? '');
                if (!empty($gpa)) {
                    if (!is_numeric($gpa)) {
                        $row_issues[] = "GPA should be numeric, found: '$gpa'";
                        $has_any_issue = true;
                    } else {
                        $gpa_value = floatval($gpa);
                        if ($gpa_value < 1.0 || $gpa_value > 5.0) {
                            $row_issues[] = "GPA out of range: '$gpa' (should be 1.0-5.0)";
                            $has_any_issue = true;
                        }
                    }
                }
                
                // Count issues
                if ($has_any_issue) {
                    $content_issues++;
                }
                
                if (!empty($critical_row_issues)) {
                    $critical_issues++;
                    if (count($critical_sample_issues) < 10) {
                        $critical_sample_issues[] = "Row " . ($row_count + 1) . ": " . implode(', ', $critical_row_issues);
                    }
                }
                
                if (!empty($row_issues) && count($sample_issues) < 10) {
                    $sample_issues[] = "Row " . ($row_count + 1) . ": " . implode(', ', $row_issues);
                }
                
                // EARLY TERMINATION: If any issues found, stop processing
                if ($content_issues > 0 || $critical_issues > 0) {
                    break;
                }
            }
        
            if ($row_count === 0) {
                throw new Exception("CSV file contains no data rows.");
            }
        
            // STRICT VALIDATION: Reject if ANY issues found
            $should_reject = false;
            $rejection_reasons = [];
        
            // Check for ANY issues (ZERO tolerance)
            if ($critical_issues > $max_allowed_critical_issues) {
                $should_reject = true;
                $rejection_reasons[] = "CRITICAL: Found $critical_issues rows with required field violations (StudentID, Name, or Course missing/invalid)";
            }
        
            if ($content_issues > $max_allowed_content_issues) {
                $should_reject = true;
                $rejection_reasons[] = "DATA QUALITY: Found $content_issues rows with data format issues - NO issues are allowed";
            }
        
            // Additional business logic checks
            if ($row_count < 5) {
                $should_reject = true;
                $rejection_reasons[] = "FILE SIZE: Too few records - only $row_count rows found (minimum 5 required for upload)";
            }
        
            if ($row_count > 10000) {
                $should_reject = true;
                $rejection_reasons[] = "FILE SIZE: Too many records - $row_count rows found (maximum 10,000 allowed per upload)";
            }
        
            // STRICT REJECTION: If any criteria fails, reject the upload
            if ($should_reject) {
                $rejection_message = "UPLOAD REJECTED - File does not meet strict quality standards\n\n";
                $rejection_message .= "REJECTION REASONS:\n";
                foreach ($rejection_reasons as $reason) {
                    $rejection_message .= " $reason\n";
                }
                
                $rejection_message .= "\nFILE STATISTICS:\n";
                $rejection_message .= "Total rows: $row_count\n";
                $rejection_message .= "Rows with issues: $content_issues\n";
                $rejection_message .= "Critical issues: $critical_issues\n";
                
                if (!empty($critical_sample_issues)) {
                    $rejection_message .= "\nCRITICAL ISSUES EXAMPLES:\n";
                    foreach (array_slice($critical_sample_issues, 0, 5) as $issue) {
                        $rejection_message .= " $issue\n";
                    }
                    if (count($critical_sample_issues) > 5) {
                        $rejection_message .= " ... and " . (count($critical_sample_issues) - 5) . " more critical issues\n";
                    }
                }
                
                if (!empty($sample_issues)) {
                    $rejection_message .= "\nDATA FORMAT ISSUES EXAMPLES:\n";
                    foreach (array_slice($sample_issues, 0, 3) as $issue) {
                        $rejection_message .= " $issue\n";
                    }
                    if (count($sample_issues) > 3) {
                        $rejection_message .= " ... and " . (count($sample_issues) - 3) . " more issues\n";
                    }
                }
                
                $rejection_message .= "\n HOW TO FIX:\n";
                $rejection_message .= " Ensure all StudentIDs follow OLFU format (e.g., OLFU2025)\n";
                $rejection_message .= " Fill in all required fields: StudentID, Student Name, Course\n";
                $rejection_message .= " Verify numeric fields (fees, balance, attendance, GPA) contain valid numbers\n";
                $rejection_message .= " Check email addresses are in correct format\n";
                $rejection_message .= " Ensure year levels are between 1-6\n";
                $rejection_message .= " Fix ALL data format issues - NO issues are allowed for upload\n";
                
                throw new Exception($rejection_message);
            }
        
            fclose($handle);
        
            // If we reach here, the file has PERFECT data quality
            echo "<div class='alert alert-success'>";
            echo "<h4><i class='fa fa-check-circle'></i>  File Validation PASSED</h4>";
            echo "<p><strong>Total rows:</strong> $row_count</p>";
            echo "<p><strong>Data quality:</strong> <span style='color: green; font-weight: bold;'>PERFECT - No issues detected</span></p>";
            echo "<p><strong>Uploaded by:</strong> $uploaded_by_name ($uploaded_by_username)</p>";
            echo "</div>";
        
        } else {
            throw new Exception("Error reading the CSV file. Please ensure the file is not corrupted.");
        }

        // Ensure table exists with correct structure (including semester column)
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `$tableName` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            StudentID VARCHAR(255) UNIQUE,
            sname VARCHAR(100),
            emailid VARCHAR(100),
            joindate DATETIME,
            about TEXT NULL,
            contact VARCHAR(20),
            fees FLOAT DEFAULT NULL,
            year INT,
            semester VARCHAR(10),
            balance FLOAT DEFAULT NULL,
            delete_status TINYINT DEFAULT 0,
            course VARCHAR(100),
            Attendance FLOAT DEFAULT NULL,
            GPA FLOAT DEFAULT NULL,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            uploaded_by_user_id INT NULL,
            uploaded_by_username VARCHAR(100) NULL,
            INDEX idx_student_id (StudentID),
            INDEX idx_year_semester (year, semester),
            INDEX idx_uploaded_by (uploaded_by_user_id)
        )";

        if (!$conn->query($createTableSQL)) {
            throw new Exception("Error creating table: " . $conn->error);
        }

        // Process the actual upload
        if (($handle = fopen($file, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ","); // Skip the header row
            $row_number = 1;
            
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row_number++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Map CSV columns to variables
                $id = isset($row[0]) ? $conn->real_escape_string(trim($row[0])) : '';
                $studentID = isset($row[1]) ? $conn->real_escape_string(trim($row[1])) : '';
                $emailid = isset($row[2]) ? $conn->real_escape_string(trim($row[2])) : '';
                $sname = isset($row[3]) ? $conn->real_escape_string(trim($row[3])) : '';
                
                $about = isset($row[4]) && !empty(trim($row[4])) ? 
                        "'" . $conn->real_escape_string(trim($row[4])) . "'" : "NULL";
                $contact = isset($row[5]) ? $conn->real_escape_string(trim($row[5])) : '';
                $fees = isset($row[6]) && is_numeric(trim($row[6])) ? 
                       floatval(trim($row[6])) : "NULL";
                $year_level = isset($row[7]) && is_numeric(trim($row[7])) ? 
                             intval(trim($row[7])) : "NULL";
                $balance = isset($row[8]) && is_numeric(trim($row[8])) ? 
                          floatval(trim($row[8])) : "NULL";
                $delete_status = isset($row[9]) && is_numeric(trim($row[9])) ? 
                                intval(trim($row[9])) : 0;
                $course = isset($row[10]) ? $conn->real_escape_string(trim($row[10])) : '';
                $attendance = isset($row[11]) && is_numeric(trim($row[11])) ? 
                             floatval(trim($row[11])) : "NULL";
                $gpa = isset($row[12]) && is_numeric(trim($row[12])) ? 
                      floatval(trim($row[12])) : "NULL";

                // Skip rows without required data
                if (empty($studentID) || empty($sname)) {
                    $error_count++;
                    $error_messages[] = "Row $row_number: Missing required data (StudentID or Name)";
                    continue;
                }

                // Insert or update student record
                $sql = "INSERT INTO `$tableName` (id, StudentID, sname, emailid, about, contact, fees, year, semester, balance, delete_status, course, Attendance, GPA, uploaded_by_user_id, uploaded_by_username) 
                        VALUES ('$id', '$studentID', '$sname', '$emailid', $about, '$contact', $fees, $year_level, '$semester', $balance, $delete_status, '$course', $attendance, $gpa, " . 
                        ($uploaded_by_user_id ? "'$uploaded_by_user_id'" : "NULL") . ", '$uploaded_by_username') 
                        ON DUPLICATE KEY UPDATE 
                        StudentID=VALUES(StudentID), sname=VALUES(sname), emailid=VALUES(emailid), 
                        about=VALUES(about), contact=VALUES(contact), fees=VALUES(fees), year=VALUES(year), semester=VALUES(semester), balance=VALUES(balance), 
                        delete_status=VALUES(delete_status), course=VALUES(course), Attendance=VALUES(Attendance), GPA=VALUES(GPA), 
                        upload_date=CURRENT_TIMESTAMP, uploaded_by_user_id=VALUES(uploaded_by_user_id), uploaded_by_username=VALUES(uploaded_by_username)";

                if ($conn->query($sql)) {
                    $success_count++;
                } else {
                    $error_count++;
                    $error_messages[] = "Row $row_number: " . $conn->error;
                    error_log("CSV Upload Error - Row $row_number: " . $conn->error);
                }
            }
            
            fclose($handle);

            // Display results
            if ($success_count > 0) {
                $upload_success = true;
                
                $semester_display = ($semester == '1') ? '1st Semester' : '2nd Semester';
                
                echo "<div class='alert alert-success'>";
                echo "<strong>Upload Successful!</strong> ";
                echo "CSV data uploaded to table: {$tableName} | ";
                echo "Year: {$year} | Semester: {$semester_display} | ";
                echo "Records processed: " . ($success_count + $error_count) . " | ";
                echo "Successfully imported: {$success_count}";
                if ($error_count > 0) {
                    echo " | Errors: {$error_count}";
                }
                echo "<br><strong>Uploaded by:</strong> $uploaded_by_name ($uploaded_by_username)";
                echo "<br><strong>Data Quality:</strong> PERFECT - All validation checks passed";
                echo "<br><strong>Upload Protection:</strong>  Year verified,  Semester validated,  No duplicates";
                echo "</div>";

                // Show first few errors if any
                if (!empty($error_messages) && count($error_messages) <= 5) {
                    echo "<div class='alert alert-warning'>";
                    echo "<strong>Processing Warnings:</strong> ";
                    foreach (array_slice($error_messages, 0, 3) as $error) {
                        echo htmlspecialchars($error) . "; ";
                    }
                    echo "</div>";
                }
            } else {
                throw new Exception("No records were successfully processed. Please check your CSV file format.");
            }
        }

        // Log the upload activity
        $status = $upload_success ? 'success' : 'failed';
        $error_message = !empty($error_messages) ? implode('; ', array_slice($error_messages, 0, 3)) : NULL;
        
        $log_sql = "INSERT INTO upload_logs (year, semester, table_name, filename, records_processed, records_success, records_error, status, error_message, uploaded_by_user_id, uploaded_by_username, uploaded_by_name, user_ip_address, user_agent)
                    VALUES ('$year', '$semester', '$tableName', '" . $conn->real_escape_string($_FILES["csvFile"]["name"]) . "',
                    " . ($success_count + $error_count) . ", $success_count, $error_count, '$status', " .
                    ($error_message ? "'" . $conn->real_escape_string($error_message) . "'" : "NULL") . ", " .
                    ($uploaded_by_user_id ? "'$uploaded_by_user_id'" : "NULL") . ", '$uploaded_by_username', '$uploaded_by_name', '{$_SERVER['REMOTE_ADDR']}', '" . $conn->real_escape_string($_SERVER['HTTP_USER_AGENT']) . "')";
        $conn->query($log_sql);
        
        // ===================================================================
        // TRIGGER PYTHON PREDICTION AFTER SUCCESSFUL UPLOAD
        // ===================================================================
  // After successful upload, replace the prediction trigger section with:

if ($upload_success) {
    echo "<div class='alert alert-info' style='margin-top: 15px;'>";
    echo "<i class='fa fa-cog fa-spin'></i> <strong>Initializing Automatic Prediction System...</strong>";
    echo "</div>";
    
    try {
        global $conn;
        
        // Create prediction_requests table if not exists
        $create_requests_table = "CREATE TABLE IF NOT EXISTS prediction_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            year INT,
            semester VARCHAR(10),
            table_name VARCHAR(100),
            records_count INT,
            uploaded_by_user_id INT NULL,
            uploaded_by_username VARCHAR(100),
            status VARCHAR(50) DEFAULT 'pending',
            prediction_started_at TIMESTAMP NULL,
            prediction_completed_at TIMESTAMP NULL,
            predictions_generated INT DEFAULT 0,
            error_message TEXT NULL,
            trigger_method VARCHAR(50) NULL,
            INDEX idx_status (status),
            INDEX idx_timestamp (request_timestamp)
        )";
        $conn->query($create_requests_table);
        
        // Insert prediction request
        $insert_request = "INSERT INTO prediction_requests 
            (year, semester, table_name, records_count, uploaded_by_user_id, uploaded_by_username, status)
            VALUES 
            ('$year', '$semester', '$tableName', $success_count, " . 
            ($uploaded_by_user_id ? "'$uploaded_by_user_id'" : "NULL") . ", 
            '$uploaded_by_username', 'pending')";
        $conn->query($insert_request);
        $prediction_request_id = $conn->insert_id;
        
        // Trigger prediction system
        $trigger_result = trigger_prediction_auto(
            $year, 
            $semester, 
            $tableName, 
            $success_count, 
            $filename,
            $uploaded_by_name, 
            $uploaded_by_username, 
            $uploaded_by_user_id
        );
        
        if ($trigger_result['success']) {
            $update_method = "UPDATE prediction_requests 
                SET status = 'processing', 
                    trigger_method = '{$trigger_result['method']}',
                    prediction_started_at = NOW()
                WHERE id = $prediction_request_id";
            $conn->query($update_method);
            
            echo "<div class='alert alert-success' style='margin-top: 10px;'>";
            echo "<i class='fa fa-check-circle'></i> <strong>Prediction System Activated!</strong><br>";
            echo "<small>Method: {$trigger_result['method']}</small><br>";
            echo "<small>{$trigger_result['message']}</small><br>";
            
            if ($trigger_result['method'] === 'watcher_detected') {
                echo "<br><strong>Status:</strong> Watcher detected - predictions will start automatically within 10 seconds";
            } elseif ($trigger_result['method'] === 'watcher_started') {
                echo "<br><strong>Status:</strong> Prediction watcher started - monitoring for completion";
            } elseif ($trigger_result['method'] === 'direct_execution') {
                echo "<br><strong>Status:</strong> Prediction running directly in background";
            } else {
                echo "<br><strong>Status:</strong> Trigger file created - manual start required";
                echo "<br><small>Run: <code>python prediction_watcher.py</code> or <code>python predict.py</code></small>";
            }
            
            echo "</div>";
            
            // Real-time status checker
            echo "<div id='prediction-status' class='alert alert-info' style='margin-top: 10px;'>";
            echo "<i class='fa fa-hourglass-half fa-spin'></i> <strong>Monitoring prediction progress...</strong>";
            echo "<div id='status-details' style='margin-top: 10px; font-size: 13px;'></div>";
            echo "</div>";
            
            // Enhanced JavaScript monitoring
            echo "<script>
            var requestId = $prediction_request_id;
            var checkAttempts = 0;
            var maxAttempts = 60; // 10 minutes
            var lastStatus = 'pending';
            
            function updateStatusDisplay(status, details) {
                var statusDiv = document.getElementById('status-details');
                var timestamp = new Date().toLocaleTimeString();
                
                var statusIcon = status === 'processing' ? 'âš™ï¸' : 
                                status === 'completed' ? 'âœ…' : 
                                status === 'failed' ? 'âŒ' : 'â³';
                
                statusDiv.innerHTML = statusIcon + ' [' + timestamp + '] ' + details;
            }
            
            var checkInterval = setInterval(function() {
                checkAttempts++;
                
                fetch('check_prediction_status.php?request_id=' + requestId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status !== lastStatus) {
                            lastStatus = data.status;
                            
                            if (data.status === 'processing') {
                                updateStatusDisplay('processing', 
                                    'Prediction in progress... Processing ' + data.records_count + ' students');
                            }
                        }
                        
                        if (data.status === 'completed') {
                            clearInterval(checkInterval);
                            document.getElementById('prediction-status').className = 'alert alert-success';
                            document.getElementById('prediction-status').innerHTML = 
                                '<i class=\"fa fa-check-circle\"></i> <strong>âœ… Predictions Completed Successfully!</strong><br>' +
                                '<div style=\"margin-top: 10px;\">' +
                                'âœ“ Generated ' + data.predictions_count + ' predictions<br>' +
                                'âœ“ Duration: ' + data.duration + '<br>' +
                                'âœ“ Method: ' + data.trigger_method + '<br><br>' +
                                '<a href=\"gpa.php\" class=\"btn btn-primary btn-sm\">' +
                                '<i class=\"fa fa-eye\"></i> View Predictions Now</a>' +
                                '</div>';
                        } else if (data.status === 'failed') {
                            clearInterval(checkInterval);
                            document.getElementById('prediction-status').className = 'alert alert-danger';
                            document.getElementById('prediction-status').innerHTML = 
                                '<i class=\"fa fa-times-circle\"></i> <strong>Prediction Failed</strong><br>' +
                                '<small>' + (data.error_message || 'Unknown error') + '</small>';
                        } else if (checkAttempts >= maxAttempts) {
                            clearInterval(checkInterval);
                            document.getElementById('prediction-status').className = 'alert alert-warning';
                            document.getElementById('prediction-status').innerHTML = 
                                '<i class=\"fa fa-clock-o\"></i> <strong>Monitoring Timeout</strong><br>' +
                                'Prediction may still be running. Check the Predictions page.';
                        }
                    })
                    .catch(error => {
                        console.error('Status check error:', error);
                        if (checkAttempts >= maxAttempts) {
                            clearInterval(checkInterval);
                        }
                    });
            }, 10000); // Check every 10 seconds
            </script>";
            
        } else {
            echo "<div class='alert alert-warning' style='margin-top: 10px;'>";
            echo "<i class='fa fa-exclamation-triangle'></i> <strong>Prediction Setup Warning</strong><br>";
            echo htmlspecialchars($trigger_result['message']) . "<br><br>";
            echo "<strong>Manual Start Options:</strong><br>";
            echo "1. Start watcher: <code>python prediction_watcher.py</code><br>";
            echo "2. Run directly: <code>python predict.py</code>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger' style='margin-top: 10px;'>";
        echo "<i class='fa fa-times-circle'></i> <strong>Prediction System Error:</strong><br>";
        echo htmlspecialchars($e->getMessage());
        echo "</div>";
        error_log("Prediction trigger error: " . $e->getMessage());
    }
}
        
    } catch (Exception $e) {
        // Main exception handler
        echo "<div class='alert alert-danger'>";
        echo "<strong>Upload Failed!</strong><br><br>";
        
        $error_message = $e->getMessage();
        
        // Special formatting for validation errors
        if (strpos($error_message, 'UPLOAD REJECTED') !== false) {
            echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 10px; border-left: 5px solid #dc3545;'>";
            echo "<i class='fa fa-ban' style='color: #dc3545; font-size: 24px;'></i><br><br>";
            echo "<pre style='white-space: pre-wrap; font-family: monospace; font-size: 12px; background-color: #fff; padding: 10px; border-radius: 3px;'>";
            echo htmlspecialchars($error_message);
            echo "</pre>";
            echo "</div>";
            
            echo "<div style='margin-top: 15px; padding: 10px; background-color: #d4edda; border-radius: 5px;'>";
            echo "<i class='fa fa-lightbulb-o' style='color: #155724;'></i> <strong>Quick Tips:</strong><br>";
            echo "<ul style='margin-top: 5px; font-size: 12px;'>";
            echo "<li>Always include the year in your CSV filename</li>";
            echo "<li>Make sure the year in filename matches your dropdown selection</li>";
            echo "<li>This validation protects your data integrity</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo nl2br(htmlspecialchars($error_message));
        }
        
        echo "</div>";
        
        // Log the error
        if (isset($conn)) {
            $escaped_year = $conn->real_escape_string($year ?? '');
            $escaped_semester = $conn->real_escape_string($semester ?? '');
            $escaped_tablename = isset($tableName) ? $conn->real_escape_string($tableName) : '';
            $escaped_filename = isset($filename) ? $conn->real_escape_string($filename) : 'unknown';
            $escaped_error = $conn->real_escape_string($e->getMessage());
            
            $log_sql = "INSERT INTO upload_logs (year, semester, table_name, filename, records_processed, records_success, records_error, status, error_message, uploaded_by_user_id, uploaded_by_username, uploaded_by_name, user_ip_address, user_agent)
                        VALUES ('$escaped_year', '$escaped_semester', '$escaped_tablename', '$escaped_filename', 0, 0, 1, 'failed', '{$escaped_error}', " .
                        ($uploaded_by_user_id ? "'$uploaded_by_user_id'" : "NULL") . ", '$uploaded_by_username', '$uploaded_by_name', '{$_SERVER['REMOTE_ADDR']}', '" . $conn->real_escape_string($_SERVER['HTTP_USER_AGENT']) . "')";
            $conn->query($log_sql);
        }
        
        error_log("CSV Upload Error: " . $e->getMessage());
    }
    
} else {
    echo "<div class='alert alert-danger'>";
    echo "<strong>Invalid Request!</strong><br>";
    echo "Please ensure all required fields are provided: Year, Semester, and CSV file.";
    echo "</div>";
}

if (isset($conn)) {
    $conn->close();
}
?>