<?php
// requests/check_stock.php — Real-time inventory check (AJAX)
// Supports both purchase_id (Fresh stock) and leftover_id (Momsi/Leftover stock)
require_once '../config/db.php';
require_once '../includes/Autoloader.php';
header('Content-Type: application/json');

$purchaseId = (int)($_GET['purchase_id'] ?? 0);
$leftoverId = (int)($_GET['leftover_id'] ?? 0);

// Must have at least one source
if (!$purchaseId && !$leftoverId) {
    echo json_encode(['ok' => true]);
    exit;
}

$saleRepo = new SaleRepository($pdo);

// ──────────────────────────────────────────────
// PATH A: Purchase-based stock (Fresh)
// ──────────────────────────────────────────────
if ($purchaseId) {
    $purchaseRepo = new PurchaseRepository($pdo);
    $purchase = $purchaseRepo->getById($purchaseId);
    if (!$purchase) {
        echo json_encode(['ok' => true]);
        exit;
    }

    if (isset($_GET['grams'])) {
        $requestedKg = (float)$_GET['grams'] / 1000;
        $totalKg     = (float)$purchaseRepo->getStockQuantity($purchaseId, true);
        $soldKg      = (float)$saleRepo->getSoldKgByPurchaseId($purchaseId);
        $available   = round($totalKg - $soldKg, 3);

        if ($requestedKg > $available) {
            echo json_encode(['ok' => false, 'available_kg' => $available, 'requested_kg' => round($requestedKg, 3)]);
        } else {
            echo json_encode(['ok' => true, 'available_kg' => $available]);
        }

    } elseif (isset($_GET['units'])) {
        $requestedUnits = (int)$_GET['units'];
        $totalUnits     = (int)($purchase['received_units'] ?? 0);
        $soldUnits      = (int)$saleRepo->getSoldUnitsByPurchaseId($purchaseId);
        $available      = $totalUnits - $soldUnits;

        if ($requestedUnits > $available) {
            echo json_encode(['ok' => false, 'available_units' => $available, 'requested_units' => $requestedUnits]);
        } else {
            echo json_encode(['ok' => true, 'available_units' => $available]);
        }

    } else {
        echo json_encode(['ok' => true]);
    }
    exit;
}

// ──────────────────────────────────────────────
// PATH B: Leftover-based stock (Momsi_Day_1 / Momsi_Day_2)
// ──────────────────────────────────────────────
if ($leftoverId) {
    $leftoverRepo = new LeftoverRepository($pdo);
    $leftover = $leftoverRepo->getById($leftoverId);
    if (!$leftover) {
        echo json_encode(['ok' => true]);
        exit;
    }

    if (isset($_GET['grams'])) {
        $requestedKg = (float)$_GET['grams'] / 1000;
        $totalKg     = (float)$leftoverRepo->getWeight($leftoverId, true);
        $soldKg      = (float)$saleRepo->getSoldKgByLeftoverId($leftoverId);
        $available   = round($totalKg - $soldKg, 3);

        if ($requestedKg > $available) {
            echo json_encode(['ok' => false, 'available_kg' => $available, 'requested_kg' => round($requestedKg, 3)]);
        } else {
            echo json_encode(['ok' => true, 'available_kg' => $available]);
        }

    } elseif (isset($_GET['units'])) {
        $requestedUnits = (int)$_GET['units'];
        $totalUnits     = (int)($leftoverRepo->getUnits($leftoverId, true));
        $soldUnits      = (int)$saleRepo->getSoldUnitsByLeftoverId($leftoverId);
        $available      = $totalUnits - $soldUnits;

        if ($requestedUnits > $available) {
            echo json_encode(['ok' => false, 'available_units' => $available, 'requested_units' => $requestedUnits]);
        } else {
            echo json_encode(['ok' => true, 'available_units' => $available]);
        }

    } else {
        echo json_encode(['ok' => true]);
    }
    exit;
}
