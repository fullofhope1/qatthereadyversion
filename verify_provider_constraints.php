<?php
// verify_provider_constraints.php
require 'c:/xampp/htdocs/qat/config/db.php';

function test_add_provider($name, $phone)
{
    global $pdo;
    echo "Testing add provider: Name='$name', Phone='$phone'...\n";

    // Simulate POST request to requests/add_provider.php logic
    try {
        // Check for existing provider with same name
        $stmt = $pdo->prepare("SELECT id FROM providers WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            echo "Expected logic hit: Name '$name' already exists.\n";
            return;
        }

        // Check for existing provider with same phone
        $stmt = $pdo->prepare("SELECT id FROM providers WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            echo "Expected logic hit: Phone '$phone' already exists.\n";
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO providers (name, phone) VALUES (?, ?)");
        $stmt->execute([$name, $phone]);
        echo "SUCCESS: Provider added.\n";
    } catch (PDOException $e) {
        echo "DATABASE ERROR: " . $e->getMessage() . "\n";
    }
}

// 1. Get an existing provider to test against
$stmt = $pdo->query("SELECT * FROM providers LIMIT 1");
$existing = $stmt->fetch();

if (!$existing) {
    echo "No providers found to test duplicates. Adding one...\n";
    test_add_provider("Test Provider", "1234567890");
    $stmt = $pdo->query("SELECT * FROM providers LIMIT 1");
    $existing = $stmt->fetch();
}

echo "Testing duplicates against: " . $existing['name'] . " / " . $existing['phone'] . "\n\n";

// 2. Test duplicate name
test_add_provider($existing['name'], "9999999999");

// 3. Test duplicate phone
test_add_provider("Unique Name", $existing['phone']);

// 4. Test unique both
test_add_provider("Completely New Provider", "9876543210");
