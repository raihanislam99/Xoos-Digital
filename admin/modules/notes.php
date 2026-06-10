<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

$pdo = db();
$isRestMode = ($pdo instanceof SupabaseRestDB);

// ── Auto-setup tables ──
$tablesExist = false;
try {
    $tablesExist = $pdo->query("SELECT count(*) FROM notes") !== false;
} catch (Exception $e) {}

if (!$tablesExist) {
    $createNotesSQL = "CREATE TABLE IF NOT EXISTS notes (
        id SERIAL PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT DEFAULT '',
        category_id INT DEFAULT NULL,
        is_pinned SMALLINT DEFAULT 0,
        note_color VARCHAR(50) DEFAULT NULL,
        tags TEXT DEFAULT '',
        note_type VARCHAR(50) DEFAULT 'note',
        reminder_at TIMESTAMP DEFAULT NULL,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $createCategoriesSQL = "CREATE TABLE IF NOT EXISTS note_categories (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        color VARCHAR(50) DEFAULT '#c8ff00',
        icon VARCHAR(50) DEFAULT 'note',
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $createChecklistSQL = "CREATE TABLE IF NOT EXISTS note_checklist_items (
        id SERIAL PRIMARY KEY,
        note_id INT NOT NULL REFERENCES notes(id) ON DELETE CASCADE,
        text VARCHAR(255) NOT NULL,
        is_checked SMALLINT DEFAULT 0,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Try direct PDO first
    if (!$isRestMode) {
        try {
            $pdo->exec($createNotesSQL);
            $pdo->exec($createCategoriesSQL);
            $pdo->exec($createChecklistSQL);
            $tablesExist = true;
        } catch (Exception $e) {}
    }

    // Fall back to Supabase Management API
    if (!$tablesExist && defined('SUPABASE_MANAGEMENT_API_KEY') && SUPABASE_MANAGEMENT_API_KEY) {
        try {
            $ref = parse_url(SUPABASE_URL, PHP_URL_HOST);
            $ref = explode('.', $ref)[0];
            $mgmtUrl = "https://api.supabase.com/v1/projects/{$ref}/database/query";
            $token = SUPABASE_MANAGEMENT_API_KEY;

            $ch = curl_init($mgmtUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['query' => $createNotesSQL]),
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
                CURLOPT_TIMEOUT => 15,
            ]);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 201) {
                foreach ([$createCategoriesSQL, $createChecklistSQL] as $sql) {
                    $ch = curl_init($mgmtUrl);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => json_encode(['query' => $sql]),
                        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
                        CURLOPT_TIMEOUT => 15,
                    ]);
                    curl_exec($ch);
                    curl_close($ch);
                }
                $tablesExist = true;
            }
        } catch (Exception $e) {}
    }
}

// ── AJAX handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $action = $_POST['action'];

        if ($action === 'create' || $action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $data = [
                'title'       => $_POST['title'] ?? '',
                'content'     => $_POST['content'] ?? '',
                'category_id' => $category_id,
            ];
            if (empty(trim($data['title']))) {
                json_response(['ok' => false, 'error' => 'Title is required'], 400);
            }
            if ($isRestMode) {
                if ($id) {
                    $pdo->restCall('PATCH', "notes?id=eq.{$id}", $data);
                } else {
                    $result = $pdo->restCall('POST', 'notes?select=id', $data, ['Prefer: return=representation']);
                    $id = !empty($result) ? ($result[0]['id'] ?? 0) : 0;
                }
            } else {
                if ($id) {
                    $data['id'] = $id;
                    $pdo->prepare("UPDATE notes SET title=:title, content=:content, category_id=:category_id WHERE id=:id")->execute($data);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO notes (title, content, category_id) VALUES (:title, :content, :category_id)");
                    $stmt->execute($data);
                    $id = $pdo->lastInsertId();
                }
            }
            json_response(['ok' => true, 'id' => (int)$id]);
        }

        if ($action === 'create_with_items') {
            $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $data = [
                'title'       => $_POST['title'] ?? '',
                'content'     => $_POST['content'] ?? '',
                'category_id' => $category_id,
            ];
            if (empty(trim($data['title']))) {
                json_response(['ok' => false, 'error' => 'Title is required'], 400);
            }
            if ($isRestMode) {
                $result = $pdo->restCall('POST', 'notes?select=id', $data, ['Prefer: return=representation']);
                $newId = !empty($result) ? ($result[0]['id'] ?? 0) : 0;
            } else {
                $stmt = $pdo->prepare("INSERT INTO notes (title, content, category_id) VALUES (:title, :content, :category_id)");
                $stmt->execute($data);
                $newId = $pdo->lastInsertId();
            }

            $items = json_decode($_POST['checklist_items'] ?? '[]', true);
            if (!empty($items)) {
                foreach ($items as $i => $item) {
                    $text = trim($item['text'] ?? '');
                    if ($text) {
                        $pdo->prepare("INSERT INTO note_checklist_items (note_id, text, sort_order) VALUES (?, ?, ?)")->execute([$newId, $text, $i]);
                    }
                }
            }
            json_response(['ok' => true, 'id' => $newId]);
        }

        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM notes WHERE id = ?")->execute([(int)($_POST['id'] ?? 0)]);
            json_response(['ok' => true]);
        }

        if ($action === 'toggle_pin') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT is_pinned FROM notes WHERE id = ?");
            $stmt->execute([$id]);
            $current = (int)$stmt->fetchColumn();
            $pdo->prepare("UPDATE notes SET is_pinned = ? WHERE id = ?")->execute([$current ? 0 : 1, $id]);
            json_response(['ok' => true, 'is_pinned' => $current ? 0 : 1]);
        }

        if ($action === 'get_note') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
            $stmt->execute([$id]);
            $note = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$note) json_response(['ok' => false, 'error' => 'Not found'], 404);
            // Attach category info
            $cid = $note['category_id'] ?? null;
            if ($cid) {
                try {
                    $cs = $pdo->prepare("SELECT name, color FROM note_categories WHERE id = ?");
                    $cs->execute([$cid]);
                    $cat = $cs->fetch(PDO::FETCH_ASSOC);
                    $note['category_name'] = $cat ? $cat['name'] : '';
                    $note['category_color'] = $cat ? $cat['color'] : '';
                } catch (Exception $e) {
                    $note['category_name'] = '';
                    $note['category_color'] = '';
                }
            } else {
                $note['category_name'] = '';
                $note['category_color'] = '';
            }
            // Get checklist items and stats
            $ci = $pdo->prepare("SELECT * FROM note_checklist_items WHERE note_id = ? ORDER BY sort_order, id");
            $ci->execute([$id]);
            $checklist = $ci->fetchAll();
            $note['checklist'] = $checklist;
            // Calculate checklist stats
            $total = count($checklist);
            $done = 0;
            foreach ($checklist as $item) {
                if ($item['is_checked'] == 1) $done++;
            }
            $note['checklistStats'] = ['total' => $total, 'done' => $done];
            json_response(['ok' => true, 'note' => $note]);
        }

        if ($action === 'checklist_add') {
            $note_id = (int)($_POST['note_id'] ?? 0);
            $text = trim($_POST['text'] ?? '');
            if (empty($text)) json_response(['ok' => false, 'error' => 'Text required'], 400);
            $maxSort = 0;
            try {
                $stmt = $pdo->prepare("SELECT sort_order FROM note_checklist_items WHERE note_id = ? ORDER BY sort_order DESC");
                $stmt->execute([$note_id]);
                $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($existing)) $maxSort = (int)$existing[0];
            } catch (Exception $e) {}
            $pdo->prepare("INSERT INTO note_checklist_items (note_id, text, sort_order) VALUES (?, ?, ?)")->execute([$note_id, $text, $maxSort + 1]);
            json_response(['ok' => true, 'id' => $pdo->lastInsertId()]);
        }

        if ($action === 'checklist_toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT is_checked FROM note_checklist_items WHERE id = ?");
            $stmt->execute([$id]);
            $current = (int)$stmt->fetchColumn();
            $pdo->prepare("UPDATE note_checklist_items SET is_checked = ? WHERE id = ?")->execute([$current ? 0 : 1, $id]);
            json_response(['ok' => true, 'is_checked' => $current ? 0 : 1]);
        }

        if ($action === 'checklist_delete') {
            $pdo->prepare("DELETE FROM note_checklist_items WHERE id = ?")->execute([(int)($_POST['id'] ?? 0)]);
            json_response(['ok' => true]);
        }

        if ($action === 'add_category') {
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) json_response(['ok' => false, 'error' => 'Name required'], 400);
            $stmt = $pdo->prepare("INSERT INTO note_categories (name) VALUES (?)");
            $stmt->execute([$name]);
            json_response(['ok' => true, 'id' => $pdo->lastInsertId(), 'name' => $name]);
        }

        json_response(['ok' => false, 'error' => 'Unknown action'], 400);
    } catch (Exception $e) {
        json_response(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}

// ── If tables don't exist, show setup page ──
if (!$tablesExist) {
    $setupSql = "CREATE TABLE IF NOT EXISTS notes (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT DEFAULT '',
    category_id INT DEFAULT NULL,
    is_pinned SMALLINT DEFAULT 0,
    note_color VARCHAR(50) DEFAULT NULL,
    tags TEXT DEFAULT '',
    note_type VARCHAR(50) DEFAULT 'note',
    reminder_at TIMESTAMP DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS note_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(50) DEFAULT '#c8ff00',
    icon VARCHAR(50) DEFAULT 'note',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS note_checklist_items (
    id SERIAL PRIMARY KEY,
    note_id INT NOT NULL REFERENCES notes(id) ON DELETE CASCADE,
    text VARCHAR(255) NOT NULL,
    is_checked SMALLINT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
    ?>
    <?php require_once __DIR__ . '/../inc/header.php'; ?>
    <style>
    .setup-card { max-width:720px;margin:3rem auto;background:var(--bg2);border:1px solid var(--border);border-radius:20px;padding:2.5rem;text-align:center }
    .setup-card i { font-size:3rem;color:var(--accent);margin-bottom:1rem;display:block }
    .setup-card h2 { font-size:1.2rem;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.75rem }
    .setup-card p { color:var(--text3);font-size:0.88rem;line-height:1.6;margin-bottom:1.5rem }
    .setup-card .sql-box { background:#0a0e15;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:1.25rem;text-align:left;font-family:'Courier New',monospace;font-size:0.78rem;color:var(--text2);line-height:1.5;white-space:pre-wrap;overflow-x:auto;margin-bottom:1.5rem;user-select:all }
    .setup-card .btn { display:inline-flex;align-items:center;gap:8px }
    </style>
    <div class="setup-card">
        <i class="ti ti-database"></i>
        <h2>Notes Module — Setup Required</h2>
        <p>Could not auto-create the database tables. Run this SQL in your Supabase Dashboard SQL Editor:</p>
        <div class="sql-box" id="setupSql"><?= h($setupSql) ?></div>
        <button class="btn btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('setupSql').textContent);showToast('SQL copied!')"><i class="ti ti-copy"></i> Copy SQL</button>
        <p style="margin-top:1rem;font-size:0.78rem;color:var(--text3)">Paste into <strong>Supabase Dashboard → SQL Editor</strong> → Run → then reload this page</p>
    </div>
    <?php require_once __DIR__ . '/../inc/footer.php'; exit;
}

// ── Fetch data ──
$notes = [];
$checklistStats = [];
$categories = [];
$totalNotes = 0;
$pinnedCount = 0;
$totalChecklistItems = 0;
$totalChecked = 0;
$checklistRate = 0;

try {
    $where = [];
    $params = [];
    if (!empty($_GET['category_id'])) {
        $where[] = 'category_id = ?';
        $params[] = (int)$_GET['category_id'];
    }
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT * FROM notes $whereClause ORDER BY is_pinned DESC, created_at DESC LIMIT 200");
    $stmt->execute($params);
    $notes = $stmt->fetchAll();

    // Attach category info (avoid JOIN — SupabaseRestDB doesn't support them)
    $catMap = [];
    try {
        $cats = $pdo->query("SELECT * FROM note_categories ORDER BY sort_order, name")->fetchAll();
        foreach ($cats as $c) { $catMap[$c['id']] = $c; }
    } catch (Exception $e) {}
    foreach ($notes as &$n) {
        $cid = $n['category_id'] ?? null;
        $n['category_name'] = $cid && isset($catMap[$cid]) ? $catMap[$cid]['name'] : '';
        $n['category_color'] = $cid && isset($catMap[$cid]) ? $catMap[$cid]['color'] : '#6b7280';
    }
    unset($n);

    if (!empty($_GET['search'])) {
        $search = $_GET['search'];
        $notes = array_values(array_filter($notes, function($n) use ($search) {
            return stripos($n['title'] ?? '', $search) !== false || stripos($n['content'] ?? '', $search) !== false;
        }));
    }

    $noteIds = array_column($notes, 'id');
    if (!empty($noteIds)) {
        $idsStr = implode(',', array_map('intval', $noteIds));
        $items = $pdo->query("SELECT * FROM note_checklist_items WHERE note_id IN ($idsStr)")->fetchAll();
        foreach ($items as $item) {
            $nid = $item['note_id'];
            if (!isset($checklistStats[$nid])) $checklistStats[$nid] = ['total' => 0, 'done' => 0];
            $checklistStats[$nid]['total']++;
            if ($item['is_checked']) $checklistStats[$nid]['done']++;
        }
    }

    $categories = $cats;

    $totalNotes = count($notes);
    $pinnedCount = count(array_filter($notes, fn($n) => $n['is_pinned']));
    $totalChecklistItems = array_sum(array_column($checklistStats, 'total'));
    $totalChecked = array_sum(array_column($checklistStats, 'done'));
    $checklistRate = $totalChecklistItems ? round($totalChecked / $totalChecklistItems * 100) : 0;
} catch (Exception $e) {}

$activeFilters = !empty($_GET['category_id']) || !empty($_GET['search']);
?>
<?php require_once __DIR__ . '/../inc/header.php'; ?>

<style>
:root {
  --glass-bg: rgba(255,255,255,0.04);
  --glass-border: rgba(255,255,255,0.08);
}
@keyframes fadeIn { from { opacity:0;transform:translateY(8px); } to { opacity:1;transform:translateY(0); } }
@keyframes modalSlideUp { from { opacity:0;transform:translateY(20px); } to { opacity:1;transform:translateY(0); } }
@keyframes slideIn { from { opacity:0;transform:translateX(-10px); } to { opacity:1;transform:translateX(0); } }

.stats-row { display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:0.75rem;margin-bottom:1.5rem; }
.stat-card { background:var(--glass-bg);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid var(--glass-border);border-radius:14px;padding:1.1rem 1rem;text-align:center;border-top:3px solid transparent; }
.stat-card:nth-child(1) { border-top-color:#fff; }
.stat-card:nth-child(2) { border-top-color:#ff8c42; }
.stat-card:nth-child(3) { border-top-color:#4f8ef7; }
.stat-card:nth-child(4) { border-top-color:#2ecc71; }
.stat-card:nth-child(5) { border-top-color:#c8ff00; }
.stat-value { font-size:2rem;font-weight:700;line-height:1.1; }
.stat-card:nth-child(1) .stat-value { color:#fff; }
.stat-card:nth-child(2) .stat-value { color:#ff8c42; }
.stat-card:nth-child(3) .stat-value { color:#4f8ef7; }
.stat-card:nth-child(4) .stat-value { color:#2ecc71; }
.stat-card:nth-child(5) .stat-value { color:#c8ff00; }
.stat-label { font-size:0.68rem;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.12em;margin-top:6px;font-weight:600; }

.toolbar { display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap; }
.filters { display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap; }
.filters select, .filters input { background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:var(--text2);padding:0.5rem 0.75rem;border-radius:8px;font-size:0.78rem;outline:none;font-family:'Inter',sans-serif; }
.filters select { background-color:#0e1420;color-scheme:dark;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;appearance:none;-webkit-appearance:none;padding-right:2rem; }
.filters select option { background:#0e1420;color:var(--text); }
.filters select:focus, .filters input:focus { border-color:var(--accent);box-shadow:0 0 0 3px rgba(200,255,0,0.1); }
.search-wrap { position:relative; }
.search-wrap i { position:absolute;left:10px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,0.3);font-size:0.85rem;pointer-events:none; }
.search-wrap input { padding-left:30px !important;min-width:180px; }
.filter-wrap { position:relative;display:inline-flex; }
.filter-wrap i { position:absolute;left:10px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,0.3);font-size:0.85rem;pointer-events:none;z-index:1; }
.filter-wrap select { padding-left:30px !important; }
.btn-new { display:inline-flex;align-items:center;gap:6px;background:var(--accent);color:#080B10;border:none;font-weight:700;padding:0.6rem 1.2rem;border-radius:10px;font-size:0.85rem;cursor:pointer;font-family:'Inter',sans-serif;transition:all 0.15s;white-space:nowrap; }
.btn-new:hover { filter:brightness(1.1);transform:translateY(-1px); }
.btn-new:active { transform:scale(0.97); }
.btn-new i { font-size:1.1rem; }
.clear-filters { font-size:0.72rem;color:var(--text3);cursor:pointer;text-decoration:none; }
.clear-filters:hover { color:var(--accent); }

.note-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem; }
.note-card { background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:1.25rem;cursor:pointer;transition:all 0.2s ease;animation:fadeIn 0.25s ease-out;position:relative;overflow:hidden;border-left:3px solid #6b7280; }
.note-card:hover { border-color:rgba(200,255,0,0.2);transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,0.3); }
.note-card.pinned { border-left-color:#ff8c42 !important; }
.note-card .pin-badge { position:absolute;top:10px;right:10px;font-size:0.75rem;color:#ff8c42; }
.note-card .card-title { font-size:0.9rem;font-weight:700;color:var(--text);margin-bottom:6px;padding-right:20px; }
.note-card .card-category { display:inline-block;padding:1px 10px;border-radius:4px;font-size:0.6rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px; }
.note-card .card-preview { font-size:0.78rem;color:var(--text3);line-height:1.5;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:10px; }
.note-card .card-footer { display:flex;align-items:center;justify-content:space-between;font-size:0.68rem;color:var(--text3);border-top:1px solid rgba(255,255,255,0.04);padding-top:10px; }
.checklist-progress { display:flex;align-items:center;gap:6px; }
.checklist-progress .bar { width:60px;height:4px;background:rgba(255,255,255,0.08);border-radius:99px;overflow:hidden; }
.checklist-progress .bar-fill { height:100%;background:#2ecc71;border-radius:99px;transition:width 0.3s; }
.card-actions { display:none;gap:4px; }
.note-card:hover .card-actions { display:flex; }
.card-actions button { background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.07);color:var(--text2);width:26px;height:26px;border-radius:6px;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;transition:all 0.1s; }
.card-actions button:hover { background:rgba(255,255,255,0.15);color:var(--text); }
.note-type-badge { font-size:0.55rem;padding:1px 6px;border-radius:3px;background:rgba(255,255,255,0.05);color:var(--text3);text-transform:uppercase;letter-spacing:0.05em; }

.empty-state { grid-column:1/-1;text-align:center;padding:4rem 1.5rem;color:var(--text3); }

.ai-spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid var(--accent);
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
  margin-right: 6px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
.empty-state i { font-size:3rem;margin-bottom:1rem;display:block;color:var(--accent);opacity:0.4; }
.empty-state p { font-size:0.85rem; }

/* ── Modal ── */
.modal-overlay { position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);z-index:1000;display:none;align-items:center;justify-content:center;padding:1rem; }
.modal-overlay.open { display:flex; }
.modal { background:#0e1420;border:1px solid rgba(255,255,255,0.08);border-radius:20px;box-shadow:0 24px 64px rgba(0,0,0,0.6);width:100%;max-height:90vh;overflow-y:auto;animation:modalSlideUp 0.25s ease;padding:0; }
.modal-sm { max-width:520px; }
.modal-lg { max-width:780px; }
.modal-title { font-size:1rem;font-weight:700;color:var(--text);padding:1.25rem 1.5rem 0.5rem;margin:0; }
.modal-body { padding:0 1.5rem 1.5rem; }
.form-group { margin-bottom:1rem; }
.form-group label { display:block;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.5);margin-bottom:6px;font-weight:600; }
.form-control { width:100%;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);color:var(--text);padding:12px 14px;border-radius:10px;font-size:0.85rem;outline:none;font-family:'Inter',sans-serif;transition:border-color 0.15s;box-sizing:border-box; }
.form-control:focus { border-color:var(--accent);box-shadow:0 0 0 3px rgba(200,255,0,0.1); }
textarea.form-control { resize:vertical;min-height:100px; }
select.form-control { appearance:none;-webkit-appearance:none;background-color:#0e1420;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;color-scheme:dark; }
select.form-control option { background:#0e1420;color:var(--text); }
.form-row { display:grid;grid-template-columns:1fr 1fr;gap:1rem; }
.form-actions { display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.5rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,0.06); }
.btn { display:inline-flex;align-items:center;gap:6px;padding:0.6rem 1.2rem;border-radius:10px;font-size:0.82rem;font-weight:600;cursor:pointer;transition:all 0.15s;font-family:'Inter',sans-serif;border:none; }
.btn-primary { background:var(--accent);color:#000;font-weight:700; }
.btn-primary:hover { filter:brightness(1.1); }
.btn-primary:active { transform:scale(0.97); }
.btn-secondary { background:transparent;border:1px solid rgba(255,255,255,0.1);color:var(--text2); }
.btn-secondary:hover { background:rgba(255,255,255,0.06);color:var(--text); }
.btn-sm { padding:0.4rem 0.8rem;font-size:0.75rem;border-radius:8px; }
.btn-danger { background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.2);color:#ef4444; }
.btn-danger:hover { background:rgba(239,68,68,0.25); }
.btn-icon { width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:8px; }

/* ── Checklist in modal (create/edit) ── */
.modal-checklist { background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:0.75rem;margin-bottom:0.5rem; }
.modal-checklist .mcl-item { display:flex;align-items:center;gap:8px;padding:4px 0;animation:slideIn 0.2s ease; }
.modal-checklist .mcl-item input { flex:1;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);color:var(--text);padding:8px 10px;border-radius:6px;font-size:0.82rem;outline:none;font-family:'Inter',sans-serif; }
.modal-checklist .mcl-item input:focus { border-color:var(--accent); }
.modal-checklist .mcl-item .mcl-del { width:24px;height:24px;border:none;background:rgba(239,68,68,0.1);color:#ef4444;border-radius:4px;cursor:pointer;font-size:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.modal-checklist .mcl-item .mcl-del:hover { background:rgba(239,68,68,0.2); }
.modal-checklist .mcl-add { display:flex;align-items:center;gap:6px;margin-top:6px;padding-top:6px;border-top:1px dashed rgba(255,255,255,0.06); }
.modal-checklist .mcl-add input { flex:1;background:transparent;border:none;color:var(--text3);font-size:0.82rem;outline:none;font-family:'Inter',sans-serif; }
.modal-checklist .mcl-add input::placeholder { color:var(--text3); }
.modal-checklist .mcl-add button { background:transparent;border:1px dashed rgba(255,255,255,0.15);color:var(--text3);padding:4px 10px;border-radius:6px;font-size:0.72rem;cursor:pointer;white-space:nowrap;transition:all 0.15s; }
.modal-checklist .mcl-add button:hover { border-color:var(--accent);color:var(--accent); }

/* ── Read modal ── */
.read-note-title { font-size:1.2rem;font-weight:700;color:var(--text);margin-bottom:4px;padding-right:30px; }
.read-note-meta { font-size:0.72rem;color:var(--text3);margin-bottom:1rem;display:flex;align-items:center;gap:12px;flex-wrap:wrap; }
.read-note-meta .cat-badge { padding:1px 10px;border-radius:4px;font-size:0.6rem;font-weight:600;text-transform:uppercase; }
.read-note-content { font-size:0.88rem;color:var(--text2);line-height:1.7;white-space:pre-wrap;margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid rgba(255,255,255,0.06); }

.checklist-section h4 { font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text3);margin-bottom:0.75rem; }
.checklist-progress-read { display:flex;align-items:center;gap:8px;margin-bottom:1rem; }
.checklist-progress-read .bar { flex:1;height:4px;background:rgba(255,255,255,0.08);border-radius:99px;overflow:hidden; }
.checklist-progress-read .bar-fill { height:100%;background:#2ecc71;border-radius:99px;transition:width 0.3s; }
.checklist-progress-read .label { font-size:0.72rem;color:var(--text3);white-space:nowrap; }
.cl-item { display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;transition:background 0.15s; }
.cl-item:hover { background:rgba(255,255,255,0.03); }
.cl-item .cl-check { width:18px;height:18px;border-radius:4px;border:2px solid rgba(255,255,255,0.2);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all 0.15s;font-size:10px;color:transparent; }
.cl-item .cl-check.checked { background:#2ecc71;border-color:#2ecc71;color:#fff; }
.cl-item .cl-text { flex:1;font-size:0.85rem;color:var(--text2);transition:all 0.15s; }
.cl-item .cl-text.done { text-decoration:line-through;color:var(--text3);opacity:0.6; }
.cl-item .cl-del { opacity:0;width:22px;height:22px;border:none;background:rgba(239,68,68,0.1);color:#ef4444;border-radius:4px;cursor:pointer;font-size:10px;display:flex;align-items:center;justify-content:center;transition:all 0.15s;flex-shrink:0; }
.cl-item:hover .cl-del { opacity:1; }
.cl-item .cl-del:hover { background:rgba(239,68,68,0.2); }
.cl-add-form { display:flex;align-items:center;gap:8px;margin-top:0.75rem;padding:0.5rem;border:1px dashed rgba(255,255,255,0.1);border-radius:8px; }
.cl-add-form input { flex:1;background:transparent;border:none;color:var(--text);font-size:0.85rem;outline:none;font-family:'Inter',sans-serif; }
.cl-add-form input::placeholder { color:var(--text3); }
.cl-add-form button { background:var(--accent);color:#000;border:none;font-weight:700;padding:0.4rem 0.8rem;border-radius:6px;font-size:0.75rem;cursor:pointer;font-family:'Inter',sans-serif;white-space:nowrap; }

/* ── Quick bar ── */
.quick-bar { display:flex;align-items:center;gap:8px;margin-top:1.5rem;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:0.75rem 1rem; }
.quick-bar .qb-label { font-size:0.72rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.08em;font-weight:600;white-space:nowrap; }
.quick-bar .qb-input { flex:1;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:var(--text);padding:0.55rem 0.75rem;border-radius:8px;font-size:0.82rem;outline:none;font-family:'Inter',sans-serif; }
.quick-bar .qb-input:focus { border-color:var(--accent); }
.quick-bar .qb-btn { background:var(--accent);color:#000;border:none;font-weight:700;padding:0.55rem 1rem;border-radius:8px;font-size:0.8rem;cursor:pointer;font-family:'Inter',sans-serif;white-space:nowrap; }

@media(max-width:768px){
  .note-grid { grid-template-columns:1fr; }
  .stats-row { grid-template-columns:repeat(3,1fr); }
  .form-row { grid-template-columns:1fr; }
  .toolbar { flex-direction:column;align-items:stretch; }
  .filters { flex-wrap:wrap; }
  .search-wrap input { min-width:140px; }
  .stat-value { font-size:1.5rem; }
  .modal-lg { max-width:100%;margin:0;border-radius:12px; }
}
@media(max-width:480px){
  .stats-row { grid-template-columns:repeat(2,1fr);gap:0.5rem; }
  .quick-bar { flex-wrap:wrap; }
  .quick-bar .qb-input { min-width:100%; }
  .note-card { padding:1rem; }
}
</style>

<div class="page-title" style="font-size:1.6rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:#fff;margin-bottom:24px">
  <i class="ti ti-notes"></i> NOTES
</div>

<div class="stats-row">
  <div class="stat-card"><div class="stat-value"><?= $totalNotes ?></div><div class="stat-label">Total</div></div>
  <div class="stat-card"><div class="stat-value"><?= $pinnedCount ?></div><div class="stat-label">Pinned</div></div>
  <div class="stat-card"><div class="stat-value"><?= $totalChecklistItems ?></div><div class="stat-label">Items</div></div>
  <div class="stat-card"><div class="stat-value"><?= $totalChecked ?></div><div class="stat-label">Done</div></div>
  <div class="stat-card"><div class="stat-value"><?= $checklistRate ?>%</div><div class="stat-label">Complete</div></div>
</div>

<div class="toolbar">
  <div class="filters">
    <div class="filter-wrap"><i class="ti ti-tag"></i>
      <select id="f-category" onchange="applyFilters()">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($_GET['category_id']??'')==(string)$c['id']?'selected':'' ?>><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="search-wrap">
      <i class="ti ti-search"></i>
      <input type="text" id="f-search" placeholder="Search notes..." value="<?= h($_GET['search'] ?? '') ?>" onkeydown="if(event.key==='Enter')applyFilters()">
    </div>
    <?php if ($activeFilters): ?>
      <a href="notes.php" class="clear-filters"><i class="ti ti-x"></i> Clear</a>
    <?php endif; ?>
  </div>
  <button class="btn-new" onclick="openNoteModal()"><i class="ti ti-plus"></i> New Note</button>
</div>

<div class="note-grid">
  <?php if (count($notes)): ?>
    <?php foreach ($notes as $n):
      $cl = $checklistStats[$n['id']] ?? ['total'=>0,'done'=>0];
      $pct = $cl['total'] ? round($cl['done']/$cl['total']*100) : 0;
      $catColor = $n['category_color'] ?? '#6b7280';
      $noteColor = $n['note_color'] ?? $catColor;
    ?>
    <div class="note-card <?= $n['is_pinned']?'pinned':'' ?>" style="border-left-color:<?= h($noteColor) ?>" data-id="<?= $n['id'] ?>" onclick="openReadModal(<?= $n['id'] ?>)">
      <?php if ($n['is_pinned']): ?>
        <span class="pin-badge"><i class="ti ti-pin-filled"></i></span>
      <?php endif; ?>
      <div class="card-title"><?= h($n['title']) ?></div>
      <?php if ($n['category_name']): ?>
        <div class="card-category" style="background:<?= h($catColor) ?>22;color:<?= h($catColor) ?>"><?= h($n['category_name']) ?></div>
      <?php endif; ?>
      <div class="card-preview"><?= h(mb_substr($n['content'] ?: '(no content)', 0, 200)) ?></div>
      <div class="card-footer">
        <div class="checklist-progress">
          <i class="ti ti-checklist" style="font-size:0.75rem"></i>
          <span><?= $cl['done'] ?: 0 ?>/<?= $cl['total'] ?: 0 ?></span>
          <div class="bar"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
        </div>
        <div class="card-actions" onclick="event.stopPropagation()">
          <button onclick="togglePin(<?= $n['id'] ?>)" title="<?= $n['is_pinned']?'Unpin':'Pin' ?>"><i class="ti ti-pin"></i></button>
          <button onclick="editNote(<?= $n['id'] ?>)" title="Edit"><i class="ti ti-pencil"></i></button>
          <button onclick="deleteNote(<?= $n['id'] ?>,'<?= h(addslashes($n['title'])) ?>')" title="Delete"><i class="ti ti-trash"></i></button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="empty-state">
      <i class="ti ti-notes-off"></i>
      <p>No notes yet. Create your first note above.</p>
    </div>
  <?php endif; ?>
</div>

<div class="quick-bar">
  <span class="qb-label">⚡ Quick Add</span>
  <input class="qb-input" id="quickNoteInput" placeholder="Note title... press Enter to add" maxlength="200">
  <button class="qb-btn" onclick="quickAddNote()">+ Add</button>
</div>

<!-- ── Create/Edit Modal ── -->
<div class="modal-overlay" id="noteModal">
  <div class="modal modal-lg">
    <h3 class="modal-title" id="noteModalTitle">New Note</h3>
    <div class="modal-body">
      <form id="noteForm" onsubmit="return saveNote(event)">
        <input type="hidden" name="id" id="nf-id" value="0">
        <div class="form-group">
          <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
            <label style="margin:0">Title *</label>
            <button type="button" class="btn btn-ai btn-sm" id="aiTitleBtn" onclick="aiImproveNoteTitle()" disabled style="white-space:nowrap"><i class="ti ti-sparkles"></i> Improve</button>
          </div>
          <input class="form-control" name="title" id="nf-title" maxlength="200" required placeholder="Note title..." oninput="document.getElementById('titleCounter').textContent=this.value.length+'/200';document.getElementById('aiTitleBtn').disabled=!this.value.trim()">
          <div style="float:right;font-size:0.72rem;color:rgba(255,255,255,0.35);margin-top:4px" id="titleCounter">0/200</div>
        </div>
        <div class="form-group">
          <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
            <label style="margin:0">Content</label>
            <button type="button" class="btn btn-ai btn-sm" id="aiContentBtn" onclick="aiImproveNoteContent()" disabled><i class="ti ti-sparkles"></i> Improve</button>
          </div>
          <textarea class="form-control" name="content" id="nf-content" rows="4" placeholder="Write your note here..." oninput="document.getElementById('aiContentBtn').disabled=!this.value.trim()"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Category</label>
            <div style="display:flex;gap:4px">
              <select class="form-control" name="category_id" id="nf-category" style="flex:1">
                <option value="">None</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= $c['id'] ?>" style="border-left:3px solid <?= h($c['color']?:'#6b7280') ?>"><?= h($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" onclick="addNewCategory()" class="btn btn-sm btn-secondary" title="Add category" style="flex-shrink:0">+</button>
            </div>
          </div>
        </div>

        <!-- Checklist section inside modal -->
        <div class="form-group">
          <label>Checklist Items <span style="text-transform:none;font-weight:400;color:var(--text3)">(optional)</span></label>
          <div class="modal-checklist" id="modalChecklist">
            <div id="mcl-items"></div>
            <div class="mcl-add">
              <input type="text" id="mcl-input" placeholder="Add checklist item..." maxlength="255" onkeydown="if(event.key==='Enter'){event.preventDefault();addModalClItem()}">
              <button type="button" onclick="addModalClItem()">+ Add</button>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary" id="nf-submit"><i class="ti ti-device-floppy"></i> Save</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('noteModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── New Category Modal ── -->
<div class="modal-overlay" id="catModal">
  <div class="modal modal-sm">
    <h3 class="modal-title">New Category</h3>
    <div class="modal-body">
      <div class="form-group">
        <label>Category Name</label>
        <input class="form-control" id="catName" placeholder="e.g. Ideas, Meetings" maxlength="100" onkeydown="if(event.key==='Enter')saveNewCategory()">
      </div>
      <div class="form-actions">
        <button class="btn btn-primary" onclick="saveNewCategory()"><i class="ti ti-plus"></i> Create</button>
        <button class="btn btn-secondary" onclick="closeModal('catModal')">Cancel</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Read Modal ── -->
<div class="modal-overlay" id="readModal">
  <div class="modal modal-lg">
    <div class="modal-body" style="padding-top:1.5rem">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:0.5rem">
        <div>
          <div class="read-note-title" id="readTitle"></div>
          <div class="read-note-meta" id="readMeta"></div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
          <button class="btn btn-sm btn-secondary" onclick="togglePinFromRead()" title="Pin/Unpin"><i class="ti ti-pin"></i></button>
          <button class="btn btn-sm btn-secondary" onclick="editFromRead()"><i class="ti ti-pencil"></i> Edit</button>
          <button class="btn btn-sm btn-secondary" onclick="closeModal('readModal')"><i class="ti ti-x"></i></button>
        </div>
      </div>
      <div class="read-note-content" id="readContent"></div>
      <div class="checklist-section">
        <h4><i class="ti ti-checklist"></i> Checklist <button type="button" class="btn btn-ai btn-sm" onclick="aiGenerateChecklist()" id="aiClBtn" style="margin-left:8px"><i class="ti ti-sparkles"></i> Generate</button></h4>
        <div class="checklist-progress-read">
          <div class="bar"><div class="bar-fill" id="clBar" style="width:0%"></div></div>
          <span class="label" id="clLabel">0/0</span>
        </div>
        <div id="clList"></div>
        <div class="cl-add-form">
          <input type="text" id="clAddInput" placeholder="Add checklist item..." maxlength="255" onkeydown="if(event.key==='Enter')addChecklistItem()">
          <button onclick="addChecklistItem()">+ Add</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var currentReadNoteId = 0;
var currentNoteContent = '';
var modalClItems = [];

function createNoteCard(note) {
  var card = document.createElement('div');
  card.className = 'note-card' + (note.is_pinned ? ' pinned' : '');
  var noteColor = note.note_color || note.category_color || '#6b7280';
  card.style.borderLeftColor = noteColor;
  card.setAttribute('data-id', note.id);
  var cl = note.checklistStats || {total:0, done:0};
  var pct = cl.total ? Math.round(cl.done / cl.total * 100) : 0;
  var catColor = note.category_color || '#6b7280';
  var preview = note.content ? note.content.substring(0, 200) : '(no content)';
  
  var html = '';
  if (note.is_pinned) {
    html += '<span class="pin-badge"><i class="ti ti-pin-filled"></i></span>';
  }
  html += '<div class="card-title">' + escapeHtml(note.title) + '</div>';
  if (note.category_name) {
    html += '<div class="card-category" style="background:' + catColor + '22;color:' + catColor + '">' + escapeHtml(note.category_name) + '</div>';
  }
  html += '<div class="card-preview">' + escapeHtml(preview) + '</div>';
  html += '<div class="card-footer">';
  html += '<div class="checklist-progress"><i class="ti ti-checklist" style="font-size:0.75rem"></i><span>' + cl.done + '/' + cl.total + '</span><div class="bar"><div class="bar-fill" style="width:' + pct + '%"></div></div></div>';
  html += '<div class="card-actions" onclick="event.stopPropagation()">';
  html += '<button onclick="togglePin(' + note.id + ')" title="' + (note.is_pinned ? 'Unpin' : 'Pin') + '"><i class="ti ti-pin"></i></button>';
  html += '<button onclick="editNote(' + note.id + ')" title="Edit"><i class="ti ti-pencil"></i></button>';
  var safeTitle = escapeHtml(note.title).replace(/'/g, "\\'");
  html += '<button onclick="deleteNote(' + note.id + ',\'' + safeTitle + '\')" title="Delete"><i class="ti ti-trash"></i></button>';
  html += '</div>';
  html += '</div>';
  
  card.innerHTML = html;
  card.addEventListener('click', function() {
    openReadModal(note.id);
  });
  return card;
}

function updateStats() {
  var cards = document.querySelectorAll('.note-card');
  var totalNotes = cards.length;
  var pinnedCount = document.querySelectorAll('.note-card.pinned').length;
  var totalItems = 0, totalDone = 0;
  cards.forEach(function(card) {
    var clSpan = card.querySelector('.checklist-progress span');
    if (clSpan) {
      var parts = clSpan.textContent.split('/');
      if (parts.length === 2) {
        totalItems += parseInt(parts[1]);
        totalDone += parseInt(parts[0]);
      }
    }
  });
  var checklistsRate = totalItems ? Math.round(totalDone / totalItems * 100) : 0;
  
  var statCards = document.querySelectorAll('.stat-card');
  if (statCards[0]) statCards[0].querySelector('.stat-value').textContent = totalNotes;
  if (statCards[1]) statCards[1].querySelector('.stat-value').textContent = pinnedCount;
  if (statCards[2]) statCards[2].querySelector('.stat-value').textContent = totalItems;
  if (statCards[3]) statCards[3].querySelector('.stat-value').textContent = totalDone;
  if (statCards[4]) statCards[4].querySelector('.stat-value').textContent = checklistsRate + '%';
  
  var emptyState = document.querySelector('.empty-state');
  if (totalNotes === 0 && !emptyState) {
    var grid = document.querySelector('.note-grid');
    emptyState = document.createElement('div');
    emptyState.className = 'empty-state';
    emptyState.innerHTML = '<i class="ti ti-notes-off"></i><p>No notes yet. Create your first note above.</p>';
    grid.appendChild(emptyState);
  } else if (totalNotes > 0 && emptyState) {
    emptyState.remove();
  }
}

function getNoteGrid() {
  return document.querySelector('.note-grid');
}

function applyFilters() {
  var params = new URLSearchParams();
  var cat = document.getElementById('f-category').value;
  var search = document.getElementById('f-search').value;
  if (cat) params.set('category_id', cat);
  if (search) params.set('search', search);
  window.location.search = params.toString();
}

function openNoteModal(data) {
  modalClItems = [];
  document.getElementById('mcl-items').innerHTML = '';

  if (data) {
    document.getElementById('noteModalTitle').textContent = 'Edit Note';
    document.getElementById('nf-id').value = data.id || 0;
    document.getElementById('nf-title').value = data.title || '';
    document.getElementById('nf-content').value = data.content || '';
    document.getElementById('nf-category').value = data.category_id || '';
    document.getElementById('titleCounter').textContent = (data.title||'').length + '/200';
    document.getElementById('nf-submit').textContent = 'Update Note';
  } else {
    document.getElementById('noteModalTitle').textContent = 'New Note';
    document.getElementById('nf-id').value = 0;
    document.getElementById('nf-title').value = '';
    document.getElementById('nf-content').value = '';
    document.getElementById('nf-category').value = '';
    document.getElementById('titleCounter').textContent = '0/200';
    document.getElementById('nf-submit').textContent = 'Save Note';
    // Add one empty checklist row for new notes
    addModalClItem();
  }
  // Sync AI button states (oninput doesn't fire on programmatic .value=)
  document.getElementById('aiTitleBtn').disabled = !document.getElementById('nf-title').value.trim();
  document.getElementById('aiContentBtn').disabled = !document.getElementById('nf-content').value.trim();
  document.getElementById('noteModal').classList.add('open');
  setTimeout(function() { document.getElementById('nf-title').focus(); }, 100);
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

document.getElementById('noteModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal('noteModal');
});
document.getElementById('readModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal('readModal');
});
document.getElementById('catModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal('catModal');
});

function addModalClItem(text) {
  var container = document.getElementById('mcl-items');
  var i = modalClItems.length;
  modalClItems.push({ text: text || '' });
  var div = document.createElement('div');
  div.className = 'mcl-item';
  div.dataset.idx = i;
  div.innerHTML = '<input type="text" value="' + escapeHtml(text || '') + '" placeholder="Item ' + (i+1) + '" maxlength="255" oninput="modalClItems[' + i + '].text=this.value">' +
    '<button type="button" class="mcl-del" onclick="removeModalClItem(' + i + ')" title="Remove"><i class="ti ti-x"></i></button>';
  container.appendChild(div);
  var inp = div.querySelector('input');
  if (!text) setTimeout(function() { inp.focus(); }, 50);
}

function removeModalClItem(idx) {
  var items = document.querySelectorAll('.mcl-item');
  if (items[idx]) items[idx].remove();
  modalClItems.splice(idx, 1);
  // Re-index
  document.querySelectorAll('.mcl-item').forEach(function(el, i) {
    el.dataset.idx = i;
    var inp = el.querySelector('input');
    inp.oninput = function() { modalClItems[i].text = this.value; };
    el.querySelector('.mcl-del').onclick = function() { removeModalClItem(i); };
  });
}

function saveNote(e) {
  e.preventDefault();
  var fd = new FormData(document.getElementById('noteForm'));
  var id = parseInt(fd.get('id'));

  if (id) {
    fd.set('action', 'update');
    fetch('notes.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
      if (j.ok) {
        showToast('Note updated');
        closeModal('noteModal');
        // Refresh the note card by reloading the note data
        var getFd = new FormData();
        getFd.append('action', 'get_note');
        getFd.append('id', id);
        fetch('notes.php', { method: 'POST', body: getFd })
        .then(function(r) { return r.json(); })
        .then(function(j2) {
          if (j2.ok && j2.note) {
            var oldCard = document.querySelector('.note-card[data-id="' + id + '"]');
            var newCard = createNoteCard(j2.note);
            oldCard.replaceWith(newCard);
            updateStats();
          }
        });
      } else {
        showToast('Error: ' + (j.error || 'Unknown'), 'error');
      }
    })
    .catch(function() { showToast('Request failed', 'error'); });
  } else {
    // Create with checklist items
    var items = modalClItems.filter(function(it) { return it.text.trim(); });
    fd.set('action', 'create_with_items');
    fd.append('checklist_items', JSON.stringify(items));
    fetch('notes.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
      if (j.ok) {
        showToast('Note created');
        closeModal('noteModal');
        // Get the new note data and add it to the grid
        var getFd = new FormData();
        getFd.append('action', 'get_note');
        getFd.append('id', j.id);
        fetch('notes.php', { method: 'POST', body: getFd })
        .then(function(r) { return r.json(); })
        .then(function(j2) {
          if (j2.ok && j2.note) {
            var grid = getNoteGrid();
            var newCard = createNoteCard(j2.note);
            // Insert at top if pinned or no pinned notes, else after pinned notes
            var firstNotPinned = grid.querySelector('.note-card:not(.pinned)');
            if (firstNotPinned) {
              grid.insertBefore(newCard, firstNotPinned);
            } else {
              grid.appendChild(newCard);
            }
            updateStats();
          }
        });
      } else {
        showToast('Error: ' + (j.error || 'Unknown'), 'error');
      }
    })
    .catch(function() { showToast('Request failed', 'error'); });
  }
  return false;
}

function editNote(id) {
  closeModal('readModal');
  var fd = new FormData();
  fd.append('action', 'get_note');
  fd.append('id', id);
  fetch('notes.php', { method: 'POST', body: fd })
  .then(function(r) { return r.json(); })
  .then(function(j) {
    if (j.ok && j.note) openNoteModal(j.note);
    else showToast('Could not load note', 'error');
  })
  .catch(function() { showToast('Request failed', 'error'); });
}

function editFromRead() {
  if (currentReadNoteId) editNote(currentReadNoteId);
}

function deleteNote(id, title) {
  confirmDelete('#', title);
  document.getElementById('deleteConfirmBtn').onclick = function(e) {
    e.preventDefault();
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('notes.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
      if (j.ok) {
        closeDeleteModal();
        closeModal('readModal');
        var card = document.querySelector('.note-card[data-id="' + id + '"]');
        if (card) {
          card.style.transition = 'all 0.2s';
          card.style.opacity = '0';
          card.style.transform = 'scale(0.95)';
          setTimeout(function() {
            card.remove();
            updateStats();
          }, 250);
        }
        showToast('Note deleted');
      }
    });
  };
}

function quickAddNote() {
  var input = document.getElementById('quickNoteInput');
  var title = input.value.trim();
  if (!title) return;
  var fd = new FormData();
  fd.append('action', 'create');
  fd.append('title', title);
  fd.append('content', '');
  fd.append('category_id', '');
  fetch('notes.php', { method: 'POST', body: fd })
  .then(function(r) { return r.json(); })
  .then(function(j) {
    if (j.ok) {
      input.value = '';
      showToast('Note created');
      // Get new note and add to grid
      var getFd = new FormData();
      getFd.append('action', 'get_note');
      getFd.append('id', j.id);
      fetch('notes.php', { method: 'POST', body: getFd })
      .then(function(r) { return r.json(); })
      .then(function(j2) {
        if (j2.ok && j2.note) {
          var grid = getNoteGrid();
          var newCard = createNoteCard(j2.note);
          var firstNotPinned = grid.querySelector('.note-card:not(.pinned)');
          if (firstNotPinned) {
            grid.insertBefore(newCard, firstNotPinned);
          } else {
            grid.appendChild(newCard);
          }
          updateStats();
        }
      });
    } else {
      showToast('Error: ' + (j.error || 'Unknown'), 'error');
    }
  })
  .catch(function() { showToast('Request failed', 'error'); });
}

document.getElementById('quickNoteInput').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') quickAddNote();
});

// ── Category ──
function addNewCategory() {
  document.getElementById('catName').value = '';
  document.getElementById('catModal').classList.add('open');
  setTimeout(function() { document.getElementById('catName').focus(); }, 100);
}

function saveNewCategory() {
  var name = document.getElementById('catName').value.trim();
  if (!name) return;
  var fd = new FormData();
  fd.append('action', 'add_category');
  fd.append('name', name);
  fetch('notes.php', { method: 'POST', body: fd })
  .then(function(r) { return r.json(); })
  .then(function(j) {
    if (j.ok) {
      closeModal('catModal');
      location.reload();
    } else {
      showToast('Error: ' + (j.error || 'Unknown'), 'error');
    }
  })
  .catch(function() { showToast('Request failed', 'error'); });
}

// ── Pin toggle ──
function togglePin(id) {
  var fd = new FormData();
  fd.append('action', 'toggle_pin');
  fd.append('id', id);
  fetch('notes.php', { method: 'POST', body: fd })
  .then(function(r) { return r.json(); })
  .then(function(j) {
    if (j.ok) {
      // Refresh note data and update the card
      var getFd = new FormData();
      getFd.append('action', 'get_note');
      getFd.append('id', id);
      fetch('notes.php', { method: 'POST', body: getFd })
      .then(function(r) { return r.json(); })
      .then(function(j2) {
        if (j2.ok && j2.note) {
          var oldCard = document.querySelector('.note-card[data-id="' + id + '"]');
          var newCard = createNoteCard(j2.note);
          // Reposition: move to top if pinned
          var grid = getNoteGrid();
          if (j2.note.is_pinned) {
            // Insert at top (before first pinned or first child)
            var first = grid.firstChild;
            grid.insertBefore(newCard, first);
          } else {
            // Insert after pinned notes
            var lastPinned = grid.querySelector('.note-card.pinned:last-child');
            if (lastPinned) {
              lastPinned.after(newCard);
            } else {
              grid.appendChild(newCard);
            }
          }
          oldCard.remove();
          updateStats();
        }
      });
    }
  });
}

function togglePinFromRead() {
  if (currentReadNoteId) togglePin(currentReadNoteId);
}

// ── Read modal ──
function openReadModal(id) {
  currentReadNoteId = id;
  var fd = new FormData();
  fd.append('action', 'get_note');
  fd.append('id', id);
  fetch('notes.php', { method: 'POST', body: fd })
  .then(function(r) { return r.json(); })
  .then(function(j) {
    if (!j.ok || !j.note) { showToast('Could not load note', 'error'); return; }
    var note = j.note;
    currentNoteContent = note.content || '';
    document.getElementById('readTitle').textContent = note.title;
    var metaHtml = '';
    if (note.category_name) metaHtml += '<span class="cat-badge" style="background:' + (note.category_color||'#6b7280') + '22;color:' + (note.category_color||'#6b7280') + '">' + escapeHtml(note.category_name) + '</span>';
    metaHtml += '<span>' + (note.created_at || '') + '</span>';
    if (note.is_pinned) metaHtml += '<span style="color:#ff8c42"><i class="ti ti-pin-filled"></i> Pinned</span>';
    document.getElementById('readMeta').innerHTML = metaHtml;
    var el = document.getElementById('readContent');
    el.style.whiteSpace = 'pre-wrap';
    el.textContent = note.content || '(no content)';

    renderChecklist(note.checklist || []);
    document.getElementById('readModal').classList.add('open');
  })
  .catch(function() { showToast('Request failed', 'error'); });
}

function renderChecklist(items) {
  var container = document.getElementById('clList');
  var total = items.length;
  var done = items.filter(function(i) { return parseInt(i.is_checked) === 1; }).length;
  var pct = total ? Math.round(done / total * 100) : 0;
  document.getElementById('clBar').style.width = pct + '%';
  document.getElementById('clLabel').textContent = done + '/' + total;
  if (!total) {
    container.innerHTML = '<div style="font-size:0.82rem;color:var(--text3);padding:0.5rem 0">No items yet.</div>';
    return;
  }
  var html = '';
  items.forEach(function(item) {
    var checked = parseInt(item.is_checked) === 1;
    html += '<div class="cl-item" data-id="' + item.id + '">';
    html += '<div class="cl-check ' + (checked ? 'checked' : '') + '" onclick="toggleChecklistItem(' + item.id + ')">' + (checked ? '<i class="ti ti-check"></i>' : '') + '</div>';
    html += '<span class="cl-text ' + (checked ? 'done' : '') + '">' + escapeHtml(item.text) + '</span>';
    html += '<button class="cl-del" onclick="deleteChecklistItem(' + item.id + ')" title="Delete"><i class="ti ti-trash"></i></button>';
    html += '</div>';
  });
  container.innerHTML = html;
}

function toggleChecklistItem(id) {
  var fd = new FormData();
  fd.append('action', 'checklist_toggle');
  fd.append('id', id);
  fetch('notes.php', { method: 'POST', body: fd })
  .then(function(r) { return r.json(); })
  .then(function(j) {
    if (j.ok && currentReadNoteId) {
      // Refresh note data instead of full reload
      var getFd = new FormData();
      getFd.append('action', 'get_note');
      getFd.append('id', currentReadNoteId);
      fetch('notes.php', { method: 'POST', body: getFd })
      .then(function(r) { return r.json(); })
      .then(function(j2) {
        if (j2.ok && j2.note) {
          // Update the checklist without closing the modal
          renderChecklist(j2.note.checklist || []);
          // Also update the note card if present
          var card = document.querySelector('.note-card[data-id="' + currentReadNoteId + '"]');
          if (card) {
            var note = j2.note;
            var cl = note.checklistStats || {total:0, done:0};
            var pct = cl.total ? Math.round(cl.done / cl.total * 100) : 0;
            var clSpan = card.querySelector('.checklist-progress span');
            var barFill = card.querySelector('.checklist-progress .bar-fill');
            if (clSpan) clSpan.textContent = cl.done + '/' + cl.total;
            if (barFill) barFill.style.width = pct + '%';
            updateStats();
          }
        }
      });
    }
  });
}

function deleteChecklistItem(id) {
  var fd = new FormData();
  fd.append('action', 'checklist_delete');
  fd.append('id', id);
  fetch('notes.php', { method: 'POST', body: fd })
  .then(function(r) { return r.json(); })
  .then(function(j) {
    if (j.ok && currentReadNoteId) {
      var getFd = new FormData();
      getFd.append('action', 'get_note');
      getFd.append('id', currentReadNoteId);
      fetch('notes.php', { method: 'POST', body: getFd })
      .then(function(r) { return r.json(); })
      .then(function(j2) {
        if (j2.ok && j2.note) {
          renderChecklist(j2.note.checklist || []);
          var card = document.querySelector('.note-card[data-id="' + currentReadNoteId + '"]');
          if (card) {
            var cl = j2.note.checklistStats || {total:0, done:0};
            var pct = cl.total ? Math.round(cl.done / cl.total * 100) : 0;
            var clSpan = card.querySelector('.checklist-progress span');
            var barFill = card.querySelector('.checklist-progress .bar-fill');
            if (clSpan) clSpan.textContent = cl.done + '/' + cl.total;
            if (barFill) barFill.style.width = pct + '%';
            updateStats();
          }
        }
      });
    }
  });
}

function addChecklistItem() {
  var input = document.getElementById('clAddInput');
  var text = input.value.trim();
  if (!text || !currentReadNoteId) return;
  var fd = new FormData();
  fd.append('action', 'checklist_add');
  fd.append('note_id', currentReadNoteId);
  fd.append('text', text);
  fetch('notes.php', { method: 'POST', body: fd })
  .then(function(r) { return r.json(); })
  .then(function(j) {
    if (j.ok) {
      input.value = '';
      var getFd = new FormData();
      getFd.append('action', 'get_note');
      getFd.append('id', currentReadNoteId);
      fetch('notes.php', { method: 'POST', body: getFd })
      .then(function(r) { return r.json(); })
      .then(function(j2) {
        if (j2.ok && j2.note) {
          renderChecklist(j2.note.checklist || []);
          var card = document.querySelector('.note-card[data-id="' + currentReadNoteId + '"]');
          if (card) {
            var cl = j2.note.checklistStats || {total:0, done:0};
            var pct = cl.total ? Math.round(cl.done / cl.total * 100) : 0;
            var clSpan = card.querySelector('.checklist-progress span');
            var barFill = card.querySelector('.checklist-progress .bar-fill');
            if (clSpan) clSpan.textContent = cl.done + '/' + cl.total;
            if (barFill) barFill.style.width = pct + '%';
            updateStats();
          }
        }
      });
    } else {
      showToast('Error: ' + (j.error || 'Unknown'), 'error');
    }
  })
  .catch(function() { showToast('Request failed', 'error'); });
}

// ── AI Generate Checklist ──
function aiGenerateChecklist() {
  var btn = document.getElementById('aiClBtn');
  var text = currentNoteContent.trim();
  if (!text) { showToast('No content to generate from', 'error'); return; }
  if (!currentReadNoteId) return;
  btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span> Generating...';
  fetch('../ai.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({task: 'note_action_items', context: text})
  }).then(function(r) { return r.json(); }).then(function(j) {
    if (!j.success || !Array.isArray(j.data)) {
      btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate';
      showToast('AI error: ' + (j.error || 'Invalid response'), 'error');
      return;
    }
    var items = j.data.filter(function(s) { return s.trim(); });
    if (!items.length) {
      btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate';
      showToast('No action items found', 'error');
      return;
    }
    var promises = items.map(function(itemText) {
      var fd = new FormData();
      fd.append('action', 'checklist_add');
      fd.append('note_id', currentReadNoteId);
      fd.append('text', itemText.trim());
      return fetch('notes.php', { method: 'POST', body: fd }).then(function(r) { return r.json(); });
    });
    Promise.all(promises).then(function(results) {
      btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate';
      var count = results.filter(function(r) { return r.ok; }).length;
      showToast(count + ' items generated');
      // Refresh checklist and card
      var getFd = new FormData();
      getFd.append('action', 'get_note');
      getFd.append('id', currentReadNoteId);
      fetch('notes.php', { method: 'POST', body: getFd })
      .then(function(r) { return r.json(); })
      .then(function(j2) {
        if (j2.ok && j2.note) {
          renderChecklist(j2.note.checklist || []);
          var card = document.querySelector('.note-card[data-id="' + currentReadNoteId + '"]');
          if (card) {
            var cl = j2.note.checklistStats || {total:0, done:0};
            var pct = cl.total ? Math.round(cl.done / cl.total * 100) : 0;
            var clSpan = card.querySelector('.checklist-progress span');
            var barFill = card.querySelector('.checklist-progress .bar-fill');
            if (clSpan) clSpan.textContent = cl.done + '/' + cl.total;
            if (barFill) barFill.style.width = pct + '%';
            updateStats();
          }
        }
      });
    });
  }).catch(function() {
    btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate';
    showToast('Request failed', 'error');
  });
}

function escapeHtml(str) {
  var div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

// ── AI Improve ──
function aiImproveNoteTitle() {
  var inp = document.getElementById('nf-title');
  var btn = document.getElementById('aiTitleBtn');
  var text = inp.value.trim();
  if (!text) return;
  btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span> Improve';
  fetch('../ai.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({task: 'improve', context: text})
  }).then(function(r) { return r.json(); }).then(function(j) {
    btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Improve';
    if (j.success) { inp.value = j.data; inp.dispatchEvent(new Event('input')); }
    else if (j.error) showToast('AI error: ' + j.error, 'error');
  }).catch(function() {
    btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Improve';
    showToast('Request failed', 'error');
  });
}

function aiImproveNoteContent() {
  var ta = document.getElementById('nf-content');
  var btn = document.getElementById('aiContentBtn');
  var text = ta.value.trim();
  if (!text) return;
  btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span> Improve';
  fetch('../ai.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({task: 'improve_content', context: text})
  }).then(function(r) { return r.json(); }).then(function(j) {
    btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Improve';
    if (j.success) ta.value = j.data;
    else if (j.error) showToast('AI error: ' + j.error, 'error');
  }).catch(function() {
    btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Improve';
    showToast('Request failed', 'error');
  });
}
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
