<?php
ob_start();
session_start();
$siteName = "Drop-Out Decision Support System";

DEFINE("BASE_URL", "https://ddss-production.up.railway.app/");

DEFINE('DB_USER', 'root');
DEFINE('DB_PSWD', 'oCZnrPaBlUHwYSSosvPTWAFRnKiSwQJI');
DEFINE('DB_HOST', 'acela.proxy.rlwy.net');
DEFINE('DB_PORT', '58509');
DEFINE('DB_NAME', 'railway');

date_default_timezone_set('Asia/Manila');
$conn = new mysqli(DB_HOST, DB_USER, DB_PSWD, DB_NAME, DB_PORT);
if($conn->connect_error)
    die("Failed to connect database " . $conn->connect_error);
?>