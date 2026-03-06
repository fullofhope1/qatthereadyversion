<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    if (!empty($id)) {
        try {
            // Delete customer - Assuming foreign keys might restrict this, but user asked for deletion.
            // If sales exist, this might fail or error depending on constraints.
            // For now, we attempt to delete the customer directly. 
            // Better approach: Check if sales exist, if so warning, but user wants "delete from page".

            // Soft Delete the customer
            $stmt = $pdo->prepare("UPDATE customers SET is_deleted = 1 WHERE id = ?");
            $stmt->execute([$id]);

            header("Location: ../customers.php?deleted=1");
        } catch (PDOException $e) {
            // If deletion fails (e.g. constraints), redirect with error
            $error = urlencode("Could not delete customer. They might have existing sales records. Error: " . $e->getMessage());
            header("Location: ../customers.php?error=$error");
        }
    } else {
        header("Location: ../customers.php");
    }
} else {
    header("Location: ../customers.php");
}
