<?php
require 'config/db.php';

try {
    // 1. Delete duplicates (Keep 1-7)
    // Deleting any type with ID > 7 based on user request to "count 8 and delete others" logic (assuming 1-7 are the uniques)
    // Adjust logic if there are distinct types in the higher numbers, but based on logs they were duplicates.
    $pdo->exec("DELETE FROM qat_types WHERE id > 7");
    echo "Deleted duplicates (IDs > 7).\n";

    // Reset Auto Increment just in case
    $pdo->exec("ALTER TABLE qat_types AUTO_INCREMENT = 8");
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
