<?php
session_start();
include("php/dbconnect.php");

// Log the logout activity if user is logged in
if (isset($_SESSION['rainbow_username']) && isset($_SESSION['rainbow_uid'])) {
    $user_id = $_SESSION['rainbow_uid'];
    $username = $_SESSION['rainbow_username'];
    $session_id = session_id();
    
    // Log the logout activity
    $logout_log_sql = "INSERT INTO user_login_logs (user_id, username, ip_address, user_agent, login_status) 
                      VALUES ('$user_id', '$username', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', 'logout')";
    $conn->query($logout_log_sql);
    
    // Mark session as inactive
    $session_update_sql = "UPDATE user_sessions SET is_active = 0 WHERE session_id = '$session_id' AND user_id = '$user_id'";
    $conn->query($session_update_sql);
    
    // AUTOMATICALLY SET USER STATUS TO INACTIVE ON LOGOUT
    $user_status_update_sql = "UPDATE user SET status = 'inactive', last_logout = NOW() WHERE id = '$user_id'";
    $conn->query($user_status_update_sql);
    
    // If it's an admin logout, log it separately (but don't change admin status)
    if (isset($_SESSION['rainbow_role']) && $_SESSION['rainbow_role'] == 'admin') {
        $admin_logout_sql = "INSERT INTO admin_activity_logs (admin_id, admin_username, activity, ip_address, user_agent, timestamp) 
                            VALUES ('$user_id', '$username', 'Admin logout - Status kept active', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
        $conn->query($admin_logout_sql);
        
        // Keep admin status active even after logout
        $admin_status_update_sql = "UPDATE user SET status = 'active' WHERE id = '$user_id' AND (role = 'admin' OR username = 'admin')";
        $conn->query($admin_status_update_sql);
    } else {
        // Log regular user logout with status change
        $user_logout_log = "INSERT INTO user_login_logs (user_id, username, ip_address, user_agent, login_status, additional_info) 
                           VALUES ('$user_id', '$username', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', 'logout', 'Status changed to inactive')";
        $conn->query($user_logout_log);
    }
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Close database connection
if (isset($conn)) {
    $conn->close();
}

// Redirect to login page with logout message
header('Location: login.php?logout=1');
exit();
?>