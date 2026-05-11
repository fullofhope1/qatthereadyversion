<?php
require 'config/db.php';

try {
    // Disable foreign key checks to allow truncation
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tablesToClear = [
        'sales',
        'purchases',
        'leftovers',
        'expenses',
        'payments',
        'refunds',
        'qat_deposits',
        'unknown_transfers',
        'closed_shifts',
        'attendance',
        'staff_attendance',
        'advertisements'
    ];

    foreach ($tablesToClear as $table) {
        $pdo->exec("TRUNCATE TABLE $table");
        echo "Table '$table' cleared successfully.<br>";
    }

    // Reset customer debts to 0 (since all transactions are gone)
    $pdo->exec("UPDATE customers SET total_debt = 0");
    echo "Customer debts reset to 0.<br>";

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<h2>System Reset (Formatting) Completed Successfully!</h2>";
    echo "The system is now clean and ready for real production data.";

} catch (Exception $e) {
    echo "Error during reset: " . $e->getMessage();
}
?>
