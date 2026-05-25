<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

if (isset($_GET['delete'])) {
    delete('faq', $_GET['delete']);
    redirect('faq.php');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    try {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'question' => trim($_POST['question'] ?? ''),
            'answer' => trim($_POST['answer'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
        if ($id) {
            update('faq', $id, $data);
        } else {
            insert('faq', $data);
        }
        $_SESSION['flash_msg'] = 'FAQ saved successfully.';
        $_SESSION['flash_type'] = 'success';
        redirect('faq.php');
    } catch (Exception $e) {
        $msg = 'Error: ' . $e->getMessage();
    }
}

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

$records = get_all('faq', 'sort_order ASC, created_at DESC');
$editItem = [
    'id'         => null,
    'question'   => '',
    'answer'     => '',
    'category'   => '',
    'sort_order' => 0,
    'is_active'  => 1,
];
$isEdit = false;
if (!empty($_GET['edit']) && is_numeric($_GET['edit'])) {
    $fetched = get_row('faq', (int)$_GET['edit']);
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
    <h1 class="page-title">FAQ</h1>
    <div class="flex flex-wrap">
        <a href="faq.php?new=1" class="btn btn-primary"><i class="ti ti-plus"></i> New FAQ</a>
    </div>
</div>

<div id="module-list" style="<?= $showForm ? 'display:none' : 'display:block' ?>">
    <div class="card">
        <?php if (count($records)): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Question</th><th>Category</th><th>Answer Preview</th><th style="text-align:right">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $p): ?>
                    <tr>
                        <td><strong style="color:var(--text)"><?= h($p['question'] ?? '') ?></strong></td>
                        <td><span class="text-muted"><?= h($p['category'] ?? '') ?></span></td>
                        <td class="text-muted" style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($p['answer'] ?? '') ?></td>
                        <td style="text-align:right">
                            <a href="?edit=<?= $p['id'] ?? 0 ?>" class="btn btn-secondary btn-sm"><i class="ti ti-pencil"></i></a>
                            <button onclick="confirmDelete('faq.php?delete=<?= $p['id'] ?? 0 ?>', 'FAQ: <?= h(addslashes($p['question'] ?? '')) ?>')" class="btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="ti ti-help"></i><p>No FAQs yet.</p><a href="faq.php?new=1" class="btn btn-primary mt-1">Add FAQ</a></div>
        <?php endif; ?>
    </div>
</div>

<div id="module-form" style="<?= $showForm ? 'display:block' : 'display:none' ?>">
    <div class="card">
        <div class="flex" style="margin-bottom:1rem">
            <button class="btn btn-secondary" onclick="showList()"><i class="ti ti-arrow-left"></i> Back</button>
        </div>
        <form method="post" action="faq.php">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Question</label>
                    <input class="form-control" name="question" value="<?= h($editItem['question'] ?? '') ?>" required placeholder="What is...?">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input class="form-control" name="category" value="<?= h($editItem['category'] ?? '') ?>" placeholder="General, Pricing, etc.">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Sort Order</label>
                    <input class="form-control" name="sort_order" type="number" value="<?= h($editItem['sort_order'] ?? 0) ?>">
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end;gap:8px">
                    <label style="white-space:nowrap">AI Generate Q&A</label>
                    <button type="button" class="btn btn-ai btn-sm" onclick="aiFaqGenerate(this)"><i class="ti ti-sparkles"></i> Generate</button>
                </div>
            </div>
            <div class="form-group">
                <label>Answer</label>
                <textarea class="form-control" name="answer" rows="5" placeholder="Answer..."><?= h($editItem['answer'] ?? '') ?></textarea>
                <div class="flex" style="margin-top:4px;gap:4px">
                    <button type="button" class="btn btn-ai btn-sm" onclick="aiFaqImprove(this)"><i class="ti ti-writing"></i> Improve Answer</button>
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
function showList() { window.location.href = 'faq.php'; }
function aiFaqGenerate(btn) {
    var topic = prompt('Enter a topic for the FAQ:');
    if (!topic) return;
    var qField = document.querySelector('input[name="question"]');
    var aField = document.querySelector('textarea[name="answer"]');
    btn.innerHTML = '<span class="ai-spinner"></span>'; btn.disabled = true;
    fetch('../ai.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({task:'faq_generate', context: topic})})
    .then(r=>r.json()).then(j=>{
        btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate'; btn.disabled = false;
        if (!j.success) return;
        var d = j.data;
        if (typeof d === 'string') { try { d = JSON.parse(d); } catch(e) { alert('Parse error'); return; } }
        if (d.question) qField.value = d.question;
        if (d.answer) aField.value = d.answer;
    }).catch(()=>{ btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate'; btn.disabled = false; });
}
function aiFaqImprove(btn) {
    var field = document.querySelector('textarea[name="answer"]');
    var text = field.value.trim();
    if (!text) { alert('Enter an answer first'); return; }
    btn.innerHTML = '<span class="ai-spinner"></span>'; btn.disabled = true;
    fetch('../ai.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({task:'faq_improve', context: text})})
    .then(r=>r.json()).then(j=>{ btn.innerHTML = '<i class="ti ti-writing"></i> Improve Answer'; btn.disabled = false; if(j.success) field.value = j.data; })
    .catch(()=>{ btn.innerHTML = '<i class="ti ti-writing"></i> Improve Answer'; btn.disabled = false; });
}
</script>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>
