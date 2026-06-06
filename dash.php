<?php
session_start();
include("php/dbconnect.php");

$error = '';
$success = '';

// Handle login
if(isset($_POST['login']))
{
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];

    if($username == '' || $password == '')
    {
        $error = 'All fields are required';
    }
    else
    {
        // Check if user exists and get login attempts
        $user_check_sql = "SELECT * FROM user WHERE username = '$username'";
        $user_check_result = $conn->query($user_check_sql);
        
        if ($user_check_result->num_rows > 0) {
            $user_data = $user_check_result->fetch_assoc();
            
            // Check if account is locked due to too many failed attempts
            if ($user_data['login_attempts'] >= 5) {
                $error = 'Account temporarily locked due to too many failed login attempts. Please contact administrator.';
            } else if ($user_data['status'] == 'inactive') {
                $error = 'Account is inactive. Please contact administrator.';
            } else {
                // Verify password
                $sql = "SELECT * FROM user WHERE username = '$username' AND password = '" . md5($password) . "'";
                $q = $conn->query($sql);
                
                if($q->num_rows == 1)
                {
                    $res = $q->fetch_assoc();
                    $_SESSION['rainbow_username'] = $res['username'];
                    $_SESSION['rainbow_uid'] = $res['id'];
                    $_SESSION['rainbow_name'] = $res['name'];
                    $_SESSION['rainbow_role'] = $res['role'] ?? 'user';
                    
                    // Reset login attempts on successful login
                    $reset_attempts_sql = "UPDATE user SET login_attempts = 0, last_login = NOW() WHERE username = '$username'";
                    $conn->query($reset_attempts_sql);
                    
                    // Log successful login
                    $login_log_sql = "INSERT INTO user_login_logs (user_id, username, ip_address, user_agent, login_status) 
                                     VALUES ('{$res['id']}', '$username', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', 'success')";
                    $conn->query($login_log_sql);
                    
                    // Create session record
                    $session_id = session_id();
                    $session_sql = "INSERT INTO user_sessions (session_id, user_id, username, ip_address, user_agent) 
                                   VALUES ('$session_id', '{$res['id']}', '$username', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}')
                                   ON DUPLICATE KEY UPDATE last_activity = CURRENT_TIMESTAMP, is_active = 1";
                    $conn->query($session_sql);
                    
                    echo '<script type="text/javascript">window.location="index.php"; </script>';
                }
                else
                {
                    $error = 'Invalid Username or Password';
                    
                    // Increment failed login attempts
                    $increment_attempts_sql = "UPDATE user SET login_attempts = login_attempts + 1 WHERE username = '$username'";
                    $conn->query($increment_attempts_sql);
                    
                    // Log failed login attempt
                    $failed_login_sql = "INSERT INTO user_login_logs (user_id, username, ip_address, user_agent, login_status) 
                                        VALUES ('{$user_data['id']}', '$username', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', 'failed')";
                    $conn->query($failed_login_sql);
                }
            }
        } else {
            $error = 'Invalid Username or Password';
            
            // Log failed login attempt for non-existent user
            $failed_login_sql = "INSERT INTO user_login_logs (user_id, username, ip_address, user_agent, login_status) 
                                VALUES (0, '$username', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', 'failed')";
            $conn->query($failed_login_sql);
        }
    }
}

// Handle forgot password
if(isset($_POST['forgot_password']))
{
    $username = mysqli_real_escape_string($conn, trim($_POST['forgot_username']));
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($username == '' || $new_password == '' || $confirm_password == '')
    {
        $error = 'All fields are required';
    }
    elseif($new_password !== $confirm_password)
    {
        $error = 'Passwords do not match';
    }
    elseif(strlen($new_password) < 6)
    {
        $error = 'Password must be at least 6 characters long';
    }
    else
    {
        // Check if username exists and account is active
        $check_sql = "SELECT * FROM user WHERE username = '$username' AND status = 'active'";
        $check_q = $conn->query($check_sql);
        
        if($check_q->num_rows == 1)
        {
            $user_data = $check_q->fetch_assoc();
            
            // Update password and reset login attempts
            $update_sql = "UPDATE user SET password = '" . md5($new_password) . "', login_attempts = 0 WHERE username = '$username'";
            if($conn->query($update_sql))
            {
                $success = 'Password updated successfully. You can now login with your new password.';
                
                // Log password reset
                $reset_log_sql = "INSERT INTO user_login_logs (user_id, username, ip_address, user_agent, login_status) 
                                 VALUES ('{$user_data['id']}', '$username', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', 'password_reset')";
                $conn->query($reset_log_sql);
            }
            else
            {
                $error = 'Error updating password';
            }
        }
        else
        {
            $error = 'Username not found or account is inactive';
        }
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
      <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Management System</title>

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
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    
    .login-container {
        margin-top: 70px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border-radius: 10px;
        overflow: hidden;
        background: white;
    }
    
    .login-header {
        background: linear-gradient(45deg, rgba(1, 129, 55, 0.9), rgba(1, 100, 40, 0.9));
        color: white;
        padding: 30px 20px;
        text-align: center;
    }
    
    .login-header h3 {
        margin: 0;
        font-weight: 300;
        font-size: 28px;
    }
    
    .login-header p {
        margin: 10px 0 0 0;
        opacity: 0.9;
    }
    
    .login-body {
        padding: 40px;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .input-group-addon {
        background-color: rgba(1, 129, 55, 0.8);
        border-color: rgba(1, 129, 55, 0.8);
        color: white;
        font-size: 16px;
        padding: 12px 15px;
    }
    
    .form-control {
        border: 2px solid #e0e0e0;
        border-radius: 0 5px 5px 0;
        padding: 12px 15px;
        font-size: 16px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: rgba(1, 129, 55, 0.8);
        box-shadow: 0 0 10px rgba(1, 129, 55, 0.2);
    }
    
    .btn-login {
        background: linear-gradient(45deg, rgba(1, 129, 55, 0.8), rgba(1, 100, 40, 0.9));
        border: none;
        color: white;
        padding: 12px 30px;
        font-size: 16px;
        font-weight: bold;
        border-radius: 25px;
        width: 100%;
        margin-top: 10px;
        transition: all 0.3s ease;
    }
    
    .btn-login:hover {
        background: linear-gradient(45deg, rgba(1, 100, 40, 0.9), rgba(1, 80, 30, 1));
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(1, 129, 55, 0.3);
        color: white;
    }
    
    .form-toggle {
        margin-top: 20px;
        text-align: center;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    
    .form-toggle a {
        color: rgba(1, 129, 55, 0.8);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }
    
    .form-toggle a:hover {
        color: rgba(1, 100, 40, 0.9);
        text-decoration: none;
    }
    
    .admin-link {
        text-align: center;
        margin-top: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }
    
    .admin-link a {
        color: #dc3545;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    
    .admin-link a:hover {
        color: #c82333;
        text-decoration: none;
    }
    
    .alert {
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .security-info {
        background-color: #e3f2fd;
        border: 1px solid #bbdefb;
        border-radius: 8px;
        padding: 15px;
        margin-top: 20px;
        text-align: center;
    }
    
    .security-info small {
        color: #1976d2;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .login-container {
            margin: 20px 10px;
        }
        
        .login-body {
            padding: 30px 25px;
        }
    }
</style>

</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4 col-sm-6 col-sm-offset-3 col-xs-10 col-xs-offset-1">
                <div class="login-container">
                    <div class="login-header">
                        <h3>Student Management System</h3>
                        <p>Secure Access Portal</p>
                    </div>
                    
                    <div class="login-body">
                        <!-- Login Form -->
                        <div id="login-form">
                            <form role="form" action="login.php" method="post">
                                <?php if($error != '' && !isset($_POST['forgot_password'])): ?>
                                    <div class="alert alert-danger">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <strong>Login Failed:</strong> <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if($success != ''): ?>
                                    <div class="alert alert-success">
                                        <i class="fa fa-check-circle"></i>
                                        <strong>Success:</strong> <?= htmlspecialchars($success) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-user"></i>
                                        </span>
                                        <input type="text" class="form-control" placeholder="Username" name="username" required />
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" placeholder="Password" name="password" required />
                                    </div>
                                </div>
                                
                                <button class="btn btn-login" type="submit" name="login">
                                    <i class="fa fa-sign-in"></i> Sign In
                                </button>
                                
                                <div class="form-toggle">
                                    <a href="#" onclick="toggleForm()">
                                        <i class="fa fa-key"></i> Forgot Password?
                                    </a>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Forgot Password Form -->
                        <div id="forgot-form" style="display:none;">
                            <form role="form" action="login.php" method="post">
                                <h4 class="text-center" style="color: rgba(1, 129, 55, 0.8); margin-bottom: 25px;">
                                    <i class="fa fa-key"></i> Reset Password
                                </h4>
                                
                                <?php if($error != '' && isset($_POST['forgot_password'])): ?>
                                    <div class="alert alert-danger">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-user"></i>
                                        </span>
                                        <input type="text" class="form-control" placeholder="Username" name="forgot_username" required />
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" placeholder="New Password" name="new_password" required />
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" placeholder="Confirm Password" name="confirm_password" required />
                                    </div>
                                </div>
                                
                                <button class="btn btn-login" type="submit" name="forgot_password">
                                    <i class="fa fa-refresh"></i> Reset Password
                                </button>
                                
                                <div class="form-toggle">
                                    <a href="#" onclick="toggleForm()">
                                        <i class="fa fa-arrow-left"></i> Back to Login
                                    </a>
                                </div>
                            </form>
                        </div>
                        
                        <div class="security-info">
                            <i class="fa fa-shield"></i>
                            <small>Your login attempts are monitored for security purposes</small>
                        </div>
                        
                        <div class="admin-link">
                            <i class="fa fa-cog"></i>
                            <strong>System Administrator?</strong>
                            <a href="admin_login.php">
                                <i class="fa fa-shield"></i> Admin Access
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.js"></script>

    <script>
        function toggleForm() {
            var loginForm = document.getElementById('login-form');
            var forgotForm = document.getElementById('forgot-form');
            
            if (loginForm.style.display === 'none') {
                loginForm.style.display = 'block';
                forgotForm.style.display = 'none';
            } else {
                loginForm.style.display = 'none';
                forgotForm.style.display = 'block';
            }
        }
        
        // Show forgot password form if there's a forgot password error
        <?php if(isset($_POST['forgot_password']) && $error != ''): ?>
            toggleForm();
        <?php endif; ?>

        // Add loading animation on form submit
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function() {
                    const button = this.querySelector('button[type="submit"]');
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
                    button.disabled = true;
                    
                    // Re-enable button after 5 seconds in case of error
                    setTimeout(function() {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }, 5000);
                });
            });
        });

        // Prevent going back to login page after successful login
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>