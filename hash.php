<?php
// ==================== GENERATE HASH PASSWORD ====================
// File: hash.php - FIX VERSION

$password = 'admin123!';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "====================================\n";
echo "GENERATE HASH PASSWORD\n";
echo "====================================\n\n";
echo "Password : " . $password . "\n";
echo "Hash     : " . $hash . "\n\n";
echo "Copy hash ini untuk dimasukkan ke database:\n";
echo $hash . "\n\n";

// Test verifikasi
if (password_verify($password, $hash)) {
    echo "✅ Verifikasi berhasil: Password cocok dengan hash\n";
} else {
    echo "❌ Verifikasi gagal\n";
}

echo "\n====================================\n";
?>