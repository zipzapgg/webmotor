<?php
include 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    set_message('error', '❌ Anda tidak memiliki izin untuk mengakses halaman ini.');
    redirect('index.php');
}

// Filter parameter
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// ========== STATISTIK UMUM ==========
$total_pendapatan_all = $conn->query("SELECT COALESCE(SUM(total_bayar), 0) as total FROM transaksi_sewa")->fetch_assoc()['total'];
$total_transaksi = $conn->query("SELECT COUNT(*) as total FROM transaksi_sewa")->fetch_assoc()['total'];
$transaksi_aktif = $conn->query("SELECT COUNT(*) as total FROM transaksi_sewa WHERE status = 'Aktif'")->fetch_assoc()['total'];
$transaksi_selesai = $conn->query("SELECT COUNT(*) as total FROM transaksi_sewa WHERE status = 'Selesai'")->fetch_assoc()['total'];

// ========== PENDAPATAN BULANAN ==========
$pendapatan_bulan_ini = $conn->query("
    SELECT COALESCE(SUM(total_bayar), 0) as total 
    FROM transaksi_sewa 
    WHERE DATE_FORMAT(tanggal_sewa, '%Y-%m') = '$bulan'
")->fetch_assoc()['total'];

$transaksi_bulan_ini = $conn->query("
    SELECT COUNT(*) as total 
    FROM transaksi_sewa 
    WHERE DATE_FORMAT(tanggal_sewa, '%Y-%m') = '$bulan'
")->fetch_assoc()['total'];

// ========== MOTOR TERLARIS ==========
$motor_terlaris = $conn->query("
    SELECT m.nama_motor, m.plat_nomor, COUNT(t.id_transaksi) as jumlah_sewa, 
           COALESCE(SUM(t.total_bayar), 0) as total_pendapatan
    FROM motor m
    LEFT JOIN transaksi_sewa t ON m.id_motor = t.id_motor
    GROUP BY m.id_motor
    ORDER BY jumlah_sewa DESC
    LIMIT 10
");

// ========== PENYEWA TERBANYAK ==========
$penyewa_terbanyak = $conn->query("
    SELECT p.nama, p.no_telepon, COUNT(t.id_transaksi) as jumlah_sewa,
           COALESCE(SUM(t.total_bayar), 0) as total_bayar
    FROM penyewa p
    LEFT JOIN transaksi_sewa t ON p.id_penyewa = t.id_penyewa
    GROUP BY p.id_penyewa
    ORDER BY jumlah_sewa DESC
    LIMIT 10
");

// ========== PENDAPATAN PER BULAN (12 BULAN TERAKHIR) ==========
$pendapatan_per_bulan = $conn->query("
    SELECT DATE_FORMAT(tanggal_sewa, '%Y-%m') as bulan,
           COUNT(*) as jumlah_transaksi,
           COALESCE(SUM(total_bayar), 0) as total_pendapatan
    FROM transaksi_sewa
    WHERE tanggal_sewa >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(tanggal_sewa, '%Y-%m')
    ORDER BY bulan DESC
");

// ========== TRANSAKSI TERLAMBAT ==========
$transaksi_terlambat = $conn->query("
    SELECT t.*, p.nama as nama_penyewa, m.nama_motor, m.plat_nomor,
           DATEDIFF(CURDATE(), t.tanggal_kembali) as hari_terlambat
    FROM transaksi_sewa t
    JOIN penyewa p ON t.id_penyewa = p.id_penyewa
    JOIN motor m ON t.id_motor = m.id_motor
    WHERE t.status = 'Aktif' AND t.tanggal_kembali < CURDATE()
    ORDER BY hari_terlambat DESC
");

include 'header.php';
?>

<div class="card card-header">
    <h1>📊 Laporan & Statistik</h1>
    <p>Dashboard laporan lengkap rental motor</p>
</div>

<!-- STATISTIK CARDS -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div>
                <p style="color: rgba(255,255,255,0.9); margin: 0 0 8px 0; font-size: 0.9rem;">Total Pendapatan</p>
                <h2 style="color: white; margin: 0; font-size: 1.8rem;"><?= format_rupiah($total_pendapatan_all) ?></h2>
            </div>
            <i class="fas fa-money-bill-wave" style="font-size: 2.5rem; opacity: 0.3;"></i>
        </div>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div>
                <p style="color: rgba(255,255,255,0.9); margin: 0 0 8px 0; font-size: 0.9rem;">Total Transaksi</p>
                <h2 style="color: white; margin: 0; font-size: 1.8rem;"><?= $total_transaksi ?></h2>
            </div>
            <i class="fas fa-exchange-alt" style="font-size: 2.5rem; opacity: 0.3;"></i>
        </div>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div>
                <p style="color: rgba(255,255,255,0.9); margin: 0 0 8px 0; font-size: 0.9rem;">Sewa Aktif</p>
                <h2 style="color: white; margin: 0; font-size: 1.8rem;"><?= $transaksi_aktif ?></h2>
            </div>
            <i class="fas fa-clock" style="font-size: 2.5rem; opacity: 0.3;"></i>
        </div>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white; border: none;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div>
                <p style="color: rgba(255,255,255,0.9); margin: 0 0 8px 0; font-size: 0.9rem;">Selesai</p>
                <h2 style="color: white; margin: 0; font-size: 1.8rem;"><?= $transaksi_selesai ?></h2>
            </div>
            <i class="fas fa-check-circle" style="font-size: 2.5rem; opacity: 0.3;"></i>
        </div>
    </div>
</div>

<!-- FILTER BULAN -->
<div class="card">
    <h3 class="mt-0">🔍 Filter Laporan</h3>
    <form method="GET" action="reports.php" style="display: flex; gap: 12px; align-items: end; flex-wrap: wrap;">
        <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
            <label>Pilih Bulan</label>
            <input type="month" name="bulan" class="form-control" value="<?= $bulan ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="margin-bottom: 0;">
            <i class="fas fa-filter"></i> Tampilkan
        </button>
        <a href="export_pdf.php?bulan=<?= $bulan ?>" class="btn btn-danger" style="margin-bottom: 0;" target="_blank">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
        <a href="export_excel.php?bulan=<?= $bulan ?>" class="btn btn-success" style="margin-bottom: 0;">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
    </form>
</div>

<!-- PENDAPATAN BULAN INI -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card">
        <h3 class="mt-0">💰 Pendapatan Bulan <?= date('F Y', strtotime($bulan)) ?></h3>
        <div style="text-align: center; padding: 20px;">
            <p style="font-size: 2.5rem; font-weight: 900; background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0;">
                <?= format_rupiah($pendapatan_bulan_ini) ?>
            </p>
            <p style="color: var(--text-secondary); margin-top: 8px;">Dari <?= $transaksi_bulan_ini ?> transaksi</p>
        </div>
    </div>
    
    <div class="card">
        <h3 class="mt-0">📈 Rata-rata per Transaksi</h3>
        <div style="text-align: center; padding: 20px;">
            <p style="font-size: 2.5rem; font-weight: 900; background: var(--gradient-success); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0;">
                <?= $transaksi_bulan_ini > 0 ? format_rupiah($pendapatan_bulan_ini / $transaksi_bulan_ini) : 'Rp 0' ?>
            </p>
            <p style="color: var(--text-secondary); margin-top: 8px;">Per transaksi bulan ini</p>
        </div>
    </div>
</div>

<!-- TRANSAKSI TERLAMBAT -->
<?php if ($transaksi_terlambat->num_rows > 0): ?>
<div class="card">
    <h3 class="mt-0" style="color: var(--danger);">⚠️ Motor Terlambat Dikembalikan</h3>
    <table>
        <thead>
            <tr>
                <th>Penyewa</th>
                <th>Motor</th>
                <th>Tgl Seharusnya Kembali</th>
                <th>Terlambat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while($tl = $transaksi_terlambat->fetch_assoc()): ?>
            <tr style="background: linear-gradient(135deg, rgba(239, 68, 111, 0.1) 0%, rgba(214, 51, 85, 0.1) 100%);">
                <td><strong><?= safe_output($tl['nama_penyewa']) ?></strong></td>
                <td><?= safe_output($tl['nama_motor']) ?> - <?= safe_output($tl['plat_nomor']) ?></td>
                <td><?= date('d M Y', strtotime($tl['tanggal_kembali'])) ?></td>
                <td>
                    <span class="motor-status maintenance">
                        <i class="fas fa-exclamation-triangle"></i> <?= $tl['hari_terlambat'] ?> hari
                    </span>
                </td>
                <td>
                    <a href="transactions.php" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem;">
                        <i class="fas fa-eye"></i> Lihat
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- MOTOR TERLARIS -->
<div class="card">
    <h3 class="mt-0">🏆 Motor Terlaris</h3>
    <table>
        <thead>
            <tr>
                <th>Motor</th>
                <th>Plat Nomor</th>
                <th>Jumlah Sewa</th>
                <th>Total Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            <?php while($mt = $motor_terlaris->fetch_assoc()): ?>
            <tr>
                <td><strong><?= safe_output($mt['nama_motor']) ?></strong></td>
                <td><?= safe_output($mt['plat_nomor']) ?></td>
                <td><?= $mt['jumlah_sewa'] ?> kali</td>
                <td><?= format_rupiah($mt['total_pendapatan']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- PENYEWA TERBANYAK -->
<div class="card">
    <h3 class="mt-0">👥 Penyewa Terbanyak</h3>
    <table>
        <thead>
            <tr>
                <th>Nama Penyewa</th>
                <th>No. Telepon</th>
                <th>Jumlah Sewa</th>
                <th>Total Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            <?php while($pt = $penyewa_terbanyak->fetch_assoc()): ?>
            <tr>
                <td><strong><?= safe_output($pt['nama']) ?></strong></td>
                <td><?= safe_output($pt['no_telepon']) ?></td>
                <td><?= $pt['jumlah_sewa'] ?> kali</td>
                <td><?= format_rupiah($pt['total_bayar']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- PENDAPATAN 12 BULAN TERAKHIR -->
<div class="card">
    <h3 class="mt-0">📅 Pendapatan 12 Bulan Terakhir</h3>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Jumlah Transaksi</th>
                    <th>Total Pendapatan</th>
                    <th>Rata-rata/Transaksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($pm = $pendapatan_per_bulan->fetch_assoc()): 
                    $rata_rata = $pm['jumlah_transaksi'] > 0 ? $pm['total_pendapatan'] / $pm['jumlah_transaksi'] : 0;
                ?>
                <tr>
                    <td><strong><?= date('F Y', strtotime($pm['bulan'] . '-01')) ?></strong></td>
                    <td><?= $pm['jumlah_transaksi'] ?> transaksi</td>
                    <td><strong style="color: var(--primary);"><?= format_rupiah($pm['total_pendapatan']) ?></strong></td>
                    <td><?= format_rupiah($rata_rata) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Auto refresh setiap 5 menit untuk data real-time
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<?php include 'footer.php'; ?>