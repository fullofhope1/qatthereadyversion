<?php
require 'config/db.php';
$stmt = $pdo->query('SELECT id, username, role, sub_role FROM users');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
