<?php
include 'config.php';

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$id_to_edit = 0;

if ($is_admin) {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        redirect('customers.php');
    }
    $id_to_edit = intval($_GET['id']);
} else {
    // User
    $stmt_check = $conn->prepare("SELECT id_penyewa FROM penyewa WHERE id_user_akun = ?");
    $stmt_check->bind_param("i", $_SESSION['id_user']);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if($result_check->num_rows > 0) {
        $id_to_edit = $result_check->fetch_assoc()['id_penyewa'];
    } else {
        set_message('error', '❌ Profil penyewa Anda tidak ditemukan.');
        redirect('customers.php');
    }
    $stmt_check->close();
}

//Update data penyewa
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_customer'])) {
    $nama = $_POST['nama'];
    $no_telepon = $_POST['no_telepon'];
    $alamat = $_POST['alamat'];
    $no_ktp = $_POST['no_ktp'];

    $stmt_update = $conn->prepare("UPDATE penyewa SET nama = ?, no_telepon = ?, alamat = ?, no_ktp = ? WHERE id_penyewa = ?");
    $stmt_update->bind_param("ssssi", $nama, $no_telepon, $alamat, $no_ktp, $id_to_edit);

    if ($stmt_update->execute()) {
        if (!$is_admin) {
            $_SESSION['nama_lengkap'] = $nama;
        }
        set_message('success', '✅ Profil berhasil diperbarui.');
        redirect('customers.php');
    } else {
        set_message('error', '❌ Gagal memperbarui profil.');
    }
    $stmt_update->close();
}

include 'header.php';

$stmt_get = $conn->prepare("SELECT * FROM penyewa WHERE id_penyewa = ?");
$stmt_get->bind_param("i", $id_to_edit);
$stmt_get->execute();
$penyewa = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$penyewa) {
    echo '<div class="card"><div class="flash-message error">Data penyewa tidak ditemukan.</div></div>';
    include 'footer.php';
    exit();
}
?>

<div class="card">
    <h2 class="mt-0">Edit Profil</h2>
    <p>Perbarui informasi data diri di bawah ini.</p>

    <form method="POST" action="edit_customer.php<?= $is_admin ? '?id=' . $id_to_edit : '' ?>">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($penyewa['nama']) ?>" required>
            </div>
            <div class="form-group">
                <label>No. Telepon</label>
                <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($penyewa['no_telepon']) ?>" required>
            </div>
            <div class="form-group">
                <label>No. KTP</label>
                <input type="text" name="no_ktp" class="form-control" value="<?= htmlspecialchars($penyewa['no_ktp']) ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label>Alamat</label>
            <textarea name="alamat" class="form-control" rows="3" required><?= htmlspecialchars($penyewa['alamat']) ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" name="update_customer" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
            <a href="customers.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Batal
            </a>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>