<?php
// includes/classes/BaseRepository.php

abstract class BaseRepository
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private static $transactionCounters = [];

    private function getPdoId() {
        return spl_object_id($this->pdo);
    }

    public function beginTransaction()
    {
        $id = $this->getPdoId();
        if (!isset(self::$transactionCounters[$id])) {
            self::$transactionCounters[$id] = 0;
        }

        if (self::$transactionCounters[$id] > 0) {
            $this->pdo->exec("SAVEPOINT qat_savepoint_" . self::$transactionCounters[$id]);
        } else {
            $this->pdo->beginTransaction();
        }
        self::$transactionCounters[$id]++;
        return true;
    }

    public function commit()
    {
        $id = $this->getPdoId();
        if (!isset(self::$transactionCounters[$id]) || self::$transactionCounters[$id] === 0) {
            return false;
        }

        self::$transactionCounters[$id]--;
        if (self::$transactionCounters[$id] === 0) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
        } else {
            // RELEASE SAVEPOINT is buena praxis in MySQL to free resources
            $this->pdo->exec("RELEASE SAVEPOINT qat_savepoint_" . self::$transactionCounters[$id]);
        }
        return true;
    }

    public function inTransaction()
    {
        $id = $this->getPdoId();
        return (isset(self::$transactionCounters[$id]) && self::$transactionCounters[$id] > 0);
    }

    public function rollBack()
    {
        $id = $this->getPdoId();
        if (!isset(self::$transactionCounters[$id]) || self::$transactionCounters[$id] === 0) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return true;
        }

        self::$transactionCounters[$id]--;
        if (self::$transactionCounters[$id] === 0) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        } else {
            $this->pdo->exec("ROLLBACK TO SAVEPOINT qat_savepoint_" . self::$transactionCounters[$id]);
        }
        return true;
    }

    protected function execute($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // Log for diagnostics
            $this->logSql($sql, $params, $stmt->rowCount());
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("SQL Error: " . $e->getMessage() . " | SQL: $sql | Params: " . json_encode($params));
            throw $e;
        }
    }

    protected function fetchAll($sql, $params = [])
    {
        return $this->execute($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function fetchOne($sql, $params = [])
    {
        return $this->execute($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    protected function fetchColumn($sql, $params = [])
    {
        return $this->execute($sql, $params)->fetchColumn();
    }

    protected function getLastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    private function logSql($sql, $params, $rowCount) {
        $id = $this->getPdoId();
        $inTx = $this->pdo->inTransaction() ? 'YES' : 'NO';
        $level = self::$transactionCounters[$id] ?? 0;
        
        $msg = date('H:i:s') . " | TX[$inTx] | Level[$level] | PDO[$id] | Rows[$rowCount] | $sql | " . json_encode($params) . PHP_EOL;
        
        // Use an absolute path relative to the root to avoid "directory not found" warnings
        // when this is called from scripts in subdirectories (like /requests).
        $logDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'tmp';
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'sql_log.txt';
        
        if (is_dir($logDir)) {
            @file_put_contents($logFile, $msg, FILE_APPEND);
        }
    }
}
