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
// v3 focus counts
$tasksDueToday = 0;
try { $tasksDueToday = (int)db_val("SELECT COUNT(*) FROM admin_tasks WHERE status IN ('pending','in_progress') AND DATE(due_date) = CURDATE()"); } catch (Exception $e) {}
$blogDrafts = 0;
try { $blogDrafts = (int)db_val("SELECT COUNT(*) FROM blog_posts WHERE status = 'draft'"); } catch (Exception $e) {}
$unpubPosts = 0;
try { $unpubPosts = (int)db_val("SELECT COUNT(*) FROM generated_posts WHERE status = 'draft'"); } catch (Exception $e) {}
$newLeadsWeek = 0;
try { $newLeadsWeek = (int)db_val("SELECT COUNT(*) FROM leads WHERE is_blacklisted = 0 AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"); } catch (Exception $e) {}
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
    <link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/admin-ui.css?v=<?= filemtime(__DIR__ . '/../assets/css/admin-ui.css') ?>">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/css/admin-v3.css?v=<?= filemtime(__DIR__ . '/../assets/css/admin-v3.css') ?>">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root {
            --bg: #0d1117; /* Matches frontend hero background */
            --bg2: #0e1420; /* Sleek, cards & sidebar */
            --bg3: #0e1420; /* Dark slate fields */
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
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(200,255,0,0.3); border-radius: 2px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent); }

        /* ── Sidebar ── */
        .sidebar {
            width: 260px;
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
            box-shadow: 4px 0 24px rgba(0,0,0,0.4);
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
            margin-left: 260px;
            flex: 1;
            padding: 2.5rem;
            min-height: 100vh;
            width: calc(100% - 260px);
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
            background: rgba(13, 17, 23, 0.8);
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
            background: rgba(13, 17, 23, 0.7);
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

        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 0.75rem;
            left: 0.75rem;
            z-index: 99;
            background: var(--bg2);
            border: 1px solid var(--border);
            color: var(--text);
            font-size: 1.3rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--radius-sm);
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }
        .mobile-menu-btn:hover { border-color: var(--accent); color: var(--accent); }

        /* ── Responsive ── */
        @media(max-width:1024px){
            .page-title { font-size: 1.1rem; }
            .search-box input { width: 180px; }
            .table-wrap { font-size: 0.82rem; }
            th, td { padding: 0.85rem 1rem; }
        }
        @media(max-width:768px){
            html { font-size: 16px; }
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .sidebar.open + .sidebar-backdrop { display: block; }
            .admin-main{padding:1.25rem; margin-left:0; width:100%}
            .mobile-menu-btn{display:flex}
            .form-row{grid-template-columns:1fr}
            .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .page-title { font-size: 1rem; }
            .top-bar { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .form-group { margin-bottom: 1rem; }
            .form-control { padding: 0.65rem 0.85rem; font-size: 0.85rem; }
            .card { padding: 1.25rem; }
            .card + .card { margin-top: 1rem; }
            .table-wrap { border-radius: var(--radius-sm); }
            th, td { padding: 0.75rem 0.85rem; font-size: 0.78rem; }
            th { font-size: 0.62rem; }
            .modal { padding: 1.25rem; max-width: 100%; margin: 0 0.5rem; }
            .modal-title { font-size: 0.85rem; }
            .modal-actions { flex-wrap: wrap; }
            .modal-actions .btn { flex: 1; min-width: 0; text-align: center; }
            .btn { padding: 0.55rem 1.1rem; font-size: 0.8rem; }
            .btn-sm { padding: 0.4rem 0.85rem; font-size: 0.72rem; }
            .search-box { width: 100%; }
            .search-box input { width: 100%; }
            .form-actions { flex-direction: column; }
            .form-actions .btn { width: 100%; justify-content: center; }
            .empty-state { padding: 2.5rem 1rem; }
            .empty-state i { font-size: 2.2rem; }
            .btn-quickaction { font-size: 12px; padding: 10px 14px; }
            .toast-msg { left: 1rem; right: 1rem; transform: none; width: auto; font-size: 0.82rem; padding: 0.65rem 1.25rem; }
            .toast-msg.show { transform: none; }
            /* Kanban responsive */
            .kanban-board { flex-direction: column; overflow-x: visible; gap: 1rem; }
            .kanban-col { min-width: 100%; max-height: none !important; }
            .kanban-col-header { position: sticky; top: 0; z-index: 2; }
            .task-card { padding: 0.75rem 0.85rem !important; }
            /* Blog page */
            .blog-sidebar { position: static !important; width: 100% !important; margin-top: 1.5rem; }
            .blog-layout { flex-direction: column; }
            .bulk-bar { flex-direction: column; align-items: stretch; }
            .lead-filters { flex-direction: column; }
            .lead-filters select, .lead-filters input { width: 100%; }
            .finder-panel { position: static !important; }
            .chart-container { height: auto !important; min-height: 200px; }
        }
        @media(max-width:480px){
            html { font-size: 15px; }
            .admin-main { padding: 0.85rem; }
            .sidebar-user { padding: 0.75rem 0.65rem 0.6rem; }
            .sidebar-user .hamburger { font-size: 1.1rem; }
            .sidebar-avatar { width: 30px; height: 30px; font-size: 0.75rem; }
            .sidebar-user-name { font-size: 0.78rem; }
            .sidebar-user-role { font-size: 0.5rem; }
            .sidebar-brand { padding: 1rem 1rem 0.85rem; }
            .sidebar-brand a { font-size: 0.85rem; }
            .sidebar-brand small { font-size: 0.5rem; }
            .sidebar-nav a { padding: 0.5rem 1rem; font-size: 0.78rem; gap: 10px; }
            .sidebar-nav a i { font-size: 1rem; width: 18px; }
            .sidebar-footer a { padding: 0.5rem 1rem; font-size: 0.75rem; }
            .nav-label { padding: 0.65rem 1rem 0.25rem; font-size: 0.55rem; }
            .page-title { font-size: 0.88rem; }
            .page-header { margin-bottom: 1.25rem; }
            .card { padding: 1rem; border-radius: var(--radius-md); }
            th, td { padding: 0.6rem 0.65rem; font-size: 0.72rem; }
            .modal { padding: 1rem; }
            .modal-title { font-size: 0.8rem; }
            .btn { padding: 0.5rem 0.9rem; font-size: 0.75rem; }
            .btn-sm { padding: 0.35rem 0.7rem; font-size: 0.68rem; }
            .form-control { padding: 0.55rem 0.75rem; font-size: 0.82rem; }
            .status-badge { font-size: 0.6rem; padding: 2px 8px; }
            .empty-state { padding: 2rem 0.75rem; }
            .empty-state i { font-size: 1.8rem; }
            .btn-quickaction { font-size: 11px; padding: 9px 12px; }
            .v3-bell { width: 28px; height: 28px; font-size: 0.95rem; }
            .v3-bell-dropdown .bd-header { font-size: 0.62rem; padding: 0.6rem 0.85rem; }
            .v3-bell-dropdown .bd-item { font-size: 0.72rem; padding: 0.55rem 0.85rem; }
            .post-card { padding: 1rem; }
            .blog-layout .blog-sidebar .sidebar-card { padding: 1rem; }
            .tpl-card { padding: 1rem; }
            .lead-card { padding: 1rem; }
        }
    </style>
    <script src="<?= ADMIN_URL ?>/js/admin.js?v=<?= filemtime(__DIR__ . '/../js/admin.js') ?>"></script>
    <script src="<?= ADMIN_URL ?>/assets/js/admin-ui.js?v=<?= filemtime(__DIR__ . '/../assets/js/admin-ui.js') ?>"></script>
    <script src="<?= ADMIN_URL ?>/assets/js/admin-v3.js?v=<?= filemtime(__DIR__ . '/../assets/js/admin-v3.js') ?>"></script>
    <script>window.blogAIProvider='<?= get_setting('ai_provider_blog','groq') ?>';</script>
</head>
<body>
    <button class="mobile-menu-btn" id="mobileMenuBtn" title="Menu">
        <i class="ti ti-menu-2"></i>
    </button>
    <aside class="sidebar v3-sidebar" id="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-avatar" data-name="<?= htmlspecialchars($_SESSION['admin_username'] ?? 'A') ?>"></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></div>
                <div class="sidebar-user-role">Administrator</div>
            </div>
            <button class="v3-bell sidebar-bell" id="v3BellBtn" title="Notifications">
                <i class="ti ti-bell"></i>
                <?php if ($unread_msgs > 0 || $tasksDueToday > 0): ?>
                    <span class="bell-dot"></span>
                <?php endif; ?>
            </button>
            <div class="v3-bell-dropdown" id="v3BellDropdown">
                <div class="bd-header">Notifications</div>
                <?php if ($unread_msgs > 0): ?>
                    <div class="bd-item" onclick="window.location.href='<?= ADMIN_URL ?>/modules/messages.php'">
                        <div class="bd-title"><?= $unread_msgs ?> unread message<?= $unread_msgs !== 1 ? 's' : '' ?></div>
                        <div class="bd-meta">From contact form</div>
                    </div>
                <?php endif; ?>
                <?php if ($tasksDueToday > 0): ?>
                    <div class="bd-item" onclick="window.location.href='<?= ADMIN_URL ?>/modules/tasks.php?view=kanban'">
                        <div class="bd-title"><?= $tasksDueToday ?> task<?= $tasksDueToday !== 1 ? 's' : '' ?> due today</div>
                        <div class="bd-meta">Check your Tasks board</div>
                    </div>
                <?php endif; ?>
                <?php if ($blogDrafts > 0): ?>
                    <div class="bd-item" onclick="window.location.href='<?= ADMIN_URL ?>/modules/blog.php'">
                        <div class="bd-title"><?= $blogDrafts ?> draft blog post<?= $blogDrafts !== 1 ? 's' : '' ?></div>
                        <div class="bd-meta">Ready to publish</div>
                    </div>
                <?php endif; ?>
                <?php if ($unread_msgs === 0 && $tasksDueToday === 0 && $blogDrafts === 0): ?>
                    <div class="bd-empty">All caught up ✨</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="v3-more-section" style="flex:1;overflow-y:auto;padding:0.5rem 0">
            <!-- Dashboard -->
            <a href="<?= ADMIN_URL ?>/index.php" class="v3-focus-item <?= $active === '' || $current === 'index.php' ? 'active' : '' ?>">
                <span class="fi-icon" style="background:rgba(255,255,255,0.04);color:var(--v3-text3)"><i class="ti ti-rocket"></i></span>
                <span class="fi-label">Dashboard</span>
            </a>

            <?php
            $focusItems = [
                ['tasks',        'Tasks',         'ti ti-checklist', 'tasks',     $tasksDueToday],
                ['blog',         'Blog Posts',    'ti ti-news',      'blog',      $blogDrafts],
                ['post-generator','Post Generator','ti ti-file-text','posts',     $unpubPosts],
                ['leads',        'Outreach',      'ti ti-users-plus','outreach',  $newLeadsWeek],
            ];
            foreach ($focusItems as $f):
                $isActive = $active === $f[0];
                $hasItems = $f[4] > 0;
            ?>
                <a href="<?= ADMIN_URL ?>/modules/<?= $f[0] ?>.php" class="v3-focus-item <?= $isActive ? 'active' : '' ?>">
                    <span class="fi-icon <?= $f[3] ?>"><i class="<?= $f[1] === 'Post Generator' ? 'ti ti-file-text' : $f[2] ?>"></i></span>
                    <span class="fi-label"><?= $f[1] ?></span>
                    <?php if ($hasItems): ?>
                        <span class="fi-badge has-items" id="v3Focus<?= ucfirst($f[3]) ?>"><?= $f[4] ?></span>
                    <?php else: ?>
                        <span class="fi-badge" id="v3Focus<?= ucfirst($f[3]) ?>">0</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>

            <div class="nav-separator"></div>

            <!-- Messages (standalone) -->
            <a href="<?= ADMIN_URL ?>/modules/messages.php" class="v3-more-item <?= $active === 'messages' ? 'active' : '' ?>" style="padding:0.55rem 1.25rem">
                <i class="ti ti-mail"></i><span>Messages</span>
                <?php if ($unread_msgs > 0): ?>
                    <span class="unread-badge"><?= $unread_msgs ?></span>
                <?php endif; ?>
            </a>

            <div class="nav-separator"></div>

            <?php
            $contentItems = [
                ['services',     'Services',      'ti ti-settings'],
                ['packages',     'Packages',      'ti ti-box'],
                ['testimonials', 'Testimonials',  'ti ti-quote'],
                ['faq',          'FAQ',           'ti ti-help'],
                ['portfolio',    'Portfolio',     'ti ti-briefcase'],
                ['brands',       'Brands',        'ti ti-building'],
                ['media',        'Media Library', 'ti ti-photo'],
                ['quote-invoice','Quote & Invoice','ti ti-receipt'],
            ];
            foreach ($contentItems as $n):
                $isActive = $active === $n[0];
            ?>
                <a href="<?= ADMIN_URL ?>/modules/<?= $n[0] ?>.php" class="v3-more-item <?= $isActive ? 'active' : '' ?>">
                    <i class="<?= $n[2] ?>"></i><span><?= $n[1] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="v3-sidebar-footer">
            <a href="<?= ADMIN_URL ?>/modules/settings.php" class="<?= $active === 'settings' ? 'active' : '' ?>">
                <i class="ti ti-tool"></i><span>Settings</span>
            </a>
        </div>
        <div class="v3-cmdk-hint">Press <kbd>Ctrl+K</kbd> to search</div>
        <div class="v3-version-badge">v3.0</div>
    </aside>
    <div class="sidebar-backdrop" onclick="document.getElementById('sidebar').classList.remove('open')"></div>
    <main class="admin-main v3-main">
