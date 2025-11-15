<?php
include 'config.php';

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if ($is_admin) {
    
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_customer'])) {
        $nama = $_POST['nama'];
        $no_telepon = $_POST['no_telepon'];
        $alamat = $_POST['alamat'];
        $no_ktp = $_POST['no_ktp'];
        $tanggal_sekarang = date('Y-m-d');

        $stmt = $conn->prepare("INSERT INTO penyewa (nama, no_telepon, alamat, no_ktp, tanggal_daftar) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nama, $no_telepon, $alamat, $no_ktp, $tanggal_sekarang);

        if ($stmt->execute()) {
            set_message('success', '✅ Penyewa baru berhasil ditambahkan.');
        } else {
            set_message('error', '❌ Gagal menambahkan penyewa. Error: ' . $stmt->error);
        }
        $stmt->close();
        redirect('customers.php');
    }

    if (isset($_GET['delete'])) {
        $id_penyewa = intval($_GET['delete']);
        
        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("DELETE FROM transaksi_sewa WHERE id_penyewa = ?");
            $stmt1->bind_param("i", $id_penyewa);
            $stmt1->execute();
            $stmt1->close();
            
            $stmt2 = $conn->prepare("DELETE FROM notifikasi WHERE id_penyewa = ?");
            $stmt2->bind_param("i", $id_penyewa);
            $stmt2->execute();
            $stmt2->close();

            $stmt3 = $conn->prepare("DELETE FROM penyewa WHERE id_penyewa = ?");
            $stmt3->bind_param("i", $id_penyewa);
            $stmt3->execute();
            $stmt3->close();
            
            $conn->commit();
            set_message('success', '🗑️ Penyewa dan riwayatnya berhasil dihapus.');
        } catch (Exception $e) {
            $conn->rollback();
            set_message('error', '❌ Gagal menghapus penyewa. Error: ' . $e->getMessage());
        }
        redirect('customers.php');
    }
}

include 'header.php';

//User ngambil data profilnya sendiri
if (!$is_admin) {
    $id_user_akun = $_SESSION['id_user'];
    $stmt_profil = $conn->prepare("SELECT * FROM penyewa WHERE id_user_akun = ?");
    $stmt_profil->bind_param("i", $id_user_akun);
    $stmt_profil->execute();
    $profil_user = $stmt_profil->get_result()->fetch_assoc();
    $stmt_profil->close();
}
?>

<?php if ($is_admin): ?>
    
    <div class="card">
        <h2 class="mt-0">Manajemen Data Penyewa</h2>
        <p>Kelola semua data penyewa yang terdaftar.</p>
    </div>
    
    <div class="card">
        <h3 class="mt-0">Tambah Penyewa Baru (Tanpa Akun)</h3>
        <p>Gunakan form ini untuk mendaftarkan penyewa yang tidak memiliki akun login.</p>
        
        <form method="POST" action="customers.php">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="text" name="no_telepon" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>No. KTP</label>
                    <input type="text" name="no_ktp" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat" class="form-control" rows="3" required></textarea>
            </div>
            <button type="submit" name="add_customer" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Tambah Penyewa
            </button>
        </form>
    </div>

    <div class="card">
        <h3 class="mt-0">Daftar Semua Penyewa</h3>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>No. Telepon</th>
                    <th>No. KTP</th>
                    <th>Tgl Daftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM penyewa ORDER BY tanggal_daftar DESC");
                if ($result->num_rows > 0):
                    while($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($row['nama']) ?></strong>
                        <?php if ($row['id_user_akun']): ?>
                        <br><small style="color: var(--success);"><i class="fas fa-check-circle"></i> Punya Akun</small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['no_telepon']) ?></td>
                    <td><?= htmlspecialchars($row['no_ktp']) ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal_daftar'])) ?></td>
                    <td>
                        <div class="form-actions" style="margin: 0;">
                            <a href="edit_customer.php?id=<?= $row['id_penyewa'] ?>" 
                               class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem;">
                                Edit
                            </a>
                            <a href="customers.php?delete=<?= $row['id_penyewa'] ?>" 
                               class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;"
                               onclick="return confirm('Yakin ingin hapus penyewa ini?')">
                                Hapus
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center">Belum ada data penyewa.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    
    <!-- Tampilan User -->
    <div class="card card-header">
        <h2>Profil Saya</h2>
        <p>Informasi data pribadi Anda</p>
    </div>
    
    <?php if (isset($profil_user)): ?>
    <div class="card">
        <h3 class="mt-0">Informasi Pribadi</h3>
        <table style="border: none;">
            <tr style="border-bottom: 1px solid var(--border);">
                <td style="width: 200px; font-weight: 600;">Nama Lengkap</td>
                <td><?= htmlspecialchars($profil_user['nama']) ?></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--border);">
                <td style="font-weight: 600;">No. Telepon</td>
                <td><?= htmlspecialchars($profil_user['no_telepon']) ?></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--border);">
                <td style="font-weight: 600;">No. KTP</td>
                <td><?= htmlspecialchars($profil_user['no_ktp']) ?></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--border);">
                <td style="font-weight: 600;">Alamat</td>
                <td><?= htmlspecialchars($profil_user['alamat']) ?></td>
            </tr>
            <tr>
                <td style="font-weight: 600;">Terdaftar Sejak</td>
                <td><?= date('d M Y', strtotime($profil_user['tanggal_daftar'])) ?></td>
            </tr>
        </table>
        
        <div class="form-actions" style="margin-top: 24px;">
            <a href="edit_customer.php" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Profil
            </a>
            <a href="change_password.php" class="btn btn-secondary">
                <i class="fas fa-key"></i> Ganti Password
            </a>
        </div>
    </div>
    
    <div class="card">
        <h3 class="mt-0">Status Request Sewa</h3>
        <table>
            <thead>
                <tr>
                    <th>Tanggal Request</th>
                    <th>Motor yang Diminta</th>
                    <th>Lama Sewa</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $id_penyewa = $profil_user['id_penyewa'];
                $request_result = $conn->query("
                    SELECT n.tanggal_notif, m.nama_motor, n.lama_sewa, n.status 
                    FROM notifikasi n
                    JOIN motor m ON n.id_motor = m.id_motor 
                    WHERE n.id_penyewa = $id_penyewa AND (n.status = 'ditolak' OR n.status = 'belum dibaca')
                    ORDER BY n.id_notif DESC
                ");
                if ($request_result->num_rows > 0):
                    while($req = $request_result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= date('d M Y, H:i', strtotime($req['tanggal_notif'])) ?></td>
                    <td><?= htmlspecialchars($req['nama_motor']) ?></td>
                    <td><?= $req['lama_sewa'] ?> hari</td>
                    <td>
                        <?php if($req['status'] == 'ditolak'): ?>
                            <span class="motor-status maintenance">❌ Ditolak</span>
                        <?php else: ?>
                            <span class="motor-status disewa">⏳ Menunggu</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" class="text-center">Tidak ada request yang aktif.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php else: ?>
    <div class="card">
        <div class="flash-message error">Profil penyewa untuk akun Anda tidak ditemukan.</div>
    </div>
    <?php endif; ?>

<?php endif; ?>

<?php include 'footer.php'; ?>