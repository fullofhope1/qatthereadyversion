<?php
require 'config/db.php';
function showTable($pdo, $name) {
    echo "--- $name ---\n";
    $stmt = $pdo->query("DESCRIBE $name");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
}
showTable($pdo, 'purchases');
showTable($pdo, 'sales');
