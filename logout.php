<?php
/**
 * LOGOUT SCRIPT - FIXED VERSION
 * Masalah: Session tidak terhapus dengan benar
 * Solusi: Lengkap destroy session + clear cookies + force redirect
 */

// Start session jika belum
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Log activity sebelum logout (opsional)
if (isset($_SESSION['id_user'])) {
    // Uncomment jika punya function log_activity
    // require_once 'config.php';
    // log_activity($_SESSION['id_user'], 'LOGOUT', 'User logged out');
}

// Step 1: Unset semua variabel session
$_SESSION = array();

// Step 2: Hapus session cookie jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Step 3: Destroy session di server
session_destroy();

// Step 4: Clear any other cookies (opsional)
// Uncomment jika ada cookie lain yang perlu dihapus
/*
setcookie('remember_me', '', time() - 3600, '/');
setcookie('user_token', '', time() - 3600, '/');
*/

// Step 5: Disable browser cache untuk halaman ini
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

// Step 6: Force redirect ke login
header("Location: login.php");
exit();
?>