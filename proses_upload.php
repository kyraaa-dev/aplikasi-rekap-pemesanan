<?php
require_once 'config.php';

// Pastikan sudah login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode(['success' => false, 'message' => 'Sesi telah berakhir. Silakan login terlebih dahulu.', 'session_expired' => true]);
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

// Cek error upload PHP
if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE   => 'Ukuran file melebihi batas upload server (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE  => 'Ukuran file melebihi batas form (MAX_FILE_SIZE).',
        UPLOAD_ERR_PARTIAL    => 'File hanya terunggah sebagian. Silakan coba lagi.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary server tidak ditemukan.',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk server.',
        UPLOAD_ERR_EXTENSION  => 'Upload dihentikan oleh ekstensi server.',
    ];
    $msg = $error_messages[$file['error']] ?? 'Terjadi kesalahan saat mengunggah file (Kode: ' . $file['error'] . ').';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// Validasi ukuran file (max 10MB)
$max_size = 10 * 1024 * 1024; // 10MB
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'Ukuran file melebihi batas maksimum 10MB.']);
    exit;
}

// Ambil ekstensi file
$original_name = $file['name'];
$ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
$allowed_extensions = ['doc', 'docx', 'xls', 'xlsx', 'pdf'];

if (!in_array($ext, $allowed_extensions)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Tipe file tidak diizinkan. Hanya dokumen Word (.doc, .docx), Excel (.xls, .xlsx), dan PDF (.pdf) yang diperbolehkan.'
    ]);
    exit;
}

// Validasi MIME Type & Magic Bytes Komprehensif (mendukung semua varian MIME Word/Excel/PDF)
$allowed_mimes = [
    'doc'  => [
        'application/msword', 
        'application/vnd.ms-office', 
        'application/octet-stream', 
        'application/x-msword',
        'application/doc'
    ],
    'docx' => [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/x-zip-compressed',
        'application/x-zip',
        'application/octet-stream'
    ],
    'xls'  => [
        'application/vnd.ms-excel', 
        'application/msexcel', 
        'application/x-msexcel', 
        'application/x-ms-excel', 
        'application/octet-stream',
        'application/excel'
    ],
    'xlsx' => [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/x-zip-compressed',
        'application/x-zip',
        'application/octet-stream'
    ],
    'pdf'  => [
        'application/pdf',
        'application/x-pdf',
        'application/acrobat',
        'applications/vnd.pdf',
        'text/pdf',
        'application/octet-stream'
    ],
];

// Deteksi MIME Type
$mime_type = '';
if (function_exists('finfo_open') && is_file($file['tmp_name'])) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
} elseif (function_exists('mime_content_type') && is_file($file['tmp_name'])) {
    $mime_type = mime_content_type($file['tmp_name']);
}

// Baca Magic Header Bytes
$handle = @fopen($file['tmp_name'], 'rb');
$header = $handle ? fread($handle, 16) : '';
if ($handle) @fclose($handle);

$is_valid = false;

// 1. Validasi via MIME Type terdaftar
if (!empty($mime_type) && isset($allowed_mimes[$ext]) && in_array(strtolower($mime_type), $allowed_mimes[$ext])) {
    $is_valid = true;
}

// 2. Validasi via Magic Header Bytes (Verifikasi Standar File Format)
if ($ext === 'pdf' && strncmp($header, "%PDF", 4) === 0) {
    $is_valid = true;
} elseif (in_array($ext, ['docx', 'xlsx']) && (strncmp($header, "PK\x03\x04", 4) === 0 || strncmp($header, "PK", 2) === 0)) {
    $is_valid = true;
} elseif (in_array($ext, ['doc', 'xls']) && (strncmp($header, "\xD0\xCF\x11\xE0", 4) === 0 || strncmp($header, "PK", 2) === 0)) {
    $is_valid = true;
}

// 3. Fallback keamanan: Pastikan bukan script berbahaya (PHP, HTML, JS, Shell)
if (!$is_valid) {
    $dangerous_patterns = ['<?php', '<?= ', '<script', '#!/bin/', '<?xml'];
    $is_dangerous = false;
    foreach ($dangerous_patterns as $pattern) {
        if (stripos($header, $pattern) !== false) {
            $is_dangerous = true;
            break;
        }
    }
    // Jika bukan file berbahaya dan ekstensinya terdaftar, izinkan
    if (!$is_dangerous) {
        $is_valid = true;
    }
}

if (!$is_valid) {
    echo json_encode([
        'success' => false, 
        'message' => 'Format file tidak sesuai dengan dokumen resmi (' . strtoupper($ext) . '). File mungkin rusak.'
    ]);
    exit;
}

// Siapkan direktori uploads
$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}
@chmod($upload_dir, 0777);

// Jika direktori uploads tidak bisa ditulis (misal Vercel read-only), gunakan folder temp
if (!is_writable($upload_dir)) {
    $fallback_dir = sys_get_temp_dir() . '/emutz_uploads/';
    if (!is_dir($fallback_dir)) {
        @mkdir($fallback_dir, 0777, true);
    }
    if (is_writable($fallback_dir)) {
        $upload_dir = $fallback_dir;
    }
}

// Generate nama file unik yang bersih
$safe_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
$safe_name = substr($safe_name, 0, 80);
$unique_name = date('Ymd_His') . '_' . $safe_name . '.' . $ext;
$destination = $upload_dir . $unique_name;

// Pindahkan file (mendukung HTTP upload & direct move)
$saved = false;
if (is_uploaded_file($file['tmp_name'])) {
    $saved = @move_uploaded_file($file['tmp_name'], $destination);
}
if (!$saved) {
    $saved = @copy($file['tmp_name'], $destination);
    if ($saved && is_file($file['tmp_name'])) {
        @unlink($file['tmp_name']);
    }
}

if ($saved) {
    @chmod($destination, 0666);

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
    $size_bytes = (int)$file['size'];
    if ($size_bytes >= 1048576) {
        $size_display = round($size_bytes / 1048576, 2) . ' MB';
    } elseif ($size_bytes >= 1024) {
        $size_display = round($size_bytes / 1024, 1) . ' KB';
    } else {
        $size_display = $size_bytes . ' Bytes';
    }

    echo json_encode([
        'success' => true,
        'message' => 'Dokumen berhasil diunggah',
        'file' => [
            'name'        => $original_name,
            'saved_as'    => $unique_name,
            'size'        => $size_display,
            'type'        => $file_type_labels[$ext] ?? 'Dokumen',
            'icon'        => $file_icons[$ext] ?? '📄',
            'extension'   => strtoupper($ext),
            'uploaded_at' => date('d/m/Y H:i'),
        ]
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal menyimpan file ke server. Periksa hak akses folder uploads (' . htmlspecialchars($upload_dir) . ').'
    ]);
}
