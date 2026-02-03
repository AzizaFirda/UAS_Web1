<?php
// backend/models/Transaction.php

require_once __DIR__ . '/../config/database.php';

class Transaction {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    // CREATE Transaction
    public function create($data) {
        $this->db->beginTransaction();
        
        try {
            // Insert transaction
            $sql = "INSERT INTO transactions (user_id, account_id, category_id, type, amount, transaction_date, description, notes, to_account_id) 
                    VALUES (:user_id, :account_id, :category_id, :type, :amount, :transaction_date, :description, :notes, :to_account_id)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':account_id' => $data['account_id'],
                ':category_id' => $data['category_id'] ?? null,
                ':type' => $data['type'],
                ':amount' => $data['amount'],
                ':transaction_date' => $data['transaction_date'] ?? date('Y-m-d'),
                ':description' => $data['description'] ?? null,
                ':notes' => $data['notes'] ?? null,
                ':to_account_id' => $data['to_account_id'] ?? null
            ]);
            
            $transactionId = $this->db->lastInsertId();
            
            // Update account balance
            $this->updateAccountBalance($data);
            
            $this->db->commit();
            return $transactionId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    // READ All Transactions
    public function getAll($userId, $filters = []) {
        $sql = "SELECT t.*, 
                       a.name as account_name, a.type as account_type,
                       c.name as category_name, c.icon as category_icon, c.color as category_color,
                       ta.name as to_account_name
                FROM transactions t
                LEFT JOIN accounts a ON t.account_id = a.id
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN accounts ta ON t.to_account_id = ta.id
                WHERE t.user_id = :user_id";
        
        $params = [':user_id' => $userId];
        
        // Apply filters
        if (!empty($filters['type'])) {
            $sql .= " AND t.type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['account_id'])) {
            $sql .= " AND t.account_id = :account_id";
            $params[':account_id'] = $filters['account_id'];
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND t.transaction_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND t.transaction_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT :limit";
        }
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if (!empty($filters['limit'])) {
            $stmt->bindValue(':limit', (int)$filters['limit'], PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // READ Single Transaction
    public function getById($id, $userId) {
        $sql = "SELECT t.*, 
                       a.name as account_name,
                       c.name as category_name,
                       ta.name as to_account_name
                FROM transactions t
                LEFT JOIN accounts a ON t.account_id = a.id
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN accounts ta ON t.to_account_id = ta.id
                WHERE t.id = :id AND t.user_id = :user_id
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        return $stmt->fetch();
    }
    
    // UPDATE Transaction
    public function update($id, $userId, $data) {
        $this->db->beginTransaction();
        
        try {
            // Get old transaction
            $oldTransaction = $this->getById($id, $userId);
            if (!$oldTransaction) {
                throw new Exception('Transaction not found');
            }
            
            // Reverse old balance changes
            $this->reverseAccountBalance($oldTransaction);
            
            // Update transaction
            $sql = "UPDATE transactions 
                    SET account_id = :account_id,
                        category_id = :category_id,
                        type = :type,
                        amount = :amount,
                        transaction_date = :transaction_date,
                        description = :description,
                        notes = :notes,
                        to_account_id = :to_account_id
                    WHERE id = :id AND user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':user_id' => $userId,
                ':account_id' => $data['account_id'],
                ':category_id' => $data['category_id'] ?? null,
                ':type' => $data['type'],
                ':amount' => $data['amount'],
                ':transaction_date' => $data['transaction_date'] ?? date('Y-m-d'),
                ':description' => $data['description'] ?? null,
                ':notes' => $data['notes'] ?? null,
                ':to_account_id' => $data['to_account_id'] ?? null
            ]);
            
            // Apply new balance changes
            $this->updateAccountBalance($data);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    // DELETE Transaction
    public function delete($id, $userId) {
        $this->db->beginTransaction();
        
        try {
            // Get transaction
            $transaction = $this->getById($id, $userId);
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            // Reverse balance changes
            $this->reverseAccountBalance($transaction);
            
            // Delete transaction
            $sql = "DELETE FROM transactions WHERE id = :id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id, ':user_id' => $userId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    // Helper: Update Account Balance
    private function updateAccountBalance($transaction) {
        if ($transaction['type'] === 'income') {
            $this->updateAccount($transaction['account_id'], $transaction['amount']);
        } 
        elseif ($transaction['type'] === 'expense') {
            $this->updateAccount($transaction['account_id'], -$transaction['amount']);
        } 
        elseif ($transaction['type'] === 'transfer') {
            $this->updateAccount($transaction['account_id'], -$transaction['amount']);
            $this->updateAccount($transaction['to_account_id'], $transaction['amount']);
        }
    }
    
    // Helper: Reverse Account Balance
    private function reverseAccountBalance($transaction) {
        if ($transaction['type'] === 'income') {
            $this->updateAccount($transaction['account_id'], -$transaction['amount']);
        } 
        elseif ($transaction['type'] === 'expense') {
            $this->updateAccount($transaction['account_id'], $transaction['amount']);
        } 
        elseif ($transaction['type'] === 'transfer') {
            $this->updateAccount($transaction['account_id'], $transaction['amount']);
            $this->updateAccount($transaction['to_account_id'], -$transaction['amount']);
        }
    }
    
    // Helper: Update Single Account
    private function updateAccount($accountId, $amount) {
        $sql = "UPDATE accounts SET current_balance = current_balance + :amount WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $accountId, ':amount' => $amount]);
    }
    
    // Get transactions by date range
    public function getByDateRange($userId, $startDate, $endDate) {
        $sql = "SELECT t.*, 
                       c.name as category_name, c.icon as category_icon, c.color as category_color,
                       a.name as account_name
                FROM transactions t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN accounts a ON t.account_id = a.id
                WHERE t.user_id = :user_id 
                AND t.transaction_date BETWEEN :start_date AND :end_date
                ORDER BY t.transaction_date DESC, t.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        return $stmt->fetchAll();
    }
}
?>