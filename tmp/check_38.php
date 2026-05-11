<?php
require 'config/db.php';
$id = 38;
$count = $pdo->query("SELECT COUNT(*) FROM leftovers WHERE purchase_id = $id")->fetchColumn();
echo "Total leftovers for purchase $id: $count\n\n";

if ($count > 0) {
    $rows = $pdo->query("SELECT * FROM leftovers WHERE purchase_id = $id")->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
}
