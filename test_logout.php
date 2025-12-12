<?php
/**
 * LOGOUT DEBUG TOOL
 * Akses: http://localhost/rental_motor/test_logout.php
 * Gunakan untuk debug masalah logout
 */

session_start();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Logout Debug Tool</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1A1A2E;
            margin-bottom: 30px;
        }
        .section {
            margin: 25px 0;
            padding: 20px;
            background: #f7fafc;
            border-radius: 12px;
            border-left: 4px solid #FF6B35;
        }
        .section h3 {
            color: #FF6B35;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #e2e8f0;
        }
        th {
            background: #2D3748;
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background: #f7fafc;
        }
        .status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }
        .status.success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
        .status.error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            margin: 5px;
        }
        .btn-danger {
            background: linear-gradient(135deg, #EF476F, #D63355);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }
        .btn-primary {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #06D6A0, #00B894);
            color: white;
            box-shadow: 0 4px 15px rgba(6, 214, 160, 0.3);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin: 15px 0;
            font-weight: 600;
        }
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 2px solid #6ee7b7;
        }
        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border: 2px solid #fca5a5;
        }
        pre {
            background: #2D3748;
            color: #E2E8F0;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Logout Debug Tool</h1>
        
        <div class="section">
            <h3>1️⃣ Session Status</h3>
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <div class="alert alert-success">
                    ✅ <strong>User Sedang Login</strong>
                </div>
                <table>
                    <tr>
                        <th>Variable</th>
                        <th>Value</th>
                        <th>Status</th>
                    </tr>
                    <tr>
                        <td><strong>loggedin</strong></td>
                        <td><?= $_SESSION['loggedin'] ? 'true' : 'false' ?></td>
                        <td><span class="status success">✅ Valid</span></td>
                    </tr>
                    <tr>
                        <td><strong>id_user</strong></td>
                        <td><?= isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 'Not Set' ?></td>
                        <td><?= isset($_SESSION['id_user']) ? '<span class="status success">✅ Valid</span>' : '<span class="status error">❌ Missing</span>' ?></td>
                    </tr>
                    <tr>
                        <td><strong>username</strong></td>
                        <td><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Not Set' ?></td>
                        <td><?= isset($_SESSION['username']) ? '<span class="status success">✅ Valid</span>' : '<span class="status error">❌ Missing</span>' ?></td>
                    </tr>
                    <tr>
                        <td><strong>nama_lengkap</strong></td>
                        <td><?= isset($_SESSION['nama_lengkap']) ? htmlspecialchars($_SESSION['nama_lengkap']) : 'Not Set' ?></td>
                        <td><?= isset($_SESSION['nama_lengkap']) ? '<span class="status success">✅ Valid</span>' : '<span class="status error">❌ Missing</span>' ?></td>
                    </tr>
                    <tr>
                        <td><strong>role</strong></td>
                        <td><?= isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Not Set' ?></td>
                        <td><?= isset($_SESSION['role']) ? '<span class="status success">✅ Valid</span>' : '<span class="status error">❌ Missing</span>' ?></td>
                    </tr>
                    <tr>
                        <td><strong>Session ID</strong></td>
                        <td><?= session_id() ?></td>
                        <td><span class="status success">✅ Active</span></td>
                    </tr>
                </table>
            <?php else: ?>
                <div class="alert alert-error">
                    ❌ <strong>User Tidak Login / Session Tidak Ada</strong>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h3>2️⃣ All Session Data</h3>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
        
        <div class="section">
            <h3>3️⃣ Session Configuration</h3>
            <table>
                <tr>
                    <th>Setting</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>session.name</td>
                    <td><?= session_name() ?></td>
                </tr>
                <tr>
                    <td>session.save_path</td>
                    <td><?= session_save_path() ?></td>
                </tr>
                <tr>
                    <td>session.use_cookies</td>
                    <td><?= ini_get('session.use_cookies') ? 'Enabled' : 'Disabled' ?></td>
                </tr>
                <tr>
                    <td>session.cookie_httponly</td>
                    <td><?= ini_get('session.cookie_httponly') ? 'Enabled' : 'Disabled' ?></td>
                </tr>
                <tr>
                    <td>session.gc_maxlifetime</td>
                    <td><?= ini_get('session.gc_maxlifetime') ?> seconds (<?= round(ini_get('session.gc_maxlifetime')/60) ?> minutes)</td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <h3>4️⃣ Test Logout Methods</h3>
            
            <div style="margin: 20px 0;">
                <h4>Method 1: Standard Logout (Recommended)</h4>
                <p>Menggunakan logout.php yang sudah diperbaiki</p>
                <a href="logout.php" class="btn btn-danger">🚪 Test Logout</a>
            </div>
            
            <div style="margin: 20px 0;">
                <h4>Method 2: Force Logout (Debug)</h4>
                <p>Logout dengan force destroy + redirect</p>
                <a href="?action=force_logout" class="btn btn-danger">💥 Force Logout</a>
            </div>
            
            <div style="margin: 20px 0;">
                <h4>Method 3: Clear Session Only</h4>
                <p>Hapus session tapi tetap di halaman ini</p>
                <a href="?action=clear_session" class="btn btn-danger">🗑️ Clear Session</a>
            </div>
        </div>
        
        <?php
        // Handle actions
        if (isset($_GET['action'])) {
            if ($_GET['action'] == 'force_logout') {
                $_SESSION = array();
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                session_destroy();
                
                // Force redirect with JavaScript as backup
                echo '<script>
                    alert("✅ Session destroyed! Redirecting to login...");
                    window.location.href = "login.php";
                </script>';
                
                header("Location: login.php");
                exit();
            }
            
            if ($_GET['action'] == 'clear_session') {
                $_SESSION = array();
                echo '<div class="alert alert-success">✅ Session cleared! Refresh halaman untuk lihat hasilnya.</div>';
            }
        }
        ?>
        
        <div class="section">
            <h3>5️⃣ Quick Actions</h3>
            <a href="index.php" class="btn btn-primary">🏠 Back to Dashboard</a>
            <a href="login.php" class="btn btn-success">🔐 Go to Login</a>
            <a href="?refresh=1" class="btn btn-primary">🔄 Refresh</a>
        </div>
        
        <div class="section" style="background: linear-gradient(135deg, rgba(6, 214, 160, 0.1), rgba(0, 184, 148, 0.1)); border-left-color: #06D6A0;">
            <h3 style="color: #06D6A0;">💡 Troubleshooting Tips:</h3>
            <ol style="line-height: 2; color: #065f46;">
                <li>Jika logout tidak work, coba <strong>Method 2: Force Logout</strong></li>
                <li>Clear browser cache (Ctrl+Shift+Del)</li>
                <li>Coba di Incognito/Private mode</li>
                <li>Pastikan file <code>logout.php</code> sudah di-update dengan versi yang baru</li>
                <li>Check browser console (F12) untuk error JavaScript</li>
                <li>Pastikan <code>auth_check.php</code> di-include di semua halaman</li>
            </ol>
        </div>
    </div>
</body>
</html>