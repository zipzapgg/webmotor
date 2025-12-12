<?php
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['username'])) {
    header('Content-Type: application/json');
    
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    
    // Check rate limiting
    $rate_check = check_rate_limit($_SERVER['REMOTE_ADDR'], 5, 300);
    if (is_array($rate_check) && !$rate_check['success']) {
        echo json_encode(['success' => false, 'message' => $rate_check['message']]);
        exit;
    }

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username dan password tidak boleh kosong.']);
        exit;
    }
    
    // Validate username format
    if (!validate_username($username)) {
        echo json_encode(['success' => false, 'message' => 'Format username tidak valid.']);
        exit;
    }
    
    // Default admin login
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION["loggedin"] = true;
        $_SESSION["id_user"] = 0;
        $_SESSION["username"] = 'admin';
        $_SESSION["nama_lengkap"] = 'Admin Utama';
        $_SESSION["role"] = 'admin';
        
        // Log activity
        log_activity(0, 'LOGIN', 'Admin login sukses');
        
        // Reset rate limit on success
        unset($_SESSION['rate_limit'][$_SERVER['REMOTE_ADDR']]);
        
        echo json_encode(['success' => true]);
        exit;
    }

    // Database login
    $sql = "SELECT id_user, username, password, nama_lengkap, role FROM user_admin WHERE username = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $username_db, $hashed_password, $nama_lengkap, $role);
                if ($stmt->fetch()) {
                    if (password_verify($password, $hashed_password)) {
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id_user"] = $id;
                        $_SESSION["username"] = $username_db;
                        $_SESSION["nama_lengkap"] = $nama_lengkap;
                        $_SESSION["role"] = $role;
                        
                        // Log activity
                        log_activity($id, 'LOGIN', "User $username_db login sukses");
                        
                        // Reset rate limit on success
                        unset($_SESSION['rate_limit'][$_SERVER['REMOTE_ADDR']]);
                        
                        echo json_encode(['success' => true]);
                        exit;
                    }
                }
            }
        }
        $stmt->close();
    }
    
    // Failed login - log it
    log_activity(0, 'LOGIN_FAILED', "Failed login attempt for username: $username from IP: " . $_SERVER['REMOTE_ADDR']);
    
    echo json_encode(['success' => false, 'message' => 'Username atau password salah.']);
    exit;
}

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rental Motor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link href="login-style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-brand">
            <div class="brand-icon">
                <i class="fas fa-motorcycle"></i>
            </div>
            <h1>Rental Motor</h1>
            <p>Sistem Manajemen Penyewaan Motor</p>
            
            <div class="login-tip">
                <strong>Login Admin:</strong>
                Username: <code>admin</code><br>
                Password: <code>admin123</code>
            </div>
        </div>
        
        <div class="login-form">
            <div class="form-header">
                <h2>Selamat Datang</h2>
                <p>Silakan login untuk melanjutkan</p>
            </div>
            
            <div id="error-alert" class="alert-danger"></div>
            
            <form id="loginForm" method="post">
                <div class="form-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" id="username" class="form-control" 
                           placeholder="Username" required maxlength="20" pattern="[a-zA-Z0-9_]{3,20}">
                </div>
                
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-control" 
                           placeholder="Password" required minlength="6">
                </div>
                
                <div style="text-align: right; margin-bottom: 20px;">
                    <a href="forgot_password.php" style="color: var(--primary); text-decoration: none; font-size: 0.9rem; font-weight: 500;">
                        <i class="fas fa-key"></i> Lupa Password?
                    </a>
                </div>
                
                <button type="submit" class="btn-primary" id="login-btn">
                    <span class="loading-spinner" id="loading-spinner"></span>
                    <span id="btn-text">Login</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="register-link">
                <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const loginBtn = document.getElementById('login-btn');
            const btnText = document.getElementById('btn-text');
            const spinner = document.getElementById('loading-spinner');
            const errorAlert = document.getElementById('error-alert');
            
            loginBtn.disabled = true;
            spinner.style.display = 'inline-block';
            btnText.textContent = 'Memproses...';
            errorAlert.style.display = 'none';

            const formData = new FormData(this);
            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btnText.textContent = 'Sukses!';
                    window.location.href = 'index.php';
                } else {
                    errorAlert.textContent = data.message;
                    errorAlert.style.display = 'flex';
                    loginBtn.disabled = false;
                    spinner.style.display = 'none';
                    btnText.textContent = 'Login';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorAlert.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
                errorAlert.style.display = 'flex';
                loginBtn.disabled = false;
                spinner.style.display = 'none';
                btnText.textContent = 'Login';
            });
        });
    </script>
</body>
</html>