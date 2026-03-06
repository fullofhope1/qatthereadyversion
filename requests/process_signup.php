<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $display_name = trim($_POST['display_name']);
    $phone = trim($_POST['phone']);

    if ($password !== $confirm_password) {
        header("Location: ../index.php?error=mismatch");
        exit;
    }

    // Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        header("Location: ../index.php?error=exists");
        exit;
    }

    // Create User
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, display_name, phone, role) VALUES (?, ?, ?, ?, 'user')");

    try {
        $stmt->execute([$username, $hashedPassword, $display_name, $phone]);

        // Success -> Redirect to Login with success msg
        header("Location: ../index.php?signup_success=1");
        exit;
    } catch (PDOException $e) {
        header("Location: ../index.php?error=generic");
        exit;
    }
}
