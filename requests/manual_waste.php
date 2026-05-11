<?php
// requests/manual_waste.php
require '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/require_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purchase_id = (int)($_POST['purchase_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $unit_type = $_POST['unit_type'] ?? 'weight';
    $reason = !empty($_POST['reason']) ? $_POST['reason'] : 'Dropped'; 
    $notes = $_POST['notes'] ?? '';

    // FIX: Fix existing records that were saved with empty status due to the previous bug
    $pdo->exec("UPDATE leftovers SET status = 'Staff_Consumption' WHERE (status = '' OR status IS NULL) AND notes LIKE '%تخزينة%'");

    if ($purchase_id <= 0 || $amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'بيانات غير مكتملة']);
        exit;
    }

    try {
        $repo = new PurchaseRepository($pdo);
        $purchase = $repo->getById($purchase_id);
        if (!$purchase) throw new Exception("الشحنة غير موجودة");

        $leftoverRepo = new LeftoverRepository($pdo);
        $today = getOperationalDate();

        $data = [
            'source_date' => $purchase['purchase_date'],
            'purchase_id' => $purchase_id,
            'qat_type_id' => $purchase['qat_type_id'],
            'weight_kg' => ($unit_type === 'weight') ? $amount : 0,
            'unit_type' => $unit_type,
            'quantity_units' => ($unit_type !== 'weight') ? (int)$amount : 0,
            'status' => $reason,
            'decision_date' => $today,
            'sale_date' => $today,
            'created_by' => $_SESSION['user_id'] ?? null,
            'notes' => $notes
        ];

        $leftoverRepo->create($data);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
