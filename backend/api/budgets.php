<?php
// backend/api/budgets.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/Budget.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

AuthMiddleware::requireAuth();

$userId = AuthMiddleware::getUserId();
$method = $_SERVER['REQUEST_METHOD'];
$budgetModel = new Budget();

try {
    switch ($method) {
        case 'GET':
            handleGet($budgetModel, $userId);
            break;
            
        case 'POST':
            handlePost($budgetModel, $userId);
            break;
            
        case 'PUT':
            handlePut($budgetModel, $userId);
            break;
            
        case 'DELETE':
            handleDelete($budgetModel, $userId);
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
        $budgets = $model->getAll($userId);
        sendSuccess(['budgets' => $budgets]);
    } 
    elseif ($action === 'progress') {
        $progress = $model->getProgress($userId);
        sendSuccess(['progress' => $progress]);
    }
    elseif ($action === 'summary') {
        $summary = $model->getSummary($userId);
        sendSuccess(['summary' => $summary]);
    }
    elseif ($action === 'single') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            sendError('Budget ID is required');
        }
        
        $budget = $model->getById($id, $userId);
        if (!$budget) {
            sendError('Budget not found', 404);
        }
        
        sendSuccess(['budget' => $budget]);
    }
    else {
        sendError('Invalid action');
    }
}

function handlePost($model, $userId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['category_id']) || empty($data['amount'])) {
        sendError('Category and amount are required');
    }
    
    $data['user_id'] = $userId;
    $budgetId = $model->create($data);
    
    sendSuccess(['budget_id' => $budgetId], 'Budget created successfully');
}

function handlePut($model, $userId) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendError('Budget ID is required');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['amount'])) {
        sendError('Amount is required');
    }
    
    $model->update($id, $userId, $data);
    sendSuccess(null, 'Budget updated successfully');
}

function handleDelete($model, $userId) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendError('Budget ID is required');
    }
    
    $model->delete($id, $userId);
    sendSuccess(null, 'Budget deleted successfully');
}
?>