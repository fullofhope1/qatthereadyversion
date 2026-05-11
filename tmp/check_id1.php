<?php
require 'config/db.php';
$stmt = $pdo->query('SELECT * FROM purchases WHERE id = 1');
print_r($stmt->fetch(PDO::FETCH_ASSOC));
