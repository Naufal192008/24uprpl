<?php
// ==================== ADMIN AJAX ====================
// File: admin_ajax.php - VERSI FINAL DENGAN SMTP

// MATIKAN ERROR DISPLAY
ini_set('display_errors', 0);
error_reporting(0);

// Mulai session
session_start();

header('Content-Type: application/json');

// CEK SESSION
if (!isset($_SESSION['admin_pusat_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['admin_up_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized - Session tidak ditemukan'
    ]);
    exit;
}

// Tentukan user_id berdasarkan role
if (isset($_SESSION['admin_pusat_id'])) {
    $user_id = $_SESSION['admin_pusat_id'];
    $role = 'super_admin';
} elseif (isset($_SESSION['admin_up_id'])) {
    $user_id = $_SESSION['admin_up_id'];
    $role = 'admin_up';
} else {
    $user_id = $_SESSION['user_id'];
    $role = 'user';
}

try {
    require_once 'config/database.php';
    require_once 'includes/functions.php';
    
    // Ambil data user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    // TEST ACTION
    if ($action === 'test') {
        echo json_encode([
            'success' => true, 
            'message' => 'AJAX BERHASIL!',
            'user' => $user['username'],
            'role' => $role,
            'session_id' => session_id()
        ]);
        exit;
    }
    
    // ==================== AKSI SUPER ADMIN ONLY ====================
    if ($role !== 'super_admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Hanya untuk Admin Pusat']);
        exit;
    }
    
    // DELETE ADMIN
    if ($action === 'delete_admin') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 1) {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus admin pusat']);
            exit;
        }
        
        try {
            // CEK DULU APAKAH USER ADA
            $check = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin_up'");
            $check->execute([$id]);
            
            if (!$check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'User tidak ditemukan di database']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin_up'");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($pdo, $user_id, $user['username'], 'delete_admin', "Menghapus admin ID: $id");
                echo json_encode(['success' => true, 'message' => 'Admin berhasil dihapus']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Admin tidak ditemukan']);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // TOGGLE ADMIN STATUS
    if ($action === 'toggle_admin') {
        $id = (int)($_POST['id'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ? AND role = 'admin_up'");
            $stmt->execute([$id]);
            $admin = $stmt->fetch();
            
            if (!$admin) {
                echo json_encode(['success' => false, 'message' => 'Admin tidak ditemukan']);
                exit;
            }
            
            $newStatus = $admin['status'] === 'active' ? 'inactive' : 'active';
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);
            
            logActivity($pdo, $user_id, $user['username'], 'toggle_admin', "Mengubah status admin ID: $id menjadi $newStatus");
            echo json_encode(['success' => true, 'message' => 'Status admin diubah']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // ADD ADMIN
    if ($action === 'add_admin') {
        $name = sanitize($_POST['name'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $notes = sanitize($_POST['notes'] ?? '');
        
        if (empty($name) || empty($username) || empty($email) || empty($phone) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Cek username sudah ada
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
                exit;
            }
            
            // Cek email sudah ada
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email sudah digunakan']);
                exit;
            }
            
            // Hash password
            $hashedPassword = hashPassword($password);
            $passwordExpiry = date('Y-m-d H:i:s', time() + (90 * 24 * 60 * 60)); // 90 hari
            
            // Simpan ke pending_passwords
            $expiryTime = date('Y-m-d H:i:s', time() + (PASSWORD_EXPIRY * 60));
            $stmt = $pdo->prepare("INSERT INTO pending_passwords (username, password, email, expiry_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password, $email, $expiryTime]);
            
            // Simpan ke users
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, name, phone, role, status, password_expiry, notes) VALUES (?, ?, ?, ?, ?, 'admin_up', ?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword, $name, $phone, $status, $passwordExpiry, $notes]);
            
            $newId = $pdo->lastInsertId();
            
            // Kirim email via SMTP
            $emailResult = sendEmailViaSMTP($email, $name, $username, $password);
            
            // Log ke email_logs
            $stmt = $pdo->prepare("INSERT INTO email_logs (recipient, subject, status, response, created_at) VALUES (?, 'Password Login Admin UP', ?, ?, NOW())");
            $stmt->execute([$email, $emailResult['success'] ? 'success' : 'failed', $emailResult['message']]);
            
            logActivity($pdo, $user_id, $user['username'], 'add_admin', "Menambahkan admin baru: $username");
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Admin berhasil ditambahkan. ' . ($emailResult['success'] ? 'Email terkirim.' : 'Email gagal dikirim: ' . $emailResult['message'])
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // UPDATE ADMIN
    if ($action === 'update_admin') {
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $notes = sanitize($_POST['notes'] ?? '');
        
        if ($id <= 0 || empty($name) || empty($username) || empty($email) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Cek username sudah ada (kecuali untuk user ini)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
                exit;
            }
            
            // Cek email sudah ada (kecuali untuk user ini)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email sudah digunakan']);
                exit;
            }
            
            if ($password !== '********') {
                // Update dengan password baru
                $hashedPassword = hashPassword($password);
                $passwordExpiry = date('Y-m-d H:i:s', time() + (90 * 24 * 60 * 60));
                
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, name = ?, phone = ?, status = ?, password_expiry = ?, notes = ? WHERE id = ?");
                $stmt->execute([$username, $email, $hashedPassword, $name, $phone, $status, $passwordExpiry, $notes, $id]);
                
                // Simpan ke pending_passwords
                $expiryTime = date('Y-m-d H:i:s', time() + (PASSWORD_EXPIRY * 60));
                $stmt = $pdo->prepare("INSERT INTO pending_passwords (username, password, email, expiry_time) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $password, $email, $expiryTime]);
                
                // Kirim email
                $emailResult = sendEmailViaSMTP($email, $name, $username, $password);
                
            } else {
                // Update tanpa ganti password
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, name = ?, phone = ?, status = ?, notes = ? WHERE id = ?");
                $stmt->execute([$username, $email, $name, $phone, $status, $notes, $id]);
                $emailResult = ['success' => true, 'message' => 'Tidak ada email dikirim (password tidak diubah)'];
            }
            
            logActivity($pdo, $user_id, $user['username'], 'update_admin', "Mengupdate admin ID: $id");
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Admin berhasil diupdate']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // RESET PASSWORD
    if ($action === 'reset_password') {
        $id = (int)($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $email = $_POST['email'] ?? '';
        $name = $_POST['name'] ?? '';
        
        if ($id <= 0 || empty($password) || empty($email) || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Ambil username dari database
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $userData = $stmt->fetch();
            
            if (!$userData) {
                echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
                exit;
            }
            
            $username = $userData['username'];
            
            // Update password
            $hashedPassword = hashPassword($password);
            $passwordExpiry = date('Y-m-d H:i:s', time() + (90 * 24 * 60 * 60));
            
            $stmt = $pdo->prepare("UPDATE users SET password = ?, password_expiry = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $passwordExpiry, $id]);
            
            // Simpan ke pending_passwords
            $expiryTime = date('Y-m-d H:i:s', time() + (PASSWORD_EXPIRY * 60));
            $stmt = $pdo->prepare("INSERT INTO pending_passwords (username, password, email, expiry_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password, $email, $expiryTime]);
            
            // KIRIM EMAIL VIA SMTP
            error_log("RESET PASSWORD - MENGIRIM EMAIL KE: $email");
            error_log("RESET PASSWORD - PASSWORD: $password");
            
            $emailResult = sendEmailViaSMTP($email, $name, $username, $password);
            
            // Log ke email_logs
            $stmt = $pdo->prepare("INSERT INTO email_logs (recipient, subject, status, response, created_at) VALUES (?, 'Reset Password Admin UP', ?, ?, NOW())");
            $stmt->execute([$email, $emailResult['success'] ? 'success' : 'failed', $emailResult['message']]);
            
            logActivity($pdo, $user_id, $user['username'], 'reset_password', "Reset password untuk admin ID: $id");
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'email_sent' => $emailResult['success'],
                'message' => $emailResult['success'] ? 'Password berhasil direset & email terkirim' : 'Password berhasil direset TAPI email gagal: ' . $emailResult['message']
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // RESET ALL EXPIRED
    if ($action === 'reset_all_expired') {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin_up' AND password_expiry <= NOW()");
            $stmt->execute();
            $expiredAdmins = $stmt->fetchAll();
            
            $count = 0;
            $emailSuccess = 0;
            
            foreach ($expiredAdmins as $admin) {
                $newPassword = generateRandomPassword(12);
                $hashedPassword = hashPassword($newPassword);
                $passwordExpiry = date('Y-m-d H:i:s', time() + (90 * 24 * 60 * 60));
                
                $stmt = $pdo->prepare("UPDATE users SET password = ?, password_expiry = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $passwordExpiry, $admin['id']]);
                
                // Simpan ke pending_passwords
                $expiryTime = date('Y-m-d H:i:s', time() + (PASSWORD_EXPIRY * 60));
                $stmt = $pdo->prepare("INSERT INTO pending_passwords (username, password, email, expiry_time) VALUES (?, ?, ?, ?)");
                $stmt->execute([$admin['username'], $newPassword, $admin['email'], $expiryTime]);
                
                // Kirim email
                $result = sendEmailViaSMTP($admin['email'], $admin['name'], $admin['username'], $newPassword);
                if ($result['success']) $emailSuccess++;
                
                $count++;
            }
            
            $pdo->commit();
            
            logActivity($pdo, $user_id, $user['username'], 'reset_all_expired', "Reset semua password expired: $count admin");
            
            echo json_encode([
                'success' => true, 
                'message' => "Berhasil reset $count admin expired. Email terkirim: $emailSuccess"
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // RESET ALL EXPIRING
    if ($action === 'reset_all_expiring') {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin_up' AND password_expiry > NOW() AND password_expiry <= DATE_ADD(NOW(), INTERVAL 24 HOUR)");
            $stmt->execute();
            $expiringAdmins = $stmt->fetchAll();
            
            $count = 0;
            $emailSuccess = 0;
            
            foreach ($expiringAdmins as $admin) {
                $newPassword = generateRandomPassword(12);
                $hashedPassword = hashPassword($newPassword);
                $passwordExpiry = date('Y-m-d H:i:s', time() + (90 * 24 * 60 * 60));
                
                $stmt = $pdo->prepare("UPDATE users SET password = ?, password_expiry = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $passwordExpiry, $admin['id']]);
                
                // Simpan ke pending_passwords
                $expiryTime = date('Y-m-d H:i:s', time() + (PASSWORD_EXPIRY * 60));
                $stmt = $pdo->prepare("INSERT INTO pending_passwords (username, password, email, expiry_time) VALUES (?, ?, ?, ?)");
                $stmt->execute([$admin['username'], $newPassword, $admin['email'], $expiryTime]);
                
                // Kirim email
                $result = sendEmailViaSMTP($admin['email'], $admin['name'], $admin['username'], $newPassword);
                if ($result['success']) $emailSuccess++;
                
                $count++;
            }
            
            $pdo->commit();
            
            logActivity($pdo, $user_id, $user['username'], 'reset_all_expiring', "Reset semua password akan expired: $count admin");
            
            echo json_encode([
                'success' => true, 
                'message' => "Berhasil reset $count admin akan expired. Email terkirim: $emailSuccess"
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // RESET ALL PASSWORDS
    if ($action === 'reset_all_passwords') {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin_up'");
            $stmt->execute();
            $allAdmins = $stmt->fetchAll();
            
            $count = 0;
            $emailSuccess = 0;
            
            foreach ($allAdmins as $admin) {
                $newPassword = generateRandomPassword(12);
                $hashedPassword = hashPassword($newPassword);
                $passwordExpiry = date('Y-m-d H:i:s', time() + (90 * 24 * 60 * 60));
                
                $stmt = $pdo->prepare("UPDATE users SET password = ?, password_expiry = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $passwordExpiry, $admin['id']]);
                
                // Simpan ke pending_passwords
                $expiryTime = date('Y-m-d H:i:s', time() + (PASSWORD_EXPIRY * 60));
                $stmt = $pdo->prepare("INSERT INTO pending_passwords (username, password, email, expiry_time) VALUES (?, ?, ?, ?)");
                $stmt->execute([$admin['username'], $newPassword, $admin['email'], $expiryTime]);
                
                // Kirim email
                $result = sendEmailViaSMTP($admin['email'], $admin['name'], $admin['username'], $newPassword);
                if ($result['success']) $emailSuccess++;
                
                $count++;
            }
            
            $pdo->commit();
            
            logActivity($pdo, $user_id, $user['username'], 'reset_all_passwords', "Reset SEMUA password: $count admin");
            
            echo json_encode([
                'success' => true, 
                'message' => "Berhasil reset SEMUA password ($count admin). Email terkirim: $emailSuccess"
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // CHECK LOGIN
    if ($action === 'check_login') {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => $user['username'],
            'role' => $role
        ]);
        exit;
    }
    
    // DEFAULT RESPONSE
    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal: ' . $action]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>