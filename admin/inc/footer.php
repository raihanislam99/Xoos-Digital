<!-- Command Palette (Ctrl+K) -->
<div class="v3-cmdk-overlay" id="v3CmdkOverlay">
    <div class="v3-cmdk-modal">
        <div class="v3-cmdk-input-wrap">
            <i class="ti ti-search"></i>
            <input class="v3-cmdk-input" id="v3CmdkInput" type="text" placeholder="Search pages, tasks, posts..." autocomplete="off" spellcheck="false">
        </div>
        <div class="v3-cmdk-hint-row">
            <span><kbd>↑↓</kbd> Navigate</span>
            <span><kbd>Enter</kbd> Open</span>
            <span><kbd>Esc</kbd> Close</span>
        </div>
        <div class="v3-cmdk-results" id="v3CmdkResults"></div>
    </div>
</div>

<!-- Keyboard Shortcuts Overlay (?) -->
<div class="v3-shortcuts-overlay" id="v3ShortcutsOverlay">
    <div class="v3-shortcuts-modal">
        <h3>Keyboard Shortcuts</h3>
        <div class="sk-row"><span>Open command palette</span> <kbd>Ctrl+K</kbd></div>
        <div class="sk-row"><span>Show shortcuts</span> <kbd>?</kbd></div>
        <div class="sk-row"><span>Close modal / palette</span> <kbd>Esc</kbd></div>
        <div class="sk-row"><span>New task (quick)</span> <kbd>N</kbd></div>
        <div style="margin-top:1rem;text-align:center;font-size:0.72rem;color:var(--v3-text3)">Click anywhere or press <kbd>Esc</kbd> to close</div>
    </div>
</div>

<!-- Toast Container -->
<div class="v3-toast-container" id="v3ToastContainer"></div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="text-align:center">
        <div style="width:56px;height:56px;border-radius:50%;background:var(--red-bg);border:1px solid rgba(255, 77, 109, 0.2);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem">
            <i class="ti ti-trash" style="font-size:24px;color:var(--red)"></i>
        </div>
        <h3 class="modal-title">Confirm Delete</h3>
        <p style="color:var(--text2);font-size:0.875rem;margin-bottom:1.5rem" id="deleteMsg">
            This action cannot be undone.
        </p>
        <div class="modal-actions" style="justify-content:center">
            <button onclick="closeDeleteModal()" class="btn btn-secondary btn-sm">
                Cancel
            </button>
            <a id="deleteConfirmBtn" class="btn btn-danger btn-sm">
                Delete
            </a>
        </div>
    </div>
</div>
<script>
function confirmDelete(url, itemName) {
    document.getElementById('deleteMsg').textContent = 'Delete "' + itemName + '"? This cannot be undone.';
    document.getElementById('deleteConfirmBtn').href = url;
    document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('open');
}
function showImagePreview(input, previewId) {
    var url = input.value.trim();
    var div = document.getElementById(previewId);
    if (!div) return;
    if (url) {
        var base = input.getAttribute('data-base') || '';
        var src = url.match(/^(https?:|\/)/i) ? url : base + url;
        div.innerHTML = '<img src="' + src.replace(/"/g,'&quot;') + '" style="max-width:200px;max-height:120px;border-radius:6px;border:1px solid var(--border);margin-top:6px" onerror="this.style.display=\'none\'">';
    } else {
        div.innerHTML = '';
    }
}
function handleFilePreview(input, previewId) {
    var div = document.getElementById(previewId);
    if (!div) return;
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            div.innerHTML = '<img src="' + e.target.result + '" style="max-width:200px;max-height:120px;border-radius:6px;border:1px solid var(--border);margin-top:6px">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
window.showToast = function(msg, type) {
    var toast = document.createElement('div');
    toast.className = 'toast-msg' + (type === 'error' ? ' toast-error' : '');
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(function() { toast.classList.add('show'); }, 10);
    setTimeout(function() {
        toast.classList.remove('show');
        setTimeout(function() { toast.remove(); }, 300);
    }, 3000);
};

document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('deleteModal');
    if (el) el.addEventListener('click', function(e) { if (e.target === this) closeDeleteModal(); });
    document.querySelectorAll('input[oninput*="showImagePreview"]').forEach(function(input) {
        var m = input.getAttribute('oninput').match(/showImagePreview\(this,'([^']+)'\)/);
        if (m && input.value.trim()) showImagePreview(input, m[1]);
    });
});
</script>
</body>
</html>