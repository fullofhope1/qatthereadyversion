<?php
require 'config/db.php';
$stmt = $pdo->query('SELECT id, username, role, sub_role FROM users');
foreach ($stmt->fetchAll() as $row) {
    echo "ID: {$row['id']} | User: {$row['username']} | Role: '{$row['role']}' | SubRole: '{$row['sub_role']}'\n";
}
