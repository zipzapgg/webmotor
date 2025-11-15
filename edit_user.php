<?php
include 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    set_message('error', '❌ Anda tidak memiliki izin untuk mengakses halaman ini.');
    redirect('index.php');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('users.php');
}
$id_user = intval($_GET['id']);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $role = $_POST['role'];


    if ($id_user == $_SESSION['id_user'] && $role == 'user') {
        set_message('error', '❌ Anda tidak dapat mengubah role akun Anda sendiri menjadi "User".');
    } else {
        $stmt_update = $conn->prepare("UPDATE user_admin SET username = ?, nama_lengkap = ?, role = ? WHERE id_user = ?");
        $stmt_update->bind_param("sssi", $username, $nama_lengkap, $role, $id_user);

        if ($stmt_update->execute()) {
            set_message('success', '✅ Data pengguna berhasil diperbarui.');
            redirect('users.php');
        } else {
            set_message('error', '❌ Gagal memperbarui data. Username mungkin sudah digunakan.');
        }
        $stmt_update->close();
    }
}

include 'header.php';

$stmt_get = $conn->prepare("SELECT * FROM user_admin WHERE id_user = ?");
$stmt_get->bind_param("i", $id_user);
$stmt_get->execute();
$user = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$user) {
    echo '<div class="card"><div class="flash-message error">Data pengguna tidak ditemukan.</div></div>';
    include 'footer.php';
    exit();
}
?>

<div class="card">
    <h2 class="mt-0">Edit Data Pengguna</h2>
    <p>Anda dapat mengubah detail pengguna di bawah ini.</p>

    <form method="POST" action="edit_user.php?id=<?= $id_user ?>">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
        </div>
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" class="form-control" required>
                <option value="user" <?= ($user['role'] == 'user') ? 'selected' : '' ?>>User (Penyewa)</option>
                <option value="admin" <?= ($user['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" name="update_user" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
            <a href="users.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Batal
            </a>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>