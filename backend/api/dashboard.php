<?php
// backend/api/dashboard.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Account.php';
require_once __DIR__ . '/../models/Category.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Require authentication
AuthMiddleware::requireAuth();

$userId = AuthMiddleware::getUserId();

try {
    $db = getDB();
    
    // Get summary data
    $summary = getSummary($db, $userId);
    
    // Get monthly detail (for chart)
    $monthlyDetail = getMonthlyDetail($db, $userId);
    
    // Get expense by category
    $expenseByCategory = getExpenseByCategory($db, $userId);
    
    // Get monthly trend
    $monthlyTrend = getMonthlyTrend($db, $userId);
    
    // Get recent transactions
    $recentTransactions = getRecentTransactions($db, $userId);
    
    $data = [
        'summary' => $summary,
        'monthly_detail' => $monthlyDetail,
        'expense_by_category' => $expenseByCategory,
        'monthly_trend' => $monthlyTrend,
        'recent_transactions' => $recentTransactions
    ];
    
    sendSuccess($data);
    
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}

function getSummary($db, $userId) {
    // Current month
    $currentMonth = date('Y-m-01');
    $nextMonth = date('Y-m-01', strtotime('+1 month'));
    
    // Get income
    $incomeStmt = $db->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM transactions 
        WHERE user_id = ? AND type = 'income' 
        AND transaction_date >= ? AND transaction_date < ?
    ");
    $incomeStmt->execute([$userId, $currentMonth, $nextMonth]);
    $incomeTotal = $incomeStmt->fetch()['total'] ?? 0;
    
    // Get expenses
    $expenseStmt = $db->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM transactions 
        WHERE user_id = ? AND type = 'expense' 
        AND transaction_date >= ? AND transaction_date < ?
    ");
    $expenseStmt->execute([$userId, $currentMonth, $nextMonth]);
    $expenseTotal = $expenseStmt->fetch()['total'] ?? 0;
    
    // Get account balances (only assets, not liabilities)
    $accountsStmt = $db->prepare("
        SELECT COALESCE(SUM(current_balance), 0) as total 
        FROM accounts 
        WHERE user_id = ? AND type IN ('cash', 'bank', 'ewallet')
    ");
    $accountsStmt->execute([$userId]);
    $accountsTotal = $accountsStmt->fetch()['total'] ?? 0;
    
    return [
        'month' => [
            'income' => floatval($incomeTotal),
            'expense' => floatval($expenseTotal),
            'balance' => floatval($incomeTotal - $expenseTotal)
        ],
        'accounts' => [
            'assets' => floatval($accountsTotal)
        ]
    ];
}

function getMonthlyDetail($db, $userId) {
    // Get last 6 months of data
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $startDate = date('Y-m-01', strtotime("-$i months"));
        $endDate = date('Y-m-t', strtotime("-$i months"));
        
        // Get income
        $incomeStmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM transactions 
            WHERE user_id = ? AND type = 'income' 
            AND transaction_date >= ? AND transaction_date <= ?
        ");
        $incomeStmt->execute([$userId, $startDate, $endDate]);
        $income = floatval($incomeStmt->fetch()['total'] ?? 0);
        
        // Get expenses
        $expenseStmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM transactions 
            WHERE user_id = ? AND type = 'expense' 
            AND transaction_date >= ? AND transaction_date <= ?
        ");
        $expenseStmt->execute([$userId, $startDate, $endDate]);
        $expense = floatval($expenseStmt->fetch()['total'] ?? 0);
        
        $months[] = [
            'month' => date('M', strtotime($startDate)),
            'label' => date('M Y', strtotime($startDate)),
            'income' => $income,
            'expense' => $expense
        ];
    }
    
    return $months;
}

function getExpenseByCategory($db, $userId) {
    $currentMonth = date('Y-m-01');
    $nextMonth = date('Y-m-01', strtotime('+1 month'));
    
    $stmt = $db->prepare("
        SELECT 
            c.name,
            c.icon as category_icon,
            SUM(t.amount) as total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.type = 'expense'
        AND t.transaction_date >= ? AND t.transaction_date < ?
        GROUP BY c.id, c.name, c.icon
        ORDER BY total DESC
    ");
    
    $stmt->execute([$userId, $currentMonth, $nextMonth]);
    $results = $stmt->fetchAll();
    
    $colors = ['#e74c3c', '#f39c12', '#f1c40f', '#2ecc71', '#3498db', '#9b59b6'];
    
    foreach ($results as &$result) {
        $result['color'] = $colors[array_key_first($results) % count($colors)];
        $result['total'] = floatval($result['total']);
    }
    
    return $results;
}

function getMonthlyTrend($db, $userId) {
    $pastMonths = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = date('Y-m-01', strtotime("-$i months"));
        $pastMonths[] = $date;
    }
    
    $trend = [];
    for ($i = 0; $i < count($pastMonths); $i++) {
        $startDate = $pastMonths[$i];
        $endDate = $i === count($pastMonths) - 1 
            ? date('Y-m-t', strtotime($startDate))
            : $pastMonths[$i + 1];
        
        // Get income
        $incomeStmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM transactions 
            WHERE user_id = ? AND type = 'income' 
            AND transaction_date >= ? AND transaction_date <= ?
        ");
        $incomeStmt->execute([$userId, $startDate, $endDate]);
        $income = floatval($incomeStmt->fetch()['total'] ?? 0);
        
        // Get expenses
        $expenseStmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM transactions 
            WHERE user_id = ? AND type = 'expense' 
            AND transaction_date >= ? AND transaction_date <= ?
        ");
        $expenseStmt->execute([$userId, $startDate, $endDate]);
        $expense = floatval($expenseStmt->fetch()['total'] ?? 0);
        
        $trend[] = [
            'month' => date('M', strtotime($startDate)),
            'income' => $income,
            'expense' => $expense
        ];
    }
    
    return $trend;
}

function getRecentTransactions($db, $userId) {
    $stmt = $db->prepare("
        SELECT 
            t.id,
            t.amount,
            t.type,
            t.transaction_date as date,
            c.name as category_name,
            c.icon as category_icon,
            a.name as account_name
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        JOIN accounts a ON t.account_id = a.id
        WHERE t.user_id = ?
        ORDER BY t.transaction_date DESC
        LIMIT 10
    ");
    
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll();
    
    foreach ($results as &$result) {
        $result['amount'] = floatval($result['amount']);
    }
    
    return $results;
}
?>
