<?php
// Include database connection if not already included
if (!isset($conn)) {
    include_once('../php/dbconnect.php');
}

// Check if delete_status column exists before using it
$check_column = $conn->query("SHOW COLUMNS FROM course LIKE 'delete_status'");

// Count total courses based on whether delete_status column exists
if($check_column->num_rows > 0) {
    // If delete_status column exists, count only active courses
    $result = $conn->query("SELECT COUNT(*) as total FROM course WHERE delete_status='0'");
} else {
    // If no delete_status column, count all courses
    $result = $conn->query("SELECT COUNT(*) as total FROM course");
}

if ($result) {
    $row = $result->fetch_assoc();
    echo $row['total'];
} else {
    echo "0";
}
?>