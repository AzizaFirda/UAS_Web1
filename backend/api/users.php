<?php
// backend/api/users.php

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, we'll handle them
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $errstr . ' in ' . $errfile . ':' . $errline
    ]);
    exit;
});

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    AuthMiddleware::requireAuth();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'error' => true,
        'message' => 'Unauthorized: ' . $e->getMessage()
    ]);
    exit;
}

$userId = AuthMiddleware::getUserId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($userId);
            break;
            
        case 'POST':
            handlePost($userId);
            break;
            
        case 'PUT':
            handlePut($userId);
            break;
            
        case 'DELETE':
            handleDelete($userId);
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}

function handleGet($userId) {
    $action = $_GET['action'] ?? 'profile';
    
    if ($action === 'profile') {
        $db = getDB();
        $sql = "SELECT id, name, email, currency, language, date_format, theme, profile_photo FROM users WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendError('User not found', 404);
            return;
        }
        
        sendSuccess(['user' => $user]);
    } else {
        sendError('Invalid action');
    }
}

function handlePost($userId) {
    $action = $_GET['action'] ?? 'upload';
    
    if ($action === 'upload-photo') {
        // Ensure uploads directory exists
        $uploadsDir = __DIR__ . '/../../uploads/profile';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['profile_photo'])) {
            sendError('No file uploaded');
            return;
        }
        
        $file = $_FILES['profile_photo'];
        
        // Validate file
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedMimes)) {
            sendError('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed');
            return;
        }
        
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            sendError('File too large. Maximum size is 5MB');
            return;
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            sendError('File upload error: ' . $file['error']);
            return;
        }
        
        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
        $filepath = $uploadsDir . '/' . $filename;
        // Store only filename - frontend will construct the path
        $storedFilename = $filename;
        
        // Move file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            sendError('Failed to save file');
            return;
        }
        
        // Delete old photo if exists
        $db = getDB();
        $stmt = $db->prepare("SELECT profile_photo FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && $user['profile_photo']) {
            // Extract filename from URL and delete
            $oldFile = __DIR__ . '/../../uploads/profile/' . basename($user['profile_photo']);
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
        
        // Update database
        $stmt = $db->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
        $stmt->execute([$storedFilename, $userId]);
        
        sendSuccess(['profile_photo' => $storedFilename], 'Profile photo updated successfully');
    } else {
        sendError('Invalid action');
    }
}

function handlePut($userId) {
    $action = $_GET['action'] ?? 'update';
    $rawInput = file_get_contents('php://input');
    
    error_log("handlePut: action=$action, userId=$userId, rawInput=$rawInput");
    
    $data = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Invalid JSON: ' . json_last_error_msg());
        return;
    }
    
    error_log("handlePut: decoded data=" . json_encode($data));
    
    if ($action === 'update') {
        $db = getDB();
        
        $fields = [];
        $params = [':user_id' => $userId];
        
        $allowedFields = ['name', 'email', 'currency', 'language', 'date_format', 'theme'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            sendError('No fields to update');
            return;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :user_id";
        error_log("handlePut UPDATE: sql=$sql, params=" . json_encode($params));
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            sendSuccess(null, 'User updated successfully');
        } catch (Exception $e) {
            error_log("handlePut UPDATE ERROR: " . $e->getMessage());
            sendError($e->getMessage());
        }
    } 
    elseif ($action === 'change-password') {
        if (!isset($data['password']) || empty($data['password'])) {
            sendError('Password is required');
            return;
        }
        
        $db = getDB();
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$hashedPassword, $userId]);
        
        sendSuccess(null, 'Password changed successfully');
    }
    elseif ($action === 'upload-photo') {
        // Handle profile photo upload
        if (!isset($_FILES['profile_photo'])) {
            sendError('No file provided');
            return;
        }
        
        $file = $_FILES['profile_photo'];
        $uploadDir = __DIR__ . '/../../uploads/profile/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $filename = uniqid('user_' . $userId . '_') . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $filepath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            sendError('Failed to upload file');
            return;
        }
        
        // Update database
        $db = getDB();
        $sql = "UPDATE users SET profile_photo = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$filename, $userId]);
        
        sendSuccess(['filename' => $filename], 'Profile photo uploaded successfully');
    }
    else {
        sendError('Invalid action');
    }
}

function handleDelete($userId) {
    $action = $_GET['action'] ?? null;
    
    if ($action === 'clear-data') {
        $db = getDB();
        
        try {
            // Delete all user data (but keep the user account)
            $db->beginTransaction();
            
            // Delete transactions
            $db->prepare("DELETE FROM transactions WHERE user_id = ?")->execute([$userId]);
            
            // Delete accounts
            $db->prepare("DELETE FROM accounts WHERE user_id = ?")->execute([$userId]);
            
            // Delete categories
            $db->prepare("DELETE FROM categories WHERE user_id = ?")->execute([$userId]);
            
            // Delete budgets
            $db->prepare("DELETE FROM budgets WHERE user_id = ?")->execute([$userId]);
            
            // Delete goals
            if ($db->query("SHOW TABLES LIKE 'goals'")->rowCount() > 0) {
                $db->prepare("DELETE FROM goals WHERE user_id = ?")->execute([$userId]);
            }
            
            // Delete reports
            if ($db->query("SHOW TABLES LIKE 'reports'")->rowCount() > 0) {
                $db->prepare("DELETE FROM reports WHERE user_id = ?")->execute([$userId]);
            }
            
            $db->commit();
            sendSuccess(null, 'All user data cleared successfully');
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    } else {
        sendError('Invalid action');
    }
}

?>
