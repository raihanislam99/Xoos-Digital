<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

$saved = isset($_GET['saved']);

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    csrf_verify();
    $uploadError = null;
    
    foreach ($_POST as $key => $value) {
        if (in_array($key, ['action', '_csrf', 'active_tab'])) continue;
        set_setting($key, trim($value));
    }
    
    // Handle profile photo upload (only when a file is actually selected)
    if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = upload_profile_photo($_FILES['profile_photo']);
        if ($uploadResult['success']) {
            set_setting('profile_photo', $uploadResult['path']);
        } else {
            $uploadError = $uploadResult['error'];
        }
    }
    
    $tab = preg_replace('/[^a-z0-9]/', '', $_POST['active_tab'] ?? 'tab1');
    
    // Check if it's an AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        $response = ['ok' => true, 'tab' => $tab];
        if ($uploadError) {
            $response['ok'] = false;
            $response['error'] = $uploadError;
        }
        json_response($response);
    }
    
    if ($uploadError) {
        $_SESSION['flash_msg'] = $uploadError;
        $_SESSION['flash_type'] = 'error';
    }
    redirect('settings.php?saved=1&tab=' . $tab);
}

function upload_profile_photo($file) {
    // Check for upload errors first
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        $errorMsg = $errors[$file['error']] ?? 'Unknown upload error';
        return ['success' => false, 'error' => $errorMsg];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp','avif'];
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP, AVIF.'];
    }

    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            return ['success' => false, 'error' => 'Failed to create uploads directory.'];
        }
    }

    // Make sure the directory is writable
    if (!is_writable($dir)) {
        return ['success' => false, 'error' => 'Uploads directory is not writable.'];
    }

    $name = 'profile_' . uniqid('', true);
    $finalFile = $name . '.' . $ext;
    $dest = $dir . '/' . $finalFile;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'error' => 'Failed to save uploaded file.'];
    }

    $filepath = 'admin/uploads/' . $finalFile;
    return ['success' => true, 'path' => $filepath, 'url' => BASE_URL . '/' . $filepath];
}

// Handle personal API key save for team members
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_own_key') {
    csrf_verify();
    $email = supabase_user_email();
    $ownKey = trim($_POST['own_api_key'] ?? '');
    $ownProvider = preg_replace('/[^a-z0-9_]/', '', $_POST['own_api_provider'] ?? 'groq');
    $success = false;
    try {
        db_update('team_members', [
            'own_api_key' => $ownKey,
            'own_api_provider' => $ownProvider,
        ], 'email = ?', [$email]);
        $_SESSION['flash_msg'] = 'Your API key has been updated.';
        $_SESSION['flash_type'] = 'success';
        $success = true;
    } catch (Exception $e) {
        $_SESSION['flash_msg'] = 'Error: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
    }
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        json_response(['ok' => $success]);
    }
    
    redirect('settings.php?saved=1&tab=tab1');
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
.ai-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid var(--accent);
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
    margin-right: 6px;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
.profile-photo-wrap {
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    flex-shrink: 0;
    overflow: hidden;
    border: 2px solid var(--border);
    cursor: pointer;
}
.profile-photo-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
}
.profile-photo-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), rgba(204,255,0,0.3));
    color: #080b12;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.5rem;
    font-family: 'Orbitron', sans-serif;
}
.profile-photo-overlay {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(0,0,0,0.6);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    opacity: 0;
    transition: opacity 0.2s;
    cursor: pointer;
    color: #fff;
    font-size: 0.55rem;
    font-weight: 600;
    text-align: center;
}
.profile-photo-overlay i {
    font-size: 1.1rem;
}
.profile-photo-wrap:hover .profile-photo-overlay {
    opacity: 1;
}
</style>

<form method="post" action="settings.php" enctype="multipart/form-data">
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
                <div class="profile-photo-wrap" id="profilePhotoWrap">
                    <?php $currPhoto = get_setting('profile_photo', ''); ?>
                    <?php if ($currPhoto): ?>
                        <img src="<?= h(image_url($currPhoto)) ?>" alt="" id="profilePhotoPreview">
                    <?php else: ?>
                        <div class="profile-photo-placeholder" id="profilePhotoPreview"><?= strtoupper(substr(get_setting('founder_name', 'A'), 0, 1)) ?></div>
                    <?php endif; ?>
                    <label class="profile-photo-overlay" for="profilePhotoInput">
                        <i class="ti ti-camera"></i>
                        <span>Change Photo</span>
                    </label>
                    <input type="file" id="profilePhotoInput" name="profile_photo" accept="image/jpeg,image/png,image/webp,image/gif,image/avif" style="display:none" onchange="previewProfilePhoto(this)">
                </div>
                <div>
                    <div style="font-size:1rem;font-weight:700;color:var(--text)"><?= h(get_setting('founder_name', 'Raihan Islam')) ?></div>
                    <div style="font-size:0.75rem;color:var(--accent);margin-top:2px"><?= h(get_setting('founder_title', 'Founder & Creative Director')) ?></div>
                    <div style="font-size:0.7rem;color:var(--text3);margin-top:2px"><?= h(supabase_user_email()) ?></div>
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

        <?php
        // Personal API key section for team members
        $currentEmail = supabase_user_email();
        $ownKeyRow = null;
        try {
            $rows = db_rows("SELECT own_api_key, own_api_provider, onboarding_complete FROM team_members WHERE email = ?", [$currentEmail]);
            $ownKeyRow = $rows[0] ?? null;
        } catch (Exception $e) {
            try {
                reload_pgrst_schema();
                $rows = db_rows("SELECT own_api_key, own_api_provider, onboarding_complete FROM team_members WHERE email = ?", [$currentEmail]);
                $ownKeyRow = $rows[0] ?? null;
            } catch (Exception $e2) {}
        }
        if ($ownKeyRow):
            $presets = ai_provider_presets();
        ?>
        <div class="settings-card">
            <div class="settings-card-title">Your Personal AI Key</div>
            <p style="font-size:0.78rem;color:var(--text3);margin-bottom:1rem">This key is used for AI features you access. Leave empty to use the team default.</p>
            <form method="post" action="settings.php" onsubmit="return saveOwnApiKey(event)">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="save_own_key">
                <div class="form-row">
                    <div class="form-group">
                        <label>AI Provider</label>
                        <select class="form-control" name="own_api_provider">
                            <?php foreach ($presets as $pk => $pv): if ($pk === 'custom') continue; ?>
                                <option value="<?= $pk ?>" <?= ($ownKeyRow['own_api_provider'] ?: 'groq') === $pk ? 'selected' : '' ?>><?= $pv['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Your API Key</label>
                        <input class="form-control" name="own_api_key" type="text" value="<?= h($ownKeyRow['own_api_key'] ?? '') ?>" placeholder="sk-... or gsk_...">
                    </div>
                </div>
                <div class="form-actions" style="margin-top:0;padding-top:0.75rem;border-top:none">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-device-floppy"></i> Save My Key</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

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

            <div class="form-group">
                <label>Image Generation Style <span class="text-muted">(applied to all featured image prompts)</span></label>
                <textarea class="form-control" name="image_gen_style" rows="4" placeholder="e.g. Vibrant neon colors, glowing gradients, futuristic UI design, high-tech holographic overlays, cinematic lighting"><?= h(get_setting('image_gen_style', '')) ?></textarea>
            </div>

            <div class="form-group" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
                <label>Chatbot Enabled</label>
                <div style="display:flex;align-items:center;gap:0.75rem;margin-top:4px">
                    <label class="switch">
                        <input type="hidden" name="chatbot_enabled" value="0">
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
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action="settings.php"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Only handle the main save action, not the own key save
            const actionInput = form.querySelector('input[name="action"]');
            if (actionInput && actionInput.value === 'save_own_key') {
                return; // Let the saveOwnApiKey handle it
            }
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="ai-spinner"></span> Saving...';

            fetch('settings.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                if (data.ok) {
                    showToast('Settings saved successfully!');
                    // Update the page without full reload? For now, just a light refresh
                    setTimeout(function() {
                        window.location.href = 'settings.php?saved=1&tab=' + data.tab;
                    }, 600);
                } else {
                    showToast(data.error || 'Error saving settings!', 'error');
                }
            })
            .catch(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                showToast('Error saving settings!', 'error');
            });
        });
    }
});

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

function saveOwnApiKey(e) {
    e.preventDefault();
    var form = e.target;
    var fd = new FormData(form);
    var submitBtn = form.querySelector('button[type="submit"]');
    var originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="ai-spinner"></span> Saving...';
    
    fetch('settings.php', {
        method: 'POST',
        body: fd,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        if (data.ok) {
            showToast('Your API key has been saved!');
            setTimeout(function() {
                window.location.href = 'settings.php?saved=1&tab=tab1';
            }, 600);
        } else {
            showToast('Error saving your API key!', 'error');
        }
    })
    .catch(function() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        showToast('Error saving your API key!', 'error');
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
function previewProfilePhoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var wrap = document.getElementById('profilePhotoWrap');
            var preview = document.getElementById('profilePhotoPreview');
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                // Replace just the preview element, keep the input and overlay!
                var newImg = document.createElement('img');
                newImg.src = e.target.result;
                newImg.id = 'profilePhotoPreview';
                newImg.alt = '';
                // Replace the placeholder with the image
                preview.replaceWith(newImg);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
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
        else alert(j.error || 'AI improvement failed');
    }).catch(function() {
        btn.innerHTML = '<i class="ti ti-sparkles"></i> Improve with AI';
        btn.disabled = false;
        alert('AI improvement failed — check console');
    });
}
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
