<?php
// Test categories API endpoint
session_start();
$_SESSION['user_id'] = 5;

// Set headers yang diperlukan
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/Category.php';

// Mock AuthMiddleware untuk testing
class AuthMiddlewareMock {
    public static function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => true, 'message' => 'Not authenticated']);
            exit;
        }
    }
    
    public static function getUserId() {
        return $_SESSION['user_id'];
    }
}

try {
    AuthMiddlewareMock::requireAuth();
    
    $userId = AuthMiddlewareMock::getUserId();
    $categoryModel = new Category();
    
    // Simulate the API call
    $type = $_GET['type'] ?? 'income';
    $categories = $categoryModel->getAll($userId, $type);
    
    echo json_encode([
        'error' => false,
        'message' => 'Success',
        'data' => ['categories' => $categories]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
