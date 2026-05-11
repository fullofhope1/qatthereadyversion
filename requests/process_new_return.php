<?php
require '../config/db.php';
require '../includes/auth.php';
require '../includes/classes/BaseRepository.php';
require '../includes/classes/BaseService.php';
require '../includes/classes/RefundRepository.php';
require '../includes/classes/CustomerRepository.php';
require '../includes/classes/SaleRepository.php';
require '../includes/classes/PurchaseRepository.php';
require '../includes/classes/LeftoverRepository.php';
require '../includes/classes/ReportRepository.php';
require '../includes/classes/RefundService.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $refundRepo = new RefundRepository($pdo);
    $customerRepo = new CustomerRepository($pdo);
    $saleRepo = new SaleRepository($pdo);
    $purchaseRepo = new PurchaseRepository($pdo);
    $leftoverRepo = new LeftoverRepository($pdo);
    $reportRepo = new ReportRepository($pdo);

    $service = new RefundService($refundRepo, $customerRepo, $saleRepo, $purchaseRepo, $leftoverRepo, $reportRepo);

    $data = [
        'customer_id'    => $_POST['customer_id'],
        'sale_id'        => !empty($_POST['sale_id']) ? $_POST['sale_id'] : null,
        'amount'         => $_POST['amount'],
        'refund_type'    => $_POST['refund_type'] ?? 'Debt',
        'reason'         => $_POST['reason'] ?? 'مرتجع بضاعة',
        'weight_kg'      => !empty($_POST['weight_kg']) ? $_POST['weight_kg'] : 0,
        'quantity_units' => !empty($_POST['quantity_units']) ? $_POST['quantity_units'] : 0,
    ];

    try {
        $userId = $_SESSION['user_id'] ?? null;
        $service->processRefund($data, $userId);
        header("Location: ../returns.php?success=1");
        exit;
    } catch (Exception $e) {
        $error = urlencode($e->getMessage());
        header("Location: ../returns.php?error=$error");
        exit;
    }
}
