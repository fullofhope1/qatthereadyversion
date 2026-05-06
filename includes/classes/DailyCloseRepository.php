<?php
// includes/classes/DailyCloseRepository.php

class DailyCloseRepository extends BaseRepository
{
    /**
     * STEP 1: Identification of ANY active leftover items that must be cleared/trashed.
     * We look for EVERYTHING currently active in 'Momsi' or 'Leftover' state 
     * to ensure a fresh start.
     */
    public function getActiveMomsiStock()
    {
        // Legacy support: identify any leftover status in purchases table that wasn't closed
        $sql = "SELECT id, qat_type_id, quantity_kg, received_units, unit_type, purchase_date FROM purchases 
                WHERE status = 'Momsi'";
        return $this->fetchAll($sql);
    }

    public function getActiveManualLeftovers()
    {
        // Identify ALL active leftovers to be transitioned or trashed
        $sql = "SELECT id, purchase_id, qat_type_id, weight_kg, quantity_units, unit_type, source_date, status 
                FROM leftovers 
                WHERE status IN ('Transferred_Next_Day', 'Auto_Momsi', 'Momsi_Day_1', 'Momsi_Day_2')";
        return $this->fetchAll($sql);
    }

    public function markAsMomsiDay2($id, $tomorrowDate)
    {
        return $this->execute("UPDATE leftovers SET status = 'Momsi_Day_2', sale_date = ? WHERE id = ?", [$tomorrowDate, $id]);
    }

    public function trashLeftover($leftoverId, $currentDate)
    {
        // Get details for waste recording
        $stmt = $this->pdo->prepare("SELECT * FROM leftovers WHERE id = ?");
        $stmt->execute([$leftoverId]);
        $l = $stmt->fetch();
        if (!$l) return;

        // Calculate surplus (what remains)
        $surplusKg = 0;
        $surplusUnits = 0;
        if ($l['unit_type'] === 'weight') {
            $sold = (float)$this->fetchColumn(
                "SELECT COALESCE(SUM(COALESCE(weight_kg, weight_grams/1000) - COALESCE(returned_kg, 0)), 0) FROM sales WHERE leftover_id = ? AND is_returned = 0",
                [$leftoverId]
            ) ?: 0;
            $surplusKg = max(0, (float)$l['weight_kg'] - $sold);
        } else {
            $sold = (int)$this->fetchColumn(
                "SELECT COALESCE(SUM(quantity_units - COALESCE(returned_units, 0)), 0) FROM sales WHERE leftover_id = ? AND is_returned = 0",
                [$leftoverId]
            ) ?: 0;
            $surplusUnits = max(0, (int)$l['quantity_units'] - $sold);
        }

        if ($surplusKg > 0.001 || $surplusUnits > 0) {
            // Record as Talf (Auto_Dropped)
            $this->pdo->prepare("INSERT INTO leftovers (source_date, purchase_id, qat_type_id, weight_kg, quantity_units, unit_type, status, decision_date, sale_date) 
                                 VALUES (?, ?, ?, ?, ?, ?, 'Auto_Dropped', ?, ?)")
                ->execute([$l['source_date'], $l['purchase_id'], $l['qat_type_id'], $surplusKg, $surplusUnits, $l['unit_type'], $currentDate, $currentDate]);
        }

        // Close the record as 'Processed' to avoid double-counting its original weight as waste.
        // The actual waste (surplus) has already been recorded as 'Auto_Dropped' above.
        return $this->pdo->prepare("UPDATE leftovers SET status = 'Closed' WHERE id = ?")->execute([$leftoverId]);
    }

    public function trashMomsiPurchase($purchaseId, $currentDate)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM purchases WHERE id = ?");
        $stmt->execute([$purchaseId]);
        $p = $stmt->fetch();
        if (!$p) return;

        if ($p['unit_type'] === 'weight') {
            $stmtW = $this->pdo->prepare("SELECT 
                (SELECT SUM(COALESCE(weight_kg, weight_grams/1000) - COALESCE(returned_kg, 0)) FROM sales WHERE purchase_id = ? AND is_returned = 0) as sold,
                (SELECT SUM(weight_kg) FROM leftovers WHERE purchase_id = ? AND status IN ('Dropped', 'Transferred_Next_Day', 'Auto_Momsi', 'Momsi_Day_1', 'Momsi_Day_2')) as managed");
            $stmtW->execute([$purchaseId, $purchaseId]);
            $row = $stmtW->fetch();
            $surplus = (float)$p['quantity_kg'] - (float)($row['sold'] ?? 0) - (float)($row['managed'] ?? 0);
            $surplusUnits = 0;
        } else {
            $stmtU = $this->pdo->prepare("SELECT 
                (SELECT SUM(quantity_units - COALESCE(returned_units, 0)) FROM sales WHERE purchase_id = ? AND is_returned = 0) as sold,
                (SELECT SUM(quantity_units) FROM leftovers WHERE purchase_id = ? AND status IN ('Dropped', 'Transferred_Next_Day', 'Auto_Momsi', 'Momsi_Day_1', 'Momsi_Day_2')) as managed");
            $stmtU->execute([$purchaseId, $purchaseId]);
            $row = $stmtU->fetch();
            $surplusUnits = (int)$p['received_units'] - (int)($row['sold'] ?? 0) - (int)($row['managed'] ?? 0);
            $surplus = 0;
        }

        if ($surplus > 0.001 || $surplusUnits > 0) {
            // Record as Waste
            $sql = "INSERT INTO leftovers (source_date, purchase_id, qat_type_id, weight_kg, quantity_units, unit_type, status, decision_date, sale_date) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Auto_Dropped', ?, ?)";
            $this->pdo->prepare($sql)->execute([
                $p['purchase_date'],
                $purchaseId,
                $p['qat_type_id'],
                $surplus,
                $surplusUnits,
                $p['unit_type'],
                $currentDate,
                $currentDate
            ]);
        }

        // Close it
        return $this->pdo->prepare("UPDATE purchases SET status = 'Closed' WHERE id = ?")->execute([$purchaseId]);
    }

    /**
     * STEP 2: Moving today's surplus Fresh stock.
     */
    public function getDayFreshStock($currentDate, $forceAll = false)
    {
        if ($forceAll) {
            $stmt = $this->pdo->prepare("SELECT id, qat_type_id, quantity_kg, received_units, unit_type, purchase_date FROM purchases 
                                        WHERE (status = 'Fresh' OR status IS NULL OR status = '')");
            $stmt->execute();
        } else {
            $stmt = $this->pdo->prepare("SELECT id, qat_type_id, quantity_kg, received_units, unit_type, purchase_date FROM purchases 
                                        WHERE (status = 'Fresh' OR status IS NULL OR status = '') AND purchase_date <= ?");
            $stmt->execute([$currentDate]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSoldAndManagedForPurchase($purchaseId)
    {
        $stmt = $this->pdo->prepare("SELECT 
            (SELECT COALESCE(SUM(COALESCE(weight_kg, weight_grams/1000) - COALESCE(returned_kg, 0)), 0) FROM sales WHERE purchase_id = ? AND is_returned = 0) as sold_kg,
            (SELECT COALESCE(SUM(weight_kg), 0) FROM leftovers WHERE purchase_id = ? AND status IN ('Dropped', 'Auto_Dropped', 'Transferred_Next_Day', 'Auto_Momsi', 'Momsi_Day_1', 'Momsi_Day_2', 'Closed')) as managed_kg,
            (SELECT COALESCE(SUM(quantity_units - COALESCE(returned_units, 0)), 0) FROM sales WHERE purchase_id = ? AND is_returned = 0) as sold_units,
            (SELECT COALESCE(SUM(quantity_units), 0) FROM leftovers WHERE purchase_id = ? AND status IN ('Dropped', 'Auto_Dropped', 'Transferred_Next_Day', 'Auto_Momsi', 'Momsi_Day_1', 'Momsi_Day_2', 'Closed')) as managed_units");
        $stmt->execute([$purchaseId, $purchaseId, $purchaseId, $purchaseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'sold_kg'      => $row['sold_kg'] ?: 0,
            'managed_kg'   => $row['managed_kg'] ?: 0,
            'sold_units'   => $row['sold_units'] ?: 0,
            'managed_units'=> $row['managed_units'] ?: 0
        ];
    }

    public function moveStockToTomorrow($purchaseId, $typeId, $surplusKg, $surplusUnits, $unitType, $sourceDate, $saleDate)
    {
        // 1. Create entry in leftovers table as Momsi_Day_1
        $sqlL = "INSERT INTO leftovers (source_date, purchase_id, qat_type_id, weight_kg, quantity_units, unit_type, status, decision_date, sale_date) 
                 VALUES (?, ?, ?, ?, ?, ?, 'Momsi_Day_1', ?, ?)";
        $this->pdo->prepare($sqlL)->execute([
            $sourceDate, 
            $purchaseId, 
            $typeId, 
            $surplusKg, 
            $surplusUnits, 
            $unitType, 
            $sourceDate, 
            $saleDate
        ]);

        // 2. Close original purchase
        $this->pdo->prepare("UPDATE purchases SET status = 'Closed' WHERE id = ?")->execute([$purchaseId]);
    }

    /**
     * STEP 3: Debt Rollover
     * Moves all unpaid Daily debts to Deferred (مؤجل) status and updates due_date.
     */
    public function migrateDailyDebts($currentDate, $tomorrow, $forceAll = false)
    {
        if ($forceAll) {
            $sql = "UPDATE sales
                    SET due_date = ?, debt_type = 'Deferred'
                    WHERE payment_method = 'Debt'
                    AND (debt_type = 'Daily' OR debt_type IS NULL OR debt_type = '')
                    AND is_paid = 0";
            return $this->pdo->prepare($sql)->execute([$tomorrow]);
        } else {
            $sql = "UPDATE sales
                    SET due_date = ?, debt_type = 'Deferred'
                    WHERE due_date <= ? AND payment_method = 'Debt'
                    AND (debt_type = 'Daily' OR debt_type IS NULL OR debt_type = '')
                    AND is_paid = 0";
            return $this->pdo->prepare($sql)->execute([$tomorrow, $currentDate]);
        }
    }

    public function closeLegacyMomsiPurchases()
    {
        // Cleanup: Any purchase still in 'Momsi' status should be closed to avoid accounting confusion
        $sql = "UPDATE purchases SET status = 'Closed' WHERE status = 'Momsi'";
        return $this->pdo->prepare($sql)->execute();
    }

    public function closePurchase($purchaseId)
    {
        return $this->pdo->prepare("UPDATE purchases SET status = 'Closed' WHERE id = ?")->execute([$purchaseId]);
    }

    public function getActiveLeftoversForDate($date, $forceAll = false)
    {
        if ($forceAll) {
            $sql = "SELECT * FROM leftovers WHERE status NOT IN ('Dropped', 'Closed', 'Auto_Dropped')";
            return $this->fetchAll($sql);
        } else {
            $sql = "SELECT * FROM leftovers WHERE sale_date <= ? AND status NOT IN ('Dropped', 'Closed', 'Auto_Dropped')";
            return $this->fetchAll($sql, [$date]);
        }
    }
}
