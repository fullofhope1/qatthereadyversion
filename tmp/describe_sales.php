<?php
$pdo = new PDO('mysql:host=localhost;dbname=qat_erp', 'root', '');
$stmt = $pdo->query('DESCRIBE sales');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
