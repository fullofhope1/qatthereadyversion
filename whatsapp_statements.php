<?php
// whatsapp_statements.php
require_once 'config/db.php';
include_once 'includes/header.php';

// Initialization via Clean Architecture
$commRepo = new CommunicationRepository($pdo);
$service = new CommunicationService($commRepo);

$customers = $service->getWhatsAppStatementsData();

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . '://' . $host . '/qat';

// Load Professional Template Data
$notifData = require 'config/notifications.php';
$acc = $notifData['accounts'];
$todayAr = date('Y-m-d');
?>

<style>
    .nav-tabs-wa .nav-link {
        font-weight: 700;
        font-size: 1rem;
        border-radius: 14px 14px 0 0;
        padding: 0.75rem 2rem;
        color: #555;
    }

    .nav-tabs-wa .nav-link.active {
        background: linear-gradient(135deg, #128c7e, #25d366);
        color: #fff;
        border-color: transparent;
    }

    .nav-tabs-wa .nav-link.sms-active.active {
        background: linear-gradient(135deg, #1976d2, #42a5f5);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(25, 135, 84, 0.03);
    }

    .row-sent {
        opacity: 0.5;
        background-color: #f8f9fa !important;
        text-decoration: line-through;
    }

    .badge-wa {
        background: #25d366;
        color: #fff;
    }

    .badge-sms {
        background: #1976d2;
        color: #fff;
    }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">
            <i class="fas fa-paper-plane text-success me-2"></i>
            إرسال كشوفات الحساب
        </h3>
        <span class="badge bg-secondary rounded-pill"><?= count($customers) ?> عميل مدين</span>
    </div>

    <!-- TABS -->
    <ul class="nav nav-tabs nav-tabs-wa mb-0 border-0" id="msgTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="wa-tab" data-bs-toggle="tab" data-bs-target="#wa-panel" type="button" role="tab">
                <i class="fab fa-whatsapp me-2"></i> واتساب
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link sms-active" id="sms-tab" data-bs-toggle="tab" data-bs-target="#sms-panel" type="button" role="tab">
                <i class="fas fa-sms me-2"></i> رسالة SMS
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <!-- ═══════════ WhatsApp TAB ═══════════ -->
        <div class="tab-pane fade show active" id="wa-panel" role="tabpanel">
            <div class="alert alert-success border-0 shadow-sm mb-0 mt-3" style="border-radius: 15px;">
                <i class="fab fa-whatsapp me-2 fs-5"></i>
                اضغط على <strong>إرسال واتساب</strong> لفتح المحادثة مع العميل. يمكنك أيضاً إرسال <strong>كشف حساب PDF</strong> لفتح الكشف.
            </div>

            <div class="card shadow-sm border-0 mt-3" style="border-radius: 20px; overflow: hidden;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">العميل</th>
                                    <th>الجوال</th>
                                    <th>الرصيد المتبقي</th>
                                    <th class="text-end pe-4">إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $c): ?>
                                    <?php
                                    $stmtUrl = $baseUrl . '/customer_statement.php?id=' . $c['id'];
                                    
                                    $msgRaw = "إشعار من {$notifData['company_name']} {$notifData['slogan']}: " . $todayAr . "\n";
                                    $msgRaw .= "عليكم إجمالي مبلغ: " . number_format($c['total_debt']) . " ريال\n\n";
                                    $msgRaw .= "ارقام حساباتنا:\n";
                                    $msgRaw .= "جيب: " . $acc['jeeb'] . "\n";
                                    $msgRaw .= "جوالي: " . $acc['jawwali'] . "\n";
                                    $msgRaw .= "كريمي: " . $acc['kuraimi'] . "\n\n";
                                    $msgRaw .= "📄 كشف حسابكم الكامل: " . $stmtUrl;
                                    
                                    $msgWithLink = rawurlencode($msgRaw);
                                    ?>
                                    <tr id="wa-row-<?= $c['id'] ?>">
                                        <td class="ps-4 fw-bold"><?= htmlspecialchars($c['name']) ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($c['phone']) ?></td>
                                        <td class="fw-bold text-danger"><?= number_format($c['total_debt']) ?> ريال</td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                                <!-- Send via WhatsApp -->
                                                <a href="https://wa.me/<?= $c['formatted_phone'] ?>?text=<?= $c['encoded_msg'] ?>"
                                                    target="_blank"
                                                    class="btn btn-success btn-sm rounded-pill px-3 shadow-sm"
                                                    onclick="markSent('wa-row-<?= $c['id'] ?>')">
                                                    <i class="fab fa-whatsapp me-1"></i> إرسال
                                                </a>
                                                <!-- Send Statement URL via WhatsApp -->
                                                <a href="https://wa.me/<?= $c['formatted_phone'] ?>?text=<?= $msgWithLink ?>"
                                                    target="_blank"
                                                    class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm">
                                                    <i class="fas fa-file-invoice me-1"></i> كشف الحساب
                                                </a>
                                                <!-- Open/Print Statement PDF -->
                                                <a href="customer_statement.php?id=<?= $c['id'] ?>"
                                                    target="_blank"
                                                    class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                                                    <i class="fas fa-print me-1"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fas fa-users-slash fs-1 d-block mb-3 opacity-25"></i>
                                            لا يوجد عملاء عليهم ديون حالياً.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div><!-- /wa-panel -->

        <!-- ═══════════ SMS TAB ═══════════ -->
        <div class="tab-pane fade" id="sms-panel" role="tabpanel">
            <div class="alert alert-info border-0 shadow-sm mb-0 mt-3" style="border-radius: 15px;">
                <i class="fas fa-mobile-alt me-2 fs-5"></i>
                مخصص للاستخدام من الجوال. اضغط على <strong>إرسال SMS</strong> لفتح تطبيق الرسائل مباشرة مع الرسالة جاهزة.
            </div>

            <div class="card shadow-sm border-0 mt-3" style="border-radius: 20px; overflow: hidden;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">العميل</th>
                                    <th>الجوال</th>
                                    <th>الرصيد المتبقي</th>
                                    <th class="text-end pe-4">إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $c): ?>
                                    <?php
                                    $smsRaw = "إشعار من {$notifData['company_name']}: عليك مبلغ " . number_format($c['total_debt']) . " ريال. حساباتنا: جيب/جوالي " . $acc['jeeb'] . " كريمي " . $acc['kuraimi'];
                                    $smsText = urlencode($smsRaw);
                                    $smsPhone = $c['phone'];
                                    ?>
                                    <tr id="sms-row-<?= $c['id'] ?>">
                                        <td class="ps-4 fw-bold"><?= htmlspecialchars($c['name']) ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($c['phone']) ?></td>
                                        <td class="fw-bold text-danger"><?= number_format($c['total_debt']) ?> ريال</td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                                <!-- SMS link (works on mobile) -->
                                                <a href="sms:<?= htmlspecialchars($c['phone']) ?>?body=<?= $smsText ?>"
                                                    class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm"
                                                    onclick="markSent('sms-row-<?= $c['id'] ?>')">
                                                    <i class="fas fa-sms me-1"></i> إرسال SMS
                                                </a>
                                                <!-- Preview statement -->
                                                <a href="customer_statement.php?id=<?= $c['id'] ?>"
                                                    target="_blank"
                                                    class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                                                    <i class="fas fa-eye me-1"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fas fa-users-slash fs-1 d-block mb-3 opacity-25"></i>
                                            لا يوجد عملاء عليهم ديون حالياً.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div><!-- /sms-panel -->

    </div><!-- /tab-content -->
</div>

<script>
    function markSent(rowId) {
        const row = document.getElementById(rowId);
        if (row) row.classList.add('row-sent');
    }
</script>

<?php include_once 'includes/footer.php'; ?>