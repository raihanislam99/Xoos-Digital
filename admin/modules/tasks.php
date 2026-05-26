<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

// Redirect old ?view=notes to standalone notes module
if (isset($_GET['view']) && $_GET['view'] === 'notes') {
    header('Location: notes.php');
    exit;
}

$pdo = db();

// AJAX handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create' || $action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'title'         => trim($_POST['title'] ?? ''),
            'description'   => trim($_POST['description'] ?? ''),
            'status'        => $_POST['status'] ?? 'pending',
            'priority'      => $_POST['priority'] ?? 'medium',
            'due_date'      => $_POST['due_date'] ?: null,
            'category'      => trim($_POST['category'] ?? ''),
            'lead_id'       => isset($_POST['lead_id']) && $_POST['lead_id'] ? (int)$_POST['lead_id'] : null,
            'assignee_type' => $_POST['assignee_type'] ?? null,
            'assignee_name' => trim($_POST['assignee_name'] ?? '') ?: null,
        ];
        if ($id) {
            $data['id'] = $id;
            $sql = "UPDATE admin_tasks SET title=:title, description=:description, status=:status, priority=:priority, due_date=:due_date, category=:category, lead_id=:lead_id, assignee_type=:assignee_type, assignee_name=:assignee_name WHERE id=:id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
        } else {
            $maxSort = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM admin_tasks")->fetchColumn();
            $sql = "INSERT INTO admin_tasks (title, description, status, priority, due_date, category, lead_id, assignee_type, assignee_name, sort_order) VALUES (:title, :description, :status, :priority, :due_date, :category, :lead_id, :assignee_type, :assignee_name, $maxSort)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            $id = $pdo->lastInsertId();
        }
        json_response(['ok' => true, 'id' => $id]);
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM admin_tasks WHERE id = ?");
        $stmt->execute([$id]);
        json_response(['ok' => true]);
    }

    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        $stmt = $pdo->prepare("UPDATE admin_tasks SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        json_response(['ok' => true]);
    }

    if ($action === 'reorder') {
        $orders = json_decode($_POST['orders'] ?? '[]', true);
        $pdo->beginTransaction();
        foreach ($orders as $o) {
            $stmt = $pdo->prepare("UPDATE admin_tasks SET sort_order = ?, status = ? WHERE id = ?");
            $stmt->execute([(int)$o['sort'], $o['status'], (int)$o['id']]);
        }
        $pdo->commit();
        json_response(['ok' => true]);
    }

    if ($action === 'get_task') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM admin_tasks WHERE id = ?");
        $stmt->execute([$id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($task) {
            json_response(['ok' => true, 'task' => $task]);
        }
        json_response(['ok' => false, 'error' => 'Not found'], 404);
    }

    json_response(['ok' => false, 'error' => 'Unknown action'], 400);
}

// Build filter query
$where = ['1=1'];
$params = [];
if (!empty($_GET['priority'])) { $where[] = 'priority = ?'; $params[] = $_GET['priority']; }
if (!empty($_GET['category'])) { $where[] = 'category = ?'; $params[] = $_GET['category']; }
if (!empty($_GET['assignee'])) { $where[] = 'CONCAT(assignee_type,":",assignee_name) = ?'; $params[] = $_GET['assignee']; }
if (!empty($_GET['search'])) { $where[] = '(title LIKE ? OR description LIKE ?)'; $params[] = '%' . $_GET['search'] . '%'; $params[] = '%' . $_GET['search'] . '%'; }
$whereClause = implode(' AND ', $where);
$sortMap = ['due-asc'=>'due_date ASC, sort_order ASC', 'due-desc'=>'due_date DESC, sort_order ASC', 'name'=>'title ASC, sort_order ASC', 'priority'=>"FIELD(priority,'urgent','high','medium','low'), sort_order ASC"];
$sortOrder = $sortMap[$_GET['sort'] ?? ''] ?? 'created_at DESC';
$tasks = $pdo->query("SELECT * FROM admin_tasks WHERE $whereClause ORDER BY $sortOrder")->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM admin_tasks WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$leads = $pdo->query("SELECT id, business_name FROM leads ORDER BY business_name")->fetchAll();
$allAssignees = $pdo->query("SELECT DISTINCT assignee_type, assignee_name FROM admin_tasks WHERE assignee_name IS NOT NULL AND assignee_name != '' ORDER BY assignee_name")->fetchAll();

$activeFilters = !empty($_GET['priority']) || !empty($_GET['category']) || !empty($_GET['search']) || !empty($_GET['assignee']);

$colTasks = ['pending' => [], 'in_progress' => [], 'done' => []];
foreach ($tasks as $t) { $colTasks[$t['status']][] = $t; }
$statusLabels = ['pending' => 'Pending', 'in_progress' => 'In Progress', 'done' => 'Done'];
$statusIcons = ['pending' => 'ti ti-clock', 'in_progress' => 'ti ti-refresh', 'done' => 'ti ti-check'];
$statusColors = ['pending' => '#ff8c42', 'in_progress' => '#4f8ef7', 'done' => '#2ecc71'];

$totalCount = count($tasks);
$stats = [
    'total'   => $totalCount,
    'pending' => count($colTasks['pending']),
    'progress'=> count($colTasks['in_progress']),
    'done'    => count($colTasks['done']),
    'rate'    => $totalCount ? round(count($colTasks['done']) / $totalCount * 100) : 0,
];
?>
<?php require_once __DIR__ . '/../inc/header.php'; ?>

<style>
:root {
  --glass-bg: rgba(255,255,255,0.04);
  --glass-border: rgba(255,255,255,0.08);
  --glass-hover: rgba(200,255,0,0.2);
}
@keyframes cardSlideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
@keyframes cardFadeOut { to { opacity: 0; transform: scale(0.95); } }
@keyframes pulse-glow {
  0%, 100% { box-shadow: 0 0 12px rgba(200,255,0,0.3); }
  50% { box-shadow: 0 0 24px rgba(200,255,0,0.6); }
}
@keyframes modalSlideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

/* ─── STAT STRIP ─── */
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 0.75rem; margin-bottom: 1.5rem; }
.stat-card { background: var(--glass-bg); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid var(--glass-border); border-radius: 14px; padding: 1.1rem 1rem; text-align: center; border-top: 3px solid transparent; }
.stat-card:nth-child(1) { border-top-color: #fff; }
.stat-card:nth-child(2) { border-top-color: #ff8c42; }
.stat-card:nth-child(3) { border-top-color: #4f8ef7; }
.stat-card:nth-child(4) { border-top-color: #2ecc71; }
.stat-card:nth-child(5) { border-top-color: #c8ff00; }
.stat-value { font-size: 2rem; font-weight: 700; line-height: 1.1; }
.stat-card:nth-child(1) .stat-value { color: #fff; }
.stat-card:nth-child(2) .stat-value { color: #ff8c42; }
.stat-card:nth-child(3) .stat-value { color: #4f8ef7; }
.stat-card:nth-child(4) .stat-value { color: #2ecc71; }
.stat-card:nth-child(5) .stat-value { color: #c8ff00; }
.stat-label { font-size: 0.68rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.12em; margin-top: 6px; font-weight: 600; }

/* ─── FILTER BAR ─── */
.tasks-toolbar { align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; text-align: right; }
.tasks-filters { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem; }
.tasks-filters select, .tasks-filters input { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: var(--text2); padding: 0.5rem 0.75rem; border-radius: 8px; font-size: 0.78rem; outline: none; font-family: 'Inter', sans-serif; transition: border-color 0.15s; }
.tasks-filters select { background-color: #0e1420; color-scheme: dark; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; appearance: none; -webkit-appearance: none; padding-right: 2rem; }
.tasks-filters select option { background: #0e1420; color: var(--text); }
.tasks-filters select:focus, .tasks-filters input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(200,255,0,0.1); }
.tasks-filters input { min-width: 180px; }
.search-wrap { position: relative; }
.search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.3); font-size: 0.85rem; pointer-events: none; }
.search-wrap input { padding-left: 30px !important; }
.filter-wrap { position: relative; display: inline-flex; }
.filter-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.3); font-size: 0.85rem; pointer-events: none; z-index: 1; line-height: 1; }
.filter-wrap select { padding-left: 30px !important; }
.btn-new-task { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #080B10; border: none; font-weight: 700; padding: 0.6rem 1.2rem; border-radius: 10px; font-size: 0.85rem; cursor: pointer; font-family: 'Inter', sans-serif; transition: all 0.15s; white-space: nowrap; }
.btn-new-task:hover { filter: brightness(1.1); transform: translateY(-1px); }
.btn-new-task:active { transform: scale(0.97); }
.btn-new-task i { font-size: 1.1rem; }
.clear-filters { font-size: 0.72rem; color: var(--text3); cursor: pointer; text-decoration: none; }
.clear-filters:hover { color: var(--accent); }

/* ─── SORT ─── */
.sort-controls { margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
.sort-controls label { font-size: 0.72rem; color: var(--text3); text-transform: uppercase; letter-spacing: 0.08em; }
.sort-controls select { background: #1a1f2e; border: 1px solid rgba(255,255,255,0.1); color: var(--text); padding: 0.5rem 2rem 0.5rem 0.75rem; border-radius: 10px; font-size: 0.85rem; outline: none; font-family: 'Inter', sans-serif; box-shadow: 0 8px 32px rgba(0,0,0,0.4); appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; cursor: pointer; }
.sort-controls select option { background: #1a1f2e; color: var(--text); padding: 10px 16px; }
.sort-controls select:focus { border-color: var(--accent); }

/* ─── KANBAN ─── */
.kanban-board { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; min-height: 65vh; width: 100%; box-sizing: border-box; }
.kanban-col { background: rgba(255,255,255,0.02); border-radius: 14px; display: flex; flex-direction: column; overflow: hidden; border: 1px solid rgba(255,255,255,0.06); width: 100%; min-width: 0; box-sizing: border-box; }
.kanban-col[data-status="pending"] { border-color: rgba(255,140,66,0.35); border-top: 3px solid #ff8c42; }
.kanban-col[data-status="in_progress"] { border-color: rgba(79,142,247,0.35); border-top: 3px solid #4f8ef7; }
.kanban-col[data-status="done"] { border-color: rgba(46,204,113,0.35); border-top: 3px solid #2ecc71; }
.kanban-col-header { padding: 1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; justify-content: space-between; }
.kanban-col-header h3 { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text); }
.kanban-col-header .count { font-size: 0.7rem; padding: 2px 10px; border-radius: 999px; font-weight: 700; }
.kanban-col[data-status="pending"] .count { background: rgba(255,140,66,0.15); color: #ff8c42; }
.kanban-col[data-status="in_progress"] .count { background: rgba(79,142,247,0.15); color: #4f8ef7; }
.kanban-col[data-status="done"] .count { background: rgba(46,204,113,0.15); color: #2ecc71; }
.kanban-cards { flex: 1; padding: 0.75rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.6rem; min-height: 150px; border-radius: var(--radius-md); transition: background 0.2s; margin: 0.5rem 0; width: 100%; min-width: 0; box-sizing: border-box; }
.kanban-cards.drag-over { background: rgba(204,255,0,0.03); border: 1px dashed var(--accent); border-radius: 10px; }

/* ─── TASK CARDS ─── */
.task-card { background: var(--bg2); border: 1px solid var(--border); border-radius: 12px; padding: 14px 16px; cursor: grab; transition: all 0.2s ease; position: relative; animation: cardSlideIn 0.25s ease-out; user-select: none; border-left: 3px solid #6b7280; }
.task-card[data-priority="urgent"] { border-left-color: #ff4757; }
.task-card[data-priority="high"] { border-left-color: #f97316; }
.task-card[data-priority="medium"] { border-left-color: #f59e0b; }
.task-card[data-priority="low"] { border-left-color: #6b7280; }
.task-card:hover { border-color: rgba(200,255,0,0.2); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
.task-card.dragging { opacity: 0.4; transform: rotate(2deg); }
.task-card.fade-out { animation: cardFadeOut 0.2s ease-out forwards; }
.task-card .drag-handle { display: none; position: absolute; left: 2px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.2); font-size: 1.1rem; cursor: grab; }
.task-card:hover .drag-handle { display: block; }
.task-card .card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
.task-card .card-title { font-size: 0.82rem; font-weight: 600; color: var(--text); line-height: 1.3; text-align:left; }
.task-card .card-priority { font-size: 0.6rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; padding: 2px 8px; border-radius: 6px; flex-shrink: 0; }
.priority-urgent { background: rgba(255,71,87,0.15); color: #ff4757; }
.priority-high { background: rgba(249,115,22,0.15); color: #f97316; }
.priority-medium { background: rgba(245,158,11,0.15); color: #f59e0b; }
.priority-low { background: rgba(107,114,128,0.15); color: #9ca3af; }
.task-card .card-meta { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; font-size: 0.68rem; }
.task-card .card-due { color: var(--text3); display: flex; align-items: center; gap: 4px; }
.task-card .card-due.overdue { color: #ef4444; font-weight: 700; }
.task-card .card-category { background: rgba(204,255,0,0.06); color: var(--accent); padding: 1px 8px; border-radius: 4px; font-size: 0.6rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
.task-card .assignee-badge { font-size: 0.6rem; font-weight: 600; padding: 1px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; }
.assignee-person { background: rgba(139,92,246,0.15); color: #a78bfa; }
.assignee-company { background: rgba(6,182,212,0.15); color: #22d3ee; }
.task-card .card-actions { display: none; gap: 2px; flex-shrink: 0; }
.task-card:hover .card-actions { display: flex; }
.task-card .card-actions button { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.07); color: var(--text2); width: 24px; height: 24px; border-radius: 6px; cursor: pointer; font-size: 11px; display: flex; align-items: center; justify-content: center; transition: all 0.1s; }
.task-card .card-actions button:hover { background: rgba(255,255,255,0.15); color: var(--text); }
.task-card .card-lead-link { font-size: 0.6rem; color: var(--blue); text-decoration: none; display: inline-flex; align-items: center; gap: 3px; }
.task-card .card-lead-link:hover { text-decoration: underline; }
.task-card .card-bottom { display: flex; align-items: center; justify-content: space-between; padding-top: 6px; border-top: 1px solid rgba(255,255,255,0.04); gap: 6px; flex-wrap: wrap; }
.task-card .card-due-right { font-size: 0.68rem; color: var(--text3); display: flex; align-items: center; gap: 4px; }
.task-card .card-due-right.overdue { color: #ef4444; font-weight: 700; }
.empty-col { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1rem; color: var(--text3); font-size: 0.75rem; text-align: center; gap: 4px; flex: 1; }
.empty-col i { font-size: 1.5rem; opacity: 0.3; }
#kanban-view { width: 100%; }

/* ─── MODAL ─── */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 1000; display: none; align-items: center; justify-content: center; }
.modal-overlay.open { display: flex; }
.modal { background: #0e1420; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; box-shadow: 0 24px 64px rgba(0,0,0,0.6); width: 90%; max-width: 640px; max-height: 90vh; overflow-y: auto; animation: modalSlideUp 0.25s ease; padding: 0; }
.modal-title { font-size: 1rem; font-weight: 700; color: var(--text); padding: 1.25rem 1.5rem 0.5rem; margin: 0; }
.modal-body { padding: 0 1.5rem 1.5rem; }

/* Form fields */
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.5); margin-bottom: 6px; font-weight: 600; }
.form-control { width: 100%; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); color: var(--text); padding: 12px 14px; border-radius: 10px; font-size: 0.85rem; outline: none; font-family: 'Inter', sans-serif; transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
.form-control:focus { border-color: #c8ff00; box-shadow: 0 0 0 3px rgba(200,255,0,0.1); }
textarea.form-control { resize: vertical; min-height: 80px; }
select.form-control { appearance: none; -webkit-appearance: none; background-color: #0e1420; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; color-scheme: dark; }
select.form-control option { background: #0e1420; color: var(--text); }

.tf-add-form { display:flex; align-items:center; gap:6px; padding:6px 0; }
.tf-add-btn:hover { background:rgba(200,255,0,0.2) !important; }
.char-counter { float: right; font-size: 0.72rem; color: rgba(255,255,255,0.35); margin-top: 4px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-actions { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.06); }

.btn { display: inline-flex; align-items: center; gap: 6px; padding: 0.6rem 1.2rem; border-radius: 10px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.15s; font-family: 'Inter', sans-serif; border: none; }
.btn:disabled { opacity: 0.35; cursor: default; pointer-events: none; }

.btn-primary { background: #c8ff00; color: #000; font-weight: 700; }
.btn-primary:hover { filter: brightness(1.1); }
.btn-primary:active { transform: scale(0.97); }
.btn-secondary { background: transparent; border: 1px solid rgba(255,255,255,0.1); color: var(--text2); }
.btn-secondary:hover { background: rgba(255,255,255,0.06); color: var(--text); }
.btn-sm { padding: 0.4rem 0.8rem; font-size: 0.75rem; border-radius: 8px; }
.btn-danger { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.2); color: #ef4444; }
.btn-danger:hover { background: rgba(239,68,68,0.25); }

/* ─── NEW TASK BUTTON ─── */
.btn-pulse { animation: pulse-glow 2s ease-in-out infinite; }

/* ─── LEAD SUGGEST ─── */
.lead-suggest { position: absolute; top: 100%; left: 0; right: 0; background: #1a1f2e; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; max-height: 160px; overflow-y: auto; z-index: 10; display: none; margin-top: 4px; box-shadow: 0 8px 24px rgba(0,0,0,0.4); }
.lead-suggest div { padding: 0.6rem 0.75rem; font-size: 0.8rem; color: var(--text2); cursor: pointer; }
.lead-suggest div:hover { background: rgba(200,255,0,0.08); color: var(--text); }
.lead-suggest div:first-child { border-radius: 10px 10px 0 0; }
.lead-suggest div:last-child { border-radius: 0 0 10px 10px; }



/* ─── QUICK BAR ─── */
.v3-task-quick-bar { display: flex; align-items: center; gap: 8px; margin-top: 1.5rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 0.75rem 1rem; }
.tqb-label { font-size: 0.72rem; color: var(--text3); text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; white-space: nowrap; }
.tqb-input { flex: 1; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: var(--text); padding: 0.55rem 0.75rem; border-radius: 8px; font-size: 0.82rem; outline: none; font-family: 'Inter', sans-serif; }
.tqb-input:focus { border-color: var(--accent); }
.tqb-btn { background: var(--accent); color: #000; border: none; font-weight: 700; padding: 0.55rem 1rem; border-radius: 8px; font-size: 0.8rem; cursor: pointer; font-family: 'Inter', sans-serif; white-space: nowrap; }

.move-select { display: none; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; border: 1px solid var(--border); background: var(--bg3); color: var(--text2); }

@media(max-width:900px){
  .kanban-board { grid-template-columns: 1fr; gap: 0.75rem; }
  .move-select { display: inline-block; }
  .stats-row { grid-template-columns: repeat(3, 1fr); }
  .form-row { grid-template-columns: 1fr; }
  .tasks-tab-bar { flex-wrap: wrap; gap: 0.75rem; }
  .tasks-tab-bar > div { flex: 1; }
  .tasks-tab-bar > div button { flex: 1; text-align: center; padding: 10px 14px !important; font-size: 0.8rem !important; }
  .v3-task-quick-bar { flex-wrap: wrap; }
  .v3-task-quick-bar .tqb-input { min-width: 100%; }
  .stat-value { font-size: 1.5rem; }
  .stat-card { padding: 0.85rem 0.75rem; }
}
@media(max-width:768px){
  .tasks-toolbar { display: flex; flex-direction: row; flex-wrap: nowrap; align-items: center; gap: 6px; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 2px; scrollbar-width: none; }
  .tasks-toolbar::-webkit-scrollbar { display: none; }
  .tasks-filters { display: flex !important; flex-wrap: nowrap !important; flex: 1; gap: 6px; overflow-x: auto; scrollbar-width: none; }
  .tasks-filters::-webkit-scrollbar { display: none; }
  .tasks-filters .search-wrap { display: none; }
  .filter-wrap { width: 44px; min-width: 44px; overflow: hidden; }
  .filter-wrap select { padding: 8px 28px 8px 28px !important; min-width: 0 !important; width: 100% !important; }
  .filter-wrap i { font-size: 0.9rem; left: 7px; }
  .btn-new-task { width: 40px; min-width: 40px; padding: 8px !important; overflow: hidden; border-radius: 8px !important; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .btn-new-task i { font-size: 1.1rem; }
  .new-task-label { display: none !important; }
}
@media(max-width:480px){
  .page-title { font-size: 1.1rem !important; }
  .stats-row { grid-template-columns: repeat(2, 1fr); gap: 0.5rem; }
  .tasks-toolbar { display: flex; flex-direction: row; flex-wrap: nowrap; align-items: center; gap: 4px; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 2px; scrollbar-width: none; }
  .tasks-toolbar::-webkit-scrollbar { display: none; }
  .tasks-filters { display: flex !important; flex-wrap: nowrap !important; flex: 1; gap: 4px; overflow-x: auto; scrollbar-width: none; }
  .tasks-filters::-webkit-scrollbar { display: none; }
  .filter-wrap { width: 38px; min-width: 38px; overflow: hidden; }
  .filter-wrap select { padding: 6px 22px 6px 22px !important; min-width: 0 !important; width: 100% !important; }
  .filter-wrap i { font-size: 0.85rem; left: 5px; }
  .btn-new-task { width: 36px; min-width: 36px; padding: 6px !important; overflow: hidden; border-radius: 6px !important; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .btn-new-task i { font-size: 1rem; }
  .new-task-label { display: none !important; }
  .stat-label { font-size: 0.6rem; }
  .kanban-col-header { padding: 0.75rem 0.85rem; }
  .kanban-cards { padding: 0.5rem; margin: 0.25rem 0; gap: 0.4rem; }
  .task-card { padding: 0.65rem 0.75rem !important; }
  .task-card .card-title { font-size: 0.78rem; }
  .modal { padding: 0 !important; margin: 0 0.5rem; }
  .modal-body { padding: 0 1rem 1rem; }
  .modal-title { padding: 1rem 1rem 0.5rem; }
  .tasks-tab-bar > div button { padding: 8px 10px !important; font-size: 0.72rem !important; }
  .v3-task-quick-bar { padding: 0.5rem 0.75rem; }
  .tqb-input { padding: 0.4rem 0.6rem; font-size: 0.78rem; }
  .tqb-btn { padding: 0.4rem 0.75rem; font-size: 0.75rem; }
  .form-control { padding: 10px 12px; font-size: 0.82rem; }
  .btn { padding: 0.5rem 0.9rem; font-size: 0.78rem; }
  .btn-sm { padding: 0.35rem 0.65rem; font-size: 0.7rem; }
  .char-counter { font-size: 0.65rem; }
}

</style>

<div class="page-title" style="font-size:1.6rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:#fff;margin-bottom:24px">TASKS</div>

<div id="tasks-panel">

<div class="stats-row">
    <div class="stat-card"><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-label">Total</div></div>
    <div class="stat-card"><div class="stat-value"><?= $stats['pending'] ?></div><div class="stat-label">Pending</div></div>
    <div class="stat-card"><div class="stat-value"><?= $stats['progress'] ?></div><div class="stat-label">In Progress</div></div>
    <div class="stat-card"><div class="stat-value"><?= $stats['done'] ?></div><div class="stat-label">Done</div></div>
    <div class="stat-card"><div class="stat-value"><?= $stats['rate'] ?>%</div><div class="stat-label">Complete</div></div>
</div>

<div class="tasks-toolbar">
    <div class="tasks-filters">
        <div class="filter-wrap"><i class="ti ti-flag"></i>
        <select id="f-priority" onchange="applyFilters()">
            <option value="">All Priorities</option>
            <option value="urgent" <?= ($_GET['priority']??'')==='urgent'?'selected':'' ?>>Urgent</option>
            <option value="high" <?= ($_GET['priority']??'')==='high'?'selected':'' ?>>High</option>
            <option value="medium" <?= ($_GET['priority']??'')==='medium'?'selected':'' ?>>Medium</option>
            <option value="low" <?= ($_GET['priority']??'')==='low'?'selected':'' ?>>Low</option>
        </select></div>
        <div class="filter-wrap"><i class="ti ti-tag"></i>
        <select id="f-category" onchange="applyFilters()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= h($c) ?>" <?= ($_GET['category']??'')===$c?'selected':'' ?>><?= h($c) ?></option>
            <?php endforeach; ?>
        </select></div>
        <div class="filter-wrap"><i class="ti ti-user"></i>
        <select id="f-assignee" onchange="applyFilters()">
            <option value="">All Assignees</option>
            <?php foreach ($allAssignees as $a): $val = $a['assignee_type'].':'.$a['assignee_name']; ?>
            <option value="<?= h($val) ?>" <?= ($_GET['assignee']??'')===$val?'selected':'' ?>><?= h($a['assignee_name']) ?></option>
            <?php endforeach; ?>
        </select></div>
        <div class="search-wrap">
            <i class="ti ti-search"></i>
            <input type="text" id="f-search" placeholder="Search tasks..." value="<?= h($_GET['search'] ?? '') ?>" onkeydown="if(event.key==='Enter')applyFilters()">
        </div>
        <div class="filter-wrap"><i class="ti ti-arrows-sort"></i>
        <select id="taskSort" onchange="applySort(this.value)" style="background-color:#0e1420;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 10px center;border:1px solid rgba(255,255,255,0.1);color:var(--text2);padding-right:2rem;border-radius:8px;font-size:0.78rem;outline:none;font-family:'Inter',sans-serif;appearance:none;-webkit-appearance:none;color-scheme:dark;cursor:pointer">
            <option value="">Sort...</option>
            <option value="due-asc" <?= ($_GET['sort']??'')==='due-asc'?'selected':'' ?>>Due Date ↑</option>
            <option value="due-desc" <?= ($_GET['sort']??'')==='due-desc'?'selected':'' ?>>Due Date ↓</option>
            <option value="name" <?= ($_GET['sort']??'')==='name'?'selected':'' ?>>Name (A-Z)</option>
            <option value="priority" <?= ($_GET['sort']??'')==='priority'?'selected':'' ?>>Priority (High→Low)</option>
        </select>
        <?php if ($activeFilters): ?>
        <a href="tasks.php" class="clear-filters"><i class="ti ti-x"></i> Clear</a>
        <?php endif; ?>
    </div>
    <button class="btn-new-task" onclick="openTaskModal()" title="New Task">
        <i class="ti ti-plus"></i> <span class="new-task-label">New Task</span>
    </button>
</div>


<div id="kanban-view">
    <div class="kanban-board">
        <?php foreach (['pending', 'in_progress', 'done'] as $s): $cardList = $colTasks[$s]; ?>
        <div class="kanban-col" data-status="<?= $s ?>">
            <div class="kanban-col-header">
                <h3><i class="<?= $statusIcons[$s] ?>"></i> <?= $statusLabels[$s] ?></h3>
                <span class="count"><?= count($cardList) ?></span>
            </div>
            <div class="kanban-cards" data-status="<?= $s ?>" ondrop="drop(event)" ondragover="allowDrop(event)" ondragleave="dragLeave(event)">
                <?php if (count($cardList)): ?>
                <?php foreach ($cardList as $t): ?>
                <?php $overdue = $t['due_date'] && $t['due_date'] < date('Y-m-d H:i:s') && $t['status'] !== 'done'; ?>
                <div class="task-card" draggable="true" ondragstart="dragStart(event)" ondragend="dragEnd(event)"
                     data-id="<?= $t['id'] ?>"
                     data-status="<?= $t['status'] ?>"
                     data-priority="<?= $t['priority'] ?>"
                     data-due="<?= h($t['due_date'] ?? '') ?>"
                     data-description="<?= h($t['description'] ?? '') ?>"
                     data-category="<?= h($t['category'] ?? '') ?>"
                     data-lead-id="<?= $t['lead_id'] ?? '' ?>"
                     data-assignee-type="<?= h($t['assignee_type'] ?? '') ?>"
                     data-assignee-name="<?= h($t['assignee_name'] ?? '') ?>">
                    <span class="drag-handle">⠿</span>
                    <div class="card-top">
                        <span class="card-title"><?= h($t['title']) ?></span>
                        <div class="card-actions">
                            <button onclick="editTask(<?= $t['id'] ?>)" title="Edit"><i class="ti ti-pencil"></i></button>
                            <button onclick="deleteTask(<?= $t['id'] ?>,'<?= h(addslashes($t['title'])) ?>')" title="Delete"><i class="ti ti-trash"></i></button>
                        </div>
                    </div>
                    <?php if ($t['description']): ?>
                    <div style="font-size:0.72rem;color:var(--text3);margin-bottom:8px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= h($t['description']) ?></div>
                    <?php endif; ?>
                    <?php if ($t['lead_id']): $lead = $pdo->query("SELECT id, business_name FROM leads WHERE id = " . (int)$t['lead_id'])->fetch(); if ($lead): ?>
                    <div class="card-meta">
                        <a href="leads.php?view=my_leads&edit=<?= $lead['id'] ?>" class="card-lead-link" target="_blank"><i class="ti ti-user"></i> <?= h($lead['business_name']) ?></a>
                    </div>
                    <?php endif; endif; ?>
                    <div class="card-bottom">
                        <span class="card-priority priority-<?= $t['priority'] ?>"><?= $t['priority'] ?></span>
                        <?php if ($t['category']): ?>
                        <span class="card-category"><?= h($t['category']) ?></span>
                        <?php endif; ?>
                        <?php if ($t['assignee_name']): ?>
                        <span class="assignee-badge assignee-<?= $t['assignee_type']?:'person' ?>"><?= h($t['assignee_name']) ?></span>
                        <?php endif; ?>
                        <?php if ($t['due_date']): ?>
                        <span class="card-due-right <?= $overdue?'overdue':'' ?>"><i class="ti ti-calendar"></i> <?= date('M j', strtotime($t['due_date'])) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="empty-col"><i class="ti ti-inbox"></i> Drop tasks here</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>



<div class="v3-task-quick-bar">
    <span class="tqb-label">⚡ Quick Add</span>
    <input class="tqb-input" id="quickTaskInput" placeholder="Task title... press Enter to add" maxlength="200">
    <button class="tqb-btn" onclick="quickAddTask()">+ Add</button>
</div>
</div>

<div class="modal-overlay" id="taskModal">
    <div class="modal">
        <h3 class="modal-title" id="taskModalTitle">Task</h3>
        <div class="modal-body">
            <form id="taskForm" onsubmit="return saveTask(event)">
                <input type="hidden" name="id" id="tf-id" value="0">
                <input type="hidden" name="status" id="tf-status" value="pending">
                <div class="form-group">
                    <div class="flex" style="gap:8px;align-items:center;margin-bottom:6px">
                        <label style="margin:0">Title *</label>
                        <button type="button" class="btn btn-ai btn-sm" id="aiTitleBtn" onclick="aiImproveTaskTitle()" disabled style="white-space:nowrap"><i class="ti ti-sparkles"></i> Improve</button>
                    </div>
                    <input class="form-control" name="title" id="tf-title" maxlength="200" required placeholder="What needs to be done?" oninput="document.getElementById('charCounter').textContent=this.value.length+'/200';document.getElementById('aiTitleBtn').disabled=!this.value.trim()">
                    <div class="char-counter" id="charCounter">0/200</div>
                </div>
                <div class="form-group">
                    <div class="flex" style="gap:8px;align-items:center;margin-bottom:6px">
                        <label style="margin:0">Description</label>
                        <button type="button" class="btn btn-ai btn-sm" id="aiDescBtn" onclick="aiImproveTaskDesc()" disabled><i class="ti ti-sparkles"></i> Improve</button>
                    </div>
                    <textarea class="form-control" name="description" id="tf-desc" rows="3" placeholder="Optional details..." oninput="document.getElementById('aiDescBtn').disabled=!this.value.trim()"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Priority</label>
                        <select class="form-control" name="priority" id="tf-priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input class="form-control" type="datetime-local" name="due_date" id="tf-due">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <div class="tf-combo-wrap" style="display:flex;gap:4px">
                            <select class="form-control" name="category" id="tf-category" style="flex:1">
                                <option value="">None</option>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= h($c) ?>"><?= h($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="tf-add-btn" data-target="category" title="Add category" style="flex-shrink:0;width:34px;height:34px;border-radius:8px;border:1px solid rgba(200,255,0,0.25);background:rgba(200,255,0,0.1);color:#c8ff00;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1rem">+</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Assignee</label>
                        <div class="tf-combo-wrap" style="display:flex;gap:4px">
                            <select class="form-control" name="assignee_name" id="tf-assignee-name" style="flex:1">
                                <option value="">None</option>
                                <?php foreach ($allAssignees as $a): ?>
                                <option value="<?= h($a['assignee_name']) ?>"><?= h($a['assignee_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="tf-add-btn" data-target="assignee" title="Add assignee" style="flex-shrink:0;width:34px;height:34px;border-radius:8px;border:1px solid rgba(200,255,0,0.25);background:rgba(200,255,0,0.1);color:#c8ff00;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1rem">+</button>
                        </div>
                    </div>
                </div>
                <div id="tf-category-add-form" class="tf-add-form" style="display:none">
                    <input type="text" id="tf-new-category" placeholder="Category name" class="form-control" style="flex:1;margin-bottom:0">
                    <button type="button" id="tf-category-add-save" class="btn btn-primary btn-sm">Add</button>
                    <button type="button" id="tf-category-add-cancel" class="btn btn-secondary btn-sm">×</button>
                </div>
                <div id="tf-assignee-add-form" class="tf-add-form" style="display:none">
                    <input type="text" id="tf-new-assignee" placeholder="Assignee name" class="form-control" style="flex:1;margin-bottom:0">
                    <button type="button" id="tf-assignee-add-save" class="btn btn-primary btn-sm">Add</button>
                    <button type="button" id="tf-assignee-add-cancel" class="btn btn-secondary btn-sm">×</button>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
                    <button type="button" class="btn btn-secondary" onclick="closeTaskModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ══════════════════════════════════════
// DRAG AND DROP
// ══════════════════════════════════════
var draggedId = null;

function allowDrop(e) { e.preventDefault(); e.currentTarget.classList.add('drag-over'); }
function dragLeave(e) { e.currentTarget.classList.remove('drag-over'); }

function dragStart(e) {
    draggedId = e.currentTarget.dataset.id;
    e.dataTransfer.effectAllowed = 'move';
    e.currentTarget.classList.add('dragging');
}

function dragEnd(e) {
    e.currentTarget.classList.remove('dragging');
    document.querySelectorAll('.kanban-cards').forEach(function(l) { l.classList.remove('drag-over'); });
    if (window._pendingReorder) clearTimeout(window._pendingReorder);
    window._pendingReorder = setTimeout(function() {
        var allCards = document.querySelectorAll('.task-card');
        var orders = [];
        allCards.forEach(function(card, i) {
            var col = card.closest('.kanban-cards');
            orders.push({ id: card.dataset.id, sort: i, status: col.dataset.status });
        });
        var fd = new FormData();
        fd.append('action', 'reorder');
        fd.append('orders', JSON.stringify(orders));
        fetch('tasks.php', { method: 'POST', body: fd });
    }, 300);
}

function drop(e) {
    e.preventDefault();
    var list = e.currentTarget;
    list.classList.remove('drag-over');
    if (!draggedId) return;
    var dragging = document.querySelector('.task-card[data-id="' + draggedId + '"]');
    if (!dragging) return;

    var newStatus = list.dataset.status;
    var afterEl = getDragAfterElement(list, e.clientY);

    if (afterEl) {
        list.insertBefore(dragging, afterEl);
    } else {
        var empty = list.querySelector('.empty-col');
        if (empty) empty.remove();
        list.appendChild(dragging);
    }

    dragging.dataset.status = newStatus;
    updateStatusAjax(draggedId, newStatus);
    updateStats();
}

function getDragAfterElement(list, y) {
    var cards = list.querySelectorAll('.task-card:not(.dragging)');
    if (!cards.length) return null;
    return Array.from(cards).reduce(function(closest, child) {
        var box = child.getBoundingClientRect();
        var offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        }
        return closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function updateStatusAjax(id, status) {
    var fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('id', id);
    fd.append('status', status);
    fetch('tasks.php', { method: 'POST', body: fd });
}

function updateStats() {
    var total = document.querySelectorAll('.task-card').length;
    var counts = {};
    document.querySelectorAll('.kanban-cards').forEach(function(list) {
        var s = list.dataset.status;
        var n = list.querySelectorAll('.task-card').length;
        counts[s] = n;
        var col = list.closest('.kanban-col');
        if (col) col.querySelector('.count').textContent = n;
    });
    var p = counts['pending'] || 0;
    var ip = counts['in_progress'] || 0;
    var d = counts['done'] || 0;
    var cards = document.querySelectorAll('.stat-card');
    if (cards.length >= 5) {
        cards[0].querySelector('.stat-value').textContent = total;
        cards[1].querySelector('.stat-value').textContent = p;
        cards[2].querySelector('.stat-value').textContent = ip;
        cards[3].querySelector('.stat-value').textContent = d;
        cards[4].querySelector('.stat-value').textContent = total ? Math.round(d / total * 100) + '%' : '0%';
    }
}

// ══════════════════════════════════════
// TASK MODAL
// ══════════════════════════════════════
function openTaskModal(data) {
    document.getElementById('taskModal').classList.add('open');
    if (data) {
        document.getElementById('taskModalTitle').textContent = 'Edit Task';
        document.getElementById('tf-id').value = data.id || 0;
        document.getElementById('tf-title').value = data.title || '';
        document.getElementById('tf-desc').value = data.description || '';
        document.getElementById('tf-status').value = data.status || 'pending';
        document.getElementById('tf-priority').value = data.priority || 'medium';
        document.getElementById('tf-due').value = data.due_date ? data.due_date.substring(0,16) : '';
        document.getElementById('tf-category').value = data.category || '';
        document.getElementById('tf-assignee-name').value = data.assignee_name || '';
    } else {
        document.getElementById('taskModalTitle').textContent = 'New Task';
        document.getElementById('tf-id').value = 0;
        document.getElementById('tf-title').value = '';
        document.getElementById('tf-desc').value = '';
        document.getElementById('tf-status').value = 'pending';
        document.getElementById('tf-priority').value = 'medium';
        var tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        var y = tomorrow.getFullYear();
        var m = String(tomorrow.getMonth() + 1).padStart(2, '0');
        var d = String(tomorrow.getDate()).padStart(2, '0');
        document.getElementById('tf-due').value = y + '-' + m + '-' + d + 'T09:00';
        document.getElementById('tf-category').value = '';
        document.getElementById('tf-assignee-name').value = '';
    }
    document.getElementById('charCounter').textContent = (document.getElementById('tf-title').value.length) + '/200';
    document.getElementById('aiTitleBtn').disabled = !document.getElementById('tf-title').value.trim();
    document.getElementById('aiDescBtn').disabled = !document.getElementById('tf-desc').value.trim();
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.remove('open');
}

function saveTask(e) {
    e.preventDefault();
    var fd = new FormData(document.getElementById('taskForm'));
    var id = parseInt(fd.get('id'));
    fd.set('action', id ? 'update' : 'create');

    fetch('tasks.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.ok) {
            showToast(id ? 'Task updated' : 'Task created');
            closeTaskModal();
            setTimeout(function() { location.reload(); }, 300);
        } else {
            showToast('Error: ' + (j.error || 'Unknown'), 'error');
        }
    })
    .catch(function() { showToast('Request failed', 'error'); });
    return false;
}

function editTask(id) {
    var fd = new FormData();
    fd.append('action', 'get_task');
    fd.append('id', id);
    fetch('tasks.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(j) {
        if (j.ok && j.task) {
            openTaskModal(j.task);
        } else {
            showToast('Could not load task', 'error');
        }
    })
    .catch(function() { showToast('Request failed', 'error'); });
}

function deleteTask(id, title) {
    confirmDelete('#', title);
    document.getElementById('deleteConfirmBtn').onclick = function(e) {
        e.preventDefault();
        var fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        fetch('tasks.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(j) {
            if (j.ok) {
                closeDeleteModal();
                var card = document.querySelector('.task-card[data-id="' + id + '"]');
                if (card) {
                    card.classList.add('fade-out');
                    card.addEventListener('animationend', function() {
                        card.remove();
                        updateStats();
                    });
                }
                showToast('Task deleted');
            }
        });
    };
}

document.getElementById('taskModal').addEventListener('click', function(e) {
    if (e.target === this) closeTaskModal();
});

// ══════════════════════════════════════
// AI IMPROVE — Task Title & Description
// ══════════════════════════════════════
function aiImproveTaskTitle() {
    var inp = document.getElementById('tf-title');
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
    }).catch(function() {
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Improve';
    });
}
function aiImproveTaskDesc() {
    var ta = document.getElementById('tf-desc');
    var btn = document.getElementById('aiDescBtn');
    var text = ta.value.trim();
    if (!text) return;
    btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span> Improve';
    fetch('../ai.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({task: 'improve', context: text})
    }).then(function(r) { return r.json(); }).then(function(j) {
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Improve';
        if (j.success) ta.value = j.data;
    }).catch(function() {
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Improve';
    });
}

function applyFilters() {
    var params = [];
    var p = document.getElementById('f-priority').value;
    var c = document.getElementById('f-category').value;
    var a = document.getElementById('f-assignee').value;
    var s = document.getElementById('f-search').value;
    if (p) params.push('priority=' + p);
    if (c) params.push('category=' + encodeURIComponent(c));
    if (a) params.push('assignee=' + encodeURIComponent(a));
    if (s) params.push('search=' + encodeURIComponent(s));
    window.location.href = 'tasks.php?' + params.join('&');
}

var searchDebounce = null;
document.getElementById('f-search').addEventListener('input', function() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(applyFilters, 300);
});

function applySort(val) {
    if (!val) return;
    var params = new URLSearchParams(window.location.search);
    params.set('sort', val);
    window.location.href = 'tasks.php?' + params.toString();
}

// Quick add task
function quickAddTask() {
    var input = document.getElementById('quickTaskInput');
    var title = input ? input.value.trim() : '';
    if (!title) return;
    var form = document.getElementById('taskForm');
    if (!form) return;
    document.getElementById('tf-id').value = '0';
    document.getElementById('tf-status').value = 'pending';
    document.getElementById('tf-title').value = title;
    document.getElementById('tf-desc').value = '';
    document.getElementById('tf-priority').value = 'medium';
    var tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    var y = tomorrow.getFullYear();
    var m = String(tomorrow.getMonth() + 1).padStart(2, '0');
    var d = String(tomorrow.getDate()).padStart(2, '0');
    document.getElementById('tf-due').value = y + '-' + m + '-' + d + 'T09:00';
    document.getElementById('tf-category').value = '';
    document.getElementById('tf-assignee-name').value = '';
    if (typeof saveTask === 'function') {
        saveTask(new Event('submit'));
        input.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.kanban-cards').forEach(function(col) {
        if (col.children.length === 1 && col.querySelector('.empty-col')) return;
        if (!col.querySelector('.task-card') && !col.querySelector('.empty-col')) {
            col.innerHTML = '<div class="empty-col"><i class="ti ti-inbox"></i> Drop tasks here</div>';
        }
    });
    var qi = document.getElementById('quickTaskInput');
    if (qi) {
        qi.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); quickAddTask(); }
        });
    }

    // Inline category add
    document.querySelectorAll('.tf-add-btn').forEach(function(btn){
      btn.addEventListener('click', function(){
        var t=this.dataset.target;
        var wrap=this.parentElement;
        wrap.style.display='none';
        var form=document.getElementById('tf-'+t+'-add-form');
        form.style.display='flex';
        form.querySelector('input').focus();
      });
    });
    document.getElementById('tf-new-category').addEventListener('keydown',function(e){
      if(e.key==='Enter') document.getElementById('tf-category-add-save').click();
      if(e.key==='Escape') document.getElementById('tf-category-add-cancel').click();
    });
    document.getElementById('tf-new-assignee').addEventListener('keydown',function(e){
      if(e.key==='Enter') document.getElementById('tf-assignee-add-save').click();
      if(e.key==='Escape') document.getElementById('tf-assignee-add-cancel').click();
    });
    document.getElementById('tf-category-add-cancel').addEventListener('click',function(){
      this.parentElement.style.display='none';
      document.querySelector('#tf-category').closest('.tf-combo-wrap').style.display='flex';
    });
    document.getElementById('tf-assignee-add-cancel').addEventListener('click',function(){
      this.parentElement.style.display='none';
      document.querySelector('#tf-assignee-name').closest('.tf-combo-wrap').style.display='flex';
    });
    document.getElementById('tf-category-add-save').addEventListener('click',function(){
      var input=document.getElementById('tf-new-category');
      var name=input.value.trim();
      if(!name) return;
      var sel=document.getElementById('tf-category');
      var opt=document.createElement('option');
      opt.value=name; opt.textContent=name;
      sel.appendChild(opt);
      sel.value=name;
      input.value='';
      this.parentElement.style.display='none';
      document.querySelector('#tf-category').closest('.tf-combo-wrap').style.display='flex';
    });
    document.getElementById('tf-assignee-add-save').addEventListener('click',function(){
      var input=document.getElementById('tf-new-assignee');
      var name=input.value.trim();
      if(!name) return;
      var sel=document.getElementById('tf-assignee-name');
      var opt=document.createElement('option');
      opt.value=name; opt.textContent=name;
      sel.appendChild(opt);
      sel.value=name;
      input.value='';
      this.parentElement.style.display='none';
      document.querySelector('#tf-assignee-name').closest('.tf-combo-wrap').style.display='flex';
    });
});

</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
