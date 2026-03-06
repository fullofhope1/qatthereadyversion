<?php
// requests/add_staff.php
require '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        die("Unauthorized");
    }

    $name = $_POST['name'];
    $role = $_POST['role'] ?? 'Employee';
    $salary = $_POST['daily_salary'] ?? 0;
    $limit = !empty($_POST['withdrawal_limit']) ? $_POST['withdrawal_limit'] : null;
    $user_id = $_SESSION['user_id'];

    $sql = "INSERT INTO staff (name, role, daily_salary, withdrawal_limit, created_by) VALUES (?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([$name, $role, $salary, $limit, $user_id]);

    header("Location: ../staff.php?success=1");
}
