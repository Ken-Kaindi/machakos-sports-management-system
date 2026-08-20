<?php
/**
 * Homepage
 * Machakos University Sports Management System
 */

require_once 'includes/auth.php';

// If logged in, go to dashboard
if (function_exists('isLoggedIn') && isLoggedIn()) {
    header("Location: admin/dashboard.php");
    exit();
}

include_once 'header.php';
?>

<main class="home-content">
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="page-title">Welcome to Machakos University Sports Management System</h1>
            <p class="hero-description">
                Efficiently manage sports teams, player registrations, equipment clearance, 
                and generate comprehensive reports all in one place.
            </p>
            
            <div class="hero-buttons">
                <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                    <a href="admin/dashboard.php" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Admin Login
                    </a>
                <?php endif; ?>
                <a href="about.php" class="btn btn-secondary">
                    <i class="fas fa-info-circle"></i> Learn More
                </a>
            </div>
        </div>
        
        <div class="hero-image">
            <img src="assets/images/sports_hero.jpg" 
                 alt="University Sports Activities"
                 onerror="this.src='assets/images/default_hero.jpg'">
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features">
        <h2 class="section-title">Key Features</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-users feature-icon"></i>
                <h3 class="feature-title">Player Registration</h3>
                <p class="feature-description">
                    Register and manage student athletes across all sports teams with ease.
                </p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-clipboard-check feature-icon"></i>
                <h3 class="feature-title">Clearance System</h3>
                <p class="feature-description">
                    Track equipment issued to students and manage returns efficiently.
                </p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-chart-bar feature-icon"></i>
                <h3 class="feature-title">Reporting</h3>
                <p class="feature-description">
                    Generate detailed reports on equipment usage and player status.
                </p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-users-cog feature-icon"></i>
                <h3 class="feature-title">Team Management</h3>
                <p class="feature-description">
                    Organize and manage multiple sports teams and their members.
                </p>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="stats-section">
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Players</h3>
                <p><?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM players");
                        echo number_format($stmt->fetchColumn());
                    } catch (PDOException $e) {
                        echo '0';
                    }
                ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Sports Teams</h3>
                <p><?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(DISTINCT team) FROM players WHERE team IS NOT NULL");
                        echo number_format($stmt->fetchColumn());
                    } catch (PDOException $e) {
                        echo '0';
                    }
                ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Items Available</h3>
                <p><?php 
                    try {
                        $stmt = $pdo->query("SELECT SUM(quantity) FROM items");
                        echo number_format($stmt->fetchColumn());
                    } catch (PDOException $e) {
                        echo '0';
                    }
                ?></p>
            </div>
        </div>
    </section>
    
    <!-- Call to Action -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>Ready to Get Started?</h2>
            <p>Login to the admin dashboard to manage your sports activities.</p>
            <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                <a href="admin/dashboard.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-arrow-right"></i> Go to Dashboard
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-arrow-right"></i> Login Now
                </a>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include_once 'footer.php'; ?>