<?php
session_start();
require '../config/db.php';
require '../includes/auth.php';

// requireRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sale_id = $_POST['sale_id'];
        $amount = $_POST['amount']; // The Refund Amount

        if ($amount <= 0) {
            die("Invalid amount");
        }

        // Get Sale Details
        $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
        $stmt->execute([$sale_id]);
        $sale = $stmt->fetch();

        if (!$sale) die("Sale not found");

        // 1. Update Sale Record (Track Refund)
        // We accumulate refunds just in case (though usually one time)
        $new_refund_total = $sale['refund_amount'] + $amount;

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE sales SET refund_amount = ? WHERE id = ?");
        $stmt->execute([$new_refund_total, $sale_id]);

        // 2. Handle Financial Impact
        if ($sale['payment_method'] === 'Debt' && $sale['is_paid'] == 0) {
            // IF DEBT: Reduce Customer Debt
            // AND reduce the sale price? Or just the debt balance?
            // Usually, if I sold for 5000 debt, and refund 1000. 
            // The sale effective price becomes 4000.
            // Customer owes 4000.
            // So we lower Customer Debt.

            if ($sale['customer_id']) {
                $stmt = $pdo->prepare("UPDATE customers SET total_debt = total_debt - ? WHERE id = ?");
                $stmt->execute([$amount, $sale['customer_id']]);
            }
        } else {
            // IF CASH / TRANSFER / PAID DEBT: 
            // User gives money BACK to customer.
            // We should record this as an Expense (Cash Out) OR just a Refund log.
            // To make "Cash Report" accurate (Sales - Expenses), we should treat Refund as Expense?
            // Or subtract from Sales Total?
            // User asked "give me back some money". Implicitly cash out.
            // Let's add an Expense record automatically so it shows up in daily close.

            $desc = "Refund for Sale #{$sale_id} (Customer Return)";
            $stmt = $pdo->prepare("INSERT INTO expenses (expense_date, description, amount, category, created_by) VALUES (CURDATE(), ?, ?, 'Refund', ?)");
            $stmt->execute([$desc, $amount, $_SESSION['user_id']]);
        }

        $pdo->commit();

        // Redirect back to report
        header("Location: ../reports.php?date=" . $sale['sale_date'] . "&detail=all&success=Refunded{$amount}");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}
