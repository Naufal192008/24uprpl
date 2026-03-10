<?php
// ==================== AUTHENTICATION CHECK ====================
// File: includes/auth_check.php - FIX VERSION

// JANGAN panggil session_start() di sini!
// Session akan dimulai di file utama

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_pusat_id']) && !isset($_SESSION['admin_up_id'])) {
    // Jika belum login, kirim response JSON untuk AJAX
    if (strpos($_SERVER['REQUEST_URI'], 'admin_ajax.php') !== false || strpos($_SERVER['REQUEST_URI'], 'payment_ajax.php') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
        exit;
    }
    
    // Redirect untuk halaman biasa
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Tentukan role yang sedang login
$isAdminPusat = isset($_SESSION['admin_pusat_id']);
$isAdminUP = isset($_SESSION['admin_up_id']);
$isUser = isset($_SESSION['user_id']);

// Ambil user_id yang sesuai
if ($isAdminPusat) {
    $userId = $_SESSION['admin_pusat_id'];
    $username = $_SESSION['admin_pusat_username'] ?? '';
    $userRole = 'super_admin';
    $userName = $_SESSION['admin_pusat_name'] ?? '';
} elseif ($isAdminUP) {
    $userId = $_SESSION['admin_up_id'];
    $username = $_SESSION['admin_up_username'] ?? '';
    $userRole = 'admin_up';
    $userName = $_SESSION['admin_up_name'] ?? '';
} else {
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? '';
    $userRole = $_SESSION['user_role'] ?? 'user';
    $userName = $_SESSION['user_name'] ?? '';
}

// Ambil data user dari database untuk validasi
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    session_destroy();
    
    if (strpos($_SERVER['REQUEST_URI'], 'admin_ajax.php') !== false || strpos($_SERVER['REQUEST_URI'], 'payment_ajax.php') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Akun tidak aktif']);
        exit;
    }
    
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Update session data jika perlu
if ($isAdminPusat) {
    $_SESSION['admin_pusat_name'] = $currentUser['name'];
    $_SESSION['admin_pusat_username'] = $currentUser['username'];
} elseif ($isAdminUP) {
    $_SESSION['admin_up_name'] = $currentUser['name'];
    $_SESSION['admin_up_username'] = $currentUser['username'];
} else {
    $_SESSION['user_name'] = $currentUser['name'];
    $_SESSION['username'] = $currentUser['username'];
}
?>