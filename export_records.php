<?php
include("php/dbconnect.php");

// Check if user is admin
if (!isset($_SESSION['rainbow_username']) || $_SESSION['rainbow_username'] !== 'admin') {
    die('Access denied. Admin privileges required.');
}

// Validate input
if (!isset($_POST['log_id']) || !isset($_POST['table_name'])) {
    die('Invalid request. Missing parameters.');
}

$log_id = intval($_POST['log_id']);
$table_name = mysqli_real_escape_string($conn, $_POST['table_name']);
$format = isset($_POST['format']) ? $_POST['format'] : 'csv';

// Verify the upload log exists
$log_info_sql = "SELECT * FROM upload_logs WHERE log_id = $log_id";
$log_info_result = $conn->query($log_info_sql);

if (!$log_info_result || $log_info_result->num_rows === 0) {
    die('Upload log not found.');
}

$log_info = $log_info_result->fetch_assoc();

// Verify table exists
$table_check_sql = "SHOW TABLES LIKE '$table_name'";
$table_check_result = $conn->query($table_check_sql);

if (!$table_check_result || $table_check_result->num_rows === 0) {
    die('Table does not exist.');
}

// Get all records from the table
$records_sql = "SELECT * FROM `$table_name` ORDER BY id ASC";
$records_result = $conn->query($records_sql);

if (!$records_result) {
    die('Error fetching records: ' . $conn->error);
}

// Set headers for CSV download
$filename = $table_name . '_export_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Write CSV header
$first_row = true;
$headers = [];

while ($record = $records_result->fetch_assoc()) {
    if ($first_row) {
        // Get column headers from first record
        $headers = array_keys($record);
        fputcsv($output, $headers);
        $first_row = false;
    }
    
    // Write record data
    fputcsv($output, array_values($record));
}

// Close output stream
fclose($output);
exit();
?>