<?php
require 'config/db.php';
try {
    $sql = file_get_contents('migrations/add_unit_type_columns.sql');
    // Split by semicolon to run multiple statements if needed, though exec can handle multiple if driver allows
    $pdo->exec($sql);
    echo "Success\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
