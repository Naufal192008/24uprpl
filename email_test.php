<?php
// ==================== TEST EMAIL ====================
// File: email_test.php - VERSI FINAL
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email - Unit Produksi RPL</title>
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
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
            width: 120px;
            color: #2c3e50;
        }
        .config-value {
            color: #667eea;
            font-weight: 600;
            font-family: monospace;
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
    </style>
</head>
<body>
    <div class="test-card">
        <h1>📧 TEST EMAIL</h1>
        <p class="subtitle">Unit Produksi RPL - SMK Negeri 24 Jakarta</p>
        
        <div class="config">
            <div class="config-item">
                <span class="config-label">Public Key:</span>
                <span class="config-value">zO6XyHCxMhxySvvaU</span>
            </div>
            <div class="config-item">
                <span class="config-label">Service ID:</span>
                <span class="config-value">service_3anwd2k</span>
            </div>
            <div class="config-item">
                <span class="config-label">Template ID:</span>
                <span class="config-value">template_wb3cmkl</span>
            </div>
        </div>
        
        <div id="result" style="display: none;" class="result"></div>
        
        <form id="emailForm">
            <div class="form-group">
                <label>Email Tujuan:</label>
                <input type="email" id="to_email" value="nmurtadho1905@gmail.com" required>
            </div>
            
            <div class="form-group">
                <label>Nama Penerima:</label>
                <input type="text" id="to_name" value="Test User" required>
            </div>
            
            <div class="form-group">
                <label>Username:</label>
                <input type="text" id="username" value="testuser" required>
            </div>
            
            <div class="form-group">
                <label>Password (ASLI):</label>
                <input type="text" id="password" value="Test123!@#" required>
            </div>
            
            <button type="button" onclick="sendTestEmail()">
                TEST KIRIM EMAIL
            </button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Kembali ke Beranda</a>
        </div>
    </div>

    <script>
        // Inisialisasi EmailJS
        emailjs.init('zO6XyHCxMhxySvvaU');
        console.log('EmailJS initialized');

        function sendTestEmail() {
            const resultDiv = document.getElementById('result');
            const to_email = document.getElementById('to_email').value;
            const to_name = document.getElementById('to_name').value;
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            resultDiv.style.display = 'block';
            resultDiv.className = 'result';
            resultDiv.innerHTML = '📤 Mengirim email...';
            
            emailjs.send('service_3anwd2k', 'template_wb3cmkl', {
                to_email: to_email,
                to_name: to_name,
                username: username,
                password: password,
                login_url: window.location.origin + '/login.php',
                from_name: 'Admin Pusat Unit Produksi RPL',
                reply_to: 'nmurtadho1905@gmail.com'
            }).then(function(response) {
                resultDiv.className = 'result success';
                resultDiv.innerHTML = '✅ EMAIL BERHASIL DIKIRIM!<br>Cek inbox/SPAM email ' + to_email;
                console.log('Sukses:', response);
            }, function(error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '❌ GAGAL: ' + JSON.stringify(error);
                console.log('Error:', error);
            });
        }
    </script>
</body>
</html>