<?php
require 'config/db.php';
$pdo->exec("UPDATE expenses SET category = 'أخرى' WHERE category = '' OR category IS NULL");
echo "Done. Rows affected: " . $pdo->query("SELECT ROW_COUNT()")->fetchColumn();
