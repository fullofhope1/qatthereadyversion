<?php
require 'config/db.php';
$stmt = $pdo->query('SELECT id, username, role, sub_role FROM users');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | User: " . $row['username'] . " | Role: " . var_export($row['role'], true) . " | SubRole: " . var_export($row['sub_role'], true) . "\n";
}
