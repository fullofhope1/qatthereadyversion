<?php
// requests/update_staff.php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];
        $salary = $_POST['daily_salary'];
        $limit = !empty($_POST['withdrawal_limit']) ? $_POST['withdrawal_limit'] : null;

        $sql = "UPDATE staff SET daily_salary = ?, withdrawal_limit = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$salary, $limit, $id]);

        header("Location: ../staff_details.php?id=$id&success=1");
    } catch (PDOException $e) {
        $error = urlencode($e->getMessage());
        header("Location: ../staff_details.php?id=$id&error=$error");
    }
}
