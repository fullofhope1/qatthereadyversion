<?php
// requests/close_day.php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $today = $_POST['date'] ?? date('Y-m-d');

    try {
        require_once '../includes/auto_close.php';

        // Check if the requested date is ALREADY closed
        $stmt = $pdo->prepare("SELECT MIN(d) FROM (
            SELECT MIN(COALESCE(purchase_date, DATE(created_at))) as d FROM purchases WHERE status IN ('Fresh', 'Momsi') AND (purchase_date <= ? OR (purchase_date IS NULL AND DATE(created_at) <= ?))
            UNION
            SELECT MIN(COALESCE(sale_date, DATE(created_at))) as d FROM sales WHERE payment_method = 'Debt' AND debt_type = 'Daily' AND is_paid = 0 AND (sale_date <= ? OR (sale_date IS NULL AND DATE(created_at) <= ?))
            UNION
            SELECT MIN(sale_date) as d FROM leftovers WHERE status IN ('Transferred_Next_Day', 'Auto_Momsi') AND sale_date <= ?
        ) as unclosed_dates");
        $stmt->execute([$today, $today, $today, $today, $today]);
        $oldest = $stmt->fetchColumn();

        if (!$oldest || $oldest > $today) {
            // The selected day is already fully closed. 
            // This means the user clicked 'Close Day' a second time.
            // They want to close the next day (which holds the newly created Momis/Leftovers)
            $tomorrow = date('Y-m-d', strtotime($today . ' +1 day'));
            trigger_auto_closing($pdo, $tomorrow);
            header("Location: ../dashboard.php?msg=Advanced and Closed Next Day Successfully");
        } else {
            // Normal close for the selected day
            trigger_auto_closing($pdo, $today);
            header("Location: ../dashboard.php?msg=Day Closed Successfully");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error Closing Day: " . $e->getMessage());
    }
}
