</div> <!-- End Container -->

<!-- Floating Help Button -->
<?php if (isset($_SESSION['user_id'])): ?>
<a href="javascript:void(0)" id="help_trigger" onclick="if(typeof startSiteTour === 'function') startSiteTour(); else alert('جاري تحميل نظام المساعدة... يرجى المحاولة بعد قليل');" title="دليل الاستخدام">
    <i class="fas fa-question"></i>
</a>
<?php endif; ?>

<!-- Floating AI Chatbot Button (Super Admin Only) -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
<a href="javascript:void(0)" id="ai_trigger" onclick="toggleAiChat()" title="المساعد الذكي" style="position:fixed; bottom:25px; left:25px; width:55px; height:55px; background:#1a1a1a; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5rem; box-shadow:0 4px 15px rgba(0,0,0,0.3); cursor:pointer; z-index:9999; border:2px solid #ffd700; transition:all 0.3s ease;">
    <i class="fas fa-robot"></i>
</a>

<!-- AI Chat Box -->
<div id="ai_chat_container" style="display:none; position:fixed; bottom:90px; left:25px; width:350px; height:450px; background:white; border-radius:15px; box-shadow:0 10px 25px rgba(0,0,0,0.2); z-index:9999; flex-direction:column; overflow:hidden;">
    <div style="background:var(--brand-gradient); color:#1a1a1a; padding:15px; font-weight:900; display:flex; justify-content:space-between; align-items:center;">
        <span><i class="fas fa-robot me-2"></i> مساعد القادري الذكي</span>
        <i class="fas fa-times" style="cursor:pointer; font-size:1.2rem;" onclick="toggleAiChat()"></i>
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
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
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
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Scroll to bottom
        history.scrollTop = history.scrollHeight;

        try {
            const response = await fetch('requests/process_ai_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: message })
            });
            const data = await response.json();
            
            let replyHtml = '';
            if (data.error) {
                replyHtml = `<span style="color:red;">${data.error}</span>`;
            } else {
                replyHtml = data.reply;
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
                    <span style="background:#f8d7da; color:#721c24; padding:8px 12px; border-radius:15px; display:inline-block; border-bottom-right-radius:0;">
                        حدث خطأ في الاتصال. يرجى التأكد من اتصالك.
                    </span>
                </div>
            `;
        }

        input.disabled = false;
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
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