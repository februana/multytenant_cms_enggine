<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
$config = load_config();
$output = [];

$items = get_gallery_items($config);
foreach ($items as $item) {
    $output[] = ['src' => '/' . ltrim($item['filename'], '/'), 'thumb' => '/' . ltrim($item['filename'], '/')];
}

if (empty($output)) {
    $fallbackDir = __DIR__ . '/gallery';
    if (is_dir($fallbackDir)) {
        foreach (scandir($fallbackDir) ?: [] as $file) {
            if ($file === '.' || $file === '..' || $file === 'thumbs') {
                continue;
            }
            $path = $fallbackDir . '/' . $file;
            if (!is_file($path)) {
                continue;
            }
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_IMAGE_TYPES, true)) {
                continue;
            }
            $output[] = '/' . 'gallery/' . rawurlencode($file);
        }
    }
}

echo json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
