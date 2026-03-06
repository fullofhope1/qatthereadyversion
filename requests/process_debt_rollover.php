<?php
session_start();
require '../config/db.php';
require '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $customer_id = $_POST['customer_id'];
        $debt_type   = $_POST['debt_type'];

        // Smart rollover: respect debt_type (#25)
        // Daily   -> +1 day
        // Monthly -> +1 month
        // Yearly  -> +1 year
        switch ($debt_type) {
            case 'Monthly':
                $new_due_date = date('Y-m-d', strtotime('+1 month'));
                break;
            case 'Yearly':
                $new_due_date = date('Y-m-d', strtotime('+1 year'));
                break;
            default: // Daily
                $new_due_date = date('Y-m-d', strtotime('+1 day'));
                break;
        }

        $sql = "UPDATE sales
                SET due_date = :newDate
                WHERE customer_id = :cid
                  AND debt_type = :dtype
                  AND is_paid = 0
                  AND due_date <= CURDATE()";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':newDate' => $new_due_date,
            ':cid'     => $customer_id,
            ':dtype'   => $debt_type
        ]);

        header("Location: ../debts.php?type={$debt_type}&msg=rolled_over");
        exit;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
