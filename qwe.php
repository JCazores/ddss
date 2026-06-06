<?php $page='predict';
include("php/dbconnect.php");

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: predict.php");
    exit();
}

$studentID = $conn->real_escape_string($_GET['id']);

// Run Python prediction script if not already loaded
if (!isset($_SESSION['prediction_data']) || true) { // Always refresh data
    $python = "C:\\Users\\Christian Azores\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
    $script = "C:\\Users\\Christian Azores\\PycharmProjects\\PythonProject\\predict.py";
    $cmd = "\"$python\" \"$script\" 2>&1";
    exec($cmd, $output, $return_var);
    if ($return_var !== 0) die("Prediction Script Error! Return Code: $return_var");

    $json_output = implode("", $output);
    $data = json_decode($json_output, true);
    if (!$data || !isset($data['results'])) die("No results found in prediction data.");

    $_SESSION['prediction_data'] = $data;
} else {
    $data = $_SESSION['prediction_data'];
}

// Find the specific student in the results
$student = null;
foreach ($data['results'] as $result) {
    if ($result['StudentID'] === $studentID) {
        $student = $result;
        break;
    }
}

if (!$student) {
    die("Student not found in prediction data.");
}

// Get additional data from database
$table_name = $conn->real_escape_string($student['table']);
$res = $conn->query("SELECT * FROM `$table_name` WHERE StudentID = '$studentID'");
if ($res && $row = $res->fetch_assoc()) {
    $student = array_merge($student, $row);
} else {
    die("Student data not found in database.");
}

// Fetch historical data for trends if available
$historyQuery = $conn->query("SELECT semester, GPA, Attendance, balance FROM `student_history` WHERE StudentID = '$studentID' ORDER BY semester ASC");
$history = [];
$semesters = [];
$gpaTrend = [];
$attendanceTrend = [];
$balanceTrend = [];

if ($historyQuery && $historyQuery->num_rows > 0) {
    while ($histRow = $historyQuery->fetch_assoc()) {
        $semesters[] = $histRow['semester'];
        $gpaTrend[] = $histRow['GPA'];
        $attendanceTrend[] = $histRow['Attendance'];
        $balanceTrend[] = $histRow['balance'];
        $history[] = $histRow;
    }
} else {
    // Use current data for visualization if no history
    $semesters = ["Current"];
    $gpaTrend = [$student['GPA']];
    $attendanceTrend = [$student['Attendance']];
    $balanceTrend = [$student['balance']];
}

// Calculate risk score (0-100) based on prediction percentage
$riskScore = $student['dropout_percentage'];

// Calculate risk trends based on history or simulate if none exists
$riskTrend = [];
if (count($history) > 0) {
    // Calculate simulated historical risk based on GPA and attendance
    foreach ($history as $idx => $hist) {
        // Simple algorithm - can be replaced with your actual risk calculation
        $historicalRisk = 100 - (($hist['GPA'] / 4) * 50 + ($hist['Attendance'] / 100) * 50);
        $riskTrend[] = max(0, min(100, $historicalRisk)); // Keep between 0-100
    }
} else {
    // Simulate a trend with the current risk as endpoint
    $riskTrend = [max(0, $riskScore - 15), $riskScore - 5, $riskScore];
    $semesters = ["2 Semesters Ago", "Previous Semester", "Current"];
}

// Risk category thresholds
$riskCategories = [
    'attendance' => ['Low' => 90, 'Medium' => 80, 'High' => 0],
    'gpa' => ['Low' => 3.0, 'Medium' => 2.0, 'High' => 0],
    'financial' => ['Low' => 1000, 'Medium' => 5000, 'High' => 10000]
];

// Get risk level for each category
function getRiskLevel($value, $category, $categories) {
    if ($category == 'attendance') {
        if ($value >= $categories[$category]['Low']) return "Low";
        if ($value >= $categories[$category]['Medium']) return "Medium";
        return "High";
    } else if ($category == 'gpa') {
        if ($value >= $categories[$category]['Low']) return "Low";
        if ($value >= $categories[$category]['Medium']) return "Medium";
        return "High";
    } else if ($category == 'financial') {
        if ($value <= $categories[$category]['Low']) return "Low";
        if ($value <= $categories[$category]['Medium']) return "Medium";
        return "High";
    }
    return "Unknown";
}

$attendanceRisk = getRiskLevel($student['Attendance'], 'attendance', $riskCategories);
$gpaRisk = getRiskLevel($student['GPA'], 'gpa', $riskCategories);
$financialRisk = getRiskLevel($student['balance'], 'financial', $riskCategories);

// Calculate risk factors impact percentages
$riskFactors = [];
$totalImpact = 0;

// Extract key risk factors from reasons
foreach ($student['reasons'] as $reason) {
    if (stripos($reason, 'financial') !== false || stripos($reason, 'balance') !== false) {
        $riskFactors['Financial Issues'] = 35;
        $totalImpact += 35;
    } else if (stripos($reason, 'attendance') !== false) {
        $riskFactors['Poor Attendance'] = 25;
        $totalImpact += 25;
    } else if (stripos($reason, 'gpa') !== false || stripos($reason, 'academic') !== false) {
        $riskFactors['Low GPA'] = 30;
        $totalImpact += 30;
    } else if (stripos($reason, 'engagement') !== false || stripos($reason, 'participation') !== false) {
        $riskFactors['Low Engagement'] = 20;
        $totalImpact += 20;
    } else {
        $riskFactors['Other Factors'] = 10;
        $totalImpact += 10;
    }
}

// Normalize percentages if needed
if ($totalImpact > 0) {
    foreach ($riskFactors as $key => $value) {
        $riskFactors[$key] = round(($value / $totalImpact) * 100);
    }
}

// Calculate predicted improvement with interventions
$interventionImpact = [
    'Financial Aid' => ($financialRisk == 'High') ? 30 : (($financialRisk == 'Medium') ? 15 : 5),
    'Academic Support' => ($gpaRisk == 'High') ? 25 : (($gpaRisk == 'Medium') ? 15 : 5),
    'Attendance Monitoring' => ($attendanceRisk == 'High') ? 20 : (($attendanceRisk == 'Medium') ? 10 : 3),
    'Counseling' => 15
];

// Calculate projected risk after interventions
$projectedRisk = max(0, $riskScore - array_sum($interventionImpact));

// Forecast next semester risk if no intervention
$forecastedRisk = min(100, $riskScore + 10); // Simple forecasting logic
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Dropout Risk Statistics</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.css">
    <link rel="stylesheet" href="css/style1.css">
    <link rel="stylesheet" href="css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        /* Global Styling */
        body {
            background-color: #f5f5f5;
        }
        .stats-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .stat-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 14px;
            text-transform: uppercase;
            color: #777;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .stat-card {
            text-align: center;
            border-radius: 8px;
            padding: 20px 15px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
            color: white;
        }
        .stat-card:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        /* Basketball Style Stats */
        .jersey-number {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 48px;
            font-weight: bold;
            opacity: 0.2;
        }
        .player-card {
            display: flex;
            align-items: center;
            background: linear-gradient(45deg, rgba(1, 129, 55, 0.9), rgba(1, 129, 55, 0.7));
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            color: white;
        }
        .player-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-right: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }
        .player-info {
            flex: 1;
        }
        .player-name {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .player-position {
            font-size: 16px;
            opacity: 0.8;
            margin-bottom: 15px;
        }
        .player-stats {
            display: flex;
            gap: 20px;
        }
        .player-stat {
            text-align: center;
        }
        .player-stat-value {
            font-size: 24px;
            font-weight: bold;
        }
        .player-stat-label {
            font-size: 12px;
            opacity: 0.7;
        }
        
        /* Risk Meter */
        .risk-meter-container {
            position: relative;
            width: 100%;
            margin: 40px 0;
        }
        .risk-meter {
            height: 30px;
            border-radius: 15px;
            background: linear-gradient(90deg, #2ecc71, #f1c40f, #e74c3c);
            position: relative;
            overflow: hidden;
        }
        .risk-meter-pointer {
            position: absolute;
            width: 4px;
            height: 40px;
            background-color: #333;
            top: -5px;
            transform: translateX(-50%);
        }
        .risk-meter-label {
            position: absolute;
            top: -25px;
            transform: translateX(-50%);
            font-weight: bold;
            font-size: 16px;
        }
        
        /* Risk Categories */
        .risk-category {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .risk-indicator {
            width: 80px;
            height: 30px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 15px;
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
        
        /* Charts */
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        
        /* Performance Radar */
        .performance-radar {
            margin: 30px 0;
            position: relative;
        }
        
        /* Forecast Section */
        .forecast-container {
            background: #2c3e50;
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .forecast-title {
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
        .forecast-info {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }
        .forecast-value {
            text-align: center;
        }
        .forecast-label {
            font-size: 14px;
            opacity: 0.7;
            margin-bottom: 10px;
        }
        .forecast-number {
            font-size: 32px;
            font-weight: bold;
        }
        
        /* Basketball Court Visual */
        .court-visual {
            background-image: url('images/court-bg.png');
            background-size: cover;
            background-position: center;
            height: 300px;
            border-radius: 10px;
            position: relative;
            margin: 30px 0;
            overflow: hidden;
        }
        .player-marker {
            position: absolute;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: rgba(231, 76, 60, 0.8);
            border: 2px solid white;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        /* Intervention Impact */
        .impact-bar {
            height: 25px;
            border-radius: 12.5px;
            margin-bottom: 15px;
            background-color: #eee;
            position: relative;
            overflow: hidden;
        }
        .impact-fill {
            height: 100%;
            background-color: #3498db;
            display: flex;
            align-items: center;
            padding-left: 15px;
            color: white;
            font-weight: bold;
        }
        
        /* Responsive fixes */
        @media (max-width: 768px) {
            .player-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .player-photo {
                margin-right: 0;
                margin-bottom: 20px;
            }
            .player-stats {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <?php include("php/header.php"); ?>
    
    <div id="page-wrapper">
        <div id="page-inner">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="page-head-line">Student Dropout Risk Statistics</h1>
                    <div class="row">
                        <div class="col-md-6">
                            <a href="predict.php" class="btn btn-primary">
                                <i class="fa fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <button class="btn btn-success" onclick="window.print()">
                                <i class="fa fa-print"></i> Print Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Player Card Section -->
            <div class="player-card">
                <div class="player-photo">
                    <i class="fa fa-user"></i>
                    <div class="jersey-number"><?= substr($student['StudentID'], -2) ?></div>
                </div>
                <div class="player-info">
                    <div class="player-name"><?= htmlspecialchars($student['sname']) ?></div>
                    <div class="player-position"><?= htmlspecialchars($student['course']) ?> • Year <?= htmlspecialchars($student['year']) ?> • <?= isset($student['semester']) ? htmlspecialchars($student['semester']) : 'Current' ?> Semester</div>
                    <div class="player-stats">
                        <div class="player-stat">
                            <div class="player-stat-value"><?= htmlspecialchars($student['GPA']) ?></div>
                            <div class="player-stat-label">GPA</div>
                        </div>
                        <div class="player-stat">
                            <div class="player-stat-value"><?= htmlspecialchars($student['Attendance']) ?>%</div>
                            <div class="player-stat-label">ATTENDANCE</div>
                        </div>
                        <div class="player-stat">
                            <div class="player-stat-value">₱<?= number_format($student['balance']) ?></div>
                            <div class="player-stat-label">BALANCE</div>
                        </div>
                        <div class="player-stat">
                            <div class="player-stat-value"><?= htmlspecialchars($student['dropout_percentage']) ?>%</div>
                            <div class="player-stat-label">DROPOUT RISK</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Risk Score Section -->
            <div class="row">
                <div class="col-md-12">
                    <div class="stats-container">
                        <h3>Dropout Risk Assessment</h3>
                        <div class="risk-meter-container">
                            <div class="risk-meter"></div>
                            <div class="risk-meter-pointer" style="left: <?= $riskScore ?>%;"></div>
                            <div class="risk-meter-label" style="left: <?= $riskScore ?>%;"><?= $riskScore ?>%</div>
                            <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                                <div>Low Risk (0%)</div>
                                <div>Medium Risk (50%)</div>
                                <div>High Risk (100%)</div>
                            </div>
                        </div>
                        
                        <div class="alert <?= ($student['final_risk_level'] == 'High Risk') ? 'alert-danger' : (($student['final_risk_level'] == 'Medium Risk') ? 'alert-warning' : 'alert-success') ?>">
                            <h4><i class="fa fa-<?= ($student['final_risk_level'] == 'High Risk') ? 'warning' : (($student['final_risk_level'] == 'Medium Risk') ? 'exclamation-circle' : 'check-circle') ?>"></i> 
                                Risk Assessment: <strong><?= htmlspecialchars($student['final_risk_level']) ?></strong>
                            </h4>
                            <p>The system has assessed this student as having a <strong><?= htmlspecialchars($student['dropout_percentage']) ?>% probability</strong> of dropping out based on current academic and financial indicators.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Risk Stats Section -->
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card" style="background: linear-gradient(45deg, #3498db, #2980b9);">
                        <div class="stat-title">GPA Status</div>
                        <div class="stat-value"><?= htmlspecialchars($student['GPA']) ?></div>
                        <span class="risk-indicator <?= $gpaRisk == 'High' ? 'high-risk-bg' : ($gpaRisk == 'Medium' ? 'medium-risk-bg' : 'low-risk-bg') ?>">
                            <?= $gpaRisk ?> Risk
                        </span>
                        <div class="jersey-number"><?= htmlspecialchars($student['GPA']) ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="background: linear-gradient(45deg, #9b59b6, #8e44ad);">
                        <div class="stat-title">Attendance Rate</div>
                        <div class="stat-value"><?= htmlspecialchars($student['Attendance']) ?>%</div>
                        <span class="risk-indicator <?= $attendanceRisk == 'High' ? 'high-risk-bg' : ($attendanceRisk == 'Medium' ? 'medium-risk-bg' : 'low-risk-bg') ?>">
                            <?= $attendanceRisk ?> Risk
                        </span>
                        <div class="jersey-number"><?= htmlspecialchars($student['Attendance']) ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="background: linear-gradient(45deg, #e67e22, #d35400);">
                        <div class="stat-title">Financial Balance</div>
                        <div class="stat-value">₱<?= number_format($student['balance']) ?></div>
                        <span class="risk-indicator <?= $financialRisk == 'High' ? 'high-risk-bg' : ($financialRisk == 'Medium' ? 'medium-risk-bg' : 'low-risk-bg') ?>">
                            <?= $financialRisk ?> Risk
                        </span>
                        <div class="jersey-number">₱</div>
                    </div>
                </div>
            </div>
            
            <!-- Performance Trends -->
            <div class="row">
                <div class="col-md-12">
                    <div class="stats-container">
                        <h3>Academic Performance Trends</h3>
                        <div class="chart-container">
                            <canvas id="performanceTrends"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Risk Factors -->
            <div class="row">
                <div class="col-md-6">
                    <div class="stats-container">
                        <h3>Risk Factor Breakdown</h3>
                        <div style="height: 300px;">
                            <canvas id="riskFactorsChart"></canvas>
                        </div>
                        <div style="margin-top: 20px;">
                            <h4>Primary Risk Factors:</h4>
                            <ul class="list-group">
                                <?php foreach ($student['reasons'] as $reason): ?>
                                    <li class="list-group-item">
                                        <i class="fa fa-exclamation-triangle text-warning"></i> 
                                        <?= htmlspecialchars($reason) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stats-container">
                        <h3>Performance Radar</h3>
                        <div class="performance-radar">
                            <canvas id="radarChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Forecast Section -->
            <div class="row">
                <div class="col-md-12">
                    <div class="forecast-container">
                        <div class="forecast-title">
                            <i class="fa fa-line-chart"></i> Risk Forecast
                        </div>
                        <div class="forecast-info">
                            <div class="forecast-value">
                                <div class="forecast-label">CURRENT RISK</div>
                                <div class="forecast-number"><?= $riskScore ?>%</div>
                            </div>
                            <div class="forecast-value">
                                <div class="forecast-label">FORECASTED NEXT SEMESTER</div>
                                <div class="forecast-number" style="color: <?= ($forecastedRisk > $riskScore) ? '#e74c3c' : '#2ecc71' ?>;">
                                    <?= $forecastedRisk ?>%
                                </div>
                            </div>
                            <div class="forecast-value">
                                <div class="forecast-label">WITH INTERVENTION</div>
                                <div class="forecast-number" style="color: #2ecc71;"><?= $projectedRisk ?>%</div>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="forecastChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Intervention Impact -->
            <div class="row">
                <div class="col-md-12">
                    <div class="stats-container">
                        <h3>Intervention Impact Assessment</h3>
                        <p>Based on the student's risk profile, here's how different interventions could reduce dropout probability:</p>
                        
                        <?php foreach ($interventionImpact as $intervention => $impact): ?>
                            <div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span><?= $intervention ?></span>
                                    <span><?= $impact ?>% reduction</span>
                                </div>
                                <div class="impact-bar">
                                    <div class="impact-fill" style="width: <?= $impact ?>%;"><?= $impact ?>%</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="alert alert-info" style="margin-top: 20px;">
                            <h4><i class="fa fa-info-circle"></i> Recommended Action Plan</h4>
                            <ul>
                                <?php foreach ($student['admin_action'] as $action): ?>
                                    <li><?= htmlspecialchars($action) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recommended Solutions -->
            <div class="row">
                <div class="col-md-12">
                    <div class="stats-container">
                        <h3>Recommended Solutions</h3>
                        <div class="row">
                            <?php foreach ($student['recommended_solutions'] as $index => $solution): ?>
                                <div class="col-md-6">
                                    <div class="panel panel-default">
                                        <div class="panel-heading" style="background-color: rgba(1, 129, 55, 0.8); color: white;">
                                            <h3 class="panel-title"><i class="fa fa-lightbulb-o"></i> Solution <?= $index + 1 ?></h3>
                                        </div>
                                        <div class="panel-body">
                                            <p><?= htmlspecialchars($solution) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="row" style="margin-top: 30px; margin-bottom: 30px;">
                <div class="col-md-12 text-center">
                    <a href="mailto:advisor@university.edu?subject=Intervention Request for <?= urlencode($student['sname']) ?>&body=Please schedule an intervention meeting for student <?= urlencode($student['sname']) ?> (ID: <?= urlencode($student['StudentID']) ?>)." class="btn btn-primary btn-lg">
                        <i class="fa fa-envelope"></i> Contact Academic Advisor
                    </a>
                    <a href="#" class="btn btn-success btn-lg" data-toggle="modal" data-target="#scheduleModal">
                        <i class="fa fa-calendar"></i> Schedule Intervention
                    </a>
                    <a href="predict.php" class="btn btn-default btn-lg">
                        <i class="fa fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Schedule Intervention Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="scheduleModalLabel">Schedule Intervention</h4>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label>Intervention Type</label>
                            <select class="form-control">
                                <option>Academic Counseling</option>
                                <option>Financial Aid Consultation</option>
                                <option>Career Guidance</option>
                                <option>Mental Health Support</option>
                                <option>Study Skills Workshop</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" class="form-control" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea class="form-control" rows="3" placeholder="Enter details about the intervention..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Schedule</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include("php/footer.php"); ?>
    
    <!-- JavaScript for Charts -->
    <script>
        // Performance Trends Chart
        const performanceTrendsCtx = document.getElementById('performanceTrends').getContext('2d');
        const performanceTrendsChart = new Chart(performanceTrendsCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($semesters) ?>,
                datasets: [
                    {
                        label: 'GPA (scale 0-4)',
                        data: <?= json_encode($gpaTrend) ?>,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.3,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Attendance (%)',
                        data: <?= json_encode($attendanceTrend) ?>,
                        borderColor: '#9b59b6',
                        backgroundColor: 'rgba(155, 89, 182, 0.1)',
                        tension: 0.3,
                        yAxisID: 'y1',
                    },
                    {
                        label: 'Risk (%)',
                        data: <?= json_encode($riskTrend) ?>,
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.3,
                        yAxisID: 'y2',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 4,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'GPA'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        max: 100,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        title: {
                            display: true,
                            text: 'Attendance %'
                        }
                    },
                    y2: {
                        beginAtZero: true,
                        max: 100,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        display: false
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Academic Performance & Risk Trends'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            }
        });
        
        // Risk Factors Chart
        const riskFactorsCtx = document.getElementById('riskFactorsChart').getContext('2d');
        const riskFactorsChart = new Chart(riskFactorsCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(<?= json_encode($riskFactors) ?>),
                datasets: [{
                    data: Object.values(<?= json_encode($riskFactors) ?>),
                    backgroundColor: [
                        'rgba(231, 76, 60, 0.8)',
                        'rgba(241, 196, 15, 0.8)',
                        'rgba(52, 152, 219, 0.8)',
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(155, 89, 182, 0.8)'
                    ],
                    borderColor: [
                        'rgba(231, 76, 60, 1)',
                        'rgba(241, 196, 15, 1)',
                        'rgba(52, 152, 219, 1)',
                        'rgba(46, 204, 113, 1)',
                        'rgba(155, 89, 182, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Risk Factor Distribution'
                    },
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                return `${label}: ${value}%`;
                            }
                        }
                    }
                }
            }
        });
        
        // Radar Chart
        const radarCtx = document.getElementById('radarChart').getContext('2d');
        const radarChart = new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: ['Academic Performance', 'Attendance', 'Financial Stability', 'Engagement', 'Participation'],
                datasets: [{
                    label: 'Current Performance',
                    data: [
                        <?= ($student['GPA'] / 4) * 100 ?>,
                        <?= $student['Attendance'] ?>,
                        <?= max(0, 100 - min(($student['balance'] / 10000) * 100, 100)) ?>,
                        <?= isset($student['engagement']) ? $student['engagement'] : 60 ?>,
                        <?= isset($student['participation']) ? $student['participation'] : 65 ?>
                    ],
                    backgroundColor: 'rgba(1, 129, 55, 0.2)',
                    borderColor: 'rgba(1, 129, 55, 0.8)',
                    pointBackgroundColor: 'rgba(1, 129, 55, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(1, 129, 55, 1)'
                }, {
                    label: 'Threshold',
                    data: [75, 80, 70, 70, 70],
                    backgroundColor: 'rgba(231, 76, 60, 0.2)',
                    borderColor: 'rgba(231, 76, 60, 0.8)',
                    borderDash: [5, 5],
                    pointBackgroundColor: 'rgba(231, 76, 60, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(231, 76, 60, 1)'
                }]
            },
            options: {
                elements: {
                    line: {
                        tension: 0.2
                    }
                },
                scales: {
                    r: {
                        min: 0,
                        max: 100,
                        beginAtZero: true,
                        ticks: {
                            display: false
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Student Performance Analysis'
                    }
                }
            }
        });
        
        // Forecast Chart
        const forecastCtx = document.getElementById('forecastChart').getContext('2d');
        const forecastChart = new Chart(forecastCtx, {
            type: 'line',
            data: {
                labels: ['Past', 'Current', 'Projected (No Intervention)', 'Projected (With Intervention)'],
                datasets: [{
                    label: 'Dropout Risk Percentage',
                    data: [<?= end($riskTrend) - 10 ?>, <?= $riskScore ?>, <?= $forecastedRisk ?>, <?= $projectedRisk ?>],
                    borderColor: 'rgba(255, 255, 255, 0.8)',
                    backgroundColor: 'rgba(255, 255, 255, 0.1)',
                    fill: true,
                    pointBackgroundColor: ['rgba(46, 204, 113, 0.8)', 'rgba(241, 196, 15, 0.8)', 'rgba(231, 76, 60, 0.8)', 'rgba(46, 204, 113, 0.8)'],
                    pointBorderColor: '#fff',
                    pointRadius: 6,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.8)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.8)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: 'rgba(255, 255, 255, 0.8)'
                        }
                    }
                }
            }
        });
    </script>
    
    <!-- Core Scripts - Include with every page -->
    <script src="assets/plugins/jquery-1.10.2.js"></script>
    <script src="assets/plugins/bootstrap/bootstrap.min.js"></script>
    <script src="assets/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="assets/plugins/pace/pace.js"></script>
    <script src="assets/scripts/siminta.js"></script>
</body>

</html>