<?php
require_once __DIR__ . '/functions.php';
require_login();

$current = basename($_SERVER['SCRIPT_NAME']);
$module = basename(dirname($_SERVER['SCRIPT_NAME'])) === 'modules' ? basename($_SERVER['SCRIPT_NAME']) : '';
$active = $module ? str_replace('.php', '', $module) : '';

// Nav groups rendered inline in the sidebar template below

$unread_msgs = 0;
try { $unread_msgs = db_val("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0"); } catch (Exception $e) {}
$pendingTasks = 0;
try { $pendingTasks = (int)db_val("SELECT COUNT(*) FROM admin_tasks WHERE status IN ('pending','in_progress')"); } catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Xoos Digital</title>
    <link rel="icon" href="<?= BASE_URL ?>/images/Icons/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root {
            --bg: #080B10; /* Premium deep slate-black space dark */
            --bg2: #0F1524; /* Sleek, cards & sidebar */
            --bg3: #182030; /* Dark slate fields */
            --border: rgba(255, 255, 255, 0.05); /* Subtle border */
            --border-hover: rgba(204, 255, 0, 0.25); /* Accent lime borders */
            --accent: #CCFF00; /* Modern toxic/lime primary accent */
            --accent-glow: rgba(204, 255, 0, 0.15); /* Accent shadow color */
            --text: #F3F4F6; /* Clear off-white */
            --text2: #9CA3AF; /* Cool grey for text descriptions */
            --text3: #6B7280; /* Muted secondary text and placeholders */
            --red: #FF4D6D; --red-bg: rgba(255, 77, 109, 0.1);
            --green: #00E676; --green-bg: rgba(0, 230, 118, 0.1);
            --blue: #00B0FF; --blue-bg: rgba(0, 176, 255, 0.1);
            --shadow: 0 12px 40px rgba(0, 0, 0, 0.4); /* Modern cards elevations */
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 8px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
            background-image: radial-gradient(circle at top right, rgba(204, 255, 0, 0.02), transparent 45%);
            background-attachment: fixed;
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent); }

        /* ── Sidebar ── */
        .sidebar {
            width: 250px;
            background: var(--bg2);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 0 1px rgba(255,255,255,0.03), 0 12px 40px rgba(0,0,0,0.5);
        }
        .sidebar-brand {
            padding: 1.5rem 1.5rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }
        .sidebar-brand a {
            font-family: 'Orbitron', sans-serif;
            font-size: 1rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text);
            text-decoration: none;
            display: block;
        }
        .sidebar-brand span { color: var(--accent); }
        .sidebar-brand small {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 0.6rem;
            color: var(--text3);
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-top: 4px;
        }
        .sidebar-nav {
            flex: 1;
            padding: 0.5rem 0;
            overflow-y: auto;
        }
        .nav-label {
            padding: 1rem 1.5rem 0.35rem;
            font-size: 0.6rem;
            font-weight: 800;
            color: var(--text3);
            text-transform: uppercase;
            letter-spacing: 0.18em;
            opacity: 0.6;
            font-family: 'Inter', sans-serif;
        }
        .nav-separator {
            height: 1px;
            background: var(--border);
            margin: 0.5rem 1.5rem;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.65rem 1.5rem;
            color: var(--text2);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 500;
            transition: all 0.15s ease;
            border-left: 3px solid transparent;
            margin: 1px 0;
        }
        .sidebar-nav a:hover {
            color: var(--text);
            background: rgba(255, 255, 255, 0.03);
        }
        .sidebar-nav a.active {
            color: var(--accent);
            background: linear-gradient(90deg, rgba(204, 255, 0, 0.06) 0%, transparent 100%);
            border-left-color: var(--accent);
            font-weight: 600;
        }
        .sidebar-nav a i {
            font-size: 1.15rem;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        .unread-badge {
            margin-left: auto;
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 999px;
            font-family: 'Inter', sans-serif;
        }
        .sidebar-footer {
            padding: 0.75rem 0;
            border-top: 1px solid var(--border);
        }
        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.6rem 1.5rem;
            color: var(--text3);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.15s ease;
        }
        .sidebar-footer a:hover {
            color: var(--text2);
            background: rgba(255,255,255,0.02);
        }

        /* ── Main ── */
        .admin-main {
            margin-left: 250px;
            flex: 1;
            padding: 2.5rem;
            min-height: 100vh;
            width: calc(100% - 250px);
            transition: all 0.3s;
        }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .page-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            position: relative;
            padding-bottom: 4px;
        }
        .page-title::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 24px;
            height: 2px;
            background: var(--accent);
            border-radius: 99px;
        }
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        .hamburger {
            display: none;
            background: none;
            border: none;
            color: var(--text2);
            font-size: 1.6rem;
            cursor: pointer;
            transition: color 0.15s;
        }
        .hamburger:hover { color: var(--accent); }
        .user-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text2);
            background: var(--bg2);
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }
        .user-badge i { font-size: 1.2rem; color: var(--accent); }

        /* ── Cards ── */
        .card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.75rem;
            box-shadow: var(--shadow);
            transition: all 0.2s ease;
        }
        .card + .card { margin-top: 1.5rem; }

        /* ── Table ── */
        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--bg2);
            box-shadow: var(--shadow);
        }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th {
            text-align: left;
            padding: 1rem 1.25rem;
            color: var(--text3);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.1em;
            border-bottom: 1px solid var(--border);
            background: rgba(255,255,255,0.01);
        }
        td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            color: var(--text2);
            transition: background 0.15s;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255, 255, 255, 0.015); color: var(--text); }
        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: var(--radius-sm);
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .status-draft { background: var(--blue-bg); color: var(--blue); }
        .status-published { background: var(--green-bg); color: var(--green); }

        /* ── Form ── */
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text2);
            margin-bottom: 0.5rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            color: var(--text);
            font-size: 0.88rem;
            outline: none;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }
        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        textarea.form-control { min-height: 140px; resize: vertical; line-height: 1.6; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .form-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.65rem 1.3rem;
            border-radius: var(--radius-md);
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            text-decoration: none;
        }
        .btn-primary {
            background: var(--accent);
            color: #080B10;
            box-shadow: 0 4px 12px var(--accent-glow);
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(204, 255, 0, 0.3);
        }
        .btn-secondary {
            background: var(--bg3);
            color: var(--text2);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: var(--border);
            color: var(--text);
            border-color: var(--text2);
        }
        .btn-danger {
            background: var(--red-bg);
            color: var(--red);
            border: 1px solid rgba(255, 77, 109, 0.2);
        }
        .btn-danger:hover {
            background: rgba(255, 77, 109, 0.2);
            transform: translateY(-1px);
        }
        .btn-success {
            background: var(--green-bg);
            color: var(--green);
            border: 1px solid rgba(0, 230, 118, 0.2);
        }
        .btn-success:hover {
            background: rgba(0, 230, 118, 0.2);
            transform: translateY(-1px);
        }
        .btn-sm { padding: 0.45rem 1rem; font-size: 0.78rem; border-radius: var(--radius-sm); }
        .btn-ai {
            background: rgba(204, 255, 0, 0.06);
            color: var(--accent);
            border: 1px solid rgba(204, 255, 0, 0.15);
        }
        .btn-ai:hover {
            background: rgba(204, 255, 0, 0.12);
            transform: translateY(-1px);
        }

        .ai-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid var(--accent);
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Modal ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(8, 11, 16, 0.8);
            backdrop-filter: blur(8px);
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            transition: all 0.3s;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 480px;
            padding: 1.75rem;
            position: relative;
            box-shadow: var(--shadow);
            animation: modalFadeIn 0.3s ease-out;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
            color: var(--text);
        }
        .modal-actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; justify-content: flex-end; }

        /* ── Utility ── */
        .text-muted { color: var(--text3); font-size: 0.8rem; }
        .text-center { text-align: center; }
        .mt-1 { margin-top: 1.25rem; }
        .mb-1 { margin-bottom: 1.25rem; }
        .gap-1 { display: flex; gap: 8px; }
        .flex { display: flex; align-items: center; gap: 8px; }
        .flex-wrap { flex-wrap: wrap; }
        .empty-state { text-align: center; padding: 4rem 1.5rem; color: var(--text3); }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--accent); }
        .search-box { display: flex; gap: 8px; align-items: center; }
        .search-box input { width: 240px; }

        /* UI/UX Utility Classes */
        .hover-accent { transition: all 0.2s ease; }
        .hover-accent:hover { border-color: var(--accent) !important; box-shadow: 0 8px 24px rgba(204, 255, 0, 0.03); }
        .btn-quickaction {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--bg3);
            border: 1px solid var(--border);
            color: var(--text2);
            font-size: 13px;
            padding: 11px 16px;
            width: 100%;
            justify-content: center;
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-quickaction:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(204, 255, 0, 0.02);
            transform: translateX(2px);
        }
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(8, 11, 16, 0.7);
            backdrop-filter: blur(4px);
            z-index: 98;
            transition: opacity 0.3s ease;
        }
        
        /* Custom Switch Slider */
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            cursor: pointer;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            inset: 0;
            background-color: var(--bg3);
            border-radius: 999px;
            transition: 0.3s;
            border: 1px solid var(--border);
        }
        .slider::before {
            content: "";
            position: absolute;
            top: 1px;
            left: 1px;
            width: 20px;
            height: 20px;
            background-color: white;
            border-radius: 50%;
            transition: 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .switch input:checked + .slider { background-color: var(--accent); border-color: var(--accent); }
        .switch input:checked + .slider::before { transform: translateX(20px); background-color: var(--bg); }

        /* Toast Notifications */
        .toast-msg {
            position: fixed;
            bottom: 2.5rem;
            left: 50%;
            transform: translate(-50%, 15px);
            background: var(--accent);
            color: #080B10;
            padding: 0.75rem 1.75rem;
            border-radius: var(--radius-md);
            font-size: 0.88rem;
            font-weight: 700;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 10000;
            pointer-events: none;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .toast-msg.show {
            opacity: 1;
            transform: translate(-50%, 0);
        }
        .toast-msg.toast-error {
            background: var(--red);
            color: white;
        }

        .touch-active .media-overlay { opacity: 1 !important; }

        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .sidebar.open + .sidebar-backdrop { display: block; }
            .admin-main{margin-left:0;width:100%;padding:1.5rem}
            .hamburger{display:block}
            .form-row{grid-template-columns:1fr}
        }
    </style>
    <script src="<?= ADMIN_URL ?>/js/admin.js"></script>
    <script>window.blogAIProvider='<?= get_setting('ai_provider_blog','groq') ?>';</script>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <a href="<?= ADMIN_URL ?>/index.php" style="display:block;padding:0;margin:0">
                <img src="<?= BASE_URL ?>/images/logo.png" alt="Xoos Digital" style="height:32px;width:auto;display:block" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                <span style="display:none;font-family:'Orbitron',sans-serif;font-size:14px;font-weight:900;color:#CCFF00">XOOS DIGITAL</span>
                <span style="font-family:'Orbitron',sans-serif;font-size:9px;letter-spacing:0.2em;text-transform:uppercase;color:#555;margin-top:4px;display:block">ADMIN PANEL</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <?php
            $groups = [
                'CONTENT' => [
                    ['blog',        'Blog Posts',       'ti ti-news'],
                    ['services',    'Services',         'ti ti-settings'],
                    ['packages',    'Packages',         'ti ti-box'],
                    ['testimonials','Testimonials',     'ti ti-quote'],
                    ['faq',         'FAQ',              'ti ti-help'],
                    ['portfolio',   'Portfolio',        'ti ti-briefcase'],
                    ['brands',      'Brands',           'ti ti-building'],
                    ['post-generator','Post Generator', 'ti ti-file-text'],
                    ['quote-invoice','Quote & Invoice', 'ti ti-receipt'],
                ],
                'COMMUNICATION' => [
                    ['messages',    'Messages',         'ti ti-mail'],
                    ['media',       'Media Library',    'ti ti-photo'],
                ],
                'OUTREACH' => [
                    ['leads',       'Outreach',         'ti ti-users-plus'],
                ],
                'PRODUCTIVITY' => [
                    ['tasks',       'Tasks',            'ti ti-checklist'],
                ],
                'SYSTEM' => [
                    ['settings',    'Settings',         'ti ti-tool'],
                ],
            ];
            $g = 0;
            foreach ($groups as $label => $items):
                if ($g > 0): ?>
                    <div class="nav-separator"></div>
                <?php endif; ?>
                <div class="nav-label"><?= $label ?></div>
                <?php foreach ($items as $n):
                    $isActive = $active === $n[0];
                ?>
                    <a href="<?= ADMIN_URL ?>/modules/<?= $n[0] ?>.php" class="<?= $isActive ? 'active' : '' ?>">
                        <i class="<?= $n[2] ?>"></i>
                        <?= $n[1] ?>
                        <?php if ($n[0] === 'messages' && $unread_msgs > 0): ?>
                            <span class="unread-badge"><?= $unread_msgs ?></span>
                        <?php endif; ?>
                        <?php if ($n[0] === 'tasks' && $pendingTasks > 0): ?>
                            <span class="unread-badge"><?= $pendingTasks ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach;
                $g++;
            endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= ADMIN_URL ?>/setup.php"><i class="ti ti-database"></i> Setup</a>
            <a href="<?= ADMIN_URL ?>/reset-password.php"><i class="ti ti-key"></i> Change Credentials</a>
            <a href="<?= ADMIN_URL ?>/logout.php"><i class="ti ti-logout"></i> Logout</a>
        </div>
    </aside>
    <div class="sidebar-backdrop" onclick="document.getElementById('sidebar').classList.remove('open')"></div>
    <main class="admin-main">
        <div class="top-bar">
            <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open')">
                <i class="ti ti-menu-2"></i>
            </button>
            <div class="user-badge">
                <i class="ti ti-user-circle"></i>
                <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>
            </div>
        </div>
