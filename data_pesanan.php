<?php
// ==================== DATA PESANAN ====================
// File: data_pesanan.php - VERSI FINAL

require_once 'config/database.php';
require_once 'includes/functions.php';

// Ambil semua orders untuk ditampilkan
$stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 100");
$orders = $stmt->fetchAll();

// Hitung statistik
$processCount = count(array_filter($orders, function($o) { return $o['status'] === 'process'; }));
$successCount = count(array_filter($orders, function($o) { return $o['status'] === 'success'; }));
$totalCount = count($orders);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pesanan - Unit Produksi RPL</title>
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
            max-width: 1200px;
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

        .back-btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #764ba2;
        }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }

        .page-title h2 {
            font-size: 32px;
            margin-bottom: 10px;
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

        .stat-icon.process { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .stat-icon.success { background: rgba(16,185,129,0.1); color: #10b981; }
        .stat-icon.total { background: rgba(102,126,234,0.1); color: #667eea; }

        .stat-info h3 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #7f8c8d;
        }

        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 16px;
        }

        .search-box input:focus {
            border-color: #667eea;
            outline: none;
        }

        .orders-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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

        .table-wrapper {
            overflow-x: auto;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 700;
            color: #2c3e50;
        }

        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .order-row.process td:first-child {
            border-left: 4px solid #f59e0b;
        }

        .order-row.success td:first-child {
            border-left: 4px solid #10b981;
        }

        .customer-info h4 {
            margin-bottom: 5px;
        }

        .customer-info .phone {
            color: #667eea;
            font-size: 13px;
        }

        .service-info h4 {
            margin-bottom: 5px;
        }

        .service-info .detail {
            color: #7f8c8d;
            font-size: 13px;
        }

        .drive-link {
            color: #667eea;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
        }

        .detail-btn {
            padding: 6px 12px;
            background: #e8f4fc;
            border: 1px solid #667eea;
            border-radius: 5px;
            color: #667eea;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }

        .detail-btn:hover {
            background: #667eea;
            color: white;
        }

        .amount {
            font-weight: 700;
            color: #667eea;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.process {
            background: rgba(245,158,11,0.1);
            color: #f59e0b;
        }

        .status-badge.success {
            background: rgba(16,185,129,0.1);
            color: #10b981;
        }

        footer {
            text-align: center;
            color: white;
            padding: 20px;
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
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>Data Pesanan</h1>
                <p>Unit Produksi RPL - SMK Negeri 24 Jakarta</p>
            </div>
            <a href="index.php" class="back-btn">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
        </header>

        <div class="page-title">
            <h2>📋 DAFTAR PESANAN</h2>
            <p>Kelola semua pesanan pelanggan</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon process">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $processCount; ?></h3>
                    <p>Dalam Proses</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $successCount; ?></h3>
                    <p>Selesai</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalCount; ?></h3>
                    <p>Total Pesanan</p>
                </div>
            </div>
        </div>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Cari pesanan...">
        </div>

        <div class="orders-container">
            <div class="orders-header">
                <h3><i class="fas fa-list"></i> Semua Pesanan</h3>
                <div class="filter-options">
                    <button class="filter-btn active" data-filter="all">Semua</button>
                    <button class="filter-btn" data-filter="process">Dalam Proses</button>
                    <button class="filter-btn" data-filter="success">Selesai</button>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Pelanggan</th>
                            <th>Layanan</th>
                            <th>Link Drive</th>
                            <th>Detail</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <?php foreach ($orders as $order): ?>
                        <tr class="order-row <?php echo $order['status']; ?>" data-order-number="<?php echo $order['order_number']; ?>">
                            <td>
                                <div class="customer-info">
                                    <h4><?php echo htmlspecialchars($order['customer_name']); ?></h4>
                                    <div><?php echo htmlspecialchars($order['customer_class']); ?></div>
                                    <div class="phone"><i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($order['customer_phone']); ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="service-info">
                                    <h4><?php echo htmlspecialchars($order['service_name']); ?></h4>
                                    <div class="detail"><?php echo htmlspecialchars(substr($order['description'] ?? '', 0, 50)); ?>...</div>
                                </div>
                            </td>
                            <td>
                                <a href="<?php echo htmlspecialchars($order['drive_link']); ?>" target="_blank" class="drive-link">
                                    <i class="fab fa-google-drive"></i> Drive
                                </a>
                            </td>
                            <td>
                                <button class="detail-btn" onclick="openDetail('<?php echo $order['order_number']; ?>')">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                            </td>
                            <td>
                                <span class="amount">Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $order['status']; ?>">
                                    <?php echo $order['status'] === 'process' ? 'Proses' : 'Selesai'; ?>
                                </span>
                                <br>
                                <small>Bayar: <?php echo $order['payment_status']; ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer>
            <p>© 2026 SMK Negeri 24 Jakarta - Unit Produksi RPL</p>
        </footer>
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

    <script>
        function openDetail(orderNumber) {
            const modal = document.getElementById('detailModal');
            const modalBody = document.getElementById('modalBody');
            
            // Cari data order
            const rows = document.querySelectorAll('.order-row');
            let orderData = null;
            
            for (let row of rows) {
                if (row.dataset.orderNumber === orderNumber) {
                    const cells = row.cells;
                    orderData = {
                        customer: cells[0].innerText,
                        service: cells[1].innerText,
                        drive: cells[2].querySelector('a')?.href || '-',
                        amount: cells[4].innerText,
                        status: cells[5].innerText
                    };
                    break;
                }
            }
            
            if (orderData) {
                modalBody.innerHTML = `
                    <div class="info-row">
                        <span class="info-label">Pelanggan:</span>
                        <span class="info-value">${orderData.customer}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Layanan:</span>
                        <span class="info-value">${orderData.service}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Link Drive:</span>
                        <span class="info-value"><a href="${orderData.drive}" target="_blank">${orderData.drive}</a></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Jumlah:</span>
                        <span class="info-value">${orderData.amount}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">${orderData.status}</span>
                    </div>
                `;
            }
            
            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('detailModal').classList.remove('active');
        }

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

        // Search
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const search = this.value.toLowerCase();
            document.querySelectorAll('.order-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        });

        // Close modal when clicking outside
        window.onclick = function(e) {
            const modal = document.getElementById('detailModal');
            if (e.target === modal) {
                closeModal();
            }
        };
    </script>
</body>
</html>