<?php
$pdo = new PDO("mysql:host=localhost;dbname=qat_erp;charset=utf8", "root", "");
echo "<pre>";
echo "=== COLUMNS in leftovers ===\n";
$r = $pdo->query("DESCRIBE leftovers");
foreach($r->fetchAll(PDO::FETCH_ASSOC) as $row)
    echo $row['Field']." | ".$row['Type']." | ".$row['Null']." | Default: ".$row['Default']."\n";

echo "\n=== FULL CREATE TABLE for leftovers ===\n";
$r = $pdo->query("SHOW CREATE TABLE leftovers");
$row = $r->fetch(PDO::FETCH_ASSOC);
echo $row['Create Table'] ?? ($row[1] ?? 'N/A');
echo "</pre>";
