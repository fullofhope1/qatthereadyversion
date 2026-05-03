<?php
require_once 'config/db.php';
require_once 'includes/Autoloader.php';

$pRepo = new PurchaseRepository($pdo);
$sRepo = new SaleRepository($pdo);
$lRepo = new LeftoverRepository($pdo);
$uService = new UnitSalesService($pRepo, $lRepo, $sRepo);
$cRepo = new CustomerRepository($pdo);
$sService = new SaleService($sRepo, $pRepo, $cRepo, $lRepo, $uService);

echo "--- TESTING PAYMENT SAFETY FIX ---\n";

// Get initial debt
$cust = $cRepo->getById(1);
$initialDebt = (float)$cust['total_debt'];

// 1. Create a Purchase to sell from
$pId = $pRepo->create(['quantity_kg' => 10, 'provider_id' => 1, 'qat_type_id' => 1, 'is_received' => 1]);

// 2. Perform a 'Cash' sale with partial payment
echo "Attempting Cash sale of 5000 with only 3000 paid...\n";
$sService->processSale([
    'purchase_id' => $pId,
    'weight_grams' => 1000,
    'price' => 5000,
    'paid_amount' => 3000,
    'payment_method' => 'Cash',
    'customer_id' => 1,
    'sale_date' => date('Y-m-d')
]);

// 3. Verify Sale Record
$stmt = $pdo->prepare("SELECT payment_method FROM sales WHERE purchase_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$pId]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Final Payment Method in DB: {$sale['payment_method']}\n";

// 4. Verify Customer Debt
$cust = $cRepo->getById(1);
$finalDebt = (float)$cust['total_debt'];
echo "Initial Debt: $initialDebt\n";
echo "Final Debt: $finalDebt\n";
$debtIncrease = $finalDebt - $initialDebt;

if ($sale['payment_method'] === 'Debt' && $debtIncrease == 2000) {
    echo "✅ PASS: Partial cash sale was auto-converted to debt and balance was tracked.\n";
} else {
    echo "❌ FAIL: Logic error in payment safety!\n";
}

// Cleanup
$pdo->exec("DELETE FROM sales WHERE purchase_id = $pId");
$pdo->exec("DELETE FROM purchases WHERE id = $pId");
$cRepo->decrementDebt(1, $debtIncrease);
