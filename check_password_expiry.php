<?php
// ==================== CHECK PASSWORD EXPIRY ====================
// File: check_password_expiry.php - FIX VERSION

require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['admin_up_id']) && !isset($_SESSION['admin_pusat_id'])) {
    echo json_encode(['expired' => false, 'expiring' => false]);
    exit;
}

// Tentukan user_id
if (isset($_SESSION['admin_up_id'])) {
    $userId = $_SESSION['admin_up_id'];
} else {
    $userId = $_SESSION['admin_pusat_id'];
}

// Cek expiry password untuk user yang login
$stmt = $pdo->prepare("SELECT password_expiry FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($user && $user['password_expiry']) {
    $expiryDate = strtotime($user['password_expiry']);
    $now = time();
    $diffHours = ($expiryDate - $now) / 3600;
    
    echo json_encode([
        'expired' => $diffHours <= 0,
        'expiring' => $diffHours > 0 && $diffHours <= 24,
        'hoursLeft' => round($diffHours, 1),
        'expiryDate' => $user['password_expiry']
    ]);
} else {
    echo json_encode(['expired' => false, 'expiring' => false]);
}
?>