<?php
/**
 * Simple file-based full-page cache for the frontend.
 * Include at the VERY TOP of any public page BEFORE any output.
 *
 * Usage:
 *   require __DIR__ . '/inc/page-cache.php';
 *   page_cache_start('index', 300); // 5 min TTL
 *   ... your PHP/HTML ...
 *   page_cache_end();
 */

function page_cache_start(string $key, int $ttl = 300): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') return;

    // Skip cache for query strings (except clean URLs)
    if (!empty($_GET) && !isset($_GET['slug'])) return;

    // Store key in globals so page_cache_end() can use it
    $slug = $_GET['slug'] ?? '';
    $GLOBALS['_page_cache_key'] = md5($key . '_' . $slug);

    $cacheDir = __DIR__ . '/../cache/pages';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }

    $cacheFile = $cacheDir . '/' . $GLOBALS['_page_cache_key'] . '.html';

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        $content = @file_get_contents($cacheFile);
        if ($content !== false) {
            echo $content;
            exit;
        }
    }

    // Start buffering
    ob_start();
}

function page_cache_end(): void {
    if (ob_get_level() === 0) return;

    // Only cache successful GET responses
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        ob_end_flush();
        return;
    }

    $html = ob_get_contents();

    $cacheDir = __DIR__ . '/../cache/pages';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }

    $key = $GLOBALS['_page_cache_key'] ?? md5($_SERVER['REQUEST_URI']);
    $cacheFile = $cacheDir . '/' . $key . '.html';
    @file_put_contents($cacheFile, $html);

    ob_end_flush();
}
