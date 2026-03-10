<?php
// ==================== DEBUG SESSION ====================
// File: debug_session.php - FIX VERSION

session_start();
header('Content-Type: application/json');

// Hapus session yang sudah kadaluarsa
if (isset($_SESSION['admin_pusat_last_activity']) && time() - $_SESSION['admin_pusat_last_activity'] > 1800) {
    session_destroy();
    session_start();
}

$response = [
    'success' => true,
    'session_exists' => isset($_SESSION) ? 'YES' : 'NO',
    'session_id' => session_id(),
    'session_name' => session_name(),
    'session_status' => session_status(),
    'session_save_path' => session_save_path(),
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE,
    'server' => [
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '',
        'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? '',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
        'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ],
    'php_version' => phpversion(),
    'time' => date('Y-m-d H:i:s')
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>