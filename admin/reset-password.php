<?php
require_once __DIR__ . '/inc/functions.php';
require_login();

$success = false;
$error   = '';
$show_recovery = false;
$recovery_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = $_POST['action'] ?? '';

    // Generate new recovery code
    if ($action === 'gen_recovery') {
        $stmt = db()->prepare("SELECT * FROM admin_users LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();

        $current = $_POST['current_password'] ?? '';
        if (!$user || !password_verify($current, $user['password_hash'])) {
            $error = 'Current password is required to generate a new recovery code.';
        } else {
            $recovery_code = generate_recovery_code();
            set_recovery_hash($recovery_code);
            $show_recovery = true;
        }
    }

    // Update credentials
    if ($action === 'update') {
        $current    = $_POST['current_password'] ?? '';
        $new_email  = trim($_POST['new_email'] ?? '');
        $new_pass   = $_POST['new_password'] ?? '';
        $confirm    = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new_email === '' || $new_pass === '' || $confirm === '') {
            $error = 'All fields are required.';
        } elseif ($new_pass !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_pass) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $new_pass)) {
            $error = 'New password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $new_pass)) {
            $error = 'New password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $new_pass)) {
            $error = 'New password must contain at least one digit.';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $new_pass)) {
            $error = 'New password must contain at least one special character.';
        } else {
            $stmt = db()->prepare("SELECT * FROM admin_users LIMIT 1");
            $stmt->execute();
            $user = $stmt->fetch();

            if (!$user || !password_verify($current, $user['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $update = db()->prepare("UPDATE admin_users SET username = ?, password_hash = ? WHERE id = ?");
                $update->execute([$new_email, $hash, $user['id']]);

                $_SESSION['admin_username'] = $new_email;
                $success = true;
            }
        }
    }
}

$current_role = '';
try {
    $current_role = db_val("SELECT role FROM admin_users LIMIT 1");
} catch (Exception $e) {}

require_once __DIR__ . '/inc/header.php';
?>

<?php if ($success): ?>
    <div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:var(--green-bg);color:var(--green)">
        Credentials updated successfully. Your new login is <strong><?= h($_SESSION['admin_username']) ?></strong>.
    </div>
<?php elseif ($error): ?>
    <div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:var(--red-bg);color:var(--red)">
        <?= h($error) ?>
    </div>
<?php endif; ?>

<?php if ($show_recovery && $recovery_code): ?>
    <div style="border:1px solid rgba(204,255,0,0.3);border-radius:12px;padding:1.25rem;margin-bottom:1rem;background:rgba(204,255,0,0.04)">
        <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#CCFF00;margin-bottom:0.5rem">⚠ New Recovery Code Generated</div>
        <p style="font-size:0.75rem;color:#9CA3AF;margin-bottom:0.75rem;line-height:1.5">Your old recovery code is now invalid. Save this new one — it is <strong>shown only once</strong>.</p>
        <div style="background:#0A0A0A;border:1px solid #2a2a2a;border-radius:8px;padding:0.75rem 1rem;font-family:monospace;font-size:1.1rem;text-align:center;color:#fff;letter-spacing:0.15em;user-select:all"><?= h($recovery_code) ?></div>
    </div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title">Change Credentials</h1>
</div>

<div class="card" style="max-width:500px">
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="update">

        <div class="form-group">
            <label>Current Password</label>
            <input class="form-control" type="password" name="current_password" placeholder="Enter your current password" required>
        </div>

        <div class="form-group">
            <label>New Email / Username</label>
            <input class="form-control" type="email" name="new_email" value="<?= h($_SESSION['admin_username'] ?? '') ?>" placeholder="admin@example.com" required>
        </div>

        <div class="form-group">
            <label>Role <span class="text-muted">(read-only)</span></label>
            <input class="form-control" value="<?= h($current_role ?: 'admin') ?>" readonly style="color:var(--text3)">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>New Password</label>
                <input class="form-control" type="password" name="new_password" placeholder="Min 8 chars, upper, lower, digit, special" required>
                <small style="color:#555;font-size:0.7rem;margin-top:4px;display:block">Min 8 chars · 1 uppercase · 1 lowercase · 1 digit · 1 special char</small>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input class="form-control" type="password" name="confirm_password" placeholder="Re-enter new password" required>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Credentials</button>
        </div>
    </form>

    <hr style="border:none;border-top:1px solid var(--border);margin:1.5rem 0">

    <form method="post">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="gen_recovery">
        <div class="form-group">
            <label>Generate New Recovery Code</label>
            <p style="color:var(--text3);font-size:0.75rem;margin-bottom:0.75rem;line-height:1.5">Generates a new recovery code (old one becomes invalid). Enter your current password to confirm.</p>
            <div style="display:flex;gap:8px">
                <input class="form-control" type="password" name="current_password" placeholder="Confirm current password" required style="flex:1">
                <button type="submit" class="btn btn-secondary" style="white-space:nowrap">Generate Code</button>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
