<?php
// includes/classes/DailyCloseService.php

class DailyCloseService extends BaseService
{
    private $repository;
    private $debtRepo;

    public function __construct(DailyCloseRepository $repository, DebtRepository $debtRepo = null)
    {
        $this->repository = $repository;
        $this->debtRepo = $debtRepo;
    }

    public function closeDay($currentDate, $forceAll = false)
    {
        $tomorrow = date('Y-m-d', strtotime($currentDate . ' +1 day'));

        try {
            $this->repository->beginTransaction();

            // 1a. Transition leftovers that were active TODAY
            // Only leftovers meant to be sold on or before $currentDate should be moved forward or trashed (unless forceAll is used).
            $activeLeftovers = $this->repository->getActiveLeftoversForDate($currentDate, $forceAll);
            foreach ($activeLeftovers as $l) {
                if ($l['status'] === 'Momsi_Day_2') {
                    // It was already on its 2nd day today. End of cycle.
                    $this->repository->trashLeftover($l['id'], $currentDate);
                } elseif ($l['status'] === 'Momsi_Day_1' || $l['status'] === 'Transferred_Next_Day' || $l['status'] === 'Auto_Momsi') {
                    // It was Day 1 (or transferred to be Day1 today). Now it becomes Day 2 for tomorrow.
                    $this->repository->markAsMomsiDay2($l['id'], $tomorrow);
                }
            }

            // 1b. Handle legacy Momsi purchases that haven't been transitioned yet
            $legacyMomsi = $this->repository->getActiveMomsiStock();
            foreach ($legacyMomsi as $p) {
                $stats = $this->repository->getSoldAndManagedForPurchase($p['id']);
                $surplusKg = (float)$p['quantity_kg'] - (float)$stats['sold_kg'] - (float)$stats['managed_kg'];
                $surplusUnits = (int)($p['received_units'] ?? 0) - (int)$stats['sold_units'] - (int)$stats['managed_units'];

                if ($surplusKg > 0.001 || $surplusUnits > 0) {
                    $this->repository->moveStockToTomorrow(
                        $p['id'], 
                        $p['qat_type_id'], 
                        $surplusKg, 
                        $surplusUnits, 
                        $p['unit_type'], 
                        $currentDate, 
                        $tomorrow
                    );
                } else {
                    $this->repository->closePurchase($p['id']);
                }
            }
            
            // --- STEP 2: MOVE TODAY'S FRESH SURPLUS TO MOMSI DAY 1 ---
            $dayFresh = $this->repository->getDayFreshStock($currentDate, $forceAll);
            foreach ($dayFresh as $p) {
                $stats = $this->repository->getSoldAndManagedForPurchase($p['id']);
                $surplusKg = (float)$p['quantity_kg'] - (float)$stats['sold_kg'] - (float)$stats['managed_kg'];
                $surplusUnits = (int)($p['received_units'] ?? 0) - (int)$stats['sold_units'] - (int)$stats['managed_units'];

                if ($surplusKg > 0.001 || $surplusUnits > 0) {
                    // Move to Day 1 (Momsi_Day_1)
                    $this->repository->moveStockToTomorrow(
                        $p['id'], 
                        $p['qat_type_id'], 
                        $surplusKg, 
                        $surplusUnits, 
                        $p['unit_type'], 
                        $currentDate, 
                        $tomorrow
                    );
                }
                $this->repository->closePurchase($p['id']);
            }

            // --- STEP 3: MIGRATE DAILY DEBTS ---
            $this->repository->migrateDailyDebts($currentDate, $tomorrow, $forceAll);

            // --- STEP 4: FINAL CLEANUP ---
            // Ensure no legacy Momsi records remain in purchases table to avoid confusion
            $this->repository->closeLegacyMomsiPurchases();

            // --- STEP 5: DEBT RECONCILIATION (FIX #15) ---
            // Recalculate all customer debts from total unpaid sales to ensure absolute accuracy for the new day.
            if ($this->debtRepo) {
                $this->debtRepo->reconcileDebts();
            }

            $this->repository->commit();
            return true;
        } catch (Exception $e) {
            if ($this->repository->inTransaction()) {
                $this->repository->rollBack();
            }
            throw $e;
        }
    }
}
