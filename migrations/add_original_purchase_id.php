<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("ALTER TABLE purchases ADD COLUMN original_purchase_id INT DEFAULT NULL");
    echo "Added original_purchase_id column.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Column original_purchase_id already exists.\n";
    } else {
        echo "Error adding column: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("ALTER TABLE purchases ADD CONSTRAINT fk_original_purchase FOREIGN KEY (original_purchase_id) REFERENCES purchases(id) ON DELETE SET NULL");
    echo "Added foreign key constraint.\n";
} catch (PDOException $e) {
    // 1061 is Duplicate key name
    echo "Key might already exist or error: " . $e->getMessage() . "\n";
}

echo "Done.\n";
