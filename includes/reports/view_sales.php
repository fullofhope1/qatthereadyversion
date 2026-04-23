<style>
    .report-table-card {
        border-radius: 15px;
        overflow: hidden;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important;
    }

    .report-table thead {
        background: #f1f4f8;
    }

    .report-table th {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1.25rem 1rem;
        border: none;
        color: #495057;
    }

    .report-table td {
        padding: 1rem;
        border-color: #f1f4f8;
    }

    .badge-method {
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.7rem;
    }
</style>

<div class="card report-table-card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark">
            <i class="fas fa-shopping-cart me-2 text-primary"></i>
            سجل المبيعات التفصيلي
            <?php if ($provider_id): ?>
                <span class="badge bg-primary-subtle text-primary ms-2 rounded-pill small fw-normal">
                    الرعوي: <?= htmlspecialchars($listSales[0]['prov_name'] ?? 'محدد') ?>
                </span>
            <?php endif; ?>
        </h5>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-light text-muted fw-normal"><?= count($listSales) ?> عملية</span>
            <button onclick="window.print()" class="btn btn-sm btn-dark rounded-pill no-print">
                <i class="fas fa-print me-1"></i> طباعة
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Search box and Filters -->
        <div class="row p-3 pb-0 g-2">
            <div class="col-md-9">
                <input type="text" id="salesReportSearch" class="form-control" placeholder="بحث باسم العميل أو الرعوي..." oninput="filterSalesReport()">
            </div>
            <div class="col-md-3">
                <select id="salesMethodFilter" class="form-select" onchange="filterSalesReport()">
                    <option value="">كل طرق الدفع</option>
                    <option value="نقداً">نقداً</option>
                    <option value="آجل (دين)">آجل (دين)</option>
                    <option value="حوالة">حوالة</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>التاريخ والوقت</th>
                        <th>العميل</th>
                        <th>الرعوي</th>
                        <th>النوع</th>
                        <th class="text-center">الكمية / النوع</th>
                        <th class="text-end">السعر (ريال)</th>
                        <th class="text-center">طريقة الدفع</th>
                        <th class="text-center">الحالة</th>
                        <th class="text-center no-print">الإجراءت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalWeightKG = 0;
                    $totalPrice = 0;
                    $totalUnitsQabdah = 0;
                    $totalUnitsQartas = 0;
                    if (empty($listSales)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fs-1 d-block mb-3 opacity-25"></i>
                                لا توجد مبيعات في هذه الفترة.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        foreach ($listSales as $s) { 
                            $ut = $s['unit_type'] ?? 'weight';
                            $qty = (int)($s['quantity_units'] ?? 0);
                            $retQty = (int)($s['returned_units'] ?? 0);
                            $netQty = $qty - $retQty;
                            
                            $wKg = (float)($s['weight_kg'] ?? 0);
                            $wGrams = (float)($s['weight_grams'] ?? 0);
                            $retKg = (float)($s['returned_kg'] ?? 0);
                            $originalKg = $wKg > 0 ? $wKg : ($wGrams / 1000);
                            $netKg = max(0, $originalKg - $retKg);

                            $netPriceRaw = $s['price'] - ($s['refund_amount'] ?? 0);

                            if (!$s['is_returned']) {
                                $totalWeightKG += $netKg;
                                $totalPrice += $netPriceRaw;
                                if ($ut === 'قبضة') { $totalUnitsQabdah += $netQty; }
                                if ($ut === 'قراطيس') { $totalUnitsQartas += $netQty; }
                            }
                        ?>
                            <tr class="<?= $s['is_returned'] ? 'opacity-50 text-decoration-line-through table-secondary' : '' ?>"
                                data-kg="<?= $ut === 'weight' ? $netKg : 0 ?>"
                                data-qabdah="<?= $ut === 'قبضة' ? $netQty : 0 ?>"
                                data-qartas="<?= $ut === 'قراطيس' ? $netQty : 0 ?>"
                                data-price="<?= $netPriceRaw ?>">
                                <td><span class="text-muted small">#<?= $s['id'] ?></span></td>
                                <td>
                                    <div class="fw-bold"><?= getArabicDay($s['sale_date']) ?></div>
                                    <div class="small text-muted"><?= date('M d, H:i', strtotime($s['sale_date'])) ?></div>
                                </td>
                                <td>
                                    <?php if ($s['customer_id']): ?>
                                        <a href="customer_details.php?id=<?= $s['customer_id'] ?>&back=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="text-decoration-none">
                                            <div class="fw-bold text-primary"><?= htmlspecialchars($s['cust_name'] ?? 'عميل سفري') ?></div>
                                            <div class="text-muted small">ID: <?= $s['customer_id'] ?></div>
                                        </a>
                                    <?php else: ?>
                                        <div class="fw-bold"><?= htmlspecialchars($s['cust_name'] ?? 'عميل سفري') ?></div>
                                        <div class="text-muted small">ID: ---</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark fw-normal border"><?= htmlspecialchars($s['prov_name'] ?? '---') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle"><?= htmlspecialchars($s['type_name']) ?></span>
                                </td>
                                <?php
                                $ut = $s['unit_type'] ?? 'weight';
                                $qty = (int)($s['quantity_units'] ?? 0);
                                $retQty = (int)($s['returned_units'] ?? 0);
                                $netQty = $qty - $retQty;
                                
                                $wKg = (float)($s['weight_kg'] ?? 0);
                                $wGrams = (float)($s['weight_grams'] ?? 0);
                                $retKg = (float)($s['returned_kg'] ?? 0);
                                
                                // Handling legacy data where weight_kg might be 0 but weight_grams is not
                                $originalKg = $wKg > 0 ? $wKg : ($wGrams / 1000);
                                $netKg = max(0, $originalKg - $retKg);
                                ?>
                                <td class="text-center fw-bold">
                                    <?php if ($ut === 'weight'): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            <i class="fas fa-weight me-1"></i>
                                            <?= $netKg < 1 ? ($netKg * 1000) . ' ج' : $netKg . ' كجم' ?>
                                        </span>
                                        <?php if ($retKg > 0 && !$s['is_returned']): ?>
                                            <div class="small text-danger fw-normal" title="تم استرجاع وزن">
                                                -<?= $retKg < 1 ? ($retKg * 1000) .'ج' : $retKg .'كجم' ?> <i class="fas fa-undo fs-xs"></i>
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ($ut === 'قبضة'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="fas fa-hand-paper me-1"></i>
                                            قبضة × <?= $netQty ?>
                                        </span>
                                        <?php if ($retQty > 0 && !$s['is_returned']): ?>
                                            <div class="small text-danger fw-normal" title="تم استرجاع كمية">
                                                -<?= $retQty ?> <i class="fas fa-undo fs-xs"></i>
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ($ut === 'قراطيس'): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                            <i class="fas fa-box me-1"></i>
                                            قراطيس × <?= $netQty ?>
                                        </span>
                                        <?php if ($retQty > 0 && !$s['is_returned']): ?>
                                            <div class="small text-danger fw-normal" title="تم استرجاع كمية">
                                                -<?= $retQty ?> <i class="fas fa-undo fs-xs"></i>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted"><?= htmlspecialchars($ut) ?> × <?= $netQty ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    <?php
                                    $netPrice = $s['price'] - ($s['refund_amount'] ?? 0);
                                    echo number_format($netPrice);
                                    ?>
                                    <?php if (!empty($s['refund_amount']) && $s['refund_amount'] > 0 && !$s['is_returned']): ?>
                                        <div class="small text-danger fw-normal" title="يشمل تعويضات/استرجاع مالي">
                                            -<?= number_format($s['refund_amount']) ?> <i class="fas fa-undo fs-xs"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['payment_method'] === 'Cash'): ?>
                                        <span class="badge-method bg-success-subtle text-success">
                                            <i class="fas fa-money-bill-wave me-1"></i> نقداً
                                        </span>
                                    <?php elseif ($s['payment_method'] === 'Internal Transfer' || $s['payment_method'] === 'Transfer'): ?>
                                        <span class="badge-method bg-info-subtle text-info">
                                            <i class="fas fa-money-check-alt me-1"></i> حوالة
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-method bg-warning-subtle text-warning-emphasis">
                                            <i class="fas fa-hand-holding-usd me-1"></i> آجل (دين)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['payment_method'] === 'Debt'): ?>
                                        <?php if ($s['is_paid']): ?>
                                            <span class="text-success" title="مسدد"><i class="fas fa-check-circle fs-5"></i></span>
                                        <?php else: ?>
                                            <span class="text-danger" title="غير مسدد"><i class="fas fa-exclamation-circle fs-5"></i></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted opacity-50"><i class="fas fa-check"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center no-print">
                                    <?php if ($s['is_returned']): ?>
                                        <span class="badge bg-danger">مرتجع</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($listSales)): ?>
                    <tfoot class="bg-light fw-bold" style="border-top: 2px solid #dee2e6;">
                        <tr>
                            <td colspan="5" class="text-end py-3">الإجمالي (حسب التصفية):</td>
                            <td class="text-center py-3 small" id="tfoot_qty">
                                <?= number_format($totalWeightKG, 2) ?> كجم
                                <?php if ($totalUnitsQabdah > 0): ?>
                                    <br><span class="text-success">قبضة: <?= $totalUnitsQabdah ?></span>
                                <?php endif; ?>
                                <?php if ($totalUnitsQartas > 0): ?>
                                    <br><span class="text-warning-emphasis">قراطيس: <?= $totalUnitsQartas ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end py-3 text-primary h5 fw-bold" id="tfoot_price"><?= number_format($totalPrice) ?> ريال</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
    function filterSalesReport() {
        const term = document.getElementById('salesReportSearch').value.toLowerCase();
        const method = document.getElementById('salesMethodFilter').value;
        
        let activeKg = 0;
        let activeQabdah = 0;
        let activeQartas = 0;
        let activePrice = 0;

        document.querySelectorAll('.report-table tbody tr').forEach(row => {
            if (row.cells.length < 9) return; 
            
            const matchTerm = row.textContent.toLowerCase().includes(term);
            const methodCell = row.cells[7] ? row.cells[7].textContent.trim() : '';
            const matchMethod = method === '' || methodCell.includes(method);

            const isVisible = (matchTerm && matchMethod);
            row.style.display = isVisible ? '' : 'none';
            
            // Re-calculate totals if visible AND not returned
            if (isVisible && !row.classList.contains('text-decoration-line-through')) {
                activeKg += parseFloat(row.getAttribute('data-kg')) || 0;
                activeQabdah += parseInt(row.getAttribute('data-qabdah')) || 0;
                activeQartas += parseInt(row.getAttribute('data-qartas')) || 0;
                activePrice += parseFloat(row.getAttribute('data-price')) || 0;
            }
        });
        
        // Update footer UI
        const fbQty = document.getElementById('tfoot_qty');
        const fbPrice = document.getElementById('tfoot_price');
        
        if (fbQty && fbPrice) {
            let qtyHtml = activeKg.toFixed(2) + ' كجم';
            if (activeQabdah > 0) qtyHtml += '<br><span class="text-success">قبضة: ' + activeQabdah + '</span>';
            if (activeQartas > 0) qtyHtml += '<br><span class="text-warning-emphasis">قراطيس: ' + activeQartas + '</span>';
            
            fbQty.innerHTML = qtyHtml;
            fbPrice.innerHTML = activePrice.toLocaleString('en-US') + ' ريال';
        }
    }
</script>