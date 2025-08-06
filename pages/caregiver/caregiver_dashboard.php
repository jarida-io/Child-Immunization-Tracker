<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';

if (($_SESSION['role'] ?? '') !== 'SocialCaregiver') {
    header('Location: ../auth/login.php');
    exit();
}

$caregiver_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_id = $_POST['child_id'] ?? '';

    if (empty($child_id)) {
        $error = "Please select a child";
    } else {
        try {
            $stmt = $conn->prepare("SELECT child_id FROM children WHERE child_id = ?");
            $stmt->execute([$child_id]);
            if (!$stmt->fetch()) {
                $error = "Child not found";
            } else {
                if (isset($_POST['assign_child'])) {
                    $stmt = $conn->prepare("INSERT INTO caregiver_assignments (caregiver_id, child_id) VALUES (?, ?)");
                    $stmt->execute([$caregiver_id, $child_id]);

                    // Update the caregiver_id in the children table
                    $stmt = $conn->prepare("UPDATE children SET caregiver_id = ? WHERE child_id = ?");
                    $stmt->execute([$caregiver_id, $child_id]);

                    $message = "Child successfully assigned to your profile";
                } elseif (isset($_POST['unassign_child'])) {
                    $stmt = $conn->prepare("DELETE FROM caregiver_assignments WHERE caregiver_id = ? AND child_id = ?");
                    $stmt->execute([$caregiver_id, $child_id]);

                    if ($stmt->rowCount() > 0) {
                        // Remove caregiver_id from children table
                        $stmt = $conn->prepare("UPDATE children SET caregiver_id = NULL WHERE child_id = ?");
                        $stmt->execute([$child_id]);

                        $message = "Child successfully unassigned from your profile";
                    } else {
                        $error = "Child was not assigned to you";
                    }
                }
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $error = "This child is already assigned to you";
            } else {
                $error = "Failed to process request. Please try again.";
            }
        }
    }
}

// Get assigned children
$assigned_children = [];
try {
    $stmt = $conn->prepare("
        SELECT c.child_id, c.name AS child_name, c.date_of_birth, u.name AS parent_name, u.location
        FROM children c
        JOIN caregiver_assignments ca ON c.child_id = ca.child_id
        JOIN users u ON c.guardian_id = u.user_id
        WHERE ca.caregiver_id = ? AND u.role = 'Guardian'
    ");
    $stmt->execute([$caregiver_id]);
    $assigned_children = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading assigned children";
}

// Get available children
$available_children = [];
try {
    $stmt = $conn->prepare("
        SELECT c.child_id, c.name AS child_name, c.date_of_birth, u.name AS parent_name, u.location
        FROM children c
        JOIN users u ON c.guardian_id = u.user_id
        WHERE u.role = 'Guardian'
          AND u.location = (SELECT location FROM users WHERE user_id = ? AND role = 'SocialCaregiver')
          AND c.child_id NOT IN (
              SELECT child_id FROM caregiver_assignments WHERE caregiver_id = ?
          )
    ");
    $stmt->execute([$caregiver_id, $caregiver_id]);
    $available_children = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading available children";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Social Caregiver Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <style>
        .children-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .children-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .child-card {
            background: white;
            border-radius: 10px;
            padding: 1.2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: 0.3s ease;
        }
        .child-card:hover {
            transform: scale(1.02);
        }
        .form-inline {
            display: inline;
        }
        .child-card h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        .child-info p {
            margin: 4px 0;
        }
        .parent-highlight {
            font-weight: bold;
            color: #333;
            background: #e9f5ff;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
        }
        .btn-sm {
            margin-top: 8px;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container">
    <h1>Welcome, Social Caregiver</h1>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Assigned Children -->
    <div class="children-section">
        <h2>Your Assigned Children</h2>
        <?php if (empty($assigned_children)): ?>
            <p>You currently have no children assigned to your profile.</p>
        <?php else: ?>
            <div class="children-grid">
                <?php foreach ($assigned_children as $child): ?>
                    <div class="child-card">
                        <h3>
                            <a href="/pages/parent/child_profile.php?child_id=<?php echo $child['child_id']; ?>">
                                <?php echo htmlspecialchars($child['child_name']); ?>
                            </a>
                        </h3>
                        <div class="child-info">
                            <p>Parent: <span class="parent-highlight"><?php echo htmlspecialchars($child['parent_name']); ?></span></p>
                            <p>Parent Location: <?php echo htmlspecialchars($child['location']); ?></p>
                            <p>Date of Birth: <?php echo htmlspecialchars($child['date_of_birth']); ?></p>
                        </div>
                        <a class="btn btn-info btn-sm" href="/pages/parent/child_profile.php?child_id=<?php echo $child['child_id']; ?>">View Profile</a>
                        <form class="form-inline" method="POST" action="">
                            <input type="hidden" name="child_id" value="<?php echo $child['child_id']; ?>">
                            <button type="submit" name="unassign_child" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to unassign this child?');">Unassign</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Available Children -->
    <div class="children-section">
        <h2>Available Children (in Your Location)</h2>
        <?php if (empty($available_children)): ?>
            <p>No available children found in your coverage area.</p>
        <?php else: ?>
            <div class="children-grid">
                <?php foreach ($available_children as $child): ?>
                    <div class="child-card">
                        <h3><?php echo htmlspecialchars($child['child_name']); ?></h3>
                        <div class="child-info">
                            <p>Parent: <span class="parent-highlight"><?php echo htmlspecialchars($child['parent_name']); ?></span></p>
                            <p>Parent Location: <?php echo htmlspecialchars($child['location']); ?></p>
                            <p>Date of Birth: <?php echo htmlspecialchars($child['date_of_birth']); ?></p>
                        </div>
                        <form class="form-inline" method="POST" action="">
                            <input type="hidden" name="child_id" value="<?php echo $child['child_id']; ?>">
                            <button type="submit" name="assign_child" class="btn btn-primary btn-sm">Assign to Me</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="/assets/js/scripts.js"></script>
</body>
</html>
