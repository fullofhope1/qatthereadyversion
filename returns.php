<?php
require 'config/db.php';
include 'includes/header.php';

// Initialization
$refundRepo = new RefundRepository($pdo);
$customerRepo = new CustomerRepository($pdo);
$saleRepo = new SaleRepository($pdo);
$purchaseRepo = new PurchaseRepository($pdo);
$leftoverRepo = new LeftoverRepository($pdo);

$service = new RefundService($refundRepo, $customerRepo, $saleRepo, $purchaseRepo, $leftoverRepo);

$data = $service->getRefundDashboardData(); // We can reuse most of this
$customers = $data['customers'];

// Filter recent records to only show those with inventory return
$stmt = $pdo->prepare("SELECT r.*, c.name as cust_name FROM refunds r LEFT JOIN customers c ON r.customer_id = c.id WHERE r.weight_kg > 0 OR r.quantity_units > 0 ORDER BY r.created_at DESC LIMIT 20");
$stmt->execute();
$recentReturns = $stmt->fetchAll();

$custJson = json_encode($customers);
?>

<style>
    .step-wizard { display: flex; align-items: center; gap: 0; margin-bottom: 1.5rem; }
    .step-wizard .step { flex: 1; text-align: center; padding: 10px 5px; background: #f1f5f9; border: 1px solid #dee2e6; font-weight: 600; font-size: 0.85rem; color: #6c757d; cursor: default; transition: all 0.3s; }
    .step-wizard .step:first-child { border-radius: 12px 0 0 12px; }
    .step-wizard .step:last-child { border-radius: 0 12px 12px 0; }
    .step-wizard .step.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
    .step-wizard .step.done { background: #22c55e; color: #fff; border-color: #22c55e; }
    .step-panel { display: none; }
    .step-panel.active { display: block; }
    .return-card { border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08); }
    .return-header { background: linear-gradient(135deg, #3b82f6, #2563eb); padding: 1.5rem 2rem; color: white; }
    .sale-item-card { cursor: pointer; border-radius: 12px; border: 2px solid #e2e8f0; transition: all 0.2s; }
    .sale-item-card:hover { border-color: #3b82f6; background: #eff6ff; }
    .sale-item-card.selected { border-color: #3b82f6; background: #eff6ff; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
</style>

<div class="row justify-content-center">
    <div class="col-md-6 mb-4">
        <div class="return-card">
            <div class="return-header">
                <h4 class="mb-0 fw-bold"><i class="fas fa-undo me-2"></i> نظام المرتجعات</h4>
                <p class="mb-0 small opacity-75 mt-1">إرجاع القات للمخزن واسترداد القيمة مديونية أو نقداً</p>
            </div>
            <div class="p-4 bg-white">
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> تمت عملية المرتجع واستعادة المخزون بنجاح!</div>
                <?php endif; ?>

                <div class="step-wizard">
                    <div class="step active" id="ind1">① الزبون</div>
                    <div class="step" id="ind2">② فاتورة البيع</div>
                    <div class="step" id="ind3">③ المرتجع ✓</div>
                </div>

                <form action="requests/process_new_return.php" method="POST" id="returnForm">
                    <input type="hidden" name="customer_id" id="selectedCustomerId">
                    <input type="hidden" name="sale_id" id="selectedSaleId">

                    <!-- Step 1: Choose Customer -->
                    <div class="step-panel active" id="sp1">
                        <label class="form-label fw-bold"><i class="fas fa-search me-1 text-primary"></i> ابحث عن الزبون</label>
                        <input type="text" id="custSearchInput" class="form-control form-control-lg mb-2" placeholder="اكتب اسم الزبون..." oninput="filterCustomers()" autocomplete="off">
                        <div id="custDropdown" class="list-group shadow-sm" style="max-height:220px; overflow-y:auto; display:none;"></div>

                        <div id="selectedCustDisplay" class="card border-primary mt-2" style="display:none;">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="fw-bold fs-5" id="scd_name">—</div>
                                    <div class="text-end">
                                        <div class="h5 text-danger fw-bold mb-0" id="scd_debt">—</div>
                                        <small class="text-muted">ريال دين</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-primary w-100 py-3 fw-bold" id="step1Next" onclick="goToStep(2)" disabled>
                                اختيار الفاتورة <i class="fas fa-arrow-left ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Choose Sale -->
                    <div class="step-panel" id="sp2">
                        <label class="form-label fw-bold mb-3"><i class="fas fa-file-invoice me-1 text-primary"></i> اختر عملية البيع المراد الإرجاع منها</label>
                        <div id="custTotalDebtBadge"></div>
                        <div id="salesList" class="d-flex flex-column gap-2" style="max-height: 400px; overflow-y: auto; padding: 5px;">
                            <!-- Sales items will appear here -->
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-outline-secondary w-50 py-3 fw-bold" onclick="goToStep(1)">
                                <i class="fas fa-arrow-right me-2"></i> رجوع
                            </button>
                            <button type="button" class="btn btn-primary w-50 py-3 fw-bold" id="step2Next" onclick="goToStep(3)" disabled>
                                التالي <i class="fas fa-arrow-left ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Quantities and Payment -->
                    <div class="step-panel" id="sp3">
                        <div class="card bg-light border-0 mb-3" style="border-radius: 12px;">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">الكمية المرتجعة</h6>
                                <div class="row g-3">
                                    <div class="col-6" id="weightGroup">
                                        <label class="small text-muted mb-1">الوزن (كجم)</label>
                                        <input type="number" name="weight_kg" id="retWeight" class="form-control" step="0.001" placeholder="0.000">
                                    </div>
                                    <div class="col-6" id="unitGroup">
                                        <label class="small text-muted mb-1">الكمية (حبة)</label>
                                        <input type="number" name="quantity_units" id="retUnits" class="form-control" step="1" placeholder="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">المبلغ المسترد (ريال)</label>
                            <input type="number" name="amount" id="retAmount" class="form-control form-control-lg fw-bold text-danger" step="1" min="1">
                            <div class="small text-muted mt-1" id="maxAmountTip"></div>
                        </div>

                        <div class="mb-4">
                            <div id="debtWarningText" class="alert alert-warning d-none fw-bold text-center"></div>
                            <label class="form-label fw-bold small">طريقة رد المبلغ</label>
                            <div class="row g-2">
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="refund_type" id="rtDebt" value="Debt" checked>
                                    <label class="btn btn-outline-primary w-100 py-2 px-1" for="rtDebt">خصم من الدين</label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="refund_type" id="rtCash" value="Cash">
                                    <label class="btn btn-outline-success w-100 py-2 px-1" for="rtCash">نقدي</label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="refund_type" id="rtTransfer" value="Transfer">
                                    <label class="btn btn-outline-info w-100 py-2 px-1" for="rtTransfer">تحويل بنكي</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">السبب (ملاحظة)</label>
                            <textarea name="reason" id="retReason" class="form-control" rows="2" placeholder="لماذا تم الإرجاع؟"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-50 py-3 fw-bold" onclick="goToStep(2)">
                                <i class="fas fa-arrow-right me-2"></i> رجوع
                            </button>
                            <button type="submit" class="btn btn-dark w-50 py-3 fw-bold">
                                <i class="fas fa-check-circle me-2"></i> تنفيذ المرتجع
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- RECENT RETURNS -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 20px;">
            <div class="card-header bg-dark text-white p-3">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i> آخر عمليات المرتجعات</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>الزبون</th>
                                <th>الكمية</th>
                                <th>المبلغ</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentReturns as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['cust_name']) ?></td>
                                    <td>
                                        <?php if ($r['weight_kg'] > 0): ?>
                                            <?= number_format($r['weight_kg'], 3) ?> كجم
                                        <?php else: ?>
                                            <?= $r['quantity_units'] ?> حبة
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-danger"><?= number_format($r['amount']) ?></td>
                                    <td class="small text-muted"><?= date('H:i d/m', strtotime($r['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const customers = <?= $custJson ?>;
    let selectedCustSales = [];

    function filterCustomers() {
        const term = document.getElementById('custSearchInput').value.toLowerCase();
        const dropdown = document.getElementById('custDropdown');
        dropdown.innerHTML = '';
        if (!term) { dropdown.style.display = 'none'; return; }
        const filtered = customers.filter(c => c.name.toLowerCase().includes(term)).slice(0, 10);
        dropdown.style.display = 'block';
        filtered.forEach(c => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
            btn.innerHTML = `<span>${c.name}</span> <span class="badge bg-danger">${Number(c.total_debt).toLocaleString()}</span>`;
            btn.onclick = () => selectCustomer(c);
            dropdown.appendChild(btn);
        });
    }

    function selectCustomer(c) {
        document.getElementById('selectedCustomerId').value = c.id;
        document.getElementById('custSearchInput').value = '';
        document.getElementById('custDropdown').style.display = 'none';
        document.getElementById('selectedCustDisplay').style.display = 'block';
        document.getElementById('scd_name').textContent = c.name;
        document.getElementById('scd_debt').textContent = Number(c.total_debt).toLocaleString();
        document.getElementById('step1Next').disabled = false;
        fetchSales(c.id);
    }

    let currentSaleRemDebt = 0;
    let currentCustomerTotalDebt = 0;
    let currentSaleMaxWeight = 0;
    let currentSaleMaxUnits = 0;

    function fetchSales(custId) {
        const list = document.getElementById('salesList');
        const debtBadge = document.getElementById('custTotalDebtBadge');
        list.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>';
        
        fetch(`requests/get_customer_sales.php?customer_id=${custId}`)
            .then(res => res.json())
            .then(data => {
                list.innerHTML = '';
                selectedCustSales = data.sales || [];
                currentCustomerTotalDebt = parseFloat(data.customer_total_debt || 0);

                // Update Debt Badge
                if (debtBadge) {
                    debtBadge.innerText = `إجمالي مديونية العميل: ${currentCustomerTotalDebt.toLocaleString()} ريال`;
                    debtBadge.className = currentCustomerTotalDebt > 0 ? 'alert alert-danger py-2 mb-3 text-center fw-bold' : 'alert alert-success py-2 mb-3 text-center fw-bold';
                }

                if (selectedCustSales.length === 0) {
                    list.innerHTML = '<div class="alert alert-info">لا توجد مبيعات لهذا الزبون في آخر 30 يوم.</div>';
                    return;
                }
                selectedCustSales.forEach(s => {
                    const card = document.createElement('div');
                    card.className = 'sale-item-card p-3';
                    const isPaid = s.is_paid == 1;
                    const statusText = isPaid ? '<span class="badge bg-success">مدفوعة</span>' : `<span class="badge bg-danger">دين: ${Number(s.remaining_debt).toLocaleString()}</span>`;
                    
                    card.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold fs-6">${s.sale_date} | ${s.type_name}</span>
                            ${statusText}
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="small text-muted">
                                ${s.unit_type === 'weight' ? (s.weight_kg + ' كجم') : (s.quantity_units + ' حبة')} | السعر: ${Number(s.price).toLocaleString()}
                            </div>
                            <div class="fw-bold text-primary">${Number(s.price).toLocaleString()} ريال</div>
                        </div>
                    `;
                    card.onclick = () => {
                        document.querySelectorAll('.sale-item-card').forEach(n => n.classList.remove('selected'));
                        card.classList.add('selected');
                        document.getElementById('selectedSaleId').value = s.id;
                        document.getElementById('step2Next').disabled = false;
                        currentSaleRemDebt = parseFloat(s.remaining_debt);
                        currentSaleMaxWeight = parseFloat(s.weight_kg);
                        currentSaleMaxUnits = parseInt(s.quantity_units);
                        prepareReturnData(s);

                        // Proactive Debt Check
                        const debtWarning = document.getElementById('debtWarningText');
                        if (currentCustomerTotalDebt < 0.1) {
                            debtWarning.innerText = "⚠️ تنبيه: العميل ليس عليه دين حالياً. يجب اختيار 'استرجاع نقدي'.";
                            debtWarning.classList.remove('d-none');
                        } else {
                            debtWarning.classList.add('d-none');
                        }
                    };
                    list.appendChild(card);
                });
            });
    }

    function prepareReturnData(sale) {
        // Show/Hide inputs based on sale type
        document.getElementById('weightGroup').style.display = sale.unit_type === 'weight' ? 'block' : 'none';
        document.getElementById('unitGroup').style.display = sale.unit_type !== 'weight' ? 'block' : 'none';
        
        // Auto-fill defaults with FULL price/quantity
        document.getElementById('retWeight').value = sale.unit_type === 'weight' ? sale.weight_kg : 0;
        document.getElementById('retUnits').value = sale.unit_type !== 'weight' ? sale.quantity_units : 0;
        
        const maxAmount = parseFloat(sale.price) - parseFloat(sale.refund_amount || 0);
        const originalWeight = parseFloat(sale.weight_kg);
        const originalUnits = parseInt(sale.quantity_units);
        
        const retAmountEl = document.getElementById('retAmount');
        retAmountEl.value = maxAmount;
        retAmountEl.max = maxAmount; // Set max for browser validation

        document.getElementById('maxAmountTip').textContent = `* الحد الأقصى للمبلغ: ${Number(maxAmount).toLocaleString()} ريال`;
        document.getElementById('retReason').value = `مرتجع من فاتورة ${sale.sale_date}`;

        // Auto-calculate amount proportionally if user changes weight/units
        document.getElementById('retWeight').oninput = function() {
            if (originalWeight > 0) {
                let current = parseFloat(this.value) || 0;
                if (current > originalWeight) current = originalWeight;
                retAmountEl.value = Math.round((current / originalWeight) * parseFloat(sale.price));
            }
        };

        document.getElementById('retUnits').oninput = function() {
            if (originalUnits > 0) {
                let current = parseInt(this.value) || 0;
                if (current > originalUnits) current = originalUnits;
                retAmountEl.value = Math.round((current / originalUnits) * parseFloat(sale.price));
            }
        };

        // Debt validation: If no remaining debt on this sale, force Cash refund
        const rtDebt = document.getElementById('rtDebt');
        const rtCash = document.getElementById('rtCash');
        const remDebt = parseFloat(sale.remaining_debt);

        if (remDebt <= 1) {
            rtDebt.disabled = true;
            rtCash.checked = true;
            rtDebt.nextElementSibling.classList.add('text-muted');
            rtDebt.nextElementSibling.innerHTML = "خصم من الدين (غير متاح)";
        } else {
            rtDebt.disabled = false;
            rtDebt.checked = true;
            rtDebt.nextElementSibling.classList.remove('text-muted');
            rtDebt.nextElementSibling.innerHTML = "خصم من الدين";
        }
    }

    function goToStep(step) {
        document.querySelectorAll('.step-panel').forEach((p, i) => {
            p.classList.toggle('active', (i+1) === step);
            const ind = document.getElementById('ind' + (i+1));
            ind.classList.remove('active', 'done');
            if ((i+1) < step) ind.classList.add('done');
            if ((i+1) === step) ind.classList.add('active');
        });
    }

    document.getElementById('returnForm').onsubmit = function(e) {
        const refundType = document.querySelector('input[name="refund_type"]:checked').value;
        const amount = parseFloat(document.getElementById('retAmount').value);
        const weight = parseFloat(document.getElementById('retWeight').value || 0);
        const units = parseInt(document.getElementById('retUnits').value || 0);

        // 1. Ensure some inventory is being returned
        if (weight <= 0 && units <= 0) {
            alert("يرجى تحديد الكمية المرتجعة (وزن أو عدد)");
            e.preventDefault();
            return false;
        }

        // 2. Quantity check against original sale
        if (weight > currentSaleMaxWeight + 0.001 || units > currentSaleMaxUnits) {
            alert(`خطأ: الكمية المرتجعة أكبر من الكمية المباعة أصلاً (${currentSaleMaxWeight || currentSaleMaxUnits})`);
            e.preventDefault();
            return false;
        }

        // 3. Debt validation
        if (refundType === 'Debt') {
            if (amount > currentSaleRemDebt + 0.1) {
                alert("المبلغ المدخل أكبر من المديونية المتبقية على هذه الفاتورة. يرجى اختيار 'استرجاع نقدي' وليس من الدين.");
                e.preventDefault();
                return false;
            }
        }
        
        return true;
    };
</script>

<?php include 'includes/footer.php'; ?>
