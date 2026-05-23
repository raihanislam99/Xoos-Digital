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
 */

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

if ($deploySecret) {
    $payload = file_get_contents('php://input');
    $sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
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

$zipData = @file_get_contents($zipUrl);
if ($zipData === false) {
    echo "Failed to download zip\n";
    exit;
}

file_put_contents($tmpZip, $zipData);

$zip = new ZipArchive;
if ($zip->open($tmpZip) !== true) {
    echo "Failed to open zip\n";
    unlink($tmpZip);
    exit;
}

$extractTo = dirname(__FILE__) . '/_deploy_temp';
if (is_dir($extractTo)) {
    array_map('unlink', glob("$extractTo/*.*"));
} else {
    mkdir($extractTo, 0755, true);
}

$zip->extractTo($extractTo);
$zip->close();
unlink($tmpZip);

// Find extracted folder (Xoos-Digital-main-xxx or similar)
$items = glob("$extractTo/*", GLOB_ONLYDIR);
if (empty($items)) {
    echo "Extract failed\n";
    array_map('unlink', glob("$extractTo/*"));
    rmdir($extractTo);
    exit;
}

$sourceDir = reset($items) . '/*';
$targetDir = dirname(__FILE__);

echo "Copying files to $targetDir ...\n";

// Recursive copy, skipping .env and deploy.php
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(reset($items), RecursiveDirectoryIterator::SKIP_DOTS),
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

// Cleanup temp
array_map('unlink', glob("$extractTo/*.*"));
$iterator2 = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(reset($items), RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);
foreach ($iterator2 as $item) {
    $item->isDir() ? rmdir($item) : unlink($item);
}
rmdir(reset($items));
rmdir($extractTo);

echo "Deploy complete\n";
