<?php
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../navbar.php'; // Include the navbar


$currentUserId = $_SESSION['user_id'] ?? null;

// Search functionality
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM users";
$params = [];

if (!empty($search)) {
    $query .= " WHERE name LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$usersStmt = $conn->prepare($query);
$usersStmt->execute($params);
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

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

    .container {
    padding-top: 20px; 
    max-width: 1200px;
    margin: 0 auto;
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
</style>

<div class="container">
    <h1>Manage Users</h3>

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="role-badge <?= htmlspecialchars($user['role']) ?>">
                            <?= ucfirst(htmlspecialchars($user['role'])) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['user_id'] !== $currentUserId): ?>
                            <a href="edit_user.php?id=<?= htmlspecialchars($user['user_id']) ?>" class="btn btn-sm">Edit</a>
                        <?php else: ?>
                            <span style="color: gray; font-size: 0.85em;">(Self)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
