<?php
// create_refunds_table.php
require 'config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS refunds (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        refund_type ENUM('Cash', 'Debt') NOT NULL, -- Cash = Pay money back, Debt = Reduce debt balance
        reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (customer_id) REFERENCES customers(id)
    )";
    $pdo->exec($sql);
    echo "Table 'refunds' created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
