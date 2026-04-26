</div> <!-- End Container -->

<!-- Floating Help Button -->
<?php if (isset($_SESSION['user_id'])): ?>
<a href="javascript:void(0)" id="help_trigger" onclick="if(typeof startSiteTour === 'function') startSiteTour(); else alert('جاري تحميل نظام المساعدة... يرجى المحاولة بعد قليل');" title="دليل الاستخدام">
    <i class="fas fa-question"></i>
</a>
<?php endif; ?>

<!-- Floating AI Chatbot Button (Super Admin Only) -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): 
    require_once 'config/ai_config.php';
    $gemini_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
?>
<a href="javascript:void(0)" id="ai_trigger" onclick="toggleAiChat()" title="المساعد الذكي" style="position:fixed; bottom:25px; left:25px; width:55px; height:55px; background:#1a1a1a; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5rem; box-shadow:0 4px 15px rgba(0,0,0,0.3); cursor:pointer; z-index:9999; border:2px solid #ffd700; transition:all 0.3s ease;">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 640 512"><path d="M320 0c17.7 0 32 14.3 32 32V96H472c39.8 0 72 32.2 72 72V440c0 39.8-32.2 72-72 72H168c-39.8 0-72-32.2-72-72V168c0-39.8 32.2-72 72-72H288V32c0-17.7 14.3-32 32-32zM208 384c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16-7.2 16-16s-7.2-16-16-16H208zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16-7.2 16-16s-7.2-16-16-16H304zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16-7.2 16-16s-7.2-16-16-16H400zM264 256a40 40 0 1 0 -80 0 40 40 0 1 0 80 0zm152 40a40 40 0 1 0 0-80 40 40 0 1 0 0 80zM48 224H64V416H48c-26.5 0-48-21.5-48-48V272c0-26.5 21.5-48 48-48zm544 0c26.5 0 48 21.5 48 48v96c0 26.5-21.5 48-48 48H576V224h16z"/></svg>
</a>

<!-- AI Chat Box -->
<div id="ai_chat_container" style="display:none; position:fixed; bottom:90px; left:25px; width:350px; height:450px; background:white; border-radius:15px; box-shadow:0 10px 25px rgba(0,0,0,0.2); z-index:9999; flex-direction:column; overflow:hidden;">
    <div style="background:var(--brand-gradient); color:#1a1a1a; padding:15px; font-weight:900; display:flex; justify-content:space-between; align-items:center;">
        <span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 640 512" class="me-2"><path d="M320 0c17.7 0 32 14.3 32 32V96H472c39.8 0 72 32.2 72 72V440c0 39.8-32.2 72-72 72H168c-39.8 0-72-32.2-72-72V168c0-39.8 32.2-72 72-72H288V32c0-17.7 14.3-32 32-32zM208 384c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16-7.2 16-16s-7.2-16-16-16H208zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16-7.2 16-16s-7.2-16-16-16H304zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16-7.2 16-16s-7.2-16-16-16H400zM264 256a40 40 0 1 0 -80 0 40 40 0 1 0 80 0zm152 40a40 40 0 1 0 0-80 40 40 0 1 0 0 80zM48 224H64V416H48c-26.5 0-48-21.5-48-48V272c0-26.5 21.5-48 48-48zm544 0c26.5 0 48 21.5 48 48v96c0 26.5-21.5 48-48 48H576V224h16z"/></svg> مساعد القادري الذكي</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 384 512" style="cursor:pointer;" onclick="toggleAiChat()"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
    </div>
    <div id="ai_chat_history" style="flex:1; padding:15px; overflow-y:auto; font-size:0.95rem; background:#f8f9fa;">
        <div style="margin-bottom:10px; text-align:right;">
            <span style="background:#e9ecef; color:#000; padding:8px 12px; border-radius:15px; display:inline-block; border-bottom-right-radius:0;">
                أهلاً بك مديرنا الغالي! أنا المساعد الذكي لنظام القات. اسألني عن أي جزء في النظام وسأشرح لك كيف يعمل.
            </span>
        </div>
    </div>
    <div style="padding:10px; border-top:1px solid #ddd; display:flex; background:#fff;">
        <input type="text" id="ai_chat_input" class="form-control form-control-sm" placeholder="كيف أضيف عميل؟" style="border-radius:20px; font-family:'Cairo', sans-serif;" onkeypress="if(event.key === 'Enter') sendAiMessage()">
        <button id="ai_chat_send_btn" class="btn btn-dark btn-sm ms-2" style="border-radius:50%; width:35px; height:35px; display:flex; align-items:center; justify-content:center;" onclick="sendAiMessage()">
            <svg id="ai_plane_icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 512 512"><path d="M498.1 5.6c10.1 7 15.4 19.1 13.5 31.2l-64 416c-1.5 9.7-7.4 18.2-16 23s-18.9 5.4-28 1.6L284 427.7l-68.5 74.1c-8.9 9.7-22.9 12.9-35.2 8.1S160 493.2 160 480V396.4c0-4 1.5-7.8 4.2-10.7L331.8 202.8c5.8-6.3 5.6-16-.4-22s-15.7-6.4-22-.7L106 360.8 17.7 316.6C7.1 311.3 .3 300.7 0 288.9s5.9-22.8 16.1-28.7l448-256c10.7-6.1 23.9-5.5 34 1.4z"/></svg>
        </button>
    </div>
</div>

<script>
    let aiChatHistory = [];

    function toggleAiChat() {
        const chatBox = document.getElementById('ai_chat_container');
        chatBox.style.display = chatBox.style.display === 'none' || chatBox.style.display === '' ? 'flex' : 'none';
        if (chatBox.style.display === 'flex') {
            document.getElementById('ai_chat_input').focus();
        }
    }

    async function sendAiMessage() {
        const input = document.getElementById('ai_chat_input');
        const message = input.value.trim();
        if (!message) return;

        const history = document.getElementById('ai_chat_history');
        const sendBtn = document.getElementById('ai_chat_send_btn');

        // Append User Message
        history.innerHTML += `
            <div style="margin-bottom:10px; text-align:left;">
                <span style="background:#ffc107; color:#000; padding:8px 12px; border-radius:15px; display:inline-block; border-bottom-left-radius:0;">
                    ${message}
                </span>
            </div>
        `;
        
        input.value = '';
        input.disabled = true;
        sendBtn.disabled = true;
        sendBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 512 512" style="animation: spin 1s linear infinite;"><style>@keyframes spin { 100% { transform: rotate(360deg); } }</style><path d="M304 48a48 48 0 1 0 -96 0 48 48 0 1 0 96 0zm0 416a48 48 0 1 0 -96 0 48 48 0 1 0 96 0zM48 304a48 48 0 1 0 0-96 48 48 0 1 0 0 96zm464-48a48 48 0 1 0 -96 0 48 48 0 1 0 96 0zM142.9 437A48 48 0 1 0 75 369.1 48 48 0 1 0 142.9 437zm0-294.2A48 48 0 1 0 75 75a48 48 0 1 0 67.9 67.9zM369.1 437A48 48 0 1 0 437 369.1 48 48 0 1 0 369.1 437z"/></svg>`;
        
        // Scroll to bottom
        history.scrollTop = history.scrollHeight;

        try {
            const apiKey = "<?= $gemini_key ?>";
            const systemPrompt = `أنت المساعد الذكي لنظام (القادري وماجد لبيع القات). مهمتك تدريب المالك بصبر وبأقصى تفصيل ممكن عن كل كبيرة وصغيرة في نظامه. يجب أن تحفظ سياق الحوار وترد على أسئلته وكأنك خبير مبرمج النظام. تحدث بلهجة يمنية مهذبة ومريحة (يا مديرنا الغالي، أبشر، من عيوني). 
لا تقطع إجابتك، وأعطِ تفاصيل وشرحاً وافياً وبشكل نقاط.

الدليل الشامل والمفصل للنظام:

1. التوريد (sourcing.php):
- يخص تسجيل البضاعة القادمة من (الرعوي/المزارع) قبل وزنها.
- يوجد فيه تبويبتان: (قات جاهز) و(قات من الرعية). يحدد فيه المدير تكلفة الشراء المبدئية وكمية الأكياس. هذا لا يؤثر على المخزون بعد.

2. استلام بضاعة/المشتريات (purchases.php):
- هنا يتم استلام التوريد الفعلي. تختار المورد المعلق، ثم تقوم بوزن القات (وزن صافي) وخصم (القرح/التالف المتوقع).
- بمجرد الحفظ، يدخل القات رسمياً في المخزن الخاص بك وتصبح حالته (Fresh / طازج).

3. المبيعات (sales.php):
- يمكنك البيع بطريقتين: (بالوزن / كيلو-جرام) أو (بالحبة / كيس).
- الدفع يكون: إما (نقدي Cash) فيدخل للصندوق مباشرة، أو (آجل Debt) ويجب اختيار اسم العميل ليسجل كدين عليه في كشف حسابه.

4. دورة حياة القات (البقايا والتوالف):
- بنهاية اليوم، القات (الطازج) الذي لم يُبع لا يرمى. يذهب المدير إلى (بيع أول sales_leftovers_1.php) لتحويله، مما يقلل سعره للنصف مثلاً.
- في اليوم التالي إذا لم يُبع يتجه إلى وظيفة (بيع ثاني sales_leftovers_2.php) ليقل سعره أكثر.
- إذا أصبح غير صالح للبيع، ينقل إلى حالة (تالف)، ليتم حسابه كخسارة وتصفير كميته.

5. المرتجعات (returns.php):
- تُستخدم إذا رجّع الزبون القات ورده لك.
- النظام سيقوم بإرجاع الكمية المرتجعة إلى المخزون (كـ طازج أو بيع أول حسب حالتها)، وسيقوم بسحب فلوس من الصندوق وإعادتها للزبون أو تنزيلها من مديونيته إذا كان عليه دين.

6. التعويضات (refunds.php):
- إذا كان الزبون غاضب من جودة القات ولكن (لم يرجعه وأبقاه لديه).
- النظام هنا يتدخل لإعطائه مبلغ مالي (خصم/ترضية). النظام لااا يتدخل في كمية المخزون هنا أبداً، بل يخصم فلوس من الصندوق أو من ديون الزبون فقط.

7. العملاء والديون:
- (customers.php): إضافة حسابات الزبائن. يمكن إضافة زبون من شاشة المبيعات مباشرة أيضاً.
- (debts.php): الديون، عند مجيء زبون لدفع قسط من ديونه، تسجل المبلغ كـ (قبض نقدية).

8. المصاريف والموظفين:
- (expenses.php): تُسجل فيها الإيجارات، الأكياس، الفواتير، وكل المصاريف النثرية التي تُسحب من الصندوق.
- (staff.php): لإدارة سلفيات وراتب العمال.

9. التقارير (reports.php):
- قلب النظام المالي! تحتوي 15 تبويبة تفصيلية: (تقرير المبيعات، تقرير المشتريات، كشوفات العملاء، حسابات الموردين، تقارير المصاريف، رواتب الموظفين، تقرير الصندوق، تقرير الأرباح والخسائر، وغيرها).

10. إغلاق اليومية (closing.php):
- أهم شاشة يومية. يجب تصفير الصندوق نهاية اليوم.
- المعادلة: النظام يحسب (كل المبيعات النقدية التي دخلت) + (كل السدادات التي دفعها المتعمدين) ويطرح منها (المصاريف التي دفعت للموردين) - (المصاريف النثرية) - (التعويضات النقدية).
- الباقي هو الموجود بالكاش، يسحبه المدير ويصفر الصندوق ليبدأ غداً بصندوق نظيف.

تذكر: استخدم المعطيات السابقة للإجابة على الأسئلة الدقيقة خطوة بخطوة، واحتفظ بسياق الكلام معه.`;

            // Append User Question to History memory
            aiChatHistory.push({ role: 'user', parts: [{ text: message }] });

            // Step 1: Dynamically find exactly which model this API key has access to!
            let targetModel = 'gemini-1.5-flash';
            try {
                const modelReq = await fetch('https://generativelanguage.googleapis.com/v1beta/models?key=' + apiKey);
                const modelData = await modelReq.json();
                if (modelData && modelData.models) {
                    const validModels = modelData.models.filter(m => m.supportedGenerationMethods.includes('generateContent') && m.name.includes('gemini'));
                    if (validModels.length > 0) {
                        targetModel = validModels[0].name.replace('models/', '');
                    }
                }
            } catch (ignore) {}

            // Step 2: Make the actual request using the conversational payload
            const payload = {
                systemInstruction: { parts: [{ text: systemPrompt }] },
                contents: aiChatHistory,
                generationConfig: { temperature: 0.2, maxOutputTokens: 2048 }
            };

            const response = await fetch('https://generativelanguage.googleapis.com/v1beta/models/' + targetModel + ':generateContent?key=' + apiKey, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            
            let replyHtml = '';
            if (data.error) {
                replyHtml = `<span style="color:red;">خطأ من جوجل: ${data.error.message}</span>`;
            } else if(data.candidates && data.candidates[0]) {
                replyHtml = data.candidates[0].content.parts[0].text;
                replyHtml = data.candidates[0].content.parts[0].text;
                // Add AI Reply to conversational history Memory
                aiChatHistory.push({ role: 'model', parts: [{ text: replyHtml }] });

                // Parse simple markdown properly
                replyHtml = replyHtml.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                replyHtml = replyHtml.replace(/\*(.*?)\*/g, '<em>$1</em>');
                replyHtml = replyHtml.replace(/\n/g, '<br>');
            } else {
                replyHtml = "لم أفهم السؤال، هل يمكنك التوضيح؟";
            }

            // Append AI Reply
            history.innerHTML += `
                <div style="margin-bottom:10px; text-align:right;">
                    <span style="background:#e9ecef; color:#000; padding:8px 12px; border-radius:15px; display:inline-block; border-bottom-right-radius:0;">
                        ${replyHtml}
                    </span>
                </div>
            `;
        } catch (error) {
            history.innerHTML += `
                <div style="margin-bottom:10px; text-align:right;">
                    <span style="background:#f8d7da; color:#721c24; padding:8px 12px; border-radius:15px; display:inline-block; border-bottom-right-radius:0; font-size:0.85rem;">
                        <b>خطأ محلي في الاتصال:</b> ${error.message} <br><small>يرجى التأكد من اتصال الإنترنت في هاتفك</small>
                    </span>
                </div>
            `;
        }

        input.disabled = false;
        sendBtn.disabled = false;
        sendBtn.innerHTML = `<svg id="ai_plane_icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 512 512"><path d="M498.1 5.6c10.1 7 15.4 19.1 13.5 31.2l-64 416c-1.5 9.7-7.4 18.2-16 23s-18.9 5.4-28 1.6L284 427.7l-68.5 74.1c-8.9 9.7-22.9 12.9-35.2 8.1S160 493.2 160 480V396.4c0-4 1.5-7.8 4.2-10.7L331.8 202.8c5.8-6.3 5.6-16-.4-22s-15.7-6.4-22-.7L106 360.8 17.7 316.6C7.1 311.3 .3 300.7 0 288.9s5.9-22.8 16.1-28.7l448-256c10.7-6.1 23.9-5.5 34 1.4z"/></svg>`;
        input.focus();
        history.scrollTop = history.scrollHeight;
    }
</script>
<?php endif; ?>

<!-- Libraries -->
<script src="https://cdn.jsdelivr.net/npm/driver.js@0.9.8/dist/driver.min.js"></script>

<!-- Global Site Data for Tour -->
<script>
    window.siteConfig = {
        role: "<?= $_SESSION['role'] ?? 'user' ?>",
        subRole: "<?= $_SESSION['sub_role'] ?? 'full' ?>",
        page: "<?= basename($_SERVER['PHP_SELF']) ?>"
    };
</script>

<script src="public/js/site-tour.js?v=<?= time() ?>"></script>
<script src="public/js/main.js?v=<?= time() ?>"></script>
</body>

</html>