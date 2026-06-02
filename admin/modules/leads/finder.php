<?php
$niches = ['Web Design Needed','Restaurant','Clinic/Doctor','Real Estate Agent','Salon/Beauty','Gym/Fitness','Lawyer/Law Firm','School/Coaching','Travel Agency','NGO/Non-Profit','Fashion Brand','Food Brand','E-Commerce Store','Garments Factory','Hotel','Pharmacy','Dental Clinic','Car Workshop','Interior Designer','Event Management','Photography Studio','Clothing Store','Furniture Shop','Electronics Store','IT Company','Construction Company','Cleaning Service','Catering','Printing Shop','Stationery','Hardware Store','Coaching Center','Kindergarten','University','Hospital','Diagnostic Center','Eye Clinic','Physiotherapy','Veterinary Clinic','Accountant','Architect','Engineering Firm','Manufacturing','Export Company','Import Business','Logistics','Courier Service','Marketing Agency','Software Company','Graphic Designer','Freelancer','Influencer','YouTuber','Bakery','Cafe','Fast Food','Grocery','Supermarket','Book Shop','Jewelry Shop','Car Dealership','Property Developer','Legal Services','Tax Consultant','Training Institute','Language School','Driving School','Computer Training','Coding Bootcamp'];
?>
<div class="page-header" style="margin-top:0">
    <div class="flex flex-wrap">
        <button class="btn btn-secondary" onclick="showManualAdd()"><i class="ti ti-plus"></i> Add Manually</button>
        <button class="btn btn-secondary" onclick="document.getElementById('csvImport').click()"><i class="ti ti-file-import"></i> Import CSV</button>
    </div>
</div>

<style>
.finder-layout { display:grid;grid-template-columns:360px 1fr;gap:1.5rem }
@media(max-width:900px){ .finder-layout{grid-template-columns:1fr} }
.finder-panel { background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:1.5rem;height:fit-content;position:sticky;top:1.5rem }
.finder-results { display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem }
.lead-card { background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:1.25rem;transition:all 0.2s }
.lead-card:hover { border-color:var(--accent);box-shadow:0 8px 24px rgba(204,255,0,0.03) }
.lead-card.saved { border-color:var(--green) }
.lead-card h3 { font-family:'Orbitron',sans-serif;font-size:0.82rem;font-weight:700;color:var(--text);margin-bottom:4px }
.lead-card .meta { font-size:0.75rem;color:var(--text3);margin-bottom:8px }
.lead-card .badge { display:inline-block;padding:2px 8px;border-radius:999px;font-size:0.65rem;font-weight:600;background:rgba(204,255,0,0.1);color:var(--accent) }
.lead-card .row { display:flex;gap:12px;font-size:0.78rem;color:var(--text2);margin-bottom:4px }
.check-group { display:flex;flex-direction:column;gap:6px;margin:0.75rem 0 }
.check-group label { display:flex;align-items:center;gap:8px;font-size:0.82rem;color:var(--text2);cursor:pointer }
.check-group input[type="checkbox"] { accent-color:var(--accent) }
</style>

<div class="finder-layout">
    <div class="finder-panel">
        <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:1rem">🔍 Search Filters</h3>
        <form id="finderForm" onsubmit="return searchLeads(event)">
            <div class="form-group">
                <label>Niche</label>
                <select class="form-control" id="f_niche" style="font-size:0.82rem">
                    <option value="">Select niche...</option>
                    <?php foreach ($niches as $n): ?>
                        <option value="<?= h($n) ?>"><?= h($n) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row" style="grid-template-columns:1fr 1fr">
                <div class="form-group">
                    <label>Country</label>
                    <select class="form-control" id="f_country" style="font-size:0.82rem">
                        <option value="">Any Country</option>
                        <option value="Bangladesh">Bangladesh</option>
                        <option value="India">India</option>
                        <option value="Pakistan">Pakistan</option>
                        <option value="Nepal">Nepal</option>
                        <option value="Sri Lanka">Sri Lanka</option>
                        <option value="Maldives">Maldives</option>
                        <option value="Bhutan">Bhutan</option>
                        <option value="Myanmar">Myanmar</option>
                        <option value="USA">USA</option>
                        <option value="UK">United Kingdom</option>
                        <option value="Canada">Canada</option>
                        <option value="Australia">Australia</option>
                        <option value="UAE">UAE</option>
                        <option value="Saudi Arabia">Saudi Arabia</option>
                        <option value="Qatar">Qatar</option>
                        <option value="Kuwait">Kuwait</option>
                        <option value="Oman">Oman</option>
                        <option value="Bahrain">Bahrain</option>
                        <option value="Singapore">Singapore</option>
                        <option value="Malaysia">Malaysia</option>
                        <option value="Indonesia">Indonesia</option>
                        <option value="Thailand">Thailand</option>
                        <option value="Vietnam">Vietnam</option>
                        <option value="Philippines">Philippines</option>
                        <option value="China">China</option>
                        <option value="Japan">Japan</option>
                        <option value="South Korea">South Korea</option>
                        <option value="Germany">Germany</option>
                        <option value="France">France</option>
                        <option value="Italy">Italy</option>
                        <option value="Spain">Spain</option>
                        <option value="Netherlands">Netherlands</option>
                        <option value="Switzerland">Switzerland</option>
                        <option value="Sweden">Sweden</option>
                        <option value="Norway">Norway</option>
                        <option value="Denmark">Denmark</option>
                        <option value="Belgium">Belgium</option>
                        <option value="Austria">Austria</option>
                        <option value="Ireland">Ireland</option>
                        <option value="Portugal">Portugal</option>
                        <option value="Poland">Poland</option>
                        <option value="Turkey">Turkey</option>
                        <option value="Egypt">Egypt</option>
                        <option value="Nigeria">Nigeria</option>
                        <option value="South Africa">South Africa</option>
                        <option value="Kenya">Kenya</option>
                        <option value="Morocco">Morocco</option>
                        <option value="Brazil">Brazil</option>
                        <option value="Argentina">Argentina</option>
                        <option value="Mexico">Mexico</option>
                        <option value="Colombia">Colombia</option>
                        <option value="Chile">Chile</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input class="form-control" id="f_city" placeholder="e.g. Dhaka" style="font-size:0.82rem">
                </div>
            </div>
            <div class="form-group">
                <label>Search Keyword</label>
                <input class="form-control" id="f_keyword" placeholder="Optional keyword..." style="font-size:0.82rem">
            </div>
            <div class="check-group">
                <label><input type="checkbox" id="f_nowebsite"> No website (high priority)</label>
                <label><input type="checkbox" id="f_nofb"> No Facebook page</label>
                <label><input type="checkbox" id="f_ads"> Not running any ads</label>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.5rem" id="finderBtn"><i class="ti ti-search"></i> Find Leads</button>
        </form>
    </div>

    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:8px">
            <div>
                <span style="color:var(--text3);font-size:0.85rem"><span id="resultCount">0</span> results</span>
            </div>
            <div id="bulkFinderBar" style="display:none;gap:8px;align-items:center">
                <button class="btn btn-primary btn-sm" onclick="saveAllVisible()"><i class="ti ti-device-floppy"></i> Save All Visible</button>
                <button class="btn btn-danger btn-sm" onclick="clearResults()"><i class="ti ti-x"></i> Clear</button>
            </div>
        </div>
        <div id="finderResults" class="finder-results">
            <div class="card" style="grid-column:1/-1;text-align:center;padding:3rem">
                <i class="ti ti-search" style="font-size:2.5rem;color:var(--text3);display:block;margin-bottom:1rem"></i>
                <p style="color:var(--text3);font-size:0.9rem">Select filters and click "Find Leads" to search for potential clients.</p>
                <p style="color:var(--text3);font-size:0.78rem;margin-top:0.5rem">Powered by Google Places API — real business data.</p>
            </div>
        </div>
        <div style="margin-top:1.5rem;padding:1rem;background:rgba(204,255,0,0.03);border:1px solid rgba(204,255,0,0.08);border-radius:12px;font-size:0.72rem;color:var(--text3);text-align:center">
            Data sourced from publicly available information only. Xoos Digital respects GDPR and Bangladesh data protection principles. All outreach includes unsubscribe options.
        </div>
    </div>
</div>

<!-- Manual Add Form (inline, hidden) -->
<div id="manualAddForm" style="display:none;margin-bottom:1.5rem">
    <div class="card">
        <h3 style="font-family:'Orbitron',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--accent);margin-bottom:1rem">Add Lead Manually</h3>
        <form onsubmit="return saveManualLead(event)">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-row">
                <div class="form-group"><label>Business Name *</label><input class="form-control" name="business_name" id="m_business" required></div>
                <div class="form-group"><label>Niche</label>
                    <select class="form-control" name="niche" id="m_niche">
                        <option value="">Select...</option>
                        <?php foreach ($niches as $n): ?>
                            <option value="<?= h($n) ?>"><?= h($n) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email</label><input class="form-control" name="email" type="email"></div>
                <div class="form-group"><label>Phone</label><input class="form-control" name="phone"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Website</label><input class="form-control" name="website"></div>
                <div class="form-group"><label>City</label><input class="form-control" name="city"></div>
            </div>
            <div class="form-group"><label>Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save Lead</button>
                <button type="button" class="btn btn-secondary" onclick="hideManualAdd()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<input type="file" id="csvImport" accept=".csv" style="display:none" onchange="importCsvFile(this)">

<script>
let _csrf = '<?= csrf_token() ?>';

document.querySelector('#finderForm select[name="niche"]')?.addEventListener('change', function() {
    document.getElementById('f_niche').value = this.value;
});

function searchLeads(e) {
    e.preventDefault();
    let btn = document.getElementById('finderBtn');
    btn.disabled = true; btn.innerHTML = '<span class="ai-spinner"></span> Searching...';

    let niche = document.getElementById('f_niche').value;
    let city = document.getElementById('f_city').value;
    let keyword = document.getElementById('f_keyword').value;
    let country = document.getElementById('f_country').value;

    let fd = new URLSearchParams();
    fd.append('niche', niche);
    fd.append('city', city);
    fd.append('country', country);
    fd.append('keyword', keyword);
    fd.append('max', '15');

    fetch('../api/leads_google.php', {method:'POST', body: fd})
    .then(r => r.json())
    .then(j => {
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-search"></i> Find Leads';
        if (j.success) {
            displayResults(j.data);
        } else {
            document.getElementById('finderResults').innerHTML = `
                <div class="card" style="grid-column:1/-1;text-align:center;padding:2rem">
                    <i class="ti ti-alert-triangle" style="font-size:2rem;color:var(--red);display:block;margin-bottom:1rem"></i>
                    <p style="color:var(--red);font-size:0.85rem">${h(j.error)}</p>
                    <p style="color:var(--text3);font-size:0.78rem;margin-top:0.5rem">Set up your Google Places API key in Settings > API & Integrations.</p>
                    <button class="btn btn-primary btn-sm" style="margin-top:1rem" onclick="clearResults()">Dismiss</button>
                </div>`;
        }
    })
    .catch(() => {
        btn.disabled = false; btn.innerHTML = '<i class="ti ti-search"></i> Find Leads';
        showToast('Network error. Check console.', 'error');
    });
}

function displayResults(results) {
    let container = document.getElementById('finderResults');
    document.getElementById('resultCount').textContent = results.length;
    document.getElementById('bulkFinderBar').style.display = 'flex';

    if (!results.length) {
        container.innerHTML = `
            <div class="card" style="grid-column:1/-1;text-align:center;padding:2rem">
                <i class="ti ti-search-off" style="font-size:2rem;color:var(--text3);display:block;margin-bottom:1rem"></i>
                <p style="color:var(--text3)">No results found. Try different filters.</p>
            </div>`;
        return;
    }

    container.innerHTML = '';
    results.forEach(function(r, idx) {
        let card = document.createElement('div');
        card.className = 'lead-card';
        card.dataset.name = r.business_name;
        let hasWebsite = !!r.website;
        let score = r.lead_score;
        let stars = r.rating ? '★'.repeat(Math.round(r.rating)) + '☆'.repeat(5 - Math.round(r.rating)) + ' ' + r.rating : '';
        let enc = function(s) { return (s||'').replace(/'/g,"\\'"); };
        card.innerHTML = `
            <h3>${h(r.business_name)}</h3>
            <div class="meta">
                <span class="badge">${h(r.niche || (r.types && r.types.length ? r.types[0].replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()) : 'Business'))}</span>
                <span style="color:var(--text3)">${h(r.city)}${r.country ? ', '+h(r.country) : ''}</span>
            </div>
            ${stars ? '<div style="font-size:0.75rem;color:var(--text3);margin-bottom:6px">'+stars+' ('+r.user_ratings_total+' reviews)</div>' : ''}
            <div class="row"><span>Website:</span> ${r.website ? '<span style="color:var(--green)">✓</span> <a href="'+h(r.website)+'" target="_blank" style="color:var(--accent);font-size:0.72rem;word-break:break-all">'+h(r.website)+'</a>' : '<span style="color:var(--red)">✗</span>'}</div>
            <div class="row"><span>Phone:</span> ${r.phone ? '<span style="color:var(--green)">✓</span> '+h(r.phone) : '<span style="color:var(--red)">✗</span>'}</div>
            <div class="row" style="font-size:0.72rem;color:var(--text3)">${h(r.address)}</div>
            <div class="row">
                <span>Score:</span>
                <span style="font-weight:700;color:${score > 60 ? 'var(--accent)' : (score > 30 ? '#F59E0B' : '#ef4444')}">${score}</span>
                <div style="flex:1;height:4px;background:var(--bg3);border-radius:99px;overflow:hidden;margin-top:6px">
                    <div style="width:${score}%;height:100%;background:${score > 60 ? 'var(--accent)' : (score > 30 ? '#F59E0B' : '#ef4444')};border-radius:99px"></div>
                </div>
            </div>
            <div style="display:flex;gap:6px;margin-top:10px">
                <button class="btn btn-sm btn-primary" onclick="saveFinderLead(this,'${enc(r.business_name)}','${enc(r.niche||'')}','${enc(r.city||'')}','${enc(r.country||'')}','${enc(r.website||'')}','${enc(r.phone||'')}','${score}','${hasWebsite?1:0}')">
                    <i class="ti ti-device-floppy"></i> Save to My Leads
                </button>
                ${r.website ? '<button class="btn btn-sm btn-ai" onclick="quickAudit(\''+enc(r.website)+'\',\''+enc(r.business_name)+'\',this)"><i class="ti ti-eye"></i> Audit</button>' : ''}
            </div>
            <div class="quickAuditResult" style="margin-top:8px;display:none"></div>
        `;
        container.appendChild(card);
    });
}

function saveFinderLead(btn, name, niche, city, country, website, phone, score, hasWebsite) {
    let fd = new URLSearchParams();
    fd.append('_csrf', _csrf);
    fd.append('action', 'quick_save');
    fd.append('business_name', name);
    fd.append('niche', niche);
    fd.append('city', city);
    fd.append('country', country);
    fd.append('website', website);
    fd.append('phone', phone);
    fd.append('lead_score', score);
    fd.append('has_website', hasWebsite);
    fd.append('source', 'google_places');

    fetch('../api/leads_save.php', {method:'POST', body: fd})
    .then(r=>r.json()).then(j=>{
        if (j.success) {
            btn.className = 'btn btn-sm btn-success';
            btn.innerHTML = '<i class="ti ti-check"></i> Saved ✓';
            btn.closest('.lead-card').classList.add('saved');
            btn.onclick = null;
            showToast('Lead saved!');
        } else {
            showToast(j.error || 'Failed to save', 'error');
        }
    });
}

function quickAudit(url, name, btn) {
    let div = btn.closest('.lead-card').querySelector('.quickAuditResult');
    div.style.display = 'block';
    div.innerHTML = '<span class="ai-spinner"></span> Auditing...';
    btn.disabled = true;

    fetch('../api/leads_audit.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({url: url, business_name: name})
    }).then(r=>r.json()).then(j=>{
        btn.disabled = false;
        if (j.success && j.data) {
            let d = j.data;
            div.innerHTML = `
                <div style="font-size:0.72rem;color:var(--text2);background:var(--bg3);border-radius:8px;padding:8px">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px">
                        <span>SSL: ${d.ssl ? '✓' : '✗'}</span>
                        <span>Speed: ${d.speed}</span>
                        <span>Mobile: ${d.mobile ? '✓' : '✗'}</span>
                        <span>Score: <strong>${d.lead_score}</strong></span>
                    </div>
                    ${d.ai_summary ? '<div style="margin-top:6px;padding-top:6px;border-top:1px solid var(--border);color:var(--text3)">'+h(d.ai_summary)+'</div>' : ''}
                </div>
            `;
        } else {
            div.innerHTML = '<span style="color:var(--red);font-size:0.72rem">Audit failed</span>';
        }
    });
}

function saveAllVisible() {
    let cards = document.querySelectorAll('.lead-card:not(.saved)');
    if (!cards.length) { showToast('No unsaved leads'); return; }
    cards.forEach(function(card) {
        let btn = card.querySelector('.btn-primary');
        if (btn) btn.click();
    });
    setTimeout(() => showToast('Saved all leads!'), 1000);
}

function clearResults() {
    document.getElementById('finderResults').innerHTML = `
        <div class="card" style="grid-column:1/-1;text-align:center;padding:3rem">
            <i class="ti ti-search" style="font-size:2.5rem;color:var(--text3);display:block;margin-bottom:1rem"></i>
            <p style="color:var(--text3);font-size:0.9rem">Search results cleared.</p>
        </div>`;
    document.getElementById('resultCount').textContent = '0';
    document.getElementById('bulkFinderBar').style.display = 'none';
}

function showManualAdd() {
    document.getElementById('manualAddForm').style.display = 'block';
    document.getElementById('manualAddForm').scrollIntoView({behavior:'smooth'});
}
function hideManualAdd() {
    document.getElementById('manualAddForm').style.display = 'none';
}

function saveManualLead(e) {
    e.preventDefault();
    let fd = new FormData(e.target);
    fd.append('action', 'quick_save');
    fd.append('source', 'manual');
    fd.append('_csrf', _csrf);
    fetch('../api/leads_save.php', {method:'POST', body: new URLSearchParams(fd)})
    .then(r=>r.json()).then(j=>{
        if(j.success){ showToast('Lead saved!'); hideManualAdd(); e.target.reset(); }
        else showToast(j.error, 'error');
    });
    return false;
}

function importCsvFile(input) {
    let file = input.files[0];
    if (!file) return;
    let reader = new FileReader();
    reader.onload = function(e) {
        let lines = e.target.result.split('\n').filter(l=>l.trim());
        if (lines.length < 2) return;
        let headers = lines[0].split(',').map(h=>h.trim().toLowerCase());
        let imported = 0;
        for (let i=1; i<lines.length; i++) {
            let cols = lines[i].split(',').map(c=>c.trim());
            let row = {business_name:'', email:'', phone:'', website:'', city:'', niche:''};
            headers.forEach((h, idx) => { if (idx < cols.length) row[h] = cols[idx]; });
            if (row.business_name) {
                let fd = new URLSearchParams();
                fd.append('_csrf', _csrf);
                fd.append('action', 'quick_save');
                fd.append('source', 'csv_import');
                Object.keys(row).forEach(k => fd.append(k, row[k] || ''));
                fetch('../api/leads_save.php', {method:'POST', body: fd});
                imported++;
            }
        }
        showToast('Importing ' + imported + ' leads...');
        setTimeout(() => location.reload(), 1500);
    };
    reader.readAsText(file);
}

function h(s) {
    if (!s) return '';
    let d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
function escAttr(s) {
    if (!s) return '';
    return s.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}
</script>
