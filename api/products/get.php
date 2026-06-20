<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/db.php';

try {
    $stmt = $pdo->prepare("SELECT id, name, unit_type, description, media_path, price_weight, price_qabdah, price_qartas FROM qat_types WHERE is_deleted = 0");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $products
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error", "error" => $e->getMessage()]);
}
?>
