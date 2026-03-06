<?php
require 'config/db.php';

echo "<h3>Updating Shipments to match new Timezone...</h3>";

// Update the last 2 shipments which were caught in the date rollover
$stmt = $pdo->query("SELECT id, purchase_date, received_at FROM purchases ORDER BY id DESC LIMIT 2");
$rows = $stmt->fetchAll();

foreach ($rows as $r) {
    if ($r['purchase_date'] == '2026-03-02') {
        $pdo->prepare("UPDATE purchases SET purchase_date = '2026-03-03', received_at = DATE_ADD(received_at, INTERVAL 11 HOUR) WHERE id = ?")
            ->execute([$r['id']]);
        echo "Updated Shipment #{$r['id']} to 2026-03-03.<br>";
    }
}

echo "Done. <a href='index.php'>Go to Homepage</a>";
