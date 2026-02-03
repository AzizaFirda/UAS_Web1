<?php
// backend/api/transactions.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/Transaction.php';

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
$transactionModel = new Transaction();

try {
    switch ($method) {
        case 'GET':
            handleGet($transactionModel, $userId);
            break;
            
        case 'POST':
            handlePost($transactionModel, $userId);
            break;
            
        case 'PUT':
            handlePut($transactionModel, $userId);
            break;
            
        case 'DELETE':
            handleDelete($transactionModel, $userId);
            break;
            
        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}

function handleGet($model, $userId) {
    $id = $_GET['id'] ?? null;
    
    if ($id) {
        // Get single transaction
        $transaction = $model->getById($id, $userId);
        if (!$transaction) {
            sendError('Transaction not found', 404);
        }
        sendSuccess(['transaction' => $transaction]);
    } else {
        // Get all transactions with filters
        $filters = [
            'type' => $_GET['type'] ?? null,
            'account_id' => $_GET['account_id'] ?? null,
            'category_id' => $_GET['category_id'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'limit' => $_GET['limit'] ?? null
        ];
        
        $transactions = $model->getAll($userId, $filters);
        sendSuccess(['transactions' => $transactions]);
    }
}

function handlePost($model, $userId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validation
    if (empty($data['type']) || empty($data['account_id']) || empty($data['amount'])) {
        sendError('Type, account, and amount are required');
    }
    
    if (!in_array($data['type'], ['income', 'expense', 'transfer'])) {
        sendError('Invalid transaction type');
    }
    
    if ($data['type'] === 'transfer' && empty($data['to_account_id'])) {
        sendError('Destination account is required for transfer');
    }
    
    if (($data['type'] === 'income' || $data['type'] === 'expense') && empty($data['category_id'])) {
        sendError('Category is required for income/expense');
    }
    
    $data['user_id'] = $userId;
    $transactionId = $model->create($data);
    
    sendSuccess(['transaction_id' => $transactionId], 'Transaction created successfully');
}

function handlePut($model, $userId) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendError('Transaction ID is required');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validation
    if (empty($data['type']) || empty($data['account_id']) || empty($data['amount'])) {
        sendError('Type, account, and amount are required');
    }
    
    $model->update($id, $userId, $data);
    sendSuccess(null, 'Transaction updated successfully');
}

function handleDelete($model, $userId) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendError('Transaction ID is required');
    }
    
    $model->delete($id, $userId);
    sendSuccess(null, 'Transaction deleted successfully');
}
?>