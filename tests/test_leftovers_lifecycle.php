<?php
// tests/test_leftovers_lifecycle.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auto_close.php';

echo "Running Automated Tests for Leftovers Lifecycle...\n";
echo "------------------------------------------------\n\n";

try {
    // --- Helper Functions ---
    // This function is added to simulate user login for testing purposes.
    // It creates a user with the specified role and sub_role, then sets session variables.
    function runTestUserAs($username, $role, $sub_role = 'full')
    {
        global $pdo;
        // Start session if not already started (needed for $_SESSION)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $pw = password_hash('123', PASSWORD_DEFAULT);
        // Clean up any existing user with the same username
        $pdo->query("DELETE FROM users WHERE username = '$username'");
        // Insert the test user
        $pdo->query("INSERT INTO users (username, password, role, sub_role) VALUES ('$username', '$pw', '$role', '$sub_role')");
        $user_id = $pdo->lastInsertId();
        // Set session variables to simulate login
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['sub_role'] = $sub_role;
    }

    // Simulate an admin user for the test (if needed for auto_close logic)
    // The instruction implies these users should be created, though their direct use
    // in this specific test file's logic isn't immediately apparent from the original content.
    // Adding them here as per the instruction.
    runTestUserAs('test_sourcing_admin', 'admin', 'full');
    // Re-run for super_admin, this would overwrite the session from the previous call
    // if the test logic actually depended on the session user.
    runTestUserAs('test_sales_admin', 'super_admin', 'full');


    // 1. Setup Test Data (Day 1)
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    $pdo->exec("INSERT INTO providers (name, phone) VALUES ('Test Provider Auto', '0000000')");
    $providerId = $pdo->lastInsertId();

    $pdo->exec("INSERT INTO purchases (provider_id, qat_type_id, status, quantity_kg, agreed_price, net_cost, purchase_date, created_at) 
                VALUES ($providerId, 1, 'Fresh', 10, 1000, 1000, '$today', NOW())");
    $purchaseId = $pdo->lastInsertId();

    echo "[PASS] Setup: Created 10kg Fresh Purchase ($today)\n";

    // 2. Perform Day 1 Close
    trigger_auto_closing($pdo, $today);

    // Verify Momsi creation and Original Close
    $stmt = $pdo->query("SELECT status FROM purchases WHERE id = $purchaseId");
    $originalStatus = $stmt->fetchColumn();
    if ($originalStatus !== 'Closed') throw new Exception("Expected original purchase to be Closed, got $originalStatus");

    $stmt = $pdo->query("SELECT id, status FROM purchases WHERE original_purchase_id = $purchaseId AND status = 'Momsi'");
    $momsiPurchase = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$momsiPurchase) throw new Exception("Expected new Momsi purchase to be created, but none found.");


    $stmt = $pdo->query("SELECT id, status, sale_date FROM leftovers WHERE purchase_id = $purchaseId");
    $leftover = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$leftover || !in_array($leftover['status'], ['Pending', 'Transferred_Next_Day', 'Auto_Momsi'])) {
        throw new Exception("Leftover not created correctly or wrong status. Got: " . ($leftover ? $leftover['status'] : 'None'));
    }
    if ($leftover['sale_date'] !== $tomorrow) throw new Exception("Leftover sale_date is not tomorrow.");

    echo "[PASS] Day 1 Close: Fresh converted to Momsi for Tomorrow ($tomorrow)\n";

    // 3. Perform Day 2 Close (Expiry)
    trigger_auto_closing($pdo, $tomorrow);

    // Verify Momsi Expiration
    $stmt = $pdo->query("SELECT status FROM purchases WHERE id = " . $momsiPurchase['id']);
    $momsiFinalStatus = $stmt->fetchColumn();
    if ($momsiFinalStatus !== 'Closed') throw new Exception("Expected old Momsi purchase to be Closed, got $momsiFinalStatus");

    $stmt = $pdo->query("SELECT status FROM leftovers WHERE id = " . $leftover['id']);
    $finalLeftoverStatus = $stmt->fetchColumn();
    if (!in_array($finalLeftoverStatus, ['Dropped', 'Auto_Dropped'])) {
        throw new Exception("Expected leftover to be Auto_Dropped/Dropped, got $finalLeftoverStatus");
    }

    echo "[PASS] Day 2 Close: Momsi successfully expired (Dropped) and Purchase Closed.\n\n";
    echo "SUCCESS: The Leftovers Lifecycle (Strict 1-Day) is functioning correctly.\n";

    // Clean up test data
    $pdo->exec("DELETE FROM leftovers WHERE purchase_id = $purchaseId");
    $pdo->exec("DELETE FROM purchases WHERE id = $purchaseId OR original_purchase_id = $purchaseId");
    $pdo->exec("DELETE FROM providers WHERE id = $providerId");
} catch (Exception $e) {
    // Clean up on fail
    if (isset($purchaseId)) {
        $pdo->exec("DELETE FROM leftovers WHERE purchase_id = $purchaseId");
        $pdo->exec("DELETE FROM purchases WHERE id = $purchaseId OR original_purchase_id = $purchaseId");
    }
    if (isset($providerId)) {
        $pdo->exec("DELETE FROM providers WHERE id = $providerId");
    }

    echo "\n[FAIL] Test aborted due to error: " . $e->getMessage() . "\n";
    exit(1);
}
