<?php
require 'config/db.php';
$stmt = $pdo->query("SELECT id, username, password, role FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
