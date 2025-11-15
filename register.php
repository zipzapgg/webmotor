<?php
require_once "config.php";

$username_err = $password_err = $nama_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (empty(trim($_POST["nama_lengkap"]))) {
        $nama_err = "Nama lengkap tidak boleh kosong.";
    } else {
        $nama_lengkap = trim($_POST["nama_lengkap"]);
    }

    if (empty(trim($_POST["username"]))) {
        $username_err = "Username tidak boleh kosong.";
    } else {
        $sql_check = "SELECT id_user FROM user_admin WHERE username = ?";
        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("s", $_POST["username"]);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows == 1) {
                $username_err = "Username ini sudah digunakan.";
            } else {
                $username = trim($_POST["username"]);
            }
            $stmt_check->close();
        }
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Password tidak boleh kosong.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password minimal 6 karakter.";
    } else {
        $password = trim($_POST["password"]);
    }

    $no_telepon = trim($_POST["no_telepon"]);
    $alamat = trim($_POST["alamat"]);
    $no_ktp = trim($_POST["no_ktp"]);

    if (empty($username_err) && empty($password_err) && empty($nama_err)) {
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user';

        $conn->begin_transaction();
        try {
            $stmt_user = $conn->prepare("INSERT INTO user_admin (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
            $stmt_user->bind_param("ssss", $username, $hashed_password, $nama_lengkap, $role);
            $stmt_user->execute();
            $new_user_id = $conn->insert_id;
            $stmt_user->close();

            $tanggal_sekarang = date('Y-m-d');
            $stmt_penyewa = $conn->prepare("INSERT INTO penyewa (id_user_akun, nama, no_telepon, alamat, no_ktp, tanggal_daftar) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_penyewa->bind_param("isssss", $new_user_id, $nama_lengkap, $no_telepon, $alamat, $no_ktp, $tanggal_sekarang);
            $stmt_penyewa->execute();
            $stmt_penyewa->close();

            $conn->commit();

            set_message('success', '✅ Pendaftaran berhasil! Silakan login.');
            redirect('login.php');

        } catch (Exception $e) {
            $conn->rollback();
            echo "Oops! Terjadi kesalahan. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Rental Motor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link href="login-style.css" rel="stylesheet">
</head>
<body class="register-page">
    <div class="wrapper">
        <h2>Buat Akun Baru</h2>
        <p>Isi data untuk mendaftar sebagai penyewa</p>
        
        <form action="register.php" method="post">
            <div class="section-title">Data Pribadi</div>
            
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" value="<?= $_POST['nama_lengkap'] ?? ''; ?>" required>
                <span class="invalid-feedback"><?= $nama_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>No. Telepon</label>
                <input type="text" name="no_telepon" class="form-control" value="<?= $_POST['no_telepon'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>No. KTP</label>
                <input type="text" name="no_ktp" class="form-control" value="<?= $_POST['no_ktp'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat" class="form-control" rows="3" required><?= $_POST['alamat'] ?? ''; ?></textarea>
            </div>
            
            <hr>
            
            <div class="section-title">Akun Login</div>
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?= $_POST['username'] ?? ''; ?>" required>
                <span class="invalid-feedback"><?= $username_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
                <span class="invalid-feedback"><?= $password_err; ?></span>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-primary">Daftar</button>
            </div>
            
            <div class="login-link">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
        </form>
    </div>
</body>
</html>