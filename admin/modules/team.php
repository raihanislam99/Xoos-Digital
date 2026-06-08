<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

// ── Post profiles for sub-permissions ──
$postProfiles = [];
try {
    $postProfiles = get_all('post_profiles', 'platform ASC, name ASC');
} catch (Exception $e) {}

// ── Auto-create team_members table if it doesn't exist ──
$pdo = db();
$isRestMode = ($pdo instanceof SupabaseRestDB);
$tableExists = false;

// Check if table exists — try direct query first, then info_schema via mgmt API
try {
    $pdo->query("SELECT count(*) FROM team_members");
    $tableExists = true;
} catch (Exception $e) {}

// If REST mode, also check via management API to be sure
if (!$tableExists && $isRestMode && defined('SUPABASE_MANAGEMENT_API_KEY') && SUPABASE_MANAGEMENT_API_KEY) {
    try {
        $ref = parse_url(SUPABASE_URL, PHP_URL_HOST);
        $ref = explode('.', $ref)[0];
        $checkUrl = 'https://api.supabase.com/v1/projects/' . $ref . '/database/query';
        $ch = curl_init($checkUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['query' => "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'team_members')"]),
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . SUPABASE_MANAGEMENT_API_KEY, 'Content-Type: application/json'],
            CURLOPT_TIMEOUT => 15,
        ]);
        $checkRes = curl_exec($ch);
        $checkCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($checkCode === 200) {
            $checkData = json_decode($checkRes, true);
            if (!empty($checkData) && !empty($checkData[0]['exists']) && $checkData[0]['exists'] === true) {
                $tableExists = true;
            }
        }
    } catch (Exception $e) {}
}

$sql = "CREATE TABLE IF NOT EXISTS team_members (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL DEFAULT '',
    role VARCHAR(50) NOT NULL DEFAULT 'editor',
    permissions TEXT DEFAULT '{}',
    avatar_url VARCHAR(500) DEFAULT '',
    supabase_uid VARCHAR(255) DEFAULT '',
    is_active SMALLINT DEFAULT 1,
    invited_at TIMESTAMP DEFAULT NULL,
    onboarding_complete SMALLINT DEFAULT 0,
    own_api_key VARCHAR(500) DEFAULT '',
    own_api_provider VARCHAR(50) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$alterSql = "ALTER TABLE team_members
    ADD COLUMN IF NOT EXISTS invited_at TIMESTAMP DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS onboarding_complete SMALLINT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS own_api_key VARCHAR(500) DEFAULT '',
    ADD COLUMN IF NOT EXISTS own_api_provider VARCHAR(50) DEFAULT ''";

// Helper to execute SQL via Management API
$execMgmt = function($querySql) use (&$ref, &$mgmtUrlBase) {
    $newRef = null;
    $newBase = null;
    if (!isset($mgmtUrlBase)) {
        $newRef = parse_url(SUPABASE_URL, PHP_URL_HOST);
        $newRef = explode('.', $newRef)[0];
        $newBase = 'https://api.supabase.com/v1/projects/' . $newRef . '/database/query';
        $ref = $newRef;
        $mgmtUrlBase = $newBase;
    }
    $ch = curl_init($mgmtUrlBase);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['query' => $querySql]),
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . SUPABASE_MANAGEMENT_API_KEY, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT => 15,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
};

$tableWasCreated = false;

// Try direct PDO if available
if (!$isRestMode) {
    if (!$tableExists) {
        try {
            $pdo->exec($sql);
            $tableExists = true;
            $tableWasCreated = true;
        } catch (Exception $e) {}
    }
    // Always try to add new columns (table may already exist from previous version)
    try {
        $pdo->exec($alterSql);
    } catch (Exception $e) {}
}

// Fallback: supabase Management API for CREATE TABLE
if (!$tableExists && defined('SUPABASE_MANAGEMENT_API_KEY') && SUPABASE_MANAGEMENT_API_KEY) {
    try {
        $code = $execMgmt($sql);
        if ($code === 201) {
            $tableExists = true;
            $tableWasCreated = true;
        }
    } catch (Exception $e) {}
}

// Always run ALTER TABLE via Management API (for existing tables missing new columns)
if (defined('SUPABASE_MANAGEMENT_API_KEY') && SUPABASE_MANAGEMENT_API_KEY) {
    try {
        $execMgmt($alterSql);
    } catch (Exception $e) {}
}

// Reload PostgREST schema cache so it picks up new columns
if (defined('SUPABASE_MANAGEMENT_API_KEY') && SUPABASE_MANAGEMENT_API_KEY) {
    try {
        if (!isset($ref)) {
            $ref = parse_url(SUPABASE_URL, PHP_URL_HOST);
            $ref = explode('.', $ref)[0];
        }
        $ch2 = curl_init('https://api.supabase.com/v1/projects/' . $ref . '/database/query');
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['query' => "NOTIFY pgrst, 'reload schema'"]),
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . SUPABASE_MANAGEMENT_API_KEY, 'Content-Type: application/json'],
            CURLOPT_TIMEOUT => 15,
        ]);
        curl_exec($ch2);
        curl_close($ch2);
    } catch (Exception $e) {}
}

// If table was created just now, redirect so schema cache refreshes
if ($tableWasCreated) {
    $qs = $_GET ? http_build_query($_GET) . '&_tb=' . time() : '_tb=' . time();
    $uri = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $uri . '?' . $qs);
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $member = get_row('team_members', $id);
    if ($member && !empty($member['supabase_uid'])) {
        try {
            $supabase = Supabase::getInstance();
            $supabase->authAdminDeleteUser($member['supabase_uid']);
        } catch (\Exception $e) {}
    }
    delete('team_members', $id);
    redirect('team.php');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    try {
        $id = (int)($_POST['id'] ?? 0);
        $permissions = [];
        $allModules = [
            'dashboard','tasks','notes','blog','post_generator','outreach',
            'quote_invoice','messages','services','packages','testimonials',
            'faq','portfolio','brands','media','settings','team'
        ];
        foreach ($allModules as $mod) {
            $permissions[$mod] = isset($_POST['perm_' . $mod]) ? 1 : 0;
        }
        // Sub-permissions: post generator profiles
        $selectedProfiles = [];
        foreach (($postProfiles ?: []) as $pp) {
            if (isset($_POST['perm_post_generator_profile_' . $pp['id']])) {
                $selectedProfiles[] = (int)$pp['id'];
            }
        }
        $permissions['post_generator_profiles'] = $selectedProfiles;
        $data = [
            'email' => trim($_POST['email'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'role' => $_POST['role'] ?? 'editor',
            'permissions' => json_encode($permissions),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($id) {
            update('team_members', $id, $data);
            $_SESSION['flash_msg'] = 'Team member updated successfully.';
        } else {
            $supabase = Supabase::getInstance();
            try {
                // First check if the user already exists in Supabase Auth
                $existingUser = $supabase->authAdminGetUserByEmail($data['email']);
                if ($existingUser) {
                    // User already exists, use their ID
                    $data['supabase_uid'] = $existingUser['id'];
                    $msg2 = 'User already exists. Added to team successfully.';
                } else {
                    // Create user FIRST
                    $tempPass = bin2hex(random_bytes(8));
                    $createResult = $supabase->authAdminCreateUser($data['email'], $tempPass);
                    
                    // Log the full response for debugging
                    error_log('Supabase create user response: ' . json_encode($createResult));
                    
                    // Try to get user ID from various possible response structures
                    $userId = null;
                    if (isset($createResult['user']['id'])) {
                        $userId = $createResult['user']['id'];
                    } elseif (isset($createResult['id'])) {
                        $userId = $createResult['id'];
                    } elseif (isset($createResult[0]['id'])) {
                        $userId = $createResult[0]['id'];
                    }
                    
                    if (!$userId) {
                        // If no ID, try to retrieve user by email as fallback
                        sleep(1);
                        $newUser = $supabase->authAdminGetUserByEmail($data['email']);
                        if ($newUser && isset($newUser['id'])) {
                            $userId = $newUser['id'];
                        } else {
                            throw new \Exception('Failed to get user ID from Supabase create response. Full response: ' . json_encode($createResult));
                        }
                    }
                    $data['supabase_uid'] = $userId;
                    
                    // Then try to send invite (non-blocking)
                    $inviteSent = false;
                    try {
                        $inviteResult = $supabase->authInviteUserByEmail($data['email'], (defined('BASE_URL') ? BASE_URL : '') . '/admin/');
                        $inviteSent = !empty($inviteResult);
                    } catch (\Exception $inviteErr) {
                        error_log('Failed to send invite to ' . $data['email'] . ': ' . $inviteErr->getMessage());
                    }

                    if ($inviteSent) {
                        $data['invited_at'] = date('Y-m-d H:i:s');
                        $msg2 = 'User created! An invite email has been sent to ' . h($data['email']) . '.';
                    } else {
                        $msg2 = 'User created! They can use the "Forgot Password" link on the login page to set their password.';
                    }
                }
            } catch (\Exception $e) {
                $msg = 'Supabase Error: ' . $e->getMessage();
                error_log('Team member creation error: ' . $e->getMessage());
            }
            if (empty($msg)) {
                $data['avatar_url'] = '';
                $data['onboarding_complete'] = 0;
                insert('team_members', $data);
                $_SESSION['flash_msg'] = 'Team member added successfully. ' . ($msg2 ?? '');
            }
        }
        if (empty($msg)) {
            $_SESSION['flash_type'] = 'success';
            redirect('team.php');
        }
    } catch (Exception $e) {
        $msg = 'Error: ' . $e->getMessage();
    }
}

$flash_msg = $_SESSION['flash_msg'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

// Try to fetch records — handle stale PostgREST schema cache
$records = [];
try {
    $records = get_all('team_members', 'created_at DESC');
} catch (Exception $e) {
    // PostgREST schema cache may be stale — try reload + re-fetch
    try {
        if ($isRestMode) {
            $pdo->restCall('GET', '?limit=0', null, ['Prefer: reload-schema']);
        }
    } catch (Exception $e2) {}
    try {
        $records = get_all('team_members', 'created_at DESC');
    } catch (Exception $e3) {}
}
$editItem = [
    'id' => null,
    'email' => '',
    'name' => '',
    'role' => 'editor',
    'permissions' => '{}',
    'avatar_url' => '',
    'is_active' => 1,
];
$isEdit = false;
if (!empty($_GET['edit']) && is_numeric($_GET['edit'])) {
    $fetched = get_row('team_members', (int)$_GET['edit']);
    if ($fetched) {
        $editItem = array_merge($editItem, $fetched);
        $isEdit = true;
    }
}
$showForm = $isEdit || isset($_GET['new']);

$allModules = [
    'dashboard' => 'Dashboard',
    'tasks' => 'Tasks',
    'notes' => 'Notes',
    'blog' => 'Blog Posts',
    'post_generator' => 'Post Generator',
    'outreach' => 'Outreach (Leads)',
    'quote_invoice' => 'Quote & Invoice',
    'messages' => 'Messages',
    'services' => 'Services',
    'packages' => 'Packages',
    'testimonials' => 'Testimonials',
    'faq' => 'FAQ',
    'portfolio' => 'Portfolio',
    'brands' => 'Brands',
    'media' => 'Media Library',
    'settings' => 'Settings',
    'team' => 'Team Management',
];
$editPermissions = [];
if ($isEdit && !empty($editItem['permissions'])) {
    $editPermissions = json_decode($editItem['permissions'], true) ?: [];
}
?>
<?php require_once __DIR__ . '/../inc/header.php'; ?>

<?php if ($flash_msg): ?>
    <div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:<?= $flash_type === 'success' ? 'var(--green-bg)' : 'var(--red-bg)' ?>;color:<?= $flash_type === 'success' ? 'var(--green)' : 'var(--red)' ?>"><?= h($flash_msg) ?></div>
<?php endif; ?>
<?php if ($msg): ?>
    <div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:var(--red-bg);color:var(--red)"><?= h($msg) ?></div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title">Team Members</h1>
    <div class="flex flex-wrap">
        <div class="search-box">
            <i class="ti ti-search" style="color:var(--text3)"></i>
            <input class="form-control" type="text" placeholder="Search..." oninput="searchTable(this)" style="width:200px">
        </div>
        <a href="team.php?new=1" class="btn btn-primary"><i class="ti ti-user-plus"></i> Add Member</a>
    </div>
</div>

<style>
.perm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 6px;
    margin-top: 8px;
}
.perm-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 0.78rem;
    color: var(--text2);
    cursor: pointer;
    transition: all 0.15s;
}
.perm-item:hover {
    border-color: var(--border-hover);
}
.perm-item input[type="checkbox"] {
    accent-color: var(--accent);
    width: 16px;
    height: 16px;
    cursor: pointer;
}
.perm-item.checked {
    border-color: var(--accent);
    background: rgba(204, 255, 0, 0.04);
    color: var(--accent);
}
.perm-expand {
    grid-column: 1 / -1;
    padding: 0 0 0 1.25rem;
    border-left: 2px solid var(--border);
    margin: -4px 0 6px 10px;
    display: none;
}
.perm-expand.open {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
.perm-sub-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 0.72rem;
    color: var(--text3);
    cursor: pointer;
    transition: all 0.15s;
}
.perm-sub-item:hover {
    border-color: var(--border-hover);
}
.perm-sub-item input[type="checkbox"] {
    accent-color: var(--accent);
    width: 14px;
    height: 14px;
    cursor: pointer;
}
.perm-sub-item.checked {
    border-color: var(--accent);
    color: var(--accent);
    background: rgba(204, 255, 0, 0.03);
}
.perm-sub-label {
    font-size: 0.6rem;
    color: var(--text-muted);
    margin-left: 2px;
}
.team-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), rgba(204, 255, 0, 0.3));
    color: #080b12;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.82rem;
    flex-shrink: 0;
    font-family: 'Orbitron', sans-serif;
}
.team-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}
</style>

<div id="module-list" style="<?= $showForm ? 'display:none' : 'display:block' ?>">
    <div class="card">
        <?php if (count($records)): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $p): ?>
                    <tr>
                        <td>
                            <div class="flex">
                                <div class="team-avatar">
                                    <?php if (!empty($p['avatar_url'])): ?>
                                        <img src="<?= h(image_url($p['avatar_url'])) ?>" alt="">
                                    <?php else: ?>
                                        <?= strtoupper(substr($p['name'] ?: $p['email'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <strong style="color:var(--text)"><?= h($p['name'] ?: $p['email']) ?></strong>
                            </div>
                        </td>
                        <td style="color:var(--text3)"><?= h($p['email']) ?></td>
                        <td>
                            <span class="status-badge" style="background:<?= $p['role'] === 'admin' ? 'var(--green-bg)' : ($p['role'] === 'editor' ? 'var(--blue-bg)' : 'var(--blue-bg)') ?>;color:<?= $p['role'] === 'admin' ? 'var(--green)' : 'var(--blue)' ?>">
                                <?= ucfirst($p['role'] ?? 'editor') ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge" style="background:<?= ($p['is_active'] ?? 1) ? 'var(--green-bg)' : 'var(--red-bg)' ?>;color:<?= ($p['is_active'] ?? 1) ? 'var(--green)' : 'var(--red)' ?>">
                                <?= ($p['is_active'] ?? 1) ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td style="text-align:right">
                            <a href="?edit=<?= $p['id'] ?? 0 ?>" class="btn btn-secondary btn-sm"><i class="ti ti-pencil"></i></a>
                            <button onclick="confirmDelete('team.php?delete=<?= $p['id'] ?? 0 ?>', '<?= h(addslashes($p['name'] ?: $p['email'])) ?>')" class="btn btn-danger btn-sm"><i class="ti ti-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><i class="ti ti-users"></i><p>No team members yet.</p><a href="team.php?new=1" class="btn btn-primary mt-1">Add Member</a></div>
        <?php endif; ?>
    </div>
</div>

<div id="module-form" style="<?= $showForm ? 'display:block' : 'display:none' ?>">
    <div class="card">
        <div class="flex" style="margin-bottom:1rem">
            <button class="btn btn-secondary" onclick="showList()"><i class="ti ti-arrow-left"></i> Back</button>
        </div>
        <form method="post" action="team.php">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input class="form-control" name="name" value="<?= h($editItem['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input class="form-control" name="email" type="email" value="<?= h($editItem['email'] ?? '') ?>" <?= $isEdit ? 'readonly' : '' ?> required>
                    <?php if (!$isEdit): ?>
                        <div style="font-size:0.68rem;color:var(--text3);margin-top:4px">A temporary password will be generated. The member can reset it after login.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Role</label>
                    <select class="form-control" name="role">
                        <option value="admin" <?= ($editItem['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrator (Full Access)</option>
                        <option value="editor" <?= ($editItem['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor (Custom Permissions)</option>
                        <option value="viewer" <?= ($editItem['role'] ?? '') === 'viewer' ? 'selected' : '' ?>>Viewer (Read Only)</option>
                    </select>
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:12px">
                    <label class="switch" style="margin-bottom:0">
                        <input type="checkbox" name="is_active" value="1" <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                    <span style="font-size:0.82rem;color:var(--text2);margin-left:10px">Active</span>
                </div>
            </div>

            <div class="form-group">
                <label>Module Permissions</label>
                <p style="font-size:0.72rem;color:var(--text3);margin-bottom:4px">Select which modules this member can access.</p>
                <div class="perm-grid">
                    <?php
                    $selectedProfileIds = $editPermissions['post_generator_profiles'] ?? [];
                    if (!is_array($selectedProfileIds)) $selectedProfileIds = [];
                    foreach ($allModules as $modKey => $modLabel):
                        $checked = ($editItem['role'] ?? '') === 'admin' || !empty($editPermissions[$modKey]);
                        $isPg = $modKey === 'post_generator';
                    ?>
                    <label class="perm-item <?= $checked ? 'checked' : '' ?>" style="<?= $isPg ? 'grid-column:1/-1' : '' ?>">
                        <input type="checkbox" name="perm_<?= $modKey ?>" value="1" <?= $checked ? 'checked' : '' ?>
                            onchange="this.parentElement.classList.toggle('checked');<?= $isPg ? "document.getElementById('pgProfiles').classList.toggle('open',this.checked)" : '' ?>">
                        <?= $modLabel ?>
                        <?php if ($isPg && $postProfiles): ?>
                            <span style="font-size:0.6rem;color:var(--text-muted);margin-left:auto"><?= count($postProfiles) ?> profiles</span>
                        <?php endif; ?>
                    </label>
                    <?php if ($isPg && $postProfiles): ?>
                    <div class="perm-expand <?= $checked ? 'open' : '' ?>" id="pgProfiles">
                        <?php foreach ($postProfiles as $pp):
                            $subChecked = ($editItem['role'] ?? '') === 'admin' || in_array((int)$pp['id'], $selectedProfileIds);
                        ?>
                        <label class="perm-sub-item <?= $subChecked ? 'checked' : '' ?>">
                            <input type="checkbox" name="perm_post_generator_profile_<?= $pp['id'] ?>" value="1" <?= $subChecked ? 'checked' : '' ?> onchange="this.parentElement.classList.toggle('checked')">
                            <i class="ti ti-<?= strtolower($pp['platform'] ?? 'brand') === 'linkedin' ? 'brand-linkedin' : (strtolower($pp['platform'] ?? '') === 'facebook' ? 'brand-facebook' : 'user') ?>" style="font-size:0.7rem"></i>
                            <?= h($pp['name'] ?: $pp['profile_url']) ?>
                            <span class="perm-sub-label"><?= h($pp['platform'] ?? '') ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="action" value="save" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button>
                <button type="button" class="btn btn-secondary" onclick="showList()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
