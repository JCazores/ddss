<?php
include("php/dbconnect.php");
include("php/checklogin.php");

// Calculate total fees collected
$sql = "SELECT SUM(paid) as total FROM fees_transaction";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_collected = $row["total"] ? $row["total"] : 0;

// Count total students
$sql = "SELECT COUNT(*) as count FROM student";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$students_count = $row["count"];

// Calculate total pending payments
$sql = "SELECT SUM(balance) as total FROM student";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_pending = $row["total"] ? $row["total"] : 0;

// Return JSON response
header("Content-Type: application/json");
echo json_encode([
    "total_collected" => $total_collected,
    "students_count" => $students_count,
    "total_pending" => $total_pending
]);
?>