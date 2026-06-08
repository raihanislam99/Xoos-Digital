<?php
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/inc/functions.php';
$pageTitle = '500 — Server Error | Xoos Digital';
$pageDesc  = 'Something went wrong on our end.';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/navbar.php';
?>
<section class="error-page" data-animate>
    <div class="error-page-inner">
        <div class="error-code error-code-500">500</div>
        <h1 class="error-heading">Server Error</h1>
        <p class="error-text">Something went wrong on our end. Please try refreshing the page or come back later.</p>
        <div class="error-actions">
            <a href="javascript:location.reload()" class="error-btn error-btn-primary">Reload Page</a>
            <a href="." class="error-btn error-btn-secondary">Go Home</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
<?php require __DIR__ . '/inc/scripts.php'; ?>
