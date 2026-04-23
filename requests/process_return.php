<?php
// requests/process_return.php
require '../config/db.php';
require '../includes/Autoloader.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

$saleId = $_POST['sale_id'] ?? null;
$reason = $_POST['reason'] ?? 'مرتجع عادي';

if (!$saleId) {
    echo json_encode(['success' => false, 'message' => 'Missing Sale ID']);
    exit;
}

try {
    // Repository Setup
    $saleRepo = new SaleRepository($pdo);
    $purchaseRepo = new PurchaseRepository($pdo);
    $customerRepo = new CustomerRepository($pdo);
    $leftoverRepo = new LeftoverRepository($pdo);
    $productRepo = new ProductRepository($pdo);
    $unitService = new UnitSalesService($purchaseRepo, $leftoverRepo, $saleRepo);

    $service = new SaleService($saleRepo, $purchaseRepo, $customerRepo, $leftoverRepo, $unitService);

    $service->processReturn($saleId, $reason);

    echo json_encode(['success' => true, 'message' => 'تم إرجاع العملية بنجاح وإعادة الكمية للمخزون.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
