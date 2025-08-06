<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user is an admin
if (strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Get statistics
$stats = [
    'children' => $conn->query("SELECT COUNT(*) FROM children")->fetchColumn(),
    'vaccinations' => $conn->query("SELECT COUNT(*) FROM vaccination_schedule WHERE status = 'Completed'")->fetchColumn(),
    'pending' => $conn->query("SELECT COUNT(*) FROM vaccination_schedule WHERE status = 'Pending'")->fetchColumn(),
    'users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'missed' => $conn->query("SELECT COUNT(*) FROM vaccination_schedule WHERE status = 'Missed'")->fetchColumn(),
    'upcoming' => $conn->query("SELECT COUNT(*) FROM vaccination_schedule WHERE status = 'Pending' AND due_date > NOW()")->fetchColumn(),
];

// Get recent activities
$activities = $conn->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Vaccination System</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
            height: 40px;
            border-radius: 50%;
            margin-right: 0.5rem;
            object-fit: cover;
        }
        
        .container {
            max-width: 1200px;
            margin: 6rem auto 2rem;
            padding: 0 1.5rem;
        }
        
        .admin-section-title {
            margin-top: 60px;
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            border-left: 6px solid #007BFF;
            padding-left: 12px;
        }

        .admin-actions {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: flex-start;
            margin-top: 1.5rem;
        }

        .admin-btn {
            background: linear-gradient(145deg, #ffffff, #f0f0f0);
            border-radius: 16px;
            box-shadow: 5px 5px 15px rgba(0, 123, 255, 0.1), -5px -5px 15px rgba(0, 123, 255, 0.05);
            padding: 2rem;
            width: 220px;
            text-align: center;
            transition: all 0.3s ease-in-out;
            text-decoration: none;
            color: #333;
            position: relative;
            overflow: hidden;
        }

        .admin-btn:hover {
            transform: translateY(-5px);
            background: #007BFF;
            color: #fff;
            box-shadow: 0 10px 20px rgba(0, 123, 255, 0.3);
        }

        .admin-btn i {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            transition: transform 0.3s;
            color: #007BFF;
        }

        .admin-btn:hover i {
            transform: scale(1.2);
            color: #fff;
        }

        .admin-btn span {
            font-size: 1.1rem;
            font-weight: 600;
        }

        :root {
            --primary-color: #007BFF;
            --accent-color: #17a2b8;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f7f9fb;
            color: #333;
        }

        h1 {
            margin-bottom: 0.5rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff, #f0f8ff);
            border-left: 5px solid var(--primary-color);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: linear-gradient(135deg, #e0f0ff, #ffffff);
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--accent-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .recent-activity {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .recent-activity h2 {
            margin-top: 0;
            margin-bottom: 1rem;
        }

        .activity-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }

        @media (max-width: 600px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .admin-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    
    <div class="container">
        <h1>Admin Dashboard</h1>
        
        
        <div class="dashboard-grid">
            <a href="reports.php?type=children" class="stat-card">
                <i class="fas fa-child"></i>
                <div class="stat-number"><?= $stats['children']; ?></div>
                <div>Registered Children</div>
            </a>

            <a href="reports.php?type=vaccinations" class="stat-card">
                <i class="fas fa-syringe"></i>
                <div class="stat-number"><?= $stats['vaccinations']; ?></div>
                <div>Vaccinations Administered</div>
            </a>

            <a href="reports.php?type=pending" class="stat-card">
                <i class="fas fa-hourglass-half"></i>
                <div class="stat-number"><?= $stats['pending']; ?></div>
                <div>Pending Vaccinations</div>
            </a>

            <a href="reports.php?type=upcoming" class="stat-card">
                <i class="fas fa-calendar-alt"></i>
                <div class="stat-number"><?= $stats['upcoming']; ?></div>
                <div>Upcoming Vaccinations</div>
            </a>

            <a href="reports.php?type=missed" class="stat-card">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="stat-number"><?= $stats['missed']; ?></div>
                <div>Missed Vaccinations</div>
            </a>

            <a href="reports.php?type=users" class="stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-number"><?= $stats['users']; ?></div>
                <div>System Users</div>
            </a>
        </div>

        

         
        <h2 class="admin-section-title">Administration</h2>
        <div class="admin-actions admin-section">
            <a href="verify_vacc_cards.php" class="admin-btn">
                <i class="fas fa-id-badge"></i>
                <span>Verify Vaccination Cards</span>
            </a>

            <a href="manage_roles.php" class="admin-btn">
                <i class="fas fa-user-cog"></i>
                <span>Manage Roles</span>
            </a>

            <a href="manage_vaccines.php" class="admin-btn">
                <i class="fas fa-syringe"></i>
                <span>Manage Vaccines</span>
            </a>
        </div>

        <h2 class="admin-section-title">Reports & Analytics</h2>
        <div class="admin-actions admin-section">
            <a href="system_logs.php" class="admin-btn">
                <i class="fas fa-clipboard-list"></i>
                <span> System Logs</span>
            </a>
        </div>
    </div>
</body>
</html>