<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Server Error</title>
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
            background-image: radial-gradient(ellipse 60% 30% at 50% 20%, rgba(255,71,87,0.06) 0%, transparent 70%);
        }
        .error-wrap {
            width: 100%;
            max-width: 480px;
            animation: fadeUp 0.5s ease-out;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .error-card {
            background: #0e1420;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px;
            padding: 3rem 2.5rem;
            box-shadow: 0 24px 64px rgba(0,0,0,0.5);
            text-align: center;
        }
        .error-code {
            font-family: 'Orbitron', sans-serif;
            font-size: 5rem;
            font-weight: 900;
            color: #ff4757;
            line-height: 1;
            margin-bottom: 0.5rem;
            text-shadow: 0 0 40px rgba(255,71,87,0.15);
        }
        .error-icon {
            font-size: 2.5rem;
            color: rgba(255,71,87,0.15);
            margin-bottom: 1rem;
        }
        .error-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.5);
            margin-bottom: 1rem;
        }
        .error-desc {
            color: rgba(255,255,255,0.45);
            font-size: 0.85rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        .error-details {
            margin-bottom: 2rem;
            padding: 1rem;
            background: rgba(255,71,87,0.04);
            border: 1px solid rgba(255,71,87,0.08);
            border-radius: 10px;
            font-size: 0.72rem;
            color: rgba(255,255,255,0.3);
            font-family: 'Inter', monospace;
            text-align: left;
            word-break: break-all;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.75rem 1.75rem;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
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
        .btn-ghost {
            background: rgba(255,255,255,0.04);
            color: rgba(255,255,255,0.5);
            border: 1px solid rgba(255,255,255,0.06);
        }
        .btn-ghost:hover {
            background: rgba(255,255,255,0.08);
        }
        .error-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        @media(max-width:480px) {
            .error-card { padding: 2rem 1.5rem; }
            .error-code { font-size: 3.5rem; }
        }
    </style>
</head>
<body>
    <div class="error-wrap">
        <div class="error-card">
            <div class="error-icon"><i class="ti ti-alert-triangle"></i></div>
            <div class="error-code">500</div>
            <div class="error-title">Server Error</div>
            <div class="error-desc">
                Something went wrong on our end.<br>
                Try refreshing or come back later.
            </div>
            <div class="error-actions">
                <a href="<?= ADMIN_URL ?>/index.php" class="btn btn-primary"><i class="ti ti-refresh"></i> Reload</a>
                <a href="<?= ADMIN_URL ?>/login.php" class="btn btn-ghost"><i class="ti ti-logout"></i> Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
