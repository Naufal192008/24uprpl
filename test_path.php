<?php
// ==================== TEST PATH ====================
// File: test_path.php

echo "<h1>Test Path PHPMailer</h1>";

$paths = [
    'current_dir' => __DIR__,
    'phpmailer_path1' => __DIR__ . '/PHPMailer/src/Exception.php',
    'phpmailer_path2' => __DIR__ . '/includes/../PHPMailer/src/Exception.php',
];

foreach ($paths as $name => $path) {
    echo "<p><strong>$name:</strong> $path</p>";
    if (file_exists($path)) {
        echo "<p style='color:green'>✅ FILE DITEMUKAN!</p>";
    } else {
        echo "<p style='color:red'>❌ FILE TIDAK DITEMUKAN!</p>";
    }
    echo "<hr>";
}

// Tampilkan struktur folder
echo "<h2>Struktur Folder:</h2>";
echo "<pre>";
system("dir " . __DIR__ . " /b");
echo "</pre>";
?>