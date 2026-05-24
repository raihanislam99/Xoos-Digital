<?php
$statuses = ['new','contacted','replied','interested','meeting_booked','closed_won','closed_lost'];
$niches = ['Web Design Needed','Restaurant','Clinic/Doctor','Real Estate Agent','Salon/Beauty','Gym/Fitness','Lawyer/Law Firm','School/Coaching','Travel Agency','NGO/Non-Profit','Fashion Brand','Food Brand','E-Commerce Store','Garments Factory','Hotel','Pharmacy','Dental Clinic','Car Workshop','Interior Designer','Event Management','Photography Studio','Clothing Store','Furniture Shop','Electronics Store','IT Company','Construction Company','Cleaning Service','Catering','Printing Shop','Stationery','Hardware Store','Coaching Center','Kindergarten','University','Hospital','Diagnostic Center','Eye Clinic','Physiotherapy','Veterinary Clinic','Accountant','Architect','Engineering Firm','Manufacturing','Export Company','Import Business','Logistics','Courier Service','Marketing Agency','Software Company','Graphic Designer','Freelancer','Influencer','YouTuber','Bakery','Cafe','Fast Food','Grocery','Supermarket','Book Shop','Jewelry Shop','Car Dealership','Property Developer','Legal Services','Tax Consultant','Training Institute','Language School','Driving School','Computer Training','Coding Bootcamp'];

$filters = [
    'status' => $_GET['f_status'] ?? '',
    'niche' => $_GET['f_niche'] ?? '',
    'city' => trim($_GET['f_city'] ?? ''),
    'search' => trim($_GET['search'] ?? ''),
    'has_website' => $_GET['f_website'] ?? '',
    'score_min' => (int)($_GET['f_score_min'] ?? 0),
];

$sort = $_GET['sort'] ?? 'newest';
$sortMap = [
    'newest' => 'created_at DESC',
    'oldest' => 'created_at ASC',
    'score' => 'lead_score DESC',
    'name' => 'business_name ASC',
    'contacted' => 'updated_at DESC',
];
$orderBy = $sortMap[$sort] ?? 'created_at DESC';

$where = ['1=1'];
$params = [];

if ($filters['status']) {
    $where[] = 'status = ?';
    $params[] = $filters['status'];
}
if ($filters['niche']) {
    $where[] = 'niche = ?';
    $params[] = $filters['niche'];
}
if ($filters['city']) {
    $where[] = 'city LIKE ?';
    $params[] = '%' . $filters['city'] . '%';
}
if ($filters['search']) {
    $where[] = '(business_name LIKE ? OR email LIKE ? OR phone LIKE ? OR owner_name LIKE ?)';
    $s = '%' . $filters['search'] . '%';
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
}
if ($filters['has_website'] === 'yes') {
    $where[] = 'has_website = 1';
} elseif ($filters['has_website'] === 'no') {
    $where[] = 'has_website = 0';
}
if ($filters['score_min'] > 0) {
    $where[] = 'lead_score >= ?';
    $params[] = $filters['score_min'];
}

$where[] = 'is_blacklisted = 0';
$whereClause = implode(' AND ', $where);

$leads = db_rows("SELECT * FROM leads WHERE $whereClause ORDER BY $orderBy LIMIT 500", $params);
$totalLeads = db_val("SELECT COUNT(*) FROM leads WHERE $whereClause", $params);
?>
<div class="page-header" style="margin-top:0">
    <div class="flex flex-wrap">
        <span style="color:var(--text3);font-size:0.85rem;margin-right:8px"><?= $totalLeads ?> leads</span>
        <a href="leads.php?view=finder" class="btn btn-primary"><i class="ti ti-users-plus"></i> Find Leads</a>
        <button class="btn btn-secondary" onclick="showAddLeadModal()"><i class="ti ti-plus"></i> Add Lead</button>
        <button class="btn btn-secondary" onclick="document.getElementById('importCsv').click()"><i class="ti ti-file-import"></i> Import CSV</button>
        <a href="leads.php?view=my_leads&export=csv<?= !empty($whereClause) ? '&' . http_build_query($_GET) : '' ?>" class="btn btn-secondary"><i class="ti ti-file-export"></i> Export</a>
    </div>
</div>

<style>
.lead-filters { display:flex;gap:8px;flex-wrap:wrap;margin-bottom:1.5rem;align-items:center }
.lead-filters select,.lead-filters input { background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0.5rem 0.75rem;color:var(--text);font-size:0.82rem;outline:none;font-family:'Inter',sans-serif }
.lead-filters select:focus,.lead-filters input:focus { border-color:var(--accent) }
.status-badge { display:inline-block;padding:3px 10px;border-radius:999px;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.04em }
.s-new { background:#374151;color:#9CA3AF }
.s-contacted { background:rgba(29,78,216,0.2);color:#60A5FA }
.s-replied { background:rgba(133,77,14,0.2);color:#F59E0B }
.s-interested { background:rgba(204,255,0,0.15);color:#CCFF00 }
.s-meeting_booked { background:rgba(88,28,135,0.2);color:#C084FC }
.s-closed_won { background:rgba(20,83,45,0.2);color:#34D399 }
.s-closed_lost { background:rgba(127,29,29,0.2);color:#F87171 }
.score-bar { height:4px;border-radius:99px;background:var(--bg3);overflow:hidden;margin-top:4px;max-width:80px }
.score-fill { height:100%;border-radius:99px;transition:width 0.3s }
.bulk-bar { display:none;padding:0.75rem 1rem;background:var(--bg2);border:1px solid var(--accent);border-radius:12px;margin-bottom:1rem;align-items:center;gap:12px;flex-wrap:wrap }
.bulk-bar.show { display:flex }
</style>

<div class="lead-filters">
    <form method="get" action="leads.php" style="display:contents">
        <input type="hidden" name="view" value="my_leads">
        <input type="text" name="search" placeholder="Search name, email, phone..." value="<?= h($filters['search']) ?>" style="width:200px" onchange="this.form.submit()">
        <select name="f_status" onchange="this.form.submit()">
            <option value="">All Status</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="f_niche" onchange="this.form.submit()">
            <option value="">All Niches</option>
            <?php foreach ($niches as $n): ?>
                <option value="<?= h($n) ?>" <?= $filters['niche'] === $n ? 'selected' : '' ?>><?= h($n) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="f_city" placeholder="City..." value="<?= h($filters['city']) ?>" style="width:120px" onchange="this.form.submit()">
        <select name="f_website" onchange="this.form.submit()">
            <option value="">Website: All</option>
            <option value="yes" <?= $filters['has_website'] === 'yes' ? 'selected' : '' ?>>Has Website</option>
            <option value="no" <?= $filters['has_website'] === 'no' ? 'selected' : '' ?>>No Website</option>
        </select>
        <select name="sort" onchange="this.form.submit()">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="score" <?= $sort === 'score' ? 'selected' : '' ?>>Score ↓</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A-Z</option>
            <option value="contacted" <?= $sort === 'contacted' ? 'selected' : '' ?>>Last Contacted</option>
        </select>
    </form>
</div>

<div class="bulk-bar" id="bulkBar">
    <strong style="color:var(--accent);font-size:0.82rem"><span id="bulkCount">0</span> selected</strong>
    <button class="btn btn-primary btn-sm" onclick="bulkEmail()"><i class="ti ti-mail"></i> Send Email</button>
    <button class="btn btn-ai btn-sm" onclick="bulkWhatsApp()"><i class="ti ti-brand-whatsapp"></i> WhatsApp</button>
    <select id="bulkStatusSelect" style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0.4rem 0.7rem;color:var(--text);font-size:0.78rem">
        <option value="">Change Status</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>"><?= ucwords(str_replace('_', ' ', $s)) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-secondary btn-sm" onclick="bulkChangeStatus()">Apply</button>
    <input type="text" id="bulkTagInput" placeholder="Tag name..." style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:0.4rem 0.7rem;color:var(--text);font-size:0.78rem;width:120px">
    <button class="btn btn-secondary btn-sm" onclick="bulkAddTag()">Add Tag</button>
    <button class="btn btn-danger btn-sm" onclick="bulkDelete()"><i class="ti ti-trash"></i> Delete</button>
</div>

<?php if (count($leads)): ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th style="width:30px"><input type="checkbox" onchange="toggleAll(this)" style="accent-color:var(--accent)"></th>
                <th style="width:30px">#</th>
                <th>Business</th>
                <th>Niche</th>
                <th>City</th>
                <th>Website</th>
                <th>Score</th>
                <th>Status</th>
                <th>Last Contact</th>
                <th style="text-align:right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($leads as $lead): ?>
            <tr>
                <td><input type="checkbox" class="lead-check" value="<?= $lead['id'] ?>" onchange="updateBulkBar()" style="accent-color:var(--accent)"></td>
                <td style="color:var(--text3)"><?= $i++ ?></td>
                <td>
                    <a href="leads.php?view=detail&id=<?= $lead['id'] ?>" style="color:var(--text);text-decoration:none;font-weight:600"><?= h($lead['business_name']) ?></a>
                    <?php if ($lead['owner_name']): ?>
                        <div style="font-size:0.75rem;color:var(--text3)"><?= h($lead['owner_name']) ?></div>
                    <?php endif; ?>
                </td>
                <td><span style="font-size:0.75rem;color:var(--accent)"><?= h($lead['niche']) ?></span></td>
                <td style="font-size:0.82rem"><?= h($lead['city']) ?></td>
                <td>
                    <?php if ($lead['website']): ?>
                        <a href="<?= h($lead['website']) ?>" target="_blank" style="color:var(--green);text-decoration:none;font-size:0.78rem" title="<?= h($lead['website']) ?>">✓</a>
                    <?php else: ?>
                        <span style="color:var(--text3);font-size:0.78rem">✗</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $score = (int)$lead['lead_score']; ?>
                    <span style="font-size:0.78rem;font-weight:700;color:<?= $score > 60 ? 'var(--accent)' : ($score > 30 ? '#F59E0B' : '#ef4444') ?>"><?= $score ?></span>
                    <div class="score-bar"><div class="score-fill" style="width:<?= $score ?>%;background:<?= $score > 60 ? 'var(--accent)' : ($score > 30 ? '#F59E0B' : '#ef4444') ?>"></div></div>
                </td>
                <td><span class="status-badge s-<?= $lead['status'] ?>"><?= $lead['status'] ?></span></td>
                <td style="font-size:0.75rem;color:var(--text3)"><?= $lead['updated_at'] ? date('d M, H:i', strtotime($lead['updated_at'])) : '-' ?></td>
                <td style="text-align:right;white-space:nowrap">
                    <button onclick="sendEmail(<?= $lead['id'] ?>,'<?= h(addslashes($lead['business_name'])) ?>','<?= h($lead['email']) ?>')" class="btn btn-sm" style="background:none;border:1px solid var(--border);color:var(--text2);padding:0.3rem 0.5rem;cursor:pointer;border-radius:6px" title="Send Email"><i class="ti ti-mail" style="font-size:1rem"></i></button>
                    <button onclick="sendWhatsApp(<?= $lead['id'] ?>,'<?= h(addslashes($lead['business_name'])) ?>','<?= h($lead['whatsapp'] ?: $lead['phone']) ?>')" class="btn btn-sm" style="background:none;border:1px solid var(--border);color:var(--text2);padding:0.3rem 0.5rem;cursor:pointer;border-radius:6px" title="WhatsApp"><i class="ti ti-brand-whatsapp" style="font-size:1rem"></i></button>
                    <a href="leads.php?view=detail&id=<?= $lead['id'] ?>" class="btn btn-sm" style="background:none;border:1px solid var(--border);color:var(--text2);padding:0.3rem 0.5rem;text-decoration:none;border-radius:6px" title="View Details"><i class="ti ti-eye" style="font-size:1rem"></i></a>
                    <button onclick="editLead(<?= $lead['id'] ?>)" class="btn btn-sm" style="background:none;border:1px solid var(--border);color:var(--text2);padding:0.3rem 0.5rem;cursor:pointer;border-radius:6px" title="Edit"><i class="ti ti-pencil" style="font-size:1rem"></i></button>
                    <button onclick="confirmDeleteLead(<?= $lead['id'] ?>,'<?= h(addslashes($lead['business_name'])) ?>')" class="btn btn-sm" style="background:none;border:1px solid var(--border);color:var(--text3);padding:0.3rem 0.5rem;cursor:pointer;border-radius:6px" title="Delete"><i class="ti ti-trash" style="font-size:1rem"></i></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="card">
    <div class="empty-state">
        <i class="ti ti-address-book"></i>
        <p>No leads found.</p>
        <div class="flex" style="justify-content:center;margin-top:1rem;gap:8px">
            <a href="leads.php?view=finder" class="btn btn-primary"><i class="ti ti-users-plus"></i> Find Leads</a>
            <button class="btn btn-secondary" onclick="showAddLeadModal()"><i class="ti ti-plus"></i> Add Manually</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Add/Edit Lead Modal -->
<div class="modal-overlay" id="leadFormModal">
    <div class="modal" style="max-width:600px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h3 class="modal-title" id="leadFormTitle">Add Lead</h3>
            <button onclick="closeModal('leadFormModal')" style="background:none;border:none;color:var(--text3);font-size:1.3rem;cursor:pointer"><i class="ti ti-x"></i></button>
        </div>
        <form id="leadForm" onsubmit="return saveLeadForm(event)">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" id="leadFormId" value="0">
            <input type="hidden" name="action" value="save_lead">
            <div class="form-row">
                <div class="form-group">
                    <label>Business Name *</label>
                    <input class="form-control" name="business_name" id="f_business" required>
                </div>
                <div class="form-group">
                    <label>Niche</label>
                    <select class="form-control" name="niche" id="f_niche">
                        <option value="">Select...</option>
                        <?php foreach ($niches as $n): ?>
                            <option value="<?= h($n) ?>"><?= h($n) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Owner Name</label>
                    <input class="form-control" name="owner_name" id="f_owner">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input class="form-control" name="email" id="f_email" type="email">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Phone</label>
                    <input class="form-control" name="phone" id="f_phone">
                </div>
                <div class="form-group">
                    <label>WhatsApp</label>
                    <input class="form-control" name="whatsapp" id="f_whatsapp">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Website</label>
                    <input class="form-control" name="website" id="f_website">
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input class="form-control" name="city" id="f_city">
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea class="form-control" name="notes" id="f_notes" rows="3"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="leadFormBtn"><i class="ti ti-device-floppy"></i> Save Lead</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('leadFormModal')">Cancel</button>
            </div>
        </form>
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
                <label>Service to Pitch</label>
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
                    <label>Email Type</label>
                    <select class="form-control" id="emailType">
                        <option value="cold">Cold Outreach</option>
                        <option value="followup">Follow-up (Day 3)</option>
                        <option value="followup7">Follow-up (Day 7)</option>
                        <option value="final">Final Follow-up</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tone</label>
                    <select class="form-control" id="emailTone">
                        <option value="professional">Professional</option>
                        <option value="friendly">Friendly & Casual</option>
                        <option value="direct">Direct & Bold</option>
                        <option value="consultative">Consultative</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Language</label>
                    <select class="form-control" id="emailLanguage">
                        <option value="English">English</option>
                        <option value="Bangla">Bangla</option>
                        <option value="Banglish">Mixed (Banglish)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button class="btn btn-ai" onclick="generateEmail()" id="genEmailBtn" style="width:100%"><i class="ti ti-sparkles"></i> Generate Email</button>
                </div>
            </div>
            <div id="emailResult" style="display:none">
                <div class="form-group">
                    <label>Subject</label>
                    <input class="form-control" id="emailSubject" style="font-weight:600">
                </div>
                <div class="form-group">
                    <label>Body</label>
                    <textarea class="form-control" id="emailBody" rows="10" style="font-size:0.82rem;line-height:1.5"></textarea>
                    <div style="display:flex;justify-content:space-between;margin-top:4px">
                        <span style="font-size:0.75rem;color:var(--text3)"><span id="emailCharCount">0</span> chars</span>
                        <span style="font-size:0.75rem;color:var(--text3)"><span id="emailWordCount">0</span> words</span>
                    </div>
                </div>
                <div class="form-group" style="display:flex;gap:8px;align-items:center">
                    <label style="margin-bottom:0;white-space:nowrap">To:</label>
                    <input class="form-control" id="emailTo" type="email" style="flex:1">
                </div>
                <div class="form-actions">
                    <button class="btn btn-ai" onclick="regenerateEmail()"><i class="ti ti-refresh"></i> Regenerate</button>
                    <button class="btn btn-primary" onclick="sendEmailNow()"><i class="ti ti-send"></i> Send Email</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- WhatsApp Modal -->
<div class="modal-overlay" id="waModal">
    <div class="modal" style="max-width:480px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;border-bottom:1px solid var(--border);padding-bottom:1rem">
            <h3 class="modal-title">💬 WhatsApp Message — <span id="waBusinessName"></span></h3>
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
            <label>Message</label>
            <textarea class="form-control" id="waMessage" rows="4"></textarea>
            <div style="display:flex;justify-content:space-between;margin-top:4px">
                <span style="font-size:0.75rem;color:var(--text3)"><span id="waCharCount">0</span> chars</span>
                <button class="btn btn-ai btn-sm" onclick="generateWhatsApp()" id="genWaBtn"><i class="ti ti-sparkles"></i> Generate</button>
            </div>
        </div>
        <div class="form-actions" style="flex-direction:column">
            <a id="waOpenLink" href="#" target="_blank" class="btn btn-primary" style="width:100%;display:none"><i class="ti ti-brand-whatsapp"></i> Open in WhatsApp Web</a>
            <button class="btn btn-secondary" onclick="copyWaMessage()" id="waCopyBtn" style="width:100%;display:none"><i class="ti ti-copy"></i> Copy Message</button>
        </div>
    </div>
</div>

<!-- Hidden CSV input -->
<input type="file" id="importCsv" accept=".csv" style="display:none" onchange="importCSV(this)">

<script>
let emailLeadId = 0, waLeadId = 0, waPhone = '';
let _csrf = '<?= csrf_token() ?>';

function updateBulkBar() {
    let checks = document.querySelectorAll('.lead-check:checked');
    let bar = document.getElementById('bulkBar');
    let count = checks.length;
    document.getElementById('bulkCount').textContent = count;
    bar.classList.toggle('show', count > 0);
}

function toggleAll(el) {
    document.querySelectorAll('.lead-check').forEach(c => c.checked = el.checked);
    updateBulkBar();
}

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.lead-check:checked')).map(c => c.value).join(',');
}

function bulkEmail() {
    let ids = getSelectedIds();
    if (!ids) return;
    window.location.href = 'leads.php?view=detail&bulk_email=1&ids=' + ids;
}

function bulkWhatsApp() {
    let ids = getSelectedIds();
    if (!ids) return showToast('WhatsApp bulk: coming soon', 'info');
}

function bulkChangeStatus() {
    let ids = getSelectedIds();
    let status = document.getElementById('bulkStatusSelect').value;
    if (!ids || !status) return;
    fetch('api/leads_save.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_csrf='+encodeURIComponent(_csrf)+'&action=bulk_action&ids='+ids+'&bulk_action=change_status&status='+status
    }).then(r=>r.json()).then(j=>{
        if(j.success){ showToast(j.message); location.reload() }
        else showToast(j.error, 'error');
    });
}

function bulkAddTag() {
    let ids = getSelectedIds();
    let tag = document.getElementById('bulkTagInput').value.trim();
    if (!ids || !tag) return;
    fetch('api/leads_save.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_csrf='+encodeURIComponent(_csrf)+'&action=bulk_action&ids='+ids+'&bulk_action=add_tag&tag='+encodeURIComponent(tag)
    }).then(r=>r.json()).then(j=>{
        if(j.success){ showToast(j.message); location.reload() }
        else showToast(j.error, 'error');
    });
}

function bulkDelete() {
    let ids = getSelectedIds();
    if (!ids || !confirm('Delete selected leads? This cannot be undone.')) return;
    fetch('api/leads_save.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_csrf='+encodeURIComponent(_csrf)+'&action=bulk_action&ids='+ids+'&bulk_action=delete'
    }).then(r=>r.json()).then(j=>{
        if(j.success){ showToast(j.message); location.reload() }
        else showToast(j.error, 'error');
    });
}

function confirmDeleteLead(id, name) {
    if (!confirm('Delete "' + name + '" permanently?')) return;
    fetch('api/leads_save.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_csrf='+encodeURIComponent(_csrf)+'&action=delete_lead&id='+id
    }).then(r=>r.json()).then(j=>{
        if(j.success){ showToast('Lead deleted'); location.reload() }
        else showToast(j.error, 'error');
    });
}

function showAddLeadModal() {
    document.getElementById('leadFormTitle').textContent = 'Add Lead';
    document.getElementById('leadFormId').value = '0';
    document.getElementById('leadForm').reset();
    openModal('leadFormModal');
}

function editLead(id) {
    fetch('api/leads_save.php?action=get&id='+id).catch(()=>{});
    // Load from table via redirect to detail
    window.location.href = 'leads.php?view=detail&id=' + id;
}

function saveLeadForm(e) {
    e.preventDefault();
    let btn = document.getElementById('leadFormBtn');
    btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span> Saving...';
    let fd = new FormData(document.getElementById('leadForm'));
    fetch('api/leads_save.php', {method:'POST', body: new URLSearchParams(fd)})
    .then(r=>r.json()).then(j=>{
        if(j.success){ showToast(j.message); closeModal('leadFormModal'); location.reload() }
        else showToast(j.error, 'error');
    }).finally(()=>{ btn.disabled=false; btn.innerHTML='<i class="ti ti-device-floppy"></i> Save Lead' });
}

function sendEmail(id, name, email) {
    emailLeadId = id;
    document.getElementById('emailBusinessName').textContent = name;
    document.getElementById('emailTo').value = email;
    document.getElementById('emailResult').style.display = 'none';
    document.getElementById('genEmailBtn').disabled = false;
    document.getElementById('genEmailBtn').innerHTML = '<i class="ti ti-sparkles"></i> Generate Email';
    openModal('emailModal');
}

function generateEmail() {
    let btn = document.getElementById('genEmailBtn');
    btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span> Generating...';
    document.getElementById('emailResult').style.display = 'none';

    let ctx = {
        task: 'generate_cold_email',
        context: {
            business_name: document.getElementById('emailBusinessName').textContent,
            niche: '',
            has_website: false,
            service: document.getElementById('emailService').value,
            tone: document.getElementById('emailTone').value,
            language: document.getElementById('emailLanguage').value,
            email_type: document.getElementById('emailType').value,
        }
    };

    fetch('api/leads_ai.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(ctx)
    }).then(r=>r.json()).then(j=>{
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate Email';
        if (j.success && j.data) {
            let data = j.data;
            if (data.subject) document.getElementById('emailSubject').value = data.subject;
            if (data.body) {
                document.getElementById('emailBody').value = data.body;
                updateEmailCounts();
            }
            document.getElementById('emailResult').style.display = 'block';
        } else {
            showToast(j.error || 'Generation failed', 'error');
        }
    }).catch(()=>{
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate Email';
        showToast('Network error', 'error');
    });
}

function regenerateEmail() { generateEmail(); }

function updateEmailCounts() {
    let body = document.getElementById('emailBody').value;
    document.getElementById('emailCharCount').textContent = body.length;
    document.getElementById('emailWordCount').textContent = body.split(/\s+/).filter(w=>w).length;
}
document.getElementById('emailBody').addEventListener('input', updateEmailCounts);

function sendEmailNow() {
    let btn = document.querySelector('#emailResult .btn-primary');
    btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span> Sending...';
    let fd = new URLSearchParams();
    fd.append('_csrf', _csrf);
    fd.append('action', 'send');
    fd.append('lead_id', emailLeadId);
    fd.append('subject', document.getElementById('emailSubject').value);
    fd.append('body', document.getElementById('emailBody').value);
    fd.append('to_email', document.getElementById('emailTo').value);
    fd.append('email_type', document.getElementById('emailType').value);

    fetch('api/leads_email.php', {method:'POST', body: fd})
    .then(r=>r.json()).then(j=>{
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-send"></i> Send Email';
        if (j.success) { showToast(j.message); closeModal('emailModal'); }
        else showToast(j.error, 'error');
    }).catch(()=>{
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-send"></i> Send Email';
        showToast('Failed to send', 'error');
    });
}

function sendWhatsApp(id, name, phone) {
    waLeadId = id; waPhone = phone;
    document.getElementById('waBusinessName').textContent = name;
    document.getElementById('waMessage').value = '';
    document.getElementById('waOpenLink').style.display = 'none';
    document.getElementById('waCopyBtn').style.display = 'none';
    document.getElementById('genWaBtn').disabled = false;
    document.getElementById('genWaBtn').innerHTML = '<i class="ti ti-sparkles"></i> Generate';
    openModal('waModal');
}

function generateWhatsApp() {
    let btn = document.getElementById('genWaBtn');
    btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span> Generating...';

    fetch('api/leads_ai.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
            task: 'generate_whatsapp',
            context: {
                business_name: document.getElementById('waBusinessName').textContent,
                owner_name: '',
                service: document.getElementById('waService').value,
                tone: document.getElementById('waTone').value,
                language: document.getElementById('waLanguage').value,
            }
        })
    }).then(r=>r.json()).then(j=>{
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate';
        if (j.success && j.data) {
            let msg = typeof j.data === 'string' ? j.data : (j.data.body || j.data.message || '');
            document.getElementById('waMessage').value = msg;
            updateWaCounts();
            document.getElementById('waOpenLink').style.display = 'block';
            document.getElementById('waCopyBtn').style.display = 'block';
            // Build WA link
            let phone = waPhone.replace(/[^0-9]/g,'');
            if (phone.startsWith('0')) phone = '88' + phone;
            document.getElementById('waOpenLink').href = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg);
        } else {
            showToast(j.error || 'Generation failed', 'error');
        }
    }).catch(()=>{
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-sparkles"></i> Generate';
        showToast('Network error', 'error');
    });
}

function updateWaCounts() {
    document.getElementById('waCharCount').textContent = document.getElementById('waMessage').value.length;
}
document.getElementById('waMessage').addEventListener('input', updateWaCounts);

function copyWaMessage() {
    let msg = document.getElementById('waMessage').value;
    navigator.clipboard.writeText(msg).then(()=>showToast('Copied!'));
}

function importCSV(input) {
    let file = input.files[0];
    if (!file) return;
    let reader = new FileReader();
    reader.onload = function(e) {
        let text = e.target.result;
        let lines = text.split('\n').filter(l=>l.trim());
        if (lines.length < 2) { showToast('CSV needs header + data rows', 'error'); return; }
        let headers = lines[0].split(',').map(h=>h.trim().toLowerCase());
        let map = {business_name:-1, email:-1, phone:-1, website:-1, city:-1, niche:-1, owner_name:-1};
        for (let h of Object.keys(map)) {
            let idx = headers.indexOf(h);
            if (idx >= 0) map[h] = idx;
        }
        if (map.business_name < 0) { showToast('CSV must have business_name column', 'error'); return; }
        let imported = 0;
        for (let i=1; i<lines.length; i++) {
            let cols = lines[i].split(',').map(c=>c.trim());
            let row = {};
            for (let h of Object.keys(map)) {
                if (map[h] >= 0 && map[h] < cols.length) row[h] = cols[map[h]];
            }
            if (row.business_name) {
                let fd = new URLSearchParams();
                fd.append('_csrf', _csrf);
                fd.append('action', 'quick_save');
                fd.append('business_name', row.business_name);
                fd.append('email', row.email||'');
                fd.append('phone', row.phone||'');
                fd.append('website', row.website||'');
                fd.append('city', row.city||'');
                fd.append('niche', row.niche||'');
                fd.append('owner_name', row.owner_name||'');
                fd.append('source', 'csv_import');
                // Synchronous-ish: fire and forget for batch
                fetch('api/leads_save.php', {method:'POST', body: fd});
                imported++;
            }
        }
        showToast('Imported ' + imported + ' leads (processing...)');
        setTimeout(()=>location.reload(), 1500);
    };
    reader.readAsText(file);
}

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Export CSV handler
document.addEventListener('DOMContentLoaded', function() {
    let url = new URL(window.location.href);
    if (url.searchParams.get('export') === 'csv') {
        let params = new URLSearchParams(window.location.search);
        params.delete('view'); params.delete('export');
        fetch('api/leads_save.php?action=export_csv&' + params.toString())
        .then(r=>r.text()).then(csv=>{
            let blob = new Blob([csv], {type:'text/csv'});
            let a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'leads_export_' + new Date().toISOString().slice(0,10) + '.csv';
            a.click();
            history.replaceState({}, '', 'leads.php?view=my_leads');
        });
    }
});
</script>
