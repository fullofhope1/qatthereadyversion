<?php
// includes/classes/CommunicationRepository.php

class CommunicationRepository extends BaseRepository
{
    public function getUnknownTransfers($limit = 100)
    {
        return $this->fetchAll("SELECT * FROM unknown_transfers ORDER BY transfer_date DESC, created_at DESC LIMIT ?", [(int)$limit]);
    }

    public function createUnknownTransfer(array $data)
    {
        $sql = "INSERT INTO unknown_transfers (transfer_date, amount, currency, receipt_number, sender_name, receiver_name, notes, created_at) 
                VALUES (:transfer_date, :amount, :currency, :receipt_number, :sender_name, :receiver_name, :notes, NOW())";
        return $this->execute($sql, $data);
    }

    public function updateUnknownTransfer($id, array $data)
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $sql = "UPDATE unknown_transfers SET " . implode(', ', $fields) . " WHERE id = :id";
        $data['id'] = $id;
        return $this->execute($sql, $data);
    }

    public function deleteUnknownTransfer($id)
    {
        return $this->execute("DELETE FROM unknown_transfers WHERE id = ?", [$id]);
    }

    public function getUnknownTransferById($id)
    {
        return $this->fetchOne("SELECT * FROM unknown_transfers WHERE id = ?", [$id]);
    }

    public function resolveTransfer($transferId, $customerId)
    {
        $transfer = $this->getUnknownTransferById($transferId);
        if (!$transfer) return false;

        $this->beginTransaction();
        try {
            // 1. Create payment record
            $sqlPay = "INSERT INTO payments (customer_id, amount, payment_date, notes, created_at) 
                       VALUES (:cid, :amt, :pdate, :notes, NOW())";
            $this->execute($sqlPay, [
                ':cid' => $customerId,
                ':amt' => $transfer['amount'],
                ':pdate' => $transfer['transfer_date'],
                ':notes' => "محولة من الحوالات المجهولة - سند رقم: " . $transfer['receipt_number'] . " - المستلم: " . $transfer['receiver_name']
            ]);

            // 2. Update customer balance
            $this->execute("UPDATE customers SET total_debt = total_debt - ? WHERE id = ?", [$transfer['amount'], $customerId]);

            // 3. Delete unknown transfer
            $this->deleteUnknownTransfer($transferId);

            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            return false;
        }
    }

    public function getDebtorsWithActivity()
    {
        // Get debtors and their LATEST SALE details (subquery for the specific sale record)
        $sql = "SELECT c.*, 
                   s.price as last_sale_amount,
                   s.sale_date as last_sale_date,
                   t.name as last_qat_type,
                   s.weight_grams as last_weight,
                   s.quantity_units as last_units,
                   s.unit_type as last_unit_type
                FROM customers c 
                LEFT JOIN sales s ON s.id = (
                    SELECT id FROM sales 
                    WHERE customer_id = c.id 
                    ORDER BY sale_date DESC, id DESC LIMIT 1
                )
                LEFT JOIN qat_types t ON s.qat_type_id = t.id
                WHERE c.total_debt > 0 
                ORDER BY c.name ASC";
        return $this->fetchAll($sql);
    }

    public function getLastTransactions($customerId, $limit = 5)
    {
        $sql = "(SELECT sale_date as t_date, 'بيع' as t_type, price as amount FROM sales WHERE customer_id = :cid AND payment_method = 'Debt')
                UNION ALL
                (SELECT payment_date as t_date, 'سداد' as t_type, -amount as amount FROM payments WHERE customer_id = :cid)
                UNION ALL
                (SELECT created_at as t_date, 'مرتجع' as t_type, -amount as amount FROM refunds WHERE customer_id = :cid AND refund_type = 'Debt')
                ORDER BY t_date DESC LIMIT :limit";
        return $this->fetchAll($sql, [':cid' => (int)$customerId, ':limit' => (int)$limit]);
    }
}
