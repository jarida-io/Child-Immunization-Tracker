<?php
require 'db.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (!$conn) {
}


function logSystemEvent($description, $user_id = null) {
    global $conn;
    
    if (!$conn) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO system_logs (description, user_id, created_at) VALUES (?, ?, NOW())");
        $result = $stmt->execute([$description, $user_id]);
        
        if ($result) {
            return true;
        } else {
            return false;
        }
    } catch (PDOException $e) {
        return false;
    }
}


function generateVaccinationSchedule($child_id, $date_of_birth, $last_vaccination_date = null, $last_vaccine_name = null) {
    global $conn;

    try {

        // Get all vaccines ordered by recommended age and dose number
        $stmt = $conn->prepare("SELECT * FROM vaccines ORDER BY recommended_age_days, dose_number");
        if (!$stmt->execute()) {
            throw new PDOException("Failed to fetch vaccines: " . implode(", ", $stmt->errorInfo()));
        }
        $vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $scheduled_count = 0;
        foreach ($vaccines as $vaccine) {
            // Calculate due date 
            $due_date = calculateVaccineDueDate($date_of_birth, $vaccine['recommended_age_days']);
            if (!$due_date) {
                continue;
            }

            // Check if dose was already completed
            $stmt = $conn->prepare("SELECT COUNT(*) FROM vaccination_schedule 
                                  WHERE child_id = ? 
                                  AND vaccine_id = ? 
                                  AND dose_number = ? 
                                  AND status = 'Completed'");
            if (!$stmt->execute([$child_id, $vaccine['vaccine_id'], $vaccine['dose_number']])) {
                continue;
            }
            
            if ($stmt->fetchColumn() > 0) {
                continue;
            }

            // Insert new schedule record
            $stmt = $conn->prepare("INSERT INTO vaccination_schedule 
                                   (child_id, vaccine_id, due_date, dose_number, status, created_at) 
                                   VALUES (?, ?, ?, ?, 'Pending', NOW())");
            if ($stmt->execute([$child_id, $vaccine['vaccine_id'], $due_date, $vaccine['dose_number']])) {
                $scheduled_count++;
                
                // Create reminder if future date (including today)
                if (strtotime($due_date) >= time()) {
                    $reminder_date = date('Y-m-d', strtotime('-1 week', strtotime($due_date)));
                    createNotification(
                        $child_id,
                        "Upcoming: {$vaccine['name']} (Dose {$vaccine['dose_number']}) on " . date('F j, Y', strtotime($due_date)),
                        $reminder_date,
                        'vaccination_reminder'
                    );
                }
            }
        }

        return $scheduled_count > 0;

    } catch (PDOException $e) {
        return false;
    }
}


function calculateVaccineDueDate($date_of_birth, $days_after_birth) {
    try {
        $dob = new DateTime($date_of_birth);
        $interval = new DateInterval("P{$days_after_birth}D");
        $dob->add($interval);
        return $dob->format('Y-m-d');
    } catch (Exception $e) {
        return false;
    }
}


function getChildName($child_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT name FROM children WHERE child_id = ?");
    $stmt->execute([$child_id]);
    return $stmt->fetchColumn() ?: 'Unknown Child';
}


function createNotification($child_id, $vaccine_name, $due_date) {
    global $conn;
    // Get guardian ID
    $stmt = $conn->prepare("SELECT guardian_id FROM children WHERE child_id = ?");
    if (!$stmt->execute([$child_id]) || !$stmt->rowCount()) {
        return false;
    }
    $guardian_id = $stmt->fetchColumn();

    // Create message
    $formatted_date = date('F j, Y', strtotime($due_date));
    $message = "Upcoming vaccination: $vaccine_name due on $formatted_date";

    // Create notification
    try {
        $stmt = $conn->prepare("INSERT INTO notifications 
                              (user_id, child_id, message, is_read, created_at)
                              VALUES (?, ?, ?, 0, NOW())");
        if (!$stmt->execute([$guardian_id, $child_id, $message])) {
            throw new PDOException(implode(", ", $stmt->errorInfo()));
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}



function calculateAge($dob) {
    if (empty($dob)) return "Age not specified";
    $birthdate = new DateTime($dob);
    $today = new DateTime();
    if ($birthdate > $today) return "Invalid birth date";

    $interval = $today->diff($birthdate);
    $parts = [];
    if ($interval->y > 0) $parts[] = $interval->y . ' year' . ($interval->y > 1 ? 's' : '');
    if ($interval->m > 0) $parts[] = $interval->m . ' month' . ($interval->m > 1 ? 's' : '');
    if ($interval->y > 0) $parts[] = $interval->d . ' day' . ($interval->d > 1 ? 's' : '');


    return $parts ? implode(', ', $parts) : '0 months';
}

function markVaccinationCompleted($schedule_id, $administered_date = null, $administered_by = null) {
    global $conn;

    // Permission check
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'doctor'])) {
        return false;
    }

    try {
        $administered_date = $administered_date ?: date('Y-m-d');
        $current_date = date('Y-m-d');
        
        $conn->beginTransaction();

        // Get current vaccination details
        $stmt = $conn->prepare("SELECT status, due_date FROM vaccination_schedule WHERE schedule_id = ?");
        $stmt->execute([$schedule_id]);
        $vaccination = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vaccination) {
            throw new PDOException("Vaccination record not found");
        }

        // Role-based due date check
        if ($_SESSION['role'] === 'admin') {
            // Admin
            if (strtotime($vaccination['due_date']) > strtotime($current_date)) {
                throw new PDOException("Cannot complete vaccinations scheduled for future dates ");
            }
        } elseif ($_SESSION['role'] === 'doctor') {
            // Doctor
            if (strtotime($vaccination['due_date']) !== strtotime($current_date)) {
                throw new PDOException("Doctor can only complete vaccinations due today");
            }
        }


        // Update record
        $stmt = $conn->prepare("UPDATE vaccination_schedule 
                              SET status = 'Completed', 
                                  completed_date = ?,
                                  administered_by = ?,
                                  updated_at = NOW()
                              WHERE schedule_id = ?");
        if (!$stmt->execute([$administered_date, $administered_by ?? $_SESSION['user_id'], $schedule_id])) {
            throw new PDOException("Failed to update vaccination record");
        }


        $stmt = $conn->prepare("SELECT vs.child_id, v.name AS vaccine_name 
                               FROM vaccination_schedule vs 
                               JOIN vaccines v ON vs.vaccine_id = v.vaccine_id 
                               WHERE vs.schedule_id = ?");
        $stmt->execute([$schedule_id]);
        $vaccination_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($vaccination_info) {
            createNotification(
                getGuardianId($vaccination_info['child_id']),
                "Vaccination completed: {$vaccination_info['vaccine_name']} on " . date('F j, Y', strtotime($administered_date))
            );

            $stmt = $conn->prepare("SELECT date_of_birth FROM children WHERE child_id = ?");
            $stmt->execute([$vaccination_info['child_id']]);
            $child = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($child) {
                generateVaccinationSchedule(
                    $vaccination_info['child_id'],
                    $child['date_of_birth'],
                    $administered_date,
                    $vaccination_info['vaccine_name']
                );
            }
        }

        $conn->commit();
        return true;
        
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
        return false;
    }
}


function notifyMissedVaccines($child_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT vs.schedule_id, vs.due_date, v.name AS vaccine_name, c.guardian_id
        FROM vaccination_schedule vs
        JOIN vaccines v ON vs.vaccine_id = v.vaccine_id
        JOIN children c ON vs.child_id = c.child_id
        WHERE vs.child_id = ? AND vs.status = 'Missed'");
    $stmt->execute([$child_id]);
    $missed = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($missed as $row) {


        $notifCheck = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND child_id = ? AND message LIKE ?");
        $likeMsg = '%Missed vaccination: ' . $row['vaccine_name'] . '%';
        $notifCheck->execute([$row['guardian_id'], $child_id, $likeMsg]);
        if ($notifCheck->fetchColumn() == 0) {
            
            $message = 'Missed vaccination: ' . $row['vaccine_name'] . ' (was due ' . date('F j, Y', strtotime($row['due_date'])) . ')';
            $insert = $conn->prepare("INSERT INTO notifications (user_id, child_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
            $insert->execute([$row['guardian_id'], $child_id, $message]);
        }
    }
}

?>
