<?php
ob_start();
session_start();

$allowed_roles = ['admin'];
if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role']), $allowed_roles)) {
    $_SESSION['redirect_message'] = "Please login as admin to access this page";
    header("Location: ../auth/login.php");
    ob_end_flush();
    exit();
}

require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/functions.php';

// Handle verification form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_card'])) {
        $card_id = $_POST['card_id'];
        $child_id = $_POST['child_id'];
        $schedules = $_POST['schedules'] ?? [];

        try {
            $conn->beginTransaction();

            // 1. Mark card as verified
            $stmt = $conn->prepare("UPDATE vaccination_cards SET verified_at = NOW(), verified_by = ? WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $card_id]);

            $conn->commit();
            $_SESSION['success'] = "Card verified successfully!";
            logSystemEvent('Verified vaccination card', $_SESSION['user_id']);
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Error verifying card: " . $e->getMessage();
        }
    }
    elseif (isset($_POST['mark_complete'])) {
        $schedule_id = $_POST['schedule_id'];
        $child_id = $_POST['child_id'];
        $card_id = $_POST['card_id'];
        $administered_date = $_POST['administered_date'];
        $notes = $_POST['notes'] ?? '';
        
        try {
            $conn->beginTransaction();
            
            // Update vaccination status
            $stmt = $conn->prepare("UPDATE vaccination_schedule 
                                  SET status = 'Completed', 
                                      completed_date = ?,
                                      administered_by = ?,
                                      notes = ?
                                  WHERE schedule_id = ?");
            $stmt->execute([$administered_date, $_SESSION['user_id'], $notes, $schedule_id]);
            
            // Update last vaccination info
            $stmt = $conn->prepare("UPDATE children SET last_vaccination_date = ?, last_vaccine_name = 
                                  (SELECT v.name FROM vaccination_schedule vs 
                                   JOIN vaccines v ON vs.vaccine_id = v.vaccine_id 
                                   WHERE vs.schedule_id = ?) 
                                  WHERE child_id = ?");
            $stmt->execute([$administered_date, $schedule_id, $child_id]);
            
            $conn->commit();
            $_SESSION['success'] = "Vaccination marked as complete successfully!";
            logSystemEvent('Marked vaccination as complete', $_SESSION['user_id']);
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Error updating record: " . $e->getMessage();
        }
    }
    
    // Check if this is an AJAX request
    if (isset($_POST['mark_complete'])) {
        // Return JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $_SESSION['success'] ?? 'Vaccination marked as complete']);
        exit();
    } else {
        // Regular form submission - redirect
        header("Location: verify_vacc_cards.php");
        ob_end_flush();
        exit();
    }
}

// Fetch unverified cards grouped by child
$sql = "SELECT ANY_VALUE(vc.id) AS id, ANY_VALUE(vc.card_path) AS card_path,
               MAX(vc.uploaded_at) AS uploaded_at, vc.child_id, c.name AS child_name,
               COUNT(vc.id) as card_count
        FROM vaccination_cards vc
        JOIN children c ON vc.child_id = c.child_id
        WHERE vc.verified_at IS NULL
        GROUP BY vc.child_id, c.name
        ORDER BY MAX(vc.uploaded_at) DESC";
$child_cards = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Vaccination Cards - Admin</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .child-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .child-card:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .child-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .child-name {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }
        
        .card-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            overflow: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }
        
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
        }
        
        .close:hover {
            opacity: 0.7;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .card-section {
            margin-bottom: 30px;
        }
        
        .card-section h3 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .vaccination-card {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .card-image-container {
            position: relative;
            display: inline-block;
            max-width: 100%;
            cursor: zoom-in;
        }
        
        .card-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .card-image:hover {
            transform: scale(1.02);
        }
        
        .zoom-modal {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            cursor: zoom-out;
        }
        
        .zoom-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 95%;
            max-height: 95%;
        }
        
        .zoom-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .schedule-table th,
        .schedule-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .schedule-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .status-pending {
            color: #f39c12;
            font-weight: 600;
        }
        
        .status-completed {
            color: #27ae60;
            font-weight: 600;
        }
        
        .status-missed {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-success {
            background-color: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #219a52;
        }
        
        .btn-warning {
            background-color: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #e67e22;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>

    <div class="container">
        <div class="card">
            <h2><i class="fas fa-id-badge"></i> Verify Vaccination Cards</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($child_cards)): ?>
                <div class="alert">
                    <p>No unverified vaccination cards found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($child_cards as $child): ?>
                    <div class="child-card" onclick="openChildModal(<?= $child['child_id'] ?>, '<?= htmlspecialchars($child['child_name']) ?>')">
                        <div class="child-header">
                            <h3 class="child-name"><?= htmlspecialchars($child['child_name']) ?></h3>
                            <span class="card-count"><?= $child['card_count'] == 1 ? 'verify card' : $child['card_count'] . ' cards to verify' ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Child Detail Modal -->
    <div id="childModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle"></h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Zoom Modal -->
    <div id="zoomModal" class="zoom-modal" onclick="closeZoomModal()">
        <div class="zoom-content">
            <img id="zoomImage" class="zoom-image" src="" alt="Vaccination Card">
        </div>
    </div>

    <!-- Mark Complete Modal -->
    <div id="markCompleteModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Mark Vaccination as Complete</h3>
                <button class="close" onclick="closeMarkCompleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="markCompleteForm" method="POST" action="verify_vacc_cards.php">
                    <input type="hidden" name="schedule_id" id="modalScheduleId">
                    <input type="hidden" name="child_id" id="modalChildId">
                    <input type="hidden" name="card_id" id="modalCardId">
                    <input type="hidden" name="mark_complete" value="1">
                    <input type="hidden" name="return_to_child" id="returnToChild" value="">
                    
                    <div class="form-group">
                        <label for="administered_date">Date Administered</label>
                        <input type="date" name="administered_date" id="administered_date" required 
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (optional)</label>
                        <textarea name="notes" id="notes" placeholder="Enter any additional notes..."></textarea>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Confirm Completion
                        </button>
                        <button type="button" class="btn btn-primary" onclick="closeMarkCompleteModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openChildModal(childId, childName) {
            // Store current child information
            currentChildId = childId;
            currentChildName = childName;
            
            document.getElementById('modalTitle').textContent = childName + ' - Vaccination Details';
            
            // Show loading
            document.getElementById('modalBody').innerHTML = '<p>Loading...</p>';
            document.getElementById('childModal').style.display = 'block';
            
            // Fetch child data
            fetch(`get_child_details.php?child_id=${childId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modalBody').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('modalBody').innerHTML = '<p>Error loading data: ' + error.message + '</p>';
                });
        }
        
        function closeModal() {
            document.getElementById('childModal').style.display = 'none';
        }
        
        function openZoomModal(imageSrc) {
            document.getElementById('zoomImage').src = imageSrc;
            document.getElementById('zoomModal').style.display = 'block';
        }
        
        function closeZoomModal() {
            document.getElementById('zoomModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('childModal');
            const zoomModal = document.getElementById('zoomModal');
            
            if (event.target === modal) {
                closeModal();
            }
            if (event.target === zoomModal) {
                closeZoomModal();
            }
        }
        
        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeZoomModal();
                closeMarkCompleteModal();
            }
        });

        // Global variables to track current child
        let currentChildId = null;
        let currentChildName = null;

        // Global functions for mark complete modal
        function openMarkCompleteModal(scheduleId, childId, cardId) {
            document.getElementById('modalScheduleId').value = scheduleId;
            document.getElementById('modalChildId').value = childId;
            document.getElementById('modalCardId').value = cardId;
            document.getElementById('returnToChild').value = childId;
            document.getElementById('markCompleteModal').style.display = 'block';
        }

        function closeMarkCompleteModal() {
            document.getElementById('markCompleteModal').style.display = 'none';
        }

        // Close mark complete modal when clicking outside
        window.addEventListener('click', function(event) {
            const markCompleteModal = document.getElementById('markCompleteModal');
            if (event.target === markCompleteModal) {
                closeMarkCompleteModal();
            }
        });

        // Handle mark complete form submission
        document.getElementById('markCompleteForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('verify_vacc_cards.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Close the mark complete modal
                closeMarkCompleteModal();
                
                // Reload the child modal content
                if (currentChildId && currentChildName) {
                    document.getElementById('modalTitle').textContent = currentChildName + ' - Vaccination Details';
                    document.getElementById('modalBody').innerHTML = '<p>Loading...</p>';
                    
                    fetch(`get_child_details.php?child_id=${currentChildId}`)
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById('modalBody').innerHTML = html;
                        })
                        .catch(error => {
                            document.getElementById('modalBody').innerHTML = '<p>Error loading data: ' + error.message + '</p>';
                        });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating vaccination. Please try again.');
            });
        });
    </script>
</body>
</html>