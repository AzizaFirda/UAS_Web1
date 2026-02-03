<?php
// backend/api/init-categories-all.php
// Script untuk initialize default categories untuk semua user yang belum punya

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    // Get all users that don't have categories yet
    $sql = "SELECT DISTINCT u.id FROM users u 
            LEFT JOIN categories c ON u.id = c.user_id 
            WHERE c.id IS NULL";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "All users already have categories\n";
        exit;
    }
    
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
    
    $insertSql = "INSERT INTO categories (user_id, name, type, icon, color) VALUES (:user_id, :name, :type, :icon, :color)";
    $insertStmt = $db->prepare($insertSql);
    
    $totalInserted = 0;
    foreach ($users as $user) {
        foreach ($categories as $cat) {
            $insertStmt->execute([
                ':user_id' => $user['id'],
                ':name' => $cat['name'],
                ':type' => $cat['type'],
                ':icon' => $cat['icon'],
                ':color' => $cat['color']
            ]);
            $totalInserted++;
        }
    }
    
    echo "Successfully created $totalInserted categories for " . count($users) . " users\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
