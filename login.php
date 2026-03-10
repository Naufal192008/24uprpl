<?php
// ==================== LOGIN ADMIN UP ====================
// File: login.php - VERSI FINAL

require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['admin_up_id'])) {
    header('Location: admin_up.php');
    exit;
}

$error = '';
$success = '';
$error_type = '';

// Cek apakah dari halaman registrasi
if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $success = '✅ Registrasi berhasil! Silakan cek email Anda untuk password.';
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '⚠️ Username dan password harus diisi!';
        $error_type = 'empty';
    } else {
        try {
            // Cek apakah username ada
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = '❌ Username tidak terdaftar!';
                $error_type = 'not_found';
                logActivity($pdo, null, $username, 'login_failed', 'Username tidak ditemukan');
            } 
            // Cek status akun
            elseif ($user['status'] !== 'active') {
                $error = '🔒 Akun Anda tidak aktif. Hubungi Admin Pusat.';
                $error_type = 'inactive';
                logActivity($pdo, $user['id'], $username, 'login_failed', 'Akun tidak aktif');
            }
            else {
                // Cek pending password (OTP) dulu
                $stmt = $pdo->prepare("SELECT * FROM pending_passwords WHERE username = ? AND expiry_time > NOW()");
                $stmt->execute([$username]);
                $pending = $stmt->fetch();
                
                if ($pending && $pending['password'] === $password) {
                    // Login dengan OTP berhasil
                    // Hapus OTP yang sudah digunakan
                    $stmt = $pdo->prepare("DELETE FROM pending_passwords WHERE username = ?");
                    $stmt->execute([$username]);
                    
                    // Set session
                    $_SESSION['admin_up_id'] = $user['id'];
                    $_SESSION['admin_up_username'] = $user['username'];
                    $_SESSION['admin_up_name'] = $user['name'];
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    // Update last login
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW(), last_ip = ? WHERE id = ?");
                    $stmt->execute([$ip, $user['id']]);
                    
                    logActivity($pdo, $user['id'], $user['username'], 'login_success', 'Login berhasil dengan OTP');
                    
                    header('Location: admin_up.php');
                    exit;
                }
                // Cek login dengan password biasa (hashed)
                elseif (verifyPassword($password, $user['password'])) {
                    // Cek apakah password sudah expired
                    if (strtotime($user['password_expiry']) < time()) {
                        $error = '⏰ PASSWORD EXPIRED! Silakan minta password baru ke Admin Pusat.';
                        $error_type = 'expired';
                        logActivity($pdo, $user['id'], $user['username'], 'login_failed', 'Password expired');
                    } else {
                        // Login berhasil
                        $_SESSION['admin_up_id'] = $user['id'];
                        $_SESSION['admin_up_username'] = $user['username'];
                        $_SESSION['admin_up_name'] = $user['name'];
                        $_SESSION['login_time'] = time();
                        $_SESSION['last_activity'] = time();
                        
                        // Update last login
                        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW(), last_ip = ? WHERE id = ?");
                        $stmt->execute([$ip, $user['id']]);
                        
                        logActivity($pdo, $user['id'], $user['username'], 'login_success', 'Login berhasil');
                        
                        header('Location: admin_up.php');
                        exit;
                    }
                } else {
                    $error = '❌ Password salah!';
                    $error_type = 'wrong_password';
                    logActivity($pdo, $user['id'], $username, 'login_failed', 'Password salah');
                }
            }
        } catch (Exception $e) {
            $error = '❌ Terjadi kesalahan: ' . $e->getMessage();
            $error_type = 'system';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin UP - Unit Produksi RPL</title>
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

        /* Animated Background */
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

        /* Floating Orbs */
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

        /* Main Container */
        .login-container {
            width: 100%;
            max-width: 450px;
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

        /* Glass Card */
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

        /* Header */
        .login-header {
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

        .login-header h2 {
            font-size: 28px;
            font-weight: 800;
            color: white;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .login-header p {
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

        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
            animation: slideDown 0.5s ease;
            border-left: 4px solid;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border-color: #10b981;
            color: #fff;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            border-color: #ef4444;
            color: #fff;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.2);
            border-color: #f59e0b;
            color: #fff;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.2);
            border-color: #3b82f6;
            color: #fff;
        }

        .alert i {
            font-size: 20px;
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

        /* Form */
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

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            font-size: 16px;
            z-index: 1;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: white;
        }

        /* Button */
        .btn-login {
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

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.5);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Links */
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .register-link a {
            color: white;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
        }

        .register-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: white;
            transition: width 0.3s;
        }

        .register-link a:hover::after {
            width: 100%;
        }

        .register-link a i {
            margin-left: 8px;
            font-size: 12px;
            transition: transform 0.3s;
        }

        .register-link a:hover i {
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

        /* Contact Card */
        .contact-card {
            margin-top: 25px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            animation: fadeInUp 0.8s 0.2s both;
        }

        .contact-title {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .contact-title i {
            color: #ffd700;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            margin-bottom: 10px;
            transition: all 0.3s;
            border: 1px solid transparent;
        }

        .contact-item:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .contact-info {
            flex: 1;
        }

        .contact-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
        }

        .contact-value {
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .whatsapp-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px;
            background: linear-gradient(135deg, #25D366, #128C7E);
            border-radius: 14px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin-top: 15px;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .whatsapp-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(37, 211, 102, 0.4);
        }

        .whatsapp-btn i {
            font-size: 18px;
        }

        /* Loading */
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

        /* Responsive */
        @media (max-width: 480px) {
            .glass-card {
                padding: 30px 20px;
            }
            
            body {
                padding: 20px 15px;
            }
            
            .orb-1 {
                width: 250px;
                height: 250px;
            }
            
            .orb-2 {
                width: 200px;
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="gradient-bg"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <!-- Loading -->
    <div class="loading" id="loading">
        <div class="loader"></div>
        <div class="loading-text">MEMVERIFIKASI LOGIN...</div>
    </div>

    <div class="login-container">
        <div class="glass-card">
            <div class="login-header">
                <div class="brand-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h2>UNIT PRODUKSI RPL</h2>
                <p>SMK Negeri 24 Jakarta</p>
                <div class="role-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>ADMIN UNIT PRODUKSI</span>
                </div>
            </div>

            <?php if ($error): 
                $alertClass = 'alert-danger';
                $icon = 'fa-exclamation-circle';
                
                if ($error_type === 'not_found') {
                    $alertClass = 'alert-warning';
                    $icon = 'fa-user-slash';
                } elseif ($error_type === 'wrong_password') {
                    $alertClass = 'alert-danger';
                    $icon = 'fa-lock';
                } elseif ($error_type === 'expired') {
                    $alertClass = 'alert-warning';
                    $icon = 'fa-hourglass-end';
                } elseif ($error_type === 'inactive') {
                    $alertClass = 'alert-info';
                    $icon = 'fa-ban';
                }
            ?>
                <div class="alert <?php echo $alertClass; ?>">
                    <i class="fas <?php echo $icon; ?>"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label><i class="fas fa-user me-2"></i>USERNAME</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fas fa-id-badge"></i></span>
                        <input type="text" class="form-control" name="username" placeholder="Masukkan username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock me-2"></i>PASSWORD</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fas fa-key"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    LOGIN
                </button>
            </form>

            <div class="register-link">
                <a href="register.php">
                    Belum punya akun? Daftar disini
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

        <!-- Contact Card -->
        <div class="contact-card">
            <div class="contact-title">
                <i class="fas fa-headset"></i>
                BUTUH BANTUAN?
            </div>
            
            <div class="contact-item">
                <div class="contact-icon">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div class="contact-info">
                    <div class="contact-label">WHATSAPP ADMIN PUSAT</div>
                    <div class="contact-value"><?php echo ADMIN_PHONE; ?></div>
                </div>
            </div>
            
            <div class="contact-item">
                <div class="contact-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="contact-info">
                    <div class="contact-label">EMAIL ADMIN PUSAT</div>
                    <div class="contact-value"><?php echo ADMIN_EMAIL; ?></div>
                </div>
            </div>
            
            <a href="https://wa.me/<?php echo ADMIN_PHONE; ?>?text=Halo%20Admin%20Pusat%2C%20saya%20minta%20bantuan%20login" 
               class="whatsapp-btn" target="_blank">
                <i class="fab fa-whatsapp"></i>
                HUBUNGI ADMIN VIA WA
            </a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('loading').classList.add('active');
        });
    </script>
</body>
</html>