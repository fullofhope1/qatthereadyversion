<?php
require 'config/db.php';
header('Content-Type: text/plain; charset=utf-8');

$tables = ['leftovers', 'sales', 'purchases'];
foreach ($tables as $t) {
    echo "--- Foreign Keys for $t ---\n";
    $stmt = $pdo->prepare("SELECT COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL");
    $stmt->execute([$t]);
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fks as $fk) {
        echo "{$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}({$fk['REFERENCED_COLUMN_NAME']})\n";
    }
    echo "\n";
}
