<?php
require_once "config.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $username = trim($_POST["username"]);
    $no_ktp = trim($_POST["no_ktp"]);
    $password_baru = trim($_POST["password_baru"]);
    $konfirmasi_password = trim($_POST["konfirmasi_password"]);
    
    if (empty($username) || empty($no_ktp)) {
        $message = "Username dan No. KTP harus diisi.";
        $message_type = "error";
    } elseif (empty($password_baru) || strlen($password_baru) < 6) {
        $message = "Password baru minimal 6 karakter.";
        $message_type = "error";
    } elseif ($password_baru !== $konfirmasi_password) {
        $message = "Konfirmasi password tidak cocok.";
        $message_type = "error";
    } else {
        // Cek apakah user dengan username tersebut ada dan cocok dengan no_ktp
        $stmt = $conn->prepare("
            SELECT u.id_user 
            FROM user_admin u
            LEFT JOIN penyewa p ON u.id_user = p.id_user_akun
            WHERE u.username = ? AND p.no_ktp = ?
        ");
        $stmt->bind_param("ss", $username, $no_ktp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $id_user = $user['id_user'];
            
            // Update password
            $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE user_admin SET password = ? WHERE id_user = ?");
            $stmt_update->bind_param("si", $hashed_password, $id_user);
            
            if ($stmt_update->execute()) {
                $message = "✅ Password berhasil direset! Silakan login dengan password baru.";
                $message_type = "success";
            } else {
                $message = "Terjadi kesalahan. Silakan coba lagi.";
                $message_type = "error";
            }
            $stmt_update->close();
        } else {
            $message = "Username atau No. KTP tidak cocok dengan data kami.";
            $message_type = "error";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Rental Motor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link href="login-style.css" rel="stylesheet">
</head>
<body class="register-page">
    <div class="wrapper">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i class="fas fa-key" style="font-size: 24px; color: white;"></i>
            </div>
            <h2 style="margin-bottom: 8px;">Lupa Password?</h2>
            <p style="color: var(--text-secondary); margin: 0;">Gunakan No. KTP Anda untuk verifikasi</p>
        </div>

        <?php if (!empty($message)): ?>
        <div class="flash-message <?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>No. KTP (Untuk Verifikasi)</label>
                <input type="text" name="no_ktp" class="form-control" required
                       placeholder="Masukkan No. KTP yang terdaftar">
                <small style="color: var(--text-secondary); display: block; margin-top: 4px;">
                    No. KTP yang Anda daftarkan saat registrasi
                </small>
            </div>

            <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border);">

            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="password_baru" class="form-control" required
                       minlength="6" placeholder="Minimal 6 karakter">
            </div>

            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="konfirmasi_password" class="form-control" required
                       minlength="6" placeholder="Ulangi password baru">
            </div>

            <div class="form-group">
                <button type="submit" name="reset_password" class="btn-primary">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </div>

            <div class="login-link">
                Ingat password Anda? <a href="login.php">Login di sini</a>
            </div>
        </form>
    </div>
</body>
</html>