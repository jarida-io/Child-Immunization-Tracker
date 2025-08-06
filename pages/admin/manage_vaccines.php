<?php
require __DIR__ . '/../../includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $disease_prevented = $_POST['disease_prevented'] ?? '';
    $recommended_age = $_POST['recommended_age'] ?? '';
    $dose_number = $_POST['dose_number'] ?? '';
    $dose_description = $_POST['dose_description'] ?? '';
    $route_of_administration = $_POST['route_of_administration'] ?? '';
    $site_of_administration = $_POST['site_of_administration'] ?? '';
    $side_effects = $_POST['side_effects'] ?? '';

    $stmt = $conn->prepare("
        INSERT INTO vaccines 
        (name, disease_prevented, recommended_age, dose_number, dose_description, route_of_administration, site_of_administration, side_effects)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $name,
        $disease_prevented,
        $recommended_age,
        $dose_number,
        $dose_description,
        $route_of_administration,
        $site_of_administration,
        $side_effects
    ]);
}


// Delete
if (isset($_GET['delete'])) {
    $vaccine_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM vaccines WHERE vaccine_id = ?");
    $stmt->execute([$vaccine_id]);
    header("Location: manage_vaccines.php");
    exit;
}

$vaccines = $conn->query("SELECT * FROM vaccines")->fetchAll(PDO::FETCH_ASSOC);

// Fetch single vaccine for editing
$editVaccine = null;
if (isset($_GET['edit'])) {
    $vaccine_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM vaccines WHERE vaccine_id = ?");
    $stmt->execute([$vaccine_id]);
    $editVaccine = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vaccines - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    .table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .table th {
        background-color: #4a6fa5;
        color: white;
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
    }

    .table td {
        padding: 12px 15px;
        border-bottom: 1px solid #e0e0e0;
        vertical-align: middle;
    }

    .table tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .table tr:hover {
        background-color: #f1f5fd;
    }

    .btn {
        display: inline-block;
        padding: 6px 12px;
        background-color: #4a6fa5;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 0.9em;
        transition: background-color 0.3s;
    }

    .btn:hover {
        background-color: #3a5a80;
    }

    .btn-sm {
        padding: 4px 8px;
        font-size: 0.8em;
    }

    .card {
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        max-width: 600px;
    }

    .card h3 {
        margin-top: 0;
        color: #4a6fa5;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #333;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 0.95em;
    }

    textarea.form-control {
        resize: vertical;
        min-height: 80px;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    h1 {
        color: #2c3e50;
        margin-bottom: 30px;
    }
</style>
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    
    <div class="container">
        <h1>Manage Vaccines</h1>
        
        <form method="POST" class="card">
    <h3>Add New Vaccine</h3>
    <div class="form-group">
        <label>Vaccine Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Disease Prevented</label>
        <input type="text" name="disease_prevented" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Recommended Age</label>
        <input type="text" name="recommended_age" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Dose Number</label>
        <input type="number" name="dose_number" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Dose Description</label>
        <textarea name="dose_description" class="form-control"></textarea>
    </div>
    <div class="form-group">
        <label>Route of Administration</label>
        <input type="text" name="route_of_administration" class="form-control">
    </div>
    <div class="form-group">
        <label>Site of Administration</label>
        <input type="text" name="site_of_administration" class="form-control">
    </div>
    <div class="form-group">
        <label>Side Effects</label>
        <textarea name="side_effects" class="form-control"></textarea>
    </div>
    <button type="submit" class="btn">Add Vaccine</button>
</form>

<!-- Table: All Vaccines -->
<table class="table">
    <thead>
        <tr>
            <th>Vaccine Name</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($vaccines as $vaccine): ?>
            <tr>
                <td><?= htmlspecialchars($vaccine['name']) ?></td>
                <td><?= htmlspecialchars($vaccine['dose_description']) ?></td>
                <td>
                    <a href="?edit=<?= $vaccine['vaccine_id'] ?>" class="btn btn-sm">Edit</a>
                    <a href="?delete=<?= $vaccine['vaccine_id'] ?>" class="btn btn-sm" style="background-color: #dc3545;" onclick="return confirm('Are you sure you want to delete this vaccine?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
    </div>
</body>
</html>
