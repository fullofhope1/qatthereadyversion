<?php
// requests/process_purchase_discount.php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        die("Unauthorized");
    }

    $purchase_id = (int)$_POST['purchase_id'];
    $amount = (float)$_POST['amount'];

    if (!$purchase_id || $amount <= 0) {
        header("Location: ../sourcing.php?error=" . urlencode("Invalid Data"));
        exit;
    }

    try {
        $purchaseRepo = new PurchaseRepository($pdo);
        $productRepo = new ProductRepository($pdo);
        $service = new PurchaseService($purchaseRepo, $productRepo);

        if ($service->applyDiscount($purchase_id, $amount)) {
            header("Location: ../sourcing.php?success=1");
            exit;
        } else {
            throw new Exception("Failed to apply discount");
        }
    } catch (Exception $e) {
        header("Location: ../sourcing.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../sourcing.php");
    exit;
}
