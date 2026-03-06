<?php
require 'config/db.php';
require 'includes/auto_close.php';

echo "Running Advanced Leftovers (Momsi & Dropped) Verification Tests...\n";

try {
    $pdo->beginTransaction();

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $two_days_ago = date('Y-m-d', strtotime('-2 days'));

    // 1. CLEAR EXISTING DATA for clean test
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec("DELETE FROM leftovers");
    $pdo->exec("DELETE FROM sales");
    $pdo->exec("DELETE FROM purchases");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    // Create a mock Qat Type and Provider
    $pdo->exec("INSERT INTO qat_types (name) VALUES ('TEST_QAT')");
    $qat_id = $pdo->lastInsertId();
    $pdo->exec("INSERT INTO providers (name, phone) VALUES ('TEST_PROV', '000000')");
    $prov_id = $pdo->lastInsertId();

    // 2. CREATE A PURCHASE 2 DAYS AGO (10kg)
    $stmt = $pdo->prepare("INSERT INTO purchases (purchase_date, qat_type_id, provider_id, quantity_kg, received_weight_grams, is_received, status) VALUES (?, ?, ?, 10.0, 10000, 1, 'Fresh')");
    $stmt->execute([$two_days_ago, $qat_id, $prov_id]);
    $fresh_pid = $pdo->lastInsertId();
    echo "Created Fresh Purchase ($two_days_ago) ID: $fresh_pid (10kg)\n";

    // 3. SELL 2kg of the Fresh Purchase (Leaves 8kg Surplus)
    $stmt = $pdo->prepare("INSERT INTO sales (sale_date, due_date, qat_type_id, purchase_id, weight_grams, price, payment_method) VALUES (?, ?, ?, ?, 2000, 10000, 'Cash')");
    $stmt->execute([$two_days_ago, $two_days_ago, $qat_id, $fresh_pid]);
    echo "Sold 2kg. Fresh Surplus is 8kg.\n";

    // 4. MANUALLY DROP 1kg of the Fresh Purchase
    $stmt = $pdo->prepare("INSERT INTO leftovers (source_date, purchase_id, qat_type_id, weight_kg, status, sale_date) VALUES (?, ?, ?, 1.0, 'Dropped', ?)");
    $stmt->execute([$two_days_ago, $fresh_pid, $qat_id, $two_days_ago]);
    echo "Manually dropped 1kg. Unmanaged Fresh Surplus should be 7kg.\n";

    // --- TRIGGER AUTO CLOSE FOR $two_days_ago ---
    $pdo->commit(); // Commit setup so auto_close can read it
    trigger_auto_closing($pdo, $two_days_ago);
    $pdo->beginTransaction(); // Re-open for further checks

    // --- ASSERTIONS AFTER CLOSE 1 ---
    echo "\n--- VERIFICATION 1 (After closing $two_days_ago) ---\n";

    // Check Momsi Purchase (should be 7kg, not 8kg)
    $stmt = $pdo->prepare("SELECT quantity_kg FROM purchases WHERE status = 'Momsi' AND purchase_date = ? AND original_purchase_id = ?");
    $stmt->execute([$yesterday, $fresh_pid]);
    $momsi_qty = $stmt->fetchColumn();
    if ($momsi_qty == 7.0) {
        echo "[PASS] Momsi purchase correctly transferred 7.0kg\n";
    } else {
        echo "[FAIL] Momsi purchase transferred $momsi_qty kg (expected 7.0kg! Did not respect manual drop)\n";
    }

    // Check Auto_Momsi leftovers record
    $stmt = $pdo->prepare("SELECT weight_kg FROM leftovers WHERE status = 'Auto_Momsi' AND source_date = ? AND purchase_id = ?");
    $stmt->execute([$two_days_ago, $fresh_pid]);
    $auto_m_qty = $stmt->fetchColumn();
    if ($auto_m_qty == 7.0) {
        echo "[PASS] Auto_Momsi leftover record correctly logged 7.0kg\n";
    } else {
        echo "[FAIL] Auto_Momsi record is $auto_m_qty kg (expected 7.0kg)\n";
    }


    // 5. CONTINUE TO YESTERDAY (Momsi exists, 7kg)
    // We sell 3kg of Momsi (Leaves 4kg Momsi Surplus)
    // First, find the Momsi ID
    $stmt = $pdo->prepare("SELECT id FROM purchases WHERE status = 'Momsi' AND purchase_date = ?");
    $stmt->execute([$yesterday]);
    $momsi_pid = $stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO sales (sale_date, due_date, qat_type_id, purchase_id, weight_grams, price, payment_method) VALUES (?, ?, ?, ?, 3000, 10000, 'Cash')");
    $stmt->execute([$yesterday, $yesterday, $qat_id, $momsi_pid]);
    echo "\nSold 3kg of Momsi ($yesterday). Momsi Surplus is 4kg.\n";

    // --- TRIGGER AUTO CLOSE FOR $yesterday ---
    $pdo->commit();
    trigger_auto_closing($pdo, $yesterday);
    $pdo->beginTransaction();

    // --- ASSERTIONS AFTER CLOSE 2 ---
    echo "\n--- VERIFICATION 2 (After closing $yesterday) ---\n";

    // Check Auto_Dropped leftovers record (should be 4kg)
    $stmt = $pdo->prepare("SELECT weight_kg FROM leftovers WHERE status = 'Auto_Dropped' AND source_date = ? AND purchase_id = ?");
    $stmt->execute([$yesterday, $momsi_pid]);
    $auto_d_qty = $stmt->fetchColumn();

    if ($auto_d_qty == 4.0) {
        echo "[PASS] Expired Momsi correctly logged as Auto_Dropped (4.0kg)\n";
    } else {
        echo "[FAIL] Auto_Dropped record is $auto_d_qty kg (expected 4.0kg)\n";
    }

    // Check no duplicate Momsi
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE status = 'Momsi' AND purchase_date = ?");
    $stmt->execute([$today]);
    $today_momsi_count = $stmt->fetchColumn();
    if ($today_momsi_count == 0) {
        echo "[PASS] No new Momsi created for today from expired Momsi\n";
    } else {
        echo "[FAIL] Created $today_momsi_count Momsi for today (should be 0!)\n";
    }

    $pdo->rollBack(); // Rollback all test data
    echo "\nVERIFICATION COMPLETE! Rolled back successfully.\n";
} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
