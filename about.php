<?php
/**
 * About Us Page
 * Machakos University Sports Management System
 */

require_once 'includes/auth.php';

include_once 'header.php';
?>

<style>
/* ABOUT PAGE STYLES */
.about-page {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 40px 20px 60px;
    min-height: 70vh;
}
.about-page .container { max-width: 1200px; margin: 0 auto; }

.about-hero {
    background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%);
    padding: 50px 40px;
    border-radius: 20px;
    text-align: center;
    color: white;
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(26,82,118,0.3);
}
.about-hero .hero-icon { font-size: 50px; display: block; color: #f39c12; margin-bottom: 10px; }
.about-hero h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 8px; }
.about-hero .subtitle { font-size: 1.1rem; opacity: 0.9; font-weight: 300; }
.about-hero .breadcrumb { margin-top: 12px; font-size: 13px; opacity: 0.7; }
.about-hero .breadcrumb a { color: white; text-decoration: none; }
.about-hero .breadcrumb a:hover { text-decoration: underline; }

.about-content {
    background: rgba(255,255,255,0.95);
    border-radius: 20px;
    padding: 40px 45px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.08);
    margin-bottom: 40px;
}
.about-content .intro {
    font-size: 1.05rem;
    line-height: 1.9;
    color: #444;
    padding: 18px 25px;
    background: #f8f9fa;
    border-radius: 12px;
    border-left: 4px solid #2980b9;
    margin-bottom: 30px;
}
.about-content .intro i { color: #2980b9; margin-right: 8px; }

.section-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 35px 0 18px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e8f0fe;
}
.section-header .icon-wrapper {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: 18px;
}
.section-header .icon-wrapper.blue { background: #dbeafe; color: #2563eb; }
.section-header .icon-wrapper.green { background: #d1fae5; color: #059669; }
.section-header .icon-wrapper.orange { background: #fef3c7; color: #d97706; }
.section-header .icon-wrapper.purple { background: #ede9fe; color: #7c3aed; }
.section-header h2 { font-size: 1.4rem; color: #1a5276; margin: 0; font-weight: 600; }

.mission-vision {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin: 5px 0 10px;
}
.mv-box {
    padding: 22px 28px;
    border-radius: 14px;
    transition: all 0.3s ease;
}
.mv-box:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.08); }
.mv-box.mission { background: linear-gradient(135deg, #dbeafe, #eff6ff); border-left: 4px solid #2563eb; }
.mv-box.vision { background: linear-gradient(135deg, #d1fae5, #ecfdf5); border-left: 4px solid #059669; }
.mv-box .mv-icon { font-size: 24px; margin-bottom: 5px; display: block; }
.mv-box.mission .mv-icon { color: #2563eb; }
.mv-box.vision .mv-icon { color: #059669; }
.mv-box h3 { font-size: 1.1rem; margin: 0 0 5px; color: #1a5276; }
.mv-box p { color: #555; line-height: 1.7; font-size: 0.95rem; margin: 0; }

.objectives-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
    gap: 12px;
    margin: 10px 0 5px;
}
.objective-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 18px;
    background: #f8fafc;
    border-radius: 10px;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}
.objective-item:hover {
    background: white;
    border-color: #dbeafe;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    transform: translateY(-2px);
}
.objective-item .check {
    width: 24px; height: 24px; border-radius: 50%;
    background: #d1fae5; color: #059669;
    display: flex; align-items: center; justify-content: center; font-size: 12px;
    flex-shrink: 0; margin-top: 2px;
}
.objective-item .text { color: #444; font-size: 0.95rem; line-height: 1.5; }

.team-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin: 15px 0 5px;
}
.team-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    background: white;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 600;
    color: #1a5276;
    border: 2px solid #e8f0fe;
    transition: all 0.3s ease;
    cursor: default;
}
.team-tag:hover {
    background: #1a5276;
    color: white;
    border-color: #1a5276;
    transform: translateY(-4px) scale(1.03);
    box-shadow: 0 8px 30px rgba(26,82,118,0.25);
}
.team-tag .emoji { font-size: 18px; }

.stats-section {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin: 20px 0 5px;
}
.stat-card {
    text-align: center;
    padding: 20px 15px;
    background: linear-gradient(135deg, #f8fafc, #eef2f7);
    border-radius: 14px;
    transition: all 0.3s ease;
}
.stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 30px rgba(0,0,0,0.08); }
.stat-card .number { font-size: 2.4rem; font-weight: 800; color: #1a5276; display: block; line-height: 1.2; }
.stat-card .number .plus { color: #2980b9; font-size: 1.5rem; }
.stat-card .label { font-size: 13px; color: #666; margin-top: 3px; display: block; font-weight: 500; }
.stat-card .icon { font-size: 24px; color: #2980b9; margin-bottom: 5px; display: block; }

.cta-section {
    text-align: center;
    margin-top: 35px;
    padding: 30px;
    background: linear-gradient(135deg, #1a5276, #2980b9);
    border-radius: 16px;
    color: white;
}
.cta-section h3 { margin: 0 0 8px; font-size: 1.4rem; }
.cta-section p { margin: 0 0 18px; opacity: 0.9; }
.cta-section .btn-cta {
    display: inline-block;
    padding: 10px 28px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    margin: 0 5px;
}
.cta-section .btn-cta.primary { background: white; color: #1a5276; }
.cta-section .btn-cta.primary:hover { transform: scale(1.05); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
.cta-section .btn-cta.secondary { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); }
.cta-section .btn-cta.secondary:hover { background: rgba(255,255,255,0.25); }

@media (max-width: 992px) {
    .stats-section { grid-template-columns: repeat(2, 1fr); }
    .mission-vision { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .about-page { padding: 20px 12px 40px; }
    .about-hero { padding: 30px 20px; }
    .about-hero h1 { font-size: 2rem; }
    .about-content { padding: 20px 16px; }
    .objectives-grid { grid-template-columns: 1fr; }
    .stats-section { grid-template-columns: 1fr 1fr; }
    .team-tags { justify-content: center; }
    .team-tag { font-size: 12px; padding: 8px 16px; }
    .cta-section .btn-cta { display: block; margin: 8px auto; max-width: 250px; }
}
@media (max-width: 480px) {
    .about-hero h1 { font-size: 1.5rem; }
    .stats-section { grid-template-columns: 1fr 1fr; }
    .stat-card .number { font-size: 1.4rem; }
}
</style>

<main class="about-page">
    <div class="container">
        
        <!-- Hero Banner -->
        <div class="about-hero">
            <span class="hero-icon"><i class="fas fa-trophy"></i></span>
            <h1>About Machakos University Sports Management</h1>
            <p class="subtitle">Empowering sports excellence through efficient management</p>
            <div class="breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <span> / About Us</span>
            </div>
        </div>
        
        <div class="about-content">
            
            <div class="intro">
                <i class="fas fa-quote-left"></i>
                The Machakos University Sports Management System is a comprehensive platform 
                designed to streamline the management of sports activities and athletes within 
                the university. Our system provides a centralized solution for administrators, 
                coaches, and student athletes.
            </div>
            
            <!-- Mission & Vision -->
            <div class="mission-vision">
                <div class="mv-box mission">
                    <span class="mv-icon"><i class="fas fa-bullseye"></i></span>
                    <h3>Our Mission</h3>
                    <p>To promote sports excellence at Machakos University by providing an efficient, transparent, and user-friendly platform for managing all sports-related activities.</p>
                </div>
                <div class="mv-box vision">
                    <span class="mv-icon"><i class="fas fa-eye"></i></span>
                    <h3>Our Vision</h3>
                    <p>To be the leading sports management system in Kenyan universities, fostering talent and nurturing champions through technology-driven excellence.</p>
                </div>
            </div>
            
            <!-- Objectives -->
            <div class="section-header">
                <div class="icon-wrapper blue"><i class="fas fa-list-check"></i></div>
                <h2>Our Objectives</h2>
            </div>
            
            <div class="objectives-grid">
                <div class="objective-item">
                    <span class="check"><i class="fas fa-check"></i></span>
                    <span class="text">Efficient registration and management of student athletes</span>
                </div>
                <div class="objective-item">
                    <span class="check"><i class="fas fa-check"></i></span>
                    <span class="text">Tracking of sports equipment issued to students</span>
                </div>
                <div class="objective-item">
                    <span class="check"><i class="fas fa-check"></i></span>
                    <span class="text">Clearance system for athletes returning equipment</span>
                </div>
                <div class="objective-item">
                    <span class="check"><i class="fas fa-check"></i></span>
                    <span class="text">Generation of reports for sports department administration</span>
                </div>
                <div class="objective-item">
                    <span class="check"><i class="fas fa-check"></i></span>
                    <span class="text">Promoting sports excellence at Machakos University</span>
                </div>
                <div class="objective-item">
                    <span class="check"><i class="fas fa-check"></i></span>
                    <span class="text">Real-time tracking of player performance and progress</span>
                </div>
            </div>
            
            <!-- Teams -->
            <div class="section-header">
                <div class="icon-wrapper orange"><i class="fas fa-users"></i></div>
                <h2>Our Teams</h2>
            </div>
            
            <p style="color: #555; line-height: 1.7; margin-bottom: 15px;">
                We support various sports teams that compete at both regional and national levels.
                Our athletes regularly participate in inter-university competitions and have brought 
                numerous trophies to the university.
            </p>
            
            <div class="team-tags">
                <span class="team-tag"><span class="emoji">⚽</span> Football</span>
                <span class="team-tag"><span class="emoji">🏀</span> Basketball</span>
                <span class="team-tag"><span class="emoji">🏐</span> Volleyball</span>
                <span class="team-tag"><span class="emoji">🏃</span> Athletics</span>
                <span class="team-tag"><span class="emoji">🏉</span> Rugby</span>
                <span class="team-tag"><span class="emoji">🥅</span> Netball</span>
                <span class="team-tag"><span class="emoji">🤾</span> Handball</span>
                <span class="team-tag"><span class="emoji">🏊</span> Swimming</span>
            </div>
            
            <!-- Stats -->
            <div class="section-header">
                <div class="icon-wrapper purple"><i class="fas fa-chart-simple"></i></div>
                <h2>Our Impact</h2>
            </div>
            
            <div class="stats-section">
                <div class="stat-card">
                    <span class="icon"><i class="fas fa-users"></i></span>
                    <span class="number">1,000<span class="plus">+</span></span>
                    <span class="label">Student Athletes</span>
                </div>
                <div class="stat-card">
                    <span class="icon"><i class="fas fa-flag"></i></span>
                    <span class="number">10<span class="plus">+</span></span>
                    <span class="label">Sports Teams</span>
                </div>
                <div class="stat-card">
                    <span class="icon"><i class="fas fa-trophy"></i></span>
                    <span class="number">50<span class="plus">+</span></span>
                    <span class="label">Trophies Won</span>
                </div>
                <div class="stat-card">
                    <span class="icon"><i class="fas fa-calendar-check"></i></span>
                    <span class="number">100<span class="plus">+</span></span>
                    <span class="label">Events Hosted</span>
                </div>
            </div>
            
            <!-- CTA -->
            <div class="cta-section">
                <h3>Ready to Join Our Sports Community?</h3>
                <p>Become part of Machakos University's rich sports tradition.</p>
                <a href="contact.php" class="btn-cta primary">
                    <i class="fas fa-envelope"></i> Contact Us Today
                </a>
                <a href="login.php" class="btn-cta secondary">
                    <i class="fas fa-sign-in-alt"></i> Admin Login
                </a>
            </div>
            
        </div>
    </div>
</main>

<?php include_once 'footer.php'; ?>