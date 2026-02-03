<?php
// backend/controllers/DashboardController.php

require_once __DIR__ . '/../config/database.php';

class DashboardController {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getDashboardData($userId) {
        return [
            'summary' => $this->getSummary($userId),
            'recent_transactions' => $this->getRecentTransactions($userId),
            'expense_by_category' => $this->getExpenseByCategory($userId),
            'monthly_trend' => $this->getMonthlyTrend($userId)
        ];
    }
    
    private function getSummary($userId) {
        $currentMonth = date('Y-m');
        
        $sql = "SELECT 
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
                    (SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) - 
                     SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END)) as balance
                FROM transactions 
                WHERE user_id = :user_id 
                AND DATE_FORMAT(date, '%Y-%m') = :month";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':month' => $currentMonth
        ]);
        
        $monthSummary = $stmt->fetch();
        
        // Get account summary
        $accountSql = "SELECT 
                        SUM(CASE WHEN type IN ('cash', 'mbanking', 'ewallet') THEN current_balance ELSE 0 END) as total_assets
                       FROM accounts WHERE user_id = :user_id";
        
        $accountStmt = $this->db->prepare($accountSql);
        $accountStmt->execute([':user_id' => $userId]);
        $accountSummary = $accountStmt->fetch();
        
        return [
            'month' => [
                'income' => (float)($monthSummary['total_income'] ?? 0),
                'expense' => (float)($monthSummary['total_expense'] ?? 0),
                'balance' => (float)($monthSummary['balance'] ?? 0)
            ],
            'accounts' => [
                'assets' => (float)($accountSummary['total_assets'] ?? 0),
                'liabilities' => (float)($accountSummary['total_liabilities'] ?? 0),
                'net_worth' => (float)(($accountSummary['total_assets'] ?? 0) - ($accountSummary['total_liabilities'] ?? 0))
            ]
        ];
    }
    
    private function getRecentTransactions($userId, $limit = 10) {
        $sql = "SELECT t.*, 
                       a.name as account_name, a.type as account_type,
                       c.name as category_name, c.icon as category_icon, c.color as category_color,
                       ta.name as to_account_name
                FROM transactions t
                LEFT JOIN accounts a ON t.account_id = a.id
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN accounts ta ON t.to_account_id = ta.id
                WHERE t.user_id = :user_id
                ORDER BY t.date DESC, t.time DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    private function getExpenseByCategory($userId) {
        $currentMonth = date('Y-m');
        
        $sql = "SELECT 
                    c.name,
                    c.icon,
                    c.color,
                    SUM(t.amount) as total
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = :user_id 
                AND t.type = 'expense'
                AND DATE_FORMAT(t.date, '%Y-%m') = :month
                GROUP BY c.id, c.name, c.icon, c.color
                ORDER BY total DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':month' => $currentMonth
        ]);
        
        return $stmt->fetchAll();
    }
    
    private function getMonthlyTrend($userId, $months = 6) {
        $sql = "SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
                FROM transactions
                WHERE user_id = :user_id
                AND date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(date, '%Y-%m')
                ORDER BY month ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':months' => $months
        ]);
        
        return $stmt->fetchAll();
    }
}

// API Endpoint
if (basename($_SERVER['PHP_SELF']) === 'dashboard.php') {
    require_once __DIR__ . '/../middleware/AuthMiddleware.php';
    
    AuthMiddleware::requireAuth();
    
    $userId = AuthMiddleware::getUserId();
    $controller = new DashboardController();
    
    try {
        $data = $controller->getDashboardData($userId);
        sendSuccess($data);
    } catch (Exception $e) {
        sendError($e->getMessage(), 500);
    }
}
?>