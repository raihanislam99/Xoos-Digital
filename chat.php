<?php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/admin/inc/functions.php';

// Rate limiting (session started by config.php)
$now = time();
if (!isset($_SESSION['rl_count'])) {
    $_SESSION['rl_count']  = 0;
    $_SESSION['rl_start']  = $now;
}
if ($now - $_SESSION['rl_start'] > 3600) {
    $_SESSION['rl_count'] = 0;
    $_SESSION['rl_start'] = $now;
}
$_SESSION['rl_count']++;
$rateLimit = 20;
if ($_SESSION['rl_count'] > $rateLimit) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many messages. Please wait a moment.']);
    exit;
}

// Parse input
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (empty($input['messages']) || !is_array($input['messages'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Sanitize messages
$messages = array_map(function($msg) {
    return [
        'role'    => in_array($msg['role'], ['user','assistant','system'])
                     ? $msg['role'] : 'user',
        'content' => substr(strip_tags($msg['content']), 0, 2000)
    ];
}, $input['messages']);

// Get chatbot AI settings
$settings = ai_feature_settings('chatbot');

try {
    $reply = ai_call($settings, $messages, 350, 0.7);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

echo json_encode(['reply' => trim($reply)]);
