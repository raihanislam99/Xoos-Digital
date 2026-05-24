<?php
$campaigns = []; // In-memory for now; could be stored in DB
$templates = db_rows("SELECT * FROM outreach_templates WHERE type = 'email' ORDER BY use_count DESC");

$niches = db_rows("SELECT DISTINCT niche FROM leads WHERE niche != '' ORDER BY niche");
?>
<div style="display:flex;justify-content:flex-end;margin-bottom:1.5rem">
    <button class="btn btn-primary" onclick="showNewCampaign()"><i class="ti ti-plus"></i> New Campaign</button>
</div>

<div id="campaignList">
    <div class="card">
        <div class="empty-state">
            <i class="ti ti-mail-forward"></i>
            <p>No campaigns yet. Create your first email campaign to start reaching out to leads in bulk.</p>
            <button class="btn btn-primary mt-1" onclick="showNewCampaign()">Create Campaign</button>
        </div>
    </div>
</div>

<!-- New Campaign Modal -->
<div class="modal-overlay" id="campaignModal">
    <div class="modal" style="max-width:650px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h3 class="modal-title">📨 New Email Campaign</h3>
            <button onclick="closeModal('campaignModal')" style="background:none;border:none;color:var(--text3);font-size:1.3rem;cursor:pointer"><i class="ti ti-x"></i></button>
        </div>
        <form id="campaignForm" onsubmit="return startCampaign(event)">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label>Campaign Name</label>
                <input class="form-control" id="campName" placeholder="e.g. Q3 Restaurant Outreach" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Target Niche</label>
                    <select class="form-control" id="campNiche">
                        <option value="">All Niches</option>
                        <?php foreach ($niches as $n): ?>
                            <option value="<?= h($n['niche']) ?>"><?= h($n['niche']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Target Status</label>
                    <select class="form-control" id="campStatus">
                        <option value="">Any Status</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="replied">Replied</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Filter: No Website Only</label>
                <label class="switch" style="margin-top:4px">
                    <input type="checkbox" id="campNoWebsite">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Service to Pitch</label>
                    <select class="form-control" id="campService">
                        <option value="WordPress Website Design">WordPress Website Design</option>
                        <option value="WooCommerce E-Commerce Store">WooCommerce E-Commerce Store</option>
                        <option value="Creative Branding & Logo">Creative Branding & Logo</option>
                        <option value="SEO & Google Rankings">SEO & Google Rankings</option>
                        <option value="Facebook/Google Ads">Facebook/Google Ads</option>
                        <option value="Full Digital Package">Full Digital Package</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Template</label>
                    <select class="form-control" id="campTemplate">
                        <option value="">Generate with AI</option>
                        <?php foreach ($templates as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= h($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email Tone</label>
                    <select class="form-control" id="campTone">
                        <option value="professional">Professional</option>
                        <option value="friendly">Friendly</option>
                        <option value="direct">Direct</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Delay Between Emails</label>
                    <input class="form-control" id="campDelay" type="number" value="30" min="10" step="5">
                </div>
            </div>
            <div class="form-group">
                <label>Daily Limit</label>
                <input class="form-control" id="campDailyLimit" type="number" value="<?= (int)get_setting('daily_email_limit', '50') ?>" min="1">
            </div>
            <div id="campaignPreview" style="display:none;background:var(--bg3);border-radius:12px;padding:1rem;margin:1rem 0">
                <p style="font-size:0.82rem;color:var(--text2)"><span id="previewCount">0</span> leads will receive this campaign</p>
                <p style="font-size:0.72rem;color:var(--text3)">Estimated time: <span id="previewTime">0</span> minutes</p>
            </div>
            <div id="campProgress" style="display:none;margin:1rem 0">
                <div style="display:flex;justify-content:space-between;font-size:0.78rem;color:var(--text2);margin-bottom:4px">
                    <span><span id="sentCount">0</span> / <span id="totalCount">0</span> sent</span>
                    <span id="failedCount" style="color:var(--red)">0 failed</span>
                </div>
                <div style="height:6px;background:var(--bg3);border-radius:99px;overflow:hidden">
                    <div id="progressBar" style="width:0%;height:100%;background:var(--accent);border-radius:99px;transition:width 0.3s"></div>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="startCampBtn"><i class="ti ti-send"></i> Start Campaign</button>
                <button type="button" class="btn btn-danger" onclick="stopCampaign()" id="stopCampBtn" style="display:none"><i class="ti ti-player-stop"></i> Stop</button>
            </div>
        </form>
    </div>
</div>

<script>
let _csrf = '<?= csrf_token() ?>';
let isRunning = false;
let shouldStop = false;

document.getElementById('campNiche').addEventListener('change', updatePreview);
document.getElementById('campStatus').addEventListener('change', updatePreview);
document.getElementById('campNoWebsite').addEventListener('change', updatePreview);

function showNewCampaign() {
    document.getElementById('campaignForm').reset();
    document.getElementById('campaignPreview').style.display = 'none';
    document.getElementById('campProgress').style.display = 'none';
    document.getElementById('stopCampBtn').style.display = 'none';
    document.getElementById('startCampBtn').style.display = 'inline-flex';
    isRunning = false;
    shouldStop = false;
    openModal('campaignModal');
}

function updatePreview() {
    fetch('../api/leads_save.php?action=count&niche='+encodeURIComponent(document.getElementById('campNiche').value)+'&status='+encodeURIComponent(document.getElementById('campStatus').value)+'&nowebsite='+(document.getElementById('campNoWebsite').checked?1:0))
    .catch(()=>{}); // preview count - simplified
    let delay = parseInt(document.getElementById('campDelay').value) || 30;
    document.getElementById('previewTime').textContent = '~' + Math.ceil(100 * delay / 60) + ' min';
    document.getElementById('previewCount').textContent = '...';
    document.getElementById('campaignPreview').style.display = 'block';
}

function startCampaign(e) {
    e.preventDefault();
    if (isRunning) return;
    isRunning = true;
    shouldStop = false;
    document.getElementById('startCampBtn').style.display = 'none';
    document.getElementById('stopCampBtn').style.display = 'inline-flex';
    document.getElementById('campProgress').style.display = 'block';

    let niche = document.getElementById('campNiche').value;
    let status = document.getElementById('campStatus').value;
    let noWebsite = document.getElementById('campNoWebsite').checked;

    let params = { where: 'is_blacklisted=0 AND email IS NOT NULL AND email !=\'\'' };
    if (niche) params.where += ' AND niche=' + dbq(niche);
    if (status) params.where += ' AND status=' + dbq(status);
    if (noWebsite) params.where += ' AND has_website=0';

    let leads = [];
    try {
        leads = db_rows("SELECT id, business_name, email, niche, city FROM leads WHERE " + params.where + " LIMIT 100");
    } catch(e) { leads = []; }

    document.getElementById('totalCount').textContent = leads.length;
    let total = leads.length;
    let sent = 0;
    let failed = 0;
    let delay = parseInt(document.getElementById('campDelay').value) || 30;
    let dailyLimit = parseInt(document.getElementById('campDailyLimit').value) || 50;

    if (!total) {
        showToast('No matching leads found', 'error');
        stopCampaign();
        return;
    }

    function sendNext(index) {
        if (shouldStop || index >= total || sent >= dailyLimit) {
            document.getElementById('stopCampBtn').style.display = 'none';
            document.getElementById('startCampBtn').style.display = 'inline-flex';
            document.getElementById('startCampBtn').innerHTML = '<i class="ti ti-refresh"></i> New Campaign';
            isRunning = false;
            if (sent >= dailyLimit) showToast('Daily limit reached (' + sent + ' sent)', 'info');
            else showToast('Campaign complete: ' + sent + ' sent, ' + failed + ' failed', sent > 0 ? 'success' : 'error');
            return;
        }

        let lead = leads[index];
        let fd = new URLSearchParams();
        fd.append('_csrf', _csrf);
        fd.append('action', 'send');
        fd.append('lead_id', lead.id);
        fd.append('to_email', lead.email);
        fd.append('subject', 'Business opportunity for ' + lead.business_name);
        fd.append('body', 'Hi ' + lead.business_name + ' team,<br><br>We help businesses like yours get more customers online. Would you be open to a quick chat?<br><br>Best,<br>Raihan Islam<br>Xoos Digital');
        fd.append('email_type', 'cold');

        fetch('../api/leads_email.php', {method:'POST', body: fd})
        .then(r=>r.json()).then(j=>{
            if (j.success) sent++;
            else failed++;
            document.getElementById('sentCount').textContent = sent;
            document.getElementById('failedCount').textContent = failed + ' failed';
            document.getElementById('progressBar').style.width = ((sent + failed) / total * 100) + '%';
        }).catch(()=>{
            failed++;
            document.getElementById('failedCount').textContent = failed + ' failed';
        }).finally(()=>{
            let jitter = Math.floor(Math.random() * 15);
            setTimeout(() => sendNext(index + 1), (delay + jitter) * 1000);
        });
    }

    sendNext(0);
}

function stopCampaign() {
    shouldStop = true;
    isRunning = false;
    document.getElementById('stopCampBtn').style.display = 'none';
    document.getElementById('startCampBtn').style.display = 'inline-flex';
    document.getElementById('startCampBtn').innerHTML = '<i class="ti ti-refresh"></i> New Campaign';
}

function dbq(s) { return "'" + s.replace(/'/g,"\\'") + "'"; }

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
</script>
