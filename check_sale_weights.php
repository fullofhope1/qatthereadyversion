<?php
require 'config/db.php';
$res = $pdo->query("SELECT id, weight_grams, weight_kg, leftover_id FROM sales WHERE id=76")->fetch(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT) . "\n";
