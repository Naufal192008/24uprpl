<?php
// ==================== SEND EMAIL VIA AJAX ====================
// File: send_email_ajax.php - MENERIMA PERMINTAAN DARI JAVASCRIPT

require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'send_email') {
    $to_email = $_POST['to_email'] ?? '';
    $to_name = $_POST['to_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validasi
    if (empty($to_email) || empty($to_name) || empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }
    
    // Kirim response sukses (email akan dikirim dari JavaScript)
    echo json_encode([
        'success' => true,
        'message' => 'Email akan dikirim dari browser',
        'data' => [
            'to_email' => $to_email,
            'to_name' => $to_name,
            'username' => $username,
            'password' => $password
        ]
    ]);
}
?>