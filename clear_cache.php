<?php
/**
 * Clear Cache Utility
 * Use this after uploading new student data to see immediate results
 */

session_start();

// Include cache manager
include_once('enhanced_cache_manager.php');

$message = '';
$messageType = '';

// Check if clear cache action was requested
if (isset($_POST['clear_cache'])) {
    try {
        $cache = new EnhancedCacheManager('cache/', 300, 500, 50);
        
        // Clear all cache files
        $cacheDir = 'cache/';
        $cleared = 0;
        
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*.cache');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $cleared++;
                }
            }
        }
        
        $message = "Successfully cleared $cleared cache file(s). New data will be loaded on next page visit.";
        $messageType = 'success';
        
        // Also clear session search
        unset($_SESSION['current_search']);
        
    } catch (Exception $e) {
        $message = "Error clearing cache: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Check if optimize cache was requested
if (isset($_POST['optimize_cache'])) {
    try {
        $cache = new EnhancedCacheManager('cache/', 300, 500, 50);
        $cache->optimize();
        
        $message = "Cache optimized successfully. Old and expired files removed.";
        $messageType = 'info';
        
    } catch (Exception $e) {
        $message = "Error optimizing cache: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get cache statistics
$cacheStats = [
    'total_files' => 0,
    'total_size' => 0,
    'oldest_file' => null,
    'newest_file' => null
];

$cacheDir = 'cache/';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '*.cache');
    $cacheStats['total_files'] = count($files);
    
    $fileTimes = [];
    foreach ($files as $file) {
        if (is_file($file)) {
            $cacheStats['total_size'] += filesize($file);
            $fileTimes[] = filemtime($file);
        }
    }
    
    if (!empty($fileTimes)) {
        $cacheStats['oldest_file'] = date('Y-m-d H:i:s', min($fileTimes));
        $cacheStats['newest_file'] = date('Y-m-d H:i:s', max($fileTimes));
    }
}

function formatBytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cache Management - Dropout Prediction System</title>
    <link rel="icon" type="image/png" href="img/icon1.jpg">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.css">
    <link rel="stylesheet" href="css/style1.css">
    <style>
        .cache-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
        }
        .stat-box h3 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
        }
        .stat-box p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include("php/header.php"); ?>
    
    <div id="page-wrapper">
        <div id="page-inner">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="page-head-line">
                        <i class="fa fa-database"></i> Cache Management
                    </h1>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade in">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <strong><?= $messageType == 'success' ? 'Success!' : ($messageType == 'danger' ? 'Error!' : 'Info:') ?></strong> <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="info-box">
                        <h4><i class="fa fa-info-circle"></i> About Cache Management</h4>
                        <p><strong>When to clear cache:</strong></p>
                        <ul>
                            <li>After uploading new student data files</li>
                            <li>When you need to see immediate prediction updates</li>
                            <li>If you notice outdated information being displayed</li>
                            <li>After making changes to student records in the database</li>
                        </ul>
                        <p><strong>Note:</strong> Clearing cache will force the system to regenerate predictions, which may take 30-60 seconds on the next page load.</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Cache Statistics -->
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h3><?= $cacheStats['total_files'] ?></h3>
                        <p>Cache Files</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3><?= formatBytes($cacheStats['total_size']) ?></h3>
                        <p>Total Cache Size</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3><?= $cacheStats['oldest_file'] ? 'Yes' : 'No' ?></h3>
                        <p>Has Cached Data</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3><?= $cacheStats['newest_file'] ? date('g:i A', strtotime($cacheStats['newest_file'])) : 'N/A' ?></h3>
                        <p>Last Cached</p>
                    </div>
                </div>
            </div>

            <?php if ($cacheStats['oldest_file']): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="cache-card">
                            <h4><i class="fa fa-clock-o"></i> Cache Timeline</h4>
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 200px;">Oldest Cached Data:</th>
                                    <td><?= date('F d, Y g:i:s A', strtotime($cacheStats['oldest_file'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Newest Cached Data:</th>
                                    <td><?= date('F d, Y g:i:s A', strtotime($cacheStats['newest_file'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Cache Age:</th>
                                    <td>
                                        <?php
                                        $ageInSeconds = time() - strtotime($cacheStats['newest_file']);
                                        $ageInMinutes = floor($ageInSeconds / 60);
                                        $ageInHours = floor($ageInMinutes / 60);
                                        
                                        if ($ageInHours > 0) {
                                            echo $ageInHours . ' hour' . ($ageInHours > 1 ? 's' : '') . ' ago';
                                        } elseif ($ageInMinutes > 0) {
                                            echo $ageInMinutes . ' minute' . ($ageInMinutes > 1 ? 's' : '') . ' ago';
                                        } else {
                                            echo 'Just now';
                                        }
                                        
                                        // Show warning if cache is very old
                                        if ($ageInHours > 24) {
                                            echo ' <span class="label label-warning">Old Cache</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="cache-card">
                        <h3><i class="fa fa-trash"></i> Clear Cache</h3>
                        <p>Remove all cached prediction data. Use this after uploading new student files.</p>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to clear all cache? This will force regeneration of predictions on next page load.');">
                            <button type="submit" name="clear_cache" class="btn btn-danger btn-lg">
                                <i class="fa fa-trash"></i> Clear All Cache
                            </button>
                        </form>
                        <div class="warning-box" style="margin-top: 15px;">
                            <strong><i class="fa fa-exclamation-triangle"></i> Warning:</strong> The next page load will take 30-60 seconds while predictions are regenerated.
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="cache-card">
                        <h3><i class="fa fa-wrench"></i> Optimize Cache</h3>
                        <p>Remove expired cache files and optimize cache storage without clearing active data.</p>
                        <form method="POST">
                            <button type="submit" name="optimize_cache" class="btn btn-info btn-lg">
                                <i class="fa fa-wrench"></i> Optimize Cache
                            </button>
                        </form>
                        <div class="info-box" style="margin-top: 15px; background: #d1ecf1; border-color: #bee5eb;">
                            <strong><i class="fa fa-info-circle"></i> Info:</strong> This will keep recent cache but remove old expired files.
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="cache-card">
                        <h3><i class="fa fa-question-circle"></i> Quick Actions</h3>
                        <div class="action-buttons">
                            <a href="gpa.php?refresh=1" class="btn btn-primary">
                                <i class="fa fa-refresh"></i> Go to Dashboard (Force Refresh)
                            </a>
                            <a href="gpa.php" class="btn btn-success">
                                <i class="fa fa-dashboard"></i> Go to Dashboard (Use Cache)
                            </a>
                            <button onclick="location.reload()" class="btn btn-default">
                                <i class="fa fa-repeat"></i> Reload This Page
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="cache-card">
                        <h3><i class="fa fa-book"></i> Cache FAQ</h3>
                        <div class="panel-group" id="accordion">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <a data-toggle="collapse" data-parent="#accordion" href="#faq1">
                                            Why is my new data not showing up?
                                        </a>
                                    </h4>
                                </div>
                                <div id="faq1" class="panel-collapse collapse in">
                                    <div class="panel-body">
                                        The system caches predictions to improve performance. After uploading new data, click "Clear All Cache" above or add <code>?refresh=1</code> to the URL to force a refresh.
                                    </div>
                                </div>
                            </div>
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <a data-toggle="collapse" data-parent="#accordion" href="#faq2">
                                            How long does cache last?
                                        </a>
                                    </h4>
                                </div>
                                <div id="faq2" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        Cache automatically expires after 5-10 minutes. However, if you upload new data, you should manually clear the cache to see immediate results.
                                    </div>
                                </div>
                            </div>
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <a data-toggle="collapse" data-parent="#accordion" href="#faq3">
                                            Will clearing cache delete my student data?
                                        </a>
                                    </h4>
                                </div>
                                <div id="faq3" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        No! Clearing cache only removes temporary prediction results. Your actual student data in the database remains completely safe and untouched.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="js/jquery.metisMenu.js"></script>
    <script src="js/custom1.js"></script>
</body>
</html>