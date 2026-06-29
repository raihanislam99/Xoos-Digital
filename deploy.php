<?php
/**
 * GitHub auto-deploy for Hostinger (no exec/shell needed)
 *
 * SETUP:
 * 1. Upload this file to public_html/deploy.php
 * 2. In .env (outside public_html), add: DEPLOY_SECRET=your_random_secret
 * 3. Go to GitHub → Settings → Webhooks → Add webhook
 *    - Payload URL: https://xoosdigital.com/deploy.php
 *    - Content type: application/json
 *    - Secret: your_random_secret
 *    - Events: Just the push event
 * 4. Push to GitHub — webhook auto-triggers, site updates
 *
 * NOTE: If behind Cloudflare, create a Page Rule for
 *       xoosdigital.com/deploy.php → Security: Off
 */

try {

header('Content-Type: text/plain');

// ── Verify webhook secret ────────────────────────
$secretFile = dirname(__DIR__) . '/.env';
$deploySecret = '';

if (is_file($secretFile)) {
    foreach (file($secretFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), 'DEPLOY_SECRET=') === 0) {
            $deploySecret = trim(substr(trim($line), strlen('DEPLOY_SECRET=')));
            break;
        }
    }
}

$payload = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if ($deploySecret && $payload) {
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $deploySecret);
    if (!hash_equals($expected, $sig)) {
        http_response_code(401);
        echo "Unauthorized\n";
        exit;
    }
}

// ── Download & extract latest from GitHub ────────
$tmpZip = sys_get_temp_dir() . '/xoos-deploy-' . time() . '.zip';
$zipUrl = 'https://github.com/raihanislam99/Xoos-Digital/archive/refs/heads/main.zip';

echo "Downloading $zipUrl ...\n";

$zipData = file_get_contents($zipUrl, false, stream_context_create(['http' => ['timeout' => 60]]));
if ($zipData === false) {
    echo "Failed to download zip — allow_url_fopen may be disabled\n";
    exit;
}

file_put_contents($tmpZip, $zipData);

$zip = new ZipArchive;
if ($zip->open($tmpZip) !== true) {
    echo "Failed to open zip — ZipArchive may not be available\n";
    unlink($tmpZip);
    exit;
}

$extractTo = __DIR__ . '/_deploy_temp';
if (is_dir($extractTo)) {
    // Remove existing temp
    $fi = new FilesystemIterator($extractTo, FilesystemIterator::SKIP_DOTS);
    foreach ($fi as $f) { $f->isDir() ? rmdir_recursive($f->getPathname()) : unlink($f->getPathname()); }
    rmdir($extractTo);
}
mkdir($extractTo, 0755, true);

$zip->extractTo($extractTo);
$zip->close();
unlink($tmpZip);

// Find extracted folder
$items = glob("$extractTo/*", GLOB_ONLYDIR);
if (empty($items)) {
    echo "Extract failed — no directory found\n";
    rmdir_recursive($extractTo);
    exit;
}

$source = reset($items);
$targetDir = __DIR__;

echo "Copying files to $targetDir ...\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    $dest = $targetDir . '/' . $iterator->getSubPathName();
    if ($item->isDir()) {
        if (!is_dir($dest)) mkdir($dest, 0755, true);
    } else {
        $basename = basename($item);
        if ($basename === '.env' || $basename === 'deploy.php') continue;
        copy($item, $dest);
    }
}

echo "Deploy complete\n";

// ── Cleanup ──
function rmdir_recursive($dir) {
    $fi = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);
    foreach ($fi as $f) {
        $f->isDir() ? rmdir_recursive($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}

rmdir_recursive($extractTo);

} catch (Throwable $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
