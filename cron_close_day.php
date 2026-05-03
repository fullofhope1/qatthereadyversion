<?php
/**
 * cron_close_day.php
 *
 * This script is designed to be run via a Cron Job or Command Line (CLI).
 * It triggers the Daily Closing logic (moving stock to leftovers, rolling over debts).
 *
 * Usage (CLI): php cron_close_day.php
 * Usage (Cron): 0 0 * * * /usr/bin/php /path/to/your/site/cron_close_day.php >> /path/to/your/site/logs/cron.log 2>&1
 */

// 1. Ensure this is only run via CLI (optional but recommended for security)
if (php_sapi_name() !== 'cli' && !isset($_GET['secret_key'])) {
    die("Unauthorized access. This script must be run via CLI or with a secret key.");
}

// Secret key check for web-triggered cron (if CLI is not available)
$secret = 'your_random_secret_here'; // Change this to something secure
if (php_sapi_name() !== 'cli' && ($_GET['secret_key'] ?? '') !== $secret) {
    die("Invalid secret key.");
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/Autoloader.php';

// No need for require_auth.php since this is a background task
require_once __DIR__ . '/includes/classes/DailyCloseRepository.php';
require_once __DIR__ . '/includes/classes/DailyCloseService.php';
require_once __DIR__ . '/includes/classes/DebtRepository.php';

define('LOG_DATE_FORMAT', 'Y-m-d H:i:s');
$today = date('Y-m-d');

echo "[".date(LOG_DATE_FORMAT)."] Starting Daily Close for date: $today\n";

try {
    $repository = new DailyCloseRepository($pdo);
    $debtRepo = new DebtRepository($pdo);
    $service = new DailyCloseService($repository, $debtRepo);
    
    // We use forceAll=true to ensure everything from yesterday/today is processed
    $success = $service->closeDay($today, true);

    if ($success) {
        // Log the closure
        $stmtLog = $pdo->prepare("INSERT IGNORE INTO closed_shifts (closing_date, closed_by) VALUES (?, ?)");
        $stmtLog->execute([$today, 'SYSTEM_CRON']);
        
        echo "[".date(LOG_DATE_FORMAT)."] SUCCESS: Daily Close completed successfully.\n";
    } else {
        echo "[".date(LOG_DATE_FORMAT)."] FAILED: Service returned false.\n";
    }

} catch (Exception $e) {
    echo "[".date(LOG_DATE_FORMAT)."] ERROR: " . $e->getMessage() . "\n";
    error_log("CRON ERROR: " . $e->getMessage());
}
