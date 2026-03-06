<?php

/**
 * Automatically closes all days prior to (or including) a target date.
 * If no target date is provided, it closes everything up to Yesterday.
 */
function trigger_auto_closing($pdo, $targetDate = null)
{
    $limitDate = $targetDate ?: date('Y-m-d', strtotime('-1 day'));

    // Find the oldest date that has unclosed activity (up to $limitDate)
    // Using COALESCE to handle NULL purchase_date/sale_date by falling back to created_at
    $stmt = $pdo->prepare("SELECT MIN(d) FROM (
        SELECT MIN(COALESCE(purchase_date, DATE(created_at))) as d FROM purchases WHERE status IN ('Fresh', 'Momsi') AND (purchase_date <= ? OR (purchase_date IS NULL AND DATE(created_at) <= ?))
        UNION
        SELECT MIN(COALESCE(sale_date, DATE(created_at))) as d FROM sales WHERE payment_method = 'Debt' AND debt_type = 'Daily' AND is_paid = 0 AND (sale_date <= ? OR (sale_date IS NULL AND DATE(created_at) <= ?))
        UNION
        SELECT MIN(sale_date) as d FROM leftovers WHERE status = 'Transferred_Next_Day' AND sale_date <= ?
    ) as unclosed_dates");
    $stmt->execute([$limitDate, $limitDate, $limitDate, $limitDate, $limitDate]);

    $oldest_unclosed = $stmt->fetchColumn();

    if (!$oldest_unclosed || $oldest_unclosed > $limitDate) {
        return; // Everything is up to date relative to the limit
    }

    $current = $oldest_unclosed;
    while ($current <= $limitDate) {
        $tomorrow = date('Y-m-d', strtotime($current . ' +1 day'));

        try {
            $pdo->beginTransaction();

            // 0. AGGRESSIVE CLEANUP: Close anything strictly older than the current day being processed
            // This catches stray items that might have been missed in previous runs
            $pdo->prepare("UPDATE purchases SET status = 'Closed' WHERE (purchase_date < ? OR (purchase_date IS NULL AND DATE(created_at) < ?)) AND status IN ('Fresh', 'Momsi')")->execute([$current, $current]);

            // 1. Expire OLD leftovers for this specific date (move from Transferred/Auto_Momsi -> Dropped)
            // Only expire if they were intended for today or older
            $pdo->prepare("UPDATE leftovers SET status = 'Dropped' WHERE status IN ('Transferred_Next_Day', 'Auto_Momsi') AND sale_date <= ?")->execute([$current]);

            // 1.a Automatic 24h Debt Rollover (Daily to Deferred)
            // Anything 'Daily' older than 24 hours OR being closed today
            $rolloverSql = "UPDATE sales
                            SET debt_type = 'Deferred', sale_date = ?, due_date = ?
                            WHERE (sale_date = ? OR (sale_date IS NULL AND DATE(created_at) = ?) OR sale_date < ? OR (sale_date IS NULL AND DATE(created_at) < ?))
                            AND payment_method = 'Debt'
                            AND debt_type = 'Daily'
                            AND is_paid = 0";
            $pdo->prepare($rolloverSql)->execute([$tomorrow, $tomorrow, $current, $current, date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))]);

            // 2. Identify and move stock for $current
            $stmtPurchases = $pdo->prepare("SELECT id, qat_type_id, quantity_kg, status FROM purchases 
                                            WHERE (purchase_date = ? OR (purchase_date IS NULL AND DATE(created_at) = ?)) 
                                            AND status IN ('Fresh', 'Momsi')");
            $stmtPurchases->execute([$current, $current]);
            $dayPurchases = $stmtPurchases->fetchAll();

            foreach ($dayPurchases as $p) {
                // Calculate sold for this specific purchase
                $stmtSold = $pdo->prepare("SELECT SUM(weight_kg) FROM sales WHERE purchase_id = ?");
                $stmtSold->execute([$p['id']]);
                $sold = $stmtSold->fetchColumn() ?: 0;

                // Calculate explicitly managed leftovers (manually transferred, manually dropped, or already auto-managed)
                $stmtManaged = $pdo->prepare("SELECT SUM(weight_kg) FROM leftovers WHERE purchase_id = ? AND status IN ('Dropped', 'Transferred_Next_Day', 'Auto_Momsi', 'Auto_Dropped')");
                $stmtManaged->execute([$p['id']]);
                $managed = $stmtManaged->fetchColumn() ?: 0;

                $surplus = $p['quantity_kg'] - $sold - $managed;

                // Only FRESH purchases generate tomorrow's leftovers.
                // Momsi (leftover) stock is simply closed at end of day — it does NOT carry forward.
                if ($p['status'] === 'Fresh') {
                    if ($surplus > 0.001) {
                        // Check idempotency for this specific purchase in next day's Momsi
                        $check = $pdo->prepare("SELECT id FROM purchases WHERE status = 'Momsi' AND original_purchase_id = ? AND purchase_date = ?");
                        $check->execute([$p['id'], $tomorrow]);
                        $momsiId = $check->fetchColumn();

                        if ($momsiId) {
                            $pdo->prepare("UPDATE purchases SET quantity_kg = ?, received_weight_grams = ? WHERE id = ?")->execute([
                                $surplus,
                                $surplus * 1000,
                                $momsiId
                            ]);
                        } else {
                            // Create a 'Momsi' purchase for the next day to show as leftovers
                            $sqlMomsi = "INSERT INTO purchases (purchase_date, qat_type_id, quantity_kg, received_weight_grams, is_received, status, received_at, provider_id, original_purchase_id)
                                         SELECT ?, qat_type_id, ?, ?, 1, 'Momsi', ?, provider_id, id 
                                         FROM purchases WHERE id = ?";
                            $pdo->prepare($sqlMomsi)->execute([
                                $tomorrow,
                                $surplus,
                                $surplus * 1000,
                                $tomorrow . ' 00:00:01', // Set to tomorrow beginning
                                $p['id']
                            ]);
                        }

                        // Explicitly insert an 'Auto_Momsi' record in leftovers so it shows as handled in leftovers.php
                        $checkL = $pdo->prepare("SELECT id FROM leftovers WHERE purchase_id = ? AND status = 'Auto_Momsi' AND source_date = ?");
                        $checkL->execute([$p['id'], $current]);
                        $autoL = $checkL->fetchColumn();
                        if ($autoL) {
                            $pdo->prepare("UPDATE leftovers SET weight_kg = ? WHERE id = ?")->execute([$surplus, $autoL]);
                        } else {
                            $pdo->prepare("INSERT INTO leftovers (source_date, purchase_id, qat_type_id, weight_kg, status, decision_date, sale_date) VALUES (?, ?, ?, ?, 'Auto_Momsi', ?, ?)")->execute([
                                $current,
                                $p['id'],
                                $p['qat_type_id'],
                                $surplus,
                                $current,
                                $tomorrow
                            ]);
                        }
                    } else {
                        // Surplus is 0 or less, ensure no Momsi exists for tomorrow
                        $pdo->prepare("DELETE FROM purchases WHERE status = 'Momsi' AND original_purchase_id = ? AND purchase_date = ?")->execute([$p['id'], $tomorrow]);
                        $pdo->prepare("DELETE FROM leftovers WHERE status = 'Auto_Momsi' AND purchase_id = ? AND source_date = ?")->execute([$p['id'], $current]);
                    }
                } elseif ($p['status'] === 'Momsi') {
                    if ($surplus > 0.001) {
                        // Insert explicitly as Auto_Dropped for Momsi
                        $checkL = $pdo->prepare("SELECT id FROM leftovers WHERE purchase_id = ? AND status = 'Auto_Dropped' AND source_date = ?");
                        $checkL->execute([$p['id'], $current]);
                        $autoL = $checkL->fetchColumn();
                        if ($autoL) {
                            $pdo->prepare("UPDATE leftovers SET weight_kg = ? WHERE id = ?")->execute([$surplus, $autoL]);
                        } else {
                            $pdo->prepare("INSERT INTO leftovers (source_date, purchase_id, qat_type_id, weight_kg, status, decision_date, sale_date) VALUES (?, ?, ?, ?, 'Auto_Dropped', ?, ?)")->execute([
                                $current,
                                $p['id'],
                                $p['qat_type_id'],
                                $surplus,
                                $current,
                                $current
                            ]);
                        }
                    } else {
                        $pdo->prepare("DELETE FROM leftovers WHERE status = 'Auto_Dropped' AND purchase_id = ? AND source_date = ?")->execute([$p['id'], $current]);
                    }
                }

                // Close the purchase regardless of type (Fresh or Momsi)
                $pdo->prepare("UPDATE purchases SET status = 'Closed' WHERE id = ?")->execute([$p['id']]);
            }

            // 3. Automatic Debt Rollover for 'Daily' debts
            $rolloverSql = "UPDATE sales
                            SET sale_date = ?, due_date = ?
                            WHERE sale_date = ?
                            AND payment_method = 'Debt'
                            AND debt_type = 'Daily'
                            AND is_paid = 0";
            $pdo->prepare($rolloverSql)->execute([$tomorrow, $tomorrow, $current]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Auto-close failed for date $current: " . $e->getMessage());
            throw $e;
        }

        $current = $tomorrow;
    }
}
