<?php
include("php/dbconnect.php");
include("php/checklogin.php");

// Get student ID from request
$sid = isset($_POST['student']) ? intval($_POST['student']) : 0;

if($sid <= 0) {
    echo '<div class="alert alert-danger">Invalid student ID.</div>';
    exit;
}

// Get student information
$sql_student = "SELECT * FROM student WHERE id = $sid";
$result_student = $conn->query($sql_student);

if($result_student->num_rows == 0) {
    echo '<div class="alert alert-danger">Student not found.</div>';
    exit;
}

$student = $result_student->fetch_assoc();

// Get all transactions for this student
$sql_transactions = "SELECT * FROM fees_transaction WHERE stdid = $sid ORDER BY submitdate DESC";
$result_transactions = $conn->query($sql_transactions);

// Calculate fee statistics
$total_fees = $student['fees'];
$total_paid = 0;

if($result_transactions->num_rows > 0) {
    $temp_result = $conn->query("SELECT SUM(paid) as total FROM fees_transaction WHERE stdid = $sid");
    $temp_row = $temp_result->fetch_assoc();
    $total_paid = $temp_row['total'];
}

$balance = $total_fees - $total_paid;
?>

<div class="panel-body">
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info">
                <h4 class="text-center">Student Fee Details</h4>
            </div>
        </div>
    </div>
    
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">Student Information</h4>
                </div>
                <div class="panel-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>Name:</th>
                            <td><?php echo $student['sname']; ?></td>
                        </tr>
                        <tr>
                            <th>Roll No:</th>
                            <td><?php echo isset($student['roll_no']) ? $student['roll_no'] : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th>Contact:</th>
                            <td><?php echo $student['contact']; ?></td>
                        </tr>
                        <tr>
                            <th>Course:</th>
                            <td><?php echo $student['course']; ?></td>
                        </tr>
                        <tr>
                            <th>Year:</th>
                            <td><?php echo $student['joindate']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">Fee Summary</h4>
                </div>
                <div class="panel-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>Total Fees:</th>
                            <td>₱<?php echo number_format($total_fees, 2); ?></td>
                        </tr>
                        <tr>
                            <th>Total Paid:</th>
                            <td>₱<?php echo number_format($total_paid, 2); ?></td>
                        </tr>
                        <tr>
                            <th>Balance:</th>
                            <td>
                                <strong style="color: <?php echo ($balance > 0) ? 'red' : 'green'; ?>">
                                    ₱<?php echo number_format($balance, 2); ?>
                                </strong>
                            </td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <?php 
                                if($balance <= 0) {
                                    echo '<span class="label label-success">Paid</span>';
                                } else {
                                    echo '<span class="label label-danger">Due</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title">Transaction History</h4>
                </div>
                <div class="panel-body">
                    <?php if($result_transactions->num_rows > 0): ?>
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Amount Paid</th>
                                    <th>Payment Date</th>
                                    <th>Payment Method</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($transaction = $result_transactions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $transaction['id']; ?></td>
                                    <td>₱<?php echo number_format($transaction['paid'], 2); ?></td>
                                    <td><?php echo date("d-m-Y h:i A", strtotime($transaction['submitdate'])); ?></td>
                                    <td>
                                        <?php
                                        // Changed from 'transcation_remark' to 'transaction_remark'
                                        echo isset($transaction['transaction_remark']) ? $transaction['transaction_remark'] : 'Cash';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Changed from 'transcation_detail' to 'transaction_detail'
                                        echo isset($transaction['transaction_detail']) ? $transaction['transaction_detail'] : '-';
                                        ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="1" class="text-right">Total Paid:</th>
                                    <th>₱<?php echo number_format($total_paid, 2); ?></th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No payment transactions found for this student.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>