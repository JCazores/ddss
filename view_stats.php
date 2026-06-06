<?php 
$page='predict';

include("php/dbconnect.php");

// Get student ID from URL parameter
$studentID = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($studentID)) {
    header("Location: gpa.php");
    exit;
}

// Fetch student prediction data from database
$studentData = null;
$stmt = $conn->prepare("
    SELECT 
        student_id,
        course,
        current_year,
        source_table,
        current_semester_data,
        next_semester_prediction,
        risk_analysis,
        interventions,
        prediction_date
    FROM student_predictions 
    WHERE student_id = ?
    ORDER BY prediction_date DESC
    LIMIT 1
");

$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Student prediction data not found in database.");
}

$row = $result->fetch_assoc();
$stmt->close();

// Parse JSON data
$currentSemester = json_decode($row['current_semester_data'], true);
$nextSemester = json_decode($row['next_semester_prediction'], true);
$riskAnalysis = json_decode($row['risk_analysis'], true);
$interventions = json_decode($row['interventions'], true);

// Build student data array
$studentData = [
    'StudentID' => $row['student_id'],
    'table' => $row['source_table'],
    'year' => $row['current_year'],
    'course' => $row['course'],
    'semester' => $currentSemester['semester'] ?? null,
    
    // Current semester data
    'GPA' => $currentSemester['gpa'],
    'Attendance' => $currentSemester['attendance'],
    'balance' => $currentSemester['balance'],
    'final_risk_level' => $currentSemester['risk_level'],
    'dropout_percentage' => round($currentSemester['dropout_probability'], 1),
    
    // Next semester predictions
    'next_semester_gpa' => round($nextSemester['predicted_gpa'], 2),
    'next_semester_attendance' => round($nextSemester['predicted_attendance'], 1),
    'next_semester_balance' => round($nextSemester['predicted_balance'], 2),
    'next_semester_risk_level' => $nextSemester['predicted_risk_level'],
    'next_semester_dropout_probability' => round($nextSemester['predicted_dropout_probability'], 1),
];

// Get additional student info from source table
$table_name = $conn->real_escape_string($studentData['table']);
$res = $conn->query("SELECT sname FROM `$table_name` WHERE StudentID = '$studentID'");
if ($res && $row = $res->fetch_assoc()) {
    $studentData['sname'] = $row['sname'];
} else {
    $studentData['sname'] = 'Unknown Student';
}

// Extract interventions
$studentData['reasons'] = [];
$studentData['recommended_solutions'] = [];
$studentData['admin_action'] = [];

if (!empty($interventions) && is_array($interventions)) {
    foreach ($interventions as $intervention) {
        if (isset($intervention['reason']) && $intervention['reason'] !== '' && $intervention['reason'] !== 'None') {
            $studentData['reasons'][] = $intervention['reason'];
        }
        if (isset($intervention['solution']) && $intervention['solution'] !== '' && $intervention['solution'] !== 'None') {
            $studentData['recommended_solutions'][] = $intervention['solution'];
        }
        if (isset($intervention['admin_action']) && $intervention['admin_action'] !== '' && $intervention['admin_action'] !== 'None') {
            $studentData['admin_action'][] = $intervention['admin_action'];
        }
    }
}

// Ensure minimum defaults
if (empty($studentData['reasons'])) {
    $studentData['reasons'] = $studentData['final_risk_level'] === 'Low Risk' 
        ? ['None'] 
        : ['Various risk factors identified'];
}
if (empty($studentData['recommended_solutions'])) {
    $studentData['recommended_solutions'] = $studentData['final_risk_level'] === 'Low Risk' 
        ? ['None'] 
        : ['Regular monitoring and support'];
}
if (empty($studentData['admin_action'])) {
    $studentData['admin_action'] = $studentData['final_risk_level'] === 'Low Risk' 
        ? ['Continue regular monitoring.'] 
        : ['Schedule follow-up meeting'];
}

// Calculate current risk score (for display)
$currentRiskScore = $studentData['dropout_percentage'];

// Create current semester display data
$currentData = [
    'semester' => 'Current Semester',
    'GPA' => number_format($studentData['GPA'], 2),
    'Attendance' => $studentData['Attendance'],
    'balance' => $studentData['balance'],
    'risk_score' => $currentRiskScore
];

// Create forecasted semester data
$forecastedData = [
    'semester' => 'Next Semester (Forecast)',
    'GPA' => number_format($studentData['next_semester_gpa'], 2),
    'Attendance' => $studentData['next_semester_attendance'],
    'balance' => $studentData['next_semester_balance'],
    'risk_score' => $studentData['next_semester_dropout_probability']
];

// Format data for charts
$labels = [$currentData['semester'], $forecastedData['semester']];
$gpaData = [floatval($currentData['GPA']), floatval($forecastedData['GPA'])];
$attendanceData = [floatval($currentData['Attendance']), floatval($forecastedData['Attendance'])];
$balanceData = [floatval($currentData['balance']), floatval($forecastedData['balance'])];
$riskScoreData = [floatval($currentData['risk_score']), floatval($forecastedData['risk_score'])];

// Calculate performance metrics
$gpaChange = $studentData['next_semester_gpa'] - floatval($studentData['GPA']);
$gpaPercentChange = (floatval($studentData['GPA']) > 0) 
    ? ($gpaChange / floatval($studentData['GPA']) * 100) 
    : 0;

$attendanceChange = $studentData['next_semester_attendance'] - $studentData['Attendance'];
$attendancePercentChange = ($studentData['Attendance'] > 0) 
    ? ($attendanceChange / $studentData['Attendance'] * 100) 
    : 0;

$riskScoreChange = $studentData['next_semester_dropout_probability'] - $currentRiskScore;

// Create risk factor matrix
$riskFactors = [
    'GPA' => ($studentData['GPA'] >= 3.0) ? 'High Risk' : 
             (($studentData['GPA'] >= 2.0) ? 'Medium Risk' : 'Low Risk'),
    'Attendance' => ($studentData['Attendance'] < 75) ? 'High Risk' : 
                    (($studentData['Attendance'] < 85) ? 'Medium Risk' : 'Low Risk'),
    'Financial Status' => ($studentData['balance'] > 10000) ? 'High Risk' : 
                          (($studentData['balance'] > 5000) ? 'Medium Risk' : 'Low Risk'),
];

// Calculate risk contribution percentages
$academicContrib = min(100, max(0, (($studentData['GPA'] - 1) / 4) * 50));
$attendanceContrib = min(100, max(0, 100 - $studentData['Attendance']));
$financialContrib = min(100, max(0, $studentData['balance'] / 200));
$otherContrib = min(100, max(0, $studentData['dropout_percentage'] / 2));

$riskContributions = [
    'Academic' => $academicContrib,
    'Attendance' => $attendanceContrib,
    'Financial' => $financialContrib,
    'Other Factors' => $otherContrib
];

// Normalize to sum to 100%
$totalContribution = array_sum($riskContributions);
if ($totalContribution > 0) {
    foreach ($riskContributions as $key => $value) {
        $riskContributions[$key] = round(($value / $totalContribution) * 100);
    }
} else {
    $riskContributions = [
        'Academic' => 25,
        'Attendance' => 25,
        'Financial' => 25,
        'Other Factors' => 25
    ];
}

// Generate peer comparison data (simulated)
$peerAverageGPA = $studentData['GPA'] + (mt_rand(-50, 50) / 100);
$peerAverageAttendance = min(100, max(70, $studentData['Attendance'] + mt_rand(-10, 10)));
$peerAverageRisk = min(100, max(0, $studentData['dropout_percentage'] + mt_rand(-15, 15)));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Statistics & Forecasting</title>
    <link rel="icon" type="image/png" href="img/icon1.jpg">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.css">
    <link rel="stylesheet" href="css/style1.css">
    <link rel="stylesheet" href="css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        .dashboard-header {
            background: linear-gradient(to right, #1d976c, #93f9b9);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            color: white;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card .count { font-size: 36px; font-weight: bold; }
        .stat-card .label { font-size: 14px; color: rgba(255, 255, 255, 0.8); }
        .stat-card .percent-change { font-size: 14px; margin-top: 5px; }
        .stat-card .positive { color: #a3f7bf; }
        .stat-card .negative { color: #ff7675; }
        
        .chart-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .chart-container h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        .chart-container canvas {
            max-height: 300px !important;
            height: 300px !important;
        }
        
        .risk-indicator {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
        }
        .high-risk-bg { background-color: #e74c3c; }
        .medium-risk-bg { background-color: #f1c40f; }
        .low-risk-bg { background-color: #2ecc71; }
        
        .risk-contributions {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 20px;
        }
        .risk-contribution {
            flex: 1;
            min-width: 150px;
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            color: white;
        }
        .risk-contribution.academic { background: linear-gradient(45deg, #e74c3c, #c0392b); }
        .risk-contribution.attendance { background: linear-gradient(45deg, #f1c40f, #f39c12); }
        .risk-contribution.financial { background: linear-gradient(45deg, #3498db, #2980b9); }
        .risk-contribution.other { background: linear-gradient(45deg, #9b59b6, #8e44ad); }
        .risk-contribution h4 { margin-top: 0; font-size: 16px; margin-bottom: 10px; }
        .risk-contribution .percentage { font-size: 24px; font-weight: bold; }
        
        .comparison-card {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        .comparison-bar {
            height: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            background-color: #eee;
            position: relative;
        }
        .comparison-fill {
            height: 100%;
            border-radius: 5px;
            position: absolute;
            top: 0;
            left: 0;
        }
        .student-fill { background-color: #3498db; }
        .peer-fill { background-color: #95a5a6; }
        
        .intervention-card {
            background-color: #f9f9f9;
            border-left: 4px solid #4b6cb7;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .key-metrics-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .key-metrics-table td {
            padding: 12px;
            background-color: #f9f9f9;
        }
        .key-metrics-table td:first-child {
            border-top-left-radius: 5px;
            border-bottom-left-radius: 5px;
            font-weight: bold;
            width: 40%;
        }
        .key-metrics-table td:last-child {
            border-top-right-radius: 5px;
            border-bottom-right-radius: 5px;
            font-weight: 600;
            text-align: right;
        }
        
        .forecast-label {
            font-style: italic;
            font-size: 14px;
            color: #95a5a6;
        }
    </style>
</head>

<body>
    <?php include("php/header.php"); ?>
    
    <div id="page-wrapper">
        <div id="page-inner">
            <div class="row">
                <div class="col-md-12">
                    <div class="back-button" style="margin-bottom: 20px;">
                        <a href="gpa.php" class="btn btn-primary">
                            <i class="fa fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                    
                    <div class="dashboard-header">
                        <div class="row">
                            <div class="col-md-8">
                                <h1><i class="fa fa-bar-chart"></i> Student Performance Analytics</h1>
                                <h4><?= htmlspecialchars($studentData['sname']) ?> - <?= htmlspecialchars($studentData['StudentID']) ?></h4>
                                <p><?= htmlspecialchars($studentData['course']) ?>, Year <?= htmlspecialchars($studentData['year']) ?></p>
                            </div>
                            <div class="col-md-4 text-right">
                                <div class="risk-indicator <?= ($studentData['final_risk_level'] == 'High Risk') ? 'high-risk-bg' : (($studentData['final_risk_level'] == 'Medium Risk') ? 'medium-risk-bg' : 'low-risk-bg') ?>" style="padding: 10px 20px; font-size: 18px; margin-top: 20px;">
                                    <?= htmlspecialchars($studentData['final_risk_level']) ?>
                                </div>
                                <div style="margin-top: 10px; font-size: 18px; font-weight: bold;">
                                    <?= htmlspecialchars($studentData['dropout_percentage']) ?>% Dropout Probability
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Key Performance Indicators -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stat-card" style="background: linear-gradient(45deg, #3498db, #2980b9);">
                                <h3><i class="fa fa-graduation-cap"></i> GPA</h3>
                                <div class="count"><?= htmlspecialchars($studentData['GPA']) ?></div>
                                <div class="label">Current GPA (1=Best, 5=Worst)</div>
                                <div class="percent-change <?= ($gpaPercentChange >= 0) ? 'positive' : 'negative' ?>">
                                    <i class="fa fa-<?= ($gpaPercentChange >= 0) ? 'arrow-up' : 'arrow-down' ?>"></i>
                                    <?= number_format(abs($gpaPercentChange), 1) ?>% forecasted change
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card" style="background: linear-gradient(45deg, #2ecc71, #27ae60);">
                                <h3><i class="fa fa-calendar-check-o"></i> Attendance</h3>
                                <div class="count"><?= htmlspecialchars($studentData['Attendance']) ?>%</div>
                                <div class="label">Current Attendance Rate</div>
                                <div class="percent-change <?= ($attendancePercentChange >= 0) ? 'positive' : 'negative' ?>">
                                    <i class="fa fa-<?= ($attendancePercentChange >= 0) ? 'arrow-up' : 'arrow-down' ?>"></i>
                                    <?= number_format(abs($attendancePercentChange), 1) ?>% forecasted change
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card" style="background: linear-gradient(45deg, #e74c3c, #c0392b);">
                                <h3><i class="fa fa-money"></i> Outstanding Balance</h3>
                                <div class="count">₱<?= number_format($studentData['balance'], 2) ?></div>
                                <div class="label">Current Balance</div>
                                <div class="percent-change">
                                    <i class="fa fa-info-circle"></i> Due by end of semester
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Risk Contribution Factors -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="risk-contributions">
                                <div class="risk-contribution academic">
                                    <h4>Academic</h4>
                                    <div class="percentage"><?= $riskContributions['Academic'] ?>%</div>
                                    <div>of total risk</div>
                                </div>
                                <div class="risk-contribution attendance">
                                    <h4>Attendance</h4>
                                    <div class="percentage"><?= $riskContributions['Attendance'] ?>%</div>
                                    <div>of total risk</div>
                                </div>
                                <div class="risk-contribution financial">
                                    <h4>Financial</h4>
                                    <div class="percentage"><?= $riskContributions['Financial'] ?>%</div>
                                    <div>of total risk</div>
                                </div>
                                <div class="risk-contribution other">
                                    <h4>Other Factors</h4>
                                    <div class="percentage"><?= $riskContributions['Other Factors'] ?>%</div>
                                    <div>of total risk</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row 1 -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="chart-container">
                                <h3><i class="fa fa-line-chart"></i> Current vs Forecasted Risk Analysis</h3>
                                <canvas id="riskChart"></canvas>
                                <div class="forecast-label text-right">
                                    <i class="fa fa-info-circle"></i> Next semester forecast based on current performance
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="chart-container">
                                <h3><i class="fa fa-pie-chart"></i> Risk Factor Distribution</h3>
                                <div id="riskFactorChart" style="height: 250px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row 2 -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h3><i class="fa fa-graduation-cap"></i> GPA: Current vs Forecast</h3>
                                <canvas id="gpaChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h3><i class="fa fa-calendar-check-o"></i> Attendance: Current vs Forecast</h3>
                                <canvas id="attendanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row 3 -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h3><i class="fa fa-money"></i> Balance: Current vs Forecast</h3>
                                <canvas id="balanceChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h3><i class="fa fa-bullseye"></i> Academic Performance Radar</h3>
                                <canvas id="radarChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Peer Comparison Section -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="chart-container">
                                <h3><i class="fa fa-users"></i> Peer Comparison</h3>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="comparison-card">
                                            <h4>GPA Comparison</h4>
                                            <div class="comparison-bar">
                                                <div class="comparison-fill student-fill" style="width: <?= ($studentData['GPA'] / 5) * 100 ?>%"></div>
                                            </div>
                                            <div class="comparison-bar">
                                                <div class="comparison-fill peer-fill" style="width: <?= ($peerAverageGPA / 5) * 100 ?>%"></div>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; font-size: 14px;">
                                                <div style="color: #3498db; font-weight: bold;">You: <?= number_format($studentData['GPA'], 2) ?></div>
                                                <div style="color: #95a5a6;">Peers: <?= number_format($peerAverageGPA, 2) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="comparison-card">
                                            <h4>Attendance Comparison</h4>
                                            <div class="comparison-bar">
                                                <div class="comparison-fill student-fill" style="width: <?= $studentData['Attendance'] ?>%"></div>
                                            </div>
                                            <div class="comparison-bar">
                                                <div class="comparison-fill peer-fill" style="width: <?= $peerAverageAttendance ?>%"></div>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; font-size: 14px;">
                                                <div style="color: #3498db; font-weight: bold;">You: <?= number_format($studentData['Attendance'], 1) ?>%</div>
                                                <div style="color: #95a5a6;">Peers: <?= number_format($peerAverageAttendance, 1) ?>%</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="comparison-card">
                                            <h4>Dropout Risk Comparison</h4>
                                            <div class="comparison-bar">
                                                <div class="comparison-fill student-fill" style="width: <?= $studentData['dropout_percentage'] ?>%"></div>
                                            </div>
                                            <div class="comparison-bar">
                                                <div class="comparison-fill peer-fill" style="width: <?= $peerAverageRisk ?>%"></div>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; font-size: 14px;">
                                                <div style="color: #3498db; font-weight: bold;">You: <?= number_format($studentData['dropout_percentage'], 1) ?>%</div>
                                                <div style="color: #95a5a6;">Peers: <?= number_format($peerAverageRisk, 1) ?>%</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Risk Factors and Metrics -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h3><i class="fa fa-exclamation-triangle"></i> Risk Assessment Factors</h3>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Factor</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($riskFactors as $factor => $level): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($factor) ?></td>
                                            <td>
                                                <span class="risk-indicator <?= ($level == 'High Risk') ? 'high-risk-bg' : (($level == 'Medium Risk') ? 'medium-risk-bg' : 'low-risk-bg') ?>">
                                                    <?= htmlspecialchars($level) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h3><i class="fa fa-key"></i> Key Performance Metrics</h3>
                                <table class="key-metrics-table">
                                    <tr>
                                        <td>Current GPA</td>
                                        <td><?= number_format($studentData['GPA'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Forecasted GPA</td>
                                        <td><?= number_format($studentData['next_semester_gpa'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Current Attendance</td>
                                        <td><?= number_format($studentData['Attendance'], 1) ?>%</td>
                                    </tr>
                                    <tr>
                                        <td>Forecasted Attendance</td>
                                        <td><?= number_format($studentData['next_semester_attendance'], 1) ?>%</td>
                                    </tr>
                                    <tr>
                                        <td>Overall Risk Level</td>
                                        <td>
                                            <span class="risk-indicator <?= ($studentData['final_risk_level'] == 'High Risk') ? 'high-risk-bg' : (($studentData['final_risk_level'] == 'Medium Risk') ? 'medium-risk-bg' : 'low-risk-bg') ?>">
                                                <?= htmlspecialchars($studentData['final_risk_level']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Current Dropout Probability</td>
                                        <td><?= htmlspecialchars($studentData['dropout_percentage']) ?>%</td>
                                    </tr>
                                    <tr>
                                        <td>Forecasted Dropout Risk</td>
                                        <td><?= number_format($studentData['next_semester_dropout_probability'], 1) ?>%</td>
                                    </tr>
                                    <tr>
                                        <td>Current Balance</td>
                                        <td>₱<?= number_format($studentData['balance'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Forecasted Balance</td>
                                        <td>₱<?= number_format($studentData['next_semester_balance'], 2) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recommended Interventions -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="chart-container">
                                <h3><i class="fa fa-medkit"></i> Recommended Interventions</h3>
                                <div class="row">
                                    <?php foreach ($studentData['recommended_solutions'] as $intervention): ?>
                                    <div class="col-md-6">
                                        <div class="intervention-card">
                                            <i class="fa fa-check-circle"></i> <?= htmlspecialchars($intervention) ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="text-center" style="margin-top: 30px; color: #777;">
                                <p>This report was generated on <?= date('F j, Y') ?> using the Student Dropout Prediction System.</p>
                                <p>Please note that predictions are based on statistical models and should be used as supportive tools only.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="js/jquery.metisMenu.js"></script>
    <script src="js/custom1.js"></script>
    
    <script>
        // Chart.js Global Configuration
        Chart.defaults.font.family = "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#666';
        
        // Risk Chart - Line Chart
        const riskCtx = document.getElementById('riskChart').getContext('2d');
        const riskChart = new Chart(riskCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Dropout Risk (%)',
                    data: <?= json_encode($riskScoreData) ?>,
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#e74c3c',
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Risk Score (%)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Semester'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    },
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        
        // GPA Chart
        const gpaCtx = document.getElementById('gpaChart').getContext('2d');
        const gpaChart = new Chart(gpaCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'GPA',
                    data: <?= json_encode($gpaData) ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3498db',
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 1,
                        max: 5,
                        reverse: true,
                        title: {
                            display: true,
                            text: 'GPA (1-5 Scale, 1=Highest)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Semester'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    },
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        
        // Attendance Chart
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceChart = new Chart(attendanceCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Attendance (%)',
                    data: <?= json_encode($attendanceData) ?>,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#2ecc71',
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Attendance Rate (%)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Semester'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    },
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        
        // Balance Chart
        const balanceCtx = document.getElementById('balanceChart').getContext('2d');
        const balanceChart = new Chart(balanceCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Balance (₱)',
                    data: <?= json_encode($balanceData) ?>,
                    backgroundColor: 'rgba(155, 89, 182, 0.7)',
                    borderColor: '#9b59b6',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Balance (₱)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Semester'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        
        // Radar Chart
        const radarCtx = document.getElementById('radarChart').getContext('2d');
        const radarChart = new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: ['GPA', 'Attendance', 'Financial', 'Overall Risk'],
                datasets: [{
                    label: 'Current Performance',
                    data: [
                        <?= (6 - $studentData['GPA']) / 4 * 100 ?>,
                        <?= $studentData['Attendance'] ?>, 
                        <?= max(0, 100 - min(100, ($studentData['balance'] / 10000) * 100)) ?>, 
                        <?= max(0, 100 - $studentData['dropout_percentage']) ?>
                    ],
                    backgroundColor: 'rgba(52, 152, 219, 0.3)',
                    borderColor: '#3498db',
                    borderWidth: 2,
                    pointBackgroundColor: '#3498db'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    r: {
                        beginAtZero: true,
                        min: 0,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let index = context.dataIndex;
                                let value = context.raw;
                                
                                switch(index) {
                                    case 0: return 'GPA: ' + (5 - (value / 100 * 4)).toFixed(2) + ' / 5.0 (1=Best)';
                                    case 1: return 'Attendance: ' + value.toFixed(1) + '%';
                                    case 2: return 'Financial Status: ' + value.toFixed(1) + '%';
                                    case 3: return 'Success Probability: ' + value.toFixed(1) + '%';
                                    default: return value;
                                }
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        
        // Risk Factor Distribution Chart using ApexCharts
        var options = {
            series: [
                <?= $riskContributions['Academic'] ?>, 
                <?= $riskContributions['Attendance'] ?>, 
                <?= $riskContributions['Financial'] ?>, 
                <?= $riskContributions['Other Factors'] ?>
            ],
            chart: {
                type: 'donut',
                height: 250
            },
            labels: ['Academic', 'Attendance', 'Financial', 'Other Factors'],
            colors: ['#e74c3c', '#f1c40f', '#3498db', '#9b59b6'],
            legend: {
                position: 'bottom'
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '14px'
                            },
                            value: {
                                show: true,
                                fontSize: '16px',
                                formatter: function (val) {
                                    return val + '%';
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total Risk',
                                formatter: function (w) {
                                    return '100%';
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        height: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        var riskFactorChart = new ApexCharts(document.querySelector("#riskFactorChart"), options);
        riskFactorChart.render();
    </script>
</body>
</html>