<?php
// backend/api/init-user-data.php
// Initialize default categories and accounts for current authenticated user

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

header('Content-Type: application/json');

// Check authentication
if (!AuthMiddleware::check()) {
    http_response_code(401);
    echo json_encode([
        'error' => true,
        'message' => 'Not authenticated'
    ]);
    exit;
}

$userId = AuthMiddleware::getUserId();

try {
    $db = getDB();
    
    // Check if user already has categories
    $checkSql = "SELECT COUNT(*) as count FROM categories WHERE user_id = :user_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([':user_id' => $userId]);
    $result = $checkStmt->fetch();
    
    $categoriesCreated = false;
    $accountsCreated = false;
    
    if ($result['count'] === 0) {
        // Default categories to create
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
        
        foreach ($categories as $cat) {
            $insertStmt->execute([
                ':user_id' => $userId,
                ':name' => $cat['name'],
                ':type' => $cat['type'],
                ':icon' => $cat['icon'],
                ':color' => $cat['color']
            ]);
        }
        $categoriesCreated = true;
    }
    
    // Check if user already has accounts
    $checkAccounts = "SELECT COUNT(*) as count FROM accounts WHERE user_id = :user_id";
    $checkAccStmt = $db->prepare($checkAccounts);
    $checkAccStmt->execute([':user_id' => $userId]);
    $accResult = $checkAccStmt->fetch();
    
    if ($accResult['count'] === 0) {
        $accounts = [
            ['name' => 'Kas', 'type' => 'cash', 'initial_balance' => 0, 'icon' => 'wallet', 'color' => '#3498db'],
            ['name' => 'Bank', 'type' => 'bank', 'initial_balance' => 0, 'icon' => 'university', 'color' => '#2980b9']
        ];
        
        $insertAccSql = "INSERT INTO accounts (user_id, name, type, initial_balance, current_balance, icon, color) VALUES (:user_id, :name, :type, :initial_balance, :current_balance, :icon, :color)";
        $insertAccStmt = $db->prepare($insertAccSql);
        
        foreach ($accounts as $acc) {
            $insertAccStmt->execute([
                ':user_id' => $userId,
                ':name' => $acc['name'],
                ':type' => $acc['type'],
                ':initial_balance' => $acc['initial_balance'],
                ':current_balance' => $acc['initial_balance'],
                ':icon' => $acc['icon'],
                ':color' => $acc['color']
            ]);
        }
        $accountsCreated = true;
    }
    
    http_response_code(200);
    echo json_encode([
        'error' => false,
        'message' => 'Data initialization successful',
        'data' => [
            'categories_created' => $categoriesCreated,
            'accounts_created' => $accountsCreated
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
