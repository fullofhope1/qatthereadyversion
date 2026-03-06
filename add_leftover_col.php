<?php
require 'config/db.php';

try {
    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM sales LIKE 'leftover_id'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN leftover_id INT AFTER purchase_id");
        $pdo->exec("ALTER TABLE sales ADD CONSTRAINT fk_sales_leftover FOREIGN KEY (leftover_id) REFERENCES leftovers(id)");
        echo "Column 'leftover_id' added to sales table.<br>";
    } else {
        echo "Column 'leftover_id' already exists.<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
