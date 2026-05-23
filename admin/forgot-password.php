<?php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/inc/functions.php';

    $ip       = $_SERVER['REMOTE_ADDR'];
    $key      = 'recovery_attempts_' . md5($ip);
    $attempts = $_SESSION[$key]['count'] ?? 0;
    $since    = $_SESSION[$key]['since'] ?? time();
    if (time() - $since > 900) {
        $attempts = 0;
        $since    = time();
    }
    if ($attempts >= 3) {
        $wait = 900 - (time() - $since);
        $error = "Too many attempts. Try again in " . ceil($wait/60) . " minutes.";
    } else {
        $code    = trim($_POST['recovery_code'] ?? '');
        $new_pass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($code === '' || $new_pass === '' || $confirm === '') {
            $error = 'All fields are required.';
        } elseif ($new_pass !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($new_pass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $new_pass)) {
            $error = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $new_pass)) {
            $error = 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $new_pass)) {
            $error = 'Password must contain at least one digit.';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $new_pass)) {
            $error = 'Password must contain at least one special character.';
        } elseif (!verify_recovery_code($code)) {
            $_SESSION[$key] = ['count' => $attempts + 1, 'since' => $since];
            $error = 'Invalid recovery code. ' . (3 - $attempts - 1) . ' attempts remaining.';
        } else {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = db()->prepare("UPDATE admin_users SET password_hash = ? WHERE id = 1");
            $stmt->execute([$hash]);

            $new_code = generate_recovery_code();
            set_recovery_hash($new_code);

            unset($_SESSION[$key]);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Xoos Digital Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:#0A0A0A;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem}
        .card{width:100%;max-width:420px;background:#111;border:1px solid #222;border-radius:1.25rem;padding:2.5rem;position:relative;overflow:hidden}
        .card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#CCFF00,transparent)}
        .logo{font-family:'Orbitron',sans-serif;font-size:1.25rem;font-weight:900;text-transform:uppercase;letter-spacing:0.04em;text-align:center;margin-bottom:0.25rem}
        .logo span{color:#CCFF00}
        .subtitle{text-align:center;color:#9CA3AF;font-size:0.8rem;margin-bottom:2rem}
        .form-group{margin-bottom:1.25rem}
        label{display:block;font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#9CA3AF;margin-bottom:0.5rem}
        input{width:100%;padding:0.75rem 1rem;background:#181818;border:1px solid #2a2a2a;border-radius:8px;color:#fff;font-size:0.9rem;outline:none;transition:border-color 0.2s;font-family:'Inter',sans-serif}
        input:focus{border-color:#CCFF00}
        .btn{width:100%;padding:0.75rem;background:#CCFF00;color:#0A0A0A;border:none;border-radius:8px;font-family:'Orbitron',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;cursor:pointer;transition:opacity 0.2s}
        .btn:hover{opacity:0.85}
        .msg{padding:0.75rem 1rem;border-radius:8px;font-size:0.8rem;margin-bottom:1rem;text-align:center}
        .msg-ok{background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.2);color:#4ade80}
        .msg-err{background:rgba(255,50,50,0.1);border:1px solid rgba(255,50,50,0.2);color:#ff4444}
        .text-muted{color:#555;font-size:0.75rem;line-height:1.5;margin-bottom:1rem}
        .back-link{text-align:center;margin-top:1.25rem}
        .back-link a{color:#555;font-size:0.75rem;text-decoration:none;transition:color 0.15s}
        .back-link a:hover{color:#CCFF00}
        small{color:#555;font-size:0.65rem;display:block;margin-top:4px}
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">Xoos <span>Digital</span></div>
        <div class="subtitle">Reset Password</div>

        <?php if ($success): ?>
            <div class="msg msg-ok">Password reset successfully. A new recovery code has been generated and stored. <a href="login.php" style="color:#CCFF00;text-decoration:underline">Go to Login</a></div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="msg msg-err"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <p class="text-muted">Enter your recovery code and a new password. If you don't have a recovery code, run <strong>setup.php</strong> from your server and use <strong>"Reset Admin User"</strong> (non-destructive, preserves all data).</p>

            <form method="post">
                <div class="form-group">
                    <label>Recovery Code</label>
                    <input type="text" name="recovery_code" required autocomplete="off" placeholder="e.g. X7k9-mQ2p-4Rw8" style="font-family:monospace;letter-spacing:0.1em;text-transform:uppercase">
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required autocomplete="new-password" placeholder="Min 8 characters">
                    <small>Min 8 chars · 1 uppercase · 1 lowercase · 1 digit · 1 special char</small>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required autocomplete="new-password" placeholder="Re-enter new password">
                </div>
                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>