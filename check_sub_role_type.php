<?php
require 'config/db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'sub_role'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Type: " . $row['Type'] . "\n";
