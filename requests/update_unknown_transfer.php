<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id             = (int)$_POST['id'];
    $transfer_date  = $_POST['transfer_date'];
    $receipt_number = $_POST['receipt_number'];
    $sender_name    = $_POST['sender_name'];
    $amount         = (float)$_POST['amount'];
    $currency       = $_POST['currency'] ?? 'YER';
    $notes          = $_POST['notes'] ?? '';

    $stmt = $pdo->prepare("UPDATE unknown_transfers SET transfer_date=?, receipt_number=?, sender_name=?, amount=?, currency=?, notes=? WHERE id=?");
    $stmt->execute([$transfer_date, $receipt_number, $sender_name, $amount, $currency, $notes, $id]);

    header("Location: ../unknown_transfers.php?success=1");
}
