<?php
// ==================== TEST PASSWORD ====================
// File: test_password.php

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>TEST PASSWORD</h1>";

// Generate password
$password = generateRandomPassword(12);
echo "<p>Password yang di-generate: <strong>$password</strong></p>";

// Cek fungsi email
echo "<p>Mencoba kirim email...</p>";

$result = sendEmailViaEmailJS('nmurtadho1905@gmail.com', 'Test User', 'testuser', $password);

echo "<pre>";
print_r($result);
echo "</pre>";

// Cek error log
echo "<p>Cek file error_log di C:\\xampp\\php\\logs\\php_error_log</p>";
?>