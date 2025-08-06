<?php
// db.php - Handles database connection for the Child Vaccination System
// Sets timezone for MySQL session to match PHP

// Database connection settings
$host = 'localhost';  
$dbname = 'cvs'; 
$username = 'root';  
$password = '';  

try {
    // Create PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // error handling
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set timezone Africa/Nairobi (+03:00)
    $conn->exec("SET time_zone = '+03:00'");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
