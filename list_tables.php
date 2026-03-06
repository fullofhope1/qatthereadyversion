<?php
// list_tables.php
require 'config/db.php';
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo implode(", ", $tables);
