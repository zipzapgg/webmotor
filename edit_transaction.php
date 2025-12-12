<?php
include 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    set_message('error', '❌ Anda tidak memiliki izin untuk mengakses halaman ini.');
    redirect('index.php');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('transactions.php');
}
$id_transaksi = intval($_GET['id']);

// Get transaction data
$stmt_get = $conn->prepare("
    SELECT t.*, p.nama as nama_penyewa, m.nama_motor, m.harga_sewa_perhari 
    FROM transaksi_sewa t 
    JOIN penyewa p ON t.id_penyewa = p.id_penyewa 
    JOIN motor m ON t.id_motor = m.id_motor 
    WHERE t.id_transaksi = ?
");
$stmt_get->bind_param("i", $id_transaksi);
$stmt_get->execute();
$transaksi = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$transaksi) {
    set_message('error', '❌ Data transaksi tidak ditemukan.');
    redirect('transactions.php');
}

// Update transaction
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_transaction'])) {
    $tanggal_sewa = $_POST['tanggal_sewa'];
    $lama_sewa = intval($_POST['lama_sewa']);
    
    // Calculate new return date and total
    $tanggal_kembali = date('Y-m-d', strtotime($tanggal_sewa . " +$lama_sewa days"));
    $total_bayar = $transaksi['harga_sewa_perhari'] * $lama_sewa;
    
    $stmt_update = $conn->prepare("
        UPDATE transaksi_sewa 
        SET tanggal_sewa = ?, tanggal_kembali = ?, lama_sewa = ?, total_bayar = ?
        WHERE id_transaksi = ?
    ");
    $stmt_update->bind_param("ssidi", $tanggal_sewa, $tanggal_kembali, $lama_sewa, $total_bayar, $id_transaksi);

    if ($stmt_update->execute()) {
        set_message('success', '✅ Data transaksi berhasil diperbarui.');
        redirect('transactions.php');
    } else {
        set_message('error', '❌ Gagal memperbarui data transaksi: ' . $stmt_update->error);
    }
    $stmt_update->close();
}

include 'header.php';
?>

<div class="card">
    <h2 class="mt-0">Edit Transaksi Sewa</h2>
    <p>Perbarui informasi transaksi di bawah ini.</p>
</div>

<div class="card">
    <h3 class="mt-0">Informasi Transaksi</h3>
    
    <div style="background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(247, 147, 30, 0.1) 100%); padding: 20px; border-radius: 12px; margin-bottom: 24px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
                <strong style="color: var(--text-secondary); display: block; margin-bottom: 4px;">Penyewa:</strong>
                <span style="font-size: 1.1rem; font-weight: 600;"><?= htmlspecialchars($transaksi['nama_penyewa']) ?></span>
            </div>
            <div>
                <strong style="color: var(--text-secondary); display: block; margin-bottom: 4px;">Motor:</strong>
                <span style="font-size: 1.1rem; font-weight: 600;"><?= htmlspecialchars($transaksi['nama_motor']) ?></span>
            </div>
            <div>
                <strong style="color: var(--text-secondary); display: block; margin-bottom: 4px;">Harga Sewa/Hari:</strong>
                <span style="font-size: 1.1rem; font-weight: 600; color: var(--primary);"><?= format_rupiah($transaksi['harga_sewa_perhari']) ?></span>
            </div>
            <div>
                <strong style="color: var(--text-secondary); display: block; margin-bottom: 4px;">Status:</strong>
                <span class="motor-status <?= strtolower($transaksi['status']) == 'aktif' ? 'tersedia' : 'maintenance' ?>">
                    <?= htmlspecialchars($transaksi['status']) ?>
                </span>
            </div>
        </div>
    </div>

    <form method="POST" action="edit_transaction.php?id=<?= $id_transaksi ?>" id="editTransactionForm">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Tanggal Sewa</label>
                <input type="date" name="tanggal_sewa" id="tanggal_sewa" class="form-control" 
                       value="<?= $transaksi['tanggal_sewa'] ?>" required>
            </div>
            
            <div class="form-group">
                <label>Lama Sewa (Hari)</label>
                <input type="number" name="lama_sewa" id="lama_sewa" class="form-control" 
                       min="1" value="<?= $transaksi['lama_sewa'] ?>" required>
            </div>
        </div>
        
        <div style="background: #f7fafc; padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 2px solid var(--border);">
            <h4 style="margin-top: 0; margin-bottom: 16px; color: var(--text-primary);">📊 Kalkulasi Otomatis</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div>
                    <strong style="color: var(--text-secondary); display: block; margin-bottom: 4px; font-size: 0.9rem;">Tanggal Kembali:</strong>
                    <span id="display_tanggal_kembali" style="font-size: 1.1rem; font-weight: 600; color: var(--primary);">
                        <?= date('d M Y', strtotime($transaksi['tanggal_kembali'])) ?>
                    </span>
                </div>
                <div>
                    <strong style="color: var(--text-secondary); display: block; margin-bottom: 4px; font-size: 0.9rem;">Total Bayar:</strong>
                    <span id="display_total_bayar" style="font-size: 1.1rem; font-weight: 600; color: var(--success);">
                        <?= format_rupiah($transaksi['total_bayar']) ?>
                    </span>
                </div>
                <div>
                    <strong style="color: var(--text-secondary); display: block; margin-bottom: 4px; font-size: 0.9rem;">Perubahan:</strong>
                    <span id="display_perubahan" style="font-size: 1.1rem; font-weight: 600; color: var(--text-secondary);">
                        Tidak ada
                    </span>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="update_transaction" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
            <a href="transactions.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Batal
            </a>
        </div>
    </form>
</div>

<script>
const hargaPerHari = <?= $transaksi['harga_sewa_perhari'] ?>;
const totalAwal = <?= $transaksi['total_bayar'] ?>;

function formatRupiah(angka) {
    return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function formatTanggal(dateString) {
    const options = { day: '2-digit', month: 'short', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}

function updateKalkulasi() {
    const tanggalSewa = document.getElementById('tanggal_sewa').value;
    const lamaSewa = parseInt(document.getElementById('lama_sewa').value) || 0;
    
    if (tanggalSewa && lamaSewa > 0) {
        // Calculate return date
        const tglSewa = new Date(tanggalSewa);
        const tglKembali = new Date(tglSewa);
        tglKembali.setDate(tglKembali.getDate() + lamaSewa);
        
        // Calculate total
        const totalBayar = hargaPerHari * lamaSewa;
        const selisih = totalBayar - totalAwal;
        
        // Update display
        document.getElementById('display_tanggal_kembali').textContent = formatTanggal(tglKembali);
        document.getElementById('display_total_bayar').textContent = formatRupiah(totalBayar);
        
        // Update perubahan
        const perubahanEl = document.getElementById('display_perubahan');
        if (selisih > 0) {
            perubahanEl.textContent = '+' + formatRupiah(selisih);
            perubahanEl.style.color = 'var(--success)';
        } else if (selisih < 0) {
            perubahanEl.textContent = formatRupiah(selisih);
            perubahanEl.style.color = 'var(--danger)';
        } else {
            perubahanEl.textContent = 'Tidak ada';
            perubahanEl.style.color = 'var(--text-secondary)';
        }
    }
}

// Event listeners
document.getElementById('tanggal_sewa').addEventListener('change', updateKalkulasi);
document.getElementById('lama_sewa').addEventListener('input', updateKalkulasi);

// Konfirmasi sebelum submit
document.getElementById('editTransactionForm').addEventListener('submit', function(e) {
    if (!confirm('Yakin ingin menyimpan perubahan transaksi ini?')) {
        e.preventDefault();
    }
});
</script>

<?php include 'footer.php'; ?>