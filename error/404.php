<?php
// ==================== ERROR 404 - NOT FOUND ====================
// File: 404.php - FIX VERSION

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .error-box {
            background: white;
            border-radius: 30px;
            padding: 50px;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            animation: fadeInUp 0.6s ease;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .error-code {
            font-size: 120px;
            font-weight: 900;
            color: #3498db;
            line-height: 1;
            margin-bottom: 10px;
            text-shadow: 5px 5px 0 rgba(52,152,219,0.2);
        }
        .error-icon {
            font-size: 80px;
            color: #3498db;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .error-message {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.7;
        }
        .btn-home {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            border: none;
            padding: 14px 35px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(52,152,219,0.3);
        }
        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(52,152,219,0.4);
            color: white;
        }
        .btn-home:active {
            transform: translateY(-1px);
        }
        .footer-text {
            margin-top: 30px;
            color: #95a5a6;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-icon">
            <i class="bi bi-compass"></i>
        </div>
        <div class="error-code">404</div>
        <div class="error-title">Halaman Tidak Ditemukan</div>
        <div class="error-message">
            Maaf, halaman yang Anda cari tidak ditemukan atau telah dipindahkan.<br>
            Periksa kembali URL atau kembali ke beranda.
        </div>
        <a href="index.php" class="btn-home">
            <i class="bi bi-house-door-fill"></i>
            Kembali ke Beranda
        </a>
        <div class="footer-text">
            <i class="bi bi-shield-check me-1"></i>
            Unit Produksi RPL - SMK Negeri 24 Jakarta
        </div>
    </div>
</body>
</html>