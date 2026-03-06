<?php
require 'config/db.php';
$stmt = $pdo->query('SHOW CREATE TABLE users');
$row = $stmt->fetch(PDO::FETCH_NUM);
echo "SQL: " . $row[1] . "\n";
