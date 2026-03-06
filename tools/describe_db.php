<?php
require '../config/db.php';
$tables = ['purchases', 'providers', 'customers', 'expenses'];
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $stmt = $pdo->query("DESCRIBE $t");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo " - " . $c['Field'] . " (" . $c['Type'] . ")\n";
    }
    echo "\n";
}
