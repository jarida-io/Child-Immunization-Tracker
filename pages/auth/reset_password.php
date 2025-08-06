<?php
require '../../includes/db.php';
require '../../includes/functions.php'; // for logging if needed

$message = "";
$token = $_GET['token'] ?? '';

// Only handle reset when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (empty($token) || empty($password) || empty($confirm)) {
        $message = "<p class='error'>All fields are required.</p>";
    } elseif ($password !== $confirm) {
        $message = "<p class='error'>Passwords do not match.</p>";
    } else {
        $tokenHash = hash('sha256', $token);
        $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ?");
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Update password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashedPassword, $row['email']]);

            // Delete used token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$row['email']]);

            // Optional: log the password reset
            if (function_exists('logSystemEvent')) {
                logSystemEvent("Password Reset", null); // optional user_id
            }

            $message = "<p class='success'>Password updated. You may now <a href='login.php'>log in</a>.</p>";
        } else {
            $message = "<p class='error'>Invalid or expired token. Please request a new password reset.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f8;
            display: flex;
            height: 100vh;
            justify-content: center;
            align-items: center;
        }
        .reset-box {
            background: white;
            padding: 2rem 3rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 1rem;
        }
        input {
            width: 100%;
            margin-bottom: 1rem;
            padding: 10px;
            font-size: 1rem;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .success {
            color: green;
            margin-top: 1rem;
            text-align: center;
        }
        .error {
            color: red;
            margin-top: 1rem;
            text-align: center;
        }
        p {
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="reset-box">
        <h2>Reset Password</h2>
        <form method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="password" name="password" required placeholder="New password">
            <input type="password" name="confirm" required placeholder="Confirm new password">
            <button type="submit">Reset Password</button>
        </form>
        <?= $message ?>
    </div>
</body>
</html>