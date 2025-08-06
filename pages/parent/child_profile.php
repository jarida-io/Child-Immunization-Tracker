<?php
session_start();

require __DIR__ . '/../../includes/db.php';

$allowed_roles = ['Parent', 'SocialCaregiver'];
if (!isset($_SESSION['user_id'])) {
    header('Location: /../auth/login.php');
    exit();
}

$child_id = $_GET['child_id'] ?? null;

if (!$child_id) {
    header('Location: dashboard.php');
    exit();
}

// Notification count (moved to top for navbar)
$unread_count = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Notification count error occurred
}

// Update status to "Missed" for overdue vaccines
$stmt = $conn->prepare("UPDATE vaccination_schedule
                        SET status = 'Missed'
                        WHERE due_date < NOW() AND status = 'Pending' AND child_id = ?");
$stmt->execute([$child_id]);

// Notify guardian about missed vaccines
require_once __DIR__ . '/../../includes/functions.php';
notifyMissedVaccines($child_id);

// Fetch child details
$stmt = $conn->prepare("
    SELECT c.* FROM children c
    LEFT JOIN caregiver_assignments ca ON c.child_id = ca.child_id
    WHERE c.child_id = ? 
    AND (c.guardian_id = ? OR ca.caregiver_id = ?)
");
$stmt->execute([$child_id, $_SESSION['user_id'], $_SESSION['user_id']]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch vaccination schedules
$stmt = $conn->prepare("SELECT vs.*, v.name as vaccine_name, v.disease_prevented, 
                       v.dose_description, v.route_of_administration, v.site_of_administration
                       FROM vaccination_schedule vs
                       JOIN vaccines v ON vs.vaccine_id = v.vaccine_id
                       WHERE vs.child_id = ?
                       ORDER BY vs.due_date");
$stmt->execute([$child_id]);
$schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$child) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($child['name']); ?>'s Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --border-radius: 8px;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        /* NAVBAR STYLES */
        body {
            padding-top: 70px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            z-index: 1000;
        }
        
        .nav-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        
        .nav-link {
            text-decoration: none;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            padding: 0.5rem 0;
        }
        
        .nav-link:hover {
            color: var(--primary-color);
        }
        
        .nav-link.active {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .notification-badge {
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            position: absolute;
            top: -5px;
            right: -10px;
        }
        
        .nav-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-role {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--dark-color);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            color: white;
        }
        
        .btn-danger:hover {
            opacity: 0.9;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* MAIN CONTENT STYLES */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        /* Profile specific styles */
        .profile-header {
            position: relative;
            overflow: hidden;
            padding: 0;
        }
        
        .profile-banner {
            height: 80px; /* Reduced height */
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .profile-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
            position: relative;
        }
        
        .profile-name {
            margin-top: 1rem;
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .profile-meta {
            display: flex;
            gap: 1.5rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--dark-color);
        }
        
        /* Timeline Styles */
        .timeline {
            position: relative;
            padding-left: 30px;
            margin-top: 2rem;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e0e0e0;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .timeline-marker {
            position: absolute;
            left: -30px;
            top: 0;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .timeline-item.pending .timeline-marker {
            background-color: var(--warning-color);
        }
        
        .timeline-item.completed .timeline-marker {
            background-color: var(--success-color);
        }
        
        .timeline-item.missed .timeline-marker {
            background-color: var(--accent-color);
        }
        
        .timeline-content {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
        }
        
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .timeline-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .timeline-body p {
            margin-bottom: 0.5rem;
        }
        
        .timeline-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        /* Filter Buttons */
        .schedule-filters {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .schedule-filters .btn {
            padding: 0.5rem 1rem;
        }
        
        .schedule-filters .btn.active {
            background-color: var(--dark-color);
            color: white;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
                height: auto;
                flex-wrap: wrap;
            }
            
            .nav-links {
                display: none;
                width: 100%;
                flex-direction: column;
                gap: 0.5rem;
                margin-top: 1rem;
            }
            
            .nav-links.active {
                display: flex;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .profile-meta {
                flex-direction: column;
                gap: 0.5rem;
                align-items: center;
            }
            
            .timeline::before {
                left: 10px;
            }
            
            .timeline-marker {
                left: -22px;
                width: 24px;
                height: 24px;
                font-size: 0.8rem;
            }
        }
        
        /* Schedule Actions */
        .schedule-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .schedule-actions .btn {
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }
        
        .schedule-actions .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .schedule-actions .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
        
        .schedule-actions .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .schedule-actions .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
        }
        
        .schedule-actions .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        /* Print styles */
        @media print {
            .navbar, .schedule-actions, .schedule-filters {
                display: none !important;
            }
            
            .container {
                max-width: none;
                margin: 0;
                padding: 20px;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .timeline-item {
                break-inside: avoid;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>

    <div class="container">
        <!-- Profile Header -->
        <div class="card profile-header">
            <div class="profile-banner"></div>
            <div class="profile-content">
                <h1 class="profile-name"><?php echo htmlspecialchars($child['name']); ?></h1>
                <div class="profile-meta">
                    <div class="meta-item">
                        <i class="fas fa-birthday-cake"></i>
                        <span><?php echo date('F j, Y', strtotime($child['date_of_birth'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-<?php echo strtolower($child['gender']) == 'male' ? 'mars' : 'venus'; ?>"></i>
                        <span><?php echo htmlspecialchars($child['gender']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-id-card"></i>
                        <span><?php echo htmlspecialchars($child['health_id']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vaccination Schedule -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2><i class="fas fa-calendar-check"></i> Vaccination Schedule</h2>
                <div class="schedule-actions">
                    <a href="print_schedule.php?child_id=<?php echo htmlspecialchars($child_id); ?>" 
                       class="btn btn-primary" 
                       target="_blank"
                       title="Download vaccination schedule as PDF">
                        <i class="fas fa-file-pdf"></i> Download Schedule
                    </a>
                    <button class="btn btn-secondary" onclick="printSchedule()" title="Print vaccination schedule">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            
            <?php if (empty($schedule)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No vaccination schedule found.
                </div>
            <?php else: ?>
                <div class="schedule-filters">
                    <button class="btn btn-sm active" data-filter="all">All Vaccines</button>
                    <button class="btn btn-sm" data-filter="pending">Pending</button>
                    <button class="btn btn-sm" data-filter="completed">Completed</button>
                    <button class="btn btn-sm" data-filter="missed">Missed</button>
                </div>
                
                <div class="timeline">
                    <?php foreach ($schedule as $vaccination): ?>
                        <div class="timeline-item <?php echo strtolower($vaccination['status']); ?>" data-status="<?php echo strtolower($vaccination['status']); ?>">
                            <div class="timeline-marker">
                                <?php if ($vaccination['status'] == 'Completed'): ?>
                                    <i class="fas fa-check"></i>
                                <?php elseif ($vaccination['status'] == 'Pending'): ?>
                                    <i class="fas fa-clock"></i>
                                <?php else: ?>
                                    <i class="fas fa-exclamation"></i>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h3 class="timeline-title"><?php echo htmlspecialchars($vaccination['vaccine_name']); ?></h3>
                                    <span class="status-badge <?php echo strtolower($vaccination['status']); ?>">
                                        <?php echo $vaccination['status']; ?>
                                    </span>
                                </div>
                                <div class="timeline-body">
                                    <p><strong>Prevents:</strong> <?php echo htmlspecialchars($vaccination['disease_prevented']); ?></p>
                                    <p><strong>Due Date:</strong> <?php echo date('M j, Y', strtotime($vaccination['due_date'])); ?></p>
                                    <p><strong>Dose:</strong> <?php echo $vaccination['dose_number']; ?> (<?php echo htmlspecialchars($vaccination['dose_description']); ?>)</p>
                                    <p><strong>Administration:</strong> 
                                        <?php 
                                        $admin = [];
                                        if (!empty($vaccination['route_of_administration'])) {
                                            $admin[] = htmlspecialchars($vaccination['route_of_administration']);
                                        }
                                        if (!empty($vaccination['site_of_administration'])) {
                                            $admin[] = htmlspecialchars($vaccination['site_of_administration']);
                                        }
                                        echo implode(' - ', $admin);
                                        ?>
                                    </p>
                                    <?php if (!empty($vaccination['side_effects'])): ?>
                                        <div class="side-effects">
                                            <button class="btn btn-sm btn-link toggle-effects">
                                                <i class="fas fa-chevron-down"></i> Show Side Effects
                                            </button>
                                            <div class="effects-content" style="display: none;">
                                                <p><?php echo htmlspecialchars($vaccination['side_effects']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            document.getElementById('navLinks').classList.toggle('active');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navLinks = document.getElementById('navLinks');
            const menuToggle = document.getElementById('mobileMenuToggle');
            
            if (!navLinks.contains(event.target) && event.target !== menuToggle) {
                navLinks.classList.remove('active');
            }
        });
        
        // Toggle side effects visibility
        document.querySelectorAll('.toggle-effects').forEach(btn => {
            btn.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const icon = this.querySelector('i');
                
                if (content.style.display === 'none') {
                    content.style.display = 'block';
                    icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                } else {
                    content.style.display = 'none';
                    icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                }
            });
        });
        
        // Filter timeline items
        document.querySelectorAll('.schedule-filters .btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.dataset.filter;
                
                // Update active button
                document.querySelectorAll('.schedule-filters .btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Filter items
                document.querySelectorAll('.timeline-item').forEach(item => {
                    if (filter === 'all' || item.dataset.status === filter) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
        
        // Print schedule function
        function printSchedule() {
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            const childName = '<?php echo htmlspecialchars($child['name']); ?>';
            const childDOB = '<?php echo date('F j, Y', strtotime($child['date_of_birth'])); ?>';
            const childHealthID = '<?php echo htmlspecialchars($child['health_id']); ?>';
            
            // Get the schedule data
            const scheduleItems = document.querySelectorAll('.timeline-item');
            let scheduleHTML = '';
            
            scheduleItems.forEach(item => {
                const vaccineName = item.querySelector('.timeline-title').textContent;
                const status = item.querySelector('.status-badge').textContent;
                const prevents = item.querySelector('p:first-of-type').textContent.replace('Prevents:', '').trim();
                const dueDate = item.querySelector('p:nth-of-type(2)').textContent.replace('Due Date:', '').trim();
                const dose = item.querySelector('p:nth-of-type(3)').textContent.replace('Dose:', '').trim();
                
                scheduleHTML += `
                    <tr>
                        <td>${vaccineName}</td>
                        <td>${prevents}</td>
                        <td>${dose}</td>
                        <td>${dueDate}</td>
                        <td>${status}</td>
                    </tr>
                `;
            });
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Vaccination Schedule - ${childName}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                        .child-info { margin-bottom: 20px; }
                        .child-info table { width: 100%; border-collapse: collapse; }
                        .child-info td { padding: 8px; border: 1px solid #ddd; }
                        .child-info td:first-child { font-weight: bold; background-color: #f5f5f5; }
                        .schedule-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        .schedule-table th, .schedule-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                        .schedule-table th { background-color: #f5f5f5; font-weight: bold; }
                        .status-completed { color: #28a745; font-weight: bold; }
                        .status-pending { color: #ffc107; font-weight: bold; }
                        .status-missed { color: #dc3545; font-weight: bold; }
                        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
                        @media print {
                            body { margin: 0; }
                            .header { margin-bottom: 20px; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Vaccination Schedule</h1>
                        <p>Child Vaccination System</p>
                    </div>
                    
                    <div class="child-info">
                        <table>
                            <tr>
                                <td>Child Name:</td>
                                <td>${childName}</td>
                            </tr>
                            <tr>
                                <td>Date of Birth:</td>
                                <td>${childDOB}</td>
                            </tr>
                            <tr>
                                <td>Health ID:</td>
                                <td>${childHealthID}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Vaccine</th>
                                <th>Disease Prevented</th>
                                <th>Dose</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${scheduleHTML}
                        </tbody>
                    </table>
                    
                    <div class="footer">
                        <p>Generated on: ${new Date().toLocaleDateString()} at ${new Date().toLocaleTimeString()}</p>
                        <p>This document is generated from the Child Vaccination System</p>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            
            // Wait for content to load then print
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }
    </script>
</body>
</html>