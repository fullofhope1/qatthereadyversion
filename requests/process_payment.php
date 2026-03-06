<?php
// requests/process_payment.php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $customer_id = (int)$_POST['customer_id'];
        $amount = (float)$_POST['amount'];
        $note = $_POST['note'];
        $back = $_POST['back'] ?? 'customers';

        // Validate amount does not exceed total_debt
        $custStmt = $pdo->prepare("SELECT total_debt FROM customers WHERE id = ?");
        $custStmt->execute([$customer_id]);
        $cust = $custStmt->fetch();

        if (!$cust) {
            header("Location: ../customer_details.php?id=$customer_id&pay_error=" . urlencode("العميل غير موجود"));
            exit;
        }

        if ($amount <= 0) {
            header("Location: ../customer_details.php?id=$customer_id&back=$back&pay_error=" . urlencode("المبلغ يجب أن يكون أكبر من صفر"));
            exit;
        }

        if ($amount > $cust['total_debt']) {
            header("Location: ../customer_details.php?id=$customer_id&back=$back&pay_error=" . urlencode("مبلغ السداد (" . number_format($amount) . ") أكبر من الدين الحالي (" . number_format($cust['total_debt']) . ")"));
            exit;
        }

        // --- START ATOMIC TRANSACTION ---
        $pdo->beginTransaction();

        // 1. Insert Payment
        $sql = "INSERT INTO payments (customer_id, amount, note, payment_date) VALUES (:id, :amount, :note, CURRENT_DATE)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $customer_id, ':amount' => $amount, ':note' => $note]);

        // 2. Distribute payment across sales (oldest first)
        $remaining = $amount;
        $getSales = $pdo->prepare("SELECT id, price, paid_amount FROM sales WHERE customer_id = ? AND payment_method = 'Debt' AND is_paid = 0 ORDER BY sale_date ASC, id ASC");
        $getSales->execute([$customer_id]);
        $unpaidSales = $getSales->fetchAll();

        foreach ($unpaidSales as $sale) {
            if ($remaining <= 0) break;

            $needed = $sale['price'] - $sale['paid_amount'];
            if ($remaining >= $needed) {
                // Fully pay this sale
                $pdo->prepare("UPDATE sales SET paid_amount = price, is_paid = 1 WHERE id = ?")->execute([$sale['id']]);
                $remaining -= $needed;
            } else {
                // Partially pay this sale
                $pdo->prepare("UPDATE sales SET paid_amount = paid_amount + ? WHERE id = ?")->execute([$remaining, $sale['id']]);
                $remaining = 0;
            }
        }

        // 3. Sync Customer total_debt (optional but good for speed)
        $updateSql = "UPDATE customers SET total_debt = total_debt - :amount WHERE id = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([':amount' => $amount, ':id' => $customer_id]);

        $pdo->commit();
        // --- END ATOMIC TRANSACTION ---

        header("Location: ../customer_details.php?id=$customer_id&back=$back&success=1");
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}
