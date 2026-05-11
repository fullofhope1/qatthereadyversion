<?php
require 'config/db.php';
$today = date('Y-m-d');

$data = [];

// 1. All purchases from yesterday and today
$stmt = $pdo->prepare("SELECT * FROM purchases WHERE (purchase_date >= ? OR status != 'Closed')");
$stmt->execute([date('Y-m-d', strtotime('-1 day'))]);
$data['purchases'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. All leftovers from today
$stmt2 = $pdo->prepare("SELECT * FROM leftovers WHERE decision_date >= ?");
$stmt2->execute([date('Y-m-d', strtotime('-1 day'))]);
$data['leftovers'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// 3. Current Date
$data['server_date'] = $today;

echo json_encode($data, JSON_PRETTY_PRINT);
