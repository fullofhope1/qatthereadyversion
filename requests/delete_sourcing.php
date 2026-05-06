<?php
// requests/delete_sourcing.php
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
        if ($p['is_received']) throw new Exception("Cannot delete a shipment that has already been received.");

        $sql = "DELETE FROM purchases WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        header("Location: ../sourcing.php?success=deleted");
        exit;
    } catch (Exception $e) {
        $errorMsg = urlencode($e->getMessage());
        header("Location: ../sourcing.php?error=$errorMsg");
        exit;
    }
}
header("Location: ../sourcing.php");
exit;
