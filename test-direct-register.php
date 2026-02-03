<?php
// Simple direct register test bypassing all buffers
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Clean any existing output
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once __DIR__ . '/backend/config/database.php';
    require_once __DIR__ . '/backend/models/User.php';
    
    $testEmail = 'directreg' . time() . '@test.com';
    
    $userModel = new User();
    
    $userData = [
        'name' => 'Direct Register Test',
        'email' => $testEmail,
        'password' => 'password123'
    ];
    
    $userId = $userModel->create($userData);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'User created successfully!',
        'user_id' => $userId,
        'email' => $testEmail
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
