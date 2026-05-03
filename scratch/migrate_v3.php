<?php
require_once 'config/db.php';
try {
    // 1. Create Staff Attendance Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS staff_attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id INT NOT NULL,
        work_date DATE NOT NULL,
        status ENUM('Present', 'Absent') DEFAULT 'Present',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_staff_date (staff_id, work_date),
        FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
    )");
    echo "Table 'staff_attendance' created.\n";

    // 2. Add is_closed to daily_closes if not exists (to track manual vs auto)
    $pdo->exec("ALTER TABLE daily_closes ADD COLUMN closed_by_user INT DEFAULT NULL AFTER is_auto");
    echo "Column 'closed_by_user' added to 'daily_closes'.\n";

} catch (Exception $e) {
    echo "Database Update Info: " . $e->getMessage() . "\n";
}
?>
