<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = $_POST['id']    ?? 0;
    $name  = trim($_POST['name']  ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($id) || empty($name) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
        exit;
    }

    try {
        // Check for existing provider with same name (excluding current ID)
        $stmt = $pdo->prepare("SELECT id FROM providers WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'هذا الاسم موجود مسبقاً لدى مورد آخر']);
            exit;
        }

        // Check for existing provider with same phone (excluding current ID)
        $stmt = $pdo->prepare("SELECT id FROM providers WHERE phone = ? AND id != ?");
        $stmt->execute([$phone, $id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'رقم الهاتف هذا موجود مسبقاً لدى مورد آخر']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE providers SET name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
