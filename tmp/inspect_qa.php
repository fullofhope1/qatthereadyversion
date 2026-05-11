<?php
require 'config/db.php';
echo "--- QAT TYPES ---\n";
$stmt = $pdo->query("SELECT * FROM qat_types WHERE name LIKE 'QA_TEST%'");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) print_r($row);

echo "--- PURCHASES ---\n";
$stmt = $pdo->query("SELECT * FROM purchases WHERE qat_type_id IN (SELECT id FROM qat_types WHERE name LIKE 'QA_TEST%')");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) print_r($row);

echo "--- SALES ---\n";
$stmt = $pdo->query("SELECT * FROM sales WHERE qat_type_id IN (SELECT id FROM qat_types WHERE name LIKE 'QA_TEST%')");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) print_r($row);
