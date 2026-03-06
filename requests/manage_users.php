<?php
require '../config/db.php';
// Session is already started by db.php or auth.php - only start if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Ensure only super admin (full) can manage users
$sub_role = $_SESSION['sub_role'] ?? 'full';
// Debug logging
$log_data = date('Y-m-d H:i:s') . " | Action: " . ($_GET['action'] ?? $_POST['action'] ?? 'none') . " | Session: " . json_encode($_SESSION) . "\n";
file_put_contents(__DIR__ . '/debug_log.txt', $log_data, FILE_APPEND);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin' || $sub_role !== 'full') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access Denied']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Role Mapper Helper
function extractRoleVars($role_group)
{
    if ($role_group === 'super_admin_full') return ['role' => 'super_admin', 'sub_role' => 'full'];
    if ($role_group === 'super_admin_verifier') return ['role' => 'super_admin', 'sub_role' => 'verifier'];
    if ($role_group === 'super_admin_seller') return ['role' => 'super_admin', 'sub_role' => 'seller'];
    if ($role_group === 'super_admin_accountant') return ['role' => 'super_admin', 'sub_role' => 'accountant'];
    if ($role_group === 'super_admin_partner') return ['role' => 'super_admin', 'sub_role' => 'partner'];
    if ($role_group === 'admin_full') return ['role' => 'admin', 'sub_role' => 'full'];
    return ['role' => 'user', 'sub_role' => 'full']; // Fallback
}

try {
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT id, username, display_name, phone, role, sub_role, created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $json = json_encode(['success' => true, 'data' => $users]);
        if ($json === false) {
            $err = json_last_error_msg();
            file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . " | JSON Error: " . $err . "\n", FILE_APPEND);
            echo json_encode(['success' => false, 'error' => 'JSON Error: ' . $err]);
        } else {
            echo $json;
        }
        exit;
    }

    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $display_name = trim($_POST['display_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role_group = trim($_POST['role_group'] ?? '');

        if (!$username || !$password || !$role_group) {
            throw new Exception("Missing required fields.");
        }

        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception("اسم المستخدم مسجل مسبقاً.");
        }

        $roles = extractRoleVars($role_group);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, display_name, phone, password, role, sub_role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $display_name, $phone, $hashed_password, $roles['role'], $roles['sub_role']]);

        echo json_encode(['success' => true, 'message' => 'تم إضافة المستخدم بنجاح.']);
        exit;
    }

    if ($action === 'edit') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $display_name = trim($_POST['display_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role_group = trim($_POST['role_group'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$user_id || !$role_group) {
            throw new Exception("Missing required fields.");
        }

        // Check current role of the target user
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$target_user) {
            throw new Exception("المستخدم غير موجود.");
        }

        if ($target_user['role'] === 'admin') {
            throw new Exception("لا يمكنك تعديل صلاحيات حساب المستلم الأساسي (admin).");
        }

        // Cannot change own role/sub_role here to prevent locking themselves out
        if ($user_id == $_SESSION['user_id']) {
            throw new Exception("لا يمكنك تعديل صلاحياتك الشخصية من هذه الشاشة. استخدم إعدادات الحساب.");
        }

        $roles = extractRoleVars($role_group);

        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET display_name = ?, phone = ?, role = ?, sub_role = ?, password = ? WHERE id = ?");
            $stmt->execute([$display_name, $phone, $roles['role'], $roles['sub_role'], $hashed_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET display_name = ?, phone = ?, role = ?, sub_role = ? WHERE id = ?");
            $stmt->execute([$display_name, $phone, $roles['role'], $roles['sub_role'], $user_id]);
        }

        echo json_encode(['success' => true, 'message' => 'تم تحديث بيانات المستخدم.']);
        exit;
    }

    if ($action === 'delete') {
        $user_id = (int)($_POST['user_id'] ?? 0);

        if (!$user_id) throw new Exception("Invalid user ID.");
        if ($user_id == $_SESSION['user_id']) throw new Exception("لا يمكنك حذف حسابك الشخصي.");

        // Check current role of the target user
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($target_user && $target_user['role'] === 'admin') {
            throw new Exception("لا يمكنك حذف حساب المستلم الأساسي (admin).");
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        echo json_encode(['success' => true, 'message' => 'تم حذف المستخدم.']);
        exit;
    }

    throw new Exception("Invalid action.");
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
