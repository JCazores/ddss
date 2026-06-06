<?php
include("php/dbconnect.php");
include("php/checklogin.php");

// Get all transactions with student information
// Fixed column names from 'transcation_remark' to 'transaction_remark' 
// and 'transcation_detail' to 'transaction_detail'
$sql = "SELECT ft.id, ft.paid, ft.submitdate, ft.transaction_remark, ft.transaction_detail, st.sname, st.contact, st.course 
        FROM fees_transaction ft 
        LEFT JOIN student st ON ft.stdid = st.id 
        ORDER BY ft.submitdate DESC";

$result = $conn->query($sql);
$total_paid = 0;
?>

<div class="panel-body">
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info">
                <h4 class="text-center">All Fee Transactions</h4>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <?php if($result->num_rows > 0): ?>
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Student Name</th>
                            <th>Contact</th>
                            <th>Course</th>
                            <th>Amount Paid</th>
                            <th>Date</th>
                            <th>Payment Method</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        while($row = $result->fetch_assoc()): 
                            $total_paid += $row['paid'];
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['sname']; ?></td>
                            <td><?php echo $row['contact']; ?></td>
                            <td><?php echo $row['course']; ?></td>
                            <td>₱<?php echo number_format($row['paid'], 2); ?></td>
                            <td><?php echo date("d-m-Y h:i A", strtotime($row['submitdate'])); ?></td>
                            <td><?php echo isset($row['transaction_remark']) ? $row['transaction_remark'] : 'Cash'; ?></td>
                            <td><?php echo isset($row['transaction_detail']) ? $row['transaction_detail'] : '-'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-right">Total Collected:</th>
                            <th>₱<?php echo number_format($total_paid, 2); ?></th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <div class="alert alert-warning">
                    No payment transactions found in the system.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>