<?php
// requests/process_leftover.php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/require_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $purchaseRepo = new PurchaseRepository($pdo);
        $leftoverRepo = new LeftoverRepository($pdo);
        $service = new LeftoverService($leftoverRepo, $purchaseRepo);

        $itemId = $_POST['item_id'];
        $sourceType = $_POST['source_type']; // 'Fresh' or 'Momsi'
        $amount = $_POST['amount'];
        $action = $_POST['action']; // 'Drop' or 'SellNextDay'
        $notes = $_POST['notes'] ?? '';

        $service->processDecision($itemId, $sourceType, $amount, $action, $notes);

        header("Location: ../leftovers.php?success=1");
        exit;
    } catch (Exception $e) {
        die("Error processing leftover: " . $e->getMessage());
    }
}
