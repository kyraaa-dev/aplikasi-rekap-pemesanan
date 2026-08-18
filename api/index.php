<?php
// Forward all requests to root PHP files
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// If asset slipped through, return false for web server
if ($uri !== '/' && file_exists(__DIR__ . '/../' . $uri)) {
    return false;
}

$file = ltrim($uri, '/');

if (empty($file)) {
    $file = 'index.php';
}

$target = __DIR__ . '/../' . $file;

// Check if requested file exists (e.g. login.php, setup.php, stok.php)
if (file_exists($target) && is_file($target)) {
    chdir(__DIR__ . '/..');
    require $target;
} elseif (file_exists($target . '.php') && is_file($target . '.php')) {
    chdir(__DIR__ . '/..');
    require $target . '.php';
} else {
    chdir(__DIR__ . '/..');
    require __DIR__ . '/../index.php';
}
