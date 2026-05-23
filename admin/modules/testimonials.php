<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

if (isset($_GET['delete'])) {
    delete('testimonials', $_GET['delete']);
    redirect('testimonials.php');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    try {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'client_name' => trim($_POST['client_name'] ?? ''),
            'quote' => trim($_POST['quote'] ?? ''),
            'rating' => (int)($_POST['rating'] ?? 5),
            'service_used' => trim($_POST['service_used'] ?? ''),
            'client_image' => trim($_POST['client_image'] ?? ''),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'client_country' => trim($_POST['client_country'] ?? ''),
            'country_flag' => trim($_POST['country_flag'] ?? ''),
            'platform' => trim($_POST['platform'] ?? ''),
            'avatar_gradient' => trim($_POST['avatar_gradient'] ?? ''),
            'avatar_letter' => trim($_POST['avatar_letter'] ?? ''),
        ];
        if ($id) {
            update('testimonials', $id, $data);
        } else {
            insert('testimonials', $data);
        }
        $_SESSION['flash_msg'] = 'Testimonial saved successfully.';
        $_SESSION['flash_type'] = 'success';
        redirect('testimonials.php');
    } catch (Exception $e) {
        $msg = 'Error: ' . $e->getMessage();
    }
}

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

$items = get_all('testimonials', 'sort_order ASC, created_at DESC');
$editItem = [];
$isEdit = false;
if (!empty($_GET['edit']) && is_numeric($_GET['edit'])) {
    $fetched = get_row('testimonials', (int)$_GET['edit']);
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
    <h1 class="page-title">Testimonials</h1>
    <div class="flex flex-wrap">
        <a href="testimonials.php?new=1" class="btn btn-primary"><i class="ti ti-plus"></i> New Testimonial</a>
    </div>
</div>

<div id="module-list" style="<?= $showForm ? 'display:none' : 'display:block' ?>">
    <div class="card">
        <?php if (count($items)): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Client</th><th>Service</th><th>Rating</th><th>Quote Preview</th><th style="text-align:right">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $p): ?>
                    <tr>
                        <td><strong style="color:var(--text)"><?= h($p['client_name']) ?></strong><?php if ($p['client_image']): ?> <span style="color:var(--accent);font-size:0.65rem">🖼</span><?php endif; ?></td>
                        <td class="text-muted"><?= h($p['service_used']) ?></td>
                        <td><?= str_repeat('⭐', $p['rating']) ?></td>
                        <td class="text-muted" style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($p['quote']) ?></td>
                        <td style="text-align:right">
                            <a href="?edit=<?= $p['id'] ?>" class="btn btn-secondary btn-sm"><i class="ti ti-pencil"></i></a>
                            <button onclick="confirmDelete('testimonials.php?delete=<?= $p['id'] ?>', '<?= h(addslashes($p['client_name'])) ?>')" class="btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="ti ti-quote"></i><p>No testimonials yet.</p><a href="testimonials.php?new=1" class="btn btn-primary mt-1">Add Testimonial</a></div>
        <?php endif; ?>
    </div>
</div>

<div id="module-form" style="<?= $showForm ? 'display:block' : 'display:none' ?>">
    <div class="card">
        <div class="flex" style="margin-bottom:1rem">
            <button class="btn btn-secondary" onclick="showList()"><i class="ti ti-arrow-left"></i> Back</button>
        </div>
        <form method="post" action="testimonials.php">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Client Name</label>
                    <input class="form-control" name="client_name" value="<?= h($editItem['client_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Service Used</label>
                    <input class="form-control" name="service_used" value="<?= h($editItem['service_used'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Client Image</label>
                <div class="flex" style="gap:8px;flex-wrap:wrap">
                    <input class="form-control" name="client_image" value="<?= h($editItem['client_image'] ?? '') ?>" placeholder="https://..." style="flex:1;min-width:200px" oninput="showImagePreview(this,'preview-testimonial')" onchange="showImagePreview(this,'preview-testimonial')">
                    <input type="file" id="img-upload" accept="image/*" style="display:none" onchange="uploadImg(this);handleFilePreview(this,'preview-testimonial')">
                    <button type="button" class="btn btn-ai btn-sm" onclick="document.getElementById('img-upload').click()"><i class="ti ti-upload"></i> Upload</button>
                </div>
                <div id="preview-testimonial"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Rating</label>
                    <select class="form-control" name="rating">
                        <?php for ($i=1; $i<=5; $i++): ?>
                        <option value="<?= $i ?>" <?= ($editItem['rating'] ?? 5) == $i ? 'selected' : '' ?>><?= $i ?> ⭐</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input class="form-control" name="sort_order" type="number" value="<?= h($editItem['sort_order'] ?? 0) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Quote</label>
                <textarea class="form-control" name="quote" rows="5" placeholder="Client testimonial..."><?= h($editItem['quote'] ?? '') ?></textarea>
                <div class="flex" style="margin-top:4px;gap:4px">
                    <button type="button" class="btn btn-ai btn-sm" onclick="aiImproveQuote(this)"><i class="ti ti-sparkles"></i> Improve Quote</button>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Client Country</label>
                    <input class="form-control" name="client_country" placeholder="Bangladesh" value="<?= h($editItem['client_country'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Country Flag</label>
                    <input class="form-control" name="country_flag" placeholder="🇧🇩" maxlength="10" value="<?= h($editItem['country_flag'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Platform</label>
                    <select class="form-control" name="platform">
                        <option value="">Select platform...</option>
                        <option value="Fiverr" <?= ($editItem['platform']??'')=='Fiverr' ? 'selected':'' ?>>Fiverr</option>
                        <option value="Upwork" <?= ($editItem['platform']??'')=='Upwork' ? 'selected':'' ?>>Upwork</option>
                        <option value="Direct" <?= ($editItem['platform']??'')=='Direct' ? 'selected':'' ?>>Direct</option>
                        <option value="Google" <?= ($editItem['platform']??'')=='Google' ? 'selected':'' ?>>Google</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Avatar Letter</label>
                    <input type="text" name="avatar_letter" id="avatarLetter" maxlength="2" placeholder="R" value="<?= h($editItem['avatar_letter'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Avatar Gradient</label>
                <div class="gradient-picker" style="display:flex;gap:8px;flex-wrap:wrap">
                    <?php $gradients = [
                        'linear-gradient(135deg,#1a1a3e,#3d2a7a)',
                        'linear-gradient(135deg,#1a3a20,#2a7a3d)',
                        'linear-gradient(135deg,#3a1a1a,#7a2a2a)',
                        'linear-gradient(135deg,#1a2d3a,#2a5a7a)',
                        'linear-gradient(135deg,#2d1a3a,#5a2a7a)',
                        'linear-gradient(135deg,#1a3a2d,#2a7a5a)',
                    ]; ?>
                    <?php foreach ($gradients as $g): ?>
                        <div class="grad-swatch" data-value="<?= $g ?>" style="background:<?= $g ?>;width:32px;height:32px;border-radius:50%;cursor:pointer;border:2px solid transparent" onclick="selectGradient(this)"></div>
                    <?php endforeach; ?>
                    <input type="hidden" name="avatar_gradient" id="avatarGradient" value="<?= h($editItem['avatar_gradient'] ?? $gradients[0]) ?>">
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
function showList() { window.location.href = 'testimonials.php'; }
function selectGradient(el) {
    document.querySelectorAll('.grad-swatch').forEach(function(s) { s.style.borderColor = 'transparent'; });
    el.style.borderColor = '#CCFF00';
    document.getElementById('avatarGradient').value = el.dataset.value;
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.grad-swatch').forEach(function(s) {
        if (s.dataset.value === document.getElementById('avatarGradient').value) {
            s.style.borderColor = '#CCFF00';
        }
    });
    var nameInput = document.querySelector('[name="client_name"]');
    var letterInput = document.getElementById('avatarLetter');
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            if (letterInput && !letterInput.value) {
                letterInput.value = this.value.charAt(0).toUpperCase();
            }
        });
    }
});

function uploadImg(input) {
    if (!input.files || !input.files[0]) return;
    var fd = new FormData();
    fd.append('file', input.files[0]);
    fetch('../upload.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.success) document.querySelector('input[name="client_image"]').value = j.url;
        else alert('Upload failed: ' + (j.error || 'Unknown error'));
    })
    .catch(function() { alert('Upload failed'); });
}
function aiImproveQuote(btn) {
    var field = document.querySelector('textarea[name="quote"]');
    var text = field.value.trim();
    if (!text) { alert('Enter a quote first'); return; }
    btn.innerHTML = '<span class="ai-spinner"></span>'; btn.disabled = true;
    fetch('../ai.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({task:'testimonial_improve', context: text})})
    .then(r=>r.json()).then(j=>{ btn.innerHTML = '<i class="ti ti-sparkles"></i> Improve Quote'; btn.disabled = false; if(j.success) field.value = j.data; })
    .catch(()=>{ btn.innerHTML = '<i class="ti ti-sparkles"></i> Improve Quote'; btn.disabled = false; });
}
</script>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>
