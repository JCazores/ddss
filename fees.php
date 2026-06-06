<?php $page='fees';
include("php/dbconnect.php");
include("php/checklogin.php");

if(isset($_POST['save'])) {
  $sid = mysqli_real_escape_string($conn, $_POST['sid']);
  $paid = mysqli_real_escape_string($conn, $_POST['paid']);
  $submitdate = mysqli_real_escape_string($conn, $_POST['submitdate']);
  $remark = mysqli_real_escape_string($conn, $_POST['transcation_remark']);

  // Fetch current balance, fees, and course
  $sql = "SELECT balance, fees, course FROM student WHERE id='$sid'";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $current_balance = $row['balance'];
      $total_fees = $row['fees'];
      $course = $row['course'];

      // Ensure paid amount is valid
      if ($paid <= $current_balance) {
          $new_balance = $current_balance - $paid;

          // Insert transaction with course info
          $sql_insert = "INSERT INTO fees_transaction (stdid, paid, submitdate, transcation_remark, course) 
                         VALUES ('$sid', '$paid', '$submitdate', '$remark', '$course')";

          if ($conn->query($sql_insert) === TRUE) {
              // Correctly update balance in student table
              $sql_update = "UPDATE student SET balance='$new_balance' WHERE id='$sid'";
              if ($conn->query($sql_update) === TRUE) {
                  header("Location: fees.php?act=1"); // Redirect with success message
                  exit();
              } else {
                  header("Location: fees.php?act=2"); // Error updating balance
                  exit();
              }
          } else {
              header("Location: fees.php?act=3"); // Error inserting payment
              exit();
          }
      } else {
          header("Location: fees.php?act=4"); // Error: Paid amount exceeds balance
          exit();
      }
  } else {
      header("Location: fees.php?act=5"); // Student not found
      exit();
  }
}
?>

<?php
$errormsg = "";
if(isset($_REQUEST['act'])) {
    if($_REQUEST['act'] == "1") {
        $errormsg = "<div class='alert alert-success alert-dismissible fade show'>
                        <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>
                        <i class='fas fa-check-circle me-2'></i>
                        <strong>Success!</strong> Fees has been submitted successfully.
                     </div>";
    } elseif($_REQUEST['act'] == "2") {
        $errormsg = "<div class='alert alert-danger alert-dismissible fade show'>
                        <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>
                        <i class='fas fa-exclamation-circle me-2'></i>
                        <strong>Error!</strong> Could not update student balance.
                     </div>";
    } elseif($_REQUEST['act'] == "3") {
        $errormsg = "<div class='alert alert-danger alert-dismissible fade show'>
                        <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>
                        <i class='fas fa-exclamation-circle me-2'></i>
                        <strong>Error!</strong> Payment transaction failed.
                     </div>";
    } elseif($_REQUEST['act'] == "4") {
        $errormsg = "<div class='alert alert-warning alert-dismissible fade show'>
                        <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>
                        <i class='fas fa-exclamation-triangle me-2'></i>
                        <strong>Warning!</strong> Paid amount exceeds the remaining balance.
                     </div>";
    } elseif($_REQUEST['act'] == "5") {
        $errormsg = "<div class='alert alert-danger alert-dismissible fade show'>
                        <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>
                        <i class='fas fa-user-times me-2'></i>
                        <strong>Error!</strong> Student record not found.
                     </div>";
    }
}

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>School Fees Management System</title>

    <!-- BOOTSTRAP STYLES-->
    <link href="css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="css/font-awesome.css" rel="stylesheet" />
       <!--CUSTOM BASIC STYLES-->
    <link href="css/style1.css" rel="stylesheet" />
    <!--CUSTOM MAIN STYLES-->
    <link href="css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
	
	<link href="css/ui.css" rel="stylesheet" />
	<link href="css/jquery-ui-1.10.3.custom.min.css" rel="stylesheet" />	
	<link href="css/datepicker.css" rel="stylesheet" />	
	   <link href="css/datatable/datatable.css" rel="stylesheet" />
	   
    <script src="js/jquery-1.10.2.js"></script>	
    <script type='text/javascript' src='js/jquery/jquery-ui-1.10.1.custom.min.js'></script>
   <script type="text/javascript" src="js/validation/jquery.validate.min.js"></script>
 
		 <script src="js/dataTable/jquery.dataTables.min.js"></script>
		 <!-- Chart.js for enhanced charts -->
		 <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
		 
	<style>
	/* Enhanced styles for better UI */
	:root {
		--primary-color: rgba(1, 129, 55);
		--primary-hover: rgba(1, 100, 42);
		--shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
		--border-radius: 0.5rem;
	}
	
	body {
		background-color: #f8f9fa;
		font-family: 'Open Sans', sans-serif;
	}
	
	.page-head-line {
		color: var(--primary-color);
		font-weight: 600;
		padding: 1.5rem 0;
		border-bottom: 3px solid var(--primary-color);
		margin-bottom: 2rem;
	}
	
	.scheduler-border {
		background: white;
		padding: 1.5rem;
		border-radius: var(--border-radius);
		box-shadow: var(--shadow);
		margin-bottom: 2rem;
		border: 1px solid #dee2e6;
	}
	
	.scheduler-border legend {
		background: var(--primary-color);
		color: white;
		padding: 0.5rem 1rem;
		border-radius: var(--border-radius);
		border: none;
		font-weight: 600;
		font-size: 0.9rem;
	}
	
	.form-control:focus {
		border-color: var(--primary-color);
		box-shadow: 0 0 0 0.2rem rgba(1, 129, 55, 0.25);
	}
	
	.btn-success {
		background-color: var(--primary-color);
		border-color: var(--primary-color);
	}
	
	.btn-success:hover {
		background-color: var(--primary-hover);
		border-color: var(--primary-hover);
	}
	
	.panel-default {
		border: none;
		box-shadow: var(--shadow);
		border-radius: var(--border-radius);
		overflow: hidden;
	}
	
	.panel-heading {
		background-color: var(--primary-color) !important;
		color: white !important;
		padding: 1rem 1.5rem;
		font-weight: 600;
	}
	
	.panel-body {
		padding: 0;
	}
	
	.table thead tr {
		background-color: var(--primary-color) !important;
		color: white !important;
	}
	
	.table thead th {
		border: none;
		font-weight: 600;
		vertical-align: middle;
	}
	
	.table tbody tr:hover {
		background-color: rgba(1, 129, 55, 0.05);
	}
	
	.modal-header {
		background-color: var(--primary-color);
		color: white;
	}
	
	.modal-header .close {
		color: white;
		opacity: 1;
	}
	
	.modal-header .close:hover {
		color: #f8f9fa;
	}
	
	.alert {
		border-radius: var(--border-radius);
		border: none;
		box-shadow: var(--shadow);
	}
	
	/* New styles for additional features */
	.stats-container {
		margin-bottom: 2rem;
	}
	
	.stats-card {
		background: white;
		padding: 1.5rem;
		border-radius: var(--border-radius);
		box-shadow: var(--shadow);
		text-align: center;
		transition: transform 0.2s ease-in-out;
	}
	
	.stats-card:hover {
		transform: translateY(-2px);
	}
	
	.stats-card .icon {
		font-size: 2.5rem;
		color: var(--primary-color);
		margin-bottom: 0.5rem;
	}
	
	.stats-card h3 {
		color: var(--primary-color);
		font-weight: 700;
		margin-bottom: 0.5rem;
	}
	
	.chart-container {
		background: white;
		padding: 1.5rem;
		border-radius: var(--border-radius);
		box-shadow: var(--shadow);
		margin-bottom: 2rem;
	}
	
	.chart-container h4 {
		color: var(--primary-color);
		font-weight: 600;
		margin-bottom: 1rem;
	}
	
	/* Chart specific styling - Make charts landscape */
	.chart-container canvas {
		max-height: 250px !important; /* Limit the height */
		width: 100% !important;
	}
	
	/* Make the line chart even more landscape-oriented */
	#feesChart {
		max-height: 200px !important; /* Even lower height for the line chart */
	}
	
	#doj .ui-datepicker-calendar {
		display: none;
	}
	
	/* Responsive improvements */
	@media (max-width: 768px) {
		.form-inline .form-group {
			margin-bottom: 1rem;
		}
		
		.btn-group .btn {
			margin-bottom: 0.5rem;
		}
		
		.chart-container canvas {
			max-height: 200px !important; /* Even smaller on mobile */
		}
	}
	
	/* Added loading indicator */
	.loading-overlay {
		display: none;
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(255, 255, 255, 0.7);
		z-index: 999;
		text-align: center;
	}
	
	.loading-spinner {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
	}
	</style>		
</head>
<?php
include("php/header.php");
?>
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h1 class="page-head-line">
						<i class="fa fa-money" style="color: var(--primary-color);"></i>
						Fees Management
						</h1>
                    </div>
                </div>
				
				<!-- Quick Stats Section -->
				<div class="stats-container">
					<div class="row">
						<div class="col-md-4">
							<div class="stats-card">
								<div class="icon">
									<i class="fa fa-money"></i>
								</div>
								<h3 id="totalFeesStats">₱0</h3>
								<p class="text-muted">Total Fees</p>
							</div>
						</div>
						<div class="col-md-4">
							<div class="stats-card">
								<div class="icon">
									<i class="fa fa-check"></i>
								</div>
								<h3 id="paidFeesStats" style="color: #28a745;">₱0</h3>
								<p class="text-muted">Fees Paid</p>
							</div>
						</div>
						<div class="col-md-4">
							<div class="stats-card">
								<div class="icon">
									<i class="fa fa-clock-o"></i>
								</div>
								<h3 id="pendingFeesStats" style="color: #ffc107;">₱0</h3>
								<p class="text-muted">Unpaid</p>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Charts Section -->
				<div class="row" style="margin-bottom: 20px;">
					<div class="col-md-8">
						<div class="chart-container">
							<h4><i class="fa fa-line-chart"></i> Fees Collection Trends</h4>
							<canvas id="feesChart"></canvas>
						</div>
					</div>
					<div class="col-md-4">
						<div class="chart-container">
							<h4><i class="fa fa-pie-chart"></i> Payment Status</h4>
							<canvas id="statusChart"></canvas>
						</div>
					</div>
				</div>
				
    	<?php
		echo $errormsg;
		?>
		
		

<div class="row" style="margin-bottom:20px;">
<div class="col-md-12">
<fieldset class="scheduler-border">
    <legend  class="scheduler-border"><i class="fa fa-search"></i> Search:</legend>
<form class="form-inline" role="form" id="searchform">
  <div class="form-group">
    <label for="email"><i class="fa fa-user"></i> Name</label>
    <input type="text" class="form-control" id="student" name="student" placeholder="Enter student name...">
  </div>
     
   <button type="button" class="btn btn-success btn-sm" style="border-radius:0%" id="find" > 
   <i class="fa fa-filter"></i> Filter 
   </button>
  <button type="reset" class="btn btn-danger btn-sm" style="border-radius:0%" id="clear" > 
  <i class="fa fa-refresh"></i> Reset 
  </button>
</form>
</fieldset>

</div>
</div>

<script type="text/javascript">
$(document).ready( function() {

/*
$('#doj').datepicker( {
        changeMonth: true,
        changeYear: true,
        showButtonPanel: false,
        dateFormat: 'mm/yy',
        onClose: function(dateText, inst) { 
            $(this).datepicker('setDate', new Date(inst.selectedYear, inst.selectedMonth, 1));
        }
    });
	
*/
	
/******************/	
	 $("#doj").datepicker({
         
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        dateFormat: 'mm/yy',
        onClose: function(dateText, inst) {
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).val($.datepicker.formatDate('MM yy', new Date(year, month, 1)));
        }
    });

    $("#doj").focus(function () {
        $(".ui-datepicker-calendar").hide();
        $("#ui-datepicker-div").position({
            my: "center top",
            at: "center bottom",
            of: $(this)
        });
    });

/*****************/
	
$('#student').autocomplete({
		      	source: function( request, response ) {
		      		$.ajax({
		      			url : 'ajx.php',
		      			dataType: "json",
						data: {
						   name_startsWith: request.term,
						   type: 'studentname'
						},
						 success: function( data ) {
						 
							 response( $.map( data, function( item ) {
							
								return {
									label: item,
									value: item
								}
							}));
						}
						
						
						
		      		});
		      	}
				/*,
		      	autoFocus: true,
		      	minLength: 0,
                 select: function( event, ui ) {
						  var abc = ui.item.label.split("-");
						  //alert(abc[0]);
						   $("#student").val(abc[0]);
						   return false;

						  },
                 */
  

						  
		      });
	

$('#find').click(function () {
mydatatable();
        });


$('#clear').click(function () {

$('#searchform')[0].reset();
mydatatable();
        });
		
function mydatatable()
{
        
              $("#subjectresult").html('<table class="table table-striped table-bordered table-hover" id="tSortable22"><thead><tr><th>StudentID</th><th>Name/Contact</th><th>Fees</th><th>Balance</th><th>Grade</th><th>Course</th><th>Attendance</th><th>GPA</th><th>DOJ</th><th>Action</th></tr></thead><tbody></tbody></table>');
			  
			    $("#tSortable22").dataTable({
							      'sPaginationType' : 'full_numbers',
							     "bLengthChange": false,
                  "bFilter": false,
                  "bInfo": false,
							       'bProcessing' : true,
							       'bServerSide': true,
							       'sAjaxSource': "datatable.php?"+$('#searchform').serialize()+"&type=feesearch",
							       'aoColumnDefs': [{
                                   'bSortable': false,
                                   'aTargets': [-1] /* 1st one, start by the right */
                                                }],
                                   "fnDrawCallback": function( oSettings ) {
                                   	refreshDataTableDisplayAndStats();
                                   }
                                   });


}
		
////////////////////////////
 $("#tSortable22").dataTable({
			     
                  'sPaginationType' : 'full_numbers',
				  "bLengthChange": false,
                  "bFilter": false,
                  "bInfo": false,
                  
                  'bProcessing' : true,
				  'bServerSide': true,
                  'sAjaxSource': "datatable.php?type=feesearch",
				  
			      'aoColumnDefs': [{
                  'bSortable': false,
                  'aTargets': [-1] /* 1st one, start by the right */
              }],
              "fnDrawCallback": function( oSettings ) {
              	refreshDataTableDisplayAndStats();
              }
            });

///////////////////////////		

// Initialize charts with empty data
let feesChart, statusChart;
initializeCharts();

// Load statistics immediately
loadStatistics();

});


function GetFeeForm(sid)
{

$.ajax({
            type: 'post',
            url: 'getfeeform.php',
            data: {student:sid,req:'1'},
            success: function (data) {
              $('#formcontent').html(data);
			  $("#myModal").modal({backdrop: "static"});
            }
          });


}

// Initialize charts function with empty data
function initializeCharts() {
    // Fees Collection Trends Chart
    const ctx1 = document.getElementById('feesChart').getContext('2d');
    window.feesChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Fees Collected (₱)',
                data: [],
                borderColor: 'rgba(1, 129, 55, 1)',
                backgroundColor: 'rgba(1, 129, 55, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 6,
                pointBackgroundColor: 'rgba(1, 129, 55, 1)',
                pointBorderColor: 'white',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Important for landscape aspect
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            aspectRatio: 3 // Force a landscape aspect ratio (width:height = 3:1)
        }
    });

    // Payment Status Chart
    const ctx2 = document.getElementById('statusChart').getContext('2d');
    window.statusChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Unpaid'],
            datasets: [{
                data: [0, 0],
                backgroundColor: [
                    '#28a745',
                    '#ffc107'
                ],
                borderWidth: 0,
                cutout: '65%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

// Load statistics function from API
function loadStatistics() {
    // Show loading state
    $("#totalFeesStats, #paidFeesStats, #pendingFeesStats").text('Loading...');
    
    // Fetch real data from server using AJAX
    $.ajax({
        url: 'get_fees_stats.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log("Stats data received:", data);
            
            // Update stats with real data
            if (data.total_fees !== undefined) {
                animateValue('totalFeesStats', 0, data.total_fees, 1000);
                animateValue('paidFeesStats', 0, data.paid_fees, 1000);
                animateValue('pendingFeesStats', 0, data.pending_fees, 1000);
            } else {
                // Fallback to zero if no data
                $("#totalFeesStats").text('₱0');
                $("#paidFeesStats").text('₱0');
                $("#pendingFeesStats").text('₱0');
            }
            
            // Update charts with real data
            if (data.chart_data) {
                updateCharts(data.chart_data);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error loading stats:", error);
            // Set stats to zero on error
            $("#totalFeesStats").text('₱0');
            $("#paidFeesStats").text('₱0');
            $("#pendingFeesStats").text('₱0');
        }
    });
}

// Function to refresh data table display after modal close
function refreshDataTableDisplay() {
    if ($.fn.DataTable.isDataTable('#tSortable22')) {
        $('#tSortable22').DataTable().ajax.reload(null, false);
    }
}

// Function to refresh statistics and charts
function refreshDataTableDisplayAndStats() {
    refreshDataTableDisplay();
    loadStatistics();
}

// Function to handle modal close and refresh
$(document).on('hidden.bs.modal', '#myModal', function () {
    refreshDataTableDisplayAndStats();
});

// Animate counter values
function animateValue(id, start, end, duration) {
    // Validate input to avoid NaN
    end = end || 0;
    
    let current = start;
    const increment = end / (duration / 16);
    const timer = setInterval(function() {
        current += increment;
        if (current >= end) {
            current = end;
            clearInterval(timer);
        }
        document.getElementById(id).textContent = '₱' + Math.floor(current).toLocaleString();
    }, 16);
}

// Function to update charts with real data
function updateCharts(chartData) {
    // Update fees trends chart
    if (window.feesChart && chartData.fees_trends) {
        console.log("Updating fees trend chart with:", chartData.fees_trends);
        
        // Update only if data exists and is valid
        if (chartData.fees_trends.labels && chartData.fees_trends.labels.length > 0 &&
            chartData.fees_trends.data && chartData.fees_trends.data.length > 0) {
            
            window.feesChart.data.labels = chartData.fees_trends.labels;
            window.feesChart.data.datasets[0].data = chartData.fees_trends.data;
            window.feesChart.update();
        }
    }
    
    // Update payment status chart
    if (window.statusChart && chartData.payment_status) {
        console.log("Updating status chart with:", chartData.payment_status);
        
        // Update only if data exists and is valid
        if (chartData.payment_status.length > 0) {
            window.statusChart.data.datasets[0].data = chartData.payment_status;
            window.statusChart.update();
        }
    }
}

</script>


		

<style>
#doj .ui-datepicker-calendar
{
display:none;
}

</style>
		
		<div class="panel panel-default" style="border-color:rgba(1, 129, 55);">
                        <div class="panel-heading" style="background-color:rgba(1, 129, 55); color: white;">
                            <i class="fa fa-table"></i> Manage Fees  
                        </div>
                        <div class="panel-body">
                            <div class="table-sorting table-responsive" id="subjectresult">
                                <table class="table table-striped table-bordered table-hover" id="tSortable22">
                                    <thead>
                                        <tr style="background-color: rgba(1, 129, 55); color: white; font-weight: 100;">
                                            <th>Student ID</th>
                                            <th>Name/Contact</th>                                            
                                            <th>Fees</th>
											<th>Balance</th>
											<th>Grade</th>
                                            <th>Course</th>
                                            <th>Attendance</th>
                                            <th>GPA</th>
											<th>DOJ</th>
											<th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
								    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                     
	
	<!-------->
	
	<!-- Modal -->
  <div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" style="color:red;">&times;</button>
          <h4 class="modal-title"><i class="fa fa-money"></i> Collect Fee</h4>
        </div>
        <div class="modal-body" id="formcontent">
        
        </div>
        
      </div>
    </div>
  </div>

	
    <!--------->
    			
            
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