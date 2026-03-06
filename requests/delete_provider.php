<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'ID مطلوب']);
    exit;
}

try {
    // Check if provider has any purchases
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE provider_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'لا يمكن حذف الراعي لوجود شحنات مسجلة باسمه. يمكنك تعديل الاسم بدلاً من الحذف.']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM providers WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
