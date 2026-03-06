<?php
require_once '../config/db.php';

// Helper for User-Friendly Errors
function showError($title, $msg, $detail = '')
{
    echo '
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - خطأ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Tajawal", sans-serif;
        }

        .err-card {
            max-width: 500px;
            width: 90%;
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .err-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="card err-card p-5 text-center">
        <div class="err-icon"><i class="fas fa-exclamation-circle"></i></div>
        <h3 class="fw-bold text-danger mb-3">' . $title . '</h3>
        <p class="fs-5 mb-4 text-dark">' . $msg . '</p>
        ' . ($detail ? '<div class="alert alert-warning small py-2" dir="ltr">' . $detail . '</div>' : '') . '
        <button onclick="history.back()" class="btn btn-secondary btn-lg w-100 fw-bold">عودة للتصحيح (Back)</button>
    </div>
</body>

</html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Gather Data
        $sale_date = $_POST['sale_date'];
        $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
        $qat_type_id = $_POST['qat_type_id'];
        $purchase_id = !empty($_POST['purchase_id']) ? $_POST['purchase_id'] : null;
        $leftover_id = !empty($_POST['leftover_id']) ? $_POST['leftover_id'] : null;
        $qat_status = !empty($_POST['qat_status']) ? $_POST['qat_status'] : 'Tari'; // Default

        $weight_grams = (float)$_POST['weight_grams'];
        $price = (float)$_POST['price'];

        $payment_method = $_POST['payment_method'];
        $notes = !empty($_POST['notes']) ? $_POST['notes'] : ''; // Fixed Warning

        // Transfer Details
        $t_sender = !empty($_POST['transfer_sender']) ? $_POST['transfer_sender'] : null;
        $t_receiver = !empty($_POST['transfer_receiver']) ? $_POST['transfer_receiver'] : null;
        $t_number = !empty($_POST['transfer_number']) ? $_POST['transfer_number'] : null;
        $t_company = !empty($_POST['transfer_company']) ? $_POST['transfer_company'] : null;

        // Debt Type
        $debt_type = ($payment_method === 'Debt' && !empty($_POST['debt_type'])) ? $_POST['debt_type'] : null;

        // Determine if paid
        $is_paid = ($payment_method === 'Debt') ? 0 : 1;

        // 1.5 BACKEND INVENTORY CHECK (Strict Mode)
        // --- START ATOMIC TRANSACTION ---
        $pdo->beginTransaction();

        $weight_kg = $weight_grams / 1000;

        if ($purchase_id) {
            // Check Daily Stock (Fresh) WITH ROW LOCKING
            $stmt = $pdo->prepare("SELECT quantity_kg FROM purchases WHERE id = ? FOR UPDATE");
            $stmt->execute([$purchase_id]);
            $total_purchased = $stmt->fetchColumn() ?: 0;

            // Check what's already sold
            $stmt = $pdo->prepare("SELECT SUM(weight_kg) FROM sales WHERE purchase_id = ?");
            $stmt->execute([$purchase_id]);
            $total_sold = $stmt->fetchColumn() ?: 0;

            $available = round($total_purchased - $total_sold, 3);

            if ($weight_kg > $available) {
                $pdo->rollBack();
                // Friendly Arabic Error
                showError(
                    "عذراً، الكمية غير متوفرة",
                    "لقد طلبت كمية أكبر من المخزون المتاح لهذا المورد.",
                    "Available: {$available}kg <br> Requested: {$weight_kg}kg"
                );
            }
        } elseif ($leftover_id) {
            // Check Leftover Stock (Old) WITH ROW LOCKING
            $stmt = $pdo->prepare("SELECT weight_kg FROM leftovers WHERE id = ? FOR UPDATE");
            $stmt->execute([$leftover_id]);
            $total_leftover = $stmt->fetchColumn() ?: 0;

            // Check what's already sold from this leftover batch
            $stmt = $pdo->prepare("SELECT SUM(weight_kg) FROM sales WHERE leftover_id = ?");
            $stmt->execute([$leftover_id]);
            $total_sold = $stmt->fetchColumn() ?: 0;

            $available = round($total_leftover - $total_sold, 3);

            if ($weight_kg > $available) {
                $pdo->rollBack();
                showError(
                    "عذراً، الكمية غير متوفرة (بقايا)",
                    "الكمية المطلوبة من البقايا غير متوفرة.",
                    "Available: {$available}kg <br> Requested: {$weight_kg}kg"
                );
            }
        }

        // 1.5.1 CREDIT LIMIT CHECK (Strict)
        if ($payment_method === 'Debt' && $customer_id) {
            $stmt = $pdo->prepare("SELECT total_debt, debt_limit FROM customers WHERE id = ? FOR UPDATE");
            $stmt->execute([$customer_id]);
            $cust = $stmt->fetch();

            if ($cust) {
                $new_debt = $cust['total_debt'] + $price;
                // Only enforce limit if debt_limit is not null
                if ($cust['debt_limit'] !== null && $new_debt > $cust['debt_limit']) {
                    $pdo->rollBack();
                    // Get user-friendly numeric format
                    $limitFmt = number_format($cust['debt_limit']);
                    $currFmt = number_format($cust['total_debt']);

                    showError(
                        "تم تجاوز سقف الدين!",
                        "لا يمكن إتمام العملية لأن الزبون تجاوز الحد المسموح للدين.",
                        "Limit: $limitFmt YER <br> Current Debt: $currFmt YER"
                    );
                }
            }
        }

        // 2. Insert into Sales
        $sql = "INSERT INTO sales (sale_date, due_date, customer_id, qat_type_id, purchase_id, leftover_id, qat_status, weight_grams, price, payment_method, transfer_sender, transfer_receiver, transfer_number, transfer_company, is_paid, debt_type, notes)
                VALUES (:sDate, :sDate, :cust, :type, :pid, :lid, :status, :grams, :price, :method, :tsender, :treceiver, :tnum, :tcompany, :paid, :dtype, :notes)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':sDate' => $sale_date,
            ':cust' => $customer_id,
            ':type' => $qat_type_id,
            ':pid' => $purchase_id,
            ':lid' => $leftover_id,
            ':status' => $qat_status,
            ':grams' => $weight_grams,
            ':price' => $price,
            ':method' => $payment_method,
            ':tsender' => $t_sender,
            ':treceiver' => $t_receiver,
            ':tnum' => $t_number,
            ':tcompany' => $t_company,
            ':paid' => $is_paid,
            ':dtype' => $debt_type,
            ':notes' => $notes
        ]);

        // 3. Update Customer Debt (if Payment Method is Debt)
        if ($payment_method === 'Debt' && $customer_id) {
            $stmt = $pdo->prepare("UPDATE customers SET total_debt = total_debt + ? WHERE id = ?");
            $stmt->execute([$price, $customer_id]);
        }

        $pdo->commit();
        // --- END ATOMIC TRANSACTION ---

        // Redirect Back
        $source = !empty($_POST['source_page']) ? $_POST['source_page'] : '';
        if ($leftover_id || $source === 'leftovers') {
            header("Location: ../sales_leftovers.php?success=1");
        } else {
            header("Location: ../sales.php?success=1");
        }
        exit;
    } catch (PDOException $e) {
        showError("حدث خطأ في النظام", "فشلت عملية البيع بسبب خطأ في قاعدة البيانات.", $e->getMessage());
    }
}
