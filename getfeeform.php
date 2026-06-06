<?php
include("php/dbconnect.php");
include("php/checklogin.php");

$id = mysqli_real_escape_string($conn, $_POST["student"]);

$sql = "SELECT * FROM student WHERE id = '" . $id . "'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo '<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info student-info">
            <div class="row">
                <div class="col-md-8">
                    <h4>' . $row["sname"] . '</h4>
                    <p><strong>Student ID:</strong> ' . $row["id"] . ' | <strong>Course:</strong> ' . $row["course"] . '</p>
                </div>
                <div class="col-md-4 text-right">
                    <div class="fees-summary">
                        <p><strong>Total Fees:</strong> $' . number_format($row["fees"], 2) . '</p>
                        <p><strong>Balance:</strong> $' . number_format($row["balance"], 2) . '</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="fees.php" method="post" id="feeForm">
    <input type="hidden" name="sid" id="sid" value="' . $id . '">
    
    <div class="form-group row">
        <label for="paid" class="col-sm-3 col-form-label">Payment Amount:</label>
        <div class="col-sm-9">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">$</span>
                </div>
                <input type="number" step="0.01" name="paid" id="paid" class="form-control" required min="1" max="' . $row["balance"] . '">
            </div>
            <small class="form-text text-muted">Maximum amount: $' . number_format($row["balance"], 2) . '</small>
        </div>
    </div>
    
    <div class="form-group row">
        <label for="submitdate" class="col-sm-3 col-form-label">Payment Date:</label>
        <div class="col-sm-9">
            <input type="text" name="submitdate" id="paymentDate" class="form-control" required value="' . date("Y-m-d") . '">
        </div>
    </div>
    
    <div class="form-group row">
        <label for="transcation_remark" class="col-sm-3 col-form-label">Remarks:</label>
        <div class="col-sm-9">
            <textarea name="transcation_remark" id="transcation_remark" class="form-control" required rows="3"></textarea>
        </div>
    </div>
    
    <div class="form-group row">
        <div class="col-sm-9 offset-sm-3">
            <button type="submit" name="save" class="btn btn-primary" id="submitBtn">
                <i class="fa fa-check-circle"></i> Submit Payment
            </button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                <i class="fa fa-times-circle"></i> Cancel
            </button>
        </div>
    </div>
</form>';
?>