<?php
// ==================== PAYMENT AJAX ====================
// File: payment_ajax.php - VERSI FINAL

require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'confirm_payment':
        $orderNumber = $_POST['order_number'] ?? '';
        $method = $_POST['method'] ?? 'dana';
        $amount = (int)($_POST['amount'] ?? 0);
        
        if (empty($orderNumber) || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            exit;
        }
        
        // Upload file
        if (!isset($_FILES['file'])) {
            echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']);
            exit;
        }
        
        $uploadResult = uploadFile($_FILES['file'], 'payments');
        
        if (!$uploadResult['success']) {
            echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Ambil data order
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
            $stmt->execute([$orderNumber]);
            $order = $stmt->fetch();
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
                exit;
            }
            
            // Simpan payment
            $stmt = $pdo->prepare("INSERT INTO payments (order_number, customer_name, customer_phone, customer_email, amount, unique_code, method, file_name, file_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $orderNumber,
                $order['customer_name'],
                $order['customer_phone'],
                $order['customer_email'],
                $amount,
                $order['unique_code'],
                $method,
                $_FILES['file']['name'],
                $uploadResult['path']
            ]);
            
            // Update status order
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', payment_method = ?, payment_time = NOW() WHERE order_number = ?");
            $stmt->execute([$method, $orderNumber]);
            
            // Log activity
            logActivity($pdo, null, $order['customer_name'], 'payment', "Konfirmasi pembayaran untuk order $orderNumber");
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil dikonfirmasi']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        break;
        
    case 'verify_payment':
        // Cek login admin pusat
        if (!isset($_SESSION['admin_pusat_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $paymentId = $_POST['id'] ?? '';
        
        if (empty($paymentId)) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Ambil data payment
            $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();
            
            if (!$payment) {
                echo json_encode(['success' => false, 'message' => 'Pembayaran tidak ditemukan']);
                exit;
            }
            
            // Update payment
            $stmt = $pdo->prepare("UPDATE payments SET verified = TRUE, verified_by = ?, verified_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['admin_pusat_id'], $paymentId]);
            
            // Update order
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'verified' WHERE order_number = ?");
            $stmt->execute([$payment['order_number']]);
            
            // Log activity
            logActivity($pdo, $_SESSION['admin_pusat_id'], $_SESSION['admin_pusat_username'], 'verify_payment', "Verifikasi pembayaran untuk order " . $payment['order_number']);
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Pembayaran diverifikasi']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
}
?>