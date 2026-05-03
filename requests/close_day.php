<?php
// requests/close_day.php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/require_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Protection
    if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? '')) {
        die("Security Check Failed: CSRF Token Mismatch.");
    }

    $today = $_POST['date'] ?? date('Y-m-d');
    $testMode = isset($_POST['test_mode']);

    try {
        // We explicitly trigger a forceful "Shift Close" which immediately ages
        // all active stock to the next stage, bypassing date comparisons.
        // This is per user request to treat closing as an explicit action.
        require_once '../includes/classes/DailyCloseRepository.php';
        require_once '../includes/classes/DailyCloseService.php';
        require_once '../includes/classes/DebtRepository.php';
        
        $repository = new DailyCloseRepository($pdo);
        $debtRepo = new DebtRepository($pdo);
        $service = new DailyCloseService($repository, $debtRepo);
        $service->closeDay($today, true);

        // Record the closure (Use INSERT IGNORE to avoid duplicate error if unique constraint exists, 
        // or just let it fail silently if we don't care about the log)
        $stmtLog = $pdo->prepare("INSERT IGNORE INTO closed_shifts (closing_date, closed_by) VALUES (?, ?)");
        $stmtLog->execute([$today, $_SESSION['user_id'] ?? null]);

        header("Location: ../closing.php?success=1");
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Handle Duplicate Entry Error (SQL state 23000, error code 1062)
        if (strpos($e->getMessage(), '1062') !== false || strpos($e->getMessage(), '23000') !== false) {
            header("Location: ../closing.php?success=1&already=1");
            exit;
        }
        
        die("<div style='direction:rtl; font-family:sans-serif; padding:20px; border:2px solid red; margin:20px; line-height:1.6;'>
                <h2 style='color:red;'>⚠️ تنبيه: تعذر إغلاق اليومية</h2>
                <p>حدث خطأ غير متوقع أثناء العملية. يرجى التواصل مع الدعم الفني.</p>
                <div style='background:#f8f9fa; padding:10px; font-family:monospace; border-radius:5px;'>
                    " . htmlspecialchars($e->getMessage()) . "
                </div>
                <br><a href='../closing.php' style='background:#333; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px;'>العودة للخلف</a>
            </div>");
    }
}
