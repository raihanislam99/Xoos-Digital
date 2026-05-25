<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

if (isset($_GET['delete'])) {
    delete('packages', $_GET['delete']);
    redirect('packages.php');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    try {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'tier' => trim($_POST['tier'] ?? ''),
            'tagline' => trim($_POST['tagline'] ?? ''),
            'price' => trim($_POST['price'] ?? ''),
            'features' => trim($_POST['features'] ?? ''),
        ];
        if ($id) {
            update('packages', $id, $data);
        } else {
            insert('packages', $data);
        }
        $_SESSION['flash_msg'] = 'Package saved successfully.';
        $_SESSION['flash_type'] = 'success';
        redirect('packages.php');
    } catch (Exception $e) {
        $msg = 'Error: ' . $e->getMessage();
    }
}

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

$records = get_all('packages', 'created_at DESC');
$editItem = [
    'id'       => null,
    'name'     => '',
    'tier'     => '',
    'tagline'  => '',
    'price'    => '',
    'features' => '',
];
$isEdit = false;
if (!empty($_GET['edit']) && is_numeric($_GET['edit'])) {
    $fetched = get_row('packages', (int)$_GET['edit']);
    if ($fetched) {
        $editItem = array_merge($editItem, $fetched);
        $isEdit = true;
    }
}
$showForm = $isEdit || isset($_GET['new']);
?>
<?php require_once __DIR__ . '/../inc/header.php'; ?>
<?php if ($flash_msg): ?>
    <div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:<?= $flash_type === 'success' ? 'var(--green-bg)' : 'var(--red-bg)' ?>;color:<?= $flash_type === 'success' ? 'var(--green)' : 'var(--red)' ?>"><?= h($flash_msg) ?></div>
<?php endif; ?>
<?php if ($msg): ?>
    <div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:var(--red-bg);color:var(--red)"><?= h($msg) ?></div>
<?php endif; ?>
<div class="page-header">
    <h1 class="page-title">Packages</h1>
    <div class="flex flex-wrap">
        <a href="packages.php?new=1" class="btn btn-primary"><i class="ti ti-plus"></i> New Package</a>
    </div>
</div>

<div id="module-list" style="<?= $showForm ? 'display:none' : 'display:block' ?>">
    <div class="card">
        <?php if (count($records)): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Name</th><th>Tier</th><th>Price</th><th>Tagline</th><th style="text-align:right">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $p): ?>
                    <tr>
                        <td><strong style="color:var(--text)"><?= h($p['name'] ?? '') ?></strong></td>
                        <td><span class="text-muted"><?= h($p['tier'] ?? '') ?></span></td>
                        <td style="color:var(--accent);font-weight:600"><?= h($p['price'] ?? '') ?></td>
                        <td class="text-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($p['tagline'] ?? '') ?></td>
                        <td style="text-align:right">
                            <a href="?edit=<?= $p['id'] ?? 0 ?>" class="btn btn-secondary btn-sm"><i class="ti ti-pencil"></i></a>
                            <button onclick="confirmDelete('packages.php?delete=<?= $p['id'] ?? 0 ?>', '<?= h(addslashes($p['name'] ?? '')) ?>')" class="btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="ti ti-box"></i><p>No packages yet.</p><a href="packages.php?new=1" class="btn btn-primary mt-1">Add Package</a></div>
        <?php endif; ?>
    </div>
</div>

<div id="module-form" style="<?= $showForm ? 'display:block' : 'display:none' ?>">
    <div class="card">
        <div class="flex" style="margin-bottom:1rem">
            <button class="btn btn-secondary" onclick="showList()"><i class="ti ti-arrow-left"></i> Back</button>
        </div>
        <form method="post" action="packages.php">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Package Name</label>
                    <input class="form-control" name="name" value="<?= h($editItem['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Tier</label>
                    <select class="form-control" name="tier">
                        <option value="starter" <?= ($editItem['tier']??'')==='starter'?'selected':'' ?>>Starter</option>
                        <option value="growth" <?= ($editItem['tier']??'')==='growth'?'selected':'' ?>>Growth</option>
                        <option value="premium" <?= ($editItem['tier']??'')==='premium'?'selected':'' ?>>Premium</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Price</label>
                    <input class="form-control" name="price" value="<?= h($editItem['price'] ?? '') ?>" placeholder="$999">
                </div>
                <div class="form-group">
                    <label>Tagline</label>
                    <input class="form-control" name="tagline" value="<?= h($editItem['tagline'] ?? '') ?>" placeholder="Best for growing businesses">
                    <div class="flex" style="margin-top:4px;gap:4px">
                        <button type="button" class="btn btn-ai btn-sm" onclick="aiTagline(this)"><i class="ti ti-sparkles"></i> Write Tagline</button>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Features <span class="text-muted">(one per line)</span></label>
                <textarea class="form-control" name="features" rows="8" placeholder="Feature 1&#10;Feature 2"><?= h($editItem['features'] ?? '') ?></textarea>
                <div class="flex" style="margin-top:4px;gap:4px">
                    <button type="button" class="btn btn-ai btn-sm" onclick="aiPackageFeatures(this)"><i class="ti ti-list-check"></i> Generate Features</button>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="action" value="save" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
                <button type="button" class="btn btn-secondary" onclick="showList()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showList() { window.location.href = 'packages.php'; }
function aiTagline(btn) {
    var name = document.querySelector('input[name="name"]').value.trim() + ' (' + document.querySelector('select[name="tier"]').value + ')';
    if (!name.trim()) { alert('Enter package name first'); return; }
    var field = document.querySelector('input[name="tagline"]');
    btn.innerHTML = '<span class="ai-spinner"></span>'; btn.disabled = true;
    fetch('../ai.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({task:'package_tagline', context: name})})
    .then(r=>r.json()).then(j=>{ btn.innerHTML = '<i class="ti ti-sparkles"></i> Write Tagline'; btn.disabled = false; if(j.success) field.value = j.data; })
    .catch(()=>{ btn.innerHTML = '<i class="ti ti-sparkles"></i> Write Tagline'; btn.disabled = false; });
}
function aiPackageFeatures(btn) {
    var name = document.querySelector('input[name="name"]').value.trim() + ' (' + document.querySelector('select[name="tier"]').value + ')';
    if (!name.trim()) { alert('Enter package name first'); return; }
    var field = document.querySelector('textarea[name="features"]');
    btn.innerHTML = '<span class="ai-spinner"></span>'; btn.disabled = true;
    fetch('../ai.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({task:'package_features', context: name})})
    .then(r=>r.json()).then(j=>{ btn.innerHTML = '<i class="ti ti-list-check"></i> Generate Features'; btn.disabled = false; if(j.success) field.value = Array.isArray(j.data) ? j.data.join('\n') : j.data; })
    .catch(()=>{ btn.innerHTML = '<i class="ti ti-list-check"></i> Generate Features'; btn.disabled = false; });
}
</script>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>
