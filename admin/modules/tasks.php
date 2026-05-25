<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

$view = $_GET['view'] ?? 'kanban';

// AJAX handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $pdo = db();

    if ($action === 'create' || $action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'title'         => trim($_POST['title'] ?? ''),
            'description'   => trim($_POST['description'] ?? ''),
            'status'        => $_POST['status'] ?? 'pending',
            'priority'      => $_POST['priority'] ?? 'medium',
            'due_date'      => $_POST['due_date'] ?: null,
            'category'      => trim($_POST['category'] ?? ''),
            'lead_id'       => $_POST['lead_id'] ? (int)$_POST['lead_id'] : null,
            'assignee_type' => $_POST['assignee_type'] ?: null,
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

    json_response(['ok' => false, 'error' => 'Unknown action'], 400);
}

// Build filter query
$where = ['1=1'];
$params = [];
if (!empty($_GET['priority'])) { $where[] = 'priority = ?'; $params[] = $_GET['priority']; }
if (!empty($_GET['category'])) { $where[] = 'category = ?'; $params[] = $_GET['category']; }
if (!empty($_GET['assignee'])) { $where[] = 'CONCAT(assignee_type,":",assignee_name) = ?'; $params[] = $_GET['assignee']; }
if (!empty($_GET['search'])) { $where[] = '(title LIKE ? OR description LIKE ?)'; $params[] = '%' . $_GET['search'] . '%'; $params[] = '%' . $_GET['search'] . '%'; }
if (!empty($_GET['overdue'])) { $where[] = 'due_date IS NOT NULL AND due_date < NOW() AND status != ?'; $params[] = 'done'; }
$whereClause = implode(' AND ', $where);
$tasks = db()->query("SELECT * FROM admin_tasks WHERE $whereClause ORDER BY sort_order ASC, created_at DESC")->fetchAll();

$categories = db()->query("SELECT DISTINCT category FROM admin_tasks WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$leads = db()->query("SELECT id, business_name FROM leads ORDER BY business_name")->fetchAll();
$allAssignees = db()->query("SELECT DISTINCT assignee_type, assignee_name FROM admin_tasks WHERE assignee_name IS NOT NULL AND assignee_name != '' ORDER BY assignee_name")->fetchAll();

$activeFilters = !empty($_GET['priority']) || !empty($_GET['category']) || !empty($_GET['search']) || !empty($_GET['overdue']) || !empty($_GET['assignee']);

$colTasks = ['pending' => [], 'in_progress' => [], 'done' => []];
foreach ($tasks as $t) { $colTasks[$t['status']][] = $t; }
$statusLabels = ['pending' => 'Pending', 'in_progress' => 'In Progress', 'done' => 'Done'];
$statusIcons = ['pending' => 'ti ti-clock', 'in_progress' => 'ti ti-refresh', 'done' => 'ti ti-check'];

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
@keyframes cardSlideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
@keyframes cardFadeOut { to { opacity: 0; transform: scale(0.95); } }

.kanban-board { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.25rem; min-height: 65vh; }
.kanban-col { background: rgba(255,255,255,0.015); border: 1px solid var(--border); border-radius: var(--radius-lg); display: flex; flex-direction: column; overflow: hidden; }
.kanban-col-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.kanban-col-header h3 { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text); }
.kanban-col-header .count { font-size: 0.7rem; background: var(--bg3); color: var(--text3); padding: 2px 10px; border-radius: 999px; font-weight: 700; }
.kanban-cards { flex: 1; padding: 0.75rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.6rem; min-height: 150px; border-radius: var(--radius-md); transition: background 0.2s; margin: 0.5rem; }
.kanban-cards.drag-over { background: rgba(204,255,0,0.03); border: 1px dashed var(--accent); }
.task-card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 0.9rem 1rem; cursor: grab; transition: all 0.15s ease; position: relative; animation: cardSlideIn 0.25s ease-out; }
.task-card:hover { border-color: var(--border-hover); box-shadow: 0 4px 16px rgba(0,0,0,0.3); }
.task-card.dragging { opacity: 0.4; transform: rotate(2deg); }
.task-card.fade-out { animation: cardFadeOut 0.2s ease-out forwards; }
.task-card .card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
.task-card .card-title { font-size: 0.82rem; font-weight: 600; color: var(--text); line-height: 1.3; flex: 1; }
.task-card .card-priority { font-size: 0.6rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; padding: 2px 8px; border-radius: 4px; flex-shrink: 0; }
.priority-urgent { background: rgba(239,68,68,0.15); color: #ef4444; }
.priority-high { background: rgba(249,115,22,0.15); color: #f97316; }
.priority-medium { background: rgba(59,130,246,0.15); color: #3b82f6; }
.priority-low { background: rgba(107,114,128,0.15); color: #9ca3af; }
.task-card .card-meta { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; font-size: 0.68rem; }
.task-card .card-due { color: var(--text3); display: flex; align-items: center; gap: 4px; }
.task-card .card-due.overdue { color: #ef4444; font-weight: 700; }
.task-card .card-category { background: rgba(204,255,0,0.06); color: var(--accent); padding: 1px 8px; border-radius: 4px; font-size: 0.6rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
.task-card .assignee-badge { font-size: 0.6rem; font-weight: 600; padding: 1px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; }
.assignee-person { background: rgba(139,92,246,0.15); color: #a78bfa; }
.assignee-company { background: rgba(6,182,212,0.15); color: #22d3ee; }
.task-card .card-actions { display: none; gap: 4px; }
.task-card:hover .card-actions { display: flex; }
.task-card .card-actions button { background: var(--bg3); border: 1px solid var(--border); color: var(--text2); width: 24px; height: 24px; border-radius: 4px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; transition: all 0.1s; }
.task-card .card-actions button:hover { background: var(--border); color: var(--text); }
.task-card .card-lead-link { font-size: 0.6rem; color: var(--blue); text-decoration: none; display: inline-flex; align-items: center; gap: 3px; }
.task-card .card-lead-link:hover { text-decoration: underline; }
.empty-col { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1rem; color: var(--text3); font-size: 0.75rem; text-align: center; gap: 4px; flex: 1; }
.empty-col i { font-size: 1.5rem; opacity: 0.3; }

.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 0.75rem; margin-bottom: 1.5rem; }
.stat-card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1rem 1rem; text-align: center; }
.stat-value { font-size: 1.4rem; font-weight: 800; line-height: 1.1; }
.stat-label { font-size: 0.6rem; color: var(--text3); text-transform: uppercase; letter-spacing: 0.12em; margin-top: 4px; font-weight: 600; }

.tasks-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.tasks-filters { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
.tasks-filters select, .tasks-filters input { background: var(--bg3); border: 1px solid var(--border); color: var(--text2); padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.78rem; outline: none; font-family: 'Inter', sans-serif; }
.tasks-filters select:focus, .tasks-filters input:focus { border-color: var(--accent); }
.tasks-filters input { min-width: 180px; }
.overdue-toggle { display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: var(--text3); cursor: pointer; }
.overdue-toggle input { accent-color: var(--accent); }
.view-toggle { display: flex; gap: 0; }
.view-toggle button { background: var(--bg3); border: 1px solid var(--border); color: var(--text3); padding: 0.5rem 0.9rem; font-size: 0.78rem; cursor: pointer; transition: all 0.15s; font-family: 'Inter', sans-serif; }
.view-toggle button:first-child { border-radius: var(--radius-sm) 0 0 var(--radius-sm); }
.view-toggle button:last-child { border-radius: 0 var(--radius-sm) var(--radius-sm) 0; }
.view-toggle button.active { background: var(--accent); color: #080B10; border-color: var(--accent); font-weight: 700; }
.clear-filters { font-size: 0.72rem; color: var(--text3); cursor: pointer; text-decoration: none; }
.clear-filters:hover { color: var(--accent); }

.modal-wide { max-width: 560px; }
.lead-suggest { position: absolute; top: 100%; left: 0; right: 0; background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius-sm); max-height: 160px; overflow-y: auto; z-index: 10; display: none; }
.lead-suggest div { padding: 0.5rem 0.75rem; font-size: 0.8rem; color: var(--text2); cursor: pointer; }
.lead-suggest div:hover { background: rgba(204,255,0,0.06); color: var(--text); }

.move-select { display: none; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; border: 1px solid var(--border); background: var(--bg3); color: var(--text2); }

@media(max-width:900px){
  .kanban-board { grid-template-columns: 1fr; }
  .tasks-toolbar { flex-direction: column; align-items: stretch; }
  .tasks-filters { flex-wrap: wrap; }
  .tasks-filters input { min-width: 100%; }
  .move-select { display: inline-block; }
  .stats-row { grid-template-columns: repeat(3, 1fr); }
}
</style>

<div class="page-header">
    <h1 class="page-title">Tasks</h1>
    <div class="flex flex-wrap">
        <button class="btn btn-primary" onclick="openTaskModal()"><i class="ti ti-plus"></i> New Task</button>
    </div>
</div>

<div class="stats-row">
    <div class="stat-card"><div class="stat-value" style="color:var(--text)"><?= $stats['total'] ?></div><div class="stat-label">Total</div></div>
    <div class="stat-card"><div class="stat-value" style="color:var(--blue)"><?= $stats['pending'] ?></div><div class="stat-label">Pending</div></div>
    <div class="stat-card"><div class="stat-value" style="color:#f59e0b"><?= $stats['progress'] ?></div><div class="stat-label">In Progress</div></div>
    <div class="stat-card"><div class="stat-value" style="color:var(--green)"><?= $stats['done'] ?></div><div class="stat-label">Done</div></div>
    <div class="stat-card"><div class="stat-value" style="color:var(--accent)"><?= $stats['rate'] ?>%</div><div class="stat-label">Complete</div></div>
</div>

<div class="tasks-toolbar">
    <div class="tasks-filters">
        <select id="f-priority" onchange="applyFilters()">
            <option value="">All Priorities</option>
            <option value="urgent" <?= ($_GET['priority']??'')==='urgent'?'selected':'' ?>>Urgent</option>
            <option value="high" <?= ($_GET['priority']??'')==='high'?'selected':'' ?>>High</option>
            <option value="medium" <?= ($_GET['priority']??'')==='medium'?'selected':'' ?>>Medium</option>
            <option value="low" <?= ($_GET['priority']??'')==='low'?'selected':'' ?>>Low</option>
        </select>
        <select id="f-category" onchange="applyFilters()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= h($c) ?>" <?= ($_GET['category']??'')===$c?'selected':'' ?>><?= h($c) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="f-assignee" onchange="applyFilters()">
            <option value="">All Assignees</option>
            <?php foreach ($allAssignees as $a): $val = $a['assignee_type'].':'.$a['assignee_name']; ?>
            <option value="<?= h($val) ?>" <?= ($_GET['assignee']??'')===$val?'selected':'' ?>><?= h($a['assignee_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="f-search" placeholder="Search tasks..." value="<?= h($_GET['search'] ?? '') ?>" onkeydown="if(event.key==='Enter')applyFilters()">
        <label class="overdue-toggle">
            <input type="checkbox" id="f-overdue" <?= !empty($_GET['overdue'])?'checked':'' ?> onchange="applyFilters()">
            Overdue only
        </label>
        <?php if ($activeFilters): ?>
        <a href="tasks.php<?= $view==='list'?'?view=list':'' ?>" class="clear-filters"><i class="ti ti-x"></i> Clear</a>
        <?php endif; ?>
    </div>
    <div class="view-toggle">
        <button class="<?= $view==='kanban'?'active':'' ?>" onclick="setView('kanban')"><i class="ti ti-layout-columns"></i> Board</button>
        <button class="<?= $view==='list'?'active':'' ?>" onclick="setView('list')"><i class="ti ti-list"></i> List</button>
    </div>
</div>

<div class="sort-controls">
    <label>Sort:</label>
    <select id="taskSort">
        <option value="">Default order</option>
        <option value="due-asc">Due Date ↑</option>
        <option value="due-desc">Due Date ↓</option>
        <option value="name">Name (A-Z)</option>
        <option value="priority">Priority (High→Low)</option>
    </select>
</div>

<div id="kanban-view" style="<?= $view==='list'?'display:none':'' ?>">
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
                <div class="task-card" draggable="true" ondragstart="dragStart(event)" ondragend="dragEnd(event)" data-id="<?= $t['id'] ?>" data-status="<?= $t['status'] ?>" data-priority="<?= $t['priority'] ?>" data-due="<?= h($t['due_date'] ?? '') ?>">
                    <div class="card-top">
                        <span class="drag-handle">⠿</span>
                        <span class="card-title"><?= h($t['title']) ?></span>
                        <span class="card-priority priority-<?= $t['priority'] ?>"><?= $t['priority'] ?></span>
                    </div>
                    <?php if ($t['description']): ?>
                    <div style="font-size:0.72rem;color:var(--text3);margin-bottom:8px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= h($t['description']) ?></div>
                    <?php endif; ?>
                    <div class="card-meta">
                        <?php if ($t['due_date']): ?>
                        <span class="card-due <?= $overdue?'overdue':'' ?>"><i class="ti ti-calendar"></i> <?= date('M j', strtotime($t['due_date'])) ?></span>
                        <?php endif; ?>
                        <?php if ($t['category']): ?>
                        <span class="card-category"><?= h($t['category']) ?></span>
                        <?php endif; ?>
                        <?php if ($t['assignee_name']): ?>
                        <span class="assignee-badge assignee-<?= $t['assignee_type']?:'person' ?>"><?= $t['assignee_type']==='company'?'��':'��' ?> <?= h($t['assignee_name']) ?></span>
                        <?php endif; ?>
                        <?php if ($t['lead_id']): $lead = db()->query("SELECT id, business_name FROM leads WHERE id = " . (int)$t['lead_id'])->fetch(); if ($lead): ?>
                        <a href="leads.php?view=my_leads&edit=<?= $lead['id'] ?>" class="card-lead-link" target="_blank"><i class="ti ti-user"></i> <?= h($lead['business_name']) ?></a>
                        <?php endif; endif; ?>
                    </div>
                    <div class="card-actions">
                        <button onclick="editTask(<?= $t['id'] ?>,'<?= h(addslashes($t['title'])) ?>')" title="Edit"><i class="ti ti-pencil"></i></button>
                        <button onclick="deleteTask(<?= $t['id'] ?>,'<?= h(addslashes($t['title'])) ?>')" title="Delete"><i class="ti ti-trash"></i></button>
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

<div id="list-view" style="<?= $view==='kanban'?'display:none':'' ?>">
    <div class="card">
        <div class="table-wrap">
            <?php if (count($tasks)): ?>
            <table>
                <thead>
                    <tr>
                        <th class="sortable" onclick="sortTable(0)">Title <i class="ti ti-arrows-sort"></i></th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th class="sortable" onclick="sortTable(3)">Due <i class="ti ti-arrows-sort"></i></th>
                        <th>Category</th>
                        <th>Assignee</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $t): ?>
                    <?php $overdue = $t['due_date'] && $t['due_date'] < date('Y-m-d H:i:s') && $t['status'] !== 'done'; ?>
                    <tr>
                        <td><strong style="color:var(--text);font-size:0.82rem"><?= h($t['title']) ?></strong></td>
                        <td><span class="card-priority priority-<?= $t['priority'] ?>"><?= $t['priority'] ?></span></td>
                        <td><span class="status-badge status-<?= $t['status']==='done'?'published':($t['status']==='in_progress'?'draft':'draft') ?>" style="font-size:0.65rem"><?= $statusLabels[$t['status']] ?></span></td>
                        <td style="<?= $overdue?'color:#ef4444;font-weight:700':'' ?>"><?= $t['due_date'] ? date('M j, Y', strtotime($t['due_date'])) : '-' ?></td>
                        <td><?= $t['category'] ? '<span class="card-category">'.h($t['category']).'</span>' : '-' ?></td>
                        <td><?= $t['assignee_name'] ? '<span class="assignee-badge assignee-'.($t['assignee_type']?:'person').'">'.h($t['assignee_name']).'</span>' : '-' ?></td>
                        <td style="text-align:right">
                            <button class="btn btn-secondary btn-sm" onclick="editTask(<?= $t['id'] ?>,'<?= h(addslashes($t['title'])) ?>')" style="padding:4px 10px"><i class="ti ti-pencil"></i></button>
                            <button class="btn btn-danger btn-sm" onclick="deleteTask(<?= $t['id'] ?>,'<?= h(addslashes($t['title'])) ?>')" style="padding:4px 10px"><i class="ti ti-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state"><i class="ti ti-checklist"></i><p>No tasks found.</p><button class="btn btn-primary mt-1" onclick="openTaskModal()">Create Task</button></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="v3-task-quick-bar">
    <span class="tqb-label">⚡ Quick Add</span>
    <input class="tqb-input" id="quickTaskInput" placeholder="Task title... press Enter to add" maxlength="200">
    <button class="tqb-btn" onclick="quickAddTask()">+ Add</button>
</div>

<div class="modal-overlay" id="taskModal">
    <div class="modal modal-wide">
        <h3 class="modal-title" id="taskModalTitle">New Task</h3>
        <form id="taskForm" onsubmit="return saveTask(event)">
            <input type="hidden" name="id" id="tf-id" value="0">
            <input type="hidden" name="status" id="tf-status" value="pending">
            <div class="form-group">
                <label>Title *</label>
                <input class="form-control" name="title" id="tf-title" maxlength="200" required placeholder="What needs to be done?">
                <div class="char-counter" id="charCounter">0/200</div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="description" id="tf-desc" rows="3" placeholder="Optional details..."></textarea>
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
                <div class="form-group" style="position:relative">
                    <label>Category</label>
                    <input class="form-control" name="category" id="tf-category" placeholder="e.g. Design, Client, Admin" list="cat-list" autocomplete="off">
                    <datalist id="cat-list">
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= h($c) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>Link to Lead</label>
                    <div style="position:relative">
                        <input class="form-control" name="lead_search" id="tf-lead-search" placeholder="Search lead..." autocomplete="off" oninput="searchLead(this.value)">
                        <input type="hidden" name="lead_id" id="tf-lead-id" value="">
                        <div class="lead-suggest" id="lead-suggest"></div>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Assignee Type</label>
                    <select class="form-control" name="assignee_type" id="tf-assignee-type">
                        <option value="">None</option>
                        <option value="person">Person</option>
                        <option value="company">Company</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Assignee Name</label>
                    <input class="form-control" name="assignee_name" id="tf-assignee-name" placeholder="Enter name..." list="assignee-list" autocomplete="off">
                    <datalist id="assignee-list">
                        <?php foreach ($allAssignees as $a): ?>
                        <option value="<?= h($a['assignee_name']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
                <button type="button" class="btn btn-secondary" onclick="closeTaskModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
var leads = <?= json_encode($leads) ?>;
var draggedId = null;

function allowDrop(e) { e.preventDefault(); e.currentTarget.classList.add('drag-over'); }
function dragLeave(e) { e.currentTarget.classList.remove('drag-over'); }

function dragStart(e) {
    draggedId = e.currentTarget.dataset.id;
    e.dataTransfer.setData('text/plain', draggedId);
    e.dataTransfer.effectAllowed = 'move';
    setTimeout(function() { e.currentTarget.classList.add('dragging'); }, 0);
}

function dragEnd(e) {
    e.currentTarget.classList.remove('dragging');
    document.querySelectorAll('.kanban-cards').forEach(function(l) { l.classList.remove('drag-over'); });
    if (window._pendingReorder) {
        clearTimeout(window._pendingReorder);
    }
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
    var id = e.dataTransfer.getData('text/plain');
    var dragging = document.querySelector('.task-card.dragging');
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
    updateStatusAjax(id, newStatus);
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
    document.querySelectorAll('.stat-card')[0].querySelector('.stat-value').textContent = total;
    document.querySelectorAll('.stat-card')[1].querySelector('.stat-value').textContent = p;
    document.querySelectorAll('.stat-card')[2].querySelector('.stat-value').textContent = ip;
    document.querySelectorAll('.stat-card')[3].querySelector('.stat-value').textContent = d;
    document.querySelectorAll('.stat-card')[4].querySelector('.stat-value').textContent = total ? Math.round(d / total * 100) + '%' : '0%';
}

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
        document.getElementById('tf-lead-id').value = data.lead_id || '';
        document.getElementById('tf-assignee-type').value = data.assignee_type || '';
        document.getElementById('tf-assignee-name').value = data.assignee_name || '';
        if (data.lead_id) {
            var lead = leads.find(function(l) { return l.id == data.lead_id; });
            document.getElementById('tf-lead-search').value = lead ? lead.business_name : '';
        } else {
            document.getElementById('tf-lead-search').value = '';
        }
    } else {
        document.getElementById('taskModalTitle').textContent = 'New Task';
        document.getElementById('tf-id').value = 0;
        document.getElementById('tf-title').value = '';
        document.getElementById('tf-desc').value = '';
        document.getElementById('tf-status').value = 'pending';
        document.getElementById('tf-priority').value = 'medium';
        document.getElementById('tf-due').value = '';
        document.getElementById('tf-category').value = '';
        document.getElementById('tf-lead-id').value = '';
        document.getElementById('tf-lead-search').value = '';
        document.getElementById('tf-assignee-type').value = '';
        document.getElementById('tf-assignee-name').value = '';
    }
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
    var card = document.querySelector('.task-card[data-id="' + id + '"]');
    if (card) {
        openTaskModal({
            id: parseInt(id),
            title: card.querySelector('.card-title').textContent,
            status: card.dataset.status,
            priority: card.querySelector('.card-priority').textContent.trim().toLowerCase()
        });
    }
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

function searchLead(q) {
    var c = document.getElementById('lead-suggest');
    c.innerHTML = '';
    if (!q.trim()) { c.style.display = 'none'; return; }
    var m = leads.filter(function(l) { return l.business_name.toLowerCase().includes(q.toLowerCase()); });
    if (!m.length) { c.style.display = 'none'; return; }
    c.style.display = 'block';
    m.forEach(function(l) {
        var div = document.createElement('div');
        div.textContent = l.business_name;
        div.onclick = function() {
            document.getElementById('tf-lead-search').value = l.business_name;
            document.getElementById('tf-lead-id').value = l.id;
            c.style.display = 'none';
        };
        c.appendChild(div);
    });
}

document.addEventListener('click', function(e) {
    var ls = document.getElementById('lead-suggest');
    if (ls && !e.target.closest('#tf-lead-search') && !e.target.closest('#lead-suggest')) {
        ls.style.display = 'none';
    }
});

document.getElementById('taskModal').addEventListener('click', function(e) {
    if (e.target === this) closeTaskModal();
});

function applyFilters() {
    var params = [];
    var p = document.getElementById('f-priority').value;
    var c = document.getElementById('f-category').value;
    var a = document.getElementById('f-assignee').value;
    var s = document.getElementById('f-search').value;
    var o = document.getElementById('f-overdue').checked ? '1' : '';
    if (p) params.push('priority=' + p);
    if (c) params.push('category=' + encodeURIComponent(c));
    if (a) params.push('assignee=' + encodeURIComponent(a));
    if (s) params.push('search=' + encodeURIComponent(s));
    if (o) params.push('overdue=1');
    params.push('view=<?= $view ?>');
    window.location.href = 'tasks.php?' + params.join('&');
}

var searchDebounce = null;
document.getElementById('f-search').addEventListener('input', function() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(applyFilters, 300);
});

function setView(v) {
    var params = [];
    <?php foreach (['priority','category','assignee','search','overdue'] as $k): ?>
    var val = <?= json_encode($_GET[$k] ?? '') ?>;
    if (val) params.push('<?= $k ?>=' + encodeURIComponent(val));
    <?php endforeach; ?>
    params.push('view=' + v);
    window.location.href = 'tasks.php?' + params.join('&');
}

function sortTable(col) {
    var tbody = document.querySelector('#list-view table tbody');
    if (!tbody) return;
    var rows = Array.from(tbody.querySelectorAll('tr'));
    var dir = tbody.dataset.sortDir === 'asc' ? 'desc' : 'asc';
    tbody.dataset.sortDir = dir;
    rows.sort(function(a, b) {
        var va = a.children[col].textContent.trim().toLowerCase();
        var vb = b.children[col].textContent.trim().toLowerCase();
        if (va === vb) return 0;
        return dir === 'asc' ? (va > vb ? 1 : -1) : (va < vb ? 1 : -1);
    });
    rows.forEach(function(r) { tbody.appendChild(r); });
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
    document.getElementById('tf-due').value = '';
    document.getElementById('tf-category').value = '';
    document.getElementById('tf-lead-id').value = '';
    document.getElementById('tf-lead-search').value = '';
    document.getElementById('tf-assignee-type').value = '';
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
    // Quick add Enter key
    var qi = document.getElementById('quickTaskInput');
    if (qi) {
        qi.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); quickAddTask(); }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
