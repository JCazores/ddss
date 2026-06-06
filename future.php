<?php 
$page='future';

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

// Get available filters from database
$availableYears = [];
$availableSemesters = [];
$availableCourses = [];
$availableRiskLevels = ['High Risk', 'Medium Risk', 'Low Risk'];

// Get available courses and years from prediction tables
$prediction_query = "SELECT DISTINCT course FROM student_predictions WHERE course IS NOT NULL AND course != '' ORDER BY course";
$prediction_result = $conn->query($prediction_query);
if ($prediction_result) {
    while ($row = $prediction_result->fetch_assoc()) {
        $course = trim($row['course']);
        if ($course && !in_array($course, $availableCourses)) {
            $availableCourses[] = $course;
        }
    }
}

// Get available years from student IDs in predictions
$year_query = "SELECT DISTINCT SUBSTRING(student_id, 5, 4) as student_year 
               FROM student_predictions 
               WHERE student_id REGEXP '^OLFU[0-9]{4}'
               AND SUBSTRING(student_id, 5, 4) REGEXP '^[0-9]{4}$'
               AND CAST(SUBSTRING(student_id, 5, 4) AS UNSIGNED) BETWEEN 2020 AND 2030
               ORDER BY student_year DESC";
$year_result = $conn->query($year_query);
if ($year_result) {
    while ($row = $year_result->fetch_assoc()) {
        $year = intval($row['student_year']);
        if ($year >= 2020 && $year <= 2030 && !in_array($year, $availableYears)) {
            $availableYears[] = $year;
        }
    }
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

// FILTER HANDLING
if (isset($_GET['filterYear']) && !empty($_GET['filterYear']) && $_GET['filterYear'] !== '') {
    $filterYear = intval($_GET['filterYear']);
    if ($filterYear >= 2020 && $filterYear <= 2030 && in_array($filterYear, $availableYears)) {
        $hasValidFilter = true;
    } else {
        $filterYear = null;
    }
}

if (isset($_GET['filterCourse']) && !empty($_GET['filterCourse']) && $_GET['filterCourse'] !== '') {
    $filterCourse = trim($_GET['filterCourse']);
    if (in_array($filterCourse, $availableCourses)) {
        $hasValidFilter = true;
    } else {
        $filterCourse = null;
    }
}

if (isset($_GET['filterRiskLevel']) && !empty($_GET['filterRiskLevel']) && $_GET['filterRiskLevel'] !== '') {
    $filterRiskLevel = trim($_GET['filterRiskLevel']);
    if (in_array($filterRiskLevel, $availableRiskLevels)) {
        $hasValidFilter = true;
    } else {
        $filterRiskLevel = null;
    }
}

// Build active filters array
if ($filterYear !== null) {
    $activeFilters[] = "Academic Year: $filterYear";
}
if ($filterCourse !== null) {
    $activeFilters[] = "Course: $filterCourse";
}
if ($filterRiskLevel !== null) {
    $activeFilters[] = "Risk Level: $filterRiskLevel";
}

// Fetch forecast data from database - FIXED QUERY
$cacheKey = $cache->generateKey('forecast_db', [
    'year' => $filterYear,
    'course' => $filterCourse,
    'risk_level' => $filterRiskLevel,
    'timestamp' => floor(time() / 300) // 5-minute cache
]);

$forecastData = null;
$dataError = null;
$lastPredictionDate = null;

// Check cache or bypass with nocache parameter
$useCache = !isset($_GET['nocache']);
if ($useCache && $cache->exists($cacheKey, 300)) {
    $forecastData = $cache->get($cacheKey);
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        echo "<div class='alert alert-info'>Cache Hit! Using cached forecast data.</div>";
    }
} else {
    try {
        // FIXED: Build query to get latest predictions per student
        $query = "SELECT 
                    p.student_id as StudentID,
                    p.course,
                    p.current_year,
                    p.current_semester_data,
                    p.next_semester_prediction,
                    p.risk_analysis,
                    p.interventions,
                    p.prediction_date
                  FROM student_predictions p
                  WHERE p.prediction_date = (
                      SELECT MAX(p2.prediction_date) 
                      FROM student_predictions p2 
                      WHERE p2.student_id = p.student_id
                  )";
        
        $params = [];
        $types = "";
        
        // Apply filters
        if ($filterYear !== null) {
            $query .= " AND SUBSTRING(p.student_id, 5, 4) = ?";
            $params[] = strval($filterYear);
            $types .= "s";
        }
        
        if ($filterCourse !== null) {
            $query .= " AND p.course = ?";
            $params[] = $filterCourse;
            $types .= "s";
        }
        
        $query .= " ORDER BY p.student_id";
        
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo "<div class='alert alert-info'><strong>Debug Query:</strong><br><pre>" . htmlspecialchars($query) . "</pre>";
            echo "Filters: Year=" . ($filterYear ?? 'none') . ", Course=" . ($filterCourse ?? 'none') . ", RiskLevel=" . ($filterRiskLevel ?? 'none') . "</div>";
        }
        
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $forecasts = [];
        $rowCount = 0;
        
        while ($row = $result->fetch_assoc()) {
            $rowCount++;
            
            // Parse JSON data with error handling
            $current_data = @json_decode($row['current_semester_data'], true);
            $next_data = @json_decode($row['next_semester_prediction'], true);
            $risk_data = @json_decode($row['risk_analysis'], true);
            $interventions = @json_decode($row['interventions'], true);
            
            // Skip if JSON parsing failed
            if (!$current_data || !$next_data) {
                if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                    echo "<div class='alert alert-warning'>Skipped student {$row['StudentID']} - JSON parse error</div>";
                }
                continue;
            }
            
            // Apply risk level filter AFTER parsing
            if ($filterRiskLevel !== null) {
                $currentRisk = isset($current_data['risk_level']) ? $current_data['risk_level'] : '';
                if ($currentRisk !== $filterRiskLevel) {
                    continue;
                }
            }
            
            $forecast = [
                'StudentID' => $row['StudentID'],
                'course' => $row['course'],
                'current_year' => $row['current_year'],
                'current_semester' => [
                    'attendance' => $current_data['attendance'] ?? 0,
                    'gpa' => $current_data['gpa'] ?? 0,
                    'balance' => $current_data['balance'] ?? 0,
                    'risk_level' => $current_data['risk_level'] ?? 'Unknown',
                    'dropout_probability' => $current_data['dropout_probability'] ?? 0,
                    'semester' => $current_data['semester'] ?? 1
                ],
                'next_semester' => [
                    'predicted_attendance' => $next_data['predicted_attendance'] ?? 0,
                    'predicted_gpa' => $next_data['predicted_gpa'] ?? 0,
                    'predicted_balance' => $next_data['predicted_balance'] ?? 0,
                    'predicted_risk_level' => $next_data['predicted_risk_level'] ?? 'Unknown',
                    'predicted_dropout_probability' => $next_data['predicted_dropout_probability'] ?? 0
                ],
                'risk_analysis' => $risk_data ?? [],
                'interventions' => $interventions ?? []
            ];
            
            $forecasts[] = $forecast;
            
            if ($lastPredictionDate === null) {
                $lastPredictionDate = $row['prediction_date'];
            }
        }
        
        $stmt->close();
        
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo "<div class='alert alert-info'><strong>Debug Results:</strong><br>";
            echo "Total rows fetched: $rowCount<br>";
            echo "Forecasts after filtering: " . count($forecasts) . "<br>";
            echo "Last prediction date: " . ($lastPredictionDate ?? 'none') . "</div>";
        }
        
        if (!empty($forecasts)) {
            $forecastData = ['forecasts' => $forecasts];
            if ($useCache) {
                $cache->set($cacheKey, $forecastData);
            }
            
            if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                echo "<div class='alert alert-success'>Successfully loaded " . count($forecasts) . " predictions!</div>";
            }
        } else {
            // Check if there's any data at all
            $count_query = "SELECT COUNT(*) as total FROM student_predictions";
            $count_result = $conn->query($count_query);
            $total_records = 0;
            if ($count_result) {
                $count_row = $count_result->fetch_assoc();
                $total_records = $count_row['total'];
            }
            
            if ($total_records == 0) {
                $dataError = "No prediction data found. Please run the prediction system first.";
            } else {
                $dataError = "No predictions match your filters. Total predictions in database: " . $total_records;
            }
        }
        
    } catch (Exception $e) {
        $dataError = "Database error: " . $e->getMessage();
        error_log("Forecast DB Error: " . $e->getMessage());
        
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo "<div class='alert alert-danger'><strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Process forecast data
$currentStats = ['High' => 0, 'Medium' => 0, 'Low' => 0];
$forecastStats = ['High' => 0, 'Medium' => 0, 'Low' => 0];
$students = [];

if ($forecastData && isset($forecastData['forecasts'])) {
    foreach ($forecastData['forecasts'] as $forecast) {
        $students[] = $forecast;
        
        // Count current stats
        $currentRisk = $forecast['current_semester']['risk_level'];
        if ($currentRisk == 'High Risk') $currentStats['High']++;
        elseif ($currentRisk == 'Medium Risk') $currentStats['Medium']++;
        else $currentStats['Low']++;
        
        // Count forecast stats
        $nextRisk = $forecast['next_semester']['predicted_risk_level'];
        if ($nextRisk == 'High Risk') $forecastStats['High']++;
        elseif ($nextRisk == 'Medium Risk') $forecastStats['Medium']++;
        else $forecastStats['Low']++;
    }
}

// Calculate changes
$riskChanges = [
    'High' => $forecastStats['High'] - $currentStats['High'],
    'Medium' => $forecastStats['Medium'] - $currentStats['Medium'],
    'Low' => $forecastStats['Low'] - $currentStats['Low']
];

// Calculate course-wise stats
$courseStats = [];
$forecastCourseStats = [];

foreach ($students as $student) {
    $course = $student['course'];
    
    if (!isset($courseStats[$course])) {
        $courseStats[$course] = ['High' => 0, 'Medium' => 0, 'Low' => 0, 'Total' => 0];
        $forecastCourseStats[$course] = ['High' => 0, 'Medium' => 0, 'Low' => 0, 'Total' => 0];
    }
    
    // Current stats
    $currentRisk = $student['current_semester']['risk_level'];
    if ($currentRisk == 'High Risk') $courseStats[$course]['High']++;
    elseif ($currentRisk == 'Medium Risk') $courseStats[$course]['Medium']++;
    else $courseStats[$course]['Low']++;
    $courseStats[$course]['Total']++;
    
    // Forecast stats
    $nextRisk = $student['next_semester']['predicted_risk_level'];
    if ($nextRisk == 'High Risk') $forecastCourseStats[$course]['High']++;
    elseif ($nextRisk == 'Medium Risk') $forecastCourseStats[$course]['Medium']++;
    else $forecastCourseStats[$course]['Low']++;
    $forecastCourseStats[$course]['Total']++;
}

// Calculate cohort trends from filtered forecast data
$latestTrends = null;
$cohortPredictionDate = $lastPredictionDate;

if (!empty($students)) {
    try {
        // Initialize counters
        $escalating = 0;
        $improving = 0;
        $stable = 0;
        $critical = 0;
        $high = 0;
        $medium = 0;
        $totalProbabilityChange = 0;
        $needsImmediateIntervention = 0;
        
        // Analyze each student in the filtered dataset
        foreach ($students as $student) {
            $currentRisk = $student['current_semester']['risk_level'];
            $predictedRisk = $student['next_semester']['predicted_risk_level'];
            
            $currentProb = $student['current_semester']['dropout_probability'];
            $predictedProb = $student['next_semester']['predicted_dropout_probability'];
            
            $probabilityChange = $predictedProb - $currentProb;
            $totalProbabilityChange += $probabilityChange;
            
            // Risk level changes
            $riskLevels = ['Low Risk' => 1, 'Medium Risk' => 2, 'High Risk' => 3];
            $currentLevel = $riskLevels[$currentRisk] ?? 2;
            $predictedLevel = $riskLevels[$predictedRisk] ?? 2;
            
            if ($predictedLevel > $currentLevel) {
                $escalating++;
            } elseif ($predictedLevel < $currentLevel) {
                $improving++;
            } else {
                $stable++;
            }
            
            // Intervention urgency based on predicted risk and probability
            if ($predictedRisk == 'High Risk' && $predictedProb > 0.7) {
                $critical++;
                $needsImmediateIntervention++;
            } elseif ($predictedRisk == 'High Risk' || ($predictedRisk == 'Medium Risk' && $predictedProb > 0.5)) {
                $high++;
                if ($probabilityChange > 0.15) {
                    $needsImmediateIntervention++;
                }
            } elseif ($predictedRisk == 'Medium Risk') {
                $medium++;
            }
        }
        
        $totalStudents = count($students);
        $avgProbabilityChange = $totalStudents > 0 ? ($totalProbabilityChange / $totalStudents) * 100 : 0;
        
        // Build trends array
        $latestTrends = [
            'total_students' => $totalStudents,
            'risk_level_changes' => [
                'escalating' => $escalating,
                'escalating_percentage' => $totalStudents > 0 ? ($escalating / $totalStudents) * 100 : 0,
                'improving' => $improving,
                'improving_percentage' => $totalStudents > 0 ? ($improving / $totalStudents) * 100 : 0,
                'stable' => $stable
            ],
            'intervention_urgency' => [
                'critical' => $critical,
                'high' => $high,
                'medium' => $medium
            ],
            'average_probability_change' => $avgProbabilityChange,
            'students_needing_immediate_intervention' => $needsImmediateIntervention
        ];
        
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo "<div class='alert alert-info'><strong>Cohort Trends Debug:</strong><br>";
            echo "Calculated from filtered data<br>";
            echo "Total students analyzed: " . $totalStudents . "<br>";
            echo "Escalating: $escalating, Improving: $improving, Stable: $stable<br>";
            echo "Critical: $critical, High: $high, Medium: $medium</div>";
        }
        
    } catch (Exception $e) {
        error_log("Error calculating cohort trends: " . $e->getMessage());
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo "<div class='alert alert-danger'>Cohort trends error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Future Forecasting - Dropout Prediction System</title>
    <link rel="icon" type="image/png" href="img/icon1.jpg">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.css">
    <link rel="stylesheet" href="css/style1.css">
    <link rel="stylesheet" href="css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        .stat-card {
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            color: white;
            position: relative;
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
        .future-card {
            background: linear-gradient(45deg, #9b59b6, #8e44ad);
        }
        .forecast-change {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 12px;
            background: rgba(255,255,255,0.2);
            padding: 2px 6px;
            border-radius: 10px;
        }
        .chart-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .chart-container canvas {
            max-height: 300px !important;
        }
        .gauge-chart {
            flex: 1;
            min-width: 200px;
            max-width: 300px;
            height: 180px;
        }
        .section-title {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .prediction-info {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .prediction-info .date {
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>

<body>
    <?php include("php/header.php"); ?>
    
    <div id="page-wrapper">
        <div id="page-inner">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="page-head-line">Future Forecasting Dashboard</h1>
                    <p class="lead">Predictive analysis for next semester's dropout risk trends</p>
                </div>
            </div>
            
           
            
            <?php if ($dataError): ?>
            <div class="alert alert-danger">
                <h4><i class="fa fa-exclamation-triangle"></i> Data Not Available</h4>
                <p><strong><?= htmlspecialchars($dataError) ?></strong></p>
                <p>To generate predictions, run the following command:</p>
                <pre style="background: #2c3e50; color: #ecf0f1; padding: 10px; border-radius: 4px;">python predict.py</pre>
                <p class="help-block">This will analyze student data and save predictions to the database.</p>
            </div>
            <?php endif; ?>
            
            <!-- Filter Section -->
            <div class="panel panel-default" style="border-color:rgba(1, 129, 55);">
                <div class="panel-heading" style="background-color: rgba(1, 129, 55); color: white;">
                    <div class="panel-title">
                        <i class="fa fa-filter"></i> Filter Results
                    </div>
                </div>
                <div class="panel-body">
                    <form method="GET" action="" id="filterForm">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filterYear">Academic Year</label>
                                    <select name="filterYear" id="filterYear" class="form-control">
                                        <option value="">All Years</option>
                                        <?php 
                                        foreach ($availableYears as $yearOption) {
                                            $selected = ($filterYear !== null && $filterYear == $yearOption) ? 'selected' : '';
                                            echo "<option value='$yearOption' $selected>$yearOption</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filterCourse">Course</label>
                                    <select name="filterCourse" id="filterCourse" class="form-control">
                                        <option value="">All Courses</option>
                                        <?php 
                                        foreach ($availableCourses as $courseOption) {
                                            $selected = ($filterCourse !== null && $filterCourse == $courseOption) ? 'selected' : '';
                                            echo "<option value='" . htmlspecialchars($courseOption) . "' $selected>" . htmlspecialchars($courseOption) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="filterRiskLevel">Current Risk Level</label>
                                    <select name="filterRiskLevel" id="filterRiskLevel" class="form-control">
                                        <option value="">All Risk Levels</option>
                                        <?php 
                                        foreach ($availableRiskLevels as $riskOption) {
                                            $selected = ($filterRiskLevel !== null && $filterRiskLevel == $riskOption) ? 'selected' : '';
                                            echo "<option value='" . htmlspecialchars($riskOption) . "' $selected>" . htmlspecialchars($riskOption) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-filter"></i> Apply Filters
                                </button>
                                <?php if ($hasValidFilter): ?>
                                    <a href="future.php" class="btn btn-default">
                                        <i class="fa fa-times"></i> Clear Filters
                                    </a>
                                <?php endif; ?>
                                <a href="?nocache=1" class="btn btn-warning">
                                    <i class="fa fa-refresh"></i> Refresh Data
                                </a>
                                
                            </div>
                        </div>
                        
                        <?php if ($hasValidFilter): ?>
                            <div class="alert alert-info" style="margin-top: 15px; margin-bottom: 0;">
                                <strong>Active Filters:</strong> <?= implode(', ', $activeFilters) ?>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <?php if ($forecastData && !empty($students)): ?>
            
            <!-- Current vs Forecast Comparison -->
            <div class="row">
                <div class="col-md-12">
                    <h2 class="section-title">
                        <i class="fa fa-line-chart"></i> Current vs Next Semester Forecast
                    </h2>
                </div>
            </div>
            
            <!-- Current Stats Cards -->
            <div class="row">
                <div class="col-md-12">
                    <h3 style="color: #34495e; margin-bottom: 15px;">
                        <i class="fa fa-calendar"></i> Current Status
                    </h3>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card high-risk">
                        <h3><i class="fa fa-warning"></i> High Risk</h3>
                        <div class="count"><?= $currentStats['High'] ?></div>
                        <p><?= $currentStats['High'] > 0 ? round(($currentStats['High'] / max(1, array_sum($currentStats))) * 100, 1) : 0 ?>%</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card medium-risk">
                        <h3><i class="fa fa-exclamation-circle"></i> Medium Risk</h3>
                        <div class="count"><?= $currentStats['Medium'] ?></div>
                        <p><?= $currentStats['Medium'] > 0 ? round(($currentStats['Medium'] / max(1, array_sum($currentStats))) * 100, 1) : 0 ?>%</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card low-risk">
                        <h3><i class="fa fa-check-circle"></i> Low Risk</h3>
                        <div class="count"><?= $currentStats['Low'] ?></div>
                        <p><?= $currentStats['Low'] > 0 ? round(($currentStats['Low'] / max(1, array_sum($currentStats))) * 100, 1) : 0 ?>%</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card total-students">
                        <h3><i class="fa fa-users"></i> Total Students</h3>
                        <div class="count"><?= array_sum($currentStats) ?></div>
                        <p>Current records</p>
                    </div>
                </div>
            </div>

            <!-- Forecast Stats Cards -->
            <div class="row" style="margin-top: 30px;">
                <div class="col-md-12">
                    <h3 style="color: #34495e; margin-bottom: 15px;">
                        <i class="fa fa-crystal-ball"></i> Next Semester Forecast
                    </h3>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card high-risk">
                        <h3><i class="fa fa-warning"></i> High Risk</h3>
                        <div class="count"><?= $forecastStats['High'] ?></div>
                        <p><?= $forecastStats['High'] > 0 ? round(($forecastStats['High'] / max(1, array_sum($forecastStats))) * 100, 1) : 0 ?>%</p>
                        <div class="forecast-change">
                            <?php if ($riskChanges['High'] > 0): ?>
                                <i class="fa fa-arrow-up"></i> +<?= $riskChanges['High'] ?>
                            <?php elseif ($riskChanges['High'] < 0): ?>
                                <i class="fa fa-arrow-down"></i> <?= $riskChanges['High'] ?>
                            <?php else: ?>
                                <i class="fa fa-minus"></i> 0
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card medium-risk">
                        <h3><i class="fa fa-exclamation-circle"></i> Medium Risk</h3>
                        <div class="count"><?= $forecastStats['Medium'] ?></div>
                        <p><?= $forecastStats['Medium'] > 0 ? round(($forecastStats['Medium'] / max(1, array_sum($forecastStats))) * 100, 1) : 0 ?>%</p>
                        <div class="forecast-change">
                            <?php if ($riskChanges['Medium'] > 0): ?>
                                <i class="fa fa-arrow-up"></i> +<?= $riskChanges['Medium'] ?>
                            <?php elseif ($riskChanges['Medium'] < 0): ?>
                                <i class="fa fa-arrow-down"></i> <?= $riskChanges['Medium'] ?>
                            <?php else: ?>
                                <i class="fa fa-minus"></i> 0
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card low-risk">
                        <h3><i class="fa fa-check-circle"></i> Low Risk</h3>
                        <div class="count"><?= $forecastStats['Low'] ?></div>
                        <p><?= $forecastStats['Low'] > 0 ? round(($forecastStats['Low'] / max(1, array_sum($forecastStats))) * 100, 1) : 0 ?>%</p>
                        <div class="forecast-change">
                            <?php if ($riskChanges['Low'] > 0): ?>
                                <i class="fa fa-arrow-up"></i> +<?= $riskChanges['Low'] ?>
                            <?php elseif ($riskChanges['Low'] < 0): ?>
                                <i class="fa fa-arrow-down"></i> <?= $riskChanges['Low'] ?>
                            <?php else: ?>
                                <i class="fa fa-minus"></i> 0
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card future-card">
                        <h3><i class="fa fa-users"></i> Forecasted Total</h3>
                        <div class="count"><?= array_sum($forecastStats) ?></div>
                        <p>Next semester</p>
                    </div>
                </div>
            </div>

            <!-- Comparison Chart -->
            <div class="chart-container">
                <div class="row">
                    <div class="col-md-8">
                        <h4><i class="fa fa-bar-chart"></i> Risk Level Comparison</h4>
                        <canvas id="comparisonChart"></canvas>
                    </div>
                    <div class="col-md-4">
                        <h4><i class="fa fa-info-circle"></i> Key Changes</h4>
                        <div class="change-item" style="padding: 10px; margin: 5px 0; border-left: 4px solid #e74c3c; background: #fdf2f2; border-radius: 4px;">
                            <strong>High Risk:</strong>
                            <span class="pull-right">
                                <?= $currentStats['High'] ?> → <?= $forecastStats['High'] ?>
                                (<?= $riskChanges['High'] >= 0 ? '+' : '' ?><?= $riskChanges['High'] ?>)
                            </span>
                        </div>
                        <div class="change-item" style="padding: 10px; margin: 5px 0; border-left: 4px solid #f1c40f; background: #fffdf2; border-radius: 4px;">
                            <strong>Medium Risk:</strong>
                            <span class="pull-right">
                                <?= $currentStats['Medium'] ?> → <?= $forecastStats['Medium'] ?>
                                (<?= $riskChanges['Medium'] >= 0 ? '+' : '' ?><?= $riskChanges['Medium'] ?>)
                            </span>
                        </div>
                        <div class="change-item" style="padding: 10px; margin: 5px 0; border-left: 4px solid #2ecc71; background: #f2fdf6; border-radius: 4px;">
                            <strong>Low Risk:</strong>
                            <span class="pull-right">
                                <?= $currentStats['Low'] ?> → <?= $forecastStats['Low'] ?>
                                (<?= $riskChanges['Low'] >= 0 ? '+' : '' ?><?= $riskChanges['Low'] ?>)
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gauge Charts -->
            <div class="chart-container">
                <h4><i class="fa fa-tachometer"></i> Risk Level Distribution Forecast</h4>
                <div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px;">
                    <div class="gauge-chart" id="forecastHighRiskGauge"></div>
                    <div class="gauge-chart" id="forecastMediumRiskGauge"></div>
                    <div class="gauge-chart" id="forecastLowRiskGauge"></div>
                </div>
            </div>

            <!-- Course-wise Forecast -->
            <?php if (!empty($forecastCourseStats)): ?>
            <div class="chart-container">
                <h4><i class="fa fa-graduation-cap"></i> Forecasted Course-wise Risk Distribution</h4>
                <canvas id="forecastCourseChart"></canvas>
            </div>
            <?php endif; ?>

            <!-- Intervention Recommendations -->
            <div class="panel panel-default" style="border-color:rgba(1, 129, 55);">
                <div class="panel-heading" style="background-color: rgba(1, 129, 55); color: white;">
                    <div class="panel-title">
                        <i class="fa fa-lightbulb-o"></i> Proactive Intervention Recommendations
                    </div>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <?php if ($riskChanges['High'] > 0): ?>
                        <div class="col-md-4">
                            <div class="alert alert-danger">
                                <h4><i class="fa fa-warning"></i> High Priority</h4>
                                <p><strong>Expected increase of <?= $riskChanges['High'] ?> high-risk students</strong></p>
                                <ul style="margin-bottom: 0; font-size: 12px;">
                                    <li>Implement early intervention programs</li>
                                    <li>Increase counseling services capacity</li>
                                    <li>Review financial aid programs</li>
                                    <li>Enhanced academic support services</li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($riskChanges['Medium'] > 0): ?>
                        <div class="col-md-4">
                            <div class="alert alert-warning">
                                <h4><i class="fa fa-exclamation-triangle"></i> Medium Priority</h4>
                                <p><strong>Expected increase of <?= $riskChanges['Medium'] ?> medium-risk students</strong></p>
                                <ul style="margin-bottom: 0; font-size: 12px;">
                                    <li>Strengthen mentorship programs</li>
                                    <li>Improve attendance monitoring</li>
                                    <li>Enhance study support groups</li>
                                    <li>Regular progress check-ins</li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($riskChanges['Low'] > 0): ?>
                        <div class="col-md-4">
                            <div class="alert alert-success">
                                <h4><i class="fa fa-check-circle"></i> Positive Trend</h4>
                                <p><strong><?= $riskChanges['Low'] ?> more students moving to low-risk</strong></p>
                                <ul style="margin-bottom: 0; font-size: 12px;">
                                    <li>Maintain current successful strategies</li>
                                    <li>Document best practices</li>
                                    <li>Consider scaling effective programs</li>
                                    <li>Continue positive reinforcement</li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($riskChanges['High'] <= 0 && $riskChanges['Medium'] <= 0 && $riskChanges['Low'] <= 0): ?>
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h4><i class="fa fa-info-circle"></i> Stable Forecast</h4>
                                <p>The forecast shows relatively stable risk levels for next semester.</p>
                                <ul style="margin-bottom: 0; font-size: 12px;">
                                    <li>Regular monitoring of key indicators</li>
                                    <li>Maintain current support programs</li>
                                    <li>Prepare contingency plans for changes</li>
                                    <li>Continue data collection for model improvement</li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Cohort Trends (if available) -->
            <?php if ($latestTrends): ?>
            <div class="panel panel-default" style="border-color:#9b59b6;">
                <div class="panel-heading" style="background-color: #9b59b6; color: white;">
                    <div class="panel-title">
                        <i class="fa fa-line-chart"></i> Cohort Trends Summary
                    </div>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="well text-center">
                                <h4>Risk Level Changes</h4>
                                <p><strong class="text-danger">Escalating:</strong> <?= $latestTrends['risk_level_changes']['escalating'] ?> 
                                   (<?= round($latestTrends['risk_level_changes']['escalating_percentage'], 1) ?>%)</p>
                                <p><strong class="text-success">Improving:</strong> <?= $latestTrends['risk_level_changes']['improving'] ?> 
                                   (<?= round($latestTrends['risk_level_changes']['improving_percentage'], 1) ?>%)</p>
                                <p><strong class="text-muted">Stable:</strong> <?= $latestTrends['risk_level_changes']['stable'] ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="well text-center">
                                <h4>Intervention Urgency</h4>
                                <p><strong class="text-danger">Critical:</strong> <?= $latestTrends['intervention_urgency']['critical'] ?> students</p>
                                <p><strong class="text-warning">High:</strong> <?= $latestTrends['intervention_urgency']['high'] ?> students</p>
                                <p><strong class="text-info">Medium:</strong> <?= $latestTrends['intervention_urgency']['medium'] ?> students</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="well text-center">
                                <h4>Overall Statistics</h4>
                                <p><strong>Total Analyzed:</strong> <?= $latestTrends['total_students'] ?> students</p>
                                <p><strong>Avg. Probability Change:</strong> 
                                   <span class="<?= $latestTrends['average_probability_change'] > 0 ? 'text-danger' : 'text-success' ?>">
                                       <?= $latestTrends['average_probability_change'] >= 0 ? '+' : '' ?><?= round($latestTrends['average_probability_change'], 2) ?>%
                                   </span>
                                </p>
                                <p><strong>Needs Immediate Action:</strong> 
                                   <?= $latestTrends['students_needing_immediate_intervention'] ?> students</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            
            <?php elseif (!$dataError): ?>
            
            <div class="alert alert-warning">
                <h4><i class="fa fa-exclamation-triangle"></i> No Forecast Data Available</h4>
                <p>No prediction data found in the database.</p>
                <p><strong>To generate predictions:</strong></p>
                <ol>
                    <li>Ensure your database contains student data in the appropriate tables</li>
                    <li>Run the prediction system: <code>python predict.py</code></li>
                    <li>Wait for the prediction process to complete</li>
                    <li>Refresh this page to view the forecast results</li>
                </ol>
                <p class="help-block">The prediction system will analyze all student data and save forecasts to the database.</p>
            </div>
            
            <?php endif; ?>
            
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($forecastData && !empty($students)): ?>
            initializeForecastCharts();
            <?php endif; ?>
        });

        function initializeForecastCharts() {
            // Comparison Chart
            const comparisonCtx = document.getElementById('comparisonChart');
            if (comparisonCtx) {
                new Chart(comparisonCtx, {
                    type: 'bar',
                    data: {
                        labels: ['High Risk', 'Medium Risk', 'Low Risk'],
                        datasets: [
                            {
                                label: 'Current',
                                data: [<?= $currentStats['High'] ?>, <?= $currentStats['Medium'] ?>, <?= $currentStats['Low'] ?>],
                                backgroundColor: ['rgba(231, 76, 60, 0.8)', 'rgba(241, 196, 15, 0.8)', 'rgba(46, 204, 113, 0.8)']
                            },
                            {
                                label: 'Forecasted',
                                data: [<?= $forecastStats['High'] ?>, <?= $forecastStats['Medium'] ?>, <?= $forecastStats['Low'] ?>],
                                backgroundColor: ['rgba(231, 76, 60, 0.4)', 'rgba(241, 196, 15, 0.4)', 'rgba(46, 204, 113, 0.4)'],
                                borderColor: ['rgb(231, 76, 60)', 'rgb(241, 196, 15)', 'rgb(46, 204, 113)'],
                                borderWidth: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        aspectRatio: 2.5,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Current vs Forecasted Risk Distribution'
                            },
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Students'
                                }
                            }
                        }
                    }
                });
            }

            // Gauge Charts
            const totalForecast = <?= array_sum($forecastStats) ?>;
            renderForecastGauge("#forecastHighRiskGauge", <?= $forecastStats['High'] ?>, '#e74c3c', 'High Risk Forecast', totalForecast);
            renderForecastGauge("#forecastMediumRiskGauge", <?= $forecastStats['Medium'] ?>, '#f1c40f', 'Medium Risk Forecast', totalForecast);
            renderForecastGauge("#forecastLowRiskGauge", <?= $forecastStats['Low'] ?>, '#2ecc71', 'Low Risk Forecast', totalForecast);

            // Course Chart
            <?php if (!empty($forecastCourseStats)): ?>
            const forecastCourseCtx = document.getElementById('forecastCourseChart');
            if (forecastCourseCtx) {
                new Chart(forecastCourseCtx, {
                    type: 'bar',
                    data: {
                        labels: [
                            <?php 
                            foreach ($forecastCourseStats as $course => $stats) {
                                echo "'" . addslashes($course) . "', ";
                            }
                            ?>
                        ],
                        datasets: [
                            {
                                label: 'High Risk',
                                data: [
                                    <?php 
                                    foreach ($forecastCourseStats as $stats) {
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
                                    foreach ($forecastCourseStats as $stats) {
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
                                    foreach ($forecastCourseStats as $stats) {
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
                        maintainAspectRatio: true,
                        aspectRatio: 2.2,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Forecasted Risk by Course'
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
            <?php endif; ?>
        }

        function renderForecastGauge(element, value, color, label, total) {
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
                            return value + " student(s)";
                        }
                    }
                },
                colors: [color],
                labels: [label],
                series: [Math.round((value / (total || 1)) * 100)]
            };

            new ApexCharts(document.querySelector(element), options).render();
        }
    </script>

    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="js/jquery.metisMenu.js"></script>
    <script src="js/custom1.js"></script>

</body>
</html>

<?php $conn->close(); ?>