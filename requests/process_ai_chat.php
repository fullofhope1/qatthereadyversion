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
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? '';

if (empty(trim($userMessage))) {
    echo json_encode(['error' => 'الرجاء كتابة سؤال.']);
    exit;
}

// System Knowledge Prompt (The ERP Manual)
$systemPrompt = "
أنت المساعد الذكي الخاص بنظام (القادري وماجد لبيع القات). أنت خبير بنظام الـ ERP هذا.
مهمتك الوحيدة هي تعليم مالك النظام (المدير) كيف يستخدم نظامه، وأين يجد الوظائف المختلفة، وكيف يقوم بالعمليات.
لا تخترع بيانات مالية، ولا تجيب عن أسئلة عامة خارج إطار النظام المحاسبي للقات. جاوبه بلهجة يمنية محترمة وواضحة، واجعل إجاباتك مختصرة المباشرة وعلى شكل نقاط مرقمة إن أمكن.

إليك دليل النظام الكامل:

1. **دورة المشتريات والتوريد:**
   - (تبويبة التوريد sourcing.php): تُستخدم لتسجيل التوريد المبدئي من الموردين (الرعية) وتحديد التكلفة التقديرية قبل وصول القات.
   - (تبويبة المشتريات purchases.php): تُستخدم للوزن الفعلي الخالص واستلام القات رسمياً (كمية كجم أو حبات) ليصبح في (المخزون الطازج Fresh). 

2. **دورة المبيعات والمرتجعات:**
   - (تبويبة المبيعات sales.php): لبيع القات (كاش، آجل/دين، أو تحويل). يجب اختيار وزن وسعر الزبون (زبون يومي أو دائم).
   - (تبويبة المرتجعات returns.php): تُستخدم لإرجاع قات بشكل (عيني). إذا أعاد الزبون القات للثلاجة، نستخدم هذه الصفحة؛ النظام تلقائياً يعيد القيمة لحسابه أو نقداً، ويرجع الكمية للمخزون.
   - (تبويبة التعويضات refunds.php): تُستخدم لإرضاء الزبون وتعويضه (مادياً فقط بدون إرجاع القات)، إما بإعطائه كاش من الصندوق أو خصم من ديونه.

3. **الديون والعملاء:**
   - (العملاء customers.php): لإضافة حسابات زبائن دائمين لتسهيل بيع الآجل لهم.
   - (الديون debts.php): لسداد ديون زبون قام بدعم دفعات نقدية (السداد).

4. **البقايا (بيع أول وبيع ثاني):**
   - عندما لا يُباع القات كطازج، يمكن نقله إلى (بيع أول sales_leftovers_1.php) لخفض سعره، ثم إن لم يُبع يتم نقله إلى (بيع ثاني sales_leftovers_2.php)، وأخيراً يُسجل كـ (تالف) إذا رمي.

5. **المصاريف والرواتب:**
   - (المصاريف expenses.php): لتسجيل الإيجارات وتكاليف التشغيل وحتى السلف للمشرفين.
   - (الموظفين staff.php): لإضافة عمال وتحديد رواتبهم الأساسية.

6. **التقارير وصافي الصندوق:**
   - (التقارير reports.php): فيها 15 تبويبة مالية دقيقة لكل ما يخص المحل. وتشمل (خلاصة الصندوق والنقدية المتوفرة حالياً)، مبيعات اليوم، مشتريات اليوم، والتنقيط حسب التاريخ.

7. **إغلاق اليومية:**
   - يجب الدخول على (إغلاق اليومية closing.php) كل منتصف ليل أو نهاية يوم لإقفال حسابات اليوم وتصفير النقدية لليوم التالي.

8. **نظام الواتساب:**
   - يمكن إرسال كشوفات حساب للمديونات والعملاء عن طريق (تبويبة واتساب whatsapp_statements.php).

استخدم هذا الدليل فقط للإجابة على سؤاله: \n\nالسؤال: " . $userMessage;

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
        "temperature" => 0.2, // Low temperature for factual instruction-based answers
        "maxOutputTokens" => 1024,
    ]
];

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . GEMINI_API_KEY;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
// Fix SSL issues on Windows XAMPP and some hostings
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
if(curl_errno($ch)){
    $error_msg = curl_error($ch);
}
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    $errDetail = isset($error_msg) ? $error_msg : "HTTP Code: $httpCode";
    echo json_encode(['error' => 'حدث خطأ في الاتصال بخوادم جوجل. التقرير الفني: ' . $errDetail]);
    exit;
}

$responseData = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'الرد من السيرفر غير صالح (ليس JSON).']);
    exit;
}

if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    $aiReply = $responseData['candidates'][0]['content']['parts'][0]['text'];
    
    // Parse Markdown to simple HTML (convert **text** to <strong>text</strong>, \n to <br>)
    $aiReply = htmlspecialchars($aiReply);
    $aiReply = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $aiReply);
    $aiReply = preg_replace('/\*([^\*]+?)\*/s', '<em>$1</em>', $aiReply);
    $aiReply = preg_replace('/^#\s+(.+)$/m', '<h3>$1</h3>', $aiReply);
    $aiReply = preg_replace('/^##\s+(.+)$/m', '<h4>$1</h4>', $aiReply);
    $aiReply = preg_replace('/^- \s*(.+)$/m', '<li>$1</li>', $aiReply);
    $aiReply = nl2br($aiReply);

    echo json_encode(['reply' => $aiReply]);
} else {
    echo json_encode(['error' => 'لم يتم التعرف على إجابة من جوجل.']);
}
