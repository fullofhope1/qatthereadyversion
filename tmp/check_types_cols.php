<?php
require 'config/db.php';
$stmt = $pdo->query('DESCRIBE qat_types');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " ";
}
echo "\n";
