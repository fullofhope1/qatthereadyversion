<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receipt_number = $_POST['receipt_number'] ?? '';
    $sender_name = $_POST['sender_name'] ?? '';
    $transfer_date = $_POST['transfer_date'] ?? date('Y-m-d');
    $amount = $_POST['amount'] ?? 0;
    $notes = $_POST['notes'] ?? '';

    if (!$sender_name) {
        die("Error: Sender Name is required.");
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO unknown_transfers (transfer_date, receipt_number, sender_name, amount, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$transfer_date, $receipt_number, $sender_name, $amount, $notes]);

        header("Location: ../unknown_transfers.php?success=1");
        exit;
    } catch (PDOException $e) {
        die("Error saving transfer: " . $e->getMessage());
    }
}
