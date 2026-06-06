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

// ✅ Risk count for Chart.js
$riskCounts = ['High' => 0, 'Medium' => 0, 'Low' => 0];
foreach ($results as $student) {
    $level = $student['final_risk_level'];
    if ($level == "High Risk") $riskCounts['High']++;
    elseif ($level == "Medium Risk") $riskCounts['Medium']++;
    else $riskCounts['Low']++;
}

// ✅ Search Logic with Redirect
$searchResult = [];
$searchTerm = '';

if (isset($_POST['search'])) {
    $searchInput = $conn->real_escape_string($_POST['searchTerm']); // Single input for StudentID, Name, or Course
    $searchTerm = $searchInput; // Store for displaying in the search box
    $filteredResults = [];

    foreach ($results as $student) {
        // Get additional data from database
        $StudentID = $conn->real_escape_string($student['StudentID']);
        $table_name = $conn->real_escape_string($student['table']);
        $res = $conn->query("SELECT StudentID, sname, year, course, semester, Attendance, GPA, balance FROM `$table_name` WHERE StudentID = '$StudentID'");
        if ($res && $row = $res->fetch_assoc()) {
            // ✅ Check if search input matches any of the criteria (case-insensitive)
            if (stripos($row['StudentID'], $searchInput) !== false || 
                stripos($row['sname'], $searchInput) !== false || 
                stripos($row['course'], $searchInput) !== false ||
                stripos($row['year'], $searchInput) !== false ||
                (isset($row['semester']) && stripos($row['semester'], $searchInput) !== false)) {
                $student = array_merge($student, $row); // Merge database fields with prediction data
                $filteredResults[] = $student;
            }
        }
    }

    // ✅ Store the search results temporarily
    $_SESSION['searchResult'] = $filteredResults;
    $_SESSION['searchTerm'] = $searchTerm;

    // ✅ Redirect to clear POST and prevent search persistence on refresh
    header("Location: " . $_SERVER['PHP_SELF'] . "?searchResult=1");
    exit();
} elseif (isset($_GET['searchResult']) && isset($_SESSION['searchResult'])) {
    // ✅ Display the search result from session
    $searchResult = $_SESSION['searchResult'];
    $searchTerm = isset($_SESSION['searchTerm']) ? $_SESSION['searchTerm'] : '';
} else {
    // ✅ Default: Show all results with complete student data
    $searchResult = [];
    foreach ($results as $student) {
        $StudentID = $conn->real_escape_string($student['StudentID']);
        $table_name = $conn->real_escape_string($student['table']);
        $res = $conn->query("SELECT StudentID, sname, year, course, semester, Attendance, GPA, balance FROM `$table_name` WHERE StudentID = '$StudentID'");
        if ($res && $row = $res->fetch_assoc()) {
            $searchResult[] = array_merge($student, $row);
        }
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

// ✅ Email functionality
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize messages
$error = $success = "";
if (isset($_POST['send_email'])) {
    $emails = explode(',', $_POST['email']); // Allow multiple emails
    $message = $_POST['message'];
    $studentId = $_POST['studentId'];
    
    // Get student data for email
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
            $mail->Username = 'smartmedsystem@gmail.com'; // Replace with your email
            $mail->Password = 'dxrc vypx qelu irfj'; // Replace with your app-specific password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('yourgmail@gmail.com', 'Dropout Risk System');

            foreach ($emails as $address) {
                $mail->addAddress(trim($address));
            }

            $mail->isHTML(true);
            $mail->Subject = "Dropout Risk Notification";

            // Build the full student details for the email
            $studentDetails = "
                <p><strong>Risk Level:</strong> {$studentData['final_risk_level']}</p>
                <p><strong>Model Predicted Risk:</strong> {$studentData['model_predicted_risk']}</p>
                <p><strong>Dropout Percentage:</strong> {$studentData['dropout_percentage']}%</p>
                <p><strong>Possible Reasons of Dropping:</strong> " . implode(", ", $studentData['reasons']) . "</p>
                <p><strong>Solutions:</strong> " . implode(", ", $studentData['recommended_solutions']) . "</p>
                <p><strong>Admin Action:</strong> " . implode(", ", $studentData['admin_action']) . "</p>
            ";

            // Append the student details inside the email
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

// Calculate semester-wise risk statistics
$semesterStats = [];
foreach ($searchResult as $student) {
    if (isset($student['semester'])) {
        $semester = $student['semester'];
        if (!isset($semesterStats[$semester])) {
            $semesterStats[$semester] = ['High' => 0, 'Medium' => 0, 'Low' => 0, 'Total' => 0];
        }
        
        $level = $student['final_risk_level'];
        if ($level == "High Risk") $semesterStats[$semester]['High']++;
        elseif ($level == "Medium Risk") $semesterStats[$semester]['Medium']++;
        else $semesterStats[$semester]['Low']++;
        
        $semesterStats[$semester]['Total']++;
    }
}

// Calculate course-wise risk statistics
$courseStats = [];
foreach ($searchResult as $student) {
    if (isset($student['course'])) {
        $course = $student['course'];
        if (!isset($courseStats[$course])) {
            $courseStats[$course] = ['High' => 0, 'Medium' => 0, 'Low' => 0, 'Total' => 0];
        }
        
        $level = $student['final_risk_level'];
        if ($level == "High Risk") $courseStats[$course]['High']++;
        elseif ($level == "Medium Risk") $courseStats[$course]['Medium']++;
        else $courseStats[$course]['Low']++;
        
        $courseStats[$course]['Total']++;
    }
}



// Add this filtering logic after the search logic and before pagination

// Apply year and semester filters
if (isset($_GET['filterYear']) && !empty($_GET['filterYear'])) {
    $filterYear = $_GET['filterYear'];
    $searchResult = array_filter($searchResult, function($student) use ($filterYear) {
        return isset($student['year']) && $student['year'] == $filterYear;
    });
}

if (isset($_GET['filterSemester']) && !empty($_GET['filterSemester'])) {
    $filterSemester = $_GET['filterSemester'];
    $searchResult = array_filter($searchResult, function($student) use ($filterSemester) {
        return isset($student['semester']) && $student['semester'] == $filterSemester;
    });
}

// Re-index the array after filtering
$searchResult = array_values($searchResult);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dropout Prediction System</title>
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
        
        /* Charts */
        .chart-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }
        .gauge-chart {
            flex: 1;
            min-width: 200px;
            max-width: 300px;
        }
        
        /* Responsive fixes */
        @media (max-width: 768px) {
            .search-container {
                flex-direction: column;
                align-items: stretch;
            }
            .search-box, .entries-selector {
                max-width: 100%;
                width: 100%;
            }
            .gauge-chart {
                min-width: 100%;
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
                    <h1 class="page-head-line">Student Dropout Prediction Dashboard</h1>
                </div>
            </div>
            
       
       
            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card high-risk">
                        <h3><i class="fa fa-warning"></i> High Risk Students</h3>
                        <div class="count"><?= $riskCounts['High'] ?></div>
                        <p><?= round(($riskCounts['High'] / max(1, array_sum($riskCounts))) * 100, 1) ?>% of total</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card medium-risk">
                        <h3><i class="fa fa-exclamation-circle"></i> Medium Risk Students</h3>
                        <div class="count"><?= $riskCounts['Medium'] ?></div>
                        <p><?= round(($riskCounts['Medium'] / max(1, array_sum($riskCounts))) * 100, 1) ?>% of total</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card low-risk">
                        <h3><i class="fa fa-check-circle"></i> Low Risk Students</h3>
                        <div class="count"><?= $riskCounts['Low'] ?></div>
                        <p><?= round(($riskCounts['Low'] / max(1, array_sum($riskCounts))) * 100, 1) ?>% of total</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card total-students">
                        <h3><i class="fa fa-users"></i> Total Students</h3>
                        <div class="count"><?= array_sum($riskCounts) ?></div>
                        <p><?= count($searchResult) ?> currently displayed</p>
                    </div>
                </div>
            </div>
            
            <!-- Charts Panel -->
            <div class="panel panel-default" style="border-color:rgba(1, 129, 55);">
                <div class="panel-heading" style="background-color: rgba(1, 129, 55); color: white; font-weight: 100;">
                    <div class="panel-title">
                        <i class="fa fa-bar-chart-o"></i> Dropout Risk Analysis
                    </div>
                </div>
                <div class="panel-body">
                    <div class="chart-container">
                        <div class="gauge-chart" id="highRiskGauge"></div>
                        <div class="gauge-chart" id="mediumRiskGauge"></div>
                        <div class="gauge-chart" id="lowRiskGauge"></div>
                    </div>
                    <div style="margin-top: 30px;">
                        <h4>Course-wise Risk Distribution</h4>
                        <canvas id="courseChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Data Table -->
            <div class="panel panel-default" style="border-color:rgba(1, 129, 55);">
                <div class="panel-heading" style="background-color: rgba(1, 129, 55); color: white; font-weight: 100;">
                    <div class="panel-title">
                        <i class="fa fa-table"></i> Prediction Results
                    </div>
                </div>
                <div class="panel-body">
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <div class="search-container">
                        <div>
                            <form method="GET" class="form-inline">
                                <label>Show 
                                    <select name="entries" class="form-control" onchange="this.form.submit()">
                                        <option value="10" <?= (isset($_GET['entries']) && $_GET['entries'] == 10) ? 'selected' : '' ?>>10</option>
                                        <option value="25" <?= (isset($_GET['entries']) && $_GET['entries'] == 25) ? 'selected' : '' ?>>25</option>
                                        <option value="50" <?= (isset($_GET['entries']) && $_GET['entries'] == 50) ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= (isset($_GET['entries']) && $_GET['entries'] == 100) ? 'selected' : '' ?>>100</option>
                                        <option value="<?= $totalStudents ?>" <?= (isset($_GET['entries']) && $_GET['entries'] == $totalStudents) ? 'selected' : '' ?>>All</option>
                                    </select> entries
                                </label>
                            </form>
                        </div>
                        
                        <div class="search-box">
                            <form method="POST" class="input-group">
                                <input type="text" name="searchTerm" class="form-control" placeholder="Search by ID, Name, Course, Year or Semester" value="<?= htmlspecialchars($searchTerm) ?>">
                                <span class="input-group-btn">
                                    <button type="submit" name="search" class="btn btn-primary">
                                        <i class="fa fa-search"></i> Search
                                    </button>
                                </span>
                            </form>
                        </div>
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
                                        <td colspan="8" class="text-center">No students found matching your criteria.</td>
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
        $filterParams = '';
        if (isset($_GET['filterYear']) && !empty($_GET['filterYear'])) {
            $filterParams .= '&filterYear=' . urlencode($_GET['filterYear']);
        }
        if (isset($_GET['filterSemester']) && !empty($_GET['filterSemester'])) {
            $filterParams .= '&filterSemester=' . urlencode($_GET['filterSemester']);
        }
        if (isset($_GET['searchResult'])) {
            $filterParams .= '&searchResult=1';
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
                                        <p><strong>Attendance:</strong> <?= htmlspecialchars($student['Attendance']) ?></p>
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
                                        <textarea name="message" id="message<?= $index ?>" class="form-control" rows="4" required>Dear <?= htmlspecialchars($student['sname']) ?>,

We would like to inform you about your current academic status. Our system has identified that you may be at risk of dropping out. We encourage you to schedule a meeting with your academic advisor to discuss strategies for improvement.

Best regards,
Student Support Services</textarea>
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
                    <div class="row">
                        <div class="col-md-6">
                            <div class="panel panel-default">
                                <div class="panel-heading" style="background-color: rgba(1, 129, 55, 0.9); color: white;">
                                    <h3 class="panel-title">Academic Performance</h3>
                                </div>
                                <div class="panel-body">
                                    <canvas id="performanceChart<?= $index ?>" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="panel panel-default">
                                <div class="panel-heading" style="background-color: rgba(1, 129, 55, 0.9); color: white;">
                                    <h3 class="panel-title">Attendance History</h3>
                                </div>
                                <div class="panel-body">
                                    <canvas id="attendanceChart<?= $index ?>" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                                
                                                // GPA Risk Factor
                                                $gpaRisk = 'Low';
                                                $gpaImpact = 'Low';
                                                if (floatval($student['GPA']) < 2.0) {
                                                    $gpaRisk = 'High';
                                                    $gpaImpact = 'Critical';
                                                } elseif (floatval($student['GPA']) < 2.5) {
                                                    $gpaRisk = 'Medium';
                                                    $gpaImpact = 'Significant';
                                                }
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
document.addEventListener('DOMContentLoaded', function() {
    // Initialize gauge charts for each student
    <?php foreach ($paginatedStudents as $index => $student): ?>
        // Set up GPA gauge
        new ApexCharts(document.querySelector("#gpaGauge<?= $index ?>"), {
            chart: {
                type: 'radialBar',
                height: 120,
                offsetY: -10,
                sparkline: {
                    enabled: true
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
                    hollow: {
                        size: '35%',
                    },
                    dataLabels: {
                        show: false
                    }
                }
            },
            colors: ['<?= floatval($student['GPA']) < 2.0 ? '#e74c3c' : (floatval($student['GPA']) < 2.5 ? '#f1c40f' : '#2ecc71') ?>'],
            series: [<?= min(100, floatval($student['GPA']) / 4.0 * 100) ?>]
        }).render();
        
        // Set up Attendance gauge
        new ApexCharts(document.querySelector("#attendanceGauge<?= $index ?>"), {
            chart: {
                type: 'radialBar',
                height: 120,
                offsetY: -10,
                sparkline: {
                    enabled: true
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
                    hollow: {
                        size: '35%',
                    },
                    dataLabels: {
                        show: false
                    }
                }
            },
            colors: ['<?= floatval($student['Attendance']) < 75 ? '#e74c3c' : (floatval($student['Attendance']) < 85 ? '#f1c40f' : '#2ecc71') ?>'],
            series: [<?= min(100, floatval($student['Attendance'])) ?>]
        }).render();
        
        // Set up Risk gauge
        new ApexCharts(document.querySelector("#riskGauge<?= $index ?>"), {
            chart: {
                type: 'radialBar',
                height: 120,
                offsetY: -10,
                sparkline: {
                    enabled: true
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
                    hollow: {
                        size: '35%',
                    },
                    dataLabels: {
                        show: false
                    }
                }
            },
            colors: ['<?= floatval($student['dropout_percentage']) > 66 ? '#e74c3c' : (floatval($student['dropout_percentage']) > 33 ? '#f1c40f' : '#2ecc71') ?>'],
            series: [<?= min(100, floatval($student['dropout_percentage'])) ?>]
        }).render();
        
        // Set up Balance indicator
        const balanceVal = <?= floatval($student['balance']) ?>;
        const balanceColor = balanceVal > 10000 ? '#e74c3c' : (balanceVal > 5000 ? '#f1c40f' : '#2ecc71');
        document.getElementById('balanceIndicator<?= $index ?>').style.backgroundColor = balanceColor;
        
        // Mock data for charts - in production you'd use real historical data
        // Performance Chart
        new Chart(document.getElementById('performanceChart<?= $index ?>').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4', 'Current'],
                datasets: [{
                    label: 'GPA History',
                    data: [
                        <?= (floatval($student['GPA']) * 0.85 + 0.5) ?>, 
                        <?= (floatval($student['GPA']) * 0.90 + 0.4) ?>, 
                        <?= (floatval($student['GPA']) * 0.95 + 0.3) ?>, 
                        <?= (floatval($student['GPA']) * 0.98 + 0.1) ?>, 
                        <?= floatval($student['GPA']) ?>
                    ],
                    borderColor: 'rgba(1, 129, 55, 0.8)',
                    backgroundColor: 'rgba(1, 129, 55, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
        aspectRatio: 1.8,
                plugins: {
                    title: {
                        display: true,
                        text: 'GPA Trend'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 0,
                        max: 4.0
                    }
                }
            }
        });
        
        // Attendance Chart
        new Chart(document.getElementById('attendanceChart<?= $index ?>').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Current'],
                datasets: [{
                    label: 'Attendance %',
                    data: [
                        <?= min(100, floatval($student['Attendance']) * 1.05) ?>, 
                        <?= min(100, floatval($student['Attendance']) * 1.03) ?>, 
                        <?= min(100, floatval($student['Attendance']) * 1.01) ?>, 
                        <?= max(60, floatval($student['Attendance']) * 0.97) ?>, 
                        <?= max(60, floatval($student['Attendance']) * 0.99) ?>, 
                        <?= floatval($student['Attendance']) ?>
                    ],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
        aspectRatio: 1.8,
                plugins: {
                    title: {
                        display: true,
                        text: 'Attendance History'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 50,
                        max: 100
                    }
                }
            }
        });
        
        // Risk Factors Chart
        new Chart(document.getElementById('riskFactorsChart<?= $index ?>').getContext('2d'), {
            type: 'radar',
            data: {
                labels: ['GPA', 'Attendance', 'Financial', 'Engagement', 'Other Factors'],
                datasets: [{
                    label: 'Risk Factors',
                    data: [
                        <?= floatval($student['GPA']) < 2.0 ? 80 : (floatval($student['GPA']) < 2.5 ? 50 : 20) ?>,
                        <?= floatval($student['Attendance']) < 75 ? 80 : (floatval($student['Attendance']) < 85 ? 50 : 20) ?>,
                        <?= floatval($student['balance']) > 10000 ? 80 : (floatval($student['balance']) > 5000 ? 50 : 20) ?>,
                        <?= floatval($student['dropout_percentage']) > 66 ? 80 : (floatval($student['dropout_percentage']) > 33 ? 50 : 20) ?>,
                        <?= $student['final_risk_level'] == 'High Risk' ? 80 : ($student['final_risk_level'] == 'Medium Risk' ? 50 : 20) ?>
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
                        angleLines: {
                            display: true
                        },
                        suggestedMin: 0,
                        suggestedMax: 100
                    }
                }
            }
        });
    <?php endforeach; ?>
});

// Function to print the report
function printStatsReport(studentId, studentName) {
    const printWindow = window.open('', '_blank');
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
                <p><strong>Generated on:</strong> ${new Date().toLocaleDateString()}</p>
            </div>
            
            <div class="section">
                <h2>Risk Assessment Summary</h2>
                <p>This report provides a detailed analysis of the student's academic performance and dropout risk factors.</p>
                
                <table>
                    <tr>
                        <th>Risk Level</th>
                        <td class="risk-${document.querySelector('#statsModal' + studentId).querySelector('.risk-indicator').textContent.toLowerCase().includes('high') ? 'high' : (document.querySelector('#statsModal' + studentId).querySelector('.risk-indicator').textContent.toLowerCase().includes('medium') ? 'medium' : 'low')}">
                            ${document.querySelector('#statsModal' + studentId).querySelector('.risk-indicator').textContent}
                        </td>
                    </tr>
                    <tr>
                        <th>Dropout Percentage</th>
                        <td>${document.getElementById('riskIndicator' + studentId)?.textContent || 'N/A'}</td>
                    </tr>
                    <tr>
                        <th>Current GPA</th>
                        <td>${document.getElementById('gpaIndicator' + studentId)?.textContent || 'N/A'}</td>
                    </tr>
                    <tr>
                        <th>Attendance</th>
                        <td>${document.getElementById('attendanceIndicator' + studentId)?.textContent || 'N/A'}</td>
                    </tr>
                </table>
            </div>
            
            <div class="section">
                <h2>Key Risk Factors</h2>
                ${Array.from(document.querySelector('#statsModal' + studentId).querySelectorAll('.table-responsive table tbody tr')).map(row => {
                    const factor = row.querySelector('td:nth-child(1)').textContent;
                    const impact = row.querySelector('td:nth-child(2)').textContent;
                    const status = row.querySelector('td:nth-child(3) span').textContent;
                    const riskClass = status.toLowerCase().includes('high') ? 'high' : (status.toLowerCase().includes('medium') ? 'medium' : 'low');
                    return `<p><strong>${factor}:</strong> ${impact} - <span class="risk-${riskClass}">${status}</span></p>`;
                }).join('')}
            </div>
            
            <div class="section">
                <h2>Recommended Interventions</h2>
                <ol>
                    ${Array.from(document.querySelector('#statsModal' + studentId).querySelectorAll('.panel-title:contains("Recommended Solutions")').closest('.panel').querySelectorAll('ol li')).map(item => {
                        return `<li>${item.textContent}</li>`;
                    }).join('')}
                </ol>
            </div>
            
            <div class="section">
                <h2>Administrative Actions Required</h2>
                <ol>
                    ${Array.from(document.querySelector('#statsModal' + studentId).querySelectorAll('.panel-title:contains("Admin Actions")').closest('.panel').querySelectorAll('ol li')).map(item => {
                        return `<li>${item.textContent}</li>`;
                    }).join('')}
                </ol>
            </div>
            
            <div class="footer">
                <p>This report is generated by the Student Dropout Prediction System.</p>
                <p>Confidential: For administrative and advisory use only.</p>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    
    // Add a small delay to ensure content is loaded before printing
    setTimeout(() => {
        printWindow.print();
    }, 500);
}

// Helper for jQuery selector with contains
jQuery.expr[':'].contains = function(a, i, m) {
    return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
};
</script>
    <!-- Scripts -->
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="js/jquery.metisMenu.js"></script>
    <script src="js/custom1.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    
    <!-- Chart Visualizations -->
    <script>
        // Risk Gauge Charts
        const highRisk = <?= $riskCounts['High'] ?>;
        const mediumRisk = <?= $riskCounts['Medium'] ?>;
        const lowRisk = <?= $riskCounts['Low'] ?>;
        const total = highRisk + mediumRisk + lowRisk;

        function renderGauge(element, value, color, label, count) {
            var options = {
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

        renderGauge("#highRiskGauge", highRisk, '#e74c3c', 'High Risk', highRisk);
        renderGauge("#mediumRiskGauge", mediumRisk, '#f1c40f', 'Medium Risk', mediumRisk);
        renderGauge("#lowRiskGauge", lowRisk, '#2ecc71', 'Low Risk', lowRisk);

        // Course-wise Risk Distribution Chart
        const courseCtx = document.getElementById('courseChart');
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
                        text: 'Dropout Risk by Course'
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
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>

<?php $conn->close(); ?>