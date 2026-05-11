<?php
$pdo = new PDO('mysql:host=localhost;dbname=qat_erp', 'root', '');
// Fetch existing enum values to avoid hardcoding Arabic if possible, or just add it.
$stmt = $pdo->query("SHOW COLUMNS FROM sales LIKE 'unit_type'");
$row = $stmt->fetch();
$type = $row['Type']; // enum('weight','...','...')
preg_match_all("/'([^']+)'/", $type, $matches);
$values = $matches[1];
if (!in_array('units', $values)) {
    $values[] = 'units';
}
$enumStr = "'" . implode("','", $values) . "'";
$sql = "ALTER TABLE sales MODIFY COLUMN unit_type ENUM($enumStr) NOT NULL DEFAULT 'weight'";
$pdo->exec($sql);
echo "Enum updated. New values: $enumStr\n";
