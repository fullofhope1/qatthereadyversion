<?php
$pdo = new PDO('mysql:host=localhost;dbname=qat_erp', 'root', '');
$stmt = $pdo->query('SELECT id, payment_method FROM sales WHERE id = 80');
var_dump($stmt->fetch(PDO::FETCH_ASSOC));
$stmt = $pdo->query('SELECT COUNT(*) FROM sales');
echo "Total sales: " . $stmt->fetchColumn() . "\n";
