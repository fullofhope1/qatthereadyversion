<?php
require 'config/db.php';
$res = $pdo->query("SHOW DATABASES");
while ($row = $res->fetch(PDO::FETCH_NUM)) {
    echo $row[0] . "\n";
}
echo "Current Database: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";
