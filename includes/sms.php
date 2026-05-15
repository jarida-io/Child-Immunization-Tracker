<?php
/**
 * sms.php — Africa's Talking SMS & USSD Integration
 * Child Immunization Tracker | Jarida Open Source
 *
 * Handles outbound SMS reminders and inbound USSD sessions via the
 * Africa's Talking API (https://developers.africastalking.com).
 *
 * Configuration: set AT_SANDBOX to false and supply production credentials
 * before going live. Use environment variables in production.
 */

// ─── Africa's Talking credentials ────────────────────────────────────────────
// Replace these with your actual AT credentials.
// In production: use $_ENV or getenv() rather than hardcoded strings.
define('AT_USERNAME',  getenv('AT_USERNAME')  ?: 'sandbox');
define('AT_API_KEY',   getenv('AT_API_KEY')   ?: 'atsk_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
define('AT_SENDER_ID', getenv('AT_SENDER_ID') ?: '');          // Short-code/sender ID (leave empty for sandbox)
define('AT_SANDBOX',   getenv('AT_SANDBOX')   !== 'false');     // true = sandbox; set env AT_SANDBOX=false for production

// ─── API endpoints ────────────────────────────────────────────────────────────
define('AT_SMS_URL',  AT_SANDBOX
    ? 'https://api.sandbox.africastalking.com/version1/messaging'
    : 'https://api.africastalking.com/version1/messaging');


/**
 * Send an SMS via Africa's Talking.
 *
 * @param string|array $to      Phone number(s) in E.164 format (+254XXXXXXXXX).
 * @param string       $message Message body (max 160 chars for single SMS).
 * @return array ['success' => bool, 'response' => array|string]
 */
function sendSMS($to, string $message): array
{
    $recipients = is_array($to) ? implode(',', $to) : $to;

    $postData = http_build_query([
        'username' => AT_USERNAME,
        'to'       => $recipients,
        'message'  => $message,
    ]);

    if (AT_SENDER_ID !== '') {
        $postData .= '&from=' . urlencode(AT_SENDER_ID);
    }

    $ch = curl_init(AT_SMS_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'apiKey: '    . AT_API_KEY,
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);

    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("AT SMS cURL error: $error");
        return ['success' => false, 'response' => $error];
    }

    $decoded = json_decode($raw, true);
    $success = $httpCode === 201 &&
        isset($decoded['SMSMessageData']['Recipients'][0]['status']) &&
        $decoded['SMSMessageData']['Recipients'][0]['status'] === 'Success';

    if (!$success) {
        error_log("AT SMS failed (HTTP $httpCode): $raw");
    }

    return ['success' => $success, 'response' => $decoded];
}


/**
 * Format a Kenyan phone number to E.164 (+254XXXXXXXXX).
 * Accepts 07XXXXXXXX, 7XXXXXXXX, +2547XXXXXXXX, 2547XXXXXXXX.
 *
 * @param string $phone
 * @return string|null Formatted number or null if invalid.
 */
function formatKenyanPhone(string $phone): ?string
{
    $phone = preg_replace('/\D/', '', $phone);

    if (strlen($phone) === 9 && $phone[0] === '7') {
        return '+254' . $phone;
    }
    if (strlen($phone) === 10 && substr($phone, 0, 2) === '07') {
        return '+254' . substr($phone, 1);
    }
    if (strlen($phone) === 12 && substr($phone, 0, 3) === '254') {
        return '+' . $phone;
    }
    if (strlen($phone) === 13 && substr($phone, 0, 4) === '+254') {
        return $phone;
    }

    return null;
}


/**
 * Send a vaccination appointment reminder SMS to a guardian.
 *
 * @param string $phone     Guardian phone number (any KE format).
 * @param string $childName Child's name.
 * @param string $vaccine   Vaccine name.
 * @param string $dueDate   Due date string (Y-m-d).
 * @return bool
 */
function sendAppointmentReminderSMS(string $phone, string $childName, string $vaccine, string $dueDate): bool
{
    $formatted = formatKenyanPhone($phone);
    if (!$formatted) {
        error_log("AT SMS: invalid phone number '$phone'");
        return false;
    }

    $date    = date('d M Y', strtotime($dueDate));
    $message = "Child Immunization Reminder: $childName is due for $vaccine on $date. "
             . "Visit your nearest clinic. Reply HELP to this message or call your CHW for assistance.";

    $result = sendSMS($formatted, $message);
    return $result['success'];
}


/**
 * Send a missed vaccination alert SMS to a guardian.
 *
 * @param string $phone     Guardian phone number.
 * @param string $childName Child's name.
 * @param string $vaccine   Missed vaccine name.
 * @param string $dueDate   Original due date (Y-m-d).
 * @return bool
 */
function sendMissedVaccineSMS(string $phone, string $childName, string $vaccine, string $dueDate): bool
{
    $formatted = formatKenyanPhone($phone);
    if (!$formatted) {
        error_log("AT SMS: invalid phone number '$phone'");
        return false;
    }

    $date    = date('d M Y', strtotime($dueDate));
    $message = "IMPORTANT - Missed Vaccine: $childName missed the $vaccine vaccination "
             . "that was due on $date. Please visit your nearest clinic as soon as possible.";

    $result = sendSMS($formatted, $message);
    return $result['success'];
}


/**
 * Send a vaccination confirmation SMS to a guardian.
 *
 * @param string $phone     Guardian phone number.
 * @param string $childName Child's name.
 * @param string $vaccine   Vaccine administered.
 * @return bool
 */
function sendVaccinationConfirmationSMS(string $phone, string $childName, string $vaccine): bool
{
    $formatted = formatKenyanPhone($phone);
    if (!$formatted) {
        return false;
    }

    $date    = date('d M Y');
    $message = "Vaccination Confirmed: $childName received the $vaccine vaccine today ($date). "
             . "Thank you for keeping your child protected. - Child Immunization Tracker";

    $result = sendSMS($formatted, $message);
    return $result['success'];
}
