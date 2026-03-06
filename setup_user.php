<?php
require 'config/db.php';

$username = 'alqadri';
$password = password_hash('admin', PASSWORD_DEFAULT);
$role = 'super admin';

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    $pdo->prepare("UPDATE users SET password = ?, role = ? WHERE username = ?")->execute([$password, $role, $username]);
    echo "Updated existing user '$username'.\n";
} else {
    $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)")->execute([$username, $password, $role]);
    echo "Created new user '$username'.\n";
}
