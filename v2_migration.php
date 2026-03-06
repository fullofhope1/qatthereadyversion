<?php
require 'config/db.php';

try {
    // 1. Update Purchases: Add expected_quantity_kg
    // We keep 'quantity_kg' as the ACTUAL measured weight.
    $check = $pdo->query("SHOW COLUMNS FROM purchases LIKE 'expected_quantity_kg'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN expected_quantity_kg DECIMAL(10, 2) DEFAULT 0.00 AFTER qat_type_id");
        echo "Added 'expected_quantity_kg' to purchases.<br>";
    }

    // 2. Update Sales: Add debt_type
    $check = $pdo->query("SHOW COLUMNS FROM sales LIKE 'debt_type'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN debt_type ENUM('Daily', 'Monthly', 'Yearly') DEFAULT NULL AFTER is_paid");
        echo "Added 'debt_type' to sales.<br>";
    }

    // 3. Update Sales: Add purchase_id (to link to specific Provider/Purchase)
    // We call it 'vendor_name' in the requirement, but linking to the purchase ID is better for strict relationships.
    // However, for simplicity and speed, let's store the vendor name directly if the user just wants to know "Which Ra'wi".
    // But better: Link to the Purchase record ID (Stock ID).
    $check = $pdo->query("SHOW COLUMNS FROM sales LIKE 'purchase_id'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN purchase_id INT DEFAULT NULL AFTER qat_type_id");
        echo "Added 'purchase_id' to sales.<br>";
    }

    echo "V2 Database Update Completed Successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
