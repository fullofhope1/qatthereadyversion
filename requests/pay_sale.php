<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_id = $_POST['sale_id'];
    $amount = $_POST['amount'];
    $mark_paid = isset($_POST['mark_paid']) ? 1 : 0;

    if (!$sale_id || !$amount) {
        header("Location: ../reports.php?error=MissingData");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Get Sale Details
        $stmt = $pdo->prepare("SELECT customer_id, price, paid_amount FROM sales WHERE id = ?");
        $stmt->execute([$sale_id]);
        $sale = $stmt->fetch();

        if ($sale && $sale['customer_id']) {
            $customer_id = $sale['customer_id'];
            $price = $sale['price'];
            $already_paid = $sale['paid_amount'] ?? 0;
            $remaining = $price - $already_paid;

            // VALIDATION: Prevent Overpayment
            if ($amount > $remaining) {
                // If amount is drastically larger, stop.
                // Allow small floating point differences? No, strict for now based on user request.
                header("Location: ../reports.php?error=Overpayment! Remaining debt for this sale is " . $remaining);
                exit;
            }

            // 2. Register Payment
            $paySql = "INSERT INTO payments (customer_id, amount, note, payment_date) VALUES (?, ?, ?, CURRENT_DATE)";
            $pdo->prepare($paySql)->execute([$customer_id, $amount, "Part Pay Sale #$sale_id"]);

            // 3. Update Customer Debt
            $updateCust = "UPDATE customers SET total_debt = total_debt - ? WHERE id = ?";
            $pdo->prepare($updateCust)->execute([$amount, $customer_id]);

            // 4. Update Sale Progress
            $new_paid_amount = $already_paid + $amount;
            // Check if fully paid (float comparison safe-ish with 2 decimals)
            $is_fully_paid = ($new_paid_amount >= $price - 0.01) ? 1 : 0;

            $updateSale = "UPDATE sales SET paid_amount = ?, is_paid = ? WHERE id = ?";
            $pdo->prepare($updateSale)->execute([$new_paid_amount, $is_fully_paid, $sale_id]);
        }

        $pdo->commit();
        header("Location: ../reports.php?success=Paid");
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: ../reports.php");
}
