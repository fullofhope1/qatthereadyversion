<?php
require 'config/db.php';
$stmt = $pdo->query("DESCRIBE sales");
while ($row = $stmt->fetch()) {
    if ($row['Field'] == 'weight_kg') {
        echo "Found: " . json_encode($row) . "\n";
    }
}
