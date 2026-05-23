<?php
require_once __DIR__ . '/inc/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    json_response(['error' => 'Upload failed'], 400);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','gif','webp','svg','avif'];
if (!in_array($ext, $allowed)) {
    json_response(['error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed)], 400);
}

$dir = __DIR__ . '/uploads';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$name = uniqid() . '.' . $ext;
$dest = $dir . '/' . $name;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_response(['error' => 'Failed to save file'], 500);
}

$filepath = 'admin/uploads/' . $name;
$url = $filepath;

// Save to media_files for media library
try {
    db_insert('media_files', [
        'filename' => $name,
        'original_name' => $file['name'],
        'filepath' => $filepath,
        'filesize' => $file['size'],
        'mime_type' => $file['type'],
    ]);
} catch (Exception $e) {
    // Non-critical; continue
}

json_response(['success' => true, 'url' => $url, 'filename' => $name]);
