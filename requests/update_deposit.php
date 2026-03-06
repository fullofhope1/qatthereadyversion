<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $recipient = $_POST['recipient'];
    $currency = $_POST['currency'];
    $amount = (float)$_POST['amount'];
    $notes = $_POST['notes'] ?? '';

    $stmt = $pdo->prepare("UPDATE qat_deposits SET recipient = ?, currency = ?, amount = ?, notes = ? WHERE id = ?");
    $stmt->execute([$recipient, $currency, $amount, $notes, $id]);

    header("Location: ../expenses.php?tab=deposit");
}
