<?php
ob_start();
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

// ── PostgREST schema reload helper ──
function reload_pgrst_schema(): bool {
    try {
        $pdo = db();
        if ($pdo instanceof SupabaseRestDB) {
            $pdo->restCall('GET', '', null, ['Prefer: reload-schema']);
            return true;
        }
    } catch (Exception $e) {
        error_log('PostgREST schema reload failed: ' . $e->getMessage());
    }
    return false;
}

// ── Session-based query cache (avoids redundant COUNT queries per page load) ──
function db_count_cached(string $cacheKey, string $query, array $params = [], int $ttl = 300): int {
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

// ── Batch dashboard stats (single DB round-trip instead of 22+ queries) ──
function get_dashboard_stats(): array {
    $cached = get_cache('dashboard_stats', 300);
    if ($cached !== null) return $cached;

    $stats = [];
    try {
        // Single batch query works with PostgreSQL PDO
        $sql = "SELECT
            (SELECT COUNT(*) FROM blog_posts) AS blog_posts,
            (SELECT COUNT(*) FROM blog_posts WHERE created_at >= NOW() - INTERVAL '7 days') AS blog_posts_new,
            (SELECT COUNT(*) FROM services) AS services,
            (SELECT COUNT(*) FROM services WHERE created_at >= NOW() - INTERVAL '7 days') AS services_new,
            (SELECT COUNT(*) FROM packages) AS packages,
            (SELECT COUNT(*) FROM packages WHERE created_at >= NOW() - INTERVAL '7 days') AS packages_new,
            (SELECT COUNT(*) FROM testimonials) AS testimonials,
            (SELECT COUNT(*) FROM testimonials WHERE created_at >= NOW() - INTERVAL '7 days') AS testimonials_new,
            (SELECT COUNT(*) FROM faq) AS faq,
            (SELECT COUNT(*) FROM faq WHERE created_at >= NOW() - INTERVAL '7 days') AS faq_new,
            (SELECT COUNT(*) FROM portfolio) AS portfolio,
            (SELECT COUNT(*) FROM portfolio WHERE created_at >= NOW() - INTERVAL '7 days') AS portfolio_new,
            (SELECT COUNT(*) FROM contact_messages) AS messages,
            (SELECT COUNT(*) FROM contact_messages WHERE is_read = 0) AS messages_new,
            (SELECT COUNT(*) FROM admin_tasks WHERE status='pending') AS task_pending,
            (SELECT COUNT(*) FROM admin_tasks WHERE status='in_progress') AS task_progress,
            (SELECT COUNT(*) FROM admin_tasks WHERE status='done') AS task_done,
            (SELECT COUNT(*) FROM admin_tasks WHERE status IN ('pending','in_progress') AND due_date::date = CURRENT_DATE) AS tasks_due,
            (SELECT COUNT(*) FROM leads WHERE is_blacklisted = 0) AS lead_total,
            (SELECT COUNT(*) FROM leads WHERE status IN ('contacted','replied','interested','meeting_booked')) AS lead_contacted,
            (SELECT COUNT(*) FROM leads WHERE status='replied') AS lead_replied,
            (SELECT COUNT(*) FROM leads WHERE status='closed_won') AS lead_won,
            (SELECT COUNT(*) FROM leads WHERE is_blacklisted = 0 AND created_at >= CURRENT_DATE - INTERVAL '7 days') AS new_leads_week,
            (SELECT COUNT(*) FROM blog_posts WHERE status='draft') AS blog_drafts,
            (SELECT COUNT(*) FROM blog_posts WHERE status='published') AS blog_published,
            (SELECT COUNT(*) FROM generated_posts WHERE status='draft') AS unpub_posts,
            (SELECT COUNT(*) FROM generated_posts WHERE status='published') AS pub_posts,
            (SELECT COUNT(*) FROM contact_messages WHERE is_read = 0) AS unread_msgs,
            (SELECT COUNT(*) FROM admin_tasks WHERE status IN ('pending','in_progress')) AS pending_tasks,
            (SELECT COUNT(*) FROM notes) AS notes_count";
        $stmt = db()->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) $stats = $row;
    } catch (Throwable $e) {
        error_log('get_dashboard_stats batch failed: ' . $e->getMessage());
        $stats = [];
    }
    set_cache('dashboard_stats', $stats);
    return $stats;
}

// ── Simple Cache Helper (File-based) ──────────────────────────────────
function get_cache(string $key, int $ttl = 300): mixed {
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . '/' . md5($key) . '.cache';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        $data = @file_get_contents($cacheFile);
        if ($data !== false) {
            return unserialize($data);
        }
    }
    return null;
}

function set_cache(string $key, mixed $data): void {
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . '/' . md5($key) . '.cache';
    @file_put_contents($cacheFile, serialize($data));
}

function clear_cache(string $prefix = ''): void {
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) return;
    
    $pattern = $cacheDir . '/' . ($prefix ? md5($prefix) . '*' : '*');
    $files = glob($pattern);
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
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
            try {
                $restDb->restCall('GET', '', null, ['Prefer: reload-schema']);
            } catch (Exception $reloadErr) {
                error_log('PostgREST schema reload failed: ' . $reloadErr->getMessage());
            }
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
            try {
                $restDb->restCall('GET', '', null, ['Prefer: reload-schema']);
            } catch (Exception $reloadErr) {
                error_log('PostgREST schema reload failed: ' . $reloadErr->getMessage());
            }
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

    // Only verify token with Supabase every 5 minutes (300s) to reduce API calls
    $tokenVerifyInterval = 300;
    $needsTokenVerify = empty($_SESSION['token_verified_at']) || 
                        (time() - $_SESSION['token_verified_at']) > $tokenVerifyInterval;

    if ($needsTokenVerify) {
        $user = $supabase->authGetUser($_SESSION['supabase_access_token']);
        if ($user === null) {
            // Token expired — try refresh
            if (!empty($_SESSION['supabase_refresh_token'])) {
                try {
                    $refreshed = $supabase->authRefreshToken($_SESSION['supabase_refresh_token']);
                    $_SESSION['supabase_access_token']  = $refreshed['access_token'];
                    $_SESSION['supabase_refresh_token']  = $refreshed['refresh_token'];
                    $_SESSION['supabase_user']           = $refreshed['user'] ?? null;
                    $_SESSION['token_verified_at']       = time();
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
        $_SESSION['token_verified_at'] = time();
    }

    $_SESSION['last_activity'] = time();

    // Redirect to onboarding if team member hasn't completed it yet
    $skipPages = ['onboarding.php', 'login.php', 'logout.php', 'forgot-password.php', 'reset-password.php'];
    $currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!in_array($currentScript, $skipPages)) {
        // Cache onboarding check in session
        $onboardCheckKey = 'onboarding_check_' . md5(supabase_user_email());
        $onboardCacheTtl = 600; // 10 minute cache
        if (!isset($_SESSION[$onboardCheckKey]) || 
            (time() - $_SESSION[$onboardCheckKey]['checked_at'] > $onboardCacheTtl)) {
            $onboardPending = false;
            $currentEmail = supabase_user_email();
            if ($currentEmail) {
                try {
                    $rows = db_rows("SELECT onboarding_complete FROM team_members WHERE email = ? AND onboarding_complete = 0", [$currentEmail]);
                    $onboardPending = !empty($rows);
                } catch (Exception $e) {
                    // Table or column may not exist in REST schema cache yet — try reload once
                    try {
                        $pdo = db();
                        if ($pdo instanceof SupabaseRestDB) {
                            $pdo->restCall('GET', '', null, ['Prefer: reload-schema']);
                        }
                    } catch (Exception $e2) {}
                    try {
                        $rows = db_rows("SELECT onboarding_complete FROM team_members WHERE email = ? AND onboarding_complete = 0", [$currentEmail]);
                        $onboardPending = !empty($rows);
                    } catch (Exception $e3) {}
                }
            }
            $_SESSION[$onboardCheckKey] = [
                'pending' => $onboardPending,
                'checked_at' => time()
            ];
        } else {
            $onboardPending = $_SESSION[$onboardCheckKey]['pending'];
        }
        
        if ($onboardPending) {
            redirect(ADMIN_URL . '/onboarding.php');
        }
    }
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
    while (ob_get_level()) ob_end_clean();
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

function get_all($table, $order = 'created_at DESC', ?int $limit = null) {
    $allowed = ['notes','note_checklist_items','blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','portfolio_categories','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks','company_info','quotations','quotation_items','invoices','invoice_items','contact_messages','media_files','settings','post_training_data','post_profiles','generated_posts','team_members'];
    if (!in_array($table, $allowed)) return [];
    $pdo = db();
    if ($pdo === null) return [];
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $order = preg_replace('/[^a-z0-9_ ,.\-]+/i', '', $order);
    $order = trim(preg_replace('/\s+/', ' ', $order));
    $limitClause = $limit !== null ? " LIMIT " . (int)$limit : '';
    try {
        return $pdo->query("SELECT * FROM {$safe} ORDER BY {$order}{$limitClause}")->fetchAll();
    } catch (Throwable $e) {
        try {
            return $pdo->query("SELECT * FROM {$safe} ORDER BY created_at DESC{$limitClause}")->fetchAll();
        } catch (Throwable $e2) {
            try {
                return $pdo->query("SELECT * FROM {$safe}{$limitClause}")->fetchAll();
            } catch (Throwable $e3) {
                return [];
            }
        }
    }
}

function get_row($table, $id) {
    $allowed = ['notes','note_checklist_items','blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','portfolio_categories','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks','company_info','quotations','quotation_items','invoices','invoice_items','contact_messages','media_files','settings','post_training_data','post_profiles','generated_posts','team_members'];
    if (!in_array($table, $allowed)) return null;
    $pdo = db();
    if ($pdo === null) return null;
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $stmt = $pdo->prepare("SELECT * FROM {$safe} WHERE id = ?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

function insert($table, $data) {
    $allowed = ['notes','note_checklist_items','blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','portfolio_categories','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks','company_info','quotations','quotation_items','invoices','invoice_items','contact_messages','media_files','settings','post_training_data','post_profiles','generated_posts','team_members'];
    if (!in_array($table, $allowed)) return false;
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $cols = implode(', ', array_keys($data));
    $vals = ':' . implode(', :', array_keys($data));
    $stmt = db()->prepare("INSERT INTO {$safe} ({$cols}) VALUES ({$vals})");
    return $stmt->execute($data);
}

function update($table, $id, $data) {
    $allowed = ['notes','note_checklist_items','blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','portfolio_categories','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks','company_info','quotations','quotation_items','invoices','invoice_items','contact_messages','media_files','settings','post_training_data','post_profiles','generated_posts','team_members'];
    if (!in_array($table, $allowed)) return false;
    $safe = preg_replace('/[^a-z_]/', '', $table);
    $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
    $data['id'] = (int)$id;
    $stmt = db()->prepare("UPDATE {$safe} SET {$sets} WHERE id = :id");
    return $stmt->execute($data);
}

function delete($table, $id) {
    $allowed = ['notes','note_checklist_items','blog_posts','blog_categories','services','packages','testimonials','faq','portfolio','portfolio_categories','brands','leads','lead_emails','lead_whatsapp','lead_activity','outreach_templates','admin_tasks','company_info','quotations','quotation_items','invoices','invoice_items','contact_messages','media_files','settings','post_training_data','post_profiles','generated_posts','team_members'];
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
function &get_settings_cache(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $cache = [];
    $cached = get_cache('settings_all', 300);
    if ($cached !== null) {
        $cache = $cached;
        return $cache;
    }

    $pdo = db();
    if ($pdo === null) return $cache;
    
    try {
        $rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
        set_cache('settings_all', $cache);
    } catch (Exception $e) {}
    return $cache;
}

function get_all_settings(): array {
    return get_settings_cache();
}

function get_setting($key, $default = '') {
    $settings = get_settings_cache();
    return $settings[$key] ?? $default;
}

function set_setting($key, $value) {
    // Update cache
    $cache = &get_settings_cache();
    $cache[$key] = $value;

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
    // Check if logged-in user is a team member with their own API key
    $email = supabase_user_email();
    if ($email) {
        try {
            $rows = db_rows("SELECT own_api_key, own_api_provider FROM team_members WHERE email = ? AND onboarding_complete = 1 AND own_api_key != ''", [$email]);
            if (!empty($rows)) {
                $ownKey = trim($rows[0]['own_api_key']);
                $ownProvider = trim($rows[0]['own_api_provider']);
                if ($ownKey && isset($presets[$ownProvider])) {
                    $key = $ownKey;
                    $provider = $ownProvider;
                    $model = ai_best_model($provider);
                    $endpoint = $presets[$provider]['endpoint'] ?? '';
                }
            }
        } catch (Exception $e) {
            // Retry once with schema reload
            try {
                reload_pgrst_schema();
                $rows = db_rows("SELECT own_api_key, own_api_provider FROM team_members WHERE email = ? AND onboarding_complete = 1 AND own_api_key != ''", [$email]);
                if (!empty($rows)) {
                    $ownKey = trim($rows[0]['own_api_key']);
                    $ownProvider = trim($rows[0]['own_api_provider']);
                    if ($ownKey && isset($presets[$ownProvider])) {
                        $key = $ownKey;
                        $provider = $ownProvider;
                        $model = ai_best_model($provider);
                        $endpoint = $presets[$provider]['endpoint'] ?? '';
                    }
                }
            } catch (Exception $e2) {}
        }
    }
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
            CURLOPT_SSL_VERIFYPEER => false,
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
            CURLOPT_SSL_VERIFYPEER => false,
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
        CURLOPT_SSL_VERIFYPEER => false,
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
