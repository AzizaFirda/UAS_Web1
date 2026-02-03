<?php
// backend/controllers/ReportController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Transaction.php';

// Install library dengan Composer:
// composer require mpdf/mpdf
// composer require phpoffice/phpspreadsheet

use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportController {
    private $transactionModel;
    
    public function __construct() {
        $this->transactionModel = new Transaction();
    }
    
    public function exportPDF($userId, $dateFrom, $dateTo, $userName = 'User') {
        $transactions = $this->transactionModel->getByDateRange($userId, $dateFrom, $dateTo);
        
        // Calculate totals
        $totalIncome = 0;
        $totalExpense = 0;
        
        foreach ($transactions as $t) {
            if ($t['type'] === 'income') {
                $totalIncome += $t['amount'];
            } elseif ($t['type'] === 'expense') {
                $totalExpense += $t['amount'];
            }
        }
        
        $balance = $totalIncome - $totalExpense;
        
        // Create PDF
        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20
        ]);
        
        $html = $this->generatePDFHTML($transactions, $dateFrom, $dateTo, $userName, $totalIncome, $totalExpense, $balance);
        
        $mpdf->WriteHTML($html);
        
        $filename = 'laporan_keuangan_' . date('YmdHis') . '.pdf';
        $filepath = __DIR__ . '/../../exports/pdf/' . $filename;
        
        $mpdf->Output($filepath, 'F');
        
        return $filename;
    }
    
    private function generatePDFHTML($transactions, $dateFrom, $dateTo, $userName, $totalIncome, $totalExpense, $balance) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; font-size: 11px; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h2 { margin: 5px 0; color: #2c3e50; }
                .info { margin-bottom: 15px; }
                .info table { width: 100%; }
                .info td { padding: 3px 0; }
                .summary { 
                    background: #ecf0f1; 
                    padding: 10px; 
                    margin: 15px 0; 
                    border-radius: 5px; 
                }
                .summary table { width: 100%; }
                .summary td { padding: 5px; font-weight: bold; }
                table.transactions { width: 100%; border-collapse: collapse; margin-top: 10px; }
                table.transactions th { 
                    background: #34495e; 
                    color: white; 
                    padding: 8px; 
                    text-align: left; 
                    font-size: 10px;
                }
                table.transactions td { 
                    border-bottom: 1px solid #ddd; 
                    padding: 6px; 
                    font-size: 10px;
                }
                .income { color: #27ae60; font-weight: bold; }
                .expense { color: #e74c3c; font-weight: bold; }
                .transfer { color: #3498db; font-weight: bold; }
                .footer { 
                    margin-top: 30px; 
                    text-align: center; 
                    font-size: 9px; 
                    color: #7f8c8d; 
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>LAPORAN TRANSAKSI KEUANGAN</h2>
                <p>Periode: ' . date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)) . '</p>
            </div>
            
            <div class="info">
                <table>
                    <tr>
                        <td width="100"><strong>Nama</strong></td>
                        <td>: ' . htmlspecialchars($userName) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Tanggal Cetak</strong></td>
                        <td>: ' . date('d/m/Y H:i:s') . '</td>
                    </tr>
                </table>
            </div>
            
            <div class="summary">
                <table>
                    <tr>
                        <td width="50%">Total Pemasukan:</td>
                        <td class="income" style="text-align: right;">Rp ' . number_format($totalIncome, 0, ',', '.') . '</td>
                    </tr>
                    <tr>
                        <td>Total Pengeluaran:</td>
                        <td class="expense" style="text-align: right;">Rp ' . number_format($totalExpense, 0, ',', '.') . '</td>
                    </tr>
                    <tr style="border-top: 2px solid #34495e;">
                        <td>Saldo:</td>
                        <td style="text-align: right; color: ' . ($balance >= 0 ? '#27ae60' : '#e74c3c') . ';">
                            Rp ' . number_format($balance, 0, ',', '.') . '
                        </td>
                    </tr>
                </table>
            </div>
            
            <table class="transactions">
                <thead>
                    <tr>
                        <th width="8%">No</th>
                        <th width="12%">Tanggal</th>
                        <th width="12%">Tipe</th>
                        <th width="20%">Kategori</th>
                        <th width="20%">Akun</th>
                        <th width="15%">Jumlah</th>
                        <th width="13%">Catatan</th>
                    </tr>
                </thead>
                <tbody>';
        
        $no = 1;
        foreach ($transactions as $t) {
            $typeClass = $t['type'];
            $typeLabel = $t['type'] === 'income' ? 'Pemasukan' : ($t['type'] === 'expense' ? 'Pengeluaran' : 'Transfer');
            
            $html .= '
                    <tr>
                        <td>' . $no++ . '</td>
                        <td>' . date('d/m/Y', strtotime($t['date'])) . '</td>
                        <td class="' . $typeClass . '">' . $typeLabel . '</td>
                        <td>' . ($t['category_name'] ?? '-') . '</td>
                        <td>' . htmlspecialchars($t['account_name']) . '</td>
                        <td class="' . $typeClass . '" style="text-align: right;">
                            Rp ' . number_format($t['amount'], 0, ',', '.') . '
                        </td>
                        <td>' . htmlspecialchars($t['notes'] ?? '-') . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <p>©Copyright by NPM_NAMA_KELAS_UASWEB1</p>
                <p>Dokumen ini dibuat secara otomatis oleh sistem Personal Finance Manager</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    public function exportExcel($userId, $dateFrom, $dateTo, $userName = 'User') {
        $transactions = $this->transactionModel->getByDateRange($userId, $dateFrom, $dateTo);
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator($userName)
            ->setTitle('Laporan Transaksi Keuangan')
            ->setSubject('Financial Report')
            ->setDescription('Laporan transaksi keuangan periode ' . $dateFrom . ' - ' . $dateTo);
        
        // Header
        $sheet->setCellValue('A1', 'LAPORAN TRANSAKSI KEUANGAN');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        $sheet->setCellValue('A2', 'Periode: ' . date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)));
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        
        // Info
        $sheet->setCellValue('A4', 'Nama:');
        $sheet->setCellValue('B4', $userName);
        $sheet->setCellValue('A5', 'Tanggal Cetak:');
        $sheet->setCellValue('B5', date('d/m/Y H:i:s'));
        
        // Table Header
        $row = 7;
        $headers = ['No', 'Tanggal', 'Tipe', 'Kategori', 'Akun', 'Jumlah', 'Catatan'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('34495e');
            $sheet->getStyle($col . $row)->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }
        
        // Data
        $row = 8;
        $no = 1;
        $totalIncome = 0;
        $totalExpense = 0;
        
        foreach ($transactions as $t) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($t['date'])));
            
            $typeLabel = $t['type'] === 'income' ? 'Pemasukan' : ($t['type'] === 'expense' ? 'Pengeluaran' : 'Transfer');
            $sheet->setCellValue('C' . $row, $typeLabel);
            
            $sheet->setCellValue('D' . $row, $t['category_name'] ?? '-');
            $sheet->setCellValue('E' . $row, $t['account_name']);
            $sheet->setCellValue('F' . $row, $t['amount']);
            $sheet->setCellValue('G' . $row, $t['notes'] ?? '-');
            
            // Color coding
            if ($t['type'] === 'income') {
                $totalIncome += $t['amount'];
                $sheet->getStyle('C' . $row . ':F' . $row)->getFont()->getColor()->setRGB('27ae60');
            } elseif ($t['type'] === 'expense') {
                $totalExpense += $t['amount'];
                $sheet->getStyle('C' . $row . ':F' . $row)->getFont()->getColor()->setRGB('e74c3c');
            }
            
            $row++;
        }
        
        // Summary
        $row += 2;
        $sheet->setCellValue('E' . $row, 'Total Pemasukan:');
        $sheet->setCellValue('F' . $row, $totalIncome);
        $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);
        $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('27ae60');
        
        $row++;
        $sheet->setCellValue('E' . $row, 'Total Pengeluaran:');
        $sheet->setCellValue('F' . $row, $totalExpense);
        $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);
        $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('e74c3c');
        
        $row++;
        $balance = $totalIncome - $totalExpense;
        $sheet->setCellValue('E' . $row, 'Saldo:');
        $sheet->setCellValue('F' . $row, $balance);
        $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);
        $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB($balance >= 0 ? '27ae60' : 'e74c3c');
        
        // Number format for amounts
        $sheet->getStyle('F8:F' . ($row - 3))->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('F' . ($row - 2) . ':F' . $row)->getNumberFormat()->setFormatCode('#,##0');
        
        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Footer
        $row += 3;
        $sheet->setCellValue('A' . $row, '©Copyright by NPM_NAMA_KELAS_UASWEB1');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A' . $row)->getFont()->setSize(9)->setItalic(true);
        
        // Save file
        $filename = 'laporan_keuangan_' . date('YmdHis') . '.xlsx';
        $filepath = __DIR__ . '/../../exports/excel/' . $filename;
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);
        
        return $filename;
    }
}

// API Endpoint
if (basename($_SERVER['PHP_SELF']) === 'reports.php') {
    require_once __DIR__ . '/../middleware/AuthMiddleware.php';
    
    AuthMiddleware::requireAuth();
    
    $userId = AuthMiddleware::getUserId();
    $user = AuthMiddleware::getUser();
    $action = $_GET['action'] ?? '';
    
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-t');
    
    $controller = new ReportController();
    
    try {
        if ($action === 'pdf') {
            $filename = $controller->exportPDF($userId, $dateFrom, $dateTo, $user['name']);
            sendSuccess(['filename' => $filename, 'url' => '/exports/pdf/' . $filename], 'PDF generated successfully');
        } elseif ($action === 'excel') {
            $filename = $controller->exportExcel($userId, $dateFrom, $dateTo, $user['name']);
            sendSuccess(['filename' => $filename, 'url' => '/exports/excel/' . $filename], 'Excel generated successfully');
        } else {
            sendError('Invalid action');
        }
    } catch (Exception $e) {
        sendError($e->getMessage(), 500);
    }
}
?>