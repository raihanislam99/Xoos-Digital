<?php
require_once __DIR__ . '/inc/functions.php';
require_login();

$email = supabase_user_email();
$member = null;
try {
    $rows = db_rows("SELECT * FROM team_members WHERE email = ? AND onboarding_complete = 0", [$email]);
    $member = $rows[0] ?? null;
} catch (Exception $e) {
    try {
        reload_pgrst_schema();
        $rows = db_rows("SELECT * FROM team_members WHERE email = ? AND onboarding_complete = 0", [$email]);
        $member = $rows[0] ?? null;
    } catch (Exception $e2) {}
}

// Already onboarded or not a team member — redirect
if (!$member) {
    redirect('index.php');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    if ($_POST['action'] === 'onboard' || $_POST['action'] === 'skip') {
        $apiKey = trim($_POST['own_api_key'] ?? '');
        $apiProvider = preg_replace('/[^a-z0-9_]/', '', $_POST['own_api_provider'] ?? 'groq');
        try {
            db_update('team_members', [
                'own_api_key' => $apiKey,
                'own_api_provider' => $apiProvider,
                'onboarding_complete' => 1,
            ], 'id = ?', [(int)$member['id']]);
            $_SESSION['flash_msg'] = $_POST['action'] === 'onboard' ? 'Welcome aboard! Your API key has been saved.' : 'You can set up your API key later in Settings.';
            $_SESSION['flash_type'] = 'success';
            redirect('index.php');
        } catch (Exception $e) {
            $msg = 'Error: ' . $e->getMessage();
        }
    }
}

$presets = ai_provider_presets();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome — Xoos Digital Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.31.0/dist/tabler-icons.min.css" crossorigin="anonymous">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body {
            font-family: 'Inter', sans-serif;
            background: #080b12;
            color: #f0f0f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background-image: radial-gradient(ellipse 60% 30% at 50% 20%, rgba(200,255,0,0.06) 0%, transparent 70%);
        }
        .onboard-wrap {
            width: 100%;
            max-width: 520px;
            animation: fadeUp 0.5s ease-out;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .onboard-card {
            background: #0e1420;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 24px 64px rgba(0,0,0,0.5);
        }
        .onboard-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: rgba(200,255,0,0.1);
            border: 1px solid rgba(200,255,0,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.8rem;
            color: #c8ff00;
        }
        .onboard-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        .onboard-sub {
            text-align: center;
            color: rgba(255,255,255,0.45);
            font-size: 0.85rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .onboard-step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 1rem;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        .onboard-step .step-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(200,255,0,0.15);
            color: #c8ff00;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 800;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .onboard-step .step-text {
            font-size: 0.82rem;
            color: rgba(255,255,255,0.65);
            line-height: 1.5;
        }
        .onboard-step .step-text strong {
            color: #f0f0f0;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.7);
            margin-bottom: 0.5rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #f0f0f0;
            font-size: 0.88rem;
            outline: none;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .form-control:focus {
            border-color: #c8ff00;
            box-shadow: 0 0 0 3px rgba(200,255,0,0.1);
        }
        select.form-control {
            cursor: pointer;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 0.85rem 1.5rem;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }
        .btn-primary {
            background: #c8ff00;
            color: #080b12;
            box-shadow: 0 4px 16px rgba(200,255,0,0.2);
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(200,255,0,0.3);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.04);
            color: rgba(255,255,255,0.5);
            border: 1px solid rgba(255,255,255,0.06);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.08);
        }
        .text-muted {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.35);
            text-align: center;
            margin-top: 1rem;
        }
        .text-muted a {
            color: #c8ff00;
            text-decoration: none;
        }
        .onboard-error {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.82rem;
            background: rgba(255,71,87,0.1);
            color: #ff4757;
            margin-bottom: 1rem;
            text-align: center;
        }
        .provider-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }
        .provider-badge {
            font-size: 0.65rem;
            padding: 3px 10px;
            border-radius: 20px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.4);
        }
        @media(max-width:480px) {
            .onboard-card { padding: 1.5rem; }
            .onboard-title { font-size: 1rem; }
        }
    </style>
</head>
<body>
    <div class="onboard-wrap">
        <div class="onboard-card">
            <div class="onboard-icon"><i class="ti ti-sparkles"></i></div>
            <h1 class="onboard-title">Welcome Aboard</h1>
            <p class="onboard-sub">You've been invited to join the Xoos Digital admin panel. Set up your AI access to get started.</p>

            <?php if ($msg): ?>
                <div class="onboard-error"><?= h($msg) ?></div>
            <?php endif; ?>

            <div class="onboard-step">
                <div class="step-num">1</div>
                <div class="step-text">
                    <strong>Get an AI API Key</strong><br>
                    Grab a free API key from one of the providers below. Groq is a great free option to start with.
                </div>
            </div>

            <div class="onboard-step">
                <div class="step-num">2</div>
                <div class="step-text">
                    <strong>Enter your key below</strong><br>
                    Your key is stored securely and used only for your AI features in the admin panel.
                </div>
            </div>

            <form method="post" action="onboarding.php">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="onboard">

                <div class="form-group">
                    <label>AI Provider</label>
                    <select class="form-control" name="own_api_provider">
                        <?php foreach ($presets as $pk => $pv): if ($pk === 'custom') continue; ?>
                            <option value="<?= $pk ?>" <?= $pk === 'groq' ? 'selected' : '' ?>><?= $pv['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="provider-badges">
                        <span class="provider-badge">Groq — Free &amp; Fast</span>
                        <span class="provider-badge">OpenAI — GPT-4o</span>
                        <span class="provider-badge">Claude — Sonnet</span>
                        <span class="provider-badge">Gemini — Free</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Your API Key</label>
                    <input class="form-control" name="own_api_key" type="text" placeholder="sk-... or gsk_..." value="" autocomplete="off">
                    <div style="margin-top:6px;font-size:0.72rem;color:rgba(255,255,255,0.35)">
                        <a href="https://console.groq.com/keys" target="_blank" style="color:#c8ff00">Get a free Groq key</a> &middot;
                        <a href="https://platform.openai.com/api-keys" target="_blank" style="color:#c8ff00">OpenAI key</a>
                    </div>
                </div>

                <button type="submit" name="action" value="onboard" class="btn btn-primary"><i class="ti ti-rocket"></i> Start Using Admin</button>

                <div style="text-align:center;margin-top:0.75rem">
                    <button type="submit" name="action" value="skip" style="background:none;border:none;color:rgba(255,255,255,0.3);font-size:0.78rem;cursor:pointer;text-decoration:none;transition:color 0.15s;font-family:'Inter',sans-serif" onmouseover="this.style.color='rgba(255,255,255,0.6)'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">Skip — I'll set this up later</button>
                </div>

                <div class="text-muted">
                    You can always update your API key later in <a href="modules/settings.php">Settings</a>.
                </div>
            </form>
        </div>
    </div>
</body>
</html>
