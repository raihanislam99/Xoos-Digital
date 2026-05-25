<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

if (isset($_GET['delete'])) {
    delete('brands', $_GET['delete']);
    redirect('brands.php');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    try {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'logo_url' => trim($_POST['logo_url'] ?? ''),
            'industry' => trim($_POST['industry'] ?? ''),
            'country' => trim($_POST['country'] ?? ''),
            'service' => trim($_POST['service'] ?? ''),
            'bloom_color' => trim($_POST['bloom_color'] ?? 'rgba(0,0,0,0.18)'),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
        if ($id) {
            update('brands', $id, $data);
        } else {
            insert('brands', $data);
        }
        $_SESSION['flash_msg'] = 'Brand saved successfully.';
        $_SESSION['flash_type'] = 'success';
        redirect('brands.php');
    } catch (Exception $e) {
        $msg = 'Error: ' . $e->getMessage();
    }
}

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

$records = get_all('brands', 'sort_order ASC, created_at DESC');
$editItem = [
    'id'          => null,
    'name'        => '',
    'logo_url'    => '',
    'industry'    => '',
    'country'     => '',
    'service'     => '',
    'bloom_color' => 'rgba(0,0,0,0.18)',
    'sort_order'  => 0,
    'is_active'   => 1,
];
$isEdit = false;
if (!empty($_GET['edit']) && is_numeric($_GET['edit'])) {
    $fetched = get_row('brands', (int)$_GET['edit']);
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
    <h1 class="page-title">Brands</h1>
    <div class="flex flex-wrap">
        <div class="search-box">
            <i class="ti ti-search" style="color:var(--text3)"></i>
            <input class="form-control" type="text" placeholder="Search brands..." oninput="searchTable(this)" style="width:200px">
        </div>
        <a href="brands.php?new=1" class="btn btn-primary"><i class="ti ti-plus"></i> New Brand</a>
    </div>
</div>

<div id="module-list" style="<?= $showForm ? 'display:none' : 'display:block' ?>">
    <div class="card">
        <?php if (count($records)): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Logo</th>
                        <th>Name</th>
                        <th>Industry</th>
                        <th>Service</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $p): ?>
                    <tr>
                        <td style="color:var(--text3)"><?= $p['sort_order'] ?? 0 ?></td>
                        <td><?php if ($p['logo_url'] ?? ''): ?><img src="../<?= h($p['logo_url'] ?? '') ?>" style="width:40px;height:40px;object-fit:contain;background:white;border-radius:4px;padding:4px"><?php else: ?><span style="color:var(--text3)">—</span><?php endif; ?></td>
                        <td><strong style="color:var(--text)"><?= h($p['name'] ?? '') ?></strong></td>
                        <td class="text-muted"><?= h($p['industry'] ?? '') ?></td>
                        <td class="text-muted"><?= h($p['service'] ?? '') ?></td>
                        <td style="text-align:right">
                            <a href="?edit=<?= $p['id'] ?? 0 ?>" class="btn btn-secondary btn-sm"><i class="ti ti-pencil"></i></a>
                            <button onclick="confirmDelete('brands.php?delete=<?= $p['id'] ?? 0 ?>', '<?= h(addslashes($p['name'] ?? '')) ?>')" class="btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="ti ti-building"></i><p>No brands yet.</p><a href="brands.php?new=1" class="btn btn-primary mt-1">Add Brand</a></div>
        <?php endif; ?>
    </div>
</div>

<div id="module-form" style="<?= $showForm ? 'display:block' : 'display:none' ?>">
    <div class="card">
        <div class="flex" style="margin-bottom:1rem">
            <button class="btn btn-secondary" onclick="showList()"><i class="ti ti-arrow-left"></i> Back</button>
        </div>
        <form method="post" action="brands.php">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">
            <div class="form-group">
                <label>Brand Name</label>
                <input class="form-control" name="name" value="<?= h($editItem['name'] ?? '') ?>" required placeholder="e.g. Bright Hash">
            </div>
            <div class="form-group">
                <label>Logo URL</label>
                <div class="flex" style="gap:8px;flex-wrap:wrap">
                    <input class="form-control" name="logo_url" value="<?= h($editItem['logo_url'] ?? '') ?>" placeholder="https://..." style="flex:1;min-width:200px" data-base="<?= BASE_URL ?>/" oninput="showImagePreview(this,'preview-brand')" onchange="showImagePreview(this,'preview-brand')">
                    <input type="file" id="logo-upload" accept="image/*" style="display:none" onchange="uploadLogo(this);handleFilePreview(this,'preview-brand')">
                    <button type="button" class="btn btn-ai btn-sm" onclick="document.getElementById('logo-upload').click()"><i class="ti ti-upload"></i> Upload</button>
                </div>
                <div id="preview-brand"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Industry</label>
                    <input class="form-control" name="industry" value="<?= h($editItem['industry'] ?? '') ?>" placeholder="Technology">
                </div>
                <div class="form-group">
                    <label>Country</label>
                    <input class="form-control" name="country" value="<?= h($editItem['country'] ?? '') ?>" placeholder="🇧🇩 DHAKA">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Service</label>
                    <input class="form-control" name="service" value="<?= h($editItem['service'] ?? '') ?>" placeholder="BRANDING">
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input class="form-control" name="sort_order" type="number" value="<?= h($editItem['sort_order'] ?? 0) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Bloom Color <span class="text-muted">(CSS rgba value for card glow)</span></label>
                <input class="form-control" name="bloom_color" value="<?= h($editItem['bloom_color'] ?? 'rgba(0,0,0,0.18)') ?>" placeholder="rgba(0, 120, 255, 0.18)">
            </div>
            <div class="form-actions">
                <button type="submit" name="action" value="save" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
                <button type="button" class="btn btn-secondary" onclick="showList()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showList() { window.location.href = 'brands.php'; }
function uploadLogo(input) {
    if (!input.files || !input.files[0]) return;
    var fd = new FormData();
    fd.append('file', input.files[0]);
    fetch('../upload.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.success) document.querySelector('input[name="logo_url"]').value = j.url;
        else alert('Upload failed: ' + (j.error || 'Unknown error'));
    })
    .catch(function() { alert('Upload failed'); });
}
</script>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>
