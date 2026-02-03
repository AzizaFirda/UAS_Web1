<?php
// backend/models/Budget.php

require_once __DIR__ . '/../config/database.php';

class Budget {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // CREATE Budget
    public function create($data) {
        $sql = "INSERT INTO budgets (user_id, category_id, name, amount, period, start_date, end_date, alert_percentage) 
                VALUES (:user_id, :category_id, :name, :amount, :period, :start_date, :end_date, :alert_percentage)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':category_id' => $data['category_id'],
            ':name' => $data['name'] ?? null,
            ':amount' => $data['amount'],
            ':period' => $data['period'] ?? 'monthly',
            ':start_date' => $data['start_date'] ?? date('Y-m-d'),
            ':end_date' => $data['end_date'] ?? null,
            ':alert_percentage' => $data['alert_percentage'] ?? 80
        ]);
        
        return $this->db->lastInsertId();
    }
    
    // READ All Budgets
    public function getAll($userId) {
        $sql = "SELECT b.*, c.name as category_name, c.icon, c.color
                FROM budgets b
                JOIN categories c ON b.category_id = c.id
                WHERE b.user_id = :user_id
                ORDER BY c.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll();
    }
    
    // READ Single Budget
    public function getById($id, $userId) {
        $sql = "SELECT b.*, c.name as category_name, c.icon, c.color
                FROM budgets b
                JOIN categories c ON b.category_id = c.id
                WHERE b.id = :id AND b.user_id = :user_id 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        return $stmt->fetch();
    }
    
    // UPDATE Budget
    public function update($id, $userId, $data) {
        $sql = "UPDATE budgets 
                SET amount = :amount,
                    period = :period
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':amount' => $data['amount'],
            ':period' => $data['period'] ?? 'monthly'
        ]);
    }
    
    // DELETE Budget
    public function delete($id, $userId) {
        $sql = "DELETE FROM budgets WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }
    
    // Get budget progress
    public function getProgress($userId) {
        $sql = "SELECT 
                    b.id,
                    b.amount as budget_amount,
                    c.name as category_name,
                    c.icon,
                    c.color,
                    COALESCE(SUM(t.amount), 0) as spent_amount,
                    (COALESCE(SUM(t.amount), 0) / b.amount * 100) as percentage,
                    CASE 
                        WHEN COALESCE(SUM(t.amount), 0) > b.amount THEN 'over'
                        WHEN COALESCE(SUM(t.amount), 0) >= b.amount * 0.8 THEN 'warning'
                        ELSE 'safe'
                    END as status
                FROM budgets b
                JOIN categories c ON b.category_id = c.id
                LEFT JOIN transactions t ON b.category_id = t.category_id 
                    AND t.type = 'expense'
                    AND DATE(t.transaction_date) >= b.start_date
                    AND (b.end_date IS NULL OR DATE(t.transaction_date) <= b.end_date)
                WHERE b.user_id = :user_id 
                GROUP BY b.id, b.amount, c.name, c.icon, c.color
                ORDER BY percentage DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll();
    }
    
    // Get overall budget summary
    public function getSummary($userId) {
        $sql = "SELECT 
                    SUM(b.amount) as total_budget,
                    COALESCE(SUM(t.amount), 0) as total_spent,
                    (SUM(b.amount) - COALESCE(SUM(t.amount), 0)) as remaining
                FROM budgets b
                LEFT JOIN transactions t ON b.category_id = t.category_id 
                    AND t.type = 'expense'
                    AND DATE(t.transaction_date) >= b.start_date
                    AND (b.end_date IS NULL OR DATE(t.transaction_date) <= b.end_date)
                WHERE b.user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetch();
    }
}
?>