<?php
require 'config/db.php';
try {
    // Add is_returned column
    $pdo->exec("ALTER TABLE sales ADD COLUMN is_returned TINYINT(1) DEFAULT 0 AFTER is_paid");
    echo "Successfully added is_returned column.\n";
    
    // Also add refund_amount for tracking partial/full refunds in the sales record directly if needed
    $pdo->exec("ALTER TABLE sales ADD COLUMN refund_amount DECIMAL(15,2) DEFAULT 0.00 AFTER is_returned");
    echo "Successfully added refund_amount column.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
