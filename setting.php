<?php 
$page='setting';
include("php/dbconnect.php");
include("php/checklogin.php");

$error = '';
$success = '';

if(isset($_POST['save'])) {
    $oldpassword = mysqli_real_escape_string($conn, $_POST['oldpassword']);
    $newpassword = mysqli_real_escape_string($conn, $_POST['newpassword']);
    
    // Verify old password
    $sql = "SELECT * FROM user WHERE id = '".$_SESSION['rainbow_uid']."' AND password = '".md5($oldpassword)."'";
    $q = $conn->query($sql);
    
    if($q->num_rows > 0) {
        // Update password
        $sql = "UPDATE user SET password = '".md5($newpassword)."' WHERE id = '".$_SESSION['rainbow_uid']."'";
        $r = $conn->query($sql);
        
        if($r) {
            $_SESSION['password_changed'] = true;
            echo '<script type="text/javascript">window.location="setting.php?success=1"; </script>';
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    } else {
        $error = 'Current password is incorrect. Please try again.';
    }
}

// Get user information
$user_sql = "SELECT username, email, name, role FROM user WHERE id = '".$_SESSION['rainbow_uid']."'";
$user_result = $conn->query($user_sql);
$user_data = $user_result->fetch_assoc();

// Determine account type based on role
$account_types = [
    'admin' => 'Administrator',
    'teacher' => 'Teacher',
    'staff' => 'Staff Member',
    'user' => 'User'
];

$account_type = isset($user_data['role']) && isset($account_types[$user_data['role']]) 
    ? $account_types[$user_data['role']] 
    : 'User';
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Account Settings - Dropout Prediction System</title>
    
    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="img/icon1.jpg">
    
    <!-- BOOTSTRAP STYLES -->
    <link href="css/bootstrap.css" rel="stylesheet" />
    
    <!-- FONTAWESOME STYLES -->
    <link href="css/font-awesome.css" rel="stylesheet" />
    
    <!-- CUSTOM STYLES -->
    <link href="css/style1.css" rel="stylesheet" />
    <link href="css/custom.css" rel="stylesheet" />
    
    <!-- GOOGLE FONTS -->
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700' rel='stylesheet' type='text/css' />
    
    <style>
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .settings-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .settings-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, rgba(1, 129, 55, 0.9), rgba(1, 129, 55, 0.8));
            color: white;
            padding: 20px 25px;
            border-bottom: none;
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .card-header h3 i {
            margin-right: 10px;
            font-size: 22px;
        }
        
        .card-body {
            padding: 30px 25px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }
        
        .form-control {
            height: 45px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: rgba(1, 129, 55, 0.8);
            box-shadow: 0 0 0 0.2rem rgba(1, 129, 55, 0.15);
            outline: none;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .form-control {
            padding-right: 45px;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 5px 10px;
            z-index: 10;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: rgba(1, 129, 55, 0.8);
        }
        
        .password-toggle:focus {
            outline: none;
        }
        
        .btn-save {
            background: linear-gradient(135deg, rgba(1, 129, 55, 0.9), rgba(1, 129, 55, 0.8));
            color: white;
            border: none;
            padding: 12px 35px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-save:hover {
            background: linear-gradient(135deg, rgba(1, 129, 55, 1), rgba(1, 129, 55, 0.9));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(1, 129, 55, 0.3);
        }
        
        .btn-save:active {
            transform: translateY(0);
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 35px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 10px;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }
        
        .alert {
            border-radius: 6px;
            padding: 15px 20px;
            margin-bottom: 25px;
            border: none;
            display: flex;
            align-items: center;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert i {
            font-size: 20px;
            margin-right: 12px;
        }
        
        .alert .close {
            margin-left: auto;
            padding-left: 20px;
            font-size: 24px;
            font-weight: 300;
            line-height: 1;
            color: inherit;
            opacity: 0.5;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .alert .close:hover {
            opacity: 0.8;
        }
        
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            display: none;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .password-strength.show {
            display: block;
        }
        
        .password-strength-text {
            font-size: 12px;
            margin-top: 5px;
            font-weight: 600;
        }
        
        .help-block {
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
            display: block;
        }
        
        .has-error .form-control {
            border-color: #dc3545;
        }
        
        .has-success .form-control {
            border-color: #28a745;
        }
        
        .form-control-feedback {
            position: absolute;
            right: 45px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            display: block;
            width: auto;
            height: auto;
            line-height: 1;
            pointer-events: none;
        }
        
        .glyphicon-ok {
            color: #28a745;
        }
        
        .glyphicon-remove {
            color: #dc3545;
        }
        
        .info-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-left: 4px solid rgba(1, 129, 55, 0.8);
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        
        .info-card h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-item i {
            color: rgba(1, 129, 55, 0.8);
            margin-right: 12px;
            font-size: 18px;
            width: 25px;
            text-align: center;
        }
        
        .info-item strong {
            color: #555;
            margin-right: 8px;
            min-width: 100px;
        }
        
        .info-item span {
            color: #333;
            font-weight: 600;
        }
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .password-requirements h5 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #555;
            font-weight: 600;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            list-style: none;
        }
        
        .password-requirements li {
            padding: 5px 0;
            font-size: 13px;
            color: #666;
            position: relative;
        }
        
        .password-requirements li:before {
            content: "•";
            color: rgba(1, 129, 55, 0.8);
            font-weight: bold;
            position: absolute;
            left: -15px;
        }
        
        .page-header {
            background: linear-gradient(135deg, rgba(1, 129, 55, 0.1), rgba(1, 129, 55, 0.05));
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid rgba(1, 129, 55, 0.8);
        }
        
        .page-header h1 {
            margin: 0;
            color: #333;
            font-size: 28px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .page-header h1 i {
            margin-right: 15px;
            color: rgba(1, 129, 55, 0.8);
        }
        
        .page-header p {
            margin: 8px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .settings-container {
                padding: 0 15px;
            }
            
            .card-body {
                padding: 20px 15px;
            }
            
            .btn-save, .btn-cancel {
                width: 100%;
                margin: 5px 0;
            }
            
            .info-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .info-item strong {
                margin-bottom: 5px;
            }
        }
        
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .loading-spinner {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
        }
        
        .loading-spinner i {
            font-size: 48px;
            color: rgba(1, 129, 55, 0.8);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
    
    <script src="js/jquery-1.10.2.js"></script>
    <script type="text/javascript" src="js/validation/jquery.validate.min.js"></script>
</head>

<body>
    <?php include("php/header.php"); ?>
    
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <i class="fa fa-spinner fa-spin"></i>
            <p style="margin-top: 15px; color: #333;">Updating password...</p>
        </div>
    </div>
    
    <div id="page-wrapper">
        <div id="page-inner">
            <div class="settings-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fa fa-cog"></i>
                        Account Settings
                    </h1>
                    <p>Manage your account security and preferences</p>
                </div>
                
                <!-- Success/Error Messages -->
                <?php if(isset($_GET['success']) && $_GET['success'] == '1'): ?>
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i>
                    <div>
                        <strong>Success!</strong> Your password has been changed successfully.
                    </div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-circle"></i>
                    <div>
                        <strong>Error!</strong> <?= htmlspecialchars($error) ?>
                    </div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Account Information Card -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3>
                            <i class="fa fa-user"></i>
                            Account Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="info-card">
                            <h4>Your Profile</h4>
                            <div class="info-item">
                                <i class="fa fa-user-circle"></i>
                                <strong>Username:</strong>
                                <span><?= htmlspecialchars($user_data['username'] ?? 'N/A') ?></span>
                            </div>
                            <?php if(isset($user_data['name']) && !empty($user_data['name'])): ?>
                            <div class="info-item">
                                <i class="fa fa-id-card"></i>
                                <strong>Full Name:</strong>
                                <span><?= htmlspecialchars($user_data['name']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if(isset($user_data['email']) && !empty($user_data['email'])): ?>
                            <div class="info-item">
                                <i class="fa fa-envelope"></i>
                                <strong>Email:</strong>
                                <span><?= htmlspecialchars($user_data['email']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <i class="fa fa-shield"></i>
                                <strong>Account Type:</strong>
                                <span><?= htmlspecialchars($account_type) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password Card -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3>
                            <i class="fa fa-lock"></i>
                            Change Password
                        </h3>
                    </div>
                    <div class="card-body">
                        <form action="setting.php" method="post" id="passwordForm">
                            <div class="form-group">
                                <label for="oldpassword">
                                    <i class="fa fa-key"></i> Current Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="oldpassword" 
                                           name="oldpassword" 
                                           placeholder="Enter your current password"
                                           autocomplete="current-password" />
                                    <button type="button" class="password-toggle" data-toggle-password="oldpassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="newpassword">
                                    <i class="fa fa-lock"></i> New Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="newpassword" 
                                           name="newpassword" 
                                           placeholder="Enter your new password"
                                           autocomplete="new-password" />
                                    <button type="button" class="password-toggle" data-toggle-password="newpassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength" id="passwordStrength">
                                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                </div>
                                <div class="password-strength-text" id="passwordStrengthText"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirmpassword">
                                    <i class="fa fa-check-circle"></i> Confirm New Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirmpassword" 
                                           name="confirmpassword" 
                                           placeholder="Re-enter your new password"
                                           autocomplete="new-password" />
                                    <button type="button" class="password-toggle" data-toggle-password="confirmpassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="password-requirements">
                                <h5><i class="fa fa-info-circle"></i> Password Requirements:</h5>
                                <ul>
                                    <li>Must be at least 6 characters long</li>
                                    <li>Should contain a mix of letters and numbers</li>
                                    <li>Avoid using common words or personal information</li>
                                    <li>Don't reuse old passwords</li>
                                </ul>
                            </div>
                            
                            <div style="margin-top: 30px; text-align: right;">
                                <button type="button" class="btn-cancel" onclick="resetForm()">
                                    <i class="fa fa-times"></i>
                                    Cancel
                                </button>
                                <button type="submit" name="save" class="btn-save">
                                    <i class="fa fa-check"></i>
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- BOOTSTRAP SCRIPTS -->
    <script src="js/bootstrap.js"></script>
    <script src="js/jquery.metisMenu.js"></script>
    <script src="js/custom1.js"></script>
    
    <script type="text/javascript">
        $(document).ready(function() {
            // Password toggle functionality
            $('.password-toggle').click(function() {
                const targetId = $(this).data('toggle-password');
                const input = $('#' + targetId);
                const icon = $(this).find('i');
                
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
            
            // Password strength meter
            $('#newpassword').on('input', function() {
                const password = $(this).val();
                const strength = calculatePasswordStrength(password);
                
                if (password.length > 0) {
                    $('#passwordStrength').addClass('show');
                    updatePasswordStrength(strength);
                } else {
                    $('#passwordStrength').removeClass('show');
                }
            });
            
            function calculatePasswordStrength(password) {
                let strength = 0;
                
                if (password.length >= 6) strength += 25;
                if (password.length >= 10) strength += 25;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
                if (/\d/.test(password)) strength += 15;
                if (/[^a-zA-Z\d]/.test(password)) strength += 10;
                
                return Math.min(strength, 100);
            }
            
            function updatePasswordStrength(strength) {
                const bar = $('#passwordStrengthBar');
                const text = $('#passwordStrengthText');
                
                bar.css('width', strength + '%');
                
                if (strength < 40) {
                    bar.css('background', '#dc3545');
                    text.text('Weak').css('color', '#dc3545');
                } else if (strength < 70) {
                    bar.css('background', '#ffc107');
                    text.text('Moderate').css('color', '#ffc107');
                } else {
                    bar.css('background', '#28a745');
                    text.text('Strong').css('color', '#28a745');
                }
            }
            
            // Form validation
            $("#passwordForm").validate({
                rules: {
                    oldpassword: {
                        required: true
                    },
                    newpassword: {
                        required: true,
                        minlength: 6
                    },
                    confirmpassword: {
                        required: true,
                        minlength: 6,
                        equalTo: "#newpassword"
                    }
                },
                messages: {
                    oldpassword: "Please enter your current password",
                    newpassword: {
                        required: "Please enter a new password",
                        minlength: "Password must be at least 6 characters long"
                    },
                    confirmpassword: {
                        required: "Please confirm your new password",
                        minlength: "Password must be at least 6 characters long",
                        equalTo: "Passwords do not match"
                    }
                },
                errorElement: "span",
                errorClass: "help-block",
                errorPlacement: function(error, element) {
                    error.addClass("help-block");
                    element.closest(".form-group").addClass("has-feedback");
                    error.insertAfter(element.closest(".input-group"));
                    
                    if (!element.next("span.form-control-feedback")[0]) {
                        $("<span class='glyphicon glyphicon-remove form-control-feedback'></span>")
                            .insertAfter(element);
                    }
                },
                success: function(label, element) {
                    $(element).closest(".form-group").removeClass("has-error").addClass("has-success");
                    $(element).next("span.form-control-feedback")
                        .removeClass("glyphicon-remove")
                        .addClass("glyphicon-ok");
                },
                highlight: function(element) {
                    $(element).closest(".form-group")
                        .removeClass("has-success")
                        .addClass("has-error");
                    $(element).next("span.form-control-feedback")
                        .removeClass("glyphicon-ok")
                        .addClass("glyphicon-remove");
                },
                unhighlight: function(element) {
                    $(element).closest(".form-group")
                        .removeClass("has-error")
                        .addClass("has-success");
                    $(element).next("span.form-control-feedback")
                        .removeClass("glyphicon-remove")
                        .addClass("glyphicon-ok");
                },
                submitHandler: function(form) {
                    $('#loadingOverlay').addClass('active');
                    form.submit();
                }
            });
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
        
        function resetForm() {
            $('#passwordForm')[0].reset();
            $('#passwordForm').validate().resetForm();
            $('.form-group').removeClass('has-error has-success');
            $('.form-control-feedback').remove();
            $('#passwordStrength').removeClass('show');
            
            // Reset password visibility
            $('input[type="text"][id$="password"]').attr('type', 'password');
            $('.password-toggle i').removeClass('fa-eye-slash').addClass('fa-eye');
        }
    </script>
</body>
</html>