<?php
session_start();
require __DIR__ . '/../../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

if (!isset($_GET['child_id'])) {
    http_response_code(400);
    exit('Child ID required');
}

$child_id = intval($_GET['child_id']);

// Fetch child information
$child_stmt = $conn->prepare("SELECT * FROM children WHERE child_id = ?");
$child_stmt->execute([$child_id]);
$child = $child_stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    exit('Child not found');
}

// Fetch unverified vaccination cards
$cards_stmt = $conn->prepare("
    SELECT * FROM vaccination_cards 
    WHERE child_id = ? AND verified_at IS NULL 
    ORDER BY uploaded_at DESC
");
$cards_stmt->execute([$child_id]);
$cards = $cards_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch vaccination schedule
$schedule_stmt = $conn->prepare("
    SELECT vs.*, v.name as vaccine_name, v.disease_prevented 
    FROM vaccination_schedule vs
    JOIN vaccines v ON vs.vaccine_id = v.vaccine_id
    WHERE vs.child_id = ?
    ORDER BY vs.due_date
");
$schedule_stmt->execute([$child_id]);
$schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);


$today = new DateTime();
foreach ($schedules as &$schedule) {
    if ($schedule['status'] === 'Pending') {
        $due_date = new DateTime($schedule['due_date']);
        if ($due_date < $today) {
            $schedule['status'] = 'Missed';
        }
    }
}
unset($schedule);
?>

<div class="card-section">
    <h3><i class="fas fa-id-card"></i> Vaccination Cards</h3>
    
    <?php if (empty($cards)): ?>
        <p>No vaccination cards uploaded for this child.</p>
    <?php else: ?>
        <?php foreach ($cards as $card): ?>
            <div class="vaccination-card">
                <div class="card-image-container" onclick="openZoomModal('/<?= htmlspecialchars($card['card_path']) ?>')">
                    <img src="/<?= htmlspecialchars($card['card_path']) ?>" 
                         alt="Vaccination Card" 
                         class="card-image"
                         title="Click to zoom">
                </div>
                <p><strong>Uploaded:</strong> <?= date('M j, Y g:i a', strtotime($card['uploaded_at'])) ?></p>
                
                <form method="POST" action="verify_vacc_cards.php" style="margin-top: 15px;">
                    <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                    <input type="hidden" name="child_id" value="<?= $child_id ?>">
                    <button type="submit" name="verify_card" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Verify Card
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="card-section">
    <h3><i class="fas fa-calendar-alt"></i> Vaccination Schedule</h3>
    
    <?php if (empty($schedules)): ?>
        <p>No vaccination schedule found for this child.</p>
    <?php else: ?>
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Vaccine</th>
                    <th>Disease Prevented</th>
                    <th>Dose</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td><?= htmlspecialchars($schedule['vaccine_name']) ?></td>
                        <td><?= htmlspecialchars($schedule['disease_prevented']) ?></td>
                        <td><?= $schedule['dose_number'] ?></td>
                        <td><?= date('M j, Y', strtotime($schedule['due_date'])) ?></td>
                        <td class="status-<?= strtolower($schedule['status']) ?>">
                            <?= $schedule['status'] ?>
                            <?php if ($schedule['status'] === 'Completed'): ?>
                                <br><small>on <?= date('M j, Y', strtotime($schedule['completed_date'])) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($schedule['status'] === 'Pending'): ?>
                                <button class="btn btn-warning btn-sm" 
                                        onclick="openMarkCompleteModal(<?= $schedule['schedule_id'] ?>, <?= $child_id ?>, <?= $cards[0]['id'] ?? 0 ?>)">
                                    <i class="fas fa-check"></i> Mark Complete
                                </button>
                            <?php elseif ($schedule['status'] === 'Missed'): ?>
                                <button class="btn btn-warning btn-sm" 
                                        onclick="openMarkCompleteModal(<?= $schedule['schedule_id'] ?>, <?= $child_id ?>, <?= $cards[0]['id'] ?? 0 ?>)">
                                    <i class="fas fa-check"></i> Mark Complete
                                </button>
                            <?php elseif ($schedule['status'] === 'Completed'): ?>
                                <span class="text-muted">Completed</span>
                            <?php else: ?>
                                <span class="text-muted"><?= $schedule['status'] ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

 