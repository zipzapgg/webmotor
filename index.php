<?php
include 'config.php';
include 'header.php';

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$jam = date('G');
if ($jam >= 5 && $jam < 11) {
    $sapaan = "Selamat Pagi 🌞";
} elseif ($jam >= 11 && $jam < 15) {
    $sapaan = "Selamat Siang 👋";
} elseif ($jam >= 15 && $jam < 19) {
    $sapaan = "Selamat Sore ☀️";
} else {
    $sapaan = "Selamat Malam 🌙";
}

if ($is_admin) {
    $total_motor = $conn->query("SELECT COUNT(id_motor) as total FROM motor")->fetch_assoc()['total'] ?? 0;
    $motor_tersedia = $conn->query("SELECT COUNT(id_motor) as total FROM motor WHERE status = 'Tersedia'")->fetch_assoc()['total'] ?? 0;
    $motor_disewa = $conn->query("SELECT COUNT(id_motor) as total FROM motor WHERE status = 'Disewa'")->fetch_assoc()['total'] ?? 0;
    $total_penyewa = $conn->query("SELECT COUNT(id_penyewa) as total FROM penyewa")->fetch_assoc()['total'] ?? 0;
    $sewa_aktif = $conn->query("SELECT COUNT(id_transaksi) as total FROM transaksi_sewa WHERE status = 'Aktif'")->fetch_assoc()['total'] ?? 0;
    
    $total_pendapatan_row = $conn->query("SELECT SUM(total_bayar) as total FROM transaksi_sewa");
    $total_pendapatan = $total_pendapatan_row->fetch_assoc()['total'] ?? 0;
} else {
    $id_user_akun = $_SESSION['id_user'];
    $stmt_profil = $conn->prepare("SELECT * FROM penyewa WHERE id_user_akun = ?");
    $stmt_profil->bind_param("i", $id_user_akun);
    $stmt_profil->execute();
    $profil_user = $stmt_profil->get_result()->fetch_assoc();
    $stmt_profil->close();
    
    $sewa_aktif_user = [];
    $riwayat_user = [];
    
    if ($profil_user) {
        $id_penyewa = $profil_user['id_penyewa'];
        
        $result_aktif = $conn->query("
            SELECT t.*, m.nama_motor, m.plat_nomor, m.foto 
            FROM transaksi_sewa t 
            JOIN motor m ON t.id_motor = m.id_motor 
            WHERE t.id_penyewa = $id_penyewa AND t.status = 'Aktif'
            ORDER BY t.tanggal_sewa DESC
        ");
        while($row = $result_aktif->fetch_assoc()) {
            $sewa_aktif_user[] = $row;
        }
        
        $result_riwayat = $conn->query("
            SELECT t.*, m.nama_motor 
            FROM transaksi_sewa t 
            JOIN motor m ON t.id_motor = m.id_motor 
            WHERE t.id_penyewa = $id_penyewa AND t.status = 'Selesai'
            ORDER BY t.id_transaksi DESC LIMIT 5
        ");
        while($row = $result_riwayat->fetch_assoc()) {
            $riwayat_user[] = $row;
        }
    }
}
?>

<div class="card card-header">
    <h1><?= $sapaan ?>, <?= htmlspecialchars(explode(' ', $_SESSION['nama_lengkap'])[0]); ?>!</h1>
    <p>Selamat datang di Sistem Rental Motor</p>
</div>

<?php if ($is_admin): ?>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 24px;">
        <div class="card" style="text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
            <i class="fas fa-motorcycle" style="font-size: 2.5rem; margin-bottom: 12px; opacity: 0.9;"></i>
            <h3 style="color: white; font-size: 2rem; margin-bottom: 4px;"><?= $total_motor ?></h3>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">Total Motor</p>
        </div>
        
        <div class="card" style="text-align: center; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none;">
            <i class="fas fa-check-circle" style="font-size: 2.5rem; margin-bottom: 12px; opacity: 0.9;"></i>
            <h3 style="color: white; font-size: 2rem; margin-bottom: 4px;"><?= $motor_tersedia ?></h3>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">Motor Tersedia</p>
        </div>
        
        <div class="card" style="text-align: center; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none;">
            <i class="fas fa-clock" style="font-size: 2.5rem; margin-bottom: 12px; opacity: 0.9;"></i>
            <h3 style="color: white; font-size: 2rem; margin-bottom: 4px;"><?= $motor_disewa ?></h3>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">Sedang Disewa</p>
        </div>
        
        <div class="card" style="text-align: center; background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white; border: none;">
            <i class="fas fa-users" style="font-size: 2.5rem; margin-bottom: 12px; opacity: 0.9;"></i>
            <h3 style="color: white; font-size: 2rem; margin-bottom: 4px;"><?= $total_penyewa ?></h3>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">Total Penyewa</p>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
        <div class="card">
            <h3 style="margin-top: 0;">💰 Total Pendapatan</h3>
            <p style="font-size: 1.8rem; font-weight: 700; color: var(--primary); margin: 0;">
                <?= format_rupiah($total_pendapatan) ?>
            </p>
        </div>
        
        <div class="card">
            <h3 style="margin-top: 0;">📋 Sewa Aktif</h3>
            <p style="font-size: 1.8rem; font-weight: 700; color: var(--success); margin: 0;">
                <?= $sewa_aktif ?> Transaksi
            </p>
        </div>
        
        <div class="card">
            <h3 style="margin-top: 0;">🔔 Notifikasi</h3>
            <p style="font-size: 1.8rem; font-weight: 700; color: var(--warning); margin: 0;">
                <?= $unread_notifications ?> Request Baru
            </p>
        </div>
    </div>

<?php else: ?>
    
    <?php if ($profil_user): ?>
        
        <div class="card">
            <h3>🏍️ Sewa Aktif Saya</h3>
            <?php if (!empty($sewa_aktif_user)): ?>
                <?php foreach($sewa_aktif_user as $sewa): 
                    $hari_tersisa = (strtotime($sewa['tanggal_kembali']) - strtotime(date('Y-m-d'))) / 86400;
                    $status_class = $hari_tersisa <= 2 ? 'background: #fef3c7; color: #92400e; padding: 8px 12px; border-radius: 6px; font-weight: 600;' : 'background: #d1fae5; color: #065f46; padding: 8px 12px; border-radius: 6px; font-weight: 600;';
                ?>
                <div style="border: 2px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 16px;">
                    <div style="display: flex; gap: 20px; align-items: start;">
                        <img src="uploads/motors/<?= htmlspecialchars($sewa['foto']) ?>" 
                             style="width: 120px; height: 90px; object-fit: cover; border-radius: 8px;"
                             onerror="this.src='https://via.placeholder.com/120x90?text=Motor'">
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 8px 0;"><?= htmlspecialchars($sewa['nama_motor']) ?></h4>
                            <p style="margin: 4px 0; color: var(--text-secondary);">
                                <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($sewa['tanggal_sewa'])) ?> - <?= date('d M Y', strtotime($sewa['tanggal_kembali'])) ?>
                            </p>
                            <p style="margin: 4px 0; color: var(--text-secondary);">
                                <i class="fas fa-clock"></i> Sisa: <strong><?= ceil($hari_tersisa) ?> hari</strong>
                            </p>
                            <p style="margin: 8px 0 0 0;">
                                <span style="<?= $status_class ?>">
                                    <?= $hari_tersisa <= 2 ? '⚠️ Segera Kembali' : '✅ Aktif' ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 20px;">
                    Anda belum memiliki sewa aktif. <a href="motors.php" style="color: var(--primary); font-weight: 600;">Lihat motor tersedia</a>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>📜 Riwayat Sewa Terakhir</h3>
            <?php if (!empty($riwayat_user)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Motor</th>
                            <th>Tanggal Sewa</th>
                            <th>Lama</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($riwayat_user as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nama_motor']) ?></td>
                            <td><?= date('d M Y', strtotime($r['tanggal_sewa'])) ?></td>
                            <td><?= $r['lama_sewa'] ?> hari</td>
                            <td><?= format_rupiah($r['total_bayar']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-secondary); padding: 20px;">Belum ada riwayat sewa</p>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <div class="card">
            <div class="flash-message error">Profil penyewa tidak ditemukan.</div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php include 'footer.php'; ?>