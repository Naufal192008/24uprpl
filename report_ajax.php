<?php
// ==================== REPORT AJAX ====================
// File: report_ajax.php - BUAT DOWNLOAD LAPORAN EXCEL

require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['admin_up_id']) && !isset($_SESSION['admin_pusat_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'get_report') {
    $periode = $_POST['periode'] ?? 'daily';
    $username = $_SESSION['admin_up_username'] ?? $_SESSION['admin_pusat_username'] ?? '';
    
    // Tentukan tanggal berdasarkan periode
    $dateCondition = "";
    switch ($periode) {
        case 'daily':
            $dateCondition = "DATE(created_at) = CURDATE()";
            break;
        case 'weekly':
            $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'monthly':
            $dateCondition = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
            break;
        default:
            $dateCondition = "1=1";
    }
    
    // Ambil data pesanan
    if (isset($_SESSION['admin_pusat_id'])) {
        // Admin Pusat: lihat semua
        $stmt = $pdo->query("SELECT * FROM orders WHERE $dateCondition ORDER BY created_at DESC");
    } else {
        // Admin UP: lihat punya sendiri
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE assigned_to = ? AND $dateCondition ORDER BY created_at DESC");
        $stmt->execute([$username]);
    }
    
    $orders = $stmt->fetchAll();
    
    // Hitung total pendapatan
    $totalRevenue = 0;
    foreach ($orders as $order) {
        $totalRevenue += $order['total'];
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total_orders' => count($orders),
        'total_revenue' => $totalRevenue,
        'periode' => $periode
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
?>