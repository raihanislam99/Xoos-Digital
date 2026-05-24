<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#6366f1">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Xoos TaskBoard">
  <link rel="manifest" href="manifest.json">
  <link rel="apple-touch-icon" href="icons/icon.php?s=192">
  <title>Xoos TaskBoard</title>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    [data-theme="light"] {
      --bg-primary: #f0f4f8;
      --bg-secondary: rgba(255, 255, 255, 0.6);
      --bg-card: rgba(255, 255, 255, 0.7);
      --bg-header: rgba(255, 255, 255, 0.8);
      --text-primary: #1a202c;
      --text-secondary: #64748b;
      --border-color: rgba(255, 255, 255, 0.4);
      --shadow-color: rgba(0, 0, 0, 0.08);
      --accent: #6366f1;
      --accent-hover: #4f46e5;
      --column-bg: rgba(241, 245, 249, 0.8);
      --column-border: rgba(203, 213, 225, 0.5);
      --input-bg: rgba(255, 255, 255, 0.8);
      --input-border: #cbd5e1;
      --modal-backdrop: rgba(0, 0, 0, 0.4);
      --stat-bg: rgba(255, 255, 255, 0.7);
      --drop-highlight: rgba(99, 102, 241, 0.08);
      --empty-text: #94a3b8;
    }

    [data-theme="dark"] {
      --bg-primary: #0f172a;
      --bg-secondary: rgba(30, 41, 59, 0.6);
      --bg-card: rgba(30, 41, 59, 0.7);
      --bg-header: rgba(15, 23, 42, 0.9);
      --text-primary: #f1f5f9;
      --text-secondary: #94a3b8;
      --border-color: rgba(148, 163, 184, 0.15);
      --shadow-color: rgba(0, 0, 0, 0.3);
      --accent: #818cf8;
      --accent-hover: #6366f1;
      --column-bg: rgba(30, 41, 59, 0.6);
      --column-border: rgba(71, 85, 105, 0.5);
      --input-bg: rgba(30, 41, 59, 0.8);
      --input-border: #475569;
      --modal-backdrop: rgba(0, 0, 0, 0.6);
      --stat-bg: rgba(30, 41, 59, 0.7);
      --drop-highlight: rgba(129, 140, 248, 0.1);
      --empty-text: #475569;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
      min-height: 100vh;
      transition: background-color 0.3s, color 0.3s;
    }

    .app-header {
      position: sticky;
      top: 0;
      z-index: 10;
      background: var(--bg-header);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border-color);
      padding: 16px 24px;
    }

    .header-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;
    }

    .app-title {
      font-size: 1.4rem;
      font-weight: 700;
      letter-spacing: -0.02em;
    }

    .header-actions { display: flex; gap: 8px; align-items: center; }

    .btn {
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.875rem;
      font-weight: 500;
      transition: background-color 0.2s, transform 0.1s, box-shadow 0.2s;
    }
    .btn:active { transform: scale(0.97); }

    .btn-primary {
      background: var(--accent);
      color: #fff;
      padding: 8px 16px;
    }
    .btn-primary:hover { background: var(--accent-hover); box-shadow: 0 2px 8px var(--shadow-color); }

    .btn-secondary {
      background: var(--bg-secondary);
      color: var(--text-primary);
      padding: 8px 16px;
      border: 1px solid var(--border-color);
    }
    .btn-secondary:hover { background: var(--column-bg); }

    .btn-icon {
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      color: var(--text-primary);
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      border-radius: 8px;
    }
    .btn-icon:hover { background: var(--column-bg); }

    .theme-icon { transition: transform 0.3s; display: inline-block; }

    .filter-bar { display: flex; gap: 8px; }

    .filter-input, .filter-select {
      padding: 8px 12px;
      border-radius: 8px;
      border: 1px solid var(--input-border);
      background: var(--input-bg);
      color: var(--text-primary);
      font-size: 0.875rem;
      transition: border-color 0.2s, box-shadow 0.2s;
      outline: none;
    }
    .filter-input:focus, .filter-select:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }
    .filter-input { flex: 1; min-width: 0; }

    /* --- Stats --- */
    .stats-panel {
      display: flex;
      gap: 12px;
      padding: 16px 24px;
      flex-wrap: wrap;
    }

    .stat-card {
      flex: 1;
      min-width: 120px;
      background: var(--stat-bg);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 14px 16px;
      text-align: center;
      box-shadow: 0 2px 12px var(--shadow-color);
    }

    .stat-value {
      font-size: 1.6rem;
      font-weight: 700;
      line-height: 1.2;
    }
    .stat-label {
      font-size: 0.75rem;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-top: 2px;
    }

    /* --- Board --- */
    .board {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      padding: 0 24px 24px;
      min-height: calc(100vh - 220px);
    }

    .column {
      background: var(--column-bg);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      border: 1px solid var(--column-border);
      border-radius: 14px;
      padding: 16px;
      display: flex;
      flex-direction: column;
      transition: background-color 0.3s;
    }

    .column-header { margin-bottom: 12px; }

    .column-title {
      font-size: 0.9rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .column-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      display: inline-block;
    }
    .dot-todo { background: #6366f1; }
    .dot-progress { background: #f59e0b; }
    .dot-done { background: #22c55e; }

    .column-count {
      background: var(--bg-secondary);
      border-radius: 10px;
      padding: 1px 8px;
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--text-secondary);
    }

    .card-list {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 10px;
      min-height: 60px;
      border-radius: 10px;
      transition: background-color 0.2s;
      padding: 4px;
    }

    .card-list.drag-over {
      background: var(--drop-highlight);
      outline: 2px dashed var(--accent);
      outline-offset: -2px;
      border-radius: 10px;
    }

    .empty-state {
      text-align: center;
      color: var(--empty-text);
      font-size: 0.8rem;
      padding: 24px 8px;
      font-style: italic;
    }

    /* --- Cards --- */
    .card {
      background: var(--bg-card);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid var(--border-color);
      border-radius: 10px;
      padding: 14px;
      cursor: grab;
      transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
      box-shadow: 0 2px 12px var(--shadow-color);
      animation: slideIn 0.25s ease-out;
    }
    .card:hover { box-shadow: 0 6px 20px var(--shadow-color); transform: translateY(-1px); }
    .card.dragging { opacity: 0.5; transform: rotate(2deg) scale(1.02); cursor: grabbing; }
    .card.fade-out { animation: fadeOut 0.2s ease-out forwards; }

    .card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px; }
    .card-title { font-size: 0.9rem; font-weight: 600; line-height: 1.3; word-break: break-word; }

    .card-actions { display: flex; gap: 2px; flex-shrink: 0; }
    .card-action-btn {
      background: none;
      border: none;
      cursor: pointer;
      color: var(--text-secondary);
      font-size: 0.85rem;
      padding: 2px 5px;
      border-radius: 4px;
      transition: background-color 0.15s, color 0.15s;
      line-height: 1;
    }
    .card-action-btn:hover { background: var(--bg-secondary); color: var(--text-primary); }

    .card-desc {
      font-size: 0.8rem;
      color: var(--text-secondary);
      line-height: 1.4;
      margin-bottom: 8px;
      word-break: break-word;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .card-meta { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }

    .priority-badge {
      font-size: 0.7rem;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 6px;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }
    .priority-high { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
    .priority-medium { background: rgba(245, 158, 11, 0.15); color: #d97706; }
    .priority-low { background: rgba(34, 197, 94, 0.15); color: #16a34a; }

    .due-date {
      font-size: 0.7rem;
      color: var(--text-secondary);
      display: flex;
      align-items: center;
      gap: 3px;
    }
    .due-date.overdue { color: #ef4444; font-weight: 600; }

    .assignee-badge {
      font-size: 0.7rem;
      font-weight: 500;
      padding: 2px 8px;
      border-radius: 6px;
      display: inline-flex;
      align-items: center;
      gap: 3px;
    }
    .assignee-person { background: rgba(139, 92, 246, 0.15); color: #7c3aed; }
    .assignee-company { background: rgba(6, 182, 212, 0.15); color: #0891b2; }

    .move-select {
      font-size: 0.7rem;
      padding: 2px 4px;
      border-radius: 4px;
      border: 1px solid var(--input-border);
      background: var(--input-bg);
      color: var(--text-primary);
      display: none;
    }

    /* --- Modal --- */
    .modal {
      border: none;
      border-radius: 16px;
      background: var(--bg-card);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      color: var(--text-primary);
      padding: 0;
      max-width: 440px;
      width: calc(100% - 32px);
      box-shadow: 0 24px 48px rgba(0, 0, 0, 0.2);
      animation: modalIn 0.2s ease-out;
    }
    .modal::backdrop { background: var(--modal-backdrop); animation: backdropIn 0.2s ease-out; }

    .modal-content { padding: 24px; }
    .modal-title { font-size: 1.15rem; font-weight: 700; margin-bottom: 20px; }

    .form-label {
      display: flex;
      flex-direction: column;
      gap: 4px;
      font-size: 0.8rem;
      font-weight: 500;
      color: var(--text-secondary);
      margin-bottom: 14px;
    }

    .form-input {
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid var(--input-border);
      background: var(--input-bg);
      color: var(--text-primary);
      font-size: 0.9rem;
      font-family: inherit;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); }
    .form-textarea { resize: vertical; min-height: 60px; }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    .modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px; }

    /* --- Animations --- */
    @keyframes slideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeOut { to { opacity: 0; transform: scale(0.95); } }
    @keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(8px); } to { opacity: 1; transform: scale(1) translateY(0); } }
    @keyframes backdropIn { from { opacity: 0; } to { opacity: 1; } }

    /* --- Responsive --- */
    @media (max-width: 768px) {
      .app-header { padding: 12px 16px; }
      .filter-bar { flex-direction: column; }
      .stats-panel { padding: 12px 16px; }
      .stat-card { min-width: 80px; }
      .board { grid-template-columns: 1fr; padding: 0 16px 16px; min-height: auto; }
      .move-select { display: inline-block; }
    }
  </style>
</head>
<body data-theme="light">

  <header class="app-header">
    <div class="header-top">
      <h1 class="app-title">Xoos TaskBoard</h1>
      <div class="header-actions">
        <button class="btn btn-primary" id="add-task-btn">+ New Task</button>
        <button class="btn btn-icon" id="theme-toggle" aria-label="Toggle theme">
          <span class="theme-icon" id="theme-icon">&#9790;</span>
        </button>
      </div>
    </div>
    <div class="filter-bar">
      <input type="search" id="search-input" class="filter-input" placeholder="Search tasks...">
      <select id="filter-priority" class="filter-select">
        <option value="all">All Priorities</option>
        <option value="high">High</option>
        <option value="medium">Medium</option>
        <option value="low">Low</option>
      </select>
      <select id="filter-assignee" class="filter-select">
        <option value="all">All Assignees</option>
      </select>
    </div>
  </header>

  <section class="stats-panel" id="stats-panel"></section>

  <main class="board" id="board">
    <div class="column" data-status="todo">
      <div class="column-header">
        <h2 class="column-title"><span class="column-dot dot-todo"></span>To Do <span class="column-count" id="count-todo">0</span></h2>
      </div>
      <div class="card-list" data-status="todo" id="list-todo"></div>
    </div>
    <div class="column" data-status="in-progress">
      <div class="column-header">
        <h2 class="column-title"><span class="column-dot dot-progress"></span>In Progress <span class="column-count" id="count-progress">0</span></h2>
      </div>
      <div class="card-list" data-status="in-progress" id="list-progress"></div>
    </div>
    <div class="column" data-status="done">
      <div class="column-header">
        <h2 class="column-title"><span class="column-dot dot-done"></span>Done <span class="column-count" id="count-done">0</span></h2>
      </div>
      <div class="card-list" data-status="done" id="list-done"></div>
    </div>
  </main>

  <dialog id="task-modal" class="modal">
    <form id="task-form" class="modal-content">
      <h2 class="modal-title" id="modal-title">New Task</h2>
      <input type="hidden" id="edit-id" value="">
      <label class="form-label">
        Title
        <input type="text" id="task-title" class="form-input" required maxlength="100">
      </label>
      <label class="form-label">
        Description
        <textarea id="task-desc" class="form-input form-textarea" rows="3" maxlength="500"></textarea>
      </label>
      <div class="form-row">
        <label class="form-label">
          Priority
          <select id="task-priority" class="form-input">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
          </select>
        </label>
        <label class="form-label">
          Due Date
          <input type="date" id="task-due" class="form-input">
        </label>
      </div>
      <div class="form-row">
        <label class="form-label">
          Assignee Type
          <select id="task-assignee-type" class="form-input">
            <option value="">None</option>
            <option value="person">Person</option>
            <option value="company">Company</option>
          </select>
        </label>
        <label class="form-label">
          Assignee Name
          <input type="text" id="task-assignee-name" class="form-input" list="assignee-suggestions" placeholder="Enter name..." maxlength="100">
          <datalist id="assignee-suggestions"></datalist>
        </label>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" id="modal-cancel">Cancel</button>
        <button type="submit" class="btn btn-primary" id="modal-submit">Add Task</button>
      </div>
    </form>
  </dialog>

  <script>
  (function() {
    'use strict';

    const API_URL = 'api.php';
    const THEME_KEY = 'kanban-theme';
    const COLUMNS = [
      { status: 'todo', listId: 'list-todo', countId: 'count-todo' },
      { status: 'in-progress', listId: 'list-progress', countId: 'count-progress' },
      { status: 'done', listId: 'list-done', countId: 'count-done' }
    ];
    const STATUS_LABELS = { 'todo': 'To Do', 'in-progress': 'In Progress', 'done': 'Done' };

    let state = { tasks: [], theme: null, filter: { text: '', priority: 'all', assignee: 'all' } };
    let debounceTimer = null;
    let saving = false;

    // --- API ---
    async function api(action, body) {
      const opts = { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) };
      if (!body) { opts.method = 'GET'; delete opts.body; delete opts.headers; }
      const res = await fetch(`${API_URL}?action=${action}`, opts);
      if (!res.ok) throw new Error('API error');
      return res.json();
    }

    async function loadTasks() {
      try {
        state.tasks = await api('list');
        render();
      } catch (e) {
        console.error('Failed to load tasks:', e);
      }
    }

    // --- Storage (theme only) ---
    function saveTheme() { localStorage.setItem(THEME_KEY, state.theme); }
    function loadTheme() { state.theme = localStorage.getItem(THEME_KEY); }

    // --- Render ---
    function render() {
      const { text, priority, assignee } = state.filter;
      const lowerText = text.toLowerCase();

      const filtered = state.tasks.filter(t => {
        if (text && !t.title.toLowerCase().includes(lowerText) && !(t.description || '').toLowerCase().includes(lowerText)) return false;
        if (priority !== 'all' && t.priority !== priority) return false;
        if (assignee !== 'all') {
          const taskAssignee = t.assigneeType && t.assigneeName ? `${t.assigneeType}:${t.assigneeName}` : '';
          if (taskAssignee !== assignee) return false;
        }
        return true;
      });

      for (const col of COLUMNS) {
        const list = document.getElementById(col.listId);
        const colTasks = filtered.filter(t => t.status === col.status);
        const allColTasks = state.tasks.filter(t => t.status === col.status);
        document.getElementById(col.countId).textContent = allColTasks.length;

        list.innerHTML = '';
        if (colTasks.length === 0) {
          const empty = document.createElement('div');
          empty.className = 'empty-state';
          empty.textContent = text || priority !== 'all' ? 'No matching tasks' : 'Drop tasks here';
          list.appendChild(empty);
        } else {
          colTasks.forEach(task => list.appendChild(createCard(task)));
        }
      }

      renderStats();
      updateAssigneeFilter();
    }

    function createCard(task) {
      const card = document.createElement('div');
      card.className = 'card';
      card.draggable = true;
      card.dataset.id = task.id;

      const today = new Date().toISOString().split('T')[0];
      const isOverdue = task.dueDate && task.dueDate < today && task.status !== 'done';

      let dueDateHtml = '';
      if (task.dueDate) {
        const cls = isOverdue ? 'due-date overdue' : 'due-date';
        const formatted = new Date(task.dueDate + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        dueDateHtml = `<span class="${cls}">${isOverdue ? '! ' : ''}${formatted}</span>`;
      }

      let assigneeHtml = '';
      if (task.assigneeType && task.assigneeName) {
        const icon = task.assigneeType === 'person' ? '&#9823;' : '&#9959;';
        assigneeHtml = `<span class="assignee-badge assignee-${task.assigneeType}">${icon} ${esc(task.assigneeName)}</span>`;
      }

      const moveOptions = Object.entries(STATUS_LABELS)
        .filter(([s]) => s !== task.status)
        .map(([s, label]) => `<option value="${s}">${label}</option>`)
        .join('');

      card.innerHTML = `
        <div class="card-top">
          <span class="card-title">${esc(task.title)}</span>
          <div class="card-actions">
            <button class="card-action-btn edit-btn" title="Edit">&#9998;</button>
            <button class="card-action-btn delete-btn" title="Delete">&times;</button>
          </div>
        </div>
        ${task.description ? `<div class="card-desc">${esc(task.description)}</div>` : ''}
        <div class="card-meta">
          <span class="priority-badge priority-${task.priority}">${task.priority}</span>
          ${assigneeHtml}
          ${dueDateHtml}
          <select class="move-select" title="Move to">${moveOptions}</select>
        </div>
      `;

      card.querySelector('.edit-btn').addEventListener('click', e => { e.stopPropagation(); openModal(task.id); });
      card.querySelector('.delete-btn').addEventListener('click', e => { e.stopPropagation(); deleteTask(task.id, card); });

      const moveSelect = card.querySelector('.move-select');
      moveSelect.addEventListener('change', e => {
        e.stopPropagation();
        moveTask(task.id, e.target.value);
      });

      card.addEventListener('dragstart', onDragStart);
      card.addEventListener('dragend', onDragEnd);

      return card;
    }

    function esc(str) {
      const d = document.createElement('div');
      d.textContent = str;
      return d.innerHTML;
    }

    function renderStats() {
      const total = state.tasks.length;
      const todo = state.tasks.filter(t => t.status === 'todo').length;
      const prog = state.tasks.filter(t => t.status === 'in-progress').length;
      const done = state.tasks.filter(t => t.status === 'done').length;
      const rate = total ? Math.round(done / total * 100) : 0;

      document.getElementById('stats-panel').innerHTML = `
        <div class="stat-card"><div class="stat-value">${total}</div><div class="stat-label">Total</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#6366f1">${todo}</div><div class="stat-label">To Do</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#f59e0b">${prog}</div><div class="stat-label">In Progress</div></div>
        <div class="stat-card"><div class="stat-value" style="color:#22c55e">${done}</div><div class="stat-label">Done</div></div>
        <div class="stat-card"><div class="stat-value">${rate}%</div><div class="stat-label">Complete</div></div>
      `;
    }

    // --- CRUD ---
    async function addTask(data) {
      const id = crypto.randomUUID();
      const task = {
        id,
        title: data.title.trim(),
        description: (data.description || '').trim(),
        priority: data.priority,
        status: 'todo',
        dueDate: data.dueDate || null,
        assigneeType: data.assigneeType || null,
        assigneeName: (data.assigneeName || '').trim() || null,
        createdAt: Date.now(),
        updatedAt: Date.now()
      };
      state.tasks.push(task);
      render();
      try {
        await api('create', task);
      } catch (e) {
        console.error('Failed to create task:', e);
        await loadTasks();
      }
    }

    async function editTask(id, data) {
      const task = state.tasks.find(t => t.id === id);
      if (!task) return;
      task.title = data.title.trim();
      task.description = (data.description || '').trim();
      task.priority = data.priority;
      task.dueDate = data.dueDate || null;
      task.assigneeType = data.assigneeType || null;
      task.assigneeName = (data.assigneeName || '').trim() || null;
      task.updatedAt = Date.now();
      render();
      try {
        await api('update', { id, ...data });
      } catch (e) {
        console.error('Failed to update task:', e);
        await loadTasks();
      }
    }

    async function deleteTask(id, cardEl) {
      if (!confirm('Delete this task?')) return;
      if (cardEl) {
        cardEl.classList.add('fade-out');
        cardEl.addEventListener('animationend', () => {
          state.tasks = state.tasks.filter(t => t.id !== id);
          render();
        });
      } else {
        state.tasks = state.tasks.filter(t => t.id !== id);
        render();
      }
      try {
        await api('delete', { id });
      } catch (e) {
        console.error('Failed to delete task:', e);
        await loadTasks();
      }
    }

    async function moveTask(id, newStatus) {
      const task = state.tasks.find(t => t.id === id);
      if (!task) return;
      task.status = newStatus;
      task.updatedAt = Date.now();
      render();
      try {
        await api('update', { id, status: newStatus });
      } catch (e) {
        console.error('Failed to move task:', e);
        await loadTasks();
      }
    }

    // --- Modal ---
    const modal = document.getElementById('task-modal');
    const form = document.getElementById('task-form');

    function openModal(editId) {
      const isEdit = !!editId;
      document.getElementById('modal-title').textContent = isEdit ? 'Edit Task' : 'New Task';
      document.getElementById('modal-submit').textContent = isEdit ? 'Save' : 'Add Task';
      document.getElementById('edit-id').value = editId || '';

      if (isEdit) {
        const task = state.tasks.find(t => t.id === editId);
        if (!task) return;
        document.getElementById('task-title').value = task.title;
        document.getElementById('task-desc').value = task.description || '';
        document.getElementById('task-priority').value = task.priority;
        document.getElementById('task-due').value = task.dueDate || '';
        document.getElementById('task-assignee-type').value = task.assigneeType || '';
        document.getElementById('task-assignee-name').value = task.assigneeName || '';
      } else {
        form.reset();
        document.getElementById('task-priority').value = 'medium';
        document.getElementById('task-assignee-type').value = '';
      }

      populateAssigneeSuggestions();
      modal.showModal();
    }

    function closeModal() { modal.close(); }

    form.addEventListener('submit', e => {
      e.preventDefault();
      const data = {
        title: document.getElementById('task-title').value,
        description: document.getElementById('task-desc').value,
        priority: document.getElementById('task-priority').value,
        dueDate: document.getElementById('task-due').value,
        assigneeType: document.getElementById('task-assignee-type').value,
        assigneeName: document.getElementById('task-assignee-name').value
      };
      const editId = document.getElementById('edit-id').value;
      if (editId) { editTask(editId, data); } else { addTask(data); }
      closeModal();
    });

    document.getElementById('modal-cancel').addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.getElementById('add-task-btn').addEventListener('click', () => openModal());

    // --- Drag & Drop ---
    let draggedId = null;

    function onDragStart(e) {
      draggedId = e.currentTarget.dataset.id;
      e.currentTarget.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', draggedId);
    }

    function onDragEnd(e) {
      e.currentTarget.classList.remove('dragging');
      draggedId = null;
      document.querySelectorAll('.card-list').forEach(l => l.classList.remove('drag-over'));
    }

    document.querySelectorAll('.card-list').forEach(list => {
      list.addEventListener('dragover', e => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        list.classList.add('drag-over');

        const afterEl = getDragAfterElement(list, e.clientY);
        const dragging = document.querySelector('.card.dragging');
        if (!dragging) return;
        if (afterEl) {
          list.insertBefore(dragging, afterEl);
        } else {
          const empty = list.querySelector('.empty-state');
          if (empty) list.removeChild(empty);
          list.appendChild(dragging);
        }
      });

      list.addEventListener('dragleave', e => {
        if (!list.contains(e.relatedTarget)) list.classList.remove('drag-over');
      });

      list.addEventListener('drop', e => {
        e.preventDefault();
        list.classList.remove('drag-over');
        const id = e.dataTransfer.getData('text/plain');
        const newStatus = list.dataset.status;
        const task = state.tasks.find(t => t.id === id);
        if (!task) return;

        task.status = newStatus;
        task.updatedAt = Date.now();

        const cards = [...list.querySelectorAll('.card')];
        const orderedIds = cards.map(c => c.dataset.id);
        const otherTasks = state.tasks.filter(t => !orderedIds.includes(t.id));
        const reordered = orderedIds.map(cid => state.tasks.find(t => t.id === cid)).filter(Boolean);
        state.tasks = [...otherTasks, ...reordered];

        render();
        const order = state.tasks.map((t, i) => ({ id: t.id, status: t.status }));
        api('reorder', { order }).catch(e => {
          console.error('Failed to reorder:', e);
          loadTasks();
        });
      });
    });

    function getDragAfterElement(list, y) {
      const cards = [...list.querySelectorAll('.card:not(.dragging)')];
      return cards.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
          return { offset, element: child };
        }
        return closest;
      }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    // --- Filters ---
    document.getElementById('search-input').addEventListener('input', e => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        state.filter.text = e.target.value;
        render();
      }, 200);
    });

    document.getElementById('filter-priority').addEventListener('change', e => {
      state.filter.priority = e.target.value;
      render();
    });

    document.getElementById('filter-assignee').addEventListener('change', e => {
      state.filter.assignee = e.target.value;
      render();
    });

    function getUniqueAssignees() {
      const seen = new Map();
      for (const t of state.tasks) {
        if (t.assigneeType && t.assigneeName) {
          const key = `${t.assigneeType}:${t.assigneeName}`;
          if (!seen.has(key)) seen.set(key, { type: t.assigneeType, name: t.assigneeName });
        }
      }
      return [...seen.values()].sort((a, b) => a.name.localeCompare(b.name));
    }

    function updateAssigneeFilter() {
      const select = document.getElementById('filter-assignee');
      const current = select.value;
      const assignees = getUniqueAssignees();
      select.innerHTML = '<option value="all">All Assignees</option>';
      for (const a of assignees) {
        const val = `${a.type}:${a.name}`;
        const icon = a.type === 'person' ? '♟ ' : '⛷ ';
        const opt = document.createElement('option');
        opt.value = val;
        opt.textContent = `${icon}${a.name}`;
        select.appendChild(opt);
      }
      select.value = current;
      if (select.value !== current) {
        state.filter.assignee = 'all';
        select.value = 'all';
      }
    }

    function populateAssigneeSuggestions() {
      const datalist = document.getElementById('assignee-suggestions');
      const assignees = getUniqueAssignees();
      datalist.innerHTML = '';
      for (const a of assignees) {
        const opt = document.createElement('option');
        opt.value = a.name;
        datalist.appendChild(opt);
      }
    }

    // --- Theme ---
    function applyTheme(theme) {
      document.body.dataset.theme = theme;
      state.theme = theme;
      document.getElementById('theme-icon').innerHTML = theme === 'dark' ? '&#9728;' : '&#9790;';
      saveTheme();
    }

    function initTheme() {
      if (state.theme) {
        applyTheme(state.theme);
      } else {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(prefersDark ? 'dark' : 'light');
      }
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (!state.theme) applyTheme(e.matches ? 'dark' : 'light');
      });
    }

    document.getElementById('theme-toggle').addEventListener('click', () => {
      applyTheme(state.theme === 'dark' ? 'light' : 'dark');
    });

    // --- Init ---
    loadTheme();
    initTheme();
    loadTasks();
  })();
  </script>
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('sw.js');
    }
  </script>
</body>
</html>
