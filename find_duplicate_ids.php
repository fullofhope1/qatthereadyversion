<?php
require 'config/db.php';

echo "<h3>Duplicate ID Identification</h3>";

echo "<h4>Duplicates for name 'عرفج':</h4>";
$stmt = $pdo->prepare("SELECT id, name, phone, (SELECT COUNT(*) FROM sales WHERE customer_id = customers.id) as sales_count FROM customers WHERE name = 'عرفج' AND is_deleted = 0");
$stmt->execute();
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";

echo "<h4>Duplicates for phone '772672573':</h4>";
$stmt = $pdo->prepare("SELECT id, name, phone, (SELECT COUNT(*) FROM sales WHERE customer_id = customers.id) as sales_count FROM customers WHERE phone = '772672573' AND is_deleted = 0");
$stmt->execute();
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
