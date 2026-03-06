<?php
require 'config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS advertisements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(255) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_url TEXT,
        link_url TEXT,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Table 'advertisements' created successfully!\n";

    // Add a sample ad if empty
    $count = $pdo->query("SELECT COUNT(*) FROM advertisements")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO advertisements (client_name, title, description, status) 
                   VALUES ('Premium Qat Sourcing', 'The Best Qat in Town', 'We provide fresh, high-quality qat daily. Contact us for bulk orders.', 'Active')");
        echo "Sample advertisement added.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
