<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $category = $_POST['category'];
    $description = $_POST['description'] ?? '';
    $amount = (float)$_POST['amount'];
    $staff_id = !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null;

    $stmt = $pdo->prepare("UPDATE expenses SET category = ?, description = ?, amount = ?, staff_id = ? WHERE id = ?");
    $stmt->execute([$category, $description, $amount, $staff_id, $id]);

    header("Location: ../expenses.php?success=1");
}
