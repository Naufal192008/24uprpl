<?php
// ==================== ADMIN PUSAT ====================
// File: admin_pusat.php - FIX VERSION

require_once 'config/database.php';
require_once 'includes/functions.php';

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== CEK SESSION ADMIN PUSAT ====================
if (!isset($_SESSION['admin_pusat_id'])) {
    header('Location: login_pusat.php');
    exit;
}

// Extra security - cek IP dan User Agent
if (isset($_SESSION['admin_pusat_ip']) && $_SESSION['admin_pusat_ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_destroy();
    header('Location: login_pusat.php?error=session_hijacked');
    exit;
}

if (isset($_SESSION['admin_pusat_user_agent']) && $_SESSION['admin_pusat_user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_destroy();
    header('Location: login_pusat.php?error=session_hijacked');
    exit;
}

// Session timeout - 30 menit tidak aktif
if (isset($_SESSION['admin_pusat_last_activity']) && time() - $_SESSION['admin_pusat_last_activity'] > 1800) {
    session_destroy();
    header('Location: login_pusat.php?error=timeout');
    exit;
}

// Update last activity
$_SESSION['admin_pusat_last_activity'] = time();

// ==================== AMBIL DATA ====================
$userId = $_SESSION['admin_pusat_id'];
$userName = $_SESSION['admin_pusat_name'];

// Ambil statistik
$stats = getAdminPusatStats($pdo);

// Ambil semua admin UP
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin_up' ORDER BY id DESC");
$admins = $stmt->fetchAll();

// Ambil semua orders
$stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 100");
$orders = $stmt->fetchAll();

// Ambil pending payments
$stmt = $pdo->query("SELECT p.*, o.customer_name, o.customer_phone FROM payments p LEFT JOIN orders o ON p.order_number = o.order_number WHERE p.verified = FALSE ORDER BY p.payment_time DESC");
$payments = $stmt->fetchAll();

// Ambil activity logs
$stmt = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100");
$logs = $stmt->fetchAll();

// Ambil admin logs
try {
    $stmt = $pdo->query("SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 100");
    $adminLogs = $stmt->fetchAll();
} catch (Exception $e) {
    $adminLogs = [];
}

// Ambil email logs
$stmt = $pdo->query("SELECT * FROM email_logs ORDER BY created_at DESC LIMIT 100");
$emailLogs = $stmt->fetchAll();

// Hitung password expired
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin_up' AND password_expiry <= NOW()");
$expiredCount = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin_up' AND password_expiry > NOW() AND password_expiry <= DATE_ADD(NOW(), INTERVAL 24 HOUR)");
$expiringCount = $stmt->fetch()['total'];

// Helper function untuk format tanggal
function formatDate($date) {
    if (!$date) return '-';
    return date('d/m/Y H:i', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Pusat - Unit Produksi RPL</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ==================== RESET & VARIABLES ==================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1e293b;
            --gray: #64748b;
            --light: #f8fafc;
            --white: #ffffff;
            --shadow: 0 5px 20px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.1);
            --radius: 12px;
            --radius-lg: 20px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            color: var(--dark);
            line-height: 1.6;
        }

        /* ==================== SIDEBAR ==================== */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a237e 0%, #0d1647 100%);
            color: white;
            display: flex;
            flex-direction: column;
            box-shadow: 5px 0 20px rgba(0,0,0,0.1);
            height: 100vh;
            position: sticky;
            top: 0;
            overflow-y: auto;
        }

        .sidebar-logo {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.2);
        }

        .sidebar-logo h2 {
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            color: white;
            margin: 0;
        }

        .sidebar-logo h2 i {
            color: #ffd700;
            font-size: 2rem;
        }

        .sidebar-logo .subtitle {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-top: 8px;
            color: #e0e0e0;
        }

        .user-profile {
            padding: 25px;
            background: rgba(0,0,0,0.15);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .user-info h4 {
            margin: 0;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .user-info p {
            margin: 5px 0 0;
            opacity: 0.8;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .security-badge {
            margin: 15px 25px;
            padding: 10px 15px;
            background: rgba(255,215,0,0.15);
            border: 1px solid rgba(255,215,0,0.3);
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
        }

        .security-badge i {
            color: #ffd700;
        }

        .ip-info {
            font-family: monospace;
            font-size: 11px;
            background: rgba(0,0,0,0.3);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .nav-menu {
            flex: 1;
            padding: 20px 0;
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            font-weight: 500;
            cursor: pointer;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .nav-link.active {
            background: rgba(255,255,255,0.15);
            border-left-color: #ffd700;
            color: white;
        }

        .nav-link i {
            width: 24px;
            text-align: center;
            font-size: 1.2rem;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            margin-left: auto;
            color: white;
        }

        .badge-primary { background: var(--primary); }
        .badge-danger { background: var(--danger); }
        .badge-warning { background: var(--warning); }
        .badge-info { background: var(--info); }
        .badge-success { background: var(--success); }
        .badge-secondary { background: var(--gray); }

        .sidebar-footer {
            padding: 25px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .logout-btn {
            width: 100%;
            padding: 12px;
            background: rgba(231,76,60,0.2);
            border: 1px solid rgba(231,76,60,0.3);
            border-radius: var(--radius);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }

        .logout-btn:hover {
            background: rgba(231,76,60,0.3);
            transform: translateY(-2px);
        }

        /* ==================== MAIN CONTENT ==================== */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--light);
            border-top-left-radius: 40px;
            border-bottom-left-radius: 40px;
            overflow: hidden;
            height: 100vh;
            overflow-y: auto;
        }

        .topbar {
            background: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }

        .page-title h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1a237e;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .breadcrumb {
            font-size: 0.9rem;
            color: var(--gray);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .datetime {
            font-weight: 600;
            color: var(--dark);
        }

        .content {
            padding: 30px;
            flex: 1;
        }

        /* ==================== CARDS ==================== */
        .card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid #eef2f7;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eef2f7;
            background: rgba(102,126,234,0.02);
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 25px;
        }

        .card-footer {
            padding: 20px 25px;
            border-top: 1px solid #eef2f7;
            background: rgba(102,126,234,0.02);
        }

        /* ==================== STATS GRID ==================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow);
            border: 1px solid #eef2f7;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 800;
            margin: 0;
            color: var(--dark);
        }

        .stat-info p {
            margin: 5px 0 0;
            color: var(--gray);
            font-weight: 600;
        }

        /* ==================== TABLES ==================== */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            text-align: left;
            padding: 15px 20px;
            background: #f8fafc;
            color: var(--dark);
            font-weight: 700;
            border-bottom: 2px solid #eef2f7;
        }

        .table td {
            padding: 15px 20px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        /* ==================== BUTTONS ==================== */
        .btn {
            padding: 10px 20px;
            border-radius: var(--radius);
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            text-decoration: none;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ee5a6f, #f093fb);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, var(--info), #2563eb);
            color: white;
        }

        .btn-outline-primary {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline-danger {
            background: transparent;
            border: 2px solid var(--danger);
            color: var(--danger);
        }

        .btn-outline-warning {
            background: transparent;
            border: 2px solid var(--warning);
            color: var(--warning);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        /* ==================== FORMS ==================== */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius);
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius);
            font-size: 14px;
            background: white;
        }

        .input-group {
            display: flex;
            align-items: center;
        }

        .input-group .form-control {
            flex: 1;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        /* ==================== MODAL ==================== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            overflow-y: auto;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-dialog {
            width: 90%;
            max-width: 800px;
            margin: 30px auto;
        }

        .modal-content {
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-weight: 700;
            margin: 0;
            font-size: 1.2rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #eef2f7;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* ==================== LOADING ==================== */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 99999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .loading.active {
            display: flex;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid rgba(255,255,255,0.1);
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* ==================== TOAST ==================== */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        }

        .toast {
            background: white;
            border-radius: var(--radius);
            padding: 15px 20px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 300px;
            animation: slideIn 0.3s ease;
            border-left: 5px solid transparent;
        }

        .toast.success { border-left-color: var(--success); }
        .toast.error { border-left-color: var(--danger); }
        .toast.warning { border-left-color: var(--warning); }
        .toast.info { border-left-color: var(--info); }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* ==================== EXPIRY WARNING ==================== */
        .expiry-warning {
            background: #fff3cd;
            border-left: 5px solid var(--warning);
            padding: 20px 25px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        /* ==================== UTILITY CLASSES ==================== */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }

        .col-6 { width: 50%; padding: 0 10px; }
        .col-4 { width: 33.33%; padding: 0 10px; }
        .col-8 { width: 66.66%; padding: 0 10px; }
        .col-12 { width: 100%; padding: 0 10px; }

        .d-flex { display: flex; }
        .align-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .justify-end { justify-content: flex-end; }
        .text-center { text-align: center; }
        .w-100 { width: 100%; }
        .gap-2 { gap: 0.5rem; }
        .gap-4 { gap: 1.5rem; }

        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 1rem; }
        .mb-4 { margin-bottom: 1.5rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-3 { margin-top: 1rem; }
        .mt-4 { margin-top: 1.5rem; }
        .me-2 { margin-right: 0.5rem; }
        .ms-auto { margin-left: auto; }

        .p-0 { padding: 0; }
        .p-2 { padding: 0.5rem; }
        .p-3 { padding: 1rem; }
        .p-4 { padding: 1.5rem; }

        .fw-bold { font-weight: 700; }
        .small { font-size: 0.875em; }
        .text-muted { color: var(--gray); }
        .text-success { color: var(--success); }
        .text-danger { color: var(--danger); }
        .text-warning { color: var(--warning); }
        .text-info { color: var(--info); }

        .empty-state {
            text-align: center;
            padding: 50px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .list-group-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eef2f7;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 992px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .main-content {
                border-radius: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .col-6, .col-4, .col-8 {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- LOADING -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <div class="loading-text" style="color: white; font-size: 18px; margin-top: 20px;">MEMPROSES...</div>
    </div>
    
    <!-- TOAST CONTAINER -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="sidebar-logo">
            <h2>
                <i class="fas fa-shield-alt"></i>
                ADMIN PUSAT
            </h2>
            <div class="subtitle">Unit Produksi RPL • Super Admin</div>
        </div>
        
        <div class="user-profile">
            <div class="user-avatar" id="sidebarAvatar"><?php echo strtoupper(substr($userName, 0, 2)); ?></div>
            <div class="user-info">
                <h4 id="sidebarName"><?php echo htmlspecialchars($userName); ?></h4>
                <p>
                    <i class="fas fa-id-badge"></i>
                    <span id="sidebarUsername"><?php echo htmlspecialchars($_SESSION['admin_pusat_username']); ?></span>
                </p>
            </div>
        </div>
        
        <div class="security-badge">
            <i class="fas fa-shield-check"></i>
            <div style="flex:1;">
                <small>IP: <span class="ip-info"><?php echo $_SERVER['REMOTE_ADDR']; ?></span></small><br>
                <small>Session: <span id="sessionTimer">30:00</span></small>
            </div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <div class="nav-link active" data-page="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </div>
            </li>
            <li class="nav-item">
                <div class="nav-link" data-page="manage-admins">
                    <i class="fas fa-users-cog"></i>
                    <span>Kelola Admin UP</span>
                    <span class="badge badge-primary" id="totalAdminBadge"><?php echo count($admins); ?></span>
                </div>
            </li>
            <li class="nav-item">
                <div class="nav-link" data-page="reset-passwords">
                    <i class="fas fa-key"></i>
                    <span>Reset Password</span>
                    <span class="badge badge-danger" id="expiryBadge"><?php echo $expiredCount + $expiringCount; ?></span>
                </div>
            </li>
            <li class="nav-item">
                <div class="nav-link" data-page="monitor-orders">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Monitor Pesanan</span>
                    <span class="badge badge-warning" id="orderBadge"><?php echo $stats['process_orders']; ?></span>
                </div>
            </li>
            <li class="nav-item">
                <div class="nav-link" data-page="verify-payments">
                    <i class="fas fa-credit-card"></i>
                    <span>Verifikasi Pembayaran</span>
                    <span class="badge badge-info" id="paymentBadge"><?php echo count($payments); ?></span>
                </div>
            </li>
            <li class="nav-item">
                <div class="nav-link" data-page="activity-logs">
                    <i class="fas fa-history"></i>
                    <span>Log Aktivitas</span>
                </div>
            </li>
            <li class="nav-item">
                <div class="nav-link" data-page="admin-logs">
                    <i class="fas fa-shield-lock"></i>
                    <span>Log Keamanan</span>
                    <span class="badge badge-secondary"><?php echo count($adminLogs); ?></span>
                </div>
            </li>
            <li class="nav-item">
                <div class="nav-link" data-page="email-logs">
                    <i class="fas fa-envelope"></i>
                    <span>Log Email</span>
                </div>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <button onclick="handleLogout()" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </button>
        </div>
    </nav>
    
    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="topbar">
            <div class="page-title">
                <h1 id="pageTitle">Dashboard Admin Pusat</h1>
                <div class="breadcrumb" id="breadcrumb">
                    <i class="fas fa-home"></i> Beranda / Dashboard
                </div>
            </div>
            
            <div class="datetime" id="currentDateTime"></div>
        </header>
        
        <div class="content" id="contentArea">
            <!-- Dynamic content akan diisi JavaScript -->
        </div>
    </main>

    <!-- MODAL TAMBAH/EDIT ADMIN -->
    <div id="adminModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminModalTitle">
                        <i class="fas fa-user-plus me-2"></i>Tambah Admin Baru
                    </h5>
                    <button class="modal-close" onclick="closeModal('adminModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="adminForm">
                        <input type="hidden" id="adminId">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="adminName" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" id="adminUsername" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" id="adminEmail" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">No. HP</label>
                                    <input type="tel" class="form-control" id="adminPhone" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-8">
                                <div class="form-group">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="adminPassword" required>
                                        <button class="btn btn-primary" type="button" onclick="generateRandomPassword()">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Password akan dikirim ke email (berlaku <?php echo PASSWORD_EXPIRY; ?> menit)</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" id="adminStatus">
                                        <option value="active">Aktif</option>
                                        <option value="inactive">Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" id="adminNotes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger" onclick="closeModal('adminModal')">Batal</button>
                    <button class="btn btn-success" onclick="saveAdmin()">Simpan Admin</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL RESET PASSWORD -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-dialog" style="max-width: 500px;">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--warning);">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>Reset Password Admin
                    </h5>
                    <button class="modal-close" onclick="closeModal('resetPasswordModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Reset password untuk: <strong id="resetAdminName"></strong></p>
                    <div class="form-group">
                        <label class="form-label">Password Baru</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="newPassword" readonly>
                            <button class="btn btn-primary" type="button" onclick="generateNewPassword()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Tujuan</label>
                        <input type="email" class="form-control" id="resetEmail" readonly>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Password akan dikirim ke email di atas dan berlaku <?php echo PASSWORD_EXPIRY; ?> menit.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger" onclick="closeModal('resetPasswordModal')">Batal</button>
                    <button class="btn btn-warning" onclick="confirmResetPassword()">Reset & Kirim Email</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DETAIL PESANAN -->
    <div id="orderDetailModal" class="modal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--info);">
                    <h5 class="modal-title">
                        <i class="fas fa-file-alt me-2"></i>Detail Pesanan
                    </h5>
                    <button class="modal-close" onclick="closeModal('orderDetailModal')">&times;</button>
                </div>
                <div class="modal-body" id="orderDetailBody">
                    Loading...
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('orderDetailModal')">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL VERIFIKASI PEMBAYARAN -->
    <div id="verifyPaymentModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--success);">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>Verifikasi Pembayaran
                    </h5>
                    <button class="modal-close" onclick="closeModal('verifyPaymentModal')">&times;</button>
                </div>
                <div class="modal-body" id="paymentDetailBody">
                    Loading...
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger" onclick="rejectPayment()">Tolak</button>
                    <button class="btn btn-success" onclick="verifyPayment()">Verifikasi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
       <!-- JAVASCRIPT - URUTAN PENTING! -->
    
    <!-- 1. JQuery dulu -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
   
    
    
    <!-- 4. Script utama Anda TERAKHIR -->
    <script>
        // ==================== DATA DARI PHP ====================
        const stats = <?php echo json_encode($stats); ?>;
        let admins = <?php echo json_encode($admins); ?>;
        const orders = <?php echo json_encode($orders); ?>;
        let payments = <?php echo json_encode($payments); ?>;
        const logs = <?php echo json_encode($logs); ?>;
        const adminLogs = <?php echo json_encode($adminLogs); ?>;
        const emailLogs = <?php echo json_encode($emailLogs); ?>;
        const expiredCount = <?php echo $expiredCount; ?>;
        const expiringCount = <?php echo $expiringCount; ?>;
        
        // ==================== VARIABEL GLOBAL ====================
        let currentPage = 'dashboard';
        let selectedUserId = null;
        let selectedPaymentId = null;
        let sessionTimer = null;
        let sessionTimeout = 1800;

        // ==================== FUNGSI UTILITY ====================
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function showToast(type, title, message) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <div style="flex:1;">
                    <strong>${title}</strong>
                    <div style="font-size: 13px; margin-top: 5px;">${message}</div>
                </div>
                <button onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0 10px;">&times;</button>
            `;
            container.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 5000);
        }

        function showLoading() {
            document.getElementById('loading').classList.add('active');
        }

        function hideLoading() {
            document.getElementById('loading').classList.remove('active');
        }

        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('currentDateTime').textContent = now.toLocaleDateString('id-ID', options);
        }

        function startSessionTimer() {
            if (sessionTimer) clearInterval(sessionTimer);
            
            const loginTime = <?php echo $_SESSION['admin_pusat_last_activity'] ?? time(); ?>;
            
            sessionTimer = setInterval(() => {
                const now = Math.floor(Date.now() / 1000);
                const elapsed = now - loginTime;
                const remaining = Math.max(0, sessionTimeout - elapsed);
                
                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                
                document.getElementById('sessionTimer').textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (remaining <= 0) {
                    clearInterval(sessionTimer);
                    showToast('warning', 'Sesi Berakhir', 'Anda akan dialihkan ke halaman login');
                    setTimeout(() => {
                        window.location.href = 'logout.php';
                    }, 2000);
                }
            }, 1000);
        }

        // ==================== NAVIGASI ====================
        function setupNavigation() {
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    const page = this.getAttribute('data-page');
                    currentPage = page;
                    loadPage(page);
                });
            });
        }

        function loadPage(page) {
            const titles = {
                'dashboard': 'Dashboard Admin Pusat',
                'manage-admins': 'Kelola Admin Unit Produksi',
                'reset-passwords': 'Reset Password Admin',
                'monitor-orders': 'Monitor Semua Pesanan',
                'verify-payments': 'Verifikasi Pembayaran',
                'activity-logs': 'Log Aktivitas Sistem',
                'admin-logs': 'Log Keamanan Admin',
                'email-logs': 'Log Pengiriman Email'
            };
            
            document.getElementById('pageTitle').textContent = titles[page];
            document.getElementById('breadcrumb').innerHTML = `<i class="fas fa-home"></i> Admin Pusat / ${titles[page]}`;
            
            // Panggil fungsi sesuai halaman
            if (page === 'dashboard') loadDashboard();
            else if (page === 'manage-admins') loadManageAdmins();
            else if (page === 'reset-passwords') loadResetPasswords();
            else if (page === 'monitor-orders') loadMonitorOrders();
            else if (page === 'verify-payments') loadVerifyPayments();
            else if (page === 'activity-logs') loadActivityLogs();
            else if (page === 'admin-logs') loadAdminLogs();
            else if (page === 'email-logs') loadEmailLogs();
        }

        // ==================== LOAD DASHBOARD ====================
        function loadDashboard() {
            let logsHtml = '';
            logs.slice(0, 10).forEach(log => {
                const date = new Date(log.created_at);
                let badgeClass = 'badge badge-info';
                if (log.level === 'error') badgeClass = 'badge badge-danger';
                else if (log.level === 'warning') badgeClass = 'badge badge-warning';
                else if (log.level === 'success') badgeClass = 'badge badge-success';
                
                logsHtml += `
                    <tr>
                        <td>${date.toLocaleString('id-ID')}</td>
                        <td>${log.username || '-'}</td>
                        <td><span class="${badgeClass}">${log.action}</span></td>
                        <td>${log.message}</td>
                    </tr>
                `;
            });
            
            let expiryHtml = '';
            if (expiredCount > 0 || expiringCount > 0) {
                expiryHtml = `
                    <div class="expiry-warning">
                        <div>
                            <h6 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i>
                                <strong>PERINGATAN PASSWORD!</strong>
                            </h6>
                            <p style="margin: 10px 0 0; color: #856404;">
                                Terdapat <strong>${expiredCount}</strong> admin dengan password expired dan 
                                <strong>${expiringCount}</strong> admin akan expired dalam 24 jam.
                            </p>
                        </div>
                        <div>
                            <button class="btn btn-warning btn-sm" onclick="loadPage('reset-passwords')">
                                <i class="fas fa-sync-alt"></i> Reset Sekarang
                            </button>
                        </div>
                    </div>
                `;
            }
            
            document.getElementById('contentArea').innerHTML = `
                ${expiryHtml}
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(52,152,219,0.1); color: #3498db;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>${stats.total_admins}</h3>
                            <p>Total Admin UP</p>
                            <small>${stats.active_admins} Aktif</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(46,204,113,0.1); color: #27ae60;">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3>${stats.total_orders}</h3>
                            <p>Total Pesanan</p>
                            <small>${stats.process_orders} Proses | ${stats.completed_orders} Selesai</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(155,89,182,0.1); color: #9b59b6;">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Rp ${stats.total_revenue.toLocaleString('id-ID')}</h3>
                            <p>Total Pendapatan</p>
                            <small>${stats.pending_payments} Menunggu Verifikasi</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(241,196,15,0.1); color: #f39c12;">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="stat-info">
                            <h3>${stats.total_emails}</h3>
                            <p>Total Email Terkirim</p>
                            <small>Admin Email: <?php echo ADMIN_EMAIL; ?></small>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-history"></i> Aktivitas Terbaru
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Aksi</th>
                                        <th>Deskripsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${logsHtml || '<tr><td colspan="4" class="text-center p-4">Belum ada aktivitas</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        // ==================== LOAD MANAGE ADMINS ====================
        function loadManageAdmins() {
            let tableRows = '';
            admins.forEach((admin, index) => {
                const expiryDate = new Date(admin.password_expiry);
                const now = new Date();
                const diffHours = Math.round((expiryDate - now) / (1000 * 60 * 60));
                
                let expiryStatus = '';
                let expiryClass = '';
                
                if (diffHours <= 0) {
                    expiryStatus = 'Expired';
                    expiryClass = 'badge badge-danger';
                } else if (diffHours < 24) {
                    expiryStatus = diffHours + ' jam';
                    expiryClass = 'badge badge-warning';
                } else {
                    const days = Math.floor(diffHours / 24);
                    expiryStatus = days + ' hari';
                    expiryClass = 'badge badge-success';
                }
                
                tableRows += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>
                            <strong>${admin.name}</strong><br>
                            <small>@${admin.username}</small>
                        </td>
                        <td>${admin.email}</td>
                        <td>${admin.phone}</td>
                        <td>
                            <span class="badge ${admin.status === 'active' ? 'badge-success' : 'badge-secondary'}">
                                ${admin.status === 'active' ? 'Aktif' : 'Nonaktif'}
                            </span>
                        </td>
                        <td>
                            <span class="${expiryClass}">${expiryStatus}</span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" onclick="editAdmin(${admin.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning" onclick="openResetModal(${admin.id}, '${admin.name}', '${admin.email}')">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-${admin.status === 'active' ? 'secondary' : 'success'}" onclick="toggleAdminStatus(${admin.id})">
                                    <i class="fas fa-${admin.status === 'active' ? 'pause' : 'play'}"></i>
                                </button>
                                ${admin.id > 1 ? `
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAdmin(${admin.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            document.getElementById('contentArea').innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h4 style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-users-cog" style="color: var(--primary);"></i>
                        Daftar Admin Unit Produksi
                    </h4>
                    <button class="btn btn-primary" onclick="openAddAdminModal()">
                        <i class="fas fa-plus"></i> Tambah Admin Baru
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama / Username</th>
                                        <th>Email</th>
                                        <th>No. HP</th>
                                        <th>Status</th>
                                        <th>Expiry</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${tableRows || '<tr><td colspan="7" class="text-center p-4">Belum ada admin</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        // ==================== FUNGSI ADMIN ====================
        function generateRandomPassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
            let password = '';
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('adminPassword').value = password;
        }

        function generateNewPassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
            let password = '';
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('newPassword').value = password;
        }

        function openAddAdminModal() {
            document.getElementById('adminModalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Tambah Admin Baru';
            document.getElementById('adminId').value = '';
            document.getElementById('adminName').value = '';
            document.getElementById('adminUsername').value = '';
            document.getElementById('adminEmail').value = '';
            document.getElementById('adminPhone').value = '';
            generateRandomPassword();
            document.getElementById('adminStatus').value = 'active';
            document.getElementById('adminNotes').value = '';
            
            showModal('adminModal');
        }

        function editAdmin(id) {
            const admin = admins.find(a => a.id == id);
            if (!admin) return;
            
            document.getElementById('adminModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Admin';
            document.getElementById('adminId').value = admin.id;
            document.getElementById('adminName').value = admin.name;
            document.getElementById('adminUsername').value = admin.username;
            document.getElementById('adminEmail').value = admin.email;
            document.getElementById('adminPhone').value = admin.phone;
            document.getElementById('adminPassword').value = '********';
            document.getElementById('adminStatus').value = admin.status;
            document.getElementById('adminNotes').value = admin.notes || '';
            
            showModal('adminModal');
        }

        function saveAdmin() {
            const id = document.getElementById('adminId').value;
            const name = document.getElementById('adminName').value.trim();
            const username = document.getElementById('adminUsername').value.trim().toLowerCase();
            const email = document.getElementById('adminEmail').value.trim();
            const phone = document.getElementById('adminPhone').value.trim();
            const password = document.getElementById('adminPassword').value;
            const status = document.getElementById('adminStatus').value;
            const notes = document.getElementById('adminNotes').value.trim();
            
            if (!name || !username || !email || !phone || !password) {
                alert('Semua field harus diisi!');
                return;
            }
            
            showLoading();
            
            $.ajax({
                url: 'admin_ajax.php',
                method: 'POST',
                data: {
                    action: id ? 'update_admin' : 'add_admin',
                    id: id,
                    name: name,
                    username: username,
                    email: email,
                    phone: phone,
                    password: password,
                    status: status,
                    notes: notes
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        showToast('success', 'Berhasil', response.message);
                        closeModal('adminModal');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('error', 'Gagal', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    showToast('error', 'Error', 'Terjadi kesalahan server: ' + error);
                    console.error(xhr.responseText);
                }
            });
        }

        function toggleAdminStatus(id) {
            if (!confirm('Ubah status admin ini?')) return;
            
            showLoading();
            
            $.ajax({
                url: 'admin_ajax.php',
                method: 'POST',
                data: {
                    action: 'toggle_admin',
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        showToast('success', 'Berhasil', response.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('error', 'Gagal', response.message);
                    }
                },
                error: function() {
                    hideLoading();
                    showToast('error', 'Error', 'Terjadi kesalahan server');
                }
            });
        }

        function deleteAdmin(id) {
            if (id <= 1) {
                showToast('error', 'Gagal', 'Tidak dapat menghapus Admin Pusat default!');
                return;
            }
            
            if (!confirm('Apakah Anda yakin ingin menghapus admin ini?')) return;
            
            showLoading();
            
            $.ajax({
                url: 'admin_ajax.php',
                method: 'POST',
                data: {
                    action: 'delete_admin',
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        showToast('success', 'Berhasil', response.message);
                        admins = admins.filter(a => a.id != id);
                        loadManageAdmins();
                    } else {
                        showToast('error', 'Gagal', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    showToast('error', 'Error', 'Terjadi kesalahan server: ' + error);
                    console.error(xhr.responseText);
                }
            });
        }

        function openResetModal(id, name, email) {
            selectedUserId = id;
            document.getElementById('resetAdminName').textContent = name;
            document.getElementById('resetEmail').value = email;
            generateNewPassword();
            showModal('resetPasswordModal');
        }

        // ==================== FUNGSI RESET PASSWORD DENGAN EMAILJS ====================
        // ==================== FUNGSI RESET PASSWORD DENGAN EMAILJS ====================

        // ==================== FUNGSI RESET PASSWORD DENGAN SMTP ====================
function confirmResetPassword() {
    const newPassword = document.getElementById('newPassword').value;
    const email = document.getElementById('resetEmail').value;
    const name = document.getElementById('resetAdminName').textContent;
    
    if (!newPassword || !email || !name) {
        showToast('error', 'Gagal', 'Data tidak lengkap');
        return;
    }
    
    showLoading();
    
    $.ajax({
        url: 'admin_ajax.php',
        method: 'POST',
        data: {
            action: 'reset_password',
            id: selectedUserId,
            password: newPassword,
            email: email,
            name: name
        },
        dataType: 'json',
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                if (response.email_sent) {
                    showToast('success', 'Berhasil', '✅ ' + response.message);
                } else {
                    showToast('warning', 'Peringatan', '⚠️ ' + response.message);
                }
                closeModal('resetPasswordModal');
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast('error', 'Gagal', response.message);
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            showToast('error', 'Error', 'Terjadi kesalahan server: ' + error);
            console.error(xhr.responseText);
        }
    });
}

        // ==================== LOAD RESET PASSWORDS ====================
        function loadResetPasswords() {
            const now = new Date();
            
            const expiredAdmins = admins.filter(a => {
                const expiryDate = new Date(a.password_expiry);
                return expiryDate <= now;
            });
            
            const expiringAdmins = admins.filter(a => {
                const expiryDate = new Date(a.password_expiry);
                const diffHours = (expiryDate - now) / (1000 * 60 * 60);
                return diffHours <= 24 && diffHours > 0;
            });
            
            let expiredHtml = '';
            expiredAdmins.forEach(admin => {
                expiredHtml += `
                    <div class="list-group-item" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>${admin.name}</strong>
                            <div class="small">${admin.email}</div>
                        </div>
                        <button class="btn btn-warning btn-sm" onclick="openResetModal(${admin.id}, '${admin.name}', '${admin.email}')">
                            <i class="fas fa-sync-alt"></i> Reset
                        </button>
                    </div>
                `;
            });
            
            let expiringHtml = '';
            expiringAdmins.forEach(admin => {
                const expiryDate = new Date(admin.password_expiry);
                const diffHours = Math.round((expiryDate - now) / (1000 * 60 * 60));
                
                expiringHtml += `
                    <tr>
                        <td><strong>${admin.name}</strong><br><small>${admin.email}</small></td>
                        <td>${expiryDate.toLocaleString('id-ID')}</td>
                        <td><span class="badge badge-warning">${diffHours} jam lagi</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-warning" onclick="openResetModal(${admin.id}, '${admin.name}', '${admin.email}')">
                                <i class="fas fa-sync-alt"></i> Reset
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            document.getElementById('contentArea').innerHTML = `
                <div class="row">
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header" style="background: var(--danger); color: white;">
                                <i class="fas fa-exclamation-triangle"></i> Password Expired (${expiredAdmins.length})
                            </div>
                            <div class="card-body p-0">
                                ${expiredAdmins.length > 0 ? expiredHtml : '<div class="text-center p-4">Tidak ada admin dengan password expired</div>'}
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header" style="background: var(--warning);">
                                <i class="fas fa-clock"></i> Akan Expired (${expiringAdmins.length})
                            </div>
                            <div class="card-body p-0">
                                ${expiringAdmins.length > 0 ? `
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Admin</th>
                                                    <th>Expired Pada</th>
                                                    <th>Sisa</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${expiringHtml}
                                            </tbody>
                                        </table>
                                    </div>
                                ` : '<div class="text-center p-4">Tidak ada admin yang akan expired</div>'}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <i class="fas fa-sync-alt"></i> Aksi Massal
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-4">
                                <button class="btn btn-warning w-100" onclick="resetAllExpired()">
                                    <i class="fas fa-sync-alt"></i> Reset Semua Expired
                                </button>
                            </div>
                            <div class="col-4">
                                <button class="btn btn-warning w-100" onclick="resetAllExpiring()">
                                    <i class="fas fa-clock"></i> Reset Semua Akan Expired
                                </button>
                            </div>
                            <div class="col-4">
                                <button class="btn btn-danger w-100" onclick="resetAllPasswords()">
                                    <i class="fas fa-exclamation-triangle"></i> Reset SEMUA Password
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // ==================== LOAD MONITOR ORDERS ====================
        function loadMonitorOrders() {
            let tableRows = '';
            orders.forEach(order => {
                const createdDate = new Date(order.created_at);
                
                let statusBadge = '';
                if (order.status === 'process') statusBadge = '<span class="badge badge-warning">Proses</span>';
                else if (order.status === 'success') statusBadge = '<span class="badge badge-success">Selesai</span>';
                else if (order.status === 'cancelled') statusBadge = '<span class="badge badge-danger">Dibatalkan</span>';
                else statusBadge = '<span class="badge badge-secondary">Pending</span>';
                
                let paymentBadge = '';
                if (order.payment_status === 'verified') paymentBadge = '<span class="badge badge-success">Terverifikasi</span>';
                else if (order.payment_status === 'paid') paymentBadge = '<span class="badge badge-info">Dibayar</span>';
                else paymentBadge = '<span class="badge badge-secondary">Belum Bayar</span>';
                
                tableRows += `
                    <tr>
                        <td>${order.order_number}</td>
                        <td>${createdDate.toLocaleDateString('id-ID')}</td>
                        <td>
                            <strong>${order.customer_name}</strong><br>
                            <small>${order.customer_class}</small>
                        </td>
                        <td>${order.service_name}</td>
                        <td>Rp ${Number(order.total).toLocaleString('id-ID')}</td>
                        <td>${statusBadge} ${paymentBadge}</td>
                        <td>${order.assigned_to || '-'}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" onclick="viewOrderDetails('${order.order_number}')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            document.getElementById('contentArea').innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h4><i class="fas fa-shopping-cart"></i> Monitor Semua Pesanan</h4>
                    <div style="display: flex; gap: 10px;">
                        <span class="badge badge-warning">Proses: ${stats.process_orders}</span>
                        <span class="badge badge-success">Selesai: ${stats.completed_orders}</span>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No. Order</th>
                                        <th>Tanggal</th>
                                        <th>Pelanggan</th>
                                        <th>Layanan</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                        <th>Admin</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${tableRows || '<tr><td colspan="8" class="text-center p-4">Belum ada pesanan</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        // ==================== LOAD VERIFY PAYMENTS ====================
        function loadVerifyPayments() {
            let tableRows = '';
            payments.forEach(payment => {
                const date = new Date(payment.payment_time);
                
                tableRows += `
                    <tr>
                        <td>${payment.order_number}</td>
                        <td>${date.toLocaleDateString('id-ID')} ${date.toLocaleTimeString('id-ID')}</td>
                        <td>
                            <strong>${payment.customer_name || payment.customerName}</strong><br>
                            <small>${payment.customer_phone || payment.customerPhone}</small>
                        </td>
                        <td>Rp ${Number(payment.amount).toLocaleString('id-ID')}</td>
                        <td>${payment.method}</td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="openVerifyModal('${payment.id}')">
                                <i class="fas fa-check-circle"></i> Verifikasi
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            document.getElementById('contentArea').innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h4><i class="fas fa-credit-card"></i> Verifikasi Pembayaran</h4>
                    <span class="badge badge-info">Menunggu: ${payments.length}</span>
                </div>
                
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No. Order</th>
                                        <th>Waktu</th>
                                        <th>Pelanggan</th>
                                        <th>Jumlah</th>
                                        <th>Metode</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${tableRows || '<tr><td colspan="6" class="text-center p-4">Tidak ada pembayaran yang perlu diverifikasi</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        // ==================== LOAD ACTIVITY LOGS ====================
        function loadActivityLogs() {
            let logsHtml = '';
            logs.forEach((log, index) => {
                const date = new Date(log.created_at);
                let badgeClass = 'badge badge-info';
                if (log.level === 'error') badgeClass = 'badge badge-danger';
                else if (log.level === 'warning') badgeClass = 'badge badge-warning';
                else if (log.level === 'success') badgeClass = 'badge badge-success';
                
                logsHtml += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${date.toLocaleString('id-ID')}</td>
                        <td><strong>${log.username || '-'}</strong></td>
                        <td><span class="${badgeClass}">${log.action}</span></td>
                        <td>${log.message}</td>
                        <td><code>${log.ip || '-'}</code></td>
                    </tr>
                `;
            });
            
            document.getElementById('contentArea').innerHTML = `
                <h4><i class="fas fa-history"></i> Log Aktivitas Sistem</h4>
                
                <div class="card mt-3">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Aksi</th>
                                        <th>Deskripsi</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${logsHtml || '<tr><td colspan="6" class="text-center p-4">Belum ada log aktivitas</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <span class="text-muted">Total Logs: ${logs.length}</span>
                    </div>
                </div>
            `;
        }

        // ==================== LOAD ADMIN LOGS ====================
        function loadAdminLogs() {
            let logsHtml = '';
            adminLogs.forEach((log, index) => {
                const date = new Date(log.created_at);
                logsHtml += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${date.toLocaleString('id-ID')}</td>
                        <td><strong>${log.username || '-'}</strong></td>
                        <td><span class="badge badge-info">${log.action}</span></td>
                        <td>${log.details}</td>
                        <td><code>${log.ip || '-'}</code></td>
                    </tr>
                `;
            });
            
            document.getElementById('contentArea').innerHTML = `
                <h4><i class="fas fa-shield-alt"></i> Log Keamanan Admin Pusat</h4>
                
                <div class="card mt-3">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Aksi</th>
                                        <th>Detail</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${logsHtml || '<tr><td colspan="6" class="text-center p-4">Belum ada log keamanan</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        // ==================== LOAD EMAIL LOGS ====================
        function loadEmailLogs() {
            let logsHtml = '';
            emailLogs.forEach((log, index) => {
                const date = new Date(log.created_at);
                let statusClass = 'badge badge-secondary';
                if (log.status === 'success') statusClass = 'badge badge-success';
                else if (log.status === 'pending') statusClass = 'badge badge-warning';
                else if (log.status === 'failed') statusClass = 'badge badge-danger';
                
                logsHtml += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${date.toLocaleString('id-ID')}</td>
                        <td>${log.recipient}</td>
                        <td>${log.subject}</td>
                        <td><span class="${statusClass}">${log.status}</span></td>
                        <td>${log.response || '-'}</td>
                    </tr>
                `;
            });
            
            document.getElementById('contentArea').innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h4><i class="fas fa-envelope"></i> Log Pengiriman Email</h4>
                    <span class="badge badge-info">Total: ${emailLogs.length}</span>
                </div>
                
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Waktu</th>
                                        <th>Tujuan</th>
                                        <th>Subjek</th>
                                        <th>Status</th>
                                        <th>Response</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${logsHtml || '<tr><td colspan="6" class="text-center p-4">Belum ada email terkirim</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        // ==================== FUNGSI VERIFIKASI ====================
        function openVerifyModal(paymentId) {
            selectedPaymentId = paymentId;
            const payment = payments.find(p => p.id == paymentId);
            
            if (!payment) return;
            
            document.getElementById('paymentDetailBody').innerHTML = `
                <div style="text-align: center; margin-bottom: 20px;">
                    <i class="fas fa-money-bill-wave" style="font-size: 48px; color: var(--success);"></i>
                    <h5>${payment.order_number}</h5>
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr><td style="padding: 8px 0;"><strong>Pelanggan:</strong></td><td>${payment.customer_name || payment.customerName}</td></tr>
                    <tr><td style="padding: 8px 0;"><strong>Jumlah:</strong></td><td><h4 style="color: var(--success);">Rp ${Number(payment.amount).toLocaleString('id-ID')}</h4></td></tr>
                    <tr><td style="padding: 8px 0;"><strong>Metode:</strong></td><td>${payment.method}</td></tr>
                    <tr><td style="padding: 8px 0;"><strong>Waktu:</strong></td><td>${new Date(payment.payment_time).toLocaleString('id-ID')}</td></tr>
                    <tr><td style="padding: 8px 0;"><strong>File:</strong></td><td><a href="uploads/${payment.file_path}" target="_blank">Lihat Bukti</a></td></tr>
                </table>
            `;
            
            showModal('verifyPaymentModal');
        }

        function verifyPayment() {
            if (!selectedPaymentId) return;
            
            showLoading();
            
            $.ajax({
                url: 'payment_ajax.php',
                method: 'POST',
                data: {
                    action: 'verify_payment',
                    id: selectedPaymentId
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        showToast('success', 'Berhasil', 'Pembayaran diverifikasi');
                        closeModal('verifyPaymentModal');
                        payments = payments.filter(p => p.id != selectedPaymentId);
                        loadVerifyPayments();
                    } else {
                        showToast('error', 'Gagal', response.message);
                    }
                },
                error: function() {
                    hideLoading();
                    showToast('error', 'Error', 'Terjadi kesalahan server');
                }
            });
        }

        function rejectPayment() {
            closeModal('verifyPaymentModal');
            showToast('info', 'Info', 'Fungsi reject dalam pengembangan');
        }

        // ==================== VIEW ORDER DETAIL ====================
        function viewOrderDetails(orderNumber) {
            const order = orders.find(o => o.order_number == orderNumber);
            if (!order) return;
            
            const createdDate = new Date(order.created_at);
            
            document.getElementById('orderDetailBody').innerHTML = `
                <div class="row">
                    <div class="col-6">
                        <h6><strong>Informasi Pesanan</strong></h6>
                        <table style="width: 100%;">
                            <tr><td>No. Order</td><td><strong>${order.order_number}</strong></td></tr>
                            <tr><td>Tanggal</td><td>${createdDate.toLocaleString('id-ID')}</td></tr>
                            <tr><td>Layanan</td><td>${order.service_name}</td></tr>
                            <tr><td>Jumlah</td><td>${order.jumlah} unit</td></tr>
                            <tr><td>Total</td><td><strong>Rp ${Number(order.total).toLocaleString('id-ID')}</strong></td></tr>
                        </table>
                    </div>
                    <div class="col-6">
                        <h6><strong>Informasi Pelanggan</strong></h6>
                        <table style="width: 100%;">
                            <tr><td>Nama</td><td><strong>${order.customer_name}</strong></td></tr>
                            <tr><td>Kelas</td><td>${order.customer_class}</td></tr>
                            <tr><td>No. HP</td><td>${order.customer_phone}</td></tr>
                            <tr><td>Email</td><td>${order.customer_email}</td></tr>
                        </table>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <h6><strong>Deskripsi</strong></h6>
                    <p>${order.description || '-'}</p>
                </div>
                <div style="margin-top: 20px;">
                    <h6><strong>Link Drive</strong></h6>
                    <a href="${order.drive_link}" target="_blank" class="btn btn-sm btn-outline-primary">Buka Drive</a>
                </div>
                ${order.catatan ? `
                    <div style="margin-top: 20px;">
                        <h6><strong>Catatan</strong></h6>
                        <p>${order.catatan}</p>
                    </div>
                ` : ''}
            `;
            
            showModal('orderDetailModal');
        }

        // ==================== AKSI MASSAL ====================
        function resetAllExpired() {
            if (!confirm('Reset semua password yang expired?')) return;
            
            showLoading();
            
            $.ajax({
                url: 'admin_ajax.php',
                method: 'POST',
                data: { action: 'reset_all_expired' },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        showToast('success', 'Berhasil', response.message);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showToast('error', 'Gagal', response.message);
                    }
                },
                error: function() {
                    hideLoading();
                    showToast('error', 'Error', 'Terjadi kesalahan server');
                }
            });
        }

        function resetAllExpiring() {
            if (!confirm('Reset semua password yang akan expired dalam 24 jam?')) return;
            
            showLoading();
            
            $.ajax({
                url: 'admin_ajax.php',
                method: 'POST',
                data: { action: 'reset_all_expiring' },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        showToast('success', 'Berhasil', response.message);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showToast('error', 'Gagal', response.message);
                    }
                },
                error: function() {
                    hideLoading();
                    showToast('error', 'Error', 'Terjadi kesalahan server');
                }
            });
        }

        function resetAllPasswords() {
            if (!confirm('⚠️ PERINGATAN! Reset SEMUA password admin UP?')) return;
            if (!confirm('KONFIRMASI AKHIR: Yakin ingin reset semua password?')) return;
            
            showLoading();
            
            $.ajax({
                url: 'admin_ajax.php',
                method: 'POST',
                data: { action: 'reset_all_passwords' },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        showToast('success', 'Berhasil', response.message);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showToast('error', 'Gagal', response.message);
                    }
                },
                error: function() {
                    hideLoading();
                    showToast('error', 'Error', 'Terjadi kesalahan server');
                }
            });
        }

        // ==================== LOGOUT ====================
        function handleLogout() {
            if (!confirm('Apakah Anda yakin ingin logout?')) return;
            window.location.href = 'logout.php';
        }

        // ==================== INIT ====================
        document.addEventListener('DOMContentLoaded', function() {
            updateDateTime();
            startSessionTimer();
            setupNavigation();
            loadDashboard();
            
            setInterval(updateDateTime, 1000);
        });
    </script>
</body>
</html>