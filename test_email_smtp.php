<?php
// ==================== TEST EMAIL SMTP ====================
// File: test_email_smtp.php - VERSI FINAL

require_once 'config/database.php';
require_once 'includes/functions.php';

$result = '';
$debug = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? ADMIN_EMAIL;
    $name = $_POST['name'] ?? 'Test User';
    $username = $_POST['username'] ?? 'testuser';
    $password = $_POST['password'] ?? 'Test123!@#';
    
    // Test kirim email via SMTP
    $result = sendEmailViaSMTP($email, $name, $username, $password);
    
    // Log ke database
    try {
        $stmt = $pdo->prepare("INSERT INTO email_logs (recipient, subject, status, response, created_at) VALUES (?, 'Test Email SMTP', ?, ?, NOW())");
        $stmt->execute([$email, $result['success'] ? 'success' : 'failed', $result['message']]);
    } catch (Exception $e) {
        $debug .= "Log error: " . $e->getMessage() . "\n";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email SMTP - Unit Produksi RPL</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        .test-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            color: #7f8c8d;
            text-align: center;
            margin-bottom: 30px;
        }
        .config {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #667eea;
        }
        .config-item {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #ddd;
        }
        .config-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .config-label {
            font-weight: 600;
            width: 150px;
            color: #2c3e50;
        }
        .config-value {
            color: #667eea;
            font-weight: 600;
            font-family: monospace;
            word-break: break-all;
        }
        .result {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .debug {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
        }
        input:focus {
            border-color: #667eea;
            outline: none;
        }
        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        button:hover {
            transform: translateY(-3px);
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success-box {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .success-box h3 {
            color: #155724;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="test-card">
        <h1>📧 TEST EMAIL SMTP</h1>
        <p class="subtitle">Unit Produksi RPL - SMK Negeri 24 Jakarta</p>
        
        <div class="warning">
            <strong>⚠️ CARA MEMBUAT APP PASSWORD GMAIL:</strong>
            <ol style="margin-top: 10px; margin-left: 20px;">
                <li>Buka <a href="https://myaccount.google.com/security" target="_blank">Google Account Security</a></li>
                <li>Aktifkan <strong>2-Step Verification</strong></li>
                <li>Buka <a href="https://myaccount.google.com/apppasswords" target="_blank">App Passwords</a></li>
                <li>Pilih "Mail" dan "Other", beri nama "UP RPL"</li>
                <li>Copy 16 digit password (tanpa spasi)</li>
                <li>Masukkan ke <code>config/database.php</code> (SMTP_PASS)</li>
            </ol>
        </div>
        
        <div class="config">
            <div class="config-item">
                <span class="config-label">SMTP Host:</span>
                <span class="config-value"><?php echo SMTP_HOST; ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">SMTP Port:</span>
                <span class="config-value"><?php echo SMTP_PORT; ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">SMTP User:</span>
                <span class="config-value"><?php echo SMTP_USER; ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">SMTP Pass:</span>
                <span class="config-value">[TERSEMBUNYI] (<?php echo strlen(SMTP_PASS); ?> digit)</span>
            </div>
        </div>
        
        <?php if ($result): ?>
            <div class="result <?php echo $result['success'] ? 'success' : 'error'; ?>">
                <strong><?php echo $result['success'] ? '✅ BERHASIL!' : '❌ GAGAL!'; ?></strong><br>
                <?php echo $result['message']; ?>
            </div>
            
            <?php if ($result['success']): ?>
            <div class="success-box">
                <h3>✅ EMAIL BERHASIL DIKIRIM!</h3>
                <p>Cek inbox email: <strong><?php echo htmlspecialchars($email); ?></strong></p>
                <p>Jangan lupa cek folder <strong>SPAM</strong> jika tidak masuk.</p>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($debug): ?>
            <div class="debug">
                <strong>Debug Info:</strong><br>
                <?php echo nl2br($debug); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email Tujuan:</label>
                <input type="email" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ADMIN_EMAIL; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Nama Penerima:</label>
                <input type="text" name="name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : 'Test User'; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" value="<?php echo isset($_POST['username']) ? $_POST['username'] : 'testuser'; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Password (ASLI):</label>
                <input type="text" name="password" value="<?php echo isset($_POST['password']) ? $_POST['password'] : 'Test123!@#'; ?>" required>
            </div>
            
            <button type="submit">
                🚀 TEST KIRIM EMAIL VIA SMTP
            </button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>