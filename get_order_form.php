<?php
// ==================== GET ORDER FORM ====================
// File: get_order_form.php - VERSI FULL DENGAN 2 OPSI UPLOAD

require_once 'config/database.php';
require_once 'includes/functions.php';

$layanan = $_POST['layanan'] ?? '';
$jenis = $_POST['jenis'] ?? '';

// Data layanan
$layananData = [
    'print_hitam' => [
        'nama' => 'Print Hitam Putih',
        'harga' => 1000,
        'satuan' => 'lembar',
        'deskripsi' => 'Print dokumen hitam putih, hasil tajam & jelas',
        'icon' => 'fa-print',
        'color' => '#3498db',
        'bg_color' => 'rgba(52,152,219,0.1)'
    ],
    'print_warna' => [
        'nama' => 'Print Full Color',
        'harga' => 2000,
        'satuan' => 'lembar',
        'deskripsi' => 'Print dokumen berwarna, kualitas photo',
        'icon' => 'fa-palette',
        'color' => '#9b59b6',
        'bg_color' => 'rgba(155,89,182,0.1)'
    ],
    'fotocopy' => [
        'nama' => 'Fotocopy',
        'harga' => 250,
        'satuan' => 'lembar',
        'deskripsi' => 'Fotocopy dokumen, bisa bolak-balik',
        'icon' => 'fa-copy',
        'color' => '#e67e22',
        'bg_color' => 'rgba(230,126,34,0.1)'
    ],
    'kaos_sablon' => [
        'nama' => 'Kaos & Sablon',
        'harga_kaos' => 50000,
        'harga_sablon' => 55000,
        'harga_paket' => 105000,
        'satuan_kaos' => 'pcs',
        'satuan_sablon' => 'sablon',
        'satuan_paket' => 'paket',
        'deskripsi' => 'Kaos katun combed 30s + sablon custom',
        'icon' => 'fa-tshirt',
        'color' => '#e74c3c',
        'bg_color' => 'rgba(231,76,60,0.1)'
    ]
];

$defaultJenis = $jenis ?: 'paket';
$defaultWarna = '#000000';
$defaultNamaWarna = 'Hitam';
?>
<style>
    /* ==================== RESET & VARIABLES ==================== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .order-form-container {
        max-width: 100%;
        margin: 0 auto;
    }

    /* ==================== SERVICE HEADER ==================== */
    .service-header {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 25px;
        border-radius: 16px;
        margin-bottom: 25px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }

    .service-header::before {
        content: '';
        position: absolute;
        top: -30px;
        right: -30px;
        width: 150px;
        height: 150px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }

    .service-header::after {
        content: '';
        position: absolute;
        bottom: -30px;
        left: -30px;
        width: 150px;
        height: 150px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }

    .service-header-content {
        position: relative;
        z-index: 10;
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .service-icon {
        width: 70px;
        height: 70px;
        background: rgba(255,255,255,0.2);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        backdrop-filter: blur(5px);
        border: 2px solid rgba(255,255,255,0.3);
    }

    .service-title {
        flex: 1;
    }

    .service-title h3 {
        font-size: 24px;
        font-weight: 800;
        margin: 0 0 5px 0;
    }

    .service-title p {
        margin: 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .price-tag {
        background: rgba(255,255,255,0.2);
        padding: 12px 20px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 18px;
        border: 2px solid rgba(255,255,255,0.3);
        backdrop-filter: blur(5px);
    }

    /* ==================== FORM SECTION ==================== */
    .form-section {
        background: #f8fafc;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 25px;
        border: 1px solid #e9ecef;
    }

    .form-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e9ecef;
    }

    .form-section-title i {
        width: 35px;
        height: 35px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .form-section-title h4 {
        margin: 0;
        font-weight: 700;
        color: #2c3e50;
        font-size: 18px;
    }

    /* ==================== PAKET CARDS ==================== */
    .paket-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin: 15px 0;
    }

    .paket-card {
        flex: 1;
        min-width: 180px;
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .paket-card:hover {
        transform: translateY(-5px);
        border-color: #667eea;
        box-shadow: 0 8px 15px rgba(102,126,234,0.2);
    }

    .paket-card.selected {
        border-color: #667eea;
        background: linear-gradient(135deg, rgba(102,126,234,0.05), rgba(118,75,162,0.05));
        box-shadow: 0 8px 15px rgba(102,126,234,0.3);
    }

    .paket-icon {
        font-size: 40px;
        color: #667eea;
        margin-bottom: 15px;
    }

    .paket-card.selected .paket-icon {
        color: #764ba2;
    }

    .paket-title {
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 8px;
        font-size: 18px;
    }

    .paket-price {
        font-size: 20px;
        font-weight: 800;
        color: #667eea;
        margin-bottom: 8px;
    }

    .paket-desc {
        font-size: 13px;
        color: #7f8c8d;
    }

    /* ==================== UKURAN CARD ==================== */
    .ukuran-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 10px 0;
    }

    .ukuran-card {
        width: 60px;
        height: 60px;
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .ukuran-card:hover {
        border-color: #667eea;
        transform: scale(1.05);
    }

    .ukuran-card.selected {
        border-color: #667eea;
        background: #667eea;
        color: white;
    }

    /* ==================== SABLON CARD ==================== */
    .sablon-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 10px 0;
    }

    .sablon-card {
        flex: 1;
        min-width: 120px;
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }

    .sablon-card:hover {
        border-color: #667eea;
        transform: translateY(-3px);
    }

    .sablon-card.selected {
        border-color: #667eea;
        background: #667eea;
        color: white;
    }

    .sablon-card.selected .sablon-price {
        color: white;
    }

    .sablon-name {
        font-weight: 700;
        margin-bottom: 5px;
    }

    .sablon-price {
        font-size: 12px;
        color: #667eea;
        font-weight: 600;
    }

    /* ==================== COLOR PICKER ==================== */
    .color-picker-grid {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin: 15px 0;
    }

    .color-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: flex-start;
    }

    .color-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 70px;
        cursor: pointer;
    }

    .color-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        margin-bottom: 5px;
        transition: all 0.3s ease;
        border: 3px solid transparent;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        cursor: pointer;
    }

    .color-circle:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    .color-circle.selected {
        border-color: #667eea;
        transform: scale(1.1);
        box-shadow: 0 4px 15px rgba(102,126,234,0.4);
    }

    .color-name {
        font-size: 12px;
        color: #2c3e50;
        font-weight: 500;
        cursor: pointer;
    }

    .selected-color-preview {
        margin-top: 15px;
        padding: 12px 15px;
        background: #f8f9fa;
        border-radius: 10px;
        border-left: 4px solid #667eea;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
    }

    .color-dot {
        width: 25px;
        height: 25px;
        border-radius: 50%;
        display: inline-block;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    /* ==================== UPLOAD METHOD SELECTOR ==================== */
    .upload-method-selector {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        padding: 8px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #667eea;
    }

    .method-option {
        display: flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
        padding: 5px 12px;
        background: white;
        border-radius: 20px;
        border: 1px solid #ddd;
        transition: all 0.3s;
        font-size: 13px;
    }

    .method-option:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .method-option input[type="radio"] {
        margin: 0;
        accent-color: #667eea;
    }

    /* ==================== UPLOAD AREA ==================== */
    .upload-section {
        margin-top: 5px;
    }

    .file-upload-area {
        border: 2px dashed #667eea;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        cursor: pointer;
        background: #f8f9fa;
        transition: all 0.3s;
    }

    .file-upload-area:hover {
        background: #e8f4fc;
        border-color: #764ba2;
    }

    .file-upload-area i {
        font-size: 20px;
        color: #667eea;
        margin-right: 8px;
    }

    .file-upload-area span {
        color: #2c3e50;
        font-size: 13px;
    }

    /* ==================== TOTAL SECTION ==================== */
    .total-section {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        border-radius: 20px;
        padding: 25px;
        color: white;
        margin: 25px 0;
        position: relative;
        overflow: hidden;
        text-align: center;
    }

    .total-section::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }

    .total-section::after {
        content: '';
        position: absolute;
        bottom: -50px;
        left: -50px;
        width: 200px;
        height: 200px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }

    .total-content {
        position: relative;
        z-index: 10;
    }

    .total-label {
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 2px;
        opacity: 0.8;
        margin-bottom: 10px;
    }

    .total-amount {
        font-size: 48px;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #fff, #e0e0e0);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .total-unit {
        font-size: 16px;
        opacity: 0.7;
    }

    /* ==================== INFO BOX ==================== */
    .info-box {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 12px;
        padding: 15px 20px;
        margin: 20px 0;
        color: #856404;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .info-box i {
        font-size: 24px;
    }

    /* ==================== SUBMIT BUTTON ==================== */
    .btn-submit {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #00b09b, #96c93d);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 10px 20px rgba(0,176,155,0.3);
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(0,176,155,0.4);
    }

    .btn-submit:active {
        transform: translateY(0);
    }

    .btn-submit i {
        font-size: 18px;
    }

    /* ==================== ROW & COLUMN ==================== */
    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }

    .col-md-6 {
        width: 50%;
        padding: 0 10px;
    }

    .col-md-12 {
        width: 100%;
        padding: 0 10px;
    }

    .mb-3 {
        margin-bottom: 15px;
    }

    .mb-4 {
        margin-bottom: 20px;
    }

    .text-center {
        text-align: center;
    }

    .text-danger {
        color: #ef4444;
    }

    .fw-bold {
        font-weight: 700;
    }

    .help-text {
        display: block;
        color: #7f8c8d;
        font-size: 12px;
        margin-top: 5px;
    }

    /* ==================== SERVICE CARD ==================== */
    .service-card {
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .service-card:hover {
        transform: translateY(-5px);
        border-color: #667eea !important;
        box-shadow: 0 10px 20px rgba(102,126,234,0.2);
    }

    /* ==================== RESPONSIVE ==================== */
    @media (max-width: 768px) {
        .service-header-content {
            flex-direction: column;
            text-align: center;
        }
        
        .col-md-6 {
            width: 100%;
        }
        
        .paket-container {
            flex-direction: column;
        }
        
        .paket-card {
            width: 100%;
        }
        
        .sablon-container {
            flex-direction: column;
        }
        
        .sablon-card {
            width: 100%;
        }
        
        .total-amount {
            font-size: 36px;
        }
        
        .color-item {
            width: 50px;
        }
        
        .color-circle {
            width: 40px;
            height: 40px;
        }
        
        .upload-method-selector {
            flex-direction: column;
            gap: 8px;
        }
    }
</style>

<?php if ($layanan && isset($layananData[$layanan])): 
    $data = $layananData[$layanan];
?>

<div class="order-form-container">
    <!-- SERVICE HEADER -->
    <div class="service-header">
        <div class="service-header-content">
            <div class="service-icon">
                <i class="fas <?php echo $data['icon'] ?? 'fa-box-seam'; ?>"></i>
            </div>
            <div class="service-title">
                <h3><?php echo $data['nama']; ?></h3>
                <p><?php echo $data['deskripsi'] ?? ''; ?></p>
            </div>
            <?php if ($layanan !== 'kaos_sablon'): ?>
                <div class="price-tag">
                    Rp <?php echo number_format($data['harga'], 0, ',', '.'); ?> /<?php echo $data['satuan']; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <form id="orderForm" onsubmit="submitOrder(event, '<?php echo $layanan; ?>')">
        <input type="hidden" id="jenis_layanan" name="jenis_layanan" value="<?php echo $layanan . ($jenis ? '_' . $jenis : ''); ?>">
        <input type="hidden" id="hidden_layanan" value="<?php echo $layanan; ?>">

        <!-- DATA PEMESAN SECTION -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-user"></i>
                <h4>Data Pemesan</h4>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="nama" name="nama" placeholder="Masukkan nama lengkap" required>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-graduation-cap"></i> Kelas <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="kelas" name="kelas" placeholder="Contoh: XII RPL 1" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fab fa-whatsapp"></i> No. WhatsApp <span class="text-danger">*</span>
                        </label>
                        <input type="tel" class="form-control" id="telepon" name="telepon" placeholder="08xxxxxxxxxx" required>
                        <span class="help-text">Aktif WhatsApp</span>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i> Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="contoh@email.com" required>
                        <span class="help-text">Untuk konfirmasi order</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- DETAIL LAYANAN SECTION -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-cog"></i>
                <h4>Detail Layanan</h4>
            </div>

            <?php if ($layanan === 'kaos_sablon'): ?>
                <!-- ==================== PILIH PAKET ==================== -->
                <div class="form-group mb-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-tags"></i> PILIH PAKET <span class="text-danger">*</span>
                    </label>
                    
                    <div class="paket-container">
                        <!-- Kaos Saja -->
                        <div class="paket-card <?php echo $defaultJenis === 'kaos' ? 'selected' : ''; ?>" onclick="selectPaket('kaos', this)">
                            <div class="paket-icon">
                                <i class="fas fa-tshirt"></i>
                            </div>
                            <div class="paket-title">Kaos Saja</div>
                            <div class="paket-price">Rp 50.000</div>
                            <div class="paket-desc">Kaos polos combed 30s</div>
                            <input type="radio" name="jenis_produk" id="paket_kaos" value="kaos" style="display: none;" <?php echo $defaultJenis === 'kaos' ? 'checked' : ''; ?>>
                        </div>
                        
                        <!-- Sablon Saja -->
                        <div class="paket-card <?php echo $defaultJenis === 'sablon' ? 'selected' : ''; ?>" onclick="selectPaket('sablon', this)">
                            <div class="paket-icon">
                                <i class="fas fa-paint-brush"></i>
                            </div>
                            <div class="paket-title">Sablon Saja</div>
                            <div class="paket-price">Rp 55.000</div>
                            <div class="paket-desc">Jasa sablon 1 warna</div>
                            <input type="radio" name="jenis_produk" id="paket_sablon" value="sablon" style="display: none;" <?php echo $defaultJenis === 'sablon' ? 'checked' : ''; ?>>
                        </div>
                        
                        <!-- Paket Lengkap -->
                        <div class="paket-card <?php echo $defaultJenis === 'paket' ? 'selected' : ''; ?>" onclick="selectPaket('paket', this)">
                            <div class="paket-icon">
                                <i class="fas fa-gift"></i>
                            </div>
                            <div class="paket-title">Paket Lengkap</div>
                            <div class="paket-price">Rp 105.000</div>
                            <div class="paket-desc">Kaos + Sablon 1 warna</div>
                            <input type="radio" name="jenis_produk" id="paket_paket" value="paket" style="display: none;" <?php echo $defaultJenis === 'paket' ? 'checked' : ''; ?>>
                        </div>
                    </div>
                </div>

                <!-- ==================== UKURAN KAOS ==================== -->
                <div class="form-group mb-4" id="ukuranGroup">
                    <label class="form-label fw-bold">
                        <i class="fas fa-ruler"></i> UKURAN KAOS <span class="text-danger">*</span>
                    </label>
                    
                    <div class="ukuran-container">
                        <div class="ukuran-card <?php echo $defaultUkuran === 'S' ? 'selected' : ''; ?>" onclick="pilihUkuran('S', this)">S</div>
                        <div class="ukuran-card <?php echo $defaultUkuran === 'M' ? 'selected' : ''; ?>" onclick="pilihUkuran('M', this)">M</div>
                        <div class="ukuran-card <?php echo $defaultUkuran === 'L' ? 'selected' : ''; ?>" onclick="pilihUkuran('L', this)">L</div>
                        <div class="ukuran-card <?php echo $defaultUkuran === 'XL' ? 'selected' : ''; ?>" onclick="pilihUkuran('XL', this)">XL</div>
                        <div class="ukuran-card <?php echo $defaultUkuran === 'XXL' ? 'selected' : ''; ?>" onclick="pilihUkuran('XXL', this)">XXL</div>
                    </div>
                    <input type="hidden" name="ukuran" id="ukuran" value="<?php echo $defaultUkuran; ?>">
                </div>

                <!-- ==================== JENIS SABLON ==================== -->
                <div class="form-group mb-4" id="sablonGroup">
                    <label class="form-label fw-bold">
                        <i class="fas fa-paint-brush"></i> JENIS SABLON <span class="text-danger">*</span>
                    </label>
                    
                    <div class="sablon-container">
                        <div class="sablon-card <?php echo $defaultSablon === '1 Warna' ? 'selected' : ''; ?>" onclick="pilihSablon('1 Warna', 55000, this)">
                            <div class="sablon-name">1 Warna</div>
                            <div class="sablon-price">Rp 55.000</div>
                        </div>
                        <div class="sablon-card <?php echo $defaultSablon === '2 Warna' ? 'selected' : ''; ?>" onclick="pilihSablon('2 Warna', 75000, this)">
                            <div class="sablon-name">2 Warna</div>
                            <div class="sablon-price">Rp 75.000</div>
                        </div>
                        <div class="sablon-card <?php echo $defaultSablon === 'Full Color' ? 'selected' : ''; ?>" onclick="pilihSablon('Full Color', 95000, this)">
                            <div class="sablon-name">Full Color</div>
                            <div class="sablon-price">Rp 95.000</div>
                        </div>
                        <div class="sablon-card <?php echo $defaultSablon === 'DTF' ? 'selected' : ''; ?>" onclick="pilihSablon('DTF', 85000, this)">
                            <div class="sablon-name">DTF</div>
                            <div class="sablon-price">Rp 85.000</div>
                        </div>
                    </div>
                    <input type="hidden" name="jenis_sablon" id="jenis_sablon" value="<?php echo $defaultSablon; ?>">
                    <input type="hidden" name="harga_sablon" id="harga_sablon" value="55000">
                </div>

                <!-- ==================== WARNA KAOS ==================== -->
                <div class="form-group mb-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-palette"></i> WARNA KAOS <span class="text-danger">*</span>
                    </label>
                    
                    <div class="color-picker-grid">
                        <div class="color-row">
                            <!-- Baris 1 -->
                            <div class="color-item" onclick="pilihWarna('#000000', 'Hitam', this)">
                                <div class="color-circle <?php echo $defaultWarna === '#000000' ? 'selected' : ''; ?>" style="background: #000000;"></div>
                                <span class="color-name">Hitam</span>
                            </div>
                            <div class="color-item" onclick="pilihWarna('#FFFFFF', 'Putih', this)">
                                <div class="color-circle <?php echo $defaultWarna === '#FFFFFF' ? 'selected' : ''; ?>" style="background: #FFFFFF; border: 2px solid #ddd;"></div>
                                <span class="color-name">Putih</span>
                            </div>
                            <div class="color-item" onclick="pilihWarna('#FF0000', 'Merah', this)">
                                <div class="color-circle <?php echo $defaultWarna === '#FF0000' ? 'selected' : ''; ?>" style="background: #FF0000;"></div>
                                <span class="color-name">Merah</span>
                            </div>
                            <div class="color-item" onclick="pilihWarna('#0000FF', 'Biru', this)">
                                <div class="color-circle <?php echo $defaultWarna === '#0000FF' ? 'selected' : ''; ?>" style="background: #0000FF;"></div>
                                <span class="color-name">Biru</span>
                            </div>
                            <div class="color-item" onclick="pilihWarna('#00FF00', 'Hijau', this)">
                                <div class="color-circle <?php echo $defaultWarna === '#00FF00' ? 'selected' : ''; ?>" style="background: #00FF00;"></div>
                                <span class="color-name">Hijau</span>
                            </div>
                        </div>
                        <div class="color-row">
                            <!-- Baris 2 -->
                            <div class="color-item" onclick="pilihWarna('#FFFF00', 'Kuning', this)">
                                <div class="color-circle <?php echo $defaultWarna === '#FFFF00' ? 'selected' : ''; ?>" style="background: #FFFF00;"></div>
                                <span class="color-name">Kuning</span>
                            </div>
                            <div class="color-item" onclick="pilihWarna('#800080', 'Ungu', this)">
                                <div class="color-circle <?php echo $defaultWarna === '#800080' ? 'selected' : ''; ?>" style="background: #800080;"></div>
                                <span class="color-name">Ungu</span>
                            </div>
                            <div class="color-item" onclick="pilihWarna('#FFA500', 'Orange', this)">
                                <div class="color-circle <?php echo $defaultWarna === '#FFA500' ? 'selected' : ''; ?>" style="background: #FFA500;"></div>
                                <span class="color-name">Orange</span>
                            </div>
                            <div class="color-item" onclick="pilihWarna('#808080', 'Abu-abu', this)">
                                <div class="color-circle <?php echo $defaultWarna === '#808080' ? 'selected' : ''; ?>" style="background: #808080;"></div>
                                <span class="color-name">Abu-abu</span>
                            </div>
                            <div class="color-item" onclick="pilihWarna('#8B4513', 'Coklat', this)">
                                <div class="color-circle <?php echo $defaultWarna === '#8B4513' ? 'selected' : ''; ?>" style="background: #8B4513;"></div>
                                <span class="color-name">Coklat</span>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="warna_kaos" id="warna_kaos" value="<?php echo $defaultWarna; ?>">
                    <input type="hidden" name="warna_kaos_nama" id="warna_kaos_nama" value="<?php echo $defaultNamaWarna; ?>">
                    
                    <div class="selected-color-preview" id="selectedColorPreview">
                        <i class="fas fa-palette" style="color: #667eea;"></i>
                        Warna terpilih: <strong><span id="selectedColorName"><?php echo $defaultNamaWarna; ?></span></strong>
                        <span class="color-dot" id="selectedColorDot" style="background: <?php echo $defaultWarna; ?>;"></span>
                    </div>
                </div>

            <?php else: ?>
                <!-- UNTUK LAYANAN PRINTING -->
                <div class="form-group">
                    <label class="form-label fw-bold">
                        <i class="fas fa-file"></i> Ukuran Kertas <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="ukuran_kertas" name="ukuran_kertas" required>
                        <option value="">Pilih Ukuran Kertas</option>
                        <option value="A4">A4 (21.0 x 29.7 cm)</option>
                        <option value="F4">F4 (21.5 x 33.0 cm)</option>
                        <option value="Legal">Legal (21.6 x 35.6 cm)</option>
                        <option value="A3">A3 (29.7 x 42.0 cm)</option>
                    </select>
                </div>
            <?php endif; ?>

            <!-- JUMLAH DAN METODE UPLOAD -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label class="form-label fw-bold">
                            <i class="fas fa-sort-numeric-up"></i> Jumlah <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="jumlah" name="jumlah" min="1" value="1" required oninput="updateTotal()">
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <!-- PILIH METODE UPLOAD -->
                    <div class="upload-method-selector">
                        <label class="method-option">
                            <input type="radio" name="upload_method" value="drive" checked onchange="toggleUploadMethod()">
                            <span>🔗 Link Google Drive</span>
                        </label>
                        <label class="method-option">
                            <input type="radio" name="upload_method" value="file" onchange="toggleUploadMethod()">
                            <span>📄 Upload PDF</span>
                        </label>
                    </div>

                    <!-- LINK DRIVE (DEFAULT) -->
                    <div id="drive-upload" class="upload-section">
                        <div class="form-group">
                            <label class="form-label fw-bold">
                                <i class="fab fa-google-drive"></i> Link Google Drive <span class="text-danger">*</span>
                            </label>
                            <input type="url" class="form-control" id="link_drive" name="link_drive" placeholder="https://drive.google.com/...">
                            <span class="help-text">Upload file ke Drive, share link di sini</span>
                        </div>
                    </div>

                    <!-- UPLOAD FILE PDF (SEMBUNYI) -->
                    <div id="file-upload" class="upload-section" style="display: none;">
                        <div class="form-group">
                            <label class="form-label fw-bold">
                                <i class="fas fa-file-pdf"></i> Upload File PDF <span class="text-danger">*</span>
                            </label>
                            <div class="file-upload-area" onclick="document.getElementById('file_input').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span id="file_name_display">Klik untuk pilih file PDF</span>
                            </div>
                            <input type="file" id="file_input" name="file_upload" accept=".pdf" style="display: none;" onchange="updateFileName(this)">
                            <span class="help-text">Maksimal 5MB, format PDF</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CATATAN -->
            <div class="form-group">
                <label class="form-label fw-bold">
                    <i class="fas fa-sticky-note"></i> Catatan Tambahan
                </label>
                <textarea class="form-control" id="catatan" name="catatan" rows="3" placeholder="Tulis catatan khusus di sini..."></textarea>
            </div>
        </div>

        <!-- TOTAL SECTION -->
        <div class="total-section">
            <div class="total-content">
                <div class="total-label">TOTAL PEMBAYARAN</div>
                <div class="total-amount" id="totalDisplay">
                    Rp <?php 
                        if ($layanan === 'kaos_sablon') {
                            echo number_format($data['harga_' . $defaultJenis], 0, ',', '.');
                        } else {
                            echo number_format($data['harga'], 0, ',', '.');
                        }
                    ?>
                </div>
                <div class="total-unit">(x <span id="jumlahDisplay">1</span> unit)</div>
            </div>
        </div>

        <!-- INFO BOX -->
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Informasi:</strong> Setelah mengirim pesanan, Anda akan diarahkan ke halaman pembayaran. 
                Pembayaran dapat dilakukan via DANA, Bank Transfer, atau E-Wallet lainnya.
            </div>
        </div>

        <!-- SUBMIT BUTTON -->
        <button type="submit" class="btn-submit">
            <i class="fas fa-paper-plane"></i>
            KIRIM PESANAN SEKARANG
        </button>
    </form>
</div>

<script>
    // ==================== FUNGSI UNTUK PILIH PAKET ====================
   // ==================== FUNGSI UNTUK PILIH PAKET ====================
window.selectPaket = function(jenis, element) {
    console.log('Memilih paket:', jenis); // Untuk debugging
    
    const cards = document.querySelectorAll('.paket-card');
    cards.forEach(card => card.classList.remove('selected'));
    element.classList.add('selected');
    
    const radio = document.getElementById('paket_' + jenis);
    if (radio) radio.checked = true;
    
    const layanan = document.getElementById('hidden_layanan')?.value || '<?php echo $layanan; ?>';
    const jenisLayanan = document.getElementById('jenis_layanan');
    if (jenisLayanan) {
        // Format: kaos_sablon_kaos, kaos_sablon_sablon, kaos_sablon_paket
        jenisLayanan.value = layanan + '_' + jenis;
        console.log('Jenis layanan diubah ke:', jenisLayanan.value);
    }
    
    const ukuranGroup = document.getElementById('ukuranGroup');
    const sablonGroup = document.getElementById('sablonGroup');
    
    if (ukuranGroup && sablonGroup) {
        if (jenis === 'sablon') {
            ukuranGroup.style.display = 'none';
            sablonGroup.style.display = 'block';
        } else if (jenis === 'kaos') {
            ukuranGroup.style.display = 'block';
            sablonGroup.style.display = 'none';
        } else {
            ukuranGroup.style.display = 'block';
            sablonGroup.style.display = 'block';
        }
    }
    
    if (typeof window.updateTotal === 'function') window.updateTotal();
};

    // ==================== FUNGSI UNTUK PILIH UKURAN ====================
    window.pilihUkuran = function(ukuran, element) {
        const cards = document.querySelectorAll('.ukuran-card');
        cards.forEach(card => card.classList.remove('selected'));
        element.classList.add('selected');
        
        const ukuranInput = document.getElementById('ukuran');
        if (ukuranInput) ukuranInput.value = ukuran;
    };

    // ==================== FUNGSI UNTUK PILIH SABLON ====================
    window.pilihSablon = function(jenis, harga, element) {
        const cards = document.querySelectorAll('.sablon-card');
        cards.forEach(card => card.classList.remove('selected'));
        element.classList.add('selected');
        
        const sablonInput = document.getElementById('jenis_sablon');
        const hargaInput = document.getElementById('harga_sablon');
        if (sablonInput) sablonInput.value = jenis;
        if (hargaInput) hargaInput.value = harga;
        
        if (typeof window.updateTotal === 'function') window.updateTotal();
    };

    // ==================== FUNGSI UNTUK PILIH WARNA ====================
    window.pilihWarna = function(kodeWarna, namaWarna, element) {
        const circles = document.querySelectorAll('.color-circle');
        circles.forEach(circle => circle.classList.remove('selected'));
        
        const circle = element.querySelector('.color-circle');
        if (circle) circle.classList.add('selected');
        
        const warnaInput = document.getElementById('warna_kaos');
        const warnaNamaInput = document.getElementById('warna_kaos_nama');
        const colorNameSpan = document.getElementById('selectedColorName');
        const colorDot = document.getElementById('selectedColorDot');
        
        if (warnaInput) warnaInput.value = kodeWarna;
        if (warnaNamaInput) warnaNamaInput.value = namaWarna;
        if (colorNameSpan) colorNameSpan.textContent = namaWarna;
        if (colorDot) colorDot.style.backgroundColor = kodeWarna;
    };

    // ==================== FUNGSI UPDATE TOTAL ====================
    window.updateTotal = function() {
        const jumlahInput = document.getElementById('jumlah');
        if (!jumlahInput) return;
        
        const jumlah = parseInt(jumlahInput.value) || 1;
        const jumlahDisplay = document.getElementById('jumlahDisplay');
        if (jumlahDisplay) jumlahDisplay.textContent = jumlah;
        
        let harga = 0;
        const paketKaos = document.getElementById('paket_kaos');
        
        if (paketKaos) {
            let jenis = 'paket';
            if (document.getElementById('paket_kaos')?.checked) jenis = 'kaos';
            else if (document.getElementById('paket_sablon')?.checked) jenis = 'sablon';
            else if (document.getElementById('paket_paket')?.checked) jenis = 'paket';
            
            const hargaSablonInput = document.getElementById('harga_sablon');
            const hargaSablon = hargaSablonInput ? parseInt(hargaSablonInput.value) || 55000 : 55000;
            
            if (jenis === 'kaos') harga = 50000;
            else if (jenis === 'sablon') harga = hargaSablon;
            else harga = 50000 + hargaSablon;
        } else {
            const hargaText = document.querySelector('.price-tag')?.textContent || '';
            const match = hargaText.match(/Rp\s+([0-9.]+)/);
            if (match) harga = parseInt(match[1].replace(/\./g, '')) || 0;
        }
        
        const total = harga * jumlah;
        const totalDisplay = document.getElementById('totalDisplay');
        if (totalDisplay) totalDisplay.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    // ==================== FUNGSI TOGGLE UPLOAD METHOD ====================
    function toggleUploadMethod() {
        const method = document.querySelector('input[name="upload_method"]:checked').value;
        const driveSection = document.getElementById('drive-upload');
        const fileSection = document.getElementById('file-upload');
        const linkDrive = document.getElementById('link_drive');
        const fileInput = document.getElementById('file_input');
        
        if (method === 'drive') {
            driveSection.style.display = 'block';
            fileSection.style.display = 'none';
            linkDrive.required = true;
            fileInput.required = false;
        } else {
            driveSection.style.display = 'none';
            fileSection.style.display = 'block';
            linkDrive.required = false;
            fileInput.required = true;
        }
    }

    // ==================== FUNGSI UPDATE NAMA FILE ====================
    function updateFileName(input) {
        const fileName = input.files[0]?.name || 'Klik untuk pilih file PDF';
        document.getElementById('file_name_display').textContent = fileName;
        
        // Validasi ukuran file (5MB)
        if (input.files[0] && input.files[0].size > 5 * 1024 * 1024) {
            alert('❌ File terlalu besar! Maksimal 5MB.');
            input.value = '';
            document.getElementById('file_name_display').textContent = 'Klik untuk pilih file PDF';
        }
    }

    // ==================== INIT ====================
    document.addEventListener('DOMContentLoaded', function() {
        updateTotal();
    });
</script>

<?php else: ?>

<!-- ==================== PILIHAN LAYANAN ==================== -->
<div style="text-align: center; padding: 30px 20px;">
    <h3 style="color: #2c3e50; margin-bottom: 30px;">Pilih Layanan yang Diinginkan</h3>
    
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; max-width: 500px; margin: 0 auto;">
        <div class="service-card" onclick="window.parent.loadOrderForm ? window.parent.loadOrderForm('print_hitam') : loadOrderForm('print_hitam')" style="padding: 20px; background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 10px; cursor: pointer; text-align: center;">
            <i class="fas fa-print" style="font-size: 2rem; color: #3498db; margin-bottom: 10px; display: block;"></i>
            <strong>Print Hitam Putih</strong>
            <div style="color: #3498db; margin-top: 5px;">Rp 1.000</div>
        </div>
        
        <div class="service-card" onclick="window.parent.loadOrderForm ? window.parent.loadOrderForm('print_warna') : loadOrderForm('print_warna')" style="padding: 20px; background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 10px; cursor: pointer; text-align: center;">
            <i class="fas fa-palette" style="font-size: 2rem; color: #9b59b6; margin-bottom: 10px; display: block;"></i>
            <strong>Print Full Color</strong>
            <div style="color: #9b59b6; margin-top: 5px;">Rp 2.000</div>
        </div>
        
        <div class="service-card" onclick="window.parent.loadOrderForm ? window.parent.loadOrderForm('fotocopy') : loadOrderForm('fotocopy')" style="padding: 20px; background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 10px; cursor: pointer; text-align: center;">
            <i class="fas fa-copy" style="font-size: 2rem; color: #e67e22; margin-bottom: 10px; display: block;"></i>
            <strong>Fotocopy</strong>
            <div style="color: #e67e22; margin-top: 5px;">Rp 250</div>
        </div>
        
        <div class="service-card" onclick="window.parent.loadOrderForm ? window.parent.loadOrderForm('kaos_sablon', 'paket') : loadOrderForm('kaos_sablon', 'paket')" style="padding: 20px; background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 10px; cursor: pointer; text-align: center;">
            <i class="fas fa-tshirt" style="font-size: 2rem; color: #e74c3c; margin-bottom: 10px; display: block;"></i>
            <strong>Kaos & Sablon</strong>
            <div style="color: #e74c3c; margin-top: 5px;">Rp 50K - 105K</div>
        </div>
    </div>
</div>

<?php endif; ?>