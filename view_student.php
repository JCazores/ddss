<?php $page='student';
include("php/dbconnect.php");
include("php/checklogin.php");

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
$errormsg = '';

// Check if ID exists
if(empty($id)) {
    header("location: student.php");
    exit;
}

// Fetch student details
$sqlStudent = $conn->query("SELECT s.*, y.year as yearName FROM student s 
                            LEFT JOIN year y ON s.year = y.id 
                            WHERE s.id='$id' AND s.delete_status='0'");

if($sqlStudent->num_rows == 0) {
    header("location: student.php");
    exit;
}

$studentData = $sqlStudent->fetch_assoc();

// Fetch payment history
$sqlPayments = $conn->query("SELECT * FROM fees_transaction WHERE stdid='$id' ORDER BY submitdate DESC");

// Get total payments
$totalPaid = $conn->query("SELECT SUM(paid) as paid FROM fees_transaction WHERE stdid='$id'")->fetch_assoc()['paid'];
$totalPaid = $totalPaid ? $totalPaid : 0;

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>OLFU Student Management System</title>

    <!-- BOOTSTRAP STYLES-->
    <link href="css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLES-->
    <link href="css/style1.css" rel="stylesheet" />
    <link href="css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css' />
    
    <link href="css/ui.css" rel="stylesheet" />
    <link href="css/datepicker.css" rel="stylesheet" />
    <link href="css/datatable/datatable.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/jquery/jquery-ui-1.10.1.custom.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    
    <style>
        :root {
            --primary-color: #018137;
            --primary-light: rgba(1, 129, 55, 0.1);
            --primary-dark: #016129;
            --secondary-color: #FFF;
            --text-dark: #333;
            --text-light: #777;
            --danger-color: #d9534f;
            --success-color: #5cb85c;
            --warning-color: #f0ad4e;
            --info-color: #5bc0de;
        }
        
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f5f5f5;
        }
        
        .navbar {
            background-color: var(--primary-color);
            border-bottom: 2px solid var(--primary-dark);
        }
        
        .panel {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: none;
        }
        
        .panel-heading {
            border-radius: 8px 8px 0 0;
            padding: 15px;
            font-weight: 600;
        }
        
        .panel-success .panel-heading {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn {
            border-radius: 4px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }
        
        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-dark);
        }
        
        .btn-success:hover {
            background-color: var(--primary-dark);
        }
        
        .page-head-line {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .profile-header {
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            color: #ccc;
            margin: 0 auto 15px;
            overflow: hidden;
            border: 3px solid var(--primary-light);
        }
        
        .student-name {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-dark);
            text-align: center;
        }
        
        .student-id {
            font-size: 16px;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .info-section {
            margin-top: 20px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .info-value {
            margin-bottom: 15px;
            color: var(--text-light);
        }
        
        .section-heading {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .tab-content {
            padding: 20px;
            background-color: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        
        .nav-tabs > li.active > a, 
        .nav-tabs > li.active > a:hover, 
        .nav-tabs > li.active > a:focus {
            border-top: 3px solid var(--primary-color);
            border-bottom: 1px solid transparent;
            font-weight: 600;
        }
        
        .payment-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 15px;
            margin-bottom: 15px;
            border-left: 3px solid var(--primary-color);
        }
        
        .payment-date {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .payment-amount {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .payment-remark {
            margin-top: 5px;
            color: var(--text-light);
            font-style: italic;
        }
        
        .progress {
            height: 8px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-left: 3px solid;
        }
        
        .stat-card-primary {
            border-color: var(--primary-color);
        }
        
        .stat-card-success {
            border-color: var(--success-color);
        }
        
        .stat-card-warning {
            border-color: var(--warning-color);
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin: 5px 0;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .chart-container {
            height: 250px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .profile-image {
                width: 100px;
                height: 100px;
                font-size: 48px;
            }
            
            .student-name {
                font-size: 20px;
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
                    <h1 class="page-head-line">Student Profile
                        <a href="student.php" class="btn btn-success btn-sm pull-right"><i class="fa fa-arrow-left"></i> Back to Students</a>
                    </h1>
                </div>
            </div>
            
            <div class="row animate__animated animate__fadeIn">
                <div class="col-md-4">
                    <div class="profile-header">
                        <div class="profile-image">
                            <i class="fa fa-user"></i>
                        </div>
                        <h3 class="student-name"><?php echo $studentData['sname']; ?></h3>
                        <div class="student-id"><?php echo $studentData['StudentID']; ?></div>
                        
                        <div class="text-center" style="margin-top: 20px;">
                            <a href="student.php?action=edit&id=<?php echo $id; ?>" class="btn btn-primary"><i class="fa fa-edit"></i> Edit Student</a>
                            <a href="#" class="btn btn-info" onclick="printProfile()"><i class="fa fa-print"></i> Print</a>
                        </div>
                    </div>
                    
                    <!-- Financial Summary -->
                    <div class="panel panel-default">
                        <div class="panel-heading" style="background-color: var(--primary-color); color: white;">
                            <i class="fa fa-money"></i> Financial Summary
                        </div>
                        <div class="panel-body">
                            <div class="stat-card stat-card-primary">
                                <div class="stat-label">Total Tuition Fee</div>
                                <div class="stat-value">₱<?php echo number_format($studentData['fees']); ?></div>
                            </div>
                            
                            <div class="stat-card stat-card-success">
                                <div class="stat-label">Amount Paid</div>
                                <div class="stat-value">₱<?php echo number_format($totalPaid); ?></div>
                            </div>
                            
                            <div class="stat-card stat-card-warning">
                                <div class="stat-label">Balance</div>
                                <div class="stat-value">₱<?php echo number_format($studentData['balance']); ?></div>
                            </div>
                            
                            <?php
                            // Calculate payment percentage
                            $paymentPercentage = 0;
                            if($studentData['fees'] > 0) {
                                $paymentPercentage = round((($studentData['fees'] - $studentData['balance']) / $studentData['fees']) * 100);
                            }
                            
                            // Determine progress bar class
                            $progressClass = "progress-bar-danger";
                            if($paymentPercentage >= 75) {
                                $progressClass = "progress-bar-success";
                            } else if($paymentPercentage >= 50) {
                                $progressClass = "progress-bar-info";
                            } else if($paymentPercentage >= 25) {
                                $progressClass = "progress-bar-warning";
                            }
                            ?>
                            
                            <div class="progress">
                                <div class="progress-bar <?php echo $progressClass; ?>" role="progressbar" aria-valuenow="<?php echo $paymentPercentage; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $paymentPercentage; ?>%">
                                </div>
                            </div>
                            <div class="text-center"><?php echo $paymentPercentage; ?>% Paid</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="panel panel-default">
                        <div class="panel-body" style="padding: 0;">
                            <ul class="nav nav-tabs">
                                <li class="active"><a data-toggle="tab" href="#details"><i class="fa fa-user"></i> Personal Details</a></li>
                                <li><a data-toggle="tab" href="#academic"><i class="fa fa-graduation-cap"></i> Academic Info</a></li>
                                <li><a data-toggle="tab" href="#financial"><i class="fa fa-money"></i> Payment History</a></li>
                            </ul>
                            
                            <div class="tab-content">
                                <!-- Personal Details Tab -->
                                <div id="details" class="tab-pane fade in active">
                                    <h4 class="section-heading">Personal Information</h4>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-label">Full Name</div>
                                            <div class="info-value"><?php echo $studentData['sname']; ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">Student ID</div>
                                            <div class="info-value"><?php echo $studentData['StudentID']; ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-label">Contact Number</div>
                                            <div class="info-value"><?php echo $studentData['contact']; ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">Email Address</div>
                                            <div class="info-value"><?php echo $studentData['emailid'] ? $studentData['emailid'] : 'Not provided'; ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-label">Date of Joining</div>
                                            <div class="info-value"><?php echo date("F d, Y", strtotime($studentData['joindate'])); ?></div>
                                        </div>
                                    </div>
                                    
                                    <?php if(!empty($studentData['about'])): ?>
                                    <h4 class="section-heading">Additional Information</h4>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="info-label">About Student</div>
                                            <div class="info-value"><?php echo nl2br($studentData['about']); ?></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Academic Info Tab -->
                                <div id="academic" class="tab-pane fade">
                                    <h4 class="section-heading">Academic Information</h4>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-label">Year Level</div>
                                            <div class="info-value"><?php echo $studentData['yearName']; ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">Course</div>
                                            <div class="info-value"><?php echo $studentData['course']; ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-label">Attendance</div>
                                            <div class="info-value">
                                                <?php if(!empty($studentData['Attendance'])): ?>
                                                    <div class="progress">
                                                        <?php
                                                        $attendanceClass = "progress-bar-danger";
                                                        if($studentData['Attendance'] >= 90) {
                                                            $attendanceClass = "progress-bar-success";
                                                        } else if($studentData['Attendance'] >= 80) {
                                                            $attendanceClass = "progress-bar-info";
                                                        } else if($studentData['Attendance'] >= 75) {
                                                            $attendanceClass = "progress-bar-warning";
                                                        }
                                                        ?>
                                                        <div class="progress-bar <?php echo $attendanceClass; ?>" role="progressbar" 
                                                             aria-valuenow="<?php echo $studentData['Attendance']; ?>" aria-valuemin="0" 
                                                             aria-valuemax="100" style="width: <?php echo $studentData['Attendance']; ?>%">
                                                        </div>
                                                    </div>
                                                    <?php echo $studentData['Attendance']; ?>%
                                                <?php else: ?>
                                                    Not recorded
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-label">GPA</div>
                                            <div class="info-value">
                                                <?php if(!empty($studentData['GPA'])): ?>
                                                    <div class="progress">
                                                        <?php
                                                        // Modified: Calculate progress bar based on 1-5 scale where 1 is highest
                                                        $gpaPercentage = (5 - $studentData['GPA']) / 4 * 100;
                                                        $gpaClass = "progress-bar-danger";
                                                        if($studentData['GPA'] <= 1.5) {
                                                            $gpaClass = "progress-bar-success";
                                                        } else if($studentData['GPA'] <= 2.5) {
                                                            $gpaClass = "progress-bar-info";
                                                        } else if($studentData['GPA'] <= 3.5) {
                                                            $gpaClass = "progress-bar-warning";
                                                        }
                                                        ?>
                                                        <div class="progress-bar <?php echo $gpaClass; ?>" role="progressbar" 
                                                             aria-valuenow="<?php echo $gpaPercentage; ?>" aria-valuemin="0" 
                                                             aria-valuemax="100" style="width: <?php echo $gpaPercentage; ?>%">
                                                        </div>
                                                    </div>
                                                    <?php echo number_format($studentData['GPA'], 2); ?> / 5.0
                                                <?php else: ?>
                                                    Not recorded
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Academic visualization -->
                                    <?php if(!empty($studentData['GPA']) || !empty($studentData['Attendance'])): ?>
                                    <div class="chart-container">
                                        <canvas id="academicChart"></canvas>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Payment History Tab -->
                                <div id="financial" class="tab-pane fade">
                                    <h4 class="section-heading">Payment History</h4>
                                    
                                    <?php if($sqlPayments->num_rows > 0): ?>
                                        <?php while($payment = $sqlPayments->fetch_assoc()): ?>
                                            <div class="payment-card">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="payment-date">
                                                            <i class="fa fa-calendar"></i> <?php echo date("F d, Y", strtotime($payment['submitdate'])); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="payment-amount">
                                                            ₱<?php echo number_format($payment['paid']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 text-right">
                                                        <span class="label label-success">Paid</span>
                                                    </div>
                                                </div>
                                                <?php if(!empty($payment['transcation_remark'])): ?>
                                                <div class="payment-remark">
                                                    <i class="fa fa-comment"></i> <?php echo $payment['transcation_remark']; ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> No payment records found for this student.
                                        </div>
                                    <?php endif; ?>
                                    
                                    <a href="fees.php?action=add&id=<?php echo $id; ?>" class="btn btn-success"><i class="fa fa-plus"></i> Add Payment</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /. PAGE INNER  -->
    </div>
    <!-- /. PAGE WRAPPER  -->
</div>
<!-- /. WRAPPER  -->

<!-- BOOTSTRAP SCRIPTS -->
<script src="js/bootstrap.js"></script>
<!-- METISMENU SCRIPTS -->
<script src="js/jquery.metisMenu.js"></script>
<!-- CUSTOM SCRIPTS -->
<script src="js/custom1.js"></script>

<script>
$(document).ready(function() {
    // Setup academic chart if data exists
    if(document.getElementById('academicChart')) {
        var ctx = document.getElementById('academicChart').getContext('2d');
        
        // Get GPA and attendance values, use 0 if not provided
        var gpa = <?php echo !empty($studentData['GPA']) ? $studentData['GPA'] : 0; ?>;
        var attendance = <?php echo !empty($studentData['Attendance']) ? $studentData['Attendance'] : 0; ?>;
        
        var academicChart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['GPA (scaled to 100%)', 'Attendance %'],
                datasets: [{
                    label: 'Current Performance',
                    // Modified: Calculate GPA percentage based on 1-5 scale where 1 is highest
                    data: [((5 - gpa) / 4) * 100, attendance],
                    backgroundColor: 'rgba(1, 129, 55, 0.2)',
                    borderColor: 'rgba(1, 129, 55, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(1, 129, 55, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                }
            }
        });
    }
});

// Print profile function
function printProfile() {
    window.print();
}
</script>

</body>
</html>