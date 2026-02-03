<?php
// Test API response - check what categories.php returns

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Account.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check auth
echo "=== AUTH CHECK ===\n";
echo "Session Status: " . (isset($_SESSION['user_id']) ? "Authenticated (ID: " . $_SESSION['user_id'] . ")" : "Not authenticated") . "\n";

if (!isset($_SESSION['user_id'])) {
    echo "ERROR: No user in session\n";
    exit(1);
}

$userId = $_SESSION['user_id'];

// Check database connection
echo "\n=== DATABASE CHECK ===\n";
try {
    $db = getDB();
    echo "Database connection: OK\n";
    
    // Check users table
    $userStmt = $db->prepare("SELECT id, username FROM users WHERE id = :id");
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch();
    echo "User found: " . ($user ? $user['username'] : "NOT FOUND") . "\n";
    
    // Check categories count
    $catStmt = $db->prepare("SELECT COUNT(*) as count FROM categories WHERE user_id = :user_id");
    $catStmt->execute([':user_id' => $userId]);
    $catResult = $catStmt->fetch();
    echo "Categories count: " . $catResult['count'] . "\n";
    
    // Check accounts count  
    $accStmt = $db->prepare("SELECT COUNT(*) as count FROM accounts WHERE user_id = :user_id");
    $accStmt->execute([':user_id' => $userId]);
    $accResult = $accStmt->fetch();
    echo "Accounts count: " . $accResult['count'] . "\n";
    
    // Get actual categories
    echo "\n=== CATEGORIES DATA ===\n";
    $allCats = $db->prepare("SELECT id, name, type FROM categories WHERE user_id = :user_id ORDER BY type, name");
    $allCats->execute([':user_id' => $userId]);
    $categories = $allCats->fetchAll();
    
    if (empty($categories)) {
        echo "NO CATEGORIES FOUND\n";
    } else {
        foreach ($categories as $cat) {
            echo "- {$cat['name']} ({$cat['type']})\n";
        }
    }
    
    // Get actual accounts
    echo "\n=== ACCOUNTS DATA ===\n";
    $allAccs = $db->prepare("SELECT id, name, type FROM accounts WHERE user_id = :user_id ORDER BY name");
    $allAccs->execute([':user_id' => $userId]);
    $accounts = $allAccs->fetchAll();
    
    if (empty($accounts)) {
        echo "NO ACCOUNTS FOUND\n";
    } else {
        foreach ($accounts as $acc) {
            echo "- {$acc['name']} ({$acc['type']})\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
