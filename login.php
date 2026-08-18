<?php
require 'config.php'; // Includes session_start()

if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $username_safe = $conn->real_escape_string($username);
    $q = $conn->query("SELECT admin_password FROM settings WHERE admin_username = '$username_safe' LIMIT 1");
    
    if ($q && $q->num_rows > 0) {
        $row = $q->fetch_assoc();
        // Since we are not using hashed passwords yet based on previous code, we just do a direct comparison
        if ($password === $row['admin_password']) {
            $_SESSION['is_logged_in'] = true;
            header("Location: index.php");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-MutZ KORPRI - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        html, body {
            height: 100vh !important;
            max-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }

        body {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.85) 0%, rgba(139, 92, 246, 0.85) 100%), url('assets/images/bg-bupati-new.jpg');
            background-size: cover;
            background-position: center;
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }
        
        [data-theme="dark"] body {
            background: linear-gradient(135deg, rgba(30, 27, 75, 0.9) 0%, rgba(76, 29, 149, 0.9) 100%), url('assets/images/bg-bupati-new.jpg');
            background-size: cover;
            background-position: center;
        }

        @media (max-height: 650px), (max-width: 480px) {
            html, body {
                overflow-y: auto !important;
                height: auto !important;
                max-height: none !important;
            }
            body {
                padding: 1.5rem 0 !important;
            }
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        .login-box {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 3rem 2.5rem;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            width: 100%;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }
        
        [data-theme="dark"] .login-box {
            background: rgba(30, 27, 75, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Staggered Animations for inside elements */
        .login-box > * {
            opacity: 0;
            transform: translateY(20px);
            animation: slideUpStagger 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .logo-container { animation-delay: 0.1s; }
        .login-box h2 { animation-delay: 0.2s; }
        .login-subtitle { animation-delay: 0.3s; }
        .alert-custom { animation-delay: 0.35s; }
        .form-group:nth-of-type(1) { animation-delay: 0.4s; }
        .form-group:nth-of-type(2) { animation-delay: 0.5s; }
        .btn-login { animation-delay: 0.6s; }

        @keyframes slideUpStagger {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Decorative colorful top border inside card */
        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #10B981, #3B82F6, #8B5CF6);
            z-index: 20;
        }

        .login-box h2 {
            text-align: center;
            margin-bottom: 0.3rem;
            color: var(--dark);
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .login-subtitle {
            text-align: center;
            color: var(--gray);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-group input {
            background-color: var(--light);
            border: 2px solid transparent;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
            color: var(--dark);
        }

        .form-group input:focus {
            background-color: var(--white);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
            outline: none;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 38px;
            cursor: pointer;
            color: var(--gray);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }

        .toggle-password:hover {
            color: var(--primary);
            background-color: rgba(79, 70, 229, 0.1);
        }
        
        .icon-pop {
            transform: scale(1.3) rotate(15deg);
        }

        .btn-login {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.9rem;
            font-size: 1.05rem;
            font-weight: 600;
            border-radius: 12px;
            margin-top: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, #6366f1 100%);
            box-shadow: 0 4px 14px 0 rgba(79, 70, 229, 0.39);
            border: none;
            color: white;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
        }
        
        .btn-loading {
            pointer-events: none;
            opacity: 0.8;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .logo-circle {
            width: 150px;
            height: 150px;
            background: rgba(79, 70, 229, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        
        [data-theme="dark"] .logo-circle {
            background: transparent;
            box-shadow: none;
            filter: drop-shadow(0 0 15px rgba(255,255,255,0.3));
        }
        
        .alert-custom {
            margin-bottom: 1.5rem;
            border-radius: 10px;
            border: none;
            background-color: #FEE2E2;
            color: #991B1B;
            padding: 12px 15px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        [data-theme="dark"] .alert-custom {
            background-color: rgba(220, 38, 38, 0.2);
            color: #FCA5A5;
        }
        
        /* Floating background shapes for coolness */
        @keyframes float1 {
            0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
            33% { transform: translate(30px, -50px) scale(1.1) rotate(10deg); }
            66% { transform: translate(-20px, 20px) scale(0.9) rotate(-5deg); }
        }
        @keyframes float2 {
            0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
            33% { transform: translate(-40px, 60px) scale(1.15) rotate(-10deg); }
            66% { transform: translate(30px, -30px) scale(0.85) rotate(5deg); }
        }

        .bg-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            z-index: 1;
            opacity: 0.6;
        }
        .shape1 {
            width: 300px;
            height: 300px;
            background: #EC4899;
            top: -100px;
            left: -100px;
            animation: float1 15s infinite ease-in-out;
        }
        .shape2 {
            width: 400px;
            height: 400px;
            background: #3B82F6;
            bottom: -150px;
            right: -100px;
            animation: float2 18s infinite ease-in-out reverse;
        }
    </style>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <script>
        if(localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    </script>
    
    <!-- Decorative background blobs -->
    <div class="bg-shape shape1"></div>
    <div class="bg-shape shape2"></div>
    
    <div class="login-wrapper">
        <div class="login-box">
            <div class="logo-container">
                <div class="logo-circle" style="overflow: hidden; padding: 15px; background: white;">
                    <img src="assets/images/logo.png?v=2" alt="Logo E-MutZ" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
            </div>
            
            <h2>E-MutZ KORPRI</h2>
            <p class="login-subtitle">Sistem Manajemen & Rekapitulasi</p>
            
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
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Masukkan admin" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                    <div class="toggle-password" onclick="toggleVisibility()">
                        <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </div>
                </div>
                <button type="submit" class="btn-login">
                    Masuk ke Dashboard
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 4px;"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </button>
            </form>
        </div>
    </div>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Animasi Togle Password
        function toggleVisibility() {
            var pwd = document.getElementById("password");
            var iconBtn = document.querySelector(".toggle-password");
            var icon = document.getElementById("eye-icon");
            
            iconBtn.classList.add('icon-pop');
            setTimeout(() => iconBtn.classList.remove('icon-pop'), 300);

            if (pwd.type === "password") {
                pwd.type = "text";
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
                iconBtn.style.color = 'var(--primary)';
            } else {
                pwd.type = "password";
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
                iconBtn.style.color = '';
            }
        }

        // Efek Loading Saat Submit Form
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.querySelector('.btn-login');
            btn.classList.add('btn-loading');
            btn.innerHTML = 'Memproses... <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 4px; animation: spin 1s linear infinite;"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>';
        });
    </script>
</body>
</html>
