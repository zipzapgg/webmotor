<?php
// Secure session start
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set 1 jika pakai HTTPS
    session_name('RENTAL_MOTOR_SESSION');
    session_start();
    
    // Regenerate session ID setiap 30 menit
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Konfigurasi Database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'rental_motor');

// Koneksi ke Database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

$conn->query("SET time_zone = '+07:00'");
date_default_timezone_set('Asia/Jakarta');

// ========== HELPER FUNCTIONS ==========

function format_rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function set_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function redirect($location) {
    header("Location: " . $location);
    exit();
}

// ========== CSRF PROTECTION ==========

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}

// ========== INPUT VALIDATION ==========

function validate_phone($phone) {
    // Format: 08xxxxxxxxxx (10-15 digit)
    return preg_match("/^08[0-9]{8,13}$/", $phone);
}

function validate_ktp($ktp) {
    // Format KTP Indonesia: 16 digit
    return preg_match("/^[0-9]{16}$/", $ktp);
}

function validate_plat($plat) {
    // Format: B 1234 XYZ atau B1234XYZ
    return preg_match("/^[A-Z]{1,2}\s?[0-9]{1,4}\s?[A-Z]{1,3}$/i", $plat);
}

function validate_username($username) {
    // Alphanumeric, underscore, 3-20 karakter
    return preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username);
}

function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

function safe_output($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// ========== RATE LIMITING ==========

function check_rate_limit($identifier, $max_attempts = 5, $time_window = 300) {
    if (!isset($_SESSION['rate_limit'][$identifier])) {
        $_SESSION['rate_limit'][$identifier] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    $data = $_SESSION['rate_limit'][$identifier];
    $elapsed = time() - $data['first_attempt'];
    
    if ($elapsed > $time_window) {
        $_SESSION['rate_limit'][$identifier] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    if ($data['attempts'] >= $max_attempts) {
        $remaining = $time_window - $elapsed;
        return [
            'success' => false,
            'message' => "Terlalu banyak percobaan. Coba lagi dalam " . ceil($remaining / 60) . " menit.",
            'remaining' => $remaining
        ];
    }
    
    $_SESSION['rate_limit'][$identifier]['attempts']++;
    return true;
}

// ========== FILE UPLOAD SECURITY ==========

function upload_foto_motor($file) {
    $target_dir = "uploads/motors/";
    
    // Cek dan buat folder jika belum ada
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            return ['success' => false, 'message' => 'Gagal membuat folder uploads/motors/'];
        }
    }
    
    // Cek apakah folder writable
    if (!is_writable($target_dir)) {
        return ['success' => false, 'message' => 'Folder uploads/motors/ tidak memiliki permission write. Chmod ke 777'];
    }
    
    // Check actual MIME type
    if (!file_exists($file["tmp_name"])) {
        return ['success' => false, 'message' => 'File temporary tidak ditemukan'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file["tmp_name"]);
    finfo_close($finfo);
    
    $allowed_mimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed_mimes)) {
        return ['success' => false, 'message' => "Format file tidak valid ($mime). Hanya JPG, PNG, GIF, atau WebP"];
    }
    
    // Check file size (max 2MB = 2097152 bytes)
    if ($file["size"] > 2097152) {
        $size_mb = round($file["size"] / 1024 / 1024, 2);
        return ['success' => false, 'message' => "Ukuran file $size_mb MB terlalu besar. Maksimal 2MB"];
    }
    
    // Generate secure filename
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = 'motor_' . uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        // Verify file exists after upload
        if (file_exists($target_file)) {
            return ['success' => true, 'filename' => $new_filename];
        } else {
            return ['success' => false, 'message' => 'File di-upload tapi tidak ditemukan di server'];
        }
    } else {
        return ['success' => false, 'message' => 'Gagal move_uploaded_file. Cek permission folder'];
    }
}

function delete_foto_motor($filename) {
    if (empty($filename) || $filename === 'default-motor.jpg') {
        return true;
    }
    
    $file_path = "uploads/motors/" . $filename;
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return true;
}

// ========== PASSWORD VALIDATION ==========

function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password minimal 8 karakter";
    }
    
    if (!preg_match("/[a-z]/", $password)) {
        $errors[] = "Password harus mengandung huruf kecil";
    }
    
    if (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password harus mengandung huruf besar";
    }
    
    if (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password harus mengandung angka";
    }
    
    return $errors;
}

// ========== LOGGING ACTIVITY ==========

function log_activity($user_id, $action, $description) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $user_id, $action, $description, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}

// ========== ERROR HANDLER ==========

function custom_error_message() {
    return '<div class="flash-message error">Terjadi kesalahan sistem. Silakan coba lagi atau hubungi administrator.</div>';
}

// Disable error display in production
if ($_SERVER['SERVER_NAME'] !== 'localhost') {
    ini_set('display_errors', 0);
    error_reporting(0);
}
?>