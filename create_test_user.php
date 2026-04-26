<?php
require 'config/db.php';
$username = 'test_ai';
$password = 'password123';
$role = 'super_admin';

// Check if exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo "User already exists\n";
} else {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hash, $role]);
    echo "User created successfully\n";
}
