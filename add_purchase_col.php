<?php
require 'config/db.php';

try {
    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM sales LIKE 'purchase_id'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN purchase_id INT AFTER qat_type_id");
        $pdo->exec("ALTER TABLE sales ADD CONSTRAINT fk_sales_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id)");
        echo "Column 'purchase_id' added to sales table.<br>";
    } else {
        echo "Column 'purchase_id' already exists.<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
