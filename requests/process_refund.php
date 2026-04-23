<?php
// requests/process_refund.php
require '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/require_auth.php';

// Modernized handler replacing legacy procedural code with RefundService.
// This ensures that ALL refunds (even from legacy reports) use the same validated logic.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $saleId = (int)($_POST['sale_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);

        if (!$saleId || $amount <= 0) {
            throw new Exception("بيانات غير مكتملة أو مبلغ غير صحيح.");
        }

        // Initialize Services & Repositories
        $refundRepo   = new RefundRepository($pdo);
        $customerRepo = new CustomerRepository($pdo);
        $saleRepo     = new SaleRepository($pdo);
        $purchaseRepo = new PurchaseRepository($pdo);
        $leftoverRepo = new LeftoverRepository($pdo);

        $service = new RefundService($refundRepo, $customerRepo, $saleRepo, $purchaseRepo, $leftoverRepo);

        // Map POST data to Service requirements
        // Legacy form only provides sale_id and amount, so we treat it as a financial compensation (No physical return).
        $refundData = [
            'sale_id'     => $saleId,
            'amount'      => $amount,
            'customer_id' => $_POST['customer_id'] ?? null, // Will be fetched by service if missing
            'refund_type' => $_POST['refund_type'] ?? 'Cash', // Default to Cash for legacy report buttons
            'note'        => $_POST['reason'] ?? 'مرتجع مالي من تقارير المبيعات'
        ];

        // Ensure customer_id is present for Debt refunds
        if ($refundData['refund_type'] === 'Debt' && empty($refundData['customer_id'])) {
            $sale = $saleRepo->getById($saleId);
            $refundData['customer_id'] = $sale['customer_id'] ?? null;
        }

        $service->processRefund($refundData, $_SESSION['user_id']);

        $returnUrl = $_POST['return_url'] ?? "../reports.php";
        $msg = "تمت عملية المرتجع بنجاح بقيمة " . number_format($amount);
        header("Location: $returnUrl" . (strpos($returnUrl, '?') !== false ? "&" : "?") . "success=1&msg=" . urlencode($msg));
        exit;
    } catch (Exception $e) {
        $returnUrl = $_POST['return_url'] ?? "../reports.php";
        $error = urlencode($e->getMessage());
        header("Location: $returnUrl" . (strpos($returnUrl, '?') !== false ? "&" : "?") . "error=$error");
        exit;
    }
}
