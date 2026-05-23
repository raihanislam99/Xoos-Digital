<?php
require_once __DIR__ . '/inc/functions.php';
require_login();

$stats = [];
try {
    $tables = ['blog_posts', 'services', 'packages', 'testimonials', 'faq', 'portfolio'];
    foreach ($tables as $t) {
        $stats[$t] = db()->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
        $stats[$t . '_new'] = db()->query("SELECT COUNT(*) FROM {$t} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    }
    $stats['messages'] = db_val("SELECT COUNT(*) FROM contact_messages");
    $stats['messages_new'] = db_val("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
    $stats['messages_unread'] = $stats['messages_new'];
} catch (Exception $e) {
    $stats = [];
}

// Recent inquiries (latest 5 unread)
try {
    $recentMessages = db_rows("SELECT * FROM contact_messages WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5");
} catch (Exception $e) { $recentMessages = []; }

// Recent blog posts (latest 3)
try {
    $recentPosts = db_rows("SELECT id, title, status, created_at FROM blog_posts ORDER BY created_at DESC LIMIT 3");
} catch (Exception $e) { $recentPosts = []; }
?>
<?php require_once __DIR__ . '/inc/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.25rem;margin-bottom:2.5rem">
    <?php $icons = [
        'blog_posts' => ['ti ti-news', 'Blog Posts'],
        'services' => ['ti ti-settings', 'Services'],
        'packages' => ['ti ti-box', 'Packages'],
        'testimonials' => ['ti ti-quote', 'Testimonials'],
        'faq' => ['ti ti-help', 'FAQ'],
        'portfolio' => ['ti ti-briefcase', 'Portfolio'],
    ]; ?>
    <?php foreach ($stats as $table => $count): ?>
        <?php if (substr($table, -4) !== '_new' && substr($table, -7) !== '_unread' && $table !== 'messages'): ?>
        <?php
            $newCount = $stats[$table . '_new'] ?? 0;
            $isUnread = isset($stats[$table . '_unread']);
        ?>
        <a href="modules/<?= str_replace('_posts', '', $table) ?>.php" style="text-decoration:none">
            <div class="card hover-accent" style="text-align:center;padding:1.5rem 1rem;<?= $isUnread && $count > 0 ? 'border-color:var(--accent)' : '' ?>">
                <i class="<?= $icons[$table][0] ?>" style="font-size:1.75rem;color:var(--accent);margin-bottom:0.5rem;display:block"></i>
                <div style="font-size:1.75rem;font-weight:700;color:var(--text);font-family:'Orbitron',sans-serif"><?= $count ?></div>
                <div style="font-size:0.75rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.06em;margin-top:4px"><?= $icons[$table][1] ?></div>
                <div style="margin-top:8px;font-size:11px;color:<?= $newCount > 0 ? 'var(--green)' : 'var(--text3)' ?>">
                    <?= $newCount > 0 ? "↑ {$newCount} new this week" : 'No changes' ?>
                </div>
            </div>
        </a>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php
        $msgCount = $stats['messages'] ?? 0;
        $msgNew = $stats['messages_new'] ?? 0;
    ?>
    <a href="modules/messages.php" style="text-decoration:none">
        <div class="card hover-accent" style="text-align:center;padding:1.5rem 1rem;<?= $msgNew > 0 ? 'border-color:#CCFF00' : '' ?>">
            <i class="ti ti-mail" style="font-size:1.75rem;color:var(--accent);margin-bottom:0.5rem;display:block"></i>
            <div style="font-size:1.75rem;font-weight:700;color:var(--text);font-family:'Orbitron',sans-serif"><?= $msgCount ?></div>
            <div style="font-size:0.75rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.06em;margin-top:4px">UNREAD MESSAGES</div>
            <div style="margin-top:8px;font-size:11px;color:<?= $msgNew > 0 ? 'var(--green)' : 'var(--text3)' ?>">
                <?= $msgNew > 0 ? "↑ {$msgNew} unread" : 'No unread' ?>
            </div>
        </div>
    </a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem;margin-bottom:2.5rem">
    <!-- Column 1: Recent Messages -->
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);font-family:'Orbitron',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text)">Recent Inquiries</div>
        <div style="padding:1rem 1.25rem">
            <?php if (count($recentMessages)): ?>
                <?php foreach ($recentMessages as $m): ?>
                    <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0;border-bottom:1px solid rgba(255,255,255,0.04)">
                        <div style="flex:1;min-width:0">
                            <div style="font-size:0.82rem;font-weight:600;color:var(--text)"><?= h($m['name']) ?></div>
                            <div style="font-size:0.7rem;color:var(--text3);margin-top:2px"><?= h($m['services'] ?: 'General inquiry') ?></div>
                        </div>
                        <div style="font-size:0.65rem;color:var(--text3);white-space:nowrap"><?= time_ago($m['created_at']) ?></div>
                        <a href="modules/messages.php?view=<?= $m['id'] ?>" class="btn btn-secondary btn-sm" style="padding:0.25rem 0.6rem;font-size:0.65rem">View</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:var(--text3);font-size:0.82rem;text-align:center;padding:1rem 0">No new inquiries</p>
            <?php endif; ?>
        </div>
        <div style="padding:0.75rem 1.25rem;border-top:1px solid var(--border);text-align:center">
            <a href="modules/messages.php" style="color:var(--accent);font-size:0.78rem;font-weight:600;text-decoration:none">View All Messages →</a>
        </div>
    </div>

    <!-- Column 2: Recent Posts -->
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);font-family:'Orbitron',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text)">Recent Posts</div>
        <div style="padding:1rem 1.25rem">
            <?php if (count($recentPosts)): ?>
                <?php foreach ($recentPosts as $p): ?>
                    <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0;border-bottom:1px solid rgba(255,255,255,0.04)">
                        <div style="flex:1;min-width:0">
                            <div style="font-size:0.82rem;font-weight:600;color:var(--text)"><?= h(mb_strimwidth($p['title'], 0, 40, '...')) ?></div>
                        </div>
                        <span class="status-badge <?= $p['status'] === 'published' ? 'status-published' : 'status-draft' ?>"><?= $p['status'] ?></span>
                        <a href="modules/blog.php?edit=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" style="padding:0.25rem 0.6rem;font-size:0.65rem">Edit</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:var(--text3);font-size:0.82rem;text-align:center;padding:1rem 0">No posts yet</p>
            <?php endif; ?>
        </div>
        <div style="padding:0.75rem 1.25rem;border-top:1px solid var(--border);text-align:center">
            <a href="modules/blog.php" class="btn btn-primary btn-sm" style="padding:0.4rem 1rem;font-size:0.7rem"><i class="ti ti-plus"></i> New Post →</a>
        </div>
    </div>

    <!-- Column 3: Quick Actions -->
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);font-family:'Orbitron',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text)">Quick Actions</div>
        <div style="padding:1rem 1.25rem;display:flex;flex-direction:column;gap:0.5rem">
            <a href="modules/blog.php" class="btn-quickaction"><i class="ti ti-plus"></i> New Blog Post</a>
            <a href="modules/services.php" class="btn-quickaction"><i class="ti ti-plus"></i> New Service</a>
            <a href="modules/portfolio.php" class="btn-quickaction"><i class="ti ti-plus"></i> New Portfolio Item</a>
            <a href="modules/testimonials.php" class="btn-quickaction"><i class="ti ti-plus"></i> New Testimonial</a>
            <a href="modules/messages.php" class="btn-quickaction"><i class="ti ti-mail"></i> View Messages</a>
            <a href="modules/settings.php" class="btn-quickaction"><i class="ti ti-tool"></i> Site Settings</a>
        </div>
    </div>
</div>

<?php
function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', strtotime($datetime));
}
?>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
