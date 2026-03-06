<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Success
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['sub_role'] = $user['sub_role'] ?? 'full';

        if ($user['role'] === 'user') {
            header("Location: ../index.php");
        } else {
            // Both admin and super_admin go to internal dashboard
            header("Location: ../dashboard.php");
        }
        exit;
    } else {
        // Fail
        header("Location: ../index.php?error=1");
        exit;
    }
}
