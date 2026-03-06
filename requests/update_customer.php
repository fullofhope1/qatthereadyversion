<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../customers.php');
    exit;
}

$id = (int)$_POST['id'];
$name = $_POST['name'];
$phone = $_POST['phone'];
$debt_limit = $_POST['debt_limit'];

// Basic validation
if ($name === '') {
    // Name is required
    header('Location: ../edit_customer.php?id=' . $id . '&error=NameRequired');
    exit;
}

// Convert empty debt_limit to NULL
if ($debt_limit === '' || $debt_limit === 'No Limit') {
    $debt_limit = null;
}

$sql = "UPDATE customers SET name = ?, phone = ?, debt_limit = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$name, $phone, $debt_limit, $id]);

header('Location: ../customers.php');
exit;
