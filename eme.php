<?php $page='predict';

include("php/dbconnect.php");

// ✅ Run Python prediction script
$python = "C:\\Users\\Christian Azores\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
$script = "C:\\Users\\Christian Azores\\PycharmProjects\\PythonProject\\predict.py";
$cmd = "\"$python\" \"$script\" 2>&1";
exec($cmd, $output, $return_var);
if ($return_var !== 0) die("Prediction Script Error! Return Code: $return_var");

$json_output = implode("", $output);
$data = json_decode($json_output, true);
if (!$data || !isset($data['results'])) die("No results found in prediction data.");

$results = $data['results'];

// ✅ Enhanced Search Logic with Redirect
$searchResult = [];

if (isset($_POST['search'])) {
    $searchInput = $conn->real_escape_string($_POST['searchQuery']);
    $searchField = isset($_POST['searchField']) ? $_POST['searchField'] : 'all'; // Default to all fields
    $filteredResults = [];

    foreach ($results as $student) {
        $matches = false;
        
        // Get student details from database for additional fields
        $StudentID = $conn->real_escape_string($student['StudentID']);
        $table_name = $conn->real_escape_string($student['table']);
        $res = $conn->query("SELECT StudentID, sname, year, course FROM `$table_name` WHERE StudentID = '$StudentID'");
        
        if ($res && $row = $res->fetch_assoc()) {
            // Search based on selected field
            switch ($searchField) {
                case 'id':
                    $matches = ($row['StudentID'] === $searchInput);
                    break;
                case 'name':
                    $matches = (stripos($row['sname'], $searchInput) !== false);
                    break;
                case 'course':
                    $matches = (stripos($row['course'], $searchInput) !== false);
                    break;
                case 'year':
                    $matches = (stripos($row['year'], $searchInput) !== false);
                    break;
                case 'all':
                default:
                    $matches = ($row['StudentID'] === $searchInput) || 
                              (stripos($row['sname'], $searchInput) !== false) || 
                              (stripos($row['course'], $searchInput) !== false) ||
                              (stripos($row['year'], $searchInput) !== false);
                    break;
            }
            
            if ($matches) {
                $filteredResults[] = $student;
            }
        }
    }

    // ✅ Store the search result temporarily
    $_SESSION['searchResult'] = $filteredResults;
    $_SESSION['searchInput'] = $searchInput;
    $_SESSION['searchField'] = $searchField;

    // ✅ Redirect to clear POST and prevent search persistence on refresh
    header("Location: " . $_SERVER['PHP_SELF'] . "?searchResult=1");
    exit();
} elseif (isset($_GET['searchResult']) && isset($_SESSION['searchResult'])) {
    // ✅ Display the search result from session
    $searchResult = $_SESSION['searchResult'];
    $searchInput = $_SESSION['searchInput'] ?? '';
    $searchField = $_SESSION['searchField'] ?? 'all';
} else {
    // ✅ Default: Show all results
    $searchResult = $results;
    $searchInput = '';
    $searchField = 'all';
}

// ✅ Risk count for charts
$riskCounts = ['High' => 0, 'Medium' => 0, 'Low' => 0];
$courseRiskData = [];
$yearRiskData = [];

foreach ($results as $student) {
    $StudentID = $student['StudentID'];
    $table_name = $student['table'];
    $res = $conn->query("SELECT course, year FROM `$table_name` WHERE StudentID = '$StudentID'");
    
    if ($res && $row = $res->fetch_assoc()) {
        $course = $row['course'];
        $year = $row['year'];
        $level = $student['final_risk_level'];
        
        // Count risk levels
        if ($level == "High Risk") $riskCounts['High']++;
        elseif ($level == "Medium Risk") $riskCounts['Medium']++;
        else $riskCounts['Low']++;
        
        // Track risk by course
        if (!isset($courseRiskData[$course])) {
            $courseRiskData[$course] = ['High' => 0, 'Medium' => 0, 'Low' => 0, 'Total' => 0];
        }
        if ($level == "High Risk") $courseRiskData[$course]['High']++;
        elseif ($level == "Medium Risk") $courseRiskData[$course]['Medium']++;
        else $courseRiskData[$course]['Low']++;
        $courseRiskData[$course]['Total']++;
        
        // Track risk by year
        if (!isset($yearRiskData[$year])) {
            $yearRiskData[$year] = ['High' => 0, 'Medium' => 0, 'Low' => 0, 'Total' => 0];
        }
        if ($level == "High Risk") $yearRiskData[$year]['High']++;
        elseif ($level == "Medium Risk") $yearRiskData[$year]['Medium']++;
        else $yearRiskData[$year]['Low']++;
        $yearRiskData[$year]['Total']++;
    }
}

// Get selected entries per page from dropdown or default to 10
$studentsPerPage = isset($_GET['entries']) ? max(1, intval($_GET['entries'])) : 10;

$totalStudents = count($searchResult);
$totalPages = ceil($totalStudents / $studentsPerPage);
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// If 'All' is selected, show all students
if ($studentsPerPage >= $totalStudents) {
    $paginatedStudents = $searchResult;
} else {
    $startIndex = ($page - 1) * $studentsPerPage;
    $paginatedStudents = array_slice($searchResult, $startIndex, $studentsPerPage);
}

// Email functionality
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize messages
$error = $success = "";
if (isset($_POST['send_email'])) {
    $emails = explode(',', $_POST['email']); // Allow multiple emails
    $message = $_POST['message'];
    $studentId = $_POST['studentId'];
    
    // Find student in results
    $student = null;
    foreach ($results as $s) {
        if ($s['StudentID'] === $studentId) {
            $student = $s;
            break;
        }
    }
    
    if (!$student) {
        $error = "Student not found";
    } else {
        require 'libs/PHPMailer/src/Exception.php';
        require 'libs/PHPMailer/src/PHPMailer.php';
        require 'libs/PHPMailer/src/SMTP.php';

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'smartmedsystem@gmail.com'; // Replace with your email
            $mail->Password = 'dxrc vypx qelu irfj'; // Replace with your app-specific password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('yourgmail@gmail.com', 'Dropout System');

            foreach ($emails as $address) {
                $mail->addAddress(trim($address));
            }

            $mail->isHTML(true);
            $mail->Subject = "Dropout Risk Notification";

            // Build the full student details for the email
            $studentDetails = "
                <p><strong>Risk Level:</strong> {$student['final_risk_level']}</p>
                <p><strong>Model Predicted Risk:</strong> {$student['model_predicted_risk']}</p>
                <p><strong>Dropout Percentage:</strong> {$student['dropout_percentage']}%</p>
                <p><strong>Possible Reasons of Dropping:</strong> " . implode(", ", $student['reasons']) . "</p>
                <p><strong>Solutions:</strong> " . implode(", ", $student['recommended_solutions']) . "</p>
                <p><strong>Admin Action:</strong> " . implode(", ", $student['admin_action']) . "</p>
            ";

            // ✅ Append the student details inside the email
            $mail->Body = nl2br($message) . "<br><br><strong>Student Prediction Details:</strong><br>" . $studentDetails;

            if ($mail->send()) {
                $success = "Email successfully sent!";
            } else {
                $error = "Failed to send email.";
            }
        } catch (Exception $e) {
            $error = "Mailer Error: " . $mail->ErrorInfo;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Dropout Analytics</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.css">
    <link rel="stylesheet" href="css/style1.css">
    <link rel="stylesheet" href="css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        :root {
            --primary-color: #0056b3;
            --secondary-color: #28a745;
            --alert-color: #dc3545;
            --text-color: #333333;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }
        
        #page-wrapper {
            background-color: #ffffff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 8px;
            margin: 20px;
        }
        
        .page-head-line {
            color: var(--primary-color);
            font-weight: 600;
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .stats-box {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .stats-box:hover {
            transform: translateY(-5px);
        }
        
        .box-high {
            border-top: 4px solid #dc3545;
        }
        
        .box-medium {
            border-top: 4px solid #ffc107;
        }
        
        .box-low {
            border-top: 4px solid #28a745;
        }
        
        .risk-number {
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .risk-label {
            font-size: 1rem;
            color: #6c757d;
        }
        
        .dashboard-header {
            background: var(--dark-bg);
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chart-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .table-header {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-responsive {
            padding: 0;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            color: var(--text-color);
            border-bottom: 2px solid #dee2e6;
        }
        
        .btn-view {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-view:hover {
            background-color: #004494;
        }
        
        .search-area {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .search-box {
            flex-grow: 1;
        }
        
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .page-link {
            color: var(--primary-color);
            background-color: white;
            border: 1px solid #dee2e6;
        }
        
        .page-link:hover {
            background-color: #e9ecef;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }
        
        .risk-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .risk-high {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .risk-medium {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .risk-low {
            background-color: #d4edda;
            color: #155724;
        }
        
        .gauge-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
        }
        
        .gauge-box {
            text-align: center;
            flex: 1;
            min-width: 250px;
            padding: 15px;
        }
        
        .nav-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin: 0 5px;
            transition: background-color 0.3s;
        }
        
        .nav-btn:hover {
            background-color: #004494;
            color: white;
            text-decoration: none;
        }
        
        .nav-btn.disabled {
            background-color: #6c757d;
            pointer-events: none;
        }
        
        .dashboard-stats {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1;
            min-width: 200px;
            border-radius: 8px;
            padding: 20px;
            margin: 10px;
            text-align: center;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin: 10px 0;
        }
        
        .stat-card p {
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .chart-tabs {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        
        .chart-tab {
            padding: 10px 20px;
            cursor: pointer;
            background: #f8f9fa;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        
        .chart-tab.active {
            background: white;
            border-color: #dee2e6;
            border-bottom-color: white;
            position: relative;
            bottom: -1px;
        }
        
        .chart-content {
            display: none;
        }
        
        .chart-content.active {
            display: block;
        }
        
        @media (max-width: 992px) {
            .search-area {
                flex-direction: column;
                align-items: stretch;
            }
            
            .gauge-container {
                flex-direction: column;
            }
        }
    </style>
</head>

<?php include("php/header.php"); ?>

<div id="page-wrapper">
    <div id="page-inner" class="p-4">
        <h1 class="page-head-line">Student Dropout Analytics Dashboard</h1>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <div class="stat-card box-high">
                <p>High Risk Students</p>
                <h3><?= $riskCounts['High'] ?></h3>
                <p><?= number_format(($riskCounts['High'] / array_sum($riskCounts)) * 100, 1) ?>% of Total</p>
            </div>
            <div class="stat-card box-medium">
                <p>Medium Risk Students</p>
                <h3><?= $riskCounts['Medium'] ?></h3>
                <p><?= number_format(($riskCounts['Medium'] / array_sum($riskCounts)) * 100, 1) ?>% of Total</p>
            </div>
            <div class="stat-card box-low">
                <p>Low Risk Students</p>
                <h3><?= $riskCounts['Low'] ?></h3>
                <p><?= number_format(($riskCounts['Low'] / array_sum($riskCounts)) * 100, 1) ?>% of Total</p>
            </div>
            <div class="stat-card">
                <p>Total Students</p>
                <h3><?= array_sum($riskCounts) ?></h3>
                <p>Dropout Prediction Analysis</p>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="chart-container">
            <div class="dashboard-header">
                <h4 class="m-0">Dropout Risk Analytics</h4>
            </div>
            
            <div class="chart-tabs mt-3">
                <div class="chart-tab active" data-target="overview-chart">Overview</div>
                <div class="chart-tab" data-target="course-chart">By Course</div>
                <div class="chart-tab" data-target="year-chart">By Year Level</div>
            </div>
            
            <div class="chart-content active" id="overview-chart">
                <div class="gauge-container">
                    <div class="gauge-box">
                        <div id="highRiskGauge"></div>
                    </div>
                    <div class="gauge-box">
                        <div id="mediumRiskGauge"></div>
                    </div>
                    <div class="gauge-box">
                        <div id="lowRiskGauge"></div>
                    </div>
                </div>
            </div>
            
            <div class="chart-content" id="course-chart">
                <canvas id="courseChart" height="300"></canvas>
            </div>
            
            <div class="chart-content" id="year-chart">
                <canvas id="yearChart" height="300"></canvas>
            </div>
        </div>
        
        <!-- Data Table -->
        <div class="table-container">
            <div class="table-header">
                <h4 class="m-0">Student Dropout Prediction Results</h4>
            </div>
            
            <div class="p-4">
                <!-- Search and Filter Area -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form method="GET" class="form-inline d-flex align-items-center">
                            <label class="mr-2">Show 
                                <select name="entries" class="form-control mx-2" onchange="this.form.submit()">
                                    <option value="10" <?= (isset($_GET['entries']) && $_GET['entries'] == 10) ? 'selected' : '' ?>>10</option>
                                    <option value="25" <?= (isset($_GET['entries']) && $_GET['entries'] == 25) ? 'selected' : '' ?>>25</option>
                                    <option value="50" <?= (isset($_GET['entries']) && $_GET['entries'] == 50) ? 'selected' : '' ?>>50</option>
                                    <option value="<?= $totalStudents ?>" <?= (isset($_GET['entries']) && $_GET['entries'] == $totalStudents) ? 'selected' : '' ?>>All</option>
                                </select> entries
                            </label>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" class="d-flex justify-content-end">
                            <select name="searchField" class="form-control mr-2" style="width: auto;">
                                <option value="all" <?= $searchField == 'all' ? 'selected' : '' ?>>All Fields</option>
                                <option value="id" <?= $searchField == 'id' ? 'selected' : '' ?>>Student ID</option>
                                <option value="name" <?= $searchField == 'name' ? 'selected' : '' ?>>Name</option>
                                <option value="course" <?= $searchField == 'course' ? 'selected' : '' ?>>Course</option>
                                <option value="year" <?= $searchField == 'year' ? 'selected' : '' ?>>Year Level</option>
                            </select>
                            <div class="input-group">
                                <input type="text" name="searchQuery" class="form-control" placeholder="Search students..." value="<?= htmlspecialchars($searchInput) ?>" required>
                                <div class="input-group-append">
                                    <button type="submit" name="search" class="btn btn-primary">
                                        <i class="fa fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
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
                                <th>Risk Level</th>
                                <th>GPA</th>
                                <th>Attendance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($paginatedStudents as $index => $student) {
                                $StudentID = $conn->real_escape_string($student['StudentID']);
                                $table_name = $conn->real_escape_string($student['table']);
                                $res = $conn->query("SELECT StudentID, sname, year, course, Attendance, GPA, balance FROM `$table_name` WHERE StudentID = '$StudentID'");
                                
                                if ($res && $row = $res->fetch_assoc()) {
                                    // Determine risk class for styling
                                    $riskClass = '';
                                    if ($student['final_risk_level'] == 'High Risk') {
                                        $riskClass = 'risk-high';
                                    } elseif ($student['final_risk_level'] == 'Medium Risk') {
                                        $riskClass = 'risk-medium';
                                    } else {
                                        $riskClass = 'risk-low';
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td><strong>{$row['StudentID']}</strong></td>";
                                    echo "<td>{$row['sname']}</td>";
                                    echo "<td>{$row['year']}</td>";
                                    echo "<td>{$row['course']}</td>";
                                    echo "<td><span class='risk-badge {$riskClass}'>{$student['final_risk_level']}</span></td>";
                                    echo "<td>{$row['GPA']}</td>";
                                    echo "<td>{$row['Attendance']}%</td>";
                                    echo "<td>
                                        <button class='btn btn-primary btn-sm' data-toggle='modal' data-target='#detailsModal{$index}'>
                                            <i class='fa fa-eye'></i> View Stats
                                        </button>
                                    </td>";
                                    echo "</tr>";

                                    // Modal
                                    echo "
                                    <div class='modal fade' id='detailsModal{$index}' tabindex='-1' role='dialog' aria-labelledby='modalLabel{$index}' aria-hidden='true'>
                                        <div class='modal-dialog modal-lg' role='document'>
                                            <div class='modal-content'>
                                                <div class='modal-header bg-primary text-white'>
                                                    <h5 class='modal-title'>Student Analytics - {$row['sname']}</h5>
                                                    <button type='button' class='close text-white' data-dismiss='modal' aria-label='Close'>
                                                        <span aria-hidden='true'>&times;</span>
                                                    </button>
                                                </div>
                                                <div class='modal-body'>
                                                    <div class='row'>
                                                        <div class='col-md-6'>
                                                            <div class='card mb-3'>
                                                                <div class='card-header bg-light'>
                                                                    <h6 class='m-0'>Student Information</h6>
                                                                </div>
                                                                <div class='card-body'>
                                                                    <p><strong>Student ID:</strong> {$row['StudentID']}</p>
                                                                    <p><strong>Name:</strong> {$row['sname']}</p>
                                                                    <p><strong>Course:</strong> {$row['course']}</p>
                                                                    <p><strong>Year Level:</strong> {$row['year']}</p>
                                                                    <p><strong>GPA:</strong> {$row['GPA']}</p>
                                                                    <p><strong>Attendance:</strong> {$row['Attendance']}%</p>
                                                                    <p><strong>Balance:</strong> ₱" . number_format($row['balance'], 2) . "</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class='col-md-6'>
                                                            <div class='card mb-3'>
                                                                <div class='card-header bg-light'>
                                                                    <h6 class='m-0'>Dropout Risk Analysis</h6>
                                                                </div>
                                                                <div class='card-body'>
                                                                    <div class='mb-3 text-center'>
                                                                        <h2 class='display-4 mb-0'>{$student['dropout_percentage']}%</h2>
                                                                        <p class='text-muted'>Dropout Probability</p>
                                                                    </div>
                                                                    <p><strong>Risk Level:</strong> <span class='risk-badge {$riskClass}'>{$student['final_risk_level']}</span></p>
                                                                    <p><strong>Model Predicted Risk:</strong> {$student['model_predicted_risk']}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class='card mb-3'>
                                                        <div class='card-header bg-light'>
                                                            <h6 class='m-0'>Risk Factors & Recommendations</h6>
                                                        </div>
                                                        <div class='card-body'>
                                                            <div class='row'>
                                                                <div class='col-md-6'>
                                                                    <h6>Possible Reasons for Dropping:</h6>
                                                                    <ul>";
                                                                    foreach ($student['reasons'] as $reason) {
                                                                        echo "<li>{$reason}</li>";
                                                                    }
                                                                    echo "</ul>
                                                                </div>
                                                                <div class='col-md-6'>
                                                                    <h6>Recommended Solutions:</h6>
                                                                    <ul>";
                                                                    foreach ($student['recommended_solutions'] as $solution) {
                                                                        echo "<li>{$solution}</li>";
                                                                    }
                                                                    echo "</ul>
                                                                    </div>
                                                                    <div class='col-md-6'>
                                                                        <h6>Recommended Solutions:</h6>
                                                                        <ul>";
                                                                        foreach ($student['recommended_solutions'] as $solution) {
                                                                            echo "<li>{$solution}</li>";
                                                                        }
                                                                        echo "</ul>
                                                                    </div>
                                                                </div>
                                                                <div class='mt-3'>
                                                                    <h6>Administrator Action Required:</h6>
                                                                    <ul>";
                                                                    foreach ($student['admin_action'] as $action) {
                                                                        echo "<li>{$action}</li>";
                                                                    }
                                                                    echo "</ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class='card'>
                                                            <div class='card-header bg-light'>
                                                                <h6 class='m-0'>Send Notification</h6>
                                                            </div>
                                                            <div class='card-body'>
                                                                <form method='post'>
                                                                    <input type='hidden' name='studentId' value='{$row['StudentID']}'>
                                                                    <div class='form-group'>
                                                                        <label for='email'>Email To (separate multiple emails with commas):</label>
                                                                        <input type='text' class='form-control' id='email' name='email' required>
                                                                    </div>
                                                                    <div class='form-group'>
                                                                        <label for='message'>Message:</label>
                                                                        <textarea class='form-control' id='message' name='message' rows='3' required></textarea>
                                                                    </div>
                                                                    <button type='submit' name='send_email' class='btn btn-primary'>
                                                                        <i class='fa fa-envelope'></i> Send Notification
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='modal-footer'>
                                                        <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if($totalPages > 1): ?>
                    <div class="pagination-container mt-4">
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php if($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1<?= isset($_GET['entries']) ? '&entries='.$_GET['entries'] : '' ?><?= isset($_GET['searchResult']) ? '&searchResult=1' : '' ?>">First</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page-1 ?><?= isset($_GET['entries']) ? '&entries='.$_GET['entries'] : '' ?><?= isset($_GET['searchResult']) ? '&searchResult=1' : '' ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                // Show a limited number of page links
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                for($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= isset($_GET['entries']) ? '&entries='.$_GET['entries'] : '' ?><?= isset($_GET['searchResult']) ? '&searchResult=1' : '' ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page+1 ?><?= isset($_GET['entries']) ? '&entries='.$_GET['entries'] : '' ?><?= isset($_GET['searchResult']) ? '&searchResult=1' : '' ?>">Next</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $totalPages ?><?= isset($_GET['entries']) ? '&entries='.$_GET['entries'] : '' ?><?= isset($_GET['searchResult']) ? '&searchResult=1' : '' ?>">Last</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <p>Showing <?= count($paginatedStudents) ?> out of <?= $totalStudents ?> entries</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript for Charts and UI -->
    <script src="js/jquery-1.11.1.js"></script>
    <script src="js/bootstrap.js"></script>
    <script>
    // Chart.js Initialization
    document.addEventListener('DOMContentLoaded', function() {
        // Chart tab switching
        document.querySelectorAll('.chart-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.chart-tab').forEach(function(t) {
                    t.classList.remove('active');
                });
                document.querySelectorAll('.chart-content').forEach(function(c) {
                    c.classList.remove('active');
                });
                
                // Add active class to clicked tab and its content
                this.classList.add('active');
                document.getElementById(this.getAttribute('data-target')).classList.add('active');
            });
        });
        
        // Course Chart
        var courseCtx = document.getElementById('courseChart').getContext('2d');
        var courseData = {
            labels: [
                <?php 
                foreach($courseRiskData as $course => $data) {
                    echo "'" . addslashes($course) . "', ";
                }
                ?>
            ],
            datasets: [
                {
                    label: 'High Risk',
                    backgroundColor: '#dc3545',
                    data: [
                        <?php 
                        foreach($courseRiskData as $data) {
                            echo $data['High'] . ", ";
                        }
                        ?>
                    ]
                },
                {
                    label: 'Medium Risk',
                    backgroundColor: '#ffc107',
                    data: [
                        <?php 
                        foreach($courseRiskData as $data) {
                            echo $data['Medium'] . ", ";
                        }
                        ?>
                    ]
                },
                {
                    label: 'Low Risk',
                    backgroundColor: '#28a745',
                    data: [
                        <?php 
                        foreach($courseRiskData as $data) {
                            echo $data['Low'] . ", ";
                        }
                        ?>
                    ]
                }
            ]
        };
        
        new Chart(courseCtx, {
            type: 'bar',
            data: courseData,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Dropout Risk by Course',
                        font: {
                            size: 16
                        }
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
        
        // Year Chart
        var yearCtx = document.getElementById('yearChart').getContext('2d');
        var yearData = {
            labels: [
                <?php 
                foreach($yearRiskData as $year => $data) {
                    echo "'" . addslashes($year) . "', ";
                }
                ?>
            ],
            datasets: [
                {
                    label: 'High Risk',
                    backgroundColor: '#dc3545',
                    data: [
                        <?php 
                        foreach($yearRiskData as $data) {
                            echo $data['High'] . ", ";
                        }
                        ?>
                    ]
                },
                {
                    label: 'Medium Risk',
                    backgroundColor: '#ffc107',
                    data: [
                        <?php 
                        foreach($yearRiskData as $data) {
                            echo $data['Medium'] . ", ";
                        }
                        ?>
                    ]
                },
                {
                    label: 'Low Risk',
                    backgroundColor: '#28a745',
                    data: [
                        <?php 
                        foreach($yearRiskData as $data) {
                            echo $data['Low'] . ", ";
                        }
                        ?>
                    ]
                }
            ]
        };
        
        new Chart(yearCtx, {
            type: 'bar',
            data: yearData,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Dropout Risk by Year Level',
                        font: {
                            size: 16
                        }
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
        
        // Gauge Charts using ApexCharts
        const totalStudents = <?= array_sum($riskCounts) ?>;
        const highRiskPercent = <?= ($riskCounts['High'] > 0) ? round(($riskCounts['High'] / array_sum($riskCounts)) * 100, 1) : 0 ?>;
        const mediumRiskPercent = <?= ($riskCounts['Medium'] > 0) ? round(($riskCounts['Medium'] / array_sum($riskCounts)) * 100, 1) : 0 ?>;
        const lowRiskPercent = <?= ($riskCounts['Low'] > 0) ? round(($riskCounts['Low'] / array_sum($riskCounts)) * 100, 1) : 0 ?>;
        
        // High Risk Gauge
        var highRiskOptions = {
            series: [highRiskPercent],
            chart: {
                height: 250,
                type: 'radialBar',
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                radialBar: {
                    startAngle: -135,
                    endAngle: 135,
                    hollow: {
                        margin: 0,
                        size: '70%',
                    },
                    dataLabels: {
                        name: {
                            offsetY: -10,
                            show: true,
                            color: '#888',
                            fontSize: '17px'
                        },
                        value: {
                            color: '#111',
                            fontSize: '36px',
                            show: true,
                            formatter: function (val) {
                                return val + '%';
                            }
                        }
                    },
                    track: {
                        background: '#f2f2f2',
                        strokeWidth: '97%',
                        margin: 5
                    }
                }
            },
            fill: {
                colors: ['#dc3545']
            },
            stroke: {
                lineCap: 'round'
            },
            labels: ['High Risk']
        };
    
        var highRiskChart = new ApexCharts(document.querySelector("#highRiskGauge"), highRiskOptions);
        highRiskChart.render();
        
        // Medium Risk Gauge
        var mediumRiskOptions = {
            series: [mediumRiskPercent],
            chart: {
                height: 250,
                type: 'radialBar',
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                radialBar: {
                    startAngle: -135,
                    endAngle: 135,
                    hollow: {
                        margin: 0,
                        size: '70%',
                    },
                    dataLabels: {
                        name: {
                            offsetY: -10,
                            show: true,
                            color: '#888',
                            fontSize: '17px'
                        },
                        value: {
                            color: '#111',
                            fontSize: '36px',
                            show: true,
                            formatter: function (val) {
                                return val + '%';
                            }
                        }
                    },
                    track: {
                        background: '#f2f2f2',
                        strokeWidth: '97%',
                        margin: 5
                    }
                }
            },
            fill: {
                colors: ['#ffc107']
            },
            stroke: {
                lineCap: 'round'
            },
            labels: ['Medium Risk']
        };
    
        var mediumRiskChart = new ApexCharts(document.querySelector("#mediumRiskGauge"), mediumRiskOptions);
        mediumRiskChart.render();
        
        // Low Risk Gauge
        var lowRiskOptions = {
            series: [lowRiskPercent],
            chart: {
                height: 250,
                type: 'radialBar',
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                radialBar: {
                    startAngle: -135,
                    endAngle: 135,
                    hollow: {
                        margin: 0,
                        size: '70%',
                    },
                    dataLabels: {
                        name: {
                            offsetY: -10,
                            show: true,
                            color: '#888',
                            fontSize: '17px'
                        },
                        value: {
                            color: '#111',
                            fontSize: '36px',
                            show: true,
                            formatter: function (val) {
                                return val + '%';
                            }
                        }
                    },
                    track: {
                        background: '#f2f2f2',
                        strokeWidth: '97%',
                        margin: 5
                    }
                }
            },
            fill: {
                colors: ['#28a745']
            },
            stroke: {
                lineCap: 'round'
            },
            labels: ['Low Risk']
        };
    
        var lowRiskChart = new ApexCharts(document.querySelector("#lowRiskGauge"), lowRiskOptions);
        lowRiskChart.render();
    });
    </script>
    <script src="js/bootstrap.js"></script>
    <!-- METISMENU SCRIPTS -->
    <script src="js/jquery.metisMenu.js"></script>
    <!-- CUSTOM SCRIPTS -->
    <script src="js/custom1.js"></script>
    
    </body>
    </html>