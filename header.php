<?php
if (session_status() == PHP_SESSION_NONE) {
    include 'config.php';
}
include 'auth_check.php';

$unread_notifications = 0;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $result = $conn->query("SELECT COUNT(id_notif) as total FROM notifikasi WHERE status = 'belum dibaca'");
    $unread_notifications = $result->fetch_assoc()['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Motor - Sistem Manajemen</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>

<!-- LOGOUT JAVASCRIPT HANDLER -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const logoutLink = document.querySelector('.logout-link');
    
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Yakin ingin logout?')) {
                // Show loading
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging out...';
                this.style.pointerEvents = 'none';
                
                // Redirect ke logout.php
                fetch('logout.php', {
                    method: 'GET',
                    credentials: 'same-origin'
                })
                .then(() => {
                    window.location.href = 'login.php';
                })
                .catch(() => {
                    // Force redirect even if error
                    window.location.href = 'login.php';
                });
            }
        });
    }
});
</script>

<body>
<nav>
    <div class="nav-container">
        <a href="index.php" class="nav-brand">
            <i class="fas fa-motorcycle"></i> Rental Motor
        </a>
        <div class="nav-links">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="motors.php"><i class="fas fa-motorcycle"></i> Data Motor</a>
                <a href="customers.php"><i class="fas fa-users"></i> Data Penyewa</a>
                <a href="transactions.php">
                    <i class="fas fa-exchange-alt"></i> Transaksi
                    <?php if ($unread_notifications > 0): ?>
                    <span class="notif-badge"><?= $unread_notifications ?></span>
                    <?php endif; ?>
                </a>
                <a href="reports.php"><i class="fas fa-chart-line"></i> Laporan</a>
                <a href="users.php"><i class="fas fa-user-cog"></i> Akun</a>
            <?php else: ?>
                <a href="motors.php"><i class="fas fa-motorcycle"></i> Daftar Motor</a>
                <a href="customers.php"><i class="fas fa-user"></i> Profil</a>
            <?php endif; ?>
        </div>
<a href="logout.php" class="logout-link" style="pointer-events: auto !important;">
    <i class="fas fa-sign-out-alt"></i> 
    Logout (<?= htmlspecialchars($_SESSION['username'] ?? '') ?>)
</a>
    </div>
</nav>
<div class="container">
    <main>
        <?php
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            echo '<div class="flash-message ' . $message['type'] . '">' . htmlspecialchars($message['message']) . '</div>';
            unset($_SESSION['flash_message']);
        }
        ?>