<?php
require 'config/db.php';

function checkUser($pdo, $id)
{
    $stmt = $pdo->prepare("SELECT id, username, role, sub_role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "ID: {$user['id']} | User: [{$user['username']}] | Role: [{$user['role']}] | SubRole: [{$user['sub_role']}]\n";
}

echo "--- BEFORE ---\n";
checkUser($pdo, 1);

echo "\n--- ATTEMPTING UPDATE ---\n";
$stmt = $pdo->prepare("UPDATE users SET role = 'super_admin' WHERE id = 1");
$stmt->execute();
echo "Affected Rows: " . $stmt->rowCount() . "\n";
if ($stmt->errorCode() !== '00000') {
    print_r($stmt->errorInfo());
}

echo "\n--- AFTER UPDATE ---\n";
checkUser($pdo, 1);

echo "\n--- ATTEMPTING UPDATE VIA USERNAME ---\n";
$stmt = $pdo->prepare("UPDATE users SET role = 'super_admin' WHERE username = 'super admin'");
$stmt->execute();
echo "Affected Rows: " . $stmt->rowCount() . "\n";

echo "\n--- FINAL CHECK ---\n";
checkUser($pdo, 1);
