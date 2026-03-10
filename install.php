<?php
// ==================== INSTALL ====================
// File: install.php - VERSI FINAL

require_once 'config/database.php';

// Cek apakah database sudah terinstal
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $installed = true;
} catch (Exception $e) {
    $installed = false;
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    // SQL untuk membuat tabel
    $sql = "
    -- ==================== TABEL users ====================
    CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) UNIQUE NOT NULL,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(20),
        `role` ENUM('super_admin', 'admin_up') DEFAULT 'admin_up',
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `password_expiry` DATETIME,
        `last_login` DATETIME,
        `last_ip` VARCHAR(45),
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- ==================== TABEL pending_passwords ====================
    CREATE TABLE IF NOT EXISTS `pending_passwords` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `expiry_time` DATETIME NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- ==================== TABEL orders ====================
    CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_number` VARCHAR(20) UNIQUE NOT NULL,
        `customer_name` VARCHAR(100) NOT NULL,
        `customer_class` VARCHAR(50) NOT NULL,
        `customer_phone` VARCHAR(20) NOT NULL,
        `customer_email` VARCHAR(100) NOT NULL,
        `service` VARCHAR(50) NOT NULL,
        `service_name` VARCHAR(100) NOT NULL,
        `jumlah` INT NOT NULL,
        `ukuran` VARCHAR(10),
        `jenis_sablon` VARCHAR(50),
        `warna_kaos` VARCHAR(20),
        `ukuran_kertas` VARCHAR(10),
        `drive_link` TEXT NOT NULL,
        `catatan` TEXT,
        `harga_satuan` INT NOT NULL,
        `subtotal` INT NOT NULL,
        `unique_code` INT NOT NULL,
        `total` INT NOT NULL,
        `status` ENUM('pending_payment', 'pending', 'process', 'success', 'cancelled') DEFAULT 'pending_payment',
        `payment_status` ENUM('belum_bayar', 'paid', 'verified') DEFAULT 'belum_bayar',
        `payment_method` VARCHAR(20),
        `payment_time` DATETIME,
        `assigned_to` VARCHAR(50),
        `assigned_at` DATETIME,
        `completed_at` DATETIME,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- ==================== TABEL payments ====================
    CREATE TABLE IF NOT EXISTS `payments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_number` VARCHAR(20) NOT NULL,
        `customer_name` VARCHAR(100) NOT NULL,
        `customer_phone` VARCHAR(20) NOT NULL,
        `customer_email` VARCHAR(100) NOT NULL,
        `amount` INT NOT NULL,
        `unique_code` INT NOT NULL,
        `method` VARCHAR(20) NOT NULL,
        `file_name` VARCHAR(255) NOT NULL,
        `file_path` VARCHAR(255) NOT NULL,
        `status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
        `verified` BOOLEAN DEFAULT FALSE,
        `verified_by` INT,
        `verified_at` DATETIME,
        `payment_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- ==================== TABEL activity_logs ====================
    CREATE TABLE IF NOT EXISTS `activity_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT,
        `username` VARCHAR(50),
        `action` VARCHAR(100),
        `level` ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
        `message` TEXT,
        `ip` VARCHAR(45),
        `user_agent` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- ==================== TABEL admin_logs ====================
    CREATE TABLE IF NOT EXISTS `admin_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT,
        `username` VARCHAR(50),
        `action` VARCHAR(100),
        `details` TEXT,
        `ip` VARCHAR(45),
        `user_agent` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- ==================== TABEL email_logs ====================
    CREATE TABLE IF NOT EXISTS `email_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `recipient` VARCHAR(100) NOT NULL,
        `subject` VARCHAR(255) NOT NULL,
        `status` ENUM('pending', 'success', 'failed') DEFAULT 'pending',
        `response` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- ==================== TABEL notifications ====================
    CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `type` ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
        `target` ENUM('all', 'super_admin', 'admin_up') DEFAULT 'all',
        `order_number` VARCHAR(20),
        `is_read` BOOLEAN DEFAULT FALSE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- ==================== TABEL settings ====================
    CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(50) UNIQUE NOT NULL,
        `setting_value` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- ==================== INSERT DEFAULT SETTINGS ====================
    INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('payment_expiry', '10'),
    ('password_expiry', '2'),
    ('session_duration', '8'),
    ('max_login_attempts', '5'),
    ('site_name', 'Unit Produksi RPL SMKN 24 JKT'),
    ('school_name', 'SMK Negeri 24 Jakarta'),
    ('admin_email', 'nmurtadho1905@gmail.com'),
    ('admin_phone', '0857107855244'),
    ('dana_number', '0857107855244'),
    ('dana_name', 'UNIT PRODUKSI RPL');

    -- ==================== INSERT DEFAULT ADMIN PUSAT ====================
    INSERT IGNORE INTO `users` (`username`, `email`, `password`, `name`, `role`, `status`, `password_expiry`) VALUES
    ('naufal19smkn24', 'nmurtadho1905@gmail.com', '$2y$12$8K3Xk9QwL5pR7sT2vW4xY.6nF7gH8iJ9kL0mN1bV2cX3zA4sD5fG6hJ7kL8m', 'Admin Pusat', 'super_admin', 'active', DATE_ADD(NOW(), INTERVAL 90 DAY));
    ";
    
    // Split queries
    $queries = explode(';', $sql);
    
    $success = true;
    $errors = [];
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        try {
            $pdo->exec($query);
        } catch (Exception $e) {
            $success = false;
            $errors[] = $e->getMessage();
        }
    }
    
    if ($success) {
        $message = "✅ Instalasi berhasil! Database telah dibuat.";
        $installed = true;
    } else {
        $message = "❌ Instalasi gagal: " . implode("<br>", $errors);
    }
}

// Update password admin pusat
$passMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $newPassword = $_POST['new_password'] ?? '';
    
    if (strlen($newPassword) < 6) {
        $passMessage = "❌ Password minimal 6 karakter";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'naufal19smkn24'");
            $stmt->execute([$hashedPassword]);
            $passMessage = "✅ Password admin pusat berhasil diupdate!";
        } catch (Exception $e) {
            $passMessage = "❌ Gagal update password: " . $e->getMessage();
        }
    }
}

// Get table count
$tableCount = 0;
$userCount = 0;
if ($installed) {
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tableCount = $stmt->rowCount();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $userCount = $stmt->fetchColumn();
    } catch (Exception $e) {
        // Ignore
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi - Unit Produksi RPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .install-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }
        h2 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .info {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        .btn-install {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .config-table {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .config-table table {
            width: 100%;
        }
        .config-table td {
            padding: 8px;
        }
        .config-table .label {
            font-weight: 600;
            color: #555;
        }
        .config-table .value {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="install-box">
        <h2>Instalasi Sistem Unit Produksi RPL</h2>
        
        <div class="info">
            <strong>Informasi Koneksi Database:</strong><br>
            Host: <?php echo DB_HOST; ?><br>
            Database: <?php echo DB_NAME; ?><br>
            User: <?php echo DB_USER; ?><br>
            Password: <?php echo str_repeat('*', strlen(DB_PASS)); ?>
        </div>
        
        <div class="config-table">
            <table>
                <tr>
                    <td class="label">Base URL:</td>
                    <td class="value"><?php echo BASE_URL; ?></td>
                </tr>
                <tr>
                    <td class="label">Admin Email:</td>
                    <td class="value"><?php echo ADMIN_EMAIL; ?></td>
                </tr>
                <tr>
                    <td class="label">Admin Phone:</td>
                    <td class="value"><?php echo ADMIN_PHONE; ?></td>
                </tr>
                <tr>
                    <td class="label">DANA Number:</td>
                    <td class="value"><?php echo DANA_NUMBER; ?></td>
                </tr>
                <tr>
                    <td class="label">EmailJS Public Key:</td>
                    <td class="value"><?php echo EMAILJS_PUBLIC_KEY; ?></td>
                </tr>
            </table>
        </div>
        
        <?php if ($message): ?>
            <div class="<?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($passMessage): ?>
            <div class="<?php echo strpos($passMessage, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $passMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$installed): ?>
            <div class="warning">
                <strong>⚠️ Database belum terinstal!</strong><br>
                Klik tombol di bawah untuk menjalankan instalasi database.
            </div>
            
            <form method="POST">
                <input type="hidden" name="install" value="1">
                <button type="submit" class="btn-install">Jalankan Instalasi Database</button>
            </form>
        <?php else: ?>
            <div class="success">
                <strong>✅ Database sudah terinstal!</strong><br>
                Total tabel: <?php echo $tableCount; ?><br>
                Total admin: <?php echo $userCount; ?>
            </div>
            
            <hr style="margin: 30px 0;">
            
            <h4>Update Password Admin Pusat</h4>
            <form method="POST" style="margin-top: 20px;">
                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <input type="text" name="new_password" class="form-control" value="admin123!" required>
                </div>
                <input type="hidden" name="update_password" value="1">
                <button type="submit" class="btn btn-warning w-100">Update Password</button>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <h4>Link Penting</h4>
            <div style="display: grid; gap: 10px; margin-top: 20px;">
                <a href="index.php" class="btn btn-primary" target="_blank">🏠 Halaman Utama</a>
                <a href="login.php" class="btn btn-success" target="_blank">🔐 Login Admin UP</a>
                <a href="login_pusat.php" class="btn btn-info" target="_blank">👑 Login Admin Pusat</a>
                <a href="admin_pusat.php" class="btn btn-warning" target="_blank">📊 Dashboard Admin Pusat</a>
                <a href="data_pesanan.php" class="btn btn-secondary" target="_blank">📋 Data Pesanan</a>
                <a href="email_test.php" class="btn btn-danger" target="_blank">📧 Test Email</a>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                <strong>Login Admin Pusat:</strong><br>
                Username: <code>naufal19smkn24</code><br>
                Password: <code>admin123!</code>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>