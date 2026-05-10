<?php
require 'config/db.php';
echo "Purchases:\n";
$stmt = $pdo->query("SELECT id, created_by FROM purchases");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "Expenses:\n";
$stmt = $pdo->query("SELECT id, created_by FROM expenses");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
