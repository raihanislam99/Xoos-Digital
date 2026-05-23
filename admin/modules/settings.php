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
</div>

<style>
.setting-tabs { display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:1.5rem }
.setting-tab { padding:0.65rem 1.25rem;font-family:'Orbitron',sans-serif;font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3);cursor:pointer;border-bottom:2px solid transparent;transition:all 0.15s;background:none;border-left:none;border-right:none;border-top:none }
.setting-tab:hover { color:var(--text) }
.setting-tab.active { color:var(--accent);border-bottom-color:var(--accent) }
.setting-pane { display:none }
.setting-pane.active { display:block }
</style>

<form method="post" action="settings.php">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="active_tab" id="activeTab" value="<?= h($_GET['tab'] ?? 'tab1') ?>">

    <div class="setting-tabs">
        <button type="button" class="setting-tab <?= (!isset($_GET['tab']) || $_GET['tab'] === 'tab1') ? 'active' : '' ?>" onclick="switchTab(this,'tab1')">Contact Info</button>
        <button type="button" class="setting-tab <?= ($_GET['tab'] ?? '') === 'tab2' ? 'active' : '' ?>" onclick="switchTab(this,'tab2')">Social Links</button>
        <button type="button" class="setting-tab <?= ($_GET['tab'] ?? '') === 'tab3' ? 'active' : '' ?>" onclick="switchTab(this,'tab3')">Stats</button>
        <button type="button" class="setting-tab <?= ($_GET['tab'] ?? '') === 'tab4' ? 'active' : '' ?>" onclick="switchTab(this,'tab4')">API & AI</button>
    </div>

    <div id="tab1" class="setting-pane <?= (!isset($_GET['tab']) || $_GET['tab'] === 'tab1') ? 'active' : '' ?>">
        <div class="card">
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
                <textarea class="form-control" name="founder_bio" rows="4"><?= h(get_setting('founder_bio', '')) ?></textarea>
                <div class="flex" style="margin-top:4px;gap:4px">
                    <button type="button" class="btn btn-ai btn-sm" onclick="aiImproveBio()"><i class="ti ti-sparkles"></i> Improve with AI</button>
                </div>
            </div>
        </div>
    </div>

    <div id="tab2" class="setting-pane <?= ($_GET['tab'] ?? '') === 'tab2' ? 'active' : '' ?>">
        <div class="card">
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

    <div id="tab3" class="setting-pane <?= ($_GET['tab'] ?? '') === 'tab3' ? 'active' : '' ?>">
        <div class="card">
            <div class="form-row">
                <div class="form-group">
                    <label>Brands Designed</label>
                    <input class="form-control" name="stat_brands" type="number" value="<?= h(get_setting('stat_brands', '70')) ?>">
                </div>
                <div class="form-group">
                    <label>Websites Launched</label>
                    <input class="form-control" name="stat_websites" type="number" value="<?= h(get_setting('stat_websites', '40')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Clients Served</label>
                    <input class="form-control" name="stat_clients" type="number" value="<?= h(get_setting('stat_clients', '80')) ?>">
                </div>
                <div class="form-group">
                    <label>Years Experience</label>
                    <input class="form-control" name="stat_years" type="number" value="<?= h(get_setting('stat_years', '5')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Countries</label>
                    <input class="form-control" name="stat_countries" type="number" value="<?= h(get_setting('stat_countries', '12')) ?>">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div></div>
                </div>
            </div>
        </div>
    </div>

    <div id="tab4" class="setting-pane <?= ($_GET['tab'] ?? '') === 'tab4' ? 'active' : '' ?>">
        <div class="card">
            <div class="form-group">
                <label>Web3Forms Key <span class="text-muted">(read-only)</span></label>
                <input class="form-control" value="f5792651-e546-4e3e-a788-216ec76ab809" readonly style="color:var(--text3)">
            </div>

            <?php $presets = ai_provider_presets(); ?>

            <hr style="border:none;border-top:1px solid var(--border);margin:1.25rem 0">
            <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3);margin-bottom:1rem">🔑 API Keys</h3>
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

            <hr style="border:none;border-top:1px solid var(--border);margin:1.25rem 0">
            <h3 style="font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text3);margin-bottom:1rem">⚙️ AI Assignment</h3>

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

    <div class="form-actions" style="margin-top:1.5rem">
        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save All Settings</button>
    </div>
</form>

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
    document.querySelectorAll('.setting-tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.setting-pane').forEach(function(p) { p.classList.remove('active'); });
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
