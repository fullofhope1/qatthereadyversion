<?php
// requests/add_provider.php
require '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $name  = trim($_POST['name']  ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'اسم الراعي مطلوب']);
        exit;
    }

    // #26: phone required for provider
    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'رقم الهاتف مطلوب للراعي']);
        exit;
    }
    if (!preg_match('/^\d{7,15}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'رقم الهاتف يجب أن يحتوي على أرقام فقط']);
        exit;
    }

    try {
        // Check for existing provider with same name
        $stmt = $pdo->prepare("SELECT id FROM providers WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'هذا الاسم موجود مسبقاً']);
            exit;
        }

        // Check for existing provider with same phone
        $stmt = $pdo->prepare("SELECT id FROM providers WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'رقم الهاتف هذا موجود مسبقاً']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO providers (name, phone, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$name, $phone, $_SESSION['user_id']]);
        $id = $pdo->lastInsertId();

        echo json_encode([
            'success'  => true,
            'provider' => ['id' => $id, 'name' => $name, 'phone' => $phone]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
