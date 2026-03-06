<?php
require 'config/db.php';
try {
    $pdo->exec("ALTER TABLE leftovers ADD COLUMN IF NOT EXISTS sale_date DATE AFTER decision_date");
    echo "Migration Successful: sale_date column added to leftovers table.";
} catch (Exception $e) {
    echo "Migration Error: " . $e->getMessage();
}
