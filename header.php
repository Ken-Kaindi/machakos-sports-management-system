<?php
/**
 * Site Header
 * Machakos University Sports Management System
 */

// Only include auth.php
require_once 'includes/auth.php';

$current_page = basename($_SERVER['PHP_SELF']);
function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machakos University - Sports Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ============================================
           HEADER STYLES - ENHANCED
           ============================================ */
        .site-header {
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            padding: 12px 0;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 3px solid #f39c12;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        /* Logo */
        .logo a {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: white;
            transition: transform 0.3s ease;
        }
        .logo a:hover {
            transform: scale(1.02);
        }
        .logo img {
            height: 55px;
            width: auto;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.3));
        }
        .logo .logo-text h1 {
            font-size: 20px;
            margin: 0;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #f39c12);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }
        .logo .logo-text small {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 300;
            display: block;
            margin-top: -2px;
            letter-spacing: 0.5px;
        }
        .logo .logo-icon {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo .logo-icon i {
            font-size: 32px;
            color: #f39c12;
            filter: drop-shadow(0 2px 8px rgba(243,156,18,0.3));
        }

        /* Navigation */
        .main-nav {
            display: flex;
            align-items: center;
        }

        .mobile-menu-toggle {
            display: none;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            font-size: 22px;
            cursor: pointer;
            padding: 8px 14px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .mobile-menu-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-links {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 4px;
            align-items: center;
        }

        .nav-links li {
            margin: 0;
        }

        .nav-links li a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            position: relative;
        }

        .nav-links li a i {
            font-size: 15px;
            opacity: 0.7;
        }

        .nav-links li a:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .nav-links li.active a {
            color: white;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: inset 0 -3px 0 #f39c12;
        }

        .nav-links li.active a i {
            opacity: 1;
            color: #f39c12;
        }

        /* Login button special style */
        .nav-links li:last-child a {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 9px 22px;
            border-radius: 25px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
        }
        .nav-links li:last-child a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(243, 156, 18, 0.4);
        }
        .nav-links li:last-child a i {
            opacity: 1;
            color: white;
        }
        /* But don't apply to logout button */
        .nav-links li:last-child a[href*="logout"] {
            background: rgba(231, 76, 60, 0.2);
            box-shadow: none;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        .nav-links li:last-child a[href*="logout"]:hover {
            background: rgba(231, 76, 60, 0.35);
        }
        /* Dashboard link */
        .nav-links li a[href*="dashboard"] {
            background: rgba(46, 204, 113, 0.15);
            border: 1px solid rgba(46, 204, 113, 0.2);
        }
        .nav-links li a[href*="dashboard"]:hover {
            background: rgba(46, 204, 113, 0.25);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                padding: 0 15px;
            }
            .logo img {
                height: 42px;
            }
            .logo .logo-text h1 {
                font-size: 16px;
            }
            .logo .logo-text small {
                font-size: 9px;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .nav-links {
                display: none;
                flex-direction: column;
                width: 100%;
                padding: 15px 0 5px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                margin-top: 10px;
                gap: 2px;
            }

            .nav-links.show {
                display: flex;
            }

            .nav-links li a {
                padding: 12px 16px;
                width: 100%;
                border-radius: 6px;
            }

            .nav-links li:last-child a,
            .nav-links li a[href*="dashboard"] {
                border-radius: 6px;
                width: 100%;
                justify-content: center;
            }

            .nav-links li.active a {
                box-shadow: inset -3px 0 0 #f39c12;
            }
        }

        @media (max-width: 480px) {
            .logo img {
                height: 35px;
            }
            .logo .logo-text h1 {
                font-size: 14px;
            }
            .logo .logo-icon i {
                font-size: 24px;
            }
            .nav-links li a {
                font-size: 13px;
                padding: 10px 14px;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-content">
            <div class="logo">
                <a href="index.php">
                    <img src="assets/images/machakos_logo.png" 
                         alt="Machakos University Logo"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="logo-icon" style="display:none;">
                        <i class="fas fa-trophy"></i>
                        <div class="logo-text">
                            <h1>Sports Management</h1>
                            <small>Machakos University</small>
                        </div>
                    </div>
                    <div class="logo-text" style="display:block;">
                        <h1>Sports Management</h1>
                        <small>Machakos University</small>
                    </div>
                </a>
            </div>
            
            <nav class="main-nav">
                <button class="mobile-menu-toggle" aria-label="Toggle Menu" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
                
                <ul class="nav-links" id="navLinks">
                    <li class="<?php echo isActive('index.php'); ?>">
                        <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="<?php echo isActive('about.php'); ?>">
                        <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
                    </li>
                    <li class="<?php echo isActive('contact.php'); ?>">
                        <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
                    </li>
                    
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <li>
                            <a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        </li>
                        <li>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="<?php echo isActive('login.php'); ?>">
                            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <script>
        function toggleMobileMenu() {
            const nav = document.getElementById('navLinks');
            nav.classList.toggle('show');
        }
        
        // Close menu on outside click
        document.addEventListener('click', function(e) {
            const nav = document.getElementById('navLinks');
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (nav.classList.contains('show') && !nav.contains(e.target) && !toggle.contains(e.target)) {
                nav.classList.remove('show');
            }
        });
    </script>