<?php
require 'config/db.php';
header('Content-Type: text/plain; charset=utf-8');

function dumpTable($pdo, $table)
{
    echo "--- TABLE: $table ---\n";
    $stmt = $pdo->query("SHOW CREATE TABLE $table");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $row['Create Table'] . "\n\n";
}

dumpTable($pdo, 'leftovers');
dumpTable($pdo, 'sales');
dumpTable($pdo, 'purchases');
