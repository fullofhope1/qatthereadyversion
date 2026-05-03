<?php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commRepo = new CommunicationRepository($pdo);
    $service = new CommunicationService($commRepo);

    $data = [
        'transfer_date'  => $_POST['transfer_date'],
        'amount'         => $_POST['amount'],
        'currency'       => $_POST['currency'] ?? 'YER',
        'receipt_number' => $_POST['receipt_number'],
        'sender_name'    => $_POST['sender_name'],
        'receiver_name'  => $_POST['receiver_name'],
        'notes'          => $_POST['notes'] ?? ''
    ];

    if ($service->processUnknownTransfer('add', $data)) {
        header("Location: ../unknown_transfers.php?success=1");
        exit;
    }
}
header("Location: ../unknown_transfers.php");
exit;
