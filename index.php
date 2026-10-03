<?php
// حذف هذا الملف أو إعادة تسميته عند رفع الموقع
header('HTTP/1.1 503 Service Unavailable');
header('Retry-After: 86400');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحت الصيانة | القادري</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .container {
            text-align: center;
            padding: 40px 20px;
            max-width: 600px;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #e2b96f;
        }
        p {
            font-size: 1.1rem;
            color: #ccc;
            line-height: 1.8;
            margin-bottom: 10px;
        }
        .badge {
            display: inline-block;
            background: rgba(226, 185, 111, 0.2);
            border: 1px solid #e2b96f;
            color: #e2b96f;
            padding: 8px 20px;
            border-radius: 20px;
            margin-top: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔧</div>
        <h1>الموقع تحت الصيانة</h1>
        <p>نعمل على تحسين الموقع وسيعود قريباً.</p>
        <p>We're working on improvements and will be back soon.</p>
        <div class="badge">⏳ نعود قريباً • Coming Soon</div>
    </div>
</body>
</html>
