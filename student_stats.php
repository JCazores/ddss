<?php 
$page = 'student_stats';
include("php/dbconnect.php");

// Get student ID and table from URL
$studentId = isset($_GET['id']) ? $_GET['id'] : '';
$tableName = isset($_GET['table']) ? $_GET['table'] : '';

if (empty($studentId) || empty($tableName)) {
    header("Location: gpa.php");
    exit();
}

// Get student data from database
$studentId = $conn->real_escape_string($studentId);
$tableName = $conn->real_escape_string($tableName);

$query = "SELECT * FROM `$tableName` WHERE StudentID = '$studentId'";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    header("Location: gpa.php?error=student_not_found");
    exit();
}

$studentData = $result->fetch_assoc();

// Get prediction data
function getStudentPrediction($studentId) {
    $python = "C:\\Users\\Christian Azores\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
    $script = "C:\\Users\\Christian Azores\\PycharmProjects\\PythonProject\\predict.py";
    $cmd = "\"$python\" \"$script\" 2>&1";
    
    exec($cmd, $output, $return_var);
    
    if ($return_var !== 0) return null;
    
    $full_output = implode("\n", $output);
    $json_start = strpos($full_output, '{');
    if ($json_start === false) return null;
    
    $json_output = substr($full_output, $json_start);
    $json_end = strrpos($json_output, '}');
    if ($json_end === false) return null;
    
    $json_output = substr($json_output, 0, $json_end + 1);
    $data = json_decode($json_output, true);
    
    if (!$data || !isset($data['results'])) return null;
    
    foreach ($data['results'] as $student) {
        if ($student['StudentID'] === $studentId) {
            return $student;
        }
    }
    
    return null;
}

$predictionData = getStudentPrediction($studentId);

// Calculate advanced statistics
$stats = [
    'academic_performance' => [
        'gpa' => isset($studentData['GPA']) ? floatval($studentData['GPA']) : 0,
        'attendance' => isset($studentData['Attendance']) ? floatval($studentData['Attendance']) : 0,
        'academic_score' => 0,
        'grade' => 'N/A'
    ],
    'financial_health' => [
        'balance' => isset($studentData['balance']) ? floatval($studentData['balance']) : 0,
        'financial_risk' => 0,
        'payment_status' => 'Unknown'
    ],
    'risk_metrics' => [
        'dropout_probability' => $predictionData ? floatval($predictionData['dropout_percentage']) : 0,
        'risk_level' => $predictionData ? $predictionData['final_risk_level'] : 'Unknown',
        'model_prediction' => $predictionData ? $predictionData['model_predicted_risk'] : 'Unknown'
    ]
];

// Calculate academic performance score (0-100)
if ($stats['academic_performance']['gpa'] > 0) {
    $gpaScore = ($stats['academic_performance']['gpa'] / 4.0) * 50; // 50% weight for GPA
    $attendanceScore = ($stats['academic_performance']['attendance'] / 100) * 50; // 50% weight for attendance
    $stats['academic_performance']['academic_score'] = $gpaScore + $attendanceScore;
}

// Determine academic grade
$academicScore = $stats['academic_performance']['academic_score'];
if ($academicScore >= 90) $stats['academic_performance']['grade'] = 'A+';
elseif ($academicScore >= 85) $stats['academic_performance']['grade'] = 'A';
elseif ($academicScore >= 80) $stats['academic_performance']['grade'] = 'B+';
elseif ($academicScore >= 75) $stats['academic_performance']['grade'] = 'B';
elseif ($academicScore >= 70) $stats['academic_performance']['grade'] = 'C+';
elseif ($academicScore >= 65) $stats['academic_performance']['grade'] = 'C';
elseif ($academicScore >= 60) $stats['academic_performance']['grade'] = 'D';
else $stats['academic_performance']['grade'] = 'F';

// Calculate financial risk (0-100)
$balance = $stats['financial_health']['balance'];
if ($balance <= 1000) {
    $stats['financial_health']['financial_risk'] = 5;
    $stats['financial_health']['payment_status'] = 'Excellent';
} elseif ($balance <= 5000) {
    $stats['financial_health']['financial_risk'] = 15;
    $stats['financial_health']['payment_status'] = 'Good';
} elseif ($balance <= 10000) {
    $stats['financial_health']['financial_risk'] = 30;
    $stats['financial_health']['payment_status'] = 'Fair';
} elseif ($balance <= 20000) {
    $stats['financial_health']['financial_risk'] = 50;
    $stats['financial_health']['payment_status'] = 'Poor';
} else {
    $stats['financial_health']['financial_risk'] = 80;
    $stats['financial_health']['payment_status'] = 'Critical';
}

// Generate comparative stats (mock data for demonstration)
$classAverage = [
    'gpa' => 2.75,
    'attendance' => 82.5,
    'dropout_risk' => 25.0
];

$departmentAverage = [
    'gpa' => 2.68,
    'attendance' => 80.2,
    'dropout_risk' => 28.5
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Stats - <?= htmlspecialchars($studentData['sname'] ?? $studentId) ?></title>
    
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.css">
    <link rel="stylesheet" href="css/style1.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }

        .stats-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .player-stats-header {
            background: linear-gradient(135deg, #018137 0%, #02a644 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .player-stats-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="hexagons" width="20" height="35" patternUnits="userSpaceOnUse"><polygon points="10,0 20,6 20,20 10,26 0,20 0,6" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23hexagons)"/></svg>');
            opacity: 0.3;
        }

        .player-info {
            display: flex;
            align-items: center;
            gap: 30px;
            position: relative;
            z-index: 1;
        }

        .player-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid rgba(255,255,255,0.3);
        }

        .player-details h1 {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .player-details p {
            font-size: 18px;
            opacity: 0.9;
            margin: 0;
        }

        .stats-dashboard {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-8px);
        }

        .stats-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }

        .stats-card-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
            flex: 1;
        }

        .stats-card-icon {
            font-size: 32px;
            color: #018137;
            opacity: 0.7;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .stat-row:last-child {
            border-bottom: none;
        }

        .stat-label {
            font-size: 16px;
            color: #7f8c8d;
            font-weight: 500;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-trend {
            font-size: 14px;
            margin-left: 10px;
        }

        .trend-up { color: #27ae60; }
        .trend-down { color: #e74c3c; }
        .trend-neutral { color: #95a5a6; }

        .performance-grade {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            margin-left: 10px;
        }

        .grade-a-plus, .grade-a { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .grade-b-plus, .grade-b { background: linear-gradient(135deg, #3498db, #5dade2); }
        .grade-c-plus, .grade-c { background: linear-gradient(135deg, #f39c12, #f7dc6f); }
        .grade-d { background: linear-gradient(135deg, #e67e22, #f8c471); }
        .grade-f { background: linear-gradient(135deg, #e74c3c, #f1948a); }

        .comparison-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .comparison-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .comparison-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
            border: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .comparison-item:hover {
            border-color: #018137;
            transform: translateY(-5px);
        }

        .comparison-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .comparison-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .comparison-label {
            font-size: 14px;
            color: #7f8c8d;
            text-transform: uppercase;
            font-weight: 600;
        }

        .risk-indicator {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .risk-low { background: #d4edda; color: #155724; }
        .risk-medium { background: #fff3cd; color: #856404; }
        .risk-high { background: #f8d7da; color: #721c24; }

        .back-button {
            display: inline-block;
            padding: 12px 24px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }

        .progress-bar-custom {
            height: 8px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #018137, #02a644);
            border-radius: 10px;
            transition: width 0.8s ease;
        }

        @media (max-width: 768px) {
            .stats-dashboard {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .comparison-grid {
                grid-template-columns: 1fr;
            }
            
            .player-info {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .player-details h1 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>
    <div class="stats-container">
        <a href="gpa.php" class="back-button">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <!-- Student Header -->
        <div class="player-stats-header">
            <div class="player-info">
                <div class="player-avatar-large">
                    <i class="fa fa-user fa-3x" style="color: white;"></i>
                </div>
                <div class="player-details">
                    <h1><?= htmlspecialchars($studentData['sname'] ?? 'Student') ?></h1>
                    <p>Student ID: <?= htmlspecialchars($studentId) ?></p>
                    <p>Table: <?= htmlspecialchars($tableName) ?></p>
                </div>
            </div>
        </div>

        <!-- Stats Dashboard -->
        <div class="stats-dashboard">
            <!-- Academic Performance Card -->
            <div class="stats-card">
                <div class="stats-card-header">
                    <h3 class="stats-card-title">Academic Performance</h3>
                    <i class="fa fa-graduation-cap stats-card-icon"></i>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">GPA</span>
                    <div>
                        <span class="stat-value"><?= number_format($stats['academic_performance']['gpa'], 2) ?></span>
                        <span class="stat-trend trend-neutral">/ 4.0</span>
                    </div>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Attendance</span>
                    <div>
                        <span class="stat-value"><?= number_format($stats['academic_performance']['attendance'], 1) ?>%</span>
                        <div class="progress-bar-custom">
                            <div class="progress-fill" style="width: <?= $stats['academic_performance']['attendance'] ?>%;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Academic Score</span>
                    <div>
                        <span class="stat-value"><?= number_format($stats['academic_performance']['academic_score'], 1) ?></span>
                        <span class="performance-grade grade-<?= strtolower(str_replace('+', '-plus', $stats['academic_performance']['grade'])) ?>">
                            <?= $stats['academic_performance']['grade'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Financial Health Card -->
            <div class="stats-card">
                <div class="stats-card-header">
                    <h3 class="stats-card-title">Financial Status</h3>
                    <i class="fa fa-money stats-card-icon"></i>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Current Balance</span>
                    <span class="stat-value">₱<?= number_format($stats['financial_health']['balance'], 2) ?></span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Payment Status</span>
                    <span class="stat-value"><?= $stats['financial_health']['payment_status'] ?></span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Financial Risk</span>
                    <div>
                        <span class="stat-value"><?= $stats['financial_health']['financial_risk'] ?>%</span>
                        <span class="risk-indicator risk-<?= 
                            $stats['financial_health']['financial_risk'] <= 20 ? 'low' : 
                            ($stats['financial_health']['financial_risk'] <= 50 ? 'medium' : 'high') 
                        ?>">
                            <?= $stats['financial_health']['financial_risk'] <= 20 ? 'Low Risk' : 
                                ($stats['financial_health']['financial_risk'] <= 50 ? 'Medium Risk' : 'High Risk') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Risk Metrics -->
        <div class="stats-card" style="margin-bottom: 30px;">
            <div class="stats-card-header">
                <h3 class="stats-card-title">Dropout Risk Analysis</h3>
                <i class="fa fa-exclamation-triangle stats-card-icon"></i>
            </div>
            
            <div class="stat-row">
                <span class="stat-label">Dropout Probability</span>
                <div>
                    <span class="stat-value"><?= number_format($stats['risk_metrics']['dropout_probability'], 1) ?>%</span>
                    <div class="progress-bar-custom">
                        <div class="progress-fill" style="width: <?= $stats['risk_metrics']['dropout_probability'] ?>%; background: linear-gradient(135deg, #e74c3c, #f39c12);"></div>
                    </div>
                </div>
            </div>
            
            <div class="stat-row">
                <span class="stat-label">Risk Level</span>
                <span class="stat-value"><?= htmlspecialchars($stats['risk_metrics']['risk_level']) ?></span>
            </div>
            
            <div class="stat-row">
                <span class="stat-label">Model Prediction</span>
                <span class="stat-value"><?= htmlspecialchars($stats['risk_metrics']['model_prediction']) ?></span>
            </div>
        </div>

        <!-- Comparison Section -->
        <div class="comparison-section">
            <h3 style="color: #2c3e50; margin-bottom: 25px; font-size: 28px; font-weight: bold;">
                <i class="fa fa-bar-chart" style="margin-right: 10px; color: #018137;"></i>
                Performance Comparison
            </h3>
            
            <div class="comparison-grid">
                <div class="comparison-item">
                    <div class="comparison-title">Student vs Class Average</div>
                    <div class="comparison-value" style="color: <?= $stats['academic_performance']['gpa'] >= $classAverage['gpa'] ? '#27ae60' : '#e74c3c' ?>">
                        GPA: <?= number_format($stats['academic_performance']['gpa'], 2) ?> vs <?= number_format($classAverage['gpa'], 2) ?>
                    </div>
                    <div class="comparison-label">Class Average GPA</div>
                </div>
                
                <div class="comparison-item">
                    <div class="comparison-title">Student vs Department Average</div>
                    <div class="comparison-value" style="color: <?= $stats['academic_performance']['gpa'] >= $departmentAverage['gpa'] ? '#27ae60' : '#e74c3c' ?>">
                        GPA: <?= number_format($stats['academic_performance']['gpa'], 2) ?> vs <?= number_format($departmentAverage['gpa'], 2) ?>
                    </div>
                    <div class="comparison-label">Department Average GPA</div>
                </div>
                
                <div class="comparison-item">
                    <div class="comparison-title">Attendance Comparison</div>
                    <div class="comparison-value" style="color: <?= $stats['academic_performance']['attendance'] >= $classAverage['attendance'] ? '#27ae60' : '#e74c3c' ?>">
                        <?= number_format($stats['academic_performance']['attendance'], 1) ?>% vs <?= number_format($classAverage['attendance'], 1) ?>%
                    </div>
                    <div class="comparison-label">Class Average Attendance</div>
                </div>
                
                <div class="comparison-item">
                    <div class="comparison-title">Risk Assessment</div>
                    <div class="comparison-value" style="color: <?= $stats['risk_metrics']['dropout_probability'] <= $classAverage['dropout_risk'] ? '#27ae60' : '#e74c3c' ?>">
                        <?= number_format($stats['risk_metrics']['dropout_probability'], 1) ?>% vs <?= number_format($classAverage['dropout_risk'], 1) ?>%
                    </div>
                    <div class="comparison-label">Class Average Risk</div>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="stats-card">
            <div class="stats-card-header">
                <h3 class="stats-card-title">Additional Information</h3>
                <i class="fa fa-info-circle stats-card-icon"></i>
            </div>
            
            <div style="line-height: 1.8; color: #7f8c8d; font-size: 16px;">
                <p><strong>Data Sources:</strong> Academic records, attendance tracking, financial system, and predictive analytics model.</p>
                <p><strong>Last Updated:</strong> <?= date('F j, Y g:i A') ?></p>
                <p><strong>Academic Score Calculation:</strong> Based on 50% GPA weight and 50% attendance weight.</p>
                <p><strong>Risk Assessment:</strong> Generated using machine learning algorithms analyzing multiple student factors.</p>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // Animate progress bars on page load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });
        });

        // Add hover effects to cards
        const cards = document.querySelectorAll('.stats-card, .comparison-item');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 25px 50px rgba(0,0,0,0.2)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.boxShadow = '0 15px 35px rgba(0,0,0,0.1)';
            });
        });
    </script>
</body>
</html>