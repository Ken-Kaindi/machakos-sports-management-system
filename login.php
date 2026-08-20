<?php
/**
 * Login Page
 * Machakos University Sports Management System
 */

require_once 'includes/auth.php';

$error = '';
$success = '';
$username = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'admin/dashboard.php';

// Check for logout success message
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = "You have been logged out successfully.";
}

// Auto-login with remember me
if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
    if (autoLoginWithRememberMe()) {
        header("Location: " . $redirect);
        exit();
    }
}

$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $remember_me = isset($_POST['remember_me']) ? true : false;
        
        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password.";
        } elseif (strlen($username) > 50 || strlen($password) > 255) {
            $error = "Invalid input length.";
        } else {
            if (login($username, $password, $remember_me)) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                // Log activity only if function exists
if (function_exists('logActivity')) {
    logActivity('login', "User '{$username}' logged in");
}
                header("Location: " . $redirect);
                exit();
            } else {
                $error = "Invalid username or password. Please try again.";
            }
        }
    }
}

if (isLoggedIn()) {
    header("Location: " . $redirect);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Machakos University Sports Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           LOGIN PAGE STYLES
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            position: relative;
            overflow: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(41, 128, 185, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 50%, rgba(231, 76, 60, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 100%, rgba(241, 196, 15, 0.08) 0%, transparent 50%);
            animation: floatBg 20s ease-in-out infinite alternate;
            z-index: 0;
        }

        @keyframes floatBg {
            0% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(2%, -2%) rotate(2deg); }
            66% { transform: translate(-1%, 1%) rotate(-1deg); }
            100% { transform: translate(0, 0) rotate(0deg); }
        }

        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: floatParticle 15s infinite ease-in-out;
        }

        .particle:nth-child(1) { left: 10%; animation-delay: 0s; animation-duration: 12s; }
        .particle:nth-child(2) { left: 20%; animation-delay: 2s; animation-duration: 18s; }
        .particle:nth-child(3) { left: 30%; animation-delay: 4s; animation-duration: 14s; }
        .particle:nth-child(4) { left: 40%; animation-delay: 1s; animation-duration: 16s; }
        .particle:nth-child(5) { left: 50%; animation-delay: 3s; animation-duration: 20s; }
        .particle:nth-child(6) { left: 60%; animation-delay: 5s; animation-duration: 13s; }
        .particle:nth-child(7) { left: 70%; animation-delay: 2.5s; animation-duration: 17s; }
        .particle:nth-child(8) { left: 80%; animation-delay: 4.5s; animation-duration: 15s; }
        .particle:nth-child(9) { left: 90%; animation-delay: 1.5s; animation-duration: 19s; }
        .particle:nth-child(10) { left: 95%; animation-delay: 3.5s; animation-duration: 11s; }
        .particle:nth-child(11) { left: 5%; animation-delay: 5.5s; animation-duration: 16s; }
        .particle:nth-child(12) { left: 45%; animation-delay: 0.5s; animation-duration: 22s; }

        @keyframes floatParticle {
            0%, 100% { transform: translateY(0) scale(1); opacity: 0.3; }
            50% { transform: translateY(-100vh) scale(2); opacity: 0.8; }
        }

        /* ========== LOGIN CONTAINER ========== */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 28px;
            padding: 45px 40px 40px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 
                0 25px 80px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            transition: all 0.4s ease;
        }

        .login-card:hover {
            box-shadow: 
                0 35px 100px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        /* ========== LOGO ========== */
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .login-header .logo-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #f39c12, #e67e22);
            border-radius: 20px;
            font-size: 32px;
            color: white;
            margin-bottom: 15px;
            box-shadow: 0 10px 40px rgba(243, 156, 18, 0.3);
            transition: transform 0.3s ease;
        }

        .login-header .logo-icon:hover {
            transform: scale(1.05) rotate(-5deg);
        }

        .login-header h1 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .login-header h1 span {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
            margin-top: 4px;
            font-weight: 400;
        }

        /* ========== ALERTS ========== */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid rgba(231, 76, 60, 0.2);
            color: #f1948a;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.15);
            border: 1px solid rgba(46, 204, 113, 0.2);
            color: #7dcea0;
        }

        .alert i {
            font-size: 18px;
            flex-shrink: 0;
        }

        /* ========== FORM ========== */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }

        .form-group .input-wrapper {
            position: relative;
        }

        .form-group .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .form-group .input-wrapper input {
            width: 100%;
            padding: 14px 16px 14px 46px;
            background: rgba(255, 255, 255, 0.06);
            border: 1.5px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            color: white;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group .input-wrapper input::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }

        .form-group .input-wrapper input:focus {
            border-color: #f39c12;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(243, 156, 18, 0.1);
        }

        .form-group .input-wrapper input:focus + i,
        .form-group .input-wrapper input:focus ~ i {
            color: #f39c12;
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s ease;
            padding: 5px;
        }

        .password-toggle:hover {
            color: rgba(255, 255, 255, 0.6);
        }

        /* ========== REMEMBER ME ========== */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .remember-me:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        .remember-me input[type="checkbox"] {
            display: none;
        }

        .remember-me .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .remember-me input:checked + .custom-checkbox {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            border-color: #f39c12;
        }

        .remember-me input:checked + .custom-checkbox::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 11px;
            color: white;
        }

        /* ========== BUTTON ========== */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #f39c12, #e67e22);
            border: none;
            border-radius: 14px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.5px;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            transition: left 0.6s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 45px rgba(243, 156, 18, 0.35);
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .btn-login i {
            margin-right: 10px;
        }

        /* ========== FOOTER ========== */
        .login-footer {
            margin-top: 30px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 25px;
        }

        .login-footer .demo-credentials {
            background: rgba(255, 255, 255, 0.04);
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 18px;
        }

        .login-footer .demo-credentials p {
            color: rgba(255, 255, 255, 0.4);
            font-size: 13px;
            margin: 0;
        }

        .login-footer .demo-credentials strong {
            color: rgba(255, 255, 255, 0.7);
        }

        .login-footer .demo-credentials .warning {
            color: #f1948a;
            font-size: 12px;
            display: block;
            margin-top: 4px;
        }

        .login-footer .links {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .login-footer .links a {
            color: rgba(255, 255, 255, 0.35);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .login-footer .links a:hover {
            color: rgba(255, 255, 255, 0.7);
        }

        .login-footer .links a i {
            font-size: 12px;
        }

        .login-footer .version {
            margin-top: 15px;
            color: rgba(255, 255, 255, 0.15);
            font-size: 12px;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 480px) {
            .login-wrapper {
                padding: 12px;
            }

            .login-card {
                padding: 30px 22px 30px;
                border-radius: 20px;
            }

            .login-header h1 {
                font-size: 20px;
            }

            .login-header .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 26px;
            }

            .form-group .input-wrapper input {
                padding: 12px 14px 12px 42px;
                font-size: 14px;
            }

            .btn-login {
                padding: 14px;
                font-size: 15px;
            }

            .form-options {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .login-footer .links {
                flex-wrap: wrap;
                gap: 12px;
            }

            .login-footer .demo-credentials {
                padding: 12px 14px;
            }
        }

        @media (max-width: 360px) {
            .login-card {
                padding: 20px 16px 24px;
            }

            .login-header .logo-icon {
                width: 50px;
                height: 50px;
                font-size: 22px;
            }

            .login-header h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

    <!-- Animated Particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="login-wrapper">
        <div class="login-card">

            <!-- Header -->
            <div class="login-header">
                <div class="logo-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <h1>Sports <span>Management</span></h1>
                <p>Machakos University</p>
            </div>

            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="login.php<?php echo $redirect !== 'admin/dashboard.php' ? '?redirect=' . urlencode($redirect) : ''; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label for="username"><i class="fas fa-user" style="margin-right:6px;"></i> Username</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($username); ?>"
                               placeholder="Enter your username" required autofocus>
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock" style="margin-right:6px;"></i> Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" 
                               placeholder="Enter your password" required>
                        <i class="fas fa-lock"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword()" tabindex="-1">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember_me" value="1">
                        <span class="custom-checkbox"></span>
                        Remember Me
                    </label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <!-- Footer -->
            <div class="login-footer">
                <div class="demo-credentials">
                    <p>
                        <i class="fas fa-info-circle" style="color:rgba(255,255,255,0.3);"></i>
                        Demo: <strong>admin</strong> / <strong>password</strong>
                        <span class="warning">⚠️ Change password after first login!</span>
                    </p>
                </div>

                <div class="links">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
                    <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
                </div>

                <div class="version">
                    v1.0.0 &copy; <?php echo date('Y'); ?> Machakos University
                </div>
            </div>

        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!$error && !$success): ?>
                document.getElementById('username').focus();
            <?php endif; ?>
        });
    </script>

</body>
</html>