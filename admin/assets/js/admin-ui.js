/* ═══════════════════════════════════════════════
   XOOS Digital Admin — UI Interactions
   ═══════════════════════════════════════════════ */

(function() {
  'use strict';

  // ── FADE-IN ON PAGE LOAD ──
  document.addEventListener('DOMContentLoaded', function() {
    var main = document.querySelector('.admin-main');
    if (main) main.style.opacity = '1';
  });

  // ── BUTTON PRESS SCALE ──
  document.addEventListener('mousedown', function(e) {
    var btn = e.target.closest('.btn, .quick-action-card, .outreach-tab, .settings-tab');
    if (btn) btn.style.transform = 'scale(0.97)';
  });
  document.addEventListener('mouseup', function(e) {
    var btn = e.target.closest('.btn, .quick-action-card, .outreach-tab, .settings-tab');
    if (btn) btn.style.transform = '';
  });

  // ── SETTINGS TAB SWITCHING ──
  var settingsTabs = document.querySelectorAll('.settings-tab');
  settingsTabs.forEach(function(tab) {
    tab.addEventListener('click', function() {
      var target = this.dataset.tab;
      if (!target) return;
      settingsTabs.forEach(function(t) { t.classList.remove('active'); });
      this.classList.add('active');
      document.querySelectorAll('.settings-section').forEach(function(s) { s.classList.remove('active'); });
      var section = document.getElementById('settings-' + target);
      if (section) section.classList.add('active');
      // Update URL hash
      history.replaceState(null, '', window.location.pathname + window.location.search + '#tab=' + target);
    });
  });
  // Restore settings tab from hash
  if (window.location.hash.indexOf('tab=') >= 0) {
    var hashTab = window.location.hash.split('tab=')[1];
    var tabBtn = document.querySelector('.settings-tab[data-tab="' + hashTab + '"]');
    if (tabBtn) tabBtn.click();
  }

  // ── TASKS SORTING (client-side DOM only) ──
  var sortSelect = document.getElementById('taskSort');
  if (sortSelect) {
    sortSelect.addEventListener('change', function() {
      var board = document.querySelector('.kanban-board');
      if (!board) return;
      var cols = board.querySelectorAll('.kanban-cards');
      var sortBy = this.value;

      cols.forEach(function(col) {
        var cards = Array.from(col.querySelectorAll('.task-card'));
        if (!cards.length) return;

        cards.sort(function(a, b) {
          switch (sortBy) {
            case 'due-asc': {
              var da = a.dataset.due || '9999-12-31';
              var db = b.dataset.due || '9999-12-31';
              return da.localeCompare(db);
            }
            case 'due-desc': {
              var da = a.dataset.due || '9999-12-31';
              var db = b.dataset.due || '9999-12-31';
              return db.localeCompare(da);
            }
            case 'name': {
              var na = (a.querySelector('.card-title') || {}).textContent || '';
              var nb = (b.querySelector('.card-title') || {}).textContent || '';
              return na.localeCompare(nb);
            }
            case 'priority': {
              var pri = { urgent: 0, high: 1, medium: 2, low: 3 };
              var pa = pri[a.dataset.priority] !== undefined ? pri[a.dataset.priority] : 99;
              var pb = pri[b.dataset.priority] !== undefined ? pri[b.dataset.priority] : 99;
              return pa - pb;
            }
            default: return 0;
          }
        });

        cards.forEach(function(card) { col.appendChild(card); });
      });
    });
  }

  // ── POST GENERATOR FILTER ──
  var genFilter = document.getElementById('genFilter');
  if (genFilter) {
    genFilter.addEventListener('input', function() {
      var q = this.value.toLowerCase();
      var cards = document.querySelectorAll('.post-card');
      cards.forEach(function(card) {
        var text = (card.textContent || '').toLowerCase();
        card.style.display = text.indexOf(q) >= 0 ? '' : 'none';
      });
    });
  }

  // ── TASK MODAL: CHARACTER COUNTER ──
  var taskTitle = document.getElementById('tf-title');
  var charCounter = document.getElementById('charCounter');
  if (taskTitle && charCounter) {
    taskTitle.addEventListener('input', function() {
      var len = this.value.length;
      charCounter.textContent = len + '/200';
      charCounter.classList.toggle('over', len > 200);
    });
  }

  // ── TASK MODAL: OVERDUE HIGHLIGHT ──
  var dueField = document.getElementById('tf-due');
  if (dueField) {
    dueField.addEventListener('change', function() {
      if (this.value && new Date(this.value) < new Date()) {
        this.classList.add('overdue-highlight');
      } else {
        this.classList.remove('overdue-highlight');
      }
    });
  }

  // ── LIST VIEW: ROW HOVER BORDER ──
  document.querySelectorAll('.table-wrap table tbody tr').forEach(function(row) {
    row.addEventListener('mouseenter', function() {
      this.style.borderLeft = '3px solid var(--neon-green)';
      this.style.paddingLeft = 'calc(1.25rem - 3px)';
    });
    row.addEventListener('mouseleave', function() {
      this.style.borderLeft = '';
      this.style.paddingLeft = '';
    });
  });

  // ── STAT CARD COUNT-UP ANIMATION ──
  document.querySelectorAll('.stat-value[data-count]').forEach(function(el) {
    var target = parseInt(el.dataset.count);
    var duration = 800;
    var start = performance.now();
    function update(now) {
      var t = Math.min((now - start) / duration, 1);
      var eased = 1 - Math.pow(1 - t, 3);
      el.textContent = Math.floor(eased * target) + (el.dataset.suffix || '');
      if (t < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
  });

  // ── AVATAR INITIALS ──
  document.querySelectorAll('.sidebar-avatar').forEach(function(el) {
    var name = el.dataset.name || 'A';
    el.textContent = name.charAt(0).toUpperCase();
  });

})();
