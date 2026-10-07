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

// Aksi: list atau delete (mendukung GET & POST)
$action = $_REQUEST['action'] ?? 'list';
$upload_dir = __DIR__ . '/uploads/';

// Buat folder uploads jika belum ada
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

if ($action === 'list') {
    $files = [];
    
    if (is_dir($upload_dir)) {
        $allowed_ext = ['doc', 'docx', 'xls', 'xlsx', 'pdf'];
        $items = scandir($upload_dir, SCANDIR_SORT_DESCENDING);
        
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
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.htaccess') continue;
            
            $filepath = $upload_dir . $item;
            if (!is_file($filepath)) continue;

            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext)) continue;
            
            $size_bytes = filesize($filepath);
            if ($size_bytes >= 1048576) {
                $size_display = round($size_bytes / 1048576, 2) . ' MB';
            } elseif ($size_bytes >= 1024) {
                $size_display = round($size_bytes / 1024, 1) . ' KB';
            } else {
                $size_display = $size_bytes . ' Bytes';
            }

            // Nama bersih untuk tampilan (hapus prefix timestamp jika ada)
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
                'url'          => 'uploads/' . rawurlencode($item),
            ];
        }
    }
    
    echo json_encode(['success' => true, 'files' => $files]);
    
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
    if (empty($filename) || $filename === '.htaccess' || $filename === '.' || $filename === '..') {
        echo json_encode(['success' => false, 'message' => 'Nama file tidak valid.']);
        exit;
    }
    
    $filepath = $upload_dir . $filename;
    
    // Validasi ketat path traversal & keberadaan file
    $real_upload = realpath($upload_dir);
    $real_file = realpath($filepath);
    
    if (!$real_upload || !$real_file || dirname($real_file) !== $real_upload || !is_file($real_file)) {
        echo json_encode(['success' => false, 'message' => 'File tidak ditemukan di server atau akses ditolak.']);
        exit;
    }
    
    if (@unlink($real_file)) {
        echo json_encode([
            'success' => true, 
            'message' => 'File berhasil dihapus dari server.',
            'file'    => $filename
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal menghapus file dari server. Periksa hak akses direktori uploads.'
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
}
