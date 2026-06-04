<?php
/**
 * Reset Password / Change Credentials — uses Supabase Auth Admin API
 */
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/supabase.php';
require_login();

$success = false;
$error   = '';
$supabase = Supabase::getInstance();
$currentUser = supabase_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'update' && $currentUser) {
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
        } else {
            // Verify current password by attempting sign-in
            try {
                $verify = $supabase->authSignIn($currentUser['email'], $current);
                if (empty($verify['access_token'])) {
                    $error = 'Current password is incorrect.';
                } else {
                    // Update via Admin API
                    $updateData = [];
                    if ($new_email !== $currentUser['email']) {
                        $updateData['email'] = $new_email;
                    }
                    $updateData['password'] = $new_pass;

                    $result = $supabase->apiCall('PUT', '/auth/v1/admin/users/' . $currentUser['id'], $updateData, true);

                    if (!empty($result['id'])) {
                        // Refresh session
                        $refreshed = $supabase->authSignIn($new_email, $new_pass);
                        if (!empty($refreshed['access_token'])) {
                            $_SESSION['supabase_access_token'] = $refreshed['access_token'];
                            $_SESSION['supabase_refresh_token'] = $refreshed['refresh_token'];
                            $_SESSION['supabase_user']          = $refreshed['user'];
                        }
                        $success = true;
                    } else {
                        $error = 'Failed to update credentials.';
                    }
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/inc/header.php';
?>

<?php if ($success): ?>
    <div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:var(--green-bg);color:var(--green)">
        Credentials updated successfully.
    </div>
<?php elseif ($error): ?>
    <div style="padding:0.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.85rem;background:var(--red-bg);color:var(--red)">
        <?= h($error) ?>
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
            <input class="form-control" type="email" name="new_email" value="<?= h(supabase_user_email()) ?>" placeholder="admin@example.com" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>New Password</label>
                <input class="form-control" type="password" name="new_password" placeholder="Min 8 characters" required>
                <small style="color:#555;font-size:0.7rem;margin-top:4px;display:block">Min 8 characters</small>
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
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
