<?php
/**
 * TEST UPLOAD - Debug upload issues
 * Akses: http://localhost/rental_motor/test_upload.php
 */

echo "<h1>🔧 Upload Configuration Test</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 4px solid #333; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #333; color: white; }
</style>";

// 1. Cek PHP Upload Settings
echo "<h2>1️⃣ PHP Upload Configuration</h2>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$settings = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time'),
    'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
    'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
];

foreach ($settings as $key => $value) {
    $status = ($key == 'file_uploads' && $value == 'Enabled') ? '✅' : 
              ($key == 'file_uploads' && $value == 'Disabled') ? '❌' : '✅';
    echo "<tr><td><strong>$key</strong></td><td>$value</td><td>$status</td></tr>";
}
echo "</table>";

// 2. Cek Folder Permissions
echo "<h2>2️⃣ Folder Permissions</h2>";

$folders_to_check = [
    'uploads' => 'uploads',
    'uploads/motors' => 'uploads/motors',
];

echo "<table>";
echo "<tr><th>Folder</th><th>Exists</th><th>Writable</th><th>Permission</th><th>Action</th></tr>";

foreach ($folders_to_check as $name => $path) {
    $exists = file_exists($path);
    $writable = is_writable($path);
    $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
    
    $exists_icon = $exists ? '<span class="success">✅ Yes</span>' : '<span class="error">❌ No</span>';
    $writable_icon = $writable ? '<span class="success">✅ Yes</span>' : '<span class="error">❌ No</span>';
    
    $action = '';
    if (!$exists) {
        $action = '<button onclick="createFolder(\'' . $path . '\')">Create Folder</button>';
    } elseif (!$writable) {
        $action = '<button onclick="fixPermission(\'' . $path . '\')">Fix Permission</button>';
    } else {
        $action = '<span class="success">✅ OK</span>';
    }
    
    echo "<tr><td><code>$path</code></td><td>$exists_icon</td><td>$writable_icon</td><td>$perms</td><td>$action</td></tr>";
}
echo "</table>";

// 3. Test Upload Form
echo "<h2>3️⃣ Test Upload Form</h2>";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['test_file'])) {
    echo "<div class='info'>";
    echo "<h3>Upload Result:</h3>";
    echo "<pre>";
    print_r($_FILES['test_file']);
    echo "</pre>";
    
    if ($_FILES['test_file']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/motors/";
        $target_file = $target_dir . basename($_FILES['test_file']['name']);
        
        if (move_uploaded_file($_FILES['test_file']['tmp_name'], $target_file)) {
            echo "<p class='success'>✅ File berhasil di-upload ke: $target_file</p>";
            echo "<img src='$target_file' style='max-width: 300px; border: 2px solid green;'>";
        } else {
            echo "<p class='error'>❌ Gagal move_uploaded_file</p>";
            echo "<p>Error: " . error_get_last()['message'] ?? 'Unknown' . "</p>";
        }
    } else {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang di-upload',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ada',
            UPLOAD_ERR_CANT_WRITE => 'Gagal write ke disk',
            UPLOAD_ERR_EXTENSION => 'Extension PHP menghentikan upload'
        ];
        
        $error_code = $_FILES['test_file']['error'];
        $error_msg = $errors[$error_code] ?? "Unknown error: $error_code";
        echo "<p class='error'>❌ Upload Error: $error_msg</p>";
    }
    echo "</div>";
}

?>

<form method="POST" enctype="multipart/form-data" style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
    <h3>Upload Test Image:</h3>
    <input type="file" name="test_file" accept="image/*" required>
    <button type="submit" style="background: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px;">
        🚀 Test Upload
    </button>
</form>

<script>
function createFolder(path) {
    if (confirm('Create folder: ' + path + '?\n\nNote: PHP must have permission to create folders.')) {
        window.location.href = '?action=create&path=' + encodeURIComponent(path);
    }
}

function fixPermission(path) {
    alert('⚠️ Cannot fix permission from browser.\n\nRun this command in terminal:\n\nchmod 777 ' + path);
}
</script>

<?php
// Handle folder creation
if (isset($_GET['action']) && $_GET['action'] == 'create') {
    $path = $_GET['path'];
    if (mkdir($path, 0777, true)) {
        echo "<script>alert('✅ Folder created: $path'); window.location.href='test_upload.php';</script>";
    } else {
        echo "<script>alert('❌ Failed to create folder: $path');</script>";
    }
}
?>

<hr>
<h2>4️⃣ Quick Fixes</h2>
<div class="info">
    <h3>Jika upload masih gagal, coba ini:</h3>
    <ol>
        <li><strong>Buat folder manual:</strong>
            <pre>mkdir uploads/motors
chmod 777 uploads/motors</pre>
        </li>
        <li><strong>Cek php.ini:</strong> Pastikan <code>file_uploads = On</code></li>
        <li><strong>Restart Apache:</strong> Kadang perlu restart setelah ubah php.ini</li>
        <li><strong>Disable Antivirus:</strong> Beberapa antivirus block file uploads</li>
        <li><strong>Cek form:</strong> Pastikan ada <code>enctype="multipart/form-data"</code></li>
    </ol>
</div>