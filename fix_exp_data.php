<?php
require 'config/db.php';
$stmt = $pdo->prepare("UPDATE expenses SET category = 'أخرى' WHERE category = '' OR category IS NULL OR TRIM(category) = ''");
$stmt->execute();
echo "Updated " . $stmt->rowCount() . " rows.";
