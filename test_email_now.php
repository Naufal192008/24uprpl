<?php
// ==================== TEST EMAIL NOW ====================
// File: test_email_now.php - PASTI WORKING

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>📧 TEST EMAIL - PASTI WORKING</h1>";
echo "<pre>";

// echo "PUBLIC KEY: " . EMAILJS_PUBLIC_KEY . "\n";
// echo "SERVICE ID: " . EMAILJS_SERVICE_ID . "\n";
// echo "TEMPLATE ID: " . EMAILJS_TEMPLATE_ID . "\n";
// echo "====================================\n\n";

// Kirim email test
$email = 'nmurtadho1905@gmail.com'; // Ganti dengan email Anda
$name = 'Test User';
$username = 'testuser';
$password = 'Test123!@#';

echo "MENGIRIM EMAIL KE: $email\n";
echo "PASSWORD: $password\n\n";

$result = sendEmailViaEmailJS($email, $name, $username, $password);

echo "HASIL: " . ($result['success'] ? '✅ BERHASIL' : '❌ GAGAL') . "\n";
echo "PESAN: " . $result['message'] . "\n";

if (!$result['success']) {
    echo "\n🔍 CEK ERROR DI ATAS!\n";
    echo "1. Pastikan Public Key benar: zO6XyHCxMhxySvvaU\n";
    echo "2. Pastikan Service ID benar: service_3anwd2k\n";
    echo "3. Pastikan Template ID benar: template_wb3cmkl\n";
    echo "4. Cek error log di C:\\xampp\\php\\logs\\php_error_log\n";
}

echo "</pre>";
?>