<?php
require_once __DIR__ . '/../config.php';
require_admin();

function safe_extract_path(string $name): ?string {
    $clean = str_replace(['\\', '..'], ['', ''], $name);
    $clean = ltrim($clean, '/');
    if ($clean === '') {
        return null;
    }
    return ROOT_DIR . '/' . $clean;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin?tab=backup');
    exit;
}
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    echo 'Token CSRF tidak valid.';
    exit;
}
if (empty($_FILES['restore_file']) || $_FILES['restore_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo 'File restore tidak diterima.';
    exit;
}
$tmpFile = tempnam(sys_get_temp_dir(), 'restore_');
if (!move_uploaded_file($_FILES['restore_file']['tmp_name'], $tmpFile)) {
    http_response_code(500);
    echo 'Gagal menyimpan file restore sementara.';
    exit;
}
$zip = new ZipArchive();
if ($zip->open($tmpFile) !== true) {
    @unlink($tmpFile);
    http_response_code(400);
    echo 'File ZIP tidak valid.';
    exit;
}

$allowedTopFiles = ['config.json', 'custom.css', 'event.ics', 'guest-links.json', basename(DB_PATH)];
$allowedFolders = ['uploads/'];
$tmpDir = sys_get_temp_dir() . '/restore_' . bin2hex(random_bytes(6));
@mkdir($tmpDir, 0755, true);
$extracted = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $entry = $zip->getNameIndex($i);
    $target = safe_extract_path($entry);
    if ($target === null) {
        continue;
    }
    $allowed = false;
    foreach ($allowedTopFiles as $allowedFile) {
        if ($entry === $allowedFile) {
            $allowed = true;
            break;
        }
    }
    foreach ($allowedFolders as $folder) {
        if (str_starts_with($entry, $folder)) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) {
        continue;
    }
    $extractPath = $tmpDir . '/' . ltrim($entry, '/');
    $extractDir = dirname($extractPath);
    @mkdir($extractDir, 0755, true);
    if (!$zip->extractTo($tmpDir, $entry)) {
        continue;
    }
    $extracted[] = $entry;
}
$zip->close();
@unlink($tmpFile);

if (!in_array('config.json', $extracted, true) && !in_array(basename(DB_PATH), $extracted, true)) {
    http_response_code(400);
    echo 'Backup tidak berisi file konfigurasi atau database yang valid.';
    exit;
}

foreach ($extracted as $entry) {
    $source = $tmpDir . '/' . ltrim($entry, '/');
    $destination = ROOT_DIR . '/' . ltrim($entry, '/');
    if (is_dir($source)) {
        continue;
    }
    $destDir = dirname($destination);
    if (!is_dir($destDir)) {
        @mkdir($destDir, 0755, true);
    }
    @copy($source, $destination);
}

function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir, SCANDIR_SORT_NONE);
    if ($items === false) return;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) rrmdir($path);
        else @unlink($path);
    }
    @rmdir($dir);
}
rrmdir($tmpDir);
header('Location: /admin?tab=backup');
exit;
