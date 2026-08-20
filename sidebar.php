<?php
/**
 * Admin Sidebar
 * Machakos University Sports Management System
 */

// Get user role from session
$user_role = $_SESSION['user_role'] ?? 'staff';
$current_page = basename($_SERVER['PHP_SELF']);

// Helper function for active class
function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
?>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/machakos_logo.png" 
             alt="Machakos University Logo"
             onerror="this.src='../assets/images/default_logo.png'">
        <h3>Sports Management</h3>
    </div>
    
    <div class="sidebar-menu">
        <ul>
            <!-- Dashboard -->
            <li class="<?php echo isActive('dashboard.php'); ?>">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <!-- Players -->
            <li class="<?php echo isActive('players.php'); ?>">
                <a href="players.php">
                    <i class="fas fa-users"></i> Players
                </a>
            </li>
            
            <!-- Clearance -->
            <li class="<?php echo isActive('clearance.php'); ?>">
                <a href="clearance.php">
                    <i class="fas fa-clipboard-check"></i> Clearance
                </a>
            </li>
            
            <!-- Reports -->
            <li class="<?php echo isActive('reports.php'); ?>">
                <a href="reports.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            
            <!-- Manage Users - Admin Only -->
            <?php if ($user_role === 'admin'): ?>
                <li class="<?php echo isActive('manage_users.php'); ?>">
                    <a href="manage_users.php">
                        <i class="fas fa-user-cog"></i> Manage Users
                    </a>
                </li>
            <?php endif; ?>
            
            <!-- Divider -->
            <li class="divider"></li>
            
            <!-- Public Pages -->
            <li class="<?php echo isActive('about.php'); ?>">
                <a href="../about.php">
                    <i class="fas fa-info-circle"></i> About Us
                </a>
            </li>
            <li class="<?php echo isActive('contact.php'); ?>">
                <a href="../contact.php">
                    <i class="fas fa-envelope"></i> Contact Us
                </a>
            </li>
            
            <!-- Divider -->
            <li class="divider"></li>
            
            <!-- Logout -->
            <li>
                <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <small>Version <?php echo defined('APP_VERSION') ? APP_VERSION : '1.0.0'; ?></small>
        <small>&copy; <?php echo date('Y'); ?> Machakos University</small>
    </div>
</div>