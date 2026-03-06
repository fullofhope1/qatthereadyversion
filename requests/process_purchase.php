<?php
// requests/process_purchase.php
require '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        die("Unauthorized");
    }

    try {
        $vendor_name = $_POST['vendor_name'];
        $qat_type_id = $_POST['qat_type_id'];
        $quantity_kg = $_POST['quantity_kg'];

        // Default values for removed fields
        $agreed_price = isset($_POST['agreed_price']) ? $_POST['agreed_price'] : 0;
        $discount = isset($_POST['discount']) ? $_POST['discount'] : 0;

        $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
        $expected = !empty($_POST['expected_quantity_kg']) ? $_POST['expected_quantity_kg'] : 0;
        $user_id = $_SESSION['user_id'];

        $sql = "INSERT INTO purchases (purchase_date, vendor_name, qat_type_id, expected_quantity_kg, quantity_kg, agreed_price, discount, status, created_by) 
                VALUES (:pDate, :vendor, :type, :expected, :qty, :price, :disc, 'Fresh', :user)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':pDate' => $purchase_date,
            ':vendor' => $vendor_name,
            ':type' => $qat_type_id,
            ':expected' => $expected,
            ':qty' => $quantity_kg,
            ':price' => $agreed_price,
            ':disc' => $discount,
            ':user' => $user_id
        ]);

        header("Location: ../purchases.php?success=1");
        exit;
    } catch (PDOException $e) {
        $errorMsg = urlencode($e->getMessage());
        header("Location: ../purchases.php?error=$errorMsg");
        exit;
    }
} else {
    header("Location: ../purchases.php");
    exit;
}
