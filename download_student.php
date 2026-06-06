<?php
include("php/dbconnect.php");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=students.csv');

$output = fopen('php://output', 'w');

// Get all column names dynamically
$columnsQuery = $conn->query("SHOW COLUMNS FROM student");
$columns = [];

while ($row = $columnsQuery->fetch_assoc()) {
    $columns[] = $row['Field'];
}

// Write column headers to CSV
fputcsv($output, $columns);

// Fetch and write all student data dynamically
$query = $conn->query("SELECT * FROM student WHERE delete_status = '0'");

while ($row = $query->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
