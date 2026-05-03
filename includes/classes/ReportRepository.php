<?php
// includes/classes/ReportRepository.php

class ReportRepository extends BaseRepository
{

    private function getWhereAndParams($reportType, $date, $month, $year, $column)
    {
        if ($reportType === 'Monthly') {
            // Handle both YYYY-MM and MM formats with explicit casting to avoid MySQL interpretation issues
            if (strpos($month, '-') !== false) {
                return ["WHERE DATE_FORMAT($column, '%Y-%m') = CAST(? AS CHAR)", [$month]];
            } else {
                return ["WHERE MONTH($column) = ? AND YEAR($column) = ?", [$month, $year]];
            }
        } elseif ($reportType === 'Yearly') {
            return ["WHERE YEAR($column) = ?", [$year]];
        } else { // Daily
            return ["WHERE $column = ?", [$date]];
        }
    }

    public function getTotals($reportType, $date, $month, $year, $userId = null)
    {
        list($whereSales, $paramsSales) = $this->getWhereAndParams($reportType, $date, $month, $year, 'sale_date');
        list($wherePurch, $paramsPurch) = $this->getWhereAndParams($reportType, $date, $month, $year, 'purchase_date');
        list($whereExp, $paramsExp) = $this->getWhereAndParams($reportType, $date, $month, $year, 'expense_date');

        // Filter expenses by the current user to separate admin/super_admin data
        if ($userId !== null) {
            $whereExp .= " AND created_by = ?";
            $paramsExp[] = $userId;
        }

        $grossSales = $this->fetchColumn("SELECT SUM(price) FROM sales $whereSales AND is_returned = 0", $paramsSales) ?: 0;
        $totalRefunds = $this->fetchColumn("SELECT SUM(refund_amount) FROM sales $whereSales AND is_returned = 0", $paramsSales) ?: 0;
        $totalSales = $grossSales - $totalRefunds;

        // HIGH ACCURACY PROFIT METRICS
        $totalPurchases = $this->fetchColumn("SELECT SUM(net_cost - discount_amount) FROM purchases $wherePurch", $paramsPurch) ?: 0;
        $totalCogs = $this->calculateCogs($reportType, $date, $month, $year);
        $totalWasteValue = $this->calculateDroppedCost($reportType, $date, $month, $year);
        $totalExpenses = $this->fetchColumn("SELECT SUM(amount) FROM expenses $whereExp AND category != 'تسديد مورد'", $paramsExp) ?: 0;
        $totalProviderPayments = $this->fetchColumn("SELECT SUM(amount) FROM expenses $whereExp AND category = 'تسديد مورد'", $paramsExp) ?: 0;
        
        // FIX #10: Include compensations/refunds in profit calculation
        $totalCompensations = 0;
        try {
            list($whereRef, $paramsRef) = $this->getWhereAndParams($reportType, $date, $month, $year, 'DATE(created_at)');
            $totalCompensations = (float)$this->fetchColumn("SELECT SUM(amount) FROM refunds $whereRef AND (weight_kg = 0 AND quantity_units = 0)", $paramsRef) ?: 0;
        } catch (Exception $e) {}

        // Final Calculation: Sales - (Cost of what was sold) - (Cost of what was trashed today) - Expenses - Compensations
        $realProfit = $totalSales - $totalCogs - $totalWasteValue - $totalExpenses - $totalCompensations;

        // Current Inventory Value (What is currently in stock)
        $currentInventoryValue = $this->getInventoryValue();

        return [
            'gross_sales' => $grossSales,
            'total_refunds' => $totalRefunds,
            'total_sales' => $totalSales,
            'total_purchases' => $totalPurchases,
            'total_cogs' => $totalCogs,
            'total_waste_value' => $totalWasteValue,
            'total_expenses' => $totalExpenses,
            'total_provider_payments' => $totalProviderPayments,
            'real_profit' => $realProfit,
            'inventory_value' => $currentInventoryValue
        ];
    }

    public function getInventoryValue()
    {
        // Calculate value of remaining items in leftovers (not dropped)
        $sql = "SELECT SUM(
                    CASE 
                        WHEN l.unit_type = 'weight' THEN (l.weight_kg * p.price_per_kilo)
                        ELSE (l.quantity_units * p.price_per_unit)
                    END
                ) 
                FROM leftovers l
                JOIN purchases p ON l.purchase_id = p.id
                WHERE l.status NOT IN ('Dropped', 'Auto_Dropped')";
        return (float)$this->fetchColumn($sql) ?: 0;
    }

    private function calculateCogs($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 's.sale_date');
        
        $sql = "SELECT SUM(
                    CASE 
                        WHEN s.purchase_id IS NOT NULL THEN 
                            (CASE WHEN s.unit_type = 'weight' THEN ((s.weight_grams/1000 - COALESCE(s.returned_kg, 0)) * p.price_per_kilo) ELSE ((s.quantity_units - COALESCE(s.returned_units, 0)) * p.price_per_unit) END)
                        WHEN s.leftover_id IS NOT NULL THEN 
                            (CASE WHEN s.unit_type = 'weight' THEN ((s.weight_grams/1000 - COALESCE(s.returned_kg, 0)) * pl.price_per_kilo) ELSE ((s.quantity_units - COALESCE(s.returned_units, 0)) * pl.price_per_unit) END)
                        ELSE 0 
                    END
                ) as total_cost
                FROM sales s
                LEFT JOIN purchases p ON s.purchase_id = p.id
                LEFT JOIN leftovers l ON s.leftover_id = l.id
                LEFT JOIN purchases pl ON l.purchase_id = pl.id
                $where AND s.is_returned = 0";

        return (float)$this->fetchColumn($sql, $params) ?: 0;
    }

    private function calculateDroppedCost($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'l.decision_date');
        
        $sql = "SELECT SUM(
                    CASE 
                        WHEN l.unit_type = 'weight' THEN (l.weight_kg * p.price_per_kilo)
                        ELSE (l.quantity_units * p.price_per_unit)
                    END
                ) as total_waste_value
                FROM leftovers l
                JOIN purchases p ON l.purchase_id = p.id
                $where AND l.status IN ('Dropped', 'Auto_Dropped')";
        
        return (float)$this->fetchColumn($sql, $params) ?: 0;
    }

    public function getDebtStats()
    {
        return [
            'total_debt' => $this->fetchColumn("SELECT SUM(price - paid_amount - COALESCE(refund_amount, 0)) FROM sales WHERE is_paid = 0 AND is_returned = 0") ?: 0,
            'overdue_count' => $this->fetchColumn("SELECT COUNT(*) FROM sales WHERE is_paid = 0 AND due_date < CURDATE() AND is_returned = 0") ?: 0,
            'today_due' => $this->fetchColumn("SELECT SUM(price - paid_amount - COALESCE(refund_amount, 0)) FROM sales WHERE is_paid = 0 AND due_date = CURDATE() AND is_returned = 0") ?: 0,
            'tomorrow_due' => $this->fetchColumn("SELECT SUM(price - paid_amount - COALESCE(refund_amount, 0)) FROM sales WHERE is_paid = 0 AND due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND is_returned = 0") ?: 0
        ];
    }

    public function getRefunds($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'DATE(r.created_at)');

        $sql = "SELECT r.*, c.name as cust_name FROM refunds r LEFT JOIN customers c ON r.customer_id = c.id $where ORDER BY r.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getSalesList($reportType, $date, $month, $year, $providerId = null)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 's.sale_date');

        if ($providerId) {
            $where .= " AND COALESCE(p.provider_id, lp.provider_id) = ?";
            $params[] = $providerId;
        }

        // ✅ FIX: Join leftovers → purchases → providers so ممسي sales also show provider name
        $sql = "SELECT s.*,
                       c.name as cust_name,
                       t.name as type_name,
                       COALESCE(prov.name, lprov.name) as prov_name
                FROM sales s
                LEFT JOIN customers c    ON s.customer_id = c.id
                LEFT JOIN qat_types t    ON s.qat_type_id = t.id
                LEFT JOIN purchases p    ON s.purchase_id = p.id
                LEFT JOIN providers prov ON p.provider_id = prov.id
                LEFT JOIN leftovers l    ON s.leftover_id = l.id
                LEFT JOIN purchases lp   ON l.purchase_id = lp.id
                LEFT JOIN providers lprov ON lp.provider_id = lprov.id
                $where ORDER BY s.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getPurchasesList($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'p.purchase_date');
        $sql = "SELECT p.*, t.name as type_name, prov.name as prov_name, (p.net_cost - p.discount_amount) as final_cost 
                FROM purchases p 
                LEFT JOIN qat_types t ON p.qat_type_id = t.id 
                LEFT JOIN providers prov ON p.provider_id = prov.id
                $where ORDER BY p.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getExpensesList($reportType, $date, $month, $year, $userId = null)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'e.expense_date');

        // Filter expenses by user to separate admin/super_admin data
        if ($userId !== null) {
            $where .= " AND e.created_by = ?";
            $params[] = $userId;
        }

        $sql = "SELECT e.*, s.name as staff_name FROM expenses e LEFT JOIN staff s ON e.staff_id = s.id $where ORDER BY e.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getWasteList($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'l.sale_date');
        $sql = "SELECT l.*, t.name as type_name, prov.name as prov_name 
                FROM leftovers l 
                LEFT JOIN qat_types t ON l.qat_type_id = t.id
                LEFT JOIN purchases p ON l.purchase_id = p.id
                LEFT JOIN providers prov ON p.provider_id = prov.id
                $where AND l.status IN ('Dropped', 'Auto_Dropped') ORDER BY l.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getCashSummary($reportType, $date, $month, $year, $userId = null)
    {
        list($whereSales, $paramsSales) = $this->getWhereAndParams($reportType, $date, $month, $year, 'sale_date');
        list($wherePay, $paramsPay) = $this->getWhereAndParams($reportType, $date, $month, $year, 'payment_date');
        list($whereExp, $paramsExp) = $this->getWhereAndParams($reportType, $date, $month, $year, 'expense_date');

        // 1. Sales Breakdown
        $cashSales = (float)$this->fetchColumn("SELECT SUM(price) FROM sales $whereSales AND payment_method = 'Cash' AND is_returned = 0", $paramsSales) ?: 0;
        $transferSales = (float)$this->fetchColumn("SELECT SUM(price) FROM sales $whereSales AND payment_method NOT IN ('Cash', 'Debt') AND is_returned = 0", $paramsSales) ?: 0;
        
        // 2. Collections Breakdown
        $totalWaselCash = (float)$this->fetchColumn("SELECT SUM(amount) FROM payments $wherePay AND payment_method = 'Cash'", $paramsPay) ?: 0;
        $totalWaselTransfer = (float)$this->fetchColumn("SELECT SUM(amount) FROM payments $wherePay AND payment_method != 'Cash'", $paramsPay) ?: 0;

        // 3. Refunds & Compensations
        $totalRefunds = (float)$this->fetchColumn("SELECT SUM(refund_amount) FROM sales $whereSales AND is_returned = 0", $paramsSales) ?: 0;

        // Actual cash payouts from the refunds table (Items returned)
        list($whereRefunds, $paramsRefunds) = $this->getWhereAndParams($reportType, $date, $month, $year, 'DATE(created_at)');
        $totalCashRefunds = (float)$this->fetchColumn("SELECT SUM(amount) FROM refunds $whereRefunds AND refund_type = 'Cash' AND (weight_kg > 0 OR quantity_units > 0)", $paramsRefunds) ?: 0;
        
        // Compensations are refunds with 0 quantity (rebates) - only count Cash ones for drawer
        $totalCompensations = (float)$this->fetchColumn("SELECT SUM(amount) FROM refunds $whereRefunds AND (weight_kg = 0 AND quantity_units = 0) AND refund_type = 'Cash'", $paramsRefunds) ?: 0;

        // 4. Expenses
        if ($userId !== null) {
            $whereExp .= " AND created_by = ?";
            $paramsExp[] = $userId;
        }
        $totalExp = (float)$this->fetchColumn("SELECT SUM(amount) FROM expenses $whereExp AND category != 'تسديد مورد'", $paramsExp) ?: 0;
        $totalCashExp = (float)$this->fetchColumn("SELECT SUM(amount) FROM expenses $whereExp AND payment_method = 'Cash' AND category != 'تسديد مورد'", $paramsExp) ?: 0;
        $totalTransferExp = (float)$this->fetchColumn("SELECT SUM(amount) FROM expenses $whereExp AND payment_method != 'Cash' AND category != 'تسديد مورد'", $paramsExp) ?: 0;
        
        $totalProvPay = (float)$this->fetchColumn("SELECT SUM(amount) FROM expenses $whereExp AND category = 'تسديد مورد'", $paramsExp) ?: 0;
        $totalCashProvPay = (float)$this->fetchColumn("SELECT SUM(amount) FROM expenses $whereExp AND payment_method = 'Cash' AND category = 'تسديد مورد'", $paramsExp) ?: 0;

        // 5. Deposits
        $deposits = $this->getDepositsByCurrency($reportType, $date, $month, $year);

        // 6. Global Debt - calculated accurately from sales table
        $totalGlobalDebt = (float)$this->fetchColumn(
            "SELECT COALESCE(SUM(price - paid_amount - COALESCE(refund_amount, 0)), 0)
             FROM sales
             WHERE payment_method = 'Debt' AND is_returned = 0 AND is_paid = 0"
        ) ?: 0;

        return [
            'cash_sales' => $cashSales,
            'transfer_sales' => $transferSales,
            'wasel_cash' => $totalWaselCash,
            'wasel_transfer' => $totalWaselTransfer,
            'total_refunds' => $totalRefunds,
            'total_cash_refunds' => $totalCashRefunds,
            'total_compensations' => $totalCompensations,
            'total_expenses' => $totalExp,
            'total_cash_expenses' => $totalCashExp,
            'total_transfer_expenses' => $totalTransferExp,
            'total_provider_payments' => $totalProvPay,
            'total_cash_provider_payments' => $totalCashProvPay,
            'deposits_yer' => $deposits['YER'] ?? 0,
            'deposits_sar' => $deposits['SAR'] ?? 0,
            'deposits_usd' => $deposits['USD'] ?? 0,
            'total_global_debt' => $totalGlobalDebt
        ];
    }

    public function getWasteStats($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'source_date');
        $sql = "SELECT SUM(weight_kg) as total_weight, SUM(quantity_units) as total_units 
                FROM leftovers 
                $where AND status IN ('Dropped', 'Auto_Dropped')";
        return $this->fetchOne($sql, $params);
    }

    public function getDetailedPayments($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'payment_date');
        $sql = "SELECT p.*, c.name as customer_name 
                FROM payments p 
                JOIN customers c ON p.customer_id = c.id 
                $where ORDER BY p.payment_date DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getSalesBreakdown($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 's.sale_date');
        // ✅ FIX #2: Momsi classification uses leftover.status (reliable) instead of s.qat_status (manual/unreliable)
        $sql = "SELECT
                SUM(CASE WHEN s.payment_method = 'Cash' THEN s.price ELSE 0 END) as cash_sales,
                SUM(CASE WHEN s.payment_method = 'Debt' THEN s.price ELSE 0 END) as debt_sales,
                SUM(CASE WHEN s.payment_method NOT IN ('Cash', 'Debt') THEN s.price ELSE 0 END) as transfer_sales,
                SUM(CASE WHEN l.status = 'Momsi_Day_1' THEN s.price ELSE 0 END) as momsi1_sales,
                SUM(CASE WHEN l.status = 'Momsi_Day_2' THEN s.price ELSE 0 END) as momsi2_sales,
                COUNT(*) as total_invoices
                FROM sales s
                LEFT JOIN leftovers l ON s.leftover_id = l.id
                $where AND s.is_returned = 0";
        return $this->fetchOne($sql, $params);
    }


    public function getLeftoversBreakdown($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'source_date');

        $sql = "SELECT
                SUM(CASE WHEN status IN ('Dropped', 'Auto_Dropped') THEN weight_kg ELSE 0 END) as total_dropped_kg,
                SUM(CASE WHEN status = 'Dropped' THEN weight_kg ELSE 0 END) as manual_dropped_kg,
                SUM(CASE WHEN status = 'Auto_Dropped' THEN weight_kg ELSE 0 END) as auto_dropped_kg,
                SUM(CASE WHEN status IN ('Transferred_Next_Day', 'Auto_Momsi', 'Momsi_Day_1') THEN weight_kg ELSE 0 END) as moms_day1_kg,
                SUM(CASE WHEN status = 'Momsi_Day_2' THEN weight_kg ELSE 0 END) as moms_day2_kg
                FROM leftovers $where";
        return $this->fetchOne($sql, $params);
    }

    public function getDepositsByCurrency($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'deposit_date');
        $sql = "SELECT currency, SUM(amount) as total FROM qat_deposits $where GROUP BY currency";
        // FIX: fetchAll only accepts 2 args - use raw PDO for KEY_PAIR fetch mode
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function getDebtSalesList($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 's.sale_date');
        $sql = "SELECT s.*, c.name as cust_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id $where AND s.payment_method = 'Debt' AND s.is_returned = 0 ORDER BY s.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getTotalReceivables()
    {
        return $this->fetchColumn("SELECT SUM(total_debt) FROM customers") ?: 0;
    }


    public function getElectronicBalance()
    {
        $methods = "'Kuraimi Deposit', 'Jayb Deposit', 'Internal Transfer', 'Transfer'";
        
        $electronicSales = $this->fetchColumn("SELECT SUM(paid_amount - COALESCE(refund_amount, 0)) FROM sales WHERE payment_method IN ($methods) AND is_returned = 0") ?: 0;
        $electronicPayments = $this->fetchColumn("SELECT SUM(amount) FROM payments WHERE payment_method != 'Cash'") ?: 0;
        
        // Subtract bank deposits (outflow from electronic drawer to bank)
        $depositsOut = $this->fetchColumn("SELECT SUM(amount) FROM qat_deposits") ?: 0;
        
        // Subtract expenses paid via transfer
        $electronicExpenses = $this->fetchColumn("SELECT SUM(amount) FROM expenses WHERE payment_method = 'Transfer'") ?: 0;
        
        return $electronicSales + $electronicPayments - $depositsOut - $electronicExpenses;
    }

    public function getDepositsList($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'deposit_date');
        $sql = "SELECT * FROM qat_deposits $where ORDER BY deposit_date DESC, id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getUnknownTransfersList($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'transfer_date');
        $sql = "SELECT * FROM unknown_transfers $where ORDER BY transfer_date DESC, id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getLeftoversList($category, $reportType, $date, $month, $year)
    {
        list($whereDate, $paramsDate) = $this->getWhereAndParams($reportType, $date, $month, $year, 'l.source_date');
        
        $statusClause = "";
        if ($category === 'Day1') {
            $statusClause = "AND l.status IN ('Momsi_Day_1', 'Transferred_Next_Day', 'Auto_Momsi')";
        } elseif ($category === 'Day2') {
            $statusClause = "AND l.status = 'Momsi_Day_2'";
        } elseif ($category === 'Damaged') {
            $statusClause = "AND l.status IN ('Dropped', 'Auto_Dropped')";
        } else {
            return [];
        }

        $sql = "SELECT l.*, t.name as type_name, prov.name as prov_name 
                FROM leftovers l 
                LEFT JOIN qat_types t ON l.qat_type_id = t.id
                LEFT JOIN purchases p ON l.purchase_id = p.id
                LEFT JOIN providers prov ON p.provider_id = prov.id
                $whereDate $statusClause 
                ORDER BY l.source_date DESC, l.id DESC";
        
        return $this->fetchAll($sql, $paramsDate);
    }

    public function getCustomerStats($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 's.sale_date');
        
        $sql = "SELECT c.id, c.name, c.phone, 
                    COUNT(s.id) as total_sales_count,
                    SUM(s.price - COALESCE(s.refund_amount, 0)) as total_sale_amount,
                    SUM(CASE WHEN s.payment_method = 'Debt' THEN (s.price - s.paid_amount - COALESCE(s.refund_amount, 0)) ELSE 0 END) as remaining_debt
                FROM customers c
                JOIN sales s ON s.customer_id = c.id
                $where AND s.is_returned = 0
                GROUP BY c.id, c.name, c.phone
                ORDER BY total_sale_amount DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getStaffStats($reportType, $date, $month, $year, $userId = null)
    {
        // Build date filter for expenses (goes in the JOIN ON clause)
        if ($reportType === 'Monthly') {
            $dateFilter = "DATE_FORMAT(e.expense_date, '%Y-%m') = ?";
            $paramsExp  = [$month];
        } elseif ($reportType === 'Yearly') {
            $dateFilter = "YEAR(e.expense_date) = ?";
            $paramsExp  = [$year];
        } else {
            $dateFilter = "e.expense_date = ?";
            $paramsExp  = [$date];
        }

        // FIX #9: Inject date filter directly into LEFT JOIN ON clause (safe and clean)
        $sql = "SELECT s.id, s.name, s.role,
                    SUM(CASE WHEN e.category = 'Staff' THEN e.amount ELSE 0 END) as total_draws,
                    SUM(CASE WHEN e.category != 'Staff' THEN e.amount ELSE 0 END) as other_expenses,
                    COUNT(e.id) as expense_count
                FROM staff s
                LEFT JOIN expenses e ON e.staff_id = s.id AND {$dateFilter}";

        if ($userId !== null) {
            $sql .= " WHERE s.created_by = ?";
            $paramsExp[] = $userId;
        }

        $sql .= " GROUP BY s.id, s.name, s.role";
        return $this->fetchAll($sql, $paramsExp);
    }

    public function getProviderFinancialSummary()
    {
        $sql = "SELECT 
                    prov.id, 
                    prov.name, 
                    prov.phone,
                    COALESCE(purch.total_purchases, 0) as total_purchases,
                    COALESCE(pay.total_paid, 0) as total_paid,
                    (COALESCE(purch.total_purchases, 0) - COALESCE(pay.total_paid, 0)) as balance
                FROM providers prov
                LEFT JOIN (
                    SELECT provider_id, SUM(agreed_price) as total_purchases 
                    FROM purchases 
                    GROUP BY provider_id
                ) purch ON prov.id = purch.provider_id
                LEFT JOIN (
                    SELECT provider_id, SUM(amount) as total_paid 
                    FROM expenses 
                    WHERE provider_id IS NOT NULL
                    GROUP BY provider_id
                ) pay ON prov.id = pay.provider_id
                ORDER BY balance DESC";
        return $this->fetchAll($sql);
    }

    public function getProviderStatement($providerId)
    {
        $sql = "(SELECT purchase_date as op_date, 'Purchase' as type, agreed_price as amount, 0 as paid, 
                        CONCAT('شراء: ', q.name, ' (', p.quantity_kg, ' كجم)') as description
                 FROM purchases p
                 LEFT JOIN qat_types q ON p.qat_type_id = q.id
                 WHERE p.provider_id = ?)
                UNION ALL
                (SELECT expense_date as op_date, 'Payment' as type, 0 as amount, amount as paid, 
                        description
                 FROM expenses
                 WHERE provider_id = ?)
                ORDER BY op_date ASC";
        return $this->fetchAll($sql, [$providerId, $providerId]);
    }

    public function getStaffBalanceSummary()
    {
        $sql = "SELECT 
                    s.id, 
                    s.name, 
                    s.role,
                    s.daily_salary,
                    COALESCE(att.days_present, 0) * s.daily_salary as total_earned,
                    COALESCE(draws.total_withdrawn, 0) as total_withdrawn,
                    (COALESCE(att.days_present, 0) * s.daily_salary - COALESCE(draws.total_withdrawn, 0)) as balance
                FROM staff s
                LEFT JOIN (
                    SELECT staff_id, COUNT(*) as days_present 
                    FROM staff_attendance 
                    WHERE status = 'Present' 
                    GROUP BY staff_id
                ) att ON s.id = att.staff_id
                LEFT JOIN (
                    SELECT staff_id, SUM(amount) as total_withdrawn 
                    FROM expenses 
                    WHERE category = 'Staff'
                    GROUP BY staff_id
                ) draws ON s.id = draws.staff_id
                WHERE s.is_active = 1
                ORDER BY balance DESC";
        return $this->fetchAll($sql);
    }

    public function getStaffDetailedStatement($staffId)
    {
        $sql = "SELECT expense_date as op_date, amount, description, payment_method
                FROM expenses
                WHERE staff_id = ? AND category = 'Staff'
                ORDER BY expense_date ASC";
        return $this->fetchAll($sql, [$staffId]);
    }

    public function getShipmentPerformance($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'p.purchase_date');

        $sql = "SELECT 
                    p.id,
                    p.purchase_date,
                    pr.name as provider_name,
                    qt.name as qat_type,
                    p.expected_quantity_kg,
                    p.quantity_kg as received_kg,
                    (p.expected_quantity_kg - p.quantity_kg) as shortage_kg,
                    (p.net_cost - p.discount_amount) as total_cost,
                    COALESCE(s.total_revenue, 0) as total_revenue,
                    COALESCE(s.fresh_kg, 0) as fresh_kg,
                    COALESCE(s.momsi1_kg, 0) as momsi1_kg,
                    COALESCE(s.momsi2_kg, 0) as momsi2_kg,
                    COALESCE(w.waste_kg, 0) as waste_kg,
                    COALESCE(l.remaining_kg, 0) as remaining_kg,
                    (COALESCE(s.total_revenue, 0) - (p.net_cost - p.discount_amount)) as net_profit
                FROM purchases p
                JOIN providers pr ON p.provider_id = pr.id
                JOIN qat_types qt ON p.qat_type_id = qt.id
                LEFT JOIN (
                    SELECT 
                        s.purchase_id, 
                        SUM(s.price) as total_revenue,
                        SUM(CASE WHEN s.qat_status = 'Tari' THEN s.weight_grams/1000 ELSE 0 END) as fresh_kg,
                        SUM(CASE WHEN l.status = 'Momsi_Day_1' THEN s.weight_grams/1000 ELSE 0 END) as momsi1_kg,
                        SUM(CASE WHEN l.status = 'Momsi_Day_2' THEN s.weight_grams/1000 ELSE 0 END) as momsi2_kg
                    FROM sales s
                    LEFT JOIN leftovers l ON s.leftover_id = l.id
                    WHERE s.is_returned = 0
                    GROUP BY s.purchase_id
                ) s ON p.id = s.purchase_id
                LEFT JOIN (
                    SELECT purchase_id, SUM(weight_kg) as waste_kg
                    FROM leftovers
                    WHERE status IN ('Dropped', 'Auto_Dropped')
                    GROUP BY purchase_id
                ) w ON p.id = w.purchase_id
                LEFT JOIN (
                    SELECT purchase_id, SUM(weight_kg) as remaining_kg
                    FROM leftovers
                    WHERE status NOT IN ('Dropped', 'Auto_Dropped')
                    GROUP BY purchase_id
                ) l ON p.id = l.purchase_id
                $where
                ORDER BY p.purchase_date DESC";

        return $this->fetchAll($sql, $params);
    }
}
