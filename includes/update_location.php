<?php
session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_location = $_POST['location'] ?? '';
    
    if (!empty($new_location)) {
        try {
            $stmt = $conn->prepare("UPDATE users SET location = ? WHERE user_id = ?");
            $stmt->execute([$new_location, $_SESSION['user_id']]);
            
            $_SESSION['success'] = "Location updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating location: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please select a valid location";
    }
}

header("Location: dashboard.php");
exit();
?>