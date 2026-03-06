<?php
// update_sourcing_db.php
require 'config/db.php';

try {
    // 1. Create Providers Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS providers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Table 'providers' created/checked.<br>";

    // 2. Modify Purchases Table
    // Check if columns exist before adding
    $columns = $pdo->query("DESCRIBE purchases")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('provider_id', $columns)) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN provider_id INT AFTER qat_type_id");
        $pdo->exec("ALTER TABLE purchases ADD CONSTRAINT fk_purchase_provider FOREIGN KEY (provider_id) REFERENCES providers(id)");
        echo "Column 'provider_id' added.<br>";
    }

    if (!in_array('price_per_kilo', $columns)) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN price_per_kilo DECIMAL(10, 2) DEFAULT 0.00 AFTER agreed_price");
        echo "Column 'price_per_kilo' added.<br>";
    }

    if (!in_array('source_weight_grams', $columns)) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN source_weight_grams DECIMAL(10, 2) DEFAULT 0.00 AFTER qat_type_id");
        echo "Column 'source_weight_grams' added.<br>";
    }

    if (!in_array('received_weight_grams', $columns)) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN received_weight_grams DECIMAL(10, 2) DEFAULT 0.00 AFTER source_weight_grams");
        echo "Column 'received_weight_grams' added.<br>";
    }

    if (!in_array('is_received', $columns)) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN is_received BOOLEAN DEFAULT 1 AFTER status");
        // Default 1 for existing records so they don't get stuck in pending
        echo "Column 'is_received' added.<br>";
    }

    if (!in_array('received_at', $columns)) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN received_at DATETIME NULL AFTER is_received");
        echo "Column 'received_at' added.<br>";
    }

    echo "Database updated successfully for Sourcing/Receiving workflow.";
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
