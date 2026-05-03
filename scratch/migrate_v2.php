<?php
require_once 'config/db.php';
try {
    $pdo->exec("ALTER TABLE expenses ADD COLUMN payment_method ENUM('Cash', 'Transfer') DEFAULT 'Cash' AFTER amount");
    echo "Column 'payment_method' added to 'expenses' table successfully.\n";
} catch (Exception $e) {
    echo "Error or already exists: " . $e->getMessage() . "\n";
}

try {
    // Also add is_active to staff if missing (good practice for balance tracking)
    $pdo->exec("ALTER TABLE staff ADD COLUMN join_date DATE DEFAULT NULL");
    echo "Column 'join_date' added to 'staff' table.\n";
} catch (Exception $e) {}
?>
