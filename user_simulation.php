<?php
require 'config/db.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== BUSINESS OWNER SIMULATION ===\n";

try {
    $pdo->beginTransaction();

    // 1. Setup Master Data
    $pdo->exec("INSERT IGNORE INTO qat_types (name) VALUES ('Sabri'), ('Arhabi'), ('Hamdani')");
    $types = $pdo->query("SELECT id, name FROM qat_types")->fetchAll(PDO::FETCH_KEY_PAIR);

    $pdo->exec("INSERT IGNORE INTO providers (name) VALUES ('Ahmed Field'), ('Khaled Farm'), ('Sultan Qat')");
    $providers = $pdo->query("SELECT id, name FROM providers")->fetchAll(PDO::FETCH_KEY_PAIR);

    $pdo->exec("INSERT IGNORE INTO customers (name) VALUES ('Ali Customer'), ('Saleh Retail'), ('Tayyar 1')");
    $customers = $pdo->query("SELECT id, name FROM customers")->fetchAll(PDO::FETCH_KEY_PAIR);

    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $today = date('Y-m-d');

    echo "Data Setup done.\n";

    // 2. Simulate "Yesterday" Morning (Purchases)
    foreach ($types as $tid => $tname) {
        $prov_id = array_rand($providers);
        $qty = rand(20, 50);
        $pdo->prepare("INSERT INTO purchases (purchase_date, qat_type_id, provider_id, source_weight_grams, received_weight_grams, quantity_kg, agreed_price, status, is_received, received_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'Fresh', 1, ?)")
            ->execute([$yesterday, $tid, $prov_id, $qty * 1000, $qty * 1000, $qty, rand(100000, 500000), $yesterday . ' 08:00:00']);
        $p_id = $pdo->lastInsertId();

        // 3. Simulate "Yesterday" Sales
        // A mix of Cash and Debt
        $sold_qty = rand(10, $qty - 5);

        // Cash Sale
        $cash_qty = floor($sold_qty * 0.4);
        $pdo->prepare("INSERT INTO sales (sale_date, customer_id, qat_type_id, purchase_id, weight_grams, weight_kg, price, payment_method, is_paid)
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'Cash', 1)")
            ->execute([$yesterday, array_rand($customers), $tid, $p_id, $cash_qty * 1000, $cash_qty, $cash_qty * 10000]);

        // Debt Sale (Daily)
        $debt_qty = $sold_qty - $cash_qty;
        $pdo->prepare("INSERT INTO sales (sale_date, due_date, customer_id, qat_type_id, purchase_id, weight_grams, weight_kg, price, payment_method, is_paid, debt_type)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Debt', 0, 'Daily')")
            ->execute([$yesterday, $yesterday, array_rand($customers), $tid, $p_id, $debt_qty * 1000, $debt_qty, $debt_qty * 9000]);
    }

    echo "Yesterday's data simulated.\n";

    // 4. Simulate Closing Yesterday
    require_once 'includes/auto_close.php';
    echo "Running Auto-Close for Yesterday...\n";
    trigger_auto_closing($pdo, $yesterday);

    // 5. Check Today's State
    echo "\n--- TODAY'S REFRESH CHECK ---\n";

    // Check Leftovers
    $l_count = $pdo->query("SELECT COUNT(*) FROM leftovers WHERE status = 'Transferred_Next_Day'")->fetchColumn();
    echo "New Leftovers created: $l_count\n";

    // Check Rolled Debts
    $d_count = $pdo->query("SELECT COUNT(*) FROM sales WHERE sale_date = '$today' AND payment_method = 'Debt' AND debt_type = 'Daily' AND is_paid = 0")->fetchColumn();
    echo "Daily Debts rolled to today: $d_count\n";

    $pdo->commit();
    echo "\nSimulation Finished Successfully.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "SIMULATION ERROR: " . $e->getMessage() . "\n";
}
