<?php
require 'config/db.php';

if (!isset($_GET['id'])) {
    header('Location: customers.php');
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) {
    header('Location: customers.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>تعديل العميل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    <div class="container py-4">
        <h2 class="mb-4">تعديل بيانات العميل</h2>
        <form action="requests/update_customer.php" method="POST">
            <input type="hidden" name="id" value="<?= $customer['id'] ?>">
            <div class="mb-3">
                <label class="form-label">الاسم</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($customer['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">الجوال</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label text-danger fw-bold">سقف الدين</label>
                <input type="number" name="debt_limit" class="form-control" value="<?= $customer['debt_limit'] ?>" placeholder="بدون سقف">
                <div class="form-text">الحد الأقصى المسموح به للديون (اتركه فارغاً = بدون سقف)</div>
            </div>
            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
            <a href="customers.php" class="btn btn-secondary ms-2">إلغاء</a>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>