<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/db.php';

$headers = getallheaders();
if(!isset($headers['Authorization'])) {
    echo json_encode(["status" => "error", "message" => "غير مصرح لك (Missing Token)"]);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);

try {
    // Validate token and get customer
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE api_token = ?");
    $stmt->execute([$token]);
    $customer = $stmt->fetch();

    if (!$customer) {
        echo json_encode(["status" => "error", "message" => "رمز المصادقة غير صالح"]);
        exit;
    }

    $customer_id = $customer['id'];

    $data = json_decode(file_get_contents("php://input"));
    
    if(!isset($data->qat_type_id) || !isset($data->price)) {
        echo json_encode(["status" => "error", "message" => "بيانات الطلب غير مكتملة"]);
        exit;
    }

    $qat_type_id = $data->qat_type_id;
    $weight_grams = isset($data->weight_grams) ? $data->weight_grams : 0;
    $quantity_units = isset($data->quantity_units) ? $data->quantity_units : 0;
    $unit_type = isset($data->unit_type) ? $data->unit_type : 'weight';
    $price = $data->price;
    $notes = isset($data->notes) ? $data->notes : '';

    // Append mobile order tag for notifications/admin filtering
    $notes = "طلب عبر تطبيق الجوال - " . $notes;

    // Insert order into sales table as unpaid
    $stmt = $pdo->prepare("INSERT INTO sales (sale_date, customer_id, qat_type_id, qat_status, weight_grams, unit_type, quantity_units, price, is_paid, payment_method, notes) VALUES (CURDATE(), ?, ?, 'Tari', ?, ?, ?, ?, 0, 'Cash', ?)");
    
    $stmt->execute([
        $customer_id, 
        $qat_type_id, 
        $weight_grams, 
        $unit_type, 
        $quantity_units, 
        $price, 
        $notes
    ]);

    $order_id = $pdo->lastInsertId();

    // Trigger Notification Logic Here (FCM)
    // TODO: Connect to Firebase Cloud Messaging to send notification to admin app or web dashboard
    $notification_sent = true;

    echo json_encode([
        "status" => "success", 
        "message" => "تم تسجيل طلبك بنجاح",
        "order_id" => $order_id
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error", "error" => $e->getMessage()]);
}
?>
