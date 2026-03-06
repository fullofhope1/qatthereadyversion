<?php
require_once 'config/db.php';
include_once 'includes/header.php';

// Fetch customers with debt > 0 and their last activity date
$stmt = $pdo->query("
    SELECT c.*, 
           (SELECT MAX(sale_date) FROM sales WHERE customer_id = c.id) as last_sale,
           (SELECT MAX(payment_date) FROM payments WHERE customer_id = c.id) as last_pay
    FROM customers c 
    WHERE total_debt > 0 
    ORDER BY name ASC
");
$customers = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fab fa-whatsapp text-success"></i> كشوفات حساب واتساب</h3>
        <div class="d-flex gap-2">
            <button id="sendBtn" class="btn btn-success btn-lg shadow" onclick="sendAll()">
                <i class="fab fa-whatsapp"></i> إرسال للكل
            </button>
            <button id="stopBtn" class="btn btn-danger btn-lg shadow" style="display:none;" onclick="stopQueue()">
                <i class="fas fa-stop"></i> إيقاف
            </button>
        </div>
    </div>

    <!-- Progress Bar (#41) -->
    <div id="progressWrapper" class="mb-4" style="display:none;">
        <div class="d-flex justify-content-between mb-1 small text-muted">
            <span id="progressText">جاري الإرسال...</span>
            <span id="progressPercent">0%</span>
        </div>
        <div class="progress" style="height: 10px;">
            <div id="progressBar" class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width: 0%"></div>
        </div>
    </div>

    <div class="alert alert-warning">
        <i class="fas fa-info-circle"></i> <b>هام:</b> عند الضغط على "إرسال للكل"، قد يقوم المتصفح بحظر النوافذ المنبثقة. يرجى <b>السماح بالنوافذ المنبثقة</b> لهذا الموقع.
        <br> سيقوم النظام بفتح تبويب واتساب لكل عميل بالتوالي.
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><input type="checkbox" id="selectAll" checked onchange="toggleAll(this)"></th>
                            <th>العميل</th>
                            <th>الجوال</th>
                            <th>الرصيد</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody id="custTable">
                        <?php foreach ($customers as $c):
                            $id = $c['id'];

                            // Fetch last 5 transactions for detail (Sales, Payments, Refunds)
                            $transStmt = $pdo->prepare("
                                (SELECT sale_date as t_date, 'بيع' as t_type, price as amount FROM sales WHERE customer_id = ? AND payment_method = 'Debt')
                                UNION ALL
                                (SELECT payment_date as t_date, 'سداد' as t_type, -amount as amount FROM payments WHERE customer_id = ?)
                                UNION ALL
                                (SELECT created_at as t_date, 'مرتجع' as t_type, -amount as amount FROM refunds WHERE customer_id = ? AND refund_type = 'Debt')
                                ORDER BY t_date DESC LIMIT 5
                            ");
                            $transStmt->execute([$id, $id, $id]);
                            $lastTrans = array_reverse($transStmt->fetchAll()); // Older first in message

                            $transLine = "";
                            foreach ($lastTrans as $tr) {
                                $date = date('m-d', strtotime($tr['t_date']));
                                $amt = number_format(abs($tr['amount']));
                                $sign = ($tr['amount'] > 0 ? '+' : '-');
                                $transLine .= "📅 {$date}: {$tr['t_type']} ({$sign}{$amt})\n";
                            }

                            // Format Detailed Message:
                            $todayDate = date('Y-m-d');
                            $msg = "مرحباً *{NAME}*، 👋\n\nنود إحاطتكم بتفاصيل مديونيتكم لدى *القادري و ماجد* بتاريخ {$todayDate}:\n\n*آخر الحركات:*\n" . ($transLine ?: "لا يوجد حركات مؤخراً\n") . "\n💰 *دينك الإجمالي الحالي:* {AMOUNT} ريال يمني.\n\nيرجى التكرم بالسداد في أقرب وقت لضمان استمرارية التعامل.\n\nشكراً لتعاملكم معنا.\n*القادري و ماجد*";

                            $msg = str_replace('{NAME}', $c['name'], $msg);
                            $msg = str_replace('{AMOUNT}', number_format($c['total_debt']), $msg);
                            $encodedMsg = urlencode($msg);

                            // Format Phone (Ensure international format without leading zeros)
                            $phone = preg_replace('/\D/', '', $c['phone']);
                            // Remove leading zero if present (e.g., 0777... -> 777...)
                            if (substr($phone, 0, 1) === '0') {
                                $phone = substr($phone, 1);
                            }
                            // Add 967 prefix if it looks like a local 9-digit number
                            if (strlen($phone) == 9 && substr($phone, 0, 1) == '7') {
                                $phone = '967' . $phone;
                            }
                        ?>
                            <tr class="cust-row" data-phone="<?= $phone ?>" data-msg="<?= $encodedMsg ?>">
                                <td><input type="checkbox" class="cust-check" checked></td>
                                <td><?= htmlspecialchars($c['name']) ?></td>
                                <td><?= htmlspecialchars($c['phone']) ?></td>
                                <td class="fw-bold text-danger"><?= number_format($c['total_debt']) ?></td>
                                <td>
                                    <a href="https://wa.me/<?= $phone ?>?text=<?= $encodedMsg ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                        <i class="fab fa-whatsapp"></i> إرسال
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="5" class="text-center">لا يوجد عملاء عليهم ديون حالياً.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    let sendingQueue = [];
    let currentIndex = 0;

    function toggleAll(source) {
        let checkboxes = document.getElementsByClassName('cust-check');
        for (let i = 0, n = checkboxes.length; i < n; i++) {
            checkboxes[i].checked = source.checked;
        }
    }

    function sendAll() {
        // Reset and build queue
        sendingQueue = [];
        currentIndex = 0;

        const rows = document.querySelectorAll('.cust-row');
        rows.forEach(row => {
            const checkbox = row.querySelector('.cust-check');
            if (checkbox.checked) {
                sendingQueue.push({
                    name: row.cells[1].innerText,
                    phone: row.getAttribute('data-phone'),
                    msg: row.getAttribute('data-msg'),
                    rowElement: row
                });
            }
        });

        if (sendingQueue.length === 0) {
            alert("يرجى تحديد عميل واحد على الأقل.");
            return;
        }

        startQueue();
    }

    let isStopped = false;

    function stopQueue() {
        isStopped = true;
        document.getElementById('stopBtn').style.display = 'none';
        document.getElementById('sendBtn').innerHTML = `<i class="fas fa-play"></i> استئناف الكل`;
        document.getElementById('sendBtn').disabled = false;
        document.getElementById('progressText').innerText = "تم الإيقاف.";
    }

    async function startQueue() {
        const btn = document.getElementById('sendBtn');
        const stopBtn = document.getElementById('stopBtn');
        const progressWrapper = document.getElementById('progressWrapper');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const progressPercent = document.getElementById('progressPercent');

        btn.disabled = true;
        stopBtn.style.display = 'block';
        progressWrapper.style.display = 'block';
        isStopped = false;

        let total = sendingQueue.length;
        let blocked = false;

        for (let i = currentIndex; i < total; i++) {
            if (isStopped) {
                currentIndex = i;
                return;
            }

            const target = sendingQueue[i];
            const url = `https://wa.me/${target.phone}?text=${target.msg}`;
            const newTab = window.open(url, '_blank');

            if (!newTab || newTab.closed || typeof newTab.closed === 'undefined') {
                blocked = true;
            } else {
                target.rowElement.classList.add('table-success', 'opacity-50');
                target.rowElement.querySelector('.cust-check').checked = false;
            }

            let percent = Math.round(((i + 1) / total) * 100);
            progressBar.style.width = percent + '%';
            progressPercent.innerText = percent + '%';
            progressText.innerText = `جاري إرسال (${i + 1} من ${total}): ${target.name}`;

            // Wait 1.5s
            await new Promise(resolve => setTimeout(resolve, 1500));
        }

        btn.disabled = false;
        stopBtn.style.display = 'none';

        if (blocked) {
            btn.innerHTML = `<i class="fas fa-exclamation-triangle"></i> فشل البعض (تحقق من Popup)`;
            btn.className = "btn btn-danger btn-lg shadow";
            showPopupInstructions();
        } else {
            btn.innerHTML = `<i class="fas fa-check-circle"></i> تم الإرسال للكل!`;
            btn.className = "btn btn-success btn-lg shadow";
        }
    }

    function showPopupInstructions() {
        alert("تنبيه: قام المتصفح بحظر النوافذ المنبثقة.\n\nيرجى الضغط على أيقونة (النافذة المحظورة) في شريط العنوان بالأعلى واختيار \"السماح دائماً بالنوافذ المنبثقة من هذا الموقع\".\n\nثم حاول مرة ثانية.");
    }
</script>

<?php include_once 'includes/footer.php'; ?>