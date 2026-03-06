<?php
require_once '../config/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        header("Location: ../expenses.php?success=deleted");
    } catch (PDOException $e) {
        header("Location: ../expenses.php?error=" . urlencode($e->getMessage()));
    }
    exit;
}
header("Location: ../expenses.php");
exit;
