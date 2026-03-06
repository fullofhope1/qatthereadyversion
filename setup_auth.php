<?php
require 'config/db.php';

try {
    // 1. Create Users Table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('super_admin', 'admin') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Users table checked/created.<br>";

    // 2. Seed Users
    // Super Admin
    $passSuper = password_hash('super admin', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, role) VALUES ('super admin', ?, 'super_admin')");
    $stmt->execute([$passSuper]);
    if ($stmt->rowCount()) echo "Super Admin created.<br>";

    // Admin
    $passAdmin = password_hash('admin', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
    $stmt->execute([$passAdmin]);
    if ($stmt->rowCount()) echo "Admin created.<br>";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
