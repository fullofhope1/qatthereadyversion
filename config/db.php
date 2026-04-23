<?php
// config/db.php
// Detect Environment
$is_localhost = (php_sapi_name() === 'cli') || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

if ($is_localhost) {
    // Local XAMPP Credentials
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "qat_erp"; // تأكد أن هذا هو اسم قاعدة البيانات في XAMPP لديك
} else {
    // Live Server Credentials (بيانات InfinityFree الجديدة)
    $servername = "sql308.infinityfree.com";
    $username = "if0_41735561";
    $password = "DVf4IqRZf9Zh6";
    $dbname = "if0_41735561_qat_db";
}

date_default_timezone_set('Asia/Aden');

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec("SET time_zone = '+03:00'");
} catch (PDOException $e) {
    // نصيحة: في السيرفر الحي، يفضل عدم إظهار الخطأ بالتفصيل للمستخدم، لكن حالياً سنبقيها للتأكد من نجاح الاتصال
    die("Database Connection Failed: " . $e->getMessage());
}
