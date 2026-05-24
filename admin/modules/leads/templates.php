<?php
$templates = db_rows("SELECT * FROM outreach_templates ORDER BY is_default DESC, use_count DESC");
$niches = ['','Restaurant','Clinic/Doctor','Real Estate','Salon/Beauty','Gym/Fitness','Lawyer','School','Travel Agency','NGO','E-Commerce','Fashion Brand','Food Brand','Hotel'];
$services = ['WordPress Website Design','WooCommerce E-Commerce Store','Creative Branding & Logo','SEO & Google Rankings','Facebook/Google Ads','Full Digital Package'];
?>
<div style="display:flex;justify-content:flex-end;margin-bottom:1.5rem">
    <button class="btn btn-primary" onclick="showNewTemplate()"><i class="ti ti-plus"></i> New Template</button>
</div>

<style>
.tpl-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem }
.tpl-card { background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:1.25rem;transition:all 0.2s }
.tpl-card:hover { border-color:var(--accent) }
.tpl-card .tpl-name { font-family:'Orbitron',sans-serif;font-size:0.8rem;font-weight:700;color:var(--text);margin-bottom:4px }
.tpl-card .tpl-tags { display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px }
.tpl-card .tpl-tags span { font-size:0.65rem;padding:2px 8px;border-radius:999px;background:var(--bg3);color:var(--text3) }
.tpl-card .tpl-subject { font-size:0.78rem;color:var(--text2);margin-bottom:4px;font-style:italic }
.tpl-card .tpl-preview { font-size:0.75rem;color:var(--text3);max-height:60px;overflow:hidden;position:relative }
.tpl-card .tpl-preview::after { content:'';position:absolute;bottom:0;left:0;right:0;height:20px;background:linear-gradient(transparent,var(--bg2)) }
.tpl-card .tpl-actions { display:flex;gap:6px;margin-top:10px;padding-top:10px;border-top:1px solid var(--border) }
</style>

<div class="tpl-grid">
    <?php if (count($templates)): foreach ($templates as $t): ?>
        <div class="tpl-card">
            <div class="tpl-name"><?= h($t['name']) ?></div>
            <div class="tpl-tags">
                <span><?= h($t['type']) ?></span>
                <?php if ($t['niche']): ?><span><?= h($t['niche']) ?></span><?php endif; ?>
                <span><?= h($t['tone']) ?></span>
                <?php if ($t['is_default']): ?><span style="background:rgba(204,255,0,0.1);color:var(--accent)">Default</span><?php endif; ?>
            </div>
            <div class="tpl-subject">"<?= h($t['subject']) ?>"</div>
            <div class="tpl-preview"><?= h(strip_tags($t['body'])) ?></div>
            <div class="tpl-actions">
                <button class="btn btn-sm btn-ai" onclick="useTemplate(<?= $t['id'] ?>)"><i class="ti ti-play"></i> Use</button>
                <button class="btn btn-sm btn-secondary" onclick="editTemplate(<?= $t['id'] ?>)"><i class="ti ti-pencil"></i></button>
                <button class="btn btn-sm btn-secondary" onclick="duplicateTemplate(<?= $t['id'] ?>)"><i class="ti ti-copy"></i></button>
                <button class="btn btn-sm btn-danger" onclick="deleteTemplate(<?= $t['id'] ?>)"><i class="ti ti-trash"></i></button>
                <span style="font-size:0.65rem;color:var(--text3);margin-left:auto">Used <?= (int)$t['use_count'] ?>x</span>
            </div>
        </div>
    <?php endforeach; else: ?>
        <div class="card" style="grid-column:1/-1">
            <div class="empty-state">
                <i class="ti ti-template"></i>
                <p>No templates yet. Create your first template using AI or write one manually.</p>
                <button class="btn btn-primary mt-1" onclick="showNewTemplate()">Create Template</button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- New/Edit Template Modal -->
<div class="modal-overlay" id="tplModal">
    <div class="modal" style="max-width:600px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h3 class="modal-title" id="tplModalTitle">New Template</h3>
            <button onclick="closeModal('tplModal')" style="background:none;border:none;color:var(--text3);font-size:1.3rem;cursor:pointer"><i class="ti ti-x"></i></button>
        </div>
        <form id="tplForm" onsubmit="return saveTemplate(event)">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" id="tplId" value="0">
            <div class="form-row">
                <div class="form-group">
                    <label>Template Name</label>
                    <input class="form-control" id="tplName" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select class="form-control" id="tplType">
                        <option value="email">Email</option>
                        <option value="whatsapp">WhatsApp</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Niche</label>
                    <select class="form-control" id="tplNiche">
                        <option value="">General</option>
                        <?php foreach ($niches as $n): if (!$n) continue; ?>
                            <option value="<?= h($n) ?>"><?= h($n) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Service</label>
                    <select class="form-control" id="tplService">
                        <option value="">General</option>
                        <?php foreach ($services as $s): ?>
                            <option value="<?= h($s) ?>"><?= h($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Tone</label>
                <select class="form-control" id="tplTone">
                    <option value="professional">Professional</option>
                    <option value="friendly">Friendly</option>
                    <option value="direct">Direct</option>
                    <option value="bold">Bold</option>
                </select>
            </div>
            <div class="form-group">
                <label>Subject</label>
                <input class="form-control" id="tplSubject">
                <button type="button" class="btn btn-ai btn-sm" style="margin-top:4px" onclick="aiGenerateSubject()"><i class="ti ti-sparkles"></i> Generate with AI</button>
            </div>
            <div class="form-group">
                <label>Body</label>
                <textarea class="form-control" id="tplBody" rows="8"></textarea>
                <button type="button" class="btn btn-ai btn-sm" style="margin-top:4px" onclick="aiGenerateBody()"><i class="ti ti-sparkles"></i> Generate Body with AI</button>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save Template</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('tplModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
let _csrf = '<?= csrf_token() ?>';
let editingTplId = 0;

function showNewTemplate() {
    editingTplId = 0;
    document.getElementById('tplModalTitle').textContent = 'New Template';
    document.getElementById('tplForm').reset();
    document.getElementById('tplId').value = '0';
    openModal('tplModal');
}

function editTemplate(id) {
    editingTplId = id;
    document.getElementById('tplModalTitle').textContent = 'Edit Template';
    // Load data from card
    let card = document.querySelector(`.tpl-card:nth-child(${id})`);
    openModal('tplModal');
    // For now, reload to populate
    window.location.href = 'leads.php?view=templates&edit=' + id;
}

function saveTemplate(e) {
    e.preventDefault();
    let fd = new URLSearchParams();
    fd.append('_csrf', _csrf);
    fd.append('action', 'save_template');
    fd.append('template_id', editingTplId);
    fd.append('name', document.getElementById('tplName').value);
    fd.append('type', document.getElementById('tplType').value);
    fd.append('niche', document.getElementById('tplNiche').value);
    fd.append('service', document.getElementById('tplService').value);
    fd.append('tone', document.getElementById('tplTone').value);
    fd.append('subject', document.getElementById('tplSubject').value);
    fd.append('body', document.getElementById('tplBody').value);

    fetch('../api/leads_save.php', {method:'POST', body: fd})
    .then(r=>r.json()).then(j=>{
        if(j.success){ showToast('Template saved'); closeModal('tplModal'); location.reload() }
        else showToast(j.error, 'error');
    });
    return false;
}

function deleteTemplate(id) {
    if (!confirm('Delete this template?')) return;
    let fd = new URLSearchParams();
    fd.append('_csrf', _csrf);
    fd.append('action', 'delete_template');
    fd.append('template_id', id);
    fetch('../api/leads_save.php', {method:'POST', body: fd})
    .then(r=>r.json()).then(j=>{
        if(j.success){ showToast('Deleted'); location.reload() }
        else showToast(j.error, 'error');
    });
}

function duplicateTemplate(id) {
    fetch('../api/leads_save.php?action=get_template&id='+id).catch(()=>{});
    showToast('Duplicate: feature coming soon');
}

function useTemplate(id) {
    showToast('Template selected — use from email composer');
}

function aiGenerateSubject() {
    let niche = document.getElementById('tplNiche').value || 'business';
    let service = document.getElementById('tplService').value || 'WordPress Website Design';
    let btn = event.target;
    btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span>';

    fetch('../api/leads_ai.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
            task: 'generate_cold_email',
            context: {
                business_name: '[Business Name]',
                niche: niche,
                service: service,
                tone: document.getElementById('tplTone').value,
                language: 'English',
                email_type: 'cold'
            }
        })
    }).then(r=>r.json()).then(j=>{
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate with AI';
        if(j.success && j.data && j.data.subject) {
            document.getElementById('tplSubject').value = j.data.subject;
        }
    }).catch(()=>{ btn.disabled=false; btn.innerHTML='<i class="ti ti-sparkles"></i> Generate with AI' });
}

function aiGenerateBody() {
    let niche = document.getElementById('tplNiche').value || 'business';
    let service = document.getElementById('tplService').value || 'WordPress Website Design';
    let subject = document.getElementById('tplSubject').value || 'Business opportunity';
    let btn = event.target;
    btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span>';

    fetch('../api/leads_ai.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
            task: 'generate_cold_email',
            context: {
                business_name: '[Business Name]',
                niche: niche,
                service: service,
                tone: document.getElementById('tplTone').value,
                language: 'English',
                email_type: 'cold'
            }
        })
    }).then(r=>r.json()).then(j=>{
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate Body with AI';
        if(j.success && j.data && j.data.body) {
            document.getElementById('tplBody').value = j.data.body;
        }
    }).catch(()=>{ btn.disabled=false; btn.innerHTML='<i class="ti ti-sparkles"></i> Generate Body with AI' });
}

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
</script>
