<?php
/**
 * Contact Us Page
 * Machakos University Sports Management System
 */

require_once 'includes/auth.php';

$success = '';
$error = '';
$name = '';
$email = '';
$subject = '';
$message = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid security token. Please try again.";
    } else {
        if (!isset($_SESSION['contact_attempts'])) {
            $_SESSION['contact_attempts'] = 0;
        }
        if ($_SESSION['contact_attempts'] >= 3) {
            $error = "You have reached the maximum number of messages (3). Please try again later.";
        } else {
            $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $subject = htmlspecialchars(trim($_POST['subject'] ?? ''), ENT_QUOTES, 'UTF-8');
            $message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');
            
            if (empty($name) || empty($email) || empty($subject) || empty($message)) {
                $error = "All fields are required!";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format!";
            } else {
                $_SESSION['contact_attempts']++;
                $success = "Thank you for your message, $name! We will get back to you soon.";
                if (function_exists('logActivity')) {
                    logActivity('contact_form', "Contact form submitted by: $name ($email)");
                }
                $name = $email = $subject = $message = '';
            }
        }
    }
}

include_once 'header.php';
?>

<style>
.contact-page {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 40px 20px 60px;
    min-height: 70vh;
}
.contact-page .container { max-width: 1200px; margin: 0 auto; }

.contact-hero {
    background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%);
    padding: 40px 40px;
    border-radius: 20px;
    text-align: center;
    color: white;
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(26,82,118,0.3);
}
.contact-hero .hero-icon { font-size: 50px; display: block; color: #f39c12; margin-bottom: 10px; }
.contact-hero h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 8px; }
.contact-hero .subtitle { font-size: 1.1rem; opacity: 0.9; font-weight: 300; }
.contact-hero .breadcrumb { margin-top: 12px; font-size: 13px; opacity: 0.7; }
.contact-hero .breadcrumb a { color: white; text-decoration: none; }
.contact-hero .breadcrumb a:hover { text-decoration: underline; }

.contact-wrapper {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 30px;
}

.contact-form-card {
    background: rgba(255,255,255,0.95);
    border-radius: 20px;
    padding: 35px 40px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.08);
}
.contact-form-card h2 { color: #1a5276; font-size: 1.5rem; margin-bottom: 5px; }
.contact-form-card .form-subtitle { color: #888; font-size: 14px; margin-bottom: 25px; display: block; }

.form-group { margin-bottom: 18px; }
.form-group label { display: block; font-weight: 500; color: #333; margin-bottom: 5px; font-size: 13px; }
.form-group label i { margin-right: 6px; color: #f39c12; }
.form-group input, .form-group textarea {
    width: 100%; padding: 10px 14px;
    border: 1.5px solid #e9ecef; border-radius: 10px;
    font-size: 14px; background: #fafbfc;
    transition: all 0.3s ease; color: #333; font-family: inherit;
}
.form-group input:focus, .form-group textarea:focus {
    border-color: #f39c12; background: white;
    outline: none; box-shadow: 0 0 0 4px rgba(243,156,18,0.08);
}
.form-group textarea { resize: vertical; min-height: 120px; }

.btn-send {
    background: linear-gradient(135deg, #1a5276, #2980b9);
    color: white; padding: 12px 30px; border: none; border-radius: 10px;
    font-size: 16px; font-weight: 600; cursor: pointer;
    transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 10px;
}
.btn-send:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(26,82,118,0.25); }

.contact-info-card {
    background: rgba(255,255,255,0.95);
    border-radius: 20px;
    padding: 35px 30px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.08);
    height: fit-content;
}
.contact-info-card h3 { color: #1a5276; font-size: 1.2rem; margin-bottom: 5px; }
.contact-info-card .info-subtitle { color: #888; font-size: 13px; margin-bottom: 25px; display: block; }

.contact-detail {
    display: flex; align-items: flex-start; gap: 15px;
    padding: 14px 0; border-bottom: 1px solid #f0f0f0;
}
.contact-detail:last-of-type { border-bottom: none; }
.contact-detail .icon {
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: 18px;
    flex-shrink: 0;
}
.contact-detail .icon.blue { background: #dbeafe; color: #2563eb; }
.contact-detail .icon.green { background: #d1fae5; color: #059669; }
.contact-detail .icon.orange { background: #fef3c7; color: #d97706; }
.contact-detail .icon.red { background: #fee2e2; color: #dc2626; }
.contact-detail .content .label { font-size: 12px; color: #999; text-transform: uppercase; letter-spacing: 0.3px; font-weight: 600; }
.contact-detail .content .value { font-size: 14px; color: #333; font-weight: 500; }

.map-container { margin-top: 20px; border-radius: 12px; overflow: hidden; }
.map-container iframe { width: 100%; height: 220px; border: none; display: block; }

.alert {
    padding: 14px 20px; border-radius: 10px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 12px;
    animation: slideDown 0.4s ease;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-15px); }
    to { opacity: 1; transform: translateY(0); }
}
.alert-success { background: linear-gradient(135deg, #d4edda, #b7ebc5); color: #0b5e2e; border: 1px solid #b7ebc5; }
.alert-danger { background: linear-gradient(135deg, #f8d7da, #f5c6cb); color: #721c24; border: 1px solid #f5c6cb; }
.alert i { font-size: 20px; }

@media (max-width: 992px) { .contact-wrapper { grid-template-columns: 1fr; } }
@media (max-width: 768px) {
    .contact-page { padding: 20px 12px 40px; }
    .contact-hero { padding: 30px 20px; }
    .contact-hero h1 { font-size: 2rem; }
    .contact-form-card { padding: 25px 20px; }
    .contact-info-card { padding: 25px 20px; }
}
</style>

<main class="contact-page">
    <div class="container">
        
        <!-- Hero Banner -->
        <div class="contact-hero">
            <span class="hero-icon"><i class="fas fa-envelope"></i></span>
            <h1>Contact Us</h1>
            <p class="subtitle">We'd love to hear from you. Get in touch with us today.</p>
            <div class="breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <span> / Contact Us</span>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Contact Content -->
        <div class="contact-wrapper">
            
            <!-- Form -->
            <div class="contact-form-card">
                <h2><i class="fas fa-paper-plane" style="color:#f39c12;"></i> Send Us a Message</h2>
                <span class="form-subtitle">Fill in the form below and we'll get back to you.</span>
                
                <form action="contact.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="name"><i class="fas fa-user"></i> Your Name</label>
                        <input type="text" id="name" name="name" required placeholder="Enter your full name" value="<?php echo htmlspecialchars($name); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Your Email</label>
                        <input type="email" id="email" name="email" required placeholder="Enter your email address" value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject"><i class="fas fa-tag"></i> Subject</label>
                        <input type="text" id="subject" name="subject" required placeholder="Enter message subject" value="<?php echo htmlspecialchars($subject); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="message"><i class="fas fa-comment"></i> Message</label>
                        <textarea id="message" name="message" required placeholder="Enter your message here..."><?php echo htmlspecialchars($message); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-send">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
            
            <!-- Contact Info -->
            <div class="contact-info-card">
                <h3><i class="fas fa-address-card" style="color:#f39c12;"></i> Contact Information</h3>
                <span class="info-subtitle">Reach out to us through any of these channels.</span>
                
                <div class="contact-detail">
                    <div class="icon blue"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="content">
                        <div class="label">Location</div>
                        <div class="value">Machakos University, Sports Complex, Machakos, Kenya</div>
                    </div>
                </div>
                
                <div class="contact-detail">
                    <div class="icon green"><i class="fas fa-phone"></i></div>
                    <div class="content">
                        <div class="label">Phone</div>
                        <div class="value">+254 712 345 678</div>
                    </div>
                </div>
                
                <div class="contact-detail">
                    <div class="icon orange"><i class="fas fa-envelope"></i></div>
                    <div class="content">
                        <div class="label">Email</div>
                        <div class="value">sports@machakos.ac.ke</div>
                    </div>
                </div>
                
                <div class="contact-detail">
                    <div class="icon red"><i class="fas fa-clock"></i></div>
                    <div class="content">
                        <div class="label">Working Hours</div>
                        <div class="value">Mon - Fri: 8:00 AM - 5:00 PM<br>Saturday: 9:00 AM - 1:00 PM</div>
                    </div>
                </div>
                
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.7551!2d37.2641!3d-1.5168!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f9e2e5d5d5d5d%3A0x5d5d5d5d5d5d5d5d!2sMachakos%20University!5e0!3m2!1sen!2ske!4v1690000000000!5m2!1sen!2ske" allowfullscreen="" loading="lazy" title="Machakos University Location"></iframe>
                </div>
            </div>
            
        </div>
    </div>
</main>

<?php include_once 'footer.php'; ?>