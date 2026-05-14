<?php
/**
 * ussd_callback.php — Africa's Talking USSD Session Handler
 * Child Immunization Tracker | Jarida Open Source
 *
 * Africa's Talking sends a POST request to this URL each time a user
 * interacts with the USSD menu. Register this file's public URL as your
 * USSD callback in the AT dashboard.
 *
 * AT sends:
 *   sessionId   — unique session identifier
 *   serviceCode — the USSD code dialled (e.g. *384*XXX#)
 *   phoneNumber — caller's number in E.164 format
 *   text        — accumulated user input, e.g. "" / "1" / "1*123456"
 *
 * Respond with:
 *   "CON <message>"  — continue session (show menu)
 *   "END <message>"  — end session (final message)
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/plain');

$sessionId   = $_POST['sessionId']   ?? '';
$serviceCode = $_POST['serviceCode'] ?? '';
$phoneNumber = $_POST['phoneNumber'] ?? '';
$text        = $_POST['text']        ?? '';

// Split accumulated input on * to get each step's response
$parts = ($text === '') ? [] : explode('*', $text);
$level = count($parts);

if ($level === 0) {
    // ── Level 0: main menu ─────────────────────────────────────────────────
    echo "CON Welcome to Child Immunization Tracker\n"
       . "1. Check vaccination schedule\n"
       . "2. Report missed vaccination\n"
       . "0. Exit";

} elseif ($level === 1) {

    $choice = $parts[0];

    if ($choice === '1') {
        echo "CON Enter your child's Health ID\n(found on vaccination card or registration slip):";

    } elseif ($choice === '2') {
        echo "CON Enter your child's Health ID\n(we will flag a missed dose for follow-up):";

    } elseif ($choice === '0') {
        echo "END Thank you. Stay well and keep your child's vaccinations up to date.";

    } else {
        echo "END Invalid option. Please dial again.";
    }

} elseif ($level === 2) {

    $choice   = $parts[0];
    $healthId = strtoupper(trim($parts[1]));

    // Look up child by health_id
    $stmt = $conn->prepare(
        "SELECT c.child_id, c.name AS child_name, c.date_of_birth, u.phone AS guardian_phone
         FROM children c
         JOIN users u ON c.guardian_id = u.user_id
         WHERE c.health_id = ?"
    );
    $stmt->execute([$healthId]);
    $child = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$child) {
        echo "END Health ID '$healthId' not found in the system.\n"
           . "Please check the ID and try again, or visit your nearest clinic.";
        exit;
    }

    $childName = htmlspecialchars($child['child_name'], ENT_QUOTES, 'UTF-8');

    if ($choice === '1') {
        // Fetch the next pending vaccination
        $stmt = $conn->prepare(
            "SELECT v.name AS vaccine_name, vs.due_date
             FROM vaccination_schedule vs
             JOIN vaccines v ON vs.vaccine_id = v.vaccine_id
             WHERE vs.child_id = ? AND vs.status = 'Pending'
             ORDER BY vs.due_date ASC
             LIMIT 1"
        );
        $stmt->execute([$child['child_id']]);
        $next = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($next) {
            $dueDate     = date('d M Y', strtotime($next['due_date']));
            $vaccineName = htmlspecialchars($next['vaccine_name'], ENT_QUOTES, 'UTF-8');
            echo "CON Child: $childName\n"
               . "Next vaccine: $vaccineName\n"
               . "Due: $dueDate\n\n"
               . "Reply 1 to confirm you will attend\n"
               . "Reply 0 to exit";
        } else {
            echo "END Child: $childName\n"
               . "All scheduled vaccinations are up to date.\n"
               . "Great work keeping your child protected!";
        }

    } elseif ($choice === '2') {
        // Flag missed vaccines for follow-up
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM vaccination_schedule
             WHERE child_id = ? AND status = 'Missed'"
        );
        $stmt->execute([$child['child_id']]);
        $missedCount = (int) $stmt->fetchColumn();

        if ($missedCount > 0) {
            // Log a follow-up notification
            $stmt = $conn->prepare(
                "INSERT INTO notifications (user_id, child_id, message, is_read, created_at)
                 SELECT guardian_id, child_id,
                        CONCAT('USSD follow-up request: ', ?, ' missed doses reported via USSD by guardian'),
                        0, NOW()
                 FROM children WHERE child_id = ?"
            );
            $stmt->execute([$missedCount, $child['child_id']]);

            echo "END Thank you. We have flagged $missedCount missed dose(s) for $childName.\n"
               . "A community health worker will follow up shortly.";
        } else {
            echo "END No missed vaccinations found for $childName.\n"
               . "All doses are on schedule. Thank you!";
        }
    }

} elseif ($level === 3 && $parts[0] === '1') {

    // User confirmed attendance at next vaccination appointment
    $healthId = strtoupper(trim($parts[1]));
    $confirm  = $parts[2];

    if ($confirm === '1') {
        $stmt = $conn->prepare(
            "SELECT c.child_id, c.name AS child_name
             FROM children c WHERE c.health_id = ?"
        );
        $stmt->execute([$healthId]);
        $child = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($child) {
            // Log confirmation as a notification for the health worker dashboard
            $stmt = $conn->prepare(
                "INSERT INTO notifications (user_id, child_id, message, is_read, created_at)
                 SELECT guardian_id, child_id,
                        'Guardian confirmed next vaccination appointment via USSD.',
                        0, NOW()
                 FROM children WHERE child_id = ?"
            );
            $stmt->execute([$child['child_id']]);

            $childName = htmlspecialchars($child['child_name'], ENT_QUOTES, 'UTF-8');
            echo "END Confirmed! We look forward to seeing $childName at the clinic.\n"
               . "You will receive an SMS reminder 7 days before the appointment.";
        } else {
            echo "END Session expired. Please dial again.";
        }

    } else {
        echo "END Thank you. Please visit the clinic when you are ready.\n"
           . "Contact your community health worker if you need help.";
    }

} else {
    echo "END Session ended. Dial again to check your child's vaccination schedule.";
}
