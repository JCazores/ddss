<?php $page='predict';

include("php/dbconnect.php");
include_once('enhanced_cache_manager.php');

// Initialize enhanced cache manager with larger limits
$cache = new EnhancedCacheManager('cache/', 30, 500, 50);

// Perform health check and optimization
$healthCheck = $cache->healthCheck();
if (!$healthCheck['healthy']) {
    foreach ($healthCheck['issues'] as $issue) {
        error_log("Cache Health Issue: " . $issue);
    }
    
    try {
        $cache->optimize();
    } catch (Exception $e) {
        error_log("Cache optimization failed: " . $e->getMessage());
    }
}

// Debug: Check what tables exist and their data
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    echo "<div class='alert alert-warning'>";
    echo "<h4>Database Debug Information:</h4>";
    
    // Show all student tables
    $tables_query = "SHOW TABLES LIKE 'student_%'";
    $tables_result = $conn->query($tables_query);
    
    echo "<strong>Existing student tables:</strong><br>";
    if ($tables_result) {
        while ($table_row = $tables_result->fetch_array()) {
            $table_name = $table_row[0];
            $count_query = "SELECT COUNT(*) as count FROM `$table_name`";
            $count_result = $conn->query($count_query);
            $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
            
            echo "- $table_name: $count records<br>";
            
            if ($count > 0) {
                $sample_query = "SELECT StudentID, sname, year, semester, course FROM `$table_name` LIMIT 3";
                $sample_result = $conn->query($sample_query);
                if ($sample_result) {
                    echo "&nbsp;&nbsp;Sample data:<br>";
                    while ($sample = $sample_result->fetch_assoc()) {
                        echo "&nbsp;&nbsp;&nbsp;- ID: {$sample['StudentID']}, Year: {$sample['year']}, Semester: {$sample['semester']}<br>";
                    }
                }
            }
        }
    }
    echo "</div>";
}

// Get available years from StudentID patterns and semesters from table structure
$availableYears = [];
$availableSemesters = [];
$availableCourses = [];
$availableRiskLevels = ['High Risk', 'Medium Risk', 'Low Risk'];

// Get all tables that match the pattern student_YYYY_sem_X
$tables_query = "SHOW TABLES LIKE 'student_%_sem_%'";
$tables_result = $conn->query($tables_query);

if ($tables_result) {
    while ($table_row = $tables_result->fetch_array()) {
        $table_name = $table_row[0];
        
        if (preg_match('/student_(\d{4})_sem_(\d)/', $table_name, $matches)) {
            $table_year = intval($matches[1]);
            $table_semester = $matches[2];
            
            if ($table_year >= 2020 && $table_year <= 2030) {
                $year_query = "SELECT DISTINCT SUBSTRING(StudentID, 5, 4) as student_year 
                              FROM `$table_name` 
                              WHERE StudentID REGEXP '^OLFU[0-9]{4}'
                              AND SUBSTRING(StudentID, 5, 4) REGEXP '^[0-9]{4}$'
                              AND CAST(SUBSTRING(StudentID, 5, 4) AS UNSIGNED) BETWEEN 2020 AND 2030";
                
                $year_result = $conn->query($year_query);
                
                if ($year_result) {
                    while ($year_row = $year_result->fetch_assoc()) {
                        $student_year = intval($year_row['student_year']);
                        if ($student_year >= 2020 && $student_year <= 2030) {
                            if (!in_array($student_year, $availableYears)) {
                                $availableYears[] = $student_year;
                            }
                        }
                    }
                }
                
                $course_query = "SELECT DISTINCT course FROM `$table_name` WHERE course IS NOT NULL AND course != ''";
                $course_result = $conn->query($course_query);
                if ($course_result) {
                    while ($course_row = $course_result->fetch_assoc()) {
                        $course = trim($course_row['course']);
                        if ($course && !in_array($course, $availableCourses)) {
                            $availableCourses[] = $course;
                        }
                    }
                }
                
                $semester_count_query = "SELECT COUNT(*) as count FROM `$table_name`";
                $semester_count_result = $conn->query($semester_count_query);
                
                if ($semester_count_result && $semester_count_result->fetch_assoc()['count'] > 0) {
                    if (!in_array($table_semester, $availableSemesters)) {
                        $availableSemesters[] = $table_semester;
                    }
                }
            }
        }
    }
}

sort($availableYears);
sort($availableSemesters);
sort($availableCourses);

// Function to get all relevant tables based on filters
function getRelevantTables($conn, $filterYear = null, $filterSemester = null) {
    $relevant_tables = [];
    
    $tables_query = "SHOW TABLES LIKE 'student_%_sem_%'";
    $tables_result = $conn->query($tables_query);
    
    if ($tables_result) {
        while ($table_row = $tables_result->fetch_array()) {
            $table_name = $table_row[0];
            
            if (preg_match('/student_(\d{4})_sem_(\d)/', $table_name, $matches)) {
                $table_year = intval($matches[1]);
                $table_semester = intval($matches[2]);
                
                $include_table = true;
                
                if ($filterSemester !== null && intval($filterSemester) !== $table_semester) {
                    $include_table = false;
                }
                
                if ($include_table) {
                    $count_query = "SELECT COUNT(*) as count FROM `$table_name`";
                    
                    if ($filterYear !== null) {
                        $count_query .= " WHERE StudentID LIKE 'OLFU" . intval($filterYear) . "%'";
                    }
                    
                    $count_result = $conn->query($count_query);
                    
                    if ($count_result && $count_result->fetch_assoc()['count'] > 0) {
                        $relevant_tables[] = [
                            'table_name' => $table_name,
                            'year' => $table_year,
                            'semester' => $table_semester
                        ];
                    }
                }
            }
        }
    }
    
    return $relevant_tables;
}

// Initialize session variables
if (!isset($_SESSION)) {
    session_start();
}

// Initialize variables
$hasValidFilter = false;
$filterYear = null;
$filterSemester = null;
$filterCourse = null;
$filterRiskLevel = null;
$activeFilters = [];
$searchTerm = '';
$isSearchResult = false;

// SEARCH HANDLING
if (isset($_POST['search']) && isset($_POST['searchTerm'])) {
    $searchTerm = trim($_POST['searchTerm']);
    
    if (!empty($searchTerm)) {
        $_SESSION['current_search'] = $searchTerm;
        
        $redirectUrl = "?search=" . urlencode($searchTerm);
        
        if (isset($_GET['filterYear']) && !empty($_GET['filterYear'])) {
            $redirectUrl .= "&filterYear=" . urlencode($_GET['filterYear']);
        }
        if (isset($_GET['filterSemester']) && !empty($_GET['filterSemester'])) {
            $redirectUrl .= "&filterSemester=" . urlencode($_GET['filterSemester']);
        }
        if (isset($_GET['filterCourse']) && !empty($_GET['filterCourse'])) {
            $redirectUrl .= "&filterCourse=" . urlencode($_GET['filterCourse']);
        }
        if (isset($_GET['filterRiskLevel']) && !empty($_GET['filterRiskLevel'])) {
            $redirectUrl .= "&filterRiskLevel=" . urlencode($_GET['filterRiskLevel']);
        }
        if (isset($_GET['entries'])) {
            $redirectUrl .= "&entries=" . urlencode($_GET['entries']);
        }
        if (isset($_GET['debug'])) {
            $redirectUrl .= "&debug=" . urlencode($_GET['debug']);
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . $redirectUrl);
        exit();
    }
}

// Handle search from GET parameters
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    $isSearchResult = true;
    $_SESSION['current_search'] = $searchTerm;
} else {
    unset($_SESSION['current_search']);
}

// Handle clear search
if (isset($_GET['clearSearch'])) {
    unset($_SESSION['current_search']);
    $searchTerm = '';
    $isSearchResult = false;
    
    $redirectUrl = "?";
    $params = [];
    
    if (isset($_GET['filterYear']) && !empty($_GET['filterYear'])) {
        $params[] = "filterYear=" . urlencode($_GET['filterYear']);
    }
    if (isset($_GET['filterSemester']) && !empty($_GET['filterSemester'])) {
        $params[] = "filterSemester=" . urlencode($_GET['filterSemester']);
    }
    if (isset($_GET['filterCourse']) && !empty($_GET['filterCourse'])) {
        $params[] = "filterCourse=" . urlencode($_GET['filterCourse']);
    }
    if (isset($_GET['filterRiskLevel']) && !empty($_GET['filterRiskLevel'])) {
        $params[] = "filterRiskLevel=" . urlencode($_GET['filterRiskLevel']);
    }
    if (isset($_GET['entries'])) {
        $params[] = "entries=" . urlencode($_GET['entries']);
    }
    if (isset($_GET['debug'])) {
        $params[] = "debug=" . urlencode($_GET['debug']);
    }
    
    if (!empty($params)) {
        $redirectUrl .= implode('&', $params);
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . $redirectUrl);
    exit();
}

// FILTER HANDLING
if (isset($_GET['filterYear']) && !empty($_GET['filterYear']) && $_GET['filterYear'] !== '') {
    $filterYear = intval($_GET['filterYear']);
    
    if ($filterYear >= 2020 && $filterYear <= 2030 && in_array($filterYear, $availableYears)) {
        // Year is valid
    } else {
        $filterYear = null;
    }
}

if (isset($_GET['filterSemester']) && !empty($_GET['filterSemester']) && $_GET['filterSemester'] !== '') {
    $filterSemester = intval($_GET['filterSemester']);
    
    if (in_array($filterSemester, [1, 2]) && in_array(strval($filterSemester), $availableSemesters)) {
        // Semester is valid
    } else {
        $filterSemester = null;
    }
}

if (isset($_GET['filterCourse']) && !empty($_GET['filterCourse']) && $_GET['filterCourse'] !== '') {
    $filterCourse = trim($_GET['filterCourse']);
    
    if (in_array($filterCourse, $availableCourses)) {
        // Course is valid
    } else {
        $filterCourse = null;
    }
}

if (isset($_GET['filterRiskLevel']) && !empty($_GET['filterRiskLevel']) && $_GET['filterRiskLevel'] !== '') {
    $filterRiskLevel = trim($_GET['filterRiskLevel']);
    
    if (in_array($filterRiskLevel, $availableRiskLevels)) {
        // Risk level is valid
    } else {
        $filterRiskLevel = null;
    }
}

// Build active filters array
if ($filterYear !== null) {
    $activeFilters[] = "Academic Year: $filterYear";
}
if ($filterSemester !== null) {
    $semester_display = ($filterSemester == 1) ? '1st Semester' : '2nd Semester';
    $activeFilters[] = "Semester: $semester_display";
}
if ($filterCourse !== null) {
    $activeFilters[] = "Course: $filterCourse";
}
if ($filterRiskLevel !== null) {
    $activeFilters[] = "Risk Level: $filterRiskLevel";
}

// Check if we have valid filters
if ($filterYear !== null || $filterSemester !== null) {
    $relevant_tables = getRelevantTables($conn, $filterYear, $filterSemester);
    if (!empty($relevant_tables)) {
        $hasValidFilter = true;
    }
} elseif ($filterCourse !== null || $filterRiskLevel !== null) {
    $hasValidFilter = true;
}

// ============================================================================
// DIRECT DATABASE QUERY FOR PREDICTIONS
// ============================================================================

$cacheKey = $cache->generateKey('db_prediction_results', [
    'year' => $filterYear,
    'semester' => $filterSemester,
    'course' => $filterCourse,
    'risk' => $filterRiskLevel,
    'timestamp' => floor(time() / 300) // 5-minute intervals
]);

$results = [];
$data = null;

// Try cache first
if ($cache->exists($cacheKey, 300)) {
    $cachedData = $cache->get($cacheKey);
    if ($cachedData && isset($cachedData['results'])) {
        $data = $cachedData;
        $results = $cachedData['results'];
        
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo "<div class='alert alert-info'>";
            echo "<strong>Cache Hit!</strong><br>";
            echo "Using cached database results from " . date('Y-m-d H:i:s', $cachedData['cache_metadata']['generated_at'] ?? time());
            echo "</div>";
        }
    }
} else {
    // Fetch directly from database
    $startTime = microtime(true);
    
    try {
        // Build WHERE clause based on filters
        $whereConditions = [];
        $params = [];
        $types = "";
        
        if ($filterYear !== null) {
            $whereConditions[] = "student_id LIKE ?";
            $params[] = "OLFU" . $filterYear . "%";
            $types .= "s";
        }
        
        if ($filterCourse !== null) {
            $whereConditions[] = "course = ?";
            $params[] = $filterCourse;
            $types .= "s";
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Query predictions from database
        $query = "SELECT 
            student_id,
            course,
            current_year,
            source_table,
            current_semester_data,
            next_semester_prediction,
            risk_analysis,
            interventions
        FROM student_predictions 
        $whereClause
        ORDER BY prediction_date DESC, student_id";
        
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $dbResults = [];
        while ($row = $result->fetch_assoc()) {
            // Parse JSON fields
            $currentSemester = json_decode($row['current_semester_data'], true);
            $nextSemester = json_decode($row['next_semester_prediction'], true);
            $riskAnalysis = json_decode($row['risk_analysis'], true);
            $interventions = json_decode($row['interventions'], true);
            
            // Apply semester filter if specified
            if ($filterSemester !== null) {
                $currentSem = $currentSemester['semester'] ?? null;
                if ($currentSem !== null && intval($currentSem) !== intval($filterSemester)) {
                    continue;
                }
            }
            
            // Apply risk level filter if specified
            if ($filterRiskLevel !== null) {
                $riskLevel = $currentSemester['risk_level'] ?? null;
                if ($riskLevel !== $filterRiskLevel) {
                    continue;
                }
            }
            
            $dbResults[] = [
                'StudentID' => $row['student_id'],
                'table' => $row['source_table'],
                'sname' => '', // Will be filled from source table
                'year' => $row['current_year'],
                'course' => $row['course'],
                'semester' => $currentSemester['semester'] ?? null,
                
                // CURRENT SEMESTER DATA (for display)
                'Attendance' => $currentSemester['attendance'],
                'GPA' => $currentSemester['gpa'],
                'balance' => $currentSemester['balance'],
                
                // CURRENT Risk information (what gpa.php should show)
                'final_risk_level' => $currentSemester['risk_level'],
                'model_predicted_risk' => $currentSemester['risk_level'],
                'dropout_percentage' => round($currentSemester['dropout_probability'], 1),
                
                // NEXT SEMESTER predictions (stored but not primary display)
                'next_semester_attendance' => round($nextSemester['predicted_attendance'], 1),
                'next_semester_gpa' => round($nextSemester['predicted_gpa'], 2),
                'next_semester_balance' => round($nextSemester['predicted_balance'], 2),
                'next_semester_risk_level' => $nextSemester['predicted_risk_level'],
                'next_semester_dropout_probability' => round($nextSemester['predicted_dropout_probability'], 1),
                
                // Risk analysis
                // Risk analysis
'risk_change' => $riskAnalysis['risk_level_change'] ?? 'Stable',
'probability_change' => round($riskAnalysis['probability_change'] ?? 0, 1),
'intervention_urgency' => $riskAnalysis['intervention_urgency'] ?? 'LOW',
                
                // Interventions
                'recommended_interventions' => $interventions,
                
                // Initialize arrays
                'reasons' => [],
                'recommended_solutions' => [],
                'admin_action' => []
            ];
        }
        
        $stmt->close();
        
        // Replace lines 460-520 in gpa.php with this:

// Extract reasons and solutions from interventions
foreach ($dbResults as &$student) {
    // Initialize arrays
    $student['reasons'] = [];
    $student['recommended_solutions'] = [];
    $student['admin_action'] = [];
    
    // Process interventions from the new format
    if (!empty($student['recommended_interventions']) && is_array($student['recommended_interventions'])) {
        foreach ($student['recommended_interventions'] as $intervention) {
            // New format: {reason, solution, admin_action}
            if (isset($intervention['reason']) && $intervention['reason'] !== '' && $intervention['reason'] !== 'None') {
                if (!in_array($intervention['reason'], $student['reasons'])) {
                    $student['reasons'][] = $intervention['reason'];
                }
            }
            
            if (isset($intervention['solution']) && $intervention['solution'] !== '' && $intervention['solution'] !== 'None') {
                if (!in_array($intervention['solution'], $student['recommended_solutions'])) {
                    $student['recommended_solutions'][] = $intervention['solution'];
                }
            }
            
            if (isset($intervention['admin_action']) && $intervention['admin_action'] !== '' && $intervention['admin_action'] !== 'None') {
                if (!in_array($intervention['admin_action'], $student['admin_action'])) {
                    $student['admin_action'][] = $intervention['admin_action'];
                }
            }
        }
    }
    
    // Ensure minimum default values if arrays are empty
    if (empty($student['reasons']) || (count($student['reasons']) === 1 && $student['reasons'][0] === 'None')) {
        if ($student['final_risk_level'] === 'Low Risk') {
            $student['reasons'] = ['None'];
        } else {
            $student['reasons'] = ['Various risk factors identified'];
        }
    }
    
    if (empty($student['recommended_solutions']) || (count($student['recommended_solutions']) === 1 && $student['recommended_solutions'][0] === 'None')) {
        if ($student['final_risk_level'] === 'Low Risk') {
            $student['recommended_solutions'] = ['None'];
        } else {
            $student['recommended_solutions'] = ['Regular monitoring and support'];
        }
    }
    
    if (empty($student['admin_action']) || (count($student['admin_action']) === 1 && $student['admin_action'][0] === 'None')) {
        if ($student['final_risk_level'] === 'Low Risk') {
            $student['admin_action'] = ['Continue regular monitoring.'];
        } else {
            $student['admin_action'] = ['Schedule follow-up meeting'];
        }
    }
}
unset($student); // Break reference
        
        $results = $dbResults;
        $executionTime = microtime(true) - $startTime;
        
        // Cache the results
        $cacheData = [
            'results' => $results,
            'cache_metadata' => [
                'generated_at' => time(),
                'execution_time' => $executionTime,
                'filter_year' => $filterYear,
                'filter_semester' => $filterSemester,
                'filter_course' => $filterCourse,
                'filter_risk_level' => $filterRiskLevel,
                'total_results' => count($results),
                'source' => 'database'
            ]
        ];
        
        if (!$cache->set($cacheKey, $cacheData)) {
            error_log("Failed to cache database prediction results");
        }
        
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo "<div class='alert alert-success'>";
            echo "<strong>✓ Database Query Success!</strong><br>";
            echo "Query execution time: " . round($executionTime, 2) . " seconds<br>";
            echo "Total results: " . count($results) . "<br>";
            echo "Results cached for 5 minutes";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo "<div class='alert alert-danger'>";
            echo "<strong>✗ Database Query Failed!</strong><br>";
            echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "</div>";
        }
        
        error_log("Database query error: " . $e->getMessage());
        $results = [];
    }
}

// ============================================================================
// PROCESS RESULTS - Get complete student data
// ============================================================================

$searchResult = [];
if (!empty($results)) { 
    foreach ($results as $student) {
        if (isset($student['StudentID']) && isset($student['table'])) {
            $StudentID = $conn->real_escape_string($student['StudentID']);
            $table_name = $conn->real_escape_string($student['table']);
            
            // Get additional student data from database
            $res = $conn->query("SELECT StudentID, sname, year, course, semester, Attendance, GPA, balance FROM `$table_name` WHERE StudentID = '$StudentID'");
            if ($res && $row = $res->fetch_assoc()) {
                // Merge with existing prediction data
                $student['sname'] = $row['sname'];
                
                // Use database values if prediction didn't provide semester
                if (!isset($student['semester']) || empty($student['semester'])) {
                    $student['semester'] = $row['semester'];
                }
            }
            
            // Apply search filter if present
            $include_in_display = true;
            
            if ($isSearchResult && !empty($searchTerm)) {
                $searchMatch = false;
                $searchLower = strtolower($searchTerm);
                
                $studentIdLower = strtolower($student['StudentID']);
                $snameLower = strtolower($student['sname'] ?? '');
                
                if (strpos($studentIdLower, $searchLower) !== false || 
                    strpos($snameLower, $searchLower) !== false) {
                    $searchMatch = true;
                }
                
                $include_in_display = $searchMatch;
            }
            
            if ($include_in_display) {
                $searchResult[] = $student;
            }
        }
    }
}

// Pagination setup
$studentsPerPage = isset($_GET['entries']) ? max(1, intval($_GET['entries'])) : 10;
$totalStudents = count($searchResult);
$totalPages = ceil($totalStudents / $studentsPerPage);
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

if ($studentsPerPage >= $totalStudents) {
    $paginatedStudents = $searchResult;
} else {
    $startIndex = ($page - 1) * $studentsPerPage;
    $paginatedStudents = array_slice($searchResult, $startIndex, $studentsPerPage);
}

// ============================================================================
// EMAIL FUNCTIONALITY
// ============================================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = $success = "";
if (isset($_POST['send_email'])) {
    $emails = explode(',', $_POST['email']);
    $message = $_POST['message'];
    $studentId = $_POST['studentId'];
    
    $studentData = null;
    foreach ($searchResult as $student) {
        if ($student['StudentID'] === $studentId) {
            $studentData = $student;
            break;
        }
    }

    if ($studentData) {
        require 'libs/PHPMailer/src/Exception.php';
        require 'libs/PHPMailer/src/PHPMailer.php';
        require 'libs/PHPMailer/src/SMTP.php';

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'DropoutDecisisonSupportSystem@gmail.com';
            $mail->Password = 'dxrc vypx qelu irfj';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('yourgmail@gmail.com', 'Dropout Risk System');

            foreach ($emails as $address) {
                $mail->addAddress(trim($address));
            }

            $mail->isHTML(true);
            $mail->Subject = "Drop-Out Decision Support System Notification";

            $studentDetails = "
                <p><strong>Risk Level:</strong> {$studentData['final_risk_level']}</p>
                <p><strong>Model Predicted Risk:</strong> {$studentData['model_predicted_risk']}</p>
                <p><strong>Dropout Percentage:</strong> {$studentData['dropout_percentage']}%</p>
                <p><strong>Possible Reasons of Dropping:</strong> " . implode(", ", $studentData['reasons']) . "</p>
                <p><strong>Solutions:</strong> " . implode(", ", $studentData['recommended_solutions']) . "</p>
                <p><strong>Admin Action:</strong> " . implode(", ", $studentData['admin_action']) . "</p>
            ";

            $mail->Body = nl2br($message) . "<br><br><strong>Student Prediction Details:</strong><br>" . $studentDetails;

            if ($mail->send()) {
                $success = "Email successfully sent to " . implode(", ", $emails) . "!";
            } else {
                $error = "Failed to send email.";
            }
        } catch (Exception $e) {
            $error = "Mailer Error: " . $mail->ErrorInfo;
        }
    } else {
        $error = "Student data not found.";
    }
}

// ============================================================================
// CALCULATE STATISTICS
// ============================================================================

$semesterStats = [];
$courseStats = [];
$riskCounts = ['High' => 0, 'Medium' => 0, 'Low' => 0];

if (!empty($searchResult)) {
    foreach ($searchResult as $student) {
        // Risk counts
        if (isset($student['final_risk_level'])) {
            $level = $student['final_risk_level'];
            if ($level == "High Risk") $riskCounts['High']++;
            elseif ($level == "Medium Risk") $riskCounts['Medium']++;
            else $riskCounts['Low']++;
        }
        
        // Semester-wise stats
        if (isset($student['semester'])) {
            $semester = $student['semester'];
            if (!isset($semesterStats[$semester])) {
                $semesterStats[$semester] = ['High' => 0, 'Medium' => 0, 'Low' => 0, 'Total' => 0];
            }
            
            $level = isset($student['final_risk_level']) ? $student['final_risk_level'] : 'Low Risk';
            if ($level == "High Risk") $semesterStats[$semester]['High']++;
            elseif ($level == "Medium Risk") $semesterStats[$semester]['Medium']++;
            else $semesterStats[$semester]['Low']++;
            
            $semesterStats[$semester]['Total']++;
        }
        
        // Course-wise stats
        if (isset($student['course'])) {
            $course = $student['course'];
            if (!isset($courseStats[$course])) {
                $courseStats[$course] = ['High' => 0, 'Medium' => 0, 'Low' => 0, 'Total' => 0];
            }
            
            $level = isset($student['final_risk_level']) ? $student['final_risk_level'] : 'Low Risk';
            if ($level == "High Risk") $courseStats[$course]['High']++;
            elseif ($level == "Medium Risk") $courseStats[$course]['Medium']++;
            else $courseStats[$course]['Low']++;
            
            $courseStats[$course]['Total']++;
        }
    }
}

// Re-index array
$searchResult = array_values($searchResult);

// Calculate academic year range
$yearRangeDisplay = '';
if (!empty($availableYears)) {
    $minYear = min($availableYears);
    $maxYear = max($availableYears);
    
    if ($minYear == $maxYear) {
        $yearRangeDisplay = "Academic Year $minYear";
    } else {
        $yearRangeDisplay = "Academic Years $minYear - $maxYear";
    }
} else {
    $yearRangeDisplay = "No Data Available";
}

// Rest of your HTML and JavaScript code remains exactly the same...
// (I'm stopping here since the HTML/JS portion doesn't change)
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dropout Prediction System</title>
     <!-- FAVICON -->
     <link rel="icon" type="image/png" href="img/icon1.jpg">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.css">
    <link rel="stylesheet" href="css/style1.css">
    <link rel="stylesheet" href="css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Dashboard styling */
        .stat-card {
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            color: white;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            margin-top: 0;
            font-size: 18px;
        }
        .stat-card .count {
            font-size: 36px;
            font-weight: bold;
        }
        .high-risk {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
           
        }
        .medium-risk {
            background: linear-gradient(45deg, #f1c40f, #f39c12);
        }
        .low-risk {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
        }
        .total-students {
            background: linear-gradient(45deg, #3498db, #2980b9);
        }
        
        /* Table styling */
        .table-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }
        .table-container th {
            background-color: rgba(1, 129, 55, 0.9);
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        .table-container td {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }
        .table-container tr:hover {
            background-color: #f9f9f9;
        }
        
        /* Search and pagination */
        .search-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .search-box {
            flex-grow: 1;
            max-width: 400px;
        }
        .entries-selector {
            width: auto;
        }
        .pagination-container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 20px;
        }
        .pagination-btn {
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            color: #333;
            padding: 5px 15px;
            font-size: 14px;
            text-decoration: none;
            border-radius: 3px;
            margin: 0 2px;
            transition: background-color 0.3s;
        }
        .pagination-btn:hover {
            background-color: #eaeaea;
        }
        .pagination-btn.disabled {
            color: #aaa;
            border-color: #ddd;
            pointer-events: none;
            cursor: not-allowed;
            background-color: #f9f9f9;
        }
        
        /* Modals */
        .modal-header {
            background-color: rgba(1, 129, 55, 0.9);
            color: white;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            border-top: 1px solid #eee;
            padding: 15px;
        }
        .gauge-chart {
    flex: 1;
    min-width: 200px;
    max-width: 300px;
    height: 200px;  /* Add fixed height */
}

/* For the course chart container */
#courseChart {
    max-height: 300px !important;
}

/* For modal charts */
.modal-body canvas {
    max-height: 250px !important;
}
        /* Risk indicators */
        .risk-indicator {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
        }
        .high-risk-bg {
            background-color: #e74c3c;
        }
        .medium-risk-bg {
            background-color: #f1c40f;
        }
        .low-risk-bg {
            background-color: #2ecc71;
        }
        
        /* Buttons and actions */
        .action-btns {
            display: flex;
            gap: 5px;
        }
        .btn-view {
            background-color: #3498db;
            color: white;
        }
        .btn-stats {
            background-color: #9b59b6;
            color: white;
        }
        .btn-email {
            background-color: #2ecc71;
            color: white;
        }
        
        /* Form elements */
        .form-group {
            margin-bottom: 15px;
        }
        .select2-container {
            width: 100% !important;
        }
        
        /* Replace the chart-related styles in your existing CSS with these improved ones */

/* Charts */
.chart-container {
    display: grid;
    margin-left: 50px;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-bottom: 30px;
    align-items: center;
}

.gauge-chart {
    background: white;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
    min-height: 200px;
    max-height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.gauge-chart h3 {
    margin: 0 0 10px 0;
    font-size: 16px;
    text-align: center;
    color: #333;
    font-weight: 600;
}

/* Ensure ApexCharts containers have consistent sizing */
.gauge-chart .apexcharts-canvas {
    margin: 0 auto !important;
}

.gauge-chart .apexcharts-svg {
    width: 180px !important;
    height: 150px !important;
}

/* Course chart improvements */
.course-chart-container {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
    margin-top: 20px;
}

.course-chart-container h4 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 18px;
    font-weight: 600;
    text-align: center;
}

#courseChart {
    max-height: 350px !important;
    height: 350px !important;
}

/* Responsive design */
@media (max-width: 992px) {
    .chart-container {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .gauge-chart {
        min-height: 180px;
        max-height: 180px;
    }
    
    .gauge-chart .apexcharts-svg {
        width: 160px !important;
        height: 130px !important;
    }
}

@media (max-width: 768px) {
    .gauge-chart {
        padding: 12px;
        min-height: 160px;
        max-height: 160px;
    }
    
    .gauge-chart h3 {
        font-size: 14px;
    }
    
    .gauge-chart .apexcharts-svg {
        width: 140px !important;
        height: 110px !important;
    }
}
        .filter-status {
    background-color: #e8f5e8;
    border-left: 4px solid #5cb85c;
    padding: 10px 15px;
    margin-bottom: 15px;
    border-radius: 0 4px 4px 0;
}

.filter-status.warning {
    background-color: #fdf6e3;
    border-left-color: #f0ad4e;
}

.filter-status.info {
    background-color: #e6f3ff;
    border-left-color: #5bc0de;
}
/* Enhanced Search Container Styles - Add this to your existing CSS */

.filter-search-container {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: 1px solid #dee2e6;
}

.filter-search-row {
    display: grid;
    grid-template-columns: 1fr 1fr 2fr 1fr;
    gap: 15px;
    align-items: end;
}

.filter-group, .search-group, .button-group {
    display: flex;
    flex-direction: column;
}

.filter-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
    font-size: 14px;
}

.search-input-group {
    position: relative;
    display: flex;
}

.search-input-group input {
    flex: 1;
    padding-right: 45px;
    border-radius: 5px 0 0 5px;
    border-right: none;
    font-size: 14px;
}

.search-btn {
    background: rgba(1, 129, 55, 0.9);
    color: white;
    border: 1px solid rgba(1, 129, 55, 0.9);
    border-radius: 0 5px 5px 0;
    padding: 8px 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: rgba(1, 129, 55, 1);
    transform: translateY(-1px);
}

.button-group {
    display: flex;
    flex-direction: row;
    gap: 10px;
    align-items: center;
}

.active-filters {
    margin-top: 15px;
    padding: 12px 15px;
    background: #fff;
    border-radius: 8px;
    border-left: 4px solid rgba(1, 129, 55, 0.8);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.filter-badge {
    display: inline-block;
    background: rgba(1, 129, 55, 0.1);
    color: rgba(1, 129, 55, 0.9);
    padding: 4px 8px;
    border-radius: 15px;
    font-size: 12px;
    margin: 2px 5px 2px 0;
    border: 1px solid rgba(1, 129, 55, 0.3);
}

.filter-badge.search {
    background: rgba(52, 152, 219, 0.1);
    color: rgba(52, 152, 219, 0.9);
    border-color: rgba(52, 152, 219, 0.3);
}

.clear-badge {
    color: #dc3545;
    text-decoration: none;
    margin-left: 5px;
    font-weight: bold;
}

.clear-badge:hover {
    color: #c82333;
    text-decoration: none;
}

.results-summary {
    float: right;
    color: #6c757d;
    font-style: italic;
}

/* Search highlighting */
mark {
    background-color: #fff3cd;
    color: #856404;
    padding: 1px 2px;
    border-radius: 2px;
}

/* Responsive design */
@media (max-width: 1200px) {
    .filter-search-row {
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .search-group {
        grid-column: 1 / -1;
    }
    
    .button-group {
        grid-column: 1 / -1;
        justify-self: center;
    }
}

@media (max-width: 768px) {
    .filter-search-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .filter-search-container {
        padding: 15px;
    }
    
    .search-input-group {
        flex-direction: column;
    }
    
    .search-input-group input {
        border-radius: 5px;
        border-right: 1px solid #ced4da;
        padding-right: 12px;
        margin-bottom: 10px;
    }
    
    .search-btn {
        border-radius: 5px;
        border: 1px solid rgba(1, 129, 55, 0.9);
    }
    
    .button-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .results-summary {
        float: none;
        display: block;
        margin-top: 8px;
    }
}

/* Loading states */
.btn-loading {
    position: relative;
    color: transparent !important;
}

.btn-loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Search suggestions (if you want to add autocomplete) */
.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ced4da;
    border-top: none;
    border-radius: 0 0 5px 5px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.search-suggestion {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
}

.search-suggestion:hover {
    background-color: #f8f9fa;
}

.search-suggestion.active {
    background-color: rgba(1, 129, 55, 0.1);
}
    </style>
</head>

<body>
    
    <?php include("php/header.php"); ?>
    
    <div id="page-wrapper">
        <div id="page-inner">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="page-head-line">Student Dropout Prediction Dashboard</h1>
                </div>
            </div>
            
       <?php
// Add this code before the stats cards section (around line 150-200)

// Calculate academic year range from available years
$yearRangeDisplay = '';
if (!empty($availableYears)) {
    $minYear = min($availableYears);
    $maxYear = max($availableYears);
    
    if ($minYear == $maxYear) {
        $yearRangeDisplay = "Academic Year $minYear";
    } else {
        $yearRangeDisplay = "Academic Years $minYear - $maxYear";
    }
} else {
    $yearRangeDisplay = "No Data Available";
}

// Count total students across all available years (for context)
$totalStudentsAllYears = 0;
if (!empty($availableYears)) {
    foreach ($availableYears as $year) {
        $relevant_tables = getRelevantTables($conn, $year, null);
        foreach ($relevant_tables as $table_info) {
            $table_name = $table_info['table_name'];
            $count_query = "SELECT COUNT(*) as count FROM `$table_name` WHERE StudentID LIKE 'OLFU" . intval($year) . "%'";
            $count_result = $conn->query($count_query);
            if ($count_result) {
                $totalStudentsAllYears += $count_result->fetch_assoc()['count'];
            }
        }
    }
}
?>
       
            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card high-risk">
                        <h3><i class="fa fa-warning"></i> High Risk Students</h3>
                        <div class="count"><?= $riskCounts['High'] ?></div>
                        <p><?= $riskCounts['High'] > 0 ? round(($riskCounts['High'] / max(1, array_sum($riskCounts))) * 100, 1) : 0 ?>% of <?= $hasValidFilter ? 'filtered' : 'total' ?></p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card medium-risk">
                        <h3><i class="fa fa-exclamation-circle"></i> Medium Risk Students</h3>
                        <div class="count"><?= $riskCounts['Medium'] ?></div>
                        <p><?= $riskCounts['Medium'] > 0 ? round(($riskCounts['Medium'] / max(1, array_sum($riskCounts))) * 100, 1) : 0 ?>% of <?= $hasValidFilter ? 'filtered' : 'total' ?></p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card low-risk">
                        <h3><i class="fa fa-check-circle"></i> Low Risk Students</h3>
                        <div class="count"><?= $riskCounts['Low'] ?></div>
                        <p><?= $riskCounts['Low'] > 0 ? round(($riskCounts['Low'] / max(1, array_sum($riskCounts))) * 100, 1) : 0 ?>% of <?= $hasValidFilter ? 'filtered' : 'total' ?></p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
    <div class="stat-card total-students">
        <h3><i class="fa fa-users"></i> <?= $hasValidFilter ? 'Filtered' : 'Total' ?> Students</h3>
        <div class="count"><?= array_sum($riskCounts) ?></div>
        <p><i class="fa fa-calendar"></i> <?= $yearRangeDisplay ?></p>
        
            
        </div>

</div>
            </div>
            
           <!-- Charts Panel -->
<div class="panel panel-default chart-panel" style="border-color:rgba(1, 129, 55, 0.3);">
    <div class="panel-heading" style="background: linear-gradient(135deg, rgba(1, 129, 55, 0.9), rgba(1, 129, 55, 0.8)); color: white; font-weight: 600; border-bottom: 2px solid rgba(1, 129, 55, 0.9);">
        <div class="panel-title">
            <i class="fa fa-bar-chart-o"></i> Dropout Risk Analysis by Course<?= $hasValidFilter ? ' (Filtered Results)' : '' ?>
        </div>
    </div>
    
        
        <div style="margin-top: 30px; padding: 20px;">
    <canvas id="courseChart" style="height: 300px !important; max-height: 300px !important;"></canvas>
</div>
    
</div>
            
            <!-- Data Table -->
            <div class="panel panel-default" style="border-color:rgba(1, 129, 55);">
                <div class="panel-heading" style="background-color: rgba(1, 129, 55); color: white; font-weight: 100;">
                    <div class="panel-title">
                        <i class="fa fa-table"></i> Prediction Results<?= $hasValidFilter ? ' (Filtered)' : '' ?>
                    </div>
                </div>
                <div class="panel-body">
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <!-- Updated Search and Filter Container - SEPARATE SYSTEMS -->
<div class="filter-search-container">
    <!-- SEARCH SECTION - Completely Independent -->
    <div class="search-section" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
        <h5 class="section-title" style="margin: 0 0 10px 0; color: #28a745;">
            <i class="fa fa-search"></i> Search Students
        </h5>
        <form method="POST" action="" id="searchForm" style="margin: 0;">
            <div class="search-row" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                <div class="search-input-wrapper" style="flex: 1; min-width: 250px;">
                    <div class="search-input-group" style="position: relative; display: flex;">
                        <input type="text" 
                               name="searchTerm" 
                               id="searchTerm" 
                               class="form-control" 
                               placeholder="Search by Student ID or Name..." 
                               value="<?= htmlspecialchars($searchTerm ?? '') ?>"
                               style="border-radius: 4px 0 0 4px; border-right: none; padding-right: 10px;">
                        <button type="submit" 
                                name="search" 
                                class="btn btn-success"
                                style="border-radius: 0 4px 4px 0; padding: 8px 12px; background: #28a745; border-color: #28a745;">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                    <small class="search-help" style="color: #6c757d; font-size: 11px; display: block; margin-top: 3px;">
                        <i class="fa fa-info-circle"></i> 
                        Search by Student ID (e.g., OLFU2023001) or Student Name
                    </small>
                </div>
                
                <!-- Clear Search Button -->
                <?php if (!empty($searchTerm)): ?>
                    <div class="clear-search-wrapper">
                        <a href="?<?= http_build_query(array_filter([
                            'filterYear' => $filterYear,
                            'filterSemester' => $filterSemester,
                            'filterCourse' => $filterCourse,
                            'filterRiskLevel' => $filterRiskLevel,
                            'entries' => $_GET['entries'] ?? null,
                            'debug' => $_GET['debug'] ?? null,
                            'clearSearch' => '1'
                        ])) ?>" class="btn btn-default clear-search-btn">
                            <i class="fa fa-times"></i> Clear Search
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- SEPARATOR -->
    <div class="separator" style="border-top: 1px solid #dee2e6; margin: 20px 0;"></div>
    
    <!-- FILTER SECTION - Completely Independent -->
    <div class="filter-section" style="background: #f1f3f4; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff;">
        <h5 class="section-title" style="margin: 0 0 15px 0; color: #007bff;">
            <i class="fa fa-filter"></i> Filter Results
        </h5>
        <form method="GET" action="" id="filterForm">
            <div class="filter-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end;">
                <!-- Academic Year Filter -->
                <div class="filter-group">
                    <label for="filterYear" class="filter-label" style="font-weight: 600; color: #495057; margin-bottom: 5px; font-size: 13px; display: block;">
                        Academic Year
                    </label>
                    <select name="filterYear" id="filterYear" class="form-control filter-select">
                        <option value="">All Years</option>
                        <?php 
                        foreach ($availableYears as $yearOption) {
                            $selected = ($filterYear !== null && $filterYear == $yearOption) ? 'selected' : '';
                            echo "<option value='$yearOption' $selected>$yearOption</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Semester Filter -->
                <div class="filter-group">
                    <label for="filterSemester" class="filter-label" style="font-weight: 600; color: #495057; margin-bottom: 5px; font-size: 13px; display: block;">
                        Semester
                    </label>
                    <select name="filterSemester" id="filterSemester" class="form-control filter-select">
                        <option value="">All Semesters</option>
                        <?php 
                        foreach ($availableSemesters as $semesterOption) {
                            $selected = ($filterSemester !== null && $filterSemester == $semesterOption) ? 'selected' : '';
                            $semesterDisplay = ($semesterOption == '1') ? '1st Semester' : '2nd Semester';
                            echo "<option value='$semesterOption' $selected>$semesterDisplay</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Course Filter -->
                <div class="filter-group">
                    <label for="filterCourse" class="filter-label" style="font-weight: 600; color: #495057; margin-bottom: 5px; font-size: 13px; display: block;">
                        Course
                    </label>
                    <select name="filterCourse" id="filterCourse" class="form-control filter-select">
                        <option value="">All Courses</option>
                        <?php 
                        foreach ($availableCourses as $courseOption) {
                            $selected = ($filterCourse !== null && $filterCourse == $courseOption) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($courseOption) . "' $selected>" . htmlspecialchars($courseOption) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Risk Level Filter -->
                <div class="filter-group">
                    <label for="filterRiskLevel" class="filter-label" style="font-weight: 600; color: #495057; margin-bottom: 5px; font-size: 13px; display: block;">
                        Risk Level
                    </label>
                    <select name="filterRiskLevel" id="filterRiskLevel" class="form-control filter-select">
                        <option value="">All Risk Levels</option>
                        <?php 
                        foreach ($availableRiskLevels as $riskOption) {
                            $selected = ($filterRiskLevel !== null && $filterRiskLevel == $riskOption) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($riskOption) . "' $selected>" . htmlspecialchars($riskOption) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <!-- Filter Action Buttons -->
                <div class="filter-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" onclick="applyFilters()" class="btn btn-primary filter-btn">
                        <i class="fa fa-filter"></i> Apply Filters
                    </button>
                    
                    <?php if ($filterYear !== null || $filterSemester !== null || $filterCourse !== null || $filterRiskLevel !== null): ?>
                        <a href="?<?= http_build_query(array_filter([
                            'search' => $searchTerm ?: null,
                            'entries' => $_GET['entries'] ?? null,
                            'debug' => $_GET['debug'] ?? null
                        ])) ?>" class="btn btn-default clear-filters-btn">
                            <i class="fa fa-times"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Preserve search parameter in filter form -->
            <?php if (!empty($searchTerm)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">
            <?php endif; ?>
            
            <!-- Preserve other parameters -->
            <?php if (isset($_GET['entries'])): ?>
                <input type="hidden" name="entries" value="<?= htmlspecialchars($_GET['entries']) ?>">
            <?php endif; ?>
            <?php if (isset($_GET['debug'])): ?>
                <input type="hidden" name="debug" value="<?= htmlspecialchars($_GET['debug']) ?>">
            <?php endif; ?>
        </form>
    </div>

    <!-- STATUS DISPLAY - Shows active search and filters -->
    <?php if (!empty($searchTerm) || $hasValidFilter): ?>
        <div class="active-status" style="margin-top: 20px; padding: 15px; background: #e8f4fd; border: 1px solid #b3d9ff; border-radius: 8px;">
            <div class="status-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <strong style="color: #0056b3;">
                    <i class="fa fa-info-circle"></i> Current Status:
                </strong>
                <div class="results-count" style="color: #6c757d; font-size: 14px;">
                    <i class="fa fa-users"></i> 
                    Showing <strong style="color: #007bff;"><?= count($searchResult) ?></strong> results
                </div>
            </div>
            
            <div class="status-badges" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <?php if (!empty($searchTerm)): ?>
                    <span class="status-badge search-badge" style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 12px; font-size: 12px; border: 1px solid #c3e6cb;">
                        <i class="fa fa-search"></i> 
                        Search: "<strong><?= htmlspecialchars($searchTerm) ?></strong>"
                    </span>
                <?php endif; ?>

                <?php if ($filterYear !== null): ?>
                    <span class="status-badge filter-badge" style="background: #cce5ff; color: #004085; padding: 4px 8px; border-radius: 12px; font-size: 12px; border: 1px solid #99d6ff;">
                        <i class="fa fa-calendar"></i> Year: <?= htmlspecialchars($filterYear) ?>
                    </span>
                <?php endif; ?>

                <?php if ($filterSemester !== null): ?>
                    <span class="status-badge filter-badge" style="background: #cce5ff; color: #004085; padding: 4px 8px; border-radius: 12px; font-size: 12px; border: 1px solid #99d6ff;">
                        <i class="fa fa-book"></i> Semester: <?= ($filterSemester == 1) ? '1st' : '2nd' ?>
                    </span>
                <?php endif; ?>

                <?php if ($filterCourse !== null): ?>
                    <span class="status-badge filter-badge" style="background: #cce5ff; color: #004085; padding: 4px 8px; border-radius: 12px; font-size: 12px; border: 1px solid #99d6ff;">
                        <i class="fa fa-graduation-cap"></i> Course: <?= htmlspecialchars($filterCourse) ?>
                    </span>
                <?php endif; ?>

                <?php if ($filterRiskLevel !== null): ?>
                    <?php 
                    $riskColorClass = '';
                    if ($filterRiskLevel == 'High Risk') {
                        $riskColorClass = 'background: #f8d7da; color: #721c24; border-color: #f5c6cb;';
                    } elseif ($filterRiskLevel == 'Medium Risk') {
                        $riskColorClass = 'background: #fff3cd; color: #856404; border-color: #ffeaa7;';
                    } else {
                        $riskColorClass = 'background: #d4edda; color: #155724; border-color: #c3e6cb;';
                    }
                    ?>
                    <span class="status-badge filter-badge" style="<?= $riskColorClass ?> padding: 4px 8px; border-radius: 12px; font-size: 12px; border: 1px solid;">
                        <i class="fa fa-warning"></i> Risk: <?= htmlspecialchars($filterRiskLevel) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="active-status default-status" style="margin-top: 20px;">
            <div class="alert alert-info" style="margin: 0;">
                <i class="fa fa-info-circle"></i> 
                <strong>Showing all students</strong> - Use search or filters above to narrow results
                <span class="total-count" style="float: right; font-weight: normal;">
                    (<?= count($searchResult) ?> total students)
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

    <!-- Your existing entries selector (updated to preserve filters) -->
    <div style="margin-bottom: 15px;">
        <form method="GET" class="form-inline">
            <!-- Preserve filter and search parameters -->
            <?php if ($filterYear): ?>
                <input type="hidden" name="filterYear" value="<?= htmlspecialchars($filterYear) ?>">
            <?php endif; ?>
            <?php if ($filterSemester): ?>
                <input type="hidden" name="filterSemester" value="<?= htmlspecialchars($filterSemester) ?>">
            <?php endif; ?>
            <?php if ($filterCourse): ?>
            <input type="hidden" name="filterCourse" value="<?= htmlspecialchars($filterCourse) ?>">
        <?php endif; ?>
        <?php if ($filterRiskLevel): ?>
            <input type="hidden" name="filterRiskLevel" value="<?= htmlspecialchars($filterRiskLevel) ?>">
        <?php endif; ?>
            <?php if (isset($_GET['searchResult'])): ?>
                <input type="hidden" name="searchResult" value="1">
            <?php endif; ?>
            <?php if (isset($_GET['debug'])): ?>
                <input type="hidden" name="debug" value="<?= htmlspecialchars($_GET['debug']) ?>">
            <?php endif; ?>
            
            <label style="margin-right: 10px;">Show 
                <select name="entries" class="form-control" onchange="this.form.submit()">
                    <option value="10" <?= (isset($_GET['entries']) && $_GET['entries'] == 10) ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= (isset($_GET['entries']) && $_GET['entries'] == 25) ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= (isset($_GET['entries']) && $_GET['entries'] == 50) ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= (isset($_GET['entries']) && $_GET['entries'] == 100) ? 'selected' : '' ?>>100</option>
                    <option value="<?= $totalStudents ?>" <?= (isset($_GET['entries']) && $_GET['entries'] == $totalStudents) ? 'selected' : '' ?>>All</option>
                </select> 
                entries
            </label>
        </form>
    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Year</th>
                                    <th>Course</th>
                                    <th>Semester</th>
                                    <th>Risk Level</th>
                                    <th>Dropout %</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paginatedStudents)): ?>
                                    <tr>
                                    <td colspan="8" class="text-center">
                                            <?php if ($hasValidFilter): ?>
                                                No students found matching the filter criteria: <?= implode(', ', $activeFilters) ?>
                                            <?php else: ?>
                                                No students found matching your criteria.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paginatedStudents as $index => $student): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($student['StudentID']) ?></td>
                                            <td><?= htmlspecialchars($student['sname']) ?></td>
                                            <td><?= htmlspecialchars($student['year']) ?></td>
                                            <td><?= htmlspecialchars($student['course']) ?></td>
                                            <td><?= isset($student['semester']) ? htmlspecialchars($student['semester']) : 'N/A' ?></td>
                                            <td>
                                                <?php
                                                $riskClass = '';
                                                if ($student['final_risk_level'] == 'High Risk') {
                                                    $riskClass = 'high-risk-bg';
                                                } elseif ($student['final_risk_level'] == 'Medium Risk') {
                                                    $riskClass = 'medium-risk-bg';
                                                } else {
                                                    $riskClass = 'low-risk-bg';
                                                }
                                                ?>
                                                <span class="risk-indicator <?= $riskClass ?>"><?= htmlspecialchars($student['final_risk_level']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($student['dropout_percentage']) ?>%</td>
                                            <td class="action-btns">
                                                <button type="button" class="btn btn-sm btn-view" data-toggle="modal" data-target="#detailsModal<?= $index ?>">
                                                    <i class="fa fa-eye"></i> View
                                                </button>
                                                <button type="button" class="btn btn-sm btn-stats" data-toggle="modal" data-target="#statsModal<?= $index ?>">
    <i class="fa fa-bar-chart"></i> Stats
</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
    <div class="pagination-container">
        <?php 
         // Build filter parameters correctly
        $filterParams = '';
        
        // Add all filter parameters
        if (isset($_GET['filterYear']) && !empty($_GET['filterYear'])) {
            $filterParams .= '&filterYear=' . urlencode($_GET['filterYear']);
        }
        if (isset($_GET['filterSemester']) && !empty($_GET['filterSemester'])) {
            $filterParams .= '&filterSemester=' . urlencode($_GET['filterSemester']);
        }
        if (isset($_GET['filterCourse']) && !empty($_GET['filterCourse'])) {
            $filterParams .= '&filterCourse=' . urlencode($_GET['filterCourse']);
        }
        if (isset($_GET['filterRiskLevel']) && !empty($_GET['filterRiskLevel'])) {
            $filterParams .= '&filterRiskLevel=' . urlencode($_GET['filterRiskLevel']);
        }
        
        // Add search parameter if present
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filterParams .= '&search=' . urlencode($_GET['search']);
        }
        
        // Add entries parameter
        $filterParams .= '&entries=' . urlencode($studentsPerPage);
        
        // Add debug parameter if present
        if (isset($_GET['debug']) && !empty($_GET['debug'])) {
            $filterParams .= '&debug=' . urlencode($_GET['debug']);
        }
        ?>
        
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&entries=<?= $studentsPerPage ?><?= $filterParams ?>" class="pagination-btn">
                <i class="fa fa-angle-left"></i> Previous
            </a>
        <?php else: ?>
            <span class="pagination-btn disabled">
                <i class="fa fa-angle-left"></i> Previous
            </span>
        <?php endif; ?>
        
        <span style="margin: 0 15px;">Page <?= $page ?> of <?= $totalPages ?></span>
        
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&entries=<?= $studentsPerPage ?><?= $filterParams ?>" class="pagination-btn">
                Next <i class="fa fa-angle-right"></i>
            </a>
        <?php else: ?>
            <span class="pagination-btn disabled">
                Next <i class="fa fa-angle-right"></i>
            </span>
        <?php endif; ?>
    </div>
<?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals for student details -->
    <?php foreach ($paginatedStudents as $index => $student): ?>
        <div class="modal fade" id="detailsModal<?= $index ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?= $index ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">Student Details - <?= htmlspecialchars($student['sname']) ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">Personal Information</h3>
                                    </div>
                                    <div class="panel-body">
                                        <p><strong>Student ID:</strong> <?= htmlspecialchars($student['StudentID']) ?></p>
                                        <p><strong>Name:</strong> <?= htmlspecialchars($student['sname']) ?></p>
                                        <p><strong>Year Level:</strong> <?= htmlspecialchars($student['year']) ?></p>
                                        <p><strong>Course:</strong> <?= htmlspecialchars($student['course']) ?></p>
                                        <?php if (isset($student['semester'])): ?>
                                            <p><strong>Semester:</strong> <?= htmlspecialchars($student['semester']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">Academic Status</h3>
                                    </div>
                                    <div class="panel-body">
                                        <p><strong>Attendance:</strong> <?= htmlspecialchars($student['Attendance']) ?>%</p>
                                        <p><strong>GPA:</strong> <?= htmlspecialchars($student['GPA']) ?></p>
                                        <p><strong>Balance:</strong> ₱<?= number_format($student['balance'], 2) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">Dropout Risk Analysis</h3>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="<?= $student['final_risk_level'] == 'High Risk' ? 'high-risk' : ($student['final_risk_level'] == 'Medium Risk' ? 'medium-risk' : 'low-risk') ?>" style="text-align: center; padding: 20px; border-radius: 5px;">
                                            <h3>Overall Risk Level</h3>
                                            <div style="font-size: 24px; font-weight: bold;"><?= htmlspecialchars($student['final_risk_level']) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="text-align: center; padding: 20px; background-color: #f5f5f5; border-radius: 5px;">
                                            <h3>Model Prediction</h3>
                                            <div style="font-size: 24px; font-weight: bold;"><?= htmlspecialchars($student['model_predicted_risk']) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div style="text-align: center; padding: 20px; background-color: #f5f5f5; border-radius: 5px;">
                                            <h3>Dropout Percentage</h3>
                                            <div style="font-size: 24px; font-weight: bold;"><?= htmlspecialchars($student['dropout_percentage']) ?>%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">Analysis and Recommendations</h3>
                                    </div>
                                    <div class="panel-body">
                                        <h4>Possible Reasons for Dropping Out:</h4>
                                        <ul class="list-group">
                                            <?php foreach ($student['reasons'] as $reason): ?>
                                                <li class="list-group-item"><?= htmlspecialchars($reason) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        
                                        <h4>Recommended Solutions:</h4>
                                        <ul class="list-group">
                                            <?php foreach ($student['recommended_solutions'] as $solution): ?>
                                                <li class="list-group-item"><?= htmlspecialchars($solution) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">Send Notification Email</h3>
                            </div>
                            <div class="panel-body">
                                <form method="POST">
                                    <input type="hidden" name="studentId" value="<?= htmlspecialchars($student['StudentID']) ?>">
                                    <div class="form-group">
                                        <label for="email<?= $index ?>">Email Recipients:</label>
                                        <input type="text" name="email" id="email<?= $index ?>" class="form-control" placeholder="example1@gmail.com, example2@gmail.com" required>
                                        <small class="text-muted">Separate multiple email addresses with commas</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="message<?= $index ?>">Message:</label>
                                        <textarea name="message" id="message<?= $index ?>" class="form-control" rows="4" required>Dear Program Head,

This message is to inform you of a student's current academic status. The systems has flagged "<?= htmlspecialchars($student['sname']) ?>" as being at risk of dropping out. We recommend that you meet with the student to discuss potential strategies and support to help them get back on track.

Best regards,
Drop-Out Decision Support System</textarea>
                                    </div>
                                    <button type="submit" name="send_email" class="btn btn-success">
                                        <i class="fa fa-envelope"></i> Send Email
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="view_stats.php?id=<?= urlencode($student['StudentID']) ?>" class="btn btn-primary">
                            <i class="fa fa-bar-chart"></i> View Detailed Statistics
                        </a>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Add this modal code after the existing modals but before the closing </body> tag -->
<?php foreach ($paginatedStudents as $index => $student): ?>
    <!-- Stats Modal -->
    <div class="modal fade" id="statsModal<?= $index ?>" tabindex="-1" role="dialog" aria-labelledby="statsModalLabel<?= $index ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Performance Statistics - <?= htmlspecialchars($student['sname']) ?></h4>
                </div>
                <div class="modal-body">
                    <!-- Key Performance Indicators -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="panel panel-default">
                                <div class="panel-heading" style="background-color: rgba(1, 129, 55, 0.9); color: white;">
                                    <h3 class="panel-title text-center">GPA</h3>
                                </div>
                                <div class="panel-body text-center">
                                    <div class="huge" id="gpaIndicator<?= $index ?>" style="font-size: 36px; font-weight: bold;"><?= htmlspecialchars($student['GPA']) ?></div>
                                    <div id="gpaGauge<?= $index ?>" style="height: 120px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-default">
                                <div class="panel-heading" style="background-color: rgba(1, 129, 55, 0.9); color: white;">
                                    <h3 class="panel-title text-center">Attendance</h3>
                                </div>
                                <div class="panel-body text-center">
                                    <div class="huge" id="attendanceIndicator<?= $index ?>" style="font-size: 36px; font-weight: bold;"><?= htmlspecialchars($student['Attendance']) ?>%</div>
                                    <div id="attendanceGauge<?= $index ?>" style="height: 120px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-default">
                                <div class="panel-heading" style="background-color: rgba(1, 129, 55, 0.9); color: white;">
                                    <h3 class="panel-title text-center">Balance</h3>
                                </div>
                                <div class="panel-body text-center">
                                    <div class="huge" style="font-size: 28px; font-weight: bold;">₱<?= number_format($student['balance'], 2) ?></div>
                                    <div class="text-muted" style="margin-top: 50px; font-weight: bold;">Outstanding Amount</div>
                                    <div id="balanceIndicator<?= $index ?>" style="margin-top: 10px; height: 50px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-default">
                                <div class="panel-heading" style="background-color: rgba(1, 129, 55, 0.9); color: white;">
                                    <h3 class="panel-title text-center">Risk Score</h3>
                                </div>
                                <div class="panel-body text-center">
                                    <div class="huge" id="riskIndicator<?= $index ?>" style="font-size: 36px; font-weight: bold;"><?= htmlspecialchars($student['dropout_percentage']) ?>%</div>
                                    <div id="riskGauge<?= $index ?>" style="height: 120px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Trend Charts -->
                    

                    <!-- Risk Factor Analysis -->
                    <div class="panel panel-default">
                        <div class="panel-heading" style="background-color: rgba(1, 129, 55, 0.9); color: white;">
                            <h3 class="panel-title">Risk Factor Analysis</h3>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <canvas id="riskFactorsChart<?= $index ?>" height="200"></canvas>
                                </div>
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th style="background-color: rgba(1, 129, 55, 0.7); color: white;">Risk Factor</th>
                                                    <th style="background-color: rgba(1, 129, 55, 0.7); color: white;">Impact</th>
                                                    <th style="background-color: rgba(1, 129, 55, 0.7); color: white;">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Define risk factors based on student data
                                                $riskFactors = [];
                                                
                                                // GPA Risk Factor (1 = Low, 5 = High)
$gpaRisk = 'Low';
$gpaImpact = 'Low';
$gpaValue = floatval($student['GPA']);

if ($gpaValue >= 4.0) {
    $gpaRisk = 'High';
    $gpaImpact = 'Critical';
} elseif ($gpaValue >= 2.5) {
    $gpaRisk = 'Medium';
    $gpaImpact = 'Significant';
}
// Values below 3.0 remain as 'Low' risk and 'Low' impact

$riskFactors[] = ['name' => 'Low GPA', 'level' => $gpaRisk, 'impact' => $gpaImpact];
                                                
                                                // Attendance Risk Factor
                                                $attendanceRisk = 'Low';
                                                $attendanceImpact = 'Low';
                                                if (floatval($student['Attendance']) < 75) {
                                                    $attendanceRisk = 'High';
                                                    $attendanceImpact = 'Critical';
                                                } elseif (floatval($student['Attendance']) < 85) {
                                                    $attendanceRisk = 'Medium';
                                                    $attendanceImpact = 'Significant';
                                                }
                                                $riskFactors[] = ['name' => 'Poor Attendance', 'level' => $attendanceRisk, 'impact' => $attendanceImpact];
                                                
                                                // Financial Risk Factor
                                                $balanceRisk = 'Low';
                                                $balanceImpact = 'Low';
                                                if (floatval($student['balance']) > 10000) {
                                                    $balanceRisk = 'High';
                                                    $balanceImpact = 'Critical';
                                                } elseif (floatval($student['balance']) > 5000) {
                                                    $balanceRisk = 'Medium';
                                                    $balanceImpact = 'Significant';
                                                }
                                                $riskFactors[] = ['name' => 'Financial Issues', 'level' => $balanceRisk, 'impact' => $balanceImpact];
                                                
                                                // Add other risk factors based on reasons for dropping out
                                                foreach ($student['reasons'] as $i => $reason) {
                                                    if ($i >= 2) break; // Limit to avoid too many rows
                                                    $riskFactors[] = [
                                                        'name' => $reason,
                                                        'level' => $student['final_risk_level'] == 'High Risk' ? 'High' : 
                                                                 ($student['final_risk_level'] == 'Medium Risk' ? 'Medium' : 'Low'),
                                                        'impact' => $student['final_risk_level'] == 'High Risk' ? 'Critical' : 
                                                                  ($student['final_risk_level'] == 'Medium Risk' ? 'Significant' : 'Minimal')
                                                    ];
                                                }
                                                
                                                // Display risk factors in table
                                                foreach ($riskFactors as $factor) {
                                                    $levelClass = '';
                                                    if ($factor['level'] == 'High') {
                                                        $levelClass = 'danger';
                                                    } elseif ($factor['level'] == 'Medium') {
                                                        $levelClass = 'warning';
                                                    } else {
                                                        $levelClass = 'success';
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($factor['name']) ?></td>
                                                        <td><?= htmlspecialchars($factor['impact']) ?></td>
                                                        <td><span class="label label-<?= $levelClass ?>"><?= htmlspecialchars($factor['level']) ?></span></td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendations -->
                    <div class="panel panel-default">
                        <div class="panel-heading" style="background-color: rgba(1, 129, 55, 0.9); color: white;">
                            <h3 class="panel-title">Intervention Recommendations</h3>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="progress-container">
                                        <div class="progress">
                                            <?php
                                            $riskPercentage = intval($student['dropout_percentage']);
                                            $progressClass = $riskPercentage > 66 ? 'danger' : ($riskPercentage > 33 ? 'warning' : 'success');
                                            ?>
                                            <div class="progress-bar progress-bar-<?= $progressClass ?>" role="progressbar" 
                                                aria-valuenow="<?= $riskPercentage ?>" aria-valuemin="0" aria-valuemax="100" 
                                                style="width: <?= $riskPercentage ?>%;">
                                                <?= $riskPercentage ?>% Risk
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <h4><i class="fa fa-info-circle"></i> Intervention Priority: 
                                            <?php if ($student['final_risk_level'] == 'High Risk'): ?>
                                                <span class="label label-danger">Urgent</span>
                                            <?php elseif ($student['final_risk_level'] == 'Medium Risk'): ?>
                                                <span class="label label-warning">Moderate</span>
                                            <?php else: ?>
                                                <span class="label label-success">Low</span>
                                            <?php endif; ?>
                                        </h4>
                                        <p>Based on the student's risk profile and current performance metrics, the following interventions are recommended:</p>
                                    </div>
                                    
                                    <!--<div class="row">
                                        <div class="col-md-6">
                                            <div class="panel panel-default">
                                                <div class="panel-heading" style="background-color: #5bc0de; color: white;">
                                                    <h3 class="panel-title"><i class="fa fa-list-ul"></i> Recommended Solutions</h3>
                                                </div>
                                                <div class="panel-body">
                                                    <ol>
                                                        <?php foreach ($student['recommended_solutions'] as $solution): ?>
                                                            <li><?= htmlspecialchars($solution) ?></li>
                                                        <?php endforeach; ?>
                                                    </ol>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="panel panel-default">
                                                <div class="panel-heading" style="background-color: #5cb85c; color: white;">
                                                    <h3 class="panel-title"><i class="fa fa-tasks"></i> Admin Actions</h3>
                                                </div>
                                                <div class="panel-body">
                                                    <ol>
                                                        <?php foreach ($student['admin_action'] as $action): ?>
                                                            <li><?= htmlspecialchars($action) ?></li>
                                                        <?php endforeach; ?>
                                                    </ol>
                                                </div>
                                            </div>
                                        </div>
                                    </div>-->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="printStatsReport('<?= htmlspecialchars($student['StudentID']) ?>', '<?= htmlspecialchars($student['sname']) ?>')">
                        <i class="fa fa-print"></i> Print Report
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
// Core JavaScript for Student Dropout Prediction System
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize all functionality
    initializeCharts();
    initializeSearch();
    initializeFilters();
    initializeStudentModals();
    initializePagination();
    
});

// Chart Initialization
function initializeCharts() {
    // Risk Gauge Charts
    const highRisk = <?= $riskCounts['High'] ?>;
    const mediumRisk = <?= $riskCounts['Medium'] ?>;
    const lowRisk = <?= $riskCounts['Low'] ?>;
    const total = highRisk + mediumRisk + lowRisk;

    // Render gauge charts
    renderGauge("#highRiskGauge", highRisk, '#e74c3c', '', highRisk, total);
    renderGauge("#mediumRiskGauge", mediumRisk, '#f1c40f', '', mediumRisk, total);
    renderGauge("#lowRiskGauge", lowRisk, '#2ecc71', '', lowRisk, total);

    // Course-wise Risk Distribution Chart
    initializeCourseChart();
}

function renderGauge(element, value, color, label, count, total) {
    if (!document.querySelector(element)) return;
    
    const options = {
        chart: {
            type: 'radialBar',
            height: 180,
            offsetY: -20,
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        plotOptions: {
            radialBar: {
                startAngle: -90,
                endAngle: 90,
                track: {
                    background: '#e7e7e7',
                    strokeWidth: '97%',
                },
                dataLabels: {
                    name: { 
                        show: true, 
                        offsetY: -10, 
                        fontSize: '16px',
                        fontWeight: 'bold',
                        color: '#333'
                    },
                    value: {
                        formatter: function (val) {
                            return val + "%";
                        },
                        fontSize: '22px',
                        show: true,
                    }
                }
            }
        },
        tooltip: {
            enabled: true,
            y: {
                formatter: function () {
                    return count + " student(s)";
                }
            }
        },
        colors: [color],
        labels: [label],
        series: [Math.round((value / (total || 1)) * 100)]
    };

    new ApexCharts(document.querySelector(element), options).render();
}

function initializeCourseChart() {
    const courseCtx = document.getElementById('courseChart');
    if (!courseCtx) return;
    
    // Check if we have any data
    const courseLabels = [
        <?php 
        if (!empty($courseStats)) {
            foreach ($courseStats as $course => $stats) {
                echo "'" . addslashes($course) . "', ";
            }
        }
        ?>
    ];
    
    const highRiskData = [
        <?php 
        if (!empty($courseStats)) {
            foreach ($courseStats as $stats) {
                echo ($stats['High'] ?? 0) . ", ";
            }
        }
        ?>
    ];
    
    const mediumRiskData = [
        <?php 
        if (!empty($courseStats)) {
            foreach ($courseStats as $stats) {
                echo ($stats['Medium'] ?? 0) . ", ";
            }
        }
        ?>
    ];
    
    const lowRiskData = [
        <?php 
        if (!empty($courseStats)) {
            foreach ($courseStats as $stats) {
                echo ($stats['Low'] ?? 0) . ", ";
            }
        }
        ?>
    ];
    
    // If no data, show message
    if (courseLabels.length === 0) {
        const chartContainer = courseCtx.parentElement;
        chartContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;"><i class="fa fa-info-circle" style="font-size: 48px; margin-bottom: 10px;"></i><p style="font-size: 16px;">No course data available for the current filters</p></div>';
        return;
    }
    
    new Chart(courseCtx, {
        type: 'bar',
        data: {
            labels: courseLabels,
            datasets: [
                {
                    label: 'High Risk',
                    data: highRiskData,
                    backgroundColor: 'rgba(231, 76, 60, 0.8)',
                    borderColor: 'rgba(231, 76, 60, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Medium Risk',
                    data: mediumRiskData,
                    backgroundColor: 'rgba(241, 196, 15, 0.8)',
                    borderColor: 'rgba(241, 196, 15, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Low Risk',
                    data: lowRiskData,
                    backgroundColor: 'rgba(46, 204, 113, 0.8)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Risk Distribution by Course<?= $hasValidFilter ? " (Filtered)" : "" ?>',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    position: 'top',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y + ' student(s)';
                            return label;
                        },
                        footer: function(tooltipItems) {
                            let total = 0;
                            tooltipItems.forEach(function(tooltipItem) {
                                total += tooltipItem.parsed.y;
                            });
                            return 'Total: ' + total + ' student(s)';
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

// Search Functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchTerm');
    const searchForm = document.querySelector('form[method="POST"]');
    
    if (!searchInput || !searchForm) return;
    
    // Handle search input
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });
    
    // Real-time validation
    searchInput.addEventListener('input', function() {
        const value = this.value.trim();
        updateSearchButton(value.length > 0);
    });
    
    // Handle search form submission
    searchForm.addEventListener('submit', function(e) {
        if (e.submitter && e.submitter.name === 'search') {
            const searchTerm = searchInput.value.trim();
            
            if (searchTerm.length === 0) {
                e.preventDefault();
                showAlert('Please enter a search term', 'warning');
                searchInput.focus();
                return false;
            }
            
            if (searchTerm.length < 2) {
                e.preventDefault();
                showAlert('Search term must be at least 2 characters long', 'warning');
                searchInput.focus();
                return false;
            }
            
            setLoadingState(true);
        }
    });
    
    // Highlight search terms if present
    const searchTerm = '<?= htmlspecialchars($searchTerm ?? '') ?>';
    if (searchTerm && searchTerm.length > 0) {
        highlightSearchTerms(searchTerm);
    }
}

function performSearch() {
    const searchInput = document.getElementById('searchTerm');
    const searchTerm = searchInput.value.trim();
    
    if (searchTerm.length === 0) {
        showAlert('Please enter a search term', 'warning');
        searchInput.focus();
        return false;
    }
    
    if (searchTerm.length < 2) {
        showAlert('Search term must be at least 2 characters long', 'warning');
        searchInput.focus();
        return false;
    }
    
    setLoadingState(true);
    document.querySelector('button[name="search"]').click();
}

function updateSearchButton(hasContent) {
    const searchBtn = document.querySelector('.search-btn');
    if (searchBtn) {
        if (hasContent) {
            searchBtn.classList.add('active');
        } else {
            searchBtn.classList.remove('active');
        }
    }
}

function setLoadingState(loading) {
    const searchBtn = document.querySelector('.search-btn');
    const searchInput = document.getElementById('searchTerm');
    
    if (loading && searchBtn && searchInput) {
        searchBtn.classList.add('btn-loading');
        searchBtn.disabled = true;
        searchInput.disabled = true;
        
        // Re-enable after timeout as backup
        setTimeout(() => {
            searchBtn.classList.remove('btn-loading');
            searchBtn.disabled = false;
            searchInput.disabled = false;
        }, 10000);
    }
}

function highlightSearchTerms(term) {
    if (!term || term.length < 2) return;
    
    const tableRows = document.querySelectorAll('table tbody tr');
    tableRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        cells.forEach((cell, index) => {
            // Only highlight Student ID (0) and Name (1) columns
            if (index === 0 || index === 1) {
                highlightInElement(cell, term);
            }
        });
    });
}

function highlightInElement(element, term) {
    const text = element.textContent;
    const regex = new RegExp(`(${escapeRegExp(term)})`, 'gi');
    
    if (regex.test(text)) {
        const html = text.replace(regex, '<mark>$1</mark>');
        element.innerHTML = html;
    }
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Filter Functionality
function initializeFilters() {
    const filterForm = document.getElementById('filterForm');
    if (!filterForm) return;
    
    // Handle filter form submission with loading state
    filterForm.addEventListener('submit', function() {
        const submitButton = this.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Filtering...';
        }
    });
}

// Apply filters function - called by button
function applyFilters() {
    const yearValue = document.getElementById('filterYear').value;
    const semesterValue = document.getElementById('filterSemester').value;
    const courseValue = document.getElementById('filterCourse').value;
    const riskValue = document.getElementById('filterRiskLevel').value;
    
    // Build URL with filter parameters
    let url = window.location.pathname + '?';
    let params = [];
    
    if (yearValue) params.push('filterYear=' + encodeURIComponent(yearValue));
    if (semesterValue) params.push('filterSemester=' + encodeURIComponent(semesterValue));
    if (courseValue) params.push('filterCourse=' + encodeURIComponent(courseValue));
    if (riskValue) params.push('filterRiskLevel=' + encodeURIComponent(riskValue));
    
    // Preserve other parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('entries')) params.push('entries=' + urlParams.get('entries'));
    if (urlParams.get('debug')) params.push('debug=' + urlParams.get('debug'));
    
    if (params.length > 0) {
        url += params.join('&');
    } else {
        // No filters, go to clean URL
        url = window.location.pathname;
        if (urlParams.get('entries')) url += '?entries=' + urlParams.get('entries');
    }
    
    window.location.href = url;
}

// Store chart instances to prevent duplicates
let studentChartInstances = {};

function initializeStudentModals() {
    <?php foreach ($paginatedStudents as $index => $student): ?>
        initializeStudentModal(<?= $index ?>, <?= json_encode($student) ?>);
    <?php endforeach; ?>
}

function initializeStudentModal(index, studentData) {
    const modal = document.getElementById('statsModal' + index);
    if (!modal) return;
    
    // Clear any existing chart instances for this modal
    if (studentChartInstances[index]) {
        Object.values(studentChartInstances[index]).forEach(chart => {
            if (chart && chart.destroy) {
                chart.destroy();
            }
        });
        studentChartInstances[index] = {};
    } else {
        studentChartInstances[index] = {};
    }
    
    // Initialize charts when modal is shown
    $(modal).on('shown.bs.modal', function () {
        // Clear containers first
        clearChartContainers(index);
        // Then initialize new charts
        setTimeout(() => {
            initializeStudentCharts(index, studentData);
        }, 100);
    });
    
    // Clean up when modal is hidden
    $(modal).on('hidden.bs.modal', function () {
        destroyStudentCharts(index);
    });
}

function clearChartContainers(index) {
    const containers = [
        `#gpaGauge${index}`,
        `#attendanceGauge${index}`,
        `#riskGauge${index}`,
        `#riskFactorsChart${index}`
    ];
    
    containers.forEach(selector => {
        const element = document.querySelector(selector);
        if (element) {
            element.innerHTML = '';
        }
    });
}

function destroyStudentCharts(index) {
    if (studentChartInstances[index]) {
        Object.values(studentChartInstances[index]).forEach(chart => {
            if (chart && chart.destroy) {
                chart.destroy();
            }
        });
        studentChartInstances[index] = {};
    }
}

function initializeStudentCharts(index, student) {
    // Destroy existing charts first
    destroyStudentCharts(index);
    
    // Initialize chart instances object for this student
    if (!studentChartInstances[index]) {
        studentChartInstances[index] = {};
    }
    
    // GPA Gauge
    const gpaElement = document.querySelector("#gpaGauge" + index);
    if (gpaElement && gpaElement.offsetParent !== null) { // Check if element is visible
        try {
            studentChartInstances[index].gpaChart = new ApexCharts(gpaElement, {
                chart: { 
                    type: 'radialBar', 
                    height: 120, 
                    sparkline: { enabled: true },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    radialBar: {
                        startAngle: -90, 
                        endAngle: 90,
                        track: { 
                            background: '#e7e7e7', 
                            strokeWidth: '97%' 
                        },
                        hollow: { size: '35%' },
                        dataLabels: { show: false }
                    }
                },
                colors: [parseFloat(student.GPA) < 2.0 ? '#2ecc71' : (parseFloat(student.GPA) < 2.5 ? '#f1c40f' : '#e74c3c')],
                series: [Math.min(100, parseFloat(student.GPA) / 4.0 * 100)],
                tooltip: {
                    enabled: true,
                    y: {
                        formatter: function(value) {
                            return `GPA: ${student.GPA}/4.0 (${Math.round(value)}%)`;
                        }
                    }
                }
            });
            studentChartInstances[index].gpaChart.render();
        } catch (error) {
            console.error('Error rendering GPA chart:', error);
        }
    }
    
    // Attendance Gauge
    const attendanceElement = document.querySelector("#attendanceGauge" + index);
    if (attendanceElement && attendanceElement.offsetParent !== null) {
        try {
            studentChartInstances[index].attendanceChart = new ApexCharts(attendanceElement, {
                chart: { 
                    type: 'radialBar', 
                    height: 120, 
                    sparkline: { enabled: true },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    radialBar: {
                        startAngle: -90, 
                        endAngle: 90,
                        track: { 
                            background: '#e7e7e7', 
                            strokeWidth: '97%' 
                        },
                        hollow: { size: '35%' },
                        dataLabels: { show: false }
                    }
                },
                colors: [parseFloat(student.Attendance) < 75 ? '#e74c3c' : (parseFloat(student.Attendance) < 85 ? '#f1c40f' : '#2ecc71')],
                series: [Math.min(100, parseFloat(student.Attendance))],
                tooltip: {
                    enabled: true,
                    y: {
                        formatter: function(value) {
                            return `Attendance: ${student.Attendance}%`;
                        }
                    }
                }
            });
            studentChartInstances[index].attendanceChart.render();
        } catch (error) {
            console.error('Error rendering Attendance chart:', error);
        }
    }
    
    // Risk Gauge
    const riskElement = document.querySelector("#riskGauge" + index);
    if (riskElement && riskElement.offsetParent !== null) {
        try {
            studentChartInstances[index].riskChart = new ApexCharts(riskElement, {
                chart: { 
                    type: 'radialBar', 
                    height: 120, 
                    sparkline: { enabled: true },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    radialBar: {
                        startAngle: -90, 
                        endAngle: 90,
                        track: { 
                            background: '#e7e7e7', 
                            strokeWidth: '97%' 
                        },
                        hollow: { size: '35%' },
                        dataLabels: { show: false }
                    }
                },
                colors: [parseFloat(student.dropout_percentage) > 66 ? '#e74c3c' : (parseFloat(student.dropout_percentage) > 33 ? '#f1c40f' : '#2ecc71')],
                series: [Math.min(100, parseFloat(student.dropout_percentage))],
                tooltip: {
                    enabled: true,
                    y: {
                        formatter: function(value) {
                            return `Risk Score: ${student.dropout_percentage}%`;
                        }
                    }
                }
            });
            studentChartInstances[index].riskChart.render();
        } catch (error) {
            console.error('Error rendering Risk chart:', error);
        }
    }
    
    // Risk Factors Radar Chart
    const riskFactorsElement = document.getElementById('riskFactorsChart' + index);
    if (riskFactorsElement && riskFactorsElement.offsetParent !== null) {
        try {
            // Destroy existing Chart.js instance if it exists
            if (studentChartInstances[index].riskFactorsChart) {
                studentChartInstances[index].riskFactorsChart.destroy();
            }
            
            studentChartInstances[index].riskFactorsChart = new Chart(riskFactorsElement.getContext('2d'), {
                type: 'radar',
                data: {
                    labels: ['GPA', 'Attendance', 'Financial', 'Engagement', 'Other Factors'],
                    datasets: [{
                        label: 'Risk Factors',
                        data: [
                            parseFloat(student.GPA) < 2.0 ? 80 : (parseFloat(student.GPA) < 2.5 ? 50 : 20),
                            parseFloat(student.Attendance) < 75 ? 80 : (parseFloat(student.Attendance) < 85 ? 50 : 20),
                            parseFloat(student.balance) > 10000 ? 80 : (parseFloat(student.balance) > 5000 ? 50 : 20),
                            parseFloat(student.dropout_percentage) > 66 ? 80 : (parseFloat(student.dropout_percentage) > 33 ? 50 : 20),
                            student.final_risk_level === 'High Risk' ? 80 : (student.final_risk_level === 'Medium Risk' ? 50 : 20)
                        ],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgb(255, 99, 132)',
                        pointBackgroundColor: 'rgb(255, 99, 132)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgb(255, 99, 132)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 1.8,
                    plugins: {
                        title: { 
                            display: true, 
                            text: 'Risk Factor Analysis' 
                        }
                    },
                    scales: {
                        r: {
                            angleLines: { display: true },
                            suggestedMin: 0,
                            suggestedMax: 100
                        }
                    },
                    animation: {
                        duration: 800,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        } catch (error) {
            console.error('Error rendering Risk Factors chart:', error);
        }
    }
}

// Global cleanup function (call this when page is unloaded)
function cleanupAllCharts() {
    Object.values(studentChartInstances).forEach(studentCharts => {
        Object.values(studentCharts).forEach(chart => {
            if (chart && chart.destroy) {
                chart.destroy();
            }
        });
    });
    studentChartInstances = {};
}

// Add cleanup on page unload
window.addEventListener('beforeunload', cleanupAllCharts);

// Pagination
function initializePagination() {
    // Add click handlers for pagination buttons
    const paginationBtns = document.querySelectorAll('.pagination-btn:not(.disabled)');
    paginationBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Add loading state
            this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Loading...';
        });
    });
}

// Print Report Function
function printStatsReport(studentId, studentName) {
    const printWindow = window.open('', '_blank');
    const currentDate = new Date().toLocaleDateString();
    
    // Find student data
    const studentRow = Array.from(document.querySelectorAll('table tbody tr')).find(row => {
        const idCell = row.querySelector('td:first-child');
        return idCell && idCell.textContent.includes(studentId);
    });
    
    if (!studentRow) {
        showAlert('Student data not found for printing', 'error');
        return;
    }
    
    const cells = studentRow.querySelectorAll('td');
    const riskLevel = cells[5].textContent.trim();
    const dropoutPercentage = cells[6].textContent.trim();
    const gpa = cells[3] ? cells[3].textContent.trim() : 'N/A';
    
    printWindow.document.write(`
        <html>
        <head>
            <title>Student Performance Report - ${studentName}</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
                h1, h2, h3 { color: #006633; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #006633; padding-bottom: 10px; }
                .section { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .risk-high { color: #e74c3c; font-weight: bold; }
                .risk-medium { color: #f1c40f; font-weight: bold; }
                .risk-low { color: #2ecc71; font-weight: bold; }
                .footer { margin-top: 50px; font-size: 12px; color: #666; text-align: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Student Performance Report</h1>
                <p><strong>Student ID:</strong> ${studentId} | <strong>Name:</strong> ${studentName}</p>
                <p><strong>Generated on:</strong> ${currentDate}</p>
            </div>
            
            <div class="section">
                <h2>Risk Assessment Summary</h2>
                <table>
                    <tr><th>Risk Level</th><td>${riskLevel}</td></tr>
                    <tr><th>Dropout Percentage</th><td>${dropoutPercentage}</td></tr>
                    <tr><th>Current GPA</th><td>${gpa}</td></tr>
                </table>
            </div>
            
            <div class="footer">
                <p>This report is generated by the Student Dropout Prediction System.</p>
                <p>Confidential: For administrative and advisory use only.</p>
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    setTimeout(() => printWindow.print(), 500);
}

// Utility Functions
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.temp-alert');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} temp-alert`;
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideInRight 0.3s ease;
    `;
    alertDiv.innerHTML = `
        <button type="button" class="close" onclick="this.parentElement.remove()">
            <span>&times;</span>
        </button>
        ${message}
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => alertDiv.remove(), 300);
        }
    }, 5000);
}

// Debug toggle function
function toggleDebug() {
    const currentUrl = new URL(window.location);
    const debug = currentUrl.searchParams.get('debug');
    
    if (debug === '1') {
        currentUrl.searchParams.delete('debug');
    } else {
        currentUrl.searchParams.set('debug', '1');
    }
    
    window.location.href = currentUrl.toString();
}

// CSS Animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .search-btn.active {
        background: rgba(1, 129, 55, 1) !important;
        transform: scale(1.05);
        transition: all 0.3s ease;
    }
    
    .btn-loading {
        position: relative;
        color: transparent !important;
    }
    
    .btn-loading::after {
        content: "";
        position: absolute;
        width: 16px; height: 16px;
        top: 50%; left: 50%;
        margin-left: -8px; margin-top: -8px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    mark {
        background-color: #fff3cd;
        color: #856404;
        padding: 1px 2px;
        border-radius: 2px;
    }
`;
document.head.appendChild(style);

// Make functions available globally
window.applyFilters = applyFilters;
window.printStatsReport = printStatsReport;
window.toggleDebug = toggleDebug;
</script>
    <!-- Scripts -->
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="js/jquery.metisMenu.js"></script>
    <script src="js/custom1.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>

<?php $conn->close(); ?>