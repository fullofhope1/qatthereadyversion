<?php
require '../config/db.php';
// Backdate all unpaid sales to yesterday so they appear as Overdue
$yesterday = date('Y-m-d', strtotime('-1 day'));
$sql = "UPDATE sales SET sale_date = '$yesterday', due_date = '$yesterday' WHERE is_paid = 0";
$pdo->exec($sql);
echo "All unpaid debts have been backdated to $yesterday. They should now appear as Overdue.";
