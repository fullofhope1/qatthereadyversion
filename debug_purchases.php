<?php
require 'config/db.php';

// Check Purchases for today
$date = date('Y-m-d');
echo "<h3>Current Server Date: $date</h3>";

echo "<h4>All Purchases:</h4>";
$stmt = $pdo->query("SELECT * FROM purchases ORDER BY id DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'><tr><th>ID</th><th>Date</th><th>Type</th><th>Vendor</th><th>Net Cost</th></tr>";
foreach ($rows as $r) {
    echo "<tr>
            <td>{$r['id']}</td>
            <td>{$r['purchase_date']}</td>
            <td>{$r['qat_type_id']}</td>
            <td>{$r['vendor_name']}</td>
            <td>{$r['net_cost']}</td>
          </tr>";
}
echo "</table>";
