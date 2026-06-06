<?php
// check_session_status.php
session_start();
include("php/dbconnect.php");

header('Content-Type: application/json');

$response = ['status' => 'valid'];

if (isset($_SESSION['rainbow_uid']) && isset($_SESSION['rainbow_username'])) {
    $user_id = $_SESSION['rainbow_uid'];
    
    // Check if user still exists and is active
    $check_sql = "SELECT status FROM user WHERE id = $user_id";
    $result = $conn->query($check_sql);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if ($user['status'] !== 'active') {
            $response['status'] = 'invalid';
            $response['reason'] = 'account_deactivated';
        }
    } else {
        $response['status'] = 'invalid';
        $response['reason'] = 'account_not_found';
    }
} else {
    $response['status'] = 'invalid';
    $response['reason'] = 'no_session';
}

echo json_encode($response);
?>