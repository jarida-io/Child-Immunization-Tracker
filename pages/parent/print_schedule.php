<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_clean();
ob_start();

session_start();
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;

// Get child_id from URL
$child_id = $_GET['child_id'] ?? null;

// Validate child_id exists
if (!$child_id || !is_numeric($child_id)) {
    header('Location: dashboard.php?error=invalid_child_id');
    exit();
}

// Check user authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /../auth/login.php');
    exit();
}

// Verify user has access to this child
$stmt = $conn->prepare("SELECT c.* FROM children c
    LEFT JOIN caregiver_assignments ca ON c.child_id = ca.child_id
    WHERE c.child_id = ? 
    AND (c.guardian_id = ? OR ca.caregiver_id = ?)");
$stmt->execute([$child_id, $_SESSION['user_id'], $_SESSION['user_id']]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    header('Location: dashboard.php?error=unauthorized_access');
    exit();
}

// Fetch vaccination schedule
try {
    $stmt = $conn->prepare("SELECT vs.*, v.name as vaccine_name, v.disease_prevented 
                           FROM vaccination_schedule vs
                           JOIN vaccines v ON vs.vaccine_id = v.vaccine_id
                           WHERE vs.child_id = ?
                           ORDER BY vs.due_date");
    $stmt->execute([$child_id]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Location: dashboard.php?error=database_error');
    exit();
}

// Generate HTML for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Vaccination Schedule for ' . htmlspecialchars($child['name']) . '</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; }
        h1 { color: #333; text-align: center; margin-bottom: 20px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; }
        .schedule-table { width: 100%; border-collapse: collapse; }
        .schedule-table th, .schedule-table td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
        }
        .schedule-table th { 
            background-color: #f2f2f2; 
            font-weight: bold; 
        }
        .completed { background-color: #d4edda; }
        .pending { background-color: #fff3cd; }
        .missed { background-color: #f8d7da; }
        .footer { margin-top: 30px; text-align: right; font-size: 0.8em; color: #666; }
    </style>
</head>
<body>
    <h1>Vaccination Schedule</h1>
    
    <table class="info-table">
        <tr>
            <td><strong>Child Name:</strong></td>
            <td>' . htmlspecialchars($child['name']) . '</td>
        </tr>
        <tr>
            <td><strong>Date of Birth:</strong></td>
            <td>' . date('F j, Y', strtotime($child['date_of_birth'])) . '</td>
        </tr>
        <tr>
            <td><strong>Health ID:</strong></td>
            <td>' . htmlspecialchars($child['health_id']) . '</td>
        </tr>
    </table>
    
    <table class="schedule-table">
        <thead>
            <tr>
                <th>Vaccine</th>
                <th>Disease Prevented</th>
                <th>Dose #</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Administered Date</th>
            </tr>
        </thead>
        <tbody>';

foreach ($schedule as $vaccination) {
    $html .= '<tr class="' . strtolower($vaccination['status']) . '">
                <td>' . htmlspecialchars($vaccination['vaccine_name']) . '</td>
                <td>' . htmlspecialchars($vaccination['disease_prevented']) . '</td>
                <td>' . $vaccination['dose_number'] . '</td>
                <td>' . date('M j, Y', strtotime($vaccination['due_date'])) . '</td>
                <td>' . $vaccination['status'] . '</td>
                <td>' . ($vaccination['completed_date'] ? date('M j, Y', strtotime($vaccination['administered_date'])) : 'Not administered') . '</td>
            </tr>';
}

$html .= '</tbody>
    </table>
    
    <div class="footer">
        <p>Generated on: ' . date('F j, Y \a\t H:i:s') . '</p>
    </div>
</body>
</html>';

// Generate and output PDF
try {
    $dompdf = new Dompdf([
        'isRemoteEnabled' => true,
        'isHtml5ParserEnabled' => true,
        'isPhpEnabled' => true,
    ]);
    
    // Set better error handling for Dompdf
    $dompdf->set_option('isFontSubsettingEnabled', true);
    $dompdf->set_option('defaultMediaType', 'all');
    $dompdf->set_option('isFontSubsettingEnabled', true);
    
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Verify PDF was generated
    if (empty($dompdf->output())) {
        throw new Exception("PDF generation failed - empty output");
    }
    
    // Generate safe filename
    $filename = 'Vaccination_Schedule_' . preg_replace('/[^a-zA-Z0-9]/', '_', $child['name']) . '.pdf';
    
    // Clear any previous output
    ob_end_clean();
    
    // Output the PDF
    $dompdf->stream($filename, [
        'Attachment' => true,
        'compress' => true
    ]);
    exit();
    
} catch (Exception $e) {
    // Redirect on PDF generation error
    header('Location: child_profile.php?child_id=' . $child_id . '&error=pdf_error');
    exit();
}