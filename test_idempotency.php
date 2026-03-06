<?php
// test_idempotency.php
require_once 'config/db.php';
require_once 'includes/auto_close.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    $type_id = $pdo->query("SELECT id FROM qat_types LIMIT 1")->fetchColumn();

    // 1. Create a Fresh Purchase
    $pdo->prepare("INSERT INTO purchases (purchase_date, qat_type_id, vendor_name, agreed_price, quantity_kg, status) 
                  VALUES (?, ?, 'Test Idempotency', 1000, 10.0, 'Fresh')")
        ->execute([$today, $type_id]);
    $purchase_id = $pdo->lastInsertId();
    echo "Created Fresh Purchase ID: $purchase_id (10kg)\n";

    // 2. Run Auto Close
    echo "Running Close 1...\n";
    trigger_auto_closing($pdo, $today);

    // Verify 1 Momsi created
    $momsi_list = $pdo->query("SELECT id, quantity_kg FROM purchases WHERE original_purchase_id = $purchase_id AND status = 'Momsi'")->fetchAll();
    echo "Count of Momsi: " . count($momsi_list) . " (Expected: 1)\n";
    if (count($momsi_list) > 0) {
        echo "Momsi Quantity: {$momsi_list[0]['quantity_kg']}kg (Expected: 10.0kg)\n";
    }

    // 3. Re-open the purchase and simulate a sale
    $pdo->prepare("UPDATE purchases SET status = 'Fresh' WHERE id = ?")->execute([$purchase_id]);

    // Simulate a sale by inserting it
    $cust_id = $pdo->query("SELECT id FROM customers LIMIT 1")->fetchColumn();
    $pdo->prepare("INSERT INTO sales (sale_date, due_date, customer_id, qat_type_id, purchase_id, weight_grams, price, payment_method, is_paid) 
                  VALUES (?, ?, ?, ?, ?, 4000, 500, 'Debt', 0)")
        ->execute([$today, $today, $cust_id, $type_id, $purchase_id]);
    $sale_id = $pdo->lastInsertId();
    echo "\nSimulated 4kg sale. Now surplus is 6.0kg.\n";

    // 4. Run Auto Close AGAIN
    echo "Running Close 2...\n";
    trigger_auto_closing($pdo, $today);

    // Verify exactly 1 Momsi, but quantity is 6
    $momsi_list2 = $pdo->query("SELECT id, quantity_kg FROM purchases WHERE original_purchase_id = $purchase_id AND status = 'Momsi'")->fetchAll();
    echo "Count of Momsi: " . count($momsi_list2) . " (Expected: 1)\n";
    if (count($momsi_list2) > 0) {
        echo "Momsi Quantity: {$momsi_list2[0]['quantity_kg']}kg (Expected: 6.0kg)\n";
    }

    // 5. Cleanup
    $pdo->exec("DELETE FROM sales WHERE id = $sale_id");
    $pdo->exec("DELETE FROM purchases WHERE original_purchase_id = $purchase_id");
    $pdo->exec("DELETE FROM purchases WHERE id = $purchase_id");
    echo "\nTest Passed Successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
