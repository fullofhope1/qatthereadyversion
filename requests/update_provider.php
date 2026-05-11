<?php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/require_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = $_POST['id']    ?? 0;
    $name  = trim($_POST['name']  ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($id) || empty($name)) {
        echo json_encode(['success' => false, 'message' => 'الاسم مطلوب']);
        exit;
    }

    try {
        $repo = new ProviderRepository($pdo);
        $service = new ProviderService($repo);
        $service->updateProvider($id, $name, $phone);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
