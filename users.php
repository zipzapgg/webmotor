<?php
include 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    set_message('error', '❌ Anda tidak memiliki izin untuk mengakses halaman ini.');
    redirect('index.php');
}

if (isset($_GET['delete_id'])) {
    $id_user_hapus = intval($_GET['delete_id']);
    
    if ($id_user_hapus === $_SESSION['id_user']) {
        set_message('error', '❌ Anda tidak dapat menghapus akun Anda sendiri.');
    } else {
        $stmt = $conn->prepare("DELETE FROM user_admin WHERE id_user = ?");
        $stmt->bind_param("i", $id_user_hapus);
        if ($stmt->execute()) {
            set_message('success', '🗑️ Pengguna berhasil dihapus.');
        } else {
            set_message('error', '❌ Gagal menghapus pengguna.');
        }
        $stmt->close();
    }
    redirect('users.php');
}

include 'header.php';
?>

<div class="card">
    <h2 class="mt-0">Manajemen Akun</h2>
    <p>Kelola semua akun yang terdaftar di sistem, baik admin maupun user (penyewa).</p>
</div>

<div class="card">
    <h3 class="mt-0">Daftar Semua Pengguna</h3>
    <table>
        <thead>
            <tr>
                <th>Nama Lengkap</th>
                <th>Username</th>
                <th>Role</th>
                <th>Tgl Daftar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT id_user, nama_lengkap, username, role, created_at FROM user_admin ORDER BY created_at DESC";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    $role_class = $row['role'] == 'admin' ? 'maintenance' : 'tersedia';
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td>
                    <span class="motor-status <?= $role_class ?>">
                        <?= $row['role'] == 'admin' ? '👑 Admin' : '👤 User' ?>
                    </span>
                </td>
                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                <td>
                    <div class="form-actions" style="margin: 0;">
                        <a href="edit_user.php?id=<?= $row['id_user'] ?>" 
                           class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem;">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <?php if ($row['id_user'] != $_SESSION['id_user']): ?>
                            <a href="users.php?delete_id=<?= $row['id_user'] ?>" 
                               class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;"
                               onclick="return confirm('Yakin ingin menghapus pengguna ini?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php 
                endwhile; 
            else: 
            ?>
            <tr>
                <td colspan="5" class="text-center">Belum ada pengguna terdaftar.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3 class="mt-0">ℹ️ Informasi</h3>
    <ul style="line-height: 2; color: var(--text-secondary);">
        <li>Pengguna baru dapat mendaftar sendiri melalui halaman <strong>Register</strong>.</li>
        <li>Semua pengguna yang mendaftar sendiri akan otomatis memiliki role <strong>User</strong>.</li>
        <li>Admin dapat mengubah role pengguna melalui menu <strong>Edit</strong>.</li>
        <li>Anda tidak dapat menghapus akun Anda sendiri yang sedang login.</li>
    </ul>
</div>

<?php include 'footer.php'; ?>