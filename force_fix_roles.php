<?php
require 'config/db.php';

echo "--- BEFORE ---\n";
$stmt = $pdo->query('SELECT id, username, role, sub_role FROM users');
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']} | User: [{$row['username']}] | Role: [{$row['role']}] | SubRole: [{$row['sub_role']}]\n";
}

echo "\n--- APPLYING FIX ---\n";
$pdo->exec("UPDATE users SET role = 'super_admin' WHERE id = 1");
$pdo->exec("UPDATE users SET role = 'super_admin' WHERE username = 'super admin'");
$pdo->exec("UPDATE users SET role = 'super_admin' WHERE username = 'admin'");
// Also set sub_role to full for these to be safe
$pdo->exec("UPDATE users SET sub_role = 'full' WHERE role = 'super_admin' AND (sub_role IS NULL OR sub_role = '')");

echo "\n--- AFTER ---\n";
$stmt = $pdo->query('SELECT id, username, role, sub_role FROM users');
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']} | User: [{$row['username']}] | Role: [{$row['role']}] | SubRole: [{$row['sub_role']}]\n";
}
