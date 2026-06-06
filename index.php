<?php 
$page='dashboard';
include("php/dbconnect.php");
include("php/checklogin.php");

// Function to check if content_warnings column exists
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// Function to get recent upload warnings (FIXED)
// FIXED: Function to get recent upload warnings and failures
function getRecentUploadWarnings($conn, $limit = 5) {
    $warnings = [];
    
    // First check if upload_logs table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'upload_logs'");
    if (!$table_check || $table_check->num_rows == 0) {
        return $warnings; // Table doesn't exist, return empty array
    }
    
    // Check if content_warnings column exists
    $has_content_warnings = columnExists($conn, 'upload_logs', 'content_warnings');
    
    // FIXED: Query to get both failed uploads AND successful uploads with warnings
    if ($has_content_warnings) {
        // Get failed uploads OR successful uploads with content warnings
        $sql = "SELECT 
                    year, 
                    semester, 
                    filename, 
                    CASE 
                        WHEN status = 'failed' THEN error_message
                        ELSE content_warnings 
                    END as issue_message,
                    upload_date, 
                    COALESCE(uploaded_by_name, uploaded_by_username, 'Unknown') as uploaded_by_name, 
                    records_success,
                    records_error,
                    records_processed,
                    status
                FROM upload_logs 
                WHERE (
                    (status = 'failed' AND error_message IS NOT NULL AND error_message != '') 
                    OR 
                    (status = 'success' AND content_warnings IS NOT NULL AND content_warnings != '')
                )
                AND upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY upload_date DESC 
                LIMIT $limit";
    } else {
        // Fallback: Get failed uploads using error_message
        $sql = "SELECT 
                    year, 
                    semester, 
                    filename, 
                    error_message as issue_message,
                    upload_date, 
                    COALESCE(uploaded_by_name, uploaded_by_username, 'Unknown') as uploaded_by_name, 
                    records_success,
                    records_error,
                    records_processed,
                    status
                FROM upload_logs 
                WHERE status = 'failed' 
                AND error_message IS NOT NULL 
                AND error_message != ''
                AND upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY upload_date DESC 
                LIMIT $limit";
    }
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $warnings[] = $row;
        }
    }
    
    return $warnings;
}


// Get recent warnings
$recent_warnings = getRecentUploadWarnings($conn);

// Check if we need to update the database schema
$needs_schema_update = false;
$table_check = $conn->query("SHOW TABLES LIKE 'upload_logs'");
if ($table_check && $table_check->num_rows > 0) {
    if (!columnExists($conn, 'upload_logs', 'content_warnings')) {
        $needs_schema_update = true;
    }
}

// Check for URL parameters to show messages (for redirect scenarios)
$show_success_message = isset($_GET['upload_success']) ? $_GET['upload_success'] : '';
$show_error_message = isset($_GET['upload_error']) ? $_GET['upload_error'] : '';
// Helper function to format issue messages for display
function formatIssueMessage($message, $max_length = 300) {
    // Clean up formatting
    $message = str_replace(['\r\n', '\n', '\r'], ' ', $message);
    $message = preg_replace('/\s+/', ' ', trim($message));
    
    // Remove HTML entities
    $message = html_entity_decode($message);
    
    // Truncate if too long
    if (strlen($message) > $max_length) {
        $message = substr($message, 0, $max_length) . '...';
    }
    
    return $message;
}

// Helper function to get severity badge
function getIssueSeverityBadge($status, $records_error) {
    if ($status == 'failed') {
        return '<span style="color: #dc3545; font-weight: bold; margin-left: 10px;">
                    <i class="fa fa-times-circle"></i> FAILED
                </span>';
    } elseif ($records_error > 0) {
        return '<span style="color: #f39c12; font-weight: bold; margin-left: 10px;">
                    <i class="fa fa-exclamation-triangle"></i> PARTIAL SUCCESS
                </span>';
    } else {
        return '<span style="color: #f39c12; font-weight: bold; margin-left: 10px;">
                    <i class="fa fa-exclamation-triangle"></i> WARNINGS
                </span>';
    }
}

// Display Recent Upload Issues HTML
function displayRecentUploadIssues($warnings) {
    if (empty($warnings)) {
        return '';
    }
    
    $html = '<div class="row" id="warnings-panel" style="display: block;">
            <div class="col-md-12">
                <div class="warning-panel show">
                    <button type="button" class="close-warnings" onclick="hideWarningsPanel()" title="Close warnings">
                        <i class="fa fa-times"></i>
                    </button>
                    <h5><i class="fa fa-exclamation-triangle" style="color: #f39c12;"></i> Recent Upload Issues</h5>
                    <p style="margin-bottom: 10px; font-size: 12px;">The following recent uploads had issues that need attention:</p>
                    
                    <div id="warnings-list">';
    
    foreach ($warnings as $warning) {
        $issue_class = $warning['status'] == 'failed' ? 'failed' : '';
        $issue_message = formatIssueMessage($warning['issue_message'], 400);
        $severity_badge = getIssueSeverityBadge($warning['status'], $warning['records_error']);
        
        $html .= '<div class="warning-item ' . $issue_class . '">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                        <div style="flex: 1;">
                            <strong>' . htmlspecialchars($warning['filename']) . '</strong> ';
        
        if (isset($warning['year']) && isset($warning['semester'])) {
            $html .= '(' . htmlspecialchars($warning['year']) . ' - Semester ' . htmlspecialchars($warning['semester']) . ')';
        }
        
        $html .= $severity_badge . '
                        </div>
                    </div>
                    
                    <div style="margin-top: 8px; padding: 8px; background-color: #fff; border-radius: 4px; border-left: 3px solid ' . ($warning['status'] == 'failed' ? '#dc3545' : '#f39c12') . ';">
                        <small style="color: ' . ($warning['status'] == 'failed' ? '#721c24' : '#856404') . '; line-height: 1.5; display: block;">
                            ' . nl2br(htmlspecialchars($issue_message)) . '
                        </small>
                    </div>
                    
                    <div class="warning-meta">
                        <i class="fa fa-clock-o"></i> ' . date('M j, Y g:i A', strtotime($warning['upload_date'])) . ' 
                        by ' . htmlspecialchars($warning['uploaded_by_name']);
        
        if ($warning['status'] == 'failed') {
            $html .= ' | <i class="fa fa-times"></i> Upload failed';
            if (isset($warning['records_processed']) && $warning['records_processed'] > 0) {
                $html .= ' (' . $warning['records_processed'] . ' records attempted)';
            }
        } else {
            if (isset($warning['records_success']) && $warning['records_success'] > 0) {
                $html .= ' | <i class="fa fa-check"></i> ' . $warning['records_success'] . ' records processed';
            }
            if (isset($warning['records_error']) && $warning['records_error'] > 0) {
                $html .= ' | <i class="fa fa-exclamation-circle"></i> ' . $warning['records_error'] . ' errors';
            }
        }
        
        $html .= '</div>
                </div>';
    }
    
    $html .= '</div>
                    
                    <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ffeaa7; font-size: 11px; color: #666;">
                        <i class="fa fa-info-circle"></i> 
                        <strong>Tips:</strong> Review your CSV data for accuracy. Failed uploads need to be fixed and re-uploaded. 
                        Warnings indicate minor issues that were handled but should be reviewed.
                    </div>
                </div>
            </div>
        </div>';
    
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Drop-Out Decision Support System</title>

    <!-- FAVICON -->
    <link rel="icon" type="image/png" href="img/icon1.jpg">
    <!-- BOOTSTRAP STYLES-->
    <link href="css/bootstrap.css" rel="stylesheet" />
    <!-- FONTAWESOME STYLES-->
    <link href="css/font-awesome.css" rel="stylesheet" />
    <!--CUSTOM BASIC STYLES-->
    <link href="css/style1.css" rel="stylesheet" />
    <!--CUSTOM MAIN STYLES-->
    <link href="css/custom.css" rel="stylesheet" />
    <!-- GOOGLE FONTS-->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />

    <style>
        /* FIX: Remove excess whitespace at bottom */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        #page-wrapper {
            min-height: calc(100vh - 60px); /* Adjust 60px based on your header height */
            padding-bottom: 30px; /* Add some padding at bottom */
        }
        
        #page-inner {
            padding-bottom: 30px;
        }
        
        .main-box {
            border-radius: 10px;
            padding: 15px 10px;
            text-align: center;
            color: #fff;
            font-size: 14px;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .main-box:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.3);
            cursor: pointer;
        }
        
        .page-head-line {
            font-size: 20px;
            margin-bottom: 20px;
        }
        
        .main-box h4, .main-box h5 {
            margin-top: 10px;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .mb-purple { background-color: #8e44ad; }
        .mb-green { background-color: #27ae60; }
        .mb-secondary { background-color: #34495e; }
        .mb-dull { background-color: #7f8c8d; }
        .mb-maroon { background-color: #c0392b; }
        .mb-yell { background-color: #f39c12; }
        
        .page-head-line {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 30px;
            color: #34495e;
        }
        
        .main-box a {
            color: #fff;
            text-decoration: none;
            display: block;
        }
        
        /* IMPROVED WARNING PANEL STYLES */
        .warning-panel {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: none;
        }
        
        .warning-panel.show {
            display: block;
        }
        
        .warning-item {
            margin-bottom: 12px;
            padding: 8px;
            background-color: #fff;
            border-radius: 5px;
            border-left: 4px solid #f39c12;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .warning-item.failed {
            border-left-color: #dc3545;
        }
        
        .warning-item:last-child {
            margin-bottom: 0;
        }
        
        .warning-meta {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        
        .close-warnings {
            float: right;
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: #666;
            padding: 0;
            margin: 0;
        }
        
        .close-warnings:hover {
            color: #000;
        }
        
        .schema-update-panel {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* POPUP MESSAGE STYLES */
        .popup-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            min-width: 300px;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.5s ease-in-out;
        }
        
        .popup-message.show {
            opacity: 1;
            transform: translateX(0);
        }
        
        .popup-message.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .popup-message.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .popup-message .close-popup {
            position: absolute;
            top: 5px;
            right: 10px;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
        }
        
        .popup-message .close-popup:hover {
            opacity: 1;
        }
        
        .popup-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background-color: rgba(0,0,0,0.2);
            border-radius: 0 0 8px 8px;
            transition: width linear;
        }
        
        .popup-message.success .popup-progress {
            background-color: #28a745;
        }
        
        .popup-message.error .popup-progress {
            background-color: #dc3545;
        }
        
        /* Panel adjustments to prevent excess space */
        .panel {
            margin-bottom: 20px;
        }
        
        .panel:last-child {
            margin-bottom: 0;
        }
        #page-inner {
    width: 100%;
    margin: 10px 20px 10px 0px;
    background-color: #fff !important;
    padding: 10px;
    min-height: 100px;
}
    </style>
</head>

<?php include("php/header.php"); ?>

<div id="page-wrapper">
    <div id="page-inner">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-head-line" style="border-bottom:2px solid rgba(1, 129, 55);">Import Student Information</h1>
            </div>
        </div>

        <!-- Schema Update Notice -->
        <?php if ($needs_schema_update): ?>
        <div class="row" id="schema-update-panel">
            <div class="col-md-12">
                <div class="schema-update-panel">
                    <button type="button" class="close-warnings" onclick="document.getElementById('schema-update-panel').style.display='none';" title="Close notice">
                        <i class="fa fa-times"></i>
                    </button>
                    <h5><i class="fa fa-info-circle" style="color: #155724;"></i> Database Update Available</h5>
                    <p style="margin-bottom: 10px;">
                        Enhanced upload logging features are available. Click the button below to update your database schema.
                    </p>
                    <button class="btn btn-success btn-sm" onclick="updateSchema()">
                        <i class="fa fa-database"></i> Update Database Schema
                    </button>
                    <div id="schema-update-result" style="margin-top: 10px;"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- CSV Upload -->
        <div class="panel panel-primary" style="border-color:rgba(1, 129, 55);">
            <div class="panel-heading" style="background-color: rgba(1, 129, 55); color: white; font-weight: 100;">Upload Student Data (CSV)</div>
            <div class="panel-body">
                <form id="uploadForm" action="upload_csv.php" method="post" enctype="multipart/form-data" class="form-inline">
                    <select name="year" class="form-control" required style="margin-right: 10px;">
                        <option value="">Select Year</option>
                        <?php 
                        // Generate years from 2020 to 2025
                        for ($y = 2025; $y >= 2020; $y--) {
                            $selected = ($y == date('Y')) ? 'selected' : '';
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>
                    <select name="semester" class="form-control" required style="margin-right: 10px;">
                        <option value="">Select Semester</option>
                        <option value="1">1st Semester</option>
                        <option value="2">2nd Semester</option>
                    </select>
                    <input type="file" name="csvFile" class="form-control" accept=".csv" required style="margin-right: 10px;">
                    <button type="submit" class="btn btn-success"><i class="fa fa-upload"></i> Upload</button>
                </form>
                
                <!-- CSV Format Helper -->
                <div style="margin-top: 15px; font-size: 12px; color: #666;">
                    <i class="fa fa-info-circle"></i> 
                    <strong>Expected CSV Format:</strong> 
                    <span style="font-family: monospace; background-color: #f8f9fa; padding: 2px 4px;">
                        id, StudentID, emailid, sname, about, contact, fees, year, balance, delete_status, course, Attendance, GPA
                    </span>
                    <br>
                    <span style="margin-left: 16px;">
                        ⚠️ <strong style="color: #dc3545;">IMPORTANT:</strong> Filename MUST contain the year (e.g., students_2024.csv) and match the selected year above.
                        <br>
                        <span style="margin-left: 16px;">
                            ✓ StudentID should follow OLFU format (e.g., OLFU2025) 
                            ✓ Fees/Balance should be numeric 
                            ✓ Year level: 1-4 
                            ✓ Attendance: 0-100 
                            ✓ GPA: 1.0-5.0
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Recent Upload Warnings -->
        <?php if (!empty($recent_warnings)): ?>
<div class="row" id="warnings-panel" style="display: block;">
    <div class="col-md-12">
        <div class="warning-panel show">
            <button type="button" class="close-warnings" onclick="hideWarningsPanel()" title="Close warnings">
                <i class="fa fa-times"></i>
            </button>
            <h5>
                <i class="fa fa-exclamation-triangle" style="color: #f39c12;"></i> 
                Recent Upload Issues (Last 30 Days)
            </h5>
            <p style="margin-bottom: 10px; font-size: 12px;">
                The following recent uploads had issues that need attention:
            </p>
            
            <div id="warnings-list">
                <?php foreach ($recent_warnings as $warning): ?>
                <div class="warning-item <?= $warning['status'] == 'failed' ? 'failed' : '' ?>">
                    <!-- Header with filename and status -->
                    <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                        <div style="flex: 1;">
                            <strong><?= htmlspecialchars($warning['filename']) ?></strong> 
                            <?php if (isset($warning['year']) && isset($warning['semester'])): ?>
                                (<?= htmlspecialchars($warning['year']) ?> - Semester <?= htmlspecialchars($warning['semester']) ?>)
                            <?php endif; ?>
                            
                            <?php if ($warning['status'] == 'failed'): ?>
                                <span style="color: #dc3545; font-weight: bold; margin-left: 10px;">
                                    <i class="fa fa-times-circle"></i> FAILED
                                </span>
                            <?php elseif (isset($warning['records_error']) && $warning['records_error'] > 0): ?>
                                <span style="color: #f39c12; font-weight: bold; margin-left: 10px;">
                                    <i class="fa fa-exclamation-triangle"></i> PARTIAL SUCCESS
                                </span>
                            <?php else: ?>
                                <span style="color: #f39c12; font-weight: bold; margin-left: 10px;">
                                    <i class="fa fa-exclamation-triangle"></i> WARNINGS
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Error/Warning Message Box -->
                    <div style="margin-top: 8px; padding: 10px; background-color: <?= $warning['status'] == 'failed' ? '#f8d7da' : '#fff3cd' ?>; border-radius: 4px; border-left: 4px solid <?= $warning['status'] == 'failed' ? '#dc3545' : '#f39c12' ?>;">
                        <div style="font-weight: 600; color: <?= $warning['status'] == 'failed' ? '#721c24' : '#856404' ?>; margin-bottom: 5px; font-size: 12px;">
                            <i class="fa fa-<?= $warning['status'] == 'failed' ? 'times-circle' : 'exclamation-triangle' ?>"></i>
                            <?= $warning['status'] == 'failed' ? 'Upload Rejected - Reason:' : 'Issues Detected:' ?>
                        </div>
                        <small style="color: <?= $warning['status'] == 'failed' ? '#721c24' : '#856404' ?>; line-height: 1.6; display: block; white-space: pre-wrap; font-family: 'Courier New', monospace;">
                            <?php 
                            // Get the issue message - prioritize error_message for failed uploads
                            $issue_message = '';
                            if ($warning['status'] == 'failed' && !empty($warning['issue_message'])) {
                                $issue_message = $warning['issue_message'];
                            } elseif (!empty($warning['issue_message'])) {
                                $issue_message = $warning['issue_message'];
                            }
                            
                            // Clean up the message
                            $issue_message = str_replace(['\r\n', '\n\r', '\r'], "\n", $issue_message);
                            $issue_message = html_entity_decode($issue_message);
                            
                            // Truncate if too long
                            if (strlen($issue_message) > 500) {
                                $issue_message = substr($issue_message, 0, 500) . '...';
                            }
                            
                            echo htmlspecialchars($issue_message);
                            ?>
                        </small>
                    </div>
                    
                    <!-- Metadata footer -->
                    <div class="warning-meta">
                        <i class="fa fa-clock-o"></i> 
                        <?= date('M j, Y g:i A', strtotime($warning['upload_date'])) ?> 
                        by <?= htmlspecialchars($warning['uploaded_by_name']) ?>
                        
                        <?php if ($warning['status'] == 'failed'): ?>
                            | <i class="fa fa-times"></i> Upload failed
                            <?php if (isset($warning['records_processed']) && $warning['records_processed'] > 0): ?>
                                (<?= $warning['records_processed'] ?> records attempted)
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (isset($warning['records_success']) && $warning['records_success'] > 0): ?>
                                | <i class="fa fa-check"></i> <?= $warning['records_success'] ?> records processed
                            <?php endif; ?>
                            <?php if (isset($warning['records_error']) && $warning['records_error'] > 0): ?>
                                | <i class="fa fa-exclamation-circle"></i> <?= $warning['records_error'] ?> errors
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Help text footer -->
            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ffeaa7; font-size: 11px; color: #666;">
                <i class="fa fa-info-circle"></i> 
                <strong>What to do:</strong> 
                Failed uploads need to be corrected and re-uploaded. 
                Review error messages to understand what went wrong. 
                Common issues: year mismatch, missing required fields, invalid data format, or duplicate files.
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

    </div> <!-- /. PAGE INNER  -->
</div> <!-- /. PAGE WRAPPER  -->

<!-- JS Scripts -->
<script src="js/jquery-1.10.2.js"></script>	
<script src="js/bootstrap.js"></script>
<script src="js/jquery.metisMenu.js"></script>
<script src="js/custom1.js"></script>

<script>
// [Same JavaScript code as before - truncated for brevity but include all the original JS]
// Function to extract year from filename - STRICT MODE
function extractYearFromFilename(filename) {
    let globalMatches = filename.match(/\d{4}/g);
    let uniqueYears = [];
    
    if (globalMatches) {
        globalMatches.forEach(match => {
            let year = parseInt(match);
            if (year >= 2020 && year <= 2030 && !uniqueYears.includes(year)) {
                uniqueYears.push(year);
            }
        });
    }
    
    return {
        year: uniqueYears.length > 0 ? uniqueYears[0] : null,
        allYears: uniqueYears,
        hasMultipleYears: uniqueYears.length > 1,
        yearCount: uniqueYears.length
    };
}

function hideWarningsPanel() {
    $('#warnings-panel').fadeOut(300);
}

function showWarningsPanel() {
    $('#warnings-panel').fadeIn(300);
    $('#warning-panel-content').addClass('show');
}

function showPopupMessage(message, type, duration = 5000) {
    $('.popup-message').remove();
    
    var popupHtml = '<div class="popup-message ' + type + '">' +
                    '<button class="close-popup" onclick="closePopup(this)">&times;</button>' +
                    '<div class="popup-content">' + message + '</div>' +
                    '<div class="popup-progress"></div>' +
                    '</div>';
    
    $('body').append(popupHtml);
    
    var popup = $('.popup-message');
    var progress = popup.find('.popup-progress');
    
    setTimeout(function() {
        popup.addClass('show');
    }, 100);
    
    progress.css({
        'width': '100%',
        'transition': 'width ' + duration + 'ms linear'
    });
    
    setTimeout(function() {
        progress.css('width', '0%');
    }, 200);
    
    setTimeout(function() {
        closePopup(popup[0]);
    }, duration);
}

function closePopup(element) {
    var popup = $(element).closest('.popup-message');
    popup.removeClass('show');
    setTimeout(function() {
        popup.remove();
    }, 500);
}

$(document).ready(function() {
    <?php if ($show_success_message): ?>
        showPopupMessage(
            '<i class="fa fa-check-circle" style="color: #28a745; margin-right: 8px;"></i>' +
            '<strong>Upload Successful!</strong><br>' +
            '<?= addslashes(htmlspecialchars($show_success_message)) ?>',
            'success',
            6000
        );
    <?php endif; ?>
    
    <?php if ($show_error_message): ?>
        showPopupMessage(
            '<i class="fa fa-times-circle" style="color: #dc3545; margin-right: 8px;"></i>' +
            '<strong>Upload Failed!</strong><br>' +
            '<?= addslashes(htmlspecialchars($show_error_message)) ?>',
            'error',
            8000
        );
    <?php endif; ?>
    
    // Form submission handler with year validation
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        
        var year = $('[name="year"]').val();
        var semester = $('[name="semester"]').val();
        var file = $('[name="csvFile"]')[0].files[0];
        
        if (!year || !semester || !file) {
            showPopupMessage(
                '<i class="fa fa-exclamation-triangle"></i> <strong>Validation Error!</strong><br>Please fill in all required fields.',
                'error',
                4000
            );
            return false;
        }
        
        if (!file.name.toLowerCase().endsWith('.csv')) {
            showPopupMessage(
                '<i class="fa fa-file-text"></i> <strong>Invalid File Type!</strong><br>Please select a valid CSV file.',
                'error',
                4000
            );
            return false;
        }
        
        var yearInfo = extractYearFromFilename(file.name);
        var selectedYear = parseInt(year);
        
        if (yearInfo.yearCount === 0) {
            showPopupMessage(
                '<i class="fa fa-calendar-times-o"></i> <strong>Missing Year in Filename!</strong><br>' +
                'Your filename does not contain any year information.<br><br>' +
                '<strong>Current filename:</strong> ' + file.name + '<br><br>' +
                '<strong>Required format:</strong> Filename must include the year (e.g., students_' + selectedYear + '.csv)',
                'error',
                12000
            );
            return false;
        }
        
        if (yearInfo.hasMultipleYears) {
            var yearsList = yearInfo.allYears.join(', ');
            showPopupMessage(
                '<i class="fa fa-exclamation-triangle"></i> <strong>Multiple Years Detected!</strong><br>' +
                'Your filename contains multiple years: <strong>' + yearsList + '</strong><br><br>' +
                'Please rename to: students_' + selectedYear + '.csv',
                'error',
                12000
            );
            return false;
        }
        
        if (yearInfo.year !== selectedYear) {
            showPopupMessage(
                '<i class="fa fa-calendar"></i> <strong>Year Mismatch!</strong><br>' +
                'Selected year: <strong>' + selectedYear + '</strong><br>' +
                'Filename year: <strong>' + yearInfo.year + '</strong>',
                'error',
                12000
            );
            return false;
        }
        
        if (file.size > 10 * 1024 * 1024) {
            showPopupMessage(
                '<i class="fa fa-file-text"></i> <strong>File Too Large!</strong><br>File size must be less than 10MB.',
                'error',
                5000
            );
            return false;
        }
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        
        // Show processing message
        var processingDuration = 60000; // 90 seconds
        showPopupMessage(
            '<i class="fa fa-spinner fa-spin"></i> <strong>Processing Upload...</strong><br>Validating and uploading your CSV file.',
            'success',
            processingDuration
        );
        
        var formData = new FormData(this);
        var processingStartTime = Date.now();
        
        $.ajax({
            url: 'upload_csv.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 70000,
            success: function(response) {
                // Calculate remaining time for processing message
                var elapsedTime = Date.now() - processingStartTime;
                var remainingTime = Math.max(0, processingDuration - elapsedTime);
                
                // Wait for processing message to finish
                setTimeout(function() {
                    // Close processing message
                    $('.popup-message').removeClass('show');
                    setTimeout(function() {
                        $('.popup-message').remove();
                    }, 500);
                    
                    // Show result message after processing message is closed
                    setTimeout(function() {
                        var tempDiv = $('<div>').html(response);
                    
                        if (tempDiv.find('.alert-success').length > 0) {
                            var successText = tempDiv.find('.alert-success').last().text().trim();
                            successText = successText.replace(/\s+/g, ' ');
                            
                            var message = '<strong>CSV uploaded successfully!</strong><br>' + successText.substring(0, 150);
                            
                            showPopupMessage(message, 'success', 6000);
                            document.getElementById('uploadForm').reset();
                        } else if (tempDiv.find('.alert-danger').length > 0) {
                            var errorText = tempDiv.find('.alert-danger').first().text().trim();
                            errorText = errorText.replace(/\s+/g, ' ').replace('Upload Failed!', '').trim();
                            
                            var message = '<strong>Upload rejected!</strong><br>' + errorText.substring(0, 200);
                            
                            showPopupMessage(message, 'error', 8000);
                        }
                        
                        submitBtn.html(originalText).prop('disabled', false);
                    }, 600);
                }, remainingTime);
            },
            error: function(xhr, status, error) {
                // Calculate remaining time for processing message
                var elapsedTime = Date.now() - processingStartTime;
                var remainingTime = Math.max(0, processingDuration - elapsedTime);
                
                // Wait for processing message to finish
                setTimeout(function() {
                    // Close processing message
                    $('.popup-message').removeClass('show');
                    setTimeout(function() {
                        $('.popup-message').remove();
                    }, 500);
                    
                    // Show error message after processing message is closed
                    setTimeout(function() {
                        var message = '<strong>Upload failed!</strong><br>';
                        if (status === 'timeout') {
                            message += 'Upload took too long. Please try with a smaller file.';
                        } else {
                            message += 'Server error. Please try again.';
                        }
                        
                        showPopupMessage(message, 'error', 6000);
                        submitBtn.html(originalText).prop('disabled', false);
                    }, 600);
                }, remainingTime);
            }
        });
    });
});
</script>

</body>
</html>