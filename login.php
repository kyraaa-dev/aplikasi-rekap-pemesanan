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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.88) 0%, rgba(29, 78, 216, 0.85) 50%, rgba(30, 64, 175, 0.9) 100%), url('assets/images/bg-bupati-new.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        [data-theme="dark"] body {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.94) 0%, rgba(30, 27, 75, 0.94) 50%, rgba(49, 46, 129, 0.92) 100%), url('assets/images/bg-bupati-new.jpg');
            background-size: cover;
            background-position: center;
        }

        /* Ambient Glow Blobs */
        .ambient-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 1;
            pointer-events: none;
            opacity: 0.5;
        }
        .blob-1 {
            width: 380px;
            height: 380px;
            background: #ec4899;
            top: -100px;
            left: -100px;
            animation: pulseGlow 12s infinite alternate ease-in-out;
        }
        .blob-2 {
            width: 420px;
            height: 420px;
            background: #3b82f6;
            bottom: -120px;
            right: -100px;
            animation: pulseGlow 15s infinite alternate-reverse ease-in-out;
        }

        @keyframes pulseGlow {
            0% { transform: scale(1) translate(0, 0); opacity: 0.4; }
            100% { transform: scale(1.15) translate(30px, -20px); opacity: 0.65; }
        }

        /* Top Bar Actions (Theme Switcher) */
        .login-topbar {
            position: absolute;
            top: 1.25rem;
            right: 1.5rem;
            z-index: 50;
        }
        .btn-theme-login {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: #ffffff;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .btn-theme-login:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: scale(1.05);
        }
        [data-theme="dark"] .btn-theme-login {
            background: rgba(30, 41, 59, 0.6);
            border-color: rgba(255, 255, 255, 0.15);
        }

        /* Login Container & Card */
        .login-wrapper {
            width: 100%;
            max-width: 400px;
            padding: 16px;
            position: relative;
            z-index: 10;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 2.25rem 2rem 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.28), 0 0 0 1px rgba(255, 255, 255, 0.6) inset;
            border: 1px solid rgba(255, 255, 255, 0.6);
            position: relative;
            overflow: hidden;
            animation: cardAppear 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes cardAppear {
            from { opacity: 0; transform: translateY(18px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        [data-theme="dark"] .login-card {
            background: rgba(15, 23, 42, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05) inset;
        }

        /* Gradient Top Strip */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #3b82f6, #8b5cf6);
        }

        /* Header / Logo */
        .login-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .logo-wrap {
            width: 76px;
            height: 76px;
            margin: 0 auto 0.85rem;
            background: #ffffff;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        .logo-wrap:hover {
            transform: scale(1.05) rotate(-3deg);
        }
        .logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .login-title {
            font-size: 1.45rem;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
        }
        [data-theme="dark"] .login-title {
            color: #f8fafc;
        }

        .login-subtitle {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 400;
        }
        [data-theme="dark"] .login-subtitle {
            color: #94a3b8;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 0.825rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.4rem;
            letter-spacing: 0.01em;
        }
        [data-theme="dark"] .form-label {
            color: #cbd5e1;
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
            color: #94a3b8;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
            transition: color 0.2s ease;
        }

        .input-group input[type="text"],
        .input-group input[type="password"],
        .form-input {
            width: 100% !important;
            height: 46px !important;
            padding: 0 42px 0 44px !important;
            font-size: 0.95rem !important;
            color: #1e293b !important;
            background: #f8fafc !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            transition: all 0.2s ease !important;
            outline: none !important;
            box-sizing: border-box !important;
            position: relative;
            z-index: 1;
        }
        [data-theme="dark"] .input-group input[type="text"],
        [data-theme="dark"] .input-group input[type="password"],
        [data-theme="dark"] .form-input {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }

        .input-group input[type="text"]:focus,
        .input-group input[type="password"]:focus,
        .form-input:focus {
            background: #ffffff !important;
            border-color: #2563EB !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
        }
        [data-theme="dark"] .input-group input[type="text"]:focus,
        [data-theme="dark"] .input-group input[type="password"]:focus,
        [data-theme="dark"] .form-input:focus {
            background: #0f172a !important;
            border-color: #3B82F6 !important;
            box-shadow: 0 0 0 3.5px rgba(99, 102, 241, 0.25) !important;
        }
        .input-group:focus-within .input-icon-left {
            color: #2563EB !important;
        }
        [data-theme="dark"] .input-group:focus-within .input-icon-left {
            color: #818cf8 !important;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
            transition: color 0.2s, transform 0.2s;
        }
        .toggle-password:hover {
            color: #2563EB;
        }
        [data-theme="dark"] .toggle-password:hover {
            color: #818cf8;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            height: 44px;
            margin-top: 0.75rem;
            background: #2563EB;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .btn-submit:hover {
            background: #1D4ED8;
        }
        .btn-submit:active {
            background: #1E40AF;
        }
        .btn-submit.loading {
            pointer-events: none;
            opacity: 0.85;
        }

        @keyframes spin { 100% { transform: rotate(360deg); } }

        .footer-note {
            margin-top: 1.25rem;
            font-size: 0.75rem;
            color: #94a3b8;
            text-align: center;
        }

        /* Responsiveness for very small screens */
        @media (max-height: 600px) {
            html, body {
                height: auto;
                min-height: 100vh;
                overflow-y: auto;
            }
            body {
                padding: 1.5rem 0;
            }
            .logo-wrap {
                width: 60px;
                height: 60px;
                margin-bottom: 0.5rem;
            }
            .login-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <script>
        if(localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    </script>
    
    <!-- Ambient Blur Shapes -->
    <div class="ambient-blob blob-1"></div>
    <div class="ambient-blob blob-2"></div>

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
