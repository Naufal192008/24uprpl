<?php
// ==================== ORDER AJAX ====================
// File: order_ajax.php - VERSI LENGKAP DENGAN DETAIL ORDER

require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['admin_up_id']) && !isset($_SESSION['admin_pusat_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Tentukan user yang login
if (isset($_SESSION['admin_up_id'])) {
    $userId = $_SESSION['admin_up_id'];
    $username = $_SESSION['admin_up_username'];
    $role = 'admin_up';
} else {
    $userId = $_SESSION['admin_pusat_id'];
    $username = $_SESSION['admin_pusat_username'];
    $role = 'super_admin';
}

$action = $_POST['action'] ?? '';

switch ($action) {
    
    // ==================== COMPLETE ORDER ====================
    case 'complete_order':
        $orderNumber = $_POST['order_number'] ?? '';
        
        if (empty($orderNumber)) {
            echo json_encode(['success' => false, 'message' => 'Nomor order tidak valid']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Cek apakah order ini milik admin yang login (jika admin UP)
            if ($role === 'admin_up') {
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND assigned_to = ?");
                $stmt->execute([$orderNumber, $username]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
                $stmt->execute([$orderNumber]);
            }
            
            $order = $stmt->fetch();
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE orders SET status = 'success', completed_at = NOW() WHERE order_number = ?");
            $stmt->execute([$orderNumber]);
            
            logActivity($pdo, $userId, $username, 'complete_order', "Menyelesaikan order: $orderNumber");
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Order selesai']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== TAKE ORDER ====================
    case 'take_order':
        $orderNumber = $_POST['order_number'] ?? '';
        
        if (empty($orderNumber)) {
            echo json_encode(['success' => false, 'message' => 'Nomor order tidak valid']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // ========== CEK BATASAN PESANAN PER ADMIN ==========
            // Cek apakah user ini udah pegang berapa pesanan yang masih proses
            $stmt_cek = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE assigned_to = ? AND status = 'process'");
            $stmt_cek->execute([$username]);
            $jumlah_pegang = $stmt_cek->fetch()['total'];
            
            // Batasi maksimal 3 pesanan per admin (bisa diubah angkanya)
            $MAX_PESANAN = 3; // Ganti angka ini sesuai keinginan
            
            if ($jumlah_pegang >= $MAX_PESANAN) {
                echo json_encode([
                    'success' => false, 
                    'message' => "Kamu sudah memegang $jumlah_pegang pesanan. Selesaikan dulu sebelum mengambil yang baru! (Maksimal $MAX_PESANAN)"
                ]);
                exit;
            }
            // ===================================================
            
            // Cek apakah order masih available (belum diassign)
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND assigned_to IS NULL");
            $stmt->execute([$orderNumber]);
            $order = $stmt->fetch();
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Pesanan sudah diambil orang lain']);
                exit;
            }
            
            // Update assigned_to dan status
            $stmt = $pdo->prepare("UPDATE orders SET assigned_to = ?, status = 'process', assigned_at = NOW() WHERE order_number = ?");
            $stmt->execute([$username, $orderNumber]);
            
            // Log aktivitas
            logActivity($pdo, $userId, $username, 'take_order', "Mengambil pesanan: $orderNumber");
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Pesanan berhasil diambil',
                'sisa_kuota' => $MAX_PESANAN - ($jumlah_pegang + 1)
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== GET ORDER DETAIL ====================
    case 'get_order_detail':
        $orderNumber = $_POST['order_number'] ?? '';
        
        if (empty($orderNumber)) {
            echo json_encode(['success' => false, 'message' => 'Nomor order tidak valid']);
            exit;
        }
        
        try {
            // Ambil detail order
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
            $stmt->execute([$orderNumber]);
            $order = $stmt->fetch();
            
            if ($order) {
                // Format tanggal biar rapi
                $order['created_at_formatted'] = date('d/m/Y H:i', strtotime($order['created_at']));
                if ($order['assigned_at']) {
                    $order['assigned_at_formatted'] = date('d/m/Y H:i', strtotime($order['assigned_at']));
                }
                if ($order['completed_at']) {
                    $order['completed_at_formatted'] = date('d/m/Y H:i', strtotime($order['completed_at']));
                }
                
                echo json_encode(['success' => true, 'order' => $order]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== CANCEL ORDER ====================
    case 'cancel_order':
        // Hanya admin pusat yang boleh cancel
        if ($role !== 'super_admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $orderNumber = $_POST['order_number'] ?? '';
        
        if (empty($orderNumber)) {
            echo json_encode(['success' => false, 'message' => 'Nomor order tidak valid']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_number = ?");
            $stmt->execute([$orderNumber]);
            
            logActivity($pdo, $userId, $username, 'cancel_order', "Membatalkan order: $orderNumber");
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Order dibatalkan']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== LIST ORDERS BY STATUS ====================
    case 'list_orders':
        $status = $_POST['status'] ?? '';
        
        try {
            if ($role === 'super_admin') {
                // Admin Pusat lihat semua
                if ($status) {
                    $stmt = $pdo->prepare("SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC");
                    $stmt->execute([$status]);
                } else {
                    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
                }
            } else {
                // Admin UP lihat punya sendiri + yang belum diassign
                if ($status) {
                    $stmt = $pdo->prepare("SELECT * FROM orders WHERE (assigned_to = ? OR assigned_to IS NULL) AND status = ? ORDER BY created_at DESC");
                    $stmt->execute([$username, $status]);
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM orders WHERE assigned_to = ? OR assigned_to IS NULL ORDER BY created_at DESC");
                    $stmt->execute([$username]);
                }
            }
            
            $orders = $stmt->fetchAll();
            echo json_encode(['success' => true, 'orders' => $orders]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        break;
    
    // ==================== DEFAULT ====================
    default:
        echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal: ' . $action]);
}
?>