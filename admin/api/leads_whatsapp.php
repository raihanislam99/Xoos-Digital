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
        $message = trim($_POST['message'] ?? '');

        if (!$leadId || !$message) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

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

        try {
            db_insert('lead_whatsapp', [
                'lead_id' => $leadId,
                'message' => $message,
                'status' => 'draft',
            ]);
            db_insert('lead_activity', [
                'lead_id' => $leadId,
                'type' => 'whatsapp_sent',
                'content' => 'WhatsApp message prepared: ' . mb_substr($message, 0, 100),
            ]);
            db_update('leads', ['status' => 'contacted'], "id = ? AND status IN ('new')", [$leadId]);

            $phone = $lead['whatsapp'] ?: $lead['phone'];
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (substr($phone, 0, 1) === '0') {
                $phone = '88' . $phone;
            }
            $waLink = 'https://wa.me/' . $phone . '?text=' . urlencode($message);

            echo json_encode(['success' => true, 'message' => 'WhatsApp message ready', 'wa_link' => $waLink]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
