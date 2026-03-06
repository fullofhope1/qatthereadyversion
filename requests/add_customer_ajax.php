<?php
require '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'] ?? '';

    try {
        // Check for existing customer with same name
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE name = ? AND is_deleted = 0");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'الاسم موجود مسبقاً (This name already exists)']);
            exit;
        }

        // Check for existing customer with same phone (if provided)
        if (!empty($phone)) {
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ? AND is_deleted = 0");
            $stmt->execute([$phone]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'رقم الهاتف موجود مسبقاً (This phone number already exists)']);
                exit;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
        $stmt->execute([$name, $phone]);
        $id = $pdo->lastInsertId();

        echo json_encode(['success' => true, 'id' => $id]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
