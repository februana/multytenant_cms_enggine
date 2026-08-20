<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config.php';

$config = load_config();
$items = get_gallery_items($config);
$out = [];
foreach ($items as $item) {
    $relativePath = (string)($item['filename'] ?? '');
    if ($relativePath === '') {
        continue;
    }
    $srcUrl = public_path($relativePath);
    // The canonical asset is already bounded and optimized by the upload
    // pipeline; no persistent thumb derivative is generated here.
    $out[] = [
        'src' => $srcUrl,
        'thumb' => $srcUrl,
    ];
}
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
