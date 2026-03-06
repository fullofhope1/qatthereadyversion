<?php
// requests/process_new_refund.php
require '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        die("Unauthorized");
    }

    $customer_id = (int)$_POST['customer_id'];
    $amount      = (float)$_POST['amount'];
    $type        = $_POST['refund_type'];
    $reason      = $_POST['reason'];
    $user_id     = $_SESSION['user_id'];

    if (!$customer_id || !$amount || !$type) {
        header("Location: ../refunds.php?error=" . urlencode("بيانات غير مكتملة"));
        exit;
    }

    // Server-side: refund amount must not exceed total_debt for Debt type (#31)
    if ($type === 'Debt') {
        $check = $pdo->prepare("SELECT total_debt FROM customers WHERE id = ?");
        $check->execute([$customer_id]);
        $cust = $check->fetch();
        if ($cust && $amount > $cust['total_debt']) {
            header("Location: ../refunds.php?error=" . urlencode("مبلغ التعويض (" . number_format($amount) . ") أكبر من دين الزبون (" . number_format($cust['total_debt']) . ")"));
            exit;
        }
    }

    try {
        $pdo->beginTransaction();

        // 1. Insert into Refunds Table
        $stmt = $pdo->prepare("INSERT INTO refunds (customer_id, amount, refund_type, reason, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$customer_id, $amount, $type, $reason, $user_id]);

        // 2. Handle Logic based on Type
        if ($type === 'Debt') {
            $stmt = $pdo->prepare("UPDATE customers SET total_debt = total_debt - ? WHERE id = ?");
            $stmt->execute([$amount, $customer_id]);
        }

        $pdo->commit();

        header("Location: ../refunds.php?success=1");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error processing refund: " . $e->getMessage());
    }
}
