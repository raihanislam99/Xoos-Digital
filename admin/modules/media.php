<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

// Handle delete by DB ID
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $file = db_rows("SELECT filepath FROM media_files WHERE id = ?", [$id]);
    if ($file) {
        $path = __DIR__ . '/../../' . ltrim($file[0]['filepath'], '/');
        if (file_exists($path)) @unlink($path);
        db_delete('media_files', 'id = ?', [$id]);
    }
    $_SESSION['flash_msg'] = 'File deleted.';
    $_SESSION['flash_type'] = 'success';
    redirect('media.php');
}

// Handle delete by filesystem path
if (isset($_GET['delete_path'])) {
    $filepath = base64_decode($_GET['delete_path']);
    $fullPath = realpath($root . '/' . ltrim($filepath, '/'));
    if ($fullPath && str_starts_with($fullPath, $root) && file_exists($fullPath)) {
        @unlink($fullPath);
        db_delete('media_files', 'filepath = ?', [$filepath]);
    }
    $_SESSION['flash_msg'] = 'File deleted.';
    $_SESSION['flash_type'] = 'success';
    redirect('media.php');
}

$search = trim($_GET['search'] ?? '');
$tab = $_GET['tab'] ?? 'all';

$root = realpath(__DIR__ . '/../..');

// Scan directories for image files
$scanDirs = [
    'uploads'     => 'admin/uploads',
    'images'      => 'images',
    'Icons'       => 'images/Icons',
    'Brand Logos' => 'images/Brands_that_we work_with',
];

$scannedFiles = [];
$imageExts = ['jpg','jpeg','png','gif','webp','svg','ico','avif'];

foreach ($scanDirs as $label => $dir) {
    $absDir = $root . '/' . $dir;
    if (!is_dir($absDir)) continue;
    $it = $label === 'images'
        ? new FilesystemIterator($absDir, FilesystemIterator::SKIP_DOTS)
        : new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absDir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($it as $fi) {
        if (!$fi->isFile()) continue;
        $ext = strtolower($fi->getExtension());
        if (!in_array($ext, $imageExts)) continue;
        $relPath = $dir . '/' . $fi->getFilename();
        $scannedFiles[] = [
            'name'       => $fi->getFilename(),
            'filepath'   => $relPath,
            'filesize'   => $fi->getSize(),
            'created_at' => date('Y-m-d H:i:s', $fi->getCTime()),
            'source'     => $label,
            'deletable'  => true,
            'id'         => null,
        ];
    }
}

// Get DB uploaded files
$dbFiles = db_rows("SELECT * FROM media_files ORDER BY created_at DESC");

// Tag DB files
$taggedDb = [];
foreach ($dbFiles as $f) {
    $f['source'] = 'Uploaded';
    $f['deletable'] = true;
    $f['id'] = (int)$f['id'];
    $taggedDb[] = $f;
}

// Merge: DB files first (newest uploads), then scanned files
$allFiles = array_merge($taggedDb, $scannedFiles);

// Collect used image paths from content tables
$usedByFilename = [];
$usageTables = [
    'blog_posts'   => 'featured_image',
    'portfolio'    => 'image_url',
    'brands'       => 'logo_url',
    'testimonials' => 'client_image',
];
foreach ($usageTables as $table => $col) {
    $rows = db_rows("SELECT DISTINCT $col FROM $table WHERE $col IS NOT NULL AND $col != ''");
    foreach ($rows as $row) {
        $val = trim($row[$col]);
        if ($val) $usedByFilename[strtolower(basename($val))] = true;
    }
}

// Filter by tab
if ($tab !== 'all') {
    $allFiles = array_values(array_filter($allFiles, fn($f) => $f['source'] === $tab));
}

// Filter by search
if ($search) {
    $q = strtolower($search);
    $allFiles = array_values(array_filter($allFiles, fn($f) => str_contains(strtolower($f['name'] ?? $f['original_name'] ?? ''), $q)));
}

$tabLabels = [
    'all'      => 'All',
    'Uploaded' => 'Uploaded',
    'images'   => 'Site Images',
    'Icons'    => 'Icons',
    'Brand Logos' => 'Brand Logos',
];

require_once __DIR__ . '/../inc/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Media Library</h1>
    <div class="flex flex-wrap">
        <div class="search-box">
            <i class="ti ti-search" style="color:var(--text3)"></i>
            <input class="form-control" type="text" placeholder="Search files..." value="<?= h($search) ?>" onchange="window.location='media.php?tab=<?= h($tab) ?>&search='+encodeURIComponent(this.value)" style="width:200px">
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('uploadInput').click()"><i class="ti ti-upload"></i> Upload</button>
        <input type="file" id="uploadInput" accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml" multiple style="display:none" onchange="uploadFiles(this)">
    </div>
</div>

<?php if ($flash_msg): ?>
    <div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:<?= $flash_type === 'success' ? 'var(--green-bg)' : 'var(--red-bg)' ?>;color:<?= $flash_type === 'success' ? 'var(--green)' : 'var(--red)' ?>"><?= h($flash_msg) ?></div>
<?php endif; ?>

<!-- Filter Tabs -->
<div style="display:flex;gap:6px;margin-bottom:1.5rem;flex-wrap:wrap">
    <?php foreach ($tabLabels as $key => $label): ?>
    <a href="media.php?tab=<?= h($key) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
       style="padding:6px 14px;border-radius:var(--radius-sm);font-size:0.75rem;text-decoration:none;font-family:'Inter',sans-serif;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;transition:all 0.2s;<?= $tab === $key ? 'background:var(--accent);color:#080B10;box-shadow:0 4px 10px var(--accent-glow)' : 'background:var(--bg2);color:var(--text2);border:1px solid var(--border)' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Drop Zone -->
<div id="dropZone" style="border:2px dashed var(--border);border-radius:var(--radius-lg);padding:2.5rem 2rem;text-align:center;margin-bottom:1.5rem;cursor:pointer;transition:all 0.2s ease">
    <i class="ti ti-cloud-upload" style="font-size:2rem;color:var(--text3);display:block;margin-bottom:0.5rem"></i>
    <p style="color:var(--text2);font-size:0.85rem">Drop images here or click to browse</p>
    <p style="color:var(--text3);font-size:0.7rem;margin-top:4px">JPG, PNG, WebP, GIF, SVG — max 5MB</p>
</div>

<div id="uploadProgress" style="display:none;margin-bottom:1.5rem">
    <div style="background:var(--bg3);border-radius:999px;height:6px;overflow:hidden">
        <div id="progressBar" style="height:100%;width:0%;background:var(--accent);transition:width 0.3s;border-radius:999px;box-shadow:0 0 10px var(--accent-glow)"></div>
    </div>
    <p id="progressText" style="color:var(--text3);font-size:0.75rem;margin-top:6px">Uploading...</p>
</div>

<div class="card">
    <?php if (count($allFiles)): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1.25rem">
        <?php foreach ($allFiles as $f): ?>
        <?php
            $fname = $f['original_name'] ?? $f['name'];
            $fpath = $f['filepath'];
            $fsize = $f['filesize'] ?? 0;
            $fdate = $f['created_at'] ?? '';
            $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp','svg','ico','avif']);
            $fileUrl = BASE_URL . '/' . ltrim($fpath, '/');
            $srcLabel = $f['source'] ?? '—';
            $isDeletable = $f['deletable'];
            $isUsed = isset($usedByFilename[strtolower(basename($fname))]);
        ?>
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;position:relative;transition:all 0.2s ease" class="hover-accent">
            <div class="media-thumb" style="height:130px;background:var(--bg);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative" onclick="this.classList.toggle('touch-active')">
                <?php if ($isImage): ?>
                    <img src="<?= $fileUrl ?>" style="max-width:100%;max-height:100%;object-fit:contain" loading="lazy">
                <?php else: ?>
                    <i class="ti ti-file" style="font-size:2rem;color:var(--text3)"></i>
                <?php endif; ?>
                <div class="media-overlay" style="position:absolute;inset:0;background:rgba(8,11,16,0.7);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;gap:8px;opacity:0;transition:opacity 0.2s">
                    <?php if ($isUsed): ?>
                    <span style="position:absolute;top:6px;left:6px;padding:2px 8px;background:var(--accent);color:#080B10;border-radius:var(--radius-sm);font-size:0.55rem;font-weight:700;font-family:'Orbitron',sans-serif;letter-spacing:0.05em">USED</span>
                    <?php endif; ?>
                    <button class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:0.65rem" onclick="copyUrl('<?= $fileUrl ?>', this)" title="Copy URL"><i class="ti ti-copy"></i></button>
                    <button onclick="confirmDelete('media.php?<?= $f['id'] ? 'delete='.$f['id'] : 'delete_path='.base64_encode($f['filepath']) ?>', '<?= h(addslashes($fname)) ?>')" class="btn btn-danger btn-sm" style="padding:4px 10px;font-size:0.65rem" title="Delete"><i class="ti ti-trash"></i></button>
                </div>
            </div>
            <div style="padding:0.5rem 0.625rem 0.625rem">
                <div style="font-size:0.72rem;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= h($fname) ?>"><?= h($fname) ?></div>
                <div style="display:flex;align-items:center;gap:6px;margin-top:4px">
                    <span style="font-size:0.6rem;padding:1px 6px;border-radius:3px;background:<?= $srcLabel === 'Uploaded' ? 'var(--accent)' : 'var(--bg)' ?>;color:<?= $srcLabel === 'Uploaded' ? '#080B10' : 'var(--text3)' ?>;border:1px solid var(--border);font-weight:600"><?= h($srcLabel) ?></span>
                    <span style="font-size:0.6rem;color:var(--text3)"><?= $fsize ? number_format($fsize / 1024, 1) . ' KB' : '' ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="ti ti-photo"></i>
        <p>No files found.</p>
    </div>
    <?php endif; ?>
</div>

<style>
.media-thumb:hover .media-overlay { opacity: 1; }
</style>

<script>
function uploadFiles(input) {
    if (!input.files || !input.files.length) return;
    uploadFileList(input.files);
}

var dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.style.borderColor = 'var(--accent)';
    this.style.background = 'rgba(204,255,0,0.03)';
});
dropZone.addEventListener('dragleave', function() {
    this.style.borderColor = '';
    this.style.background = '';
});
dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.style.borderColor = '';
    this.style.background = '';
    if (e.dataTransfer.files.length) uploadFileList(e.dataTransfer.files);
});
dropZone.addEventListener('click', function() {
    document.getElementById('uploadInput').click();
});

function uploadFileList(files) {
    var bar = document.getElementById('uploadProgress');
    var progressBar = document.getElementById('progressBar');
    var progressText = document.getElementById('progressText');
    bar.style.display = 'block';
    var total = files.length;
    var done = 0;

    for (var i = 0; i < total; i++) {
        (function(file) {
            var fd = new FormData();
            fd.append('file', file);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../upload.php', true);
            xhr.onload = function() {
                done++;
                var pct = Math.round((done / total) * 100);
                progressBar.style.width = pct + '%';
                progressText.textContent = done + ' of ' + total + ' uploaded';
                if (done === total) {
                    setTimeout(function() {
                        bar.style.display = 'none';
                        window.location.reload();
                    }, 500);
                }
            };
            xhr.send(fd);
        })(files[i]);
    }
}

function copyUrl(url, btn) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() {
            var orig = btn.innerHTML;
            btn.innerHTML = '<i class="ti ti-check"></i>';
            setTimeout(function() { btn.innerHTML = orig; }, 1500);
        });
    }
}
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
