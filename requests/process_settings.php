<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?auth=1");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? 'password';

    if ($action === 'username') {
        $new_username = trim($_POST['new_username']);
        $confirm_pass = $_POST['confirm_password_username'];

        if (strlen($new_username) < 3) {
            header("Location: ../settings.php?error=" . urlencode("اسم المستخدم قصير جداً (3 أحرف على الأقل)"));
            exit;
        }

        // Verify password first
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($confirm_pass, $user['password'])) {
            // Check name not taken
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check->execute([$new_username, $user_id]);
            if ($check->fetch()) {
                header("Location: ../settings.php?error=" . urlencode("اسم المستخدم مستخدم بالفعل"));
                exit;
            }

            $pdo->prepare("UPDATE users SET username = ? WHERE id = ?")->execute([$new_username, $user_id]);
            $_SESSION['username'] = $new_username;
            header("Location: ../settings.php?success=username");
        } else {
            header("Location: ../settings.php?error=" . urlencode("كلمة المرور غير صحيحة"));
        }
        exit;
    }

    // Default: password change
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        header("Location: ../settings.php?error=" . urlencode("كلمتا المرور غير متطابقتين"));
        exit;
    }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($current_pass, $user['password'])) {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_hash, $user_id]);
        header("Location: ../settings.php?success=password");
    } else {
        header("Location: ../settings.php?error=" . urlencode("كلمة المرور الحالية غير صحيحة"));
    }
} else {
    header("Location: ../settings.php");
}
