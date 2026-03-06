<?php
require 'config/db.php';
try {
    $stmt = $pdo->prepare("UPDATE qat_types SET is_deleted = 0");
    $stmt->execute();
    echo "Successfully restored " . $stmt->rowCount() . " qat types.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
