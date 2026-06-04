<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/supabase.php';
supabase_logout();
header('Location: ' . ADMIN_URL . '/login.php');
exit;
