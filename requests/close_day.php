<?php
// requests/close_day.php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/require_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $today = $_POST['date'] ?? date('Y-m-d');

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

        header("Location: ../closing.php");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error Closing Day: " . $e->getMessage());
    }
}
