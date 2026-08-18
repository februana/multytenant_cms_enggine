<?php
require_once dirname(__DIR__) . '/config.php';

$cleanup = in_array('--cleanup', $argv ?? [], true);
$config = load_config();
$groups = [
    'cover' => UPLOADS_COVER_DIR,
    'background' => UPLOADS_BACKGROUND_DIR,
    'gallery' => UPLOADS_GALLERY_DIR,
    'love_story' => UPLOADS_LOVE_STORY_DIR,
    'theme_assets' => UPLOADS_THEME_ASSETS_DIR,
];

$rows = [];
foreach ($groups as $group => $root) {
    if (!is_dir($root)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) continue;
        $path = $fileInfo->getPathname();
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, array_merge(ALLOWED_IMAGE_TYPES, ['tmp', 'bak']), true)) continue;
        $relative = relative_path($path);
        $usage = detect_media_usage($config, $relative);
        $info = $extension === 'webp' ? @getimagesize($path) : false;
        $rows[] = [
            'group' => $group,
            'path' => $relative,
            'extension' => $extension,
            'bytes' => (int)filesize($path),
            'mime' => safe_image_mime($path) ?: 'unknown',
            'dimensions' => is_array($info) ? ($info[0] . 'x' . $info[1]) : '',
            'referenced' => !empty($usage),
            'used_by' => $usage,
            'canonical' => $extension === 'webp' && safe_image_mime($path) === 'image/webp',
        ];
    }
}

usort($rows, static fn(array $a, array $b): int => strcmp($a['path'], $b['path']));
echo "Media inventory (dry-run=" . ($cleanup ? 'false' : 'true') . ")\n";
echo "Group | Path | Format | Dimensions | Bytes | Referenced | Used by | Canonical\n";
foreach ($rows as $row) {
    echo implode(' | ', [
        $row['group'],
        $row['path'],
        $row['mime'],
        $row['dimensions'] ?: '-',
        (string)$row['bytes'],
        $row['referenced'] ? 'YES' : 'NO',
        $row['used_by'] ? implode(', ', $row['used_by']) : '-',
        $row['canonical'] ? 'YES' : 'NO',
    ]) . "\n";
}

if (!$cleanup) {
    echo "No files were deleted. Use --cleanup only after reviewing the inventory.\n";
    exit(0);
}

$deleted = 0;
foreach ($rows as $row) {
    if ($row['extension'] !== 'jpg' && $row['extension'] !== 'jpeg' && $row['extension'] !== 'png' && $row['extension'] !== 'gif') continue;
    if ($row['referenced']) continue;
    $candidate = preg_replace('/\.(?:jpg|jpeg|png|gif)$/i', '.webp', $row['path']);
    $candidatePath = ROOT_DIR . '/' . $candidate;
    if (!is_file($candidatePath) || !verify_webp_output($candidatePath)) continue;
    if (delete_uploaded_asset($row['path'])) {
        $deleted++;
        echo "DELETED: {$row['path']} (verified sibling {$candidate})\n";
    }
}
echo "Deleted {$deleted} safe obsolete source file(s). Unique or referenced media was preserved.\n";
