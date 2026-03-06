<?php
require 'config/db.php';

try {
    // 1. Unknown Transfers Table
    $sql1 = "CREATE TABLE IF NOT EXISTS unknown_transfers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transfer_date DATE NOT NULL,
        receipt_number VARCHAR(100),
        sender_name VARCHAR(100) NOT NULL,
        amount DECIMAL(10, 2) DEFAULT 0.00,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql1);
    echo "Table 'unknown_transfers' checked/created.<br>";

    // 2. Leftovers Table
    // Tracking leftovers from a specific day
    $sql2 = "CREATE TABLE IF NOT EXISTS leftovers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        source_date DATE NOT NULL, -- The day the goods were purchased
        qat_type_id INT,
        weight_kg DECIMAL(10, 2) NOT NULL, -- Quantity left
        status ENUM('Pending', 'Dropped', 'Transferred_Next_Day', 'Sold') DEFAULT 'Pending',
        decision_date DATE, -- When the decision (drop/sell) was made
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (qat_type_id) REFERENCES qat_types(id)
    )";
    $pdo->exec($sql2);
    echo "Table 'leftovers' checked/created.<br>";

    // 3. Add 'Leftover' as a generic provider (Ra'wi) name? 
    // Actually, for selling next day, we might need to link it back to sales.
    // For now, let's keep it simple.

    echo "Database updates completed successfully.";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
