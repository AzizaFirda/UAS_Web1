<?php
// backend/api/accounts.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/Account.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

AuthMiddleware::requireAuth();

$userId = AuthMiddleware::getUserId();
$method = $_SERVER['REQUEST_METHOD'];
$accountModel = new Account();

try {
    switch ($method) {
        case 'GET':
            handleGet($accountModel, $userId);
            break;
            
        case 'POST':
            handlePost($accountModel, $userId);
            break;
            
        case 'PUT':
            handlePut($accountModel, $userId);
            break;
            
        case 'DELETE':
            handleDelete($accountModel, $userId);
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}

function handleGet($model, $userId) {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        $accounts = $model->getAll($userId);
        sendSuccess(['accounts' => $accounts]);
    } 
    elseif ($action === 'summary') {
        $summary = $model->getSummary($userId);
        sendSuccess(['summary' => $summary]);
    }
    elseif ($action === 'single') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            sendError('Account ID is required');
        }
        
        $account = $model->getById($id, $userId);
        if (!$account) {
            sendError('Account not found', 404);
        }
        
        sendSuccess(['account' => $account]);
    }
    else {
        sendError('Invalid action');
    }
}

function handlePost($model, $userId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['name']) || empty($data['type'])) {
        sendError('Name and type are required');
    }
    
    // Map 'mbanking' to 'bank' for backward compatibility
    if ($data['type'] === 'mbanking') {
        $data['type'] = 'bank';
    }
    
    if (!in_array($data['type'], ['cash', 'bank', 'ewallet', 'debt'])) {
        sendError('Invalid account type. Must be: cash, bank, mbanking, ewallet, or debt');
    }
    
    $data['user_id'] = $userId;
    $accountId = $model->create($data);
    
    sendSuccess(['account_id' => $accountId], 'Account created successfully');
}

function handlePut($model, $userId) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendError('Account ID is required');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $model->update($id, $userId, $data);
    sendSuccess(null, 'Account updated successfully');
}

function handleDelete($model, $userId) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendError('Account ID is required');
    }
    
    $model->delete($id, $userId);
    sendSuccess(null, 'Account deleted successfully');
}
?>