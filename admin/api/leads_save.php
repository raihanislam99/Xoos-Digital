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
$id = (int)($_POST['id'] ?? 0);

switch ($action) {
    case 'save_lead':
        csrf_verify();
        $data = [
            'business_name' => substr(strip_tags(trim($_POST['business_name'] ?? '')), 0, 255),
            'niche' => substr(strip_tags(trim($_POST['niche'] ?? '')), 0, 100),
            'owner_name' => substr(strip_tags(trim($_POST['owner_name'] ?? '')), 0, 150),
            'email' => substr(strip_tags(trim($_POST['email'] ?? '')), 0, 255),
            'phone' => substr(strip_tags(trim($_POST['phone'] ?? '')), 0, 50),
            'whatsapp' => substr(strip_tags(trim($_POST['whatsapp'] ?? '')), 0, 50),
            'website' => substr(strip_tags(trim($_POST['website'] ?? '')), 0, 500),
            'facebook' => substr(strip_tags(trim($_POST['facebook'] ?? '')), 0, 500),
            'instagram' => substr(strip_tags(trim($_POST['instagram'] ?? '')), 0, 500),
            'address' => strip_tags(trim($_POST['address'] ?? '')),
            'city' => substr(strip_tags(trim($_POST['city'] ?? '')), 0, 100),
            'country' => substr(strip_tags(trim($_POST['country'] ?? 'Bangladesh')), 0, 100),
            'google_maps_url' => substr(strip_tags(trim($_POST['google_maps_url'] ?? '')), 0, 500),
            'has_website' => (int)(!empty($_POST['website'])),
            'website_score' => (int)($_POST['website_score'] ?? 0),
            'lead_score' => (int)($_POST['lead_score'] ?? 0),
            'ai_audit' => strip_tags(trim($_POST['ai_audit'] ?? '')),
            'source' => substr(strip_tags(trim($_POST['source'] ?? 'manual')), 0, 100),
            'tags' => substr(strip_tags(trim($_POST['tags'] ?? '')), 0, 500),
            'notes' => strip_tags(trim($_POST['notes'] ?? '')),
        ];

        // Auto-calculate lead score if not explicitly provided
        if (empty($_POST['lead_score']) && empty($_POST['id'])) {
            $score = 30;
            if ($data['website']) $score += 15;
            if ($data['email']) $score += 10;
            if ($data['phone']) $score += 10;
            if ($data['whatsapp']) $score += 5;
            if ($data['facebook']) $score += 5;
            if ($data['instagram']) $score += 5;
            if ($data['owner_name']) $score += 5;
            if ($data['city']) $score += 5;
            if ($data['niche']) $score += 10;
            $data['lead_score'] = max(0, min(100, $score));
        }

        try {
            if ($id) {
                db_update('leads', $data, 'id = ?', [$id]);
                echo json_encode(['success' => true, 'message' => 'Lead updated', 'id' => $id]);
            } else {
                $id = db_insert('leads', $data);
                echo json_encode(['success' => true, 'message' => 'Lead saved', 'id' => $id]);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            error_log('leads_save[save_lead]: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'quick_save':
        csrf_verify();
        $fields = ['business_name', 'niche', 'owner_name', 'email', 'phone', 'website', 'city', 'country', 'source', 'lead_score', 'has_website'];
        $data = [];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $data[$f] = substr(strip_tags(trim($_POST[$f])), 0, 500);
            }
        }
        if (empty($data['business_name'])) {
            echo json_encode(['success' => false, 'error' => 'Business name required']);
            exit;
        }
        // Auto-calculate lead score for new leads if not provided
        if (!isset($data['lead_score']) && !$id) {
            $score = 30;
            if (!empty($data['website'])) $score += 15;
            if (!empty($data['email'])) $score += 10;
            if (!empty($data['phone'])) $score += 10;
            if (!empty($data['owner_name'])) $score += 5;
            if (!empty($data['city'])) $score += 5;
            if (!empty($data['niche'])) $score += 10;
            $data['lead_score'] = max(0, min(100, $score));
        }
        try {
            if ($id) {
                db_update('leads', $data, 'id = ?', [$id]);
                echo json_encode(['success' => true, 'message' => 'Updated', 'id' => $id]);
            } else {
                $newId = db_insert('leads', $data);
                echo json_encode(['success' => true, 'message' => 'Saved', 'id' => $newId]);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'delete_lead':
        csrf_verify();
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID required']);
            exit;
        }
        try {
            db_delete('leads', 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => 'Deleted']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'update_status':
        csrf_verify();
        $status = $_POST['status'] ?? '';
        $allowed = ['new','contacted','replied','interested','meeting_booked','closed_won','closed_lost'];
        if (!in_array($status, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
            exit;
        }
        try {
            db_update('leads', ['status' => $status], 'id = ?', [$id]);
            db_insert('lead_activity', [
                'lead_id' => $id,
                'type' => 'status_change',
                'content' => 'Status changed to ' . $status,
            ]);
            echo json_encode(['success' => true, 'message' => 'Status updated']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'update_tags':
        csrf_verify();
        $tags = substr(strip_tags(trim($_POST['tags'] ?? '')), 0, 500);
        try {
            db_update('leads', ['tags' => $tags], 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => 'Tags updated']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'add_note':
        csrf_verify();
        $note = strip_tags(trim($_POST['note'] ?? ''));
        if (!$note || !$id) {
            echo json_encode(['success' => false, 'error' => 'Note and ID required']);
            exit;
        }
        try {
            db_insert('lead_activity', [
                'lead_id' => $id,
                'type' => 'note',
                'content' => $note,
            ]);
            if (!empty($_POST['update_notes'])) {
                $existing = db_val("SELECT notes FROM leads WHERE id = ?", [$id]) ?: '';
                db_update('leads', ['notes' => $existing . "\n" . $note], 'id = ?', [$id]);
            }
            echo json_encode(['success' => true, 'message' => 'Note added']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'bulk_action':
        csrf_verify();
        $ids = isset($_POST['ids']) ? array_map('intval', explode(',', $_POST['ids'])) : [];
        $bulkAction = $_POST['bulk_action'] ?? '';
        if (empty($ids)) {
            echo json_encode(['success' => false, 'error' => 'No IDs selected']);
            exit;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        try {
            switch ($bulkAction) {
                case 'delete':
                    db_delete('leads', "id IN ($placeholders)", $ids);
                    echo json_encode(['success' => true, 'message' => count($ids) . ' leads deleted']);
                    break;
                case 'change_status':
                    $status = $_POST['status'] ?? '';
                    $allowed = ['new','contacted','replied','interested','meeting_booked','closed_won','closed_lost'];
                    if (!in_array($status, $allowed)) {
                        echo json_encode(['success' => false, 'error' => 'Invalid status']);
                        exit;
                    }
                    db_update('leads', ['status' => $status], "id IN ($placeholders)", $ids);
                    echo json_encode(['success' => true, 'message' => 'Status updated for ' . count($ids) . ' leads']);
                    break;
                case 'add_tag':
                    $tag = substr(strip_tags(trim($_POST['tag'] ?? '')), 0, 50);
                    if (!$tag) {
                        echo json_encode(['success' => false, 'error' => 'Tag required']);
                        exit;
                    }
                    $stmt = db()->prepare("SELECT id, tags FROM leads WHERE id IN ($placeholders)");
                    $stmt->execute($ids);
                    while ($row = $stmt->fetch()) {
                        $currentTags = $row['tags'] ? explode(',', $row['tags']) : [];
                        if (!in_array($tag, $currentTags)) {
                            $currentTags[] = $tag;
                        }
                        db_update('leads', ['tags' => implode(',', $currentTags)], 'id = ?', [$row['id']]);
                    }
                    echo json_encode(['success' => true, 'message' => 'Tag added to ' . count($ids) . ' leads']);
                    break;
                default:
                    echo json_encode(['success' => false, 'error' => 'Unknown bulk action']);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'blacklist':
        csrf_verify();
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID required']);
            exit;
        }
        try {
            $val = (int)(!empty($_POST['blacklist']));
            db_update('leads', ['is_blacklisted' => $val], 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => $val ? 'Blacklisted' : 'Removed from blacklist']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'set_reminder':
        csrf_verify();
        $reminderDate = strip_tags(trim($_POST['reminder_date'] ?? ''));
        $note = 'Reminder set for ' . $reminderDate . ': ' . strip_tags(trim($_POST['reminder_note'] ?? ''));
        try {
            db_insert('lead_activity', [
                'lead_id' => $id,
                'type' => 'reminder',
                'content' => $note,
            ]);
            echo json_encode(['success' => true, 'message' => 'Reminder set']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'save_template':
        csrf_verify();
        $tplData = [
            'name' => substr(strip_tags(trim($_POST['name'] ?? '')), 0, 150),
            'type' => $_POST['type'] ?? 'email',
            'niche' => substr(strip_tags(trim($_POST['niche'] ?? '')), 0, 100),
            'service' => substr(strip_tags(trim($_POST['service'] ?? '')), 0, 100),
            'tone' => $_POST['tone'] ?? 'professional',
            'subject' => substr(strip_tags(trim($_POST['subject'] ?? '')), 0, 255),
            'body' => $_POST['body'] ?? '',
        ];
        $tplId = (int)($_POST['template_id'] ?? 0);
        try {
            if ($tplId) {
                db_update('outreach_templates', $tplData, 'id = ?', [$tplId]);
                echo json_encode(['success' => true, 'message' => 'Template updated', 'id' => $tplId]);
            } else {
                $newId = db_insert('outreach_templates', $tplData);
                echo json_encode(['success' => true, 'message' => 'Template saved', 'id' => $newId]);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'delete_template':
        csrf_verify();
        $tplId = (int)($_POST['template_id'] ?? 0);
        if (!$tplId) {
            echo json_encode(['success' => false, 'error' => 'ID required']);
            exit;
        }
        try {
            db_delete('outreach_templates', 'id = ?', [$tplId]);
            echo json_encode(['success' => true, 'message' => 'Template deleted']);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
