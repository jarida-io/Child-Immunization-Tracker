<?php

ob_start(); // Prevent "headers already sent" error
session_start(); 

error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    define('APP_ROOT', dirname(__DIR__, 2));

    require APP_ROOT . '/includes/db.php';
    require APP_ROOT . '/includes/functions.php';
    require APP_ROOT . '/includes/auth.php'; // Add this line to include auth functions

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Validate email format
    if (empty($email)) {
        $error = "Please enter your email address";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } 
    // Only check if password is empty (no complexity checks)
    elseif (empty($password)) {
        $error = "Please enter your password";
    } 
    else {
        try {
                    // Use the loginUser function from auth.php which includes logging
        $loginResult = loginUser($email, $password);
        
        if ($loginResult === 'tfa_required') {
            // TFA is required, redirect to TFA verification page
            header('Location: tfa_verify.php');
            exit();
        } elseif ($loginResult === true) {
            // Login successful, redirect based on role
            $redirectMap = [
                'admin' => '/pages/admin/admin_dashboard.php',
                'guardian' => '/pages/parent/dashboard.php',
                'SocialCaregiver' => '/pages/caregiver/caregiver_dashboard.php',
                'HealthcareGiver' => '/pages/doctor/doctor_dashboard.php',
                'doctor' => '/pages/doctor/doctor_dashboard.php',
                'healthcaregiver' => '/pages/doctor/doctor_dashboard.php',
                'socialcaregiver' => '/pages/caregiver/caregiver_dashboard.php',
                'healthcare_provider' => '/pages/doctor/doctor_dashboard.php'
            ];
            
            // Get the role and convert to lowercase for case-insensitive matching
            $userRole = strtolower($_SESSION['role']);
            
            if (array_key_exists($userRole, $redirectMap)) {
                header('Location: ' . $redirectMap[$userRole]);
                exit();
            } else {
                // Fallback for unknown roles - redirect to a default page
                header('Location: /pages/parent/dashboard.php');
                exit();
            }
        } else {
            $error = "Invalid email or password";
        }
        } catch (PDOException $e) {
            $error = "System error. Please try again later.";
        }
    }
}

ob_end_flush(); // Ensure output is sent correctly
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ChildVax System</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }
        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .alert {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 style="text-align: center; color: #3498db;">ChildVax</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
        
        <div style="text-align: center; margin-top: 1rem;">
            <a href="register.php" style="color: #3498db;">Register</a>
            <span> | </span>
            <a href="forgot_password.php" style="color: #3498db;">Forgot Password?</a>
        </div>
    </div>
</body>
</html>