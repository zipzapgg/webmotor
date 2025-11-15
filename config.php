<?php
session_start();

//Konfigurasi Database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'rental_motor');

//Koneksi ke Database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

$conn->query("SET time_zone = '+07:00'");
date_default_timezone_set('Asia/Jakarta');

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

function upload_foto_motor($file) {
    $target_dir = "uploads/motors/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Format file harus JPG, JPEG, PNG, atau GIF'];
    }
    
    if ($file["size"] > 2000000) {
        return ['success' => false, 'message' => 'Ukuran file maksimal 2MB'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'message' => 'Gagal mengupload file'];
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
?>