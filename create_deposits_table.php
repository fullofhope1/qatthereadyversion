<?php
require 'config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS qat_deposits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        deposit_date DATE NOT NULL,
        currency ENUM('YER', 'SAR', 'USD') NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        recipient VARCHAR(100) NOT NULL, -- e.g. الصراف, المندوب
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'qat_deposits' created successfully!\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
