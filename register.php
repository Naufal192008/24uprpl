<?php
// ==================== REGISTER ADMIN UP ====================
// File: register.php - VERSI LENGKAP DENGAN EMAIL (UI ANDA 100% ASLI)

require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['admin_up_id'])) {
    header('Location: admin_up.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['fullname'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    
    // Validasi input
    if (empty($name) || empty($username) || empty($phone) || empty($email)) {
        $error = '⚠️ Semua field harus diisi!';
    } elseif (strlen($username) < 3) {
        $error = '⚠️ Username minimal 3 karakter!';
    } elseif (!validateEmail($email)) {
        $error = '⚠️ Format email tidak valid!';
    } elseif (!validatePhone($phone)) {
        $error = '⚠️ Nomor HP harus 10-13 digit!';
    } else {
        try {
            // Cek username sudah dipakai
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = '❌ Username sudah terdaftar!';
            } 
            // Cek email sudah dipakai
            else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = '❌ Email sudah terdaftar!';
                } else {
                    // Mulai transaksi
                    $pdo->beginTransaction();
                    
                    // Generate password random
                    $password = generateRandomPassword(12);
                    
                    // Simpan ke pending_passwords (OTP)
                    $expiryTime = date('Y-m-d H:i:s', time() + (PASSWORD_EXPIRY * 60));
                    $stmt = $pdo->prepare("INSERT INTO pending_passwords (username, password, email, expiry_time) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $password, $email, $expiryTime]);
                    
                    // Hash password untuk disimpan di tabel users
                    $hashedPassword = hashPassword($password);
                    $passwordExpiry = date('Y-m-d H:i:s', time() + (90 * 24 * 60 * 60)); // 90 hari
                    
                    // Simpan ke tabel users
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, name, phone, role, status, password_expiry, last_login, last_ip, notes) VALUES (?, ?, ?, ?, ?, 'admin_up', 'active', ?, NULL, NULL, NULL)");
                    $stmt->execute([$username, $email, $hashedPassword, $name, $phone, $passwordExpiry]);
                    
                    $userId = $pdo->lastInsertId();
                    
                    // ========== KIRIM EMAIL VIA SMTP ==========
                    $emailResult = sendEmailViaSMTP($email, $name, $username, $password);
                    
                    // Log ke email_logs
                    $logStmt = $pdo->prepare("INSERT INTO email_logs (recipient, subject, status, response, created_at) VALUES (?, 'Password Login Admin UP', ?, ?, NOW())");
                    $logStmt->execute([$email, $emailResult['success'] ? 'success' : 'failed', $emailResult['message']]);
                    
                    // Simpan data ke session
                    $_SESSION['last_registered_username'] = $username;
                    $_SESSION['last_registered_email'] = $email;
                    $_SESSION['last_registered_name'] = $name;
                    
                    // Jika email gagal, simpan password untuk ditampilkan
                    if (!$emailResult['success']) {
                        $_SESSION['temp_password'] = $password;
                        $_SESSION['temp_message'] = $emailResult['message'];
                    }
                    // ==========================================
                    
                    // Commit transaksi
                    $pdo->commit();
                    
                    // Redirect ke halaman login dengan parameter sukses
                    header('Location: login.php?registered=success');
                    exit;
                }
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = '❌ Gagal registrasi: ' . $e->getMessage();
            error_log("REGISTRASI ERROR: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Admin UP - Unit Produksi RPL</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            overflow-x: hidden;
        }

        .gradient-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(-45deg, #667eea, #764ba2, #6b46c1, #4c51bf);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            z-index: -2;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: float 20s infinite;
            z-index: -1;
        }

        .orb-1 {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
            background: radial-gradient(circle at 30% 30%, rgba(102, 126, 234, 0.4), rgba(118, 75, 162, 0.2));
            animation-delay: 0s;
        }

        .orb-2 {
            width: 300px;
            height: 300px;
            bottom: -50px;
            left: -50px;
            background: radial-gradient(circle at 70% 70%, rgba(107, 70, 193, 0.4), rgba(76, 81, 191, 0.2));
            animation-delay: -5s;
        }

        .orb-3 {
            width: 200px;
            height: 200px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.2), transparent);
            animation: pulse 8s infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.3; }
            50% { transform: translate(-50%, -50%) scale(1.5); opacity: 0.1; }
        }

        .register-container {
            width: 100%;
            max-width: 550px;
            position: relative;
            z-index: 10;
            animation: fadeInUp 0.8s cubic-bezier(0.23, 1, 0.32, 1);
            margin: auto;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 32px;
            padding: 40px 35px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.6);
        }

        .register-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .brand-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
            color: white;
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.5);
            position: relative;
            overflow: hidden;
        }

        .brand-icon::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: rotate(45deg);
            animation: shine 6s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) rotate(45deg); }
            20% { transform: translateX(100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        .register-header h2 {
            font-size: 28px;
            font-weight: 800;
            color: white;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .register-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            font-weight: 400;
        }

        .role-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            padding: 8px 24px;
            border-radius: 50px;
            margin-top: 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .role-badge span {
            color: white;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 1px;
        }

        .role-badge i {
            color: #ffd700;
            margin-right: 8px;
        }

        .info-box {
            background: rgba(255, 255, 255, 0.08);
            border-left: 4px solid #667eea;
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideDown 0.5s ease;
        }

        .info-box i {
            font-size: 28px;
            color: #667eea;
        }

        .info-box p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }

        .info-box strong {
            color: #ffd700;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert {
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
            animation: slideDown 0.5s ease;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fff;
        }

        .alert i {
            font-size: 20px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }

        .form-col {
            flex: 1;
            padding: 0 10px;
            min-width: 200px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            font-size: 16px;
            z-index: 1;
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 16px 16px 16px 48px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            font-size: 15px;
            color: white;
            transition: all 0.3s;
            backdrop-filter: blur(5px);
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.3);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .help-text {
            display: block;
            color: rgba(255, 255, 255, 0.5);
            font-size: 11px;
            margin-top: 6px;
            margin-left: 5px;
        }

        .btn-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 16px;
            color: white;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.5);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-link a {
            color: white;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: white;
            transition: width 0.3s;
        }

        .login-link a:hover::after {
            width: 100%;
        }

        .login-link a i {
            margin-left: 8px;
            font-size: 12px;
            transition: transform 0.3s;
        }

        .login-link a:hover i {
            transform: translateX(5px);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-link a:hover {
            color: white;
        }

        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(15px);
            z-index: 99999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .loading.active {
            display: flex;
        }

        .loader {
            width: 80px;
            height: 80px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            border-top: 4px solid #667eea;
            border-right: 4px solid #764ba2;
            animation: spin 1s linear infinite;
            margin-bottom: 25px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: white;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 2px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @media (max-width: 640px) {
            .form-col {
                min-width: 100%;
            }
            .glass-card {
                padding: 30px 20px;
            }
            body {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="gradient-bg"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="loading" id="loading">
        <div class="loader"></div>
        <div class="loading-text">MEMPROSES REGISTRASI...</div>
    </div>

    <div class="register-container">
        <div class="glass-card">
            <div class="register-header">
                <div class="brand-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h2>UNIT PRODUKSI RPL</h2>
                <p>SMK Negeri 24 Jakarta</p>
                <div class="role-badge">
                    <i class="fas fa-user-plus"></i>
                    <span>REGISTRASI ADMIN UP</span>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <i class="fas fa-envelope-open-text"></i>
                <p>
                    <strong>Password akan dikirim ke email</strong> setelah registrasi. 
                    Password berlaku <strong><?php echo PASSWORD_EXPIRY; ?> menit</strong>.
                </p>
            </div>

            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label><i class="fas fa-user me-2"></i>NAMA LENGKAP</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fas fa-id-card"></i></span>
                        <input type="text" class="form-control" name="fullname" placeholder="Masukkan nama lengkap" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label><i class="fas fa-at me-2"></i>USERNAME</label>
                            <div class="input-wrapper">
                                <span class="input-icon"><i class="fas fa-id-badge"></i></span>
                                <input type="text" class="form-control" name="username" placeholder="Username" required>
                            </div>
                            <span class="help-text">Huruf kecil, angka, dan titik</span>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label><i class="fas fa-phone me-2"></i>NOMOR HP</label>
                            <div class="input-wrapper">
                                <span class="input-icon"><i class="fab fa-whatsapp"></i></span>
                                <input type="tel" class="form-control" name="phone" placeholder="08xxxxxx" required>
                            </div>
                            <span class="help-text">10-13 digit</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope me-2"></i>EMAIL</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" name="email" placeholder="contoh@email.com" required>
                    </div>
                    <span class="help-text">Password akan dikirim ke email ini</span>
                </div>

                <button type="submit" class="btn-register">
                    <i class="fas fa-paper-plane"></i>
                    DAFTAR
                </button>
            </form>

            <div class="login-link">
                <a href="login.php">
                    Sudah punya akun? Login disini
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="back-link">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function() {
            document.getElementById('loading').classList.add('active');
        });
        
        document.querySelector('input[name="phone"]').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        document.querySelector('input[name="username"]').addEventListener('input', function() {
            this.value = this.value.toLowerCase().replace(/[^a-z0-9.]/g, '');
        });
        
        <?php if (isset($_SESSION['temp_password'])): ?>
        window.addEventListener('load', function() {
            alert('⚠️ PERHATIAN!\n\nEmail gagal dikirim: <?php echo $_SESSION['temp_message']; ?>\n\nGunakan password berikut untuk login:\n\nUsername: <?php echo $_SESSION['last_registered_username']; ?>\nPassword: <?php echo $_SESSION['temp_password']; ?>\n\nPassword berlaku <?php echo PASSWORD_EXPIRY; ?> menit!');
            <?php 
            unset($_SESSION['temp_password']);
            unset($_SESSION['temp_message']);
            ?>
        });
        <?php endif; ?>
    </script>
</body>
</html>