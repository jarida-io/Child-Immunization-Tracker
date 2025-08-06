<?php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle mark as read via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $notif_id = intval($_POST['mark_read_id']);
    
    // Delete the notification from database
    $stmt = $conn->prepare("DELETE FROM notifications WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $_SESSION['user_id']]);
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'notification_id' => $notif_id]);
    exit();
}

// Fetch notifications for the logged-in user, join to children for child name
$stmt = $conn->prepare("SELECT n.*, c.name AS child_name FROM notifications n LEFT JOIN children c ON n.child_id = c.child_id WHERE n.user_id = ? ORDER BY n.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group notifications by child_id (or 'Other' if null)
$grouped = [];
foreach ($notifications as $notif) {
    $key = $notif['child_id'] ? ($notif['child_name'] ?: 'Unknown Child') : 'Other';
    $grouped[$key][] = $notif;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Child Vaccination System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .child-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .child-card:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .child-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .child-header:hover {
            background: linear-gradient(135deg, #2980b9, #1f5f8b);
        }
        
        .child-name {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }
        
        .notification-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .child-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .child-content.expanded {
            max-height: 1000px;
        }
        
        .notification-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            opacity: 1;
            transform: translateX(0);
        }
        
        .notification-item.fade-out {
            opacity: 0;
            transform: translateX(-100%);
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item.unread {
            background: #fff5f5;
            border-left: 4px solid #e74c3c;
        }
        
        .notification-message {
            font-weight: 500;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .notification-time {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .mark-read-btn {
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: background 0.3s;
        }
        
        .mark-read-btn:hover {
            background: #219a52;
        }
        
        .expand-icon {
            transition: transform 0.3s ease;
        }
        
        .expand-icon.expanded {
            transform: rotate(180deg);
        }
        
        .alert {
            padding: 1rem;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <div class="container">
        <div class="card">
            <h2>Notifications</h2>
            
            <?php if (empty($notifications)): ?>
                <div class="alert">No notifications to display.</div>
            <?php else: ?>
                <?php foreach ($grouped as $child_name => $notifs): ?>
                    <?php 
                    $unread_count = 0;
                    foreach ($notifs as $notif) {
                        if (!$notif['is_read']) $unread_count++;
                    }
                    ?>
                    <div class="child-card">
                        <div class="child-header" onclick="toggleChild('<?= htmlspecialchars($child_name) ?>')">
                            <h3 class="child-name"><?= htmlspecialchars($child_name) ?></h3>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <span class="notification-count"><?= count($notifs) ?> notification<?= count($notifs) != 1 ? 's' : '' ?></span>
                                <i class="fas fa-chevron-down expand-icon" id="icon-<?= htmlspecialchars($child_name) ?>"></i>
                            </div>
                        </div>
                        
                        <div class="child-content" id="content-<?= htmlspecialchars($child_name) ?>">
                            <ul class="notification-list">
                                <?php foreach ($notifs as $notification): ?>
                                    <li class="notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>" id="notif-<?= $notification['notification_id'] ?>">
                                        <div class="notification-message">
                                            <?= htmlspecialchars($notification['message']) ?>
                                        </div>
                                        <div class="notification-time">
                                            <?= date('M j, Y g:i a', strtotime($notification['created_at'])) ?>
                                        </div>
                                        <?php if (!$notification['is_read']): ?>
                                            <button class="mark-read-btn" onclick="markAsRead(<?= $notification['notification_id'] ?>, '<?= htmlspecialchars($child_name) ?>')">
                                                Mark as Read
                                            </button>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <a href="/pages/parent/dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>

    <script>
        function toggleChild(childName) {
            const content = document.getElementById('content-' + childName);
            const icon = document.getElementById('icon-' + childName);
            
            content.classList.toggle('expanded');
            icon.classList.toggle('expanded');
        }
        
        function markAsRead(notificationId, childName) {
            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'mark_read_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notificationItem = document.getElementById('notif-' + notificationId);
                    notificationItem.classList.add('fade-out');
                    
                    setTimeout(() => {
                        notificationItem.remove();
                        
                        // Update notification count
                        const childCard = notificationItem.closest('.child-card');
                        const countElement = childCard.querySelector('.notification-count');
                        const currentCount = parseInt(countElement.textContent);
                        const newCount = currentCount - 1;
                        
                        if (newCount === 0) {
                            countElement.textContent = '0 notifications';
                        } else {
                            countElement.textContent = newCount + ' notification' + (newCount !== 1 ? 's' : '');
                        }
                        
                        // If no more notifications in this child card, hide the card
                        const remainingNotifications = childCard.querySelectorAll('.notification-item');
                        if (remainingNotifications.length === 0) {
                            childCard.style.display = 'none';
                        }
                    }, 300);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>