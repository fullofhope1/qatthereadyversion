<?php
require 'config/db.php';
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE purchase_date > ?");
$stmt->execute([$today]);
echo 'Future Purchases: ' . $stmt->fetchColumn() . "\n";

$stmt = $pdo->prepare("SELECT DISTINCT pt.name FROM purchases p JOIN qat_types pt ON p.qat_type_id = pt.id WHERE p.purchase_date > ?");
$stmt->execute([$today]);
$types = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo 'Upcoming Types: ' . implode(', ', $types) . "\n";
