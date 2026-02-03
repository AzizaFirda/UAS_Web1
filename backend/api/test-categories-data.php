<?php
// Test script untuk check categories API dengan proper session
session_start();

// Set fake session user untuk testing
$_SESSION['user_id'] = 1;

// Now test categories API
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Category.php';

try {
    $userId = $_SESSION['user_id'];
    $categoryModel = new Category();
    
    // Get income categories
    $incomeCategories = $categoryModel->getAll($userId, 'income');
    echo "Income Categories: " . count($incomeCategories) . "\n";
    foreach ($incomeCategories as $cat) {
        echo "  - " . $cat['name'] . " (" . $cat['icon'] . ")\n";
    }
    
    // Get expense categories
    $expenseCategories = $categoryModel->getAll($userId, 'expense');
    echo "\nExpense Categories: " . count($expenseCategories) . "\n";
    foreach ($expenseCategories as $cat) {
        echo "  - " . $cat['name'] . " (" . $cat['icon'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
