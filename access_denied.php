<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: 'Tajawal', sans-serif;
        }

        .error-card {
            max-width: 500px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .icon-box {
            font-size: 80px;
            color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="card error-card p-5 text-center">
        <div class="icon-box mb-4">
            <i class="fa-solid fa-ban"></i> &#128683; <!-- Fallback unicode if fontawesome fails -->
        </div>
        <h2 class="text-danger fw-bold mb-3">عذراً، غير مسموح لك بالدخول</h2>
        <p class="lead mb-4">
            هذه الصفحة مخصصة فقط للمشرف العام.
            <br>
            ليس لديك الصلاحية للوصول إلى هذا القسم.
        </p>
        <div class="d-grid gap-2">
            <a href="sourcing.php" class="btn btn-warning btn-lg fw-bold">العودة إلى التوريد</a>
            <a href="logout.php" class="btn btn-outline-secondary">تسجيل الخروج</a>
        </div>
    </div>
</body>

</html>