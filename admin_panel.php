<?php
session_start();
include("php/dbconnect.php");

// Check if user is admin
if (!isset($_SESSION['rainbow_username']) || $_SESSION['rainbow_username'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}

$error = '';
$success = '';

// Handle user creation
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
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Check if username already exists
        $check_sql = "SELECT * FROM user WHERE username = '$username'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $error = 'Username already exists';
        } else {
            // Insert new user
            $hashed_password = md5($password);
            $insert_sql = "INSERT INTO user (username, password, name, email, role, created_date, status) 
                          VALUES ('$username', '$hashed_password', '$name', '$email', '$role', NOW(), 'active')";
            
            if ($conn->query($insert_sql)) {
                $success = 'User created successfully';
                
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

// Handle user status update
if (isset($_POST['update_status'])) {
    $user_id = intval($_POST['user_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_sql = "UPDATE user SET status = '$status' WHERE id = $user_id";
    if ($conn->query($update_sql)) {
        $success = 'User status updated successfully';
        
        // Log admin activity
        $admin_id = $_SESSION['rainbow_uid'];
        $activity = "Updated user status to: $status";
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

// Get all users
$users_sql = "SELECT * FROM user ORDER BY created_date DESC";
$users_result = $conn->query($users_sql);

// Get admin activity logs
$admin_logs_sql = "SELECT * FROM admin_activity_logs ORDER BY timestamp DESC LIMIT 100";
$admin_logs_result = $conn->query($admin_logs_sql);

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_uploads,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_uploads,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_uploads,
    SUM(records_success) as total_records_processed
    FROM upload_logs";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

$users_count_sql = "SELECT COUNT(*) as total_users FROM user";
$users_count_result = $conn->query($users_count_sql);
$total_users = $users_count_result->fetch_assoc()['total_users'];
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Panel - Student Management System</title>

    <!-- BOOTSTRAP STYLES-->
    <link href="css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="css/font-awesome.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    
    <style>
        @font-face {
            font-family: Poppins;
            src: url("fonts/Poppins-Regular.ttf");
        }
        
        html * {
            font-family: "Poppins", sans-serif;
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
        }
        
        .stat-card h3 {
            color: rgba(1, 129, 55, 0.8);
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .panel-custom {
            border: 1px solid rgba(1, 129, 55, 0.3);
        }
        
        .panel-custom .panel-heading {
            background-color: rgba(1, 129, 55, 0.8);
            color: white;
        }
        
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
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
        
        .log-entry {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        
        .log-time {
            color: #666;
            font-size: 0.9em;
        }
        
        .pagination {
            justify-content: center;
        }
        
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .admin-header {
                text-align: center;
            }
            
            .stat-card {
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid" style="padding: 20px;">
        <!-- Header -->
        <div class="admin-header">
            <div class="row">
                <div class="col-md-8">
                    <h2><i class="fa fa-cogs"></i> Admin Control Panel</h2>
                    <p>Welcome, <?= htmlspecialchars($_SESSION['rainbow_name']) ?> | System Administrator</p>
                </div>
                <div class="col-md-4 text-right">
                    <a href="index.php" class="btn btn-light">
                        <i class="fa fa-home"></i> Back to Dashboard
                    </a>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fa fa-sign-out"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fa fa-users"></i> Total Users</h3>
                    <div class="stat-number"><?= $total_users ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fa fa-upload"></i> Total Uploads</h3>
                    <div class="stat-number"><?= $stats['total_uploads'] ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fa fa-check-circle"></i> Successful Uploads</h3>
                    <div class="stat-number"><?= $stats['successful_uploads'] ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="fa fa-database"></i> Records Processed</h3>
                    <div class="stat-number"><?= number_format($stats['total_records_processed']) ?></div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- User Management Panel -->
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
                                    <option value="">Select Role</option>
                                    <option value="user">Regular User</option>
                                    <option value="moderator">Moderator</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Password *</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Confirm Password *</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                </div>
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
                        <div class="table-responsive" style="max-height: 350px;">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $users_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td>
                                                <span class="label label-<?= $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'moderator' ? 'warning' : 'primary') ?>">
                                                    <?= htmlspecialchars(ucfirst($user['role'] ?? 'user')) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-<?= $user['status'] ?? 'active' ?>">
                                                    <?= htmlspecialchars(ucfirst($user['status'] ?? 'active')) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['username'] != 'admin'): ?>
                                                    <div class="btn-group">
                                                        <button class="btn btn-xs btn-warning" onclick="toggleUserStatus(<?= $user['id'] ?>, '<?= $user['status'] == 'active' ? 'inactive' : 'active' ?>')">
                                                            <i class="fa fa-<?= $user['status'] == 'active' ? 'pause' : 'play' ?>"></i>
                                                        </button>
                                                        <button class="btn btn-xs btn-danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                            <i class="fa fa-trash"></i>
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

        <!-- Upload Logs Panel -->
        <div class="panel panel-custom">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-list-alt"></i> Upload Activity Logs
                </h3>
            </div>
            <div class="panel-body">
                <!-- Filters -->
                <div class="filter-section">
                    <form method="GET" class="form-inline">
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status_filter" class="form-control">
                                <option value="">All Status</option>
                                <option value="success" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'success') ? 'selected' : '' ?>>Success</option>
                                <option value="failed" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'failed') ? 'selected' : '' ?>>Failed</option>
                            </select>
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
                        <a href="admin.php" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Clear
                        </a>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Table Name</th>
                                <th>Filename</th>
                                <th>Records</th>
                                <th>Success</th>
                                <th>Errors</th>
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
                                        <td><?= htmlspecialchars($log['year']) ?></td>
                                        <td><?= htmlspecialchars($log['semester']) ?></td>
                                        <td><code><?= htmlspecialchars($log['table_name']) ?></code></td>
                                        <td><?= htmlspecialchars($log['filename']) ?></td>
                                        <td><?= htmlspecialchars($log['records_processed']) ?></td>
                                        <td class="status-success"><?= htmlspecialchars($log['records_success']) ?></td>
                                        <td class="status-failed"><?= htmlspecialchars($log['records_error']) ?></td>
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
                                    <td colspan="11" class="text-center">No upload logs found</td>
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
                                <li><a href="?log_page=<?= $page - 1 ?><?= isset($_GET['status_filter']) ? '&status_filter=' . $_GET['status_filter'] : '' ?><?= isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' ?><?= isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' ?>">&laquo; Previous</a></li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="<?= $i == $page ? 'active' : '' ?>">
                                    <a href="?log_page=<?= $i ?><?= isset($_GET['status_filter']) ? '&status_filter=' . $_GET['status_filter'] : '' ?><?= isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' ?><?= isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li><a href="?log_page=<?= $page + 1 ?><?= isset($_GET['status_filter']) ? '&status_filter=' . $_GET['status_filter'] : '' ?><?= isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' ?><?= isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' ?>">Next &raquo;</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <!-- Admin Activity Logs -->
        <div class="panel panel-custom">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-history"></i> Admin Activity Logs (Last 100 Actions)
                </h3>
            </div>
            <div class="panel-body">
                <div class="table-responsive" style="max-height: 400px;">
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
                        <p class="text-center text-muted">No admin activity logs found</p>
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

        // Auto refresh every 5 minutes
        setTimeout(function() {
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>