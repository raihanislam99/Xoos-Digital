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
            'slug' => trim($_POST['slug'] ?? ''),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'challenge' => trim($_POST['challenge'] ?? ''),
            'solution' => trim($_POST['solution'] ?? ''),
            'results' => trim($_POST['results'] ?? ''),
            'client_testimonial' => trim($_POST['client_testimonial'] ?? ''),
            'technologies' => trim($_POST['technologies'] ?? ''),
            'video_url' => trim($_POST['video_url'] ?? ''),
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => !empty($_POST['is_active']) ? true : false,
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
        error_log('Portfolio save failed: ' . $e->getMessage());
    }
}

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

$records = get_all('portfolio', 'sort_order ASC, created_at DESC');
$categories = [];
try {
    $categories = get_all('portfolio_categories', 'sort_order ASC, name ASC');
} catch (Throwable $e) {
    $categories = [];
}
$editItem = [
    'id'                => null,
    'project_name'      => '',
    'client'            => '',
    'service'           => '',
    'description'       => '',
    'image_url'         => '',
    'link'              => '',
    'slug'              => '',
    'category_id'       => 0,
    'challenge'         => '',
    'solution'          => '',
    'results'           => '',
    'client_testimonial'=> '',
    'technologies'      => '',
    'video_url'         => '',
    'meta_title'        => '',
    'meta_description'  => '',
    'sort_order'        => 0,
    'is_active'         => 1,
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
                    <tr><th>Order</th><th>Project</th><th>Client</th><th>Service</th><th>Status</th><th style="text-align:right">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $p): ?>
                    <tr>
                        <td class="text-muted" style="font-size:0.8rem"><?= (int)($p['sort_order'] ?? 0) ?></td>
                        <td><strong style="color:var(--text)"><?= h($p['project_name'] ?? '') ?></strong></td>
                        <td class="text-muted"><?= h($p['client'] ?? '') ?></td>
                        <td><span class="status-badge status-published"><?= h($p['service'] ?? '') ?></span></td>
                        <td><span class="status-badge <?= !empty($p['is_active']) ? 'status-published' : 'status-draft' ?>"><?= !empty($p['is_active']) ? 'Active' : 'Hidden' ?></span></td>
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
                    <input class="form-control" name="project_name" id="pf-name" value="<?= h($editItem['project_name'] ?? '') ?>" required oninput="autoSlug(this)">
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
                    <label>Category</label>
                    <select class="form-control" name="category_id">
                        <option value="0">None</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($editItem['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Slug</label>
                    <div class="flex" style="gap:4px">
                        <input class="form-control" name="slug" id="pf-slug" value="<?= h($editItem['slug'] ?? '') ?>" placeholder="Auto-generated from name" style="flex:1">
                        <button type="button" class="btn btn-ai btn-sm" onclick="document.getElementById('pf-slug').value = slugify(document.getElementById('pf-name').value)"><i class="ti ti-refresh"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input class="form-control" name="sort_order" type="number" value="<?= (int)($editItem['sort_order'] ?? 0) ?>" placeholder="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Project Link</label>
                    <input class="form-control" name="link" value="<?= h($editItem['link'] ?? '') ?>" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label>Video URL</label>
                    <input class="form-control" name="video_url" value="<?= h($editItem['video_url'] ?? '') ?>" placeholder="https://youtube.com/...">
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
                <label>Technologies / Tools Used</label>
                <input class="form-control" name="technologies" value="<?= h($editItem['technologies'] ?? '') ?>" placeholder="React, Node.js, Figma, Google Ads...">
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px">
                    <input type="checkbox" name="is_active" value="1" <?= !empty($editItem['is_active']) ? 'checked' : '' ?>>
                    Active / Published
                </label>
            </div>

            <hr style="border-color:#2a2d3a;margin:1.5rem 0">

            <h3 style="color:var(--accent);margin-bottom:1rem;font-size:0.9rem;text-transform:uppercase;letter-spacing:0.08em">Case Study Details</h3>
            <div class="form-group">
                <label>Short Description <span style="color:#6b7280;font-weight:400">(shown in grid card)</span></label>
                <textarea class="form-control" name="description" rows="3" placeholder="Brief project summary..."><?= h($editItem['description'] ?? '') ?></textarea>
                <div class="flex" style="margin-top:4px;gap:4px">
                    <button type="button" class="btn btn-ai btn-sm" onclick="aiPortfolioDesc(this)"><i class="ti ti-sparkles"></i> Write Description</button>
                </div>
            </div>
            <div class="form-group">
                <label>The Challenge</label>
                <textarea class="form-control" name="challenge" rows="4" placeholder="What problem did the client have?"><?= h($editItem['challenge'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>The Solution</label>
                <textarea class="form-control" name="solution" rows="6" placeholder="What did you deliver and how?"><?= h($editItem['solution'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>The Results</label>
                <textarea class="form-control" name="results" rows="4" placeholder="Measurable outcomes (traffic, conversions, etc.)"><?= h($editItem['results'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Client Testimonial</label>
                <textarea class="form-control" name="client_testimonial" rows="3" placeholder="Client quote..."><?= h($editItem['client_testimonial'] ?? '') ?></textarea>
            </div>
            <div class="flex" style="gap:4px;margin-bottom:1.5rem">
                <button type="button" class="btn btn-ai btn-sm" onclick="aiPortfolioCaseStudy()"><i class="ti ti-sparkles"></i> Generate Full Case Study</button>
            </div>

            <hr style="border-color:#2a2d3a;margin:1.5rem 0">

            <h3 style="color:var(--accent);margin-bottom:1rem;font-size:0.9rem;text-transform:uppercase;letter-spacing:0.08em">SEO Settings</h3>
            <div class="form-group">
                <label>Meta Title <span style="color:#6b7280;font-weight:400">(max 60 chars)</span></label>
                <input class="form-control" name="meta_title" value="<?= h($editItem['meta_title'] ?? '') ?>" placeholder="SEO title..." maxlength="60">
            </div>
            <div class="form-group">
                <label>Meta Description <span style="color:#6b7280;font-weight:400">(max 160 chars)</span></label>
                <textarea class="form-control" name="meta_description" rows="2" placeholder="SEO meta description..." maxlength="160"><?= h($editItem['meta_description'] ?? '') ?></textarea>
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

function aiPortfolioCaseStudy() {
    var name = document.querySelector('input[name="project_name"]').value.trim();
    var client = document.querySelector('input[name="client"]').value.trim();
    var service = document.querySelector('input[name="service"]').value.trim();
    var desc = document.querySelector('textarea[name="description"]').value.trim();
    if (!name) { alert('Enter project name first'); return; }
    var context = JSON.stringify({project_name: name, client: client, service: service, description: desc});
    fetch('../ai.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({task:'portfolio_case_study', context: context})})
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (!j.success) return;
        var d = j.data;
        if (d.challenge) document.querySelector('textarea[name="challenge"]').value = d.challenge;
        if (d.solution) document.querySelector('textarea[name="solution"]').value = d.solution;
        if (d.results) document.querySelector('textarea[name="results"]').value = d.results;
        if (d.testimonial) document.querySelector('textarea[name="client_testimonial"]').value = d.testimonial;
    });
}

function slugify(text) {
    return text.toString().toLowerCase().trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

function autoSlug(input) {
    var slugField = document.getElementById('pf-slug');
    if (!slugField.value || slugField.dataset.auto !== 'false') {
        slugField.value = slugify(input.value);
    }
}
</script>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>
