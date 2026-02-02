<?php
/**
 * Notification service: log to DB and optionally push email/SMS.
 * Requires config.php and db.php when sending (for notifications table / item contact).
 */

if (!function_exists('send_notification')) {
    /**
     * Log a notification and optionally send email/SMS.
     *
     * @param int $itemId Item id (for DB and to resolve contact_email/contact_phone)
     * @param string $channel 'email' or 'sms'
     * @param string $message Message text
     * @param string|null $toEmail Override recipient email (optional)
     * @param string|null $toPhone Override recipient phone (optional)
     * @return bool True if logged (and sent if push enabled)
     */
    function send_notification(int $itemId, string $channel, string $message, ?string $toEmail = null, ?string $toPhone = null): bool
    {
        global $mysqli;
        if (!isset($mysqli)) {
            return false;
        }
        $stmt = $mysqli->prepare("INSERT INTO notifications (item_id, channel, message) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $itemId, $channel, $message);
        if (!$stmt->execute()) {
            return false;
        }
        $pushed = false;
        if ($channel === 'email' && defined('NOTIFY_PUSH_EMAIL') && NOTIFY_PUSH_EMAIL) {
            $email = $toEmail;
            if ($email === null) {
                $r = $mysqli->query("SELECT contact_email FROM items WHERE id = " . (int) $itemId . " LIMIT 1");
                if ($r && ($row = $r->fetch_assoc())) {
                    $email = $row['contact_email'];
                }
            }
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $from = defined('NOTIFY_EMAIL_FROM') ? NOTIFY_EMAIL_FROM : 'noreply@localhost';
                $subject = 'Loyola Lost & Found – Match Alert';
                $headers = 'From: ' . $from . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
                $pushed = @mail($email, $subject, $message, $headers);
            }
        }
        if ($channel === 'sms' && defined('NOTIFY_PUSH_SMS') && NOTIFY_PUSH_SMS && defined('NOTIFY_SMS_GATEWAY_URL') && NOTIFY_SMS_GATEWAY_URL !== '') {
            $phone = $toPhone;
            if ($phone === null) {
                $r = $mysqli->query("SELECT contact_phone FROM items WHERE id = " . (int) $itemId . " LIMIT 1");
                if ($r && ($row = $r->fetch_assoc()) && !empty($row['contact_phone'])) {
                    $phone = $row['contact_phone'];
                }
            }
            if ($phone) {
                $url = str_replace(['{phone}', '{message}'], [rawurlencode($phone), rawurlencode($message)], NOTIFY_SMS_GATEWAY_URL);
                $ctx = stream_context_create(['http' => ['timeout' => 5]]);
                $pushed = @file_get_contents($url, false, $ctx) !== false;
            }
        }
        return true;
    }
}
