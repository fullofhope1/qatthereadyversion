<?php
// includes/classes/ReportRepository.php

class ReportRepository extends BaseRepository
{

    private function getWhereAndParams($reportType, $date, $month, $year, $column)
    {
        if ($reportType === 'Monthly') {
            return ["WHERE DATE_FORMAT($column, '%Y-%m') = ?", [$month]];
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

        $totalSales = $this->fetchColumn("SELECT SUM(price) FROM sales $whereSales", $paramsSales) ?: 0;
        $totalPurchases = $this->fetchColumn("SELECT SUM(net_cost) FROM purchases $wherePurch", $paramsPurch) ?: 0;
        $totalExpenses = $this->fetchColumn("SELECT SUM(amount) FROM expenses $whereExp", $paramsExp) ?: 0;

        return [
            'total_sales' => $totalSales,
            'total_purchases' => $totalPurchases,
            'total_expenses' => $totalExpenses
        ];
    }

    public function getDebtStats()
    {
        return [
            'total_debt' => $this->fetchColumn("SELECT SUM(price - paid_amount - COALESCE(refund_amount, 0)) FROM sales WHERE is_paid = 0") ?: 0,
            'overdue_count' => $this->fetchColumn("SELECT COUNT(*) FROM sales WHERE is_paid = 0 AND due_date < CURDATE()") ?: 0,
            'today_due' => $this->fetchColumn("SELECT SUM(price - paid_amount - COALESCE(refund_amount, 0)) FROM sales WHERE is_paid = 0 AND due_date = CURDATE()") ?: 0,
            'tomorrow_due' => $this->fetchColumn("SELECT SUM(price - paid_amount - COALESCE(refund_amount, 0)) FROM sales WHERE is_paid = 0 AND due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)") ?: 0
        ];
    }

    public function getRefunds($reportType, $date, $month, $year)
    {
        if ($reportType === 'Monthly') {
            $where = "WHERE DATE_FORMAT(r.created_at, '%Y-%m') = ?";
            $params = [$month];
        } elseif ($reportType === 'Yearly') {
            $where = "WHERE YEAR(r.created_at) = ?";
            $params = [$year];
        } else {
            $where = "WHERE DATE(r.created_at) = ?";
            $params = [$date];
        }

        $sql = "SELECT r.*, c.name as cust_name FROM refunds r LEFT JOIN customers c ON r.customer_id = c.id $where ORDER BY r.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getSalesList($reportType, $date, $month, $year, $providerId = null)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 's.sale_date');

        if ($providerId) {
            $where .= " AND p.provider_id = ?";
            $params[] = $providerId;
        }

        $sql = "SELECT s.*, c.name as cust_name, t.name as type_name, prov.name as prov_name 
                FROM sales s 
                LEFT JOIN customers c ON s.customer_id = c.id 
                LEFT JOIN qat_types t ON s.qat_type_id = t.id
                LEFT JOIN purchases p ON s.purchase_id = p.id
                LEFT JOIN providers prov ON p.provider_id = prov.id
                $where ORDER BY s.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getPurchasesList($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'p.purchase_date');
        $sql = "SELECT p.*, t.name as type_name, prov.name as prov_name 
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
        list($whereDep, $paramsDep) = $this->getWhereAndParams($reportType, $date, $month, $year, 'deposit_date');

        if ($reportType === 'Monthly') {
            $whereRef = "WHERE DATE_FORMAT(r.created_at, '%Y-%m') = ?";
            $paramsRef = [$month];
        } elseif ($reportType === 'Yearly') {
            $whereRef = "WHERE YEAR(r.created_at) = ?";
            $paramsRef = [$year];
        } else {
            $whereRef = "WHERE DATE(r.created_at) = ?";
            $paramsRef = [$date];
        }

        $cashSales = $this->fetchColumn("SELECT SUM(CASE WHEN payment_method = 'Cash' THEN price ELSE 0 END) FROM sales $whereSales", $paramsSales) ?: 0;
        $collectedPayments = $this->fetchColumn("SELECT SUM(amount) FROM payments $wherePay", $paramsPay) ?: 0;
        $depositsYER = $this->fetchColumn("SELECT SUM(amount) FROM qat_deposits $whereDep AND currency = 'YER'", $paramsDep) ?: 0;
        $cashRefunds = $this->fetchColumn("SELECT SUM(amount) FROM refunds r $whereRef AND refund_type = 'Cash'", $paramsRef) ?: 0;
        $debtRefunds = $this->fetchColumn("SELECT SUM(amount) FROM refunds r $whereRef AND refund_type = 'Debt'", $paramsRef) ?: 0;
        
        list($whereUT, $paramsUT) = $this->getWhereAndParams($reportType, $date, $month, $year, 'transfer_date');
        $totalUT = $this->fetchColumn("SELECT SUM(amount) FROM unknown_transfers $whereUT", $paramsUT) ?: 0;

        try {
            $collectedCash = $this->fetchColumn("SELECT SUM(amount) FROM payments $wherePay AND payment_method = 'Cash'", $paramsPay) ?: 0;
            $collectedTransfer = $this->fetchColumn("SELECT SUM(amount) FROM payments $wherePay AND payment_method = 'Transfer'", $paramsPay) ?: 0;
        } catch (PDOException $e) {
            // Fallback if column doesn't exist yet
            $collectedCash = $this->fetchColumn("SELECT SUM(amount) FROM payments $wherePay", $paramsPay) ?: 0;
            $collectedTransfer = 0;
        }

        // Total expenses
        list($whereExp, $paramsExp) = $this->getWhereAndParams($reportType, $date, $month, $year, 'expense_date');
        if ($userId !== null) {
            $whereExp .= " AND staff_id = ?";
            $paramsExp[] = $userId;
        }
        $totalExpenses = $this->fetchColumn("SELECT SUM(amount) FROM expenses $whereExp", $paramsExp) ?: 0;

        return [
            'cash_sales' => $cashSales,
            'collected_payments' => $collectedCash + $collectedTransfer,
            'wasel_cash' => $collectedCash,
            'wasel_transfer' => $collectedTransfer,
            'deposits_yer' => $depositsYER,
            'cash_refunds' => $cashRefunds,
            'debt_refunds' => $debtRefunds,
            'total_expenses' => $totalExpenses,
            'total_unknown_transfers' => $totalUT
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
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'sale_date');
        $sql = "SELECT
                SUM(CASE WHEN payment_method = 'Cash' THEN price ELSE 0 END) as cash_sales,
                SUM(CASE WHEN payment_method = 'Debt' THEN price ELSE 0 END) as debt_sales,
                SUM(CASE WHEN payment_method NOT IN ('Cash', 'Debt') THEN price ELSE 0 END) as transfer_sales,
                SUM(CASE WHEN qat_status IN ('Momsi', 'Leftover') THEN price ELSE 0 END) as momsi_sales,
                COUNT(*) as total_invoices
                FROM sales $where";
        return $this->fetchOne($sql, $params);
    }

    public function getLeftoversBreakdown($reportType, $date, $month, $year)
    {
        if ($reportType === 'Monthly') {
            $where = "WHERE DATE_FORMAT(source_date, '%Y-%m') = ?";
            $params = [$month];
        } elseif ($reportType === 'Yearly') {
            $where = "WHERE YEAR(source_date) = ?";
            $params = [$year];
        } else {
            $where = "WHERE source_date = ?";
            $params = [$date];
        }

        $sql = "SELECT
                SUM(CASE WHEN status IN ('Dropped') THEN weight_kg ELSE 0 END) as manual_dropped_kg,
                SUM(CASE WHEN status = 'Auto_Dropped' THEN weight_kg ELSE 0 END) as auto_dropped_kg,
                SUM(CASE WHEN status = 'Transferred_Next_Day' THEN weight_kg ELSE 0 END) as manual_momsi_kg,
                SUM(CASE WHEN status = 'Auto_Momsi' THEN weight_kg ELSE 0 END) as auto_momsi_kg
                FROM leftovers $where";
        return $this->fetchOne($sql, $params);
    }

    public function getDepositsByCurrency($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'deposit_date');
        $sql = "SELECT currency, SUM(amount) as total FROM qat_deposits $where GROUP BY currency";
        return $this->fetchAll($sql, $params, PDO::FETCH_KEY_PAIR);
    }

    public function getDebtSalesList($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 's.sale_date');
        $sql = "SELECT s.*, c.name as cust_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id $where AND s.payment_method = 'Debt' ORDER BY s.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getTotalReceivables()
    {
        return $this->fetchColumn("SELECT SUM(total_debt) FROM customers") ?: 0;
    }

    public function getInventoryValue()
    {
        return $this->fetchColumn("SELECT SUM(agreed_price) FROM purchases WHERE status = 'Fresh'") ?: 0;
    }

    public function getElectronicBalance()
    {
        $electronicSales = $this->fetchColumn("SELECT SUM(price) FROM sales WHERE payment_method IN ('Kuraimi Deposit', 'Jayb Deposit', 'Internal Transfer')") ?: 0;
        $deposits = $this->fetchColumn("SELECT SUM(amount) FROM qat_deposits") ?: 0;
        return $electronicSales + $deposits;
    }

    public function getUnknownTransfersList($reportType, $date, $month, $year)
    {
        list($where, $params) = $this->getWhereAndParams($reportType, $date, $month, $year, 'transfer_date');
        $sql = "SELECT * FROM unknown_transfers $where ORDER BY transfer_date DESC, id DESC";
        return $this->fetchAll($sql, $params);
    }
}
