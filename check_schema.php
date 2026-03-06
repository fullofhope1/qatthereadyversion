<?php
require 'config/db.php';

echo "Purchases Schema:\n";
$stmt = $pdo->query("SHOW CREATE TABLE purchases");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\nLeftovers Schema:\n";
$stmt = $pdo->query("SHOW CREATE TABLE leftovers");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
