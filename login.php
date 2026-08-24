<?php
require 'config.php'; // Includes session_start()

if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

// Anti Brute-Force Rate Limiting Settings
$max_attempts = 5;
$lockout_time = 300; // 5 minutes in seconds

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_failed_login'] = 0;
}

// Check if locked out
$is_locked_out = false;
$seconds_remaining = 0;
if ($_SESSION['login_attempts'] >= $max_attempts) {
    $time_passed = time() - $_SESSION['last_failed_login'];
    if ($time_passed < $lockout_time) {
        $is_locked_out = true;
        $seconds_remaining = $lockout_time - $time_passed;
    } else {
        // Reset after lockout time expires
        $_SESSION['login_attempts'] = 0;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error = "Token keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.";
    } elseif ($is_locked_out) {
        $error = "Terlalu banyak percobaan gagal! Akun dikunci sementara demi keamanan. Coba lagi dalam {$seconds_remaining} detik.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT id, admin_username, admin_password FROM settings WHERE admin_username = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if (verify_and_upgrade_password($conn, $row['id'], $password, $row['admin_password'])) {
                    // Reset login attempts on success
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['is_logged_in'] = true;
                    $_SESSION['admin_user'] = $row['admin_username'];

                    // Set 30-day Persistent Auth Cookie for seamless reconnection
                    $token = generate_auth_token($row['admin_username'], $row['admin_password']);
                    $cookie_val = base64_encode($row['admin_username'] . ':' . $token);
                    $is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
                    
                    setcookie('emutz_auth_remember', $cookie_val, [
                        'expires' => time() + (86400 * 30),
                        'path' => '/',
                        'secure' => $is_https,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);

                    $stmt->close();
                    header("Location: index.php");
                    exit;
                } else {
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_failed_login'] = time();
                    $remaining = $max_attempts - $_SESSION['login_attempts'];
                    if ($remaining > 0) {
                        $error = "Password salah! Sisa percobaan: {$remaining} kali.";
                    } else {
                        $error = "Terlalu banyak percobaan gagal! Akun dikunci sementara selama 5 menit.";
                    }
                }
            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['last_failed_login'] = time();
                $remaining = $max_attempts - $_SESSION['login_attempts'];
                if ($remaining > 0) {
                    $error = "Username tidak ditemukan! Sisa percobaan: {$remaining} kali.";
                } else {
                    $error = "Terlalu banyak percobaan gagal! Akun dikunci sementara selama 5 menit.";
                }
            }
            $stmt->close();
        } else {
            $error = "Terjadi kesalahan pada sistem database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>E-MutZ KORPRI - Login</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563EB">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-MutZ KORPRI">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        html, body {
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            position: relative;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            /* Background comes from app.css */
        }

        /* Top Bar Actions (Theme Switcher) */
        .login-topbar {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 50;
        }
        .btn-theme-login {
            background: var(--primary);
            border: var(--brutal-border);
            color: var(--dark);
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: var(--shadow);
        }
        .btn-theme-login:hover {
            transform: translate(-4px, -4px);
            box-shadow: 8px 8px 0px #000000;
        }
        [data-theme="dark"] .btn-theme-login:hover {
            box-shadow: 8px 8px 0px #FFFFFF;
        }

        /* Login Container & Card */
        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 16px;
            position: relative;
            z-index: 10;
        }

        .login-card {
            background: var(--white);
            padding: 3rem 2.5rem;
            border: var(--brutal-border);
            box-shadow: 12px 12px 0px #000000;
            position: relative;
            animation: cardAppear 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        [data-theme="dark"] .login-card {
            background: var(--brutal-bg);
            box-shadow: 12px 12px 0px #FFFFFF;
        }

        @keyframes cardAppear {
            from { opacity: 0; transform: translateY(40px) rotate(-2deg); }
            to { opacity: 1; transform: translateY(0) rotate(0); }
        }

        /* Header / Logo */
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-wrap {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.25rem;
            background: var(--primary);
            border: var(--brutal-border);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            transition: all 0.2s;
        }
        .logo-wrap:hover {
            transform: translate(-4px, -4px) rotate(-5deg);
            box-shadow: 8px 8px 0px #000000;
        }
        [data-theme="dark"] .logo-wrap:hover {
            box-shadow: 8px 8px 0px #FFFFFF;
        }
        
        .logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        [data-theme="dark"] .logo-wrap img {
            filter: drop-shadow(0 0 0 transparent);
        }

        .login-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--dark);
            text-transform: uppercase;
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
        }
        .login-subtitle {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 600;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .input-icon-left {
            position: absolute;
            left: 14px;
            color: var(--dark);
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
            transition: transform 0.2s;
        }

        .input-group input[type="text"],
        .input-group input[type="password"],
        .form-input {
            width: 100% !important;
            height: 50px !important;
            padding: 0 42px 0 44px !important;
            font-size: 1rem !important;
            font-weight: 600 !important;
            color: var(--dark) !important;
            background: var(--white) !important;
            border: 2px solid var(--dark) !important;
            box-shadow: 4px 4px 0px var(--dark) !important;
            transition: all 0.2s ease !important;
            outline: none !important;
            box-sizing: border-box !important;
            position: relative;
            z-index: 1;
        }
        [data-theme="dark"] .input-group input[type="text"],
        [data-theme="dark"] .input-group input[type="password"],
        [data-theme="dark"] .form-input {
            background: var(--brutal-bg) !important;
            border-color: var(--white) !important;
            color: var(--white) !important;
            box-shadow: 4px 4px 0px var(--white) !important;
        }

        .input-group input[type="text"]:focus,
        .input-group input[type="password"]:focus,
        .form-input:focus {
            transform: translate(-2px, -2px) !important;
            box-shadow: 6px 6px 0px var(--dark) !important;
            background: var(--primary) !important;
            color: var(--dark) !important;
        }
        [data-theme="dark"] .input-group input[type="text"]:focus,
        [data-theme="dark"] .input-group input[type="password"]:focus,
        [data-theme="dark"] .form-input:focus {
            box-shadow: 6px 6px 0px var(--white) !important;
            background: var(--primary) !important;
            color: var(--dark) !important;
        }

        .input-group:focus-within .input-icon-left {
            transform: scale(1.1);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            background: transparent;
            border: none;
            color: var(--dark);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
            transition: transform 0.2s;
        }
        [data-theme="dark"] .toggle-password {
            color: var(--white);
        }
        .toggle-password:hover {
            transform: scale(1.1);
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            height: 52px;
            margin-top: 1rem;
            background: var(--primary);
            color: var(--dark);
            border: 2px solid var(--dark);
            box-shadow: 6px 6px 0px var(--dark);
            font-size: 1.05rem;
            font-weight: 800;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        [data-theme="dark"] .btn-submit {
            border-color: var(--white);
            box-shadow: 6px 6px 0px var(--white);
        }
        
        .btn-submit:hover {
            transform: translate(-4px, -4px);
            box-shadow: 10px 10px 0px var(--dark);
            background: #FFE600; /* Force cyber yellow */
        }
        [data-theme="dark"] .btn-submit:hover {
            box-shadow: 10px 10px 0px var(--white);
            background: #00FFCC; /* Force cyber cyan */
        }
        
        .btn-submit:active {
            transform: translate(0px, 0px);
            box-shadow: 2px 2px 0px var(--dark);
        }
        [data-theme="dark"] .btn-submit:active {
            box-shadow: 2px 2px 0px var(--white);
        }
        
        .btn-submit.loading {
            pointer-events: none;
            opacity: 0.9;
        }

        @keyframes spin { 100% { transform: rotate(360deg); } }

        .footer-note {
            margin-top: 2rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--gray);
            text-align: center;
            text-transform: uppercase;
        }

        /* Responsiveness */
        @media (max-height: 600px) {
            html, body {
                height: auto;
                min-height: 100vh;
                overflow-y: auto;
            }
            body {
                padding: 1.5rem 0;
            }
            .login-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <script>
        if(localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    </script>
    
    <!-- Removed Ambient Blur Shapes -->

    <!-- Theme Toggle -->
    <div class="login-topbar">
        <button id="themeToggleLogin" class="btn-theme-login" title="Ganti Tema" aria-label="Ganti Tema">
            <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
        </button>
    </div>
    
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-wrap">
                    <img src="assets/images/logo.png?v=2" alt="Logo E-MutZ">
                </div>
                <h1 class="login-title">E-MutZ KORPRI</h1>
                <p class="login-subtitle">Sistem Rekapitulasi & Pemesanan</p>
            </div>
            
            <?php if ($error): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Login Gagal',
                            text: '<?= addslashes($error) ?>',
                            icon: 'error',
                            confirmButtonColor: '#EF4444'
                        });
                    });
                </script>
            <?php elseif (isset($_GET['notif']) && $_GET['notif'] === 'logout_sukses'): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Berhasil Keluar',
                            text: 'Sesi aman telah ditutup dan cache browser berhasil dibersihkan.',
                            icon: 'success',
                            confirmButtonColor: '#2563EB',
                            timer: 3000,
                            timerProgressBar: true
                        });
                        window.history.replaceState(null, null, window.location.pathname);
                    });
                </script>
            <?php endif; ?>

            <form method="POST" id="loginForm" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-icon-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                        <input type="text" name="username" class="form-input" placeholder="Masukkan username" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-icon-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" radius="2" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </span>
                        <input type="password" name="password" id="passwordInput" class="form-input" placeholder="Masukkan password" required>
                        <button type="button" class="toggle-password" id="togglePasswordBtn" title="Lihat Password" aria-label="Lihat Password">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="btnSubmit">
                    <span>Masuk ke Dashboard</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </button>
            </form>

            <div class="footer-note">
                &copy; <?= date('Y') ?> E-MutZ KORPRI. Hak Cipta Dilindungi.
            </div>
        </div>
    </div>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Theme switcher for login page
        const themeToggle = document.getElementById('themeToggleLogin');
        const svgDark = '<svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';
        const svgLight = '<svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>';

        function updateIcon() {
            if(themeToggle) {
                themeToggle.innerHTML = (document.documentElement.getAttribute('data-theme') === 'dark') ? svgLight : svgDark;
            }
        }
        updateIcon();

        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                if (document.documentElement.getAttribute('data-theme') === 'dark') {
                    document.documentElement.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                }
                updateIcon();
            });
        }

        // Password visibility toggle
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const pwdInput = document.getElementById('passwordInput');
        const eyeIcon = document.getElementById('eyeIcon');

        if (toggleBtn && pwdInput && eyeIcon) {
            toggleBtn.addEventListener('click', function() {
                if (pwdInput.type === 'password') {
                    pwdInput.type = 'text';
                    eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
                    toggleBtn.style.color = '#2563EB';
                } else {
                    pwdInput.type = 'password';
                    eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
                    toggleBtn.style.color = '';
                }
            });
        }

        // Submit form loading state
        const loginForm = document.getElementById('loginForm');
        const btnSubmit = document.getElementById('btnSubmit');

        if (loginForm && btnSubmit) {
            loginForm.addEventListener('submit', function() {
                btnSubmit.classList.add('loading');
                btnSubmit.innerHTML = 'Memproses... <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 0.8s linear infinite;"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>';
            });
        }

        // Progressive Web App (PWA) Service Worker Registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('E-MutZ PWA SW Registered! Scope:', reg.scope))
                .catch(err => console.warn('E-MutZ PWA SW failed:', err));
        }
    </script>
</body>
</html>
