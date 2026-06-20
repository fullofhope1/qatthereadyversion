<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/db.php';

$data = json_decode(file_get_contents("php://input"));

if(!isset($data->email) || empty($data->email) || !isset($data->otp) || empty($data->otp)) {
    echo json_encode(["status" => "error", "message" => "البريد الإلكتروني ورمز التحقق مطلوبان"]);
    exit;
}

$email = $data->email;
$otp = $data->otp;

try {
    $stmt = $pdo->prepare("SELECT id, otp_code, otp_expires_at FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $customer = $stmt->fetch();

    if ($customer) {
        if ($customer['otp_code'] == $otp) {
            $expires = strtotime($customer['otp_expires_at']);
            if (time() > $expires) {
                echo json_encode(["status" => "error", "message" => "رمز التحقق منتهي الصلاحية"]);
                exit;
            }

            // OTP valid. Generate token.
            $token = bin2hex(random_bytes(32));
            
            $stmt = $pdo->prepare("UPDATE customers SET api_token = ?, email_verified_at = CURRENT_TIMESTAMP, otp_code = NULL WHERE id = ?");
            $stmt->execute([$token, $customer['id']]);

            echo json_encode([
                "status" => "success", 
                "message" => "تم التحقق بنجاح", 
                "token" => $token,
                "customer_id" => $customer['id']
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "رمز التحقق غير صحيح"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "البريد الإلكتروني غير مسجل"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "حدث خطأ في قاعدة البيانات", "error" => $e->getMessage()]);
}
?>
