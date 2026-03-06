<?php
// config/db.php
$host = 'sql111.infinityfree.com'; // Your MySQL Host Name
$dbname = 'if0_41103629_qat';     // Your MySQL DB Name
$username = 'if0_41103629';       // Your MySQL User Name
$password = 'HpnYShAsAaK';         // Your vPanel Password

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
