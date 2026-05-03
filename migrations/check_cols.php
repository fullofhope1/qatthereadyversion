<?php
require 'config/db.php';
$cols = $pdo->query("DESCRIBE leftovers")->fetchAll(PDO::FETCH_COLUMN);
echo "Columns in leftovers: " . implode(', ', $cols);
?>
