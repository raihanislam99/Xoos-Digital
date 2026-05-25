<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

if (isset($_GET['delete'])) {
    delete('portfolio', $_GET['delete']);
    redirect('portfolio.php');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    try {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'project_name' => trim($_POST['project_name'] ?? ''),
            'client' => trim($_POST['client'] ?? ''),
            'service' => trim($_POST['service'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'image_url' => trim($_POST['image_url'] ?? ''),
            'link' => trim($_POST['link'] ?? ''),
        ];
        if ($id) {
            update('portfolio', $id, $data);
        } else {
            insert('portfolio', $data);
        }
        $_SESSION['flash_msg'] = 'Project saved successfully.';
        $_SESSION['flash_type'] = 'success';
        redirect('portfolio.php');
    } catch (Exception $e) {
        $msg = 'Error: ' . $e->getMessage();
    }
}

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

$records = get_all('portfolio', 'created_at DESC');
$editItem = [
    'id'           => null,
    'project_name' => '',
    'client'       => '',
    'service'      => '',
    'description'  => '',
    'image_url'    => '',
    'link'         => '',
    'sort_order'   => 0,
    'is_active'    => 1,
];
$isEdit = false;
if (!empty($_GET['edit']) && is_numeric($_GET['edit'])) {
    $fetched = get_row('portfolio', (int)$_GET['edit']);
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
    <h1 class="page-title">Portfolio</h1>
    <div class="flex flex-wrap">
        <a href="portfolio.php?new=1" class="btn btn-primary"><i class="ti ti-plus"></i> New Project</a>
    </div>
</div>

<div id="module-list" style="<?= $showForm ? 'display:none' : 'display:block' ?>">
    <div class="card">
        <?php if (count($records)): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Project</th><th>Client</th><th>Service</th><th>Description</th><th style="text-align:right">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $p): ?>
                    <tr>
                        <td><strong style="color:var(--text)"><?= h($p['project_name'] ?? '') ?></strong></td>
                        <td class="text-muted"><?= h($p['client'] ?? '') ?></td>
                        <td><span class="status-badge status-published"><?= h($p['service'] ?? '') ?></span></td>
                        <td class="text-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($p['description'] ?? '') ?></td>
                        <td style="text-align:right">
                            <a href="?edit=<?= $p['id'] ?? 0 ?>" class="btn btn-secondary btn-sm"><i class="ti ti-pencil"></i></a>
                            <button onclick="confirmDelete('portfolio.php?delete=<?= $p['id'] ?? 0 ?>', '<?= h(addslashes($p['project_name'] ?? '')) ?>')" class="btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="ti ti-briefcase"></i><p>No portfolio items yet.</p><a href="portfolio.php?new=1" class="btn btn-primary mt-1">Add Project</a></div>
        <?php endif; ?>
    </div>
</div>

<div id="module-form" style="<?= $showForm ? 'display:block' : 'display:none' ?>">
    <div class="card">
        <div class="flex" style="margin-bottom:1rem">
            <button class="btn btn-secondary" onclick="showList()"><i class="ti ti-arrow-left"></i> Back</button>
        </div>
        <form method="post" action="portfolio.php">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Project Name</label>
                    <input class="form-control" name="project_name" value="<?= h($editItem['project_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Client</label>
                    <input class="form-control" name="client" value="<?= h($editItem['client'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Service</label>
                    <input class="form-control" name="service" value="<?= h($editItem['service'] ?? '') ?>" placeholder="Branding, Web Dev, etc.">
                </div>
                <div class="form-group">
                    <label>Project Link</label>
                    <input class="form-control" name="link" value="<?= h($editItem['link'] ?? '') ?>" placeholder="https://...">
                </div>
            </div>
            <div class="form-group">
                <label>Image</label>
                <div class="flex" style="gap:8px;flex-wrap:wrap">
                    <input class="form-control" name="image_url" value="<?= h($editItem['image_url'] ?? '') ?>" placeholder="https://..." style="flex:1;min-width:200px" oninput="showImagePreview(this,'preview-pf')" onchange="showImagePreview(this,'preview-pf')">
                    <input type="file" id="pf-img-upload" accept="image/*" style="display:none" onchange="portfolioUploadImage(this);handleFilePreview(this,'preview-pf')">
                    <button type="button" class="btn btn-ai btn-sm" onclick="document.getElementById('pf-img-upload').click()"><i class="ti ti-upload"></i> Upload</button>
                    <button type="button" class="btn btn-ai btn-sm" onclick="aiPortfolioImagePrompt()"><i class="ti ti-sparkles"></i> Generate Prompt</button>
                </div>
                <div id="preview-pf"></div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="5" placeholder="Project description..."><?= h($editItem['description'] ?? '') ?></textarea>
                <div class="flex" style="margin-top:4px;gap:4px">
                    <button type="button" class="btn btn-ai btn-sm" onclick="aiPortfolioDesc(this)"><i class="ti ti-sparkles"></i> Write Description</button>
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
function showList() { window.location.href = 'portfolio.php'; }
function aiPortfolioDesc(btn) {
    var name = document.querySelector('input[name="project_name"]').value.trim();
    var client = document.querySelector('input[name="client"]').value.trim();
    var service = document.querySelector('input[name="service"]').value.trim();
    if (!name) { alert('Enter project name first'); return; }
    var context = 'Project: ' + name + (client ? ', Client: ' + client : '') + (service ? ', Service: ' + service : '');
    var field = document.querySelector('textarea[name="description"]');
    btn.innerHTML = '<span class="ai-spinner"></span>'; btn.disabled = true;
    fetch('../ai.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({task:'portfolio_description', context: context})})
    .then(r=>r.json()).then(j=>{ btn.innerHTML = '<i class="ti ti-sparkles"></i> Write Description'; btn.disabled = false; if(j.success) field.value = j.data; })
    .catch(()=>{ btn.innerHTML = '<i class="ti ti-sparkles"></i> Write Description'; btn.disabled = false; });
}

function portfolioUploadImage(input) {
    if (!input.files || !input.files[0]) return;
    var fd = new FormData();
    fd.append('file', input.files[0]);
    fetch('../upload.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.success) document.querySelector('input[name="image_url"]').value = j.url;
        else alert('Upload failed: ' + (j.error || 'Unknown error'));
    })
    .catch(function() { alert('Upload failed'); });
}

function aiPortfolioImagePrompt() {
    var name = document.querySelector('input[name="project_name"]').value.trim();
    var client = document.querySelector('input[name="client"]').value.trim();
    var service = document.querySelector('input[name="service"]').value.trim();
    var ctx = (name ? 'Project: ' + name : '') + (client ? ', Client: ' + client : '') + (service ? ', Service: ' + service : '');
    if (!ctx.trim()) { alert('Enter project details first'); return; }
    fetch('../ai.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({task:'image_prompt', context: ctx})
    }).then(function(r) { return r.json(); }).then(function(j) {
        if (!j.success) return;
        var prompt = j.data;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(prompt).then(function() { alert('Prompt copied to clipboard!'); });
        } else {
            prompt('Image prompt:\n\n' + prompt);
        }
    });
}
</script>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>
