<?php
/**
 * AUTH CHECK - ENHANCED VERSION
 * Cek apakah user sudah login dengan benar
 */

// Pastikan session sudah dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Destroy any remaining session data
    session_unset();
    session_destroy();
    
    // Redirect ke login
    header("Location: login.php");
    exit();
}

// Extra validation: Cek apakah session masih valid
// Regenerate session ID jika sudah lebih dari 30 menit
if (isset($_SESSION['created'])) {
    if (time() - $_SESSION['created'] > 1800) { // 30 menit
        // Session terlalu lama, logout paksa
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
} else {
    $_SESSION['created'] = time();
}

// Update last activity
$_SESSION['last_activity'] = time();

// Validasi data session yang penting
$required_session_vars = ['id_user', 'username', 'nama_lengkap', 'role'];
foreach ($required_session_vars as $var) {
    if (!isset($_SESSION[$var])) {
        // Session corrupt, logout paksa
        session_unset();
        session_destroy();
        header("Location: login.php?error=session_corrupt");
        exit();
    }
}
?>