<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

// Handle delete
if (isset($_GET['delete'])) {
    $m = db_rows("SELECT name FROM contact_messages WHERE id = ?", [(int)$_GET['delete']]);
    db_delete('contact_messages', 'id = ?', [(int)$_GET['delete']]);
    $_SESSION['flash_msg'] = 'Message deleted.';
    $_SESSION['flash_type'] = 'success';
    redirect('messages.php');
}

// ── DETAIL VIEW ──
if (isset($_GET['view'])) {
    $id = (int)$_GET['view'];
    db_update('contact_messages', ['is_read' => 1], 'id = ?', ['id' => $id]);
    $rows = db_rows("SELECT * FROM contact_messages WHERE id = ?", [$id]);
    if (!$rows) { redirect('messages.php'); }
    $m = $rows[0];
    require_once __DIR__ . '/../inc/header.php';
?>
<div style="max-width:800px">
    <div class="flex" style="margin-bottom:1.5rem">
        <a href="messages.php" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Back to Messages</a>
    </div>

    <div class="card" style="margin-bottom:1rem">
        <div class="flex" style="justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:0.5rem">
            <h2 style="font-family:'Orbitron',sans-serif;font-size:1rem;font-weight:700;text-transform:uppercase">
                Message from <?= h($m['name']) ?>
            </h2>
            <span style="font-size:0.75rem;color:var(--text3)">
                <?= date('M j, Y g:i A', strtotime($m['created_at'])) ?>
            </span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem">
            <div>
                <div style="font-size:0.65rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;font-weight:600">Name</div>
                <div style="color:var(--text);font-weight:600"><?= h($m['name']) ?></div>
            </div>
            <div>
                <div style="font-size:0.65rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;font-weight:600">Email</div>
                <div><a href="mailto:<?= h($m['email']) ?>" style="color:var(--accent)"><?= h($m['email']) ?></a></div>
            </div>
            <?php if ($m['phone']): ?>
            <div>
                <div style="font-size:0.65rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;font-weight:600">Phone</div>
                <div style="color:var(--text)"><?= h($m['phone']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($m['company']): ?>
            <div>
                <div style="font-size:0.65rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;font-weight:600">Company</div>
                <div style="color:var(--text)"><?= h($m['company']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($m['country']): ?>
            <div>
                <div style="font-size:0.65rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;font-weight:600">Country</div>
                <div style="color:var(--text)"><?= h($m['country']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($m['budget']): ?>
            <div>
                <div style="font-size:0.65rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;font-weight:600">Budget</div>
                <div style="color:var(--accent);font-weight:700"><?= h($m['budget']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($m['timeline']): ?>
            <div>
                <div style="font-size:0.65rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;font-weight:600">Timeline</div>
                <div style="color:var(--text)"><?= h($m['timeline']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($m['services']): ?>
        <div style="margin-bottom:1.25rem">
            <div style="font-size:0.65rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;font-weight:600;margin-bottom:0.5rem">Services</div>
            <div class="flex flex-wrap" style="gap:6px">
                <?php foreach (explode(',', $m['services']) as $svc): ?>
                    <span style="background:rgba(204,255,0,0.1);color:var(--accent);padding:2px 10px;border-radius:999px;font-size:0.7rem;font-weight:600;border:1px solid rgba(204,255,0,0.15)">
                        <?= h(trim($svc)) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($m['message']): ?>
        <div style="margin-bottom:1.25rem">
            <div style="font-size:0.65rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;font-weight:600;margin-bottom:0.5rem">Message</div>
            <div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:1rem;line-height:1.7;font-size:0.85rem;color:var(--text2);white-space:pre-wrap"><?= h($m['message']) ?></div>
        </div>
        <?php endif; ?>

        <div class="form-actions" style="margin-top:1.5rem">
            <?php if ($m['phone']): ?>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $m['phone']) ?>?text=Hello%20<?= urlencode($m['name']) ?>%2C%20this%20is%20Xoos%20Digital." target="_blank" class="btn btn-success">
                <i class="ti ti-brand-whatsapp"></i> Reply via WhatsApp
            </a>
            <?php endif; ?>
            <a href="mailto:<?= h($m['email']) ?>" class="btn btn-primary">
                <i class="ti ti-mail"></i> Reply via Email
            </a>
            <button onclick="confirmDelete('messages.php?delete=<?= $m['id'] ?>', 'message from <?= h(addslashes($m['name'])) ?>')" class="btn btn-danger">
                <i class="ti ti-trash"></i> Delete
            </button>
        </div>
    </div>
</div>
<?php
require_once __DIR__ . '/../inc/footer.php';
exit;
}

// ── LIST VIEW ──
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$total = db_val("SELECT COUNT(*) FROM contact_messages");
$pages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;
$messages = db_rows("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");

require_once __DIR__ . '/../inc/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Messages</h1>
</div>

<?php if ($flash_msg): ?>
    <div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:<?= $flash_type === 'success' ? 'var(--green-bg)' : 'var(--red-bg)' ?>;color:<?= $flash_type === 'success' ? 'var(--green)' : 'var(--red)' ?>"><?= h($flash_msg) ?></div>
<?php endif; ?>

<div class="card">
    <?php if (count($messages)): ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Services</th>
                    <th>Budget</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $m): ?>
                <tr style="<?= $m['is_read'] ? '' : 'font-weight:600;color:var(--text)' ?>">
                    <td style="color:var(--text3)">#<?= $m['id'] ?></td>
                    <td><strong style="color:<?= $m['is_read'] ? 'var(--text2)' : 'var(--text)' ?>"><?= h($m['name']) ?></strong></td>
                    <td style="color:<?= $m['is_read'] ? '' : 'var(--text)' ?>"><?= h($m['email']) ?></td>
                    <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:<?= $m['is_read'] ? '' : 'var(--text)' ?>"><?= h($m['services'] ?: '—') ?></td>
                    <td style="color:var(--accent)"><?= h($m['budget'] ?: '—') ?></td>
                    <td class="text-muted"><?= date('M j, Y', strtotime($m['created_at'])) ?></td>
                    <td>
                        <?php if (!$m['is_read']): ?>
                            <span style="background:rgba(204,255,0,0.1);color:var(--accent);padding:2px 10px;border-radius:999px;font-size:0.65rem;font-weight:700;text-transform:uppercase">NEW</span>
                        <?php else: ?>
                            <span style="background:var(--bg3);color:var(--text3);padding:2px 10px;border-radius:999px;font-size:0.65rem;font-weight:600;text-transform:uppercase">READ</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right">
                        <a href="?view=<?= $m['id'] ?>" class="btn btn-secondary btn-sm"><i class="ti ti-eye"></i></a>
                        <button onclick="confirmDelete('messages.php?delete=<?= $m['id'] ?>', 'message from <?= h(addslashes($m['name'])) ?>')" class="btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
    <div class="flex" style="justify-content:center;margin-top:1rem;gap:0.5rem">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="?p=<?= $i ?>" style="padding:0.4rem 0.75rem;background:<?= $i === $page ? 'var(--accent)' : 'var(--bg3)' ?>;color:<?= $i === $page ? 'var(--bg)' : 'var(--text2)' ?>;border-radius:6px;text-decoration:none;font-size:0.8rem;font-weight:600"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div class="empty-state">
        <i class="ti ti-mail"></i>
        <p>No messages yet.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
