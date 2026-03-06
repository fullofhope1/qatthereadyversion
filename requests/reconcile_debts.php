<?php
// requests/reconcile_debts.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php'; // Assuming auth is needed for admin operations

try {
    $pdo->beginTransaction();

    // 1. Get all customers
    $customers = $pdo->query("SELECT id FROM customers")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($customers as $cid) {
        // 1. Reset all paid_amounts for this customer
        $pdo->prepare("UPDATE sales SET paid_amount = 0.00, is_paid = 0 WHERE customer_id = ? AND payment_method = 'Debt'")->execute([$cid]);

        // 2. Calculate Total Payments
        $stmtPayments = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE customer_id = ?");
        $stmtPayments->execute([$cid]);
        $totalPaid = (float)($stmtPayments->fetchColumn() ?: 0);

        // 3. Distribute payments across sales
        if ($totalPaid > 0) {
            $stmtSales = $pdo->prepare("SELECT id, price FROM sales WHERE customer_id = ? AND payment_method = 'Debt' ORDER BY sale_date ASC, id ASC");
            $stmtSales->execute([$cid]);
            $unpaidSales = $stmtSales->fetchAll();

            $remaining = $totalPaid;
            foreach ($unpaidSales as $sale) {
                if ($remaining <= 0) break;
                $price = (float)$sale['price'];
                if ($remaining >= $price) {
                    $pdo->prepare("UPDATE sales SET paid_amount = ?, is_paid = 1 WHERE id = ?")->execute([$price, $sale['id']]);
                    $remaining -= $price;
                } else {
                    $pdo->prepare("UPDATE sales SET paid_amount = ?, is_paid = 0 WHERE id = ?")->execute([$remaining, $sale['id']]);
                    $remaining = 0;
                }
            }
        }

        // 4. Update Customer Record for quick access
        $stmtTotalSold = $pdo->prepare("SELECT SUM(price - COALESCE(refund_amount, 0)) FROM sales WHERE customer_id = ? AND payment_method = 'Debt'");
        $stmtTotalSold->execute([$cid]);
        $totalSoldDebt = (float)($stmtTotalSold->fetchColumn() ?: 0);
        $newDebt = round($totalSoldDebt - $totalPaid, 2);

        $pdo->prepare("UPDATE customers SET total_debt = ? WHERE id = ?")->execute([$newDebt, $cid]);
    }

    $pdo->commit();
    header("Location: ../debts.php?reconciled=1");
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Reconciliation Error: " . $e->getMessage());
}
