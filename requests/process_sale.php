<?php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/error_page.php';
require_once '../includes/require_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $repo = new SaleRepository($pdo);
        $purchaseRepo = new PurchaseRepository($pdo);
        $customerRepo = new CustomerRepository($pdo);
        $leftoverRepo = new LeftoverRepository($pdo);
        $unitSalesService = new UnitSalesService($purchaseRepo, $leftoverRepo, $repo);
        $service = new SaleService($repo, $purchaseRepo, $customerRepo, $leftoverRepo, $unitSalesService);

        $data = [
            // FIX #5: Always use server-side date. Never trust POST for sale_date.
            'sale_date'        => date('Y-m-d'),
            'customer_id'      => !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
            'qat_type_id'      => (int)$_POST['qat_type_id'],
            'unit_type'        => $_POST['unit_type'] ?? 'weight',
            'purchase_id'      => !empty($_POST['purchase_id']) ? (int)$_POST['purchase_id'] : null,
            'leftover_id'      => !empty($_POST['leftover_id']) ? (int)$_POST['leftover_id'] : null,
            'qat_status'       => !empty($_POST['qat_status']) ? $_POST['qat_status'] : 'Tari',
            'weight_grams'     => (float)($_POST['weight_grams'] ?? 0),
            'quantity_units'   => (int)($_POST['quantity_units'] ?? 0),
            'price'            => (float)$_POST['price'],
            'payment_method'   => $_POST['payment_method'],
            'transfer_sender'  => !empty($_POST['transfer_sender'])  ? $_POST['transfer_sender']  : null,
            'transfer_receiver'=> !empty($_POST['transfer_receiver']) ? $_POST['transfer_receiver']: null,
            'transfer_number'  => !empty($_POST['transfer_number'])  ? $_POST['transfer_number']  : null,
            'transfer_company' => !empty($_POST['transfer_company'])  ? $_POST['transfer_company'] : null,
            'is_paid'          => ($_POST['payment_method'] === 'Debt') ? 0 : 1,
            'debt_type'        => ($_POST['payment_method'] === 'Debt') ? (!empty($_POST['debt_type']) ? $_POST['debt_type'] : 'Daily') : null,
            'notes'            => !empty($_POST['notes']) ? $_POST['notes'] : ''
        ];

        // FIX #12: Reject zero-price sales
        if ($data['price'] <= 0) {
            showErrorPage("سعر غير صالح", "لا يمكن إتمام البيع بسعر صفر أو أقل. يرجى إدخال السعر الصحيح.", "Price: " . $data['price']);
            exit;
        }

        $saleId = $service->processSale($data);

        $source = !empty($_POST['source_page']) ? $_POST['source_page'] : '';
        if ($source === 'leftovers_1') {
            header("Location: ../sales_leftovers_1.php?success=1&sale_id=" . $saleId);
        } elseif ($source === 'leftovers_2') {
            header("Location: ../sales_leftovers_2.php?success=1&sale_id=" . $saleId);
        } elseif ($data['leftover_id'] || $source === 'leftovers') {
            // Fallback if source wasn't explicitly 1 or 2
            // Let's guess sales_leftovers_1.php
            header("Location: ../sales_leftovers_1.php?success=1&sale_id=" . $saleId);
        } else {
            header("Location: ../sales.php?success=1&sale_id=" . $saleId);
        }
        exit;
    } catch (Exception $e) {
        $parts = explode('|', $e->getMessage());
        $errorCode = $parts[0];

        if ($errorCode === 'InventoryExceeded') {
            showErrorPage("عذراً، الكمية غير متوفرة", "لقد طلبت كمية أكبر من المخزون المتاح لهذا المورد.", "Available: {$parts[1]}kg <br> Requested: {$parts[2]}kg");
        } elseif ($errorCode === 'UnitSalesExceeded') {
            showErrorPage("عذراً، العدد غير متوفر", "لقد طلبت عدداً أكبر من المخزون المتاح لهذا المورد.", "Available: {$parts[1]} <br> Requested: {$parts[2]}");
        } elseif ($errorCode === 'LeftoverExceeded') {
            showErrorPage("عذراً، الكمية غير متوفرة (بقايا)", "الكمية المطلوبة من البقايا غير متوفرة.", "Available: {$parts[1]}kg <br> Requested: {$parts[2]}kg");
        } elseif ($errorCode === 'CreditLimitExceeded') {
            showErrorPage("تم تجاوز سقف الدين!", "لا يمكن إتمام العملية لأن الزبون تجاوز الحد المسموح للدين.", "Limit: " . number_format($parts[1]) . " YER <br> Current Debt: " . number_format($parts[2]) . " YER");
        } else {
            showErrorPage("حدث خطأ في النظام", "فشلت عملية البيع بسبب خطأ غير متوقع.", $e->getMessage());
        }
    }
}
