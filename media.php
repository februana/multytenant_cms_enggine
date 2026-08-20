<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$currentTenant = current_tenant(true);
$requestedPath = normalize_media_relative_path((string)($_GET['path'] ?? ''));
if ($requestedPath === null || !media_path_is_safe_storage($requestedPath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Media tidak ditemukan.';
    exit;
}

$absolutePath = ROOT_DIR . '/' . $requestedPath;
if (!is_file($absolutePath) || !is_readable($absolutePath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Media tidak ditemukan.';
    exit;
}

$mime = safe_image_mime($absolutePath);
if ($mime === null || !(str_starts_with($mime, 'image/') || str_starts_with($mime, 'audio/') || str_starts_with($mime, 'video/'))) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Tipe media tidak diizinkan.';
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($absolutePath));
header('Content-Disposition: inline; filename="' . str_replace(['"', "\\"], '', basename($absolutePath)) . '"');
header('Cache-Control: private, no-store');
header('Vary: Host');
readfile($absolutePath);
