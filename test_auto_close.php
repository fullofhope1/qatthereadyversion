<?php
// test_auto_close.php
require_once 'config/db.php';
require_once 'includes/auto_close.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Auto-Close Verification Test (Final) ===\n";

try {
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $today = date('Y-m-d');

    echo "Testing for date: $yesterday\n";

    // 1. Setup Data
    $type_id = $pdo->query("SELECT id FROM qat_types LIMIT 1")->fetchColumn();
    $cust_id = $pdo->query("SELECT id FROM customers LIMIT 1")->fetchColumn();

    // Purchase for Yesterday (10kg)
    $pdo->prepare("INSERT INTO purchases (purchase_date, qat_type_id, vendor_name, agreed_price, quantity_kg, status) 
                  VALUES (?, ?, 'Test Vendor', 1000, 10.0, 'Fresh')")
        ->execute([$yesterday, $type_id]);
    $purchase_id = $pdo->lastInsertId();
    echo "Created Yesterday Purchase ID: $purchase_id (10kg)\n";

    // Fresh Sale for Yesterday (4kg)
    $pdo->prepare("INSERT INTO sales (sale_date, due_date, customer_id, qat_type_id, purchase_id, weight_grams, price, payment_method, is_paid) 
                  VALUES (?, ?, ?, ?, ?, 4000, 500, 'Debt', 0)")
        ->execute([$yesterday, $yesterday, $cust_id, $type_id, $purchase_id]);
    echo "Created Yesterday Fresh Sale (4kg)\n";

    // Create a real leftover to link a sale to
    $pdo->prepare("INSERT INTO leftovers (source_date, purchase_id, qat_type_id, weight_kg, status, decision_date, sale_date)
                  VALUES (?, ?, ?, 5.0, 'Transferred_Next_Day', ?, ?)")
        ->execute(['2020-01-01', $purchase_id, $type_id, '2020-01-01', '2020-01-01']);
    $dummy_lid = $pdo->lastInsertId();

    // Leftover Sale for Yesterday (1kg) - Linked to a different 'leftover_id'
    $pdo->prepare("INSERT INTO sales (sale_date, due_date, customer_id, qat_type_id, leftover_id, weight_grams, price, payment_method, is_paid) 
                  VALUES (?, ?, ?, ?, ?, 1000, 100, 'Cash', 1)")
        ->execute([$yesterday, $yesterday, $cust_id, $type_id, $dummy_lid]);
    echo "Created Yesterday Leftover Sale (1kg) - Should be ignored by fresh surplus logic\n";

    // Existing Old Leftover
    $pdo->prepare("INSERT INTO leftovers (source_date, purchase_id, qat_type_id, weight_kg, status, decision_date, sale_date)
                  VALUES (?, ?, ?, 2.0, 'Transferred_Next_Day', ?, ?)")
        ->execute([$yesterday, $purchase_id, $type_id, $yesterday, $yesterday]);
    $old_lid = $pdo->lastInsertId();
    echo "Created Existing Leftover ($old_lid, 2kg) - Should be Dropped\n";

    // 2. Run Logic
    echo "\nRunning trigger_auto_closing(\$pdo, '$yesterday') [Manual Mode]...\n";
    trigger_auto_closing($pdo, $yesterday);

    // 3. Verify
    $p_status = $pdo->query("SELECT status FROM purchases WHERE id = $purchase_id")->fetchColumn();
    echo "Purchase Status: $p_status (Expected: Closed)\n";

    $new_leftover = $pdo->query("SELECT weight_kg FROM leftovers WHERE purchase_id = $purchase_id AND source_date = '$yesterday' AND status = 'Transferred_Next_Day'")->fetch();
    if ($new_leftover) {
        $val = (float)$new_leftover['weight_kg'];
        echo "New Leftover Weight: {$val}kg (Expected: 6.0kg)\n";
        if (abs($val - 6.0) < 0.001) {
            echo "SUCCESS: Surplus calculation is correct.\n";
        } else {
            echo "FAILED: Surplus calculation incorrect ($val != 6.0)!\n";
        }
    } else {
        echo "FAILED: New Leftover not found!\n";
    }

    $old_status = $pdo->query("SELECT status FROM leftovers WHERE id = $old_lid")->fetchColumn();
    echo "Old Leftover Status: $old_status (Expected: Dropped)\n";

    echo "\n=== Test Completed! ===\n";

    // Manual Cleanup
    $pdo->exec("DELETE FROM sales WHERE purchase_id = $purchase_id OR leftover_id IN ($dummy_lid, $old_lid, 9999) OR sale_date = '$yesterday'");
    $pdo->exec("DELETE FROM leftovers WHERE purchase_id = $purchase_id OR id IN ($dummy_lid, $old_lid)");
    $pdo->exec("DELETE FROM purchases WHERE id = $purchase_id");
    echo "Cleanup Done.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
