<?php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';

header('Content-Type: application/json');

$leftover_id = $_POST['leftover_id'] ?? null;
$reason = $_POST['reason'] ?? 'Manual Drop';

if (!$leftover_id) {
    echo json_encode(['success' => false, 'error' => 'Missing ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Get leftover details to reduce inventory or mark as dropped
    $stmt = $pdo->prepare("SELECT * FROM leftovers WHERE id = ?");
    $stmt->execute([$leftover_id]);
    $leftover = $stmt->fetch();

    if (!$leftover) {
        throw new Exception("Leftover not found");
    }

    // 2. Mark as Dropped (Trash)
    // We reuse the existing status logic.
    $update = $pdo->prepare("UPDATE leftovers SET status = 'Manual_Dropped', notes = CONCAT(IFNULL(notes,''), ' - Transferred to Trash: ', ?) WHERE id = ?");
    $update->execute([$reason, $leftover_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
