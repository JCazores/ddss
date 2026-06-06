<?php $page='course';
include("php/dbconnect.php");
include("php/checklogin.php");
$errormsg = '';
$action = "add";

$course_name='';
$description = '';
$id= '';
if(isset($_POST['save']))
{
    $course_name = mysqli_real_escape_string($conn,$_POST['course_name']);
    $description = mysqli_real_escape_string($conn,$_POST['description']);

    if($_POST['action']=="add")
    {
        $sql = $conn->query("INSERT INTO course (course_name, description) VALUES ('$course_name','$description')");
        echo '<script type="text/javascript">window.location="grade.php?act=1";</script>';
    }
    else if($_POST['action']=="update")
    {
        $id = mysqli_real_escape_string($conn,$_POST['id']);	
        $sql = $conn->query("UPDATE course SET course_name = '$course_name', description = '$description' WHERE id = '$id'");
        echo '<script type="text/javascript">window.location="grade.php?act=2";</script>';
    }
}

if(isset($_GET['action']) && $_GET['action']=="delete"){
    // Check if delete_status column exists before using it
    $check_column = $conn->query("SHOW COLUMNS FROM course LIKE 'delete_status'");
    if($check_column->num_rows > 0) {
        $conn->query("UPDATE course SET delete_status = '1' WHERE id='".$_GET['id']."'");
    } else {
        // If no delete_status column, use a hard delete
        $conn->query("DELETE FROM course WHERE id='".$_GET['id']."'");
    }
    header("location: grade.php?act=3");
}

$action = "add";
if(isset($_GET['action']) && $_GET['action']=="edit" ){
    $id = isset($_GET['id'])?mysqli_real_escape_string($conn,$_GET['id']):'';

    $sqlEdit = $conn->query("SELECT * FROM course WHERE id='".$id."'");
    if($sqlEdit->num_rows)
    {
        $rowsEdit = $sqlEdit->fetch_assoc();
        extract($rowsEdit);
        $action = "update";
    }
    else
    {
        $_GET['action']="";
    }
}

if(isset($_REQUEST['act']) && @$_REQUEST['act']=="1")
{
    $errormsg = "<div class='alert alert-success'><i class='fa fa-check-circle'></i> Course has been added successfully</div>";
}
else if(isset($_REQUEST['act']) && @$_REQUEST['act']=="2")
{
    $errormsg = "<div class='alert alert-success'><i class='fa fa-check-circle'></i> Course has been updated successfully</div>";
}
else if(isset($_REQUEST['act']) && @$_REQUEST['act']=="3")
{
    $errormsg = "<div class='alert alert-success'><i class='fa fa-check-circle'></i> Course has been deleted successfully</div>";
}

// Get course statistics for chart
$courseStats = array();
$courseLabels = array();
$courseData = array();

// Check if delete_status column exists before using it
$check_column = $conn->query("SHOW COLUMNS FROM course LIKE 'delete_status'");
            
// If delete_status column exists, use it in the query
if($check_column->num_rows > 0) {
    $sql = "SELECT course_name FROM course WHERE delete_status='0'";
} else {
    // If no delete_status column, fetch all records
    $sql = "SELECT course_name FROM course";
}

$courseQuery = $conn->query($sql);
while($row = $courseQuery->fetch_assoc()) {
    if(isset($courseStats[$row['course_name']])) {
        $courseStats[$row['course_name']]++;
    } else {
        $courseStats[$row['course_name']] = 1;
    }
}

// Prepare data for chart
foreach($courseStats as $name => $count) {
    $courseLabels[] = $name;
    $courseData[] = $count;
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Course Management - School Fees Management System</title>

    <!-- BOOTSTRAP STYLES-->
    <link href="css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="css/font-awesome.css" rel="stylesheet" />
    <!--CUSTOM BASIC STYLES-->
    <link href="css/style1.css" rel="stylesheet" />
    <!--CUSTOM MAIN STYLES-->
    <link href="css/custom.css" rel="stylesheet" />

    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,600,700' rel='stylesheet' type='text/css' />
	
    <script src="js/jquery-1.10.2.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    
    <style>
        body {
            background-color: #f5f5f5;
            font-family: 'Open Sans', sans-serif;
        }
        .page-head-line {
            font-weight: 600;
            padding-bottom: 15px;
            border-bottom: 2px solid #018137;
            margin-bottom: 20px;
            color: #333;
        }
        .btn-action {
            border-radius: 4px;
            margin-right: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }
        .primary-btn {
            background-color: #018137;
            color: white;
            border: none;
            text-transform: uppercase;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 8px 16px;
        }
        .primary-btn:hover {
            background-color: #016a2e;
            color: white;
        }
        .panel {
            border-radius: 6px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            border: none;
        }
        .panel-heading {
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
            font-weight: 600;
            font-size: 16px;
        }
        .form-control {
            border-radius: 4px;
            border: 1px solid #ddd;
            box-shadow: none;
            padding: 10px 12px;
            height: auto;
        }
        .form-control:focus {
            border-color: #018137;
            box-shadow: 0 0 5px rgba(1, 129, 55, 0.3);
        }
        .dashboard-cards {
            margin-bottom: 30px;
        }
        .card {
            background: #018137;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .card-icon {
            font-size: 32px;
            color: white;
            margin-bottom: 10px;
        }
        .card-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
        }
        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }
        .table-header {
            background-color: #018137;
            color: white;
            font-weight: 600;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(1, 129, 55, 0.05);
        }
        .alert {
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .chart-container {
            margin-top: 20px;
            margin-bottom: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .chart-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
            text-align: center;
        }
        .dataTables_filter input {
            border-radius: 4px;
            border: 1px solid #ddd;
            padding: 6px 10px;
            margin-left: 5px;
        }
        .dataTables_length select {
            border-radius: 4px;
            border: 1px solid #ddd;
            padding: 6px 10px;
        }
        .action-btn-success {
            background-color: #28a745;
            color: white;
        }
        .action-btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            margin-right: 5px;
        }
        .has-error .form-control {
            border-color: #dc3545;
        }
        .has-success .form-control {
            border-color: #28a745;
        }
        .help-block {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
<?php include("php/header.php"); ?>
    <div id="page-wrapper">
        <div id="page-inner" class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="page-head-line">Course Management 
                    <?php
                    echo (isset($_GET['action']) && @$_GET['action']=="add" || @$_GET['action']=="edit")?
                    ' <a href="grade.php" class="btn primary-btn pull-right"><i class="fa fa-arrow-left"></i> Back to Courses</a>':'<a href="grade.php?action=add" class="btn primary-btn pull-right"><i class="fa fa-plus"></i> Add New Course</a>';
                    ?>
                    </h1>
                 
                    <?php echo $errormsg; ?>
                </div>
            </div>
            
            <?php 
            if(isset($_GET['action']) && @$_GET['action']=="add" || @$_GET['action']=="edit")
            {
            ?>
            
                <script type="text/javascript" src="js/validation/jquery.validate.min.js"></script>
                <div class="row">
                    <div class="col-md-8 col-md-offset-2">
                        <div class="panel panel-default">
                            <div class="panel-heading" style="background-color: #018137; color: white;">
                                <i class="fa fa-<?php echo ($action=="add")? "plus-circle": "edit"; ?>"></i> <?php echo ($action=="add")? "Add New Course": "Edit Course"; ?>
                            </div>
                            <form action="grade.php" method="post" id="signupForm1" class="form-horizontal">
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label" for="course_name">Course Name</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="course_name" name="course_name" value="<?php echo $course_name;?>" placeholder="Enter course name" />
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label" for="description">Description</label>
                                        <div class="col-sm-9">
                                            <textarea class="form-control" name="description" id="description" rows="5" placeholder="Enter course description"><?php echo $description;?></textarea>
                                        </div>
                                    </div>
                                
                                    <div class="form-group">
                                        <div class="col-sm-9 col-sm-offset-3">
                                            <input type="hidden" name="id" value="<?php echo $id;?>">
                                            <input type="hidden" name="action" value="<?php echo $action;?>">
                                            
                                            <button type="submit" name="save" class="btn primary-btn"><i class="fa fa-save"></i> Save Course</button>
                                            <a href="grade.php" class="btn btn-default">Cancel</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
               
                <script type="text/javascript">
                $( document ).ready( function () {			
                    if($("#signupForm1").length > 0) {
                        $( "#signupForm1" ).validate( {
                            rules: {
                                course_name: "required"
                            },
                            messages: {
                                course_name: "Please enter course name"
                            },
                            errorElement: "em",
                            errorPlacement: function ( error, element ) {
                                error.addClass( "help-block" );
                                element.parents( ".col-sm-9" ).addClass( "has-feedback" );

                                if ( element.prop( "type" ) === "checkbox" ) {
                                    error.insertAfter( element.parent( "label" ) );
                                } else {
                                    error.insertAfter( element );
                                }

                                if ( !element.next( "span" )[ 0 ] ) {
                                    $( "<span class='glyphicon glyphicon-remove form-control-feedback'></span>" ).insertAfter( element );
                                }
                            },
                            success: function ( label, element ) {
                                if ( !$( element ).next( "span" )[ 0 ] ) {
                                    $( "<span class='glyphicon glyphicon-ok form-control-feedback'></span>" ).insertAfter( $( element ) );
                                }
                            },
                            highlight: function ( element, errorClass, validClass ) {
                                $( element ).parents( ".col-sm-9" ).addClass( "has-error" ).removeClass( "has-success" );
                                $( element ).next( "span" ).addClass( "glyphicon-remove" ).removeClass( "glyphicon-ok" );
                            },
                            unhighlight: function ( element, errorClass, validClass ) {
                                $( element ).parents( ".col-sm-9" ).addClass( "has-success" ).removeClass( "has-error" );
                                $( element ).next( "span" ).addClass( "glyphicon-ok" ).removeClass( "glyphicon-remove" );
                            }
                        } );
                    }
                } );
                </script>
            <?php
            }
            else
            {
            ?>
                <!-- Dashboard Overview Cards -->
                <div class="row dashboard-cards">
                    <?php
                    // Check if delete_status column exists before using it
                    $check_column = $conn->query("SHOW COLUMNS FROM course LIKE 'delete_status'");
                    
                    // Count total courses
                    if($check_column->num_rows > 0) {
                        $total_courses = $conn->query("SELECT COUNT(*) as total FROM course WHERE delete_status='0'")->fetch_assoc()['total'];
                    } else {
                        $total_courses = $conn->query("SELECT COUNT(*) as total FROM course")->fetch_assoc()['total'];
                    }
                    
                    // Count courses created in the last month
                    $last_month = date('Y-m-d H:i:s', strtotime('-30 days'));
                    if($check_column->num_rows > 0) {
                        $recent_courses = $conn->query("SELECT COUNT(*) as total FROM course WHERE delete_status='0' AND created_at >= '$last_month'")->fetch_assoc()['total'];
                    } else {
                        $recent_courses = $conn->query("SELECT COUNT(*) as total FROM course WHERE created_at >= '$last_month'")->fetch_assoc()['total'];
                    }
                    ?>
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-icon"><i class="fa fa-book"></i></div>
                            <div class="card-title">Total Courses</div>
                            <div class="card-value"><?php echo $total_courses; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Course Chart -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">Course Distribution</div>
                            <canvas id="courseChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
                
                <link href="css/datatable/datatable.css" rel="stylesheet" />
         
                <div class="panel panel-default">
                    <div class="panel-heading table-header" style="background-color: #018137; color: white;">
                        <i class="fa fa-table"></i> Course List
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="coursesTable">
                                <thead>
                                    <tr style="background-color: #018137; color: white;">
                                        <th>#</th>
                                        <th>Course Name</th>
                                        <th>Description</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                // Check if delete_status column exists before using it
                                $check_column = $conn->query("SHOW COLUMNS FROM course LIKE 'delete_status'");
                                
                                // If delete_status column exists, use it in the query
                                if($check_column->num_rows > 0) {
                                    $sql = "SELECT * FROM course WHERE delete_status='0'";
                                } else {
                                    // If no delete_status column, fetch all records
                                    $sql = "SELECT * FROM course";
                                }
                                
                                $q = $conn->query($sql);
                                $i=1;
                                while($r = $q->fetch_assoc())
                                {
                                    echo '<tr>
                                        <td>'.$i.'</td>
                                        <td>'.$r['course_name'].'</td>
                                        <td>'.substr($r['description'], 0, 100).(strlen($r['description']) > 100 ? '...' : '').'</td>
                                        <td>'.date('Y-m-d H:i', strtotime($r['created_at'])).'</td>
                                        <td>
                                        <a href="grade.php?action=edit&id='.$r['id'].'" class="action-btn action-btn-success" title="Edit"><i class="fa fa-edit"></i></a>
                                        
                                        <a onclick="return confirm(\'Are you sure you want to delete this course?\');" href="grade.php?action=delete&id='.$r['id'].'" class="action-btn action-btn-danger" title="Delete"><i class="fa fa-trash-o"></i></a> 
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
                     
                <script src="js/dataTable/jquery.dataTables.min.js"></script>
                <script>
                    $(document).ready(function () {
                        $('#coursesTable').dataTable({
                            "bPaginate": true,
                            "bLengthChange": true,
                            "bFilter": true,
                            "bInfo": true,
                            "bAutoWidth": false,
                            "pageLength": 10,
                            "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                            "language": {
                                "lengthMenu": "Show _MENU_ courses per page",
                                "zeroRecords": "No courses found",
                                "info": "Showing page _PAGE_ of _PAGES_",
                                "infoEmpty": "No courses available",
                                "infoFiltered": "(filtered from _MAX_ total courses)"
                            }
                        });

                        // Course Chart
                        var ctx = document.getElementById('courseChart').getContext('2d');
                        var courseChart = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: <?php echo json_encode($courseLabels); ?>,
                                datasets: [{
                                    data: <?php echo json_encode($courseData); ?>,
                                    backgroundColor: [
                                        '#018137',
                                        '#2ecc71',
                                        '#3498db',
                                        '#9b59b6',
                                        '#e74c3c',
                                        '#f39c12',
                                        '#1abc9c',
                                        '#34495e'
                                    ],
                                    borderWidth: 2,
                                    borderColor: '#f5f5f5'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            padding: 10,
                                            boxWidth: 12
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                                        titleColor: '#333',
                                        bodyColor: '#666',
                                        borderColor: '#ddd',
                                        borderWidth: 1,
                                        padding: 15,
                                        displayColors: true,
                                        callbacks: {
                                            title: function(tooltipItem) {
                                                return tooltipItem[0].label;
                                            },
                                            label: function(context) {
                                                return ' Count: ' + context.raw;
                                            }
                                        }
                                    }
                                },
                                cutout: '60%',
                                animation: {
                                    animateScale: true,
                                    animateRotate: true
                                }
                            }
                        });
                    });
                </script>
            <?php
            }
            ?>
        </div>
        <!-- /. PAGE INNER  -->
    </div>
    <!-- /. PAGE WRAPPER  -->

    <!-- BOOTSTRAP SCRIPTS -->
    <script src="js/bootstrap.js"></script>
    <!-- METISMENU SCRIPTS -->
    <script src="js/jquery.metisMenu.js"></script>
    <!-- CUSTOM SCRIPTS -->
    <script src="js/custom1.js"></script>
</body>
</html>