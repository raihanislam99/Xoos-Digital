<?php
// Notes view — rendered by tasks.php?view=notes
$pdo = db();
?>
<link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/notes.css?v=1">
<script src="<?= ADMIN_URL ?>/assets/js/notes.js?v=1" defer></script>

<div id="notesApp">
    <div class="page-header">
        <h1 class="page-title">Notes</h1>
        <div class="flex flex-wrap">
            <button class="btn btn-primary btn-pulse" onclick="N.createNote()"><i class="ti ti-plus"></i> New Note</button>
        </div>
    </div>

    <div class="notes-layout">
        <!-- LEFT PANEL -->
        <div class="notes-left">
            <div class="ns-search">
                <i class="ti ti-search"></i>
                <input type="text" id="nsSearchInput" placeholder="Search notes..." oninput="N.onSearch(this.value)">
            </div>

            <div>
                <div class="ns-cat-header">
                    <label>Categories</label>
                    <button onclick="N.toggleCatAdd()" title="Add category"><i class="ti ti-plus"></i></button>
                </div>
                <div class="ns-cat-list" id="nsCatList"></div>
                <div class="ns-cat-add-form" id="nsCatAddForm">
                    <input type="color" id="nsCatColor" value="#c8ff00">
                    <input type="text" id="nsCatName" placeholder="Name...">
                    <button onclick="N.addCategory()">+</button>
                    <button onclick="document.getElementById('nsCatAddForm').classList.remove('open')" style="background:none;border:none;color:var(--text3);cursor:pointer">✕</button>
                </div>
            </div>

            <div class="ns-stats" id="nsStats"></div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="notes-right">
            <div class="ns-toolbar">
                <div class="ns-toolbar-left">
                    <button class="ns-pill" onclick="N.toggleShowPinned(this)">📌 Pinned</button>
                    <select class="ns-sort-select" onchange="N.setSort(this.value)">
                        <option value="newest">Newest</option>
                        <option value="oldest">Oldest</option>
                        <option value="az">A–Z</option>
                        <option value="pinned">Pinned first</option>
                    </select>
                </div>
                <div class="ns-view-toggle">
                    <button data-view="grid" class="active" onclick="N.setView('grid')" title="Grid"><i class="ti ti-layout-grid"></i></button>
                    <button data-view="list" onclick="N.setView('list')" title="List"><i class="ti ti-list"></i></button>
                </div>
            </div>

            <div id="nsContent">
                <div class="ns-empty">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    <h3>Loading...</h3>
                </div>
            </div>
        </div>
    </div>
</div>
