<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$unread_count = 0;

if (isset($_SESSION['user_id'])) {
    try {
        require __DIR__ . '/../includes/db.php';
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        
        $stmt->execute([$_SESSION['user_id']]);
        $unread_count = $stmt->fetchColumn();
    } catch (PDOException $e) {
    }
}

// check if current page is a dashboard
function isDashboardPage() {
    $current_page = basename($_SERVER['PHP_SELF']);
    $dashboard_pages = [
        'admin_dashboard.php',
        'doctor_dashboard.php',
        'dashboard.php',
        'caregiver_dashboard.php'
    ];
    return in_array($current_page, $dashboard_pages);
}

// get dashboard link based on role
function getDashboardLink() {
    if (!isset($_SESSION['role'])) return '/pages/auth/login.php';
    
    $role = strtolower($_SESSION['role']);
    
    switch ($role) {
        case 'admin': 
            return '/pages/admin/admin_dashboard.php';
        case 'healthcaregiver': 
            return '/pages/doctor/doctor_dashboard.php';
        case 'guardian': 
            return '/pages/parent/dashboard.php';
        case 'socialcaregiver': 
            return '/pages/caregiver/caregiver_dashboard.php';
        default:
            return '/pages/auth/login.php';
    }
}

// get role display name
function getRoleDisplayName($role) {
    $role_names = [
        'admin' => 'Administrator',
        'healthcaregiver' => 'Healthcare Provider',
        'guardian' => 'Parent/Guardian',
        'socialcaregiver' => 'Social Caregiver'
    ];
    return $role_names[strtolower($role)] ?? ucfirst($role);
}
?>
<style>
    :root {
        --primary-color: #007BFF;
        --secondary-color: #0056b3;
        --accent-color: #e74c3c;
        --light-color: #ecf0f1;
        --dark-color: #2c3e50;
        --success-color: #27ae60;
        --warning-color: #f39c12;
        --white: #ffffff;
    }
    
    body {
        padding-top: 70px; 
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 2rem;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: var(--white);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 70px; 
        z-index: 1000;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .nav-logo {
        font-size: 1.8rem; 
        font-weight: 700;
        color: var(--white);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: opacity 0.3s ease;
    }
    
    .nav-logo:hover {
        opacity: 0.9;
        color: var(--white);
    }
    
    .main-content {
        position: relative;
        z-index: 1; 
        margin-top: 20px; 
    }
    
    .nav-user {
        display: flex;
        align-items: center;
        gap: 1rem;
        color: var(--white);
    }
    
    .user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.25rem;
    }
    
    .user-name {
        font-weight: 600;
        font-size: 1rem;
    }
    
    .user-role {
        font-size: 0.85rem;
        opacity: 0.9;
    }
    
    .btn {
        padding: 0.5rem 1rem;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.875rem;
    }
    
    .btn-primary {
        background-color: rgba(255, 255, 255, 0.2);
        color: var(--white);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .btn-primary:hover {
        background-color: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        color: var(--white);
    }
    
    .btn-danger {
        background-color: var(--accent-color);
        color: var(--white);
        border: 1px solid var(--accent-color);
    }
    
    .btn-danger:hover {
        background-color: #c0392b;
        border-color: #c0392b;
        color: var(--white);
    }
    
    .nav-links {
        display: flex;
        gap: 1.5rem;
        align-items: center;
        margin: 0 2rem;
    }
    
    .nav-link {
        text-decoration: none;
        color: var(--white);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        position: relative;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.2);
        color: var(--white);
    }
    
    .nav-link.active {
        background-color: rgba(255, 255, 255, 0.3);
        color: var(--white);
        font-weight: 600;
    }
    
    .notification-badge {
        background-color: var(--accent-color);
        color: var(--white);
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        position: absolute;
        top: -8px;
        right: -12px;
        font-weight: bold;
    }
    
    .menu-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--white);
        padding: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .navbar {
            padding: 0 1rem;
        }
        
        .nav-links {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            flex-direction: column;
            padding: 1rem;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
            gap: 0.5rem;
            margin: 0;
        }
        
        .nav-links.active {
            display: flex;
        }
        
        .nav-link {
            width: 100%;
            justify-content: flex-start;
            padding: 0.75rem 1rem;
        }
        
        .menu-toggle {
            display: block;
        }
        
        .nav-user {
            margin-left: auto;
        }
        
        .user-info {
            display: none;
        }
        
        .nav-logo {
            font-size: 1.5rem;
        }
    }
    
    @media (max-width: 480px) {
        .navbar {
            padding: 0 0.5rem;
        }
        
        .nav-logo {
            font-size: 1.3rem;
        }
        
        .btn-sm {
            padding: 0.3rem 0.6rem;
            font-size: 0.8rem;
        }
    }
</style>

<nav class="navbar">
    <a href="<?php echo getDashboardLink(); ?>" class="nav-logo">
        <i class="fas fa-shield-virus"></i> ChildVax
    </a>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="nav-links" id="navLinks">
            <a href="<?php echo getDashboardLink(); ?>" 
               class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === basename(getDashboardLink()) ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            
            <!-- Role-specific navigation -->
            <?php if (isset($_SESSION['role'])): ?>
                <?php $role = strtolower($_SESSION['role']); ?>
                
                <!-- Admin Navigation -->
                <?php if ($role === 'admin'): ?>
                    <a href="/pages/admin/verify_vacc_cards.php" 
                       class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'verify_vacc_cards.php' ? 'active' : ''; ?>">
                       <i class="fas fa-id-badge"></i> Verify Cards
                    </a>
                    <a href="/pages/admin/manage_roles.php" 
                       class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_roles.php' ? 'active' : ''; ?>">
                       <i class="fas fa-users-cog"></i> Manage Users
                    </a>
                    <a href="/pages/admin/manage_vaccines.php"
                       class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_vaccines.php' ? 'active' : ''; ?>">
                       <i class="fas fa-syringe"></i> Manage Vaccines
                    </a>
                
                <!-- Doctor/Healthcare Provider Navigation -->
                <?php elseif ($role === 'healthcaregiver'): ?>
                    <!-- No navigation links for healthcaregiver -->
                
                <!-- Parent/Guardian Navigation -->
                <?php elseif ($role === 'guardian'): ?>
                    <a href="/pages/parent/register_child.php" 
                       class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'register_child.php' ? 'active' : ''; ?>">
                       <i class="fas fa-child"></i> Register Child
                    </a>
                
                <!-- Social Caregiver Navigation -->
                <?php elseif ($role === 'socialcaregiver'): ?>
                    <a href="/pages/parent/register_child.php" 
                       class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'register_child.php' ? 'active' : ''; ?>">
                       <i class="fas fa-child"></i> Register Child
                    </a>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Notifications (for all authenticated users except admin and healthcaregiver) -->
            <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'healthcaregiver'): ?>
                <a href="/pages/notifications.php" 
                   class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
        
        <button class="menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>
    <?php endif; ?>
    
    <div class="nav-user">
        <?php if (isset($_SESSION['name'])): ?>
            <div class="user-info">
                <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <span class="user-role"><?php echo getRoleDisplayName($_SESSION['role'] ?? ''); ?></span>
            </div>
            <a href="/pages/logout.php" class="btn btn-sm btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        <?php else: ?>
            <a href="/pages/auth/login.php" class="btn btn-sm btn-primary">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
        <?php endif; ?>
    </div>
</nav>

<script>
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navLinks = document.getElementById('navLinks');
    
    if (mobileMenuToggle && navLinks) {
        mobileMenuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!navLinks.contains(event.target) && event.target !== mobileMenuToggle) {
                navLinks.classList.remove('active');
            }
        });
        
        // Close mobile menu when clicking on a link
        const navLinkElements = navLinks.querySelectorAll('.nav-link');
        navLinkElements.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.classList.remove('active');
            });
        });
    }
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">