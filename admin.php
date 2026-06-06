<?php

include("php/dbconnect.php");

// Check if user is admin
if (!isset($_SESSION['rainbow_username']) || $_SESSION['rainbow_username'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Handle file record editing
if (isset($_POST['edit_record'])) {
    $record_id = intval($_POST['record_id']);
    $table_name = mysqli_real_escape_string($conn, $_POST['table_name']);
    
    // Build update query dynamically based on form fields
    $update_fields = [];
    foreach ($_POST as $key => $value) {
        if ($key !== 'edit_record' && $key !== 'record_id' && $key !== 'table_name') {
            $escaped_value = mysqli_real_escape_string($conn, $value);
            $escaped_key = mysqli_real_escape_string($conn, $key);
            $update_fields[] = "`$escaped_key` = '$escaped_value'";
        }
    }
    
    if (!empty($update_fields)) {
        $update_sql = "UPDATE `$table_name` SET " . implode(', ', $update_fields) . " WHERE id = $record_id";
        if ($conn->query($update_sql)) {
            $success = 'Record updated successfully';
            
            // Log admin activity
            $admin_id = $_SESSION['rainbow_uid'];
            $activity = "Edited record ID $record_id in table $table_name";
            $log_sql = "INSERT INTO admin_activity_logs (admin_id, admin_username, activity, target_user, ip_address, user_agent, timestamp) 
                       VALUES ('$admin_id', '{$_SESSION['rainbow_username']}', '$activity', NULL, '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
            $conn->query($log_sql);
        } else {
            $error = 'Error updating record: ' . $conn->error;
        }
    }
}

// Handle file record deletion
if (isset($_POST['delete_record'])) {
    $record_id = intval($_POST['record_id']);
    $table_name = mysqli_real_escape_string($conn, $_POST['table_name']);
    
    $delete_sql = "DELETE FROM `$table_name` WHERE id = $record_id";
    if ($conn->query($delete_sql)) {
        $success = 'Record deleted successfully';
        
        // Log admin activity
        $admin_id = $_SESSION['rainbow_uid'];
        $activity = "Deleted record ID $record_id from table $table_name";
        $log_sql = "INSERT INTO admin_activity_logs (admin_id, admin_username, activity, target_user, ip_address, user_agent, timestamp) 
                   VALUES ('$admin_id', '{$_SESSION['rainbow_username']}', '$activity', NULL, '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
        $conn->query($log_sql);
    } else {
        $error = 'Error deleting record: ' . $conn->error;
    }
}

// Handle bulk file deletion
if (isset($_POST['delete_file_data'])) {
    $log_id = intval($_POST['log_id']);
    
    // Get file information first
    $file_info_sql = "SELECT * FROM upload_logs WHERE log_id = $log_id";
    $file_info_result = $conn->query($file_info_sql);
    $file_info = $file_info_result->fetch_assoc();
    
    if ($file_info) {
        $table_name = $file_info['table_name'];
        $filename = $file_info['filename'];
        
        // Delete all records from the table (removes dependency on upload_batch_id)
        $delete_sql = "DELETE FROM `$table_name`";
        if ($conn->query($delete_sql)) {
            $affected_rows = $conn->affected_rows;
            
            // Update upload log status
            $update_log_sql = "UPDATE upload_logs SET status = 'deleted', error_message = 'File data deleted by admin' WHERE log_id = $log_id";
            $conn->query($update_log_sql);
            
            $success = "Successfully deleted $affected_rows records from file: $filename";
            
            // Log admin activity
            $admin_id = $_SESSION['rainbow_uid'];
            $activity = "Deleted all data from uploaded file: $filename ($affected_rows records)";
            $log_sql = "INSERT INTO admin_activity_logs (admin_id, admin_username, activity, target_user, ip_address, user_agent, timestamp) 
                       VALUES ('$admin_id', '{$_SESSION['rainbow_username']}', '$activity', NULL, '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
            $conn->query($log_sql);
        } else {
            $error = 'Error deleting file data: ' . $conn->error;
        }
    }
}
// Updated User Creation Handler with improved password requirements
if (isset($_POST['create_user'])) {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    if (empty($username) || empty($password) || empty($name) || empty($email)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif (strlen($password) > 16) {
        $error = 'Password must not exceed 16 characters';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $error = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
    } else {
        // Check if username already exists
        $check_sql = "SELECT * FROM user WHERE username = '$username'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $error = 'Username already exists';
        } else {
            // Insert new user with active status
            $hashed_password = md5($password);
            $insert_sql = "INSERT INTO user (username, password, name, email, role, created_date, status) 
                          VALUES ('$username', '$hashed_password', '$name', '$email', '$role', NOW(), 'active')";
            
            if ($conn->query($insert_sql)) {
                $success = 'User created successfully with active status';
                
                // Log admin activity
                $admin_id = $_SESSION['rainbow_uid'];
                $activity = "Created new user: $username ($name) with role: $role";
                $log_sql = "INSERT INTO admin_activity_logs (admin_id, admin_username, activity, target_user, ip_address, user_agent, timestamp) 
                           VALUES ('$admin_id', '{$_SESSION['rainbow_username']}', '$activity', '$username', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
                $conn->query($log_sql);
            } else {
                $error = 'Error creating user: ' . $conn->error;
            }
        }
    }
}
// Handle user update with role
if (isset($_POST['update_user'])) {
    $user_id = intval($_POST['user_id']);
    $new_username = mysqli_real_escape_string($conn, trim($_POST['new_username']));
    $new_password = $_POST['new_password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    if (empty($new_username)) {
        $error = 'Username is required';
    } else {
        // Get current user data for logging
        $current_user_sql = "SELECT username, role FROM user WHERE id = $user_id";
        $current_result = $conn->query($current_user_sql);
        $current_user = $current_result->fetch_assoc();
        $old_username = $current_user['username'];
        $old_role = $current_user['role'];
        
        // Check if new username already exists (excluding current user)
        $check_sql = "SELECT * FROM user WHERE username = '$new_username' AND id != $user_id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $error = 'Username already exists';
        } else {
            // Build update query
            $update_fields = ["`username` = '$new_username'", "`role` = '$role'", "`status_updated_date` = NOW()"];
            
            // Only update password if provided
            if (!empty($new_password)) {
                if (strlen($new_password) < 8) {
                    $error = 'Password must be at least 8 characters long';
                } elseif (strlen($new_password) > 16) {
                    $error = 'Password must not exceed 16 characters';
                } else {
                    $hashed_password = md5($new_password);
                    $update_fields[] = "`password` = '$hashed_password'";
                }
            }
            
            if (!$error) {
                $update_sql = "UPDATE user SET " . implode(', ', $update_fields) . " WHERE id = $user_id AND username != 'admin'";
                
                if ($conn->query($update_sql)) {
                    $success = 'User updated successfully';
                    
                    // Log admin activity
                    $admin_id = $_SESSION['rainbow_uid'];
                    $changes = [];
                    if ($old_username != $new_username) {
                        $changes[] = "username: $old_username → $new_username";
                    }
                    if ($old_role != $role) {
                        $changes[] = "role: $old_role → $role";
                    }
                    if (!empty($new_password)) {
                        $changes[] = "password updated";
                    }
                    
                    $activity = "Updated user: " . implode(', ', $changes);
                    $log_sql = "INSERT INTO admin_activity_logs (admin_id, admin_username, activity, target_user, ip_address, user_agent, timestamp) 
                               VALUES ('$admin_id', '{$_SESSION['rainbow_username']}', '$activity', '$new_username', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
                    $conn->query($log_sql);
                } else {
                    $error = 'Error updating user: ' . $conn->error;
                }
            }
        }
    }
}
// Handle user status update
if (isset($_POST['update_status'])) {
    $user_id = intval($_POST['user_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_sql = "UPDATE user SET status = '$status', status_updated_date = NOW() WHERE id = $user_id";
    if ($conn->query($update_sql)) {
        $success = 'User status updated successfully';
        
        // Log admin activity
        $admin_id = $_SESSION['rainbow_uid'];
        $activity = "Manually updated user status to: $status";
        $target_user_sql = "SELECT username FROM user WHERE id = $user_id";
        $target_result = $conn->query($target_user_sql);
        $target_user = $target_result->fetch_assoc()['username'];
        
        $log_sql = "INSERT INTO admin_activity_logs (admin_id, admin_username, activity, target_user, ip_address, user_agent, timestamp) 
                   VALUES ('$admin_id', '{$_SESSION['rainbow_username']}', '$activity', '$target_user', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
        $conn->query($log_sql);
    } else {
        $error = 'Error updating user status';
    }
}

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    
    // Get username for logging
    $target_user_sql = "SELECT username FROM user WHERE id = $user_id";
    $target_result = $conn->query($target_user_sql);
    $target_user = $target_result->fetch_assoc()['username'];
    
    $delete_sql = "DELETE FROM user WHERE id = $user_id AND username != 'admin'"; // Prevent admin deletion
    if ($conn->query($delete_sql)) {
        $success = 'User deleted successfully';
        
        // Log admin activity
        $admin_id = $_SESSION['rainbow_uid'];
        $activity = "Deleted user account";
        $log_sql = "INSERT INTO admin_activity_logs (admin_id, admin_username, activity, target_user, ip_address, user_agent, timestamp) 
                   VALUES ('$admin_id', '{$_SESSION['rainbow_username']}', '$activity', '$target_user', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
        $conn->query($log_sql);
    } else {
        $error = 'Error deleting user';
    }
}

// Pagination and filtering for upload logs
$page = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

$log_filter = '';
if (isset($_GET['status_filter']) && !empty($_GET['status_filter'])) {
    $status_filter = mysqli_real_escape_string($conn, $_GET['status_filter']);
    $log_filter .= " WHERE status = '$status_filter'";
}

if (isset($_GET['user_filter']) && !empty($_GET['user_filter'])) {
    $user_filter = mysqli_real_escape_string($conn, $_GET['user_filter']);
    $log_filter .= empty($log_filter) ? " WHERE" : " AND";
    $log_filter .= " uploaded_by_username LIKE '%$user_filter%'";
}

if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $date_from = mysqli_real_escape_string($conn, $_GET['date_from']);
    $log_filter .= empty($log_filter) ? " WHERE" : " AND";
    $log_filter .= " DATE(upload_date) >= '$date_from'";
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $date_to = mysqli_real_escape_string($conn, $_GET['date_to']);
    $log_filter .= empty($log_filter) ? " WHERE" : " AND";
    $log_filter .= " DATE(upload_date) <= '$date_to'";
}

// Get upload logs with pagination
$upload_logs_sql = "SELECT * FROM upload_logs $log_filter ORDER BY upload_date DESC LIMIT $records_per_page OFFSET $offset";
$upload_logs_result = $conn->query($upload_logs_sql);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM upload_logs $log_filter";
$count_result = $conn->query($count_sql);
$total_logs = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_logs / $records_per_page);

// Get all users with login/logout logs
$users_sql = "SELECT u.*, 
              CASE 
                WHEN u.last_logout IS NOT NULL THEN CONCAT('Last logout: ', DATE_FORMAT(u.last_logout, '%M %d, %Y %H:%i'))
                ELSE 'Never logged out'
              END as logout_info,
              (SELECT COUNT(*) FROM user_login_logs ull WHERE ull.user_id = u.id AND ull.login_status = 'login') as total_logins,
              (SELECT COUNT(*) FROM user_login_logs ull WHERE ull.user_id = u.id AND ull.login_status = 'logout') as total_logouts,
              (SELECT MAX(ull.timestamp) FROM user_login_logs ull WHERE ull.user_id = u.id AND ull.login_status = 'login') as last_login,
              (SELECT COUNT(*) FROM user_login_logs ull WHERE ull.user_id = u.id AND ull.login_status = 'failed') as failed_attempts
              FROM user u ORDER BY u.created_date DESC";
$users_result = $conn->query($users_sql);

// Pagination and filtering for admin logs
$admin_page = isset($_GET['admin_page']) ? max(1, intval($_GET['admin_page'])) : 1;
$admin_records_per_page = 50;
$admin_offset = ($admin_page - 1) * $admin_records_per_page;

$admin_log_filter = '';
if (isset($_GET['admin_user_filter']) && !empty($_GET['admin_user_filter'])) {
    $admin_user_filter = mysqli_real_escape_string($conn, $_GET['admin_user_filter']);
    $admin_log_filter .= " WHERE admin_username LIKE '%$admin_user_filter%'";
}

if (isset($_GET['admin_target_filter']) && !empty($_GET['admin_target_filter'])) {
    $admin_target_filter = mysqli_real_escape_string($conn, $_GET['admin_target_filter']);
    $admin_log_filter .= empty($admin_log_filter) ? " WHERE" : " AND";
    $admin_log_filter .= " target_user LIKE '%$admin_target_filter%'";
}

if (isset($_GET['admin_date_from']) && !empty($_GET['admin_date_from'])) {
    $admin_date_from = mysqli_real_escape_string($conn, $_GET['admin_date_from']);
    $admin_log_filter .= empty($admin_log_filter) ? " WHERE" : " AND";
    $admin_log_filter .= " DATE(timestamp) >= '$admin_date_from'";
}

if (isset($_GET['admin_date_to']) && !empty($_GET['admin_date_to'])) {
    $admin_date_to = mysqli_real_escape_string($conn, $_GET['admin_date_to']);
    $admin_log_filter .= empty($admin_log_filter) ? " WHERE" : " AND";
    $admin_log_filter .= " DATE(timestamp) <= '$admin_date_to'";
}

// Get admin activity logs with pagination
$admin_logs_sql = "SELECT * FROM admin_activity_logs $admin_log_filter ORDER BY timestamp DESC LIMIT $admin_records_per_page OFFSET $admin_offset";
$admin_logs_result = $conn->query($admin_logs_sql);

// Get total count for admin logs pagination
$admin_count_sql = "SELECT COUNT(*) as total FROM admin_activity_logs $admin_log_filter";
$admin_count_result = $conn->query($admin_count_sql);
$total_admin_logs = $admin_count_result->fetch_assoc()['total'];
$total_admin_pages = ceil($total_admin_logs / $admin_records_per_page);

// Pagination and filtering for user login/logout logs
$user_page = isset($_GET['user_page']) ? max(1, intval($_GET['user_page'])) : 1;
$user_records_per_page = 50;
$user_offset = ($user_page - 1) * $user_records_per_page;

$user_log_filter = '';
if (isset($_GET['login_user_filter']) && !empty($_GET['login_user_filter'])) {
    $login_user_filter = mysqli_real_escape_string($conn, $_GET['login_user_filter']);
    $user_log_filter .= " WHERE ull.username LIKE '%$login_user_filter%'";
}

if (isset($_GET['login_status_filter']) && !empty($_GET['login_status_filter'])) {
    $login_status_filter = mysqli_real_escape_string($conn, $_GET['login_status_filter']);
    $user_log_filter .= empty($user_log_filter) ? " WHERE" : " AND";
    $user_log_filter .= " ull.login_status = '$login_status_filter'";
}

if (isset($_GET['login_date_from']) && !empty($_GET['login_date_from'])) {
    $login_date_from = mysqli_real_escape_string($conn, $_GET['login_date_from']);
    $user_log_filter .= empty($user_log_filter) ? " WHERE" : " AND";
    $user_log_filter .= " DATE(ull.timestamp) >= '$login_date_from'";
}

if (isset($_GET['login_date_to']) && !empty($_GET['login_date_to'])) {
    $login_date_to = mysqli_real_escape_string($conn, $_GET['login_date_to']);
    $user_log_filter .= empty($user_log_filter) ? " WHERE" : " AND";
    $user_log_filter .= " DATE(ull.timestamp) <= '$login_date_to'";
}

// Get user login/logout logs with pagination
$user_logs_sql = "SELECT ull.*, u.name as user_name 
                  FROM user_login_logs ull 
                  LEFT JOIN user u ON ull.user_id = u.id 
                  $user_log_filter
                  ORDER BY ull.timestamp DESC LIMIT $user_records_per_page OFFSET $user_offset";
$user_logs_result = $conn->query($user_logs_sql);

// Get total count for user logs pagination
$user_count_sql = "SELECT COUNT(*) as total FROM user_login_logs ull LEFT JOIN user u ON ull.user_id = u.id $user_log_filter";
$user_count_result = $conn->query($user_count_sql);
$total_user_logs = $user_count_result->fetch_assoc()['total'];
$total_user_pages = ceil($total_user_logs / $user_records_per_page);

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_uploads,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_uploads,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_uploads,
    SUM(records_success) as total_records_processed,
    COUNT(DISTINCT uploaded_by_username) as unique_uploaders
    FROM upload_logs";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

$users_count_sql = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users
    FROM user";
$users_count_result = $conn->query($users_count_sql);
$user_counts = $users_count_result->fetch_assoc();

// Get top uploaders
$top_uploaders_sql = "SELECT 
    uploaded_by_username, 
    uploaded_by_name,
    COUNT(*) as upload_count,
    SUM(records_success) as total_records,
    MAX(upload_date) as last_upload
    FROM upload_logs 
    WHERE uploaded_by_username IS NOT NULL 
    GROUP BY uploaded_by_username, uploaded_by_name 
    ORDER BY upload_count DESC 
    LIMIT 5";
$top_uploaders_result = $conn->query($top_uploaders_sql);
// Get file records for management
$file_page = isset($_GET['file_page']) ? max(1, intval($_GET['file_page'])) : 1;
$file_records_per_page = 20;
$file_offset = ($file_page - 1) * $file_records_per_page;

$file_filter = '';
if (isset($_GET['file_table_filter']) && !empty($_GET['file_table_filter'])) {
    $table_filter = mysqli_real_escape_string($conn, $_GET['file_table_filter']);
    $file_filter .= " WHERE table_name = '$table_filter'";
}

if (isset($_GET['file_status_filter']) && !empty($_GET['file_status_filter'])) {
    $status_filter = mysqli_real_escape_string($conn, $_GET['file_status_filter']);
    $file_filter .= empty($file_filter) ? " WHERE" : " AND";
    $file_filter .= " status = '$status_filter'";
}

// Get upload logs for file management
$file_logs_sql = "SELECT * FROM upload_logs $file_filter ORDER BY upload_date DESC LIMIT $file_records_per_page OFFSET $file_offset";
$file_logs_result = $conn->query($file_logs_sql);

// Get total count for file pagination
$file_count_sql = "SELECT COUNT(*) as total FROM upload_logs $file_filter";
$file_count_result = $conn->query($file_count_sql);
$total_file_logs = $file_count_result->fetch_assoc()['total'];
$total_file_pages = ceil($total_file_logs / $file_records_per_page);


?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Panel - Student Management System</title>
    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="img/icon1.jpg">
    <!-- BOOTSTRAP STYLES-->
    <link href="css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="css/font-awesome.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    
    <style>
        .record-row {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            margin-bottom: 10px;
            border-radius: 5px;
            padding: 10px;
        }
        
        .record-field {
            margin-bottom: 10px;
        }
        
        .record-field label {
            font-weight: bold;
            color: #333;
        }
        
        .record-actions {
            text-align: right;
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .file-management-card {
            border: 2px solid rgba(1, 129, 55, 0.3);
            border-radius: 8px;
            margin-bottom: 20px;
            background: white;
        }
        
        .file-header {
            background: rgba(1, 129, 55, 0.1);
            padding: 15px;
            border-bottom: 1px solid rgba(1, 129, 55, 0.3);
            border-radius: 6px 6px 0 0;
        }
        
        .edit-mode {
            background: #fff3cd !important;
            border: 2px solid #ffc107;
        }
        
        .btn-file-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        
        .btn-file-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            color: white;
        }
        @font-face {
            font-family: Poppins;
            src: url("fonts/Poppins-Regular.ttf");
        }
        
        html * {
            font-family: "Poppins", sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(180deg, rgba(1, 129, 55, 0.95), rgba(1, 100, 40, 1));
            color: white;
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar.collapsed .sidebar-header .brand-text {
            display: none;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .sidebar-nav li {
            margin: 0;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid white;
            text-decoration: none;
        }

        .sidebar-nav i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }

        .sidebar.collapsed .sidebar-nav .nav-text {
            display: none;
        }

        .sidebar-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            background: rgba(1, 129, 55, 0.9);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            z-index: 1001;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: rgba(1, 100, 40, 1);
            color: white;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: 70px;
        }

        .admin-header {
            background: linear-gradient(45deg, rgba(1, 129, 55, 0.9), rgba(1, 100, 40, 0.9));
            color: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid rgba(1, 129, 55, 0.8);
    transition: transform 0.2s ease;
    min-height: 140px; /* Set minimum height for uniform size */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.stat-card h3 {
    color: rgba(1, 129, 55, 0.8);
    margin-bottom: 10px;
    font-size: 16px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #333;
    margin: 10px 0;
}

.stat-card small {
    display: block;
    margin-top: auto;
}

/* Ensure equal heights in each row */
.row {
    display: flex;
    flex-wrap: wrap;
}

.row > [class*='col-'] {
    display: flex;
    flex-direction: column;
}

@media (max-width: 768px) {
    .stat-card {
        min-height: 120px;
    }
    
    .stat-number {
        font-size: 24px;
    }
}
        
        .panel-custom {
            border: 1px solid rgba(1, 129, 55, 0.3);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            background: white;
            margin-bottom: 20px;
        }
        
        .panel-custom .panel-heading {
            background-color: rgba(1, 129, 55, 0.8);
            color: white;
            border-radius: 8px 8px 0 0;
            padding: 15px 20px;
        }
        
        .panel-custom .panel-body {
            padding: 20px;
        }
        
        .table-responsive {
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .status-success {
            color: #5cb85c;
            font-weight: bold;
        }
        
        .status-failed {
            color: #d9534f;
            font-weight: bold;
        }
        
        .status-active {
            color: #5cb85c;
        }
        
        .status-inactive {
            color: #d9534f;
        }
        
        .btn-custom {
            background-color: rgba(1, 129, 55, 0.8);
            border-color: rgba(1, 129, 55, 0.8);
            color: white;
        }
        
        .btn-custom:hover {
            background-color: rgba(1, 100, 40, 0.9);
            border-color: rgba(1, 100, 40, 0.9);
            color: white;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .user-badge {
            background-color: #e7f3ff;
            color: #0066cc;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .login-info-card {
            background: #f8f9fa;
            border-left: 3px solid rgba(1, 129, 55, 0.6);
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
        }

        .login-stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .login-stat {
            background: white;
            padding: 8px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            border: 1px solid #e0e0e0;
        }

        .log-entry {
            background: white;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid rgba(1, 129, 55, 0.3);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .log-time {
            color: #666;
            font-size: 0.9em;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .nav-text,
            .sidebar .brand-text {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .stat-card {
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fa fa-shield fa-2x"></i>
            <div class="brand-text">
                <h4 style="margin: 10px 0 0 0;">Admin Panel</h4>
            </div>
        </div>
        
        <ul class="sidebar-nav">
            <li>
                <a href="#" onclick="showSection('dashboard')" class="nav-link active">
                    <i class="fa fa-dashboard"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="#" onclick="showSection('accounts')" class="nav-link">
                    <i class="fa fa-users"></i>
                    <span class="nav-text">Accounts</span>
                </a>
            </li>
            <li>
            <a href="#" onclick="showSection('file-management')" class="nav-link">
                <i class="fa fa-files-o"></i>
                <span class="nav-text">File Management</span>
            </a>
        </li>
            <li>
                <a href="#" onclick="showSection('upload-logs')" class="nav-link">
                    <i class="fa fa-upload"></i>
                    <span class="nav-text">Upload Logs</span>
                </a>
            </li>
            <li>
                <a href="#" onclick="showSection('admin-logs')" class="nav-link">
                    <i class="fa fa-history"></i>
                    <span class="nav-text">Admin Logs</span>
                </a>
            </li>
            <li>
                <a href="#" onclick="showSection('user-logs')" class="nav-link">
                    <i class="fa fa-history"></i>
                    <span class="nav-text">User Logs</span>
                </a>
            </li>
        </ul>

        <div style="position: absolute; bottom: 20px; width: 100%; padding: 0 20px;">
            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; text-align: center;">
                <!--<a href="index.php" class="btn btn-sm" style="background: rgba(255,255,255,0.1); color: white; margin-bottom: 5px; width: 100%;">
                    <i class="fa fa-home"></i>
                    <span class="nav-text"> Dashboard</span>
                </a>-->
                <a href="logout.php" class="btn btn-sm" style="background: rgba(220,53,69,0.8); color: white; width: 100%;">
                    <i class="fa fa-sign-out"></i>
                    <span class="nav-text"> Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        
        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Section -->
        <div id="dashboard" class="content-section active">
            <div class="admin-header">
                <div class="row">
                    <div class="col-md-8">
                        <h2><i class="fa fa-dashboard"></i> Dashboard Overview</h2>
                        <p>Welcome, <?= htmlspecialchars($_SESSION['rainbow_name']) ?></p>
                    </div>
                    <div class="col-md-4 text-right">
                        <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 14px;">
                            <i class="fa fa-clock-o"></i> <?= date('M j, Y - H:i') ?>
                        </span>
                    </div>
                </div>
            </div>

          <!-- Statistics Cards -->
<div class="row">
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="stat-card">
            <h3><i class="fa fa-users"></i> Total Users</h3>
            <div class="stat-number"><?= $user_counts['total_users'] ?></div>
            <small class="text-muted">
                Active: <?= $user_counts['active_users'] ?> | 
                Inactive: <?= $user_counts['inactive_users'] ?>
            </small>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="stat-card">
            <h3><i class="fa fa-upload"></i> Total Uploads</h3>
            <div class="stat-number"><?= $stats['total_uploads'] ?></div>
            <small class="text-muted">
                Success: <?= $stats['successful_uploads'] ?> | 
                Failed: <?= $stats['failed_uploads'] ?>
            </small>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="stat-card">
            <h3><i class="fa fa-user-plus"></i> Active Uploaders</h3>
            <div class="stat-number"><?= $stats['unique_uploaders'] ?></div>
            <small class="text-muted">Unique users who uploaded</small>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="stat-card">
            <h3><i class="fa fa-database"></i> Records Processed</h3>
            <div class="stat-number"><?= number_format($stats['total_records_processed']) ?></div>
            <small class="text-muted">Total records in system</small>
        </div>
    </div>
</div>



            <!-- Quick Actions -->
            <div class="panel panel-custom">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-bolt"></i> Quick Actions
                    </h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-3">
                            <button onclick="showSection('accounts')" class="btn btn-custom btn-block btn-lg">
                                <i class="fa fa-user-plus"></i><br>
                                Create New User
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button onclick="showSection('upload-logs')" class="btn btn-custom btn-block btn-lg">
                                <i class="fa fa-list-alt"></i><br>
                                View Upload Logs
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button onclick="showSection('admin-logs')" class="btn btn-custom btn-block btn-lg">
                                <i class="fa fa-history"></i><br>
                                Admin Activity
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button onclick="showSection('user-logs')" class="btn btn-custom btn-block btn-lg">
                                <i class="fa fa-sign-in"></i><br>
                                Login Activity
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accounts Section -->
        <div id="accounts" class="content-section">
            <div class="admin-header">
                <h2><i class="fa fa-users"></i> User Account Management</h2>
                <p>Create, manage, and monitor user accounts</p>
            </div>

            <div class="row">
                <!-- User Creation Panel -->
                <div class="col-md-6">
                    <div class="panel panel-custom">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <i class="fa fa-user-plus"></i> Create New User Account
                            </h3>
                        </div>
                        <div class="panel-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>Username *</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Role *</label>
                                    <select name="role" class="form-control" required>
                                        
                                        <option value="user">User</option>
                                        
                                    </select>
                                </div>
                                <div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" id="createPassword" class="form-control" required minlength="8" maxlength="16" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,16}$">
            <div class="password-strength" id="passwordStrength" style="margin-top: 5px; font-size: 0.85em;"></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Confirm Password *</label>
            <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required minlength="8" maxlength="16">
            <div id="passwordMatch" style="margin-top: 5px; font-size: 0.85em;"></div>
        </div>
    </div>
</div>
<div class="alert alert-info" style="font-size: 0.9em; margin-top: 10px;">
    <i class="fa fa-info-circle"></i> <strong>Password Requirements:</strong>
    <ul style="margin: 5px 0 0 20px;">
        <li>8-16 characters in length</li>
        <li>At least one uppercase letter (A-Z)</li>
        <li>At least one lowercase letter (a-z)</li>
        <li>At least one number (0-9)</li>
    </ul>
</div>
                                <button type="submit" name="create_user" class="btn btn-custom">
                                    <i class="fa fa-plus"></i> Create User Account
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Current Users List -->
                <div class="col-md-6">
                    <div class="panel panel-custom">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <i class="fa fa-users"></i> Current Users
                            </h3>
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive" style="max-height: 450px;">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>User Info</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Activity</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($user = $users_result->fetch_assoc()): ?>
                                            <tr class="<?= ($user['status'] == 'inactive') ? 'warning' : '' ?>">
                                                <td>
                                                    <strong><?= htmlspecialchars($user['username']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($user['name']) ?></small><br>
                                                    <small class="text-info"><?= htmlspecialchars($user['email']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="label label-<?= $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'moderator' ? 'warning' : 'primary') ?>">
                                                        <?= htmlspecialchars(ucfirst($user['role'] ?? 'user')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-<?= $user['status'] ?? 'active' ?>">
                                                        <i class="fa fa-<?= ($user['status'] ?? 'active') == 'active' ? 'check-circle' : 'times-circle' ?>"></i>
                                                        <?= htmlspecialchars(ucfirst($user['status'] ?? 'active')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="login-info-card">
                                                        <div class="login-stats">
                                                            <span class="login-stat">
                                                                <i class="fa fa-sign-in text-success"></i> <?= $user['total_logins'] ?>
                                                            </span>
                                                            <span class="login-stat">
                                                                <i class="fa fa-sign-out text-warning"></i> <?= $user['total_logouts'] ?>
                                                            </span>
                                                            <?php if ($user['failed_attempts'] > 0): ?>
                                                                <span class="login-stat">
                                                                    <i class="fa fa-times text-danger"></i> <?= $user['failed_attempts'] ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <?php if ($user['last_login']): ?>
                                                                Last: <?= date('M j, H:i', strtotime($user['last_login'])) ?>
                                                            <?php else: ?>
                                                                Never logged in
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($user['username'] != 'admin'): ?>
                                                        <div class="btn-group">
                                                            
                                                            <button class="btn btn-xs btn-primary" onclick="openUpdateModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>', '<?= $user['role'] ?? 'user' ?>')" title="Update User">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                                            
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Protected</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Replace the Update User Modal with this version: -->
<div class="modal fade" id="updateUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-edit"></i> Update User Account
                </h4>
            </div>
            <form method="POST" id="updateUserForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="updateUserId">
                    <input type="hidden" name="update_user" value="1">
                    
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="new_username" id="updateUsername" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" id="updatePassword" class="form-control" placeholder="Leave empty to keep current password">
                        <small class="text-muted">Minimum 8 characters (leave empty to keep current password)</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" id="updateRole" class="form-control" required>
                            <option value="user">User</option>
                            
                        </select>
                        <small class="text-muted">Select the user's access level and permissions</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
        </div>
        <div id="file-management" class="content-section">
        <div class="admin-header">
            <h2><i class="fa fa-files-o"></i> Uploaded File Management</h2>
            <p>View all uploaded file data</p>
        </div>

        <div class="panel panel-custom">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-files-o"></i> Uploaded Files Data Management
                </h3>
            </div>
            <div class="panel-body">
                <!-- File Filters -->
                <div class="filter-section">
                    <form method="GET" class="form-inline">
                        <input type="hidden" name="section" value="file-management">
                        <div class="form-group">
                            <label>Table:</label>
                            <select name="file_table_filter" class="form-control">
                                <option value="">All Tables</option>
                                <?php
                                // Get unique table names
                                $tables_sql = "SELECT DISTINCT table_name FROM upload_logs ORDER BY table_name";
                                $tables_result = $conn->query($tables_sql);
                                while ($table = $tables_result->fetch_assoc()) {
                                    $selected = (isset($_GET['file_table_filter']) && $_GET['file_table_filter'] == $table['table_name']) ? 'selected' : '';
                                    echo "<option value='{$table['table_name']}' $selected>{$table['table_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="file_status_filter" class="form-control">
                                <option value="">All Status</option>
                                <option value="success" <?= (isset($_GET['file_status_filter']) && $_GET['file_status_filter'] == 'success') ? 'selected' : '' ?>>Success</option>
                                <option value="failed" <?= (isset($_GET['file_status_filter']) && $_GET['file_status_filter'] == 'failed') ? 'selected' : '' ?>>Failed</option>
                                <option value="deleted" <?= (isset($_GET['file_status_filter']) && $_GET['file_status_filter'] == 'deleted') ? 'selected' : '' ?>>Deleted</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-custom">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                        <button type="button" onclick="clearFileFilters()" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Clear
                        </button>
                    </form>
                </div>

                <!-- File List -->
                <?php if ($file_logs_result && $file_logs_result->num_rows > 0): ?>
                    <?php while ($file_log = $file_logs_result->fetch_assoc()): ?>
                        <div class="file-management-card">
                            <div class="file-header">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4><i class="fa fa-file-text"></i> <?= htmlspecialchars($file_log['filename']) ?></h4>
                                        <p class="text-muted">
                                            Table: <code><?= htmlspecialchars($file_log['table_name']) ?></code> | 
                                            Uploaded: <?= date('M j, Y H:i', strtotime($file_log['upload_date'])) ?> |
                                            By: <?= htmlspecialchars($file_log['uploaded_by_name'] ?? $file_log['uploaded_by_username'] ?? 'Unknown') ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <span class="label label-<?= $file_log['status'] == 'success' ? 'success' : ($file_log['status'] == 'deleted' ? 'default' : 'danger') ?>">
                                            <?= htmlspecialchars(ucfirst($file_log['status'])) ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            Records: <?= $file_log['records_success'] ?> success, <?= $file_log['records_error'] ?> errors
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- File Actions -->
                                <?php if ($file_log['status'] == 'success'): ?>
                                    <div style="margin-top: 10px;">
                                        <button onclick="viewFileData(<?= $file_log['log_id'] ?>, '<?= $file_log['table_name'] ?>')" 
                                                class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i> View Data
                                        </button>
                                        <!--<button onclick="confirmDeleteFileData(<?= $file_log['log_id'] ?>, '<?= htmlspecialchars($file_log['filename']) ?>')" 
                                                class="btn btn-sm btn-file-danger">
                                            <i class="fa fa-trash"></i> Delete All Data
                                        </button>-->
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Data Display Area (Initially Hidden) -->
                            <div id="file-data-<?= $file_log['log_id'] ?>" class="panel-body" style="display: none;">
                                <div id="records-container-<?= $file_log['log_id'] ?>">
                                    <!-- Records will be loaded here via AJAX -->
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <!-- File Pagination -->
                    <?php if ($total_file_pages > 1): ?>
                        <nav aria-label="File logs pagination">
                            <ul class="pagination">
                                <?php if ($file_page > 1): ?>
                                    <li><a href="?section=file-management&file_page=<?= $file_page - 1 ?><?= isset($_GET['file_table_filter']) ? '&file_table_filter=' . $_GET['file_table_filter'] : '' ?><?= isset($_GET['file_status_filter']) ? '&file_status_filter=' . $_GET['file_status_filter'] : '' ?>">&laquo; Previous</a></li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $file_page - 2); $i <= min($total_file_pages, $file_page + 2); $i++): ?>
                                    <li class="<?= $i == $file_page ? 'active' : '' ?>">
                                        <a href="?section=file-management&file_page=<?= $i ?><?= isset($_GET['file_table_filter']) ? '&file_table_filter=' . $_GET['file_table_filter'] : '' ?><?= isset($_GET['file_status_filter']) ? '&file_status_filter=' . $_GET['file_status_filter'] : '' ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($file_page < $total_file_pages): ?>
                                    <li><a href="?section=file-management&file_page=<?= $file_page + 1 ?><?= isset($_GET['file_table_filter']) ? '&file_table_filter=' . $_GET['file_table_filter'] : '' ?><?= isset($_GET['file_status_filter']) ? '&file_status_filter=' . $_GET['file_status_filter'] : '' ?>">Next &raquo;</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="text-center text-muted" style="padding: 50px;">
                        <i class="fa fa-file-o fa-3x" style="opacity: 0.3;"></i>
                        <h4>No uploaded files found</h4>
                        <p>No files have been uploaded yet or they don't match your filter criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Hidden Forms for File Management -->
    <form id="deleteFileForm" method="POST" style="display: none;">
        <input type="hidden" name="log_id" id="deleteLogId">
        <input type="hidden" name="delete_file_data" value="1">
    </form>

    <form id="editRecordForm" method="POST" style="display: none;">
        <input type="hidden" name="record_id" id="editRecordId">
        <input type="hidden" name="table_name" id="editTableName">
        <input type="hidden" name="edit_record" value="1">
        <div id="editRecordFields"></div>
    </form>

    <form id="deleteRecordForm" method="POST" style="display: none;">
        <input type="hidden" name="record_id" id="deleteRecordId">
        <input type="hidden" name="table_name" id="deleteTableName">
        <input type="hidden" name="delete_record" value="1">
    </form>
        <!-- Upload Logs Section -->
        <div id="upload-logs" class="content-section">
            <div class="admin-header">
                <h2><i class="fa fa-upload"></i> Upload Activity Logs</h2>
                <p>Monitor all file upload activities with user tracking</p>
            </div>

            <div class="panel panel-custom">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-list-alt"></i> Upload Activity Logs with User Tracking
                    </h3>
                </div>
                <div class="panel-body">
                    <!-- Filters -->
                    <div class="filter-section">
                        <form method="GET" class="form-inline">
                            <input type="hidden" name="section" value="upload-logs">
                            <div class="form-group">
                                <label>Status:</label>
                                <select name="status_filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="success" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'success') ? 'selected' : '' ?>>Success</option>
                                    <option value="failed" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'failed') ? 'selected' : '' ?>>Failed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>User:</label>
                                <input type="text" name="user_filter" class="form-control" placeholder="Username" value="<?= $_GET['user_filter'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <label>From:</label>
                                <input type="date" name="date_from" class="form-control" value="<?= $_GET['date_from'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <label>To:</label>
                                <input type="date" name="date_to" class="form-control" value="<?= $_GET['date_to'] ?? '' ?>">
                            </div>
                            <button type="submit" class="btn btn-custom">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                            <button type="button" onclick="clearUploadFilters()" class="btn btn-default">
                                <i class="fa fa-refresh"></i> Clear
                            </button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Uploaded By</th>
                                    <th>Year/Semester</th>
                                    <th>Table Name</th>
                                    <th>Filename</th>
                                    <th>Records</th>
                                    <th>Success/Errors</th>
                                    <th>Upload Date</th>
                                    <th>Status</th>
                                    <th>Error Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($upload_logs_result && $upload_logs_result->num_rows > 0): ?>
                                    <?php while ($log = $upload_logs_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['log_id']) ?></td>
                                            <td>
                                                <?php if ($log['uploaded_by_username']): ?>
                                                    <div style="background: #f8f9fa; padding: 8px; border-radius: 4px; border-left: 3px solid rgba(1, 129, 55, 0.6);">
                                                        <strong><?= htmlspecialchars($log['uploaded_by_name'] ?? $log['uploaded_by_username']) ?></strong><br>
                                                        <small class="user-badge">@<?= htmlspecialchars($log['uploaded_by_username']) ?></small>
                                                        <?php if ($log['user_ip_address']): ?>
                                                            <br><small class="text-muted">IP: <?= htmlspecialchars($log['user_ip_address']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Unknown User</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($log['year']) ?></strong><br>
                                                <small class="text-muted">Sem <?= htmlspecialchars($log['semester']) ?></small>
                                            </td>
                                            <td><code><?= htmlspecialchars($log['table_name']) ?></code></td>
                                            <td><?= htmlspecialchars($log['filename']) ?></td>
                                            <td class="text-center"><?= htmlspecialchars($log['records_processed']) ?></td>
                                            <td>
                                                <span class="status-success"><?= htmlspecialchars($log['records_success']) ?></span> / 
                                                <span class="status-failed"><?= htmlspecialchars($log['records_error']) ?></span>
                                            </td>
                                            <td><?= date('M j, Y H:i', strtotime($log['upload_date'])) ?></td>
                                            <td>
                                                <span class="status-<?= $log['status'] ?>">
                                                    <i class="fa fa-<?= $log['status'] == 'success' ? 'check-circle' : 'times-circle' ?>"></i>
                                                    <?= htmlspecialchars(ucfirst($log['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($log['error_message']): ?>
                                                    <span class="text-danger" title="<?= htmlspecialchars($log['error_message']) ?>">
                                                        <?= htmlspecialchars(substr($log['error_message'], 0, 50)) ?><?= strlen($log['error_message']) > 50 ? '...' : '' ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No upload logs found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Upload logs pagination">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li><a href="?section=upload-logs&log_page=<?= $page - 1 ?><?= isset($_GET['status_filter']) ? '&status_filter=' . $_GET['status_filter'] : '' ?><?= isset($_GET['user_filter']) ? '&user_filter=' . $_GET['user_filter'] : '' ?><?= isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' ?><?= isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' ?>">&laquo; Previous</a></li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="<?= $i == $page ? 'active' : '' ?>">
                                        <a href="?section=upload-logs&log_page=<?= $i ?><?= isset($_GET['status_filter']) ? '&status_filter=' . $_GET['status_filter'] : '' ?><?= isset($_GET['user_filter']) ? '&user_filter=' . $_GET['user_filter'] : '' ?><?= isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' ?><?= isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li><a href="?section=upload-logs&log_page=<?= $page + 1 ?><?= isset($_GET['status_filter']) ? '&status_filter=' . $_GET['status_filter'] : '' ?><?= isset($_GET['user_filter']) ? '&user_filter=' . $_GET['user_filter'] : '' ?><?= isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' ?><?= isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' ?>">Next &raquo;</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Admin Logs Section -->
        <div id="admin-logs" class="content-section">
            <div class="admin-header">
                <h2><i class="fa fa-history"></i> Admin Activity Logs</h2>
                <p>Track all administrative actions and system changes</p>
            </div>

            <div class="panel panel-custom">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-history"></i> Admin Activity Logs
                    </h3>
                </div>
                <div class="panel-body">
                    <!-- Admin Filters -->
                    <div class="filter-section">
                        <form method="GET" class="form-inline">
                            <input type="hidden" name="section" value="admin-logs">
                            <div class="form-group">
                                <label>Admin:</label>
                                <input type="text" name="admin_user_filter" class="form-control" placeholder="Admin Username" value="<?= $_GET['admin_user_filter'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <label>Target User:</label>
                                <input type="text" name="admin_target_filter" class="form-control" placeholder="Target User" value="<?= $_GET['admin_target_filter'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <label>From:</label>
                                <input type="date" name="admin_date_from" class="form-control" value="<?= $_GET['admin_date_from'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <label>To:</label>
                                <input type="date" name="admin_date_to" class="form-control" value="<?= $_GET['admin_date_to'] ?? '' ?>">
                            </div>
                            <button type="submit" class="btn btn-custom">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                            <button type="button" onclick="clearAdminFilters()" class="btn btn-default">
                                <i class="fa fa-refresh"></i> Clear
                            </button>
                        </form>
                    </div>

                    <div style="max-height: 500px; overflow-y: auto;">
                        <?php if ($admin_logs_result && $admin_logs_result->num_rows > 0): ?>
                            <?php while ($admin_log = $admin_logs_result->fetch_assoc()): ?>
                                <div class="log-entry">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <strong><?= htmlspecialchars($admin_log['admin_username']) ?></strong>
                                            <?= htmlspecialchars($admin_log['activity']) ?>
                                            <?php if ($admin_log['target_user']): ?>
                                                <span class="text-info">(Target: <?= htmlspecialchars($admin_log['target_user']) ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <span class="log-time">
                                                <i class="fa fa-clock-o"></i>
                                                <?= date('M j, Y H:i:s', strtotime($admin_log['timestamp'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php if ($admin_log['ip_address']): ?>
                                        <small class="text-muted">
                                            IP: <?= htmlspecialchars($admin_log['ip_address']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fa fa-info-circle fa-3x" style="opacity: 0.3;"></i>
                                <h4>No admin activity logs found</h4>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Admin Pagination -->
                    <?php if ($total_admin_pages > 1): ?>
                        <nav aria-label="Admin logs pagination" style="margin-top: 20px;">
                            <ul class="pagination">
                                <?php if ($admin_page > 1): ?>
                                    <li><a href="?section=admin-logs&admin_page=<?= $admin_page - 1 ?><?= isset($_GET['admin_user_filter']) ? '&admin_user_filter=' . $_GET['admin_user_filter'] : '' ?><?= isset($_GET['admin_target_filter']) ? '&admin_target_filter=' . $_GET['admin_target_filter'] : '' ?><?= isset($_GET['admin_date_from']) ? '&admin_date_from=' . $_GET['admin_date_from'] : '' ?><?= isset($_GET['admin_date_to']) ? '&admin_date_to=' . $_GET['admin_date_to'] : '' ?>">&laquo; Previous</a></li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $admin_page - 2); $i <= min($total_admin_pages, $admin_page + 2); $i++): ?>
                                    <li class="<?= $i == $admin_page ? 'active' : '' ?>">
                                        <a href="?section=admin-logs&admin_page=<?= $i ?><?= isset($_GET['admin_user_filter']) ? '&admin_user_filter=' . $_GET['admin_user_filter'] : '' ?><?= isset($_GET['admin_target_filter']) ? '&admin_target_filter=' . $_GET['admin_target_filter'] : '' ?><?= isset($_GET['admin_date_from']) ? '&admin_date_from=' . $_GET['admin_date_from'] : '' ?><?= isset($_GET['admin_date_to']) ? '&admin_date_to=' . $_GET['admin_date_to'] : '' ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($admin_page < $total_admin_pages): ?>
                                    <li><a href="?section=admin-logs&admin_page=<?= $admin_page + 1 ?><?= isset($_GET['admin_user_filter']) ? '&admin_user_filter=' . $_GET['admin_user_filter'] : '' ?><?= isset($_GET['admin_target_filter']) ? '&admin_target_filter=' . $_GET['admin_target_filter'] : '' ?><?= isset($_GET['admin_date_from']) ? '&admin_date_from=' . $_GET['admin_date_from'] : '' ?><?= isset($_GET['admin_date_to']) ? '&admin_date_to=' . $_GET['admin_date_to'] : '' ?>">Next &raquo;</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Login/Logout Logs Section -->
        <div id="user-logs" class="content-section">
            <div class="admin-header">
                <h2><i class="fa fa-sign-in"></i> User Login/Logout Activity</h2>
                <p>Monitor user authentication activities and session management</p>
            </div>

            <div class="panel panel-custom">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-sign-in"></i> User Login/Logout Logs
                    </h3>
                </div>
                <div class="panel-body">
                    <!-- User Filters -->
                    <div class="filter-section">
                        <form method="GET" class="form-inline">
                            <input type="hidden" name="section" value="user-logs">
                            <div class="form-group">
                                <label>User:</label>
                                <input type="text" name="login_user_filter" class="form-control" placeholder="Username" value="<?= $_GET['login_user_filter'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <label>Status:</label>
                                <select name="login_status_filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="login" <?= (isset($_GET['login_status_filter']) && $_GET['login_status_filter'] == 'login') ? 'selected' : '' ?>>Login</option>
                                    <option value="logout" <?= (isset($_GET['login_status_filter']) && $_GET['login_status_filter'] == 'logout') ? 'selected' : '' ?>>Logout</option>
                                    <option value="failed" <?= (isset($_GET['login_status_filter']) && $_GET['login_status_filter'] == 'failed') ? 'selected' : '' ?>>Failed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>From:</label>
                                <input type="date" name="login_date_from" class="form-control" value="<?= $_GET['login_date_from'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <label>To:</label>
                                <input type="date" name="login_date_to" class="form-control" value="<?= $_GET['login_date_to'] ?? '' ?>">
                            </div>
                            <button type="submit" class="btn btn-custom">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                            <button type="button" onclick="clearUserFilters()" class="btn btn-default">
                                <i class="fa fa-refresh"></i> Clear
                            </button>
                        </form>
                    </div>

                    <div style="max-height: 500px; overflow-y: auto;">
                        <?php if ($user_logs_result && $user_logs_result->num_rows > 0): ?>
                            <?php while ($user_log = $user_logs_result->fetch_assoc()): ?>
                                <div class="log-entry">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <span class="label label-<?= $user_log['login_status'] == 'login' ? 'success' : ($user_log['login_status'] == 'logout' ? 'warning' : 'danger') ?>">
                                                <i class="fa fa-<?= $user_log['login_status'] == 'login' ? 'sign-in' : ($user_log['login_status'] == 'logout' ? 'sign-out' : 'times') ?>"></i>
                                                <?= strtoupper($user_log['login_status']) ?>
                                            </span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong><?= htmlspecialchars($user_log['username']) ?></strong>
                                            <?php if ($user_log['user_name']): ?>
                                                <small class="text-muted">(<?= htmlspecialchars($user_log['user_name']) ?>)</small>
                                            <?php endif; ?>
                                            <?php if ($user_log['additional_info']): ?>
                                                <br><small class="text-info"><?= htmlspecialchars($user_log['additional_info']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <span class="log-time">
                                                <i class="fa fa-clock-o"></i>
                                                <?= date('M j, Y H:i:s', strtotime($user_log['timestamp'])) ?>
                                            </span>
                                            <br><small class="text-muted">IP: <?= htmlspecialchars($user_log['ip_address']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fa fa-info-circle fa-3x" style="opacity: 0.3;"></i>
                                <h4>No user activity logs found</h4>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- User Pagination -->
                    <?php if ($total_user_pages > 1): ?>
                        <nav aria-label="User logs pagination" style="margin-top: 20px;">
                            <ul class="pagination">
                                <?php if ($user_page > 1): ?>
                                    <li><a href="?section=user-logs&user_page=<?= $user_page - 1 ?><?= isset($_GET['login_user_filter']) ? '&login_user_filter=' . $_GET['login_user_filter'] : '' ?><?= isset($_GET['login_status_filter']) ? '&login_status_filter=' . $_GET['login_status_filter'] : '' ?><?= isset($_GET['login_date_from']) ? '&login_date_from=' . $_GET['login_date_from'] : '' ?><?= isset($_GET['login_date_to']) ? '&login_date_to=' . $_GET['login_date_to'] : '' ?>">&laquo; Previous</a></li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $user_page - 2); $i <= min($total_user_pages, $user_page + 2); $i++): ?>
                                    <li class="<?= $i == $user_page ? 'active' : '' ?>">
                                        <a href="?section=user-logs&user_page=<?= $i ?><?= isset($_GET['login_user_filter']) ? '&login_user_filter=' . $_GET['login_user_filter'] : '' ?><?= isset($_GET['login_status_filter']) ? '&login_status_filter=' . $_GET['login_status_filter'] : '' ?><?= isset($_GET['login_date_from']) ? '&login_date_from=' . $_GET['login_date_from'] : '' ?><?= isset($_GET['login_date_to']) ? '&login_date_to=' . $_GET['login_date_to'] : '' ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($user_page < $total_user_pages): ?>
                                    <li><a href="?section=user-logs&user_page=<?= $user_page + 1 ?><?= isset($_GET['login_user_filter']) ? '&login_user_filter=' . $_GET['login_user_filter'] : '' ?><?= isset($_GET['login_status_filter']) ? '&login_status_filter=' . $_GET['login_status_filter'] : '' ?><?= isset($_GET['login_date_from']) ? '&login_date_from=' . $_GET['login_date_from'] : '' ?><?= isset($_GET['login_date_to']) ? '&login_date_to=' . $_GET['login_date_to'] : '' ?>">Next &raquo;</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Forms for AJAX Actions -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="user_id" id="statusUserId">
        <input type="hidden" name="status" id="statusValue">
        <input type="hidden" name="update_status" value="1">
    </form>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="user_id" id="deleteUserId">
        <input type="hidden" name="delete_user" value="1">
    </form>

    <!-- Scripts -->
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.js"></script>

    <script>
       // Replace the openUpdateModal JavaScript function with this version:
function openUpdateModal(userId, username, role) {
    document.getElementById('updateUserId').value = userId;
    document.getElementById('updateUsername').value = username;
    document.getElementById('updateRole').value = role || 'user';
    document.getElementById('updatePassword').value = ''; // Clear password field
    $('#updateUserModal').modal('show');
}

// Keep the existing deleteUser function as is
function deleteUser(userId, username) {
    if (confirm('Are you sure you want to delete user "' + username + '"? This action cannot be undone.')) {
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('deleteForm').submit();
    }
}
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        function showSection(sectionId) {
            // Hide all sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });

            // Show selected section
            document.getElementById(sectionId).classList.add('active');

            // Update navigation active state
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.classList.remove('active');
            });
            event.target.closest('.nav-link').classList.add('active');

            // Update URL without refresh
            const url = new URL(window.location);
            url.searchParams.set('section', sectionId);
            window.history.pushState({}, '', url);
        }

        function toggleUserStatus(userId, newStatus) {
            if (confirm('Are you sure you want to change this user\'s status to ' + newStatus + '?')) {
                document.getElementById('statusUserId').value = userId;
                document.getElementById('statusValue').value = newStatus;
                document.getElementById('statusForm').submit();
            }
        }

        function deleteUser(userId, username) {
            if (confirm('Are you sure you want to delete user "' + username + '"? This action cannot be undone.')) {
                document.getElementById('deleteUserId').value = userId;
                document.getElementById('deleteForm').submit();
            }
        }

        function clearUploadFilters() {
            window.location.href = 'admin.php?section=upload-logs';
        }

        function clearAdminFilters() {
            window.location.href = 'admin.php?section=admin-logs';
        }

        function clearUserFilters() {
            window.location.href = 'admin.php?section=user-logs';
        }

        // Check URL parameters to show appropriate section on load
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const section = urlParams.get('section');
            
            if (section) {
                // Hide all sections
                const sections = document.querySelectorAll('.content-section');
                sections.forEach(section => {
                    section.classList.remove('active');
                });

                // Show selected section
                const targetSection = document.getElementById(section);
                if (targetSection) {
                    targetSection.classList.add('active');
                }

                // Update navigation active state
                const navLinks = document.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.classList.remove('active');
                });
                
                const activeLink = document.querySelector(`[onclick="showSection('${section}')"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                }
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('alert-success')) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);

        // Responsive sidebar for mobile
        if (window.innerWidth <= 768) {
            document.getElementById('sidebar').classList.add('collapsed');
            document.getElementById('mainContent').classList.add('expanded');
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.add('collapsed');
                document.getElementById('mainContent').classList.add('expanded');
            }
        });
    </script>
    <script>
        // [All your existing JavaScript functions...]
        
        // File Management Functions
        function clearFileFilters() {
            window.location.href = 'admin.php?section=file-management';
        }

        function confirmDeleteFileData(logId, filename) {
            if (confirm('Are you sure you want to delete ALL DATA from the file "' + filename + '"?\n\nThis will permanently delete all records that were uploaded from this file.\n\nThis action cannot be undone!')) {
                document.getElementById('deleteLogId').value = logId;
                document.getElementById('deleteFileForm').submit();
            }
        }

        function viewFileData(logId, tableName) {
            const container = document.getElementById('file-data-' + logId);
            const recordsContainer = document.getElementById('records-container-' + logId);
            
            if (container.style.display === 'none') {
                container.style.display = 'block';
                loadFileRecords(logId, tableName);
            } else {
                container.style.display = 'none';
            }
        }

        function loadFileRecords(logId, tableName) {
            const recordsContainer = document.getElementById('records-container-' + logId);
            recordsContainer.innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading records...</div>';
            
            // AJAX call to load records
            $.ajax({
                url: 'load_file_records.php',
                method: 'POST',
                data: {
                    log_id: logId,
                    table_name: tableName
                },
                success: function(response) {
                    recordsContainer.innerHTML = response;
                },
                error: function() {
                    recordsContainer.innerHTML = '<div class="alert alert-danger">Error loading records</div>';
                }
            });
        }

        function editRecord(recordId, tableName) {
            if (confirm('Are you sure you want to edit this record?')) {
                const row = document.getElementById('record-row-' + recordId);
                row.classList.add('edit-mode');
                
                // Convert display fields to input fields
                const fields = row.querySelectorAll('[data-field]');
                fields.forEach(field => {
                    const fieldName = field.getAttribute('data-field');
                    const currentValue = field.textContent;
                    field.innerHTML = '<input type="text" name="' + fieldName + '" value="' + currentValue + '" class="form-control">';
                });
                
                // Show save/cancel buttons
                const actionsDiv = row.querySelector('.record-actions');
                actionsDiv.innerHTML = `
                    <button onclick="saveRecord(${recordId}, '${tableName}')" class="btn btn-sm btn-success">
                        <i class="fa fa-save"></i> Save
                    </button>
                    <button onclick="cancelEdit(${recordId}, '${tableName}')" class="btn btn-sm btn-default">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                `;
            }
        }

        function saveRecord(recordId, tableName) {
            const row = document.getElementById('record-row-' + recordId);
            const inputs = row.querySelectorAll('input[type="text"]');
            
            // Prepare form data
            document.getElementById('editRecordId').value = recordId;
            document.getElementById('editTableName').value = tableName;
            
            const fieldsContainer = document.getElementById('editRecordFields');
            fieldsContainer.innerHTML = '';
            
            inputs.forEach(input => {
                const hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = input.name;
                hiddenField.value = input.value;
                fieldsContainer.appendChild(hiddenField);
            });
            
            document.getElementById('editRecordForm').submit();
        }

        function cancelEdit(recordId, tableName) {
            // Reload the records to cancel edit mode
            const logId = document.querySelector('[id^="records-container-"]').id.split('-')[2];
            loadFileRecords(logId, tableName);
        }

        function deleteRecord(recordId, tableName) {
            if (confirm('Are you sure you want to delete this record?\n\nThis action cannot be undone!')) {
                document.getElementById('deleteRecordId').value = recordId;
                document.getElementById('deleteTableName').value = tableName;
                document.getElementById('deleteRecordForm').submit();
            }
        }
    </script>
</body>
</html>>