<?php
/**
 * DIAGNOSTIC TOOL: Test all paths and configurations
 * Save this as test_paths.php and run it in your browser
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>Path Diagnostic Tool</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
.success { border-left-color: #28a745; }
.warning { border-left-color: #ffc107; }
.error { border-left-color: #dc3545; }
h2 { margin-top: 0; }
pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
.code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style></head><body>";

echo "<h1>🔍 Prediction System Path Diagnostics</h1>";

// ============================================================================
// 1. PHP ENVIRONMENT
// ============================================================================
echo "<div class='box'>";
echo "<h2>1️⃣ PHP Environment</h2>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Operating System:</strong> " . PHP_OS . "<br>";
echo "<strong>Is Windows:</strong> " . (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'YES' : 'NO') . "<br>";
echo "<strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "</div>";

// ============================================================================
// 2. PATH DETECTION
// ============================================================================
echo "<div class='box'>";
echo "<h2>2️⃣ Path Detection</h2>";

$paths = [
    '__FILE__' => __FILE__,
    'dirname(__FILE__)' => dirname(__FILE__),
    'realpath(dirname(__FILE__))' => realpath(dirname(__FILE__)),
    'getcwd()' => getcwd(),
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
];

foreach ($paths as $label => $path) {
    echo "<strong>$label:</strong><br>";
    echo "<span class='code'>$path</span><br><br>";
}

$script_dir = realpath(dirname(__FILE__));
echo "<div style='background: #d4edda; padding: 10px; border-radius: 3px;'>";
echo "✅ <strong>Using Script Directory:</strong> <span class='code'>$script_dir</span>";
echo "</div>";
echo "</div>";

// ============================================================================
// 3. PYTHON FILES CHECK
// ============================================================================
echo "<div class='box'>";
echo "<h2>3️⃣ Python Files Check</h2>";

$python_files = [
    'predict.py',
    'prediction_watcher.py',
    'scaler_v2.pkl',
    'svm_model_v2.pkl',
    'logistic_model_v2.pkl',
    'label_encoder_v2.pkl'
];

$all_found = true;

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f8f9fa;'><th style='text-align: left; padding: 8px;'>File</th><th style='padding: 8px;'>Status</th><th style='text-align: left; padding: 8px;'>Full Path</th></tr>";

foreach ($python_files as $file) {
    $full_path = $script_dir . DIRECTORY_SEPARATOR . $file;
    $exists = file_exists($full_path);
    $readable = is_readable($full_path);
    
    $status = $exists ? '✅ Found' : '❌ Missing';
    $color = $exists ? '#28a745' : '#dc3545';
    
    if (!$exists) $all_found = false;
    
    echo "<tr>";
    echo "<td style='padding: 8px;'><strong>$file</strong></td>";
    echo "<td style='padding: 8px; text-align: center; color: $color;'>$status</td>";
    echo "<td style='padding: 8px; font-family: monospace; font-size: 11px;'>$full_path</td>";
    echo "</tr>";
    
    if ($exists && !$readable) {
        echo "<tr><td colspan='3' style='padding: 4px 8px; color: #856404; background: #fff3cd;'>⚠️ File exists but is not readable - check permissions</td></tr>";
    }
}

echo "</table>";

if (!$all_found) {
    echo "<div style='background: #f8d7da; padding: 10px; margin-top: 10px; border-radius: 3px; color: #721c24;'>";
    echo "⚠️ <strong>Some files are missing!</strong> Make sure all Python files are in: <span class='code'>$script_dir</span>";
    echo "</div>";
}

echo "</div>";

// ============================================================================
// 4. DIRECTORY CONTENTS
// ============================================================================
echo "<div class='box'>";
echo "<h2>4️⃣ Directory Contents</h2>";
echo "<strong>Listing all files in:</strong> <span class='code'>$script_dir</span><br><br>";

if (is_dir($script_dir)) {
    $files = scandir($script_dir);
    echo "<ul style='columns: 2; column-gap: 20px;'>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $is_python = (pathinfo($file, PATHINFO_EXTENSION) === 'py');
            $is_model = (pathinfo($file, PATHINFO_EXTENSION) === 'pkl');
            $icon = $is_python ? '🐍' : ($is_model ? '🤖' : '📄');
            echo "<li>$icon $file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color: #dc3545;'>❌ Directory not accessible</p>";
}
echo "</div>";

// ============================================================================
// 5. PYTHON EXECUTABLE CHECK
// ============================================================================
echo "<div class='box'>";
echo "<h2>5️⃣ Python Executable Check</h2>";

$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

$candidates = $is_windows ? 
    ['py', 'python', 'python3', 'C:\\Python312\\python.exe', 'C:\\Python311\\python.exe'] :
    ['python3', 'python', '/usr/bin/python3', '/usr/local/bin/python3'];

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f8f9fa;'><th style='text-align: left; padding: 8px;'>Python Command</th><th style='padding: 8px;'>Found</th><th style='text-align: left; padding: 8px;'>Version</th></tr>";

$python_found = null;

foreach ($candidates as $python) {
    if ($is_windows) {
        $check = shell_exec("where $python 2>nul");
        $version = shell_exec("$python --version 2>&1");
    } else {
        $check = shell_exec("which $python 2>/dev/null");
        $version = shell_exec("$python --version 2>&1");
    }
    
    $found = !empty($check);
    $status = $found ? '✅ Yes' : '❌ No';
    $color = $found ? '#28a745' : '#dc3545';
    
    if ($found && !$python_found) {
        $python_found = $python;
    }
    
    echo "<tr>";
    echo "<td style='padding: 8px;'><strong>$python</strong></td>";
    echo "<td style='padding: 8px; text-align: center; color: $color;'>$status</td>";
    echo "<td style='padding: 8px;'>" . ($version ?: 'N/A') . "</td>";
    echo "</tr>";
}

echo "</table>";

if ($python_found) {
    echo "<div style='background: #d4edda; padding: 10px; margin-top: 10px; border-radius: 3px;'>";
    echo "✅ <strong>Python found:</strong> <span class='code'>$python_found</span>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; margin-top: 10px; border-radius: 3px; color: #721c24;'>";
    echo "❌ <strong>No Python executable found!</strong> Install Python 3.6+ and ensure it's in your system PATH.";
    echo "</div>";
}

echo "</div>";

// ============================================================================
// 6. PROCESS CHECK
// ============================================================================
echo "<div class='box'>";
echo "<h2>6️⃣ Running Processes Check</h2>";

if ($is_windows) {
    $processes = shell_exec('tasklist /FI "IMAGENAME eq python.exe" 2>nul');
    echo "<strong>Python processes:</strong><br>";
    echo "<pre>" . htmlspecialchars($processes ?: "None found") . "</pre>";
    
    $watcher_check = shell_exec('tasklist /V 2>nul | findstr /i "prediction_watcher"');
    if (!empty($watcher_check)) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 3px;'>";
        echo "✅ <strong>Prediction watcher IS running</strong>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 3px;'>";
        echo "⚠️ <strong>Prediction watcher is NOT running</strong>";
        echo "</div>";
    }
} else {
    $processes = shell_exec('ps aux | grep python');
    echo "<strong>Python processes:</strong><br>";
    echo "<pre>" . htmlspecialchars($processes ?: "None found") . "</pre>";
    
    $watcher_check = shell_exec('ps aux | grep "[p]rediction_watcher.py"');
    if (!empty($watcher_check)) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 3px;'>";
        echo "✅ <strong>Prediction watcher IS running</strong>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 3px;'>";
        echo "⚠️ <strong>Prediction watcher is NOT running</strong>";
        echo "</div>";
    }
}

echo "</div>";

// ============================================================================
// 7. TRIGGER FILE CHECK
// ============================================================================
echo "<div class='box'>";
echo "<h2>7️⃣ Trigger File Check</h2>";

$trigger_file = $script_dir . DIRECTORY_SEPARATOR . "prediction_trigger.json";
echo "<strong>Trigger file path:</strong> <span class='code'>$trigger_file</span><br><br>";

if (file_exists($trigger_file)) {
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 3px;'>";
    echo "✅ <strong>Trigger file EXISTS</strong><br>";
    echo "Size: " . filesize($trigger_file) . " bytes<br>";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($trigger_file));
    echo "</div>";
    
    $content = file_get_contents($trigger_file);
    echo "<br><strong>Content:</strong>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
} else {
    echo "<div style='background: #fff3cd; padding: 10px; border-radius: 3px;'>";
    echo "⚠️ <strong>No trigger file found</strong> - This is normal if no upload is pending";
    echo "</div>";
}

echo "</div>";

// ============================================================================
// 8. PERMISSIONS CHECK
// ============================================================================
echo "<div class='box'>";
echo "<h2>8️⃣ Directory Permissions</h2>";

$is_writable = is_writable($script_dir);
$is_readable = is_readable($script_dir);

echo "<strong>Directory:</strong> <span class='code'>$script_dir</span><br><br>";
echo "<strong>Readable:</strong> " . ($is_readable ? '✅ Yes' : '❌ No') . "<br>";
echo "<strong>Writable:</strong> " . ($is_writable ? '✅ Yes' : '❌ No') . "<br>";

if (!$is_writable) {
    echo "<br><div style='background: #f8d7da; padding: 10px; border-radius: 3px; color: #721c24;'>";
    echo "❌ <strong>Directory is not writable!</strong> Cannot create trigger files. Fix permissions.";
    echo "</div>";
}

echo "</div>";

// ============================================================================
// 9. MANUAL TEST COMMANDS
// ============================================================================
echo "<div class='box'>";
echo "<h2>9️⃣ Manual Test Commands</h2>";

if ($python_found) {
    echo "<p>Try running these commands manually in your terminal/command prompt:</p>";
    
    if ($is_windows) {
        echo "<strong>Navigate to directory:</strong><br>";
        echo "<pre>cd /d \"$script_dir\"</pre>";
        echo "<strong>Test watcher:</strong><br>";
        echo "<pre>$python_found prediction_watcher.py</pre>";
        echo "<strong>Test prediction:</strong><br>";
        echo "<pre>$python_found predict.py</pre>";
    } else {
        echo "<strong>Navigate to directory:</strong><br>";
        echo "<pre>cd '$script_dir'</pre>";
        echo "<strong>Test watcher:</strong><br>";
        echo "<pre>$python_found prediction_watcher.py</pre>";
        echo "<strong>Test prediction:</strong><br>";
        echo "<pre>$python_found predict.py</pre>";
    }
}

echo "</div>";

// ============================================================================
// 10. SUMMARY & RECOMMENDATIONS
// ============================================================================
echo "<div class='box " . ($all_found && $python_found && $is_writable ? 'success' : 'error') . "'>";
echo "<h2>🎯 Summary & Recommendations</h2>";

$issues = [];

if (!$all_found) {
    $issues[] = "Missing Python files - upload all required files to: $script_dir";
}

if (!$python_found) {
    $issues[] = "Python not found - install Python 3.6+ and add to PATH";
}

if (!$is_writable) {
    $issues[] = "Directory not writable - fix permissions on: $script_dir";
}

if (empty($issues)) {
    echo "<p style='color: #28a745; font-size: 18px;'><strong>✅ All checks passed! System should work.</strong></p>";
    echo "<p>If predictions still don't trigger automatically, try:</p>";
    echo "<ol>";
    echo "<li>Start the watcher manually: <span class='code'>$python_found prediction_watcher.py</span></li>";
    echo "<li>Check PHP error logs for more details</li>";
    echo "<li>Verify web server has permission to execute Python</li>";
    echo "</ol>";
} else {
    echo "<p style='color: #dc3545; font-size: 18px;'><strong>❌ Issues found:</strong></p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}

echo "</div>";

echo "</body></html>";
?>