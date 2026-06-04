<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/supabase.php';

if (!empty($_SESSION['supabase_access_token'])) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    $ip       = $_SERVER['REMOTE_ADDR'];
    $key      = 'login_attempts_' . md5($ip);
    $attempts = $_SESSION[$key]['count'] ?? 0;
    $since    = $_SESSION[$key]['since'] ?? time();
    if (time() - $since > 900) {
        $attempts = 0;
        $since    = time();
    }
    if ($attempts >= 5) {
        $wait = 900 - (time() - $since);
        $error = "Too many failed attempts. Try again in " . ceil($wait/60) . " minutes.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username && $password) {
            $success = supabase_login($username, $password);
            if ($success) {
                unset($_SESSION[$key]);
                header('Location: ' . ADMIN_URL . '/index.php');
                exit;
            } else {
                $_SESSION[$key] = ['count' => $attempts + 1, 'since' => $since];
                $error = "Invalid email or password. " . (5 - $attempts - 1) . " attempts remaining.";
            }
        }
        if (!$error) $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Xoos Digital Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{
            font-family:'Inter',sans-serif;
            background:#080B10;
            color:#fff;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:1rem;
            background-image:radial-gradient(circle at center, rgba(204, 255, 0, 0.03), transparent 55%);
        }
        .login-card{
            width:100%;
            max-width:400px;
            background:#0F1524;
            border:1px solid rgba(255,255,255,0.05);
            border-radius:16px;
            padding:2.5rem;
            position:relative;
            overflow:hidden;
            box-shadow:0 20px 50px rgba(0,0,0,0.5);
        }
        .login-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#CCFF00,transparent)}
        .logo{font-family:'Orbitron',sans-serif;font-size:1.25rem;font-weight:900;text-transform:uppercase;letter-spacing:0.04em;text-align:center;margin-bottom:0.25rem}
        .logo span{color:#CCFF00}
        .subtitle{text-align:center;color:#9CA3AF;font-size:0.8rem;margin-bottom:2rem}
        .form-group{margin-bottom:1.25rem}
        label{display:block;font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:#9CA3AF;margin-bottom:0.5rem}
        input{
            width:100%;
            padding:0.75rem 1rem;
            background:#182030;
            border:1px solid rgba(255,255,255,0.05);
            border-radius:12px;
            color:#fff;
            font-size:0.9rem;
            outline:none;
            transition:all 0.2s ease;
            font-family:'Inter',sans-serif;
        }
        input:focus{border-color:#CCFF00;box-shadow:0 0 0 3px rgba(204,255,0,0.15)}
        .btn{
            width:100%;
            padding:0.75rem;
            background:#CCFF00;
            color:#080B10;
            border:none;
            border-radius:12px;
            font-family:'Inter',sans-serif;
            font-size:0.85rem;
            font-weight:700;
            cursor:pointer;
            transition:all 0.2s ease;
            box-shadow:0 4px 12px rgba(204,255,0,0.15);
        }
        .btn:hover{opacity:0.9;transform:translateY(-1px);box-shadow:0 6px 16px rgba(204,255,0,0.3)}
        .error{background:rgba(255,77,92,0.1);border:1px solid rgba(255,77,92,0.2);color:#FF4D6D;padding:0.75rem 1rem;border-radius:12px;font-size:0.8rem;margin-bottom:1.5rem;text-align:center}
        .forgot-pwd{color:#6B7280;font-size:0.75rem;text-decoration:none;transition:color 0.15s}
        .forgot-pwd:hover{color:#CCFF00}
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">Xoos <span>Digital</span></div>
        <div class="subtitle">Admin Panel</div>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="username">Email</label>
                <input type="email" id="username" name="username" required autocomplete="email" placeholder="admin@example.com">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
            </div>
            <button type="submit" class="btn">Sign In</button>
            <div style="text-align:center;margin-top:1rem">
                <a href="forgot-password.php" class="forgot-pwd">Forgot Password?</a>
            </div>
        </form>
    </div>
</body>
</html>
