<?php
require_once 'config.php';

if (!headers_sent()) {
    header('Content-Type: application/json');
}

// Pastikan sudah login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode([
        'success' => false, 
        'message' => 'Sesi Anda telah berakhir. Silakan login kembali.',
        'session_expired' => true
    ]);
    exit;
}

// Aksi: list, delete, atau download
$action = $_REQUEST['action'] ?? 'list';

// Direktori uploads (mendukung folder utama dan fallback jika lingkungan serverless)
$upload_dirs = [__DIR__ . '/uploads/'];
$fallback_dir = sys_get_temp_dir() . '/emutz_uploads/';
if (is_dir($fallback_dir)) {
    $upload_dirs[] = $fallback_dir;
}
$main_upload_dir = __DIR__ . '/uploads/';
if (!is_dir($main_upload_dir)) {
    @mkdir($main_upload_dir, 0777, true);
}

if ($action === 'list') {
    $files = [];
    $allowed_ext = ['doc', 'docx', 'xls', 'xlsx', 'pdf'];
    $seen = [];

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

    foreach ($upload_dirs as $dir) {
        if (!is_dir($dir)) continue;
        $items = scandir($dir, SCANDIR_SORT_DESCENDING);
        if (!$items) continue;

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.htaccess' || $item === '.gitkeep') continue;
            if (isset($seen[$item])) continue;

            $filepath = $dir . $item;
            if (!is_file($filepath)) continue;

            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext)) continue;

            $seen[$item] = true;
            $size_bytes = filesize($filepath);
            if ($size_bytes >= 1048576) {
                $size_display = round($size_bytes / 1048576, 2) . ' MB';
            } elseif ($size_bytes >= 1024) {
                $size_display = round($size_bytes / 1024, 1) . ' KB';
            } else {
                $size_display = $size_bytes . ' Bytes';
            }

            // Nama bersih untuk tampilan
            $clean_name = $item;
            if (preg_match('/^\d{8}_\d{6}_(.+)$/', $item, $m)) {
                $clean_name = $m[1];
            }

            $files[] = [
                'name'         => $item,
                'display_name' => $clean_name,
                'size'         => $size_display,
                'size_bytes'   => $size_bytes,
                'type'         => $file_type_labels[$ext] ?? 'Dokumen',
                'icon'         => $file_icons[$ext] ?? '📄',
                'extension'    => strtoupper($ext),
                'uploaded_at'  => date('d/m/Y H:i', filemtime($filepath)),
                'url'          => 'api_files.php?action=download&file=' . rawurlencode($item),
            ];
        }
    }

    echo json_encode(['success' => true, 'files' => $files]);

} elseif ($action === 'download') {
    $filename = basename($_GET['file'] ?? '');
    if (empty($filename) || $filename === '.htaccess' || $filename === '.gitkeep') {
        http_response_code(400);
        die('Nama file tidak valid.');
    }

    $found_path = null;
    foreach ($upload_dirs as $dir) {
        $check = $dir . $filename;
        if (is_file($check)) {
            $real_dir = realpath($dir);
            $real_file = realpath($check);
            if ($real_file && dirname($real_file) === $real_dir) {
                $found_path = $real_file;
                break;
            }
        }
    }

    if (!$found_path || !is_file($found_path)) {
        http_response_code(404);
        die('File tidak ditemukan di server.');
    }

    $clean_name = $filename;
    if (preg_match('/^\d{8}_\d{6}_(.+)$/', $filename, $m)) {
        $clean_name = $m[1];
    }

    // Bersihkan buffer output sebelum streaming binary file
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . rawurlencode($clean_name) . '"; filename*=UTF-8\'\'' . rawurlencode($clean_name));
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($found_path));
    readfile($found_path);
    exit;

} elseif ($action === 'delete') {
    // Verifikasi CSRF Token (mendukung POST & GET)
    $csrf_token = $_REQUEST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Token keamanan CSRF tidak valid atau telah kedaluwarsa. Silakan refresh halaman.'
        ]);
        exit;
    }

    $filename = basename($_REQUEST['file'] ?? '');
    if (empty($filename) || $filename === '.htaccess' || $filename === '.gitkeep' || $filename === '.' || $filename === '..') {
        echo json_encode(['success' => false, 'message' => 'Nama file tidak valid.']);
        exit;
    }

    $deleted = false;
    foreach ($upload_dirs as $dir) {
        $check = $dir . $filename;
        if (is_file($check)) {
            $real_dir = realpath($dir);
            $real_file = realpath($check);
            if ($real_file && dirname($real_file) === $real_dir) {
                if (@unlink($real_file)) {
                    $deleted = true;
                    break;
                }
            }
        }
    }

    if ($deleted) {
        echo json_encode([
            'success' => true, 
            'message' => 'Dokumen berhasil dihapus dari server.',
            'file'    => $filename
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal menghapus dokumen dari server atau file tidak ditemukan.'
        ]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
}
