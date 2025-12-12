<?php
include 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    set_message('error', '❌ Anda tidak memiliki izin untuk mengakses halaman ini.');
    redirect('index.php');
}

if (isset($_GET['aksi']) && isset($_GET['id_notif'])) {
    $id_notif = intval($_GET['id_notif']);

    if ($_GET['aksi'] === 'proses') {
        $stmt_notif = $conn->prepare("SELECT id_penyewa, id_motor, lama_sewa FROM notifikasi WHERE id_notif = ? AND status = 'belum dibaca'");
        $stmt_notif->bind_param("i", $id_notif);
        $stmt_notif->execute();
        $notif_data = $stmt_notif->get_result()->fetch_assoc();
        $stmt_notif->close();

        if ($notif_data) {
            $id_penyewa = $notif_data['id_penyewa'];
            $id_motor = $notif_data['id_motor'];
            $lama_sewa = $notif_data['lama_sewa'];
            
            $conn->begin_transaction();
            try {
                $stmt_motor = $conn->prepare("SELECT harga_sewa_perhari FROM motor WHERE id_motor = ?");
                $stmt_motor->bind_param("i", $id_motor);
                $stmt_motor->execute();
                $motor = $stmt_motor->get_result()->fetch_assoc();
                $stmt_motor->close();
                
                $harga_perhari = $motor['harga_sewa_perhari'];
                $total_bayar = $harga_perhari * $lama_sewa;
                
                $tanggal_sewa = date('Y-m-d');
                $tanggal_kembali = date('Y-m-d', strtotime("+$lama_sewa days"));
                
                $stmt_trans = $conn->prepare("INSERT INTO transaksi_sewa (id_penyewa, id_motor, tanggal_sewa, tanggal_kembali, lama_sewa, total_bayar, status) VALUES (?, ?, ?, ?, ?, ?, 'Aktif')");
                $stmt_trans->bind_param("iissid", $id_penyewa, $id_motor, $tanggal_sewa, $tanggal_kembali, $lama_sewa, $total_bayar);
                $stmt_trans->execute();
                $stmt_trans->close();
                
                $conn->query("UPDATE motor SET status = 'Disewa' WHERE id_motor = $id_motor");
                
                $conn->query("UPDATE notifikasi SET status = 'sudah dibaca' WHERE id_notif = $id_notif");
                
                $conn->commit();
                set_message('success', '✅ Request berhasil diproses! Transaksi sewa telah dibuat.');
            } catch (Exception $e) {
                $conn->rollback();
                set_message('error', '❌ Terjadi kesalahan saat memproses request.');
            }
        }
    } elseif ($_GET['aksi'] === 'tolak') {
        $stmt = $conn->prepare("UPDATE notifikasi SET status = 'ditolak' WHERE id_notif = ?");
        $stmt->bind_param("i", $id_notif);
        $stmt->execute();
        $stmt->close();
        set_message('success', 'Request telah ditolak.');
    }
    redirect('transactions.php');
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_transaction'])) {
    $id_penyewa = $_POST['id_penyewa'];
    $id_motor = $_POST['id_motor'];
    $lama_sewa = intval($_POST['lama_sewa']);
    
    $stmt_motor = $conn->prepare("SELECT harga_sewa_perhari FROM motor WHERE id_motor = ?");
    $stmt_motor->bind_param("i", $id_motor);
    $stmt_motor->execute();
    $motor = $stmt_motor->get_result()->fetch_assoc();
    $stmt_motor->close();
    
    $harga_perhari = $motor['harga_sewa_perhari'];
    $total_bayar = $harga_perhari * $lama_sewa;
    
    $tanggal_sewa = date('Y-m-d');
    $tanggal_kembali = date('Y-m-d', strtotime("+$lama_sewa days"));
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO transaksi_sewa (id_penyewa, id_motor, tanggal_sewa, tanggal_kembali, lama_sewa, total_bayar, status) VALUES (?, ?, ?, ?, ?, ?, 'Aktif')");
        $stmt->bind_param("iissid", $id_penyewa, $id_motor, $tanggal_sewa, $tanggal_kembali, $lama_sewa, $total_bayar);
        $stmt->execute();
        $stmt->close();
        
        $conn->query("UPDATE motor SET status = 'Disewa' WHERE id_motor = $id_motor");
        
        $conn->commit();
        set_message('success', '✅ Transaksi berhasil ditambahkan.');
    } catch (Exception $e) {
        $conn->rollback();
        set_message('error', '❌ Gagal menambahkan transaksi.');
    }
    redirect('transactions.php');
}

if (isset($_GET['kembalikan'])) {
    $id_transaksi = intval($_GET['kembalikan']);
    
    $conn->begin_transaction();
    try {
        $stmt_get = $conn->prepare("SELECT id_motor FROM transaksi_sewa WHERE id_transaksi = ?");
        $stmt_get->bind_param("i", $id_transaksi);
        $stmt_get->execute();
        $trans = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();
        
        $id_motor = $trans['id_motor'];
        
        $tanggal_dikembalikan = date('Y-m-d');
        $stmt_update = $conn->prepare("UPDATE transaksi_sewa SET status = 'Selesai', tanggal_dikembalikan = ? WHERE id_transaksi = ?");
        $stmt_update->bind_param("si", $tanggal_dikembalikan, $id_transaksi);
        $stmt_update->execute();
        $stmt_update->close();
        
        $conn->query("UPDATE motor SET status = 'Tersedia' WHERE id_motor = $id_motor");
        
        $conn->commit();
        set_message('success', '✅ Motor berhasil dikembalikan.');
    } catch (Exception $e) {
        $conn->rollback();
        set_message('error', '❌ Gagal memproses pengembalian.');
    }
    redirect('transactions.php');
}

if (isset($_GET['delete'])) {
    $id_transaksi = intval($_GET['delete']);
    
    $conn->begin_transaction();
    try {
        $stmt_get = $conn->prepare("SELECT id_motor, status FROM transaksi_sewa WHERE id_transaksi = ?");
        $stmt_get->bind_param("i", $id_transaksi);
        $stmt_get->execute();
        $trans = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();
        
        $id_motor = $trans['id_motor'];
        
        $stmt_delete = $conn->prepare("DELETE FROM transaksi_sewa WHERE id_transaksi = ?");
        $stmt_delete->bind_param("i", $id_transaksi);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        if ($trans['status'] == 'Aktif') {
            $conn->query("UPDATE motor SET status = 'Tersedia' WHERE id_motor = $id_motor");
        }
        
        $conn->commit();
        set_message('success', '🗑️ Transaksi berhasil dihapus.');
    } catch (Exception $e) {
        $conn->rollback();
        set_message('error', '❌ Gagal menghapus transaksi.');
    }
    redirect('transactions.php');
}

include 'header.php';
?>

<div class="card">
    <h2 class="mt-0">Manajemen Transaksi Sewa</h2>
    <p>Kelola semua transaksi penyewaan motor.</p>
</div>

<div class="card">
    <h3 class="mt-0">🔔 Request Sewa Baru</h3>
    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Pesan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $notif_result = $conn->query("SELECT * FROM notifikasi WHERE status = 'belum dibaca' ORDER BY tanggal_notif DESC");
            if ($notif_result->num_rows > 0):
                while($notif = $notif_result->fetch_assoc()):
            ?>
            <tr>
                <td><?= date('d M Y, H:i', strtotime($notif['tanggal_notif'])) ?></td>
                <td><?= htmlspecialchars($notif['pesan']) ?></td>
                <td>
                    <div class="form-actions" style="margin: 0;">
                        <a href="transactions.php?aksi=proses&id_notif=<?= $notif['id_notif'] ?>" 
                           class="btn btn-success" style="padding: 6px 12px; font-size: 0.85rem;"
                           onclick="return confirm('Proses transaksi ini?')">
                            <i class="fas fa-check"></i> Proses
                        </a>
                        <a href="transactions.php?aksi=tolak&id_notif=<?= $notif['id_notif'] ?>" 
                           class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;"
                           onclick="return confirm('Tolak request ini?')">
                            <i class="fas fa-times"></i> Tolak
                        </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="3" class="text-center">Tidak ada request baru.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3 class="mt-0">Tambah Transaksi Manual</h3>
    <p>Gunakan form ini untuk menambahkan transaksi secara manual (offline).</p>
    
    <form method="POST" action="transactions.php">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Pilih Penyewa</label>
                <select name="id_penyewa" class="form-control" required>
                    <option value="">-- Pilih Penyewa --</option>
                    <?php
                    $result = $conn->query("SELECT id_penyewa, nama FROM penyewa ORDER BY nama ASC");
                    while ($row = $result->fetch_assoc()):
                    ?>
                        <option value="<?= $row['id_penyewa'] ?>"><?= htmlspecialchars($row['nama']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Pilih Motor</label>
                <select name="id_motor" class="form-control" required>
                    <option value="">-- Pilih Motor --</option>
                    <?php
                    $result = $conn->query("SELECT id_motor, nama_motor, plat_nomor FROM motor WHERE status = 'Tersedia' ORDER BY nama_motor ASC");
                    while ($row = $result->fetch_assoc()):
                    ?>
                        <option value="<?= $row['id_motor'] ?>">
                            <?= htmlspecialchars($row['nama_motor']) ?> (<?= htmlspecialchars($row['plat_nomor']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Lama Sewa (Hari)</label>
                <input type="number" name="lama_sewa" class="form-control" min="1" value="1" required>
            </div>
        </div>
        <button type="submit" name="add_transaction" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Transaksi
        </button>
    </form>
</div>

<div class="card">
    <h3 class="mt-0">🏍️ Sewa Aktif</h3>
    <table>
        <thead>
            <tr>
                <th>Penyewa</th>
                <th>Motor</th>
                <th>Tgl Sewa</th>
                <th>Tgl Kembali</th>
                <th>Total</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql_aktif = "
                SELECT t.*, p.nama as nama_penyewa, m.nama_motor, m.plat_nomor 
                FROM transaksi_sewa t 
                JOIN penyewa p ON t.id_penyewa = p.id_penyewa 
                JOIN motor m ON t.id_motor = m.id_motor 
                WHERE t.status = 'Aktif'
                ORDER BY t.tanggal_sewa DESC
            ";
            $result_aktif = $conn->query($sql_aktif);
            if ($result_aktif->num_rows > 0):
                while($trans = $result_aktif->fetch_assoc()):
                    $hari_tersisa = (strtotime($trans['tanggal_kembali']) - strtotime(date('Y-m-d'))) / 86400;
                    $status_class = $hari_tersisa <= 0 ? 'maintenance' : ($hari_tersisa <= 2 ? 'disewa' : 'tersedia');
            ?>
            <tr>
                <td><?= htmlspecialchars($trans['nama_penyewa']) ?></td>
                <td>
                    <strong><?= htmlspecialchars($trans['nama_motor']) ?></strong><br>
                    <small><?= htmlspecialchars($trans['plat_nomor']) ?></small>
                </td>
                <td><?= date('d M Y', strtotime($trans['tanggal_sewa'])) ?></td>
                <td>
                    <?= date('d M Y', strtotime($trans['tanggal_kembali'])) ?><br>
                    <span class="motor-status <?= $status_class ?>">
                        <?= $hari_tersisa <= 0 ? 'Terlambat' : 'Sisa ' . ceil($hari_tersisa) . ' hari' ?>
                    </span>
                </td>
                <td><?= format_rupiah($trans['total_bayar']) ?></td>
                <td>
                    <div class="form-actions" style="margin: 0;">
                        <!-- INI YANG DIPERBAIKI: pastikan mengarah ke edit_transaction.php -->
                        <a href="edit_transaction.php?id=<?= $trans['id_transaksi'] ?>" 
                           class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem;"
                           title="Edit data transaksi">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="transactions.php?kembalikan=<?= $trans['id_transaksi'] ?>" 
                           class="btn btn-success" style="padding: 6px 12px; font-size: 0.85rem;"
                           onclick="return confirm('Motor sudah dikembalikan?')">
                            <i class="fas fa-check-circle"></i> Kembalikan
                        </a>
                        <a href="transactions.php?delete=<?= $trans['id_transaksi'] ?>" 
                           class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;"
                           onclick="return confirm('Hapus transaksi ini?')">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="6" class="text-center">Tidak ada sewa aktif.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3 class="mt-0">📜 Riwayat Transaksi</h3>
    <table>
        <thead>
            <tr>
                <th>Penyewa</th>
                <th>Motor</th>
                <th>Tgl Sewa</th>
                <th>Tgl Kembali</th>
                <th>Lama</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql_selesai = "
                SELECT t.*, p.nama as nama_penyewa, m.nama_motor 
                FROM transaksi_sewa t 
                JOIN penyewa p ON t.id_penyewa = p.id_penyewa 
                JOIN motor m ON t.id_motor = m.id_motor 
                WHERE t.status = 'Selesai'
                ORDER BY t.tanggal_dikembalikan DESC
                LIMIT 20
            ";
            $result_selesai = $conn->query($sql_selesai);
            if ($result_selesai->num_rows > 0):
                while($trans = $result_selesai->fetch_assoc()):
            ?>
            <tr>
                <td><?= htmlspecialchars($trans['nama_penyewa']) ?></td>
                <td><?= htmlspecialchars($trans['nama_motor']) ?></td>
                <td><?= date('d M Y', strtotime($trans['tanggal_sewa'])) ?></td>
                <td><?= date('d M Y', strtotime($trans['tanggal_dikembalikan'])) ?></td>
                <td><?= $trans['lama_sewa'] ?> hari</td>
                <td><?= format_rupiah($trans['total_bayar']) ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="6" class="text-center">Belum ada riwayat transaksi.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>