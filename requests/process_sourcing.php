<?php
// requests/process_sourcing.php
require '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        die("Unauthorized");
    }

    try {
        $provider_id = $_POST['provider_id'];
        $type_name = trim($_POST['type_name']);
        $source_weight_grams = $_POST['source_weight_grams']; // Always calculated in grams by frontend
        $price_per_kilo = $_POST['price_per_kilo'];
        $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
        $user_id = $_SESSION['user_id'];

        // Automatic Linking: Find or Create the Type
        $stmtType = $pdo->prepare("SELECT id FROM qat_types WHERE name = ? LIMIT 1");
        $stmtType->execute([$type_name]);
        $type = $stmtType->fetch();

        if ($type) {
            $qat_type_id = $type['id'];
        } else {
            // "Adding a new product I don't want it" -> User wants it automatic
            $stmtNewType = $pdo->prepare("INSERT INTO qat_types (name, description) VALUES (?, ?)");
            $stmtNewType->execute([$type_name, 'Auto-created from sourcing']);
            $qat_type_id = $pdo->lastInsertId();
        }

        // Calculate Agreed Price (Total Check sent from field)
        // Weight in Kg = grams / 1000
        $weight_kg = $source_weight_grams / 1000;
        $agreed_price = $weight_kg * $price_per_kilo;

        // Helper to handle uploads
        $media_path = null;
        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $file_name = time() . '_' . basename($_FILES['media']['name']);
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['media']['tmp_name'], $target_file)) {
                $media_path = 'uploads/' . $file_name;
            }
        }

        $sql = "INSERT INTO purchases (
            purchase_date, 
            provider_id, 
            qat_type_id, 
            source_weight_grams, 
            quantity_kg, 
            price_per_kilo, 
            agreed_price, 
            is_received, 
            status,
            media_path,
            created_by
        ) VALUES (
            :pDate, 
            :provider, 
            :type, 
            :sWeight, 
            0, -- quantity_kg (Received Weight) is 0 until received
            :ppk, 
            :agreed, 
            0, -- Not Received Yet
            'Fresh',
            :media,
            :user
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':pDate' => $purchase_date,
            ':provider' => $provider_id,
            ':type' => $qat_type_id,
            ':sWeight' => $source_weight_grams,
            ':ppk' => $price_per_kilo,
            ':agreed' => $agreed_price,
            ':media' => $media_path,
            ':user' => $user_id
        ]);

        header("Location: ../sourcing.php?success=1");
        exit;
    } catch (PDOException $e) {
        $errorMsg = urlencode($e->getMessage());
        header("Location: ../sourcing.php?error=$errorMsg");
        exit;
    }
} else {
    header("Location: ../sourcing.php");
    exit;
}
