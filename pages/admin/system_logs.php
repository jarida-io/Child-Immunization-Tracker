<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle filters and pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Filter parameters
$search = trim($_GET['search'] ?? '');
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$user_filter = $_GET['user_filter'] ?? '';
$action_filter = $_GET['action_filter'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(sl.description LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(sl.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(sl.created_at) <= ?";
    $params[] = $date_to;
}

if (!empty($user_filter)) {
    $where_conditions[] = "sl.user_id = ?";
    $params[] = $user_filter;
}

if (!empty($action_filter)) {
    $where_conditions[] = "sl.description LIKE ?";
    $params[] = "%$action_filter%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM system_logs sl LEFT JOIN users u ON sl.user_id = u.user_id $where_clause";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get system logs with user information
$query = "SELECT sl.*, u.name as user_name, u.email as user_email, u.role as user_role 
          FROM system_logs sl 
          LEFT JOIN users u ON sl.user_id = u.user_id 
          $where_clause 
          ORDER BY sl.created_at DESC 
          LIMIT $per_page OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique users for filter dropdown
$users_stmt = $conn->query("SELECT user_id, name, email FROM users ORDER BY name");
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique actions for filter dropdown
$actions_stmt = $conn->query("SELECT DISTINCT description FROM system_logs ORDER BY description");
$actions = $actions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="system_logs_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, ['Date/Time', 'Action', 'User', 'User Email', 'User Role', 'User ID']);
    
    // Export all filtered data
    $export_query = "SELECT sl.*, u.name as user_name, u.email as user_email, u.role as user_role 
                     FROM system_logs sl 
                     LEFT JOIN users u ON sl.user_id = u.user_id 
                     $where_clause 
                     ORDER BY sl.created_at DESC";
    
    $export_stmt = $conn->prepare($export_query);
    $export_stmt->execute($params);
    
    while ($row = $export_stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['created_at'],
            $row['description'],
            $row['user_name'] ?? 'System',
            $row['user_email'] ?? 'N/A',
            $row['user_role'] ?? 'N/A',
            $row['user_id'] ?? 'N/A'
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Admin Dashboard</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .filters-section {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filter-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
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
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #1e7e34;
        }
        
        .logs-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .log-action {
            font-weight: 500;
            color: #007BFF;
        }
        
        .log-user {
            font-weight: 500;
        }
        
        .log-role {
            background-color: #e9ecef;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 12px;
            color: #495057;
        }
        
        .log-time {
            color: #6c757d;
            font-size: 14px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #007BFF;
            border-radius: 4px;
        }
        
        .pagination .current {
            background-color: #007BFF;
            color: white;
            border-color: #007BFF;
        }
        
        .pagination a:hover {
            background-color: #f8f9fa;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007BFF;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        
        .no-logs {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .no-logs i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        
        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .table-container {
                font-size: 14px;
            }
            
            th, td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-clipboard-list"></i> System Activity Logs</h1>
            <div class="filter-actions">
                <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-success">
                    <i class="fas fa-download"></i> Download Reports
                </a>
                <a href="admin_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
        <!-- Statistics Summary -->
        <div class="stats-summary">
            <div class="stat-card">
                <div class="stat-number"><?= number_format($total_records) ?></div>
                <div class="stat-label">Total Log Entries</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format(count($users)) ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format(count($actions)) ?></div>
                <div class="stat-label">Unique Actions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_pages ?></div>
                <div class="stat-label">Total Pages</div>
            </div>
        </div>
        
        <!-- Logs Table -->
        <div class="logs-table">
            <?php if (empty($logs)): ?>
                <div class="no-logs">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No logs found</h3>
                    <p>No system activity logs match your current filters.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Action</th>
                                <th>User</th>
                                <th>User Role</th>
                                <th>User Email</th>
                                <th>User ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="log-time">
                                        <?= date('M j, Y g:i A', strtotime($log['created_at'])) ?>
                                    </td>
                                    <td class="log-action">
                                        <?= htmlspecialchars($log['description']) ?>
                                    </td>
                                    <td class="log-user">
                                        <?= htmlspecialchars($log['user_name'] ?? 'System') ?>
                                    </td>
                                    <td>
                                        <?php if ($log['user_role']): ?>
                                            <span class="log-role"><?= htmlspecialchars($log['user_role']) ?></span>
                                        <?php else: ?>
                                            <span class="log-role">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($log['user_email'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <?= $log['user_id'] ? htmlspecialchars($log['user_id']) : 'N/A' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-submit form when filters change
        document.addEventListener('DOMContentLoaded', function() {
            const filterInputs = document.querySelectorAll('select[name="user_filter"], select[name="action_filter"]');
            
            filterInputs.forEach(input => {
                input.addEventListener('change', function() {
                    this.closest('form').submit();
                });
            });
            
            // Date range validation
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');
            
            dateFrom.addEventListener('change', function() {
                if (dateTo.value && this.value > dateTo.value) {
                    dateTo.value = this.value;
                }
            });
            
            dateTo.addEventListener('change', function() {
                if (dateFrom.value && this.value < dateFrom.value) {
                    dateFrom.value = this.value;
                }
            });
        });
    </script>
</body>
</html> 