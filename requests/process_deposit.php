<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['deposit_date'] ?? date('Y-m-d');
    $currency = $_POST['currency'] ?? 'YER';
    $amount = $_POST['amount'] ?? 0;
    $recipient = $_POST['recipient'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if ($amount <= 0 || empty($recipient)) {
        header("Location: ../expenses.php?error=يرجى إدخال المبلغ والجهة المستلمة");
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO qat_deposits (deposit_date, currency, amount, recipient, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$date, $currency, $amount, $recipient, $notes, $_SESSION['user_id']]);
        header("Location: ../expenses.php?success=1");
    } catch (PDOException $e) {
        header("Location: ../expenses.php?error=" . urlencode($e->getMessage()));
    }
}
