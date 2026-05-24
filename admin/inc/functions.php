<?php
require_once __DIR__ . '/../config.php';

function db() {
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

function require_login() {
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
    $timeout = 1800;
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_destroy();
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function slugify($str) {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9]+/', '-', $str);
    return trim($str, '-');
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

function get_all($table, $order = 'created_at DESC') {
    $allowed = ['blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks'];
    if (!in_array($table, $allowed)) return [];
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $order = preg_replace('/[^a-z0-9_ ,.\-]+/i', '', $order);
    $order = trim(preg_replace('/\s+/', ' ', $order));
    try {
        return db()->query("SELECT * FROM {$safe} ORDER BY {$order}")->fetchAll();
    } catch (PDOException $e) {
        return db()->query("SELECT * FROM {$safe}")->fetchAll();
    }
}

function get_row($table, $id) {
    $allowed = ['blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks'];
    if (!in_array($table, $allowed)) return null;
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $stmt = db()->prepare("SELECT * FROM {$safe} WHERE id = ?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

function insert($table, $data) {
    $allowed = ['blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks'];
    if (!in_array($table, $allowed)) return false;
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $cols = implode(', ', array_keys($data));
    $vals = ':' . implode(', :', array_keys($data));
    $stmt = db()->prepare("INSERT INTO {$safe} ({$cols}) VALUES ({$vals})");
    return $stmt->execute($data);
}

function update($table, $id, $data) {
    $allowed = ['blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks'];
    if (!in_array($table, $allowed)) return false;
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
    $data['id'] = (int)$id;
    $stmt = db()->prepare("UPDATE {$safe} SET {$sets} WHERE id = :id");
    return $stmt->execute($data);
}

function delete($table, $id) {
    $allowed = ['blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks'];
    if (!in_array($table, $allowed)) return false;
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $stmt = db()->prepare("DELETE FROM {$safe} WHERE id = ?");
    return $stmt->execute([(int)$id]);
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

function db_val($query, $params = []) {
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function db_rows($query, $params = []) {
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_insert($table, $data) {
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $cols = implode(', ', array_keys($data));
    $vals = ':' . implode(', :', array_keys($data));
    $stmt = db()->prepare("INSERT INTO {$safe} ({$cols}) VALUES ({$vals})");
    $stmt->execute($data);
    return db()->lastInsertId();
}

function db_update($table, $data, $where, $whereParams = []) {
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
    $stmt = db()->prepare("UPDATE {$safe} SET {$sets} WHERE {$where}");
    return $stmt->execute(array_merge($data, $whereParams));
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
    $stmt = db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
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

// ── Recovery Code ──

function ensure_recovery_column(): void {
    try {
        $stmt = db()->query("SHOW COLUMNS FROM admin_users LIKE 'recovery_hash'");
        if (!$stmt->fetch()) {
            db()->exec("ALTER TABLE admin_users ADD COLUMN recovery_hash VARCHAR(64) DEFAULT NULL AFTER password_hash");
        }
    } catch (Exception $e) {}
}

function generate_recovery_code(): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $code = '';
    for ($i = 0; $i < 12; $i++) {
        if ($i === 4 || $i === 8) $code .= '-';
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function set_recovery_hash(string $code): void {
    ensure_recovery_column();
    $hash = hash('sha256', $code);
    $stmt = db()->prepare("UPDATE admin_users SET recovery_hash = ? WHERE id = 1");
    $stmt->execute([$hash]);
}

function verify_recovery_code(string $code): bool {
    ensure_recovery_column();
    $stmt = db()->prepare("SELECT recovery_hash FROM admin_users WHERE id = 1");
    $stmt->execute();
    $hash = $stmt->fetchColumn();
    return $hash !== false && hash_equals($hash, hash('sha256', $code));
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
