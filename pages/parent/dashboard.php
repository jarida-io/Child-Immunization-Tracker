<?php
// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Verify user is a parent/guardian
if (strtolower($_SESSION['role']) !== 'guardian') {
    session_destroy();
    header('Location: ../auth/login.php');
    exit();
}

// Handle location update if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['location'])) {
    $new_location = trim($_POST['location']);
    
    if (!empty($new_location)) {
        try {
            $stmt = $conn->prepare("UPDATE users SET location = ? WHERE user_id = ?");
            $stmt->execute([$new_location, $_SESSION['user_id']]);
            $_SESSION['success'] = "Location updated successfully!";
            logSystemEvent('Changed location', $_SESSION['user_id']);
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating location: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please select a valid location";
    }
    
    // Refresh the page to show updated location
    header("Location: dashboard.php");
    exit();
}

// Fetch user location
$location = '';
try {
    $stmt = $conn->prepare("SELECT location FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $location = $result['location'] ?? 'Not set';
} catch (PDOException $e) {
    $location = 'Unknown';
}

// Fetch notifications
$unread_count = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND status = 'Pending'");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
} catch (PDOException $e) {
    // Notification error occurred
}

// Fetch children with vaccination status
$children = [];
try {
    $stmt = $conn->prepare("
        SELECT c.*, 
               COUNT(vs.schedule_id) AS total_vaccines,
               SUM(CASE WHEN vs.status = 'Completed' THEN 1 ELSE 0 END) AS completed_vaccines
        FROM children c
        LEFT JOIN vaccination_schedule vs ON c.child_id = vs.child_id
        WHERE c.guardian_id = ?
        GROUP BY c.child_id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error loading children data";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - Child Vaccination System</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        


        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .children-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .child-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            padding-bottom: 1.5rem;
            box-sizing: border-box;
            overflow: visible;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
        }

        .child-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .child-card .btn {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin-top: auto;
            padding-top: 1rem;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            background-color: var(--primary-color) !important;
            color: white !important;
            border: none !important;
            padding: 0.75rem 1.5rem !important;
            border-radius: 5px !important;
            text-decoration: none !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
        }

        .child-card .btn:hover {
            background-color: var(--secondary-color) !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .child-name {
            margin-top: 0;
            color: var(--primary-color);
        }

        .vaccine-progress {
            margin: 1rem 0;
        }

        .progress-bar {
            height: 10px;
            background-color: #eee;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--success-color);
        }

        .progress-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .btn {
            display: inline-block !important;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 10 !important;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        @media (max-width: 768px) {
            .children-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><i class="fas fa-child"></i> Your Children</h2>
            
            <?php if (empty($children)): ?>
                <div class="empty-state">
                    <p><i class="fas fa-child"></i> No children registered yet.</p>
                </div>
            <?php else: ?>
                <div class="children-grid">
                    <?php foreach ($children as $child): ?>
                        <div class="child-card">
                            <h3 class="child-name"><?php echo htmlspecialchars($child['name']); ?></h3>
                            <p><strong>Date of Birth:</strong> <?php echo date('M j, Y', strtotime($child['date_of_birth'])); ?></p>
                            <p><strong>Age:</strong> <?php echo calculateAge($child['date_of_birth']); ?></p>
                            
                            <div class="vaccine-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php 
                                        echo ($child['total_vaccines'] > 0) 
                                            ? round(($child['completed_vaccines']/$child['total_vaccines'])*100) 
                                            : 0; ?>%">
                                    </div>
                                </div>
                                <p class="progress-text">
                                    <?php echo $child['completed_vaccines'] ?? 0; ?> of <?php echo $child['total_vaccines'] ?? 0; ?> vaccines completed
                                </p>
                            </div>
                            
                            <a href="child_profile.php?child_id=<?php echo $child['child_id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2><i class="fas fa-map-marker-alt"></i> Update Your Location</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="location">Current Location:</label>
                    <select name="location" id="location" class="form-control" required>
                        <option value="">-- Select County --</option>
                        <?php
                        $counties = ["Baringo", "Bomet", "Bungoma", "Busia", "Elgeyo-Marakwet", "Embu", 
                                    "Garissa", "Homa Bay", "Isiolo", "Kajiado", "Kakamega", "Kericho", 
                                    "Kiambu", "Kilifi", "Kirinyaga", "Kisii", "Kisumu", "Kitui", "Kwale", 
                                    "Laikipia", "Lamu", "Machakos", "Makueni", "Mandera", "Marsabit", 
                                    "Meru", "Migori", "Mombasa", "Murang'a", "Nairobi", "Nakuru", 
                                    "Nandi", "Narok", "Nyamira", "Nyandarua", "Nyeri", "Samburu", 
                                    "Siaya", "Taita Taveta", "Tana River", "Tharaka-Nithi", 
                                    "Trans Nzoia", "Turkana", "Uasin Gishu", "Vihiga", "Wajir", "West Pokot"];
                        
                        foreach ($counties as $county) {
                            $selected = ($location === $county) ? 'selected' : '';
                            echo "<option value=\"$county\" $selected>$county</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Location
                </button>
            </form>
        </div>

        <div class="card">
            <div class="action-buttons">
                <a href="register_child.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add Child
                </a>
                <a href="../notifications.php" class="btn btn-primary">
                    <i class="fas fa-bell"></i> Notifications (<?php echo $unread_count; ?>)
                </a>
            </div>
        </div>
    </div>
</body>
</html>