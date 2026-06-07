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

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME, DB_USER, DB_PSWD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create mysqli-compatible wrapper
    $conn = new class($pdo) {
        public $pdo;
        public $connect_error = null;
        public function __construct($pdo) { $this->pdo = $pdo; }
        public function query($sql) { return $this->pdo->query($sql); }
        public function prepare($sql) { return $this->pdo->prepare($sql); }
        public function real_escape_string($s) { return addslashes($s); }
        public function close() {}
    };
} catch(Exception $e) {
    die("Failed to connect database: " . $e->getMessage());
}
?>