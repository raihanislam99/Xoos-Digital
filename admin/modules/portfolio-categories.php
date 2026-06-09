<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

if (isset($_GET['delete'])) {
    delete('portfolio_categories', $_GET['delete']);
    redirect('portfolio-categories.php');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    try {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (!$slug) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $slug = trim($slug, '-');
        }
        $data = [
            'name' => $name,
            'slug' => $slug,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
        if ($id) {
            update('portfolio_categories', $id, $data);
        } else {
            insert('portfolio_categories', $data);
        }
        $_SESSION['flash_msg'] = 'Category saved.';
        $_SESSION['flash_type'] = 'success';
        redirect('portfolio-categories.php');
    } catch (Exception $e) {
        $msg = 'Error: ' . $e->getMessage();
    }
}

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

$records = get_all('portfolio_categories', 'sort_order ASC, name ASC');
$editItem = ['id' => null, 'name' => '', 'slug' => '', 'sort_order' => 0];
$isEdit = false;
if (!empty($_GET['edit']) && is_numeric($_GET['edit'])) {
    $fetched = get_row('portfolio_categories', (int)$_GET['edit']);
    if ($fetched) { $editItem = array_merge($editItem, $fetched); $isEdit = true; }
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
    <h1 class="page-title">Portfolio Categories</h1>
    <div class="flex flex-wrap">
        <a href="portfolio-categories.php?new=1" class="btn btn-primary"><i class="ti ti-plus"></i> New Category</a>
    </div>
</div>

<div id="module-list" style="<?= $showForm ? 'display:none' : 'display:block' ?>">
    <div class="card">
        <?php if (count($records)): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Order</th><th>Name</th><th>Slug</th><th style="text-align:right">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $c): ?>
                    <tr>
                        <td class="text-muted"><?= (int)($c['sort_order'] ?? 0) ?></td>
                        <td><strong style="color:var(--text)"><?= h($c['name'] ?? '') ?></strong></td>
                        <td class="text-muted"><?= h($c['slug'] ?? '') ?></td>
                        <td style="text-align:right">
                            <a href="?edit=<?= $c['id'] ?? 0 ?>" class="btn btn-secondary btn-sm"><i class="ti ti-pencil"></i></a>
                            <button onclick="confirmDelete('portfolio-categories.php?delete=<?= $c['id'] ?? 0 ?>', '<?= h(addslashes($c['name'] ?? '')) ?>')" class="btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="ti ti-tags"></i><p>No categories yet.</p><a href="portfolio-categories.php?new=1" class="btn btn-primary mt-1">Add Category</a></div>
        <?php endif; ?>
    </div>
</div>

<div id="module-form" style="<?= $showForm ? 'display:block' : 'display:none' ?>">
    <div class="card">
        <div class="flex" style="margin-bottom:1rem">
            <button class="btn btn-secondary" onclick="showList()"><i class="ti ti-arrow-left"></i> Back</button>
        </div>
        <form method="post" action="portfolio-categories.php">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Name</label>
                    <input class="form-control" name="name" id="cat-name" value="<?= h($editItem['name'] ?? '') ?>" required oninput="autoSlug(this)">
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input class="form-control" name="slug" id="cat-slug" value="<?= h($editItem['slug'] ?? '') ?>" placeholder="Auto-generated">
                </div>
            </div>
            <div class="form-group">
                <label>Sort Order</label>
                <input class="form-control" name="sort_order" type="number" value="<?= (int)($editItem['sort_order'] ?? 0) ?>">
            </div>
            <div class="form-actions">
                <button type="submit" name="action" value="save" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
                <button type="button" class="btn btn-secondary" onclick="showList()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showList() { window.location.href = 'portfolio-categories.php'; }
function slugify(text) {
    return text.toString().toLowerCase().trim()
        .replace(/[^\w\s-]/g, '').replace(/[\s_]+/g, '-')
        .replace(/-+/g, '-').replace(/^-|-$/g, '');
}
function autoSlug(input) {
    var slug = document.getElementById('cat-slug');
    if (!slug.value) { slug.value = slugify(input.value); }
}
</script>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>
