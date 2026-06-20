<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/db.php';

$data = json_decode(file_get_contents("php://input"));

if(!isset($data->email) || empty($data->email)) {
    echo json_encode(["status" => "error", "message" => "البريد الإلكتروني مطلوب"]);
    exit;
}

$email = $data->email;
$name = isset($data->name) ? $data->name : 'زبون جديد';

$otp = rand(100000, 999999);
$expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

try {
    // Check if customer exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $customer = $stmt->fetch();

    if ($customer) {
        // Update OTP for existing customer
        $stmt = $pdo->prepare("UPDATE customers SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
        $stmt->execute([$otp, $expires_at, $customer['id']]);
    } else {
        // Insert new customer
        $stmt = $pdo->prepare("INSERT INTO customers (name, email, otp_code, otp_expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $otp, $expires_at]);
    }

    // Send email (using basic mail function, in production use SMTP)
    $subject = "كود التحقق الخاص بك - القات";
    $message = "رمز التحقق الخاص بك هو: $otp\nهذا الرمز صالح لمدة 10 دقائق.";
    $headers = "From: no-reply@alqaadri.gt.tc";
    
    // For local testing, we return the OTP in the response
    // Remove this in production
    $is_localhost = (php_sapi_name() === 'cli') || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
    
    if (!$is_localhost) {
        mail($email, $subject, $message, $headers);
    }

    $response = ["status" => "success", "message" => "تم إرسال رمز التحقق إلى بريدك الإلكتروني بنجاح"];
    if ($is_localhost) {
        $response["debug_otp"] = $otp; // ONLY FOR LOCAL TESTING
    }
    
    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "حدث خطأ في قاعدة البيانات", "error" => $e->getMessage()]);
}
?>
