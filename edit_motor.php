<?php
include 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    set_message('error', '❌ Anda tidak memiliki izin untuk mengakses halaman ini.');
    redirect('index.php');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('motors.php');
}
$id_motor = intval($_GET['id']);

$stmt_get = $conn->prepare("SELECT * FROM motor WHERE id_motor = ?");
$stmt_get->bind_param("i", $id_motor);
$stmt_get->execute();
$motor = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$motor) {
    set_message('error', '❌ Data motor tidak ditemukan.');
    redirect('motors.php');
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_motor'])) {
    $nama_motor = $_POST['nama_motor'];
    $merk = $_POST['merk'];
    $tahun = intval($_POST['tahun']);
    $plat_nomor = $_POST['plat_nomor'];
    $harga_sewa = floatval($_POST['harga_sewa_perhari']);
    $deskripsi = $_POST['deskripsi'];
    
    $foto = $motor['foto'];
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $upload_result = upload_foto_motor($_FILES['foto']);
        if ($upload_result['success']) {
            if ($foto != 'default-motor.jpg' && !empty($foto)) {
                delete_foto_motor($foto);
            }
            $foto = $upload_result['filename'];
        } else {
            set_message('error', $upload_result['message']);
            redirect('edit_motor.php?id=' . $id_motor);
        }
    }
    
    $stmt_update = $conn->prepare("UPDATE motor SET nama_motor = ?, merk = ?, tahun = ?, plat_nomor = ?, harga_sewa_perhari = ?, foto = ?, deskripsi = ? WHERE id_motor = ?");
    $stmt_update->bind_param("ssissssi", $nama_motor, $merk, $tahun, $plat_nomor, $harga_sewa, $foto, $deskripsi, $id_motor);

    if ($stmt_update->execute()) {
        set_message('success', '✅ Data motor berhasil diperbarui.');
        redirect('motors.php');
    } else {
        set_message('error', '❌ Gagal memperbarui data motor: ' . $stmt_update->error);
    }
    $stmt_update->close();
}

include 'header.php';
?>

<div class="card">
    <h2 class="mt-0">Edit Data Motor</h2>
    <p>Perbarui informasi motor di bawah ini.</p>

    <form method="POST" action="edit_motor.php?id=<?= $id_motor ?>" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Nama Motor</label>
                <input type="text" name="nama_motor" class="form-control" value="<?= htmlspecialchars($motor['nama_motor']) ?>" required>
            </div>
            <div class="form-group">
                <label>Merk</label>
                <input type="text" name="merk" class="form-control" value="<?= htmlspecialchars($motor['merk']) ?>" required>
            </div>
            <div class="form-group">
                <label>Tahun</label>
                <input type="number" name="tahun" class="form-control" value="<?= $motor['tahun'] ?>" min="2000" max="2025" required>
            </div>
            <div class="form-group">
                <label>Plat Nomor</label>
                <input type="text" name="plat_nomor" class="form-control" value="<?= htmlspecialchars($motor['plat_nomor']) ?>" required>
            </div>
            <div class="form-group">
                <label>Harga Sewa/Hari (Rp)</label>
                <input type="number" name="harga_sewa_perhari" class="form-control" value="<?= $motor['harga_sewa_perhari'] ?>" required>
            </div>
            <div class="form-group">
                <label>Foto Motor Baru (Opsional)</label>
                <input type="file" name="foto" class="form-control" accept="image/*">
                <small style="color: var(--text-secondary);">Biarkan kosong jika tidak ingin mengubah foto</small>
            </div>
        </div>
        
        <div class="form-group">
            <label>Foto Saat Ini</label>
            <div>
                <img src="uploads/motors/<?= htmlspecialchars($motor['foto']) ?>" 
                     style="max-width: 300px; height: auto; border-radius: 8px; border: 1px solid var(--border);"
                     onerror="this.src='https://via.placeholder.com/300x200?text=Motor'">
            </div>
        </div>
        
        <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($motor['deskripsi']) ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="update_motor" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
            <a href="motors.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Batal
            </a>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>