<?php $page='student';
include("php/dbconnect.php");
include("php/checklogin.php");
$errormsg = '';
$action = "add";

$id="";
$StudentID='';
$emailid='';
$sname='';
$joindate = '';
$remark='';
$contact='';
$balance = 0;
$fees='';
$about = '';
$year='';
$course='';
$Attendance='';
$GPA='';

if(isset($_POST['save']))
{
    $StudentID = isset($_POST['StudentID']) ? mysqli_real_escape_string($conn,$_POST['StudentID']) : '';
    $sname = mysqli_real_escape_string($conn,$_POST['sname']);
    $joindate = mysqli_real_escape_string($conn,$_POST['joindate']);
    $contact = mysqli_real_escape_string($conn,$_POST['contact']);
    $about = mysqli_real_escape_string($conn,$_POST['about']);
    $emailid = mysqli_real_escape_string($conn,$_POST['emailid']);
    $year = mysqli_real_escape_string($conn,$_POST['year']);
    $course = mysqli_real_escape_string($conn,$_POST['course']);
    $Attendance = mysqli_real_escape_string($conn,$_POST['Attendance']);
    $GPA = mysqli_real_escape_string($conn,$_POST['GPA']);

    if($_POST['action']=="add"){
        $StudentID = generateStudentID($conn);
        $remark = mysqli_real_escape_string($conn,$_POST['remark']);
        $fees = mysqli_real_escape_string($conn,$_POST['fees']);
$advancefees = mysqli_real_escape_string($conn,$_POST['advancefees']);
$balance = floatval($fees) - floatval($advancefees);
        
        $q1 = $conn->query("INSERT INTO student (StudentID,sname,joindate,contact,about,emailid,year,course,Attendance,GPA,balance,fees) VALUES ('$StudentID','$sname','$joindate','$contact','$about','$emailid','$year','$course','$Attendance','$GPA','$balance','$fees')");
        $sid = $conn->insert_id;
        
        $conn->query("INSERT INTO fees_transaction (stdid,paid,submitdate,transcation_remark) VALUES ('$sid','$advancefees','$joindate','$remark')");
        echo '<script type="text/javascript">window.location="student.php?act=1";</script>';
    } else if($_POST['action']=="update") {
        $id = mysqli_real_escape_string($conn,$_POST['id']);
        $sql = $conn->query("UPDATE student SET year = '$year', course = '$course', sname = '$sname', contact = '$contact', about = '$about', emailid = '$emailid', Attendance = '$Attendance', GPA = '$GPA' WHERE id = '$id'");
        echo '<script type="text/javascript">window.location="student.php?act=2";</script>';
    }
}

if(isset($_GET['action']) && $_GET['action']=="delete"){
    $conn->query("DELETE FROM student WHERE id='" . $_GET['id'] . "'");
    header("location: student.php?act=3");
}

$action = "add";
if(isset($_GET['action']) && $_GET['action']=="edit"){
    $id = isset($_GET['id'])?mysqli_real_escape_string($conn,$_GET['id']):'';
    $sqlEdit = $conn->query("SELECT * FROM student WHERE id='".$id."'");
    if($sqlEdit->num_rows) {
        $rowsEdit = $sqlEdit->fetch_assoc();
        extract($rowsEdit);
        $action = "update";
    } else {
        $_GET['action']="";
    }
}

if(isset($_REQUEST['act']) && @$_REQUEST['act']=="1") {
    $errormsg = "<div class='alert alert-success'> <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>Student record has been added!</div>";
} else if(isset($_REQUEST['act']) && @$_REQUEST['act']=="2") {
    $errormsg = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>Student record has been updated!</div>";
} else if(isset($_REQUEST['act']) && @$_REQUEST['act']=="3") {
    $errormsg = "<div class='alert alert-success'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>Student has been deleted!</div>";
}

function generateStudentID($conn) {
    $year = date("Y");
    $prefix = "OLFU$year-";
    $result = $conn->query("SELECT StudentID FROM student WHERE StudentID LIKE 'OLFU$year-%' ORDER BY id DESC LIMIT 1");

    if ($result->num_rows > 0) {
        $lastID = $result->fetch_assoc()['StudentID'];
        preg_match('/-(\d+)$/', $lastID, $matches);
        $nextNum = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
    } else {
        $nextNum = 1;
    }

    return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
}

// Get student statistics for dashboard
$totalStudents = $conn->query("SELECT COUNT(*) as total FROM student WHERE delete_status='0'")->fetch_assoc()['total'];
$totalFees = $conn->query("SELECT SUM(fees) as total FROM student WHERE delete_status='0'")->fetch_assoc()['total'];
$totalBalance = $conn->query("SELECT SUM(balance) as total FROM student WHERE delete_status='0'")->fetch_assoc()['total'];
$totalPaid = $totalFees - $totalBalance;

// Get course distribution data
$courseData = [];
$courseQuery = $conn->query("SELECT course, COUNT(*) as count FROM student WHERE delete_status='0' GROUP BY course");
while ($row = $courseQuery->fetch_assoc()) {
    $courseData[] = $row;
}

// Get GPA distribution data
$gpaData = [];
$gpaQuery = $conn->query("SELECT 
                            CASE 
                                WHEN GPA >= 3.5 THEN 'Excellent (3.5-4.0)'
                                WHEN GPA >= 3.0 THEN 'Very Good (3.0-3.49)'
                                WHEN GPA >= 2.5 THEN 'Good (2.5-2.99)'
                                WHEN GPA >= 2.0 THEN 'Satisfactory (2.0-2.49)'
                                ELSE 'Needs Improvement (<2.0)'
                            END as gpa_range,
                            COUNT(*) as count
                          FROM student 
                          WHERE delete_status='0' AND GPA IS NOT NULL
                          GROUP BY gpa_range");
while ($row = $gpaQuery->fetch_assoc()) {
    $gpaData[] = $row;
}

// Get attendance data
$attendanceData = [];
$attendanceQuery = $conn->query("SELECT 
                                CASE 
                                    WHEN Attendance >= 90 THEN 'Excellent (90-100%)'
                                    WHEN Attendance >= 80 THEN 'Good (80-89%)'
                                    WHEN Attendance >= 75 THEN 'Satisfactory (75-79%)'
                                    ELSE 'Below Required (<75%)'
                                END as attendance_range,
                                COUNT(*) as count
                              FROM student 
                              WHERE delete_status='0' AND Attendance IS NOT NULL
                              GROUP BY attendance_range");
while ($row = $attendanceQuery->fetch_assoc()) {
    $attendanceData[] = $row;
}

// For year distribution chart
$yearData = [];
$yearQuery = $conn->query("SELECT y.year, COUNT(s.id) as count 
                           FROM year y
                           LEFT JOIN student s ON y.id = s.year AND s.delete_status = '0'
                           WHERE y.delete_status = '0'
                           GROUP BY y.year
                           ORDER BY y.year");
while ($row = $yearQuery->fetch_assoc()) {
    $yearData[] = $row;
}
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
        
        .btn-danger {
            background-color: var(--danger-color);
        }
        
        .page-head-line {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-control {
            border-radius: 4px;
            border: 1px solid #ddd;
            height: 40px;
            padding: 8px 12px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 8px rgba(1, 129, 55, 0.2);
        }
        
        .table > thead > tr > th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-bottom: 2px solid var(--primary-dark);
        }
        
        .table-hover > tbody > tr:hover {
            background-color: var(--primary-light);
        }
        
        .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: rgba(245, 245, 245, 0.5);
        }
        
        .alert {
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .stat-panel {
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            min-height: 120px;
        }
        
        .stat-panel:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-panel-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .stat-panel-info {
            background-color: var(--info-color);
            color: white;
        }
        
        .stat-panel-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .stat-panel-warning {
            background-color: var(--warning-color);
            color: white;
        }
        
        .stat-panel-number {
            font-size: 32px;
            font-weight: 700;
            display: block;
            margin-bottom: 10px;
        }
        
        .stat-panel-title {
            font-size: 16px;
            font-weight: 600;
            opacity: 0.8;
        }
        
        .chart-container {
            position: relative;
            margin: auto;
            height: 250px;
            width: 100%;
        }
        
        /* Responsive fixes */
        @media (max-width: 768px) {
            .stat-panel {
                margin-bottom: 15px;
            }
            
            .chart-container {
                height: 200px;
            }
        }
        
        /* Custom animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
<?php include("php/header.php"); ?>
    <div id="page-wrapper">
        <div id="page-inner">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="page-head-line">Student Management System
                        <?php
                        echo (isset($_GET['action']) && @$_GET['action']=="add" || @$_GET['action']=="edit")?
                        ' <a href="student.php" class="btn btn-success btn-sm pull-right"><i class="fa fa-arrow-left"></i> Go Back </a>':'<a href="student.php?action=add" class="btn btn-success btn-sm pull-right"><i class="fa fa-plus"></i> Add New Student</a>';
                        ?>
                    </h1>
                    <?php echo $errormsg; ?>
                </div>
            </div>
            
            <?php 
            if(isset($_GET['action']) && @$_GET['action']=="add" || @$_GET['action']=="edit") {
            ?>
            
            <script type="text/javascript" src="js/validation/jquery.validate.min.js"></script>
            <div class="row">
                <div class="col-sm-10 col-sm-offset-1">
                    <div class="panel panel-success animate__animated animate__fadeIn">
                        <div class="panel-heading">
                           <?php echo ($action=="add")? "Add Student Details": "Edit Student Details"; ?>
                        </div>
                        <form action="student.php" method="post" id="signupForm1" class="form-horizontal">
                            <div class="panel-body">
                                <ul class="nav nav-tabs">
                                    <li class="active"><a data-toggle="tab" href="#personal">Personal Information</a></li>
                                    <li><a data-toggle="tab" href="#academic">Academic Information</a></li>
                                    <li><a data-toggle="tab" href="#financial">Financial Information</a></li>
                                    <li><a data-toggle="tab" href="#optional">Additional Information</a></li>
                                </ul>
                                
                                <div class="tab-content" style="padding-top: 20px;">
                                    <div id="personal" class="tab-pane fade in active">
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label" for="sname">Full Name <span class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="sname" name="sname" value="<?php echo $sname;?>" placeholder="Enter student's full name" />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label" for="contact">Contact Number <span class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="contact" name="contact" value="<?php echo $contact;?>" maxlength="10" placeholder="Enter contact number" />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label">Student ID</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" value="<?php echo generateStudentID($conn); ?>" readonly disabled placeholder="Auto-generated ID" />
                                                <small class="text-muted">ID will be automatically generated upon saving</small>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label" for="joindate">Date of Joining <span class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" placeholder="YYYY-MM-DD" id="joindate" name="joindate" value="<?php echo ($joindate!='')?date("Y-m-d", strtotime($joindate)):'';?>" style="background-color: #fff;" readonly />
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label" for="emailid">Email Address</label>
                                            <div class="col-sm-9">
                                                <input type="email" class="form-control" id="emailid" name="emailid" value="<?php echo $emailid;?>" placeholder="Enter email address" />
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="academic" class="tab-pane fade">
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label" for="year">Year Level <span class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select class="form-control" id="year" name="year">
                                                    <option value="">Select Year Level</option>
                                                    <?php
                                                    $sql = "select * from year where delete_status='0' order by year.year asc";
                                                    $q = $conn->query($sql);
                                                    
                                                    while($r = $q->fetch_assoc()) {
                                                        echo '<option value="'.$r['id'].'" '.(($year==$r['id'])?'selected="selected"':'').'>'.$r['year'].'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label" for="course">Course <span class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select class="form-control" id="course" name="course">
                                                    <option value="">Select Course</option>
                                                    <?php
                                                    $courseQuery = $conn->query("SELECT * FROM course");
                                                    while ($row = $courseQuery->fetch_assoc()) {
                                                        $selected = ($row['course_name'] == $course) ? "selected" : "";
                                                        echo "<option value='{$row['course_name']}' $selected>{$row['course_name']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                    </div>
                                    
                                    <div id="financial" class="tab-pane fade">
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label" for="fees">Total Tuition Fee <span class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <div class="input-group">
                                                    <span class="input-group-addon">₱</span>
                                                    <input type="text" class="form-control" id="fees" name="fees" value="<?php echo $fees;?>" <?php echo ($action=="update")?"disabled":""; ?> placeholder="Enter total fees" />
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php
                                        if($action=="add") {
                                        ?>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label" for="advancefees">Advance Payment <span class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <div class="input-group">
                                                    <span class="input-group-addon">₱</span>
                                                    <input type="text" class="form-control" id="advancefees" name="advancefees" readonly placeholder="Enter advance payment" />
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        }
                                        ?>

                                        <div class="form-group">
                                            <label class="col-sm-3 control-label" for="balance">Balance</label>
                                            <div class="col-sm-9">
                                                <div class="input-group">
                                                    <span class="input-group-addon">₱</span>
                                                    <input type="text" class="form-control" id="balance" name="balance" value="<?php echo $balance;?>" disabled placeholder="Remaining balance" />
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php
                                        if($action=="add") {
                                        ?>
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label" for="remark">Payment Remarks</label>
                                            <div class="col-sm-9">
                                                <textarea class="form-control" id="remark" name="remark" rows="3" placeholder="Enter any remarks about payment"><?php echo $remark;?></textarea>
                                            </div>
                                        </div>
                                        <?php
                                        }
                                        ?>
                                    </div>
                                    
                                    <div id="optional" class="tab-pane fade">
                                        <div class="form-group">
                                            <label class="col-sm-3 control-label" for="about">About Student</label>
                                            <div class="col-sm-9">
                                                <textarea class="form-control" id="about" name="about" rows="5" placeholder="Enter additional information about the student"><?php echo $about;?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                <div class="form-group">
                                    <div class="col-sm-9 col-sm-offset-3">
                                        <input type="hidden" name="id" value="<?php echo $id;?>">
                                        <input type="hidden" name="action" value="<?php echo $action;?>">
                                        <button type="submit" name="save" class="btn btn-success"><i class="fa fa-save"></i> Save Student</button>
                                        <a href="student.php" class="btn btn-default"><i class="fa fa-times"></i> Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script type="text/javascript">
                $(document).ready(function() {
                    $("#joindate").datepicker({
                        dateFormat: "yy-mm-dd",
                        changeMonth: true,
                        changeYear: true,
                        yearRange: "1970:<?php echo date('Y');?>"
                    });
                    
                    if($("#signupForm1").length > 0) {
                        <?php if($action=='add') { ?>
                            $("#signupForm1").validate({
                                rules: {
                                    sname: "required",
                                    joindate: "required",
                                    emailid: "email",
                                    year: "required",
                                    course: "required",
                                    contact: {
                                        required: true,
                                        digits: true
                                    },
                                    fees: {
                                        required: true,
                                        digits: true
                                    },
                                    advancefees: {
                                        required: true,
                                        digits: true
                                    },
                                    Attendance: {
                                        range: [0, 100],
                                        digits: true
                                    },
                                    GPA: {
                                        range: [0, 4]
                                    }
                                },
                                messages: {
                                    sname: "Please enter student name",
                                    joindate: "Please select date of joining",
                                    year: "Please select year level",
                                    course: "Please select a course",
                                    contact: {
                                        required: "Please enter contact number",
                                        digits: "Only numbers allowed"
                                    },
                                    fees: {
                                        required: "Please enter total fees",
                                        digits: "Only numbers allowed"
                                    },
                                    advancefees: {
                                        required: "Please enter advance payment",
                                        digits: "Only numbers allowed"
                                    }
                                },
                        <?php } else { ?>
                            $("#signupForm1").validate({
                                rules: {
                                    sname: "required",
                                    joindate: "required",
                                    emailid: "email",
                                    year: "required",
                                    course: "required",
                                    contact: {
                                        required: true,
                                        digits: true
                                    },
                                    Attendance: {
                                        range: [0, 100],
                                        digits: true
                                    },
                                    GPA: {
                                        range: [0, 4]
                                    }
                                },
                                messages: {
                                    sname: "Please enter student name",
                                    joindate: "Please select date of joining",
                                    year: "Please select year level",
                                    course: "Please select a course",
                                    contact: {
                                        required: "Please enter contact number",
                                        digits: "Only numbers allowed"
                                    }
                                },
                        <?php } ?>
                        
                                errorElement: "em",
                                errorPlacement: function(error, element) {
                                    // Add the `help-block` class to the error element
                                    error.addClass("help-block");

                                    // Add `has-feedback` class to the parent div.form-group
                                    // in order to add icons to inputs
                                    element.parents(".col-sm-9").addClass("has-feedback");

                                    if (element.prop("type") === "checkbox") {
                                        error.insertAfter(element.parent("label"));
                                    } else {
                                        error.insertAfter(element);
                                    }

                                    // Add the span element, if doesn't exists, and apply the icon classes to it.
                                    if (!element.next("span")[0]) {
                                        $("<span class='glyphicon glyphicon-remove form-control-feedback'></span>").insertAfter(element);
                                    }
                                },
                                success: function(label, element) {
                                    // Add the span element, if doesn't exists, and apply the icon classes to it.
                                    if (!$(element).next("span")[0]) {
                                        $("<span class='glyphicon glyphicon-ok form-control-feedback'></span>").insertAfter($(element));
                                    }
                                },
                                highlight: function(element, errorClass, validClass) {
                                    $(element).parents(".col-sm-9").addClass("has-error").removeClass("has-success");
                                    $(element).next("span").addClass("glyphicon-remove").removeClass("glyphicon-ok");
                                },
                                unhighlight: function(element, errorClass, validClass) {
                                    $(element).parents(".col-sm-9").addClass("has-success").removeClass("has-error");
                                    $(element).next("span").addClass("glyphicon-ok").removeClass("glyphicon-remove");
                                }
                            });
                    }
                });
			
                // Handle fees calculation
                $("#fees").keyup(function() {
                    $("#advancefees").val("");
                    $("#balance").val(0);
                    var fee = $.trim($(this).val());
                    if (fee != '' && !isNaN(fee)) {
                        $("#advancefees").removeAttr("readonly");
                        $("#balance").val(fee);
                        $('#advancefees').rules("add", {
                            max: parseInt(fee)
                        });
                    } else {
                        $("#advancefees").attr("readonly", "readonly");
                    }
                });

                $("#advancefees").keyup(function() {
                    var advancefees = parseInt($.trim($(this).val()));
                    var totalfee = parseInt($("#fees").val());
                    if (advancefees != '' && !isNaN(advancefees) && advancefees <= totalfee) {
                        var balance = totalfee - advancefees;
                        $("#balance").val(balance);
                    } else {
                        $("#balance").val(totalfee);
                    }
                });
            </script>

            <?php
            } else {
            ?>
            
            <!-- DASHBOARD VIEW -->
            <div class="row animate__animated animate__fadeIn">
                <div class="col-md-12">
                    <div class="stat-panel stat-panel-primary">
                        <span class="stat-panel-number"><?php echo $totalStudents; ?></span>
                        <span class="stat-panel-title">Total Students</span>
                    </div>
                </div>
                
            </div>
            
            <!-- CHARTS ROW -->
            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading" style="background-color: var(--primary-color); color: white;">
                            <i class="fa fa-pie-chart"></i> Students by Course
                        </div>
                        <div class="panel-body">
                            <div class="chart-container">
                                <canvas id="courseChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading" style="background-color: var(--primary-color); color: white;">
                            <i class="fa fa-bar-chart"></i> Students by Year Level
                        </div>
                        <div class="panel-body">
                            <div class="chart-container">
                                <canvas id="yearChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            
            
            <!-- DATA TABLE SECTION -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading" style="background-color: var(--primary-color); color: white;">
                            <i class="fa fa-users"></i> Student Records
                            <div class="pull-right">
                                <a href="download_student.php" class="btn btn-sm btn-default">
                                    <i class="fa fa-download"></i> Export Data
                                </a>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="studentTable">
                                    <thead>
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="12%">Student ID</th>
                                            <th width="20%">Name & Contact</th>
                                            <th width="8%">Year</th>
                                            <th width="15%">Course</th>
                                            <th width="8%">Attendance</th>
                                            <th width="7%">GPA</th>
                                            <th width="10%">Tuition</th>
                                            <th width="10%">Balance</th>
                                            <th width="10%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $sql = "SELECT s.*, y.year as yearName FROM student s 
                                            LEFT JOIN year y ON s.year = y.id 
                                            WHERE s.delete_status='0'";
                                    $q = $conn->query($sql);
                                    $i=1;
                                    while($r = $q->fetch_assoc()) {
                                        // Calculate the balance percentage for progress bar
                                        $balancePercent = 0;
                                        if($r['fees'] > 0) {
                                            $balancePercent = 100 - round(($r['balance'] / $r['fees']) * 100);
                                        }
                                        
                                        // Determine card style based on balance
                                        $cardClass = "success";
                                        if($r['balance'] > 0) {
                                            $cardClass = ($r['balance'] >= $r['fees']/2) ? "danger" : "warning";
                                        }
                                        
                                        // Format attendance and GPA display
                                        $attendanceDisplay = (!empty($r['Attendance'])) ? $r['Attendance'] . '%' : 'N/A';
                                        $gpaDisplay = (!empty($r['GPA'])) ? number_format($r['GPA'], 2) : 'N/A';
                                        
                                        echo '<tr>
                                            <td>'.$i.'</td>
                                            <td>'.$r['StudentID'].'</td>
                                            <td>
                                                <strong>'.$r['sname'].'</strong><br/>
                                                <small class="text-muted"><i class="fa fa-phone"></i> '.$r['contact'].'</small>
                                                '.(!empty($r['emailid']) ? '<br/><small class="text-muted"><i class="fa fa-envelope"></i> '.$r['emailid'].'</small>' : '').'
                                            </td>
                                            <td>'.$r['yearName'].'</td>
                                            <td>'.$r['course'].'</td>
                                            <td>'.$attendanceDisplay.'</td>
                                            <td>'.$gpaDisplay.'</td>
                                            <td>₱'.number_format($r['fees']).'</td>
                                            <td>
                                                <div class="progress" style="margin-bottom:5px; height:5px;">
                                                    <div class="progress-bar progress-bar-'.$cardClass.'" role="progressbar" aria-valuenow="'.$balancePercent.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$balancePercent.'%"></div>
                                                </div>
                                                ₱'.number_format($r['balance']).'
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="student.php?action=edit&id='.$r['id'].'" class="btn btn-sm btn-primary" title="Edit"><i class="fa fa-edit"></i></a>
                                                    <a href="view_student.php?id='.$r['id'].'" class="btn btn-sm btn-info" title="View Details"><i class="fa fa-eye"></i></a>
                                                    <a onclick="return confirm(\'Are you sure you want to delete this student?\');" href="student.php?action=delete&id='.$r['id'].'" class="btn btn-sm btn-danger" title="Delete"><i class="fa fa-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>';
                                        $i++;
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script src="js/dataTable/jquery.dataTables.min.js"></script>
            <script src="js/dataTable/dataTables.bootstrap.min.js"></script>
            <script>
                $(document).ready(function() {
                    // Initialize DataTable
                    $('#studentTable').dataTable({
                        "bPaginate": true,
                        "bLengthChange": true,
                        "bFilter": true,
                        "bInfo": true,
                        "bAutoWidth": false,
                        "responsive": true,
                        "ordering": true,
                        "order": [[0, "asc"]],
                        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                        "language": {
                            "search": "Search Records:",
                            "lengthMenu": "Show _MENU_ entries",
                            "info": "Showing _START_ to _END_ of _TOTAL_ students"
                        }
                    });
                    
                    // Course Distribution Chart
                    var courseData = <?php echo json_encode($courseData); ?>;
                    var courseCtx = document.getElementById('courseChart').getContext('2d');
                    var courseLabels = courseData.map(item => item.course);
                    var courseCounts = courseData.map(item => item.count);
                    var courseColors = generateRandomColors(courseLabels.length);
                    
                    var courseChart = new Chart(courseCtx, {
                        type: 'pie',
                        data: {
                            labels: courseLabels,
                            datasets: [{
                                data: courseCounts,
                                backgroundColor: courseColors,
                                borderColor: 'white',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right'
                                },
                                title: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            var label = context.label || '';
                                            var value = context.raw || 0;
                                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            var percentage = Math.round((value / total) * 100);
                                            return `${label}: ${value} students (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                    
                    // Year Level Distribution Chart
                    var yearData = <?php echo json_encode($yearData); ?>;
                    var yearCtx = document.getElementById('yearChart').getContext('2d');
                    var yearLabels = yearData.map(item => item.year);
                    var yearCounts = yearData.map(item => item.count);
                    
                    var yearChart = new Chart(yearCtx, {
                        type: 'bar',
                        data: {
                            labels: yearLabels,
                            datasets: [{
                                label: 'Number of Students',
                                data: yearCounts,
                                backgroundColor: 'rgba(1, 129, 55, 0.7)',
                                borderColor: 'rgba(1, 129, 55, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                    
                    // GPA Distribution Chart
                    var gpaData = <?php echo json_encode($gpaData); ?>;
                    var gpaCtx = document.getElementById('gpaChart').getContext('2d');
                    var gpaLabels = gpaData.map(item => item.gpa_range);
                    var gpaCounts = gpaData.map(item => item.count);
                    var gpaColors = [
                        'rgba(92, 184, 92, 0.7)',    // Excellent
                        'rgba(91, 192, 222, 0.7)',   // Very Good
                        'rgba(240, 173, 78, 0.7)',   // Good
                        'rgba(217, 83, 79, 0.7)',    // Satisfactory
                        'rgba(119, 119, 119, 0.7)'   // Needs Improvement
                    ];
                    
                    var gpaChart = new Chart(gpaCtx, {
                        type: 'horizontalBar',
                        data: {
                            labels: gpaLabels,
                            datasets: [{
                                label: 'Number of Students',
                                data: gpaCounts,
                                backgroundColor: gpaColors,
                                borderColor: gpaColors.map(color => color.replace('0.7', '1')),
                                borderWidth: 1
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                    
                    // Attendance Distribution Chart
                    var attendanceData = <?php echo json_encode($attendanceData); ?>;
                    var attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
                    var attendanceLabels = attendanceData.map(item => item.attendance_range);
                    var attendanceCounts = attendanceData.map(item => item.count);
                    var attendanceColors = [
                        'rgba(92, 184, 92, 0.7)',    // Excellent
                        'rgba(91, 192, 222, 0.7)',   // Good
                        'rgba(240, 173, 78, 0.7)',   // Satisfactory
                        'rgba(217, 83, 79, 0.7)'     // Below Required
                    ];
                    
                    var attendanceChart = new Chart(attendanceCtx, {
                        type: 'doughnut',
                        data: {
                            labels: attendanceLabels,
                            datasets: [{
                                data: attendanceCounts,
                                backgroundColor: attendanceColors,
                                borderColor: attendanceColors.map(color => color.replace('0.7', '1')),
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right'
                                },
                                title: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            var label = context.label || '';
                                            var value = context.raw || 0;
                                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            var percentage = Math.round((value / total) * 100);
                                            return `${label}: ${value} students (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                    
                    // Utility function to generate random colors
                    function generateRandomColors(count) {
                        var predefinedColors = [
                            'rgba(1, 129, 55, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(199, 199, 199, 0.7)'
                        ];
                        
                        // If we have enough predefined colors, use them
                        if (count <= predefinedColors.length) {
                            return predefinedColors.slice(0, count);
                        }
                        
                        // Otherwise generate additional random colors
                        var colors = [...predefinedColors];
                        for (var i = predefinedColors.length; i < count; i++) {
                            var r = Math.floor(Math.random() * 255);
                            var g = Math.floor(Math.random() * 255);
                            var b = Math.floor(Math.random() * 255);
                            colors.push(`rgba(${r}, ${g}, ${b}, 0.7)`);
                        }
                        
                        return colors;
                    }
                });
            </script>
            
            <?php
            }
            ?>
            
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

</body>
</html>