<?php
include("php/dbconnect.php");

// Check if user is admin
if (!isset($_SESSION['rainbow_username']) || $_SESSION['rainbow_username'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    exit();
}

// Validate input
if (!isset($_POST['record_id']) || !isset($_POST['table_name'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Missing parameters.']);
    exit();
}

$record_id = intval($_POST['record_id']);
$table_name = mysqli_real_escape_string($conn, $_POST['table_name']);

// Verify table exists
$table_check_sql = "SHOW TABLES LIKE '$table_name'";
$table_check_result = $conn->query($table_check_sql);

if (!$table_check_result || $table_check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Table does not exist.']);
    exit();
}

// Check if record exists before attempting to delete
$check_sql = "SELECT id FROM `$table_name` WHERE id = $record_id";
$check_result = $conn->query($check_sql);

if (!$check_result || $check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Record not found.']);
    exit();
}

// Attempt to delete the record
$delete_sql = "DELETE FROM `$table_name` WHERE id = $record_id LIMIT 1";
$delete_result = $conn->query($delete_sql);

if ($delete_result) {
    if ($conn->affected_rows > 0) {
        // Log the deletion for audit trail
        $log_sql = "INSERT INTO deletion_log (table_name, record_id, deleted_by, deleted_at) VALUES (?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        if ($log_stmt) {
            $deleted_by = $_SESSION['rainbow_username'];
            $log_stmt->bind_param("sis", $table_name, $record_id, $deleted_by);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Record deleted successfully.',
            'record_id' => $record_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No record was deleted. Record may not exist.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
?>