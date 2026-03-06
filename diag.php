<?php
require 'config/db.php';
$stmt = $pdo->query('SELECT id, username, role, sub_role FROM users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
