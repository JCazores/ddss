<?php
// Create a simple test file to check if flag creation works
// Save this as test_flag.php and run it in your browser

echo "<h2>Flag File Creation Test</h2>";

// Test different possible paths
$test_paths = [
    "C:\\Users\\Christian Azores\\retrain_flag.txt",
    "retrain_flag.txt",
    "./retrain_flag.txt",
    $_SERVER['DOCUMENT_ROOT'] . "/retrain_flag.txt",
    __DIR__ . "/retrain_flag.txt"
];

$test_content = json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'test' => true,
    'message' => 'This is a test flag file'
], JSON_PRETTY_PRINT);

echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
echo "<tr><th>Path</th><th>Directory Exists</th><th>Directory Writable</th><th>File Creation</th><th>File Exists After</th><th>File Readable</th></tr>";

foreach ($test_paths as $path) {
    $dir = dirname($path);
    $dir_exists = is_dir($dir);
    $dir_writable = $dir_exists ? is_writable($dir) : false;
    
    // Try to create directory if it doesn't exist
    if (!$dir_exists && $dir !== '.') {
        @mkdir($dir, 0755, true);
        $dir_exists = is_dir($dir);
        $dir_writable = $dir_exists ? is_writable($dir) : false;
    }
    
    $file_written = file_put_contents($path, $test_content);
    $file_exists = file_exists($path);
    $file_readable = $file_exists ? (file_get_contents($path) !== false) : false;
    
    echo "<tr>";
    echo "<td><code>" . htmlspecialchars($path) . "</code></td>";
    echo "<td>" . ($dir_exists ? "✅" : "❌") . "</td>";
    echo "<td>" . ($dir_writable ? "✅" : "❌") . "</td>";
    echo "<td>" . ($file_written !== false ? "✅ ($file_written bytes)" : "❌") . "</td>";
    echo "<td>" . ($file_exists ? "✅" : "❌") . "</td>";
    echo "<td>" . ($file_readable ? "✅" : "❌") . "</td>";
    echo "</tr>";
    
    // Clean up test files
    if ($file_exists) {
        @unlink($path);
    }
}

echo "</table>";

echo "<h3>System Information</h3>";
echo "<p><strong>Current working directory:</strong> <code>" . getcwd() . "</code></p>";
echo "<p><strong>Script directory:</strong> <code>" . __DIR__ . "</code></p>";
echo "<p><strong>Document root:</strong> <code>" . $_SERVER['DOCUMENT_ROOT'] . "</code></p>";
echo "<p><strong>Server software:</strong> <code>" . $_SERVER['SERVER_SOFTWARE'] . "</code></p>";

// Check if running on Windows
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    echo "<p><strong>OS:</strong> Windows detected</p>";
    
    // Try to get current user
    $whoami = shell_exec('whoami');
    if ($whoami) {
        echo "<p><strong>Current user:</strong> <code>" . trim($whoami) . "</code></p>";
    }
} else {
    echo "<p><strong>OS:</strong> " . PHP_OS . "</p>";
}

echo "<h3>Recommended Solution</h3>";
echo "<div style='background:#f0f8ff; padding:15px; border-left:4px solid #007cba;'>";
echo "<p>Based on the test results above:</p>";
echo "<ol>";
echo "<li>Use the path that shows ✅ for all columns</li>";
echo "<li>If C:\\Users\\Christian Azores\\ doesn't work, use a relative path like <code>./retrain_flag.txt</code></li>";
echo "<li>Update both your PHP script and Python watcher to use the same working path</li>";
echo "</ol>";
echo "</div>";
?>