<?php
// ==================== ADMIN UP ====================
// File: admin_up.php - VERSI LENGKAP DENGAN LAPORAN EXCEL

require_once 'config/database.php';
require_once 'includes/functions.php';

// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login admin UP
if (!isset($_SESSION['admin_up_id'])) {
    header('Location: login.php');
    exit;
}

// Session timeout 30 menit
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();

$userId = $_SESSION['admin_up_id'];
$userName = $_SESSION['admin_up_name'];
$username = $_SESSION['admin_up_username'];

// Tampilkan SEMUA pesanan, urutkan dari yang belum diassign
$stmt = $pdo->prepare("SELECT * FROM orders ORDER BY 
    CASE WHEN assigned_to IS NULL THEN 0 ELSE 1 END, 
    created_at DESC");
$stmt->execute();
$orders = $stmt->fetchAll();

// Hitung statistik
$totalOrders = count($orders);
$processOrders = count(array_filter($orders, function($o) { return $o['status'] === 'process'; }));
$completedOrders = count(array_filter($orders, function($o) { return $o['status'] === 'success'; }));
$totalRevenue = array_sum(array_column($orders, 'total'));

// Fungsi format tanggal Indonesia
function formatTanggal($date) {
    if (!$date) return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $t = strtotime($date);
    return date('d', $t) . ' ' . $bulan[date('n', $t)] . ' ' . date('Y H:i', $t);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin UP - Unit Produksi RPL</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .logo h1 {
            color: #2c3e50;
            font-size: 24px;
        }

        .logo p {
            color: #7f8c8d;
            font-size: 14px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .user-detail {
            text-align: right;
        }

        .user-name {
            font-weight: 700;
            color: #2c3e50;
        }

        .user-role {
            font-size: 12px;
            color: #7f8c8d;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .session-timer {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ==================== TOMBOL LAPORAN ==================== */
        .report-buttons {
            display: flex;
            gap: 8px;
            margin-right: 15px;
        }

        .report-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 3px 8px rgba(52, 152, 219, 0.3);
            text-decoration: none;
        }

        .report-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(52, 152, 219, 0.5);
        }

        .report-btn i {
            font-size: 14px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.total { background: rgba(102,126,234,0.1); color: #667eea; }
        .stat-icon.process { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .stat-icon.success { background: rgba(16,185,129,0.1); color: #10b981; }
        .stat-icon.revenue { background: rgba(155,89,182,0.1); color: #9b59b6; }

        .stat-info h3 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #7f8c8d;
        }

        .orders-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .orders-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .filter-options {
            display: flex;
            gap: 10px;
        }

        .filter-btn {
            padding: 8px 16px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .orders-table th {
            background: #f8f9fa;
            padding: 15px 10px;
            text-align: left;
            font-weight: 700;
            color: #2c3e50;
            border-bottom: 2px solid #ddd;
        }

        .orders-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .orders-table tbody tr:hover {
            background: #f5f5f5;
        }

        .order-row.process td:first-child {
            border-left: 4px solid #f59e0b;
        }

        .order-row.success td:first-child {
            border-left: 4px solid #10b981;
        }

        .order-row.pending td:first-child {
            border-left: 4px solid #3498db;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            min-width: 80px;
        }

        .status-badge.process {
            background: rgba(245,158,11,0.2);
            color: #c05621;
            border: 1px solid #f59e0b;
        }

        .status-badge.success {
            background: rgba(16,185,129,0.2);
            color: #047857;
            border: 1px solid #10b981;
        }

        .status-badge.pending {
            background: rgba(52,152,219,0.2);
            color: #1e4a6b;
            border: 1px solid #3498db;
        }

        .assigned-info {
            font-size: 11px;
            color: #7f8c8d;
            margin-top: 3px;
        }

        .action-btn {
            padding: 6px 12px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }

        .take-btn {
            padding: 6px 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .take-btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .detail-btn {
            padding: 5px 10px;
            background: #e8f4fc;
            border: 1px solid #667eea;
            border-radius: 5px;
            color: #667eea;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .detail-btn:hover {
            background: #667eea;
            color: white;
        }

        .drive-link {
            color: #667eea;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            padding: 5px 8px;
            background: #f0f4ff;
            border-radius: 5px;
        }

        .drive-link:hover {
            background: #667eea;
            color: white;
        }

        .footer {
            text-align: center;
            color: white;
            padding: 20px;
            margin-top: 30px;
        }

        .empty-state {
            text-align: center;
            padding: 50px;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .timeout-warning {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #1e293b;
            color: white;
            padding: 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideIn 0.3s;
        }

        .timeout-warning.warning {
            background: #e74c3c;
            animation: pulse 1s infinite;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-label {
            width: 120px;
            font-weight: 600;
            color: #2c3e50;
        }

        .info-value {
            flex: 1;
            color: #666;
        }

        .loading-spinner {
            display: inline-block;
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 992px) {
            .user-info {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .report-buttons {
                order: 3;
                margin-right: 0;
                margin-top: 10px;
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Timeout Warning -->
    <div class="timeout-warning" id="timeoutWarning">
        <i class="fas fa-hourglass-half"></i>
        <span>Sesi akan berakhir dalam <span id="sessionCountdown">30:00</span></span>
    </div>

    <div class="container">
        <header>
            <div class="logo">
                <h1>Dashboard Admin UP</h1>
                <p>Unit Produksi RPL - SMK Negeri 24 Jakarta</p>
            </div>
            
            <div class="user-info">
                <!-- TOMBOL LAPORAN EXCEL -->
                <div class="report-buttons">
                    <a href="report_excel.php?periode=daily" class="report-btn" target="_blank">
                        <i class="fas fa-calendar-day"></i> Harian
                    </a>
                    <a href="report_excel.php?periode=weekly" class="report-btn" target="_blank">
                        <i class="fas fa-calendar-week"></i> Mingguan
                    </a>
                    <a href="report_excel.php?periode=monthly" class="report-btn" target="_blank">
                        <i class="fas fa-calendar-alt"></i> Bulanan
                    </a>
                </div>
                
                <div class="session-timer">
                    <i class="fas fa-clock"></i>
                    <span id="timerDisplay">30:00</span>
                </div>
                <div class="user-detail">
                    <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                    <div class="user-role">Admin Unit Produksi</div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalOrders; ?></h3>
                    <p>Total Pesanan</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon process">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $processOrders; ?></h3>
                    <p>Dalam Proses</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $completedOrders; ?></h3>
                    <p>Selesai</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon revenue">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Rp <?php echo number_format($totalRevenue, 0, ',', '.'); ?></h3>
                    <p>Total Pendapatan</p>
                </div>
            </div>
        </div>

        <div class="orders-container">
            <div class="orders-header">
                <h3><i class="fas fa-clipboard-list"></i> Daftar Pesanan</h3>
                <div class="filter-options">
                    <button class="filter-btn active" data-filter="all">Semua</button>
                    <button class="filter-btn" data-filter="pending">Menunggu</button>
                    <button class="filter-btn" data-filter="process">Dalam Proses</button>
                    <button class="filter-btn" data-filter="success">Selesai</button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>ID Order</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Layanan</th>
                            <th>Link Drive</th>
                            <th>Detail</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Admin</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="11" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>Belum ada pesanan</h4>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php 
                            $no = 1;
                            foreach ($orders as $order): 
                                // Tentukan class untuk row
                                $rowClass = '';
                                if ($order['status'] === 'process') $rowClass = 'process';
                                else if ($order['status'] === 'success') $rowClass = 'success';
                                else if ($order['assigned_to'] === null) $rowClass = 'pending';
                            ?>
                            <tr class="order-row <?php echo $rowClass; ?>" data-order-number="<?php echo $order['order_number']; ?>">
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo $order['order_number']; ?></strong></td>
                                <td><?php echo formatTanggal($order['created_at']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($order['customer_class']); ?></small><br>
                                    <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['service_name']); ?></strong><br>
                                    <?php if (!empty($order['catatan'])): ?>
                                    <small><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars(substr($order['catatan'], 0, 30)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($order['drive_link']); ?>" target="_blank" class="drive-link">
                                        <i class="fab fa-google-drive"></i> Drive
                                    </a>
                                </td>
                                <td>
                                    <button class="detail-btn" onclick="viewDetail('<?php echo $order['order_number']; ?>')">
                                        <i class="fas fa-eye"></i> Detail
                                    </button>
                                </td>
                                <td><strong>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></strong></td>
                                
                                <!-- KOLOM STATUS -->
                                <td>
                                    <?php if ($order['status'] === 'process'): ?>
                                        <span class="status-badge process">Proses</span>
                                    <?php elseif ($order['status'] === 'success'): ?>
                                        <span class="status-badge success">Selesai</span>
                                    <?php elseif ($order['assigned_to'] === null): ?>
                                        <span class="status-badge pending">Menunggu</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- KOLOM ADMIN -->
                                <td>
                                    <?php if ($order['assigned_to']): ?>
                                        <span><?php echo $order['assigned_to']; ?></span>
                                        <?php if (!empty($order['completed_at']) && $order['status'] === 'success'): ?>
                                            <div class="assigned-info">Selesai: <?php echo formatTanggal($order['completed_at']); ?></div>
                                        <?php elseif (!empty($order['assigned_at'])): ?>
                                            <div class="assigned-info"><?php echo formatTanggal($order['assigned_at']); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="assigned-info">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- KOLOM AKSI -->
                                <td>
                                    <?php if ($order['assigned_to'] === null): ?>
                                        <button class="take-btn" onclick="ambilPesanan('<?php echo $order['order_number']; ?>')">
                                            <i class="fas fa-hand-pointer"></i> Ambil
                                        </button>
                                        
                                    <?php elseif ($order['assigned_to'] === $username && $order['status'] === 'process'): ?>
                                        <button class="action-btn" onclick="markAsDone('<?php echo $order['order_number']; ?>')">
                                            <i class="fas fa-check"></i> Selesai
                                        </button>
                                        
                                    <?php elseif ($order['assigned_to'] === $username && $order['status'] === 'success'): ?>
                                        <span class="status-badge success" style="background: transparent; border: none;">
                                            <i class="fas fa-check-circle"></i> Selesai
                                        </span>
                                        
                                    <?php else: ?>
                                        <span style="color: #7f8c8d; font-size: 12px;">
                                            <i class="fas fa-lock"></i> Diambil
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="footer">
            <p>© 2026 SMK Negeri 24 Jakarta - Unit Produksi RPL</p>
        </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detail Pesanan</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                Loading...
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Session Timer
        const timeoutDuration = 1800;
        let timeLeft = timeoutDuration;
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const timerStr = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            document.getElementById('timerDisplay').textContent = timerStr;
            document.getElementById('sessionCountdown').textContent = timerStr;
            
            if (timeLeft <= 60) {
                document.getElementById('timeoutWarning').classList.add('warning');
            }
            
            if (timeLeft <= 0) {
                window.location.href = 'logout.php';
            }
            
            timeLeft--;
        }
        
        setInterval(updateTimer, 1000);
        
        // Reset timer on activity
        document.addEventListener('click', () => timeLeft = timeoutDuration);
        document.addEventListener('keypress', () => timeLeft = timeoutDuration);
        document.addEventListener('mousemove', () => timeLeft = timeoutDuration);

        // Filter
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                document.querySelectorAll('.order-row').forEach(row => {
                    if (filter === 'all' || row.classList.contains(filter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Mark as done
        function markAsDone(orderNumber) {
            if (!confirm('Tandai pesanan ini sebagai selesai?')) return;
            
            showLoading();
            
            $.ajax({
                url: 'order_ajax.php',
                method: 'POST',
                data: {
                    action: 'complete_order',
                    order_number: orderNumber
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        alert('✅ Pesanan selesai!');
                        location.reload();
                    } else {
                        alert('❌ Gagal: ' + response.message);
                    }
                },
                error: function() {
                    hideLoading();
                    alert('❌ Terjadi kesalahan server');
                }
            });
        }

        // Ambil pesanan
        function ambilPesanan(orderNumber) {
            if (!confirm('Ambil pesanan ini untuk dikerjakan?')) return;
            
            showLoading();
            
            $.ajax({
                url: 'order_ajax.php',
                method: 'POST',
                data: {
                    action: 'take_order',
                    order_number: orderNumber
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        alert('✅ Pesanan berhasil diambil!');
                        location.reload();
                    } else {
                        alert('❌ Gagal: ' + response.message);
                    }
                },
                error: function() {
                    hideLoading();
                    alert('❌ Terjadi kesalahan server');
                }
            });
        }

        // View detail
        function viewDetail(orderNumber) {
            const modal = document.getElementById('detailModal');
            const modalBody = document.getElementById('modalBody');
            
            modalBody.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <div class="loading-spinner"></div>
                    <p style="margin-top: 20px;">Mengambil data...</p>
                </div>
            `;
            
            modal.classList.add('active');
            
            $.ajax({
                url: 'order_ajax.php',
                method: 'POST',
                data: {
                    action: 'get_order_detail',
                    order_number: orderNumber
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const o = response.order;
                        modalBody.innerHTML = `
                            <div class="info-row">
                                <span class="info-label">No. Order:</span>
                                <span class="info-value"><strong>${o.order_number}</strong></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Tanggal:</span>
                                <span class="info-value">${o.created_at}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Pelanggan:</span>
                                <span class="info-value">${o.customer_name} (${o.customer_class})</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Kontak:</span>
                                <span class="info-value">${o.customer_phone} / ${o.customer_email}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Layanan:</span>
                                <span class="info-value">${o.service_name}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Jumlah:</span>
                                <span class="info-value">${o.jumlah} unit</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Harga Satuan:</span>
                                <span class="info-value">Rp ${Number(o.harga_satuan).toLocaleString('id-ID')}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Subtotal:</span>
                                <span class="info-value">Rp ${Number(o.subtotal).toLocaleString('id-ID')}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Kode Unik:</span>
                                <span class="info-value">${o.unique_code}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Total:</span>
                                <span class="info-value"><strong>Rp ${Number(o.total).toLocaleString('id-ID')}</strong></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Link Drive:</span>
                                <span class="info-value"><a href="${o.drive_link}" target="_blank">${o.drive_link}</a></span>
                            </div>
                            ${o.catatan ? `
                            <div class="info-row">
                                <span class="info-label">Catatan:</span>
                                <span class="info-value">${o.catatan}</span>
                            </div>
                            ` : ''}
                            <div class="info-row">
                                <span class="info-label">Status:</span>
                                <span class="info-value">${o.status} (Pembayaran: ${o.payment_status})</span>
                            </div>
                            ${o.assigned_to ? `
                            <div class="info-row">
                                <span class="info-label">Admin:</span>
                                <span class="info-value">${o.assigned_to}</span>
                            </div>
                            ` : ''}
                        `;
                    } else {
                        modalBody.innerHTML = `<div style="color:red;padding:20px;">Gagal mengambil data</div>`;
                    }
                },
                error: function() {
                    modalBody.innerHTML = `<div style="color:red;padding:20px;">Error server</div>`;
                }
            });
        }

        function closeModal() {
            document.getElementById('detailModal').classList.remove('active');
        }

        // Close modal on outside click
        window.onclick = function(e) {
            const modal = document.getElementById('detailModal');
            if (e.target === modal) {
                closeModal();
            }
        };

        // Loading functions
        function showLoading() {
            const loading = document.createElement('div');
            loading.id = 'loading';
            loading.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.8); z-index: 99999;
                display: flex; justify-content: center; align-items: center;
                flex-direction: column; color: white;
            `;
            loading.innerHTML = '<div class="loading-spinner"></div><p style="margin-top: 20px;">Loading...</p>';
            document.body.appendChild(loading);
        }

        function hideLoading() {
            const loading = document.getElementById('loading');
            if (loading) loading.remove();
        }
    </script>
</body>
</html>