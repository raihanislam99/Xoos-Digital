<?php
require_once __DIR__ . '/functions.php';
require_login();

// Send cache headers for static pages (non-POST requests)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Cache-Control: private, no-cache, must-revalidate');
    header('Pragma: no-cache');
    // Let browser cache static files (css/js/images) longer
}

$current = basename($_SERVER['SCRIPT_NAME']);
$module = basename(dirname($_SERVER['SCRIPT_NAME'])) === 'modules' ? basename($_SERVER['SCRIPT_NAME']) : '';
$active = $module ? str_replace('.php', '', $module) : '';

// Sidebar stats — use batched cache from dashboard if available, else individual
$s = get_cache('dashboard_stats', 300);
if ($s !== null) {
    $unread_msgs   = (int)($s['unread_msgs'] ?? 0);
    $pendingTasks  = (int)($s['pending_tasks'] ?? 0);
    $tasksDueToday = (int)($s['tasks_due'] ?? 0);
    $blogDrafts    = (int)($s['blog_drafts'] ?? 0);
    $blogCount     = (int)($s['blog_posts'] ?? 0);
    $unpubPosts    = (int)($s['unpub_posts'] ?? 0);
    $newLeadsWeek  = (int)($s['new_leads_week'] ?? 0);
    $notesCount    = (int)($s['notes_count'] ?? 0);
} else {
    $unread_msgs = db_count_cached('unread_msgs', "SELECT COUNT(*) FROM contact_messages WHERE is_read = 0", [], 60);
    $pendingTasks = db_count_cached('pending_tasks', "SELECT COUNT(*) FROM admin_tasks WHERE status IN ('pending','in_progress')", [], 60);
    $tasksDueToday = db_count_cached('tasks_due_today', "SELECT COUNT(*) FROM admin_tasks WHERE status IN ('pending','in_progress') AND due_date::date = CURRENT_DATE", [], 60);
    $blogDrafts = db_count_cached('blog_drafts', "SELECT COUNT(*) FROM blog_posts WHERE status = 'draft'");
    $blogCount = db_count_cached('blog_count', "SELECT COUNT(*) FROM blog_posts");
    $unpubPosts = db_count_cached('unpub_posts', "SELECT COUNT(*) FROM generated_posts WHERE status = 'draft'");
    $newLeadsWeek = db_count_cached('new_leads_week', "SELECT COUNT(*) FROM leads WHERE is_blacklisted = 0 AND created_at >= CURRENT_DATE - INTERVAL '7 days'");
    $notesCount = db_count_cached('notes_count', "SELECT COUNT(*) FROM notes");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Xoos Digital</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/favicon.svg" />
    <link rel="shortcut icon" href="<?= BASE_URL ?>/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Xoos Digital" />
    <link rel="manifest" href="<?= BASE_URL ?>/site.webmanifest" />
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.31.0/dist/tabler-icons.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/admin-ui.css?v=<?= filemtime(__DIR__ . '/../assets/css/admin-ui.css') ?>">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/admin-v3.css?v=<?= filemtime(__DIR__ . '/../assets/css/admin-v3.css') ?>">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/admin-base.css?v=<?= filemtime(__DIR__ . '/../assets/css/admin-base.css') ?>">
    <script defer src="<?= ADMIN_URL ?>/js/admin.js?v=<?= filemtime(__DIR__ . '/../js/admin.js') ?>"></script>
    <script defer src="<?= ADMIN_URL ?>/assets/js/admin-ui.js?v=<?= filemtime(__DIR__ . '/../assets/js/admin-ui.js') ?>"></script>
    <script defer src="<?= ADMIN_URL ?>/assets/js/admin-v3.js?v=<?= filemtime(__DIR__ . '/../assets/js/admin-v3.js') ?>"></script>
    <script>window.ADMIN_URL='<?= ADMIN_URL ?>';window.BASE_URL='<?= BASE_URL ?>';window.blogAIProvider='<?= get_setting('ai_provider_blog','groq') ?>';</script>
</head>
<body>
    <button class="mobile-menu-btn" id="mobileMenuBtn" title="Menu">
        <i class="ti ti-menu-2"></i>
    </button>
<?php
$profilePhoto = get_setting('profile_photo', '');
$founderName = get_setting('founder_name', 'Admin');
$founderTitle = get_setting('founder_title', 'Administrator');
$userEmail = supabase_user_email();
$avatarLetter = strtoupper(substr($founderName ?: $userEmail, 0, 1));
?>
    <aside class="sidebar v3-sidebar" id="sidebar">
        <div class="sidebar-user">
            <a href="<?= ADMIN_URL ?>/modules/settings.php" class="sidebar-user-link" title="Edit Profile">
                <div class="sidebar-avatar" style="position:relative">
                    <?php if ($profilePhoto): ?>
                        <img src="<?= h(image_url($profilePhoto)) ?>" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover">
                    <?php else: ?>
                        <?= $avatarLetter ?>
                    <?php endif; ?>
                    <span class="sidebar-avatar-badge"><i class="ti ti-settings"></i></span>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= htmlspecialchars($founderName) ?></div>
                    <div class="sidebar-user-role"><?= htmlspecialchars($founderTitle) ?></div>
                </div>
            </a>
            <button class="v3-bell sidebar-bell" id="v3BellBtn" title="Notifications">
                <i class="ti ti-bell"></i>
                <?php if ($unread_msgs > 0 || $tasksDueToday > 0): ?>
                    <span class="bell-dot"></span>
                <?php endif; ?>
            </button>
            <div class="v3-bell-dropdown" id="v3BellDropdown">
                <div class="bd-header">Notifications</div>
                <?php if ($unread_msgs > 0): ?>
                    <div class="bd-item" onclick="window.location.href='<?= ADMIN_URL ?>/modules/messages.php'">
                        <div class="bd-title"><?= $unread_msgs ?> unread message<?= $unread_msgs !== 1 ? 's' : '' ?></div>
                        <div class="bd-meta">From contact form</div>
                    </div>
                <?php endif; ?>
                <?php if ($tasksDueToday > 0): ?>
                    <div class="bd-item" onclick="window.location.href='<?= ADMIN_URL ?>/modules/tasks.php?tab=tasks'">
                        <div class="bd-title"><?= $tasksDueToday ?> task<?= $tasksDueToday !== 1 ? 's' : '' ?> due today</div>
                        <div class="bd-meta">Check your Tasks board</div>
                    </div>
                <?php endif; ?>
                <?php if ($blogDrafts > 0): ?>
                    <div class="bd-item" onclick="window.location.href='<?= ADMIN_URL ?>/modules/blog.php'">
                        <div class="bd-title"><?= $blogDrafts ?> draft blog post<?= $blogDrafts !== 1 ? 's' : '' ?></div>
                        <div class="bd-meta">Ready to publish</div>
                    </div>
                <?php endif; ?>
                <?php if ($unread_msgs === 0 && $tasksDueToday === 0 && $blogDrafts === 0): ?>
                    <div class="bd-empty">All caught up ✨</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="v3-more-section" style="flex:1;overflow-y:auto;padding:0.5rem 0">
            <!-- Dashboard -->
            <a href="<?= ADMIN_URL ?>/index.php" class="v3-focus-item <?= $active === '' || $current === 'index.php' ? 'active' : '' ?>">
                <span class="fi-icon" style="background:rgba(255,255,255,0.04);color:var(--v3-text3)"><i class="ti ti-rocket"></i></span>
                <span class="fi-label">Dashboard</span>
            </a>

            <!-- Tasks -->
            <?php $isTasksPage = strpos($_SERVER['SCRIPT_NAME'] ?? '', 'tasks.php') !== false; $taskCount = (int)$pendingTasks; ?>
            <a href="<?= ADMIN_URL ?>/modules/tasks.php" class="v3-focus-item <?= $isTasksPage ? 'active' : '' ?>">
                <span class="fi-icon tasks"><i class="ti ti-checklist"></i></span>
                <span class="fi-label">Tasks</span>
                <?php if ($taskCount > 0): ?>
                    <span class="fi-badge has-items"><?= $taskCount ?></span>
                <?php else: ?>
                    <span class="fi-badge">0</span>
                <?php endif; ?>
            </a>

            <!-- Notes -->
            <?php $isNotesPage = strpos($_SERVER['SCRIPT_NAME'] ?? '', 'notes.php') !== false; ?>
            <a href="<?= ADMIN_URL ?>/modules/notes.php" class="v3-focus-item <?= $isNotesPage ? 'active' : '' ?>">
                <span class="fi-icon tasks"><i class="ti ti-notes"></i></span>
                <span class="fi-label">Notes</span>
                <span class="fi-badge <?= $notesCount > 0 ? 'has-items' : '' ?>"><?= $notesCount ?></span>
            </a>

            <?php
            $focusItems = [
                ['blog',         'Blog Posts',    'ti ti-news',      'blog',      $blogCount],
                ['post-generator','Post Generator','ti ti-file-text','posts',     $unpubPosts],
                ['leads',        'Outreach',      'ti ti-users-plus','outreach',  $newLeadsWeek],
            ];
            foreach ($focusItems as $f):
                $isActive = $active === $f[0];
                $hasItems = $f[4] > 0;
            ?>
                <a href="<?= ADMIN_URL ?>/modules/<?= $f[0] ?>.php" class="v3-focus-item <?= $isActive ? 'active' : '' ?>">
                    <span class="fi-icon <?= $f[3] ?>"><i class="<?= $f[1] === 'Post Generator' ? 'ti ti-file-text' : $f[2] ?>"></i></span>
                    <span class="fi-label"><?= $f[1] ?></span>
                    <?php if ($hasItems): ?>
                        <span class="fi-badge has-items" id="v3Focus<?= ucfirst($f[3]) ?>"><?= $f[4] ?></span>
                    <?php else: ?>
                        <span class="fi-badge" id="v3Focus<?= ucfirst($f[3]) ?>">0</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>

            <?php
            $qiCount = db_count_cached('qi_draft', "SELECT COUNT(*) FROM quotations WHERE status='draft'");
            ?>
            <a href="<?= ADMIN_URL ?>/modules/quote-invoice.php" class="v3-focus-item <?= $active === 'quote-invoice' ? 'active' : '' ?>">
                <span class="fi-icon outreach"><i class="ti ti-receipt"></i></span>
                <span class="fi-label">Quote & Invoice</span>
                <span class="fi-badge <?= $qiCount > 0 ? 'has-items' : '' ?>"><?= $qiCount ?></span>
            </a>

            <div class="nav-separator"></div>

            <!-- Messages (standalone) -->
            <a href="<?= ADMIN_URL ?>/modules/messages.php" class="v3-more-item <?= $active === 'messages' ? 'active' : '' ?>" style="padding:0.55rem 1.25rem">
                <i class="ti ti-mail"></i><span>Messages</span>
                <?php if ($unread_msgs > 0): ?>
                    <span class="unread-badge"><?= $unread_msgs ?></span>
                <?php endif; ?>
            </a>

            <div class="nav-separator"></div>

            <?php
            $contentItems = [
                ['services',     'Services',      'ti ti-settings'],
                ['packages',     'Packages',      'ti ti-box'],
                ['testimonials', 'Testimonials',  'ti ti-quote'],
                ['faq',          'FAQ',           'ti ti-help'],
                ['portfolio',    'Portfolio',     'ti ti-briefcase'],
                ['portfolio-categories', 'Categories', 'ti ti-tags'],
                ['brands',       'Brands',        'ti ti-building'],
                ['media',        'Media Library', 'ti ti-photo'],
            ];
            foreach ($contentItems as $n):
                $isActive = $active === $n[0];
            ?>
                <a href="<?= ADMIN_URL ?>/modules/<?= $n[0] ?>.php" class="v3-more-item <?= $isActive ? 'active' : '' ?>">
                    <i class="<?= $n[2] ?>"></i><span><?= $n[1] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="v3-sidebar-footer">
            <a href="<?= ADMIN_URL ?>/modules/team.php" class="<?= $active === 'team' ? 'active' : '' ?>">
                <i class="ti ti-users"></i><span>Team</span>
            </a>
            <a href="<?= ADMIN_URL ?>/modules/settings.php" class="<?= $active === 'settings' ? 'active' : '' ?>">
                <i class="ti ti-tool"></i><span>Settings</span>
            </a>
            <a href="<?= ADMIN_URL ?>/reset-password.php" style="font-size:0.75rem;color:var(--text-muted);border-top:1px solid var(--border);margin-top:4px;padding-top:10px">
                <i class="ti ti-key"></i><span>Credentials</span>
            </a>
        </div>
        <div class="v3-cmdk-hint">Press <kbd>Ctrl+K</kbd> to search</div>
        <div class="v3-version-badge">v3.0</div>
    </aside>
    <div class="sidebar-backdrop" onclick="document.getElementById('sidebar').classList.remove('open')"></div>
    <main class="admin-main v3-main">
