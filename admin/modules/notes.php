<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();
$pdo = db();
?>
<?php require_once __DIR__ . '/../inc/header.php'; ?>

<link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/notes.css?v=<?= filemtime(__DIR__ . '/../assets/css/notes.css') ?>">

<div class="page-title" style="font-size:1.6rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:#fff;margin-bottom:24px">NOTES</div>

<div class="notes-layout">

  <div class="notes-sidebar">
    <div class="notes-sidebar-inner">
    <div class="notes-search-wrap">
      <input type="text" id="ns-search" placeholder="Search notes..." class="notes-search-input">
    </div>
    <div class="notes-cat-header">
      <span>CATEGORIES</span>
      <button id="ns-add-cat-btn" title="Add category">+</button>
    </div>
    <div id="ns-cat-form" style="display:none;" class="add-cat-form">
      <input type="text" id="ns-cat-name" placeholder="Category name">
      <input type="color" id="ns-cat-color" value="#6b7280">
      <button id="ns-cat-save">+</button>
      <button id="ns-cat-cancel">×</button>
    </div>
    <ul id="ns-cat-list" class="notes-cat-list">
      <li class="notes-cat-item active" data-id="0">
        <span class="cat-dot" style="background:#c8ff00"></span>
        <span class="cat-name">All Notes</span>
        <span class="cat-count" id="ns-total-count">0</span>
      </li>
    </ul>
    <div class="notes-stats">
      <div>Total <span id="ns-stat-total">0</span></div>
      <div>Pinned <span id="ns-stat-pinned">0</span></div>
    </div>
  </div>
</div>
<div id="ns-sidebar-backdrop" class="notes-sidebar-backdrop"></div>
  <div class="notes-main">
    <div id="ns-toolbar" class="notes-toolbar" style="display:flex">
      <div class="notes-toolbar-left">
        <button id="ns-sidebar-toggle" class="notes-pill-btn" title="Categories" style="display:none">☰</button>
        <button id="ns-pin-filter" class="notes-pill-btn">★ Pinned</button>
        <select id="ns-sort" class="notes-select">
          <option value="newest">Newest</option>
          <option value="oldest">Oldest</option>
          <option value="az">A → Z</option>
          <option value="pinned">Pinned First</option>
        </select>
      </div>
      <div class="notes-toolbar-right">
        <button class="btn btn-primary btn-pulse" id="ns-new-btn" style="padding:7px 16px;font-size:0.82rem"><i class="ti ti-plus"></i> New Note</button>
      </div>
    </div>

    <div id="ns-grid" class="notes-grid" style="display:block"></div>
    <div id="ns-list" class="notes-list-view" style="display:none;"></div>

    <div id="ns-editor" style="display:none;" class="notes-editor">
      <div class="editor-topbar">
        <button id="ns-back">← All Notes</button>
        <div class="editor-actions">
          <button id="ns-pin-btn">📌 Pin</button>
          <button id="ns-del-btn">🗑 Delete</button>
        </div>
      </div>
      <div class="editor-meta">
        <select id="ns-cat-select" class="notes-select">
          <option value="">No Category</option>
        </select>
        <div class="color-swatches" id="ns-swatches">
          <span class="swatch active" data-color="" style="background:#444" title="Default"></span>
          <span class="swatch" data-color="#ff4757" style="background:#ff4757"></span>
          <span class="swatch" data-color="#ff8c42" style="background:#ff8c42"></span>
          <span class="swatch" data-color="#f59e0b" style="background:#f59e0b"></span>
          <span class="swatch" data-color="#2ecc71" style="background:#2ecc71"></span>
          <span class="swatch" data-color="#4f8ef7" style="background:#4f8ef7"></span>
          <span class="swatch" data-color="#9b6dff" style="background:#9b6dff"></span>
          <span class="swatch" data-color="#c8ff00" style="background:#c8ff00"></span>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;width:100%">
        <input type="text" id="ns-title" placeholder="Note title..." class="editor-title-input" style="flex:1;min-width:0;margin-bottom:0">
        <button id="ns-ai-title-btn" class="ns-ai-btn" disabled style="flex-shrink:0;white-space:nowrap"><i class="ti ti-sparkles"></i> Improve</button>
      </div>
      <div style="display:flex;align-items:center;justify-content:flex-end;margin-bottom:4px;width:100%">
        <button id="ns-ai-content-btn" class="ns-ai-btn" disabled style="white-space:nowrap"><i class="ti ti-sparkles"></i> Improve Content</button>
      </div>
      <textarea id="ns-content" placeholder="Write your note here..." class="editor-content-area"></textarea>
      <input type="hidden" id="ns-id" value="">
      <div class="editor-footer">
        <span id="ns-status">—</span>
        <button id="ns-save-btn">Save ✓</button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const API = 'notes_api.php';
  let cats = [];
  let curCat = 0;
  let curId = null;
  let curSort = 'newest';
  let pinOnly = false;
  let saveTimer = null;

  function esc(s) { return String(s||'').replace(/[&<>"]/g,function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]||m;}); }

  function dateStr(s) { if(!s)return''; var d=new Date(s.replace(' ','T')); return d.toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'}); }

  function toast(msg,type) {
    var t=document.createElement('div'); t.className='notes-toast notes-toast-'+type; t.textContent=msg;
    document.body.appendChild(t);
    setTimeout(function(){t.classList.add('show');},10);
    setTimeout(function(){t.classList.remove('show');setTimeout(function(){t.remove();},300);},3000);
  }

  function fetchApi(data) {
    var fd=new FormData();
    Object.keys(data).forEach(function(k){fd.append(k,data[k]??'');});
    return fetch(API,{method:'POST',body:fd}).then(function(r){return r.json();});
  }

  function loadCats() {
    fetchApi({action:'get_categories'}).then(function(data){
      if(!Array.isArray(data)) return;
      cats=data;
      var el=document.getElementById('ns-cat-list');
      el.querySelectorAll('li:not(:first-child)').forEach(function(e){e.remove();});
      var total=0;
      cats.forEach(function(c){
        total+=parseInt(c.note_count)||0;
        var li=document.createElement('li');
        li.className='notes-cat-item'+(curCat==c.id?' active':'');
        li.dataset.id=c.id;
        li.innerHTML='<span class="cat-dot" style="background:'+c.color+'"></span><span class="cat-name">'+esc(c.name)+'</span><span class="cat-count">'+(c.note_count||0)+'</span><button class="cat-del-btn" data-id="'+c.id+'" title="Delete">×</button>';
        el.appendChild(li);
      });
      document.getElementById('ns-total-count').textContent=total;
      document.getElementById('ns-stat-total').textContent=total;
      var sel=document.getElementById('ns-cat-select');
      sel.innerHTML='<option value="">No Category</option>';
      cats.forEach(function(c){
        var o=document.createElement('option'); o.value=c.id; o.textContent=c.name; sel.appendChild(o);
      });
    });
  }

  function loadNotes() {
    var search=document.getElementById('ns-search').value;
    fetchApi({action:'get_notes',category_id:curCat,search:search,sort:curSort}).then(function(data){
      if(!Array.isArray(data)) return;
      var list=pinOnly?data.filter(function(n){return n.is_pinned==1;}):data;
      var pinned=list.filter(function(n){return n.is_pinned==1;});
      var unpinned=list.filter(function(n){return n.is_pinned!=1;});
      document.getElementById('ns-stat-pinned').textContent=pinned.length;
      renderGrid(pinned,unpinned);
    });
  }

  function renderGrid(pinned,unpinned) {
    var grid=document.getElementById('ns-grid');
    grid.style.display='';
    document.getElementById('ns-list').style.display='none';
    var html='';
    if(pinned.length){
      html+='<div class="pinned-label">📌 PINNED</div><div class="notes-cards-grid pinned-grid">'+pinned.map(cardHTML).join('')+'</div>';
    }
    if(unpinned.length){
      html+='<div class="notes-cards-grid">'+unpinned.map(cardHTML).join('')+'</div>';
    }
    if(!pinned.length&&!unpinned.length){
      html='<div class="notes-empty"><div class="empty-icon">📝</div><div class="empty-title">No notes yet</div><div class="empty-sub">Click "+ New Note" to get started.</div></div>';
    }
    grid.innerHTML=html;
  }


  function cardHTML(n) {
    var bc=n.note_color||n.category_color||'#333';
    var pill=n.category_name?'<span class="note-cat-pill" style="background:'+n.category_color+'22;color:'+n.category_color+'">'+esc(n.category_name)+'</span>':'';
    var prev=(n.content||'').substring(0,120);
    return '<div class="note-card'+(n.is_pinned==1?' is-pinned':'')+'" data-id="'+n.id+'" style="border-left-color:'+bc+'"><div class="note-card-title">'+esc(n.title)+'</div><div class="note-card-preview">'+esc(prev)+'</div><div class="note-card-footer">'+pill+'<span class="note-date">'+dateStr(n.updated_at)+'</span></div></div>';
  }

  function openEditor(id) {
    document.getElementById('ns-grid').style.display='none';
    document.getElementById('ns-list').style.display='none';
    document.getElementById('ns-toolbar').style.display='none';
    document.getElementById('ns-editor').style.display='flex';
    curId=id||null;
    document.getElementById('ns-id').value=id||'';
    document.getElementById('ns-status').textContent='—';
    if(id){
      fetchApi({action:'get_note',id:id}).then(function(n){
        document.getElementById('ns-title').value=n.title||'';
        document.getElementById('ns-content').value=n.content||'';
        setCatSel(n.category_id);
        document.getElementById('ns-pin-btn').textContent=n.is_pinned==1?'📌 Unpin':'📍 Pin';
        setSwatch(n.note_color||'');
        updateAiButtons();
      });
    } else {
      document.getElementById('ns-title').value='';
      document.getElementById('ns-content').value='';
      document.getElementById('ns-pin-btn').textContent='📍 Pin';
      setSwatch('');
      updateAiButtons();
    }
  }

  function closeEditor() {
    document.getElementById('ns-editor').style.display='none';
    document.getElementById('ns-toolbar').style.display='';
    document.getElementById('ns-grid').style.display='';
    curId=null;
    loadNotes();
  }

  function setCatSel(id) {
    var sel=document.getElementById('ns-cat-select');
    for(var i=0;i<sel.options.length;i++){sel.options[i].selected=(sel.options[i].value==String(id));}
  }

  function setSwatch(c) {
    document.querySelectorAll('#ns-swatches .swatch').forEach(function(s){s.classList.toggle('active',s.dataset.color===c);});
  }

  function saveNote() {
    var id=document.getElementById('ns-id').value;
    var title=document.getElementById('ns-title').value.trim()||'Untitled';
    var content=document.getElementById('ns-content').value;
    var catId=document.getElementById('ns-cat-select').value;
    var swatch=document.querySelector('#ns-swatches .swatch.active');
    var color=swatch?swatch.dataset.color:'';
    document.getElementById('ns-status').textContent='Saving...';
    (id
      ? fetchApi({action:'update_note',id:id,title:title,content:content,category_id:catId,note_color:color})
      : fetchApi({action:'create_note',title:title,content:content,category_id:catId,note_color:color}).then(function(r){if(r.success){document.getElementById('ns-id').value=r.id;curId=r.id;}})
    ).then(function(r){
      if(r&&r.success){
        document.getElementById('ns-status').textContent='Saved ✓ '+new Date().toLocaleTimeString();
        loadCats();
      } else {
        document.getElementById('ns-status').textContent='Error saving';
      }
    });
  }

  function scheduleSave() {
    clearTimeout(saveTimer);
    saveTimer=setTimeout(saveNote,2000);
  }

  function updateAiButtons() {
    var t=document.getElementById('ns-title').value.trim();
    var c=document.getElementById('ns-content').value.trim();
    document.getElementById('ns-ai-title-btn').disabled=!t;
    document.getElementById('ns-ai-content-btn').disabled=!c;
  }

  // ── EVENTS ──
  document.addEventListener('DOMContentLoaded', function() {
    loadCats();
    loadNotes();

    document.getElementById('ns-cat-list').addEventListener('click',function(e){
      var del=e.target.closest('.cat-del-btn');
      if(del){
        if(!confirm('Delete this category? Notes will be uncategorized.')) return;
        fetchApi({action:'delete_category',id:del.dataset.id}).then(function(){loadCats();loadNotes();});
        return;
      }
      var item=e.target.closest('.notes-cat-item');
      if(item){
        document.querySelectorAll('.notes-cat-item').forEach(function(el){el.classList.remove('active');});
        item.classList.add('active');
        curCat=item.dataset.id;
        loadNotes();
      }
    });

    document.getElementById('ns-add-cat-btn').addEventListener('click',function(){
      var f=document.getElementById('ns-cat-form');
      f.style.display=f.style.display==='none'?'flex':'none';
      if(f.style.display==='flex') document.getElementById('ns-cat-name').focus();
    });
    document.getElementById('ns-cat-save').addEventListener('click',function(){
      var name=document.getElementById('ns-cat-name').value.trim();
      var color=document.getElementById('ns-cat-color').value;
      if(!name) return;
      fetchApi({action:'create_category',name:name,color:color}).then(function(){
        document.getElementById('ns-cat-name').value='';
        document.getElementById('ns-cat-form').style.display='none';
        loadCats();
      });
    });
    document.getElementById('ns-cat-cancel').addEventListener('click',function(){
      document.getElementById('ns-cat-form').style.display='none';
      document.getElementById('ns-cat-name').value='';
    });

    var searchTimer;
    document.getElementById('ns-search').addEventListener('input',function(){
      clearTimeout(searchTimer);
      searchTimer=setTimeout(loadNotes,300);
    });

    document.getElementById('ns-sort').addEventListener('change',function(){
      curSort=this.value; loadNotes();
    });

    document.getElementById('ns-pin-filter').addEventListener('click',function(){
      pinOnly=!pinOnly; this.classList.toggle('active',pinOnly); loadNotes();
    });

    document.getElementById('ns-new-btn').addEventListener('click',function(){openEditor(null);});

    // Mobile sidebar toggle
    function toggleSidebar() {
      document.getElementById('ns-sidebar-toggle').classList.toggle('open');
      document.querySelector('.notes-sidebar').classList.toggle('open');
      document.getElementById('ns-sidebar-backdrop').classList.toggle('show');
    }
    document.getElementById('ns-sidebar-toggle').addEventListener('click',toggleSidebar);
    document.getElementById('ns-sidebar-backdrop').addEventListener('click',toggleSidebar);
    document.querySelector('.notes-sidebar .notes-cat-item')?.addEventListener('click',function(){
      if(window.innerWidth<=768) toggleSidebar();
    });

    document.getElementById('ns-grid').addEventListener('click',function(e){
      var card=e.target.closest('.note-card');
      if(card) openEditor(card.dataset.id);
    });

    document.getElementById('ns-list').addEventListener('click',function(e){
      var edit=e.target.closest('.list-edit-btn');
      if(edit){openEditor(edit.dataset.id); return;}
      var del=e.target.closest('.list-del-btn');
      if(del){
        if(!confirm('Delete this note?')) return;
        fetchApi({action:'delete_note',id:del.dataset.id}).then(function(){toast('Note deleted');loadNotes();loadCats();});
        return;
      }
      var row=e.target.closest('tr[data-id]');
      if(row&&!edit&&!del) openEditor(row.dataset.id);
    });

    document.getElementById('ns-back').addEventListener('click',closeEditor);
    document.getElementById('ns-save-btn').addEventListener('click',saveNote);
    document.getElementById('ns-title').addEventListener('input',scheduleSave);
    document.getElementById('ns-content').addEventListener('input',scheduleSave);
    document.getElementById('ns-cat-select').addEventListener('change',scheduleSave);

    // AI button enable/disable on input
    document.getElementById('ns-title').addEventListener('input',updateAiButtons);
    document.getElementById('ns-content').addEventListener('input',updateAiButtons);

    document.getElementById('ns-ai-title-btn').addEventListener('click',function(){
      var input=document.getElementById('ns-title');
      var text=input.value.trim();
      if(!text) return;
      var btn=this;
      btn.disabled=true;
      btn.innerHTML='<span class="ai-spinner"></span>';
      fetch('../ai.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({task:'improve',context:text})})
      .then(function(r){return r.json();})
      .then(function(j){
        btn.innerHTML='<i class="ti ti-sparkles"></i> Improve';
        btn.disabled=!1;
        if(j.success){input.value=j.data;scheduleSave();updateAiButtons();}
      }).catch(function(){btn.innerHTML='<i class="ti ti-sparkles"></i> Improve';btn.disabled=!1;});
    });

    document.getElementById('ns-ai-content-btn').addEventListener('click',function(){
      var input=document.getElementById('ns-content');
      var text=input.value.trim();
      if(!text) return;
      var btn=this;
      btn.disabled=true;
      btn.innerHTML='<span class="ai-spinner"></span>';
      fetch('../ai.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({task:'improve',context:text})})
      .then(function(r){return r.json();})
      .then(function(j){
        btn.innerHTML='<i class="ti ti-sparkles"></i> Improve Content';
        btn.disabled=!1;
        if(j.success){input.value=j.data;scheduleSave();updateAiButtons();}
      }).catch(function(){btn.innerHTML='<i class="ti ti-sparkles"></i> Improve Content';btn.disabled=!1;});
    });

    document.getElementById('ns-pin-btn').addEventListener('click',function(){
      if(!curId) return;
      fetchApi({action:'toggle_pin',id:curId}).then(function(r){
        document.getElementById('ns-pin-btn').textContent=r.is_pinned==1?'📌 Unpin':'📍 Pin';
        toast(r.is_pinned?'Note pinned':'Note unpinned');
      });
    });

    document.getElementById('ns-del-btn').addEventListener('click',function(){
      if(!curId) return;
      if(!confirm('Delete this note permanently?')) return;
      fetchApi({action:'delete_note',id:curId}).then(function(){
        toast('Note deleted');
        closeEditor();
        loadCats();
      });
    });

    document.getElementById('ns-swatches').addEventListener('click',function(e){
      var sw=e.target.closest('.swatch');
      if(!sw) return;
      document.querySelectorAll('#ns-swatches .swatch').forEach(function(s){s.classList.remove('active');});
      sw.classList.add('active');
      scheduleSave();
    });
  });
})();
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
