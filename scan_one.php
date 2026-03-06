<?php
require 'config/db.php';

echo "--- SCANNING FOR 'one' DUPLICATES --- \n";

echo "PURCHASES (Momsi):\n";
$stmt = $pdo->query("SELECT id, purchase_date, quantity_kg, status FROM purchases WHERE status = 'Momsi'");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']} | Date: {$row['purchase_date']} | Qty: {$row['quantity_kg']} | Status: {$row['status']}\n";
}

echo "\nLEFTOVERS:\n";
$stmt = $pdo->query("SELECT id, purchase_id, source_date, sale_date, weight_kg, status FROM leftovers");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']} | PID: {$row['purchase_id']} | Src: {$row['source_date']} | Sale: {$row['sale_date']} | Qty: {$row['weight_kg']} | Status: {$row['status']}\n";
}
