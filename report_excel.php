<?php
// ==================== REPORT EXCEL ====================
// File: report_excel.php - GENERATE LAPORAN EXCEL (.xlsx)

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['admin_up_id']) && !isset($_SESSION['admin_pusat_id'])) {
    die('Unauthorized');
}

$periode = $_GET['periode'] ?? 'daily';
$username = $_SESSION['admin_up_username'] ?? $_SESSION['admin_pusat_username'] ?? '';

// Tentukan tanggal berdasarkan periode
$dateCondition = "";
$periodeLabel = "";
switch ($periode) {
    case 'daily':
        $dateCondition = "DATE(created_at) = CURDATE()";
        $periodeLabel = "Laporan Harian (" . date('d/m/Y') . ")";
        break;
    case 'weekly':
        $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
        $periodeLabel = "Laporan Mingguan (Minggu ini)";
        break;
    case 'monthly':
        $dateCondition = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        $periodeLabel = "Laporan Bulanan (" . date('F Y') . ")";
        break;
    default:
        $dateCondition = "1=1";
        $periodeLabel = "Laporan Semua Waktu";
}

// Ambil data pesanan
if (isset($_SESSION['admin_pusat_id'])) {
    // Admin Pusat: lihat semua
    $stmt = $pdo->query("SELECT * FROM orders WHERE $dateCondition ORDER BY created_at DESC");
} else {
    // Admin UP: lihat punya sendiri + yang belum diassign
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE (assigned_to = ? OR assigned_to IS NULL) AND $dateCondition ORDER BY created_at DESC");
    $stmt->execute([$username]);
}

$orders = $stmt->fetchAll();

// Hitung total pendapatan
$totalRevenue = 0;
$totalProcess = 0;
$totalSuccess = 0;
$totalPending = 0;

foreach ($orders as $order) {
    $totalRevenue += $order['total'];
    if ($order['status'] == 'process') $totalProcess++;
    elseif ($order['status'] == 'success') $totalSuccess++;
    else $totalPending++;
}

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ==================== HEADER UTAMA ====================
$sheet->setCellValue('A1', 'UNIT PRODUKSI RPL - SMK NEGERI 24 JAKARTA');
$sheet->mergeCells('A1:K1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', $periodeLabel);
$sheet->mergeCells('A2:K2');
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A3', 'Generated: ' . date('d/m/Y H:i:s') . ' | Oleh: ' . $username);
$sheet->mergeCells('A3:K3');
$sheet->getStyle('A3')->getFont()->setItalic(true);
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Baris kosong
$sheet->setCellValue('A4', '');

// ==================== HEADER KOLOM ====================
$headers = [
    'A5' => 'No',
    'B5' => 'No Order',
    'C5' => 'Tanggal',
    'D5' => 'Pelanggan',
    'E5' => 'Kelas',
    'F5' => 'No HP',
    'G5' => 'Email',
    'H5' => 'Layanan',
    'I5' => 'Jumlah',
    'J5' => 'Total',
    'K5' => 'Status',
    'L5' => 'Admin'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// Style header
$sheet->getStyle('A5:L5')->getFont()->setBold(true);
$sheet->getStyle('A5:L5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4F81BD');
$sheet->getStyle('A5:L5')->getFont()->getColor()->setARGB('FFFFFFFF');
$sheet->getStyle('A5:L5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A5:L5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// ==================== DATA PESANAN ====================
$row = 6;
$no = 1;

foreach ($orders as $order) {
    // Tentukan status dalam bahasa Indonesia
    $statusText = '';
    switch ($order['status']) {
        case 'process':
            $statusText = 'Proses';
            break;
        case 'success':
            $statusText = 'Selesai';
            break;
        case 'pending_payment':
            $statusText = 'Pending Bayar';
            break;
        default:
            $statusText = ucfirst($order['status']);
    }
    
    $sheet->setCellValue('A' . $row, $no++);
    $sheet->setCellValue('B' . $row, $order['order_number']);
    $sheet->setCellValue('C' . $row, date('d/m/Y H:i', strtotime($order['created_at'])));
    $sheet->setCellValue('D' . $row, $order['customer_name']);
    $sheet->setCellValue('E' . $row, $order['customer_class']);
    $sheet->setCellValue('F' . $row, $order['customer_phone']);
    $sheet->setCellValue('G' . $row, $order['customer_email']);
    $sheet->setCellValue('H' . $row, $order['service_name']);
    $sheet->setCellValue('I' . $row, $order['jumlah']);
    $sheet->setCellValue('J' . $row, $order['total']);
    $sheet->setCellValue('K' . $row, $statusText);
    $sheet->setCellValue('L' . $row, $order['assigned_to'] ?? '-');
    
    // Format number untuk kolom Jumlah dan Total
    $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('"Rp " #,##0');
    
    // Border untuk setiap baris data
    $sheet->getStyle('A' . $row . ':L' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    // Warna background berdasarkan status
    if ($order['status'] == 'success') {
        $sheet->getStyle('A' . $row . ':L' . $row)->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFE2F0D9'); // Hijau muda
    } elseif ($order['status'] == 'process') {
        $sheet->getStyle('A' . $row . ':L' . $row)->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFFFE699'); // Kuning muda
    }
    
    $row++;
}

// ==================== BARIS STATISTIK ====================
$row += 2; // Kasih 2 baris kosong

// Total Pesanan
$sheet->setCellValue('A' . $row, 'TOTAL PESANAN');
$sheet->mergeCells('A' . $row . ':C' . $row);
$sheet->getStyle('A' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->setCellValue('D' . $row, count($orders));
$sheet->getStyle('D' . $row)->getFont()->setBold(true);
$sheet->getStyle('D' . $row)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);

// Proses
$row++;
$sheet->setCellValue('A' . $row, 'DALAM PROSES');
$sheet->mergeCells('A' . $row . ':C' . $row);
$sheet->getStyle('A' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->setCellValue('D' . $row, $totalProcess);
$sheet->getStyle('D' . $row)->getFont()->setBold(true);

// Selesai
$row++;
$sheet->setCellValue('A' . $row, 'SELESAI');
$sheet->mergeCells('A' . $row . ':C' . $row);
$sheet->getStyle('A' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->setCellValue('D' . $row, $totalSuccess);
$sheet->getStyle('D' . $row)->getFont()->setBold(true);

// Pending
$row++;
$sheet->setCellValue('A' . $row, 'PENDING');
$sheet->mergeCells('A' . $row . ':C' . $row);
$sheet->getStyle('A' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->setCellValue('D' . $row, $totalPending);
$sheet->getStyle('D' . $row)->getFont()->setBold(true);

// Total Pendapatan
$row += 2;
$sheet->setCellValue('A' . $row, 'TOTAL PENDAPATAN');
$sheet->mergeCells('A' . $row . ':C' . $row);
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

$sheet->setCellValue('D' . $row, $totalRevenue);
$sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('"Rp " #,##0');
$sheet->getStyle('D' . $row)->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('D' . $row)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);

// ==================== AUTO SIZE KOLOM ====================
foreach (range('A', 'L') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// ==================== DOWNLOAD FILE ====================
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="laporan_' . $periode . '_' . date('Ymd') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>