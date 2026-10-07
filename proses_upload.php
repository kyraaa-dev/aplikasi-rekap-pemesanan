<?php
require_once 'config.php';

// Pastikan sudah login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login terlebih dahulu.']);
    exit;
}

if (!headers_sent()) {
    header('Content-Type: application/json');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid.']);
    exit;
}

// Verifikasi CSRF Token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Token keamanan CSRF tidak valid. Silakan muat ulang halaman.']);
    exit;
}

if (!isset($_FILES['upload_file']) || $_FILES['upload_file']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada file yang dipilih.']);
    exit;
}

$file = $_FILES['upload_file'];

// Cek error upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas upload server.',
        UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas yang ditentukan.',
        UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan.',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
        UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP.',
    ];
    $msg = $error_messages[$file['error']] ?? 'Terjadi kesalahan saat mengunggah file.';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// Validasi ukuran file (max 10MB)
$max_size = 10 * 1024 * 1024; // 10MB
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'Ukuran file melebihi batas maksimum (10MB).']);
    exit;
}

// Daftar ekstensi dan MIME type yang diizinkan
$allowed_extensions = [
    'doc'  => ['application/msword'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'xls'  => ['application/vnd.ms-excel'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    'pdf'  => ['application/pdf'],
];

// Ambil ekstensi file
$original_name = $file['name'];
$ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

if (!array_key_exists($ext, $allowed_extensions)) {
    echo json_encode(['success' => false, 'message' => 'Tipe file tidak diizinkan. Hanya Word (.doc, .docx), Excel (.xls, .xlsx), dan PDF (.pdf) yang diperbolehkan.']);
    exit;
}

// Validasi MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_extensions[$ext])) {
    echo json_encode(['success' => false, 'message' => 'Konten file tidak sesuai dengan ekstensi. File mungkin telah dimodifikasi.']);
    exit;
}

// Buat direktori uploads jika belum ada
$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate nama file unik
$safe_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
$safe_name = substr($safe_name, 0, 100); // Batasi panjang nama
$unique_name = date('Ymd_His') . '_' . $safe_name . '.' . $ext;
$destination = $upload_dir . $unique_name;

// Pindahkan file
if (move_uploaded_file($file['tmp_name'], $destination)) {
    // Tentukan ikon berdasarkan tipe file
    $file_icons = [
        'doc'  => '📄',
        'docx' => '📄',
        'xls'  => '📊',
        'xlsx' => '📊',
        'pdf'  => '📕',
    ];

    $file_type_labels = [
        'doc'  => 'Word Document',
        'docx' => 'Word Document',
        'xls'  => 'Excel Spreadsheet',
        'xlsx' => 'Excel Spreadsheet',
        'pdf'  => 'PDF Document',
    ];

    // Format ukuran file
    $size_bytes = $file['size'];
    if ($size_bytes >= 1048576) {
        $size_display = round($size_bytes / 1048576, 2) . ' MB';
    } elseif ($size_bytes >= 1024) {
        $size_display = round($size_bytes / 1024, 1) . ' KB';
    } else {
        $size_display = $size_bytes . ' Bytes';
    }

    echo json_encode([
        'success' => true,
        'message' => 'File berhasil diunggah! 🏀 Slam dunk!',
        'file' => [
            'name' => $original_name,
            'saved_as' => $unique_name,
            'size' => $size_display,
            'type' => $file_type_labels[$ext] ?? 'Unknown',
            'icon' => $file_icons[$ext] ?? '📄',
            'extension' => strtoupper($ext),
            'uploaded_at' => date('d/m/Y H:i:s'),
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file. Periksa izin folder uploads.']);
}
