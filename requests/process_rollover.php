<?php
require '../config/db.php';

if (isset($_GET['sale_id'])) {
    $sale_id = $_GET['sale_id'];

    try {
        // Move sale to tomorrow
        // We update sale_date and due_date to DATE_ADD(sale_date, INTERVAL 1 DAY)
        // Or should we just move it to "Tomorrow" relative to today?
        // User said "move... from today's one to tomorrow's one".
        // Assuming this means "shift the date forward by one day".

        $sql = "UPDATE sales SET sale_date = DATE_ADD(sale_date, INTERVAL 1 DAY), due_date = DATE_ADD(due_date, INTERVAL 1 DAY) WHERE id = ?";
        $pdo->prepare($sql)->execute([$sale_id]);

        // Redirect back
        // We need to preserve the report view if possible, but for now just back to reports
        header("Location: ../reports.php?success=RolloverDeone");
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: ../reports.php");
}
