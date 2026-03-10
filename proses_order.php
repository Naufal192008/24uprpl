<?php
ini_set('log_errors', 1);
ini_set('error_log', 'C:\xampp1\php\logs\php_error_log');
error_log("========== PROSES ORDER DIAKSES ==========");
// ==================== PROSES ORDER ====================
// File: proses_order.php - VERSI DENGAN UPLOAD PDF

require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log request
error_log("PROSES ORDER STARTED: " . print_r($_POST, true));
error_log("FILES: " . print_r($_FILES, true));

try {
    // Validasi input
    $nama = sanitize($_POST['nama'] ?? '');
    $kelas = sanitize($_POST['kelas'] ?? '');
    $telepon = sanitize($_POST['telepon'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $catatan = sanitize($_POST['catatan'] ?? '');
    $jenis_layanan = $_POST['jenis_layanan'] ?? '';
    $ukuran = sanitize($_POST['ukuran'] ?? '');
    $jenis_sablon = sanitize($_POST['jenis_sablon'] ?? '');
    $warna_kaos = sanitize($_POST['warna_kaos'] ?? '');
    $ukuran_kertas = sanitize($_POST['ukuran_kertas'] ?? '');
    $upload_method = $_POST['upload_method'] ?? 'drive';
    
    // Validasi dasar
    if (empty($nama) || empty($kelas) || empty($telepon) || empty($email) || $jumlah < 1) {
        echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
        exit;
    }
    
    if (!validateEmail($email)) {
        echo json_encode(['success' => false, 'message' => 'Email tidak valid']);
        exit;
    }
    
    if (!validatePhone($telepon)) {
        echo json_encode(['success' => false, 'message' => 'Nomor HP harus 10-13 digit']);
        exit;
    }
    
    if (empty($jenis_layanan)) {
        echo json_encode(['success' => false, 'message' => 'Jenis layanan tidak boleh kosong']);
        exit;
    }
    
    // Handle Link Drive atau File Upload
    $drive_link = '';
    $file_path = '';
    
    if ($upload_method === 'drive') {
        // Validasi link drive
        $drive_link = sanitize($_POST['link_drive'] ?? '');
        if (empty($drive_link)) {
            echo json_encode(['success' => false, 'message' => 'Link Google Drive harus diisi']);
            exit;
        }
        if (!str_starts_with($drive_link, 'https://drive.google.com/') && !str_starts_with($drive_link, 'https://docs.google.com/')) {
            echo json_encode(['success' => false, 'message' => 'Link Google Drive tidak valid']);
            exit;
        }
    } else {
        // Validasi file upload
        if (!isset($_FILES['file_upload']) || $_FILES['file_upload']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'File PDF harus diupload']);
            exit;
        }
        
        $file = $_FILES['file_upload'];
        
        // Cek tipe file
        if ($file['type'] !== 'application/pdf') {
            echo json_encode(['success' => false, 'message' => 'File harus berformat PDF']);
            exit;
        }
        
        // Cek ukuran file (maks 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File terlalu besar! Maksimal 5MB']);
            exit;
        }
        
        // Buat folder uploads kalo belum ada
        $upload_dir = 'uploads/order_files/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate nama file unik
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        // Pindahin file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file']);
            exit;
        }
        
        $drive_link = ''; // Kosongin karena pake file
    }
    
    // ========== PARSE JENIS LAYANAN ==========
    error_log("JENIS LAYANAN DITERIMA: " . $jenis_layanan);

    // CEK APAKAH INI KAOS SABLON
    if (strpos($jenis_layanan, 'kaos_sablon') !== false) {
        $layanan = 'kaos_sablon';
        
        // Ambil jenis dari akhir string (kaos/sablon/paket)
        if (strpos($jenis_layanan, '_kaos') !== false) {
            $jenis = 'kaos';
        } elseif (strpos($jenis_layanan, '_sablon') !== false) {
            $jenis = 'sablon';
        } elseif (strpos($jenis_layanan, '_paket') !== false) {
            $jenis = 'paket';
        } else {
            $jenis = 'paket'; // default
        }
        
        error_log("KAOS SABLON DETECTED: layanan = $layanan, jenis = $jenis");
    } else {
        // Bukan kaos sablon, langsung pake string aslinya
        $layanan = $jenis_layanan;
        $jenis = '';
        error_log("LAYANAN BIASA: $layanan");
    }
    // ================================================================
    
    // Data layanan
    $layananData = [
        'print_hitam' => ['nama' => 'Print Hitam Putih', 'harga' => 1000],
        'print_warna' => ['nama' => 'Print Full Color', 'harga' => 2000],
        'fotocopy' => ['nama' => 'Fotocopy', 'harga' => 250],
        'kaos_sablon' => [
            'nama' => 'Kaos & Sablon',
            'harga_kaos' => 50000,
            'harga_sablon' => 55000,
            'harga_paket' => 105000
        ]
    ];
    
    // Validasi layanan
    if ($layanan == 'print_hitam' || $layanan == 'print_warna' || $layanan == 'fotocopy' || $layanan == 'kaos_sablon') {
        // Ini valid, lanjutkan
        error_log("LAYANAN VALID: $layanan");
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Layanan tidak valid: ' . $layanan . ' (dari: ' . $jenis_layanan . ')'
        ]);
        exit;
    }
    
    // ========== HITUNG HARGA ==========
    if ($layanan === 'kaos_sablon') {
        // Untuk kaos sablon, cek jenisnya
        if (empty($jenis)) {
            $jenis = 'paket'; // default
        }
        
        $hargaKey = 'harga_' . $jenis;
        if (!isset($layananData['kaos_sablon'][$hargaKey])) {
            echo json_encode(['success' => false, 'message' => 'Jenis paket tidak valid: ' . $jenis]);
            exit;
        }
        
        $hargaSatuan = $layananData['kaos_sablon'][$hargaKey];
        $serviceName = $layananData['kaos_sablon']['nama'] . ' (' . $jenis . ')';
        error_log("KAOS SABLON: harga = $hargaSatuan, nama = $serviceName");
    } else {
        // Untuk layanan biasa (print/fotocopy)
        $hargaSatuan = $layananData[$layanan]['harga'];
        $serviceName = $layananData[$layanan]['nama'];
        error_log("LAYANAN BIASA: harga = $hargaSatuan, nama = $serviceName");
    }
    // ==================================
    
    $subtotal = $hargaSatuan * $jumlah;
    $uniqueCode = generateUniqueCode();
    $total = $subtotal + $uniqueCode;
    $orderNumber = generateOrderNumber();
    
    // ========== SEMUA PESANAN MASUK KE ANTRIAN ==========
    // Tidak auto-assign ke siapapun
    // Semua admin akan lihat pesanan ini sebagai "Menunggu"
    $assigned_to = null;
    $status = 'pending_payment';

    error_log("PESANAN BARU: $orderNumber - $serviceName - Total: $total (MENUNGGU ADMIN)");
    // ====================================================
    
    // ========== SIMPAN KE DATABASE ==========
    // Catatan: Kita perlu tambah kolom file_path di tabel orders kalo belum ada
    // Tapi untuk sementara, kita simpan link drive aja (file_path bisa diisi path file)
    
    // Cek dulu struktur tabel, kalo belum ada kolom file_path, pake link drive aja
    try {
        $stmt = $pdo->prepare("INSERT INTO orders (
            order_number, customer_name, customer_class, customer_phone, customer_email,
            service, service_name, jumlah, ukuran, jenis_sablon, warna_kaos, ukuran_kertas,
            drive_link, catatan, harga_satuan, subtotal, unique_code, total,
            status, payment_status, assigned_to, created_at
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, NOW()
        )");
        
        $result = $stmt->execute([
            $orderNumber, $nama, $kelas, $telepon, $email,
            $layanan, $serviceName, $jumlah, $ukuran, $jenis_sablon, $warna_kaos, $ukuran_kertas,
            $drive_link ?: $file_path, $catatan, $hargaSatuan, $subtotal, $uniqueCode, $total,
            $status, 'belum_bayar', $assigned_to
        ]);
    } catch (Exception $e) {
        error_log("ERROR INSERT: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database: ' . $e->getMessage()]);
        exit;
    }
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database']);
        exit;
    }
    
    // Log aktivitas
    logActivity($pdo, null, $nama, 'create_order', 'Membuat pesanan baru: ' . $orderNumber . ' (' . $serviceName . ')');
    
    // Return success
    echo json_encode([
        'success' => true,
        'order' => [
            'orderNumber' => $orderNumber,
            'nama' => $nama,
            'kelas' => $kelas,
            'total' => $total,
            'uniqueCode' => $uniqueCode,
            'serviceName' => $serviceName,
            'jumlah' => $jumlah,
            'assigned_to' => $assigned_to
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pesanan: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>