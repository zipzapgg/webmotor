<?php
include 'config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    redirect('login.php');
}

$password_lama_err = $password_baru_err = $konfirmasi_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_user = $_SESSION['id_user'];

    if (empty(trim($_POST['password_lama']))) {
        $password_lama_err = "Silakan masukkan password Anda saat ini.";
    } else {
        $stmt_check = $conn->prepare("SELECT password FROM user_admin WHERE id_user = ?");
        $stmt_check->bind_param("i", $id_user);
        $stmt_check->execute();
        $user = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();
        
        if (!password_verify($_POST['password_lama'], $user['password'])) {
            $password_lama_err = "Password lama yang Anda masukkan salah.";
        }
    }

    if (empty(trim($_POST['password_baru']))) {
        $password_baru_err = "Password baru tidak boleh kosong.";
    } elseif (strlen(trim($_POST['password_baru'])) < 6) {
        $password_baru_err = "Password baru minimal harus 6 karakter.";
    }

    if (trim($_POST['password_baru']) !== trim($_POST['konfirmasi_password'])) {
        $konfirmasi_err = "Konfirmasi password tidak cocok.";
    }

    if (empty($password_lama_err) && empty($password_baru_err) && empty($konfirmasi_err)) {
        $password_baru_hashed = password_hash($_POST['password_baru'], PASSWORD_DEFAULT);
        
        $stmt_update = $conn->prepare("UPDATE user_admin SET password = ? WHERE id_user = ?");
        $stmt_update->bind_param("si", $password_baru_hashed, $id_user);
        
        if ($stmt_update->execute()) {
            set_message('success', '✅ Password Anda berhasil diperbarui.');
            redirect('customers.php');
        } else {
            set_message('error', '❌ Terjadi kesalahan. Gagal memperbarui password.');
        }
        $stmt_update->close();
    }
}

include 'header.php';
?>

<div class="card">
    <h2 class="mt-0">Ganti Password</h2>
    <p>Untuk keamanan, ganti password Anda secara berkala.</p>

    <form action="change_password.php" method="POST">
        <div class="form-group">
            <label>Password Saat Ini</label>
            <input type="password" name="password_lama" class="form-control">
            <?php if($password_lama_err): ?>
            <span style="color: var(--danger); font-size: 0.85rem; margin-top: 4px; display: block;">
                <?= $password_lama_err ?>
            </span>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label>Password Baru</label>
            <input type="password" name="password_baru" class="form-control">
            <?php if($password_baru_err): ?>
            <span style="color: var(--danger); font-size: 0.85rem; margin-top: 4px; display: block;">
                <?= $password_baru_err ?>
            </span>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label>Konfirmasi Password Baru</label>
            <input type="password" name="konfirmasi_password" class="form-control">
            <?php if($konfirmasi_err): ?>
            <span style="color: var(--danger); font-size: 0.85rem; margin-top: 4px; display: block;">
                <?= $konfirmasi_err ?>
            </span>
            <?php endif; ?>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-key"></i> Ganti Password
            </button>
            <a href="customers.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Batal
            </a>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>