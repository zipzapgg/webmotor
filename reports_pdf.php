<?php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized');
}

$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Query data
$pendapatan_bulan = $conn->query("
    SELECT COALESCE(SUM(total_bayar), 0) as total 
    FROM transaksi_sewa 
    WHERE DATE_FORMAT(tanggal_sewa, '%Y-%m') = '$bulan'
")->fetch_assoc()['total'];

$transaksi_bulan = $conn->query("
    SELECT COUNT(*) as total 
    FROM transaksi_sewa 
    WHERE DATE_FORMAT(tanggal_sewa, '%Y-%m') = '$bulan'
")->fetch_assoc()['total'];

$detail_transaksi = $conn->query("
    SELECT t.*, p.nama as nama_penyewa, m.nama_motor, m.plat_nomor
    FROM transaksi_sewa t
    JOIN penyewa p ON t.id_penyewa = p.id_penyewa
    JOIN motor m ON t.id_motor = m.id_motor
    WHERE DATE_FORMAT(t.tanggal_sewa, '%Y-%m') = '$bulan'
    ORDER BY t.tanggal_sewa DESC
");

// Generate HTML untuk PDF
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Rental Motor - <?= date('F Y', strtotime($bulan . '-01')) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #FF6B35;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #FF6B35;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 14px;
        }
        .summary {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .summary-item {
            display: table-cell;
            width: 50%;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .summary-item h3 {
            color: #FF6B35;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .summary-item .value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #FF6B35;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
            font-size: 11px;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        .signature {
            margin-top: 60px;
            text-align: right;
        }
        .signature-line {
            display: inline-block;
            border-top: 1px solid #333;
            padding-top: 5px;
            margin-top: 50px;
            min-width: 200px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏍️ RENTAL MOTOR</h1>
        <p>Laporan Transaksi Periode <?= date('F Y', strtotime($bulan . '-01')) ?></p>
        <p style="font-size: 11px;">Dicetak pada: <?= date('d F Y, H:i') ?> WIB</p>
    </div>

    <div class="summary">
        <div class="summary-item">
            <h3>💰 Total Pendapatan</h3>
            <div class="value"><?= format_rupiah($pendapatan_bulan) ?></div>
        </div>
        <div class="summary-item">
            <h3>📊 Jumlah Transaksi</h3>
            <div class="value"><?= $transaksi_bulan ?> Transaksi</div>
        </div>
    </div>

    <h2 style="color: #FF6B35; font-size: 16px; margin-bottom: 10px;">Detail Transaksi</h2>
    
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Penyewa</th>
                <th width="20%">Motor</th>
                <th width="15%">Tanggal Sewa</th>
                <th width="15%">Tanggal Kembali</th>
                <th width="10%">Lama</th>
                <th width="15%">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $total_keseluruhan = 0;
            while($tr = $detail_transaksi->fetch_assoc()): 
                $total_keseluruhan += $tr['total_bayar'];
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= safe_output($tr['nama_penyewa']) ?></td>
                <td><?= safe_output($tr['nama_motor']) ?><br><small><?= safe_output($tr['plat_nomor']) ?></small></td>
                <td><?= date('d/m/Y', strtotime($tr['tanggal_sewa'])) ?></td>
                <td><?= date('d/m/Y', strtotime($tr['tanggal_kembali'])) ?></td>
                <td><?= $tr['lama_sewa'] ?> hari</td>
                <td><strong><?= format_rupiah($tr['total_bayar']) ?></strong></td>
            </tr>
            <?php endwhile; ?>
            <tr style="background: #fff3cd; font-weight: bold;">
                <td colspan="6" style="text-align: right; padding-right: 10px;">TOTAL</td>
                <td><?= format_rupiah($total_keseluruhan) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="signature">
        <p>Jakarta, <?= date('d F Y') ?></p>
        <p style="margin-bottom: 80px;">Manager</p>
        <div class="signature-line">
            <strong>(__________________)</strong>
        </div>
    </div>

    <div class="footer">
        <p>Laporan ini digenerate otomatis oleh Sistem Rental Motor</p>
        <p>© <?= date('Y') ?> Rental Motor. All Rights Reserved.</p>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Untuk simple PDF, kita gunakan browser print to PDF
// Atau install library TCPDF/DOMPDF untuk PHP native

// Simple solution: Output HTML yang bisa di-print
header('Content-Type: text/html; charset=UTF-8');
echo $html;
echo '<script>window.print();</script>';

// ===== ALTERNATIVE: Install TCPDF =====
// Uncomment code di bawah jika sudah install TCPDF
/*
require_once('tcpdf/tcpdf.php');

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->SetCreator('Rental Motor');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Laporan Transaksi ' . date('F Y', strtotime($bulan . '-01')));

$pdf->AddPage();
$pdf->writeHTML($html, true, false, true, false, '');

$filename = 'Laporan_' . $bulan . '.pdf';
$pdf->Output($filename, 'I'); // I = inline, D = download
*/
?>