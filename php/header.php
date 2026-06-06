<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Add this at the top of your file to maintain the sidebar state using cookies
if(isset($_GET['toggle_sidebar'])) {
    $sidebar_state = $_GET['toggle_sidebar'] == '1' ? '1' : '0';
    setcookie('sidebar_collapsed', $sidebar_state, time() + (86400 * 30), "/"); // Cookie valid for 30 days
    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit();
}

$sidebar_collapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == '1';

// Check if user is logged in and session variables exist
if (!isset($_SESSION['rainbow_name'])) {
    // Redirect to login page if session is not set
    header("Location: login.php");
    exit();
}

// Get user name with fallback
$user_name = isset($_SESSION['rainbow_name']) ? $_SESSION['rainbow_name'] : 'User';
$user_role = isset($_SESSION['rainbow_role']) ? $_SESSION['rainbow_role'] : 'user';
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Drop-Out Decision Support System</title>

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
            padding: 0;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
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

        .sidebar.collapsed .sidebar-header .brand-text,
        .sidebar.collapsed .sidebar-header h4 {
            display: none;
        }

        .user-profile {
            background: rgba(255,255,255,0.1);
            margin: 20px 0px;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .sidebar.collapsed .user-profile .profile-text {
            display: none;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid white;
            margin-bottom: 10px;
        }

        .sidebar.collapsed .user-avatar {
            width: 35px;
            height: 35px;
            margin-bottom: 5px;
        }

        .user-role-badge {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            position: relative;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid white;
            text-decoration: none;
        }

        .sidebar-nav a.active::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: white;
        }

        .sidebar-nav i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar.collapsed .sidebar-nav .nav-text {
            display: none;
        }

        .sidebar-toggle {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 15px;
        }

        .sidebar-toggle:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-1px);
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: 70px;
        }

        .top-bar {
            background: linear-gradient(45deg, rgba(1, 129, 55, 0.9), rgba(1, 100, 40, 0.9));
            color: white;
            padding: 15px 30px;
            margin: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .top-bar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .top-bar-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-dropdown-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #4CAF50;
            border-radius: 50%;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .logout-section {
            position: absolute;
            bottom: 20px;
            width: calc(100% - 30px);
            margin: 0 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 15px;
        }

        .logout-btn {
            width: 100%;
            background: rgba(220,53,69,0.8);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: rgba(220,53,69,1);
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }

        .sidebar.collapsed .logout-btn .logout-text {
            display: none;
        }

        /* Hover effect for collapsed sidebar */
        .sidebar.collapsed:hover {
            width: 260px;
        }

        .sidebar.collapsed:hover .sidebar-header .brand-text,
        .sidebar.collapsed:hover .sidebar-header h4,
        .sidebar.collapsed:hover .sidebar-nav .nav-text,
        .sidebar.collapsed:hover .profile-text,
        .sidebar.collapsed:hover .logout-text {
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
                width: 260px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                left: 15px;
            }
            
            .top-bar {
                padding: 15px 20px;
            }
            
            .page-title {
                font-size: 20px;
            }
        }

        @media (max-width: 480px) {
            .top-bar {
                padding: 12px 15px;
            }
            
            .page-title {
                font-size: 18px;
            }
            
            .top-bar-actions {
                gap: 10px;
            }
        }

        /* Page wrapper */
        #page-wrapper {
            margin: 0;
            padding: 5px 10px;
            min-height: calc(100vh - 70px);
        }

        @media (max-width: 768px) {
            #page-wrapper {
                padding: 15px 20px;
            }
        }

        /* Card styles for content */
        .content-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .content-card-header {
            background: rgba(1, 129, 55, 0.05);
            padding: 20px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .content-card-body {
            padding: 20px;
        }

        /* Notification badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar <?php echo $sidebar_collapsed ? 'collapsed' : ''; ?>" id="sidebar">
        <div class="sidebar-header">
            <i class="fa fa-graduation-cap fa-2x"></i>
            <div class="brand-text">
                <h4 style="margin: 10px 0 0 0;">Drop-Out Decision</h4>
                <small style="opacity: 0.8;">Support System</small>
            </div>
        </div>
        
        <!-- User Profile Section -->
        <div class="user-profile">
            <img src="img/admin.png" class="user-avatar" alt="User Avatar" />
            <div class="profile-text">
                <h5 style="margin: 0 0 5px 0; font-weight: 600;"><?php echo htmlspecialchars($user_name); ?></h5>
                <span class="user-role-badge"><?php echo htmlspecialchars(ucfirst($user_role)); ?></span>
                <div style="margin-top: 8px;">
                    <span class="status-indicator"></span>
                    <small style="opacity: 0.8;">Online</small>
                </div>
            </div>
        </div>
        
        <ul class="sidebar-nav">
            <li>
                <a class="<?php if($page=='dashboard'){ echo 'active';}?>" href="index.php">
                    <i class="fa fa-upload"></i>
                    <span class="nav-text">Upload Data</span>
                </a>
            </li>

            <li>
                <a class="<?php if($page=='predict'){ echo 'active';}?>" href="gpa.php">
                <i class="fa fa-exclamation-circle"></i>
                    <span class="nav-text">Drop-out Prediction</span>
                </a>
            </li>
            <li>
                <a class="<?php if($page=='future'){ echo 'active';}?>" href="future.php">
                    <i class="fa fa-area-chart"></i>
                    <span class="nav-text">Future Forecasting</span>
                </a>
            </li>
            <li>
                <a class="<?php if($page=='setting'){ echo 'active';}?>" href="setting.php">
                    <i class="fa fa-cogs"></i>
                    <span class="nav-text">Account Settings</span>
                </a>
            </li>

            <?php if ($user_role === 'admin'): ?>
                <li style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                    <a href="admin.php">
                        <i class="fa fa-shield"></i>
                        <span class="nav-text">Admin Panel</span>
                        <span class="notification-badge" style="position: absolute; right: 20px;">!</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Logout Section -->
        <div class="logout-section">
            <a href="logout.php" class="logout-btn">
                <i class="fa fa-sign-out"></i>
                <span class="logout-text">Sign Out</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content <?php echo $sidebar_collapsed ? 'expanded' : ''; ?>" id="mainContent">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-content">
                <div style="display: flex; align-items: center;">
                    <!-- Sidebar Toggle Button -->
                    <button class="sidebar-toggle" onclick="toggleSidebar()" id="sidebarToggle">
                        <i class="fa fa-bars"></i>
                    </button>
                    
                </div>
                <div class="top-bar-actions">
                    <span style="opacity: 0.9;">
                        <i class="fa fa-calendar"></i>
                        <?php echo date('M j, Y - H:i'); ?>
                    </span>
                    <div class="user-dropdown">
                        <div class="user-dropdown-btn">
                            <i class="fa fa-user"></i>
                            <span><?php echo htmlspecialchars($user_name); ?></span>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Content Wrapper -->
        <div id="page-wrapper">
            <!-- Your page content will go here -->

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggle = document.getElementById('sidebarToggle');
            
            // Check if mobile
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-open');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                
                // Update cookie
                const isCollapsed = sidebar.classList.contains('collapsed');
                document.cookie = `sidebar_collapsed=${isCollapsed ? '1' : '0'}; path=/; max-age=${86400 * 30}`;
            }
        }

        // Handle responsive sidebar
        function handleResize() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
            } else {
                sidebar.classList.remove('mobile-open');
                // Restore collapsed state from cookie
                const isCollapsed = document.cookie.split(';')
                    .find(row => row.trim().startsWith('sidebar_collapsed='))
                    ?.split('=')[1] === '1';
                
                if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                }
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const toggle = document.getElementById('sidebarToggle');
                
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });

        // Initialize on load
        window.addEventListener('load', handleResize);
        window.addEventListener('resize', handleResize);

        // Smooth scrolling for navigation
        document.querySelectorAll('.sidebar-nav a').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                // Add loading state if needed
                this.style.opacity = '0.7';
                setTimeout(() => {
                    this.style.opacity = '1';
                }, 100);
            });
        });

        // Add active state management
        function setActiveNavItem() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.sidebar-nav a');
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href === currentPage) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        }

        // Call on page load
        document.addEventListener('DOMContentLoaded', setActiveNavItem);
    </script>