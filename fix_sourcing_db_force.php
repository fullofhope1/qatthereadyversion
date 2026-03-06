<?php
// fix_sourcing_db_force.php
require 'config/db.php';

try {
    echo "Starting DB Fix...<br>";

    // 1. Force Create Providers Table
    $sql = "CREATE TABLE IF NOT EXISTS providers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    $pdo->exec($sql);
    echo "Table 'providers' check/create completed.<br>";

    // 2. Add Columns to Purchases if missing
    $columns = $pdo->query("DESCRIBE purchases")->fetchAll(PDO::FETCH_COLUMN);

    // provider_id
    if (!in_array('provider_id', $columns)) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN provider_id INT AFTER qat_type_id");
        echo "Added column 'provider_id'.<br>";
    } else {
        echo "Column 'provider_id' exists.<br>";
    }

    // price_per_kilo
    if (!in_array('price_per_kilo', $columns)) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN price_per_kilo DECIMAL(10, 2) DEFAULT 0.00 AFTER agreed_price");
        echo "Added column 'price_per_kilo'.<br>";
    }

    // source_weight_grams
    if (!in_array('source_weight_grams', $columns)) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN source_weight_grams DECIMAL(10, 2) DEFAULT 0.00 AFTER qat_type_id");
        echo "Added column 'source_weight_grams'.<br>";
    }

    // received_weight_grams
    if (!in_array('received_weight_grams', $columns)) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN received_weight_grams DECIMAL(10, 2) DEFAULT 0.00 AFTER source_weight_grams");
        echo "Added column 'received_weight_grams'.<br>";
    }

    // is_received
    if (!in_array('is_received', $columns)) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN is_received BOOLEAN DEFAULT 1 AFTER status");
        echo "Added column 'is_received'.<br>";
    }

    // received_at
    if (!in_array('received_at', $columns)) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN received_at DATETIME NULL AFTER is_received");
        echo "Added column 'received_at'.<br>";
    }

    // 3. Foreign Key Check (Try/Catch in case it exists)
    try {
        $pdo->exec("ALTER TABLE purchases ADD CONSTRAINT fk_purchase_provider FOREIGN KEY (provider_id) REFERENCES providers(id)");
        echo "Foreign Key added.<br>";
    } catch (Exception $e) {
        echo "Foreign Key might already exist or error: " . $e->getMessage() . "<br>";
    }

    echo "Fix Complete.";
} catch (PDOException $e) {
    echo "CRITICAL ERROR: " . $e->getMessage();
}
