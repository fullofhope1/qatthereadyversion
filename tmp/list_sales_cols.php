<?php
require 'config/db.php';
$stmt = $pdo->query("DESCRIBE sales");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
