<?php 
$page='predict';

include("php/dbconnect.php");
include_once('enhanced_cache_manager.php');

// Initialize enhanced cache manager
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

// Get available years from database
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

// Handle search
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
        
        header("Location: " . $_SERVER['PHP_SELF'] . $redirectUrl);
        exit();
    }
}

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
    
    if (!empty($params)) {
        $redirectUrl .= implode('&', $params);
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . $redirectUrl);
    exit();
}

// Handle filters
if (isset($_GET['filterYear']) && !empty($_GET['filterYear']) && $_GET['filterYear'] !== '') {
    $filterYear = intval($_GET['filterYear']);
    
    if ($filterYear >= 2020 && $filterYear <= 2030 && in_array($filterYear, $availableYears)) {
        // Valid
    } else {
        $filterYear = null;
    }
}

if (isset($_GET['filterSemester']) && !empty($_GET['filterSemester']) && $_GET['filterSemester'] !== '') {
    $filterSemester = intval($_GET['filterSemester']);
    
    if (in_array($filterSemester, [1, 2]) && in_array(strval($filterSemester), $availableSemesters)) {
        // Valid
    } else {
        $filterSemester = null;
    }
}

if (isset($_GET['filterCourse']) && !empty($_GET['filterCourse']) && $_GET['filterCourse'] !== '') {
    $filterCourse = trim($_GET['filterCourse']);
    
    if (in_array($filterCourse, $availableCourses)) {
        // Valid
    } else {
        $filterCourse = null;
    }
}

if (isset($_GET['filterRiskLevel']) && !empty($_GET['filterRiskLevel']) && $_GET['filterRiskLevel'] !== '') {
    $filterRiskLevel = trim($_GET['filterRiskLevel']);
    
    if (in_array($filterRiskLevel, $availableRiskLevels)) {
        // Valid
    } else {
        $filterRiskLevel = null;
    }
}

// Build active filters array
if ($filterYear !== null) {
    $activeFilters[] = "Academic Year: $filterYear";
    $hasValidFilter = true;
}
if ($filterSemester !== null) {
    $semester_display = ($filterSemester == 1) ? '1st Semester' : '2nd Semester';
    $activeFilters[] = "Semester: $semester_display";
    $hasValidFilter = true;
}
if ($filterCourse !== null) {
    $activeFilters[] = "Course: $filterCourse";
    $hasValidFilter = true;
}
if ($filterRiskLevel !== null) {
    $activeFilters[] = "Risk Level: $filterRiskLevel";
    $hasValidFilter = true;
}

// API-BASED PREDICTION FETCHING
$API_URL = "http://127.0.0.1:8000/api/predict";

// Build cache key for API results
$cacheKey = $cache->generateKey('api_prediction_results', [
    'year' => $filterYear,
    'semester' => $filterSemester,
    'timestamp' => floor(time() / 300) // 5-minute cache
]);

$results = [];
$data = null;

// Try to get from cache first
if ($cache->exists($cacheKey, 300)) {
    $cachedData = $cache->get($cacheKey);
    if ($cachedData && isset($cachedData['results'])) {
        $data = $cachedData;
        $results = $cachedData['results'];
    }
} else {
    // Fetch from API
    $apiUrl = $API_URL;
    $queryParams = [];
    
    if ($filterYear !== null) {
        $queryParams[] = "year=" . intval($filterYear);
    }
    if ($filterSemester !== null) {
        $queryParams[] = "semester=" . intval($filterSemester);
    }
    
    if (!empty($queryParams)) {
        $apiUrl .= "?" . implode("&", $queryParams);
    }
    
    $startTime = microtime(true);
    
    // Initialize cURL
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $executionTime = microtime(true) - $startTime;
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        
        if ($data && isset($data['results']) && $data['success']) {
            $results = $data['results'];
            
            // Cache the results
            $cacheData = $data;
            $cacheData['cache_metadata'] = [
                'generated_at' => date('Y-m-d H:i:s'),
                'execution_time' => $executionTime,
                'filter_year' => $filterYear,
                'filter_semester' => $filterSemester,
                'total_results' => count($results)
            ];
            
            $cache->set($cacheKey, $cacheData);
        } else {
            error_log("API returned invalid data structure");
        }
    } else {
        error_log("API request failed: HTTP $httpCode - $curlError");
    }
}

// Process results with filters and search
$searchResult = [];
if (!empty($results)) {
    foreach ($results as $student) {
        $include_in_display = true;
        
        // Apply course filter
        if ($filterCourse !== null && trim($student['course']) !== $filterCourse) {
            $include_in_display = false;
        }
        
        // Apply risk level filter
        if ($filterRiskLevel !== null && trim($student['final_risk_level']) !== $filterRiskLevel) {
            $include_in_display = false;
        }
        
        // Apply search filter
        if ($include_in_display && $isSearchResult && !empty($searchTerm)) {
            $searchMatch = false;
            $searchLower = strtolower($searchTerm);
            
            $studentIdLower = strtolower($student['StudentID']);
            $snameLower = isset($student['sname']) ? strtolower($student['sname']) : '';
            
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

// Pagination
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

// Calculate statistics
$semesterStats = [];
$courseStats = [];
$riskCounts = ['High' => 0, 'Medium' => 0, 'Low' => 0];

if (!empty($searchResult)) {
    foreach ($searchResult as $student) {
        if (isset($student['final_risk_level'])) {
            $level = $student['final_risk_level'];
            if ($level == "High Risk") $riskCounts['High']++;
            elseif ($level == "Medium Risk") $riskCounts['Medium']++;
            else $riskCounts['Low']++;
        }
        
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

$searchResult = array_values($searchResult);

// Email functionality
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = $success = "";
if (isset($_POST['send_email'])) {
    $emails = explode(',', $_POST['email']);
    $message = $_POST['message'];
    $studentId = $_POST['studentId'];
    
    $studentData = null;
    foreach ($results as $student) {
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
            $mail->Username = 'smartmedsystem@gmail.com';
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

// Calculate year range
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

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dropout Prediction System</title>
    <link rel="icon" type="image/png" href="img/icon1.jpg">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.css">
    <link rel="stylesheet" href="css/style1.css">
    <link rel="stylesheet" href="css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Include all your existing styles from the original file */
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
        
        .api-status-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 15px;
            border-radius: 20px;
            background: #2ecc71;
            color: white;
            font-size: 12px;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .api-status-indicator.offline {
            background: #e74c3c;
        }
        
        .api-status-indicator.loading {
            background: #f39c12;
        }
        
        /* Add loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <?php include("php/header.php"); ?>
    
    <!-- API Status Indicator -->
    <div id="apiStatus" class="api-status-indicator" style="display: none;">
        <span id="apiStatusText">API Connected</span>
    </div>
    
    <div id="page-wrapper">
        <div id="page-inner">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="page-head-line">Student Dropout Prediction Dashboard</h1>
                    <p style="color: #666; font-size: 14px;">
                        <i class="fa fa-info-circle"></i> 
                        Data is fetched from the prediction API and cached for 5 minutes for optimal performance
                    </p>
                </div>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $success ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
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
                <div class="panel-heading" style="background: linear-gradient(135deg, rgba(1, 129, 55, 0.9), rgba(1, 129, 55, 0.8)); color: white; font-weight: 600;">
                    <div class="panel-title">
                        <i class="fa fa-bar-chart-o"></i> Dropout Risk Analysis by Course<?= $hasValidFilter ? ' (Filtered Results)' : '' ?>
                    </div>
                </div>
                <div class="panel-body">
                    <div style="margin-top: 30px; margin-bottom: -50px;">
                        <canvas id="courseChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Data Table -->
            <div class="panel panel-default" style="border-color:rgba(1, 129, 55);">
                <div class="panel-heading" style="background-color: rgba(1, 129, 55); color: white;">
                    <div class="panel-title">
                        <i class="fa fa-table"></i> Prediction Results<?= $hasValidFilter ? ' (Filtered)' : '' ?>
                    </div>
                </div>
                <div class="panel-body">
                    <!-- Search and Filter Container -->
                    <div class="filter-search-container">
                        <!-- SEARCH SECTION -->
                        <div class="search-section" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                            <h5 class="section-title" style="margin: 0 0 10px 0; color: #28a745;">
                                <i class="fa fa-search"></i> Search Students
                            </h5>
                            <form method="POST" action="" id="searchForm">
                                <div class="search-row" style="display: flex; gap: 10px; align-items: flex-end;">
                                    <div class="search-input-wrapper" style="flex: 1;">
                                        <input type="text" 
                                               name="searchTerm" 
                                               id="searchTerm" 
                                               class="form-control" 
                                               placeholder="Search by Student ID or Name..." 
                                               value="<?= htmlspecialchars($searchTerm ?? '') ?>">
                                    </div>
                                    <button type="submit" name="search" class="btn btn-success">
                                        <i class="fa fa-search"></i> Search
                                    </button>
                                    <?php if (!empty($searchTerm)): ?>
                                        <a href="?clearSearch=1" class="btn btn-default">
                                            <i class="fa fa-times"></i> Clear
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                        
                        <!-- SEPARATOR -->
                        <div style="border-top: 1px solid #dee2e6; margin: 20px 0;"></div>
                        
                        <!-- FILTER SECTION -->
                        <div class="filter-section" style="background: #f1f3f4; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff;">
                            <h5 style="margin: 0 0 15px 0; color: #007bff;">
                                <i class="fa fa-filter"></i> Filter Results
                            </h5>
                            <form method="GET" action="" id="filterForm">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                                    <div>
                                        <label>Academic Year</label>
                                        <select name="filterYear" class="form-control">
                                            <option value="">All Years</option>
                                            <?php foreach ($availableYears as $yearOption): ?>
                                                <option value="<?= $yearOption ?>" <?= ($filterYear === $yearOption) ? 'selected' : '' ?>>
                                                    <?= $yearOption ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label>Semester</label>
                                        <select name="filterSemester" class="form-control">
                                            <option value="">All Semesters</option>
                                            <?php foreach ($availableSemesters as $semesterOption): ?>
                                                <option value="<?= $semesterOption ?>" <?= ($filterSemester == $semesterOption) ? 'selected' : '' ?>>
                                                    <?= ($semesterOption == '1') ? '1st Semester' : '2nd Semester' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label>Course</label>
                                        <select name="filterCourse" class="form-control">
                                            <option value="">All Courses</option>
                                            <?php foreach ($availableCourses as $courseOption): ?>
                                                <option value="<?= htmlspecialchars($courseOption) ?>" <?= ($filterCourse === $courseOption) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($courseOption) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label>Risk Level</label>
                                        <select name="filterRiskLevel" class="form-control">
                                            <option value="">All Risk Levels</option>
                                            <?php foreach ($availableRiskLevels as $riskOption): ?>
                                                <option value="<?= htmlspecialchars($riskOption) ?>" <?= ($filterRiskLevel === $riskOption) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($riskOption) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 15px; display: flex; gap: 10px;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-filter"></i> Apply Filters
                                    </button>
                                    <?php if ($hasValidFilter): ?>
                                        <a href="?" class="btn btn-default">
                                            <i class="fa fa-times"></i> Clear Filters
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Status Display -->
                        <?php if (!empty($searchTerm) || $hasValidFilter): ?>
                            <div style="margin-top: 20px; padding: 15px; background: #e8f4fd; border: 1px solid #b3d9ff; border-radius: 8px;">
                                <strong><i class="fa fa-info-circle"></i> Current Status:</strong>
                                <span style="float: right;">Showing <strong><?= count($searchResult) ?></strong> results</span>
                                <br>
                                <div style="margin-top: 10px;">
                                    <?php if (!empty($searchTerm)): ?>
                                        <span class="badge" style="background: #28a745;">Search: "<?= htmlspecialchars($searchTerm) ?>"</span>
                                    <?php endif; ?>
                                    <?php foreach ($activeFilters as $filter): ?>
                                        <span class="badge" style="background: #007bff;"><?= htmlspecialchars($filter) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Entries selector -->
                    <div style="margin: 15px 0;">
                        <form method="GET" class="form-inline">
                            <?php if ($filterYear): ?><input type="hidden" name="filterYear" value="<?= $filterYear ?>"><?php endif; ?>
                            <?php if ($filterSemester): ?><input type="hidden" name="filterSemester" value="<?= $filterSemester ?>"><?php endif; ?>
                            <?php if ($filterCourse): ?><input type="hidden" name="filterCourse" value="<?= htmlspecialchars($filterCourse) ?>"><?php endif; ?>
                            <?php if ($filterRiskLevel): ?><input type="hidden" name="filterRiskLevel" value="<?= htmlspecialchars($filterRiskLevel) ?>"><?php endif; ?>
                            <?php if (!empty($searchTerm)): ?><input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>"><?php endif; ?>
                            
                            <label>Show 
                                <select name="entries" class="form-control" onchange="this.form.submit()">
                                    <option value="10" <?= ($studentsPerPage == 10) ? 'selected' : '' ?>>10</option>
                                    <option value="25" <?= ($studentsPerPage == 25) ? 'selected' : '' ?>>25</option>
                                    <option value="50" <?= ($studentsPerPage == 50) ? 'selected' : '' ?>>50</option>
                                    <option value="100" <?= ($studentsPerPage == 100) ? 'selected' : '' ?>>100</option>
                                    <option value="<?= $totalStudents ?>" <?= ($studentsPerPage >= $totalStudents) ? 'selected' : '' ?>>All</option>
                                </select> 
                                entries
                            </label>
                        </form>
                    </div>
                    
                    <!-- Results Table -->
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
                                            <?php if ($hasValidFilter || $isSearchResult): ?>
                                                No students found matching your criteria.
                                            <?php else: ?>
                                                No data available. Please check if the API is running.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paginatedStudents as $index => $student): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($student['StudentID']) ?></td>
                                            <td><?= htmlspecialchars($student['sname'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($student['year']) ?></td>
                                            <td><?= htmlspecialchars($student['course']) ?></td>
                                            <td><?= htmlspecialchars($student['semester'] ?? 'N/A') ?></td>
                                            <td>
                                                <?php
                                                $riskClass = '';
                                                if ($student['final_risk_level'] == 'High Risk') {
                                                    $riskClass = 'label-danger';
                                                } elseif ($student['final_risk_level'] == 'Medium Risk') {
                                                    $riskClass = 'label-warning';
                                                } else {
                                                    $riskClass = 'label-success';
                                                }
                                                ?>
                                                <span class="label <?= $riskClass ?>"><?= htmlspecialchars($student['final_risk_level']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($student['dropout_percentage']) ?>%</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#detailsModal<?= $index ?>">
                                                    <i class="fa fa-eye"></i> View
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
                                        
                                        <h4>Recommended Admin Actions:</h4>
                                        <ul class="list-group">
                                            <?php foreach ($student['admin_action'] as $action): ?>
                                                <li class="list-group-item"><?= htmlspecialchars($action) ?></li>
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
                                    <div class="text-muted">Outstanding Amount</div>
                                    <div id="balanceIndicator<?= $index ?>" style="margin-top: 10px; height: 10px;"></div>
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
                                    
                                    <div class="row">
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
                                    </div>
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
    
    new Chart(courseCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                foreach ($courseStats as $course => $stats) {
                    echo "'" . addslashes($course) . "', ";
                }
                ?>
            ],
            datasets: [
                {
                    label: 'High Risk',
                    data: [
                        <?php 
                        foreach ($courseStats as $stats) {
                            echo $stats['High'] . ", ";
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(231, 76, 60, 0.8)'
                },
                {
                    label: 'Medium Risk',
                    data: [
                        <?php 
                        foreach ($courseStats as $stats) {
                            echo $stats['Medium'] . ", ";
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(241, 196, 15, 0.8)'
                },
                {
                    label: 'Low Risk',
                    data: [
                        <?php 
                        foreach ($courseStats as $stats) {
                            echo $stats['Low'] . ", ";
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(46, 204, 113, 0.8)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            aspectRatio: 2,
            plugins: {
                title: {
                    display: true,
                    text: '<?= $hasValidFilter ? " (Filtered)" : "" ?>'
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    beginAtZero: true
                }
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
