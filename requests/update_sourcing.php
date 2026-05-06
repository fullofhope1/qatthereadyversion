<?php
// requests/update_sourcing.php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'super_admin') {
        die("Unauthorized");
    }

    try {
        $id = (int)$_POST['purchase_id'];
        $purchaseRepo = new PurchaseRepository($pdo);
        
        $p = $purchaseRepo->getById($id);
        if (!$p) throw new Exception("Shipment not found.");
        if ($p['is_received']) throw new Exception("Cannot edit a shipment that has already been received.");

        $data = [
            'provider_id' => (int)$_POST['provider_id'],
            'qat_type_id' => (int)$_POST['qat_type_id']
        ];

        if ($p['unit_type'] === 'weight') {
            $data['source_weight_grams'] = (float)$_POST['source_weight_grams'];
            $data['quantity_kg'] = $data['source_weight_grams'] / 1000;
            $data['price_per_kilo'] = (float)$_POST['price_per_kilo'];
            $data['agreed_price'] = $data['quantity_kg'] * $data['price_per_kilo'];
        } else {
            $data['source_units'] = (int)$_POST['source_units'];
            $data['received_units'] = $data['source_units'];
            $data['price_per_unit'] = (float)$_POST['price_per_unit'];
            $data['agreed_price'] = $data['source_units'] * $data['price_per_unit'];
        }
        
        $data['net_cost'] = $data['agreed_price'];

        $purchaseRepo->update($id, $data);

        header("Location: ../sourcing.php?success=updated");
        exit;
    } catch (Exception $e) {
        $errorMsg = urlencode($e->getMessage());
        header("Location: ../sourcing.php?error=$errorMsg");
        exit;
    }
}
header("Location: ../sourcing.php");
exit;
