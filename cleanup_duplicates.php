<?php
require 'config/db.php';

echo "--- STARTING DUPLICATE CLEANUP --- \n";

// Find duplicates: items in 'leftovers' table that have a corresponding 'Momsi' entry in 'purchases'
$stmt = $pdo->query("
    SELECT l.id as leftover_id, l.purchase_id
    FROM leftovers l
    JOIN purchases p ON l.purchase_id = p.id
    WHERE p.status = 'Momsi' AND l.status = 'Transferred_Next_Day'
");

$duplicates = $stmt->fetchAll();
echo "Found " . count($duplicates) . " duplicates.\n";

foreach ($duplicates as $dup) {
    echo "Deleting duplicate from leftovers table: ID {$dup['leftover_id']} (Ref PID: {$dup['purchase_id']})\n";
    $pdo->prepare("DELETE FROM leftovers WHERE id = ?")->execute([$dup['leftover_id']]);
}

echo "Cleanup completed.\n";
