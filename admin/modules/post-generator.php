<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

$view = $_GET['view'] ?? 'dashboard';
$pdo = db();

$platforms = [
    'linkedin'  => ['label' => 'LinkedIn',  'icon' => 'ti-brand-linkedin',  'color' => '#0a66c2'],
    'facebook'  => ['label' => 'Facebook',  'icon' => 'ti-brand-facebook',  'color' => '#1877f2'],
    'instagram' => ['label' => 'Instagram', 'icon' => 'ti-brand-instagram', 'color' => '#e4405f'],
    'x'         => ['label' => 'X (Twitter)','icon' => 'ti-brand-x',        'color' => '#000000'],
    'reddit'    => ['label' => 'Reddit',    'icon' => 'ti-brand-reddit',    'color' => '#ff4500'],
    'youtube'   => ['label' => 'YouTube',   'icon' => 'ti-brand-youtube',   'color' => '#ff0000'],
    'tiktok'    => ['label' => 'TikTok',    'icon' => 'ti-brand-tiktok',    'color' => '#000000'],
    'telegram'  => ['label' => 'Telegram',  'icon' => 'ti-brand-telegram',  'color' => '#0088cc'],
    'whatsapp'  => ['label' => 'WhatsApp',  'icon' => 'ti-brand-whatsapp',  'color' => '#25d366'],
];
function plat($key, $field) { global $platforms; return $platforms[$key][$field] ?? ''; }

// ── DB Migration ──
try {
    $pdo->exec("ALTER TABLE post_profiles ADD COLUMN IF NOT EXISTS language VARCHAR(50) DEFAULT 'english'");
    $pdo->exec("ALTER TABLE post_profiles ADD COLUMN IF NOT EXISTS tone VARCHAR(50) DEFAULT 'semi-professional'");
    $pdo->exec("ALTER TABLE post_profiles ADD COLUMN IF NOT EXISTS niche VARCHAR(500) DEFAULT ''");
    $pdo->exec("ALTER TABLE post_profiles ADD COLUMN IF NOT EXISTS color VARCHAR(7) DEFAULT '#c8f135'");
    $pdo->exec("ALTER TABLE post_profiles ADD COLUMN IF NOT EXISTS post_length INT DEFAULT 200");
    $pdo->exec("ALTER TABLE post_profiles ADD COLUMN IF NOT EXISTS type VARCHAR(20) DEFAULT 'personal'");
    $pdo->exec("ALTER TABLE post_profiles ADD COLUMN IF NOT EXISTS business_type VARCHAR(200) DEFAULT ''");
    $pdo->exec("ALTER TABLE post_profiles ADD COLUMN IF NOT EXISTS target_audience TEXT DEFAULT ''");
    $pdo->exec("ALTER TABLE post_profiles ADD COLUMN IF NOT EXISTS brand_voice TEXT DEFAULT ''");
    $pdo->exec("ALTER TABLE post_profiles ADD COLUMN IF NOT EXISTS avoid_topics TEXT DEFAULT ''");
    $pdo->exec("ALTER TABLE post_training_data ADD COLUMN IF NOT EXISTS profile_id INT DEFAULT NULL");
    $pdo->exec("CREATE TABLE IF NOT EXISTS post_hashtags (id INT AUTO_INCREMENT PRIMARY KEY, platform VARCHAR(50) NOT NULL, tag VARCHAR(100) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("ALTER TABLE generated_posts ADD COLUMN IF NOT EXISTS profile_id INT DEFAULT NULL");
    $pdo->exec("ALTER TABLE generated_posts ADD COLUMN IF NOT EXISTS linkedin_content TEXT");
    $pdo->exec("ALTER TABLE generated_posts ADD COLUMN IF NOT EXISTS facebook_content TEXT");
    $pdo->exec("ALTER TABLE generated_posts ADD COLUMN IF NOT EXISTS hashtags_used TEXT");
} catch (Exception $e) { /* columns may already exist */ }

// ── Seed default profiles ──
$existingProfiles = $pdo->query("SELECT COUNT(*) FROM post_profiles")->fetchColumn();
if (!$existingProfiles) {
    $defaults = [
        ['Jahidul Islam', 'linkedin', 'english', 'semi-professional', 'Digital marketing, agency, freelancing, personal brand', '#c8f135', '', 200, 'personal'],
        ['Xoos Digital', 'linkedin', 'english', 'professional', 'Digital agency, web design, branding, marketing services', '#0a66c2', '', 200, 'personal'],
        ['Personal Facebook', 'facebook', 'mixed', 'casual', 'Cricket, rajniti, trending topics, humor, lifestyle Bangladesh', '#1877f2', '', 200, 'personal'],
        ['Xoos Digital Page', 'facebook', 'mixed', 'semi-professional', 'Web design, graphics, digital marketing for Bangladeshi businesses', '#4267B2', '', 200, 'personal'],
        ['Jahidul Islam', 'instagram', 'mixed', 'casual', 'Digital marketing, lifestyle, Bangladesh, travel, food', '#e4405f', '', 200, 'personal'],
        ['Xoos Digital', 'x', 'english', 'professional', 'Digital marketing, web design, branding, tech trends Bangladesh', '#000000', '', 200, 'personal'],
        ['Jahidul Vlogs', 'youtube', 'mixed', 'casual', 'Digital marketing tutorials, freelancing tips, web design, Bangladesh', '#ff0000', '', 200, 'personal'],
    ];
    $stmt = $pdo->prepare("INSERT INTO post_profiles (name, platform, language, tone, niche, color, profile_url, post_length, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($defaults as $d) $stmt->execute($d);
}

// ── Seed client profiles ──
$clientCheck = $pdo->query("SELECT COUNT(*) FROM post_profiles WHERE type='client'")->fetchColumn();
if (!$clientCheck) {
    $clients = [
        ['Holy Basket', 'facebook', 'mixed', 'casual', 200, 'organic food, healthy eating, local produce, chemical-free lifestyle', '#4caf50', '', 'health-conscious urban Bangladeshis, mothers, age 25-45', 'warm, friendly, trustworthy, natural, community-focused', 'politics, religion, competitors, unhealthy food comparisons'],
        ['Skill Planet', 'facebook', 'mixed', 'semi-professional', 200, 'air ticketing, visa processing, GDS training, aviation career, EdTech', '#2196f3', '', 'job seekers, fresh graduates, career changers, age 18-35 Bangladesh', 'motivating, career-focused, aspirational, practical', 'politics, unverified success claims, competitor institutes'],
        ['Holy Basket', 'instagram', 'mixed', 'casual', 200, 'organic food, healthy eating, local produce, chemical-free lifestyle', '#4caf50', '', 'health-conscious urban Bangladeshis, mothers, age 25-45', 'warm, friendly, trustworthy, natural, community-focused', 'politics, religion, competitors, unhealthy food comparisons'],
        ['TechTune BD', 'x', 'english', 'professional', 200, 'tech news, gadget reviews, software updates, Bangladesh tech scene', '#1da1f2', '', 'tech enthusiasts, developers, students, age 18-40 Bangladesh', 'informative, timely, trustworthy, neutral', 'politics, religion, spam, fake news'],
        ['Cricket Bangla', 'reddit', 'mixed', 'casual', 200, 'Bangladesh cricket, BPL, international cricket, analysis, memes', '#ff4500', '', 'cricket fans, Bangladesh sports enthusiasts, age 18-45', 'passionate, fan-first, humorous, analytical', 'politics, religion, player bashing, hate speech'],
        ['Xoos Tech Reviews', 'youtube', 'english', 'semi-professional', 200, 'web design tutorials, digital marketing tips, tech reviews, freelancing', '#ff0000', '', 'aspiring freelancers, small business owners, students Bangladesh', 'helpful, practical, step-by-step, encouraging', 'politics, religion, unverified claims, plagiarism'],
    ];
    $cstmt = $pdo->prepare("INSERT INTO post_profiles (name, platform, language, tone, post_length, niche, color, profile_url, target_audience, brand_voice, avoid_topics, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'client')");
    foreach ($clients as $c) $cstmt->execute($c);
}

// ── AJAX handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'train_add') {
        $stmt = $pdo->prepare("INSERT INTO post_training_data (content, type, profile_id) VALUES (?, ?, ?)");
        $stmt->execute([trim($_POST['content']), $_POST['type'] ?? 'topic', $_POST['profile_id'] ? (int)$_POST['profile_id'] : null]);
        $_SESSION['flash_msg'] = 'Training data added.';
        $_SESSION['flash_type'] = 'success';
        redirect('post-generator.php?view=training');
    }

    if ($action === 'train_delete') {
        $pdo->prepare("DELETE FROM post_training_data WHERE id = ?")->execute([(int)$_POST['id']]);
        json_response(['ok' => true]);
    }

    if ($action === 'profile_add') {
        $stmt = $pdo->prepare("INSERT INTO post_profiles (platform, profile_url, name, notes, language, tone, niche, color, post_length, type, business_type, target_audience, brand_voice, avoid_topics) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['platform'], trim($_POST['profile_url'] ?? ''), trim($_POST['name']), trim($_POST['notes'] ?? ''), $_POST['language'] ?? 'english', $_POST['tone'] ?? 'semi-professional', trim($_POST['niche'] ?? ''), $_POST['color'] ?? '#c8f135', (int)($_POST['post_length'] ?? 200), $_POST['type'] ?? 'personal', trim($_POST['business_type'] ?? ''), trim($_POST['target_audience'] ?? ''), trim($_POST['brand_voice'] ?? ''), trim($_POST['avoid_topics'] ?? '')]);
        $_SESSION['flash_msg'] = 'Profile added.';
        $_SESSION['flash_type'] = 'success';
        redirect('post-generator.php?view=profiles');
    }

    if ($action === 'profile_delete') {
        $pdo->prepare("DELETE FROM post_profiles WHERE id = ?")->execute([(int)$_POST['id']]);
        json_response(['ok' => true]);
    }

    if ($action === 'profile_get') {
        $stmt = $pdo->prepare("SELECT * FROM post_profiles WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        $prof = $stmt->fetch();
        if (!$prof) json_response(['ok' => false, 'error' => 'Not found'], 404);
        json_response(['ok' => true, 'profile' => $prof]);
    }

    if ($action === 'profile_update') {
        $stmt = $pdo->prepare("UPDATE post_profiles SET name=?, platform=?, language=?, tone=?, niche=?, color=?, profile_url=?, notes=?, post_length=?, type=?, business_type=?, target_audience=?, brand_voice=?, avoid_topics=? WHERE id=?");
        $stmt->execute([trim($_POST['name']), $_POST['platform'], $_POST['language'] ?? 'english', $_POST['tone'] ?? 'semi-professional', trim($_POST['niche'] ?? ''), $_POST['color'] ?? '#c8f135', trim($_POST['profile_url'] ?? ''), trim($_POST['notes'] ?? ''), (int)($_POST['post_length'] ?? 200), $_POST['type'] ?? 'personal', trim($_POST['business_type'] ?? ''), trim($_POST['target_audience'] ?? ''), trim($_POST['brand_voice'] ?? ''), trim($_POST['avoid_topics'] ?? ''), (int)$_POST['id']]);
        json_response(['ok' => true]);
    }

    if ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        $status = $_POST['status'] === 'published' ? 'published' : 'draft';
        $pdo->prepare("UPDATE generated_posts SET status = ? WHERE id = ?")->execute([$status, $id]);
        json_response(['ok' => true]);
    }

    if ($action === 'delete_post') {
        $pdo->prepare("DELETE FROM generated_posts WHERE id = ?")->execute([(int)$_POST['id']]);
        json_response(['ok' => true]);
    }

    if ($action === 'update_post') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE generated_posts SET content = ? WHERE id = ?")->execute([trim($_POST['content']), $id]);
        json_response(['ok' => true]);
    }

    // ── Hashtag CRUD ──
    if ($action === 'hashtag_add') {
        $stmt = $pdo->prepare("INSERT INTO post_hashtags (platform, tag) VALUES (?, ?)");
        $stmt->execute([$_POST['platform'], trim($_POST['tag'])]);
        json_response(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }
    if ($action === 'hashtag_delete') {
        $pdo->prepare("DELETE FROM post_hashtags WHERE id = ?")->execute([(int)$_POST['id']]);
        json_response(['ok' => true]);
    }

    // ── Save generated posts ──
    if ($action === 'save_generated') {
        $stmt = $pdo->prepare("INSERT INTO generated_posts (platform, content, linkedin_content, facebook_content, language, status, topic, profile_id, hashtags_used, training_ids, profile_ids) VALUES (?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?, ?)");
        $platform = $_POST['platform'] ?? 'linkedin';
        $content = trim($_POST['content'] ?? '');
        $stmt->execute([$platform, $content, '', '', $_POST['language'] ?? 'en', $_POST['topic'] ?? '', (int)($_POST['profile_id'] ?? 0), '', $_POST['training_ids'] ?? '', $_POST['profile_ids'] ?? '']);
        json_response(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }

    // ── Generate Ideas (Claude) ──
    if ($action === 'generate_ideas') {
        $topic = trim($_POST['topic'] ?? '');
        $profileId = (int)($_POST['profile_id'] ?? 0);
        $selectedTone = trim($_POST['selected_tone'] ?? '');
        $selectedLength = (int)($_POST['selected_length'] ?? 0);
        $trainingIds = json_decode($_POST['training_ids'] ?? '[]', true);

        if (!$topic || !$profileId) { json_response(['ok' => false, 'error' => 'Select a profile and enter a topic.'], 400); exit; }

        $profile = $pdo->prepare("SELECT * FROM post_profiles WHERE id = ?");
        $profile->execute([$profileId]);
        $prof = $profile->fetch(PDO::FETCH_ASSOC);
        if (!$prof) { json_response(['ok' => false, 'error' => 'Profile not found.'], 400); exit; }

        $trainingTexts = [];
        if (!empty($trainingIds)) {
            $ph = implode(',', array_fill(0, count($trainingIds), '?'));
            $stmt = $pdo->prepare("SELECT content FROM post_training_data WHERE id IN ($ph) AND (profile_id = ? OR profile_id IS NULL)");
            $params = $trainingIds;
            $params[] = $profileId;
            $stmt->execute($params);
            $trainingTexts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        if (empty($trainingTexts)) {
            $stmt = $pdo->prepare("SELECT content FROM post_training_data WHERE profile_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmt->execute([$profileId]);
            $trainingTexts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        $settings = ai_feature_settings('posts');
        if (empty($settings['key'])) { json_response(['ok' => false, 'error' => 'AI not configured. Go to Settings.'], 400); exit; }

        $trainingStr = $trainingTexts ? "TRAINING STYLE EXAMPLES (match this writing style, never copy directly):\n" . implode("\n\n---\n\n", array_slice($trainingTexts, -5)) . "\n" : '';

        $activeTone = $selectedTone ?: $prof['tone'];
        $system = "You are a social media content strategist for Xoos Digital, Bangladesh. Generate exactly 5 unique post IDEAS based on the inputs below. Return ONLY valid JSON, no markdown, no explanation.";
        $user = "PROFILE NAME: {$prof['name']}\nPLATFORM: {$prof['platform']}\nTONE: {$activeTone}\nLANGUAGE: {$prof['language']}\nNICHE: {$prof['niche']}\nTOPIC: {$topic}\n\n{$trainingStr}\nRULES:\n- Each idea must have a strong hook: curiosity / pain_point / story / controversy / result\n- Ideas inspired by training style but 100% original\n- Relevant to profile niche and tone\n- One punchy sentence per idea\n- If language is \"mixed\" — ideas can be Bangla-English mix\n\nReturn ONLY this JSON:\n[{\"id\":1,\"idea\":\"idea text\",\"hook\":\"pain_point\"},{\"id\":2,\"idea\":\"idea text\",\"hook\":\"curiosity\"},{\"id\":3,\"idea\":\"idea text\",\"hook\":\"story\"},{\"id\":4,\"idea\":\"idea text\",\"hook\":\"controversy\"},{\"id\":5,\"idea\":\"idea text\",\"hook\":\"result\"}]";

        try {
            $response = ai_call($settings, [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ], 1000, 0.7);
            $clean = trim(preg_replace('/```json|```/', '', $response));
            $ideas = json_decode($clean, true);
            if (!$ideas) throw new RuntimeException('Invalid JSON response');
            json_response(['ok' => true, 'data' => $ideas]);
        } catch (Exception $e) {
            json_response(['ok' => false, 'error' => 'AI error: ' . $e->getMessage()], 500);
        }
        exit;
    }

    // ── Generate Posts (Claude) ──
    if ($action === 'generate_posts') {
        $topic = trim($_POST['topic'] ?? '');
        $hookType = trim($_POST['hook_type'] ?? '');
        $profileId = (int)($_POST['profile_id'] ?? 0);
        $selectedTone = trim($_POST['selected_tone'] ?? '');
        $selectedLength = (int)($_POST['selected_length'] ?? 0);
        $trainingIds = json_decode($_POST['training_ids'] ?? '[]', true);

        if (!$topic || !$profileId) { json_response(['ok' => false, 'error' => 'Select a profile and enter a topic.'], 400); exit; }

        $profile = $pdo->prepare("SELECT * FROM post_profiles WHERE id = ?");
        $profile->execute([$profileId]);
        $prof = $profile->fetch(PDO::FETCH_ASSOC);
        if (!$prof) { json_response(['ok' => false, 'error' => 'Profile not found.'], 400); exit; }

        $trainingTexts = [];
        if (!empty($trainingIds)) {
            $ph = implode(',', array_fill(0, count($trainingIds), '?'));
            $stmt = $pdo->prepare("SELECT content FROM post_training_data WHERE id IN ($ph) AND (profile_id = ? OR profile_id IS NULL)");
            $params = $trainingIds;
            $params[] = $profileId;
            $stmt->execute($params);
            $trainingTexts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        if (empty($trainingTexts)) {
            $stmt = $pdo->prepare("SELECT content FROM post_training_data WHERE profile_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmt->execute([$profileId]);
            $trainingTexts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        $tagStmt = $pdo->prepare("SELECT tag FROM post_hashtags WHERE platform=?");
        $tagStmt->execute([$prof['platform']]);
        $platformTags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
        $liTags = $platformTags; // used in prompt as platform-specific hashtags

        $settings = ai_feature_settings('posts');
        if (empty($settings['key'])) { json_response(['ok' => false, 'error' => 'AI not configured.'], 400); exit; }

        $trainingStr = $trainingTexts ? implode("\n\n---\n\n", array_slice($trainingTexts, -5)) : '';
        $tagStr = $platformTags ? implode(' ', $platformTags) : '';
        $hookLabel = $hookType ?: 'general';

        $lengthConfigs = [
            100 => [
                'minWords' => 100, 'maxWords' => 150,
                'style' => 'Short and punchy. No long storytelling. Get to the point fast.',
                'label' => 'Short',
                'structure' => "PART 1 — HOOK (1-2 lines): Bold statement or question. Stop the scroll.\nPART 2 — CORE MESSAGE (50-70 words): One key insight or value. Direct and clear.\nPART 3 — CTA (1-2 lines): One simple action. Comment, message, or share.",
            ],
            200 => [
                'minWords' => 200, 'maxWords' => 280,
                'style' => 'Balanced. Brief story or context, then value. Not too long, not too short.',
                'label' => 'Medium',
                'structure' => "PART 1 — HOOK (2-3 lines): Open with a relatable moment or bold claim.\nPART 2 — PROBLEM or CONTEXT (60-80 words): Set the scene. Make reader feel understood.\nPART 3 — INSIGHT or VALUE (80-100 words): 3-4 key points. Actionable and clear.\nPART 4 — CTA (1-2 lines): Warm and direct.",
            ],
            300 => [
                'minWords' => 300, 'maxWords' => 450,
                'style' => 'Long-form storytelling. Full emotional arc. Deep value delivery.',
                'label' => 'Long',
                'structure' => "PART 1 — THE HOOK (first 2-3 lines)\n- Open with a scene, a moment, or a bold statement\n- Must make the reader stop scrolling\n- Examples:\n  → \"৩ মাস আগে একটা call এসেছিল। ভদ্রলোক বললেন, ভাই website বানাতে কত লাগবে?\"\n  → \"I almost lost a client last year. Not because of my work. Because I didn't have a website.\"\n  → \"সত্যি কথা বলব? আমি নিজেও একসময় মনে করতাম Facebook page থাকলেই যথেষ্ট।\"\n- Never start with a generic statement like \"In today's digital world...\"\n- Never start with \"I\" on LinkedIn\n\nPART 2 — THE PROBLEM / TENSION (next 80-100 words)\n- Describe the pain point or challenge in detail\n- Make the reader feel understood\n- Use a real or realistic scenario with specific details\n- Show consequences of NOT solving this problem\n- Build emotional connection — the reader should think \"এটা তো আমার কথাই বলছে\"\n\nPART 3 — THE TURNING POINT (next 60-80 words)\n- Introduce the shift or realization\n- This is where the story changes direction\n- Can be a personal experience, a client story, or an observation\n- Should feel natural, not forced\n\nPART 4 — THE INSIGHT / VALUE (next 100-120 words)\n- Deliver the actual value — lessons, tips, or explanation\n- Break into short paragraphs or 3-5 punchy points\n- Each point should be actionable or eye-opening\n- For Facebook: use numbered or bulleted format in Bangla\n- For LinkedIn: use arrow (→) or line break format\n\nPART 5 — THE CTA (last 30-40 words)\n- End with ONE clear call to action\n- Make it feel personal and low-pressure\n- LinkedIn: end with a reflective question OR soft pitch\n- Facebook: end with direct ask — comment, message, or share",
            ],
        ];
        $pl = $selectedLength ?: (int)($prof['post_length'] ?? 200);
        if (!isset($lengthConfigs[$pl])) $pl = 200;
        $lc = $lengthConfigs[$pl];
        $activeTone = $selectedTone ?: $prof['tone'];

        // Build profile context block
        $profileContext = "PROFILE: {$prof['name']}\nTYPE: " . ($prof['type'] === 'client' ? 'Client Brand Page' : 'Personal Profile') . "\nPLATFORM: {$prof['platform']}\nTONE: {$activeTone}\nLANGUAGE: {$prof['language']}\nNICHE: {$prof['niche']}\nPOST LENGTH: minimum {$lc['minWords']} words";

        if ($prof['type'] === 'client') {
            $profileContext .= "\nBUSINESS TYPE: " . h($prof['business_type'] ?? '') . "\nTARGET AUDIENCE: " . h($prof['target_audience'] ?? '') . "\nBRAND VOICE: " . h($prof['brand_voice'] ?? '') . "\nAVOID TOPICS: " . h($prof['avoid_topics'] ?? '') . "\n\nIMPORTANT: You are writing ON BEHALF OF {$prof['name']}.\nWrite as the brand, not as a person.\nThe post should feel like it comes from {$prof['name']} directly.\nDo NOT mention personal experiences or \"I did this\" stories unless it fits a brand storytelling format.";
        }

        $system = "You are an expert social media copywriter for Xoos Digital, Bangladesh. Your writing style is: engaging, storytelling-first, human, and conversational.";

        $user = <<<PROMPT
Write TWO {$lc['label']} social media posts based on the inputs below.

{$profileContext}
TOPIC: {$topic}
HOOK TYPE: {$hookLabel}

WRITING STYLE — study this carefully and match the voice, never copy:
{$trainingStr}

PLATFORM HASHTAGS: {$tagStr}

════════════════════════════════════════
LENGTH REQUIREMENT
════════════════════════════════════════
Minimum {$lc['minWords']} words per post.
Maximum {$lc['maxWords']} words per post.
Style: {$lc['style']}
NEVER go below {$lc['minWords']} words. This is non-negotiable.

════════════════════════════════════════
POST STRUCTURE (follow exactly)
════════════════════════════════════════
{$lc['structure']}

════════════════════════════════════════
TONE GUIDE
════════════════════════════════════════
- professional      → authoritative but human. no jargon.
- semi-professional → smart friend. credible + personality.
- casual            → relaxed. short punchy sentences.
- humorous          → witty, trendy, punchy one-liners mixed in.

════════════════════════════════════════
LANGUAGE GUIDE
════════════════════════════════════════
- english → 100% English. Natural, no robotic phrasing.
- bangla  → 100% Bangla. Warm and readable.
- mixed   → Primarily Bangla. Mix English naturally like educated
            Bangladeshis write on social media.
            Use English for: tech terms, brand names, trendy words.
            NEVER force-translate natural English words.
            NEVER write robotic Bangla nobody actually speaks.

════════════════════════════════════════
PLATFORM RULES
════════════════════════════════════════
PLATFORM-SPECIFIC:
- Adjust tone and style to match the PLATFORM above
- LinkedIn: professional tone, longer paragraphs, industry insights, → for lists, thought-provoking question at end
- Facebook: warm, relatable, conversational, uses line breaks generously, ✅ or numbered lists, direct CTA
- Instagram: short punchy lines, emojis per line, hashtags in caption, conversational and trendy
- X (Twitter): very concise, single hook + insight, max 280 chars for each post, use #hashtags sparingly
- Reddit: informative, neutral tone, community-focused, avoid marketing language, value-first
- YouTube: script-style, conversational, hook in first line, call to action (subscribe/comment)
- TikTok: ultra-short, trendy, hook in first 2 lines, hashtags, emoji-heavy
- Telegram: direct, news-style, bullet points, no fluff, clear CTA
- WhatsApp: casual, personal, short, direct, one message per paragraph
- Hashtags at the end only, separated by spaces
- 1-3 emojis max, placed naturally
- Write for the specific platform's audience and culture

════════════════════════════════════════
QUALITY CHECK — verify before returning:
════════════════════════════════════════
☑ Post: minimum {$lc['minWords']} words
☑ Follows the {$pl}-word structure above
☑ Fits the platform's style and format
☑ No generic AI-sounding opening lines
☑ Language feels human, not translated
☑ Hashtags at end only, not inside body
☑ Proper line breaks for readability

════════════════════════════════════════
OUTPUT — return ONLY valid JSON, no markdown:
════════════════════════════════════════
{
  "content": "full post text\nwith \\n for line breaks"
}

If a platform is not applicable for this profile, return "" for content.
PROMPT;

        try {
            $response = ai_call($settings, [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ], 2000, 0.7);
            $clean = trim(preg_replace('/```json|```/', '', $response));
            $posts = json_decode($clean, true);
            if (!$posts) throw new RuntimeException('Invalid JSON response');

            // Save to DB
            $content = $posts['content'] ?? '';
            $platform = $prof['platform'];
            $stmt = $pdo->prepare("INSERT INTO generated_posts (platform, content, linkedin_content, facebook_content, language, status, topic, profile_id, hashtags_used, training_ids, profile_ids) VALUES (?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?, ?)");
            $stmt->execute([$platform, $content, '', '', $prof['language'], $topic, $profileId, '', json_encode($trainingIds), json_encode([$profileId])]);

            $postId = $pdo->lastInsertId();
            json_response(['ok' => true, 'data' => ['id' => $postId, 'content' => $content, 'platform' => $platform]]);
        } catch (Exception $e) {
            json_response(['ok' => false, 'error' => 'AI error: ' . $e->getMessage()], 500);
        }
        exit;
    }

    if ($action === 'clear_history') {
        $pdo->exec("DELETE FROM generated_posts");
        json_response(['ok' => true]);
        exit;
    }

    json_response(['ok' => false, 'error' => 'Unknown action'], 400);
}

// ── Load data ──
$profiles = $pdo->query("SELECT * FROM post_profiles ORDER BY created_at DESC")->fetchAll();
$trainingData = $pdo->query("SELECT t.*, p.name as profile_name FROM post_training_data t LEFT JOIN post_profiles p ON t.profile_id = p.id ORDER BY t.created_at DESC")->fetchAll();
$generatedPosts = $pdo->query("SELECT g.*, p.name as profile_name FROM generated_posts g LEFT JOIN post_profiles p ON g.profile_id = p.id ORDER BY g.created_at DESC LIMIT 50")->fetchAll();
$hashtags = $pdo->query("SELECT * FROM post_hashtags ORDER BY platform, tag")->fetchAll();

$hashtagsByPlatform = [];
foreach ($hashtags as $h) $hashtagsByPlatform[$h['platform']][] = $h;

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

// Handle restore
$restoreTopic = '';
$restoreContent = '';

if (isset($_GET['restore'])) {
    $rp = $pdo->prepare("SELECT * FROM generated_posts WHERE id = ?");
    $rp->execute([(int)$_GET['restore']]);
    $rp = $rp->fetch();
    if ($rp) {
        $_SESSION['restore_data'] = ['topic' => $rp['topic'], 'content' => $rp['content'] ?? ''];
        redirect('post-generator.php');
    }
}

$restoreTopic = '';
if (isset($_SESSION['restore_data'])) {
    $restoreTopic = $_SESSION['restore_data']['topic'] ?? '';
    $restoreContent = $_SESSION['restore_data']['content'] ?? '';
    unset($_SESSION['restore_data']);
}

$pageTitles = ['dashboard' => 'Post Generator', 'training' => 'Training Data', 'profiles' => 'Profiles', 'hashtags' => 'Hashtags', 'history' => 'Post History'];
$pageTitle = $pageTitles[$view] ?? 'Post Generator';
?>
<?php require_once __DIR__ . '/../inc/header.php'; ?>

<style>
.pg-tabs { display:flex; gap:0; border-bottom:1px solid var(--border); margin-bottom:1.5rem; flex-wrap:wrap; }
.pg-tab { padding:0.65rem 1.25rem; font-family:'Orbitron',sans-serif; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text3); cursor:pointer; border-bottom:2px solid transparent; transition:all 0.15s; background:none; border-left:none; border-right:none; border-top:none; text-decoration:none; }
.pg-tab:hover { color:var(--text); }
.pg-tab.active { color:var(--accent); border-bottom-color:var(--accent); }
.pg-tab i { margin-right:6px; font-size:0.8rem; }

/* Mode toggle */
.pg-mode-toggle { display:flex; gap:0; margin-bottom:1rem; }
.pg-mode-btn { padding:0.5rem 1rem; font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; cursor:pointer; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.03); color:var(--text3); transition:all 0.15s; font-family:'Inter',sans-serif; }
.pg-mode-btn:first-child { border-radius:8px 0 0 8px; }
.pg-mode-btn:last-child { border-radius:0 8px 8px 0; }
.pg-mode-btn.active { background:rgba(200,255,0,0.1); color:var(--accent); border-color:var(--accent); }

/* Profile chips */
.pg-profile-chips { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:1rem; }
.pg-profile-chip { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.03); cursor:pointer; transition:all 0.15s; font-size:0.82rem; color:var(--text2); }
.pg-profile-chip:hover { border-color:rgba(255,255,255,0.2); }
.pg-profile-chip.active { border-color:var(--accent); background:rgba(200,255,0,0.08); color:#fff; }
.pg-profile-chip .pc-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.pg-profile-chip .pc-platform { font-size:0.6rem; text-transform:uppercase; color:var(--text3); letter-spacing:0.06em; }

/* Idea cards */
.pg-ideas { display:none; grid-template-columns:1fr; gap:8px; margin-bottom:1rem; }
.pg-ideas.show { display:grid; }
.pg-idea-card { padding:12px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.07); background:rgba(255,255,255,0.02); cursor:pointer; transition:all 0.15s; display:flex; align-items:flex-start; gap:10px; }
.pg-idea-card:hover { border-color:rgba(255,255,255,0.18); background:rgba(255,255,255,0.04); }
.pg-idea-card.selected { border-color:var(--accent); background:rgba(200,255,0,0.06); }
.pg-idea-card .idea-hook { font-size:0.6rem; text-transform:uppercase; letter-spacing:0.08em; padding:2px 8px; border-radius:4px; background:rgba(255,255,255,0.06); color:var(--text3); white-space:nowrap; flex-shrink:0; }
.pg-idea-card .idea-text { font-size:0.85rem; color:var(--text); line-height:1.5; flex:1; }
.pg-idea-card .idea-copy { background:none; border:none; color:var(--text3); cursor:pointer; padding:4px; flex-shrink:0; font-size:0.9rem; }
.pg-idea-card .idea-copy:hover { color:var(--accent); }

/* Post boxes */
.post-card { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius-md); padding:1.25rem; margin-bottom:1rem; }
.post-card .post-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75rem; flex-wrap:wrap; gap:8px; }
.post-card .post-platform { font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; display:flex; align-items:center; gap:6px; }
.post-card .post-content { font-size:0.85rem; line-height:1.6; color:var(--text2); white-space:pre-wrap; word-break:break-word; }
.post-card .post-content textarea { width:100%; min-height:120px; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:10px; color:var(--text); font-size:0.82rem; line-height:1.6; font-family:'Inter',sans-serif; resize:vertical; outline:none; }
.post-card .post-content textarea:focus { border-color:var(--accent); }
.post-card .post-meta { display:flex; align-items:center; gap:1rem; margin-top:0.75rem; font-size:0.72rem; color:var(--text3); flex-wrap:wrap; }
.post-card .post-actions { display:flex; gap:6px; flex-wrap:wrap; }
.generate-result { display:none; }
.generate-result.show { display:block; }
.copy-btn { cursor:pointer; }
.copy-btn:hover { color:var(--accent); }

/* Hashtag pills */
.pg-hashtag-area { margin-bottom:1.5rem; }
.pg-hashtag-area h4 { font-family:'Orbitron',sans-serif; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--accent); margin-bottom:0.75rem; }
.pg-hashtag-pool { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:8px; }
.pg-hashtag-pill { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:6px; font-size:0.78rem; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.03); color:var(--text2); }
.pg-hashtag-pill .ht-del { background:none; border:none; color:var(--text3); cursor:pointer; padding:0; font-size:0.8rem; line-height:1; }
.pg-hashtag-pill .ht-del:hover { color:#ff4757; }
.pg-hashtag-add { display:flex; gap:6px; align-items:center; }
.pg-hashtag-add input { flex:1; max-width:200px; padding:6px 10px; border-radius:6px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); color:#fff; font-size:0.8rem; outline:none; }
.pg-hashtag-add input:focus { border-color:var(--accent); }
.pg-hashtag-add button { padding:6px 12px; border-radius:6px; border:none; background:var(--accent); color:#000; font-weight:600; font-size:0.78rem; cursor:pointer; }

/* Filter pill for training */
.pg-filter-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:1rem; align-items:center; }
.pg-filter-chip { padding:4px 12px; border-radius:8px; font-size:0.72rem; cursor:pointer; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.03); color:var(--text3); transition:all 0.12s; }
.pg-filter-chip:hover { border-color:rgba(255,255,255,0.2); }
.pg-filter-chip.active { border-color:var(--accent); color:var(--accent); background:rgba(200,255,0,0.08); }

/* History restore */
.btn-restore { background:none; border:1px solid var(--accent); color:var(--accent); padding:4px 10px; border-radius:6px; font-size:0.72rem; cursor:pointer; transition:all 0.12s; }
.btn-restore:hover { background:var(--accent); color:#000; }

/* Length option radio pills */
.length-options { display:flex; gap:8px; }
.length-options input { display:none; }
.length-options label { flex:1; text-align:center; padding:8px 12px; border-radius:8px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.03); cursor:pointer; transition:all 0.12s; }
.length-options label span { display:block; font-size:0.82rem; font-weight:600; color:var(--text2); }
.length-options label small { display:block; font-size:0.65rem; color:var(--text3); margin-top:2px; }
.length-options input:checked + label { border-color:var(--accent); background:rgba(200,255,0,0.08); }
.length-options input:checked + label span { color:var(--accent); }

/* Profile chip length badge */
.pc-length { font-size:0.6rem; color:var(--text3); background:rgba(255,255,255,0.05); padding:1px 6px; border-radius:4px; margin-left:auto; white-space:nowrap; }

/* Type toggle pills */
.type-toggle { display:flex; gap:8px; margin-bottom:0.5rem; }
.type-toggle input { display:none; }
.type-toggle label { flex:1; text-align:center; padding:10px 16px; border-radius:8px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.03); cursor:pointer; transition:all 0.12s; font-size:0.9rem; font-weight:600; color:var(--text2); }
.type-toggle input:checked + label { border-color:var(--accent); background:rgba(200,255,0,0.08); color:var(--accent); }

/* Tone pills */
.tone-pills { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:0.75rem; }
.tone-pill { padding:6px 14px; border-radius:8px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.03); color:var(--text2); cursor:pointer; font-size:0.78rem; font-weight:500; transition:all 0.12s; font-family:'Inter',sans-serif; }
.tone-pill:hover { border-color:rgba(255,255,255,0.2); }
.tone-pill.active { border-color:var(--accent); background:rgba(200,255,0,0.08); color:var(--accent); }

/* Client fields section */
.client-fields { padding:1rem; border-radius:8px; border:1px dashed rgba(255,255,255,0.12); background:rgba(255,255,255,0.02); margin-bottom:1rem; }

/* Profile dropdown in dashboard */
#profileSelect { max-width:100%; }

@media (max-width:480px) {
  .pg-mode-btn { padding:0.4rem 0.7rem; font-size:0.65rem; }
  .pg-profile-chip { padding:6px 10px; font-size:0.76rem; }
}
</style>

<?php if ($flash_msg): ?>
<div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:<?= $flash_type==='success'?'var(--green-bg)':'var(--red-bg)' ?>;color:<?= $flash_type==='success'?'var(--green)':'var(--red)' ?>"><?= h($flash_msg) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title"><?= h($pageTitle) ?></h1>
</div>

<div class="pg-tabs">
    <a href="post-generator.php" class="pg-tab <?= $view==='dashboard'?'active':'' ?>"><i class="ti ti-sparkles"></i> Dashboard</a>
    <a href="post-generator.php?view=training" class="pg-tab <?= $view==='training'?'active':'' ?>"><i class="ti ti-database"></i> Training</a>
    <a href="post-generator.php?view=profiles" class="pg-tab <?= $view==='profiles'?'active':'' ?>"><i class="ti ti-users"></i> Profiles</a>
    <a href="post-generator.php?view=hashtags" class="pg-tab <?= $view==='hashtags'?'active':'' ?>"><i class="ti ti-hash"></i> Hashtags</a>
    <a href="post-generator.php?view=history" class="pg-tab <?= $view==='history'?'active':'' ?>"><i class="ti ti-clock"></i> History</a>
</div>

<?php if ($view === 'dashboard'): ?>

<div class="v3-gen-area">
    <div class="ga-label"><i class="ti ti-sparkles"></i> Generate Posts</div>

    <!-- Mode toggle -->
    <div class="pg-mode-toggle">
        <button class="pg-mode-btn active" id="modeIdeas" onclick="setMode('ideas')"><i class="ti ti-bulb"></i> Ideas First</button>
        <button class="pg-mode-btn" id="modeDirect" onclick="setMode('direct')"><i class="ti ti-send"></i> Direct Post</button>
    </div>

    <!-- Profile dropdown -->
    <label style="font-size:0.72rem;font-weight:600;color:var(--text3);margin-bottom:6px;display:block">Select Profile</label>
    <select class="form-control" id="profileSelect" onchange="selectProfile(this)" style="margin-bottom:0.75rem">
        <optgroup label="👤 Personal">
            <?php $i = 0; foreach ($personalProfiles as $pr): ?>
            <option value="<?= $pr['id'] ?>" data-length="<?= (int)($pr['post_length'] ?? 200) ?>" data-tone="<?= h($pr['tone']) ?>" <?= $i===0?'selected':'' ?>><?= h($pr['name']) ?> · <?= $pr['platform'] ?> · <?= $pr['language'] ?></option>
            <?php $i++; endforeach; ?>
        </optgroup>
        <?php if (count($clientProfiles)): ?>
        <optgroup label="🏪 Client">
            <?php foreach ($clientProfiles as $pr): ?>
            <option value="<?= $pr['id'] ?>" data-length="<?= (int)($pr['post_length'] ?? 200) ?>" data-tone="<?= h($pr['tone']) ?>"><?= h($pr['name']) ?> · <?= h($pr['business_type']) ?> · <?= $pr['platform'] ?></option>
            <?php endforeach; ?>
        </optgroup>
        <?php endif; ?>
    </select>

    <!-- Tone pills -->
    <label style="font-size:0.72rem;font-weight:600;color:var(--text3);margin-bottom:6px;display:block">Tone <span class="text-muted">(override per generation)</span></label>
    <div class="tone-pills" id="tonePills">
        <button class="tone-pill active" data-tone="semi-professional" onclick="selectTone(this)">Semi-Pro</button>
        <button class="tone-pill" data-tone="casual" onclick="selectTone(this)">Casual</button>
        <button class="tone-pill" data-tone="professional" onclick="selectTone(this)">Pro</button>
        <button class="tone-pill" data-tone="humorous" onclick="selectTone(this)">😄 Funny</button>
    </div>

    <!-- Length pills -->
    <label style="font-size:0.72rem;font-weight:600;color:var(--text3);margin-bottom:6px;display:block">Post Length</label>
    <div class="tone-pills" id="lengthPills" style="margin-bottom:0.75rem">
        <button class="tone-pill" data-length="100" onclick="selectLength(this)">100w</button>
        <button class="tone-pill active" data-length="200" onclick="selectLength(this)">200w</button>
        <button class="tone-pill" data-length="300" onclick="selectLength(this)">300w</button>
    </div>

    <form id="genForm" onsubmit="return handleGenerate(event)">
        <div class="form-group">
            <label>What's the topic?</label>
            <textarea class="form-control" name="topic" id="gen-topic" rows="3" placeholder="e.g. Digital branding for small businesses in Bangladesh..." required><?= h($restoreTopic) ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-pulse" id="gen-btn"><i class="ti ti-sparkles"></i> <span id="genBtnLabel">Generate Posts</span></button>
            <button type="button" class="btn btn-secondary" id="genToPostsBtn" style="display:none" onclick="generatePostsFromIdeas()"><i class="ti ti-send"></i> Generate Posts from Selected Idea</button>
        </div>
    </form>
</div>

<!-- Idea cards -->
<div class="pg-ideas" id="pgIdeas">
    <label style="font-size:0.72rem;font-weight:600;color:var(--text3);margin-bottom:4px;display:block">Select an idea to generate posts:</label>
    <div id="pgIdeaList"></div>
</div>

<!-- Result posts -->
<div id="gen-result" class="generate-result">
    <div class="post-card" id="genResultBox" style="display:none">
        <div class="post-header">
            <span class="post-platform" id="genResultLabel" style="color:#0a66c2"><i class="ti ti-brand-linkedin"></i> <span id="genResultPlatform">Post</span></span>
            <div class="post-actions">
                <button class="btn btn-secondary btn-sm" onclick="copyPostText()"><i class="ti ti-copy"></i></button>
                <button class="btn btn-success btn-sm" onclick="publishGenPost()"><i class="ti ti-check"></i> Publish</button>
            </div>
        </div>
        <div class="post-content"><textarea id="genResultContent" rows="5"></textarea></div>
        <div class="post-meta"><span id="genResultMeta">Hashtags will be appended on publish</span></div>
        <input type="hidden" id="gen-result-id" value="0">
        <input type="hidden" id="gen-result-platform" value="">
    </div>
</div>

<div style="margin-top:2rem">
    <div class="flex" style="justify-content:space-between;margin-bottom:1rem">
        <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3)"><i class="ti ti-clock"></i> Latest</h3>
    </div>
    <?php if (count($generatedPosts)): $latest = $generatedPosts[0]; ?>
    <?php $lp = $latest['platform'] ?? 'linkedin'; $lic = plat($lp, 'icon'); $lc = plat($lp, 'color') ?: '#0a66c2'; ?>
    <div class="post-card" style="border-left:3px solid <?= h($lc) ?>">
        <div class="post-header">
            <span class="post-platform" style="color:<?= h($lc) ?>">
                <i class="ti <?= h($lic) ?>"></i> <?= h($latest['topic'] ?? '') ?>
                <span class="text-muted" style="font-weight:400;text-transform:none;letter-spacing:0;margin-left:8px">via <?= h($latest['profile_name'] ?? '') ?></span>
            </span>
            <div class="post-actions">
                <span class="status-badge <?= $latest['status']==='published'?'status-published':'status-draft' ?>" style="font-size:0.6rem;padding:2px 8px"><?= $latest['status'] ?></span>
                <button class="btn btn-secondary btn-sm" onclick="restorePost(<?= $latest['id'] ?>)" style="padding:3px 8px"><i class="ti ti-history"></i></button>
            </div>
        </div>
        <div class="post-content"><?= h(mb_substr($latest['content'] ?? ($latest['linkedin_content'] ?: $latest['facebook_content'] ?? ''), 0, 200)) ?>...</div>
        <div class="post-meta"><span><?= date('M j, Y h:i A', strtotime($latest['created_at'])) ?></span></div>
    </div>
    <?php else: ?>
    <div class="empty-illustration"><div class="empty-icon"><i class="ti ti-file-text"></i></div><h4>No posts yet</h4><p>Generate your first post above.</p></div>
    <?php endif; ?>
</div>

<?php elseif ($view === 'training'): ?>

<div class="pg-filter-bar">
    <span class="pg-filter-chip active" data-id="0" onclick="filterTraining(this,0)">All</span>
    <?php foreach ($profiles as $pr): ?>
    <span class="pg-filter-chip" data-id="<?= $pr['id'] ?>" onclick="filterTraining(this,<?= $pr['id'] ?>)"><?= h($pr['name']) ?></span>
    <?php endforeach; ?>
</div>

<div class="card" style="margin-bottom:1.5rem">
    <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:1rem"><i class="ti ti-plus"></i> Add Training Data</h3>
    <form method="post" action="post-generator.php">
        <input type="hidden" name="action" value="train_add">
        <div class="form-row">
            <div class="form-group">
                <label>Profile</label>
                <select class="form-control" name="profile_id">
                    <option value="">General (all profiles)</option>
                    <?php foreach ($profiles as $pr): ?>
                    <option value="<?= $pr['id'] ?>"><?= h($pr['name']) ?> (<?= h($pr['platform']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select class="form-control" name="type">
                    <option value="topic">Topic / Idea</option>
                    <option value="style">Writing Style</option>
                    <option value="brand">Brand Info</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Content</label>
            <textarea class="form-control" name="content" rows="5" placeholder="Paste articles, notes, or posts to train the AI..." required></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table id="trainingTable">
            <thead><tr><th>Profile</th><th>Type</th><th>Content Preview</th><th>Date</th><th style="text-align:right">Action</th></tr></thead>
            <tbody>
                <?php foreach ($trainingData as $td): ?>
                <tr data-profile="<?= $td['profile_id'] ?: 0 ?>">
                    <td><span style="font-size:0.72rem;color:var(--text3)"><?= h($td['profile_name'] ?? 'General') ?></span></td>
                    <td><span class="status-badge <?= $td['type']==='topic'?'status-draft':($td['type']==='style'?'status-published':'') ?>" style="font-size:0.6rem;text-transform:uppercase"><?= $td['type'] ?></span></td>
                    <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($td['content']) ?></td>
                    <td class="text-muted"><?= date('M j, Y', strtotime($td['created_at'])) ?></td>
                    <td style="text-align:right"><button class="btn btn-danger btn-sm" onclick="deleteTraining(<?= $td['id'] ?>)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($view === 'profiles'): ?>

<div class="card" style="margin-bottom:1.5rem">
    <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:1rem"><i class="ti ti-plus"></i> Add Profile</h3>
    <form method="post" action="post-generator.php">
        <input type="hidden" name="action" value="profile_add">

        <div class="form-group">
            <label>Profile Type</label>
            <div class="type-toggle">
                <input type="radio" name="type" value="personal" id="addTypePersonal" checked onchange="toggleClientFields('add')">
                <label for="addTypePersonal">👤 Personal</label>
                <input type="radio" name="type" value="client" id="addTypeClient" onchange="toggleClientFields('add')">
                <label for="addTypeClient">🏪 Client</label>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Profile Name</label>
                <input class="form-control" name="name" placeholder="e.g. Elon Musk" required>
            </div>
            <div class="form-group">
                <label>Platform</label>
                <select class="form-control" name="platform" required>
                    <?php foreach ($platforms as $pk => $pv): ?>
                    <option value="<?= $pk ?>"><?= h($pv['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="client-fields" id="addClientFields" style="display:none">
            <div class="form-group">
                <label>Business Type</label>
                <input class="form-control" name="business_type" placeholder="e.g. Organic Food Store, EdTech Platform">
            </div>
            <div class="form-group">
                <label>Target Audience</label>
                <textarea class="form-control" name="target_audience" rows="2" placeholder="e.g. health-conscious urban Bangladeshis, mothers, age 25-45"></textarea>
            </div>
            <div class="form-group">
                <label>Brand Voice</label>
                <textarea class="form-control" name="brand_voice" rows="2" placeholder="e.g. warm, friendly, trustworthy, natural, community-focused"></textarea>
            </div>
            <div class="form-group">
                <label>Avoid Topics</label>
                <textarea class="form-control" name="avoid_topics" rows="2" placeholder="e.g. politics, religion, competitors"></textarea>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Language</label>
                <select class="form-control" name="language">
                    <option value="english">English</option>
                    <option value="bangla">Bangla</option>
                    <option value="mixed">Mixed (Banglish)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Default Tone</label>
                <select class="form-control" name="tone">
                    <option value="professional">Professional</option>
                    <option value="semi-professional" selected>Semi-Professional</option>
                    <option value="casual">Casual</option>
                    <option value="humorous">Humorous</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Niche <span class="text-muted">(comma-separated topics)</span></label>
            <input class="form-control" name="niche" placeholder="e.g. Digital marketing, freelancing, agency growth" value="Digital marketing, agency, freelancing">
        </div>
        <div class="form-group">
            <label>Post Length</label>
            <div class="length-options">
                <input type="radio" name="post_length" value="100" id="addLen100">
                <label for="addLen100"><span>Short</span><small>~100 words</small></label>
                <input type="radio" name="post_length" value="200" id="addLen200" checked>
                <label for="addLen200"><span>Medium</span><small>~200 words</small></label>
                <input type="radio" name="post_length" value="300" id="addLen300">
                <label for="addLen300"><span>Long</span><small>~300 words</small></label>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Profile URL</label>
                <input class="form-control" name="profile_url" placeholder="https://linkedin.com/in/...">
            </div>
            <div class="form-group">
                <label>Color</label>
                <input class="form-control" name="color" type="color" value="#c8f135" style="height:38px;padding:4px">
            </div>
        </div>
        <div class="form-group">
            <label>Notes <span class="text-muted">(style notes)</span></label>
            <textarea class="form-control" name="notes" rows="2" placeholder="e.g. Posts are conversational, uses storytelling..."></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save Profile</button>
        </div>
    </form>
</div>

<?php
$personalProfiles = []; $clientProfiles = [];
foreach ($profiles as $p) {
    if (($p['type'] ?? 'personal') === 'client') $clientProfiles[] = $p;
    else $personalProfiles[] = $p;
}
?>

<div class="card" style="margin-bottom:1.5rem">
    <h4 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:0.75rem">👤 Personal Profiles</h4>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Platform</th><th>Language</th><th>Tone</th><th>Length</th><th style="text-align:right">Action</th></tr></thead>
            <tbody>
                <?php foreach ($personalProfiles as $pr): ?>
                <tr>
                    <td><strong style="color:var(--text)"><?= h($pr['name']) ?></strong></td>
                    <td><span style="font-size:0.6rem;color:<?= h(plat($pr['platform'],'color')) ?>"><i class="ti <?= h(plat($pr['platform'],'icon')) ?>"></i> <?= h(plat($pr['platform'],'label') ?: $pr['platform']) ?></span></td>
                    <td class="text-muted"><?= $pr['language'] ?></td>
                    <td class="text-muted"><?= $pr['tone'] ?></td>
                    <td class="text-muted"><?= (int)($pr['post_length'] ?? 200) ?>w</td>
                    <td style="text-align:right"><button class="btn btn-secondary btn-sm" onclick="editProfile(<?= $pr['id'] ?>)" style="padding:3px 8px;margin-right:4px"><i class="ti ti-pencil"></i></button><button class="btn btn-danger btn-sm" onclick="deleteProfile(<?= $pr['id'] ?>)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (count($clientProfiles)): ?>
<div class="card">
    <h4 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:0.75rem">🏪 Client Profiles</h4>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Business</th><th>Platform</th><th>Language</th><th>Tone</th><th style="text-align:right">Action</th></tr></thead>
            <tbody>
                <?php foreach ($clientProfiles as $pr): ?>
                <tr>
                    <td><strong style="color:var(--text)"><?= h($pr['name']) ?></strong></td>
                    <td class="text-muted" style="font-size:0.78rem"><?= h($pr['business_type'] ?: '-') ?></td>
                    <td><span style="font-size:0.6rem;color:<?= h(plat($pr['platform'],'color')) ?>"><i class="ti <?= h(plat($pr['platform'],'icon')) ?>"></i> <?= h(plat($pr['platform'],'label') ?: $pr['platform']) ?></span></td>
                    <td class="text-muted"><?= $pr['language'] ?></td>
                    <td class="text-muted"><?= $pr['tone'] ?></td>
                    <td style="text-align:right"><button class="btn btn-secondary btn-sm" onclick="editProfile(<?= $pr['id'] ?>)" style="padding:3px 8px;margin-right:4px"><i class="ti ti-pencil"></i></button><button class="btn btn-danger btn-sm" onclick="deleteProfile(<?= $pr['id'] ?>)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="modal-overlay" id="profileModal">
    <div class="modal modal-wide">
        <h3 class="modal-title">Edit Profile</h3>
        <form onsubmit="return saveProfile(event)">
            <input type="hidden" id="edit-profile-id" value="0">

            <div class="form-group">
                <label>Profile Type</label>
                <div class="type-toggle">
                    <input type="radio" name="edit-type" value="personal" id="editTypePersonal" onchange="toggleClientFields('edit')">
                    <label for="editTypePersonal">👤 Personal</label>
                    <input type="radio" name="edit-type" value="client" id="editTypeClient" onchange="toggleClientFields('edit')">
                    <label for="editTypeClient">🏪 Client</label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Profile Name</label>
                    <input class="form-control" id="edit-profile-name" required>
                </div>
                <div class="form-group">
                    <label>Platform</label>
                    <select class="form-control" id="edit-profile-platform" required>
                        <?php foreach ($platforms as $pk => $pv): ?>
                        <option value="<?= $pk ?>"><?= h($pv['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="client-fields" id="editClientFields" style="display:none">
                <div class="form-group">
                    <label>Business Type</label>
                    <input class="form-control" id="edit-business-type" placeholder="e.g. Organic Food Store, EdTech Platform">
                </div>
                <div class="form-group">
                    <label>Target Audience</label>
                    <textarea class="form-control" id="edit-target-audience" rows="2" placeholder="e.g. health-conscious urban Bangladeshis, mothers, age 25-45"></textarea>
                </div>
                <div class="form-group">
                    <label>Brand Voice</label>
                    <textarea class="form-control" id="edit-brand-voice" rows="2" placeholder="e.g. warm, friendly, trustworthy, natural, community-focused"></textarea>
                </div>
                <div class="form-group">
                    <label>Avoid Topics</label>
                    <textarea class="form-control" id="edit-avoid-topics" rows="2" placeholder="e.g. politics, religion, competitors"></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Language</label>
                    <select class="form-control" id="edit-profile-language">
                        <option value="english">English</option>
                        <option value="bangla">Bangla</option>
                        <option value="mixed">Mixed (Banglish)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Default Tone</label>
                    <select class="form-control" id="edit-profile-tone">
                        <option value="professional">Professional</option>
                        <option value="semi-professional">Semi-Professional</option>
                        <option value="casual">Casual</option>
                        <option value="humorous">Humorous</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Niche <span class="text-muted">(comma-separated topics)</span></label>
                <input class="form-control" id="edit-profile-niche" placeholder="e.g. Digital marketing, freelancing, agency growth">
            </div>
            <div class="form-group">
                <label>Post Length</label>
                <div class="length-options">
                    <input type="radio" name="edit-length" value="100" id="editLen100">
                    <label for="editLen100"><span>Short</span><small>~100 words</small></label>
                    <input type="radio" name="edit-length" value="200" id="editLen200" checked>
                    <label for="editLen200"><span>Medium</span><small>~200 words</small></label>
                    <input type="radio" name="edit-length" value="300" id="editLen300">
                    <label for="editLen300"><span>Long</span><small>~300 words</small></label>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Profile URL</label>
                    <input class="form-control" id="edit-profile-url" placeholder="https://linkedin.com/in/...">
                </div>
                <div class="form-group">
                    <label>Color</label>
                    <input class="form-control" id="edit-profile-color" type="color" style="height:38px;padding:4px">
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea class="form-control" id="edit-profile-notes" rows="2" placeholder="e.g. Posts are conversational, uses storytelling..."></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
                <button type="button" class="btn btn-secondary" onclick="closeProfileModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($view === 'hashtags'): ?>

<?php foreach ($platforms as $pk => $pv): ?>
<div class="pg-hashtag-area">
    <h4 style="color:<?= h($pv['color']) ?>"><i class="ti <?= h($pv['icon']) ?>"></i> <?= h($pv['label']) ?> Hashtags</h4>
    <div class="pg-hashtag-pool" id="<?= $pk ?>HashPool">
        <?php foreach ($hashtagsByPlatform[$pk] ?? [] as $h): ?>
        <span class="pg-hashtag-pill"><?= h($h['tag']) ?> <button class="ht-del" onclick="deleteHashtag(<?= $h['id'] ?>,this)">✕</button></span>
        <?php endforeach; ?>
    </div>
    <div class="pg-hashtag-add">
        <input type="text" id="<?= $pk ?>HashInput" placeholder="#AddTag" onkeydown="if(event.key==='Enter'){event.preventDefault();addHashtag('<?= $pk ?>')}">
        <button onclick="addHashtag('<?= $pk ?>')">Add</button>
    </div>
</div>
<?php endforeach; ?>

<?php elseif ($view === 'history'): ?>

<?php if (count($generatedPosts)): ?>
<div style="margin-bottom:1rem">
    <button class="btn btn-danger btn-sm" onclick="if(confirm('Clear all history?')){var fd=new FormData();fd.append('action','clear_history');fetch('post-generator.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(j){if(j.ok)location.reload()})}" style="float:right"><i class="ti ti-trash"></i> Clear All</button>
</div>
<?php $grouped = []; foreach ($generatedPosts as $gp) { $k = substr($gp['created_at'], 0, 10); $grouped[$k][] = $gp; } ?>
<?php foreach ($grouped as $date => $posts): ?>
<div style="font-size:0.65rem;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.5rem;margin-top:1rem"><?= h(date('M j, Y', strtotime($date))) ?></div>
<?php foreach ($posts as $gp): ?>
<?php $hpl = $gp['platform'] ?? 'linkedin'; $hic = plat($hpl, 'icon'); $hcol = plat($hpl, 'color') ?: '#0a66c2'; ?>
<div class="post-card" id="history-<?= $gp['id'] ?>" style="border-left:3px solid <?= h($hcol) ?>">
    <div class="post-header">
        <span class="post-platform" style="color:<?= h($hcol) ?>">
            <i class="ti <?= h($hic) ?>"></i>
            <?= h($gp['topic']) ?>
            <?php if ($gp['profile_name']): ?><span class="text-muted" style="font-weight:400;text-transform:none;letter-spacing:0;margin-left:8px">via <?= h($gp['profile_name']) ?></span><?php endif; ?>
        </span>
        <div class="post-actions">
            <span class="status-badge <?= $gp['status']==='published'?'status-published':'status-draft' ?>" style="font-size:0.6rem;padding:2px 8px;margin-right:4px"><?= $gp['status'] ?></span>
            <button class="btn-restore" onclick="restorePost(<?= $gp['id'] ?>)"><i class="ti ti-history"></i> Restore</button>
            <button class="btn btn-secondary btn-sm" onclick="copyText(this,<?= $gp['id'] ?>)" style="padding:3px 8px"><i class="ti ti-copy"></i></button>
            <button class="btn btn-secondary btn-sm" onclick="editPost(<?= $gp['id'] ?>)" style="padding:3px 8px"><i class="ti ti-pencil"></i></button>
            <button class="btn btn-danger btn-sm" onclick="deletePost(<?= $gp['id'] ?>)" style="padding:3px 8px"><i class="ti ti-trash"></i></button>
        </div>
    </div>
    <div class="post-content" id="post-content-<?= $gp['id'] ?>"><?= h($gp['content'] ?? $gp['linkedin_content'] ?: $gp['facebook_content'] ?? '') ?></div>
    <div class="post-meta">
        <span><?= date('h:i A', strtotime($gp['created_at'])) ?></span>
        <span>🌐 <?= $gp['language'] ?? 'en' ?></span>
    </div>
</div>
<?php endforeach; endforeach; ?>
<?php else: ?>
<div class="empty-illustration"><div class="empty-icon"><i class="ti ti-clock"></i></div><h4>No history yet</h4><p>Generated posts will appear here.</p></div>
<?php endif; ?>

<?php endif; ?>

<div class="modal-overlay" id="editModal">
    <div class="modal modal-wide">
        <h3 class="modal-title">Edit Post</h3>
        <form onsubmit="return saveEditPost(event)">
            <input type="hidden" id="edit-post-id" value="0">
            <div class="form-group">
                <label>Post Content</label>
                <textarea class="form-control" id="edit-post-content" rows="10" style="font-size:0.82rem;line-height:1.6;white-space:pre-wrap"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
var currentMode = 'ideas';
var selectedProfileId = parseInt(document.getElementById('profileSelect').value || 0);
var selectedIdea = null;
var currentPostId = 0;

// Sync tone/length pills to first profile default on load
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('profileSelect');
    if (sel) {
        var first = sel.options[sel.selectedIndex];
        if (first) {
            var tone = first.dataset.tone || 'semi-professional';
            var len = parseInt(first.dataset.length || '200');
            document.querySelectorAll('#tonePills .tone-pill').forEach(function(b) { b.classList.toggle('active', b.dataset.tone === tone); });
            document.querySelectorAll('#lengthPills .tone-pill').forEach(function(b) { b.classList.toggle('active', parseInt(b.dataset.length) === len); });
        }
    }
});

function toggleClientFields(prefix) {
    var isClient = document.getElementById(prefix + 'TypeClient').checked;
    document.getElementById(prefix + 'ClientFields').style.display = isClient ? 'block' : 'none';
}

function setMode(mode) {
    currentMode = mode;
    document.getElementById('modeIdeas').classList.toggle('active', mode === 'ideas');
    document.getElementById('modeDirect').classList.toggle('active', mode === 'direct');
    document.getElementById('genBtnLabel').textContent = mode === 'ideas' ? 'Generate Ideas' : 'Generate Posts';
    document.getElementById('pgIdeas').classList.remove('show');
    document.getElementById('genToPostsBtn').style.display = 'none';
    selectedIdea = null;
    document.querySelectorAll('.pg-idea-card').forEach(function(c) { c.classList.remove('selected'); });
}

function selectProfile(el) {
    selectedProfileId = parseInt(el.value);
    var opt = el.options[el.selectedIndex];
    var tone = opt.dataset.tone || 'semi-professional';
    var len = parseInt(opt.dataset.length || '200');
    // Sync tone pills to profile default
    document.querySelectorAll('#tonePills .tone-pill').forEach(function(b) { b.classList.toggle('active', b.dataset.tone === tone); });
    // Sync length pills to profile default
    document.querySelectorAll('#lengthPills .tone-pill').forEach(function(b) { b.classList.toggle('active', parseInt(b.dataset.length) === len); });
    document.getElementById('pgIdeas').classList.remove('show');
    document.getElementById('genToPostsBtn').style.display = 'none';
    selectedIdea = null;
}

function selectTone(el) {
    document.querySelectorAll('#tonePills .tone-pill').forEach(function(b) { b.classList.remove('active'); });
    el.classList.add('active');
}

function selectLength(el) {
    document.querySelectorAll('#lengthPills .tone-pill').forEach(function(b) { b.classList.remove('active'); });
    el.classList.add('active');
}

function getActiveTone() {
    var active = document.querySelector('#tonePills .tone-pill.active');
    return active ? active.dataset.tone : 'semi-professional';
}

function getActiveLength() {
    var active = document.querySelector('#lengthPills .tone-pill.active');
    return active ? parseInt(active.dataset.length) : 200;
}

function handleGenerate(e) {
    e.preventDefault();
    var topic = document.getElementById('gen-topic').value.trim();
    if (!topic) { showToast('Enter a topic', 'error'); return; }
    if (currentMode === 'ideas') return generateIdeas(topic);
    return generatePostsDirect(topic);
}

function generateIdeas(topic) {
    var btn = document.getElementById('gen-btn');
    btn.innerHTML = '<span class="ai-spinner"></span> Generating ideas...';
    btn.disabled = true;
    document.getElementById('pgIdeas').classList.remove('show');
    document.getElementById('genToPostsBtn').style.display = 'none';

    var fd = new FormData();
    fd.append('action', 'generate_ideas');
    fd.append('topic', topic);
    fd.append('profile_id', selectedProfileId);
    fd.append('selected_tone', getActiveTone());
    fd.append('selected_length', getActiveLength());
    fd.append('training_ids', '[]');

    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        btn.innerHTML = '<i class="ti ti-sparkles"></i> <span id="genBtnLabel">Generate Ideas</span>';
        btn.disabled = false;
        if (!j.ok) { showToast(j.error || 'Failed', 'error'); return; }
        showIdeas(j.data);
    })
    .catch(function() {
        btn.innerHTML = '<i class="ti ti-sparkles"></i> <span id="genBtnLabel">Generate Ideas</span>';
        btn.disabled = false;
        showToast('Request failed', 'error');
    });
    return false;
}

function showIdeas(ideas) {
    var list = document.getElementById('pgIdeaList');
    list.innerHTML = '';
    ideas.forEach(function(idea) {
        var card = document.createElement('div');
        card.className = 'pg-idea-card';
        card.dataset.idea = JSON.stringify(idea);
        card.onclick = function() { selectIdeaCard(this); };
        card.innerHTML = '<span class="idea-hook">' + idea.hook + '</span><span class="idea-text">' + escHtml(idea.idea) + '</span><button class="idea-copy" onclick="event.stopPropagation();copyTextStr(\'' + escHtml(idea.idea.replace(/'/g, "\\'")) + '\')"><i class="ti ti-copy"></i></button>';
        list.appendChild(card);
    });
    document.getElementById('pgIdeas').classList.add('show');
}

function selectIdeaCard(el) {
    document.querySelectorAll('.pg-idea-card').forEach(function(c) { c.classList.remove('selected'); });
    el.classList.add('selected');
    selectedIdea = JSON.parse(el.dataset.idea);
    document.getElementById('genToPostsBtn').style.display = 'inline-flex';
}

function generatePostsFromIdeas() {
    if (!selectedIdea) { showToast('Select an idea first', 'error'); return; }
    var topic = document.getElementById('gen-topic').value.trim() + ': ' + selectedIdea.idea;
    var btn = document.getElementById('genToPostsBtn');
    btn.innerHTML = '<span class="ai-spinner"></span> Generating...';
    btn.disabled = true;
    callGeneratePosts(topic, selectedIdea.hook, function() {
        btn.innerHTML = '<i class="ti ti-send"></i> Generate Posts from Selected Idea';
        btn.disabled = false;
    });
}

function generatePostsDirect(topic) {
    var btn = document.getElementById('gen-btn');
    btn.innerHTML = '<span class="ai-spinner"></span> Generating...';
    btn.disabled = true;
    callGeneratePosts(topic, '', function() {
        btn.innerHTML = '<i class="ti ti-sparkles"></i> <span id="genBtnLabel">Generate Posts</span>';
        btn.disabled = false;
    });
}

function callGeneratePosts(topic, hookType, done) {
    var fd = new FormData();
    fd.append('action', 'generate_posts');
    fd.append('topic', topic);
    fd.append('hook_type', hookType);
    fd.append('profile_id', selectedProfileId);
    fd.append('selected_tone', getActiveTone());
    fd.append('training_ids', '[]');

    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (!j.ok) { showToast(j.error || 'Generation failed', 'error'); done(); return; }
        showPostResults(j.data);
        showToast('Posts generated!');
        done();
    })
    .catch(function() {
        showToast('Request failed', 'error');
        done();
    });
}

function showPostResults(data) {
    document.getElementById('gen-result').classList.add('show');
    currentPostId = data.id;
    var platform = data.platform || 'linkedin';

    var box = document.getElementById('genResultBox');
    var label = document.getElementById('genResultLabel');
    var nameEl = document.getElementById('genResultPlatform');

    box.style.display = 'block';
    box.style.borderLeftColor = platformColors[platform] || '#0a66c2';
    label.style.color = platformColors[platform] || '#0a66c2';
    label.innerHTML = (platformIcons[platform] || 'ti ti-brand-generic') + ' ' + (platformLabels[platform] || platform) + ' Post</i>';
    nameEl.textContent = platformLabels[platform] || platform;
    document.getElementById('genResultContent').value = data.content || '';
    document.getElementById('gen-result-id').value = data.id;
    document.getElementById('gen-result-platform').value = platform;
}

var platformColors = {
    linkedin: '#0a66c2', facebook: '#1877f2', instagram: '#e4405f',
    x: '#000000', reddit: '#ff4500', youtube: '#ff0000', tiktok: '#000000',
    telegram: '#0088cc', whatsapp: '#25d366'
};
var platformIcons = {
    linkedin: '<i class="ti ti-brand-linkedin"></i>', facebook: '<i class="ti ti-brand-facebook"></i>',
    instagram: '<i class="ti ti-brand-instagram"></i>', x: '<i class="ti ti-brand-x"></i>',
    reddit: '<i class="ti ti-brand-reddit"></i>', youtube: '<i class="ti ti-brand-youtube"></i>',
    tiktok: '<i class="ti ti-brand-tiktok"></i>', telegram: '<i class="ti ti-brand-telegram"></i>',
    whatsapp: '<i class="ti ti-brand-whatsapp"></i>'
};
var platformLabels = {
    linkedin: 'LinkedIn', facebook: 'Facebook', instagram: 'Instagram',
    x: 'X (Twitter)', reddit: 'Reddit', youtube: 'YouTube', tiktok: 'TikTok',
    telegram: 'Telegram', whatsapp: 'WhatsApp'
};

function copyPostText() {
    var textarea = document.getElementById('genResultContent');
    navigator.clipboard.writeText(textarea.value).then(function() { showToast('Copied!'); });
}

function copyText(btn, id) {
    var content = document.getElementById('post-content-' + id).textContent;
    navigator.clipboard.writeText(content).then(function() { showToast('Copied!'); });
}

function copyTextStr(t) {
    navigator.clipboard.writeText(t).then(function() { showToast('Copied!'); });
}

function publishGenPost() {
    var id = document.getElementById('gen-result-id').value;
    if (!id || id === '0') { showToast('Save the post first', 'error'); return; }
    var fd = new FormData();
    fd.append('action', 'toggle_status');
    fd.append('id', id);
    fd.append('status', 'published');
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.ok) { showToast('Published!'); }
    });
}

function deleteTraining(id) {
    if (!confirm('Delete this training data?')) return;
    var fd = new FormData();
    fd.append('action', 'train_delete');
    fd.append('id', id);
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) { if (j.ok) location.reload(); });
}

function editProfile(id) {
    var fd = new FormData();
    fd.append('action', 'profile_get');
    fd.append('id', id);
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (!j.ok) return;
        var p = j.profile;
        document.getElementById('edit-profile-id').value = p.id;
        document.getElementById('edit-profile-name').value = p.name;
        document.getElementById('edit-profile-platform').value = p.platform;
        document.getElementById('edit-profile-language').value = p.language || 'english';
        document.getElementById('edit-profile-tone').value = p.tone || 'semi-professional';
        document.getElementById('edit-profile-niche').value = p.niche || '';
        document.getElementById('edit-profile-url').value = p.profile_url || '';
        document.getElementById('edit-profile-color').value = p.color || '#c8f135';
        document.getElementById('edit-profile-notes').value = p.notes || '';
        document.getElementById('edit-business-type').value = p.business_type || '';
        document.getElementById('edit-target-audience').value = p.target_audience || '';
        document.getElementById('edit-brand-voice').value = p.brand_voice || '';
        document.getElementById('edit-avoid-topics').value = p.avoid_topics || '';
        var len = p.post_length || 200;
        document.getElementById('editLen' + len).checked = true;
        var type = p.type || 'personal';
        document.getElementById('editType' + type.charAt(0).toUpperCase() + type.slice(1)).checked = true;
        document.getElementById('editClientFields').style.display = type === 'client' ? 'block' : 'none';
        document.getElementById('profileModal').classList.add('open');
    });
}

function closeProfileModal() { document.getElementById('profileModal').classList.remove('open'); }

function saveProfile(e) {
    e.preventDefault();
    var id = document.getElementById('edit-profile-id').value;
    var fd = new FormData();
    fd.append('action', 'profile_update');
    fd.append('id', id);
    fd.append('name', document.getElementById('edit-profile-name').value);
    fd.append('platform', document.getElementById('edit-profile-platform').value);
    fd.append('language', document.getElementById('edit-profile-language').value);
    fd.append('tone', document.getElementById('edit-profile-tone').value);
    fd.append('niche', document.getElementById('edit-profile-niche').value);
    fd.append('profile_url', document.getElementById('edit-profile-url').value);
    fd.append('color', document.getElementById('edit-profile-color').value);
    fd.append('notes', document.getElementById('edit-profile-notes').value);
    fd.append('post_length', document.querySelector('input[name="edit-length"]:checked').value);
    fd.append('type', document.querySelector('input[name="edit-type"]:checked').value);
    fd.append('business_type', document.getElementById('edit-business-type').value);
    fd.append('target_audience', document.getElementById('edit-target-audience').value);
    fd.append('brand_voice', document.getElementById('edit-brand-voice').value);
    fd.append('avoid_topics', document.getElementById('edit-avoid-topics').value);
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) { if (j.ok) { closeProfileModal(); location.reload(); } });
}

function deleteProfile(id) {
    if (!confirm('Delete this profile?')) return;
    var fd = new FormData();
    fd.append('action', 'profile_delete');
    fd.append('id', id);
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) { if (j.ok) location.reload(); });
}

function deletePost(id) {
    if (!confirm('Delete this post?')) return;
    var fd = new FormData();
    fd.append('action', 'delete_post');
    fd.append('id', id);
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.ok) { var el = document.getElementById('history-' + id); if (el) { el.remove(); showToast('Deleted'); } }
    });
}

function editPost(id) {
    document.getElementById('edit-post-id').value = id;
    document.getElementById('edit-post-content').value = document.getElementById('post-content-' + id).textContent;
    document.getElementById('editModal').classList.add('open');
}

function closeEditModal() { document.getElementById('editModal').classList.remove('open'); }

function saveEditPost(e) {
    e.preventDefault();
    var id = document.getElementById('edit-post-id').value;
    var content = document.getElementById('edit-post-content').value;
    var fd = new FormData();
    fd.append('action', 'update_post');
    fd.append('id', id);
    fd.append('content', content);
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.ok) { document.getElementById('post-content-' + id).textContent = content; closeEditModal(); showToast('Updated'); }
    });
}

function restorePost(id) {
    window.location.href = 'post-generator.php?restore=' + id;
}

// Auto-populate post boxes from restore
<?php if ($restoreContent): ?>
document.addEventListener('DOMContentLoaded', function() {
    var content = <?= json_encode($restoreContent) ?>;
    if (content) {
        document.getElementById('gen-result').classList.add('show');
        document.getElementById('genResultBox').style.display = 'block';
        document.getElementById('genResultContent').value = content;
    }
});
<?php endif; ?>

// Hashtag functions
function addHashtag(platform) {
    var input = document.getElementById(platform + 'HashInput');
    var tag = input.value.trim();
    if (!tag) return;
    if (!tag.startsWith('#')) tag = '#' + tag;
    var fd = new FormData();
    fd.append('action', 'hashtag_add');
    fd.append('platform', platform);
    fd.append('tag', tag);
    var pool = document.getElementById(platform + 'HashPool');
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.ok) {
            var pill = document.createElement('span');
            pill.className = 'pg-hashtag-pill';
            pill.innerHTML = tag + ' <button class="ht-del" onclick="deleteHashtag(' + j.id + ',this)">✕</button>';
            pool.appendChild(pill);
            input.value = '';
            showToast('Hashtag added');
        }
    });
}

function deleteHashtag(id, btn) {
    var fd = new FormData();
    fd.append('action', 'hashtag_delete');
    fd.append('id', id);
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.ok) { btn.closest('.pg-hashtag-pill').remove(); showToast('Removed'); }
    });
}

// Training filter
function filterTraining(el, id) {
    document.querySelectorAll('.pg-filter-chip').forEach(function(c) { c.classList.remove('active'); });
    el.classList.add('active');
    document.querySelectorAll('#trainingTable tbody tr').forEach(function(r) {
        r.style.display = (id === 0 || parseInt(r.dataset.profile) === id) ? '' : 'none';
    });
}

// Escaping helper
function escHtml(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

document.getElementById('editModal').addEventListener('click', function(e) { if (e.target === this) closeEditModal(); });
document.getElementById('profileModal').addEventListener('click', function(e) { if (e.target === this) closeProfileModal(); });

</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
