const NotesApp = (() => {
  const API = 'notes_api.php';
  let currentCategoryId = 0;
  let currentNoteId = null;
  let currentSort = 'newest';
  let pinnedOnly = false;
  let viewMode = 'grid';
  let saveTimer = null;
  let allNotes = [];
  let categories = [];

  // ── API HELPER ──
  async function api(data) {
    const form = new FormData();
    Object.entries(data).forEach(([k, v]) => form.append(k, v ?? ''));
    const res = await fetch(API, { method: 'POST', body: form });
    const json = await res.json();
    if (json && json.error) console.warn('NotesApp API warning:', data.action, json.error);
    return json;
  }

  // ── TOAST ──
  function toast(msg, type = 'success') {
    const t = document.createElement('div');
    t.className = 'notes-toast notes-toast-' + type;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.classList.add('show'), 10);
    setTimeout(() => {
      t.classList.remove('show');
      setTimeout(() => t.remove(), 300);
    }, 3000);
  }

  // ── RENDER CATEGORIES ──
  async function loadCategories() {
    const data = await api({ action: 'get_categories' });
    if (!data || !data.categories) {
      console.error('NotesApp: get_categories returned invalid data', data);
      return;
    }
    categories = data.categories;
    const totalNotes = data.totalNotes || 0;
    const list = document.getElementById('ns-cat-list');

    list.querySelectorAll('li:not(:first-child)').forEach(el => el.remove());

    categories.forEach(cat => {
      const li = document.createElement('li');
      li.className = 'notes-cat-item' + (currentCategoryId == cat.id ? ' active' : '');
      li.dataset.id = cat.id;
      li.innerHTML =
        '<span class="cat-dot" style="background:' + cat.color + '"></span>' +
        '<span class="cat-name">' + escHtml(cat.name) + '</span>' +
        '<span class="cat-count">' + (cat.note_count || 0) + '</span>' +
        '<button class="cat-del-btn" data-id="' + cat.id + '" title="Delete">×</button>';
      list.appendChild(li);
    });

    document.getElementById('ns-total-count').textContent = totalNotes;
    document.getElementById('ns-stat-total').textContent = totalNotes;
  }

  // ── RENDER NOTES ──
  async function loadNotes() {
    const search = document.getElementById('ns-search').value;
    const params = {
      action: 'get_notes',
      category_id: currentCategoryId,
      search,
      sort: currentSort
    };
    const data = await api(params);
    if (!Array.isArray(data)) {
      console.error('NotesApp: get_notes returned non-array', data);
      return;
    }
    allNotes = data;

    const filtered = pinnedOnly
      ? allNotes.filter(n => n.is_pinned == 1)
      : allNotes;

    const pinned = filtered.filter(n => n.is_pinned == 1);
    const unpinned = filtered.filter(n => n.is_pinned != 1);

    document.getElementById('ns-stat-pinned').textContent = pinned.length;

    if (viewMode === 'grid') {
      renderGrid(pinned, unpinned);
    } else {
      renderList(filtered);
    }
  }

  function escHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatDate(str) {
    if (!str) return '';
    const d = new Date(str.replace(' ', 'T'));
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  function noteCardHTML(note) {
    const borderColor = note.note_color || note.category_color || '#333';
    const catPill = note.category_name
      ? '<span class="note-cat-pill" style="background:' + note.category_color + '22;color:' + note.category_color + '">' + escHtml(note.category_name) + '</span>'
      : '';
    const preview = (note.content || '').substring(0, 120);
    return '<div class="note-card ' + (note.is_pinned == 1 ? 'is-pinned' : '') + '" data-id="' + note.id + '" style="border-left-color:' + borderColor + '">' +
      '<div class="note-card-title">' + escHtml(note.title) + '</div>' +
      '<div class="note-card-preview">' + escHtml(preview) + '</div>' +
      '<div class="note-card-footer">' + catPill + '<span class="note-date">' + formatDate(note.updated_at) + '</span></div>' +
    '</div>';
  }

  function renderGrid(pinned, unpinned) {
    const grid = document.getElementById('ns-grid');
    grid.style.display = '';
    document.getElementById('ns-list').style.display = 'none';

    let html = '';

    if (pinned.length > 0) {
      html += '<div class="pinned-label">📌 PINNED</div><div class="notes-cards-grid pinned-grid">' +
        pinned.map(n => noteCardHTML(n)).join('') + '</div>';
    }

    if (unpinned.length > 0) {
      html += '<div class="notes-cards-grid">' +
        unpinned.map(n => noteCardHTML(n)).join('') + '</div>';
    }

    if (pinned.length === 0 && unpinned.length === 0) {
      html = '<div class="notes-empty"><div class="empty-icon">📝</div><div class="empty-title">No notes yet</div><div class="empty-sub">Click "+ New Note" to get started.</div></div>';
    }

    grid.innerHTML = html;
  }

  function renderList(notes) {
    document.getElementById('ns-grid').style.display = 'none';
    const list = document.getElementById('ns-list');
    list.style.display = '';

    if (notes.length === 0) {
      list.innerHTML = '<div class="notes-empty"><div class="empty-icon">📝</div><div class="empty-title">No notes</div></div>';
      return;
    }

    list.innerHTML =
      '<table class="notes-table"><thead><tr><th>Title</th><th>Category</th><th>Pinned</th><th>Updated</th><th>Actions</th></tr></thead><tbody>' +
      notes.map(n =>
        '<tr data-id="' + n.id + '">' +
          '<td class="note-title-cell">' + escHtml(n.title) + '</td>' +
          '<td>' + (n.category_name
            ? '<span class="note-cat-pill" style="background:' + n.category_color + '22;color:' + n.category_color + '">' + escHtml(n.category_name) + '</span>'
            : '—') + '</td>' +
          '<td>' + (n.is_pinned == 1 ? '📌' : '—') + '</td>' +
          '<td>' + formatDate(n.updated_at) + '</td>' +
          '<td><button class="list-edit-btn" data-id="' + n.id + '">Edit</button><button class="list-del-btn" data-id="' + n.id + '">Delete</button></td>' +
        '</tr>'
      ).join('') +
      '</tbody></table>';
  }

  // ── EDITOR ──
  function populateCategoryDropdown(selectedId) {
    const sel = document.getElementById('ns-cat-select');
    sel.innerHTML = '<option value="">No Category</option>';
    categories.forEach(cat => {
      const opt = document.createElement('option');
      opt.value = cat.id;
      opt.textContent = cat.name;
      if (cat.id == selectedId) opt.selected = true;
      sel.appendChild(opt);
    });
  }

  function openEditor(noteId) {
    document.getElementById('ns-grid').style.display = 'none';
    document.getElementById('ns-list').style.display = 'none';
    document.getElementById('ns-toolbar').style.display = 'none';
    const editor = document.getElementById('ns-editor');
    editor.style.display = 'flex';

    currentNoteId = noteId || null;
    document.getElementById('ns-id').value = noteId || '';
    document.getElementById('ns-status').textContent = '—';

    populateCategoryDropdown(null);

    if (noteId) {
      api({ action: 'get_note', id: noteId }).then(note => {
        document.getElementById('ns-title').value = note.title || '';
        document.getElementById('ns-content').value = note.content || '';
        populateCategoryDropdown(note.category_id);
        updatePinBtn(note.is_pinned == 1);
        setActiveSwatch(note.note_color || '');
      });
    } else {
      document.getElementById('ns-title').value = '';
      document.getElementById('ns-content').value = '';
      updatePinBtn(false);
      setActiveSwatch('');
    }
  }

  function closeEditor() {
    document.getElementById('ns-editor').style.display = 'none';
    document.getElementById('ns-toolbar').style.display = '';
    if (viewMode === 'grid') {
      document.getElementById('ns-grid').style.display = '';
    } else {
      document.getElementById('ns-list').style.display = '';
    }
    currentNoteId = null;
    loadNotes();
  }

  function updatePinBtn(isPinned) {
    document.getElementById('ns-pin-btn').textContent = isPinned ? '📌 Unpin' : '📍 Pin';
  }

  function setActiveSwatch(color) {
    document.querySelectorAll('.swatch').forEach(s => {
      s.classList.toggle('active', s.dataset.color === color);
    });
  }

  async function saveNote(closeAfter) {
    const id = document.getElementById('ns-id').value;
    const title = document.getElementById('ns-title').value.trim() || 'Untitled';
    const content = document.getElementById('ns-content').value;
    const cat_id = document.getElementById('ns-cat-select').value;
    const activeSwatch = document.querySelector('.swatch.active');
    const note_color = activeSwatch ? activeSwatch.dataset.color : '';

    document.getElementById('ns-status').textContent = 'Saving...';

    let result;
    if (id) {
      result = await api({ action: 'update_note', id, title, content, category_id: cat_id, note_color });
    } else {
      result = await api({ action: 'create_note', title, content, category_id: cat_id, note_color });
      if (result.success) {
        document.getElementById('ns-id').value = result.id;
        currentNoteId = result.id;
      }
    }

    if (result.success) {
      if (closeAfter) { closeEditor(); }
      else { document.getElementById('ns-status').textContent = 'Saved ✓ ' + new Date().toLocaleTimeString(); }
      loadCategories();
    } else {
      document.getElementById('ns-status').textContent = 'Error saving';
    }
  }

  // ── AUTO SAVE ──
  function scheduleAutoSave() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(saveNote, 2000);
  }

  // ── EVENT DELEGATION ──
  function bindEvents() {

    // Category list click
    document.getElementById('ns-cat-list').addEventListener('click', e => {
      const item = e.target.closest('.notes-cat-item');
      const delBtn = e.target.closest('.cat-del-btn');

      if (delBtn) {
        if (!confirm('Delete this category? Notes will be uncategorized.')) return;
        api({ action: 'delete_category', id: delBtn.dataset.id }).then(() => {
          loadCategories();
          loadNotes();
        });
        return;
      }

      if (item) {
        document.querySelectorAll('.notes-cat-item').forEach(el => el.classList.remove('active'));
        item.classList.add('active');
        currentCategoryId = item.dataset.id;
        loadNotes();
      }
    });

    // Add category
    document.getElementById('ns-add-cat-btn').addEventListener('click', () => {
      const form = document.getElementById('ns-cat-form');
      form.style.display = form.style.display === 'none' ? 'flex' : 'none';
    });

    document.getElementById('ns-cat-save').addEventListener('click', async () => {
      const name = document.getElementById('ns-cat-name').value.trim();
      const color = document.getElementById('ns-cat-color').value;
      if (!name) return;
      await api({ action: 'create_category', name, color });
      document.getElementById('ns-cat-name').value = '';
      document.getElementById('ns-cat-form').style.display = 'none';
      loadCategories();
    });

    document.getElementById('ns-cat-cancel').addEventListener('click', () => {
      document.getElementById('ns-cat-form').style.display = 'none';
    });

    // Search
    let searchTimer;
    document.getElementById('ns-search').addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(loadNotes, 300);
    });

    // Sort
    document.getElementById('ns-sort').addEventListener('change', e => {
      currentSort = e.target.value;
      loadNotes();
    });

    // Pinned filter
    document.getElementById('ns-pin-filter').addEventListener('click', function() {
      pinnedOnly = !pinnedOnly;
      this.classList.toggle('active', pinnedOnly);
      loadNotes();
    });

    // View toggles
    document.getElementById('ns-grid-btn').addEventListener('click', () => {
      viewMode = 'grid';
      document.getElementById('ns-grid-btn').classList.add('active');
      document.getElementById('ns-list-btn').classList.remove('active');
      loadNotes();
    });

    document.getElementById('ns-list-btn').addEventListener('click', () => {
      viewMode = 'list';
      document.getElementById('ns-list-btn').classList.add('active');
      document.getElementById('ns-grid-btn').classList.remove('active');
      loadNotes();
    });

    // Note card clicks (delegated)
    document.getElementById('ns-grid').addEventListener('click', e => {
      const card = e.target.closest('.note-card');
      if (card) openEditor(card.dataset.id);
    });

    // List view clicks (delegated)
    document.getElementById('ns-list').addEventListener('click', e => {
      const editBtn = e.target.closest('.list-edit-btn');
      const delBtn = e.target.closest('.list-del-btn');
      const row = e.target.closest('tr[data-id]');

      if (editBtn) { openEditor(editBtn.dataset.id); return; }
      if (delBtn) {
        if (!confirm('Delete this note?')) return;
        api({ action: 'delete_note', id: delBtn.dataset.id })
          .then(() => { toast('Note deleted'); loadNotes(); loadCategories(); });
        return;
      }
      if (row && !editBtn && !delBtn) openEditor(row.dataset.id);
    });

    // New Note button
    document.getElementById('ns-new-btn').addEventListener('click', () => {
      openEditor(null);
    });

    // Mobile sidebar toggle
    function toggleSidebar() {
      document.getElementById('ns-sidebar-toggle').classList.toggle('open');
      document.querySelector('.notes-sidebar').classList.toggle('open');
      document.getElementById('ns-sidebar-backdrop').classList.toggle('show');
    }
    document.getElementById('ns-sidebar-toggle').addEventListener('click', toggleSidebar);
    document.getElementById('ns-sidebar-backdrop').addEventListener('click', toggleSidebar);
    document.querySelector('.notes-sidebar .notes-cat-item')?.addEventListener('click', () => {
      if (window.innerWidth <= 768) toggleSidebar();
    });

    // Editor events
    document.getElementById('ns-back').addEventListener('click', closeEditor);

    document.getElementById('ns-save-btn').addEventListener('click', () => saveNote(true));

    document.getElementById('ns-title').addEventListener('input', scheduleAutoSave);
    document.getElementById('ns-content').addEventListener('input', scheduleAutoSave);
    document.getElementById('ns-cat-select').addEventListener('change', scheduleAutoSave);

    document.getElementById('ns-pin-btn').addEventListener('click', async () => {
      if (!currentNoteId) return;
      const res = await api({ action: 'toggle_pin', id: currentNoteId });
      updatePinBtn(res.is_pinned === 1);
      toast(res.is_pinned ? 'Note pinned' : 'Note unpinned');
    });

    document.getElementById('ns-del-btn').addEventListener('click', async () => {
      if (!currentNoteId) return;
      if (!confirm('Delete this note permanently?')) return;
      await api({ action: 'delete_note', id: currentNoteId });
      toast('Note deleted');
      closeEditor();
      loadCategories();
    });

    // Color swatches
    document.getElementById('ns-swatches').addEventListener('click', e => {
      const swatch = e.target.closest('.swatch');
      if (!swatch) return;
      document.querySelectorAll('.swatch').forEach(s => s.classList.remove('active'));
      swatch.classList.add('active');
      scheduleAutoSave();
    });
  }

  // ── PUBLIC INIT ──
  function init() {
    console.log('NotesApp.init() called');
    document.getElementById('ns-toolbar').style.display = 'flex';
    document.getElementById('ns-grid').style.display = 'block';
    document.getElementById('ns-list').style.display = 'none';
    const editor = document.getElementById('ns-editor');
    if (editor) editor.style.display = 'none';
    bindEvents();
    loadCategories();
    loadNotes();
    NotesApp.init = () => { console.log('NotesApp.init() re-called (reload only)'); loadCategories(); loadNotes(); };
  }

  NotesApp.openEditor = openEditor;
  NotesApp.closeEditor = closeEditor;
  NotesApp.saveNote = saveNote;

  return { init, openEditor, closeEditor };
})();

window.NotesApp = NotesApp;
console.log('NotesApp loaded and ready');
