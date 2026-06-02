<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$url = trim($input['url'] ?? '');
$leadId = (int)($input['lead_id'] ?? 0);
$businessName = trim($input['business_name'] ?? '');
$niche = trim($input['niche'] ?? '');

if (!$url) {
    echo json_encode(['success' => false, 'error' => 'URL is required']);
    exit;
}

if (!preg_match('#^https?://#i', $url)) {
    $url = 'https://' . $url;
}

$audit = [
    'business_name' => $businessName,
    'niche' => $niche,
    'website' => $url,
    'ssl' => false,
    'load_time' => 0,
    'speed' => 'Unknown',
    'mobile' => false,
    'meta' => false,
    'title' => '',
    'has_title' => false,
    'contact_form' => false,
    'has_wp' => false,
    'last_modified' => '',
];

// SSL check
$audit['ssl'] = (strpos($url, 'https://') === 0);

// Fetch website
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HEADER => true,
]);

$start = microtime(true);
$response = curl_exec($ch);
$loadTime = microtime(true) - $start;
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);

if ($curlErr || $httpCode >= 400) {
    curl_close($ch);
    $audit['error'] = $curlErr ?: "HTTP $httpCode";
    $audit['lead_score'] = 60;
// Save score & audit data back to lead
if ($leadId) {
    db_update('leads', ['lead_score' => $score, 'website_score' => round($score * 0.8), 'has_website' => 1, 'ai_audit' => json_encode($audit)], 'id = ?', [$leadId]);
}

echo json_encode(['success' => true, 'data' => $audit]);
    exit;
}

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$html = substr($response, $headerSize);
curl_close($ch);

$audit['load_time'] = round($loadTime, 2);
if ($loadTime < 1) $audit['speed'] = 'Fast';
elseif ($loadTime < 3) $audit['speed'] = 'Moderate';
else $audit['speed'] = 'Slow';

// Check last modified
if (preg_match('/Last-Modified:\s*(.+)/i', $headers, $m)) {
    $audit['last_modified'] = trim($m[1]);
}

// Mobile viewport
$audit['mobile'] = stripos($html, 'viewport') !== false;

// Meta description
$audit['meta'] = preg_match('/<meta\s+name=["\']description["\']/i', $html) === 1;

// Title tag
if (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
    $audit['title'] = trim(strip_tags($m[1]));
    $audit['has_title'] = true;
}

// Contact form
$audit['contact_form'] = stripos($html, '<form') !== false;

// WordPress detection
$audit['has_wp'] = stripos($html, 'wp-content') !== false || stripos($html, 'wp-includes') !== false;

// Calculate lead score
$score = 100;
if (!$audit['ssl']) $score -= 20;
if ($audit['speed'] === 'Slow') $score -= 15;
elseif ($audit['speed'] === 'Moderate') $score -= 5;
if (!$audit['mobile']) $score -= 20;
if (!$audit['meta']) $score -= 10;
if (!$audit['contact_form']) $score -= 5;
if (!$audit['has_title']) $score -= 5;
$score = max(0, min(100, $score));
$audit['lead_score'] = $score;

// Generate AI summary if we have business context
if ($businessName) {
    try {
        $aiCtx = array_merge($audit, ['business_name' => $businessName, 'niche' => $niche]);
        $aiSettings = ai_feature_settings('admin');
        $aiMessages = [
            ['role' => 'system', 'content' => "You are a website auditor. Given audit data about a business website, write 2-3 sentences in plain English assessing the website's quality and what issues are costing them business. Be specific and constructive. Return only the text."],
            ['role' => 'user', 'content' => "Audit results for $businessName ($niche):\nSSL: " . ($audit['ssl'] ? 'Yes' : 'No') . "\nSpeed: {$audit['speed']} ({$audit['load_time']}s)\nMobile friendly: " . ($audit['mobile'] ? 'Yes' : 'No') . "\nMeta description: " . ($audit['meta'] ? 'Yes' : 'No') . "\nTitle: " . ($audit['has_title'] ? $audit['title'] : 'Missing') . "\nContact form: " . ($audit['contact_form'] ? 'Yes' : 'No') . "\nWordPress: " . ($audit['has_wp'] ? 'Yes' : 'No') . "\n\nWrite a 2-3 sentence assessment."],
        ];
        $audit['ai_summary'] = ai_call($aiSettings, $aiMessages, 300, 0.7);
    } catch (Exception $e) {
        $audit['ai_summary'] = '';
    }
}

// Save score & audit data back to lead
if ($leadId) {
    db_update('leads', ['lead_score' => $score, 'website_score' => round($score * 0.8), 'has_website' => 1, 'ai_audit' => json_encode($audit)], 'id = ?', [$leadId]);
}

echo json_encode(['success' => true, 'data' => $audit]);
