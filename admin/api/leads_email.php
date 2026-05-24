<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'send':
        csrf_verify();
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $body = $_POST['body'] ?? '';
        $type = $_POST['email_type'] ?? 'cold';
        $toEmail = trim($_POST['to_email'] ?? '');

        if (!$leadId || !$subject || !$body || !$toEmail) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

        // Check blacklist
        $lead = db_rows("SELECT * FROM leads WHERE id = ?", [$leadId]);
        if (!$lead) {
            echo json_encode(['success' => false, 'error' => 'Lead not found']);
            exit;
        }
        $lead = $lead[0];
        if ($lead['is_blacklisted']) {
            echo json_encode(['success' => false, 'error' => 'Lead is blacklisted']);
            exit;
        }

        // Check daily limit
        $sentToday = (int)db_val("SELECT COUNT(*) FROM lead_emails WHERE DATE(sent_at) = CURDATE() AND status != 'draft'");
        $dailyLimit = (int)get_setting('daily_email_limit', '50');
        if ($sentToday >= $dailyLimit) {
            echo json_encode(['success' => false, 'error' => "Daily email limit reached ($dailyLimit)"]);
            exit;
        }

        // Build email
        $fromName = get_setting('smtp_from_name', 'Raihan Islam — Xoos Digital');
        $fromEmail = get_setting('smtp_from_email', 'raihan@xoosdigital.com');
        $signature = nl2br(h(get_setting('lead_signature', "Raihan Islam\nFounder, Xoos Digital\nxoosdigital.com\n+880 1572-932943")));

        $fullBody = $body;
        $fullBody .= "\n\n<br><br>---<br>" . $signature;
        $fullBody .= "\n\n<br><br><small style='color:#999'>If you'd prefer not to receive emails, <a href='" . BASE_URL . "/unsubscribe?email=" . urlencode($toEmail) . "'>unsubscribe here</a>.</small>";

        // Strip any injected headers from body/subject
        $subject = str_replace(["\r", "\n"], '', $subject);
        $fullBody = str_ireplace(["\r\nContent-Type:", "\r\nBcc:", "\r\nCc:", "\r\nTo:", "\r\nFrom:"], '', $fullBody);

        // Send via PHP mail() or SMTP
        $host = get_setting('smtp_host', '');
        $port = (int)get_setting('smtp_port', '587');
        $user = get_setting('smtp_user', '');
        $pass = get_setting('smtp_pass', '');

        $sent = false;
        $error = '';

        if ($host && $user && $pass) {
            // SMTP send via fsockopen
            $sent = smtp_send($host, $port, $user, $pass, $fromEmail, $fromName, $toEmail, $subject, $fullBody, $error);
        } else {
            // Fallback to mail()
            $headers = "From: $fromName <$fromEmail>\r\n";
            $headers .= "Reply-To: $fromEmail\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $sent = mail($toEmail, $subject, $fullBody, $headers);
            if (!$sent) $error = 'mail() function failed';
        }

        if ($sent) {
            try {
                db_insert('lead_emails', [
                    'lead_id' => $leadId,
                    'subject' => $subject,
                    'body' => $body,
                    'type' => $type,
                    'status' => 'sent',
                    'sent_at' => date('Y-m-d H:i:s'),
                ]);
                db_insert('lead_activity', [
                    'lead_id' => $leadId,
                    'type' => 'email_sent',
                    'content' => "Email sent: $subject",
                ]);
                db_update('leads', ['status' => 'contacted'], "id = ? AND status IN ('new')", [$leadId]);
            } catch (Exception $e) {
                // Log but don't fail
            }
            echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send: ' . $error]);
        }
        break;

    case 'test':
        csrf_verify();
        $host = get_setting('smtp_host', '');
        $port = (int)get_setting('smtp_port', '587');
        $user = get_setting('smtp_user', '');
        $pass = get_setting('smtp_pass', '');
        $fromEmail = get_setting('smtp_from_email', '');
        $fromName = get_setting('smtp_from_name', '');

        if (!$host || !$user || !$pass) {
            echo json_encode(['success' => false, 'error' => 'SMTP not configured']);
            exit;
        }

        $testBody = "<h2>Test Email</h2><p>If you're reading this, your SMTP settings for Xoos Digital Leads module are working correctly.</p><p>Sent at: " . date('Y-m-d H:i:s') . "</p>";
        $error = '';
        $sent = smtp_send($host, $port, $user, $pass, $fromEmail, $fromName, $fromEmail, 'SMTP Test - Xoos Digital Leads', $testBody, $error);

        if ($sent) {
            echo json_encode(['success' => true, 'message' => 'Test email sent successfully to ' . $fromEmail]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed: ' . $error]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

function smtp_send($host, $port, $user, $pass, $fromEmail, $fromName, $toEmail, $subject, $htmlBody, &$error) {
    $errno = 0;
    $errstr = '';
    $socket = @fsockopen($host, $port, $errno, $errstr, 15);
    if (!$socket) {
        $error = "Socket failed: $errstr ($errno)";
        return false;
    }

    $log = [];
    $log[] = fgets($socket, 512);

    fwrite($socket, "EHLO leads.xoosdigital.com\r\n");
    $log[] = fgets($socket, 512);
    // Read all EHLO responses
    while (isset($socket) && !feof($socket)) {
        $line = fgets($socket, 512);
        if ($line === false) break;
        $log[] = $line;
        if (substr($line, 3, 1) === ' ') break;
    }

    if ($port == 587) {
        fwrite($socket, "STARTTLS\r\n");
        $log[] = fgets($socket, 512);
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        // Re-EHLO after STARTTLS
        fwrite($socket, "EHLO leads.xoosdigital.com\r\n");
        $log[] = fgets($socket, 512);
        while (isset($socket) && !feof($socket)) {
            $line = fgets($socket, 512);
            if ($line === false) break;
            $log[] = $line;
            if (substr($line, 3, 1) === ' ') break;
        }
    }

    fwrite($socket, "AUTH LOGIN\r\n");
    $log[] = fgets($socket, 512);
    fwrite($socket, base64_encode($user) . "\r\n");
    $log[] = fgets($socket, 512);
    fwrite($socket, base64_encode($pass) . "\r\n");
    $authResp = fgets($socket, 512);
    $log[] = $authResp;

    if (strpos($authResp, '235') === false && strpos($authResp, '334') === false) {
        $error = "SMTP auth failed: " . $authResp;
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return false;
    }

    fwrite($socket, "MAIL FROM:<$fromEmail>\r\n");
    $log[] = fgets($socket, 512);
    fwrite($socket, "RCPT TO:<$toEmail>\r\n");
    $log[] = fgets($socket, 512);

    fwrite($socket, "DATA\r\n");
    $log[] = fgets($socket, 512);

    $headers = "From: $fromName <$fromEmail>\r\n";
    $headers .= "Reply-To: $fromEmail\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "X-Mailer: Xoos Digital Leads/1.0\r\n";

    fwrite($socket, $headers . "\r\n" . $htmlBody . "\r\n.\r\n");
    $log[] = fgets($socket, 512);

    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return true;
}
