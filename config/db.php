<?php
// config/db.php
$servername = "sql111.infinityfree.com";
$username = "if0_41103629";
$password = "HpnYShAsAaK";
$dbname = "if0_41103629_qat";

// Set Timezone for PHP
date_default_timezone_set('Asia/Aden');

try {
    // Fixed: Changed $host to $servername to match your variable above
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Set Timezone for MySQL Session
    $pdo->exec("SET time_zone = '+03:00'");
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
