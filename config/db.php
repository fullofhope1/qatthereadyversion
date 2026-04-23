<?php
// config/db.php
// Detect Environment
$is_localhost = (php_sapi_name() === 'cli') || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

if ($is_localhost) {
    // Local XAMPP Credentials
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "qat_erp";
} else {
    // Live Server Credentials
    $servername = "sql100.hstn.me";
    $username = "mseet_41427862";
    $password = "zt92DPSWefgb";
    $dbname = "mseet_41427862_qat_erp";
}

date_default_timezone_set('Asia/Aden');

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec("SET time_zone = '+03:00'");
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
