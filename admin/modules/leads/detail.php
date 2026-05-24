<?php
$id = (int)($_GET['id'] ?? 0);
$lead = db_rows("SELECT * FROM leads WHERE id = ?", [$id]);
if (!$lead) {
    echo '<div class="card"><div class="empty-state"><i class="ti ti-user-off"></i><p>Lead not found.</p><a href="leads.php?view=my_leads" class="btn btn-primary mt-1">Back to Leads</a></div></div>';
    return;
}
$lead = $lead[0];

$activity = db_rows("SELECT * FROM lead_activity WHERE lead_id = ? ORDER BY created_at DESC LIMIT 50", [$id]);
$emails = db_rows("SELECT * FROM lead_emails WHERE lead_id = ? ORDER BY created_at DESC", [$id]);
$waMessages = db_rows("SELECT * FROM lead_whatsapp WHERE lead_id = ? ORDER BY created_at DESC", [$id]);

$statuses = ['new','contacted','replied','interested','meeting_booked','closed_won','closed_lost'];
$services = ['WordPress Website Design','WooCommerce E-Commerce Store','Creative Branding & Logo','SEO & Google Rankings','Facebook/Google Ads','Full Digital Package'];
?>
<div class="page-header">
    <h1 class="page-title"><?= h($lead['business_name']) ?></h1>
    <div class="flex flex-wrap">
        <a href="leads.php?view=my_leads" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Back</a>
        <button onclick="deleteLead(<?= $id ?>)" class="btn btn-danger"><i class="ti ti-trash"></i> Delete</button>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 380px;gap:1.5rem">
<div>

<!-- Business Info Card -->
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem">
        <h3 style="font-family:'Orbitron',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent)">Business Information</h3>
        <button class="btn btn-sm btn-secondary" onclick="toggleEditInfo()"><i class="ti ti-pencil"></i> Edit</button>
    </div>
    <div id="infoDisplay">
        <div class="form-row" style="margin-bottom:0.75rem">
            <div><strong style="color:var(--text);font-size:0.85rem"><?= h($lead['business_name']) ?></strong><br><span style="color:var(--accent);font-size:0.75rem"><?= h($lead['niche']) ?></span></div>
            <div style="text-align:right">
                <?php if ($lead['website']): ?><a href="<?= h($lead['website']) ?>" target="_blank" style="color:var(--accent);font-size:0.8rem"><?= h($lead['website']) ?></a><?php endif; ?>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;font-size:0.85rem;color:var(--text2)">
            <?php if ($lead['owner_name']): ?><div><span style="color:var(--text3)">Owner:</span> <?= h($lead['owner_name']) ?></div><?php endif; ?>
            <?php if ($lead['email']): ?><div><span style="color:var(--text3)">Email:</span> <a href="mailto:<?= h($lead['email']) ?>" style="color:var(--accent)"><?= h($lead['email']) ?></a></div><?php endif; ?>
            <?php if ($lead['phone']): ?><div><span style="color:var(--text3)">Phone:</span> <?= h($lead['phone']) ?></div><?php endif; ?>
            <?php if ($lead['whatsapp']): ?><div><span style="color:var(--text3)">WhatsApp:</span> <?= h($lead['whatsapp']) ?></div><?php endif; ?>
            <?php if ($lead['city'] || $lead['country']): ?><div><span style="color:var(--text3)">Location:</span> <?= h($lead['city']) ?><?= $lead['city'] && $lead['country'] ? ', ' : '' ?><?= h($lead['country']) ?></div><?php endif; ?>
            <?php if ($lead['address']): ?><div style="grid-column:1/-1"><span style="color:var(--text3)">Address:</span> <?= h($lead['address']) ?></div><?php endif; ?>
            <?php if ($lead['facebook']): ?><div><span style="color:var(--text3)">Facebook:</span> <a href="<?= h($lead['facebook']) ?>" target="_blank" style="color:var(--accent)">View</a></div><?php endif; ?>
            <?php if ($lead['instagram']): ?><div><span style="color:var(--text3)">Instagram:</span> <a href="<?= h($lead['instagram']) ?>" target="_blank" style="color:var(--accent)">View</a></div><?php endif; ?>
            <?php if ($lead['google_maps_url']): ?><div><span style="color:var(--text3)">Maps:</span> <a href="<?= h($lead['google_maps_url']) ?>" target="_blank" style="color:var(--accent)">Open</a></div><?php endif; ?>
        </div>
    </div>
    <div id="infoEdit" style="display:none">
        <form id="editInfoForm" onsubmit="return saveInfo(event)">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-row">
                <div class="form-group"><label>Business Name</label><input class="form-control" name="business_name" value="<?= h($lead['business_name']) ?>"></div>
                <div class="form-group"><label>Niche</label><input class="form-control" name="niche" value="<?= h($lead['niche']) ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Owner Name</label><input class="form-control" name="owner_name" value="<?= h($lead['owner_name']) ?>"></div>
                <div class="form-group"><label>Email</label><input class="form-control" name="email" value="<?= h($lead['email']) ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Phone</label><input class="form-control" name="phone" value="<?= h($lead['phone']) ?>"></div>
                <div class="form-group"><label>WhatsApp</label><input class="form-control" name="whatsapp" value="<?= h($lead['whatsapp']) ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Website</label><input class="form-control" name="website" value="<?= h($lead['website']) ?>"></div>
                <div class="form-group"><label>City</label><input class="form-control" name="city" value="<?= h($lead['city']) ?>"></div>
            </div>
            <div class="form-group"><label>Address</label><textarea class="form-control" name="address" rows="2"><?= h($lead['address']) ?></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Facebook</label><input class="form-control" name="facebook" value="<?= h($lead['facebook']) ?>"></div>
                <div class="form-group"><label>Instagram</label><input class="form-control" name="instagram" value="<?= h($lead['instagram']) ?>"></div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
                <button type="button" class="btn btn-secondary" onclick="toggleEditInfo()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Website Audit Card -->
<?php if ($lead['website']): ?>
<div class="card" id="auditCard">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h3 style="font-family:'Orbitron',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent)">🌐 Website Audit</h3>
        <button class="btn btn-ai btn-sm" onclick="runAudit()" id="auditBtn"><i class="ti ti-refresh"></i> Run Audit</button>
    </div>
    <div id="auditResults">
        <p style="color:var(--text3);font-size:0.85rem">Click "Run Audit" to analyze <?= h($lead['website']) ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Activity Timeline -->
<div class="card">
    <h3 style="font-family:'Orbitron',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:1rem">📋 Activity Timeline</h3>
    <div style="max-height:400px;overflow-y:auto">
        <?php if (count($activity)): ?>
            <?php foreach ($activity as $act): ?>
                <div style="padding:0.6rem 0;border-bottom:1px solid var(--border);font-size:0.82rem;display:flex;gap:10px;align-items:flex-start">
                    <span style="font-size:1rem;flex-shrink:0">
                        <?php $icons = ['note'=>'ti ti-note','email_sent'=>'ti ti-mail','whatsapp_sent'=>'ti ti-brand-whatsapp','status_change'=>'ti ti-arrow-right','reminder'=>'ti ti-bell','reply_received'=>'ti ti-message-reply']; ?>
                        <i class="<?= $icons[$act['type']] ?? 'ti ti-circle' ?>" style="color:var(--text3)"></i>
                    </span>
                    <div style="flex:1">
                        <p style="color:var(--text2)"><?= h($act['content']) ?></p>
                        <span style="font-size:0.7rem;color:var(--text3)"><?= time_ago($act['created_at']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:var(--text3);font-size:0.85rem">No activity yet.</p>
        <?php endif; ?>
    </div>
    <form onsubmit="return addNote(event)" style="margin-top:1rem;display:flex;gap:8px">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="text" id="noteInput" placeholder="Add a note..." class="form-control" style="flex:1;min-height:auto;padding:0.6rem 0.75rem">
        <button type="submit" class="btn btn-primary btn-sm">Add</button>
    </form>
</div>

</div>
<div>

<!-- Status & Score -->
<div class="card" style="margin-bottom:1.5rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <div>
            <div style="font-size:2.5rem;font-weight:900;font-family:'Orbitron',sans-serif;color:<?= $lead['lead_score'] > 60 ? 'var(--accent)' : ($lead['lead_score'] > 30 ? '#F59E0B' : '#ef4444') ?>"><?= (int)$lead['lead_score'] ?></div>
            <div style="font-size:0.7rem;color:var(--text3);text-transform:uppercase">LEAD SCORE</div>
        </div>
        <div>
            <select class="form-control" id="statusSelect" onchange="changeStatus(<?= $id ?>)" style="width:auto;padding:0.5rem 0.75rem;font-size:0.8rem">
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $lead['status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:0.75rem">
        <?php if ($lead['tags']): foreach (explode(',', $lead['tags']) as $tag): $tag = trim($tag); if (!$tag) continue; ?>
            <span style="background:rgba(204,255,0,0.1);color:var(--accent);padding:2px 10px;border-radius:999px;font-size:0.7rem;display:flex;align-items:center;gap:4px">
                <?= h($tag) ?>
                <span onclick="removeTag(<?= $id ?>,'<?= h($tag) ?>')" style="cursor:pointer;opacity:0.6">&times;</span>
            </span>
        <?php endforeach; endif; ?>
    </div>
    <div style="display:flex;gap:6px">
        <input type="text" id="tagInput" placeholder="Add tag..." style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0.4rem 0.7rem;color:var(--text);font-size:0.78rem;flex:1" onkeydown="if(event.key==='Enter'){event.preventDefault();addTag(<?= $id ?>)}">
        <button class="btn btn-sm btn-secondary" onclick="addTag(<?= $id ?>)">Add</button>
    </div>
    <div style="margin-top:0.75rem">
        <label style="font-size:0.7rem;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Suggested Service</label>
        <select class="form-control" id="serviceSuggestion" style="font-size:0.8rem;padding:0.4rem 0.7rem">
            <option value="">Select service to pitch...</option>
            <?php foreach ($services as $svc): ?>
                <option value="<?= h($svc) ?>" <?= $lead['tags'] && stripos($lead['tags'], $svc) !== false ? 'selected' : '' ?>><?= h($svc) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-bottom:1.5rem">
    <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3);margin-bottom:0.75rem">⚡ Quick Actions</h3>
    <div style="display:flex;flex-direction:column;gap:8px">
        <button class="btn-quickaction" onclick="openEmailComposer(<?= $id ?>,'<?= h(addslashes($lead['business_name'])) ?>','<?= h($lead['email']) ?>')"><i class="ti ti-mail"></i> Send Email</button>
        <button class="btn-quickaction" onclick="openWaComposer(<?= $id ?>,'<?= h(addslashes($lead['business_name'])) ?>','<?= h($lead['whatsapp'] ?: $lead['phone']) ?>')"><i class="ti ti-brand-whatsapp"></i> Send WhatsApp</button>
        <button class="btn-quickaction" onclick="showReminderModal(<?= $id ?>)"><i class="ti ti-bell"></i> Set Reminder</button>
        <button class="btn-quickaction" onclick="toggleBlacklist(<?= $id ?>, <?= $lead['is_blacklisted'] ?>)" style="color:var(--red)"><i class="ti ti-ban"></i> <?= $lead['is_blacklisted'] ? 'Remove Blacklist' : 'Blacklist Lead' ?></button>
    </div>
</div>

<!-- Email History -->
<div class="card" style="margin-bottom:1.5rem">
    <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3);margin-bottom:0.75rem">📧 Email History</h3>
    <?php if (count($emails)): ?>
        <?php foreach ($emails as $e): ?>
            <div style="padding:0.6rem 0;border-bottom:1px solid var(--border);font-size:0.82rem">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <strong style="color:var(--text);font-size:0.8rem"><?= h($e['subject']) ?></strong>
                    <span class="status-badge" style="font-size:0.65rem;background:<?= $e['status']==='sent' ? 'rgba(59,130,246,0.15)' : ($e['status']==='opened' ? 'rgba(204,255,0,0.15)' : ($e['status']==='replied' ? 'rgba(16,185,129,0.15)' : 'var(--bg3)')) ?>;color:<?= $e['status']==='sent' ? '#60A5FA' : ($e['status']==='opened' ? '#CCFF00' : ($e['status']==='replied' ? '#34D399' : 'var(--text3)')) ?>"><?= $e['status'] ?></span>
                </div>
                <div style="display:flex;gap:12px;margin-top:4px;font-size:0.72rem;color:var(--text3)">
                    <span><?= $e['type'] ?></span>
                    <span><?= $e['sent_at'] ? date('d M, H:i', strtotime($e['sent_at'])) : '' ?></span>
                </div>
                <button class="btn btn-sm" style="background:none;border:none;color:var(--accent);font-size:0.72rem;cursor:pointer;padding:2px 0" onclick="showEmailBody(<?= $id ?>, this)">View Email</button>
                <div class="emailBodyPreview" style="display:none;margin-top:8px;padding:8px;background:var(--bg3);border-radius:8px;font-size:0.78rem;color:var(--text2);max-height:200px;overflow-y:auto"><?= nl2br(h($e['body'])) ?></div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:var(--text3);font-size:0.82rem">No emails sent yet.</p>
    <?php endif; ?>
</div>

<!-- WhatsApp History -->
<div class="card">
    <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3);margin-bottom:0.75rem">💬 WhatsApp History</h3>
    <?php if (count($waMessages)): ?>
        <?php foreach ($waMessages as $w): ?>
            <div style="padding:0.6rem 0;border-bottom:1px solid var(--border);font-size:0.82rem">
                <div style="display:flex;justify-content:space-between">
                    <span class="status-badge" style="font-size:0.65rem;background:<?= $w['status']==='sent' ? 'rgba(59,130,246,0.15)' : ($w['status']==='replied' ? 'rgba(16,185,129,0.15)' : 'var(--bg3)') ?>;color:<?= $w['status']==='sent' ? '#60A5FA' : ($w['status']==='replied' ? '#34D399' : 'var(--text3)') ?>"><?= $w['status'] ?></span>
                    <span style="font-size:0.72rem;color:var(--text3)"><?= $w['created_at'] ? date('d M, H:i', strtotime($w['created_at'])) : '' ?></span>
                </div>
                <p style="margin-top:4px;font-size:0.8rem;color:var(--text2)"><?= h(mb_substr($w['message'], 0, 150)) ?><?= mb_strlen($w['message']) > 150 ? '...' : '' ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:var(--text3);font-size:0.82rem">No WhatsApp messages yet.</p>
    <?php endif; ?>
</div>

</div>
</div>

<!-- Email Composer Modal -->
<div class="modal-overlay" id="emailModal">
    <div class="modal" style="max-width:700px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;border-bottom:1px solid var(--border);padding-bottom:1rem">
            <h3 class="modal-title">✉ AI Email Composer — <span id="emailBusinessName"></span></h3>
            <button onclick="closeModal('emailModal')" style="background:none;border:none;color:var(--text3);font-size:1.3rem;cursor:pointer"><i class="ti ti-x"></i></button>
        </div>
        <div id="emailComposerBody">
            <div class="form-group">
                <label>Service</label>
                <select class="form-control" id="emailService">
                    <option value="WordPress Website Design">WordPress Website Design</option>
                    <option value="WooCommerce E-Commerce Store">WooCommerce E-Commerce Store</option>
                    <option value="Creative Branding & Logo">Creative Branding & Logo</option>
                    <option value="SEO & Google Rankings">SEO & Google Rankings</option>
                    <option value="Facebook/Google Ads">Facebook/Google Ads</option>
                    <option value="Full Digital Package">Full Digital Package</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Type</label>
                    <select class="form-control" id="emailType">
                        <option value="cold">Cold Outreach</option>
                        <option value="followup">Follow-up</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tone</label>
                    <select class="form-control" id="emailTone">
                        <option value="professional">Professional</option>
                        <option value="friendly">Friendly</option>
                        <option value="direct">Direct</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Language</label>
                    <select class="form-control" id="emailLanguage">
                        <option value="English">English</option>
                        <option value="Bangla">Bangla</option>
                        <option value="Banglish">Banglish</option>
                    </select>
                </div>
                <div class="form-group">
                    <button class="btn btn-ai" onclick="generateEmailDetail()" id="genEmailBtn" style="width:100%"><i class="ti ti-sparkles"></i> Generate</button>
                </div>
            </div>
            <div id="emailResult" style="display:none">
                <div class="form-group"><label>Subject</label><input class="form-control" id="emailSubject"></div>
                <div class="form-group"><label>Body</label><textarea class="form-control" id="emailBody" rows="8"></textarea></div>
                <div class="form-group"><label>To:</label><input class="form-control" id="emailTo" type="email"></div>
                <div class="form-actions">
                    <button class="btn btn-ai" onclick="generateEmailDetail()"><i class="ti ti-refresh"></i> Regenerate</button>
                    <button class="btn btn-primary" onclick="sendEmailNowDetail()"><i class="ti ti-send"></i> Send</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- WhatsApp Composer Modal -->
<div class="modal-overlay" id="waModal">
    <div class="modal" style="max-width:480px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h3 class="modal-title">💬 WhatsApp — <span id="waBusinessName"></span></h3>
            <button onclick="closeModal('waModal')" style="background:none;border:none;color:var(--text3);font-size:1.3rem;cursor:pointer"><i class="ti ti-x"></i></button>
        </div>
        <div class="form-group">
            <label>Service</label>
            <select class="form-control" id="waService">
                <option value="WordPress Website Design">WordPress Website Design</option>
                <option value="WooCommerce E-Commerce Store">WooCommerce E-Commerce Store</option>
                <option value="Creative Branding & Logo">Creative Branding & Logo</option>
                <option value="SEO & Google Rankings">SEO & Google Rankings</option>
                <option value="Facebook/Google Ads">Facebook/Google Ads</option>
                <option value="Full Digital Package">Full Digital Package</option>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Tone</label>
                <select class="form-control" id="waTone">
                    <option value="friendly">Friendly</option>
                    <option value="direct">Direct</option>
                </select>
            </div>
            <div class="form-group">
                <label>Language</label>
                <select class="form-control" id="waLanguage">
                    <option value="English">English</option>
                    <option value="Bangla">Bangla</option>
                    <option value="Banglish">Banglish</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <textarea class="form-control" id="waMessage" rows="4" placeholder="Generated message..."></textarea>
        </div>
        <div class="form-actions">
            <button class="btn btn-ai" onclick="generateWaDetail()" id="genWaBtn"><i class="ti ti-sparkles"></i> Generate</button>
            <a id="waOpenLink" href="#" target="_blank" class="btn btn-primary" style="display:none"><i class="ti ti-brand-whatsapp"></i> Open WhatsApp</a>
        </div>
    </div>
</div>

<!-- Reminder Modal -->
<div class="modal-overlay" id="reminderModal">
    <div class="modal" style="max-width:400px">
        <h3 class="modal-title">⏰ Set Reminder</h3>
        <form onsubmit="return setReminder(event, <?= $id ?>)">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label>Date & Time</label>
                <input class="form-control" type="datetime-local" id="reminderDate" required>
            </div>
            <div class="form-group">
                <label>Note</label>
                <input class="form-control" id="reminderNote" placeholder="e.g. Call back about website">
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary"><i class="ti ti-bell"></i> Set</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('reminderModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
let _csrf = '<?= csrf_token() ?>';
let currentLeadId = <?= $id ?>;

function toggleEditInfo() {
    document.getElementById('infoDisplay').style.display = document.getElementById('infoDisplay').style.display === 'none' ? 'block' : 'none';
    document.getElementById('infoEdit').style.display = document.getElementById('infoEdit').style.display === 'none' ? 'block' : 'none';
}

function saveInfo(e) {
    e.preventDefault();
    let btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span> Saving...';
    let fd = new FormData(e.target);
    fd.append('action', 'save_lead');
    fd.append('id', currentLeadId);
    fetch('../api/leads_save.php', {method:'POST', body: new URLSearchParams(fd)})
    .then(r=>r.json()).then(j=>{
        if(j.success){ showToast('Saved'); location.reload() }
        else showToast(j.error, 'error');
    }).finally(()=>{ btn.disabled=false; btn.innerHTML='<i class="ti ti-device-floppy"></i> Save' });
}

function changeStatus(id) {
    let status = document.getElementById('statusSelect').value;
    fetch('../api/leads_save.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'_csrf='+encodeURIComponent(_csrf)+'&action=update_status&id='+id+'&status='+status
    }).then(r=>r.json()).then(j=>{
        if(j.success) showToast('Status updated');
        else showToast(j.error, 'error');
    });
}

function addTag(id) {
    let input = document.getElementById('tagInput');
    let tag = input.value.trim();
    if (!tag) return;
    let current = '<?= h($lead['tags']) ?>';
    let tags = current ? current + ',' + tag : tag;
    fetch('../api/leads_save.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'_csrf='+encodeURIComponent(_csrf)+'&action=update_tags&id='+id+'&tags='+encodeURIComponent(tags)
    }).then(r=>r.json()).then(j=>{
        if(j.success){ showToast('Tag added'); location.reload() }
        else showToast(j.error, 'error');
    });
}

function removeTag(id, tag) {
    let current = '<?= h($lead['tags']) ?>';
    let tags = current.split(',').filter(t=>t.trim()!==tag).join(',');
    fetch('../api/leads_save.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'_csrf='+encodeURIComponent(_csrf)+'&action=update_tags&id='+id+'&tags='+encodeURIComponent(tags)
    }).then(r=>r.json()).then(j=>{
        if(j.success) location.reload();
    });
}

function runAudit() {
    let btn = document.getElementById('auditBtn');
    btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span> Auditing...';
    fetch('../api/leads_audit.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
            url: '<?= h($lead['website']) ?>',
            lead_id: <?= $id ?>,
            business_name: '<?= h(addslashes($lead['business_name'])) ?>',
            niche: '<?= h(addslashes($lead['niche'])) ?>'
        })
    }).then(r=>r.json()).then(j=>{
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-refresh"></i> Re-run Audit';
        if (j.success && j.data) {
            let d = j.data;
            let html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;font-size:0.82rem">';
            html += '<div><span style="color:var(--text3)">SSL:</span> ' + (d.ssl ? '<span style="color:var(--green)">✓ Secure</span>' : '<span style="color:var(--red)">✗ No SSL</span>') + '</div>';
            html += '<div><span style="color:var(--text3)">Speed:</span> ' + (d.speed === 'Fast' ? '<span style="color:var(--green)">' : d.speed === 'Moderate' ? '<span style="color:#F59E0B">' : '<span style="color:var(--red)">') + d.speed + ' ('+d.load_time+'s)</span></div>';
            html += '<div><span style="color:var(--text3)">Mobile:</span> ' + (d.mobile ? '<span style="color:var(--green)">✓ Yes</span>' : '<span style="color:var(--red)">✗ No</span>') + '</div>';
            html += '<div><span style="color:var(--text3)">Meta Desc:</span> ' + (d.meta ? '<span style="color:var(--green)">✓</span>' : '<span style="color:var(--red)">✗ Missing</span>') + '</div>';
            html += '<div style="grid-column:1/-1"><span style="color:var(--text3)">Title:</span> ' + (d.has_title ? h(d.title) : '<span style="color:var(--red)">Missing</span>') + '</div>';
            html += '<div><span style="color:var(--text3)">Contact Form:</span> ' + (d.contact_form ? '<span style="color:var(--green)">✓</span>' : '<span style="color:var(--text3)">✗</span>') + '</div>';
            html += '<div><span style="color:var(--text3)">WordPress:</span> ' + (d.has_wp ? '<span style="color:var(--green)">✓</span>' : '<span style="color:var(--text3)">No</span>') + '</div>';
            html += '<div style="grid-column:1/-1;padding-top:0.75rem;border-top:1px solid var(--border)"><span style="color:var(--text3);font-size:0.72rem;text-transform:uppercase;font-weight:600">Lead Score:</span> <span style="font-size:1.3rem;font-weight:900;font-family:\'Orbitron\',sans-serif;color:'+(d.lead_score>60?'var(--accent)':(d.lead_score>30?'#F59E0B':'#ef4444'))+'">'+d.lead_score+'/100</span></div>';
            if (d.ai_summary) html += '<div style="grid-column:1/-1;padding-top:0.5rem"><div style="background:rgba(204,255,0,0.05);border:1px solid rgba(204,255,0,0.1);border-radius:8px;padding:0.75rem;font-size:0.82rem;color:var(--text2)">🤖 ' + h(d.ai_summary) + '</div></div>';
            html += '</div>';
            document.getElementById('auditResults').innerHTML = html;
        } else {
            document.getElementById('auditResults').innerHTML = '<p style="color:var(--red)">Audit failed: ' + (j.error||'Unknown error') + '</p>';
        }
    });
}

function addNote(e) {
    e.preventDefault();
    let note = document.getElementById('noteInput').value.trim();
    if (!note) return;
    fetch('../api/leads_save.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'_csrf='+encodeURIComponent(_csrf)+'&action=add_note&id='+currentLeadId+'&note='+encodeURIComponent(note)
    }).then(r=>r.json()).then(j=>{
        if(j.success){ showToast('Note added'); location.reload() }
        else showToast(j.error, 'error');
    });
}

function showReminderModal(id) {
    document.getElementById('reminderModal').classList.add('open');
}

function setReminder(e, id) {
    e.preventDefault();
    let date = document.getElementById('reminderDate').value;
    let note = document.getElementById('reminderNote').value;
    fetch('../api/leads_save.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'_csrf='+encodeURIComponent(_csrf)+'&action=set_reminder&id='+id+'&reminder_date='+encodeURIComponent(date)+'&reminder_note='+encodeURIComponent(note)
    }).then(r=>r.json()).then(j=>{
        if(j.success){ showToast('Reminder set'); closeModal('reminderModal'); location.reload() }
        else showToast(j.error, 'error');
    });
    return false;
}

function toggleBlacklist(id, current) {
    let val = current ? 0 : 1;
    if (val && !confirm('Blacklist this lead?')) return;
    fetch('../api/leads_save.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'_csrf='+encodeURIComponent(_csrf)+'&action=blacklist&id='+id+'&blacklist='+val
    }).then(r=>r.json()).then(j=>{
        if(j.success) location.reload();
        else showToast(j.error, 'error');
    });
}

function deleteLead(id) {
    if (!confirm('Delete this lead permanently?')) return;
    fetch('../api/leads_save.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'_csrf='+encodeURIComponent(_csrf)+'&action=delete_lead&id='+id
    }).then(r=>r.json()).then(j=>{
        if(j.success){ showToast('Deleted'); window.location.href='leads.php?view=my_leads' }
        else showToast(j.error, 'error');
    });
}

function showEmailBody(id, btn) {
    let div = btn.nextElementSibling;
    div.style.display = div.style.display === 'none' ? 'block' : 'none';
}

function h(s) { let d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openModal(id) { document.getElementById(id).classList.add('open'); }

let emailLeadId = <?= $id ?>, waLeadId = <?= $id ?>, waPhone = '<?= h(addslashes($lead['whatsapp'] ?: $lead['phone'])) ?>';

function openEmailComposer(id, name, email) {
    emailLeadId = id;
    document.getElementById('emailBusinessName').textContent = name;
    document.getElementById('emailTo').value = email;
    document.getElementById('emailResult').style.display = 'none';
    openModal('emailModal');
}

function generateEmailDetail() {
    let btn = document.getElementById('genEmailBtn');
    btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span>';
    document.getElementById('emailResult').style.display = 'none';
    fetch('../api/leads_ai.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
            task:'generate_cold_email',
            context:{
                business_name: document.getElementById('emailBusinessName').textContent,
                niche: '<?= h(addslashes($lead['niche'])) ?>',
                has_website: <?= $lead['has_website'] ? 1 : 0 ?>,
                service: document.getElementById('emailService').value,
                tone: document.getElementById('emailTone').value,
                language: document.getElementById('emailLanguage').value,
                email_type: document.getElementById('emailType').value,
            }
        })
    }).then(r=>r.json()).then(j=>{
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate';
        if(j.success && j.data){
            if(j.data.subject) document.getElementById('emailSubject').value = j.data.subject;
            if(j.data.body) document.getElementById('emailBody').value = j.data.body;
            document.getElementById('emailResult').style.display = 'block';
        } else showToast(j.error||'Failed', 'error');
    });
}

function sendEmailNowDetail() {
    let btn = document.querySelector('#emailResult .btn-primary');
    btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span>';
    let fd = new URLSearchParams();
    fd.append('_csrf', _csrf);
    fd.append('action', 'send');
    fd.append('lead_id', emailLeadId);
    fd.append('subject', document.getElementById('emailSubject').value);
    fd.append('body', document.getElementById('emailBody').value);
    fd.append('to_email', document.getElementById('emailTo').value);
    fetch('../api/leads_email.php', {method:'POST', body: fd})
    .then(r=>r.json()).then(j=>{
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-send"></i> Send';
        if(j.success){ showToast('Sent!'); closeModal('emailModal'); location.reload(); }
        else showToast(j.error, 'error');
    });
}

function openWaComposer(id, name, phone) {
    waLeadId = id; waPhone = phone;
    document.getElementById('waBusinessName').textContent = name;
    document.getElementById('waMessage').value = '';
    document.getElementById('waOpenLink').style.display = 'none';
    openModal('waModal');
}

function generateWaDetail() {
    let btn = document.getElementById('genWaBtn');
    btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span>';
    fetch('../api/leads_ai.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
            task:'generate_whatsapp',
            context:{
                business_name: document.getElementById('waBusinessName').textContent,
                niche: '<?= h(addslashes($lead['niche'])) ?>',
                service: document.getElementById('waService').value,
                tone: document.getElementById('waTone').value,
                language: document.getElementById('waLanguage').value,
            }
        })
    }).then(r=>r.json()).then(j=>{
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate';
        if(j.success && j.data) {
            let msg = typeof j.data === 'string' ? j.data : (j.data.body || '');
            document.getElementById('waMessage').value = msg;
            let phone = waPhone.replace(/[^0-9]/g,'');
            if (phone.startsWith('0')) phone = '88' + phone;
            document.getElementById('waOpenLink').href = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg);
            document.getElementById('waOpenLink').style.display = 'block';
        }
    });
}

<?php if ($lead['website']): ?>
// Auto-run audit on page load
setTimeout(() => { if (document.getElementById('auditResults').querySelector('p')) runAudit(); }, 500);
<?php endif; ?>
</script>
