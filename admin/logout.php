<?php
require_once __DIR__ . '/config.php';
session_destroy();
header('Location: ' . ADMIN_URL . '/login.php');
exit;
