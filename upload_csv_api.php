<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("php/dbconnect.php");

/**
 * FAST API-BASED PREDICTION TRIGGER
 * Replaces file-watching with direct API call
 */
function trigger_prediction_via_api($year, $semester, $tableName, $success_count, $uploaded_by_name, $uploaded_by_username, $uploaded_by_user_id) {
    // API Configuration
    $API_URL = 'http://localhost:5000/api/predict';
    $API_KEY = 'dev_key_12345';  // CHANGE THIS to your actual API key
    
    $result = [
        'success' => false,
        'message' => '',
        'method' => 'api',
        'execution_time' => 0
    ];
    
    try {
        $start_time = microtime(true);
        
        // Prepare API request
        $request_data = [
            'table_name' => $tableName,
            'year' => intval($year),
            'semester' => $semester,
            'uploaded_by' => $uploaded_by_name,
            'uploaded_by_username' => $uploaded_by_username,
            'uploaded_by_user_id' => $uploaded_by_user_id,
            'records_uploaded' => intval($success_count)
        ];
        
        error_log("🚀 Calling Prediction API...");
        error_log("   URL: $API_URL");
        error_log("   Data: " . json_encode($request_data));
        
        // Initialize cURL
        $ch = curl_init($API_URL);
        
        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($request_data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $API_KEY
            ],
            CURLOPT_TIMEOUT => 300,  // 5 minute timeout
            CURLOPT_CONNECTTIMEOUT => 10
        ]);
        
        // Execute API call
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        $execution_time = microtime(true) - $start_time;
        $result['execution_time'] = round($execution_time, 2);
        
        // Check for cURL errors
        if ($curl_error) {
            throw new Exception("API connection error: $curl_error");
        }
        
        // Parse response
        $response_data = json_decode($response, true);
        
        if ($http_code !== 200) {
            $error_msg = isset($response_data['error']) ? $response_data['error'] : 'Unknown API error';
            throw new Exception("API error (HTTP $http_code): $error_msg");
        }
        
        if (!$response_data || !isset($response_data['success'])) {
            throw new Exception("Invalid API response format");
        }
        
        if ($response_data['success']) {
            $result['success'] = true;
            $result['message'] = "Predictions generated successfully";
            $result['predictions_count'] = $response_data['predictions_generated'] ?? 0;
            $result['students_processed'] = $response_data['students_processed'] ?? 0;
            $result['api_execution_time'] = $response_data['execution_time'] ?? 0;
            
            error_log("✅ API call successful!");
            error_log("   Predictions: " . $result['predictions_count']);
            error_log("   Execution time: " . $result['api_execution_time'] . "s");
        } else {
            throw new Exception($response_data['message'] ?? 'Prediction failed');
        }
        
    } catch (Exception $e) {
        $result['success'] = false;
        $result['message'] = $e->getMessage();
        error_log("❌ API call failed: " . $e->getMessage());
    }
    
    return $result;
}

/**
 * Extract year from filename with STRICT validation
 */
function extractYearFromFilename($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    preg_match_all('/\d{4}/', $name, $all_matches);
    
    $found_years = [];
    if (!empty($all_matches[0])) {
        foreach ($all_matches[0] as $match) {
            $year = intval($match);
            if ($year >= 2020 && $year <= 2030) {
                if (!in_array($year, $found_years)) {
                    $found_years[] = $year;
                }
            }
        }
    }
    
    return [
        'year' => !empty($found_years) ? $found_years[0] : null,
        'all_years' => $found_years,
        'year_count' => count($found_years),
        'has_multiple_years' => count($found_years) > 1,
        'has_no_year' => empty($found_years)
    ];
}

// ============================================================================
// MAIN UPLOAD PROCESSING
// ============================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csvFile"]) && isset($_POST["year"]) && isset($_POST["semester"])) {
    $year = $conn->real_escape_string($_POST["year"]);
    $semester = $conn->real_escape_string($_POST["semester"]);
    
    // Get current user info
    $uploaded_by_user_id = isset($_SESSION['rainbow_uid']) ? $_SESSION['rainbow_uid'] : null;
    $uploaded_by_username = isset($_SESSION['rainbow_username']) ? $conn->real_escape_string($_SESSION['rainbow_username']) : 'unknown';
    $uploaded_by_name = isset($_SESSION['rainbow_name']) ? $conn->real_escape_string($_SESSION['rainbow_name']) : 'Unknown User';
    
    // Create dynamic table name
    $tableName = "student_" . $year . "_sem_" . $semester;
    $file = $_FILES["csvFile"]["tmp_name"];
    $filename = $_FILES["csvFile"]["name"];
    $upload_success = false;
    $error_messages = [];
    $success_count = 0;
    $error_count = 0;

    try {
        // Validate semester
        $valid_semesters = ['1', '2'];
        if (!in_array($semester, $valid_semesters)) {
            throw new Exception("Invalid semester selected. Only 1st and 2nd semester are allowed.");
        }

        // Validate year
        if (!is_numeric($year) || $year < 2020 || $year > 2025) {
            throw new Exception("Invalid year selected. Only years 2020-2025 are allowed.");
        }
        
        // STRICT year validation
        $year_info = extractYearFromFilename($filename);
        $selected_year = intval($year);
        
        if ($year_info['has_no_year']) {
            throw new Exception("❌ UPLOAD REJECTED - Missing Year in Filename\n\nFilename must contain the year (e.g., students_$selected_year.csv)");
        }
        
        if ($year_info['has_multiple_years']) {
            throw new Exception("❌ UPLOAD REJECTED - Multiple Years Detected in Filename\n\nFilename must contain ONLY ONE year");
        }
        
        if ($year_info['year'] !== $selected_year) {
            throw new Exception("❌ UPLOAD REJECTED - Year Mismatch\n\nFilename year ({$year_info['year']}) does not match selected year ($selected_year)");
        }
        
        error_log("✅ Year validation passed: $selected_year");

        // Check file upload errors
        if ($_FILES["csvFile"]["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error occurred.");
        }

        // Validate file type
        $file_info = pathinfo($_FILES["csvFile"]["name"]);
        if (strtolower($file_info['extension']) !== 'csv') {
            throw new Exception("Only CSV files are allowed.");
        }
        
        // Check for duplicate uploads
        $check_duplicate_query = "SELECT log_id, filename, upload_date, records_success, uploaded_by_name 
                                  FROM upload_logs 
                                  WHERE filename = ? AND year = ? AND semester = ? AND status = 'success'
                                  ORDER BY upload_date DESC LIMIT 1";
        
        $stmt_check = $conn->prepare($check_duplicate_query);
        $stmt_check->bind_param("sss", $filename, $year, $semester);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $existing = $result_check->fetch_assoc();
            $existing_date = date('F j, Y \a\t g:i A', strtotime($existing['upload_date']));
            throw new Exception("❌ DUPLICATE FILE - Already uploaded on $existing_date by {$existing['uploaded_by_name']}");
        }
        $stmt_check->close();

        // Validate file size (10MB limit)
        $max_size = 10 * 1024 * 1024;
        if ($_FILES["csvFile"]["size"] > $max_size) {
            throw new Exception("File size must be less than 10MB.");
        }

        // CSV validation
        if (($handle = fopen($file, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ",");
            if (!$headers) {
                throw new Exception("CSV file is empty or has invalid headers.");
            }
        
            $headers = array_map(function($header) {
                return trim(str_replace("\xEF\xBB\xBF", '', $header));
            }, $headers);
        
            $expected_headers = ['id', 'StudentID', 'emailid', 'sname', 'about', 'contact', 'fees', 'year', 'balance', 'delete_status', 'course', 'Attendance', 'GPA'];
            
            if (count($headers) !== count($expected_headers)) {
                throw new Exception("Header validation failed: Expected " . count($expected_headers) . " columns, found " . count($headers));
            }
            
            // Basic content validation
            $row_count = 0;
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (empty(array_filter($row))) continue;
                $row_count++;
                
                // Just check StudentID and name exist
                $studentID = trim($row[1] ?? '');
                $sname = trim($row[3] ?? '');
                
                if (empty($studentID) || empty($sname)) {
                    throw new Exception("Row $row_count: Missing StudentID or Name");
                }
            }
        
            if ($row_count === 0) {
                throw new Exception("CSV file contains no data rows.");
            }
            
            if ($row_count < 5) {
                throw new Exception("Too few records - minimum 5 required");
            }
        
            fclose($handle);
        
            echo "<div class='alert alert-success'>";
            echo "<h4><i class='fa fa-check-circle'></i> ✅ File Validation PASSED</h4>";
            echo "<p><strong>Total rows:</strong> $row_count</p>";
            echo "</div>";
        }

        // Create table if not exists
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

        // Process CSV upload
        if (($handle = fopen($file, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ",");
            $row_number = 1;
            
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row_number++;
                if (empty(array_filter($row))) continue;
                
                // Map columns
                $id = isset($row[0]) ? $conn->real_escape_string(trim($row[0])) : '';
                $studentID = isset($row[1]) ? $conn->real_escape_string(trim($row[1])) : '';
                $emailid = isset($row[2]) ? $conn->real_escape_string(trim($row[2])) : '';
                $sname = isset($row[3]) ? $conn->real_escape_string(trim($row[3])) : '';
                $about = isset($row[4]) && !empty(trim($row[4])) ? "'" . $conn->real_escape_string(trim($row[4])) . "'" : "NULL";
                $contact = isset($row[5]) ? $conn->real_escape_string(trim($row[5])) : '';
                $fees = isset($row[6]) && is_numeric(trim($row[6])) ? floatval(trim($row[6])) : "NULL";
                $year_level = isset($row[7]) && is_numeric(trim($row[7])) ? intval(trim($row[7])) : "NULL";
                $balance = isset($row[8]) && is_numeric(trim($row[8])) ? floatval(trim($row[8])) : "NULL";
                $delete_status = isset($row[9]) && is_numeric(trim($row[9])) ? intval(trim($row[9])) : 0;
                $course = isset($row[10]) ? $conn->real_escape_string(trim($row[10])) : '';
                $attendance = isset($row[11]) && is_numeric(trim($row[11])) ? floatval(trim($row[11])) : "NULL";
                $gpa = isset($row[12]) && is_numeric(trim($row[12])) ? floatval(trim($row[12])) : "NULL";

                if (empty($studentID) || empty($sname)) {
                    $error_count++;
                    continue;
                }

                $sql = "INSERT INTO `$tableName` (id, StudentID, sname, emailid, about, contact, fees, year, semester, balance, delete_status, course, Attendance, GPA, uploaded_by_user_id, uploaded_by_username) 
                        VALUES ('$id', '$studentID', '$sname', '$emailid', $about, '$contact', $fees, $year_level, '$semester', $balance, $delete_status, '$course', $attendance, $gpa, " . 
                        ($uploaded_by_user_id ? "'$uploaded_by_user_id'" : "NULL") . ", '$uploaded_by_username') 
                        ON DUPLICATE KEY UPDATE 
                        sname=VALUES(sname), emailid=VALUES(emailid), about=VALUES(about), contact=VALUES(contact), 
                        fees=VALUES(fees), year=VALUES(year), semester=VALUES(semester), balance=VALUES(balance), 
                        delete_status=VALUES(delete_status), course=VALUES(course), Attendance=VALUES(Attendance), GPA=VALUES(GPA)";

                if ($conn->query($sql)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
            
            fclose($handle);

            if ($success_count > 0) {
                $upload_success = true;
                $semester_display = ($semester == '1') ? '1st Semester' : '2nd Semester';
                
                echo "<div class='alert alert-success'>";
                echo "<strong>Upload Successful!</strong><br>";
                echo "Table: {$tableName} | Year: {$year} | Semester: {$semester_display}<br>";
                echo "Successfully imported: {$success_count} records";
                if ($error_count > 0) echo " | Errors: {$error_count}";
                echo "</div>";
            }
        }

        // Log upload
        $status = $upload_success ? 'success' : 'failed';
        $log_sql = "INSERT INTO upload_logs (year, semester, table_name, filename, records_processed, records_success, records_error, status, uploaded_by_user_id, uploaded_by_username, uploaded_by_name, user_ip_address, user_agent)
                    VALUES ('$year', '$semester', '$tableName', '" . $conn->real_escape_string($filename) . "',
                    " . ($success_count + $error_count) . ", $success_count, $error_count, '$status', " .
                    ($uploaded_by_user_id ? "'$uploaded_by_user_id'" : "NULL") . ", '$uploaded_by_username', '$uploaded_by_name', '{$_SERVER['REMOTE_ADDR']}', '" . $conn->real_escape_string($_SERVER['HTTP_USER_AGENT']) . "')";
        $conn->query($log_sql);
        
        // ====================================================================
        // FAST API-BASED PREDICTION TRIGGER
        // ====================================================================
        
        if ($upload_success) {
            echo "<div class='alert alert-info' style='margin-top: 15px;'>";
            echo "<i class='fa fa-cog fa-spin'></i> <strong>🚀 Starting Fast Prediction System...</strong>";
            echo "</div>";
            
            // Call API directly
            $api_result = trigger_prediction_via_api(
                $year, 
                $semester, 
                $tableName, 
                $success_count,
                $uploaded_by_name, 
                $uploaded_by_username, 
                $uploaded_by_user_id
            );
            
            if ($api_result['success']) {
                echo "<div class='alert alert-success' style='margin-top: 10px;'>";
                echo "<i class='fa fa-check-circle'></i> <strong>✅ Predictions Completed Successfully!</strong><br><br>";
                echo "<strong>📊 Results:</strong><br>";
                echo "• Predictions Generated: {$api_result['predictions_count']}<br>";
                echo "• Students Processed: {$api_result['students_processed']}<br>";
                echo "• Execution Time: {$api_result['api_execution_time']}s<br>";
                echo "• Total Time: {$api_result['execution_time']}s<br><br>";
                echo "<a href='gpa.php' class='btn btn-primary btn-sm'>";
                echo "<i class='fa fa-eye'></i> View Predictions Now</a>";
                echo "</div>";
            } else {
                echo "<div class='alert alert-danger' style='margin-top: 10px;'>";
                echo "<i class='fa fa-times-circle'></i> <strong>Prediction Error</strong><br>";
                echo htmlspecialchars($api_result['message']);
                echo "<br><br><small>Check that the API server is running: python prediction_api.py</small>";
                echo "</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "<strong>Upload Failed!</strong><br><br>";
        echo nl2br(htmlspecialchars($e->getMessage()));
        echo "</div>";
        
        if (isset($conn)) {
            $escaped_error = $conn->real_escape_string($e->getMessage());
            $log_sql = "INSERT INTO upload_logs (year, semester, table_name, filename, records_processed, records_success, records_error, status, error_message, uploaded_by_user_id, uploaded_by_username, uploaded_by_name)
                        VALUES ('$year', '$semester', '$tableName', '" . $conn->real_escape_string($filename) . "', 0, 0, 1, 'failed', '{$escaped_error}', " .
                        ($uploaded_by_user_id ? "'$uploaded_by_user_id'" : "NULL") . ", '$uploaded_by_username', '$uploaded_by_name')";
            $conn->query($log_sql);
        }
    }
    
} else {
    echo "<div class='alert alert-danger'>";
    echo "<strong>Invalid Request!</strong><br>Please ensure all required fields are provided.";
    echo "</div>";
}

if (isset($conn)) {
    $conn->close();
}
?>