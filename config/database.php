<?php
// ==================== KONFIGURASI DATABASE ====================
// File: config/database.php - FIX VERSION DENGAN SMTP

// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_NAME', 'uprpl_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Konfigurasi aplikasi
define('BASE_URL', 'http://localhost/uprpl-php');
define('SITE_NAME', 'Unit Produksi RPL');
define('SCHOOL_NAME', 'SMK Negeri 24 Jakarta');
define('ADMIN_EMAIL', 'nmurtadho1905@gmail.com');
define('ADMIN_PHONE', '0857107855244');
define('DANA_NUMBER', '0857107855244');
define('DANA_NAME', 'UNIT PRODUKSI RPL');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// ==================== SMTP KONFIGURASI (GMAIL) ====================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'nmurtadho1905@gmail.com');
define('SMTP_PASS', 'xkmjffgnkwpqrlpp'); // APP PASSWORD 16 DIGIT
define('SMTP_FROM', 'nmurtadho1905@gmail.com');
define('SMTP_FROM_NAME', 'Admin Pusat UP RPL');

// ==================== EMAILJS KONFIGURASI (TIDAK DIPAKAI) ====================
define('EMAILJS_PUBLIC_KEY', 'zO6XyHCxMhxySvvaU');
define('EMAILJS_SERVICE_ID', 'service_3anwd2k');
define('EMAILJS_TEMPLATE_ID', 'template_wb3cmkl');

// ==================== KONFIGURASI SESSION ====================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
ini_set('session.gc_maxlifetime', 3600); // Session 1 jam

// ==================== KONEKSI DATABASE ====================
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// ==================== AMBIL SETTINGS ====================
$PAYMENT_EXPIRY = 10;
$PASSWORD_EXPIRY = 2; // 2 menit
$SESSION_DURATION = 8;
$MAX_LOGIN_ATTEMPTS = 5;

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $PAYMENT_EXPIRY = isset($settings['payment_expiry']) ? (int)$settings['payment_expiry'] : 10;
    $PASSWORD_EXPIRY = isset($settings['password_expiry']) ? (int)$settings['password_expiry'] : 2;
    $SESSION_DURATION = isset($settings['session_duration']) ? (int)$settings['session_duration'] : 8;
    $MAX_LOGIN_ATTEMPTS = isset($settings['max_login_attempts']) ? (int)$settings['max_login_attempts'] : 5;
} catch (Exception $e) {
    // Pakai default
}

define('PAYMENT_EXPIRY', $PAYMENT_EXPIRY);
define('PASSWORD_EXPIRY', $PASSWORD_EXPIRY);
define('SESSION_DURATION', $SESSION_DURATION);
define('MAX_LOGIN_ATTEMPTS', $MAX_LOGIN_ATTEMPTS);

date_default_timezone_set('Asia/Jakarta');
?>