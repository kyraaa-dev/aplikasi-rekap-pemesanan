<?php
require 'config.php';

$_SESSION = [];
session_destroy();

// Hapus persistent cookie
setcookie('emutz_auth_remember', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Anti-cache headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header("Location: login.php?notif=logout_sukses");
exit;
?>
