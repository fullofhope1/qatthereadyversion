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

    public function getTotals($reportType, $date, $month, $year, $userId = null, $role = 'super_admin')
    {
        list($whereSales, $paramsSales) = $this->getWhereAndParams($reportType, $date, $month, $year, 's.sale_date');
        list($wherePurch, $paramsPurch) = $this->getWhereAndParams($reportType, $date, $month, $year, 'p.purchase_date');
        list($whereExp, $paramsExp) = $this->getWhereAndParams($reportType, $date, $month, $year, 'e.expense_date');

        // Isolation Logic: Filter ALL metrics by the creator's role (Merchant team vs Supplier team)
        // Super Admin (Owner) can see Merchant (Super Admin/Sales/Staff), Supplier (Admin) data, and legacy records (NULL role)
        if ($role === 'super_admin') {
            // Super Admin (Merchant Team) sees only self and sellers (user role) for finance/staff
            $roleFilter = " AND (u.role IN ('super_admin', 'user') OR u.role IS NULL)";
            $whereSales .= $roleFilter;
            $whereExp .= $roleFilter;
            // Note: $wherePurch is NOT filtered for super_admin to ensure Merchant sees all inventory inputs
        } else {
            // Admin (Supplier Team) sees only their own data
            $roleFilter = " AND u.role = ?";
            $paramsSales[] = $role;
            $paramsPurch[] = $role;
            $paramsExp[] = $role;
            $whereSales .= $roleFilter;
            $wherePurch .= $roleFilter;
            $whereExp .= $roleFilter;
        }

        $grossSales = (float)$this->fetchColumn("SELECT SUM(s.price) FROM sales s LEFT JOIN users u ON s.created_by = u.id $whereSales AND s.is_returned = 0", $paramsSales) ?: 0;
        
        // FIX: Calculate refunds/compensations based on when they happened (today), not when the sale happened.
        list($whereRef, $paramsRef) = $this->getWhereAndParams($reportType, $date, $month, $year, 'DATE(r.created_at)');
        if ($role === 'super_admin') {
            $whereRef .= " AND (u.role IN ('super_admin', 'user') OR u.role IS NULL)";
        } else {
            $whereRef .= " AND u.role = ?";
            $paramsRef[] = $role;
        }
        $totalRefunds = (float)$this->fetchColumn("SELECT SUM(r.amount) FROM refunds r LEFT JOIN users u ON r.created_by = u.id $whereRef", $paramsRef) ?: 0;
        
        $totalSales = $grossSales - $totalRefunds;

        // HIGH ACCURACY PROFIT METRICS
        $totalPurchaseDiscounts = (float)$this->fetchColumn("SELECT SUM(p.discount_amount) FROM purchases p LEFT JOIN users u ON p.created_by = u.id $wherePurch", $paramsPurch) ?: 0;
        $totalPurchases = $this->fetchColumn("SELECT SUM(p.net_cost - p.discount_amount) FROM purchases p LEFT JOIN users u ON p.created_by = u.id $wherePurch", $paramsPurch) ?: 0;
        $totalCogs = $this->calculateCogs($reportType, $date, $month, $year, $role);
        $totalWasteValue = $this->calculateDroppedCost($reportType, $date, $month, $year, $role);
        $totalExpenses = $this->fetchColumn("SELECT SUM(e.amount) FROM expenses e LEFT JOIN users u ON e.created_by = u.id $whereExp AND e.category != 'تسديد مورد'", $paramsExp) ?: 0;
        $totalProviderPayments = $this->fetchColumn("SELECT SUM(e.amount) FROM expenses e LEFT JOIN users u ON e.created_by = u.id $whereExp AND e.category = 'تسديد مورد'", $paramsExp) ?: 0;
        
        // Final Calculation: Sales - (Cost of what was sold) - (Cost of what was trashed today) - Expenses
        $realProfit = $totalSales - $totalCogs - $totalWasteValue - $totalExpenses;


        // Current Inventory Value (What is currently in stock)
        $currentInventoryValue = $this->getInventoryValue();

        return [
            'gross_sales' => $grossSales,
            'total_refunds' => $totalRefunds,
            'total_sales' => $totalSales,
            'total_purchases' => $totalPurchases,
            'total_purchase_discounts' => $totalPurchaseDiscounts,
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
        $sql = "
            SELECT (
                -- 1. Value of Fresh Purchases (unsold portion)
                COALESCE((
                    SELECT SUM(
                        CASE 
                            WHEN p.unit_type = 'weight' THEN 
                                (p.quantity_kg - COALESCE(s.sold_kg, 0) - COALESCE(l.leftover_kg, 0)) * p.price_per_kilo
                            ELSE 
                                (p.received_units - COALESCE(s.sold_units, 0) - COALESCE(l.leftover_units, 0)) * p.price_per_unit
                        END
                    )
                    FROM purchases p
                    LEFT JOIN (
                        SELECT purchase_id, 
                               SUM(COALESCE(weight_kg, weight_grams/1000) - COALESCE(returned_kg, 0)) as sold_kg,
                               SUM(quantity_units - COALESCE(returned_units, 0)) as sold_units
                        FROM sales WHERE purchase_id IS NOT NULL AND is_returned = 0 GROUP BY purchase_id
                    ) s ON p.id = s.purchase_id
                    LEFT JOIN (
                        SELECT purchase_id, SUM(weight_kg) as leftover_kg, SUM(quantity_units) as leftover_units
                        FROM leftovers 
                        WHERE status != 'Reception_Loss' 
                        GROUP BY purchase_id
                    ) l ON p.id = l.purchase_id
                    WHERE p.is_received = 1 AND p.status IN ('Fresh', 'Momsi')
                ), 0)
                +
                -- 2. Value of Active Leftovers (unsold portion)
                COALESCE((
                    SELECT SUM(
                        CASE 
                            WHEN l.unit_type = 'weight' THEN 
                                (l.weight_kg - COALESCE(s.sold_kg, 0)) * p.price_per_kilo
                            ELSE 
                                (l.quantity_units - COALESCE(s.sold_units, 0)) * p.price_per_unit
                        END
                    )
                    FROM leftovers l
                    JOIN purchases p ON l.purchase_id = p.id
                    LEFT JOIN (
                        SELECT leftover_id, 
                               SUM(COALESCE(weight_kg, weight_grams/1000) - COALESCE(returned_kg, 0)) as sold_kg,
                               SUM(quantity_units - COALESCE(returned_units, 0)) as sold_units
                        FROM sales WHERE leftover_id IS NOT NULL AND is_returned = 0 GROUP BY leftover_id
                    ) s ON l.id = s.leftover_id
                    WHERE l.status IN ('Transferred_Next_Day', 'Auto_Momsi', 'Momsi_Day_1', 'Momsi_Day_2')
                ), 0)
            ) as total_value";
            
        return (float)$this->fetchColumn($sql) ?: 0;
    }

    private function calculateCogs($reportType, $date, $month, $year, $role = 'super_admin')
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 's.sale_date');
        if ($role === 'super_admin') {
            $where .= " AND (u.role IN ('super_admin', 'user') OR u.role IS NULL)";
        } else {
            $where .= " AND u.role = ?";
            $params[] = $role;
        }

        $sql = "SELECT SUM(
                    CASE 
                        WHEN s.purchase_id IS NOT NULL THEN 
                            (CASE 
                                WHEN s.unit_type = 'weight' THEN 
                                    ((s.weight_grams/1000 - COALESCE(s.returned_kg, 0)) * ((p.agreed_price - p.discount_amount) / NULLIF(p.quantity_kg, 0))) 
                                ELSE 
                                    ((s.quantity_units - COALESCE(s.returned_units, 0)) * ((p.agreed_price - p.discount_amount) / NULLIF(p.received_units, 0))) 
                            END)
                        WHEN s.leftover_id IS NOT NULL THEN 
                            (CASE 
                                WHEN s.unit_type = 'weight' THEN 
                                    ((s.weight_grams/1000 - COALESCE(s.returned_kg, 0)) * ((pl.agreed_price - pl.discount_amount) / NULLIF(pl.quantity_kg, 0))) 
                                ELSE 
                                    ((s.quantity_units - COALESCE(s.returned_units, 0)) * ((pl.agreed_price - pl.discount_amount) / NULLIF(pl.received_units, 0))) 
                            END)
                        ELSE 0 
                    END
                ) as total_cost
                FROM sales s
                LEFT JOIN users u ON s.created_by = u.id
                LEFT JOIN purchases p ON s.purchase_id = p.id
                LEFT JOIN leftovers l ON s.leftover_id = l.id
                LEFT JOIN purchases pl ON l.purchase_id = pl.id
                $where AND s.is_returned = 0";

        return (float)$this->fetchColumn($sql, $params) ?: 0;
    }

    private function calculateDroppedCost($reportType, $date, $month, $year, $role = 'super_admin')
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'l.decision_date');
        if ($role === 'super_admin') {
            $where .= " AND (u.role IN ('super_admin', 'user') OR u.role IS NULL)";
        } else {
            $where .= " AND u.role = ?";
            $params[] = $role;
        }
        
        $sql = "SELECT SUM(
                    CASE 
                        WHEN l.unit_type = 'weight' THEN 
                            (l.weight_kg * ((p.agreed_price - p.discount_amount) / NULLIF(p.quantity_kg, 0)))
                        ELSE 
                            (l.quantity_units * ((p.agreed_price - p.discount_amount) / NULLIF(p.received_units, 0)))
                    END
                ) as total_waste_value
                FROM leftovers l
                JOIN purchases p ON l.purchase_id = p.id
                LEFT JOIN users u ON l.created_by = u.id
                $where AND l.status IN ('Dropped', 'Auto_Dropped')";
        
        return (float)$this->fetchColumn($sql, $params) ?: 0;
    }

    public function getDebtStats()
    {
        return [
            'total_debt' => $this->getTotalReceivables(),
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

    public function getSalesList($reportType, $date, $month, $year, $providerId = null, $role = 'super_admin')
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 's.sale_date');
        
        if ($role === 'super_admin') {
            $where .= " AND (u.role IN ('super_admin', 'user') OR u.role IS NULL)";
        } else {
            $where .= " AND u.role = ?";
            $params[] = $role;
        }

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
                LEFT JOIN users u ON s.created_by = u.id
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

    public function getPurchasesList($reportType, $date, $month, $year, $role = 'super_admin')
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'p.purchase_date');
        
        if ($role !== 'super_admin') {
            $where .= " AND u.role = ?";
            $params[] = $role;
        }
        // super_admin sees all purchases regardless of who created them

        $sql = "SELECT p.*, t.name as type_name, prov.name as prov_name, u.username as creator_name, (p.net_cost - p.discount_amount) as final_cost 
                FROM purchases p 
                LEFT JOIN users u ON p.created_by = u.id
                LEFT JOIN qat_types t ON p.qat_type_id = t.id 
                LEFT JOIN providers prov ON p.provider_id = prov.id
                $where ORDER BY p.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getExpensesList($reportType, $date, $month, $year, $userId = null, $role = 'super_admin')
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'e.expense_date');

        if ($role === 'super_admin') {
            $where .= " AND (u.role IN ('super_admin', 'user') OR u.role IS NULL)";
        } else {
            $where .= " AND u.role = ?";
            $params[] = $role;
        }

        // Filter expenses by user ONLY for non-super_admin roles to maintain team isolation
        if ($role !== 'super_admin' && $userId !== null) {
            $where .= " AND e.created_by = ?";
            $params[] = $userId;
        }

        $sql = "SELECT e.*, s.name as staff_name, u.username as creator_name 
                FROM expenses e 
                LEFT JOIN users u ON e.created_by = u.id
                LEFT JOIN staff s ON e.staff_id = s.id 
                $where ORDER BY e.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getWasteList($reportType, $date, $month, $year, $role = 'super_admin')
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'l.sale_date');
        
        if ($role === 'super_admin') {
            $where .= " AND (u.role IN ('super_admin', 'user') OR u.role IS NULL)";
        } else {
            $where .= " AND u.role = ?";
            $params[] = $role;
        }

        $sql = "SELECT l.*, t.name as type_name, prov.name as prov_name, u.username as creator_name 
                FROM leftovers l 
                LEFT JOIN users u ON l.created_by = u.id
                LEFT JOIN qat_types t ON l.qat_type_id = t.id
                LEFT JOIN purchases p ON l.purchase_id = p.id
                LEFT JOIN providers prov ON p.provider_id = prov.id
                $where AND l.status IN ('Dropped', 'Auto_Dropped') ORDER BY l.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getCashSummary($reportType, $date, $month, $year, $userId = null, $role = 'super_admin')
    {
        list($whereSales, $paramsSales) = $this->getWhereAndParams($reportType, $date, $month, $year, 's.sale_date');
        list($wherePay, $paramsPay) = $this->getWhereAndParams($reportType, $date, $month, $year, 'p.payment_date');
        list($whereExp, $paramsExp) = $this->getWhereAndParams($reportType, $date, $month, $year, 'e.expense_date');
        list($whereDep, $paramsDep) = $this->getWhereAndParams($reportType, $date, $month, $year, 'd.deposit_date');
        list($whereRef, $paramsRef) = $this->getWhereAndParams($reportType, $date, $month, $year, 'DATE(r.created_at)');

        if ($role === 'super_admin') {
            $roleFilter = " AND (u.role IN ('super_admin', 'user') OR u.role IS NULL)";
        } else {
            $roleFilter = " AND u.role = ?";
            $paramsSales[] = $role;
            $paramsPay[] = $role;
            $paramsExp[] = $role;
            $paramsDep[] = $role;
            $paramsRef[] = $role;
        }

        $whereSales .= $roleFilter;
        $wherePay .= $roleFilter;
        $whereExp .= $roleFilter;
        $whereDep .= $roleFilter;
        $whereRef .= $roleFilter;

        // 1. Sales Breakdown
        $cashSales = (float)$this->fetchColumn("SELECT SUM(s.price) FROM sales s LEFT JOIN users u ON s.created_by = u.id $whereSales AND s.payment_method = 'Cash' AND s.is_returned = 0", $paramsSales) ?: 0;
        $transferSales = (float)$this->fetchColumn("SELECT SUM(s.price) FROM sales s LEFT JOIN users u ON s.created_by = u.id $whereSales AND s.payment_method NOT IN ('Cash', 'Debt') AND s.is_returned = 0", $paramsSales) ?: 0;
        
        // 2. Collections Breakdown
        $totalWaselCash = (float)$this->fetchColumn("SELECT SUM(p.amount) FROM payments p LEFT JOIN users u ON p.created_by = u.id $wherePay AND p.payment_method = 'Cash'", $paramsPay) ?: 0;
        $totalWaselTransfer = (float)$this->fetchColumn("SELECT SUM(p.amount) FROM payments p LEFT JOIN users u ON p.created_by = u.id $wherePay AND p.payment_method != 'Cash'", $paramsPay) ?: 0;

        // 3. Refunds & Compensations
        $totalRefunds = (float)$this->fetchColumn("SELECT SUM(s.refund_amount) FROM sales s LEFT JOIN users u ON s.created_by = u.id $whereSales AND s.is_returned = 0", $paramsSales) ?: 0;

        // Actual cash payouts from the refunds table (Items returned)
        $totalCashRefunds = (float)$this->fetchColumn("SELECT SUM(r.amount) FROM refunds r LEFT JOIN users u ON r.created_by = u.id $whereRef AND r.refund_type = 'Cash' AND (r.weight_kg > 0 OR r.quantity_units > 0)", $paramsRef) ?: 0;
        
        // Actual transfer/bank refunds
        $totalTransferRefunds = (float)$this->fetchColumn("SELECT SUM(r.amount) FROM refunds r LEFT JOIN users u ON r.created_by = u.id $whereRef AND r.refund_type != 'Cash' AND (r.weight_kg > 0 OR r.quantity_units > 0)", $paramsRef) ?: 0;

        // Compensations are refunds with 0 quantity (rebates) - count all types for the summary row
        $totalCompensations = (float)$this->fetchColumn("SELECT SUM(r.amount) FROM refunds r LEFT JOIN users u ON r.created_by = u.id $whereRef AND (r.weight_kg = 0 AND r.quantity_units = 0)", $paramsRef) ?: 0;

        
        // Transfer Compensations (Rebates given via transfer)
        $totalTransferCompensations = (float)$this->fetchColumn("SELECT SUM(r.amount) FROM refunds r LEFT JOIN users u ON r.created_by = u.id $whereRef AND (r.weight_kg = 0 AND r.quantity_units = 0) AND r.refund_type != 'Cash'", $paramsRef) ?: 0;


        // Filter expenses by user ONLY for non-super_admin roles to maintain team isolation
        if ($role !== 'super_admin' && $userId !== null) {
            $whereExp .= " AND e.created_by = ?";
            $paramsExp[] = $userId;
        }
        $totalExp = (float)$this->fetchColumn("SELECT SUM(e.amount) FROM expenses e LEFT JOIN users u ON e.created_by = u.id $whereExp AND e.category != 'تسديد مورد'", $paramsExp) ?: 0;
        $totalCashExp = (float)$this->fetchColumn("SELECT SUM(e.amount) FROM expenses e LEFT JOIN users u ON e.created_by = u.id $whereExp AND e.payment_method = 'Cash' AND e.category != 'تسديد مورد'", $paramsExp) ?: 0;
        $totalTransferExp = (float)$this->fetchColumn("SELECT SUM(e.amount) FROM expenses e LEFT JOIN users u ON e.created_by = u.id $whereExp AND e.payment_method != 'Cash' AND e.category != 'تسديد مورد'", $paramsExp) ?: 0;
        
        $totalProvPay = (float)$this->fetchColumn("SELECT SUM(e.amount) FROM expenses e LEFT JOIN users u ON e.created_by = u.id $whereExp AND e.category = 'تسديد مورد'", $paramsExp) ?: 0;
        $totalCashProvPay = (float)$this->fetchColumn("SELECT SUM(e.amount) FROM expenses e LEFT JOIN users u ON e.created_by = u.id $whereExp AND e.payment_method = 'Cash' AND e.category = 'تسديد مورد'", $paramsExp) ?: 0;

        // 5. Deposits
        $totalDepositsYER = (float)$this->fetchColumn("SELECT SUM(d.amount) FROM qat_deposits d LEFT JOIN users u ON d.created_by = u.id $whereDep AND d.currency = 'YER'", $paramsDep) ?: 0;

        // 6. Global Debt - calculated accurately (Opening Balances + Unpaid Sales - Payments)
        $totalGlobalDebt = $this->getTotalReceivables();
        
        // 7. Today's New Debt Sales (for daily reconciliation)
        $todayDebtSales = (float)$this->fetchColumn("SELECT SUM(s.price) FROM sales s LEFT JOIN users u ON s.created_by = u.id $whereSales AND s.payment_method = 'Debt' AND s.is_returned = 0", $paramsSales) ?: 0;

        return [
            'cash_sales' => $cashSales,
            'transfer_sales' => $transferSales,
            'wasel_cash' => $totalWaselCash,
            'wasel_transfer' => $totalWaselTransfer,
            'total_refunds' => $totalRefunds,
            'total_cash_refunds' => $totalCashRefunds,
            'total_transfer_refunds' => $totalTransferRefunds,
            'total_compensations' => $totalCompensations,
            'total_transfer_compensations' => $totalTransferCompensations,
            'total_expenses' => $totalExp,
            'total_cash_expenses' => $totalCashExp,
            'total_transfer_expenses' => $totalTransferExp,
            'total_provider_payments' => $totalProvPay,
            'total_cash_provider_payments' => $totalCashProvPay,
            'deposits_yer' => $totalDepositsYER,
            'total_global_debt' => $totalGlobalDebt,
            'today_debt_sales' => $todayDebtSales
        ];
    }

    public function getWasteStats($reportType, $date, $month, $year, $role = 'super_admin')
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'l.source_date');
        
        if ($role === 'super_admin') {
            $where .= " AND (u.role IN ('super_admin', 'user') OR u.role IS NULL)";
        } else {
            $where .= " AND u.role = ?";
            $params[] = $role;
        }

        $sql = "SELECT SUM(l.weight_kg) as total_weight, SUM(l.quantity_units) as total_units 
                FROM leftovers l
                LEFT JOIN users u ON l.created_by = u.id
                $where AND l.status IN ('Dropped', 'Auto_Dropped')";
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


    public function getLeftoversBreakdown($reportType, $date, $month, $year, $role = 'super_admin')
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'l.source_date');

        if ($role === 'super_admin') {
            $where .= " AND (u.role IN ('super_admin', 'user') OR u.role IS NULL)";
        } else {
            $where .= " AND u.role = ?";
            $params[] = $role;
        }

        $sql = "SELECT
                SUM(CASE WHEN l.status IN ('Dropped', 'Auto_Dropped') THEN l.weight_kg ELSE 0 END) as total_dropped_kg,
                SUM(CASE WHEN l.status = 'Dropped' THEN l.weight_kg ELSE 0 END) as manual_dropped_kg,
                SUM(CASE WHEN l.status = 'Auto_Dropped' THEN l.weight_kg ELSE 0 END) as auto_dropped_kg,
                SUM(CASE WHEN l.status IN ('Transferred_Next_Day', 'Auto_Momsi', 'Momsi_Day_1') THEN l.weight_kg ELSE 0 END) as moms_day1_kg,
                SUM(CASE WHEN l.status = 'Momsi_Day_2' THEN l.weight_kg ELSE 0 END) as moms_day2_kg
                FROM leftovers l
                LEFT JOIN users u ON l.created_by = u.id
                $where";
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
        $sql = "SELECT 
                    (SELECT SUM(COALESCE(opening_balance, 0) - COALESCE(paid_opening_balance, 0)) FROM customers) +
                    (SELECT SUM(price - paid_amount - COALESCE(refund_amount, 0)) FROM sales WHERE is_paid = 0 AND is_returned = 0)
                as total_due";
        return (float)$this->fetchColumn($sql) ?: 0;
    }


    public function getElectronicBalance()
    {
        $methods = "'Kuraimi Deposit', 'Jayb Deposit', 'Internal Transfer', 'Transfer'";
        
        $electronicSales = $this->fetchColumn("SELECT SUM(paid_amount) FROM sales WHERE payment_method IN ($methods) AND is_returned = 0") ?: 0;
        $electronicPayments = $this->fetchColumn("SELECT SUM(amount) FROM payments WHERE payment_method != 'Cash'") ?: 0;
        
        // Subtract bank deposits (outflow from electronic drawer to bank)
        $depositsOut = $this->fetchColumn("SELECT SUM(amount) FROM qat_deposits") ?: 0;
        
        // Subtract expenses paid via transfer
        $electronicExpenses = $this->fetchColumn("SELECT SUM(amount) FROM expenses WHERE payment_method = 'Transfer'") ?: 0;
        
        // Subtract refunds paid via transfer (FIX: This is more accurate than subtracting all sale refund_amounts)
        $transferRefunds = $this->fetchColumn("SELECT SUM(amount) FROM refunds WHERE refund_type = 'Transfer'") ?: 0;
        
        return $electronicSales + $electronicPayments - $depositsOut - $electronicExpenses - $transferRefunds;
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
        list($whereDate, $paramsDate) = $this->getWhereAndParams($reportType, $date, $month, $year, 'l.decision_date');
        
        $statusClause = "";
        if ($category === 'Day1') {
            $statusClause = "AND l.status IN ('Momsi_Day_1', 'Transferred_Next_Day', 'Auto_Momsi')";
        } elseif ($category === 'Day2') {
            $statusClause = "AND l.status = 'Momsi_Day_2'";
        } elseif ($category === 'Damaged') {
            // Include everything that isn't a transfer or reception loss
            $statusClause = "AND l.status NOT IN ('Momsi_Day_1', 'Momsi_Day_2', 'Transferred_Next_Day', 'Auto_Momsi', 'Reception_Loss')";
        } else {
            return [];
        }

        $sql = "SELECT l.*, t.name as type_name, prov.name as prov_name 
                FROM leftovers l 
                LEFT JOIN qat_types t ON l.qat_type_id = t.id
                LEFT JOIN purchases p ON l.purchase_id = p.id
                LEFT JOIN providers prov ON p.provider_id = prov.id
                $whereDate $statusClause 
                ORDER BY l.id DESC";
        
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

    public function getStaffStats($reportType, $date, $month, $year, $userId = null, $role = 'super_admin')
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
        // Filter staff members by their creator's role group
        if ($role === 'super_admin') {
            $sql = "SELECT s.id, s.name, s.role,
                        SUM(CASE WHEN e.category = 'Staff' THEN e.amount ELSE 0 END) as total_draws,
                        SUM(CASE WHEN e.category != 'Staff' THEN e.amount ELSE 0 END) as other_expenses,
                        COUNT(e.id) as expense_count
                    FROM staff s
                    JOIN users u ON s.created_by = u.id
                    LEFT JOIN expenses e ON e.staff_id = s.id AND {$dateFilter}
                    WHERE (u.role IN ('super_admin', 'user') OR u.role IS NULL)
                    GROUP BY s.id, s.name, s.role";
        } else {
            $sql = "SELECT s.id, s.name, s.role,
                        SUM(CASE WHEN e.category = 'Staff' THEN e.amount ELSE 0 END) as total_draws,
                        SUM(CASE WHEN e.category != 'Staff' THEN e.amount ELSE 0 END) as other_expenses,
                        COUNT(e.id) as expense_count
                    FROM staff s
                    JOIN users u ON s.created_by = u.id
                    LEFT JOIN expenses e ON e.staff_id = s.id AND {$dateFilter}
                    WHERE u.role = ?
                    GROUP BY s.id, s.name, s.role";
            $paramsExp[] = $role;
        }
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

    public function getStaffBalanceSummary($role = 'super_admin')
    {
        $params = [];
        if ($role === 'super_admin') {
            $roleFilter = " AND (u.role IN ('super_admin', 'user') OR u.role IS NULL)";
        } else {
            $roleFilter = " AND u.role = ?";
            $params[] = $role;
        }

        $sql = "SELECT 
                    s.id, 
                    s.name, 
                    s.role,
                    s.daily_salary,
                    COALESCE(att.days_present, 0) * s.daily_salary as total_earned,
                    COALESCE(draws.total_withdrawn, 0) as total_withdrawn,
                    (COALESCE(att.days_present, 0) * s.daily_salary - COALESCE(draws.total_withdrawn, 0)) as balance
                FROM staff s
                JOIN users u ON s.created_by = u.id
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
                WHERE s.is_active = 1 $roleFilter
                ORDER BY balance DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getStaffDetailedStatement($staffId)
    {
        $sql = "SELECT expense_date as op_date, amount, description, payment_method
                FROM expenses
                WHERE staff_id = ? AND category = 'Staff'
                ORDER BY expense_date ASC";
        return $this->fetchAll($sql, [$staffId]);
    }

    public function getShipmentPerformance($reportType, $date, $month, $year, $role = 'super_admin')
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'p.purchase_date');

        if ($role !== 'super_admin') {
            $where .= " AND u.role = ?";
            $params[] = $role;
        }
        // super_admin (Merchant) sees performance of all business shipments

        $sql = "SELECT 
                    p.id,
                    p.purchase_date,
                    pr.name as provider_name,
                    qt.name as qat_type,
                    (p.source_weight_grams / 1000.0) as expected_kg,
                    p.quantity_kg as received_kg,
                    ((p.source_weight_grams / 1000.0) - p.quantity_kg) as shortage_kg,
                    (p.net_cost - p.discount_amount) as total_cost,
                    COALESCE(s.total_revenue, 0) as total_revenue,
                    COALESCE(s.fresh_kg, 0) as fresh_kg,
                    COALESCE(s.momsi1_kg, 0) as momsi1_kg,
                    COALESCE(s.momsi2_kg, 0) as momsi2_kg,
                    COALESCE(w.waste_kg, 0) as waste_kg,
                    COALESCE(l.remaining_kg, 0) as remaining_kg,
                    (COALESCE(s.total_revenue, 0) - (p.net_cost - p.discount_amount)) as net_profit,
                    u.username as creator_name
                FROM purchases p
                LEFT JOIN users u ON p.created_by = u.id
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
                    WHERE status IN ('Dropped', 'Auto_Dropped', 'Staff_Consumption')
                    GROUP BY purchase_id
                ) w ON p.id = w.purchase_id
                LEFT JOIN (
                    SELECT purchase_id, SUM(weight_kg) as remaining_kg
                    FROM leftovers
                    WHERE status NOT IN ('Dropped', 'Auto_Dropped', 'Staff_Consumption')
                    GROUP BY purchase_id
                ) l ON p.id = l.purchase_id
                $where
                ORDER BY p.purchase_date DESC";

        return $this->fetchAll($sql, $params);
    }
}
