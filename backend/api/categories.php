<?php
// backend/api/categories.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/Category.php';

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
$categoryModel = new Category();

try {
    switch ($method) {
        case 'GET':
            handleGet($categoryModel, $userId);
            break;
            
        case 'POST':
            handlePost($categoryModel, $userId);
            break;
        
        case 'PUT':
            handlePut($categoryModel, $userId);
            break;
        
        case 'DELETE':
            handleDelete($categoryModel, $userId);
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
        $type = $_GET['type'] ?? null;
        $categories = $model->getAll($userId, $type);
        sendSuccess(['categories' => $categories]);
    } 
    elseif ($action === 'statistics') {
        $month = $_GET['month'] ?? null;
        $year = $_GET['year'] ?? null;
        $statistics = $model->getStatistics($userId, $month, $year);
        sendSuccess(['statistics' => $statistics]);
    }
    elseif ($action === 'single') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            sendError('Category ID is required');
        }
        
        $category = $model->getById($id, $userId);
        if (!$category) {
            sendError('Category not found', 404);
        }
        
        sendSuccess(['category' => $category]);
    }
    else {
        sendError('Invalid action');
    }
}

function handlePost($model, $userId) {
    $action = $_GET['action'] ?? 'create';
    
    if ($action === 'create') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['name']) || empty($data['type'])) {
            sendError('Name and type are required');
        }
        
        if (!in_array($data['type'], ['income', 'expense'])) {
            sendError('Type must be income or expense');
        }
        
        $data['user_id'] = $userId;
        $categoryId = $model->create($data);
        
        sendSuccess(['category_id' => $categoryId], 'Category created successfully');
    } 
    else {
        sendError('Invalid action');
    }
}

function handlePut($model, $userId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        sendError('Category ID is required');
    }
    
    if (empty($data['name']) || empty($data['type'])) {
        sendError('Name and type are required');
    }
    
    if (!in_array($data['type'], ['income', 'expense'])) {
        sendError('Type must be income or expense');
    }
    
    $model->update($data['id'], $userId, $data);
    sendSuccess(null, 'Category updated successfully');
}

function handleDelete($model, $userId) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendError('Category ID is required');
    }
    
    $model->delete($id, $userId);
    sendSuccess(null, 'Category deleted successfully');
}
?>