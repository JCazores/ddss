<?php
include("php/dbconnect.php");
include("php/checklogin.php");

if(isset($_POST["student"])) {
    $sid = mysqli_real_escape_string($conn, $_POST["student"]);
    
    // Get student details
    $sql = "SELECT * FROM student WHERE id = '" . $sid . "'";
    $result = $conn->query($sql);
    $studentRow = $result->fetch_assoc();
    
    // Get payment history
    $sql = "SELECT * FROM fees_transaction WHERE stdid = '" . $sid . "' ORDER BY submitdate DESC";
    $result = $conn->query($sql);
    
    echo '<div class="student-details p-3 mb-3 bg-light rounded">
            <div class="row">
                <div class="col-md-6">
                    <h5>' . $studentRow["sname"] . '</h5>
                    <p class="mb-1"><strong>ID:</strong> ' . $studentRow["id"] . '</p>
                    <p class="mb-1"><strong>Contact:</strong> ' . $studentRow["contact"] . '</p>
                </div>
                <div class="col-md-6 text-right">
                    <p class="mb-1"><strong>Course:</strong> ' . $studentRow["course"] . '</p>
                    <p class="mb-1"><strong>Total Fees:</strong> $' . number_format($studentRow["fees"], 2) . '</p>
                    <p class="mb-1"><strong>Balance:</strong> $' . number_format($studentRow["balance"], 2) . '</p>
                </div>
            </div>
        </div>';
    
    if($result->num_rows > 0) {
        echo '<div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>Payment Date</th>
                        <th>Amount Paid</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>';
                
        $totalPaid = 0;
        while($row = $result->fetch_assoc()) {
            $totalPaid += $row["paid"];
            echo '<tr>
                    <td>' . date("d M Y", strtotime($row["submitdate"])) . '</td>
                    <td>$' . number_format($row["paid"], 2) . '</td>
                    <td>' . $row["transcation_remark"] . '</td>
                </tr>';
        }
        
        echo '</tbody>
            <tfoot>
                <tr class="table-success">
                    <th>Total Paid</th>
                    <th>$' . number_format($totalPaid, 2) . '</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
        </div>';
    } else {
        echo '<div class="alert alert-info">No payment records found for this student.</div>';
    }
}
?>