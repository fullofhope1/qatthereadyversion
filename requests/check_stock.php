<?php
// requests/check_stock.php — Real-time inventory check (AJAX)
require_once '../config/db.php';
require_once '../includes/Autoloader.php';
header('Content-Type: application/json');

$purchaseId = (int)($_GET['purchase_id'] ?? 0);
if (!$purchaseId) {
    echo json_encode(['ok' => true]);
    exit;
}

$purchaseRepo = new PurchaseRepository($pdo);
$saleRepo     = new SaleRepository($pdo);

// Determine unit type
$purchase = $purchaseRepo->getById($purchaseId);
if (!$purchase) {
    echo json_encode(['ok' => true]);
    exit;
}

if (isset($_GET['grams'])) {
    // Weight-based check
    $requestedGrams = (float)$_GET['grams'];
    $requestedKg    = $requestedGrams / 1000;

    $totalKg   = (float)$purchaseRepo->getStockQuantity($purchaseId, true);
    $soldKg    = (float)$saleRepo->getSoldKgByPurchaseId($purchaseId);
    $available = round($totalKg - $soldKg, 3);

    if ($requestedKg > $available) {
        echo json_encode([
            'ok'           => false,
            'available_kg' => $available,
            'requested_kg' => round($requestedKg, 3),
        ]);
    } else {
        echo json_encode(['ok' => true, 'available_kg' => $available]);
    }
} elseif (isset($_GET['units'])) {
    // Unit-based check
    $requestedUnits = (int)$_GET['units'];

    $totalUnits = (int)($purchase['received_units'] ?? 0);
    $soldUnits  = (int)$saleRepo->getSoldUnitsByPurchaseId($purchaseId);
    $available  = $totalUnits - $soldUnits;

    if ($requestedUnits > $available) {
        echo json_encode([
            'ok'              => false,
            'available_units' => $available,
            'requested_units' => $requestedUnits,
        ]);
    } else {
        echo json_encode(['ok' => true, 'available_units' => $available]);
    }
} else {
    echo json_encode(['ok' => true]);
}
