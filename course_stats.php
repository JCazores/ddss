<?php
include("php/dbconnect.php");
include("php/checklogin.php");

// Get fee collection by course
$sql = "SELECT course, SUM(paid) as amount FROM fees_transaction GROUP BY course ORDER BY amount DESC";
$result = $conn->query($sql);

$stats = array();
while($row = $result->fetch_assoc()) {
    $stats[] = array(
        "course" => $row["course"],
        "amount" => $row["amount"]
    );
}

// Return JSON response
header("Content-Type: application/json");
echo json_encode($stats);
?>