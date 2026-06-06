<?php

include("php/dbconnect.php");

$error = '';

// Redirect if already logged in as admin
if (isset($_SESSION['rainbow_username']) && $_SESSION['rainbow_username'] === 'admin') {
    header('Location: admin.php');
    exit();
}

// Handle admin login
if (isset($_POST['admin_login'])) {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'All fields are required';
    } else {
        $sql = "SELECT * FROM user WHERE username = '$username' AND password = '" . md5($password) . "' AND role = 'admin'";
        $result = $conn->query($sql);
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $_SESSION['rainbow_username'] = $user['username'];
            $_SESSION['rainbow_uid'] = $user['id'];
            $_SESSION['rainbow_name'] = $user['name'];
            $_SESSION['rainbow_role'] = $user['role'];
            
            // Log admin login
            $login_sql = "INSERT INTO admin_activity_logs (admin_id, admin_username, activity, ip_address, user_agent, timestamp) 
                         VALUES ('{$user['id']}', '{$user['username']}', 'Admin login successful', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
            $conn->query($login_sql);
            
            header('Location: admin.php');
            exit();
        } else {
            $error = 'Invalid admin credentials';
            
            // Log failed login attempt
            $failed_login_sql = "INSERT INTO admin_activity_logs (admin_username, activity, ip_address, user_agent, timestamp) 
                                VALUES ('$username', 'Failed admin login attempt', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
            $conn->query($failed_login_sql);
        }
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login - Student Management System</title>

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
        }

        .admin-login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            padding: 40px;
            margin: 20px auto;
            max-width: 450px;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .admin-header h2 {
            color: rgba(1, 129, 55, 0.9);
            font-weight: bold;
            margin-bottom: 10px;
        }

        .admin-header p {
            color: #666;
            margin-bottom: 0;
        }

        .admin-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, rgba(1, 129, 55, 0.8), rgba(1, 100, 40, 0.9));
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(1, 129, 55, 0.3);
        }

        .admin-icon i {
            font-size: 36px;
            color: white;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .input-group-addon {
            background: rgba(1, 129, 55, 0.8);
            border-color: rgba(1, 129, 55, 0.8);
            color: white;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: rgba(1, 129, 55, 0.8);
            box-shadow: 0 0 10px rgba(1, 129, 55, 0.2);
        }

        .btn-admin {
            background: linear-gradient(45deg, rgba(1, 129, 55, 0.8), rgba(1, 100, 40, 0.9));
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 25px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(1, 129, 55, 0.3);
        }

        .btn-admin:hover {
            background: linear-gradient(45deg, rgba(1, 100, 40, 0.9), rgba(1, 80, 30, 1));
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(1, 129, 55, 0.4);
            color: white;
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .back-to-login {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .back-to-login a {
            color: rgba(1, 129, 55, 0.8);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-to-login a:hover {
            color: rgba(1, 100, 40, 0.9);
            text-decoration: none;
        }

        .security-notice {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }

        .security-notice small {
            color: #6c757d;
            display: block;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .admin-login-container {
                margin: 10px;
                padding: 30px 25px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-login-container">
            <div class="admin-header">
                <div class="admin-icon">
                    <i class="fa fa-shield"></i>
                </div>
                <h2>Admin Access</h2>
                <p>Student Management System</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Access Denied:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" role="form">
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-user-shield" aria-hidden="true"></i>
                        </span>
                        <input type="text" 
                               name="username" 
                               class="form-control" 
                               placeholder="Admin Username" 
                               required 
                               autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-lock" aria-hidden="true"></i>
                        </span>
                        <input type="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Admin Password" 
                               required 
                               autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" name="admin_login" class="btn btn-admin">
                    <i class="fa fa-sign-in"></i> Access Admin Panel
                </button>
            </form>

            <div class="security-notice">
                <i class="fa fa-info-circle text-info"></i>
                <strong>Security Notice:</strong> This area is restricted to authorized administrators only.
                <small>All login attempts are logged and monitored for security purposes.</small>
            </div>

            <div class="back-to-login">
                <a href="login.php">
                    <i class="fa fa-arrow-left"></i> Back to Regular Login
                </a>
            </div>
        </div>
    </div>

    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.js"></script>

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Focus effect on form controls
            const formControls = document.querySelectorAll('.form-control');
            formControls.forEach(function(control) {
                control.addEventListener('focus', function() {
                    this.parentNode.style.transform = 'scale(1.02)';
                });
                
                control.addEventListener('blur', function() {
                    this.parentNode.style.transform = 'scale(1)';
                });
            });

            // Button click effect
            const adminBtn = document.querySelector('.btn-admin');
            adminBtn.addEventListener('click', function(e) {
                this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Authenticating...';
            });
        });

        // Prevent going back to this page after successful login
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>