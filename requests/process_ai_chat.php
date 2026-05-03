<?php
require_once '../config/db.php';
require_once '../config/ai_config.php';
require_once '../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Only allow Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'غير مصرح لك باستخدام هذه الميزة.']);
    exit;
}

// Get the user's prompt
$userMessage = $_POST['message'] ?? '';
if (empty($userMessage)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $userMessage = $data['message'] ?? '';
}

if (empty(trim($userMessage))) {
    echo json_encode(['error' => 'الرجاء كتابة سؤال.']);
    exit;
}

// System Knowledge Prompt (The ERP Manual)
$systemPrompt = "
أنت 'مساعد القادري الذكي'. مهمتك إرشاد المدير لمكان أي معلومة في النظام بأسرع وأدق طريقة ممكنة.
يجب أن تتبع هذه القواعد بصرامة في إجاباتك:
1. المسارات: عند السؤال عن 'كيف أجد' أو 'أين'، أجب بمسار مختصر وواضح باستخدام الرمز '>' مثل: التقارير > المبيعات > (تصفية: أجل).
2. اللهجة: يمنية محترمة، مختصرة جداً، ومباشرة.
3. الدقة: لا تخمن، استخدم الدليل أدناه فقط.

دليل النظام والمسارات:
- مبيعات الآجل: التقارير > المبيعات > (تصفية نوع الدفع: أجل).
- سدادات الديون (الحوالات والنقدي): التقارير > التحصيلات.
- إجمالي ديون العملاء: التقارير > الديون (لعرض الكشف) أو التقارير > الخلاصة (لمعرفة الإجمالي فقط).
- الحوالات المجهولة (غير المربوطة): التقارير > حوالات.
- إضافة مبيع جديد: المبيعات > إضافة مبيع.
- إضافة توريد جديد: التوريد > إضافة توريد.
- استلام مشتريات (وزن): استلام المشتريات > وزن واستلام.
- مصاريف اليوم: التقارير > المصاريف.
- كشوفات واتساب: واتساب > اختيار العميل > إرسال.
- تسجيل حوالة مجهولة جديدة: تحويلات مجهولة > (اتبع خطوات المعالج).
- المرتجعات (إرجاع قات): المرتجعات > اختيار العملية > تنفيذ المرتجع.
- التعويضات (خصم مالي): التعويضات > إضافة تعويض.
- إغلاق الحسابات اليومية: إغلاق اليومية > تنفيذ الإغلاق.

إذا سألك عن شيء غير موجود في هذه القائمة، وجهه بذكاء لأقرب تبويب مناسب.
السؤال: " . $userMessage;

// Prepare payload for Gemini API
$payload = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => $systemPrompt]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.1,
        "maxOutputTokens" => 800,
    ]
];

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . GEMINI_API_KEY;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode(['error' => 'حدث خطأ في الاتصال بخوادم المساعد الذكي.']);
    exit;
}

$responseData = json_decode($response, true);
if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    $aiReply = $responseData['candidates'][0]['content']['parts'][0]['text'];
    
    // Parse Markdown to simple HTML
    $aiReply = htmlspecialchars($aiReply);
    $aiReply = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $aiReply);
    $aiReply = preg_replace('/^- \s*(.+)$/m', '• $1', $aiReply);
    $aiReply = nl2br($aiReply);

    echo json_encode(['reply' => $aiReply]);
} else {
    echo json_encode(['error' => 'لم يتم التعرف على إجابة، أعد المحاولة.']);
}
