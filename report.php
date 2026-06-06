<?php $page='report';
include("php/dbconnect.php");
include("php/checklogin.php");
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
    
    <style>
        #doj .ui-datepicker-calendar {
            display: none;
        }
        .dashboard-stats {
            background-color: #fff;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
        }
        .dashboard-stats:hover {
            box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
        }
        .dashboard-stats h3 {
            margin-top: 0;
            font-size: 24px;
            font-weight: 300;
        }
        .dashboard-stats p {
            font-size: 36px;
            font-weight: bold;
            margin: 0;
        }
        .bg-primary {
            background-color: rgba(1, 129, 55, 0.9) !important;
            color: white;
        }
        .bg-info {
            background-color: #5bc0de !important;
            color: white;
        }
        .bg-warning {
            background-color: #f0ad4e !important;
            color: white;
        }
        .bg-danger {
            background-color: #d9534f !important;
            color: white;
        }
        .search-box {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 30px;
            border: 1px solid #e9e9e9;
        }
        .btn {
            border-radius: 4px !important;
            padding: 6px 15px;
        }
        .panel {
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }
        .table-hover > tbody > tr:hover {
            background-color: #f5f5f5;
        }
        .modal-content {
            border-radius: 6px;
        }
    </style>
</head>
<?php include("php/header.php"); ?>
<div id="page-wrapper">
    <div id="page-inner">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-head-line">Payment Reports</h1>
            </div>
        </div>

        <!-- Search Section -->
        <div class="row">
            <div class="col-md-12">
                <div class="search-box">
                    <h4><i class="fa fa-search"></i> Search Records</h4>
                    <form class="form-inline" role="form" id="searchform">
                        <div class="form-group">
                            <label for="student">Student Name:</label>
                            <input type="text" class="form-control" id="student" name="student" placeholder="Enter student name">
                        </div>
                        <button type="button" class="btn btn-success" id="find"><i class="fa fa-filter"></i> Filter</button>
                        <button type="reset" class="btn btn-danger" id="clear"><i class="fa fa-refresh"></i> Reset</button>
                        
                    </form>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="panel panel-default">
            <div class="panel-heading bg-primary">
                <h3 class="panel-title" style="color: white;"><i class="fa fa-list"></i> Student Fees Records</h3>
            </div>
            <div class="panel-body">
                <div class="table-responsive" id="subjectresult">
                    <table class="table table-striped table-bordered table-hover" id="tSortable22">
                        <thead>
                            <tr>
                                <th>Name/Contact</th>
                                <th>Fees</th>
                                <th>Balance</th>
                                <th>Year</th>
                                <th>DOJ</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal for Fee Report -->
        <div class="modal fade" id="myModal" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary" style="color: white;">
                        <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                        <h4 class="modal-title"><i class="fa fa-file-text"></i> Student Transaction History</h4>
                    </div>
                    <div class="modal-body" id="formcontent">
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p>Loading student transactions...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="printReport()"><i class="fa fa-print"></i> Print Report</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal for All Transactions Report -->
        <div class="modal fade" id="allTransactionsModal" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary" style="color: white;">
                        <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                        <h4 class="modal-title"><i class="fa fa-list"></i> All Payment Transactions</h4>
                    </div>
                    <div class="modal-body" id="allTransactionsContent">
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p>Loading transactions...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="printAllTransactionsReport()"><i class="fa fa-print"></i> Print All Transactions</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /. PAGE INNER  -->
</div>
<!-- /. PAGE WRAPPER  -->

<script type="text/javascript">
$(document).ready(function() {
    // Initialize autocomplete for student search
    $('#student').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'ajx.php',
                dataType: "json",
                data: {
                    name_startsWith: request.term,
                    type: 'report'
                },
                success: function(data) {
                    response($.map(data, function(item) {
                        return {
                            label: item,
                            value: item
                        }
                    }));
                }
            });
        }
    });

    // Filter button click event
    $('#find').click(function() {
        mydatatable();
    });

    // Clear button click event
    $('#clear').click(function() {
        $('#searchform')[0].reset();
        mydatatable();
    });
    
    // Print All Transactions button click event
    $('#printAllTransactions').click(function() {
        // Load all transactions into the modal
        $.ajax({
            type: 'post',
            url: 'getalltransactions.php',
            success: function(data) {
                $('#allTransactionsContent').html(data);
                $("#allTransactionsModal").modal({
                    backdrop: "static"
                });
            }
        });
    });

    // Initialize DataTable
    $("#tSortable22").dataTable({
        'sPaginationType': 'full_numbers',
        "bLengthChange": false,
        "bFilter": false,
        "bInfo": false,
        'bProcessing': true,
        'bServerSide': true,
        'sAjaxSource': "datatable.php?type=report",
        'aoColumnDefs': [{
            'bSortable': false,
            'aTargets': [-1]
        }]
    });

    // Function to reload DataTable with filter
    function mydatatable() {
        $("#subjectresult").html('<table class="table table-striped table-bordered table-hover" id="tSortable22"><thead><tr><th>Name/Contact</th><th>Fees</th><th>Balance</th><th>Year</th><th>DOJ</th><th>Action</th></tr></thead><tbody></tbody></table>');
        
        $("#tSortable22").dataTable({
            'sPaginationType': 'full_numbers',
            "bLengthChange": false,
            "bFilter": false,
            "bInfo": false,
            'bProcessing': true,
            'bServerSide': true,
            'sAjaxSource': "datatable.php?" + $('#searchform').serialize() + "&type=report",
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': [-1]
            }]
        });
    }
});

// Function to get fee form for a student
function GetFeeForm(sid) {
    $.ajax({
        type: 'post',
        url: 'getstudentreport.php',
        data: {
            student: sid
        },
        success: function(data) {
            $('#formcontent').html(data);
            $("#myModal").modal({
                backdrop: "static"
            });
        }
    });
}

// Function to print the fee report
function printReport() {
    var printContents = document.getElementById("formcontent").innerHTML;
    var originalContents = document.body.innerHTML;

    // Get student name from the report
    var studentName = $('#formcontent .panel-body table.table-bordered tr:first-child td').text();

    document.body.innerHTML = "<html><head><title>Student Transaction History</title><style>body { font-family: Arial, sans-serif; } table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; } .report-header { text-align: center; margin-bottom: 20px; } .report-header h2 { margin-bottom: 5px; } .report-date { margin-bottom: 20px; } @media print { .no-print { display: none; } }</style></head><body>" +
    "<div class='report-header'><h2>School Fees Management System</h2><h3>Student Transaction History Report</h3><h4>" + studentName + "</h4></div>" +
    "<div class='report-date'>Generated on: " + new Date().toLocaleString() + "</div>" +
    printContents + "</body></html>";
    
    window.print();

    document.body.innerHTML = originalContents;
    location.reload();  // Reload to restore the page
}

// Function to print all transactions
function printAllTransactionsReport() {
    var printContents = document.getElementById("allTransactionsContent").innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = "<html><head><title>All Transactions Report</title><style>body { font-family: Arial, sans-serif; } table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; } .report-header { text-align: center; margin-bottom: 20px; } .report-header h2 { margin-bottom: 5px; } .report-date { margin-bottom: 20px; } @media print { .no-print { display: none; } }</style></head><body>" +
    "<div class='report-header'><h2>School Fees Management System</h2><h3>All Payment Transactions Report</h3></div>" +
    "<div class='report-date'>Generated on: " + new Date().toLocaleString() + "</div>" +
    printContents + "</body></html>";
    
    window.print();

    document.body.innerHTML = originalContents;
    location.reload();  // Reload to restore the page
}
</script>

<!-- BOOTSTRAP SCRIPTS -->
<script src="js/bootstrap.js"></script>
<!-- METISMENU SCRIPTS -->
<script src="js/jquery.metisMenu.js"></script>
<!-- CUSTOM SCRIPTS -->
<script src="js/custom1.js"></script>

</body>
</html>