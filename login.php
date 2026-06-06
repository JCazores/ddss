<?php

include("php/dbconnect.php");

$error = '';
$success = '';

// Check if user is already logged in
if (isset($_SESSION['rainbow_username'])) {
    // Redirect based on role
    if ($_SESSION['rainbow_role'] === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

// Handle logout message
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $success = 'You have been successfully logged out. Your status has been set to inactive.';
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];
    $login_type = $_POST['login_type'] ?? 'user'; // Default to user login
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        // Hash the password using MD5 (matching your existing system)
        $hashed_password = md5($password);
        
        // Build query based on login type
        if ($login_type === 'admin') {
            $login_sql = "SELECT * FROM user WHERE username = '$username' AND password = '$hashed_password' AND role = 'admin'";
        } else {
            $login_sql = "SELECT * FROM user WHERE username = '$username' AND password = '$hashed_password'";
        }
        
        $result = $conn->query($login_sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if user account is not deleted/banned
            if (isset($user['status']) && $user['status'] == 'banned') {
                $error = 'Your account has been banned. Please contact administrator.';
            } else {
                // For admin login, verify role
                if ($login_type === 'admin' && $user['role'] !== 'admin') {
                    $error = 'Invalid admin credentials';
                    
                    // Log failed admin login attempt
                    $failed_login_sql = "INSERT INTO admin_activity_logs (admin_username, activity, ip_address, user_agent, timestamp) 
                                        VALUES ('$username', 'Failed admin login attempt - Not admin role', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
                    $conn->query($failed_login_sql);
                } else {
                    // AUTOMATICALLY SET USER STATUS TO ACTIVE ON SUCCESSFUL LOGIN
                    $user_id = $user['id'];
                    $activate_sql = "UPDATE user SET status = 'active', status_updated_date = NOW() WHERE id = '$user_id'";
                    $conn->query($activate_sql);
                    
                    // Set session variables
                    $_SESSION['rainbow_uid'] = $user['id'];
                    $_SESSION['rainbow_username'] = $user['username'];
                    $_SESSION['rainbow_name'] = $user['name'];
                    $_SESSION['rainbow_email'] = $user['email'];
                    $_SESSION['rainbow_role'] = $user['role'] ?? 'user';
                    
                    // Log the successful login
                    $session_id = session_id();
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $user_agent = $_SERVER['HTTP_USER_AGENT'];
                    
                    $login_log_sql = "INSERT INTO user_login_logs (user_id, username, ip_address, user_agent, login_status, additional_info) 
                                     VALUES ('$user_id', '$username', '$ip_address', '" . mysqli_real_escape_string($conn, $user_agent) . "', 'login', 'Status automatically set to active - Login type: $login_type')";
                    $conn->query($login_log_sql);
                    
                    // Create or update user session record
                    $session_sql = "INSERT INTO user_sessions (session_id, user_id, username, ip_address, user_agent, created_time, last_activity, is_active) 
                                   VALUES ('$session_id', '$user_id', '$username', '$ip_address', '" . mysqli_real_escape_string($conn, $user_agent) . "', NOW(), NOW(), 1)
                                   ON DUPLICATE KEY UPDATE 
                                   last_activity = NOW(), is_active = 1, ip_address = '$ip_address'";
                    $conn->query($session_sql);
                    
                    // If it's an admin login, log it separately
                    if ($user['role'] == 'admin') {
                        $admin_login_sql = "INSERT INTO admin_activity_logs (admin_id, admin_username, activity, ip_address, user_agent, timestamp) 
                                           VALUES ('$user_id', '$username', 'Admin login successful - Status set to active', '$ip_address', '" . mysqli_real_escape_string($conn, $user_agent) . "', NOW())";
                        $conn->query($admin_login_sql);
                        $success = 'Admin login successful! Redirecting to admin panel...';
                        $redirect_url = 'admin.php';
                    } else {
                        $success = 'Login successful! Your status has been activated.';
                        $redirect_url = 'index.php';
                    }
                    
                    // Redirect after successful login
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = '$redirect_url';
                        }, 1500);
                    </script>";
                }
            }
        } else {
            if ($login_type === 'admin') {
                $error = 'Invalid admin credentials';
                
                // Log failed admin login attempt
                $failed_login_sql = "INSERT INTO admin_activity_logs (admin_username, activity, ip_address, user_agent, timestamp) 
                                    VALUES ('$username', 'Failed admin login attempt', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
                $conn->query($failed_login_sql);
            } else {
                $error = 'Invalid username or password';
            }
            
            // Log failed login attempt
            $failed_login_sql = "INSERT INTO user_login_logs (username, ip_address, user_agent, login_status, additional_info) 
                                VALUES ('$username', '{$_SERVER['REMOTE_ADDR']}', '" . mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT']) . "', 'failed', 'Invalid credentials - Login type: $login_type')";
            $conn->query($failed_login_sql);
        }
    }
}

// Get some statistics for display
$stats_sql = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users
    FROM user";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total_users' => 0, 'active_users' => 0, 'inactive_users' => 0, 'admin_users' => 0];
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Drop-Out Decision Support System</title>
    
    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="img/icon1.jpg">
   
    
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
            background: linear-gradient(135deg, rgba(1, 129, 55, 0.8), rgba(1, 100, 40, 0.9));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            margin: 20px;
        }
        
        .login-header {
            background: linear-gradient(45deg, rgba(1, 129, 55, 0.9), rgba(1, 100, 40, 0.9));
            color: white;
            text-align: center;
            padding: 30px 20px;
            position: relative;
        }
        
        .login-header.admin-mode {
            background: linear-gradient(45deg, rgba(220, 53, 69, 0.9), rgba(183, 28, 28, 0.9));
        }
        
        .login-header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .login-type-toggle {
            position: absolute;
            top: 10px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .login-type-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            height: 45px;
            border-radius: 5px;
            border: 2px solid #e0e0e0;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: rgba(1, 129, 55, 0.8);
            box-shadow: 0 0 0 0.2rem rgba(1, 129, 55, 0.25);
        }
        
        .admin-mode .form-control:focus {
            border-color: rgba(220, 53, 69, 0.8);
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(45deg, rgba(1, 129, 55, 0.9), rgba(1, 100, 40, 0.9));
            border: none;
            color: white;
            height: 45px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 5px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: linear-gradient(45deg, rgba(1, 100, 40, 0.9), rgba(1, 80, 30, 0.9));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .admin-mode .btn-login {
            background: linear-gradient(45deg, rgba(220, 53, 69, 0.9), rgba(183, 28, 28, 0.9));
        }
        
        .admin-mode .btn-login:hover {
            background: linear-gradient(45deg, rgba(183, 28, 28, 0.9), rgba(157, 22, 22, 0.9));
        }
        
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .login-mode-indicator {
            background: rgba(255, 255, 255, 0.15);
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }
        
        .login-mode-indicator i {
            margin-right: 8px;
        }
        
        .system-stats {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .stats-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .stats-row:last-child {
            margin-bottom: 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .stat-value {
            font-weight: bold;
            color: rgba(1, 129, 55, 0.8);
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-active {
            background-color: #5cb85c;
        }
        
        .status-inactive {
            background-color: #d9534f;
        }
        
        .status-admin {
            background-color: #f0ad4e;
        }
        
        .login-footer {
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            color: #666;
            font-size: 12px;
        }
        
        .input-group-addon {
            background-color: rgba(1, 129, 55, 0.1);
            border-color: #e0e0e0;
            transition: all 0.3s ease;
        }
        
        .admin-mode .input-group-addon {
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .security-notice {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 12px;
            margin-top: 15px;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        }
        
        .admin-mode .security-notice {
            background: #fff5f5;
            border-color: #fed7d7;
            color: #c53030;
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
                max-width: none;
            }
            
            .login-body {
                padding: 20px;
            }
        }
        .password-toggle-container {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #666;
    z-index: 10;
    padding: 5px;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: rgba(1, 129, 55, 0.8);
}

.admin-mode .password-toggle:hover {
    color: rgba(220, 53, 69, 0.8);
}

.password-field-with-toggle {
    padding-right: 40px !important;
}
    </style>
</head>

<body>
    <div class="login-container" id="loginContainer">
        <div class="login-header" id="loginHeader">
            
            
            <i class="fa fa-graduation-cap fa-2x" id="headerIcon" style="margin-bottom: 10px;"></i>
            <h2>Drop-Out Decision Support System</h2>
            <p style="margin: 5px 0 0 0; opacity: 0.9;" id="headerSubtext">Please login to continue</p>
            
            
        </div>
        
        <div class="login-body">
            <!-- Display Messages -->
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

            <!-- Login Form -->
            <form method="POST" id="loginForm">
                <input type="hidden" name="login_type" id="loginType" value="user">
                
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-user" id="usernameIcon"></i>
                        </span>
                        <input type="text" name="username" class="form-control" id="usernameField" 
                               placeholder="Username" required 
                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                    </div>
                </div>
                
                <div class="form-group">
    <div class="input-group password-toggle-container">
        <span class="input-group-addon">
            <i class="fa fa-lock"></i>
        </span>
        <input type="password" name="password" class="form-control password-field-with-toggle" id="passwordField" 
               placeholder="Password" required>
        <i class="fa fa-eye password-toggle" id="passwordToggle" onclick="togglePasswordVisibility()"></i>
    </div>
</div>
                
                <div class="form-group">
                    <button type="submit" name="login" class="btn btn-login" id="loginButton">
                        <i class="fa fa-sign-in"></i> <span id="buttonText">Login</span>
                    </button>
                </div>
            </form>
            
          
            
            <div class="security-notice" id="securityNotice">
                <i class="fa fa-shield"></i>
                <strong>Security:</strong> All login attempts are logged and monitored.
            </div>
        </div>
        
        <!-- System Statistics -->
        <div class="login-footer">
            <!--<div class="system-stats">
                <h5 style="margin-bottom: 15px; color: rgba(1, 129, 55, 0.8);">
                    <i class="fa fa-bar-chart"></i> System Status
                </h5>
                <div class="stats-row">
                    <span class="stat-label">
                        <i class="fa fa-users"></i> Total Users
                    </span>
                    <span class="stat-value"><?= $stats['total_users'] ?></span>
                </div>
                <div class="stats-row">
                    <span class="stat-label">
                        <span class="status-indicator status-active"></span>
                        Active Users
                    </span>
                    <span class="stat-value"><?= $stats['active_users'] ?></span>
                </div>
                <div class="stats-row">
                    <span class="stat-label">
                        <span class="status-indicator status-inactive"></span>
                        Inactive Users
                    </span>
                    <span class="stat-value"><?= $stats['inactive_users'] ?></span>
                </div>
                <div class="stats-row">
                    <span class="stat-label">
                        <span class="status-indicator status-admin"></span>
                        Admin Users
                    </span>
                    <span class="stat-value"><?= $stats['admin_users'] ?></span>
                </div>
            </div>-->
            
            <p style="margin-top: 15px; margin-bottom: 0;">
                © <?= date('Y') ?> Drop-Out Decision Support System. All rights reserved.
            </p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.js"></script>
    <script>
        function togglePasswordVisibility() {
    const passwordField = document.getElementById('passwordField');
    const passwordToggle = document.getElementById('passwordToggle');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        passwordToggle.className = 'fa fa-eye-slash password-toggle';
    } else {
        passwordField.type = 'password';
        passwordToggle.className = 'fa fa-eye password-toggle';
    }
}
    </script>
    <script>
        let isAdminMode = false;
        
        
        
        // Auto-hide success messages after 3 seconds
        $(document).ready(function() {
            if ($('.alert-success').length) {
                setTimeout(function() {
                    $('.alert-success').fadeOut();
                }, 3000);
            }
        });
        
        // Add loading animation on form submit
        $('#loginForm').submit(function() {
            const button = $(this).find('button[type="submit"]');
            const originalText = button.html();
            
            if (isAdminMode) {
                button.html('<i class="fa fa-spinner fa-spin"></i> Authenticating Admin...');
            } else {
                button.html('<i class="fa fa-spinner fa-spin"></i> Logging in...');
            }
            
            // Reset button text after 10 seconds if form doesn't submit
            setTimeout(function() {
                button.html(originalText);
            }, 10000);
        });
        
        // Add keyboard shortcut for quick admin toggle (Ctrl + Shift + A)
        $(document).keydown(function(e) {
            if (e.ctrlKey && e.shiftKey && e.keyCode === 65) {
                e.preventDefault();
                toggleLoginType();
            }
        });
        
        // Focus username field on page load
        $(document).ready(function() {
            $('#usernameField').focus();
        });
    </script>
</body>
</html>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>