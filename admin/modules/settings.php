<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

$saved = isset($_GET['saved']);

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    csrf_verify();
    foreach ($_POST as $key => $value) {
        if (in_array($key, ['action', '_csrf', 'active_tab'])) continue;
        set_setting($key, trim($value));
    }
    $tab = preg_replace('/[^a-z0-9]/', '', $_POST['active_tab'] ?? 'tab1');
    redirect('settings.php?saved=1&tab=' . $tab);
}

// Handle AJAX single-key save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_key']) && isset($_POST['provider'])) {
    header('Content-Type: application/json');
    csrf_verify();
    $provider = preg_replace('/[^a-z0-9_]/i', '', $_POST['provider']);
    set_setting('api_key_' . $provider, trim($_POST['api_key']));
    echo json_encode(['success' => true]);
    exit;
}

require_once __DIR__ . '/../inc/header.php';
?>

<?php if ($saved): ?>
    <div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:var(--green-bg);color:var(--green)">Settings saved successfully.</div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title">Site Settings</h1>
    <div style="display:flex;gap:8px">
        <a href="../setup.php" class="btn btn-secondary btn-sm"><i class="ti ti-database"></i> Setup</a>
        <a href="../reset-password.php" class="btn btn-secondary btn-sm"><i class="ti ti-key"></i> Credentials</a>
        <a href="../logout.php" class="btn btn-danger btn-sm"><i class="ti ti-logout"></i> Logout</a>
    </div>
</div>

<style>
.setting-pane { display:none }
.setting-pane.active { display:block }
</style>

<form method="post" action="settings.php">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="active_tab" id="activeTab" value="<?= h($_GET['tab'] ?? 'tab1') ?>">

    <div class="settings-tabs">
        <button type="button" class="settings-tab <?= (!isset($_GET['tab']) || $_GET['tab'] === 'tab1') ? 'active' : '' ?>" data-tab="tab1" onclick="switchTab(this,'tab1')">Profile &amp; Contact</button>
        <button type="button" class="settings-tab <?= ($_GET['tab'] ?? '') === 'tab2' ? 'active' : '' ?>" data-tab="tab2" onclick="switchTab(this,'tab2')">API &amp; Integrations</button>
        <button type="button" class="settings-tab <?= ($_GET['tab'] ?? '') === 'tab3' ? 'active' : '' ?>" data-tab="tab3" onclick="switchTab(this,'tab3')">Lead &amp; Outreach</button>
    </div>

    <!-- ════════════ TAB 1: Profile & Contact ════════════ -->
    <div id="tab1" class="settings-section <?= (!isset($_GET['tab']) || $_GET['tab'] === 'tab1') ? 'active' : '' ?>">

        <div class="settings-card">
            <div class="settings-card-title">User Profile</div>
            <div style="display:flex;align-items:center;gap:1.25rem;margin-bottom:1.5rem">
                <div class="avatar-upload" title="Upload avatar (placeholder)"><i class="ti ti-camera"></i></div>
                <div>
                    <div style="font-size:1rem;font-weight:700;color:var(--text)"><?= h(get_setting('founder_name', 'Raihan Islam')) ?></div>
                    <div style="font-size:0.75rem;color:var(--neon-green);margin-top:2px"><?= h(get_setting('founder_title', 'Founder & Creative Director')) ?></div>
                    <div style="font-size:0.7rem;color:var(--text3);margin-top:2px"><?= h(get_setting('contact_email', 'xoosdigital@gmail.com')) ?></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Founder Name</label>
                    <input class="form-control" name="founder_name" value="<?= h(get_setting('founder_name', 'Raihan Islam')) ?>">
                </div>
                <div class="form-group">
                    <label>Founder Title</label>
                    <input class="form-control" name="founder_title" value="<?= h(get_setting('founder_title', 'Founder & Creative Director')) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Founder Bio</label>
                <textarea class="form-control" name="founder_bio" rows="3"><?= h(get_setting('founder_bio', '')) ?></textarea>
                <div class="flex" style="margin-top:4px;gap:4px">
                    <button type="button" class="btn btn-ai btn-sm" onclick="aiImproveBio()"><i class="ti ti-sparkles"></i> Improve with AI</button>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-title">Contact Information</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Contact Email</label>
                    <input class="form-control" name="contact_email" value="<?= h(get_setting('contact_email', 'xoosdigital@gmail.com')) ?>">
                </div>
                <div class="form-group">
                    <label>Phone / WhatsApp</label>
                    <input class="form-control" name="contact_phone" value="<?= h(get_setting('contact_phone', '+8801572932943')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>WhatsApp Number (digits only)</label>
                    <input class="form-control" name="whatsapp_number" value="<?= h(get_setting('whatsapp_number', '8801600008085')) ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input class="form-control" name="address" value="<?= h(get_setting('address', 'Khilgaon, Dhaka, Bangladesh')) ?>">
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-title">Social Links</div>
            <div class="form-group">
                <label>Facebook URL</label>
                <input class="form-control" name="facebook_url" value="<?= h(get_setting('facebook_url', 'https://facebook.com/xoosdigital')) ?>">
            </div>
            <div class="form-group">
                <label>LinkedIn URL</label>
                <input class="form-control" name="linkedin_url" value="<?= h(get_setting('linkedin_url', 'https://linkedin.com/in/ri-raihanislam99')) ?>">
            </div>
            <div class="form-group">
                <label>Instagram URL</label>
                <input class="form-control" name="instagram_url" value="<?= h(get_setting('instagram_url', '')) ?>">
            </div>
            <div class="form-group">
                <label>Fiverr URL</label>
                <input class="form-control" name="fiverr_url" value="<?= h(get_setting('fiverr_url', '')) ?>">
            </div>
            <div class="form-group">
                <label>Upwork URL</label>
                <input class="form-control" name="upwork_url" value="<?= h(get_setting('upwork_url', '')) ?>">
            </div>
        </div>
    </div>

    <!-- ════════════ TAB 2: API & Integrations ════════════ -->
    <div id="tab2" class="settings-section <?= ($_GET['tab'] ?? '') === 'tab2' ? 'active' : '' ?>">

        <div class="settings-card">
            <div class="settings-card-title">Web3Forms</div>
            <div class="form-group">
                <label>Access Key <span class="text-muted">(read-only)</span></label>
                <input class="form-control" value="<?= WEB3FORMS_ACCESS_KEY ?>" readonly style="color:var(--text3)">
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-title">API Keys</div>
            <?php $presets = ai_provider_presets(); ?>
            <?php foreach ($presets as $pk => $pv): if ($pk === 'custom') continue;
                $savedKey = get_setting('api_key_' . $pk, '');
            ?>
                <div class="form-group" style="display:flex;align-items:center;gap:8px;width:100%;margin-bottom:0.6rem">
                    <label style="margin-bottom:0;white-space:nowrap;min-width:90px;font-size:0.8rem;color:var(--text2)"><?= $pv['label'] ?></label>
                    <input class="form-control" type="text" name="api_key_<?= $pk ?>" id="key_<?= $pk ?>" value="<?= h($savedKey) ?>"
                        <?= $savedKey ? 'readonly' : 'placeholder="sk-..."' ?>
                        style="flex:1;<?= $savedKey ? 'color:var(--text2);font-family:monospace;font-size:0.8rem' : '' ?>"
                        onfocus="this.removeAttribute('readonly');this.value=''"
                        onblur="if(!this.value.trim()){this.value='<?= str_replace(["\\","'"],["\\\\","\\'"],$savedKey) ?>';this.setAttribute('readonly','')}">
                    <a href="#" onclick="saveApiKey('<?= $pk ?>');return false" title="Save <?= $pv['label'] ?> key" style="color:var(--text3);text-decoration:none;font-size:1.1rem;display:flex"><i class="ti ti-device-floppy"></i></a>
                    <?php if ($pv['url']): ?>
                        <a href="<?= $pv['url'] ?>" target="_blank" title="Get <?= $pv['label'] ?> API key" style="color:var(--text3);text-decoration:none;font-size:1.1rem;display:flex"><i class="ti ti-external-link"></i></a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="settings-card">
            <div class="settings-card-title">AI Assignment</div>

            <div class="form-group">
                <label>Chatbot AI</label>
                <select class="form-control" name="ai_provider_chatbot">
                    <?php foreach ($presets as $pk => $pv): ?>
                        <option value="<?= $pk ?>" <?= get_setting('ai_provider_chatbot','groq') === $pk ? 'selected' : '' ?>><?= $pv['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Blog AI</label>
                <select class="form-control" name="ai_provider_blog">
                    <?php foreach ($presets as $pk => $pv): ?>
                        <option value="<?= $pk ?>" <?= get_setting('ai_provider_blog','groq') === $pk ? 'selected' : '' ?>><?= $pv['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Admin AI <span class="text-muted">(Services, Packages, Testimonials, FAQ, etc.)</span></label>
                <select class="form-control" name="ai_provider_admin">
                    <?php foreach ($presets as $pk => $pv): ?>
                        <option value="<?= $pk ?>" <?= get_setting('ai_provider_admin','groq') === $pk ? 'selected' : '' ?>><?= $pv['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Post AI <span class="text-muted">(LinkedIn &amp; Facebook Post Generator)</span></label>
                <select class="form-control" name="ai_provider_posts">
                    <?php foreach ($presets as $pk => $pv): ?>
                        <option value="<?= $pk ?>" <?= get_setting('ai_provider_posts','groq') === $pk ? 'selected' : '' ?>><?= $pv['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
                <label>Chatbot Enabled</label>
                <div style="display:flex;align-items:center;gap:0.75rem;margin-top:4px">
                    <label class="switch">
                        <input type="checkbox" name="chatbot_enabled" value="1" onchange="document.getElementById('chatbotStatusText').textContent = this.checked ? 'Enabled' : 'Disabled';" <?= get_setting('chatbot_enabled', '1') === '1' ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                    <span id="chatbotStatusText" style="font-size:0.82rem;color:var(--text2)"><?= get_setting('chatbot_enabled', '1') === '1' ? 'Enabled' : 'Disabled' ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════ TAB 3: Lead & Outreach ════════════ -->
    <div id="tab3" class="settings-section <?= ($_GET['tab'] ?? '') === 'tab3' ? 'active' : '' ?>">

        <div class="settings-card">
            <div class="settings-card-title">SMTP Configuration</div>
            <div class="form-row">
                <div class="form-group">
                    <label>SMTP Host</label>
                    <input class="form-control" name="smtp_host" value="<?= h(get_setting('smtp_host', '')) ?>" placeholder="smtp.gmail.com">
                </div>
                <div class="form-group">
                    <label>SMTP Port</label>
                    <input class="form-control" name="smtp_port" value="<?= h(get_setting('smtp_port', '587')) ?>" placeholder="587">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>SMTP Username</label>
                    <input class="form-control" name="smtp_user" value="<?= h(get_setting('smtp_user', '')) ?>" placeholder="your@email.com">
                </div>
                <div class="form-group">
                    <label>SMTP Password</label>
                    <input class="form-control" type="password" name="smtp_pass" value="<?= h(get_setting('smtp_pass', '')) ?>" placeholder="App password or SMTP key">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>From Name</label>
                    <input class="form-control" name="smtp_from_name" value="<?= h(get_setting('smtp_from_name', 'Raihan Islam — Xoos Digital')) ?>">
                </div>
                <div class="form-group">
                    <label>From Email</label>
                    <input class="form-control" name="smtp_from_email" value="<?= h(get_setting('smtp_from_email', 'raihan@xoosdigital.com')) ?>">
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:8px">
                <button type="button" class="btn btn-secondary btn-sm" onclick="testSmtp()"><i class="ti ti-send"></i> Test Connection</button>
                <span id="smtpTestResult" style="font-size:0.78rem;color:var(--text3);align-self:center"></span>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-title">Sending Limits</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Daily Email Limit</label>
                    <input class="form-control" name="daily_email_limit" type="number" value="<?= h(get_setting('daily_email_limit', '50')) ?>">
                </div>
                <div class="form-group">
                    <label>Email Delay (seconds)</label>
                    <input class="form-control" name="email_delay_seconds" type="number" value="<?= h(get_setting('email_delay_seconds', '30')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>WhatsApp Delay (seconds)</label>
                    <input class="form-control" name="wa_delay_seconds" type="number" value="<?= h(get_setting('wa_delay_seconds', '15')) ?>">
                </div>
                <div class="form-group">
                    <label>Google Places API Key</label>
                    <input class="form-control" name="google_places_api_key" value="<?= h(get_setting('google_places_api_key', '')) ?>" placeholder="AIza..." style="font-family:monospace;font-size:0.78rem">
                </div>
            </div>
            <div style="font-size:0.72rem;color:var(--text3);margin-top:8px">Required for the Lead Finder. Get a key at <a href="https://console.cloud.google.com/apis/credentials" target="_blank" style="color:var(--accent)">Google Cloud Console</a>, <strong>enable Places API</strong>, and <strong>enable billing</strong> (the $200/mo free credit covers it).</div>
        </div>

        <div class="settings-card">
            <div class="settings-card-title">AI Configuration</div>
            <p style="font-size:0.78rem;color:var(--text3);margin-bottom:1rem">Uses the same AI provider as Admin AI (set in API & Integrations tab).</p>
            <div class="form-group">
                <label>Default Tone</label>
                <select class="form-control" name="lead_default_tone">
                    <option value="professional" <?= get_setting('lead_default_tone','professional') === 'professional' ? 'selected' : '' ?>>Professional</option>
                    <option value="friendly" <?= get_setting('lead_default_tone','professional') === 'friendly' ? 'selected' : '' ?>>Friendly & Casual</option>
                    <option value="direct" <?= get_setting('lead_default_tone','professional') === 'direct' ? 'selected' : '' ?>>Direct & Bold</option>
                </select>
            </div>
            <div class="form-group">
                <label>Default Language</label>
                <select class="form-control" name="lead_default_language">
                    <option value="English" <?= get_setting('lead_default_language','English') === 'English' ? 'selected' : '' ?>>English</option>
                    <option value="Bangla" <?= get_setting('lead_default_language','English') === 'Bangla' ? 'selected' : '' ?>>Bangla</option>
                    <option value="Banglish" <?= get_setting('lead_default_language','English') === 'Banglish' ? 'selected' : '' ?>>Banglish</option>
                </select>
            </div>
            <div class="form-group">
                <label>Default Service</label>
                <select class="form-control" name="lead_default_service">
                    <option value="WordPress Website Design" <?= get_setting('lead_default_service','WordPress Website Design') === 'WordPress Website Design' ? 'selected' : '' ?>>WordPress Website Design</option>
                    <option value="WooCommerce E-Commerce Store" <?= get_setting('lead_default_service','WordPress Website Design') === 'WooCommerce E-Commerce Store' ? 'selected' : '' ?>>WooCommerce Store</option>
                    <option value="Creative Branding & Logo" <?= get_setting('lead_default_service','WordPress Website Design') === 'Creative Branding & Logo' ? 'selected' : '' ?>>Branding & Logo</option>
                    <option value="SEO & Google Rankings" <?= get_setting('lead_default_service','WordPress Website Design') === 'SEO & Google Rankings' ? 'selected' : '' ?>>SEO & Google</option>
                    <option value="Facebook/Google Ads" <?= get_setting('lead_default_service','WordPress Website Design') === 'Facebook/Google Ads' ? 'selected' : '' ?>>Facebook/Google Ads</option>
                </select>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-title">Email Signature</div>
            <div class="form-group">
                <textarea class="form-control" name="lead_signature" rows="5"><?= h(get_setting('lead_signature', "Raihan Islam\nFounder, Xoos Digital\nxoosdigital.com\n+880 1572-932943\nDhaka, Bangladesh")) ?></textarea>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-title">Data Management</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="../modules/leads.php?view=my_leads&export=csv" class="btn btn-secondary btn-sm"><i class="ti ti-file-export"></i> Export All Leads CSV</a>
                <button type="button" class="btn btn-danger btn-sm" onclick="clearAllLeads()"><i class="ti ti-trash"></i> Clear All Leads</button>
            </div>
        </div>
    </div>

    <div class="form-actions" style="margin-top:1.5rem">
        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save All Settings</button>
    </div>
</form>

<script>
function testSmtp() {
    var btn = event.target;
    var result = document.getElementById('smtpTestResult');
    btn.disabled = true;
    result.textContent = 'Testing...';
    var csrf = document.querySelector('input[name="_csrf"]').value;
    fetch('../api/leads_email.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_csrf=' + encodeURIComponent(csrf) + '&action=test'
    }).then(function(r) { return r.json(); }).then(function(j) {
        btn.disabled = false;
        result.textContent = j.success ? '✓ ' + j.message : '✗ ' + j.error;
        result.style.color = j.success ? 'var(--green)' : 'var(--red)';
    }).catch(function() {
        btn.disabled = false;
        result.textContent = '✗ Connection failed';
        result.style.color = 'var(--red)';
    });
}
function clearAllLeads() {
    if (!confirm('Are you sure? This will permanently delete ALL leads.')) return;
    if (!confirm('This CANNOT be undone. Continue?')) return;
    var csrf = document.querySelector('input[name="_csrf"]').value;
    fetch('../api/leads_save.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_csrf=' + encodeURIComponent(csrf) + '&action=bulk_action&bulk_action=delete&ids=all'
    }).then(function(r) { return r.json(); }).then(function(j) {
        alert(j.message || 'Cleared');
        location.reload();
    });
}
</script>

<script>
function saveApiKey(provider) {
    var input = document.getElementById('key_' + provider);
    var val = input.value.trim();
    if (!val) { alert('Enter an API key first'); return; }
    var icon = input.parentNode.querySelector('.ti-device-floppy').parentNode;
    var orig = icon.innerHTML;
    icon.innerHTML = '<span class="ai-spinner"></span>';
    var csrf = document.querySelector('input[name="_csrf"]').value;
    fetch('settings.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_csrf=' + encodeURIComponent(csrf) + '&api_key=' + encodeURIComponent(val) + '&provider=' + encodeURIComponent(provider)
    }).then(function(r) { return r.json(); }).then(function(j) {
        icon.innerHTML = '<i class="ti ti-check" style="color:var(--green)"></i>';
        setTimeout(function() { icon.innerHTML = orig; }, 2000);
        // Update onblur to restore the newly saved key
        var escaped = val.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/'/g,'&#39;').replace(/"/g,'&#34;');
        input.setAttribute('onblur', "if(!this.value.trim()){this.value='" + escaped + "';this.setAttribute('readonly','')}");
        input.setAttribute('readonly', '');
        input.style.color = 'var(--text2)';
        input.style.fontFamily = 'monospace';
        input.style.fontSize = '0.8rem';
    }).catch(function() {
        icon.innerHTML = orig;
        alert('Failed to save key');
    });
    return false;
}
function switchTab(btn, tabId) {
    document.querySelectorAll('.settings-tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.settings-section').forEach(function(p) { p.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById(tabId).classList.add('active');
    document.getElementById('activeTab').value = tabId;
}
function aiImproveBio() {
    var field = document.querySelector('textarea[name="founder_bio"]');
    var text = field.value.trim();
    if (!text) { alert('Enter a bio first'); return; }
    var btn = event.target;
    btn.innerHTML = '<span class="ai-spinner"></span>';
    btn.disabled = true;
    fetch('../ai.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({task:'improve_bio', context: text})
    }).then(function(r) { return r.json(); }).then(function(j) {
        btn.innerHTML = '<i class="ti ti-sparkles"></i> Improve with AI';
        btn.disabled = false;
        if (j.success) field.value = j.data;
    }).catch(function() {
        btn.innerHTML = '<i class="ti ti-sparkles"></i> Improve with AI';
        btn.disabled = false;
    });
}
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
