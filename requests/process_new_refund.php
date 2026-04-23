<?php
// requests/process_new_refund.php
require '../config/db.php';
require_once '../includes/Autoloader.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        die("Unauthorized");
    }

    $customer_id = (int)$_POST['customer_id'];
    $amount      = (float)$_POST['amount'];
    $type        = $_POST['refund_type'];
    $reason      = $_POST['reason'];
    $user_id     = $_SESSION['user_id'];

    if (!$customer_id || !$amount || !$type) {
        header("Location: ../refunds.php?error=" . urlencode("بيانات غير مكتملة"));
        exit;
    }

    $refundRepo = new RefundRepository($pdo);
    $customerRepo = new CustomerRepository($pdo);
    $saleRepo = new SaleRepository($pdo);
    $purchaseRepo = new PurchaseRepository($pdo);
    $leftoverRepo = new LeftoverRepository($pdo);
    
    $service = new RefundService($refundRepo, $customerRepo, $saleRepo, $purchaseRepo, $leftoverRepo);

    $data = [
        'customer_id' => (int)$_POST['customer_id'],
        'amount' => (float)$_POST['amount'],
        'refund_type' => $_POST['refund_type'],
        'reason' => $_POST['reason'],
        'weight_kg' => (float)($_POST['weight_kg'] ?? 0),
        'quantity_units' => (int)($_POST['quantity_units'] ?? 0),
        'sale_id' => !empty($_POST['sale_id']) ? (int)$_POST['sale_id'] : null
    ];

    try {
        if ($service->processRefund($data, $user_id)) {
            header("Location: ../refunds.php?success=1");
            exit;
        }
    } catch (Exception $e) {
        header("Location: ../refunds.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}
