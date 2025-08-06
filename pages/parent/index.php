<?php
session_start();
$loggedIn = isset($_SESSION['user_id']);
$userRole = $loggedIn ? $_SESSION['role'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChildVax | Smart Vaccination Management</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --dark: #1b263b;
            --light: #f8f9fa;
            --success: #4cc9f0;
            --white: #ffffff;
            --gray: #6c757d;
            --light-gray: #e9ecef;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: var(--light);
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Navigation */
        .navbar {
            background-color: var(--white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand img {
            height: 32px;
            margin-right: 10px;
        }
        
        .navbar-nav {
            display: flex;
            list-style: none;
        }
        
        .nav-item {
            margin-left: 30px;
        }
        
        .nav-link {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            position: relative;
        }
        
        .nav-link:hover {
            color: var(--primary);
        }
        
        .nav-link.active:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-outline:hover {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .btn-lg {
            padding: 15px 30px;
            font-size: 18px;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero:before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background-image: url('/assets/images/hero-bg-pattern.png');
            background-size: cover;
            opacity: 0.1;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 600px;
        }
        
        .hero-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .hero-buttons {
            display: flex;
            gap: 15px;
        }
        
        .hero-image {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 50%;
            max-width: 600px;
        }
        
        /* Features Section */
        .section {
            padding: 80px 0;
        }
        
        .section-title {
            font-size: 36px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .section-subtitle {
            font-size: 18px;
            color: var(--gray);
            text-align: center;
            max-width: 700px;
            margin: 0 auto 50px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background-color: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background-color: rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .feature-icon img {
            width: 40px;
            height: 40px;
        }
        
        .feature-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .feature-text {
            color: var(--gray);
            line-height: 1.7;
        }
        
        /* About */
        .how-it-works {
            background-color: var(--light-gray);
        }
        
        .steps {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 30px;
            margin-top: 50px;
        }
        
        .step {
            flex: 1;
            min-width: 250px;
            max-width: 300px;
            text-align: center;
            position: relative;
        }
        
        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 40px;
            right: -30px;
            width: 30px;
            height: 2px;
            background-color: var(--primary-light);
        }
        
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background-color: var(--primary);
            color: var(--white);
            border-radius: 50%;
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .step-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .step-text {
            color: var(--gray);
        }
        
        /* Testimonials */
        .testimonials-slider {
            margin-top: 50px;
        }
        
        .testimonial-card {
            background-color: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin: 0 15px;
        }
        
        .testimonial-text {
            font-size: 18px;
            font-style: italic;
            margin-bottom: 20px;
            color: var(--dark);
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }
        
        .author-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .author-role {
            font-size: 14px;
            color: var(--gray);
        }
        
        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            padding: 80px 0;
            text-align: center;
        }
        
        .cta-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .cta-text {
            font-size: 18px;
            max-width: 700px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }
        
        /* Footer */
        .footer {
            background-color: var(--dark);
            color: var(--white);
            padding: 60px 0 20px;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-brand {
            max-width: 300px;
        }
        
        .footer-logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 20px;
            display: block;
        }
        
        .footer-text {
            opacity: 0.7;
            margin-bottom: 20px;
            line-height: 1.7;
        }
        
        .footer-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--white);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-link {
            margin-bottom: 10px;
        }
        
        .footer-link a {
            color: var(--light-gray);
            text-decoration: none;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .footer-link a:hover {
            opacity: 1;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            text-align: center;
            opacity: 0.7;
            font-size: 14px;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .hero-content {
                max-width: 100%;
                text-align: center;
            }
            
            .hero-image {
                display: none;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .step:not(:last-child):after {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 36px;
            }
            
            .section-title {
                font-size: 30px;
            }
            
            .navbar-nav {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container navbar-container">
            <a href="/" class="navbar-brand">
                ChildVax
            </a>
            
            <ul class="navbar-nav">
                <?php if ($loggedIn): ?>
                    <li class="nav-item"><a href="/pages/auth/register.php" class="nav-link active">Register An Account</a></li>
                <?php else: ?>
                    <li class="nav-item"><a href="/" class="nav-link active">Home</a></li>
                    <li class="nav-item"><a href="#features" class="nav-link">Features</a></li>
                    <li class="nav-item"><a href="#how-it-works" class="nav-link">About</a></li>
                    <li class="nav-item"><a href="/pages/auth/login.php" class="nav-link">Login</a></li>
                    <li class="nav-item"><a href="/pages/auth/register.php" class="btn btn-outline">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Never Miss a Child's Vaccination Again</h1>
                <p class="hero-subtitle">ChildVax helps parents and healthcare providers manage immunization schedules with automated reminders, digital records and seamless coordination.</p>
                
                <div class="hero-buttons">
                    <?php if ($loggedIn): ?>
                        <a href="/pages/dashboard.php" class="btn btn-primary btn-lg">Go to Dashboard</a>
                    <?php else: ?>
                        <a href="/pages/auth/register.php" class="btn btn-primary btn-lg">Get Started </a>
                        <a href="#how-it-works" class="btn btn-outline btn-lg">Learn More</a>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section">
        <div class="container">
            <h2 class="section-title">Features for Child Vaccination Tracking</h2>
            <p class="section-subtitle">Everything you need to manage childhood vaccinations in one secure platform</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <h3 class="feature-title">Automated Scheduling</h3>
                    <p class="feature-text">Smart reminders for upcoming vaccinations based on your child's age and medical history.</p>
                </div>
                
                <div class="feature-card">
                    <h3 class="feature-title">Digital Health Records</h3>
                    <p class="feature-text">Secure cloud storage for all immunization records accessible from any device.</p>
                </div>
                
                <div class="feature-card">
                    <h3 class="feature-title">Care Team Coordination</h3>
                    <p class="feature-text">Share records with doctors, schools, and caregivers with controlled permissions.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About -->
    <section id="how-it-works" class="section how-it-works">
        <div class="container">
            <h2 class="section-title">Simple Setup, Lasting Protection</h2>
            <p class="section-subtitle">Get started with ChildVax in just three easy steps</p>
            
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Create Your Account</h3>
                    <p class="step-text">Register as a parent or healthcare provider in under 2 minutes.</p>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Add Child Profiles</h3>
                    <p class="step-text">Enter basic information and existing vaccination records.</p>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Stay Protected</h3>
                    <p class="step-text">Receive automatic reminders and track immunization history.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2 class="cta-title">Ready to Simplify Vaccination Management?</h2>
            <p class="cta-text">Join thousands of parents and healthcare professionals using ChildVax to ensure no child misses their vital immunizations.</p>
            <a href="<?php echo $loggedIn ? '/pages/dashboard.php' : '/pages/auth/register.php'; ?>" class="btn btn-primary btn-lg">
                <?php echo $loggedIn ? 'Go to Dashboard' : 'Register Account'; ?>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="/" class="footer-logo">ChildVax</a>
                    <p class="footer-text">Comprehensive vaccination management for modern families and healthcare providers.</p>
                </div>
                
                <div>
                    <h3 class="footer-title">Legal</h3>
                    <ul class="footer-links">
                        <li class="footer-link">Privacy Policy</li>
                        <li class="footer-link">Terms of Service</li>
                        <li class="footer-link">Security</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="footer-title">Contact Us</h3>
                    <ul class="footer-links">
                        <li class="footer-link"><a href="mailto:hello@childvax.com">hello@childvax.com</a></li>
                        <li class="footer-link"><a href="tel:+254177777777">(254) 177-7777</a></li>
                        <li class="footer-link">123 Health St, Nairobi Ke</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> ChildVax. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>