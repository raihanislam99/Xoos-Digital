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

$task = $input['task'] ?? '';
$context = $input['context'] ?? [];

if (!$task) {
    echo json_encode(['success' => false, 'error' => 'Missing task']);
    exit;
}

$systemBase = "You are Raihan Islam, founder of Xoos Digital in Dhaka, Bangladesh. Your agency builds WordPress websites, WooCommerce stores, brand identities, and runs digital marketing. Your target clients are local Bangladeshi businesses and international SMEs. You write emails that are SHORT, GENUINE, and focused on the specific problem you can solve for that business. Never be pushy. Never use templates that sound like spam. Always mention something SPECIFIC about the recipient's business.";

$prompts = [
    'generate_cold_email' => [
        'system' => $systemBase . " Generate a cold outreach email. Return ONLY a JSON object with keys: subject, body. Max 150 words. Use HTML for body paragraphs. No markdown fences.",
        'user' => function($ctx) {
            $business = $ctx['business_name'] ?? 'the business';
            $niche = $ctx['niche'] ?? '';
            $city = $ctx['city'] ?? '';
            $hasWebsite = !empty($ctx['has_website']);
            $issues = $ctx['issues'] ?? '';
            $service = $ctx['service'] ?? 'WordPress Website Design';
            $tone = $ctx['tone'] ?? 'professional';
            $lang = $ctx['language'] ?? 'English';
            $type = $ctx['email_type'] ?? 'cold';

            $prompt = "Generate a $tone cold outreach email in $lang language.\n";
            $prompt .= "Business: $business\nNiche: $niche\nCity: $city\n";
            $prompt .= "Has website: " . ($hasWebsite ? 'Yes' : 'No') . "\n";
            if ($issues) $prompt .= "Website issues: $issues\n";
            $prompt .= "Service to pitch: $service\n";
            $prompt .= "Email type: $type\n";
            $prompt .= "Return JSON with subject and body fields only.";
            return $prompt;
        },
        'maxTokens' => 600,
    ],
    'generate_followup' => [
        'system' => $systemBase . " Generate a FOLLOW-UP email. Keep it shorter than the first. Reference the previous email. Return JSON {subject, body}. No markdown.",
        'user' => function($ctx) {
            return "Follow-up day: " . ($ctx['day'] ?? '3') . "\nBusiness: " . ($ctx['business_name'] ?? '') . "\nNiche: " . ($ctx['niche'] ?? '') . "\nService: " . ($ctx['service'] ?? 'WordPress Website Design') . "\nPrevious email subject: " . ($ctx['prev_subject'] ?? '') . "\nTone: " . ($ctx['tone'] ?? 'professional') . "\nLanguage: " . ($ctx['language'] ?? 'English') . "\nReturn JSON with subject and body.";
        },
        'maxTokens' => 500,
    ],
    'generate_whatsapp' => [
        'system' => $systemBase . " Generate a SHORT WhatsApp message (max 250 characters). First line: attention-grabbing opener. Middle: one specific pain point for their niche. End: soft CTA. Never pushy. Return ONLY the message text, no JSON, no quotes.",
        'user' => function($ctx) {
            $business = $ctx['business_name'] ?? '';
            $niche = $ctx['niche'] ?? '';
            $city = $ctx['city'] ?? '';
            $service = $ctx['service'] ?? 'WordPress Website Design';
            $tone = $ctx['tone'] ?? 'friendly';
            $lang = $ctx['language'] ?? 'English';
            $name = $ctx['owner_name'] ?? '';

            $prompt = "Generate a $tone WhatsApp message in $lang language.\n";
            if ($name) $prompt .= "Owner name: $name\n";
            $prompt .= "Business: $business\nNiche: $niche\nCity: $city\n";
            $prompt .= "Service: $service\n";
            $prompt .= "Max 250 characters. Return ONLY the message text.";
            return $prompt;
        },
        'maxTokens' => 200,
    ],
    'audit_summary' => [
        'system' => "You are a website auditor. Given audit data about a business website, write 2-3 sentences in plain English assessing the website's quality and what issues are costing them business. Be specific and constructive. Return only the text.",
        'user' => function($ctx) {
            $data = is_array($ctx) ? $ctx : [];
            $parts = [];
            foreach (['business_name','niche','website','ssl','speed','mobile','meta','title','contact_form','load_time','has_wp'] as $k) {
                if (isset($data[$k])) $parts[] = "$k: {$data[$k]}";
            }
            return "Audit results for " . ($data['business_name'] ?? 'Unknown') . ":\n" . implode("\n", $parts) . "\n\nWrite a 2-3 sentence assessment.";
        },
        'maxTokens' => 300,
    ],
    'score_lead' => [
        'system' => "You are a lead scoring AI. Rate a business lead from 0-100 based on how likely they need Xoos Digital services. Higher score = more need. Return ONLY a JSON object with: score (0-100), reasoning (1 sentence). No markdown.",
        'user' => function($ctx) {
            $data = is_array($ctx) ? $ctx : [];
            return "Business: " . ($data['business_name'] ?? '') . "\nNiche: " . ($data['niche'] ?? '') . "\nHas website: " . ($data['has_website'] ?? 'No') . "\nCity: " . ($data['city'] ?? '') . "\nWebsite issues: " . ($data['issues'] ?? 'None') . "\n\nReturn JSON with score and reasoning.";
        },
        'maxTokens' => 200,
    ],
    'suggest_service' => [
        'system' => $systemBase . " Given a business lead, suggest the ONE service Xoos Digital should pitch to them. Return ONLY the service name from this list: WordPress Website Design, WooCommerce E-Commerce Store, Creative Branding & Logo, SEO & Google Rankings, Facebook/Google Ads, Full Digital Package.",
        'user' => function($ctx) {
            $data = is_array($ctx) ? $ctx : [];
            return "Business: " . ($data['business_name'] ?? '') . "\nNiche: " . ($data['niche'] ?? '') . "\nHas website: " . ($data['has_website'] ?? 'No') . "\nWebsite issues: " . ($data['issues'] ?? 'None') . "\n\nSuggest ONE service only.";
        },
        'maxTokens' => 100,
    ],
    'improve_email' => [
        'system' => $systemBase . " Improve the given email draft to be more compelling and natural. Keep the same core message. Return JSON {subject, body}. No markdown.",
        'user' => function($ctx) {
            $data = is_array($ctx) ? $ctx : [];
            return "Improve this email draft:\n\nSubject: " . ($data['subject'] ?? '') . "\nBody: " . ($data['body'] ?? '') . "\n\nTone: " . ($data['tone'] ?? 'professional') . "\nReturn JSON with subject and body.";
        },
        'maxTokens' => 600,
    ],
    'translate_bangla' => [
        'system' => "You are a translator. Translate the given English email or message to natural Bangla (Bengali). Keep the same tone and structure. Return ONLY the translated text.",
        'user' => function($ctx) {
            $data = is_array($ctx) ? $ctx : [];
            return "Translate to Bangla:\n\n" . ($data['text'] ?? $ctx);
        },
        'maxTokens' => 500,
    ],
];

if (!isset($prompts[$task])) {
    echo json_encode(['success' => false, 'error' => 'Unknown task: ' . $task]);
    exit;
}

$prompt = $prompts[$task];
$userMessage = is_callable($prompt['user']) ? $prompt['user']($context) : (is_string($context) ? $context : '');

$messages = [
    ['role' => 'system', 'content' => $prompt['system']],
    ['role' => 'user', 'content' => $userMessage],
];

$settings = ai_feature_settings('admin');
$maxTokens = $prompt['maxTokens'] ?? 400;

try {
    $reply = ai_call($settings, $messages, $maxTokens, 0.7);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

$jsonTasks = ['generate_cold_email', 'generate_followup', 'score_lead', 'improve_email'];
if (in_array($task, $jsonTasks)) {
    $reply = preg_replace('/```(?:json)?\s*/i', '', $reply);
    $reply = preg_replace('/```/', '', $reply);
    $reply = trim($reply);
    $parsed = json_decode($reply, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode(['success' => true, 'data' => $parsed]);
        exit;
    }
    echo json_encode(['success' => true, 'data' => ['body' => $reply]]);
    exit;
}

echo json_encode(['success' => true, 'data' => $reply]);
