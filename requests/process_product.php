<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Helper to handle uploads
    $media_path = null;
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_name = time() . '_' . basename($_FILES['media']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['media']['tmp_name'], $target_file)) {
            $media_path = 'uploads/' . $file_name;
        }
    }

    if ($action === 'add') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);

        $stmt = $pdo->prepare("INSERT INTO qat_types (name, description, media_path) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $media_path]);
    } elseif ($action === 'update') {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);

        if ($media_path) {
            $stmt = $pdo->prepare("UPDATE qat_types SET name = ?, description = ?, media_path = ? WHERE id = ?");
            $stmt->execute([$name, $description, $media_path, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE qat_types SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        // Soft delete instead of physical delete to preserve historical data and avoid FK errors
        $stmt = $pdo->prepare("UPDATE qat_types SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    header("Location: ../manage_products.php?success=1");
    exit;
}
header("Location: ../manage_products.php");
exit;
