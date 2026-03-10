<?php
// ==================== CHECK PASSWORD STATUS ====================
// File: check_password_status.php - FIX VERSION

require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

$username = $_POST['username'] ?? '';

if (empty($username)) {
    echo json_encode(['exists' => false]);
    exit;
}

// Hapus expired passwords
$stmt = $pdo->prepare("DELETE FROM pending_passwords WHERE expiry_time <= NOW()");
$stmt->execute();

// Cek password aktif
$stmt = $pdo->prepare("SELECT * FROM pending_passwords WHERE username = ? AND expiry_time > NOW()");
$stmt->execute([$username]);
$pending = $stmt->fetch();

if ($pending) {
    $expiryTime = strtotime($pending['expiry_time']);
    $now = time();
    $remainingSeconds = max(0, $expiryTime - $now);
    
    echo json_encode([
        'exists' => true,
        'password' => $pending['password'],
        'remainingSeconds' => $remainingSeconds,
        'email' => $pending['email'],
        'expiryTime' => $pending['expiry_time']
    ]);
} else {
    echo json_encode(['exists' => false]);
}
?>