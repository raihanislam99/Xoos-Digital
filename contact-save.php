<?php
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/inc/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = $_POST;
$raw = file_get_contents('php://input');
if (empty($input) && $raw) {
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) $input = $parsed;
}

$name     = trim(strip_tags($input['name'] ?? ''));
$email    = trim($input['email'] ?? '');
$phone    = trim(strip_tags($input['phone'] ?? ''));
$company  = trim(strip_tags($input['company'] ?? ''));
$country  = trim(strip_tags($input['country'] ?? ''));
$services = trim(strip_tags($input['services'] ?? ''));
$budget   = trim(strip_tags($input['budget'] ?? ''));
$timeline = trim(strip_tags($input['timeline'] ?? ''));
$message  = trim(strip_tags($input['message'] ?? ''));

$name     = mb_substr($name, 0, 255);
$email    = mb_substr($email, 0, 255);
$phone    = mb_substr($phone, 0, 50);
$company  = mb_substr($company, 0, 255);
$country  = mb_substr($country, 0, 100);
$services = mb_substr($services, 0, 255);
$budget   = mb_substr($budget, 0, 100);
$timeline = mb_substr($timeline, 0, 100);
$message  = mb_substr($message, 0, 5000);

if (!$name || !$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Name and email are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

try {
    db_insert('contact_messages', [
        'name'       => $name,
        'email'      => $email,
        'phone'      => $phone,
        'company'    => $company,
        'country'    => $country,
        'services'   => $services,
        'budget'     => $budget,
        'timeline'   => $timeline,
        'message'    => $message,
        'is_read'    => 0,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
    echo json_encode(['saved' => true, 'message' => 'Thank you! We have received your inquiry.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save message']);
}
