<?php
require_once __DIR__ . '/../config.php';

// ── Composer autoload (already loaded by config.php, but ensure) ──
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];
foreach ($autoloadPaths as $p) {
    if (is_file($p)) { require_once $p; break; }
}

// ── Supabase class ──
require_once __DIR__ . '/supabase.php';

// ── Session-based query cache (avoids redundant COUNT queries per page load) ──
function db_count_cached(string $cacheKey, string $query, array $params = [], int $ttl = 30): int {
    $cache = &$_SESSION['_db_cache']; $now = time();
    if (!is_array($cache)) $cache = [];
    if (isset($cache[$cacheKey]) && ($now - $cache[$cacheKey]['time']) < $ttl) return $cache[$cacheKey]['value'];
    try {
        if (empty($params)) {
            $val = (int)db()->query($query)->fetchColumn();
        } else {
            $s = db()->prepare($query);
            $s->execute($params);
            $val = (int)$s->fetchColumn();
        }
    } catch (Exception $e) { $val = 0; }
    $cache[$cacheKey] = ['value' => $val, 'time' => $now];
    return $val;
}

// ── Supabase PostgreSQL (primary via PDO, fallback via REST) ──

function db() {
    static $pdo = null;
    static $restDb = null;
    static $useRest = null;

    if ($useRest === null) {
        // Decide once per request: can we use PDO or must we fall back to REST?
        if (!extension_loaded('pdo_pgsql') || empty(SUPABASE_DB_HOST)) {
            $useRest = true;
        } else {
            // Quick DNS check — avoids a multi-second PDO timeout when the host
            // is not resolvable (e.g. IPv6-only Supabase on a Windows / XAMPP box).
            // gethostbynamel checks IPv4; if that fails, try AAAA (IPv6) via dns_get_record.
            $ipv4 = @gethostbynamel(SUPABASE_DB_HOST);
            if ($ipv4 !== false) {
                $useRest = false;
            } else {
                $aaaa = @dns_get_record(SUPABASE_DB_HOST, DNS_AAAA);
                $useRest = empty($aaaa);
            }
        }
    }

    if ($useRest) {
        if ($restDb === null) {
            $restDb = Supabase::getInstance()->restDb();
        }
        return $restDb;
    }

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'pgsql:host=' . SUPABASE_DB_HOST . ';port=' . SUPABASE_DB_PORT . ';dbname=' . SUPABASE_DB_NAME . ';sslmode=require;connect_timeout=3',
                SUPABASE_DB_USER,
                SUPABASE_DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            $useRest = true;
            $restDb = Supabase::getInstance()->restDb();
            return $restDb;
        }
    }
    return $pdo;
}

// ── MySQL (for migration only) ───────────────────────

function mysql_db() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            return null;
        }
    }
    return $pdo;
}

// ── Auth (Supabase) ──────────────────────────────────

function require_login() {
    $supabase = Supabase::getInstance();

    if (empty($_SESSION['supabase_access_token'])) {
        redirect(ADMIN_URL . '/login.php');
    }

    // Check session timeout (30 minutes)
    $timeout = 1800;
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        supabase_logout();
        redirect(ADMIN_URL . '/login.php');
    }

    // Verify token is still valid with Supabase
    $user = $supabase->authGetUser($_SESSION['supabase_access_token']);
    if ($user === null) {
        // Token expired — try refresh
        if (!empty($_SESSION['supabase_refresh_token'])) {
            try {
                $refreshed = $supabase->authRefreshToken($_SESSION['supabase_refresh_token']);
                $_SESSION['supabase_access_token']  = $refreshed['access_token'];
                $_SESSION['supabase_refresh_token']  = $refreshed['refresh_token'];
                $_SESSION['supabase_user']           = $refreshed['user'] ?? null;
                $_SESSION['last_activity']           = time();
                return;
            } catch (\Exception $e) {
                // Refresh failed
            }
        }
        supabase_logout();
        redirect(ADMIN_URL . '/login.php');
    }

    $_SESSION['supabase_user'] = $user;
    $_SESSION['last_activity'] = time();
}

function supabase_login(string $email, string $password): bool {
    $supabase = Supabase::getInstance();
    try {
        $result = $supabase->authSignIn($email, $password);
        if (!empty($result['access_token'])) {
            session_regenerate_id(true);
            $_SESSION['supabase_access_token'] = $result['access_token'];
            $_SESSION['supabase_refresh_token'] = $result['refresh_token'];
            $_SESSION['supabase_user']          = $result['user'] ?? null;
            $_SESSION['last_activity']          = time();
            return true;
        }
    } catch (\Exception $e) {}
    return false;
}

function supabase_logout(): void {
    $supabase = Supabase::getInstance();
    if (!empty($_SESSION['supabase_access_token'])) {
        $supabase->authSignOut($_SESSION['supabase_access_token']);
    }
    unset($_SESSION['supabase_access_token']);
    unset($_SESSION['supabase_refresh_token']);
    unset($_SESSION['supabase_user']);
    unset($_SESSION['last_activity']);
    session_destroy();
}

function supabase_user(): ?array {
    return $_SESSION['supabase_user'] ?? null;
}

function supabase_user_email(): string {
    return $_SESSION['supabase_user']['email'] ?? '';
}

// ── Generic helpers ──────────────────────────────────

function slugify($str) {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9]+/', '-', $str);
    return trim($str, '-');
}

function currency_symbol($code = 'USD') {
    $map = ['USD' => '$', 'BDT' => '৳'];
    return $map[$code] ?? '$';
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function image_url($path) {
    if (empty($path)) return '';
    // Already an absolute URL that's not localhost — return as-is
    if (preg_match('#^https?://#', $path) && stripos($path, 'localhost') === false) {
        return $path;
    }
    // Strip old localhost prefix
    $path = preg_replace('#^https?://[^/]+/#', '', $path);
    // Strip "xoosdigital/" prefix if present (local dev prefix)
    $path = preg_replace('#^xoosdigital/#', '', $path);
    return BASE_URL . '/' . ltrim($path, '/');
}

// ── Generic DB shortcuts ──

function get_all($table, $order = 'created_at DESC') {
    $allowed = ['blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks','company_info','quotations','quotation_items','invoices','invoice_items','contact_messages','media_files','settings','post_training_data','post_profiles','generated_posts'];
    if (!in_array($table, $allowed)) return [];
    $pdo = db();
    if ($pdo === null) return [];
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $order = preg_replace('/[^a-z0-9_ ,.\-]+/i', '', $order);
    $order = trim(preg_replace('/\s+/', ' ', $order));
    try {
        return $pdo->query("SELECT * FROM {$safe} ORDER BY {$order}")->fetchAll();
    } catch (PDOException $e) {
        return $pdo->query("SELECT * FROM {$safe}")->fetchAll();
    }
}

function get_row($table, $id) {
    $allowed = ['blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks','company_info','quotations','quotation_items','invoices','invoice_items','contact_messages','media_files','settings','post_training_data','post_profiles','generated_posts'];
    if (!in_array($table, $allowed)) return null;
    $pdo = db();
    if ($pdo === null) return null;
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $stmt = $pdo->prepare("SELECT * FROM {$safe} WHERE id = ?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

function insert($table, $data) {
    $allowed = ['blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks','company_info','quotations','quotation_items','invoices','invoice_items','contact_messages','media_files','settings','post_training_data','post_profiles','generated_posts'];
    if (!in_array($table, $allowed)) return false;
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $cols = implode(', ', array_keys($data));
    $vals = ':' . implode(', :', array_keys($data));
    $stmt = db()->prepare("INSERT INTO {$safe} ({$cols}) VALUES ({$vals})");
    return $stmt->execute($data);
}

function update($table, $id, $data) {
    $allowed = ['blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks','company_info','quotations','quotation_items','invoices','invoice_items','contact_messages','media_files','settings','post_training_data','post_profiles','generated_posts'];
    if (!in_array($table, $allowed)) return false;
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
    $data['id'] = (int)$id;
    $stmt = db()->prepare("UPDATE {$safe} SET {$sets} WHERE id = :id");
    return $stmt->execute($data);
}

function delete($table, $id) {
    $allowed = ['blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks','company_info','quotations','quotation_items','invoices','invoice_items','contact_messages','media_files','settings','post_training_data','post_profiles','generated_posts'];
    if (!in_array($table, $allowed)) return false;
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $stmt = db()->prepare("DELETE FROM {$safe} WHERE id = ?");
    return $stmt->execute([(int)$id]);
}

function db_val($query, $params = []) {
    $pdo = db();
    if ($pdo === null) return false;
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function db_rows($query, $params = []) {
    $pdo = db();
    if ($pdo === null) return [];
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_insert($table, $data) {
    $pdo = db();
    if ($pdo === null) return false;
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $cols = implode(', ', array_keys($data));
    $vals = ':' . implode(', :', array_keys($data));
    $stmt = $pdo->prepare("INSERT INTO {$safe} ({$cols}) VALUES ({$vals})");
    $stmt->execute($data);
    return $pdo->lastInsertId();
}

function db_update($table, $data, $where, $whereParams = []) {
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $sets = implode(', ', array_map(fn($c) => "{$c} = ?", array_keys($data)));
    $params = array_merge(array_values($data), $whereParams);
    $stmt = db()->prepare("UPDATE {$safe} SET {$sets} WHERE {$where}");
    return $stmt->execute($params);
}

function db_delete($table, $where, $params = []) {
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $stmt = db()->prepare("DELETE FROM {$safe} WHERE {$where}");
    return $stmt->execute($params);
}

// ── Settings ──

function get_setting($key, $default = '') {
    $pdo = db();
    if ($pdo === null) return $default;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $v = $stmt->fetchColumn();
    return $v !== false ? $v : $default;
}

function set_setting($key, $value) {
    $pdo = db();
    if ($pdo instanceof SupabaseRestDB) {
        try {
            $pdo->restCall('PATCH', 'settings?setting_key=eq.' . urlencode($key), ['setting_value' => $value]);
            $check = $pdo->restCall('GET', 'settings?setting_key=eq.' . urlencode($key) . '&select=id');
            if (empty($check)) {
                $pdo->restCall('POST', 'settings?select=id', ['setting_key' => $key, 'setting_value' => $value], ['Prefer: return=representation']);
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value");
    return $stmt->execute([$key, $value]);
}

// ── CSRF ──

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): void {
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// ── AI Provider ──

function ai_provider_presets(): array {
    return [
        'groq'      => ['endpoint' => 'https://api.groq.com/openai/v1/chat/completions',                'label' => 'Groq',      'url' => 'https://console.groq.com/keys'],
        'openai'    => ['endpoint' => 'https://api.openai.com/v1/chat/completions',                       'label' => 'OpenAI',    'url' => 'https://platform.openai.com/api-keys'],
        'openrouter'=> ['endpoint' => 'https://openrouter.ai/api/v1/chat/completions',                    'label' => 'OpenRouter','url' => 'https://openrouter.ai/keys'],
        'deepseek'  => ['endpoint' => 'https://api.deepseek.com/chat/completions',                        'label' => 'DeepSeek',  'url' => 'https://platform.deepseek.com/api_keys'],
        'together'  => ['endpoint' => 'https://api.together.xyz/v1/chat/completions',                     'label' => 'Together',  'url' => 'https://api.together.xyz/settings/api-keys'],
        'gemini'    => ['endpoint' => '',                                                                  'label' => 'Gemini',    'url' => 'https://aistudio.google.com/apikey'],
        'custom'    => ['endpoint' => '',                                                                  'label' => 'Custom',    'url' => ''],
        'claude'    => ['endpoint' => 'https://api.anthropic.com/v1/messages',                              'label' => 'Claude',    'url' => 'https://console.anthropic.com/'],
    ];
}

function ai_best_model(string $provider): string {
    $models = [
        'groq'      => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.3-70b-versatile',
        'openai'    => 'gpt-4o',
        'openrouter'=> 'gpt-4o',
        'deepseek'  => 'deepseek-chat',
        'together'  => 'mistralai/Mixtral-8x22B-Instruct-v0.1',
        'gemini'    => 'gemini-2.5-flash-lite',
        'custom'    => 'gpt-4o',
        'claude'    => 'claude-sonnet-4-20250514',
    ];
    return $models[$provider] ?? 'gpt-4o';
}

function ai_feature_settings(string $feature): array {
    $provider = get_setting("ai_provider_{$feature}", 'groq');
    $presets  = ai_provider_presets();
    $key   = get_setting('api_key_' . $provider, ($provider === 'groq' && defined('GROQ_API_KEY')) ? GROQ_API_KEY : '');
    $model = ai_best_model($provider);
    $endpoint = $presets[$provider]['endpoint'] ?? '';
    return compact('provider', 'key', 'model', 'endpoint');
}

function ai_call(array $settings, array $messages, int $maxTokens = 1000, float $temperature = 0.7): string {
    if ($settings['provider'] === 'claude') {
        $systemMsg = null;
        $chatMessages = [];
        foreach ($messages as $m) {
            if ($m['role'] === 'system') { $systemMsg = $m['content']; continue; }
            $chatMessages[] = ['role' => $m['role'], 'content' => $m['content']];
        }
        $payload = ['model' => $settings['model'], 'max_tokens' => $maxTokens, 'temperature' => $temperature, 'messages' => $chatMessages];
        if ($systemMsg) $payload['system'] = $systemMsg;

        $ch = curl_init($settings['endpoint']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $settings['key'],
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);
        if ($curlErr) throw new RuntimeException('AI connection failed: ' . $curlErr);
        if ($httpCode !== 200) {
            $body = json_decode($response, true);
            $msg  = $body['error']['message'] ?? $body['error'] ?? "Claude service error (HTTP $httpCode)";
            throw new RuntimeException($msg);
        }
        $data = json_decode($response, true);
        return trim($data['content'][0]['text'] ?? '');
    }

    if ($settings['provider'] === 'gemini') {
        $geminiModel = $settings['model'];
        $url = "https://generativelanguage.googleapis.com/v1/models/{$geminiModel}:generateContent?key=" . urlencode($settings['key']);
        $contents = [];
        foreach ($messages as $m) {
            $role = $m['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = ['role' => $role, 'parts' => [['text' => $m['content']]]];
        }
        $payload = json_encode(['contents' => $contents, 'generationConfig' => ['maxOutputTokens' => $maxTokens, 'temperature' => $temperature]]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) throw new RuntimeException('AI connection failed: ' . $curlErr);
        if ($httpCode !== 200) {
            $body = json_decode($response, true);
            $msg  = $body['error']['message'] ?? "Gemini service error (HTTP $httpCode)";
            throw new RuntimeException($msg);
        }
        $data = json_decode($response, true);
        return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
    }

    $payload = json_encode([
        'model'       => $settings['model'],
        'messages'    => $messages,
        'max_tokens'  => $maxTokens,
        'temperature' => $temperature,
        'stream'      => false,
    ]);

    $ch = curl_init($settings['endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $settings['key'],
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) throw new RuntimeException('AI connection failed: ' . $curlErr);
    if ($httpCode !== 200) {
        $body = json_decode($response, true);
        $msg  = $body['error']['message'] ?? "AI service error (HTTP $httpCode)";
        throw new RuntimeException($msg);
    }

    $data = json_decode($response, true);
    return trim($data['choices'][0]['message']['content'] ?? '');
}
