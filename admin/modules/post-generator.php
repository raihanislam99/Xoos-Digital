<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

$view = $_GET['view'] ?? 'dashboard';
$pdo = db();

// AJAX handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'train_add') {
        $stmt = $pdo->prepare("INSERT INTO post_training_data (content, type) VALUES (?, ?)");
        $stmt->execute([trim($_POST['content']), $_POST['type'] ?? 'topic']);
        $_SESSION['flash_msg'] = 'Training data added.';
        $_SESSION['flash_type'] = 'success';
        redirect('post-generator.php?view=training');
    }

    if ($action === 'train_delete') {
        $pdo->prepare("DELETE FROM post_training_data WHERE id = ?")->execute([(int)$_POST['id']]);
        json_response(['ok' => true]);
    }

    if ($action === 'profile_add') {
        $stmt = $pdo->prepare("INSERT INTO post_profiles (platform, profile_url, name, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['platform'], trim($_POST['profile_url']), trim($_POST['name']), trim($_POST['notes'] ?? '')]);
        $_SESSION['flash_msg'] = 'Profile added.';
        $_SESSION['flash_type'] = 'success';
        redirect('post-generator.php?view=profiles');
    }

    if ($action === 'profile_delete') {
        $pdo->prepare("DELETE FROM post_profiles WHERE id = ?")->execute([(int)$_POST['id']]);
        json_response(['ok' => true]);
    }

    if ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        $status = $_POST['status'] === 'published' ? 'published' : 'draft';
        $pdo->prepare("UPDATE generated_posts SET status = ? WHERE id = ?")->execute([$status, $id]);
        json_response(['ok' => true]);
    }

    if ($action === 'delete_post') {
        $pdo->prepare("DELETE FROM generated_posts WHERE id = ?")->execute([(int)$_POST['id']]);
        json_response(['ok' => true]);
    }

    if ($action === 'update_post') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE generated_posts SET content = ? WHERE id = ?")->execute([trim($_POST['content']), $id]);
        json_response(['ok' => true]);
    }

    if ($action === 'generate') {
        $topic = trim($_POST['topic'] ?? '');
        $selectedProfileIds = json_decode($_POST['profile_ids'] ?? '[]', true);
        $selectedTrainingIds = json_decode($_POST['training_ids'] ?? '[]', true);

        if (!$topic) {
            json_response(['ok' => false, 'error' => 'Enter a topic for today.'], 400);
            exit;
        }

        // Fetch training context
        $trainingTexts = [];
        if (!empty($selectedTrainingIds)) {
            $placeholders = implode(',', array_fill(0, count($selectedTrainingIds), '?'));
            $stmt = $pdo->prepare("SELECT content FROM post_training_data WHERE id IN ($placeholders)");
            $stmt->execute($selectedTrainingIds);
            $trainingTexts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        // Fetch profile info
        $profileNotes = [];
        if (!empty($selectedProfileIds)) {
            $placeholders = implode(',', array_fill(0, count($selectedProfileIds), '?'));
            $stmt = $pdo->prepare("SELECT platform, name, profile_url, notes FROM post_profiles WHERE id IN ($placeholders)");
            $stmt->execute($selectedProfileIds);
            $profiles = $stmt->fetchAll();
            foreach ($profiles as $p) {
                $profileNotes[] = "{$p['platform']}: {$p['name']} ({$p['profile_url']})" . ($p['notes'] ? " — {$p['notes']}" : '');
            }
        }

        $settings = ai_feature_settings('posts');
        if (empty($settings['key'])) {
            json_response(['ok' => false, 'error' => 'Post AI is not configured. Go to Settings and set up an AI provider.'], 400);
            exit;
        }

        $trainingContext = $trainingTexts ? "Training data the user provided:\n" . implode("\n---\n", $trainingTexts) . "\n" : '';
        $profileContext = $profileNotes ? "Reference profiles to match style:\n" . implode("\n", $profileNotes) . "\n" : '';

        // Generate LinkedIn post (English)
        $liSystem = "You are a professional LinkedIn content creator. Write a single LinkedIn post in English based on the user's topic and training data. The post should be insightful, professional, and engaging. Use a conversational yet authoritative tone. Include relevant hashtags at the end (3-5). Do NOT use markdown. Keep it 150-300 words. Return ONLY the post text, no explanations.";

        $liUser = "Topic: {$topic}\n" . ($trainingContext ? $trainingContext . "\n" : '') . ($profileContext ? "Match the style of these profiles:\n{$profileContext}" : '');

        try {
            $linkedinPost = ai_call($settings, [
                ['role' => 'system', 'content' => $liSystem],
                ['role' => 'user', 'content' => $liUser],
            ], 1500, 0.7);
        } catch (Exception $e) {
            json_response(['ok' => false, 'error' => 'AI error (LinkedIn): ' . $e->getMessage()], 500);
            exit;
        }

        // Generate Facebook post (Bangla + English)
        $fbSystem = "You are a Facebook content creator for a Bangladeshi audience. Write a single Facebook post mixing Bangla (Bengali script) and English naturally (Banglish style). The post should be engaging, conversational, and relatable. Use emojis sparingly. Include 3-5 relevant hashtags mixing Bangla and English at the end. The post should feel authentic to a Bangladeshi audience. Do NOT use markdown. Keep it 100-250 words. Return ONLY the post text, no explanations.";

        $fbUser = "Topic: {$topic}\n" . ($trainingContext ? $trainingContext . "\n" : '') . ($profileContext ? "Match the style of these profiles:\n{$profileContext}" : '');

        try {
            $facebookPost = ai_call($settings, [
                ['role' => 'system', 'content' => $fbSystem],
                ['role' => 'user', 'content' => $fbUser],
            ], 1500, 0.7);
        } catch (Exception $e) {
            json_response(['ok' => false, 'error' => 'AI error (Facebook): ' . $e->getMessage()], 500);
            exit;
        }

        // Save to DB
        $trainingIdsJson = json_encode($selectedTrainingIds);
        $profileIdsJson = json_encode($selectedProfileIds);

        $pdo->prepare("INSERT INTO generated_posts (platform, content, language, status, topic, training_ids, profile_ids) VALUES ('linkedin', ?, 'en', 'draft', ?, ?, ?)")->execute([$linkedinPost, $topic, $trainingIdsJson, $profileIdsJson]);
        $pdo->prepare("INSERT INTO generated_posts (platform, content, language, status, topic, training_ids, profile_ids) VALUES ('facebook', ?, 'bn-en', 'draft', ?, ?, ?)")->execute([$facebookPost, $topic, $trainingIdsJson, $profileIdsJson]);
        $liId = $pdo->lastInsertId() - 1;

        json_response(['ok' => true, 'data' => [
            'linkedin' => ['id' => $liId, 'content' => $linkedinPost],
            'facebook' => ['id' => $liId + 1, 'content' => $facebookPost],
        ]]);
    }

    json_response(['ok' => false, 'error' => 'Unknown action'], 400);
}

// Load data
$trainingData = $pdo->query("SELECT * FROM post_training_data ORDER BY created_at DESC")->fetchAll();
$profiles = $pdo->query("SELECT * FROM post_profiles ORDER BY created_at DESC")->fetchAll();
$generatedPosts = $pdo->query("SELECT * FROM generated_posts ORDER BY created_at DESC LIMIT 50")->fetchAll();

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

$pageTitles = ['dashboard' => 'Post Generator', 'training' => 'Training Data', 'profiles' => 'Profiles'];
$pageTitle = $pageTitles[$view] ?? 'Post Generator';
?>
<?php require_once __DIR__ . '/../inc/header.php'; ?>

<style>
.pg-tabs { display:flex; gap:0; border-bottom:1px solid var(--border); margin-bottom:1.5rem; }
.pg-tab { padding:0.65rem 1.25rem; font-family:'Orbitron',sans-serif; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--text3); cursor:pointer; border-bottom:2px solid transparent; transition:all 0.15s; background:none; border-left:none; border-right:none; border-top:none; text-decoration:none; }
.pg-tab:hover { color:var(--text); }
.pg-tab.active { color:var(--accent); border-bottom-color:var(--accent); }
.pg-tab i { margin-right:6px; font-size:0.8rem; }
.post-card { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius-md); padding:1.25rem; margin-bottom:1rem; }
.post-card .post-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75rem; }
.post-card .post-platform { font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; display:flex; align-items:center; gap:6px; }
.post-card .post-content { font-size:0.85rem; line-height:1.6; color:var(--text2); white-space:pre-wrap; word-break:break-word; }
.post-card .post-meta { display:flex; align-items:center; gap:1rem; margin-top:0.75rem; font-size:0.72rem; color:var(--text3); }
.post-card .post-actions { display:flex; gap:6px; }
.generate-result { display:none; }
.generate-result.show { display:block; }
.copy-btn { cursor:pointer; }
.copy-btn:hover { color:var(--accent); }
</style>

<?php if ($flash_msg): ?>
<div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:<?= $flash_type==='success'?'var(--green-bg)':'var(--red-bg)' ?>;color:<?= $flash_type==='success'?'var(--green)':'var(--red)' ?>"><?= h($flash_msg) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title"><?= h($pageTitle) ?></h1>
</div>

<div class="pg-tabs">
    <a href="post-generator.php" class="pg-tab <?= $view==='dashboard'?'active':'' ?>"><i class="ti ti-sparkles"></i> Dashboard</a>
    <a href="post-generator.php?view=training" class="pg-tab <?= $view==='training'?'active':'' ?>"><i class="ti ti-database"></i> Training Data</a>
    <a href="post-generator.php?view=profiles" class="pg-tab <?= $view==='profiles'?'active':'' ?>"><i class="ti ti-users"></i> Profiles</a>
</div>

<?php if ($view === 'dashboard'): ?>

<div class="card" style="margin-bottom:1.5rem">
    <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:1rem"><i class="ti ti-sparkles"></i> Generate Today's Posts</h3>
    <form id="genForm" onsubmit="return generatePosts(event)">
        <div class="form-group">
            <label>What's the topic today?</label>
            <textarea class="form-control" name="topic" id="gen-topic" rows="3" placeholder="e.g. Talk about the importance of digital branding for small businesses in Bangladesh, share a client success story, or discuss latest SEO trends..." required></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Use Training Data</label>
                <div style="max-height:120px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.5rem">
                    <?php if (count($trainingData)): ?>
                    <?php foreach ($trainingData as $td): ?>
                    <label style="display:flex;align-items:flex-start;gap:8px;font-size:0.75rem;color:var(--text2);padding:4px 0;cursor:pointer">
                        <input type="checkbox" name="training_ids" value="<?= $td['id'] ?>" style="margin-top:2px">
                        <span class="text-muted" style="display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden"><?= h(mb_substr($td['content'], 0, 120)) ?></span>
                    </label>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-muted" style="font-size:0.75rem">No training data yet. <a href="post-generator.php?view=training" style="color:var(--accent)">Add some</a></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Reference Profiles</label>
                <div style="max-height:120px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.5rem">
                    <?php if (count($profiles)): ?>
                    <?php foreach ($profiles as $pr): ?>
                    <label style="display:flex;align-items:flex-start;gap:8px;font-size:0.75rem;color:var(--text2);padding:4px 0;cursor:pointer">
                        <input type="checkbox" name="profile_ids" value="<?= $pr['id'] ?>" style="margin-top:2px">
                        <span><span class="<?= $pr['platform']==='linkedin'?'priority-high':'priority-medium' ?>" style="font-size:0.6rem;font-weight:700;text-transform:uppercase;padding:0 4px;border-radius:2px"><?= $pr['platform'] ?></span> <?= h($pr['name'] ?: $pr['profile_url']) ?></span>
                    </label>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-muted" style="font-size:0.75rem">No profiles yet. <a href="post-generator.php?view=profiles" style="color:var(--accent)">Add some</a></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary" id="gen-btn"><i class="ti ti-sparkles"></i> Generate Posts</button>
        </div>
    </form>
</div>

<div id="gen-result" class="generate-result">
    <div class="post-card" id="li-result" style="border-left:3px solid #0a66c2">
        <div class="post-header">
            <span class="post-platform" style="color:#0a66c2"><i class="ti ti-brand-linkedin"></i> LinkedIn Post (English)</span>
            <div class="post-actions">
                <button class="btn btn-secondary btn-sm" onclick="copyPost('li-result')"><i class="ti ti-copy"></i></button>
                <button class="btn btn-success btn-sm" onclick="publishPost(this,'linkedin')"><i class="ti ti-check"></i> Publish</button>
            </div>
        </div>
        <div class="post-content" id="li-content"></div>
        <input type="hidden" id="li-id" value="0">
    </div>
    <div class="post-card" id="fb-result" style="border-left:3px solid #1877f2">
        <div class="post-header">
            <span class="post-platform" style="color:#1877f2"><i class="ti ti-brand-facebook"></i> Facebook Post (Bangla + English)</span>
            <div class="post-actions">
                <button class="btn btn-secondary btn-sm" onclick="copyPost('fb-result')"><i class="ti ti-copy"></i></button>
                <button class="btn btn-success btn-sm" onclick="publishPost(this,'facebook')"><i class="ti ti-check"></i> Publish</button>
            </div>
        </div>
        <div class="post-content" id="fb-content"></div>
        <input type="hidden" id="fb-id" value="0">
    </div>
</div>

<div style="margin-top:2rem">
    <div class="flex" style="justify-content:space-between;margin-bottom:1rem">
        <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3)"><i class="ti ti-clock"></i> Recent Generations</h3>
    </div>
    <?php $grouped = []; foreach ($generatedPosts as $gp) { $k = substr($gp['created_at'], 0, 10); $grouped[$k][] = $gp; } ?>
    <?php if (count($grouped)): foreach ($grouped as $date => $posts): ?>
    <div style="font-size:0.65rem;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.5rem;margin-top:1rem"><?= h(date('M j, Y', strtotime($date))) ?></div>
    <?php foreach ($posts as $gp): ?>
    <div class="post-card" id="history-<?= $gp['id'] ?>" style="border-left:3px solid <?= $gp['platform']==='linkedin'?'#0a66c2':'#1877f2' ?>">
        <div class="post-header">
            <span class="post-platform" style="color:<?= $gp['platform']==='linkedin'?'#0a66c2':'#1877f2' ?>">
                <i class="<?= $gp['platform']==='linkedin'?'ti ti-brand-linkedin':'ti ti-brand-facebook' ?>"></i> <?= $gp['platform']==='linkedin'?'LinkedIn':'Facebook' ?>
                <span class="text-muted" style="font-weight:400;text-transform:none;letter-spacing:0;margin-left:8px"><?= h($gp['topic']) ?></span>
            </span>
            <div class="post-actions">
                <span class="status-badge <?= $gp['status']==='published'?'status-published':'status-draft' ?>" style="font-size:0.6rem;padding:2px 8px;margin-right:4px"><?= $gp['status'] ?></span>
                <button class="btn btn-secondary btn-sm" onclick="copyText(this)" style="padding:3px 8px"><i class="ti ti-copy"></i></button>
                <button class="btn btn-secondary btn-sm" onclick="editPost(<?= $gp['id'] ?>)" style="padding:3px 8px"><i class="ti ti-pencil"></i></button>
                <button class="btn btn-danger btn-sm" onclick="deletePost(<?= $gp['id'] ?>)" style="padding:3px 8px"><i class="ti ti-trash"></i></button>
            </div>
        </div>
        <div class="post-content" id="post-content-<?= $gp['id'] ?>"><?= h($gp['content']) ?></div>
        <div class="post-meta">
            <span><?= date('h:i A', strtotime($gp['created_at'])) ?></span>
            <span>🌐 <?= $gp['language'] ?></span>
        </div>
    </div>
    <?php endforeach; endforeach; else: ?>
    <div class="empty-state"><i class="ti ti-file-text"></i><p>No posts generated yet.</p></div>
    <?php endif; ?>
</div>

<?php elseif ($view === 'training'): ?>

<div class="card" style="margin-bottom:1.5rem">
    <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:1rem"><i class="ti ti-plus"></i> Add Training Data</h3>
    <form method="post" action="post-generator.php">
        <input type="hidden" name="action" value="train_add">
        <div class="form-group">
            <label>Content</label>
            <textarea class="form-control" name="content" rows="5" placeholder="Paste articles, notes, ideas, insights, or any content to train the AI..." required></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Type</label>
                <select class="form-control" name="type">
                    <option value="topic">Topic / Idea</option>
                    <option value="style">Writing Style</option>
                    <option value="brand">Brand Info</option>
                </select>
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end">
                <button type="submit" class="btn btn-primary" style="width:100%"><i class="ti ti-device-floppy"></i> Save</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <?php if (count($trainingData)): ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Type</th><th>Content Preview</th><th>Date</th><th style="text-align:right">Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($trainingData as $td): ?>
                <tr>
                    <td><span class="status-badge <?= $td['type']==='topic'?'status-draft':($td['type']==='style'?'status-published':'') ?>" style="font-size:0.6rem;text-transform:uppercase"><?= $td['type'] ?></span></td>
                    <td style="max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($td['content']) ?></td>
                    <td class="text-muted"><?= date('M j, Y', strtotime($td['created_at'])) ?></td>
                    <td style="text-align:right"><button class="btn btn-danger btn-sm" onclick="deleteTraining(<?= $td['id'] ?>)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state"><i class="ti ti-database"></i><p>No training data yet. Add articles, notes, or style guides to train the AI.</p></div>
    <?php endif; ?>
</div>

<?php elseif ($view === 'profiles'): ?>

<div class="card" style="margin-bottom:1.5rem">
    <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:1rem"><i class="ti ti-plus"></i> Add Profile</h3>
    <form method="post" action="post-generator.php">
        <input type="hidden" name="action" value="profile_add">
        <div class="form-row">
            <div class="form-group">
                <label>Platform</label>
                <select class="form-control" name="platform" required>
                    <option value="linkedin">LinkedIn</option>
                    <option value="facebook">Facebook</option>
                </select>
            </div>
            <div class="form-group">
                <label>Profile Name</label>
                <input class="form-control" name="name" placeholder="e.g. Elon Musk, Gary Vee">
            </div>
        </div>
        <div class="form-group">
            <label>Profile URL</label>
            <input class="form-control" name="profile_url" placeholder="https://linkedin.com/in/..." required>
        </div>
        <div class="form-group">
            <label>Notes <span class="text-muted">(What to learn from this profile's style?)</span></label>
            <textarea class="form-control" name="notes" rows="2" placeholder="e.g. Posts are conversational, uses storytelling, focuses on entrepreneurship..."></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save Profile</button>
        </div>
    </form>
</div>

<div class="card">
    <?php if (count($profiles)): ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Platform</th><th>Name</th><th>URL</th><th>Notes</th><th>Date</th><th style="text-align:right">Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $pr): ?>
                <tr>
                    <td><span class="card-priority <?= $pr['platform']==='linkedin'?'priority-high':'priority-medium' ?>" style="font-size:0.6rem"><?= $pr['platform'] ?></span></td>
                    <td><strong style="color:var(--text)"><?= h($pr['name'] ?: '-') ?></strong></td>
                    <td><a href="<?= h($pr['profile_url']) ?>" target="_blank" style="color:var(--blue);font-size:0.78rem;text-decoration:none">Open <i class="ti ti-external-link" style="font-size:10px"></i></a></td>
                    <td class="text-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($pr['notes'] ?: '-') ?></td>
                    <td class="text-muted"><?= date('M j', strtotime($pr['created_at'])) ?></td>
                    <td style="text-align:right"><button class="btn btn-danger btn-sm" onclick="deleteProfile(<?= $pr['id'] ?>)"><i class="ti ti-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state"><i class="ti ti-users"></i><p>No profiles yet. Add LinkedIn or Facebook profile links to train the AI on post style.</p></div>
    <?php endif; ?>
</div>

<?php endif; ?>

<div class="modal-overlay" id="editModal">
    <div class="modal modal-wide">
        <h3 class="modal-title">Edit Post</h3>
        <form onsubmit="return saveEditPost(event)">
            <input type="hidden" id="edit-post-id" value="0">
            <div class="form-group">
                <label>Post Content</label>
                <textarea class="form-control" id="edit-post-content" rows="10" style="font-size:0.82rem;line-height:1.6;white-space:pre-wrap"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function generatePosts(e) {
    e.preventDefault();
    var btn = document.getElementById('gen-btn');
    btn.innerHTML = '<span class="ai-spinner"></span> Generating...';
    btn.disabled = true;

    var trainingIds = [], profileIds = [];
    document.querySelectorAll('input[name="training_ids"]:checked').forEach(function(c) { trainingIds.push(c.value); });
    document.querySelectorAll('input[name="profile_ids"]:checked').forEach(function(c) { profileIds.push(c.value); });

    var fd = new FormData();
    fd.append('action', 'generate');
    fd.append('topic', document.getElementById('gen-topic').value);
    fd.append('training_ids', JSON.stringify(trainingIds));
    fd.append('profile_ids', JSON.stringify(profileIds));

    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate Posts';
        btn.disabled = false;
        if (!j.ok) { showToast(j.error || 'Generation failed', 'error'); return; }
        document.getElementById('gen-result').classList.add('show');
        document.getElementById('li-content').textContent = j.data.linkedin.content;
        document.getElementById('li-id').value = j.data.linkedin.id;
        document.getElementById('fb-content').textContent = j.data.facebook.content;
        document.getElementById('fb-id').value = j.data.facebook.id;
        showToast('Posts generated!');
    })
    .catch(function() {
        btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate Posts';
        btn.disabled = false;
        showToast('Request failed', 'error');
    });
    return false;
}

function copyPost(id) {
    var el = document.getElementById(id).querySelector('.post-content');
    navigator.clipboard.writeText(el.textContent).then(function() { showToast('Copied!'); });
}

function copyText(btn) {
    var content = btn.closest('.post-card').querySelector('.post-content').textContent;
    navigator.clipboard.writeText(content).then(function() { showToast('Copied!'); });
}

function publishPost(btn, platform) {
    var idField = platform === 'linkedin' ? 'li-id' : 'fb-id';
    var id = document.getElementById(idField).value;
    if (!id || id === '0') { showToast('Save the post first', 'error'); return; }
    var fd = new FormData();
    fd.append('action', 'toggle_status');
    fd.append('id', id);
    fd.append('status', 'published');
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.ok) { showToast('Published!'); btn.innerHTML = '<i class="ti ti-check-circle"></i> Published'; btn.disabled = true; }
    });
}

function deleteTraining(id) {
    if (!confirm('Delete this training data?')) return;
    var fd = new FormData();
    fd.append('action', 'train_delete');
    fd.append('id', id);
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) { if (j.ok) location.reload(); });
}

function deleteProfile(id) {
    if (!confirm('Delete this profile?')) return;
    var fd = new FormData();
    fd.append('action', 'profile_delete');
    fd.append('id', id);
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) { if (j.ok) location.reload(); });
}

function deletePost(id) {
    if (!confirm('Delete this post?')) return;
    var fd = new FormData();
    fd.append('action', 'delete_post');
    fd.append('id', id);
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.ok) {
            var el = document.getElementById('history-' + id);
            if (el) { el.remove(); showToast('Deleted'); }
        }
    });
}

function editPost(id) {
    document.getElementById('edit-post-id').value = id;
    document.getElementById('edit-post-content').value = document.getElementById('post-content-' + id).textContent;
    document.getElementById('editModal').classList.add('open');
}

function closeEditModal() { document.getElementById('editModal').classList.remove('open'); }

function saveEditPost(e) {
    e.preventDefault();
    var id = document.getElementById('edit-post-id').value;
    var content = document.getElementById('edit-post-content').value;
    var fd = new FormData();
    fd.append('action', 'update_post');
    fd.append('id', id);
    fd.append('content', content);
    fetch('post-generator.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.ok) {
            document.getElementById('post-content-' + id).textContent = content;
            closeEditModal();
            showToast('Updated');
        }
    });
}

document.getElementById('editModal').addEventListener('click', function(e) { if (e.target === this) closeEditModal(); });
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
