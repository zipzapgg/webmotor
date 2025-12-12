<?php
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized');
}

$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Query data
$detail_transaksi = $conn->query("
    SELECT t.*, p.nama as nama_penyewa, p.no_telepon, m.nama_motor, m.plat_nomor
    FROM transaksi_sewa t
    JOIN penyewa p ON t.id_penyewa = p.id_penyewa
    JOIN motor m ON t.id_motor = m.id_motor
    WHERE DATE_FORMAT(t.tanggal_sewa, '%Y-%m') = '$bulan'
    ORDER BY t.tanggal_sewa DESC
");

$pendapatan_total = 0;

// Set headers untuk download Excel
$filename = "Laporan_Rental_Motor_" . $bulan . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Output Excel content
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th {
            background-color: #FF6B35;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 8px;
            border: 1px solid #ddd;
        }
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .total-row {
            background-color: #fff3cd;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN TRANSAKSI RENTAL MOTOR</h2>
        <p>Periode: <?= date('F Y', strtotime($bulan . '-01')) ?></p>
        <p>Dicetak: <?= date('d F Y H:i') ?> WIB</p>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>ID Transaksi</th>
                <th>Nama Penyewa</th>
                <th>No. Telepon</th>
                <th>Motor</th>
                <th>Plat Nomor</th>
                <th>Tanggal Sewa</th>
                <th>Tanggal Kembali</th>
                <th>Lama Sewa (Hari)</th>
                <th>Total Bayar</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while($row = $detail_transaksi->fetch_assoc()): 
                $pendapatan_total += $row['total_bayar'];
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $row['id_transaksi'] ?></td>
                <td><?= $row['nama_penyewa'] ?></td>
                <td><?= $row['no_telepon'] ?></td>
                <td><?= $row['nama_motor'] ?></td>
                <td><?= $row['plat_nomor'] ?></td>
                <td><?= date('d/m/Y', strtotime($row['tanggal_sewa'])) ?></td>
                <td><?= date('d/m/Y', strtotime($row['tanggal_kembali'])) ?></td>
                <td><?= $row['lama_sewa'] ?></td>
                <td><?= $row['total_bayar'] ?></td>
                <td><?= $row['status'] ?></td>
            </tr>
            <?php endwhile; ?>
            <tr class="total-row">
                <td colspan="9" style="text-align: right; font-weight: bold;">TOTAL PENDAPATAN</td>
                <td style="font-weight: bold;"><?= $pendapatan_total ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <br><br>
    <table border="0">
        <tr>
            <td><strong>Total Transaksi:</strong></td>
            <td><?= $no - 1 ?> transaksi</td>
        </tr>
        <tr>
            <td><strong>Total Pendapatan:</strong></td>
            <td>Rp <?= number_format($pendapatan_total, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td><strong>Rata-rata per Transaksi:</strong></td>
            <td>Rp <?= number_format($pendapatan_total / ($no - 1), 0, ',', '.') ?></td>
        </tr>
    </table>
</body>
</html>