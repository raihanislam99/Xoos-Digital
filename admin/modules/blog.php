<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

// Handle delete
if (isset($_GET['delete'])) {
    delete('blog_posts', $_GET['delete']);
    redirect('blog.php');
}

// Handle save
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    try {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'slug' => slugify($_POST['title'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'tags' => trim($_POST['tags'] ?? ''),
            'status' => $_POST['action'] === 'publish' ? 'published' : 'draft',
            'featured_image' => trim($_POST['featured_image'] ?? ''),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'read_time' => (int)($_POST['read_time'] ?? 0),
        ];
        if ($id) {
            update('blog_posts', $id, $data);
        } else {
            insert('blog_posts', $data);
        }
        $_SESSION['flash_msg'] = 'Post saved successfully.';
        $_SESSION['flash_type'] = 'success';
        redirect('blog.php');
    } catch (Exception $e) {
        $msg = 'Error: ' . $e->getMessage();
    }
}

// Handle category add/rename/delete
$catMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cat_action'])) {
    csrf_verify();
    try {
        $cat_action = $_POST['cat_action'];
        if ($cat_action === 'add_cat') {
            $name = trim($_POST['cat_name'] ?? '');
            if ($name) {
                insert('blog_categories', [
                    'name' => $name,
                    'slug' => slugify($name),
                ]);
                $_SESSION['flash_msg'] = 'Category added.';
                $_SESSION['flash_type'] = 'success';
            }
        } elseif ($cat_action === 'edit_cat' && !empty($_POST['cat_id'])) {
            $name = trim($_POST['cat_name'] ?? '');
            if ($name) {
                update('blog_categories', (int)$_POST['cat_id'], [
                    'name' => $name,
                    'slug' => slugify($name),
                ]);
                $_SESSION['flash_msg'] = 'Category renamed.';
                $_SESSION['flash_type'] = 'success';
            }
        } elseif ($cat_action === 'delete_cat' && !empty($_POST['cat_id'])) {
            $catId = (int)$_POST['cat_id'];
            // Unlink posts from this category
            $db->exec("UPDATE blog_posts SET category_id = 0 WHERE category_id = $catId");
            delete('blog_categories', $catId);
            $_SESSION['flash_msg'] = 'Category deleted.';
            $_SESSION['flash_type'] = 'success';
        }
        redirect('blog.php');
    } catch (Exception $e) {
        $catMsg = 'Error: ' . $e->getMessage();
    }
}

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

$posts = get_all('blog_posts', 'created_at DESC');
$categories = get_all('blog_categories', 'name ASC');
$post = [];
$isEdit = false;
if (!empty($_GET['edit']) && is_numeric($_GET['edit'])) {
    $fetched = get_row('blog_posts', (int)$_GET['edit']);
    if ($fetched) {
        $post = $fetched;
        $isEdit = true;
    }
}
$showForm = $isEdit || isset($_GET['new']);
?>
<?php require_once __DIR__ . '/../inc/header.php'; ?>

<style>
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
@keyframes shimmer{0%{background-position:-200% center}100%{background-position:200% center}}
.idea-skeleton{height:36px;border-radius:6px;background:linear-gradient(90deg,var(--bg) 25%,var(--bg3) 50%,var(--bg) 75%);background-size:200% auto;animation:shimmer 1.5s linear infinite;margin-bottom:6px}
.ai-btn-loading{opacity:.7;cursor:wait!important;pointer-events:none}
.ai-btn-loading::before{content:'\23F3 ';font-size:.75rem}
.blog-form-layout{display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start}
.blog-sidebar{position:sticky;top:1rem}
.blog-sidebar .sidebar-card{background:var(--bg2);border:1px solid var(--border);border-radius:12px;padding:1.25rem}
.suggestion-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem}
.suggestion-header h3{font-family:'Orbitron',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--accent)}
.suggestion-list{display:flex;flex-direction:column;gap:6px;max-height:calc(100vh - 220px);overflow-y:auto}
.suggestion-item{padding:0.5rem 0.75rem;background:var(--bg);border:1px solid var(--border);border-radius:6px;font-size:0.78rem;color:var(--text2);cursor:pointer;transition:all 0.15s;line-height:1.4}
.suggestion-item:hover{border-color:var(--accent);color:var(--accent)}
.field-counter{font-size:0.65rem;color:var(--text3);margin-top:2px;text-align:right}
.field-counter.warn{color:var(--red)}
.btn-generate-all{background:var(--accent);color:var(--bg);font-weight:700;padding:0.5rem 1rem;font-size:0.8rem}
.btn-generate-all:hover{opacity:0.85}
.btn-generate-all:disabled{opacity:0.5;cursor:not-allowed}
.image-prompt-field{min-height:80px;resize:vertical;line-height:1.5}
.toast-msg{position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);background:var(--accent);color:var(--bg);padding:0.5rem 1.25rem;border-radius:8px;font-size:0.82rem;font-weight:600;opacity:0;transition:opacity 0.3s;z-index:999;pointer-events:none}
.toast-msg.show{opacity:1}
@media(max-width:1024px){.blog-form-layout{grid-template-columns:1fr}.blog-sidebar{position:static}}
</style>

<?php if ($flash_msg): ?>
    <div class="flash flash-<?= $flash_type ?>" style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:<?= $flash_type === 'success' ? 'var(--green-bg)' : 'var(--red-bg)' ?>;color:<?= $flash_type === 'success' ? 'var(--green)' : 'var(--red)' ?>">
        <?= h($flash_msg) ?>
    </div>
<?php endif; ?>
<?php if ($msg): ?>
    <div class="flash flash-error" style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:var(--red-bg);color:var(--red)"><?= h($msg) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title">Blog Posts</h1>
    <div class="flex flex-wrap">
        <div class="search-box">
            <i class="ti ti-search" style="color:var(--text3)"></i>
            <input class="form-control" type="text" placeholder="Search posts..." oninput="searchTable(this)" style="width:200px">
        </div>
        <a href="blog.php?new=1" class="btn btn-primary"><i class="ti ti-plus"></i> New Post</a>
    </div>
</div>

<div class="card" style="margin-bottom:1rem">
    <div onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none'" style="cursor:pointer;display:flex;align-items:center;gap:8px;padding:0.75rem 1rem;font-size:0.85rem;color:var(--text2);user-select:none">
        <i class="ti ti-folder"></i> Manage Categories <span style="margin-left:auto;font-size:0.75rem;color:var(--text3)">▼</span>
    </div>
    <div style="display:none;padding:1rem;border-top:1px solid var(--border)">
        <form method="post" action="blog.php" style="display:flex;gap:8px;margin-bottom:1rem">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="cat_action" value="add_cat">
            <input class="form-control" name="cat_name" placeholder="New category name" required style="flex:1">
            <button class="btn btn-primary btn-sm">Add</button>
        </form>
        <?php if (count($categories)): ?>
        <div style="display:flex;flex-direction:column;gap:6px">
            <?php $counts = []; foreach (db()->query("SELECT category_id, COUNT(*) as c FROM blog_posts WHERE category_id > 0 GROUP BY category_id") as $row) { $counts[$row['category_id']] = $row['c']; } ?>
            <?php foreach ($categories as $cat): ?>
            <div style="display:flex;align-items:center;gap:8px;padding:0.5rem 0.75rem;background:var(--bg);border-radius:6px;border:1px solid var(--border)">
                <form method="post" action="blog.php" style="flex:1;display:flex;gap:6px;margin:0">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="cat_action" value="edit_cat">
                    <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                    <input class="form-control" name="cat_name" value="<?= h($cat['name']) ?>" required style="flex:1;font-size:0.85rem;padding:0.35rem 0.6rem">
                    <button class="btn btn-secondary btn-sm" title="Rename"><i class="ti ti-check"></i></button>
                </form>
                <span class="text-muted" style="font-size:0.8rem;white-space:nowrap"><?= (int)($counts[$cat['id']] ?? 0) ?> posts</span>
                <form method="post" action="blog.php" style="margin:0" onsubmit="return confirm('Delete category &#8220;<?= h($cat['name']) ?>&#8221;? Existing posts will be uncategorized.');">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="cat_action" value="delete_cat">
                    <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                    <button class="btn btn-danger btn-sm" title="Delete"><i class="ti ti-trash"></i></button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted" style="font-size:0.85rem">No categories yet. Create one above.</p>
        <?php endif; ?>
    </div>
</div>

<div id="module-list" style="<?= $showForm ? 'display:none' : 'display:block' ?>">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
        <div class="v3-view-toggle">
            <button class="active" id="v3GridBtn" onclick="setBlogView('grid')"><i class="ti ti-layout-grid"></i> Grid</button>
            <button id="v3TableBtn" onclick="setBlogView('table')"><i class="ti ti-list"></i> Table</button>
        </div>
        <span style="font-size:0.75rem;color:var(--v3-text3)"><?= count($posts) ?> post<?= count($posts) !== 1 ? 's' : '' ?></span>
    </div>

    <!-- Card Grid View -->
    <div id="v3BlogGrid" class="v3-blog-grid">
        <?php foreach ($posts as $p): ?>
        <a href="?edit=<?= $p['id'] ?? 0 ?>" class="v3-blog-card">
            <span class="bc-status <?= ($p['status'] ?? 'draft') === 'published' ? 'published' : 'draft' ?>"><?= $p['status'] ?? 'draft' ?></span>
            <div class="bc-icon"><i class="ti ti-news"></i></div>
            <div class="bc-title"><?= h($p['title'] ?? '') ?></div>
            <?php if ($p['meta_title'] ?? ''): ?><div style="font-size:0.65rem;color:var(--v3-text3);margin-top:4px">SEO: <?= h(mb_strimwidth($p['meta_title'], 0, 40, '...')) ?></div><?php endif; ?>
            <div class="bc-meta"><?= date('M j, Y', strtotime($p['updated_at'] ?? 'now')) ?></div>
            <div class="bc-actions">
                <span class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:0.65rem;text-decoration:none;pointer-events:none" onclick="event.stopPropagation();event.preventDefault()"><i class="ti ti-pencil"></i> Edit</span>
                <span class="btn btn-danger btn-sm" style="padding:2px 8px;font-size:0.65rem;text-decoration:none;pointer-events:none" onclick="event.stopPropagation();event.preventDefault();confirmDelete('blog.php?delete=<?= $p['id'] ?? 0 ?>', '<?= h(addslashes($p['title'] ?? '')) ?>')"><i class="ti ti-trash"></i></span>
            </div>
        </a>
        <?php endforeach; ?>
        <a href="blog.php?new=1" class="v3-blog-card bc-new">
            <i class="ti ti-plus"></i>
            <span>New Post</span>
        </a>
    </div>

    <!-- Table View (hidden by default) -->
    <div id="v3BlogTable" style="display:none">
        <div class="card">
            <?php if (count($posts)): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $p): ?>
                        <tr>
                            <td style="color:var(--text3)">#<?= $p['id'] ?? 0 ?></td>
                            <td>
                                <strong style="color:var(--text)"><?= h($p['title'] ?? '') ?></strong>
                                <?php if ($p['meta_title'] ?? ''): ?><br><span class="text-muted">SEO: <?= h($p['meta_title'] ?? '') ?></span><?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?= ($p['status'] ?? 'draft') === 'published' ? 'status-published' : 'status-draft' ?>">
                                    <?= $p['status'] ?? 'draft' ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= date('M j, Y', strtotime($p['updated_at'] ?? 'now')) ?></td>
                            <td style="text-align:right">
                                <a href="?edit=<?= $p['id'] ?? 0 ?>" class="btn btn-secondary btn-sm"><i class="ti ti-pencil"></i></a>
                                <button onclick="confirmDelete('blog.php?delete=<?= $p['id'] ?? 0 ?>', '<?= h(addslashes($p['title'] ?? '')) ?>')" class="btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="ti ti-news"></i>
                <p>No blog posts yet.</p>
                <a href="blog.php?new=1" class="btn btn-primary mt-1">Create First Post</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="module-form" style="<?= $showForm ? 'display:block' : 'display:none' ?>">
    <div class="flex" style="margin-bottom:1rem">
        <button class="btn btn-secondary" onclick="showList()"><i class="ti ti-arrow-left"></i> Back to List</button>
        <?php $blogProv = get_setting('ai_provider_blog', 'groq'); $presets = ai_provider_presets(); $blogLabel = $presets[$blogProv]['label'] ?? $blogProv; ?>
        <div id="aiStatus" style="margin-left:auto;display:flex;align-items:center;gap:6px;font-size:0.7rem;padding:4px 10px;border-radius:6px;background:var(--bg3);border:1px solid var(--border)">
            <span style="width:6px;height:6px;border-radius:50%;background:var(--accent);display:inline-block;animation:pulse 2s ease-in-out infinite"></span>
            <span style="color:var(--text2)"><?= h($blogLabel) ?> AI</span>
            <a href="settings.php" style="color:var(--accent);text-decoration:none;font-size:0.65rem;margin-left:4px" title="Change AI provider"><i class="ti ti-settings"></i></a>
        </div>
    </div>

    <div class="blog-form-layout">
        <div class="card">
            <form method="post" action="blog.php">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" value="<?= $post['id'] ?? 0 ?>">

                <div class="form-group">
                    <label>Keyword / Title</label>
                    <div class="flex" style="gap:8px;flex-wrap:wrap">
                        <input class="form-control" name="title" id="blogTitle" value="<?= h($post['title'] ?? '') ?>" required placeholder="Enter keyword or topic..." style="flex:1;min-width:200px">
                        <button type="button" class="btn btn-generate-all" id="genAllBtn" onclick="aiGenerateAll()"><i class="ti ti-sparkles"></i> Generate All</button>
                        <button type="button" class="btn btn-ai btn-sm" onclick="aiBlogGenerate(this)" title="Generate full post from title"><i class="ti ti-sparkles"></i> Content</button>
                        <button type="button" class="btn btn-ai btn-sm" onclick="aiBlogOutline(this)" title="Generate outline"><i class="ti ti-list"></i> Outline</button>
                        <button type="button" class="btn btn-ai btn-sm" onclick="aiSeoTitle()" title="Generate SEO title under 60 chars"><i class="ti ti-tag"></i> SEO Title</button>
                    </div>
                    <div class="field-counter" id="titleCounter">0 / 60</div>
                </div>

                <div class="form-group">
                    <label>Content <span class="text-muted">(HTML supported)</span></label>
                    <div class="rte-toolbar" style="display:flex;gap:4px;padding:6px 8px;background:var(--bg3);border:1px solid var(--border);border-bottom:none;border-radius:8px 8px 0 0;flex-wrap:wrap">
                        <button type="button" onclick="rteWrap('h2')" title="Heading 2" style="padding:4px 8px;background:var(--bg);border:1px solid var(--border);color:var(--text2);cursor:pointer;font-family:'Orbitron',sans-serif;font-size:0.65rem;font-weight:700;border-radius:4px">H2</button>
                        <button type="button" onclick="rteWrap('h3')" title="Heading 3" style="padding:4px 8px;background:var(--bg);border:1px solid var(--border);color:var(--text2);cursor:pointer;font-family:'Orbitron',sans-serif;font-size:0.65rem;font-weight:700;border-radius:4px">H3</button>
                        <button type="button" onclick="rteWrap('p')" title="Paragraph" style="padding:4px 8px;background:var(--bg);border:1px solid var(--border);color:var(--text2);cursor:pointer;font-family:'Inter',sans-serif;font-size:0.8rem;border-radius:4px">¶</button>
                        <span style="width:1px;background:var(--border);margin:0 4px"></span>
                        <button type="button" onclick="rteWrap('strong')" title="Bold" style="padding:4px 8px;background:var(--bg);border:1px solid var(--border);color:var(--text2);cursor:pointer;font-family:'Inter',sans-serif;font-size:0.8rem;font-weight:700;border-radius:4px"><b>B</b></button>
                        <button type="button" onclick="rteWrap('em')" title="Italic" style="padding:4px 8px;background:var(--bg);border:1px solid var(--border);color:var(--text2);cursor:pointer;font-family:'Inter',sans-serif;font-size:0.8rem;font-style:italic;border-radius:4px"><i>I</i></button>
                        <button type="button" onclick="rteLink()" title="Link" style="padding:4px 8px;background:var(--bg);border:1px solid var(--border);color:var(--text2);cursor:pointer;font-family:'Inter',sans-serif;font-size:0.8rem;border-radius:4px">🔗</button>
                        <button type="button" onclick="rteWrap('ul > li')" title="List" style="padding:4px 8px;background:var(--bg);border:1px solid var(--border);color:var(--text2);cursor:pointer;font-family:'Inter',sans-serif;font-size:0.8rem;border-radius:4px">≡</button>
                    </div>
                    <textarea class="form-control" id="content" name="content" rows="16" placeholder="Write your post content here..." style="border-radius:0 0 8px 8px"><?= h($post['content'] ?? '') ?></textarea>
                    <div class="field-counter" id="wordCounter">0 words</div>
                    <div class="flex" style="margin-top:6px;gap:4px;flex-wrap:wrap">
                        <span class="text-muted" style="font-size:0.7rem">AI:</span>
                        <button type="button" class="btn btn-ai btn-sm" onclick="aiTone('professional')" style="font-size:0.7rem">Professional</button>
                        <button type="button" class="btn btn-ai btn-sm" onclick="aiTone('conversational')" style="font-size:0.7rem">Conversational</button>
                        <button type="button" class="btn btn-ai btn-sm" onclick="aiTone('persuasive')" style="font-size:0.7rem">Persuasive</button>
                        <button type="button" class="btn btn-ai btn-sm" onclick="aiTone('simple')" style="font-size:0.7rem">Simple</button>
                        <button type="button" class="btn btn-ai btn-sm" onclick="aiSeoPost()" style="font-size:0.7rem"><i class="ti ti-file-text"></i> Write SEO Post</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Featured Image</label>
                    <div class="flex" style="gap:8px;flex-wrap:wrap">
                        <input class="form-control" name="featured_image" value="<?= h($post['featured_image'] ?? '') ?>" placeholder="https://..." style="flex:1;min-width:200px" oninput="showImagePreview(this,'preview-featured')" onchange="showImagePreview(this,'preview-featured')">
                        <input type="file" id="img-upload" accept="image/*" style="display:none" onchange="uploadImage(this);handleFilePreview(this,'preview-featured')">
                        <button type="button" class="btn btn-ai btn-sm" onclick="document.getElementById('img-upload').click()"><i class="ti ti-upload"></i> Upload</button>
                    </div>
                    <div id="preview-featured"></div>
                    <div id="blogImgPreview" style="margin-top:0.75rem"></div>
                </div>

                <div class="form-group">
                    <label>Featured Image Prompt</label>
                    <textarea class="form-control image-prompt-field" id="imagePrompt" name="image_prompt" placeholder="AI image generation prompt for the featured image..."><?= h($post['image_prompt'] ?? '') ?></textarea>
                    <div class="flex" style="margin-top:4px;gap:4px">
                        <button type="button" class="btn btn-ai btn-sm" id="genImgPromptBtn" onclick="aiImagePromptGen()"><i class="ti ti-sparkles"></i> Generate Image Prompt</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="copyImgPromptBtn" onclick="copyImagePrompt()"><i class="ti ti-copy"></i> Copy</button>
                    </div>
                </div>

                <?php $cats = db_rows('SELECT id, name FROM blog_categories ORDER BY sort_order'); ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <select class="form-control" name="category_id">
                            <option value="">Select category...</option>
                            <?php foreach ($cats as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($post['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Read Time <span class="text-muted">(minutes, auto-calculated)</span></label>
                        <input class="form-control" name="read_time" id="read_time" type="number" value="<?= h($post['read_time'] ?? 0) ?>" placeholder="Auto-calculated">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Meta Title</label>
                        <input class="form-control" name="meta_title" id="metaTitle" value="<?= h($post['meta_title'] ?? '') ?>" placeholder="SEO title" maxlength="60">
                        <div class="field-counter" id="metaTitleCounter">0 / 60</div>
                    </div>
                    <div class="form-group">
                        <label>Tags</label>
                        <input class="form-control" name="tags" value="<?= h($post['tags'] ?? '') ?>" placeholder="tag1, tag2, tag3">
                    </div>
                </div>

                <div class="form-group">
                    <label>Meta Description</label>
                    <textarea class="form-control" name="meta_description" id="metaDesc" rows="2" placeholder="SEO description" maxlength="160"><?= h($post['meta_description'] ?? '') ?></textarea>
                    <div class="field-counter" id="metaDescCounter">0 / 160</div>
                </div>

                <!-- Google Search Snippet Preview -->
                <div class="form-group" style="margin-top:1.25rem;margin-bottom:1.5rem">
                    <label>Google Search Snippet Preview</label>
                    <div style="background:#151515;border:1px solid var(--border);border-radius:8px;padding:1.25rem;font-family:Arial,sans-serif;box-shadow:0 4px 6px rgba(0,0,0,0.15)">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                            <div style="background:#202124;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:1px solid #303134">
                                <span style="font-size:10px;color:#bdc1c6;font-weight:bold">x</span>
                            </div>
                            <div style="display:flex;flex-direction:column;line-height:1.2">
                                <span style="font-size:12px;color:#e8eaed;font-weight:600">Xoos Digital</span>
                                <span style="font-size:11px;color:#9aa0a6">xoosdigital.com › blog › <span id="googlePreviewSlug" style="color:#bdc1c6">post-slug</span></span>
                            </div>
                        </div>
                        <div id="googlePreviewTitle" style="font-size:19px;color:#8ab4f8;line-height:1.3;margin-bottom:4px;font-weight:normal;font-family:Google Sans,Roboto,sans-serif;cursor:pointer" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            Please enter a title...
                        </div>
                        <div id="googlePreviewDesc" style="font-size:14px;color:#bdc1c6;line-height:1.5;word-wrap:break-word;font-family:Roboto,sans-serif">
                            Please enter a meta description...
                        </div>
                    </div>
                </div>

                <div class="flex" style="gap:8px;margin-top:4px;flex-wrap:wrap">
                    <button type="button" class="btn btn-ai btn-sm" onclick="aiBlogMeta()"><i class="ti ti-tags"></i> Generate SEO Meta</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="copyAllJson()"><i class="ti ti-copy"></i> Copy All as JSON</button>
                </div>

                <div class="form-actions">
                    <button type="submit" name="action" value="draft" class="btn btn-secondary"><i class="ti ti-file"></i> Save Draft</button>
                    <button type="submit" name="action" value="publish" class="btn btn-success"><i class="ti ti-send"></i> Publish</button>
                    <button type="button" class="btn btn-secondary" onclick="showList()">Cancel</button>
                </div>
            </form>
        </div>

        <div class="blog-sidebar">
            <div class="sidebar-card">
                <div class="suggestion-header">
                    <h3>Post Ideas <span id="ideasCount" style="font-size:0.65rem;color:var(--text3);font-weight:400"></span></h3>
                    <div class="flex" style="gap:4px">
                        <button type="button" class="btn btn-secondary btn-sm" id="clearIdeasBtn" onclick="clearIdeas()" title="Clear keyword and content"><i class="ti ti-x"></i></button>
                        <button type="button" class="btn btn-secondary btn-sm" id="refreshBtn" onclick="loadSuggestions()"><i class="ti ti-refresh"></i></button>
                    </div>
                </div>
                <div class="suggestion-list" id="suggestionList">
                    <div style="text-align:center;color:var(--text3);font-size:0.78rem;padding:2rem 0">Click Refresh to load ideas</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    updateCounters();
    var imgInput = document.querySelector('input[name="featured_image"]');
    if (imgInput && imgInput.value) showBlogImagePreview(imgInput.value);
    loadSuggestions();
});

function aiButtonStart(btnId, loadingText) {
    var btn = typeof btnId === 'string' ? document.getElementById(btnId) : btnId;
    if (!btn) return;
    btn.dataset.origText = btn.innerHTML;
    btn.innerHTML = (loadingText || 'Working...');
    btn.disabled = true;
    btn.classList.add('ai-btn-loading');
}

function aiButtonEnd(btnId) {
    var btn = typeof btnId === 'string' ? document.getElementById(btnId) : btnId;
    if (!btn) return;
    btn.innerHTML = btn.dataset.origText || btn.innerHTML;
    btn.disabled = false;
    btn.classList.remove('ai-btn-loading');
}

function rteWrap(tag) {
    const ta = document.getElementById('content');
    const sel = ta.value.substring(ta.selectionStart, ta.selectionEnd);
    if (!sel) return;
    if (tag.includes('>')) {
        const [outer, inner] = tag.split(' > ');
        ta.setRangeText('<' + outer + '>\n  <' + inner + '>' + sel + '</' + inner + '>\n</' + outer + '>');
    } else {
        ta.setRangeText('<' + tag + '>' + sel + '</' + tag + '>');
    }
}
function rteLink() {
    const url = prompt('Enter URL:');
    if (!url) return;
    const ta = document.getElementById('content');
    const sel = ta.value.substring(ta.selectionStart, ta.selectionEnd) || 'Link text';
    ta.setRangeText('<a href="' + url + '">' + sel + '</a>');
}

function updateCounters() {
    var title = document.getElementById('blogTitle').value;
    var tc = document.getElementById('titleCounter');
    if (tc) { tc.textContent = title.length + ' / 60'; tc.className = 'field-counter' + (title.length > 60 ? ' warn' : ''); }

    var content = document.getElementById('content').value;
    var words = content.replace(/<[^>]+>/g, ' ').split(/\s+/).filter(function(w) { return w.length > 0; }).length;
    var wc = document.getElementById('wordCounter');
    if (wc) { wc.textContent = words + ' words'; }

    var mins = Math.max(1, Math.round(words / 200));
    var rt = document.getElementById('read_time');
    if (rt) rt.value = mins;

    var mt = document.getElementById('metaTitle').value;
    var mtc = document.getElementById('metaTitleCounter');
    if (mtc) { mtc.textContent = mt.length + ' / 60'; mtc.className = 'field-counter' + (mt.length > 60 ? ' warn' : ''); }

    var md = document.getElementById('metaDesc').value;
    var mdc = document.getElementById('metaDescCounter');
    if (mdc) { mdc.textContent = md.length + ' / 160'; mdc.className = 'field-counter' + (md.length > 160 ? ' warn' : ''); }

    // Update Google Search Preview elements
    var gpTitle = document.getElementById('googlePreviewTitle');
    var gpDesc = document.getElementById('googlePreviewDesc');
    var gpSlug = document.getElementById('googlePreviewSlug');
    
    if (gpTitle) {
        var displayTitle = mt.trim() || title.trim() || 'Please enter a title...';
        gpTitle.textContent = displayTitle;
    }
    if (gpDesc) {
        var displayDesc = md.trim() || 'Please enter a meta description...';
        gpDesc.textContent = displayDesc;
    }
    if (gpSlug) {
        var rawTitle = title.trim() || 'post-slug';
        var slug = rawTitle.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
        gpSlug.textContent = slug || 'post-slug';
    }
}

document.getElementById('blogTitle').addEventListener('input', updateCounters);
document.getElementById('content').addEventListener('input', updateCounters);
document.getElementById('metaTitle').addEventListener('input', updateCounters);
document.getElementById('metaDesc').addEventListener('input', updateCounters);

function extractJson(raw) {
    if (!raw || typeof raw !== 'string') throw new Error('No valid JSON found');
    var s = raw.trim();
    try { return JSON.parse(s); } catch(e) {}
    s = s.replace(/^```(?:json)?\s*/i, '').replace(/\s*```\s*$/g, '').trim();
    try { return JSON.parse(s); } catch(e) {}
    var start = s.indexOf('[');
    if (start !== -1) { var end = s.lastIndexOf(']');
        if (end > start) { try { return JSON.parse(s.substring(start, end + 1)); } catch(e) {} } }
    start = s.indexOf('{');
    if (start !== -1) { var end = s.lastIndexOf('}');
        if (end > start) { try { return JSON.parse(s.substring(start, end + 1)); } catch(e) {} } }
    throw new Error('No valid JSON found');
}

function _parseTextTitles(raw) {
    if (!raw || typeof raw !== 'string') throw new Error('Invalid input');
    try { return extractJson(raw); } catch(e) {}
    var titles = raw.split('\n')
        .map(function(l) { return l.replace(/^[\s\•\-*]+/, '').replace(/^\d+[.)]\s*/, '').replace(/^["'\s]+|["'\s]+$/g, '').trim(); })
        .filter(function(l) { return l.length > 10; });
    if (titles.length >= 3) return titles;
    titles = raw.split(/\n\s*\n/)
        .map(function(l) { return l.replace(/\s+/g, ' ').trim(); })
        .filter(function(l) { return l.length > 15; });
    if (titles.length >= 3) return titles;
    throw new Error('No valid titles found');
}

function escapeHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function aiGenerateAll() {
    var keyword = document.getElementById('blogTitle').value.trim();
    if (!keyword) { alert('Enter a keyword or topic first'); return; }
    aiButtonStart('genAllBtn', 'Generating...');
    fetch('../ai.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({task:'blog_generate_all', context:keyword}) })
    .then(function(r){return r.json()}).then(function(j){
        aiButtonEnd('genAllBtn');
        if (!j.success) { alert('Generation failed'); return; }
        var d = j.data;
        if (typeof d === 'string') { try { d = extractJson(d); } catch(e) { alert('Failed to parse response'); return; } }
        if (d.title) document.getElementById('blogTitle').value = d.title;
        if (d.content) document.getElementById('content').value = d.content;
        if (d.image_prompt) document.getElementById('imagePrompt').value = d.image_prompt;
        if (d.meta_title) document.getElementById('metaTitle').value = d.meta_title;
        if (d.meta_description) document.getElementById('metaDesc').value = d.meta_description;
        if (d.tags) document.querySelector('input[name="tags"]').value = d.tags;
        updateCounters();
    }).catch(function(){ aiButtonEnd('genAllBtn'); });
}

function aiBlogGenerate(btn) {
    var title = document.getElementById('blogTitle').value.trim();
    if (!title) { alert('Enter a title first'); return; }
    var contentField = document.getElementById('content');
    aiButtonStart(btn, 'Generating...');
    fetch('../ai.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({task:'blog_generate', context: title})
    }).then(function(r){return r.json()}).then(function(j){
        aiButtonEnd(btn);
        if (j.success) contentField.value = j.data;
    }).catch(function(){ aiButtonEnd(btn); });
}

function aiBlogOutline(btn) {
    var title = document.getElementById('blogTitle').value.trim();
    if (!title) { alert('Enter a title first'); return; }
    var contentField = document.getElementById('content');
    aiButtonStart(btn, 'Generating...');
    fetch('../ai.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({task:'blog_outline', context: title})
    }).then(function(r){return r.json()}).then(function(j){
        aiButtonEnd(btn);
        if (j.success) contentField.value = j.data;
    }).catch(function(){ aiButtonEnd(btn); });
}

function aiTone(tone) {
    var contentField = document.getElementById('content');
    var text = contentField.value.trim();
    if (!text) { alert('Write some content first'); return; }
    fetch('../ai.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({task:'blog_tone', context: text, extra: tone})
    }).then(function(r){return r.json()}).then(function(j){
        if (j.success && window.__aiShowResult) {
            window.__aiShowResult(contentField, j.data);
        } else if (j.success) {
            contentField.value = j.data;
        }
    });
}

function aiBlogMeta() {
    var content = document.getElementById('content').value.trim();
    if (!content) { alert('Write content first'); return; }
    fetch('../ai.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({task:'blog_meta', context: content})
    }).then(function(r){return r.json()}).then(function(j){
        if (!j.success) return;
        var d = j.data;
        if (typeof d === 'string') { try { d = extractJson(d); } catch(e) {} }
        if (d.meta_title) document.getElementById('metaTitle').value = d.meta_title;
        if (d.meta_description) document.getElementById('metaDesc').value = d.meta_description;
        if (d.tags) document.querySelector('input[name="tags"]').value = d.tags;
    });
}

function uploadImage(input) {
    if (!input.files || !input.files[0]) return;
    var fd = new FormData();
    fd.append('file', input.files[0]);
    fetch('../upload.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.success) {
            document.querySelector('input[name="featured_image"]').value = j.url;
            showBlogImagePreview(j.url);
        } else {
            alert('Upload failed: ' + (j.error || 'Unknown error'));
        }
    })
    .catch(function() { alert('Upload failed'); });
}

function showList() { window.location.href = 'blog.php'; }
function setBlogView(view) {
    var grid = document.getElementById('v3BlogGrid');
    var table = document.getElementById('v3BlogTable');
    var gridBtn = document.getElementById('v3GridBtn');
    var tableBtn = document.getElementById('v3TableBtn');
    if (!grid || !table) return;
    if (view === 'grid') {
        grid.style.display = '';
        table.style.display = 'none';
        gridBtn.classList.add('active');
        tableBtn.classList.remove('active');
        localStorage.setItem('blogView', 'grid');
    } else {
        grid.style.display = 'none';
        table.style.display = '';
        tableBtn.classList.add('active');
        gridBtn.classList.remove('active');
        localStorage.setItem('blogView', 'table');
    }
}
// Restore blog view preference
(function() {
    var v = localStorage.getItem('blogView');
    if (v === 'table') setBlogView('table');
})();

function aiImagePromptGen() {
    var title = document.getElementById('blogTitle').value.trim();
    var content = document.getElementById('content').value.trim();
    if (!title && !content) { alert('Enter a title or write some content first'); return; }
    aiButtonStart('genImgPromptBtn', 'Generating...');
    fetch('../ai.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({task:'image_prompt', context:{title: title, excerpt: content.replace(/<[^>]+>/g,'').substring(0, 300)}})
    }).then(function(r) { return r.json(); }).then(function(j) {
        aiButtonEnd('genImgPromptBtn');
        if (j.success) document.getElementById('imagePrompt').value = j.data;
    }).catch(function() { aiButtonEnd('genImgPromptBtn'); });
}

function copyImagePrompt() {
    var field = document.getElementById('imagePrompt');
    if (!field || !field.value) { alert('Generate a prompt first.'); return; }
    navigator.clipboard.writeText(field.value).then(function() {
        var btn = document.getElementById('copyImgPromptBtn');
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-check"></i> Copied!';
        btn.style.color = 'var(--accent)';
        setTimeout(function() { btn.innerHTML = orig; btn.style.color = ''; }, 1500);
    });
}

function aiSeoTitle() {
    var titleInput = document.getElementById('blogTitle');
    var keyword = titleInput.value.trim();
    if (!keyword) { alert('Enter a keyword or topic first'); return; }
    var btn = event.target.closest('button');
    aiButtonStart(btn, 'Generating...');
    fetch('../ai.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({task:'blog_seo_title', context:keyword}) })
    .then(function(r){return r.json()}).then(function(j){
        aiButtonEnd(btn);
        if (j.success) document.getElementById('blogTitle').value = j.data;
    }).catch(function(){ aiButtonEnd(btn); });
}

function aiSeoPost() {
    var titleInput = document.getElementById('blogTitle');
    var title = titleInput.value.trim();
    if (!title) { alert('Enter a title or topic first'); return; }
    var contentField = document.getElementById('content');
    var btn = event.target.closest('button');
    aiButtonStart(btn, 'Generating...');
    fetch('../ai.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({task:'blog_seo_full', context:title}) })
    .then(function(r){return r.json()}).then(function(j){
        aiButtonEnd(btn);
        if (!j.success) return;
        var d = j.data;
        if (typeof d === 'string') { try { d = extractJson(d); } catch(e) { document.getElementById('content').value = d; return; } }
        if (d.title) document.getElementById('blogTitle').value = d.title;
        if (d.content) document.getElementById('content').value = d.content;
        if (d.meta_title) document.getElementById('metaTitle').value = d.meta_title;
        if (d.meta_description) document.getElementById('metaDesc').value = d.meta_description;
        if (d.tags) document.querySelector('input[name="tags"]').value = d.tags;
    }).catch(function(){ aiButtonEnd(btn); });
}

function renderSuggestions(titles) {
    var container = document.getElementById('suggestionList');
    container.innerHTML = '';
    var countBadge = document.getElementById('ideasCount');
    if (Array.isArray(titles)) {
        if (countBadge) countBadge.textContent = '(' + titles.length + ')';
        titles.forEach(function(t) {
            var div = document.createElement('div');
            div.className = 'suggestion-item';
            div.title = 'Click to use as title';
            div.textContent = typeof t === 'string' ? t : (t.title || t.topic || JSON.stringify(t));
            div.onclick = function() { useIdea(div.textContent.trim()); };
            container.appendChild(div);
        });
    } else {
        if (countBadge) countBadge.textContent = '';
    }
}

function loadSuggestions() {
    var btn = document.getElementById('refreshBtn');
    var container = document.getElementById('suggestionList');
    var countBadge = document.getElementById('ideasCount');
    if (btn) { btn.innerHTML = '<span class="ai-spinner"></span>'; btn.disabled = true; }
    if (countBadge) countBadge.textContent = '';

    container.innerHTML = Array(5).fill(0).map(function() { return '<div class="idea-skeleton"></div>'; }).join('');

    var topic = document.getElementById('blogTitle').value.trim();

    fetch('../ai.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({task:'blog_ideas', context:{topic: topic, existing: []}})
    }).then(function(r){return r.json()}).then(function(j){
        if (btn) { btn.innerHTML = '<i class="ti ti-refresh"></i>'; btn.disabled = false; }
        if (!j.success) {
            container.innerHTML = '<div style="text-align:center;color:var(--red);font-size:0.78rem;padding:1rem">Failed to load ideas</div>';
            return;
        }
        var d = j.data;
        if (typeof d === 'string') { try { d = _parseTextTitles(d); } catch(e) {} }
        if (!Array.isArray(d)) {
            container.innerHTML = '<div style="text-align:center;color:var(--red);font-size:0.78rem;padding:1rem">Failed to parse ideas</div>';
            return;
        }
        renderSuggestions(d);
    }).catch(function(){
        if (btn) { btn.innerHTML = '<i class="ti ti-refresh"></i>'; btn.disabled = false; }
        container.innerHTML = '<div style="text-align:center;color:var(--red);font-size:0.78rem;padding:1rem">Failed to load ideas</div>';
    });
}

function copyAllJson() {
    var data = {
        title: document.getElementById('blogTitle').value,
        content: document.getElementById('content').value,
        image_prompt: document.getElementById('imagePrompt').value,
        featured_image: document.querySelector('input[name="featured_image"]').value,
        category_id: document.querySelector('select[name="category_id"]').value,
        meta_title: document.getElementById('metaTitle').value,
        meta_description: document.getElementById('metaDesc').value,
        tags: document.querySelector('input[name="tags"]').value
    };
    navigator.clipboard.writeText(JSON.stringify(data, null, 2)).then(function() {
        var toast = document.createElement('div');
        toast.className = 'toast-msg';
        toast.textContent = 'Copied to clipboard!';
        document.body.appendChild(toast);
        setTimeout(function() { toast.classList.add('show'); }, 10);
        setTimeout(function() { toast.classList.remove('show'); setTimeout(function() { toast.remove(); }, 300); }, 2000);
    });
}
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
