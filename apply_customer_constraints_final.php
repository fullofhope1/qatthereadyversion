<?php
require 'config/db.php';

try {
    $pdo->beginTransaction();

    echo "<h3>Cleaning up duplicates and applying constraints...</h3>";

    // 1. Delete redundant duplicates (they have 0 sales)
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id IN (23, 24)");
    $stmt->execute();
    echo "<p style='color:green'>Deleted redundant duplicate IDs (23, 24).</p>";

    // 2. Apply Unique Indexes
    try {
        $pdo->exec("ALTER TABLE customers ADD UNIQUE INDEX UNIQUE_NAME (name)");
        echo "<p style='color:green'>Unique index added for 'name'.</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p style='color:orange'>Unique index for 'name' already exists.</p>";
        } else throw $e;
    }

    try {
        $pdo->exec("ALTER TABLE customers ADD UNIQUE INDEX UNIQUE_PHONE (phone)");
        echo "<p style='color:green'>Unique index added for 'phone'.</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p style='color:orange'>Unique index for 'phone' already exists.</p>";
        } else throw $e;
    }

    $pdo->commit();
    echo "<br><p><strong>Migration Successful!</strong> <a href='index.php'>Go to Homepage</a></p>";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "<p style='color:red'>Critical Error: " . $e->getMessage() . "</p>";
}
