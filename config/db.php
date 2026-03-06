<?php
// config/db.php
$host = 'localhost';
$dbname = 'qat_erp';
$username = 'root';
$password = ''; // Default XAMPP password

// Set Timezone for PHP
date_default_timezone_set('Asia/Aden');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Set Timezone for MySQL Session
    $pdo->exec("SET time_zone = '+03:00'");
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
