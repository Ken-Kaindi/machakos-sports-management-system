<?php
/**
 * Site Footer
 * Machakos University Sports Management System
 */

// Check if user is logged in for footer links
$isLoggedIn = function_exists('isLoggedIn') && isLoggedIn();
?>

    </main> <!-- Close main-content -->

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                    <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact Us</a></li>
                    <?php if ($isLoggedIn): ?>
                        <li><a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Contact Information</h4>
                <p><i class="fas fa-map-marker-alt"></i> Machakos University, Machakos, Kenya</p>
                <p><i class="fas fa-phone"></i> +254 123 456 789</p>
                <p><i class="fas fa-envelope"></i> sports@machakos.ac.ke</p>
            </div>
            
            <div class="footer-section">
                <h4>Follow Us</h4>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Machakos University - Sports Management System. All rights reserved.</p>
            <p>Version <?php echo defined('APP_VERSION') ? APP_VERSION : '1.0.0'; ?></p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="assets/js/script.js"></script>
    
    <!-- Toast notifications container -->
    <div id="toast-container"></div>

</body>
</html>