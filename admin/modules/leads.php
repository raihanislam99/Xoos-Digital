<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

$view = $_GET['view'] ?? 'analytics';

$pageTitles = [
    'analytics' => 'Lead Analytics',
    'finder'    => 'Lead Finder',
    'my_leads'  => 'My Leads',
    'campaigns' => 'Campaigns',
    'templates' => 'Templates',
];
$pageTitle = $pageTitles[$view] ?? 'Outreach';

$tabs = [
    ['analytics', 'Lead Analytics', 'ti ti-chart-dots'],
    ['finder',    'Lead Finder',    'ti ti-users-plus'],
    ['my_leads',  'My Leads',       'ti ti-address-book'],
    ['campaigns', 'Campaigns',      'ti ti-mail-forward'],
    ['templates', 'Templates',      'ti ti-template'],
];

// Handle export CSV
if ($view === 'my_leads' && isset($_GET['export']) && $_GET['export'] === 'csv') {
    $where = ['is_blacklisted = 0'];
    $params = [];
    if (!empty($_GET['f_status'])) { $where[] = 'status = ?'; $params[] = $_GET['f_status']; }
    if (!empty($_GET['f_niche'])) { $where[] = 'niche = ?'; $params[] = $_GET['f_niche']; }
    if (!empty($_GET['f_city'])) { $where[] = 'city LIKE ?'; $params[] = '%' . $_GET['f_city'] . '%'; }
    $whereClause = implode(' AND ', $where);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=leads_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID','Business Name','Niche','Owner Name','Email','Phone','WhatsApp','Website','Facebook','Instagram','City','Country','Lead Score','Status','Source','Tags','Created']);
    $leads = db_rows("SELECT * FROM leads WHERE $whereClause ORDER BY created_at DESC", $params);
    foreach ($leads as $l) {
        fputcsv($output, [$l['id'],$l['business_name'],$l['niche'],$l['owner_name'],$l['email'],$l['phone'],$l['whatsapp'],$l['website'],$l['facebook'],$l['instagram'],$l['city'],$l['country'],$l['lead_score'],$l['status'],$l['source'],$l['tags'],$l['created_at']]);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/../inc/header.php';

// Flash messages
$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
?>
<?php if ($flash_msg): ?>
    <div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:<?= $flash_type === 'success' ? 'var(--green-bg)' : 'var(--red-bg)' ?>;color:<?= $flash_type === 'success' ? 'var(--green)' : 'var(--red)' ?>"><?= h($flash_msg) ?></div>
<?php endif; ?>

<style>
.outreach-header { margin-bottom: 2rem; }
.outreach-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 1.3rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text);
    margin-bottom: 1.25rem;
    position: relative;
    padding-bottom: 6px;
}
.outreach-title::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 24px;
    height: 2px;
    background: var(--accent);
    border-radius: 99px;
}
.outreach-tabs {
    display: flex;
    gap: 2px;
    background: var(--bg3);
    border-radius: var(--radius-md);
    padding: 3px;
    width: fit-content;
    max-width: 100%;
    overflow-x: auto;
}
.outreach-tab {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 0.55rem 1.1rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--text3);
    text-decoration: none;
    border-radius: calc(var(--radius-md) - 3px);
    transition: all 0.15s ease;
    white-space: nowrap;
    font-family: 'Inter', sans-serif;
}
.outreach-tab:hover {
    color: var(--text2);
    background: rgba(255,255,255,0.03);
}
.outreach-tab.active {
    color: #080B10;
    background: var(--accent);
    font-weight: 700;
}
.outreach-tab i { font-size: 1rem; }
</style>

<div class="outreach-header">
    <div class="outreach-title">Outreach</div>
    <div class="outreach-tabs">
        <?php foreach ($tabs as $t): ?>
            <a href="?view=<?= $t[0] ?>" class="outreach-tab <?= $view === $t[0] ? 'active' : '' ?>">
                <i class="<?= $t[2] ?>"></i>
                <?= $t[1] ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php
$GLOBALS['leads_csrf'] = csrf_token();

switch ($view) {
    case 'finder':
        require __DIR__ . '/leads/finder.php';
        break;
    case 'detail':
        require __DIR__ . '/leads/detail.php';
        break;
    case 'campaigns':
        require __DIR__ . '/leads/campaigns.php';
        break;
    case 'templates':
        require __DIR__ . '/leads/templates.php';
        break;
    case 'analytics':
        require __DIR__ . '/leads/analytics.php';
        break;
    case 'my_leads':
    default:
        require __DIR__ . '/leads/my_leads.php';
        break;
}

// Helper function for time_ago used in detail.php
function time_ago($datetime) {
    if (!$datetime) return '';
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 0) return 'just now';
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

require_once __DIR__ . '/../inc/footer.php'; ?>
