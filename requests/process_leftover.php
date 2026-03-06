<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $purchase_id = $_POST['purchase_id'];
        $qat_type_id = $_POST['qat_type_id'];
        $weight_kg = $_POST['weight_kg'];
        $action = $_POST['action']; // 'Drop' or 'SellNextDay'
        $notes = $_POST['notes'] ?? '';

        // Status mapping
        $status = ($action === 'Drop') ? 'Dropped' : 'Transferred_Next_Day';

        // Insert into leftovers
        $sql = "INSERT INTO leftovers (source_date, purchase_id, qat_type_id, weight_kg, status, decision_date, sale_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $source_date = date('Y-m-d');
        $decision_date = date('Y-m-d');
        $sale_date = ($status === 'Transferred_Next_Day') ? date('Y-m-d', strtotime('+1 day')) : $decision_date;

        $stmt->execute([$source_date, $purchase_id, $qat_type_id, $weight_kg, $status, $decision_date, $sale_date]);

        // Use lastInsertId to update the purchase status if needed? 
        // Or leave it. The purchase is just 'consumed' effectively.

        // If Dropped, maybe add to Expenses?
        // User said: "not good anymore... drop them". Usually implies a loss record.
        // I will optionally add to expenses if requested, but for now just tracking in leftovers is enough.

        header("Location: ../leftovers.php?success=1");
        exit;
    } catch (PDOException $e) {
        die("Error processing leftover: " . $e->getMessage());
    }
}
