<?php
// backend/models/Category.php

require_once __DIR__ . '/../config/database.php';

class Category {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    // CREATE Category
    public function create($data) {
        $sql = "INSERT INTO categories (user_id, name, type, icon, color) 
                VALUES (:user_id, :name, :type, :icon, :color)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':name' => $data['name'],
            ':type' => $data['type'],
            ':icon' => $data['icon'] ?? 'tag',
            ':color' => $data['color'] ?? '#95a5a6'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    // READ All Categories
    public function getAll($userId, $type = null) {
        $sql = "SELECT * FROM categories WHERE user_id = :user_id";
        
        if ($type) {
            $sql .= " AND type = :type";
        }
        
        $sql .= " ORDER BY type, name";
        
        $stmt = $this->db->prepare($sql);
        $params = [':user_id' => $userId];
        
        if ($type) {
            $params[':type'] = $type;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    // READ Single Category
    public function getById($id, $userId) {
        $sql = "SELECT * FROM categories WHERE id = :id AND user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        return $stmt->fetch();
    }
    
    // UPDATE Category
    public function update($id, $userId, $data) {
        $fields = [];
        $params = [':id' => $id, ':user_id' => $userId];
        
        $allowedFields = ['name', 'type', 'icon', 'color'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    // DELETE Category
    public function delete($id, $userId) {
        // Check if category has transactions
        $checkSql = "SELECT COUNT(*) as count FROM transactions WHERE category_id = :id";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([':id' => $id]);
        $result = $checkStmt->fetch();
        
        if ($result['count'] > 0) {
            throw new Exception('Cannot delete category with existing transactions');
        }
        
        $sql = "DELETE FROM categories WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }
    
    // Get category statistics
    public function getStatistics($userId, $month = null, $year = null) {
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');
        
        $sql = "SELECT 
                    c.id,
                    c.name,
                    c.type,
                    c.icon,
                    c.color,
                    COUNT(t.id) as transaction_count,
                    COALESCE(SUM(t.amount), 0) as total_amount
                FROM categories c
                LEFT JOIN transactions t ON c.id = t.category_id 
                    AND MONTH(t.transaction_date) = :month 
                    AND YEAR(t.transaction_date) = :year
                WHERE c.user_id = :user_id
                GROUP BY c.id, c.name, c.type, c.icon, c.color
                ORDER BY total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':month' => $month,
            ':year' => $year
        ]);
        
        return $stmt->fetchAll();
    }
}
?>