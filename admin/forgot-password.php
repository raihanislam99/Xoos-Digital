<?php
/**
 * Forgot Password — uses Supabase Auth password reset
 * Sends a password reset email via Supabase Auth
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/supabase.php';

if (!empty($_SESSION['supabase_access_token'])) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip       = $_SERVER['REMOTE_ADDR'];
    $key      = 'reset_attempts_' . md5($ip);
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
        $email = trim($_POST['email'] ?? '');
        if (!$email) {
            $error = 'Email is required.';
        } else {
            try {
                $supabase = Supabase::getInstance();
                // Supabase Auth: send password reset email
                $redirectTo = ADMIN_URL . '/login.php';
                $supabase->apiCall('POST', '/auth/v1/recover', [
                    'email' => $email,
                    'redirect_to' => $redirectTo,
                ], false, false);
                $_SESSION[$key] = ['count' => $attempts + 1, 'since' => $since];
                $success = true;
            } catch (Exception $e) {
                $_SESSION[$key] = ['count' => $attempts + 1, 'since' => $since];
                $error = 'Could not send reset email. Please contact support or use Supabase dashboard.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Xoos Digital Admin</title>
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
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">Xoos <span>Digital</span></div>
        <div class="subtitle">Forgot Password</div>

        <?php if ($success): ?>
            <div class="msg msg-ok">If an account exists with that email, a password reset link has been sent. Check your inbox (and spam). <a href="login.php" style="color:#CCFF00;text-decoration:underline">Back to Login</a></div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="msg msg-err"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <p class="text-muted">Enter your admin email address and we'll send a password reset link via Supabase Auth.</p>

            <form method="post">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required autocomplete="email" placeholder="admin@example.com">
                </div>
                <button type="submit" class="btn">Send Reset Link</button>
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>
