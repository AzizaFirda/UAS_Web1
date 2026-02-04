<?php
// backend/api/statistics.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

AuthMiddleware::requireAuth();

$userId = AuthMiddleware::getUserId();
$db = getDB();

try {
    $action = $_GET['action'] ?? 'overview';
    
    switch ($action) {
        case 'overview':
            getOverview($db, $userId);
            break;
            
        case 'category':
            getCategoryStats($db, $userId);
            break;
            
        case 'account':
            getAccountStats($db, $userId);
            break;
            
        case 'trend':
            getTrendData($db, $userId);
            break;
            
        case 'calendar':
            getCalendarData($db, $userId);
            break;
            
        default:
            sendError('Invalid action');
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}

function getOverview($db, $userId) {
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    
    // Monthly summary
    $sql = "SELECT 
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense,
                COUNT(CASE WHEN type = 'income' THEN 1 END) as income_count,
                COUNT(CASE WHEN type = 'expense' THEN 1 END) as expense_count
            FROM transactions 
            WHERE user_id = :user_id 
            AND MONTH(transaction_date) = :month 
            AND YEAR(transaction_date) = :year";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':user_id' => $userId, ':month' => $month, ':year' => $year]);
    $monthly = $stmt->fetch();
    
    // Daily average
    $dayOfMonth = date('d');
    $avgIncome = $monthly['income'] / $dayOfMonth;
    $avgExpense = $monthly['expense'] / $dayOfMonth;
    
    sendSuccess([
        'monthly' => $monthly,
        'daily_average' => [
            'income' => round($avgIncome, 2),
            'expense' => round($avgExpense, 2)
        ]
    ]);
}

function getCategoryStats($db, $userId) {
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    $type = $_GET['type'] ?? 'expense';
    
    $sql = "SELECT 
                c.id,
                c.name,
                c.icon,
                c.color,
                COUNT(t.id) as count,
                SUM(t.amount) as total,
                AVG(t.amount) as average
            FROM categories c
            LEFT JOIN transactions t ON c.id = t.category_id 
                AND MONTH(t.transaction_date) = :month 
                AND YEAR(t.transaction_date) = :year
            WHERE c.user_id = :user_id 
            AND c.type = :type
            GROUP BY c.id, c.name, c.icon, c.color
            HAVING total > 0
            ORDER BY total DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':user_id' => $userId,
        ':month' => $month,
        ':year' => $year,
        ':type' => $type
    ]);
    
    sendSuccess(['categories' => $stmt->fetchAll()]);
}

function getAccountStats($db, $userId) {
    $sql = "SELECT 
                a.id,
                a.name,
                a.type,
                a.icon,
                a.color,
                a.initial_balance,
                a.current_balance,
                (a.current_balance - a.initial_balance) as `change`,
                COUNT(t.id) as transaction_count
            FROM accounts a
            LEFT JOIN transactions t ON a.id = t.account_id OR a.id = t.to_account_id
            WHERE a.user_id = :user_id
            GROUP BY a.id, a.name, a.type, a.icon, a.color, a.initial_balance, a.current_balance
            ORDER BY a.current_balance DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    
    sendSuccess(['accounts' => $stmt->fetchAll()]);
}

function getTrendData($db, $userId) {
    $months = $_GET['months'] ?? 12;
    
    $sql = "SELECT 
                DATE_FORMAT(transaction_date, '%Y-%m') as period,
                DATE_FORMAT(transaction_date, '%b %Y') as label,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense,
                COUNT(CASE WHEN type = 'income' THEN 1 END) as income_count,
                COUNT(CASE WHEN type = 'expense' THEN 1 END) as expense_count
            FROM transactions
            WHERE user_id = :user_id
            AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY period, label
            ORDER BY period ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':user_id' => $userId, ':months' => $months]);
    
    sendSuccess(['trend' => $stmt->fetchAll()]);
}

function getCalendarData($db, $userId) {
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    
    $sql = "SELECT 
                DATE(transaction_date) as day,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense,
                COUNT(*) as transaction_count
            FROM transactions
            WHERE user_id = :user_id 
            AND MONTH(transaction_date) = :month 
            AND YEAR(transaction_date) = :year
            GROUP BY DATE(transaction_date)
            ORDER BY transaction_date";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':user_id' => $userId, ':month' => $month, ':year' => $year]);
    
    sendSuccess(['calendar' => $stmt->fetchAll()]);
}
?>