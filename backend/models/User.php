<?php
// backend/models/User.php

require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function create($data) {
        $sql = "INSERT INTO users (name, email, password, currency, language, date_format, theme) 
                VALUES (:name, :email, :password, :currency, :language, :date_format, :theme)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_BCRYPT),
            ':currency' => $data['currency'] ?? 'IDR',
            ':language' => $data['language'] ?? 'id',
            ':date_format' => $data['date_format'] ?? 'DD/MM/YYYY',
            ':theme' => $data['theme'] ?? 'light'
        ]);
        
        $userId = $this->db->lastInsertId();
        
        // Create default categories
        $this->createDefaultCategories($userId);
        
        return $userId;
    }
    
    private function createDefaultCategories($userId) {
        $categories = [
            // Income
            ['name' => 'Gaji', 'type' => 'income', 'icon' => 'briefcase', 'color' => '#27ae60'],
            ['name' => 'Bonus', 'type' => 'income', 'icon' => 'gift', 'color' => '#2ecc71'],
            ['name' => 'Investasi', 'type' => 'income', 'icon' => 'trending-up', 'color' => '#16a085'],
            ['name' => 'Lainnya', 'type' => 'income', 'icon' => 'plus-circle', 'color' => '#1abc9c'],
            // Expense
            ['name' => 'Makanan & Minuman', 'type' => 'expense', 'icon' => 'utensils', 'color' => '#e74c3c'],
            ['name' => 'Transportasi', 'type' => 'expense', 'icon' => 'car', 'color' => '#e67e22'],
            ['name' => 'Belanja', 'type' => 'expense', 'icon' => 'shopping-cart', 'color' => '#f39c12'],
            ['name' => 'Tagihan', 'type' => 'expense', 'icon' => 'file-text', 'color' => '#d35400'],
            ['name' => 'Hiburan', 'type' => 'expense', 'icon' => 'film', 'color' => '#9b59b6'],
            ['name' => 'Kesehatan', 'type' => 'expense', 'icon' => 'heart', 'color' => '#c0392b'],
            ['name' => 'Pendidikan', 'type' => 'expense', 'icon' => 'book', 'color' => '#8e44ad'],
            ['name' => 'Lainnya', 'type' => 'expense', 'icon' => 'more-horizontal', 'color' => '#7f8c8d']
        ];
        
        $sql = "INSERT INTO categories (user_id, name, type, icon, color) VALUES (:user_id, :name, :type, :icon, :color)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($categories as $cat) {
            $stmt->execute([
                ':user_id' => $userId,
                ':name' => $cat['name'],
                ':type' => $cat['type'],
                ':icon' => $cat['icon'],
                ':color' => $cat['color']
            ]);
        }
    }
    
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }
    
    public function findById($id) {
        $sql = "SELECT id, name, email, profile_photo, currency, language, date_format, theme, created_at 
                FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['name', 'email', 'profile_photo', 'currency', 'language', 'date_format', 'theme'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function updatePassword($id, $newPassword) {
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':password' => password_hash($newPassword, PASSWORD_BCRYPT)
        ]);
    }
    
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>