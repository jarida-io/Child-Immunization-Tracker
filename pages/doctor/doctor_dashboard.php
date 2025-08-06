<?php
ob_start();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$allowed_roles = ['healthcaregiver', 'doctor', 'healthcare_provider', 'admin'];
if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role']), $allowed_roles)) {
    $_SESSION['redirect_message'] = "Please login as a healthcare provider to access this page";
    header("Location: ../auth/login.php");
    ob_end_flush();
    exit();
}

// Database connection
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/functions.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    $schedule_id = $_POST['schedule_id'];
    $child_id = $_POST['child_id'];
    
    try {
        $conn->beginTransaction();
        
        // Update vaccination status
        $stmt = $conn->prepare("UPDATE vaccination_schedule SET status = 'Completed', completed_date = NOW(), administered_by = ? WHERE schedule_id = ?");
        $stmt->execute([$_SESSION['user_id'], $schedule_id]);
        
        // Update last vaccination info
        $stmt = $conn->prepare("UPDATE children SET last_vaccination_date = NOW(), last_vaccine_name = 
                              (SELECT v.name FROM vaccination_schedule vs 
                               JOIN vaccines v ON vs.vaccine_id = v.vaccine_id 
                               WHERE vs.schedule_id = ?) 
                              WHERE child_id = ?");
        $stmt->execute([$schedule_id, $child_id]);
        
        // Log the vaccination completion
        logSystemEvent('Marked vaccination as complete (Doctor)', $_SESSION['user_id']);
        
        $conn->commit();
        $_SESSION['success'] = "Vaccination marked as complete successfully!";
        
        
        header("Location: ".$_SERVER['PHP_SELF']."?child_id=".$child_id);
        ob_end_flush();
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error updating record: " . $e->getMessage();
        header("Location: ".$_SERVER['PHP_SELF']."?child_id=".$child_id);
        ob_end_flush();
        exit();
    }
}



$children = $conn->query("SELECT child_id, name FROM children ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle child selection
$selected_child = null;
$vaccination_schedule = [];
$child_id = $_GET['child_id'] ?? null;

if ($child_id && is_numeric($child_id)) {
    $stmt = $conn->prepare("SELECT * FROM children WHERE child_id = ?");
    $stmt->execute([$child_id]);
    $selected_child = $stmt->fetch(PDO::FETCH_ASSOC);
    
    
    $stmt = $conn->prepare("SELECT vs.*, v.name as vaccine_name, v.disease_prevented 
                           FROM vaccination_schedule vs 
                           JOIN vaccines v ON vs.vaccine_id = v.vaccine_id 
                           WHERE vs.child_id = ? 
                           ORDER BY vs.due_date");
    $stmt->execute([$child_id]);
    $vaccination_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Child Vaccination System</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding-top: 80px;
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .vaccine-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #3498db;
            transition: transform 0.2s;
        }
        
        .vaccine-card:hover {
            transform: translateY(-2px);
        }
        
        .vaccine-card.completed {
            border-left-color: #27ae60;
            background: #e8f5e9;
        }
        
        .vaccine-card.pending {
            border-left-color: #f39c12;
        }
        
        .vaccine-card.missed {
            border-left-color: #e74c3c;
        }
        
        .btn-mark {
            padding: 0.5rem 1rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-mark:hover {
            background: #2980b9;
        }
        
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    
    <div class="dashboard-container">
        <div class="card child-selector">
            <h2><i class="fas fa-child"></i> Select Child</h2>
            <form method="GET">
                <select name="child_id" onchange="this.form.submit()">
                    <option value="">-- Select a child --</option>
                    <?php foreach ($children as $child): ?>
                        <option value="<?= htmlspecialchars($child['child_id']) ?>" 
                            <?= ($child_id == $child['child_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($child['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            
            <?php if ($selected_child): ?>
                <div class="child-info">
                    <h3>Child Information</h3>
                    <p><strong>Name:</strong> <?= htmlspecialchars($selected_child['name']) ?></p>
                    <p><strong>DOB:</strong> <?= date('M j, Y', strtotime($selected_child['date_of_birth'])) ?></p>
                    <p><strong>Age:</strong> <?= calculateAge($selected_child['date_of_birth']) ?></p>
                    <p><strong>Gender:</strong> <?= htmlspecialchars($selected_child['gender']) ?></p>
                    <p><strong>Health ID:</strong> <?= htmlspecialchars($selected_child['health_id']) ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card child-details">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if ($selected_child): ?>
                <h2><i class="fas fa-syringe"></i> Vaccination Schedule</h2>
                
                <?php if (empty($vaccination_schedule)): ?>
                    <div class="alert alert-info">No vaccination schedule found for this child.</div>
                <?php else: ?>
                    <div class="vaccination-list">
                        <?php foreach ($vaccination_schedule as $vaccine): ?>
                            <div class="vaccine-card <?= strtolower($vaccine['status']) ?>">
                                <div style="display: flex; justify-content: space-between;">
                                    <h3><?= htmlspecialchars($vaccine['vaccine_name']) ?></h3>
                                    <span style="background: <?= 
                                        $vaccine['status'] === 'Completed' ? '#27ae60' : 
                                        ($vaccine['status'] === 'Pending' ? '#f39c12' : '#e74c3c') 
                                        ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 4px;">
                                        <?= $vaccine['status'] ?>
                                    </span>
                                </div>
                                <p><strong>Prevents:</strong> <?= htmlspecialchars($vaccine['disease_prevented']) ?></p>
                                <p><strong>Due Date:</strong> <?= date('M j, Y', strtotime($vaccine['due_date'])) ?></p>
                                <p><strong>Dose:</strong> <?= $vaccine['dose_number'] ?></p>
                                
                                <?php if ($vaccine['status'] === 'Pending'): ?>
                                    <form method="POST" style="margin-top: 1rem;">
                                        <input type="hidden" name="schedule_id" value="<?= $vaccine['schedule_id'] ?>">
                                        <input type="hidden" name="child_id" value="<?= $child_id ?>">
                                        <button type="submit" name="mark_complete" class="btn-mark">
                                            <i class="fas fa-check"></i> Mark as Completed
                                        </button>
                                    </form>
                                <?php elseif ($vaccine['status'] === 'Completed'): ?>
                                    <p><strong>Administered On:</strong> <?= date('M j, Y H:i', strtotime($vaccine['updated_at'])) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Please select a child to view vaccination details.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-refresh every 5 minutes
        setTimeout(function() {
            window.location.reload();
        }, 300000);
        
        // Confirm before marking as complete
        document.querySelectorAll('button[name="mark_complete"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to mark this vaccination as complete?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>