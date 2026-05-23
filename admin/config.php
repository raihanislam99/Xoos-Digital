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

define('DB_HOST', 'localhost');
define('DB_NAME', 'xoosdigital');
define('DB_USER', 'root');
define('DB_PASS', '');

define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: ''); // Set via environment variable in production
define('GROQ_MODEL', 'llama-3.3-70b-versatile');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$projectRoot = dirname(__DIR__);
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$basePath = str_replace('\\', '/', substr($projectRoot, strlen(rtrim($docRoot, '\\/'))));
define('BASE_URL', rtrim($protocol . '://' . $host . $basePath, '/'));
define('ADMIN_URL', BASE_URL . '/admin');
