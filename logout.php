<?php
// ==================== LOGOUT ====================
// File: logout.php

// Cek session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua session
$_SESSION = array();

// Hapus cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Redirect ke halaman index
header('Location: index.php');
exit;
?>