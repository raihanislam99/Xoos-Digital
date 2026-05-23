<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

if (isset($_GET['delete'])) {
    delete('services', $_GET['delete']);
    redirect('services.php');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    try {
        $id = (int)($_POST['id'] ?? 0);
        $features = trim($_POST['features'] ?? '');
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'features' => $features,
            'hashtags' => trim($_POST['hashtags'] ?? ''),
            'price' => trim($_POST['price'] ?? ''),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
        if ($id) {
            update('services', $id, $data);
        } else {
            insert('services', $data);
        }
        $_SESSION['flash_msg'] = 'Service saved successfully.';
        $_SESSION['flash_type'] = 'success';
        redirect('services.php');
    } catch (Exception $e) {
        $msg = 'Error: ' . $e->getMessage();
    }
}

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

$items = get_all('services', 'sort_order ASC, created_at DESC');
$editItem = [];
$isEdit = false;
if (!empty($_GET['edit']) && is_numeric($_GET['edit'])) {
    $fetched = get_row('services', (int)$_GET['edit']);
    if ($fetched) { $editItem = $fetched; $isEdit = true; }
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
    <h1 class="page-title">Services</h1>
    <div class="flex flex-wrap">
        <div class="search-box">
            <i class="ti ti-search" style="color:var(--text3)"></i>
            <input class="form-control" type="text" placeholder="Search..." oninput="searchTable(this)" style="width:200px">
        </div>
        <a href="services.php?new=1" class="btn btn-primary"><i class="ti ti-plus"></i> New Service</a>
    </div>
</div>

<div id="module-list" style="<?= $showForm ? 'display:none' : 'display:block' ?>">
    <div class="card">
        <?php if (count($items)): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $p): ?>
                    <tr>
                        <td style="color:var(--text3)"><?= $p['sort_order'] ?></td>
                        <td><strong style="color:var(--text)"><?= h($p['name']) ?></strong></td>
                        <td class="text-muted" style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($p['description']) ?></td>
                        <td style="text-align:right">
                            <a href="?edit=<?= $p['id'] ?>" class="btn btn-secondary btn-sm"><i class="ti ti-pencil"></i></a>
                            <button onclick="confirmDelete('services.php?delete=<?= $p['id'] ?>', '<?= h(addslashes($p['name'])) ?>')" class="btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="ti ti-settings"></i><p>No services yet.</p><a href="services.php?new=1" class="btn btn-primary mt-1">Add Service</a></div>
        <?php endif; ?>
    </div>
</div>

<div id="module-form" style="<?= $showForm ? 'display:block' : 'display:none' ?>">
    <div class="card">
        <div class="flex" style="margin-bottom:1rem">
            <button class="btn btn-secondary" onclick="showList()"><i class="ti ti-arrow-left"></i> Back</button>
        </div>
        <form method="post" action="services.php">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">
            <div class="form-group">
                <label>Service Name</label>
                <input class="form-control" name="name" value="<?= h($editItem['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="4" placeholder="Service description..."><?= h($editItem['description'] ?? '') ?></textarea>
                <div class="flex" style="margin-top:4px;gap:4px">
                    <button type="button" class="btn btn-ai btn-sm" onclick="aiServiceDesc(this)"><i class="ti ti-sparkles"></i> Write Description</button>
                </div>
            </div>
            <div class="form-group">
                <label>Features <span class="text-muted">(one per line)</span></label>
                <textarea class="form-control" name="features" rows="6" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"><?= h($editItem['features'] ?? '') ?></textarea>
                <div class="flex" style="margin-top:4px;gap:4px">
                    <button type="button" class="btn btn-ai btn-sm" onclick="aiServiceFeatures(this)"><i class="ti ti-check"></i> Generate Features</button>
                </div>
            </div>
            <div class="form-group">
                <label>Hashtags</label>
                <input class="form-control" name="hashtags" value="<?= h($editItem['hashtags'] ?? '') ?>" placeholder="#Branding #WebDesign">
                <div class="flex" style="margin-top:4px;gap:4px">
                    <button type="button" class="btn btn-ai btn-sm" onclick="aiServiceHashtags(this)"><i class="ti ti-hash"></i> Suggest Hashtags</button>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Price</label>
                    <input class="form-control" name="price" value="<?= h($editItem['price'] ?? '') ?>" placeholder="$299">
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input class="form-control" name="sort_order" type="number" value="<?= h($editItem['sort_order'] ?? 0) ?>">
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
function showList() { window.location.href = 'services.php'; }
function aiServiceDesc(btn) {
    var name = document.querySelector('input[name="name"]').value.trim();
    if (!name) { alert('Enter service name first'); return; }
    var field = document.querySelector('textarea[name="description"]');
    btn.innerHTML = '<span class="ai-spinner"></span>'; btn.disabled = true;
    fetch('../ai.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({task:'service_description', context: name})})
    .then(r=>r.json()).then(j=>{ btn.innerHTML = '<i class="ti ti-sparkles"></i> Write Description'; btn.disabled = false; if(j.success) field.value = j.data; })
    .catch(()=>{ btn.innerHTML = '<i class="ti ti-sparkles"></i> Write Description'; btn.disabled = false; });
}
function aiServiceFeatures(btn) {
    var name = document.querySelector('input[name="name"]').value.trim();
    if (!name) { alert('Enter service name first'); return; }
    var field = document.querySelector('textarea[name="features"]');
    btn.innerHTML = '<span class="ai-spinner"></span>'; btn.disabled = true;
    fetch('../ai.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({task:'service_features', context: name})})
    .then(r=>r.json()).then(j=>{ btn.innerHTML = '<i class="ti ti-check"></i> Generate Features'; btn.disabled = false; if(j.success) field.value = Array.isArray(j.data) ? j.data.join('\n') : j.data; })
    .catch(()=>{ btn.innerHTML = '<i class="ti ti-check"></i> Generate Features'; btn.disabled = false; });
}
function aiServiceHashtags(btn) {
    var name = document.querySelector('input[name="name"]').value.trim();
    if (!name) { alert('Enter service name first'); return; }
    var desc = document.querySelector('textarea[name="description"]').value.trim();
    var field = document.querySelector('input[name="hashtags"]');
    btn.innerHTML = '<span class="ai-spinner"></span>'; btn.disabled = true;
    fetch('../ai.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({task:'service_hashtags', context: name + '\n' + desc})})
    .then(r=>r.json()).then(j=>{ btn.innerHTML = '<i class="ti ti-hash"></i> Suggest Hashtags'; btn.disabled = false; if(j.success) field.value = j.data.replace(/\n/g, ' '); })
    .catch(()=>{ btn.innerHTML = '<i class="ti ti-hash"></i> Suggest Hashtags'; btn.disabled = false; });
}
</script>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>
