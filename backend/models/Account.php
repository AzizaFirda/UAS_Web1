<?php
// backend/models/Account.php

require_once __DIR__ . '/../config/database.php';

class Account {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    public function create($data) {
        $sql = "INSERT INTO accounts (user_id, name, type, initial_balance, current_balance, icon, color) 
                VALUES (:user_id, :name, :type, :initial_balance, :current_balance, :icon, :color)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':name' => $data['name'],
            ':type' => $data['type'],
            ':initial_balance' => $data['initial_balance'] ?? 0,
            ':current_balance' => $data['initial_balance'] ?? 0,
            ':icon' => $data['icon'] ?? 'wallet',
            ':color' => $data['color'] ?? '#3498db'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function getAll($userId) {
        $sql = "SELECT * FROM accounts WHERE user_id = :user_id ORDER BY type, name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    public function getById($id, $userId) {
        $sql = "SELECT * FROM accounts WHERE id = :id AND user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        return $stmt->fetch();
    }
    
    public function update($id, $userId, $data) {
        $fields = [];
        $params = [':id' => $id, ':user_id' => $userId];
        
        $allowedFields = ['name', 'type', 'icon', 'color', 'current_balance'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE accounts SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id, $userId) {
        // Check if account has transactions
        $checkSql = "SELECT COUNT(*) as count FROM transactions WHERE account_id = :id OR to_account_id = :id";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([':id' => $id]);
        $result = $checkStmt->fetch();
        
        if ($result['count'] > 0) {
            throw new Exception('Cannot delete account with existing transactions');
        }
        
        $sql = "DELETE FROM accounts WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }
    
    public function getSummary($userId) {
        $sql = "SELECT 
                    SUM(CASE WHEN type IN ('cash', 'bank', 'ewallet') THEN current_balance ELSE 0 END) as total_assets,
                    SUM(CASE WHEN type = 'debt' AND current_balance > 0 THEN current_balance ELSE 0 END) as total_liabilities
                FROM accounts 
                WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        
        // Calculate net worth
        $total_assets = floatval($result['total_assets'] ?? 0);
        $total_liabilities = floatval($result['total_liabilities'] ?? 0);
        $net_worth = $total_assets - $total_liabilities;
        
        return [
            'total_assets' => $total_assets,
            'total_liabilities' => $total_liabilities,
            'net_worth' => $net_worth
        ];
    }
}
?>