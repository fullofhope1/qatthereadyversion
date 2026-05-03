<?php
require 'config/db.php';
$stmt = $pdo->query('SELECT username, password, role FROM users');
echo "Current Users in Database:\n";
echo "--------------------------\n";
while($row = $stmt->fetch()){
    echo "Username: " . $row['username'] . "\n";
    echo "Role: " . $row['role'] . "\n";
    echo "Password (Hashed): " . $row['password'] . "\n";
    echo "--------------------------\n";
}
