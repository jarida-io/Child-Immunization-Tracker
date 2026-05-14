<?php
// auth.php - Handles authentication, registration, TFA, and session management
// Sets timezone for consistency

date_default_timezone_set('Africa/Nairobi');

require 'db.php';
require_once 'functions.php'; 

//TFA Functions 


 //Generate a 6-digit TFA code

function generateTFACode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Send a TFA code to the user's email and store it in the database
 
function sendTFACode($email, $code) {
    global $conn;
    //expiry 10 min
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $stmt = $conn->prepare("INSERT INTO tfa_codes (email, code, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE code = ?, expires_at = ?");
    $stmt->execute([$email, $code, $expires, $code, $expires]);
    // Send code via email using PHPMailer
    require_once __DIR__ . '/../vendor/autoload.php';
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'wanjirupurity203@gmail.com';
        $mail->Password   = 'bwwo aekr zcjv vhfa'; 
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('wanjirupurity203@gmail.com', 'Child Vaccination System');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'CHILD VAX 2FA Code';
        $mail->Body    = "Your verification code is: <strong>$code</strong><br><br>This code expires in 10 minutes.";
        return $mail->send();
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("TFA Email Error: " . $e->getMessage());
        return false;
    }
}

// Verify a TFA code for a given email
 
function verifyTFACode($email, $code) {
    global $conn;
    // Check if code exists and is not expired
    $stmt = $conn->prepare("SELECT * FROM tfa_codes WHERE email = ? AND code = ? AND expires_at > NOW()");
    $stmt->execute([$email, $code]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        // Delete the used code
        $stmt = $conn->prepare("DELETE FROM tfa_codes WHERE email = ? AND code = ?");
        $stmt->execute([$email, $code]);
        return true;
    }
    return false;
}

//  User Registration 

function registerUser($name, $email, $password, $phone, $role, $location, $id_number) {
    global $conn;
    if (emailExists($email)) {
        logSystemEvent("User Registration Failed");
        return false;
    }
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users 
        (name, email, password, phone, role, location, id_number) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $success = $stmt->execute([
        $name, $email, $hashedPassword, $phone, $role, $location, $id_number
    ]);
    if ($success) {
        $user_id = $conn->lastInsertId();
        logSystemEvent("User Registration", $user_id);
    } else {
        logSystemEvent("User Registration Failed");
    }
    return $success;
}

// Login 

function loginUser($email, $password) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        // Allow SKIP_TFA env var to bypass email 2FA (demo/local-dev only)
        if (getenv('SKIP_TFA') === 'true') {
            completeLogin($user);
            return true;
        }
        // Generate and send TFA code
        $tfaCode = generateTFACode();
        if (sendTFACode($email, $tfaCode)) {
            // Store user info in session for TFA verification
            $_SESSION['pending_user'] = $user;
            $_SESSION['tfa_required'] = true;
            logSystemEvent("TFA Code Sent", $user['user_id']);
            return 'tfa_required';
        } else {
            logSystemEvent("TFA Code Send Failed", $user['user_id']);
            return false;
        }
    }
    logSystemEvent("Login Failed");
    return false;
}

function completeLogin($user) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['LAST_ACTIVITY'] = time();
    $_SESSION['tfa_required'] = false;
    unset($_SESSION['pending_user']);
    logSystemEvent("Login", $user['user_id']);
    return true;
}

function logout() {
    if (isset($_SESSION['user_id'])) {
        logSystemEvent("Logout", $_SESSION['user_id']);
    }
    session_unset();
    session_destroy();
    header('Location: /pages/auth/login.php');
    exit();  
}

function emailExists($email) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Email check error: " . $e->getMessage());
        return false;
    }
}
?>