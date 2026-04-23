<?php
require 'config/db.php';
include 'includes/header.php';

// Initialization via Clean Architecture
$refundRepo = new RefundRepository($pdo);
$customerRepo = new CustomerRepository($pdo);
$saleRepo = new SaleRepository($pdo);
$purchaseRepo = new PurchaseRepository($pdo);
$leftoverRepo = new LeftoverRepository($pdo);

$service = new RefundService($refundRepo, $customerRepo, $saleRepo, $purchaseRepo, $leftoverRepo);

$data = $service->getRefundDashboardData();
$customers = $data['customers'];
$recentRefunds = $data['recent_refunds'];

$custJson = json_encode($customers);
?>

<style>
    .step-wizard {
        display: flex;
        align-items: center;
        gap: 0;
        margin-bottom: 1.5rem;
    }

    .step-wizard .step {
        flex: 1;
        text-align: center;
        padding: 10px 5px;
        background: #f1f5f9;
        border: 1px solid #dee2e6;
        font-weight: 600;
        font-size: 0.85rem;
        color: #6c757d;
        cursor: default;
        transition: all 0.3s;
    }

    .step-wizard .step:first-child {
        border-radius: 12px 0 0 12px;
    }

    .step-wizard .step:last-child {
        border-radius: 0 12px 12px 0;
    }

    .step-wizard .step.active {
        background: #f59e0b;
        color: #000;
        border-color: #f59e0b;
    }

    .step-wizard .step.done {
        background: #22c55e;
        color: #fff;
        border-color: #22c55e;
    }

    .step-panel {
        display: none;
    }

    .step-panel.active {
        display: block;
    }

    .refund-card {
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    }

    .refund-header {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        padding: 1.5rem 2rem;
    }

    .type-card {
        cursor: pointer;
        border-radius: 14px;
        padding: 1rem;
        border: 2px solid #e2e8f0;
        transition: all 0.25s;
        background: #f8fafc;
    }

    .type-card:hover {
        border-color: #f59e0b;
        background: #fffbeb;
    }

    .type-card.selected {
        border-color: #f59e0b;
        background: #fffbeb;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, .2);
    }

    .type-card input[type="radio"] {
        display: none;
    }

    .recent-search {
        border-radius: 12px;
        padding: 10px 16px;
        border: 1.5px solid #e2e8f0;
    }
</style>

<div class="row justify-content-center">
    <!-- REFUND WIZARD FORM -->
    <div class="col-md-6 mb-4">
        <div class="refund-card">
            <div class="refund-header text-dark">
                <h4 class="mb-0 fw-bold"><i class="fas fa-hand-holding-usd me-2"></i> التعويضات والاسترجاع</h4>
                <p class="mb-0 small opacity-75 mt-1">خصم من الدين أو استرجاع نقدي — خطوة بخطوة</p>
            </div>
            <div class="p-4 bg-white">
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> تمت العملية بنجاح!</div>
                <?php endif; ?>

                <!-- Step Indicator -->
                <div class="step-wizard">
                    <div class="step active" id="ind1">① اختر الزبون</div>
                    <div class="step" id="ind2">② نوع العملية</div>
                    <div class="step" id="ind3">③ تأكيد ✓</div>
                </div>

                <form action="requests/process_new_refund.php" method="POST" onsubmit="return validateRefund()">
                    <input type="hidden" name="customer_id" id="selectedCustomerId">

                    <!-- Step 1: Choose Customer -->
                    <div class="step-panel active" id="sp1">
                        <label class="form-label fw-bold"><i class="fas fa-search me-1 text-warning"></i> ابحث عن الزبون</label>
                        <input type="text" id="custSearchInput" class="form-control form-control-lg mb-2" placeholder="اكتب اسم الزبون..." oninput="filterRefundCustomers()" autocomplete="off">
                        <div id="custDropdown" class="list-group shadow-sm" style="max-height:220px; overflow-y:auto; display:none; border-radius: 12px;"></div>

                        <div id="selectedCustDisplay" class="card border-warning mt-2" style="display:none;">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold fs-5" id="scd_name">—</div>
                                        <small class="text-muted">الدين الحالي</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="h4 text-danger fw-bold mb-0" id="scd_debt">—</div>
                                        <small class="text-muted">ريال</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-warning w-100 py-3 fw-bold" id="step1Next" onclick="goRefundStep(2)" disabled>
                                التالي <i class="fas fa-arrow-left ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Amount & Type -->
                    <div class="step-panel" id="sp2">
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-coins me-1 text-warning"></i> المبلغ <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="refundAmount" class="form-control form-control-lg" step="1" min="1" placeholder="0">
                            <div class="text-danger small mt-1" id="amountError"></div>
                        </div>

                        <!-- Financial Operation Only -->
                        <label class="form-label fw-bold d-block mb-2"><i class="fas fa-list me-1 text-warning"></i> نوع العملية</label>
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="type-card selected" id="card_debt" onclick="selectType('Debt')">
                                    <input type="radio" name="refund_type" value="Debt" id="typeDebt" checked>
                                    <div class="text-center">
                                        <i class="fas fa-file-invoice-dollar fs-2 text-warning mb-2 d-block"></i>
                                        <div class="fw-bold">خصم من الدين</div>
                                        <small class="text-muted">تعويض / خصم على جودة القات</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="type-card" id="card_cash" onclick="selectType('Cash')">
                                    <input type="radio" name="refund_type" value="Cash" id="typeCash">
                                    <div class="text-center">
                                        <i class="fas fa-money-bill-wave fs-2 text-success mb-2 d-block"></i>
                                        <div class="fw-bold">استرجاع نقدي</div>
                                        <small class="text-muted">إعادة مبلغ نقدي للزبون</small>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-comment me-1 text-warning"></i> السبب <span class="text-danger">*</span></label>
                            <textarea name="reason" id="refundReason" class="form-control" rows="3" placeholder="مثال: الجودة كانت سيئة، تعويض عن 1 كجم..."></textarea>
                            <div class="text-danger small mt-1" id="reasonError"></div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-50 py-3 fw-bold" onclick="goRefundStep(1)">
                                <i class="fas fa-arrow-right me-2"></i> رجوع
                            </button>
                            <button type="button" class="btn btn-warning w-50 py-3 fw-bold" onclick="goRefundStep(3)">
                                مراجعة <i class="fas fa-arrow-left ms-2"></i>
                            </button>
                        </div>
                    </div>
            </div>

            <!-- Step 3: Confirm -->
            <div class="step-panel" id="sp3">
                <div class="card bg-warning-subtle border-warning mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold text-warning-emphasis mb-3"><i class="fas fa-check-circle me-2"></i> مراجعة العملية</h6>
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="text-muted fw-bold">الزبون</td>
                                <td id="rev_cust">—</td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-bold">نوع العملية</td>
                                <td id="rev_type">—</td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-bold">المبلغ</td>
                                <td id="rev_amount" class="fw-bold text-danger fs-5">—</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary w-50 py-3 fw-bold" onclick="goRefundStep(2)">
                        <i class="fas fa-arrow-right me-2"></i> رجوع
                    </button>
                    <button type="submit" class="btn btn-dark w-50 py-3 fw-bold">
                        <i class="fas fa-check-circle me-2"></i> تنفيذ العملية
                    </button>
                </div>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- RECENT OPERATIONS -->
<div class="col-md-6 mb-4">
    <div class="card shadow-sm border-0 h-100" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i> العمليات الأخيرة</h5>
            <span class="badge bg-warning text-dark"><?= count($recentRefunds) ?></span>
        </div>
        <div class="card-body p-3">
            <input type="text" id="recentSearch" class="form-control recent-search mb-3" placeholder="🔍 بحث في العمليات...">
            <div style="max-height: 520px; overflow-y: auto;">
                <table class="table table-hover mb-0 align-middle" id="recentTable">
                    <thead class="table-light">
                        <tr>
                            <th>الزبون</th>
                            <th>المبلغ</th>
                            <th>النوع</th>
                            <th>السبب</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentRefunds)): ?>
                            <tr>
                                <td colspan="4" class="text-muted py-5 text-center"><i class="fas fa-inbox fs-1 d-block mb-2 opacity-25"></i>لا توجد عمليات تعويض حديثة.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentRefunds as $r): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($r['cust_name']) ?></td>
                                    <td class="text-danger fw-bold"><?= number_format($r['amount']) ?></td>
                                    <td>
                                        <?php if ($r['refund_type'] == 'Debt'): ?>
                                            <span class="badge bg-warning text-dark">خصم دين</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">نقدي</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($r['reason']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    const allCustomers = <?= $custJson ?>;
    let selectedDebt = 0;
    let selectedCustName = '';

    function filterRefundCustomers() {
        const term = document.getElementById('custSearchInput').value.toLowerCase();
        const dropdown = document.getElementById('custDropdown');
        dropdown.innerHTML = '';
        if (!term) {
            dropdown.style.display = 'none';
            return;
        }
        const filtered = allCustomers.filter(c => c.name.toLowerCase().includes(term)).slice(0, 10);
        if (filtered.length === 0) {
            dropdown.style.display = 'none';
            return;
        }
        dropdown.style.display = 'block';
        filtered.forEach(c => {
            const item = document.createElement('a');
            item.href = '#';
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2';
            item.innerHTML = `<span class="fw-bold">${c.name}</span><span class="badge bg-danger">${Number(c.total_debt).toLocaleString()} ريال</span>`;
            item.onclick = (e) => {
                e.preventDefault();
                selectCustomer(c);
            };
            dropdown.appendChild(item);
        });
    }

    function selectCustomer(c) {
        document.getElementById('selectedCustomerId').value = c.id;
        document.getElementById('custSearchInput').value = '';
        document.getElementById('custDropdown').style.display = 'none';
        document.getElementById('selectedCustDisplay').style.display = 'block';
        document.getElementById('scd_name').textContent = c.name;

        const debt = parseFloat(c.total_debt) || 0;
        document.getElementById('scd_debt').textContent = debt.toLocaleString();
        document.getElementById('step1Next').disabled = false;
        selectedDebt = debt;
        selectedCustName = c.name;
        
        // Fetch customer sales
        fetchCustomerSales(c.id);
    }

    let customerSales = [];
    function fetchCustomerSales(custId) {
        const select = document.getElementById('saleSelect');
        select.innerHTML = '<option value="">-- جارِ التحميل... --</option>';
        
        fetch(`requests/get_customer_unpaid_sales.php?customer_id=${custId}`)
            .then(res => res.json())
            .then(data => {
                select.innerHTML = '<option value="">-- اختر عملية بيع سابقة --</option>';
                if (data.sales && data.sales.length > 0) {
                    customerSales = data.sales;
                    data.sales.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.id;
                        opt.textContent = `${s.sale_date} | ${s.type_name} | المتبقي: ${Number(s.remaining_debt).toLocaleString()} ريال`;
                        select.appendChild(opt);
                    });
                } else {
                    select.innerHTML = '<option value="">-- لا توجد مبيعات غير مدفوعة --</option>';
                }
            });
    }

    function autoFillRefundFromSale() {
        const saleId = document.getElementById('saleSelect').value;
        const inputs = document.getElementById('inventoryInputs');
        if (!saleId) {
            inputs.style.display = 'none';
            return;
        }
        
        inputs.style.display = 'flex';
        const sale = customerSales.find(s => s.id == saleId);
        if (sale) {
            document.getElementById('refundAmount').value = Math.floor(sale.remaining_debt);
            // Default to 1 unit if units-based, or 0 kg if weight-based (user fills it)
            if (sale.unit_type === 'units') {
                document.getElementById('refundUnits').value = sale.quantity_units;
                document.getElementById('refundWeight').value = 0;
            } else {
                document.getElementById('refundWeight').value = (sale.weight_grams / 1000).toFixed(3);
                document.getElementById('refundUnits').value = 0;
            }
        }
    }

    function selectType(val) {
        document.getElementById('typeDebt').checked = val === 'Debt';
        document.getElementById('typeCash').checked = val === 'Cash';
        document.getElementById('card_debt').classList.toggle('selected', val === 'Debt');
        document.getElementById('card_cash').classList.toggle('selected', val === 'Cash');
    }

    function goRefundStep(step) {
        if (step === 3) {
            const amountInput = document.getElementById('refundAmount');
            const amount = parseFloat(amountInput.value) || 0;
            const refundTypeElem = document.querySelector('input[name="refund_type"]:checked');
            const refundType = refundTypeElem ? refundTypeElem.value : 'Debt';
            const reasonInput = document.getElementById('refundReason');
            const reason = reasonInput.value.trim();

            let valid = true;
            if (amount <= 0) {
                document.getElementById('amountError').innerText = 'يرجى إدخال مبلغ أكبر من صفر';
                valid = false;
            } else if (refundType === 'Debt' && amount > selectedDebt) {
                document.getElementById('amountError').innerText = `⚠ المبلغ (${amount.toLocaleString()}) أكبر من دين الزبون (${selectedDebt.toLocaleString()})`;
                valid = false;
            } else {
                document.getElementById('amountError').innerText = '';
            }

            if (!reason) {
                document.getElementById('reasonError').innerText = 'يرجى إدخال سبب العملية';
                valid = false;
            } else {
                document.getElementById('reasonError').innerText = '';
            }

            if (!valid) return;

            document.getElementById('rev_cust').textContent = selectedCustName || '—';
            document.getElementById('rev_type').textContent = refundType === 'Debt' ? 'خصم من الدين' : 'استرجاع نقدي';
            document.getElementById('rev_amount').textContent = amount.toLocaleString() + ' ريال';
        }
        [1, 2, 3].forEach(i => {
            const panel = document.getElementById('sp' + i);
            const indicator = document.getElementById('ind' + i);
            if (panel) panel.classList.toggle('active', i === step);
            if (indicator) {
                indicator.classList.remove('active', 'done');
                if (i < step) indicator.classList.add('done');
                if (i === step) indicator.classList.add('active');
            }
        });
    }

    function validateRefund() {
        const custId = document.getElementById('selectedCustomerId').value;
        if (!custId) {
            alert('يرجى اختيار زبون أولاً');
            return false;
        }
        return true;
    }

    document.getElementById('recentSearch').addEventListener('input', function() {
        const term = this.value.toLowerCase();
        document.querySelectorAll('#recentTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });
</script>

<?php include 'includes/footer.php'; ?>