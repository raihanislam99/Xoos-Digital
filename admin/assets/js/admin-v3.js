/* ═══════════════════════════════════════════════
   XOOS Command Center v3 — UI Interactions
   ═══════════════════════════════════════════════ */

(function() {
  'use strict';

  /* ── TOAST SYSTEM ── */
  var toastContainer = null;
  var cmdkOverlay = null;
  var cmdkInput = null;
  var cmdkResults = null;
  var cmdkItems = [];
  var cmdkHighlightIndex = -1;

  function initV3() {
    toastContainer = document.getElementById('v3ToastContainer');
    if (!toastContainer) {
      toastContainer = document.createElement('div');
      toastContainer.id = 'v3ToastContainer';
      toastContainer.className = 'v3-toast-container';
      document.body.appendChild(toastContainer);
    }

    cmdkOverlay = document.getElementById('v3CmdkOverlay');
    cmdkInput = document.getElementById('v3CmdkInput');
    cmdkResults = document.getElementById('v3CmdkResults');

    bindCmdk();
    bindNotifications();
    bindSidebar();
    bindFadeIn();
  }

  window.v3Toast = function(message, type) {
    if (!toastContainer) return;
    type = type || 'info';
    var icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
    var toast = document.createElement('div');
    toast.className = 'v3-toast toast-' + type;
    toast.innerHTML =
      '<span class="toast-icon">' + (icons[type] || 'ℹ') + '</span>' +
      '<span class="toast-msg">' + message + '</span>' +
      '<button class="toast-close" onclick="this.parentElement.classList.add(\'toast-out\');setTimeout(function(){this.parentElement.remove()}.bind(this),200)">×</button>';
    toastContainer.appendChild(toast);
    setTimeout(function() {
      if (toast.parentNode) {
        toast.classList.add('toast-out');
        setTimeout(function() { if (toast.parentNode) toast.remove(); }, 200);
      }
    }, 3500);
  };

  // Override the old showToast
  window.showToast = function(msg, type) {
    window.v3Toast(msg, type === 'error' ? 'error' : 'success');
  };

  /* ── COMMAND PALETTE (Ctrl+K) ── */

  function openCmdk() {
    if (!cmdkOverlay) return;
    cmdkOverlay.classList.add('open');
    if (cmdkInput) {
      cmdkInput.value = '';
      cmdkInput.focus();
    }
    cmdkHighlightIndex = -1;
    renderCmdk('');
  }

  function closeCmdk() {
    if (!cmdkOverlay) return;
    cmdkOverlay.classList.remove('open');
  }

  function renderCmdk(query) {
    if (!cmdkResults) return;
    var q = query.toLowerCase().trim();

    // Build items from data
    var allItems = [
      { section: 'Today\u2019s Focus', items: [
        { label: 'Tasks', desc: 'Manage your tasks', icon: 'ti ti-checklist', iconClass: 'tasks', href: 'modules/tasks.php', badge: document.getElementById('v3FocusTasks') ? document.getElementById('v3FocusTasks').textContent : '' },
        { label: 'Blog Posts', desc: 'Write and manage content', icon: 'ti ti-news', iconClass: 'blog', href: 'modules/blog.php', badge: document.getElementById('v3FocusBlog') ? document.getElementById('v3FocusBlog').textContent : '' },
        { label: 'Post Generator', desc: 'Generate social media posts', icon: 'ti ti-file-text', iconClass: 'posts', href: 'modules/post-generator.php', badge: document.getElementById('v3FocusPosts') ? document.getElementById('v3FocusPosts').textContent : '' },
        { label: 'Outreach', desc: 'Leads, campaigns, and outreach', icon: 'ti ti-users-plus', iconClass: 'outreach', href: 'modules/leads.php', badge: document.getElementById('v3FocusOutreach') ? document.getElementById('v3FocusOutreach').textContent : '' },
      ]},
      { section: 'Content', items: [
        { label: 'Services', desc: 'Manage services', icon: 'ti ti-settings', href: 'modules/services.php' },
        { label: 'Packages', desc: 'Pricing packages', icon: 'ti ti-box', href: 'modules/packages.php' },
        { label: 'Testimonials', desc: 'Client testimonials', icon: 'ti ti-quote', href: 'modules/testimonials.php' },
        { label: 'FAQ', desc: 'Frequently asked questions', icon: 'ti ti-help', href: 'modules/faq.php' },
        { label: 'Portfolio', desc: 'Portfolio projects', icon: 'ti ti-briefcase', href: 'modules/portfolio.php' },
        { label: 'Brands', desc: 'Brand partners', icon: 'ti ti-building', href: 'modules/brands.php' },
        { label: 'Quote & Invoice', desc: 'Quotes and invoices', icon: 'ti ti-receipt', href: 'modules/quote-invoice.php' },
      ]},
      { section: 'Inbox', items: [
        { label: 'Messages', desc: 'Contact form messages', icon: 'ti ti-mail', href: 'modules/messages.php' },
        { label: 'Media Library', desc: 'Uploaded media files', icon: 'ti ti-photo', href: 'modules/media.php' },
      ]},
      { section: 'System', items: [
        { label: 'Settings', desc: 'Admin configuration', icon: 'ti ti-tool', href: 'modules/settings.php' },
        { label: 'Setup', desc: 'Database setup and migrations', icon: 'ti ti-database', href: 'setup.php' },
        { label: 'Credentials', desc: 'Change password and email', icon: 'ti ti-key', href: 'reset-password.php' },
        { label: 'Logout', desc: 'Sign out', icon: 'ti ti-logout', href: 'logout.php' },
      ]},
    ];

    var filtered = [];
    allItems.forEach(function(group) {
      var matched = group.items.filter(function(item) {
        if (!q) return true;
        return item.label.toLowerCase().indexOf(q) >= 0 ||
               item.desc.toLowerCase().indexOf(q) >= 0;
      });
      if (matched.length) {
        filtered.push({ section: group.section, items: matched });
      }
    });

    if (!filtered.length) {
      cmdkResults.innerHTML = '<div class="v3-cmdk-empty">No results found for "' + query + '"</div>';
      cmdkItems = [];
      return;
    }

    var html = '';
    cmdkItems = [];
    filtered.forEach(function(group) {
      html += '<div class="v3-cmdk-group-label">' + group.section + '</div>';
      group.items.forEach(function(item) {
        var badgeHtml = item.badge ? '<span class="ci-badge">' + item.badge + '</span>' : '';
        var iconClass = item.iconClass ? ' ' + item.iconClass : '';
        html += '<a class="v3-cmdk-item" data-href="' + item.href + '" href="' + item.href + '">' +
          '<span class="ci-icon' + iconClass + '"><i class="' + item.icon + '"></i></span>' +
          '<span class="ci-label">' + item.label + '<div class="ci-desc">' + item.desc + '</div></span>' +
          badgeHtml +
        '</a>';
        cmdkItems.push(item);
      });
    });
    cmdkResults.innerHTML = html;
    cmdkHighlightIndex = -1;

    // Click handler
    cmdkResults.querySelectorAll('.v3-cmdk-item').forEach(function(el) {
      el.addEventListener('click', function(e) {
        e.preventDefault();
        var href = this.dataset.href;
        if (href) window.location.href = href;
        closeCmdk();
      });
    });
  }

  function bindCmdk() {
    if (cmdkInput) {
      cmdkInput.addEventListener('input', function() {
        renderCmdk(this.value);
      });
      cmdkInput.addEventListener('keydown', function(e) {
        var items = cmdkResults.querySelectorAll('.v3-cmdk-item');
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          cmdkHighlightIndex = Math.min(cmdkHighlightIndex + 1, items.length - 1);
          updateHighlight(items);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          cmdkHighlightIndex = Math.max(cmdkHighlightIndex - 1, -1);
          updateHighlight(items);
        } else if (e.key === 'Enter' && cmdkHighlightIndex >= 0) {
          e.preventDefault();
          if (items[cmdkHighlightIndex]) items[cmdkHighlightIndex].click();
        }
      });
    }

    // Click outside to close command palette
    if (cmdkOverlay) {
      cmdkOverlay.addEventListener('click', function(e) {
        if (e.target === this) closeCmdk();
      });
    }

    // Ctrl+K button trigger
    document.querySelectorAll('[data-cmdk]').forEach(function(el) {
      el.addEventListener('click', function(e) {
        e.preventDefault();
        openCmdk();
      });
    });
  }

  function updateHighlight(items) {
    items.forEach(function(el, i) {
      el.classList.toggle('highlighted', i === cmdkHighlightIndex);
      if (i === cmdkHighlightIndex) el.scrollIntoView({ block: 'nearest' });
    });
  }

  // Global Ctrl+K listener
  document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      if (cmdkOverlay && cmdkOverlay.classList.contains('open')) {
        closeCmdk();
      } else {
        openCmdk();
      }
    }
    // Esc closes command palette and shortcuts
    if (e.key === 'Escape') {
      closeCmdk();
      var skOverlay = document.getElementById('v3ShortcutsOverlay');
      if (skOverlay) skOverlay.classList.remove('open');
    }
    // ? shows shortcuts
    if (e.key === '?' && !e.ctrlKey && !e.metaKey && !e.altKey) {
      var activeEl = document.activeElement;
      if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.isContentEditable)) return;
      e.preventDefault();
      var skOverlay = document.getElementById('v3ShortcutsOverlay');
      if (skOverlay) skOverlay.classList.toggle('open');
    }
  });

  // 'N' key → new task shortcut
  document.addEventListener('keydown', function(e) {
    if (e.key === 'n' || e.key === 'N') {
      if (e.ctrlKey || e.metaKey || e.altKey) return;
      var activeEl = document.activeElement;
      if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.isContentEditable)) return;
      var p = window.location.pathname;
      if (p.indexOf('index.php') >= 0 || p.indexOf('tasks.php') >= 0 || p === ADMIN_URL + '/' || p === ADMIN_URL + '/index.php') {
        e.preventDefault();
        window.location.href = 'modules/tasks.php';
      }
    }
  });

  function bindNotifications() {
    var bellBtn = document.getElementById('v3BellBtn');
    var bellDropdown = document.getElementById('v3BellDropdown');
    if (bellBtn && bellDropdown) {
      bellBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        bellDropdown.classList.toggle('open');
      });
      document.addEventListener('click', function(e) {
        if (!bellBtn.contains(e.target) && !bellDropdown.contains(e.target)) {
          bellDropdown.classList.remove('open');
        }
      });
    }
  }

  function bindSidebar() {
    var hamburger = document.querySelector('.hamburger');
    if (hamburger) {
      hamburger.addEventListener('click', function() {
        var sidebar = document.getElementById('sidebar');
        if (sidebar) sidebar.classList.toggle('open');
      });
    }

    var sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        var sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
      });
      // Restore collapsed state from localStorage
      if (localStorage.getItem('sidebarCollapsed') === '1' && window.innerWidth >= 1024) {
        document.getElementById('sidebar').classList.add('collapsed');
      }
    }
  }

  function bindFadeIn() {
    var main = document.querySelector('.admin-main, .v3-main');
    if (main) main.style.opacity = '1';
    var body = document.body;
    if (body) body.style.opacity = '1';
  }

  document.addEventListener('DOMContentLoaded', initV3);
})();
