<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ── Load Composer autoloader ─────────────────────────
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];
foreach ($autoloadPaths as $p) {
    if (is_file($p)) { require_once $p; break; }
}

// ── Load .env into $_ENV ─────────────────────────────
// Walk up from admin/ to find .env (supports various server layouts)
$envFile = '';
$searchDir = __DIR__;
for ($i = 0; $i < 6; $i++) {
    $candidate = dirname($searchDir, $i ?: 1) . '/.env';
    if (is_file($candidate)) { $envFile = $candidate; break; }
}
if ($envFile && is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $eq = strpos($line, '=');
        if ($eq === false) continue;
        $_ENV[trim(substr($line, 0, $eq))] = trim(substr($line, $eq + 1));
    }
}

function env(string $key, mixed $default = null): mixed {
    return array_key_exists($key, $_ENV) && $_ENV[$key] !== '' ? $_ENV[$key] : $default;
}

// ── Database (MySQL — kept for migration / fallback) ─
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'xoosdigital'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// ── Supabase ─────────────────────────────────────────
define('SUPABASE_URL',             env('SUPABASE_URL', ''));
define('SUPABASE_ANON_KEY',        env('SUPABASE_ANON_KEY', ''));
define('SUPABASE_SERVICE_ROLE_KEY', env('SUPABASE_SERVICE_ROLE_KEY', ''));
define('SUPABASE_DB_HOST',         env('SUPABASE_DB_HOST', ''));
define('SUPABASE_DB_PORT',         env('SUPABASE_DB_PORT', '5432'));
define('SUPABASE_DB_NAME',         env('SUPABASE_DB_NAME', 'postgres'));
define('SUPABASE_DB_USER',         env('SUPABASE_DB_USER', 'postgres'));
define('SUPABASE_DB_PASS',         env('SUPABASE_DB_PASS', ''));
define('SUPABASE_MANAGEMENT_API_KEY', env('SUPABASE_MANAGEMENT_API_KEY', ''));

// ── AI Providers ─────────────────────────────────────
define('GROQ_API_KEY', env('GROQ_API_KEY', ''));
define('GROQ_MODEL', 'llama-3.3-70b-versatile');

// ── Web3Forms (public, shared across front-end) ──────
define('WEB3FORMS_ACCESS_KEY', env('WEB3FORMS_ACCESS_KEY', 'f5792651-e546-4e3e-a788-216ec76ab809'));

// ── Base URLs ────────────────────────────────────────
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$projectRoot = dirname(__DIR__);
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$basePath = str_replace('\\', '/', substr($projectRoot, strlen(rtrim($docRoot, '\\/'))));
define('BASE_URL', rtrim($protocol . '://' . $host . $basePath, '/'));
define('ADMIN_URL', BASE_URL . '/admin');
