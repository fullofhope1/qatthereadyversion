<?php
require 'config/db.php';
$id = 38;
$p = $pdo->query("SELECT p.*, prov.name as prov_name FROM purchases p LEFT JOIN providers prov ON p.provider_id = prov.id WHERE p.id = $id")->fetch(PDO::FETCH_ASSOC);
echo "Purchase 38 Provider Name: " . ($p['prov_name'] ?? 'NONE') . "\n";
print_r($p);
