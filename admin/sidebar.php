<?php
$user_role = $_SESSION['user_role'] ?? 'staff';
$current_page = basename($_SERVER['PHP_SELF']);

function isActive($page)
{
    global $current_page;
    return $current_page === $page ? 'active' : '';
}

$username = $_SESSION['username'] ?? 'System User';
?>

<!-- Mobile Toggle -->
<button class="sidebar-toggle d-lg-none" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>

<aside class="sidebar" id="mainSidebar">

    <!-- ================= Logo ================= -->
    <div class="sidebar-header">

        <div class="logo-wrapper">

            <img
                src="../assets/images/machakos_logo.png"
                alt="Machakos University Logo"
                class="sidebar-logo"
                onerror="this.style.display='none';document.getElementById('logoFallback').style.display='flex';">

            <div id="logoFallback" class="logo-fallback">
                <i class="fas fa-trophy"></i>
            </div>

            <div class="logo-text">
                <h4>Sports Management</h4>
                <span>Machakos University</span>
            </div>

        </div>

    </div>

    <!-- ================= User ================= -->
    <div class="sidebar-user">

        <div class="user-avatar">
            <i class="fas fa-user-circle"></i>
        </div>

        <div class="user-details">
            <h6><?php echo htmlspecialchars($username); ?></h6>
            <span>
                <?php echo ucfirst($user_role); ?>
            </span>
        </div>

    </div>

    <!-- ================= Navigation ================= -->
    <nav class="sidebar-menu">

        <ul>

            <li class="<?php echo isActive('dashboard.php'); ?>">
                <a href="dashboard.php">
                    <i class="fas fa-house"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="<?php echo isActive('players.php'); ?>">
                <a href="players.php">
                    <i class="fas fa-users"></i>
                    <span>Players</span>
                </a>
            </li>

            <li class="<?php echo isActive('issue_item.php'); ?>">
                <a href="issue_item.php">
                    <i class="fas fa-plus-circle"></i>
                    <span>Issue Equipment</span>
                </a>
            </li>

            <li class="<?php echo isActive('clearance.php'); ?>">
                <a href="clearance.php">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Clearance</span>
                </a>
            </li>

            <li class="<?php echo isActive('reports.php'); ?>">
                <a href="reports.php">
                    <i class="fas fa-chart-column"></i>
                    <span>Reports</span>
                </a>
            </li>

            <?php if ($user_role === 'admin'): ?>

                <li class="<?php echo isActive('manage_users.php'); ?>">
                    <a href="manage_users.php">
                        <i class="fas fa-user-gear"></i>
                        <span>Manage Users</span>
                    </a>
                </li>

                <li class="<?php echo isActive('activity_log.php'); ?>">
                    <a href="activity_log.php">
                        <i class="fas fa-clock-rotate-left"></i>
                        <span>Activity Log</span>
                    </a>
                </li>

            <?php endif; ?>

            <li class="menu-divider"></li>

            <li class="<?php echo isActive('about.php'); ?>">
                <a href="../about.php">
                    <i class="fas fa-circle-info"></i>
                    <span>About</span>
                </a>
            </li>

            <li class="<?php echo isActive('contact.php'); ?>">
                <a href="../contact.php">
                    <i class="fas fa-envelope"></i>
                    <span>Contact</span>
                </a>
            </li>

            <li class="menu-divider"></li>

            <li>

                <a
                    href="../logout.php"
                    class="logout-link"
                    onclick="return confirm('Are you sure you want to logout?');">

                    <i class="fas fa-right-from-bracket"></i>

                    <span>Logout</span>

                </a>

            </li>

        </ul>

    </nav>

    <!-- ================= Footer ================= -->
    <div class="sidebar-footer">

        <div class="version">
            Version 2.0
        </div>

        <small>
            © <?php echo date('Y'); ?> Machakos University
        </small>

    </div>

</aside>