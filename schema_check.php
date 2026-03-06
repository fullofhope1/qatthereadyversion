<?php
require 'config/db.php';
$stmt = $pdo->query('SHOW COLUMNS FROM users');
foreach ($stmt->fetchAll() as $row) {
    echo $row['Field'] . "\n";
}
