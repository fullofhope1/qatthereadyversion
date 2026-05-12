<?php
require_once '../config/db.php';
require_once '../includes/require_auth.php';

// Only super_admin with full access can import
if ($_SESSION['role'] !== 'super_admin' || ($_SESSION['sub_role'] ?? 'full') !== 'full') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالقيام بهذا الإجراء.']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['sql_file'])) {
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح.']);
    exit;
}

try {
    $file = $_FILES['sql_file'];

    // Basic validation
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($ext) !== 'sql') {
        throw new Exception("يرجى اختيار ملف بصيغة .sql فقط.");
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("خطأ أثناء رفع الملف (Error: " . $file['error'] . ")");
    }

    $tmp_file = $file['tmp_name'];

    // Import using Pure PHP PDO
    $sqlContent = file_get_contents($tmp_file);
    if ($sqlContent === false) {
        throw new Exception("لا يمكن قراءة الملف المرفوع.");
    }

    try {
        // Enforce emulate prepares so we can run multiple statements in one query if driver supports it
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");

        // 1. Drop all existing tables to avoid "Table already exists" errors
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table` ");
        }

        // 2. Execute the entire dump
        $pdo->exec($sqlContent);

        $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
    } catch (PDOException $e) {
        throw new Exception("فشل في استعادة البيانات. تأكد من أن الملف صالح. تفاصيل الخطأ: " . $e->getMessage());
    }

    echo json_encode(['success' => true, 'message' => 'تم استعادة قاعدة البيانات بنجاح! سيتم إعادة تحميل الصفحة...']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
