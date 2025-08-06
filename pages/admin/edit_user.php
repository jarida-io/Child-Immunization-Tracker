<?php
require __DIR__ . '/../../includes/auth.php';


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_roles.php');
    exit();
}

$user_id = intval($_GET['id']);

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: manage_roles.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    $valid_roles = ['admin', 'guardian', 'HealthcareGiver', 'SocialCaregiver'];
    if (!in_array($role, $valid_roles)) {
        $errors[] = 'Invalid role selected';
    }
    
    // Check if email already exists for another user
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = 'Email is already in use by another user';
    }
    
    // Update role
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE user_id = ?");
        if ($stmt->execute([$name, $email, $role, $user_id])) {
            $_SESSION['success_message'] = 'User updated successfully';
            header("Location: manage_roles.php");
            exit();
        } else {
            $errors[] = 'Failed to update user. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #4a6fa5;
            margin-top: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .error {
            color: #dc3545;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 4px;
        }
        
        .error ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4a6fa5;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #3a5a80;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .role-badge.admin {
            background-color: #d4edda;
            color: #155724;
        }
        
        .role-badge.editor {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .role-badge.user {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .role-badge.guest {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    <div class="container">
        <h1>Edit User</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="guardian" <?= $user['role'] === 'guardian' ? 'selected' : '' ?>>Guardian</option>
                    <option value="HealthcareGiver" <?= $user['role'] === 'HealthcareGiver' ? 'selected' : '' ?>>HealthcareGiver</option>
                    <option value="SocialCaregiver" <?= $user['role'] === 'SocialCaregiver' ? 'selected' : '' ?>>SocialCaregiver</option>
                </select>
                <div style="margin-top: 5px;">
                    Current role: <span class="role-badge <?= htmlspecialchars($user['role']) ?>">
                        <?= ucfirst(htmlspecialchars($user['role'])) ?>
                    </span>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Update User</button>
                <a href="manage_roles.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>