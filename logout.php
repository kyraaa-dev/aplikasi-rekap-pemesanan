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

header("Location: login.php");
exit;
?>
