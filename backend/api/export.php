<?php
// backend/api/export.php
// Export financial reports to Excel and PDF with comprehensive statistics

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Require authentication
AuthMiddleware::requireAuth();

$userId = AuthMiddleware::getUserId();
$format = $_GET['format'] ?? 'excel'; // excel or pdf
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

try {
    $db = getDB();
    
    // Get report data
    $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    // Get overview (summary)
    $incomeStmt = $db->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM transactions 
        WHERE user_id = ? AND type = 'income' 
        AND transaction_date >= ? AND transaction_date <= ?
    ");
    $incomeStmt->execute([$userId, $startDate, $endDate]);
    $income = $incomeStmt->fetch()['total'] ?? 0;
    
    $expenseStmt = $db->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM transactions 
        WHERE user_id = ? AND type = 'expense' 
        AND transaction_date >= ? AND transaction_date <= ?
    ");
    $expenseStmt->execute([$userId, $startDate, $endDate]);
    $expense = $expenseStmt->fetch()['total'] ?? 0;
    
    // Get expense by category
    $categoryStmt = $db->prepare("
        SELECT 
            c.name,
            c.icon as category_icon,
            SUM(t.amount) as total,
            COUNT(t.id) as count
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.type = 'expense'
        AND t.transaction_date >= ? AND t.transaction_date <= ?
        GROUP BY c.id, c.name, c.icon
        ORDER BY total DESC
    ");
    $categoryStmt->execute([$userId, $startDate, $endDate]);
    $categories = $categoryStmt->fetchAll();
    
    // Get transactions for detailed list
    $transactionStmt = $db->prepare("
        SELECT 
            t.id,
            t.amount,
            t.type,
            t.transaction_date,
            c.name as category_name,
            a.name as account_name,
            to_acc.name as to_account_name
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN accounts a ON t.account_id = a.id
        LEFT JOIN accounts to_acc ON t.to_account_id = to_acc.id
        WHERE t.user_id = ? AND t.transaction_date >= ? AND t.transaction_date <= ?
        ORDER BY t.transaction_date DESC
    ");
    $transactionStmt->execute([$userId, $startDate, $endDate]);
    $transactions = $transactionStmt->fetchAll();
    
    // Get monthly trend (last 12 months)
    $trendData = [];
    for ($i = 11; $i >= 0; $i--) {
        $trendMonth = date('Y-m-01', strtotime("-$i months"));
        $trendStart = $trendMonth;
        $trendEnd = date('Y-m-t', strtotime($trendMonth));
        
        $trendIncomeStmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM transactions 
            WHERE user_id = ? AND type = 'income' 
            AND transaction_date >= ? AND transaction_date <= ?
        ");
        $trendIncomeStmt->execute([$userId, $trendStart, $trendEnd]);
        $trendIncome = $trendIncomeStmt->fetch()['total'] ?? 0;
        
        $trendExpenseStmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM transactions 
            WHERE user_id = ? AND type = 'expense' 
            AND transaction_date >= ? AND transaction_date <= ?
        ");
        $trendExpenseStmt->execute([$userId, $trendStart, $trendEnd]);
        $trendExpense = $trendExpenseStmt->fetch()['total'] ?? 0;
        
        $trendData[] = [
            'month' => date('M', strtotime($trendMonth)),
            'income' => floatval($trendIncome),
            'expense' => floatval($trendExpense)
        ];
    }
    
    // Get user info
    $userStmt = $db->prepare("SELECT name FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    // Clear any previous output
    ob_clean();
    
    if ($format === 'excel') {
        exportExcel($transactions, $income, $expense, $categories, $trendData, $month, $year, $user['name']);
    } elseif ($format === 'pdf') {
        exportPDF($transactions, $income, $expense, $categories, $trendData, $month, $year, $user['name']);
    } else {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'Format tidak valid']);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}

function exportExcel($transactions, $income, $expense, $categories, $trendData, $month, $year, $userName) {
    // Create CSV format (compatible with Excel)
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="Laporan_Keuangan_' . $month . '_' . $year . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['LAPORAN KEUANGAN BULANAN'], ';');
    fputcsv($output, ['Nama: ' . $userName], ';');
    fputcsv($output, ['Periode: ' . bulanIndonesia($month) . ' ' . $year], ';');
    fputcsv($output, ['Tanggal Export: ' . date('d-m-Y H:i:s')], ';');
    fputcsv($output, [], ';');
    
    // Summary
    fputcsv($output, ['RINGKASAN KEUANGAN BULAN INI'], ';');
    fputcsv($output, ['Total Pemasukan', formatCurrencyCSV($income)], ';');
    fputcsv($output, ['Total Pengeluaran', formatCurrencyCSV($expense)], ';');
    fputcsv($output, ['Saldo Bersih', formatCurrencyCSV($income - $expense)], ';');
    fputcsv($output, [], ';');
    
    // Category breakdown
    fputcsv($output, ['PENGELUARAN PER KATEGORI'], ';');
    fputcsv($output, ['Kategori', 'Jumlah', 'Transaksi', 'Persentase'], ';');
    
    $totalExpense = array_sum(array_column($categories, 'total'));
    foreach ($categories as $cat) {
        $percentage = $totalExpense > 0 ? ($cat['total'] / $totalExpense * 100) : 0;
        fputcsv($output, [
            $cat['name'],
            formatCurrencyCSV($cat['total']),
            $cat['count'],
            round($percentage, 2) . '%'
        ], ';');
    }
    fputcsv($output, [], ';');
    
    // Trend data
    fputcsv($output, ['TREN 12 BULAN TERAKHIR'], ';');
    fputcsv($output, ['Bulan', 'Pemasukan', 'Pengeluaran', 'Saldo'], ';');
    
    foreach ($trendData as $trend) {
        fputcsv($output, [
            $trend['month'],
            formatCurrencyCSV($trend['income']),
            formatCurrencyCSV($trend['expense']),
            formatCurrencyCSV($trend['income'] - $trend['expense'])
        ], ';');
    }
    fputcsv($output, [], ';');
    
    // Detailed Transactions
    fputcsv($output, ['RINCIAN TRANSAKSI BULANAN'], ';');
    fputcsv($output, ['Tanggal', 'Jenis', 'Kategori', 'Akun', 'Jumlah'], ';');
    
    foreach ($transactions as $t) {
        $typeLabel = $t['type'] === 'income' ? 'Pemasukan' : ($t['type'] === 'expense' ? 'Pengeluaran' : 'Transfer');
        $category = $t['category_name'] ?? ($t['to_account_name'] ?? 'Transfer');
        
        fputcsv($output, [
            date('d-m-Y', strtotime($t['transaction_date'])),
            $typeLabel,
            $category,
            $t['account_name'] ?? '-',
            formatCurrencyCSV($t['amount'])
        ], ';');
    }
    
    fclose($output);
    exit;
}

function exportPDF($transactions, $income, $expense, $categories, $trendData, $month, $year, $userName) {
    // Create comprehensive HTML report
    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.6;
            background: #f5f5f5;
        }
        .page {
            background: white;
            margin: 20px auto;
            padding: 40px;
            max-width: 900px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #10B981;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .header-info {
            text-align: center;
            border-bottom: 2px solid #10B981;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header-info p {
            margin: 5px 0;
            color: #666;
        }
        h2 {
            color: #10B981;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
            font-size: 18px;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #10B981;
            text-align: center;
        }
        .card-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .card-value {
            color: #10B981;
            font-size: 24px;
            font-weight: bold;
        }
        .card.expense .card-value {
            color: #e74c3c;
        }
        .card.balance .card-value {
            color: #27ae60;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #10B981;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .positive {
            color: #27ae60;
            font-weight: 600;
        }
        .negative {
            color: #e74c3c;
            font-weight: 600;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
        .page-break {
            page-break-after: always;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="page">
        <h1>📊 LAPORAN KEUANGAN BULANAN</h1>
        <div class="header-info">
            <p><strong>Nama:</strong> {userName}</p>
            <p><strong>Periode:</strong> {monthName} {year}</p>
            <p><strong>Tanggal Export:</strong> {exportDate}</p>
        </div>

        <h2>💰 RINGKASAN KEUANGAN BULAN INI</h2>
        <div class="summary-cards">
            <div class="card income">
                <div class="card-label">Total Pemasukan</div>
                <div class="card-value">{income}</div>
            </div>
            <div class="card expense">
                <div class="card-label">Total Pengeluaran</div>
                <div class="card-value">{expense}</div>
            </div>
            <div class="card balance">
                <div class="card-label">Saldo Bersih</div>
                <div class="card-value">{balance}</div>
            </div>
        </div>

        <h2>📈 PENGELUARAN PER KATEGORI</h2>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="text-right">Jumlah</th>
                    <th class="text-right">Transaksi</th>
                    <th class="text-right">Persentase</th>
                </tr>
            </thead>
            <tbody>
                {categoryRows}
            </tbody>
        </table>

        <div class="page-break"></div>

        <h2>📊 TREN 12 BULAN TERAKHIR</h2>
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th class="text-right">Pemasukan</th>
                    <th class="text-right">Pengeluaran</th>
                    <th class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                {trendRows}
            </tbody>
        </table>

        <h2>📋 RINCIAN TRANSAKSI</h2>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Kategori</th>
                    <th>Akun</th>
                    <th class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                {transactionRows}
            </tbody>
        </table>

        <div class="footer">
            <p>Laporan ini dibuat secara otomatis oleh Sistem Finance Manager</p>
            <p>© 2026 Personal Finance Manager</p>
        </div>
    </div>
</body>
</html>
HTML;

    // Build category rows
    $categoryRows = '';
    $totalExpenseAmount = array_sum(array_column($categories, 'total'));
    foreach ($categories as $cat) {
        $percentage = $totalExpenseAmount > 0 ? ($cat['total'] / $totalExpenseAmount * 100) : 0;
        $categoryRows .= '<tr>';
        $categoryRows .= '<td>' . htmlspecialchars($cat['name']) . '</td>';
        $categoryRows .= '<td class="text-right negative">' . formatCurrencyHTML($cat['total']) . '</td>';
        $categoryRows .= '<td class="text-right">' . $cat['count'] . '</td>';
        $categoryRows .= '<td class="text-right">' . round($percentage, 1) . '%</td>';
        $categoryRows .= '</tr>';
    }
    
    // Build trend rows
    $trendRows = '';
    foreach ($trendData as $trend) {
        $balance = $trend['income'] - $trend['expense'];
        $balanceClass = $balance >= 0 ? 'positive' : 'negative';
        $trendRows .= '<tr>';
        $trendRows .= '<td>' . $trend['month'] . '</td>';
        $trendRows .= '<td class="text-right positive">' . formatCurrencyHTML($trend['income']) . '</td>';
        $trendRows .= '<td class="text-right negative">' . formatCurrencyHTML($trend['expense']) . '</td>';
        $trendRows .= '<td class="text-right ' . $balanceClass . '">' . formatCurrencyHTML($balance) . '</td>';
        $trendRows .= '</tr>';
    }
    
    // Build transaction rows
    $transactionRows = '';
    foreach ($transactions as $t) {
        $typeLabel = $t['type'] === 'income' ? 'Pemasukan' : ($t['type'] === 'expense' ? 'Pengeluaran' : 'Transfer');
        $category = htmlspecialchars($t['category_name'] ?? ($t['to_account_name'] ?? 'Transfer'));
        $amountClass = $t['type'] === 'income' ? 'positive' : 'negative';
        
        $transactionRows .= '<tr>';
        $transactionRows .= '<td>' . date('d-m-Y', strtotime($t['transaction_date'])) . '</td>';
        $transactionRows .= '<td>' . $typeLabel . '</td>';
        $transactionRows .= '<td>' . $category . '</td>';
        $transactionRows .= '<td>' . htmlspecialchars($t['account_name'] ?? '-') . '</td>';
        $transactionRows .= '<td class="text-right ' . $amountClass . '">' . formatCurrencyHTML($t['amount']) . '</td>';
        $transactionRows .= '</tr>';
    }
    
    // Replace placeholders
    $html = str_replace('{userName}', htmlspecialchars($userName), $html);
    $html = str_replace('{monthName}', bulanIndonesia($month), $html);
    $html = str_replace('{year}', $year, $html);
    $html = str_replace('{exportDate}', date('d-m-Y H:i:s'), $html);
    $html = str_replace('{income}', formatCurrencyHTML($income), $html);
    $html = str_replace('{expense}', formatCurrencyHTML($expense), $html);
    $html = str_replace('{balance}', formatCurrencyHTML($income - $expense), $html);
    $html = str_replace('{categoryRows}', $categoryRows, $html);
    $html = str_replace('{trendRows}', $trendRows, $html);
    $html = str_replace('{transactionRows}', $transactionRows, $html);
    
    // Return HTML as JSON with proper content type for PDF generation on frontend
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'html' => $html,
        'filename' => 'Laporan_Keuangan_' . $month . '_' . $year . '.pdf'
    ]);
    exit;
}

function formatCurrencyCSV($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatCurrencyHTML($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function bulanIndonesia($month) {
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $bulan[$month] ?? '';
}
