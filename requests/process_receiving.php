<?php
// requests/process_receiving.php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $purchase_id = $_POST['purchase_id'];
        $received_weight_grams = $_POST['received_weight_grams']; // Calculated from frontend

        // Final Quantity in KG for sales inventory logic
        $quantity_kg = $received_weight_grams / 1000;

        $sql = "UPDATE purchases SET 
                received_weight_grams = :rWeight,
                quantity_kg = :qtyKg,
                is_received = 1,
                received_at = NOW(),
                purchase_date = CURRENT_DATE
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':rWeight' => $received_weight_grams,
            ':qtyKg' => $quantity_kg,
            ':id' => $purchase_id
        ]);

        // NEW: Sync photo to product display
        // Fetch the shipment details to get qat_type_id and media_path
        $stmtFetch = $pdo->prepare("SELECT qat_type_id, media_path FROM purchases WHERE id = ?");
        $stmtFetch->execute([$purchase_id]);
        $shipment = $stmtFetch->fetch();

        if ($shipment && $shipment['media_path']) {
            $updateProduct = $pdo->prepare("UPDATE qat_types SET media_path = ? WHERE id = ?");
            $updateProduct->execute([$shipment['media_path'], $shipment['qat_type_id']]);
        }

        header("Location: ../purchases.php?success=received");
        exit;
    } catch (PDOException $e) {
        $errorMsg = urlencode($e->getMessage());
        header("Location: ../purchases.php?error=$errorMsg");
        exit;
    }
} else {
    header("Location: ../purchases.php");
    exit;
}
