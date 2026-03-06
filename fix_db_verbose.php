<?php
// fix_db_verbose.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/db.php';

echo "Connected to DB\n";

try {
    $sql = "CREATE TABLE IF NOT EXISTS providers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";

    $pdo->exec($sql);
    echo "CMD: CREATE TABLE providers -> Executed\n";

    // Verify
    $stmt = $pdo->query("SHOW TABLES LIKE 'providers'");
    $tableExists = $stmt->fetchColumn();

    if ($tableExists) {
        echo "SUCCESS: Table 'providers' exists.\n";
    } else {
        echo "FAILURE: Table 'providers' was NOT created.\n";
    }

    // Check columns in purchases
    $cols = $pdo->query("DESCRIBE purchases")->fetchAll(PDO::FETCH_COLUMN);
    echo "Purchases Columns: " . implode(", ", $cols) . "\n";
} catch (PDOException $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
