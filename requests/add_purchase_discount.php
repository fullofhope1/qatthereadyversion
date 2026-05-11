<?php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/require_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purchase_id = (int)$_POST['purchase_id'];
    $discount_amount = (float)$_POST['discount_amount'];
    $reason = $_POST['discount_reason'] ?? '';

    if ($purchase_id <= 0 || $discount_amount <= 0) {
        header("Location: ../sourcing.php?error=invalid_data");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Get purchase data to verify
        $stmt = $pdo->prepare("SELECT * FROM purchases WHERE id = ?");
        $stmt->execute([$purchase_id]);
        $purchase = $stmt->fetch();

        if (!$purchase) {
            throw new Exception("الشحنة غير موجودة");
        }

        // 2. Update purchase with discount
        // Note: net_cost is already stored. We update discount_amount.
        // If the system logic is net_cost = gross - discount, we should be careful.
        // Based on ReportRepository logic: SUM(net_cost - discount_amount)
        $updateStmt = $pdo->prepare("UPDATE purchases SET discount_amount = discount_amount + ? WHERE id = ?");
        $updateStmt->execute([$discount_amount, $purchase_id]);

        // 3. Log the discount if there's a reason or for history (Optional, but good for transparency)
        // For now, the discount_amount column is our primary record.

        $pdo->commit();
        header("Location: ../sourcing.php?success=1");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = urlencode($e->getMessage());
        header("Location: ../sourcing.php?error=$error");
        exit;
    }
}

header("Location: ../sourcing.php");
exit;
