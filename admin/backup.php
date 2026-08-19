<?php
require_once __DIR__ . '/../config.php';
require_admin();
if (!is_super_admin()) {
    http_response_code(403);
    exit('Backup database hanya dapat dilakukan oleh Super Admin.');
}

$timestamp = gmdate('Ymd\THis');
$filename = 'wedding-invitation-backup-' . $timestamp . '.zip';
$tmpFile = tempnam(sys_get_temp_dir(), 'backup_');
$zip = new ZipArchive();
if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo 'Gagal membuat backup.';
    exit;
}

$files = [CONFIG_FILE, CUSTOM_CSS_FILE, EVENT_ICS_FILE, GUEST_LINKS_FILE];
if (is_readable(DB_PATH)) {
    $files[] = DB_PATH;
}
foreach ($files as $file) {
    if (is_file($file)) {
        $zip->addFile($file, basename($file));
    }
}

$dirs = [UPLOADS_DIR];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $item) {
        if ($item->isFile()) {
            $localPath = substr($item->getRealPath(), strlen(ROOT_DIR) + 1);
            $zip->addFile($item->getRealPath(), $localPath);
        }
    }
}

$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Length: ' . filesize($tmpFile));
readfile($tmpFile);
@unlink($tmpFile);
exit;
