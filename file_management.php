<?php
include("php/dbconnect.php");

// Check if user is admin
if (!isset($_SESSION['rainbow_username']) || $_SESSION['rainbow_username'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Handle file deletion
if (isset($_POST['delete_file'])) {
    $log_id = intval($_POST['log_id']);
    
    // Get file information first
    $file_info_sql = "SELECT * FROM upload_logs WHERE log_id = $log_id";
    $file_info_result = $conn->query($file_info_sql);
    $file_info = $file_info_result->fetch_assoc();
    
    if ($file_info) {
        $table_name = $file_info['table_name'];
        $filename = $file_info['filename'];
        $file_path = $file_info['file_path'] ?? 'uploads/' . $filename;
        
        // Delete all records from the table that were uploaded from this file
        $delete_data_sql = "DELETE FROM `$table_name` WHERE upload_batch_id = $log_id";
        $data_deleted = $conn->query($delete_data_sql);
        $affected_rows = $conn->affected_rows;
        
        // Delete the physical file if it exists
        $file_deleted = false;
        if (file_exists($file_path)) {
            $file_deleted = unlink($file_path);
        } else {
            // File doesn't exist, consider it as successfully "deleted"
            $file_deleted = true;
        }
        
        // Delete the upload log entry
        $delete_log_sql = "DELETE FROM upload_logs WHERE log_id = $log_id";
        $log_deleted = $conn->query($delete_log_sql);
        
        if ($data_deleted && $log_deleted) {
            $success = "File '$filename' completely deleted: $affected_rows database records removed" . 
                      ($file_deleted ? " and physical file removed" : " (physical file was already missing)");
            
            // Log admin activity
            $admin_id = $_SESSION['rainbow_uid'];
            $activity = "Completely deleted file: $filename ($affected_rows records + upload log)";
            $log_sql = "INSERT INTO admin_activity_logs (admin_id, admin_username, activity, target_user, ip_address, user_agent, timestamp) 
                       VALUES ('$admin_id', '{$_SESSION['rainbow_username']}', '$activity', NULL, '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
            $conn->query($log_sql);
        } else {
            $error = 'Error deleting file completely. Some components may not have been removed.';
        }
    } else {
        $error = 'File not found in upload logs.';
    }
}

// Handle bulk file data deletion (keeping the log entry)
if (isset($_POST['delete_file_data'])) {
    $log_id = intval($_POST['log_id']);
    
    // Get file information first
    $file_info_sql = "SELECT * FROM upload_logs WHERE log_id = $log_id";
    $file_info_result = $conn->query($file_info_sql);
    $file_info = $file_info_result->fetch_assoc();
    
    if ($file_info) {
        $table_name = $file_info['table_name'];
        $filename = $file_info['filename'];
        
        // Delete all records from the table that were uploaded from this file
        $delete_sql = "DELETE FROM `$table_name` WHERE upload_batch_id = $log_id";
        if ($conn->query($delete_sql)) {
            $affected_rows = $conn->affected_rows;
            
            // Update upload log status
            $update_log_sql = "UPDATE upload_logs SET status = 'deleted', error_message = 'File data deleted by admin' WHERE log_id = $log_id";
            $conn->query($update_log_sql);
            
            $success = "Successfully deleted $affected_rows records from file: $filename (log entry preserved)";
            
            // Log admin activity
            $admin_id = $_SESSION['rainbow_uid'];
            $activity = "Deleted data from uploaded file: $filename ($affected_rows records)";
            $log_sql = "INSERT INTO admin_activity_logs (admin_id, admin_username, activity, target_user, ip_address, user_agent, timestamp) 
                       VALUES ('$admin_id', '{$_SESSION['rainbow_username']}', '$activity', NULL, '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
            $conn->query($log_sql);
        } else {
            $error = 'Error deleting file data: ' . $conn->error;
        }
    }
}

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

// Handle single record deletion
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

// Pagination and filtering
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

$filter = '';
if (isset($_GET['table_filter']) && !empty($_GET['table_filter'])) {
    $table_filter = mysqli_real_escape_string($conn, $_GET['table_filter']);
    $filter .= " WHERE table_name = '$table_filter'";
}

if (isset($_GET['status_filter']) && !empty($_GET['status_filter'])) {
    $status_filter = mysqli_real_escape_string($conn, $_GET['status_filter']);
    $filter .= empty($filter) ? " WHERE" : " AND";
    $filter .= " status = '$status_filter'";
}

if (isset($_GET['user_filter']) && !empty($_GET['user_filter'])) {
    $user_filter = mysqli_real_escape_string($conn, $_GET['user_filter']);
    $filter .= empty($filter) ? " WHERE" : " AND";
    $filter .= " uploaded_by_username LIKE '%$user_filter%'";
}

if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $date_from = mysqli_real_escape_string($conn, $_GET['date_from']);
    $filter .= empty($filter) ? " WHERE" : " AND";
    $filter .= " DATE(upload_date) >= '$date_from'";
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $date_to = mysqli_real_escape_string($conn, $_GET['date_to']);
    $filter .= empty($filter) ? " WHERE" : " AND";
    $filter .= " DATE(upload_date) <= '$date_to'";
}

// Get upload logs with pagination
$files_sql = "SELECT * FROM upload_logs $filter ORDER BY upload_date DESC LIMIT $records_per_page OFFSET $offset";
$files_result = $conn->query($files_sql);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM upload_logs $filter";
$count_result = $conn->query($count_sql);
$total_files = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_files / $records_per_page);

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_files,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_files,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_files,
    SUM(CASE WHEN status = 'deleted' THEN 1 ELSE 0 END) as deleted_files,
    SUM(records_success) as total_records,
    COUNT(DISTINCT table_name) as unique_tables,
    COUNT(DISTINCT uploaded_by_username) as unique_uploaders
    FROM upload_logs $filter";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>File Management - Student Management System</title>

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
        
        body {
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }

        .header-section {
            background: linear-gradient(45deg, rgba(1, 129, 55, 0.9), rgba(1, 100, 40, 0.9));
            color: white;
            padding: 30px;
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
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card h4 {
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

        .file-card {
            border: 2px solid rgba(1, 129, 55, 0.3);
            border-radius: 8px;
            margin-bottom: 20px;
            background: white;
            transition: all 0.3s ease;
        }

        .file-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .file-header {
            background: rgba(1, 129, 55, 0.1);
            padding: 20px;
            border-bottom: 1px solid rgba(1, 129, 55, 0.3);
            border-radius: 6px 6px 0 0;
        }

        .file-body {
            padding: 20px;
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

        .btn-delete {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
            border-color: #bd2130;
            color: white;
        }

        .btn-delete-data {
            background-color: #fd7e14;
            border-color: #fd7e14;
            color: white;
        }
        
        .btn-delete-data:hover {
            background-color: #e8640c;
            border-color: #dc5f0d;
            color: white;
        }

        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }

        .status-success {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-failed {
            color: #dc3545;
            font-weight: bold;
        }

        .status-deleted {
            color: #6c757d;
            font-weight: bold;
        }

        .user-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border-left: 3px solid rgba(1, 129, 55, 0.6);
        }

        .user-badge {
            background-color: #e7f3ff;
            color: #0066cc;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .records-container {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
            display: none;
        }

        .record-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
        }

        .back-link {
            margin-bottom: 20px;
        }

        .danger-zone {
            background: #fff5f5;
            border: 2px solid #fed7d7;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .file-path {
            background: #f1f3f4;
            padding: 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <!-- Back to Admin Link -->
        <div class="back-link">
            <a href="admin.php" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> Back to Admin Panel
            </a>
        </div>

        <!-- Header -->
        <div class="header-section">
            <div class="row">
                <div class="col-md-8">
                    <h1><i class="fa fa-files-o"></i> File Management System</h1>
                    <p>Comprehensive management of all uploaded files and their data</p>
                </div>
                <div class="col-md-4 text-right">
                    <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 14px;">
                        <i class="fa fa-clock-o"></i> <?= date('M j, Y - H:i') ?>
                    </span>
                    <br>
                    <small>Logged in as: <?= htmlspecialchars($_SESSION['rainbow_name']) ?></small>
                </div>
            </div>
        </div>

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

        <!-- Statistics -->
        <div class="row">
            <div class="col-md-2">
                <div class="stat-card">
                    <h4><i class="fa fa-files-o"></i> Total Files</h4>
                    <div class="stat-number"><?= $stats['total_files'] ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <h4><i class="fa fa-check-circle"></i> Successful</h4>
                    <div class="stat-number status-success"><?= $stats['successful_files'] ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <h4><i class="fa fa-times-circle"></i> Failed</h4>
                    <div class="stat-number status-failed"><?= $stats['failed_files'] ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <h4><i class="fa fa-trash"></i> Deleted</h4>
                    <div class="stat-number status-deleted"><?= $stats['deleted_files'] ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <h4><i class="fa fa-database"></i> Records</h4>
                    <div class="stat-number"><?= number_format($stats['total_records']) ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <h4><i class="fa fa-users"></i> Uploaders</h4>
                    <div class="stat-number"><?= $stats['unique_uploaders'] ?></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="panel panel-custom">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-filter"></i> Filter Files
                </h3>
            </div>
            <div class="panel-body">
                <form method="GET" class="form-horizontal">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Table Name:</label>
                                <select name="table_filter" class="form-control">
                                    <option value="">All Tables</option>
                                    <?php
                                    // Get unique table names
                                    $tables_sql = "SELECT DISTINCT table_name FROM upload_logs ORDER BY table_name";
                                    $tables_result = $conn->query($tables_sql);
                                    while ($table = $tables_result->fetch_assoc()) {
                                        $selected = (isset($_GET['table_filter']) && $_GET['table_filter'] == $table['table_name']) ? 'selected' : '';
                                        echo "<option value='{$table['table_name']}' $selected>{$table['table_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Status:</label>
                                <select name="status_filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="success" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'success') ? 'selected' : '' ?>>Success</option>
                                    <option value="failed" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'failed') ? 'selected' : '' ?>>Failed</option>
                                    <option value="deleted" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'deleted') ? 'selected' : '' ?>>Deleted</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Uploaded By:</label>
                                <input type="text" name="user_filter" class="form-control" placeholder="Username" value="<?= $_GET['user_filter'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date From:</label>
                                <input type="date" name="date_from" class="form-control" value="<?= $_GET['date_from'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date To:</label>
                                <input type="date" name="date_to" class="form-control" value="<?= $_GET['date_to'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-custom btn-block">
                                        <i class="fa fa-filter"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" onclick="clearFilters()" class="btn btn-default">
                                <i class="fa fa-refresh"></i> Clear All Filters
                            </button>
                            <span class="text-muted">Showing <?= $stats['total_files'] ?> files</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Files List -->
        <div class="panel panel-custom">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-list"></i> Uploaded Files
                </h3>
            </div>
            <div class="panel-body">
                <?php if ($files_result && $files_result->num_rows > 0): ?>
                    <?php while ($file = $files_result->fetch_assoc()): ?>
                        <div class="file-card">
                            <div class="file-header">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4>
                                            <i class="fa fa-file-text"></i> 
                                            <?= htmlspecialchars($file['filename']) ?>
                                        </h4>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Table:</strong> <code><?= htmlspecialchars($file['table_name']) ?></code></p>
                                                <p><strong>Year/Semester:</strong> <?= htmlspecialchars($file['year']) ?> - Semester <?= htmlspecialchars($file['semester']) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Upload Date:</strong> <?= date('M j, Y H:i:s', strtotime($file['upload_date'])) ?></p>
                                                <p><strong>Records:</strong> 
                                                    <span class="status-success"><?= $file['records_success'] ?> success</span> / 
                                                    <span class="status-failed"><?= $file['records_error'] ?> errors</span>
                                                </p>
                                            </div>
                                        </div>
                                        
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <?php if ($file['status'] == 'success'): ?>
                                            <button onclick="confirmDeleteFile(<?= $file['log_id'] ?>, '<?= htmlspecialchars($file['filename']) ?>')" 
                                                class="btn btn-delete btn-sm">
                                            <i class="fa fa-trash"></i> Delete Completely
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Records Container (Initially Hidden) -->
                                <div id="file-records-<?= $file['log_id'] ?>" class="records-container">
                                    <div id="records-content-<?= $file['log_id'] ?>">
                                        <!-- Records will be loaded here via AJAX -->
                                    </div>
                                </div>

                                <!-- Danger Zone for Complete File Deletion -->
                                <div class="danger-zone">
                                    <h5 style="color: #dc3545; margin-bottom: 10px;">
                                        <i class="fa fa-exclamation-triangle"></i> Danger Zone
                                    </h5>
                                    <p style="margin-bottom: 15px; font-size: 0.9em;">
                                        <strong>Delete Data Only:</strong> Removes all database records but keeps the upload log entry for audit purposes.<br>
                                        <strong>Delete Completely:</strong> Removes database records, physical file, and upload log entry. This action is irreversible.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Files pagination" style="margin-top: 30px;">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li>
                                        <a href="?page=<?= $page - 1 ?><?= http_build_query(array_filter(array_intersect_key($_GET, array_flip(['table_filter', 'status_filter', 'user_filter', 'date_from', 'date_to']))), '', '&') ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="<?= $i == $page ? 'active' : '' ?>">
                                        <a href="?page=<?= $i ?><?= http_build_query(array_filter(array_intersect_key($_GET, array_flip(['table_filter', 'status_filter', 'user_filter', 'date_from', 'date_to']))), '', '&') ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li>
                                        <a href="?page=<?= $page + 1 ?><?= http_build_query(array_filter(array_intersect_key($_GET, array_flip(['table_filter', 'status_filter', 'user_filter', 'date_from', 'date_to']))), '', '&') ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="text-center" style="padding: 50px;">
                        <i class="fa fa-file-o fa-4x text-muted" style="opacity: 0.3;"></i>
                        <h3 class="text-muted">No Files Found</h3>
                        <p class="text-muted">No uploaded files match your current filter criteria.</p>
                        <button onclick="clearFilters()" class="btn btn-custom">
                            <i class="fa fa-refresh"></i> Clear Filters
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Hidden Forms -->
    <form id="deleteFileForm" method="POST" style="display: none;">
        <input type="hidden" name="log_id" id="deleteLogId">
        <input type="hidden" name="delete_file" value="1">
    </form>

    <form id="deleteFileDataForm" method="POST" style="display: none;">
        <input type="hidden" name="log_id" id="deleteDataLogId">
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

    <!-- Scripts -->
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.js"></script>

    <script>
        function clearFilters() {
            window.location.href = 'file_management.php';
        }

        function confirmDeleteFile(logId, filename) {
            if (confirm('⚠️ COMPLETE FILE DELETION ⚠️\n\n' +
                       'This will PERMANENTLY delete:\n' +
                       '• All database records from this file\n' +
                       '• The physical file from the server\n' +
                       '• The upload log entry\n\n' +
                       'File: "' + filename + '"\n\n' +
                       'This action CANNOT be undone!\n\n' +
                       'Are you absolutely sure you want to proceed?')) {
                
                if (confirm('FINAL CONFIRMATION\n\n' +
                           'You are about to completely remove all traces of "' + filename + '" from the system.\n\n' +
                           'Click OK to proceed with permanent deletion.')) {
                    document.getElementById('deleteLogId').value = logId;
                    document.getElementById('deleteFileForm').submit();
                }
            }
        }

        function confirmDeleteFileData(logId, filename) {
            if (confirm('Are you sure you want to delete ALL DATA from the file "' + filename + '"?\n\n' +
                       'This will:\n' +
                       '• Delete all database records from this file\n' +
                       '• Keep the upload log entry for audit purposes\n' +
                       '• Keep the physical file on the server\n\n' +
                       'This action cannot be undone!')) {
                document.getElementById('deleteDataLogId').value = logId;
                document.getElementById('deleteFileDataForm').submit();
            }
        }

        function toggleFileData(logId, tableName) {
            const container = document.getElementById('file-records-' + logId);
            const content = document.getElementById('records-content-' + logId);
            
            if (container.style.display === 'none' || container.style.display === '') {
                container.style.display = 'block';
                loadFileRecords(logId, tableName);
            } else {
                container.style.display = 'none';
            }
        }

        function loadFileRecords(logId, tableName) {
            const content = document.getElementById('records-content-' + logId);
            content.innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading records...</div>';
            
            // AJAX call to load records
            $.ajax({
                url: 'load_file_records.php',
                method: 'POST',
                data: {
                    log_id: logId,
                    table_name: tableName
                },
                success: function(response) {
                    content.innerHTML = response;
                },
                error: function() {
                    content.innerHTML = '<div class="alert alert-danger">Error loading records. Please try again.</div>';
                }
            });
        }

        function editRecord(recordId, tableName) {
            if (confirm('Are you sure you want to edit this record?')) {
                const row = document.getElementById('record-row-' + recordId);
                if (!row) return;
                
                row.style.background = '#fff3cd';
                row.style.border = '2px solid #ffc107';
                
                // Convert display fields to input fields
                const fields = row.querySelectorAll('[data-field]');
                fields.forEach(field => {
                    const fieldName = field.getAttribute('data-field');
                    const currentValue = field.textContent.trim();
                    field.innerHTML = '<input type="text" name="' + fieldName + '" value="' + currentValue + '" class="form-control input-sm">';
                });
                
                // Show save/cancel buttons
                const actionsDiv = row.querySelector('.record-actions');
                if (actionsDiv) {
                    actionsDiv.innerHTML = `
                        <button onclick="saveRecord(${recordId}, '${tableName}')" class="btn btn-xs btn-success">
                            <i class="fa fa-save"></i> Save
                        </button>
                        <button onclick="cancelEdit(${recordId}, '${tableName}')" class="btn btn-xs btn-default">
                            <i class="fa fa-times"></i> Cancel
                        </button>
                    `;
                }
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
            const logId = document.querySelector(`#records-content-${recordId}`).closest('[id^="file-records-"]').id.split('-')[2];
            loadFileRecords(logId, tableName);
        }

        function deleteRecord(recordId, tableName) {
            if (confirm('Are you sure you want to delete this individual record?\n\nThis action cannot be undone!')) {
                document.getElementById('deleteRecordId').value = recordId;
                document.getElementById('deleteTableName').value = tableName;
                document.getElementById('deleteRecordForm').submit();
            }
        }

        function downloadFileInfo(logId) {
            // Create a simple download of file information
            window.open('download_file_info.php?log_id=' + logId, '_blank');
        }

        // Auto-hide success alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Enhanced search functionality
        function searchFiles() {
            const searchTerm = document.getElementById('fileSearch').value.toLowerCase();
            const fileCards = document.querySelectorAll('.file-card');
            
            fileCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+F for search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                const searchInput = document.getElementById('fileSearch');
                if (searchInput) {
                    searchInput.focus();
                }
            }
            
            // Escape to clear search
            if (e.key === 'Escape') {
                const searchInput = document.getElementById('fileSearch');
                if (searchInput && searchInput.value) {
                    searchInput.value = '';
                    searchFiles();
                }
            }
        });
    </script>
</body>
</html>
                                        