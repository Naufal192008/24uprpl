<?php
// ==================== LOGIN CHECK ====================
// File: login_check.php - FIX VERSION

session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Check - Unit Produksi RPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            padding: 30px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: white;
            font-weight: 800;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 20px 25px;
            font-weight: 700;
        }
        .card-body {
            padding: 25px;
        }
        .session-table {
            width: 100%;
            border-collapse: collapse;
        }
        .session-table th {
            width: 30%;
            background: #f8fafc;
            padding: 12px 15px;
            font-weight: 600;
            border: 1px solid #e2e8f0;
        }
        .session-table td {
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            font-family: monospace;
        }
        .badge-active {
            background: #10b981;
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 14px;
        }
        .badge-inactive {
            background: #ef4444;
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 14px;
        }
        pre {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            max-height: 300px;
            overflow: auto;
        }
        .btn-check {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }
        .btn-check:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.4);
        }
        .btn-check:active {
            transform: translateY(0);
        }
        .result-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border-left: 5px solid #667eea;
        }
        .footer {
            text-align: center;
            color: rgba(255,255,255,0.9);
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center">
            <i class="bi bi-shield-lock-fill me-2"></i>
            LOGIN CHECK & SESSION DEBUG
        </h1>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-person-circle me-2"></i>
                        SESSION INFO
                    </div>
                    <div class="card-body">
                        <table class="session-table">
                            <tr>
                                <th>Session ID:</th>
                                <td><?php echo session_id(); ?></td>
                            </tr>
                            <tr>
                                <th>Session Name:</th>
                                <td><?php echo session_name(); ?></td>
                            </tr>
                            <tr>
                                <th>Session Status:</th>
                                <td>
                                    <?php
                                    $status = session_status();
                                    if ($status === PHP_SESSION_ACTIVE) {
                                        echo '<span class="badge-active">ACTIVE</span>';
                                    } else {
                                        echo '<span class="badge-inactive">INACTIVE</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>User ID:</th>
                                <td><?php echo $_SESSION['admin_pusat_id'] ?? $_SESSION['admin_up_id'] ?? $_SESSION['user_id'] ?? '<span class="text-muted">Not set</span>'; ?></td>
                            </tr>
                            <tr>
                                <th>Username:</th>
                                <td><?php echo $_SESSION['admin_pusat_username'] ?? $_SESSION['admin_up_username'] ?? $_SESSION['username'] ?? '<span class="text-muted">Not set</span>'; ?></td>
                            </tr>
                            <tr>
                                <th>Name:</th>
                                <td><?php echo $_SESSION['admin_pusat_name'] ?? $_SESSION['admin_up_name'] ?? $_SESSION['user_name'] ?? '<span class="text-muted">Not set</span>'; ?></td>
                            </tr>
                            <tr>
                                <th>Role:</th>
                                <td>
                                    <?php
                                    if (isset($_SESSION['admin_pusat_id'])) echo 'super_admin';
                                    elseif (isset($_SESSION['admin_up_id'])) echo 'admin_up';
                                    elseif (isset($_SESSION['user_id'])) echo 'user';
                                    else echo '<span class="text-muted">Not set</span>';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Login Time:</th>
                                <td>
                                    <?php
                                    $loginTime = $_SESSION['admin_pusat_login_time'] ?? $_SESSION['login_time'] ?? null;
                                    if ($loginTime) {
                                        echo date('Y-m-d H:i:s', $loginTime) . ' (' . round((time() - $loginTime) / 60) . ' menit yang lalu)';
                                    } else {
                                        echo '<span class="text-muted">Not set</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Last Activity:</th>
                                <td>
                                    <?php
                                    $lastActivity = $_SESSION['admin_pusat_last_activity'] ?? null;
                                    if ($lastActivity) {
                                        echo date('Y-m-d H:i:s', $lastActivity) . ' (' . round((time() - $lastActivity) / 60) . ' menit yang lalu)';
                                    } else {
                                        echo '<span class="text-muted">Not set</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-cookie me-2"></i>
                        COOKIE INFO
                    </div>
                    <div class="card-body">
                        <pre><?php print_r($_COOKIE); ?></pre>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <i class="bi bi-file-code me-2"></i>
                RAW SESSION DATA
            </div>
            <div class="card-body">
                <pre><?php print_r($_SESSION); ?></pre>
            </div>
        </div>

        <div class="text-center mt-4">
            <button class="btn-check" onclick="checkAjaxSession()">
                <i class="bi bi-arrow-repeat me-2"></i>
                TEST AJAX SESSION
            </button>
        </div>

        <div id="ajaxResult" class="result-box" style="display: none;">
            <h5 class="fw-bold mb-3"><i class="bi bi-shield-check me-2"></i>AJAX SESSION RESULT</h5>
            <div id="ajaxContent"></div>
        </div>

        <div class="mt-4 text-center">
            <a href="index.php" class="text-white text-decoration-none me-3">
                <i class="bi bi-house-door"></i> Beranda
            </a>
            <a href="login_pusat.php" class="text-white text-decoration-none me-3">
                <i class="bi bi-crown"></i> Login Pusat
            </a>
            <a href="login.php" class="text-white text-decoration-none me-3">
                <i class="bi bi-person"></i> Login UP
            </a>
            <a href="logout.php" class="text-white text-decoration-none">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <div class="footer">
        <p>© 2026 SMK Negeri 24 Jakarta - Unit Produksi RPL</p>
    </div>

    <script>
        function checkAjaxSession() {
            const resultDiv = document.getElementById('ajaxResult');
            const contentDiv = document.getElementById('ajaxContent');
            
            resultDiv.style.display = 'block';
            contentDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading...</p></div>';
            
            $.ajax({
                url: 'debug_session.php',
                method: 'GET',
                dataType: 'json',
                xhrFields: {
                    withCredentials: true
                },
                success: function(response) {
                    contentDiv.innerHTML = '<pre class="mb-0">' + JSON.stringify(response, null, 2) + '</pre>';
                },
                error: function(xhr, status, error) {
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Error:</strong> ${error}<br>
                            <strong>Status:</strong> ${status}<br>
                            <strong>Response:</strong> ${xhr.responseText}
                        </div>
                    `;
                }
            });
        }
    </script>
</body>
</html>