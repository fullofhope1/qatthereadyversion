<?php
// requests/process_expense.php
require '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        die("Unauthorized");
    }

    try {
        $desc = $_POST['description'];
        $amount = $_POST['amount'];
        $cat = $_POST['category'];
        $staff_id = !empty($_POST['staff_id']) ? $_POST['staff_id'] : null;
        $date = !empty($_POST['expense_date']) ? $_POST['expense_date'] : date('Y-m-d');
        $user_id = $_SESSION['user_id'];

        // VALIDATION: Check Staff Limit
        if ($cat === 'Staff' && $staff_id) {
            $stmtLimit = $pdo->prepare("
                SELECT s.name, s.withdrawal_limit, 
                (SELECT SUM(amount) FROM expenses WHERE staff_id = s.id AND category = 'Staff') as current_total
                FROM staff s WHERE s.id = ?
            ");
            $stmtLimit->execute([$staff_id]);
            $staffData = $stmtLimit->fetch();

            if ($staffData) {
                // Only enforce if limit is NOT NULL and > 0
                if ($staffData['withdrawal_limit'] !== null && $staffData['withdrawal_limit'] > 0) {
                    $used = $staffData['current_total'] ?: 0;
                    $rem = $staffData['withdrawal_limit'] - $used;
                    if ($amount > $rem) {
                        $error = urlencode("تجاوز السقف للعامل (" . $staffData['name'] . ")! المتبقي: " . number_format($rem) . " ريال (المسحوب: " . number_format($used) . ")");
                        header("Location: ../expenses.php?error=$error");
                        exit;
                    }
                }
            }
        }

        $sql = "INSERT INTO expenses (expense_date, description, amount, category, staff_id, created_by) VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$date, $desc, $amount, $cat, $staff_id, $user_id]);

        header("Location: ../expenses.php?success=1");
    } catch (PDOException $e) {
        $error = urlencode("Database Error: " . $e->getMessage());
        header("Location: ../expenses.php?error=$error");
    }
}
