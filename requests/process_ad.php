<?php
session_start();
require '../config/db.php';

// Restricted to super_admin or admin (though request says super admins)
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
        $client_name = trim($_POST['client_name']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $image_url = trim($_POST['image_url']);
        $link_url = trim($_POST['link_url']);
        $status = $_POST['status'] ?? 'Active';

        $stmt = $pdo->prepare("INSERT INTO advertisements (client_name, title, description, image_url, link_url, status, media_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$client_name, $title, $description, $image_url, $link_url, $status, $media_path]);
    } elseif ($action === 'update') {
        $id = $_POST['id'];
        $client_name = trim($_POST['client_name']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $image_url = trim($_POST['image_url']);
        $link_url = trim($_POST['link_url']);
        $status = $_POST['status'];

        if ($media_path) {
            $stmt = $pdo->prepare("UPDATE advertisements SET client_name = ?, title = ?, description = ?, image_url = ?, link_url = ?, status = ?, media_path = ? WHERE id = ?");
            $stmt->execute([$client_name, $title, $description, $image_url, $link_url, $status, $media_path, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE advertisements SET client_name = ?, title = ?, description = ?, image_url = ?, link_url = ?, status = ? WHERE id = ?");
            $stmt->execute([$client_name, $title, $description, $image_url, $link_url, $status, $id]);
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM advertisements WHERE id = ?");
        $stmt->execute([$id]);
    }

    header("Location: ../manage_ads.php?success=1");
    exit;
}
header("Location: ../manage_ads.php");
exit;
