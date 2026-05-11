<?php
// includes/classes/DebtRepository.php

class DebtRepository extends BaseRepository
{
    public function getDebtorsByType($type)
    {
        $sql = "";
        $params = [];

        if ($type == 'All') {
            // 'All' tab: Include Opening Balance + All Unpaid Sales
            $sql = "SELECT c.id, c.name, c.phone,
                    (COALESCE(c.opening_balance, 0) - COALESCE(c.paid_opening_balance, 0) + COALESCE(SUM(s.price - s.paid_amount - COALESCE(s.refund_amount, 0)), 0)) as due_amount,
                    COUNT(CASE WHEN s.due_date < CURDATE() THEN 1 END) as overdue_count,
                    MIN(s.due_date) as earliest_due
                    FROM customers c
                    LEFT JOIN sales s ON c.id = s.customer_id AND s.is_paid = 0
                    WHERE c.is_deleted = 0
                    GROUP BY c.id
                    HAVING due_amount > 0
                    ORDER BY due_amount DESC";
        } elseif ($type == 'Daily') {
            $sql = "SELECT c.id, c.name, c.phone,
                    SUM(s.price - s.paid_amount - COALESCE(s.refund_amount,0)) as due_amount,
                    COUNT(CASE WHEN s.due_date < CURDATE() THEN 1 END) as overdue_count,
                    MIN(s.due_date) as earliest_due
                    FROM sales s
                    JOIN customers c ON s.customer_id = c.id
                    WHERE s.is_paid = 0 AND s.debt_type = 'Daily' AND s.due_date <= CURDATE()
                    GROUP BY c.id ORDER BY due_amount DESC";
        } elseif ($type == 'Upcoming') {
            $sql = "SELECT c.id, c.name, c.phone,
                    SUM(s.price - s.paid_amount - COALESCE(s.refund_amount,0)) as due_amount,
                    0 as overdue_count, null as earliest_due
                    FROM sales s
                    JOIN customers c ON s.customer_id = c.id
                    WHERE s.is_paid = 0 
                    AND (
                        (s.debt_type = 'Daily' AND s.due_date > CURDATE())
                        OR s.debt_type = 'Deferred'
                    )
                    GROUP BY c.id ORDER BY due_amount DESC";
        } elseif ($type == 'Monthly') {
            $sql = "SELECT c.id, c.name, c.phone,
                    SUM(s.price - s.paid_amount - COALESCE(s.refund_amount,0)) as due_amount,
                    COUNT(CASE WHEN s.due_date < CURDATE() THEN 1 END) as overdue_count,
                    MIN(s.due_date) as earliest_due
                    FROM sales s
                    JOIN customers c ON s.customer_id = c.id
                    WHERE s.is_paid = 0 AND s.debt_type = 'Monthly' AND s.due_date <= CURDATE()
                    GROUP BY c.id ORDER BY due_amount DESC";
        } elseif ($type == 'Yearly') {
            $sql = "SELECT c.id, c.name, c.phone,
                    SUM(s.price - s.paid_amount - COALESCE(s.refund_amount,0)) as due_amount,
                    COUNT(CASE WHEN s.due_date < CURDATE() THEN 1 END) as overdue_count,
                    MIN(s.due_date) as earliest_due
                    FROM sales s
                    JOIN customers c ON s.customer_id = c.id
                    WHERE s.is_paid = 0 AND s.debt_type = 'Yearly' AND s.due_date <= CURDATE()
                    GROUP BY c.id ORDER BY due_amount DESC";
        }

        return $this->fetchAll($sql, $params);
    }

    public function rolloverDebt($customerId, $debtType)
    {
        $newDueDate = date('Y-m-d', strtotime('+1 day'));
        $sql = "UPDATE sales SET due_date = ? 
                WHERE customer_id = ? AND is_paid = 0 AND debt_type = ? AND due_date <= CURDATE()";
        return $this->execute($sql, [$newDueDate, $customerId, $debtType]);
    }

    public function reconcileDebts()
    {
        // 1. Reset all customer debts to 0
        $this->execute("UPDATE customers SET total_debt = 0");

        // 2. Recalculate based on unpaid sales + opening balance
        $sql = "UPDATE customers c 
                SET c.total_debt = (COALESCE(c.opening_balance, 0) - COALESCE(c.paid_opening_balance, 0)) + (
                    SELECT COALESCE(SUM(s.price - s.paid_amount - COALESCE(s.refund_amount, 0)), 0)
                    FROM sales s 
                    WHERE s.customer_id = c.id AND s.is_paid = 0
                )";
        return $this->execute($sql);
    }

    public function rolloverSale($saleId)
    {
        $sql = "UPDATE sales SET sale_date = DATE_ADD(sale_date, INTERVAL 1 DAY), due_date = DATE_ADD(due_date, INTERVAL 1 DAY) WHERE id = ?";
        return $this->execute($sql, [$saleId]);
    }

    public function insertPayment($customerId, $amount, $note, $method = 'Cash', $date = null, $transferData = [])
    {
        $date = $date ?: date('Y-m-d');
        $sender = $transferData['sender'] ?? null;
        $receiver = $transferData['receiver'] ?? null;
        $number = $transferData['number'] ?? null;
        $company = $transferData['company'] ?? null;

        $sql = "INSERT INTO payments (customer_id, amount, payment_method, note, payment_date, 
                                    transfer_sender, transfer_receiver, transfer_number, transfer_company) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->execute($sql, [$customerId, $amount, $method, $note, $date, $sender, $receiver, $number, $company]);
    }

    public function getUnpaidSales($customerId)
    {
        $sql = "SELECT id, price, paid_amount, COALESCE(refund_amount, 0) as refund_amount 
                FROM sales 
                WHERE customer_id = ? AND payment_method = 'Debt' AND is_paid = 0 
                ORDER BY sale_date ASC, id ASC";
        return $this->fetchAll($sql, [$customerId]);
    }

    public function updateSalePayment($saleId, $paidAmount, $isPaid)
    {
        $sql = "UPDATE sales SET paid_amount = ?, is_paid = ? WHERE id = ?";
        return $this->execute($sql, [$paidAmount, $isPaid ? 1 : 0, $saleId]);
    }

    public function updateCustomerDebt($customerId, $amountReduction)
    {
        // ✅ FIX #3: Recalculate total_debt from actual sales data instead of just decrementing.
        // This prevents the cache from drifting over time due to partial payments, refunds, etc.
        $sql = "UPDATE customers SET total_debt = (COALESCE(opening_balance, 0) - COALESCE(paid_opening_balance, 0)) + (
                    SELECT COALESCE(SUM(price - paid_amount - COALESCE(refund_amount, 0)), 0)
                    FROM sales
                    WHERE customer_id = ? AND is_paid = 0 AND is_returned = 0
                ) WHERE id = ?";
        return $this->execute($sql, [$customerId, $customerId]);
    }

    public function getOpeningDebtInfo($customerId)
    {
        return $this->fetchOne("SELECT opening_balance, paid_opening_balance FROM customers WHERE id = ?", [$customerId]);
    }

    public function updateOpeningBalancePayment($customerId, $amount)
    {
        return $this->execute("UPDATE customers SET paid_opening_balance = paid_opening_balance + ? WHERE id = ?", [$amount, $customerId]);
    }
}
