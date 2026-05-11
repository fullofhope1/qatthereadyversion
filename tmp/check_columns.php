<?php
require 'config/db.php';
$tables = ['purchases', 'sales', 'leftovers', 'refunds', 'qat_types'];
foreach ($tables as $table) {
    echo "\nTable: $table\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    } catch (Exception $e) {
        echo "Error describing $table: " . $e->getMessage() . "\n";
    }
}
