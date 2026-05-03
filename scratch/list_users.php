<?php
require 'config/db.php';
$stmt = $pdo->query("SELECT id, username, role, sub_role FROM users");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | User: " . $row['username'] . " | Role: " . $row['role'] . " | Sub: " . $row['sub_role'] . "\n";
}
