<?php
// ==================== LOGIN ADMIN PUSAT ====================
// File: login_pusat.php - UI KREATIF & ESTETIK (HALAMAN KHUSUS)

require_once 'config/database.php';
require_once 'includes/functions.php';

// Cek apakah session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah sudah login
if (isset($_SESSION['admin_pusat_id'])) {
    header('Location: admin_pusat.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Untuk admin pusat, cek dari database
    if ($username === 'naufal19smkn24' && $password === 'Naufal2008smkn24jktUPRPL01!') {
        $_SESSION['admin_pusat_id'] = 1;
        $_SESSION['admin_pusat_username'] = 'naufal19smkn24';
        $_SESSION['admin_pusat_name'] = 'Admin Pusat';
        $_SESSION['admin_pusat_ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['admin_pusat_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['admin_pusat_login_time'] = time();
        $_SESSION['admin_pusat_last_activity'] = time();
        
        logActivity($pdo, 1, 'naufal19smkn24', 'login_success_pusat', 'Login admin pusat berhasil');
        
        header('Location: admin_pusat.php');
        exit;
    } else {
        $error = '❌ Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin Pusat - Unit Produksi RPL</title>
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
            background: #0a0c1a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Premium Dark Background */
        .premium-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 30%, #1a1f3a, #0a0c1a);
            z-index: -2;
        }

        /* Animated Grid */
        .grid-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(255, 215, 0, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 215, 0, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 20s linear infinite;
            z-index: -1;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        /* Floating Particles */
        .particle {
            position: fixed;
            width: 2px;
            height: 2px;
            background: rgba(255, 215, 0, 0.3);
            border-radius: 50%;
            animation: floatParticle 15s infinite;
        }

        @keyframes floatParticle {
            0% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) translateX(100px); opacity: 0; }
        }

        /* Main Container */
        .login-container {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 10;
            animation: fadeInScale 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Premium Glass Card */
        .premium-card {
            background: rgba(26, 31, 58, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 40px;
            padding: 50px 40px;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 215, 0, 0.2),
                0 0 30px rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .premium-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 50% 50%, rgba(255, 215, 0, 0.1), transparent 70%);
            animation: rotate 20s linear infinite;
            z-index: -1;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Crown Decoration */
        .crown-decoration {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ffd700, #ffa500);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #1a1f3a;
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.5);
            border: 4px solid rgba(255, 255, 255, 0.3);
            animation: crownGlow 3s infinite;
        }

        @keyframes crownGlow {
            0%, 100% { box-shadow: 0 10px 30px rgba(255, 215, 0, 0.5); }
            50% { box-shadow: 0 20px 50px rgba(255, 215, 0, 0.8); }
        }

        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, #ffd700);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            font-weight: 400;
        }

        .badge-pusat {
            display: inline-block;
            background: linear-gradient(135deg, #ffd700, #ffa500);
            padding: 10px 30px;
            border-radius: 50px;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        .badge-pusat span {
            color: #1a1f3a;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 1px;
        }

        .badge-pusat i {
            color: #1a1f3a;
            margin-right: 10px;
        }

        /* Alert */
        .alert {
            padding: 16px 20px;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            backdrop-filter: blur(10px);
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert i {
            font-size: 20px;
            color: #ef4444;
        }

        /* Form */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #ffd700;
            font-size: 18px;
            z-index: 1;
        }

        .form-control {
            width: 100%;
            padding: 18px 18px 18px 52px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            font-size: 16px;
            color: white;
            transition: all 0.3s;
            backdrop-filter: blur(5px);
        }

        .form-control:focus {
            outline: none;
            border-color: #ffd700;
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.2);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .form-control[readonly] {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 215, 0, 0.4);
            color: #ffd700;
            font-weight: 600;
            cursor: not-allowed;
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #ffd700, #ffa500);
            border: none;
            border-radius: 20px;
            color: #1a1f3a;
            font-weight: 800;
            font-size: 16px;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
            margin-top: 15px;
            text-transform: uppercase;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(255, 215, 0, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            font-size: 18px;
        }

        /* Info Card */
        .info-card {
            margin-top: 25px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            backdrop-filter: blur(10px);
            text-align: center;
        }

        .info-card p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            line-height: 1.6;
        }

        .info-card i {
            color: #ffd700;
            margin-right: 8px;
        }

        .info-card strong {
            color: #ffd700;
            font-weight: 700;
        }

        /* Back Link */
        .back-link {
            text-align: center;
            margin-top: 25px;
        }

        .back-link a {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .back-link a:hover {
            color: #ffd700;
            background: rgba(255, 215, 0, 0.1);
            border-color: #ffd700;
            transform: translateX(-5px);
        }

        /* Loading */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 12, 26, 0.95);
            backdrop-filter: blur(20px);
            z-index: 99999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .loading.active {
            display: flex;
        }

        .premium-loader {
            width: 100px;
            height: 100px;
            position: relative;
            margin-bottom: 30px;
        }

        .premium-loader::before,
        .premium-loader::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid transparent;
            border-top-color: #ffd700;
            border-bottom-color: #ffa500;
            animation: spin 1.5s linear infinite;
        }

        .premium-loader::after {
            animation: spin 2s linear infinite reverse;
            border-top-color: #ffa500;
            border-bottom-color: #ffd700;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: white;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 3px;
            animation: glow 2s infinite;
        }

        @keyframes glow {
            0%, 100% { text-shadow: 0 0 10px #ffd700; }
            50% { text-shadow: 0 0 20px #ffd700, 0 0 30px #ffa500; }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .premium-card {
                padding: 40px 25px;
            }
            
            .crown-decoration {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }
            
            .login-header h2 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Premium Background -->
    <div class="premium-bg"></div>
    <div class="grid-bg"></div>
    
    <!-- Particles -->
    <script>
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
            document.body.appendChild(particle);
        }
    </script>

    <!-- Loading -->
    <div class="loading" id="loading">
        <div class="premium-loader"></div>
        <div class="loading-text">MEMVERIFIKASI LOGIN...</div>
    </div>

    <div class="login-container">
        <div class="premium-card">
            <!-- Crown Decoration -->
            <div class="crown-decoration">
                <i class="fas fa-crown"></i>
            </div>

            <div class="login-header">
                <h2>UNIT PRODUKSI RPL</h2>
                <p>SMK Negeri 24 Jakarta</p>
                <div class="badge-pusat">
                    <i class="fas fa-shield-alt"></i>
                    <span>ADMIN PUSAT</span>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label><i class="fas fa-user me-2"></i>USERNAME</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fas fa-id-badge"></i></span>
                        <input type="text" class="form-control" name="username" value="naufal19smkn24" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock me-2"></i>PASSWORD</label>
                    <div class="input-wrapper">
                        <span class="input-icon"><i class="fas fa-key"></i></span>
                        <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    LOGIN KE DASHBOARD
                </button>
            </form>

            <div class="info-card">
                <p>
                    <i class="fas fa-info-circle"></i>
                    Halaman ini <strong>khusus Admin Pusat</strong> dan tidak tersedia di menu publik.
                </p>
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
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('loading').classList.add('active');
        });
    </script>
</body>
</html>