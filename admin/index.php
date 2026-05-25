<?php
require_once __DIR__ . '/inc/functions.php';
require_login();

// ── Dashboard data ──
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
} catch (Exception $e) { $stats = []; }

// Tasks stats
$taskWidgetPending = 0; $taskWidgetProgress = 0; $taskWidgetDone = 0; $tasksDueToday = 0;
try { $taskWidgetPending = (int)db_val("SELECT COUNT(*) FROM admin_tasks WHERE status='pending'"); } catch (Exception $e) {}
try { $taskWidgetProgress = (int)db_val("SELECT COUNT(*) FROM admin_tasks WHERE status='in_progress'"); } catch (Exception $e) {}
try { $taskWidgetDone = (int)db_val("SELECT COUNT(*) FROM admin_tasks WHERE status='done'"); } catch (Exception $e) {}
try { $tasksDueToday = (int)db_val("SELECT COUNT(*) FROM admin_tasks WHERE status IN ('pending','in_progress') AND DATE(due_date) = CURDATE()"); } catch (Exception $e) {}
$taskWidgetTotal = max($taskWidgetPending + $taskWidgetProgress + $taskWidgetDone, 1);
$taskPct = round($taskWidgetDone / $taskWidgetTotal * 100);

// Lead stats
$leadTotal = 0; $leadContacted = 0; $leadReplied = 0; $leadWon = 0; $newLeadsWeek = 0;
try { $leadTotal = (int)db_val("SELECT COUNT(*) FROM leads WHERE is_blacklisted = 0"); } catch (Exception $e) {}
try { $leadContacted = (int)db_val("SELECT COUNT(*) FROM leads WHERE status IN ('contacted','replied','interested','meeting_booked')"); } catch (Exception $e) {}
try { $leadReplied = (int)db_val("SELECT COUNT(*) FROM leads WHERE status='replied'"); } catch (Exception $e) {}
try { $leadWon = (int)db_val("SELECT COUNT(*) FROM leads WHERE status='closed_won'"); } catch (Exception $e) {}
try { $newLeadsWeek = (int)db_val("SELECT COUNT(*) FROM leads WHERE is_blacklisted = 0 AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"); } catch (Exception $e) {}

// Blog stats
$blogDrafts = 0; $blogPublished = 0;
try { $blogDrafts = (int)db_val("SELECT COUNT(*) FROM blog_posts WHERE status='draft'"); } catch (Exception $e) {}
try { $blogPublished = (int)db_val("SELECT COUNT(*) FROM blog_posts WHERE status='published'"); } catch (Exception $e) {}

// Post gen stats
$unpubPosts = 0; $pubPosts = 0;
try { $unpubPosts = (int)db_val("SELECT COUNT(*) FROM generated_posts WHERE status='draft'"); } catch (Exception $e) {}
try { $pubPosts = (int)db_val("SELECT COUNT(*) FROM generated_posts WHERE status='published'"); } catch (Exception $e) {}

// Activity feed (last 24h across modules)
$activity = [];
try {
    $actTasks = db_rows("SELECT 'task' as type, title as text, created_at FROM admin_tasks WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 5");
    foreach ($actTasks as $a) $activity[] = ['type' => 'tasks', 'text' => 'Created task <strong>' . h($a['text']) . '</strong>', 'time' => $a['created_at']];
} catch (Exception $e) {}
try {
    $actBlog = db_rows("SELECT 'blog' as type, CONCAT('Published \"', title, '\"') as text, created_at FROM blog_posts WHERE status='published' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 3");
    foreach ($actBlog as $a) $activity[] = ['type' => 'blog', 'text' => $a['text'], 'time' => $a['created_at']];
} catch (Exception $e) {}
try {
    $actPosts = db_rows("SELECT 'post' as type, CONCAT('Generated ', platform, ' post about ', topic) as text, created_at FROM generated_posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 3");
    foreach ($actPosts as $a) $activity[] = ['type' => 'posts', 'text' => $a['text'], 'time' => $a['created_at']];
} catch (Exception $e) {}
try {
    $actLeads = db_rows("SELECT 'lead' as type, CONCAT('Added lead: ', COALESCE(business_name, owner_name, 'Unknown')) as text, created_at FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 3");
    foreach ($actLeads as $a) $activity[] = ['type' => 'outreach', 'text' => $a['text'], 'time' => $a['created_at']];
} catch (Exception $e) {}
usort($activity, function($a, $b) { return strtotime($b['time']) - strtotime($a['time']); });
$activity = array_slice($activity, 0, 8);

// Recent inquiries (latest 5 unread)
try { $recentMessages = db_rows("SELECT * FROM contact_messages WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5"); } catch (Exception $e) { $recentMessages = []; }
// Recent blog posts (latest 3)
try { $recentPosts = db_rows("SELECT id, title, status, created_at FROM blog_posts ORDER BY created_at DESC LIMIT 3"); } catch (Exception $e) { $recentPosts = []; }

$hour = (int)date('G');
if ($hour < 12) $greeting = 'Good morning';
elseif ($hour < 17) $greeting = 'Good afternoon';
else $greeting = 'Good evening';

$dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$dayName = $dayNames[(int)date('w')];
?>
<?php require_once __DIR__ . '/inc/header.php'; ?>

<!-- Stats Row -->
<div class="v3-dash-stats">
    <div class="v3-dash-stat" data-accent="orange">
        <div class="ds-value" style="color:var(--orange)"><?= $taskWidgetPending + $taskWidgetProgress ?></div>
        <div class="ds-label">Active Tasks</div>
    </div>
    <div class="v3-dash-stat" data-accent="purple">
        <div class="ds-value" style="color:var(--purple)"><?= $blogDrafts + $unpubPosts ?></div>
        <div class="ds-label">Draft Posts</div>
    </div>
    <div class="v3-dash-stat" data-accent="blue">
        <div class="ds-value" style="color:var(--blue)"><?= $newLeadsWeek ?></div>
        <div class="ds-label">New Leads</div>
    </div>
    <div class="v3-dash-stat" data-accent="green">
        <div class="ds-value" style="color:var(--green)"><?= $stats['messages_unread'] ?? 0 ?></div>
        <div class="ds-label">Unread Messages</div>
    </div>
    <div class="v3-dash-stat" data-accent="accent">
        <div class="ds-value" style="color:var(--accent)"><?= $taskPct ?>%</div>
        <div class="ds-label">Tasks Done</div>
    </div>
</div>

<!-- Focus Cards -->
<div class="v3-focus-grid">
    <a href="modules/tasks.php" class="v3-focus-card">
        <div class="fc-head">
            <div class="fc-icon tasks"><i class="ti ti-checklist"></i></div>
            <div class="fc-count"><?= $tasksDueToday ?></div>
            <span class="fc-ext-link"><i class="ti ti-arrow-up-right"></i></span>
        </div>
        <div class="fc-label">Tasks Due Today</div>
        <?php if ($tasksDueToday > 0): ?><div class="fc-overdue"><?= $taskWidgetPending ?> pending &middot; <?= $taskWidgetProgress ?> in progress</div><?php endif; ?>
        <div class="fc-mini-chart" style="color:var(--blue)"><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
        <div class="fc-action">Open Tasks <span>→</span></div>
    </a>
    <a href="modules/blog.php" class="v3-focus-card">
        <div class="fc-head">
            <div class="fc-icon blog"><i class="ti ti-news"></i></div>
            <div class="fc-count"><?= $blogDrafts ?></div>
            <span class="fc-ext-link"><i class="ti ti-arrow-up-right"></i></span>
        </div>
        <div class="fc-label">Draft Blog Posts</div>
        <div class="fc-sub"><?= $blogPublished ?> published total</div>
        <div class="fc-mini-chart" style="color:var(--purple)"><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
        <div class="fc-action">Write a Post <span>→</span></div>
    </a>
    <a href="modules/post-generator.php" class="v3-focus-card">
        <div class="fc-head">
            <div class="fc-icon posts"><i class="ti ti-file-text"></i></div>
            <div class="fc-count"><?= $unpubPosts ?></div>
            <span class="fc-ext-link"><i class="ti ti-arrow-up-right"></i></span>
        </div>
        <div class="fc-label">Unpublished Posts</div>
        <div class="fc-sub"><?= $pubPosts ?> published total</div>
        <div class="fc-mini-chart" style="color:var(--orange)"><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
        <div class="fc-action">Generate Posts <span>→</span></div>
    </a>
    <a href="modules/leads.php" class="v3-focus-card">
        <div class="fc-head">
            <div class="fc-icon outreach"><i class="ti ti-users-plus"></i></div>
            <div class="fc-count"><?= $newLeadsWeek ?></div>
            <span class="fc-ext-link"><i class="ti ti-arrow-up-right"></i></span>
        </div>
        <div class="fc-label">New Leads This Week</div>
        <div class="fc-sub"><?= $leadTotal ?> total &middot; <?= $leadContacted ?> contacted</div>
        <div class="fc-mini-chart" style="color:var(--green)"><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
        <div class="fc-action">Open Outreach <span>→</span></div>
    </a>
</div>

<!-- Activity Feed -->
<div class="v3-activity-card">
    <div class="ac-header">
        <div class="ac-title">Activity — Last 24 Hours</div>
        <a href="modules/tasks.php" class="ac-show-more">View all →</a>
    </div>
    <?php if (count($activity)): ?>
        <?php foreach ($activity as $a): ?>
            <div class="v3-activity-item">
                <div class="ai-dot"></div>
                <div class="ai-text"><?= $a['text'] ?></div>
                <div class="ai-time"><?= time_ago($a['time']) ?></div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="v3-activity-item">
            <div class="ai-text" style="text-align:center;color:var(--text-muted);padding:0.5rem 0">No activity in the last 24 hours</div>
        </div>
    <?php endif; ?>
</div>

<!-- Mini Charts Row -->
<div class="v3-mini-charts-row">
    <div class="v3-mini-chart-card">
        <div class="mc-title">Tasks by Priority</div>
        <div class="mc-body">
            <?php
            $taskPriorities = ['urgent','high','medium','low'];
            $taskPriCounts = [];
            try {
                foreach ($taskPriorities as $p) {
                    $taskPriCounts[$p] = (int)db_val("SELECT COUNT(*) FROM admin_tasks WHERE status IN ('pending','in_progress') AND priority='$p'");
                }
            } catch (Exception $e) {}
            $taskPriTot = max(array_sum($taskPriCounts), 1);
            $taskPriColors = ['urgent'=>'var(--red)','high'=>'var(--orange)','medium'=>'var(--blue)','low'=>'var(--text-muted)'];
            foreach ($taskPriorities as $p):
                $c = $taskPriCounts[$p] ?? 0;
                $pct = round($c / $taskPriTot * 100);
            ?>
            <div class="mc-row">
                <span class="mcr-label"><span class="mcr-dot" style="background:<?= $taskPriColors[$p] ?>"></span> <?= ucfirst($p) ?></span>
                <div class="mcr-bar"><div class="mcr-bar-fill" style="--pct:<?= $pct ?>;background:<?= $taskPriColors[$p] ?>"></div></div>
                <span class="mcr-val" style="color:<?= $taskPriColors[$p] ?>"><?= $c ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="v3-mini-chart-card">
        <div class="mc-title">Blog by Status</div>
        <div class="mc-body">
            <?php $blogTot = max($blogDrafts + $blogPublished, 1); $blogDraftPct = round($blogDrafts / $blogTot * 100); $blogPubPct = round($blogPublished / $blogTot * 100); ?>
            <div class="mc-row"><span class="mcr-label"><span class="mcr-dot" style="background:var(--text-muted)"></span> Draft</span><div class="mcr-bar"><div class="mcr-bar-fill" style="--pct:<?= $blogDraftPct ?>;background:var(--text-muted)"></div></div><span class="mcr-val" style="color:var(--text-muted)"><?= $blogDrafts ?></span></div>
            <div class="mc-row"><span class="mcr-label"><span class="mcr-dot" style="background:var(--green)"></span> Published</span><div class="mcr-bar"><div class="mcr-bar-fill" style="--pct:<?= $blogPubPct ?>;background:var(--green)"></div></div><span class="mcr-val" style="color:var(--green)"><?= $blogPublished ?></span></div>
        </div>
    </div>
    <div class="v3-mini-chart-card">
        <div class="mc-title">Outreach Pipeline</div>
        <div class="mc-body">
            <?php $leadTot = max($leadTotal, 1); ?>
            <div class="mc-row"><span class="mcr-label"><span class="mcr-dot" style="background:var(--blue)"></span> Total Leads</span><div class="mcr-bar"><div class="mcr-bar-fill" style="--pct:<?= round($leadTotal / $leadTot * 100) ?>;background:var(--blue)"></div></div><span class="mcr-val" style="color:var(--blue)"><?= $leadTotal ?></span></div>
            <div class="mc-row"><span class="mcr-label"><span class="mcr-dot" style="background:var(--purple)"></span> Contacted</span><div class="mcr-bar"><div class="mcr-bar-fill" style="--pct:<?= round($leadContacted / $leadTot * 100) ?>;background:var(--purple)"></div></div><span class="mcr-val" style="color:var(--purple)"><?= $leadContacted ?></span></div>
            <div class="mc-row"><span class="mcr-label"><span class="mcr-dot" style="background:var(--orange)"></span> Replied</span><div class="mcr-bar"><div class="mcr-bar-fill" style="--pct:<?= round($leadReplied / $leadTot * 100) ?>;background:var(--orange)"></div></div><span class="mcr-val" style="color:var(--orange)"><?= $leadReplied ?></span></div>
            <div class="mc-row"><span class="mcr-label"><span class="mcr-dot" style="background:var(--accent)"></span> Closed Won</span><div class="mcr-bar"><div class="mcr-bar-fill" style="--pct:<?= round($leadWon / $leadTot * 100) ?>;background:var(--accent)"></div></div><span class="mcr-val" style="color:var(--accent)"><?= $leadWon ?></span></div>
        </div>
    </div>
</div>

<?php
function time_ago($datetime) {
    if (!$datetime) return '';
    $diff = time() - strtotime($datetime);
    if ($diff < 0) return 'just now';
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', strtotime($datetime));
}
?>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
