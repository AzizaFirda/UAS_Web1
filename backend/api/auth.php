<?php
// backend/api/auth.php

// MUST be first - clean output buffer
ob_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[$errno] $errstr in $errfile:$errline");
    return false;
});

set_exception_handler(function($exception) {
    ob_end_clean(); // Clear any output
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred',
        'error' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine()
    ]);
    exit();
});

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../middleware/AuthMiddleware.php';
    require_once __DIR__ . '/../models/User.php';
    require_once __DIR__ . '/../models/Category.php';
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load required files',
        'error' => $e->getMessage()
    ]);
    exit();
}

header('Content-Type: application/json');

// Handle CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = ['localhost', '127.0.0.1', 'pipil.my.id', 'www.pipil.my.id'];
foreach ($allowed_origins as $allowed) {
    if (strpos($origin, $allowed) !== false) {
        header('Access-Control-Allow-Origin: ' . $origin);
        break;
    }
}
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

try {
    // Try to instantiate User model
    $userModel = new User();
    
    switch ($action) {
        case 'register':
            handleRegister($userModel);
            break;
            
        case 'login':
            handleLogin($userModel);
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        case 'check':
            handleCheck();
            break;
            
        case 'me':
            handleMe($userModel);
            break;
            
        case 'init-missing-accounts':
            handleInitMissingAccounts();
            break;
            
        default:
            sendError('Invalid action', 400);
    }
} catch (Exception $e) {
    error_log("Auth Error: " . $e->getMessage());
    sendError('Server error: ' . $e->getMessage(), 500);
}

function handleRegister($userModel) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validation
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        sendError('Name, email, and password are required');
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format');
    }
    
    if (strlen($data['password']) < 6) {
        sendError('Password must be at least 6 characters');
    }
    
    // Check if email exists
    if ($userModel->findByEmail($data['email'])) {
        sendError('Email already registered');
    }
    
    // Create user
    $userId = $userModel->create($data);
    
    // Initialize default categories for new user
    initializeDefaultData($userId);
    
    sendSuccess(['user_id' => $userId], 'Registration successful');
}

function handleLogin($userModel) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        error_log("Login attempt: email=" . ($data['email'] ?? 'NOT SET'));
        
        if (empty($data['email']) || empty($data['password'])) {
            sendError('Email and password are required');
        }
        
        $user = $userModel->findByEmail($data['email']);
        error_log("User found: " . ($user ? 'YES (id=' . $user['id'] . ')' : 'NO'));
        
        if (!$user || !$userModel->verifyPassword($data['password'], $user['password'])) {
            sendError('Invalid email or password', 401);
        }
        
        // Login user
        try {
            error_log("Calling AuthMiddleware::login for user_id=" . $user['id']);
            AuthMiddleware::login($user);
            error_log("Login successful for user_id=" . $user['id']);
        } catch (Exception $authEx) {
            error_log("AuthMiddleware error: " . $authEx->getMessage());
            sendError('Login failed: ' . $authEx->getMessage(), 500);
        }
        
        sendSuccess([
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'profile_photo' => $user['profile_photo'],
                'currency' => $user['currency'],
                'theme' => $user['theme']
            ]
        ], 'Login successful');
    } catch (Exception $e) {
        error_log("handleLogin exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        sendError('Login error: ' . $e->getMessage(), 500);
    }
}

function handleLogout() {
    AuthMiddleware::logout();
    sendSuccess(null, 'Logout successful');
}

function handleCheck() {
    $isAuthenticated = AuthMiddleware::check();
    sendSuccess(['authenticated' => $isAuthenticated]);
}

function handleMe($userModel) {
    AuthMiddleware::requireAuth();
    
    $userId = AuthMiddleware::getUserId();
    $user = $userModel->findById($userId);
    
    if (!$user) {
        sendError('User not found', 404);
    }
    
    sendSuccess(['user' => $user]);
}

function initializeDefaultData($userId) {
    try {
        $db = getDB();
        
        // Check if user already has categories
        $checkSql = "SELECT COUNT(*) as count FROM categories WHERE user_id = :user_id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([':user_id' => $userId]);
        $result = $checkStmt->fetch();
        
        if ($result['count'] > 0) {
            return; // User already has categories
        }
        
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
        
        // Default accounts
        $checkAccounts = "SELECT COUNT(*) as count FROM accounts WHERE user_id = :user_id";
        $checkAccStmt = $db->prepare($checkAccounts);
        $checkAccStmt->execute([':user_id' => $userId]);
        $accResult = $checkAccStmt->fetch();
        
        if ($accResult['count'] === 0) {
            $accounts = [
                ['name' => 'Kas', 'type' => 'cash', 'initial_balance' => 0, 'icon' => 'wallet', 'color' => '#3498db'],
                ['name' => 'Bank', 'type' => 'bank', 'initial_balance' => 0, 'icon' => 'university', 'color' => '#2980b9'],
                ['name' => 'E-Wallet', 'type' => 'ewallet', 'initial_balance' => 0, 'icon' => 'mobile-alt', 'color' => '#e74c3c']
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
        }
        
    } catch (Exception $e) {
        // Silently fail - don't break registration if initialization fails
        error_log('Error initializing default data: ' . $e->getMessage());
    }
}

function handleInitMissingAccounts() {
    $db = getDB();
    
    session_start();
    if (!isset($_SESSION['user_id'])) {
        sendError('Unauthorized', 401);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        // Define all default accounts
        $allAccounts = [
            ['name' => 'Kas', 'type' => 'cash', 'initial_balance' => 0, 'icon' => 'wallet', 'color' => '#3498db'],
            ['name' => 'Bank', 'type' => 'bank', 'initial_balance' => 0, 'icon' => 'university', 'color' => '#2980b9'],
            ['name' => 'E-Wallet', 'type' => 'ewallet', 'initial_balance' => 0, 'icon' => 'mobile-alt', 'color' => '#e74c3c']
        ];
        
        // Get existing account types for this user
        $checkSql = "SELECT type FROM accounts WHERE user_id = :user_id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([':user_id' => $userId]);
        $existingTypes = array_column($checkStmt->fetchAll(), 'type');
        
        // Add missing accounts
        $insertSql = "INSERT INTO accounts (user_id, name, type, initial_balance, current_balance, icon, color) VALUES (:user_id, :name, :type, :initial_balance, :current_balance, :icon, :color)";
        $insertStmt = $db->prepare($insertSql);
        
        $addedCount = 0;
        foreach ($allAccounts as $acc) {
            if (!in_array($acc['type'], $existingTypes)) {
                $insertStmt->execute([
                    ':user_id' => $userId,
                    ':name' => $acc['name'],
                    ':type' => $acc['type'],
                    ':initial_balance' => $acc['initial_balance'],
                    ':current_balance' => $acc['initial_balance'],
                    ':icon' => $acc['icon'],
                    ':color' => $acc['color']
                ]);
                $addedCount++;
            }
        }
        
        sendSuccess(['added' => $addedCount], 'Accounts initialized successfully');
        
    } catch (Exception $e) {
        sendError('Error initializing accounts: ' . $e->getMessage(), 500);
    }
}
?>