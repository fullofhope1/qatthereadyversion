<?php
require 'config/db.php';
$l = $pdo->query("SELECT * FROM leftovers WHERE id=31")->fetch(PDO::FETCH_ASSOC);
echo "Leftover 31: " . json_encode($l, JSON_PRETTY_PRINT) . "\n";
$sold = $pdo->query("SELECT SUM(weight_kg) FROM sales WHERE leftover_id=31")->fetchColumn();
echo "Total sold for 31: " . ($sold ?: 0) . " kg\n";
