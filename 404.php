<?php
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/inc/functions.php';
$pageTitle = '404 — Page Not Found | Xoos Digital';
$pageDesc  = 'The page you are looking for does not exist.';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/navbar.php';
?>
<section class="error-page" data-animate>
    <div class="error-page-inner">
        <div class="error-code">404</div>
        <h1 class="error-heading">Page Not Found</h1>
        <p class="error-text">The page you're looking for doesn't exist or has been moved. Let's get you back on track.</p>
        <div class="error-actions">
            <a href="." class="error-btn error-btn-primary">Back to Home</a>
            <a href="contact" class="error-btn error-btn-secondary">Contact Us</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
<?php require __DIR__ . '/inc/scripts.php'; ?>
