<?php
/**
 * Security Helper Functions
 * Tambahkan ke config.php atau include di awal file
 */

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
    return preg_match("/^[0-9]{10,15}$/", $phone);
}

function validate_ktp($ktp) {
    // Format KTP Indonesia: 16 digit
    return preg_match("/^[0-9]{16}$/", $ktp);
}

function validate_plat($plat) {
    // Format: B 1234 XYZ atau B 1234 X
    return preg_match("/^[A-Z]{1,2}\s?[0-9]{1,4}\s?[A-Z]{1,3}$/i", $plat);
}

function validate_username($username) {
    // Alphanumeric, underscore, min 3 char
    return preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username);
}

function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// ========== FILE UPLOAD SECURITY ==========
function secure_upload_motor($file) {
    $target_dir = "uploads/motors/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Check actual MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file["tmp_name"]);
    finfo_close($finfo);
    
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed_mimes)) {
        return ['success' => false, 'message' => 'Format file tidak valid. Hanya JPG, PNG, GIF, atau WebP'];
    }
    
    // Check file size (max 2MB)
    if ($file["size"] > 2000000) {
        return ['success' => false, 'message' => 'Ukuran file maksimal 2MB'];
    }
    
    // Generate secure filename
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = hash('sha256', uniqid() . time()) . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'message' => 'Gagal mengupload file'];
    }
}

// ========== PASSWORD STRENGTH ==========
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

// ========== RATE LIMITING ==========
function check_rate_limit($identifier, $max_attempts = 5, $time_window = 300) {
    // $identifier bisa IP address atau username
    // $max_attempts = jumlah percobaan maksimal
    // $time_window = dalam detik (300 = 5 menit)
    
    if (!isset($_SESSION['rate_limit'][$identifier])) {
        $_SESSION['rate_limit'][$identifier] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    $data = $_SESSION['rate_limit'][$identifier];
    $elapsed = time() - $data['first_attempt'];
    
    // Reset if time window passed
    if ($elapsed > $time_window) {
        $_SESSION['rate_limit'][$identifier] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    // Check if exceeded max attempts
    if ($data['attempts'] >= $max_attempts) {
        $remaining = $time_window - $elapsed;
        return [
            'success' => false,
            'message' => "Terlalu banyak percobaan. Coba lagi dalam " . ceil($remaining / 60) . " menit.",
            'remaining' => $remaining
        ];
    }
    
    // Increment attempts
    $_SESSION['rate_limit'][$identifier]['attempts']++;
    return true;
}

// ========== SQL SAFE FUNCTIONS ==========
function escape_like($string, $conn) {
    // Escape for LIKE queries
    $string = $conn->real_escape_string($string);
    $string = str_replace(['%', '_'], ['\\%', '\\_'], $string);
    return $string;
}

// ========== XSS PROTECTION ==========
function xss_clean($data) {
    // Clean array
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = xss_clean($value);
        }
        return $data;
    }
    
    // Clean string
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// ========== SECURE REDIRECT ==========
function secure_redirect($location) {
    // Prevent open redirect vulnerability
    $allowed_domains = [
        $_SERVER['HTTP_HOST']
    ];
    
    $parsed = parse_url($location);
    
    if (isset($parsed['host']) && !in_array($parsed['host'], $allowed_domains)) {
        $location = 'index.php'; // Redirect to safe page
    }
    
    header("Location: " . $location);
    exit();
}

// ========== SESSION SECURITY ==========
function secure_session_start() {
    $session_name = 'RENTAL_MOTOR_SESSION';
    $secure = false; // Set true jika pakai HTTPS
    $httponly = true;
    
    if (session_status() == PHP_SESSION_NONE) {
        ini_set('session.use_only_cookies', 1);
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params(
            $cookieParams["lifetime"],
            $cookieParams["path"],
            $cookieParams["domain"],
            $secure,
            $httponly
        );
        
        session_name($session_name);
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            // Regenerate every 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

// ========== LOGGING ==========
function log_activity($user_id, $action, $description) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $action, $description, $ip_address);
    $stmt->execute();
    $stmt->close();
}

// ========== SAFE OUTPUT ==========
function safe_output($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function safe_url($url) {
    return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}

// ========== ERROR HANDLER ==========
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    // Log error to file instead of displaying
    $error_message = date('Y-m-d H:i:s') . " - Error [$errno]: $errstr in $errfile on line $errline\n";
    error_log($error_message, 3, "error_log.txt");
    
    // Display user-friendly message
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // Don't show detailed errors to users
    echo "<div class='flash-message error'>Terjadi kesalahan. Silakan coba lagi nanti.</div>";
    return true;
}

// Set custom error handler
// set_error_handler("custom_error_handler");

?>