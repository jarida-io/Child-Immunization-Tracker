<?php
// Session is already started in auth.php, so no need to start it again
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/auth.php';

$error = '';
$success = '';

//TFA is required
if (!isset($_SESSION['tfa_required']) || !$_SESSION['tfa_required']) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tfa_code = $_POST['tfa_code'] ?? '';
    
    if (empty($tfa_code)) {
        $error = "Please enter the verification code";
    } elseif (strlen($tfa_code) !== 6 || !is_numeric($tfa_code)) {
        $error = "Please enter a valid 6-digit code";
    } else {
        $pending_user = $_SESSION['pending_user'] ?? null;
        
        if (!$pending_user) {
            $error = "Session expired. Please login again.";
        } elseif (verifyTFACode($pending_user['email'], $tfa_code)) {
            // TFA successful, complete login
            completeLogin($pending_user);
            
            // Redirect based on role
            $redirectMap = [
                'admin' => '/pages/admin/admin_dashboard.php',
                'guardian' => '/pages/parent/dashboard.php',
                'socialcaregiver' => '/pages/caregiver/caregiver_dashboard.php',
                'healthcaregiver' => '/pages/doctor/doctor_dashboard.php',
            ];
            
            $userRole = strtolower($pending_user['role']);
            
            if (array_key_exists($userRole, $redirectMap)) {
                header('Location: ' . $redirectMap[$userRole]);
                exit();
            } else {
                header('Location: /pages/parent/dashboard.php');
                exit();
            }
        } else {
            $error = "Invalid or expired verification code";
        }
    }
}

// Resend functionality
if (isset($_POST['resend_code'])) {
    $pending_user = $_SESSION['pending_user'] ?? null;
    if ($pending_user) {
        $tfaCode = generateTFACode();
        if (sendTFACode($pending_user['email'], $tfaCode)) {
            $success = "New verification code sent to your email";
            logSystemEvent("TFA Code Resent", $pending_user['user_id']);
        } else {
            $error = "Failed to send verification code. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - ChildVax System</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <style>
        .tfa-container {
            max-width: 400px;
            margin: 4rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .tfa-icon {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1.2rem;
            text-align: center;
            letter-spacing: 0.5rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin: 0.5rem;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
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
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .info-text {
            color: #666;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="tfa-container">
        <h1>Two-Factor Authentication</h1>
        
        <p class="info-text">
            We've sent a 6-digit verification code to your email address.<br>
            Please enter it below to complete your login.
        </p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <input type="text" 
                       name="tfa_code" 
                       class="form-control" 
                       placeholder="000000" 
                       maxlength="6" 
                       pattern="[0-9]{6}" 
                       required 
                       autocomplete="off">
            </div>
            
            <button type="submit" class="btn">Verify Code</button>
            <button type="submit" name="resend_code" class="btn btn-secondary">Resend Code</button>
        </form>
        
        <div style="margin-top: 2rem;">
            <a href="login.php" style="color: #3498db; text-decoration: none;">← Back to Login</a>
        </div>
    </div>

    <script>
        // Auto-focus on the input field
        document.querySelector('input[name="tfa_code"]').focus();
        
        // Auto-submit when 6 digits are entered
        document.querySelector('input[name="tfa_code"]').addEventListener('input', function() {
            if (this.value.length === 6) {
                this.form.submit();
            }
        });
    </script>
</body>
</html> 