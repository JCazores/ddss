<?php
// 1. First, create a session validation function (add to dbconnect.php or create session_check.php)

function validateUserSession($conn) {
    if (isset($_SESSION['rainbow_uid']) && isset($_SESSION['rainbow_username'])) {
        // Check if user still exists and is active
        $user_id = $_SESSION['rainbow_uid'];
        $check_sql = "SELECT status FROM user WHERE id = $user_id";
        $result = $conn->query($check_sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // If user is inactive, destroy session and redirect to login
            if ($user['status'] === 'inactive') {
                // Log the forced logout
                $log_sql = "INSERT INTO user_login_logs (user_id, username, login_status, ip_address, user_agent, timestamp, additional_info) 
                           VALUES ({$_SESSION['rainbow_uid']}, '{$_SESSION['rainbow_username']}', 'logout', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW(), 'Account deactivated - forced logout')";
                $conn->query($log_sql);
                
                // Update user's last logout time
                $logout_sql = "UPDATE user SET last_logout = NOW() WHERE id = {$_SESSION['rainbow_uid']}";
                $conn->query($logout_sql);
                
                // Destroy session
                session_destroy();
                
                // Redirect to login with message
                header('Location: login.php?message=account_deactivated');
                exit();
            }
        } else {
            // User doesn't exist anymore, destroy session
            session_destroy();
            header('Location: login.php?message=account_not_found');
            exit();
        }
    }
}

// 2. Enhanced status update function in admin.php
// Replace the existing user status update section with this:

if (isset($_POST['update_status'])) {
    $user_id = intval($_POST['user_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Get user info before update
    $user_info_sql = "SELECT username, name FROM user WHERE id = $user_id";
    $user_info_result = $conn->query($user_info_sql);
    $user_info = $user_info_result->fetch_assoc();
    $target_username = $user_info['username'];
    
    $update_sql = "UPDATE user SET status = '$status', status_updated_date = NOW() WHERE id = $user_id";
    if ($conn->query($update_sql)) {
        // If deactivating user, also invalidate their active sessions
        if ($status === 'inactive') {
            // Insert a forced logout entry for this user
            $forced_logout_sql = "INSERT INTO user_login_logs (user_id, username, login_status, ip_address, user_agent, timestamp, additional_info) 
                                 VALUES ($user_id, '$target_username', 'logout', '{$_SERVER['REMOTE_ADDR']}', 'ADMIN-FORCED', NOW(), 'Account deactivated by admin - session terminated')";
            $conn->query($forced_logout_sql);
            
            // Update user's last logout time
            $logout_update_sql = "UPDATE user SET last_logout = NOW() WHERE id = $user_id";
            $conn->query($logout_update_sql);
            
            $success = 'User status updated successfully. User has been logged out if they were online.';
        } else {
            $success = 'User status updated successfully';
        }
        
        // Log admin activity
        $admin_id = $_SESSION['rainbow_uid'];
        $activity = "Updated user status to: $status" . ($status === 'inactive' ? ' (forced logout)' : '');
        
        $log_sql = "INSERT INTO admin_activity_logs (admin_id, admin_username, activity, target_user, ip_address, user_agent, timestamp) 
                   VALUES ('$admin_id', '{$_SESSION['rainbow_username']}', '$activity', '$target_username', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
        $conn->query($log_sql);
    } else {
        $error = 'Error updating user status';
    }
}

// 3. Add this session check at the top of every protected page (index.php, upload.php, etc.)
// Add this right after include("php/dbconnect.php");

// Validate user session and status
validateUserSession($conn);

// 4. Optional: Create a more sophisticated session management table
// Run this SQL to create an active sessions table:

/*
CREATE TABLE IF NOT EXISTS active_sessions (
    session_id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);
*/

// 5. Enhanced session tracking functions
function createSessionRecord($conn, $user_id, $username) {
    $session_id = session_id();
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Insert or update session record
    $session_sql = "INSERT INTO active_sessions (session_id, user_id, username, ip_address, user_agent) 
                   VALUES ('$session_id', $user_id, '$username', '$ip_address', '$user_agent')
                   ON DUPLICATE KEY UPDATE 
                   last_activity = CURRENT_TIMESTAMP, 
                   ip_address = '$ip_address',
                   user_agent = '$user_agent'";
    $conn->query($session_sql);
}

function destroySessionRecord($conn, $user_id = null) {
    $session_id = session_id();
    
    if ($user_id) {
        // Remove all sessions for this user
        $cleanup_sql = "DELETE FROM active_sessions WHERE user_id = $user_id";
    } else {
        // Remove current session
        $cleanup_sql = "DELETE FROM active_sessions WHERE session_id = '$session_id'";
    }
    $conn->query($cleanup_sql);
}

// 6. Enhanced status update with session cleanup
function forceLogoutUser($conn, $user_id, $reason = 'Admin action') {
    // Get user info
    $user_sql = "SELECT username FROM user WHERE id = $user_id";
    $user_result = $conn->query($user_sql);
    $user_info = $user_result->fetch_assoc();
    
    if ($user_info) {
        // Log forced logout
        $logout_sql = "INSERT INTO user_login_logs (user_id, username, login_status, ip_address, user_agent, timestamp, additional_info) 
                      VALUES ($user_id, '{$user_info['username']}', 'logout', '{$_SERVER['REMOTE_ADDR']}', 'ADMIN-FORCED', NOW(), '$reason')";
        $conn->query($logout_sql);
        
        // Update last logout time
        $update_logout_sql = "UPDATE user SET last_logout = NOW() WHERE id = $user_id";
        $conn->query($update_logout_sql);
        
        // Remove all active sessions for this user
        destroySessionRecord($conn, $user_id);
        
        return true;
    }
    return false;
}

?>

<!-- 7. Add JavaScript for real-time session checking -->
<script>
// Check session status every 30 seconds
setInterval(function() {
    $.ajax({
        url: 'check_session_status.php',
        method: 'POST',
        success: function(response) {
            if (response.status === 'invalid') {
                alert('Your account has been deactivated. You will be logged out.');
                window.location.href = 'login.php?message=account_deactivated';
            }
        },
        error: function() {
            // Silently handle errors
        }
    });
}, 30000); // Check every 30 seconds
</script>