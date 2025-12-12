<?php
include 'config.php';

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if ($is_admin) {
    
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_motor'])) {
        $nama_motor = $_POST['nama_motor'];
        $merk = $_POST['merk'];
        $tahun = $_POST['tahun'];
        $plat_nomor = $_POST['plat_nomor'];
        $harga_sewa = $_POST['harga_sewa_perhari'];
        $deskripsi = $_POST['deskripsi'];
        $foto = 'default-motor.jpg';
        
        // DEBUG: Cek apakah file di-upload
        if (isset($_FILES['foto'])) {
            error_log("File upload info: " . print_r($_FILES['foto'], true));
            
            // Cek apakah ada error upload
            if ($_FILES['foto']['error'] == UPLOAD_ERR_OK) {
                $upload_result = upload_foto_motor($_FILES['foto']);
                
                if ($upload_result['success']) {
                    $foto = $upload_result['filename'];
                    set_message('success', '✅ Motor berhasil ditambahkan dengan foto: ' . $foto);
                } else {
                    set_message('error', '❌ Motor ditambahkan tapi foto gagal: ' . $upload_result['message']);
                }
            } elseif ($_FILES['foto']['error'] == UPLOAD_ERR_NO_FILE) {
                // Tidak ada file yang di-upload, gunakan default
                set_message('success', '✅ Motor berhasil ditambahkan dengan foto default (tidak ada file di-upload).');
            } else {
                // Ada error upload lain
                $error_messages = [
                    UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi upload_max_filesize di php.ini)',
                    UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi MAX_FILE_SIZE)',
                    UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
                    UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
                    UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
                    UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension PHP'
                ];
                $error_msg = isset($error_messages[$_FILES['foto']['error']]) 
                    ? $error_messages[$_FILES['foto']['error']] 
                    : 'Error tidak dikenal: ' . $_FILES['foto']['error'];
                
                set_message('error', '❌ Error upload: ' . $error_msg);
            }
        } else {
            set_message('warning', '⚠️ Motor ditambahkan dengan foto default (tidak ada input file).');
        }
        
        $stmt = $conn->prepare("INSERT INTO motor (nama_motor, merk, tahun, plat_nomor, harga_sewa_perhari, foto, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissds", $nama_motor, $merk, $tahun, $plat_nomor, $harga_sewa, $foto, $deskripsi);
        
        if ($stmt->execute()) {
            if (!isset($_SESSION['flash_message'])) {
                set_message('success', '✅ Motor baru berhasil ditambahkan dengan foto: ' . $foto);
            }
        } else {
            set_message('error', '❌ Gagal menambahkan motor. ' . $stmt->error);
        }
        $stmt->close();
        redirect('motors.php');
    }
    
    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        
        $stmt_foto = $conn->prepare("SELECT foto FROM motor WHERE id_motor = ?");
        $stmt_foto->bind_param("i", $id);
        $stmt_foto->execute();
        $result = $stmt_foto->get_result();
        if ($row = $result->fetch_assoc()) {
            delete_foto_motor($row['foto']);
        }
        $stmt_foto->close();
        
        $stmt = $conn->prepare("DELETE FROM motor WHERE id_motor = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            set_message('success', '🗑️ Motor berhasil dihapus.');
        } else {
            set_message('error', '❌ Gagal menghapus motor.');
        }
        $stmt->close();
        redirect('motors.php');
    }
    
    if (isset($_GET['update_status'])) {
        $id = intval($_GET['id']);
        $status = $_GET['status'];
        
        $stmt = $conn->prepare("UPDATE motor SET status = ? WHERE id_motor = ?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            set_message('success', '✅ Status motor berhasil diupdate.');
        }
        $stmt->close();
        redirect('motors.php');
    }
}

if (!$is_admin && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_sewa'])) {
    $id_motor = $_POST['id_motor'];
    $lama_sewa = intval($_POST['lama_sewa']);
    $id_user_akun = $_SESSION['id_user'];
    
    $stmt_penyewa = $conn->prepare("SELECT id_penyewa, nama FROM penyewa WHERE id_user_akun = ?");
    $stmt_penyewa->bind_param("i", $id_user_akun);
    $stmt_penyewa->execute();
    $penyewa = $stmt_penyewa->get_result()->fetch_assoc();
    $stmt_penyewa->close();
    
    $stmt_motor = $conn->prepare("SELECT nama_motor FROM motor WHERE id_motor = ?");
    $stmt_motor->bind_param("i", $id_motor);
    $stmt_motor->execute();
    $motor = $stmt_motor->get_result()->fetch_assoc();
    $stmt_motor->close();
    
    if ($penyewa && $motor) {
        $pesan = "Penyewa '" . $penyewa['nama'] . "' ingin menyewa motor '" . $motor['nama_motor'] . "' selama " . $lama_sewa . " hari.";
        
        $stmt_notif = $conn->prepare("INSERT INTO notifikasi (id_penyewa, id_motor, pesan, lama_sewa) VALUES (?, ?, ?, ?)");
        $stmt_notif->bind_param("iisi", $penyewa['id_penyewa'], $id_motor, $pesan, $lama_sewa);
        
        if($stmt_notif->execute()) {
            set_message('success', '✅ Request sewa telah dikirim ke admin.');
        } else {
            set_message('error', '❌ Gagal mengirim request.');
        }
        $stmt_notif->close();
    }
    redirect('motors.php');
}

include 'header.php';
?>

<?php if ($is_admin): ?>
    
    <div class="card">
        <h2 class="mt-0">Data Motor</h2>
        <p>Kelola semua data motor yang tersedia untuk disewakan.</p>
        
        <!-- INFO UPLOAD -->
        <div style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.1) 0%, rgba(8, 145, 178, 0.1) 100%); 
                    padding: 16px; border-radius: 12px; border-left: 4px solid #06b6d4; margin-top: 16px;">
            <h4 style="margin: 0 0 8px 0; color: #06b6d4; font-size: 0.95rem;">
                <i class="fas fa-info-circle"></i> Info Upload Foto:
            </h4>
            <ul style="margin: 0; padding-left: 20px; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6;">
                <li>Format: JPG, PNG, GIF, WebP</li>
                <li>Ukuran maksimal: 2MB</li>
                <li>Folder upload: <code>uploads/motors/</code></li>
                <li>PHP max upload: <strong><?= ini_get('upload_max_filesize') ?></strong></li>
                <li>PHP post max: <strong><?= ini_get('post_max_size') ?></strong></li>
            </ul>
        </div>
    </div>
    
    <div class="card">
        <h3 class="mt-0">Tambah Motor Baru</h3>
        
        <!-- IMPORTANT: enctype="multipart/form-data" INI WAJIB! -->
        <form method="POST" action="motors.php" enctype="multipart/form-data" id="addMotorForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Nama Motor</label>
                    <input type="text" name="nama_motor" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Merk</label>
                    <input type="text" name="merk" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Tahun</label>
                    <input type="number" name="tahun" class="form-control" min="2000" max="2025" required>
                </div>
                <div class="form-group">
                    <label>Plat Nomor</label>
                    <input type="text" name="plat_nomor" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Harga Sewa/Hari (Rp)</label>
                    <input type="number" name="harga_sewa_perhari" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Foto Motor</label>
                    <input type="file" name="foto" id="foto" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <small style="color: var(--text-secondary); display: block; margin-top: 4px;">
                        Max 2MB. Format: JPG, PNG, GIF, WebP
                    </small>
                </div>
            </div>
            
            <!-- PREVIEW FOTO -->
            <div class="form-group" id="preview-container" style="display: none;">
                <label>Preview Foto:</label>
                <div style="position: relative; display: inline-block;">
                    <img id="preview-image" src="" style="max-width: 300px; height: auto; border-radius: 12px; border: 2px solid var(--border);">
                    <button type="button" onclick="removePreview()" 
                            style="position: absolute; top: 8px; right: 8px; background: var(--danger); color: white; 
                                   border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; 
                                   font-weight: bold; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                        ×
                    </button>
                </div>
                <div style="margin-top: 8px;">
                    <span id="file-info" style="color: var(--text-secondary); font-size: 0.9rem;"></span>
                </div>
            </div>
            
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="3"></textarea>
            </div>
            
            <button type="submit" name="add_motor" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Motor
            </button>
        </form>
    </div>
    
    <div class="card">
        <h3 class="mt-0">Daftar Semua Motor</h3>
        <table>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Motor</th>
                    <th>Plat</th>
                    <th>Harga/Hari</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM motor ORDER BY id_motor DESC");
                if ($result->num_rows > 0):
                    while($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td>
                        <img src="uploads/motors/<?= htmlspecialchars($row['foto']) ?>" 
                             style="width: 80px; height: 60px; object-fit: cover; border-radius: 8px;"
                             onerror="this.src='uploads/motors/default-motor.jpg'"
                             title="File: <?= htmlspecialchars($row['foto']) ?>">
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($row['nama_motor']) ?></strong><br>
                        <small><?= htmlspecialchars($row['merk']) ?> (<?= $row['tahun'] ?>)</small><br>
                        <small style="color: var(--text-secondary); font-size: 0.75rem;">
                            Foto: <?= htmlspecialchars($row['foto']) ?>
                        </small>
                    </td>
                    <td><?= htmlspecialchars($row['plat_nomor']) ?></td>
                    <td><?= format_rupiah($row['harga_sewa_perhari']) ?></td>
                    <td>
                        <span class="motor-status <?= strtolower($row['status']) ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="form-actions" style="margin: 0;">
                            <?php if ($row['status'] == 'Tersedia'): ?>
                                <a href="motors.php?update_status=1&id=<?= $row['id_motor'] ?>&status=Maintenance" 
                                   class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;">
                                    Maintenance
                                </a>
                            <?php elseif ($row['status'] == 'Maintenance'): ?>
                                <a href="motors.php?update_status=1&id=<?= $row['id_motor'] ?>&status=Tersedia" 
                                   class="btn btn-success" style="padding: 6px 12px; font-size: 0.85rem;">
                                    Aktifkan
                                </a>
                            <?php endif; ?>
                            <a href="edit_motor.php?id=<?= $row['id_motor'] ?>" 
                               class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem;">
                                Edit
                            </a>
                            <a href="motors.php?delete=<?= $row['id_motor'] ?>" 
                               class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;"
                               onclick="return confirm('Yakin hapus motor ini?')">
                                Hapus
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="6" class="text-center">Belum ada data motor.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    
    <div class="card card-header">
        <h2>🏍️ Motor Tersedia</h2>
        <p>Pilih motor yang ingin Anda sewa</p>
    </div>
    
    <div class="motor-grid">
        <?php
        $result = $conn->query("SELECT * FROM motor WHERE status = 'Tersedia' ORDER BY nama_motor ASC");
        if ($result->num_rows > 0):
            while($motor = $result->fetch_assoc()):
        ?>
        <div class="motor-card">
            <img src="uploads/motors/<?= htmlspecialchars($motor['foto']) ?>" 
                 alt="<?= htmlspecialchars($motor['nama_motor']) ?>"
                 onerror="this.src='uploads/motors/default-motor.jpg'">
            <div class="motor-card-body">
                <h3 class="motor-card-title"><?= htmlspecialchars($motor['nama_motor']) ?></h3>
                <div class="motor-card-info">
                    <p><i class="fas fa-tag"></i> <?= htmlspecialchars($motor['merk']) ?> • Tahun <?= $motor['tahun'] ?></p>
                    <p><i class="fas fa-id-card"></i> Plat: <?= htmlspecialchars($motor['plat_nomor']) ?></p>
                    <?php if (!empty($motor['deskripsi'])): ?>
                    <p style="margin-top: 8px; font-size: 0.9rem;"><?= htmlspecialchars($motor['deskripsi']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="motor-card-price">
                    <?= format_rupiah($motor['harga_sewa_perhari']) ?>/hari
                </div>
                <form method="POST" action="motors.php">
                    <input type="hidden" name="id_motor" value="<?= $motor['id_motor'] ?>">
                    <div class="form-group">
                        <label>Lama Sewa (Hari)</label>
                        <input type="number" name="lama_sewa" class="form-control" min="1" value="1" required>
                    </div>
                    <button type="submit" name="request_sewa" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Request Sewa
                    </button>
                </form>
            </div>
        </div>
        <?php 
            endwhile;
        else:
        ?>
        <div class="card" style="grid-column: 1 / -1;">
            <p class="text-center">Saat ini tidak ada motor yang tersedia.</p>
        </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<script>
// Preview image sebelum upload
function previewImage(input) {
    const preview = document.getElementById('preview-image');
    const container = document.getElementById('preview-container');
    const fileInfo = document.getElementById('file-info');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validasi ukuran file (2MB = 2097152 bytes)
        if (file.size > 2097152) {
            alert('❌ Ukuran file terlalu besar! Maksimal 2MB.\nUkuran file Anda: ' + (file.size / 1024 / 1024).toFixed(2) + ' MB');
            input.value = '';
            container.style.display = 'none';
            return;
        }
        
        // Validasi tipe file
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('❌ Format file tidak valid! Hanya JPG, PNG, GIF, atau WebP.\nFormat Anda: ' + file.type);
            input.value = '';
            container.style.display = 'none';
            return;
        }
        
        // Preview
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
            
            // Info file
            const sizeInMB = (file.size / 1024 / 1024).toFixed(2);
            fileInfo.innerHTML = `
                <i class="fas fa-check-circle" style="color: var(--success);"></i> 
                <strong>${file.name}</strong> (${sizeInMB} MB)
            `;
        }
        reader.readAsDataURL(file);
    }
}

function removePreview() {
    document.getElementById('foto').value = '';
    document.getElementById('preview-container').style.display = 'none';
    document.getElementById('preview-image').src = '';
}

// Validasi sebelum submit
document.getElementById('addMotorForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('foto');
    
    if (fileInput.files.length === 0) {
        if (!confirm('⚠️ Anda belum memilih foto motor.\n\nMotor akan menggunakan foto default.\n\nLanjutkan?')) {
            e.preventDefault();
        }
    }
});
</script>

<?php include 'footer.php'; ?>