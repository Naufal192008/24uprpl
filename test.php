<?php
// ==================== SIMPLE TEST ====================
// File: test.php - FIX VERSION

session_start();

// Cek apakah ada parameter khusus
$clear = $_GET['clear'] ?? false;
if ($clear) {
    session_destroy();
    session_start();
    echo "Session telah dihapus. Refresh halaman.<br>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Unit Produksi RPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            padding: 30px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .test-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        h2 {
            color: #2c3e50;
            font-weight: 800;
            margin-bottom: 20px;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        .btn-test {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .btn-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="test-card">
        <h2><i class="bi bi-info-circle"></i> System Test</h2>
        
        <div class="alert alert-info">
            <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
            <strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
            <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?><br>
            <strong>Request URI:</strong> <?php echo $_SERVER['REQUEST_URI']; ?><br>
            <strong>Remote Addr:</strong> <?php echo $_SERVER['REMOTE_ADDR']; ?>
        </div>

        <h3 class="mt-4">Session Data:</h3>
        <pre><?php print_r($_SESSION); ?></pre>

        <h3 class="mt-4">Session ID:</h3>
        <pre><?php echo session_id(); ?></pre>

        <h3 class="mt-4">Constants:</h3>
        <pre>
BASE_URL: <?php echo defined('BASE_URL') ? BASE_URL : 'NOT DEFINED'; ?>

PASSWORD_EXPIRY: <?php echo defined('PASSWORD_EXPIRY') ? PASSWORD_EXPIRY : 'NOT DEFINED'; ?>

ADMIN_EMAIL: <?php echo defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'NOT DEFINED'; ?>

ADMIN_PHONE: <?php echo defined('ADMIN_PHONE') ? ADMIN_PHONE : 'NOT DEFINED'; ?>

DANA_NUMBER: <?php echo defined('DANA_NUMBER') ? DANA_NUMBER : 'NOT DEFINED'; ?>
        </pre>

        <div class="mt-4">
            <a href="?clear=1" class="btn-test">Clear Session</a>
            <a href="debug_session.php" class="btn-test" target="_blank">Debug Session</a>
            <a href="login_check.php" class="btn-test" target="_blank">Login Check</a>
            <a href="index.php" class="btn-test" target="_blank">Home</a>
        </div>
    </div>
</body>
</html>