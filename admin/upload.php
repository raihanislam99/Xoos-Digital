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
$allowed = ['jpg','jpeg','png','gif','webp','avif'];
if (!in_array($ext, $allowed)) {
    json_response(['error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed)], 400);
}

$dir = __DIR__ . '/uploads';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$name = uniqid();
$dest = $dir . '/' . $name . '.' . $ext;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_response(['error' => 'Failed to save file'], 500);
}

// Convert to WebP with resize (skip SVG — vector format)
$finalName = $name . '.webp';
$finalDest = $dir . '/' . $finalName;
$converted = false;

if ($ext !== 'svg') {
    $img = null;
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            if (function_exists('imagecreatefromjpeg')) $img = @imagecreatefromjpeg($dest);
            break;
        case 'png':
            if (function_exists('imagecreatefrompng')) $img = @imagecreatefrompng($dest);
            break;
        case 'gif':
            if (function_exists('imagecreatefromgif')) $img = @imagecreatefromgif($dest);
            break;
        case 'webp':
            if (function_exists('imagecreatefromwebp')) $img = @imagecreatefromwebp($dest);
            break;
        case 'avif':
            if (function_exists('imagecreatefromavif')) $img = @imagecreatefromavif($dest);
            break;
    }

    if ($img) {
        // Convert palette images to truecolor (WebP doesn't support palette)
        if (!imageistruecolor($img)) {
            $w = imagesx($img);
            $h = imagesy($img);
            $tc = imagecreatetruecolor($w, $h);
            imagealphablending($tc, false);
            imagesavealpha($tc, true);
            imagecopy($tc, $img, 0, 0, 0, 0, $w, $h);
            imagedestroy($img);
            $img = $tc;
        }

        $ow = imagesx($img);
        $oh = imagesy($img);
        $mw = 1080;
        $mh = 1920;
        $nw = $ow;
        $nh = $oh;

        if ($nw > $mw) { $nh = round($nh * $mw / $nw); $nw = $mw; }
        if ($nh > $mh) { $nw = round($nw * $mh / $nh); $nh = $mh; }

        if ($nw !== $ow || $nh !== $oh) {
            $resized = imagecreatetruecolor($nw, $nh);
            if ($ext === 'png' || $ext === 'gif') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $nw, $nh, $ow, $oh);
            imagedestroy($img);
            $img = $resized;
        }

        if (imagewebp($img, $finalDest, 80)) {
            $converted = true;
        }
        imagedestroy($img);
    }
}

if ($converted) {
    unlink($dest);
    $finalFile = $finalName;
} else {
    $finalFile = $name . '.' . $ext;
}

$filepath = 'admin/uploads/' . $finalFile;
$url = BASE_URL . '/' . $filepath;

// Save to media_files for media library
try {
    db_insert('media_files', [
        'filename' => $finalFile,
        'original_name' => $file['name'],
        'filepath' => $filepath,
        'filesize' => filesize($dir . '/' . $finalFile),
        'mime_type' => 'image/webp',
    ]);
} catch (Throwable $e) {
    // Non-critical; continue
}

json_response(['success' => true, 'url' => $url, 'filename' => $finalFile]);
