<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$valid_types = ['children', 'vaccinations', 'pending', 'users','missed', 'activity'];
$type = $_GET['type'] ?? 'children';
$pageTitle = ucfirst($type) . " Report";

// Fetch data based on type
$data = [];

switch ($type) {
    case 'children':
        $stmt = $conn->query("SELECT * FROM children ORDER BY created_at DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'vaccinations':
        $stmt = $conn->query("
            SELECT vs.*, v.name AS vaccine_name, c.name AS child_name
            FROM vaccination_schedule vs
            JOIN vaccines v ON vs.vaccine_id = v.vaccine_id
            JOIN children c ON vs.child_id = c.child_id
            WHERE vs.status = 'Completed'
            ORDER BY vs.updated_at DESC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'pending':
        $stmt = $conn->query("
            SELECT vs.*, v.name AS vaccine_name, c.name AS child_name
            FROM vaccination_schedule vs
            JOIN vaccines v ON vs.vaccine_id = v.vaccine_id
            JOIN children c ON vs.child_id = c.child_id
            WHERE vs.status = 'Pending'
            ORDER BY vs.due_date ASC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'users':
        $stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'missed':
        $stmt = $conn->query("
            SELECT vs.*, 
                   v.name AS vaccine_name, 
                   c.name AS child_name,
                   g.name AS guardian_name,
                   cg.name AS caregiver_name
            FROM vaccination_schedule vs
            JOIN vaccines v ON vs.vaccine_id = v.vaccine_id
            JOIN children c ON vs.child_id = c.child_id
            LEFT JOIN users g ON c.guardian_id = g.user_id
            LEFT JOIN users cg ON c.caregiver_id = cg.user_id
            WHERE vs.status = 'Missed'
            ORDER BY vs.due_date ASC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'activity':
        $stmt = $conn->query("
            SELECT sl.*, u.name as user_name, u.email as user_email, u.role as user_role 
            FROM system_logs sl 
            LEFT JOIN users u ON sl.user_id = u.user_id 
            ORDER BY sl.created_at DESC 
            LIMIT 100
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    default:
        $pageTitle = "Invalid Report Type";
        $data = [];
        break;
}

// Identify columns that are NOT entirely empty
$columns = [];
if (!empty($data)) {
    $all_cols = array_keys($data[0]);
    foreach ($all_cols as $col) {
        $nonEmpty = array_filter($data, fn($row) => !empty($row[$col]) && $row[$col] !== '0000-00-00');
        if (!empty($nonEmpty)) {
            $columns[] = $col;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <style>
        .container {
            max-width: 1400px;
            margin: 6rem auto 2rem;
            padding: 0 1.5rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .table-container {
            background: white;
            padding: 1.5rem;
            margin-top: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        th, td {
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .action-cell {
            font-weight: 500;
            color: #007BFF;
        }
        
        .user-cell {
            font-weight: 500;
        }
        
        .role-badge {
            background-color: #e9ecef;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 12px;
            color: #495057;
        }
        
        .time-cell {
            color: #6c757d;
            font-size: 14px;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #007BFF;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #545b62;
        }
        
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .table-container {
                font-size: 14px;
            }
            
            th, td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-chart-bar"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <a href="system_logs.php" class="btn btn-primary">
                <i class="fas fa-clipboard-list"></i> System Logs
            </a>
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if (empty($data)): ?>
        <div class="no-data">
            <i class="fas fa-chart-bar"></i>
            <h3>No data found</h3>
            <p>No data available for this report type.</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <?php foreach ($columns as $col): ?>
                            <th><?php echo ucwords(str_replace('_', ' ', $col)); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <td class="<?php 
                                    if ($col === 'description') echo 'action-cell';
                                    elseif ($col === 'user_name') echo 'user-cell';
                                    elseif ($col === 'user_role') echo '';
                                    elseif ($col === 'created_at') echo 'time-cell';
                                    else echo '';
                                ?>">
                                    <?php if ($col === 'user_role' && !empty($row[$col])): ?>
                                        <span class="role-badge"><?php echo htmlspecialchars($row[$col]); ?></span>
                                    <?php elseif ($col === 'created_at' && !empty($row[$col])): ?>
                                        <?php echo date('M j, Y g:i A', strtotime($row[$col])); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($row[$col] ?? 'N/A'); ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($type === 'activity'): ?>
            <div style="margin-top: 2rem; text-align: center;">
                <p><strong>System activities.</strong></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
