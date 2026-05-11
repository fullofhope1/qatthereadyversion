<?php
require_once '../config/db.php';
require_once '../includes/Autoloader.php';
require_once '../includes/require_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = (int)$_POST['customer_id'];
    $amount = (float)$_POST['amount'];
    $note = $_POST['note'] ?? '';
    $back = $_POST['back'] ?? 'customers';

    $debtRepo = new DebtRepository($pdo);
    $service = new DebtService($debtRepo);
    $customerRepo = new CustomerRepository($pdo);
    $customerService = new CustomerService($customerRepo);

    try {
        $cust = $customerService->getCustomer($customer_id);
        if (!$cust) {
            throw new Exception("العميل غير موجود");
        }

        if ($amount <= 0) {
            throw new Exception("المبلغ يجب أن يكون أكبر من صفر");
        }

        // ✅ FIX #1: Real-time debt — includes (Opening Balance + Unpaid Sales)
        $debtStmt = $pdo->prepare(
            "SELECT 
                (SELECT COALESCE(opening_balance, 0) - COALESCE(paid_opening_balance, 0) FROM customers WHERE id = ?) +
                (SELECT COALESCE(SUM(price - paid_amount - COALESCE(refund_amount, 0)), 0) FROM sales WHERE customer_id = ? AND is_paid = 0 AND is_returned = 0)
            as actual_debt"
        );
        $debtStmt->execute([$customer_id, $customer_id]);
        $realDebt = (float)$debtStmt->fetchColumn();


        if ($amount > $realDebt + 0.01) {
            throw new Exception("مبلغ السداد (" . number_format($amount) . ") أكبر من الدين الفعلي (" . number_format($realDebt) . ")");
        }

        $payment_method = $_POST['payment_method'] ?? 'Cash';
        $transferData = [];
        if ($payment_method === 'Transfer') {
            $transferData = [
                'sender' => $_POST['transfer_sender'] ?? '',
                'receiver' => $_POST['transfer_receiver'] ?? '',
                'number' => $_POST['transfer_number'] ?? '',
                'company' => $_POST['transfer_company'] ?? ''
            ];
        }

        if ($service->recordPayment($customer_id, $amount, $note, $payment_method, $transferData)) {
            header("Location: ../customer_details.php?id=$customer_id&back=$back&success=1");
            exit;
        }
    } catch (Exception $e) {
        $error = urlencode($e->getMessage());
        header("Location: ../customer_details.php?id=$customer_id&back=$back&pay_error=$error");
        exit;
    }
}
header("Location: ../index.php");
exit;
