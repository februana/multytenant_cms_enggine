<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config.php';

$storageDir = UPLOADS_GALLERY_DIR;
$webDir = 'uploads/gallery';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$allowed = ['jpg','jpeg','png','gif','webp'];
$coverNames = ['cover.webp','cover.jpg','cover.jpeg'];
$items = [];
foreach (scandir($storageDir) ?: [] as $f) {
    if ($f === '.' || $f === '..' || $f === 'thumbs') continue;
    $path = $storageDir . '/' . $f;
    if (!is_file($path)) continue;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) continue;
    if (in_array(strtolower($f), $coverNames, true)) continue;
    $items[] = ['name' => $f, 'mtime' => filemtime($path) ?: 0];
}
usort($items, function($a, $b){ return $b['mtime'] <=> $a['mtime']; });
$thumbDir = $storageDir . '/thumbs';
if (!is_dir($thumbDir)) @mkdir($thumbDir, 0755, true);

function create_thumb($srcPath, $thumbPath, $maxWidth = 600) {
    if (!extension_loaded('gd')) return false;
    $data = @file_get_contents($srcPath);
    if ($data === false) return false;
    $srcImg = @imagecreatefromstring($data);
    if (!$srcImg) return false;
    $w = imagesx($srcImg);
    $h = imagesy($srcImg);
    if ($w <= 0 || $h <= 0) { imagedestroy($srcImg); return false; }
    if ($w > $maxWidth) {
        $newW = $maxWidth;
        $newH = (int) (($h * $newW) / $w);
    } else { $newW = $w; $newH = $h; }
    $tmp = imagecreatetruecolor($newW, $newH);
    imagecopyresampled($tmp, $srcImg, 0,0,0,0, $newW, $newH, $w, $h);
    $tmpFile = $thumbPath . '.tmp';
    $ok = false;
    if (function_exists('imagewebp')) {
        $ok = imagewebp($tmp, $tmpFile, 80);
    } elseif (function_exists('imagejpeg')) {
        $ok = imagejpeg($tmp, $tmpFile, 85);
    }
    imagedestroy($tmp);
    imagedestroy($srcImg);
    if ($ok) {
        @rename($tmpFile, $thumbPath);
        return file_exists($thumbPath);
    }
    @unlink($tmpFile);
    return false;
}

$out = [];
foreach ($items as $it) {
    $name = $it['name'];
    $srcUrl = $webDir . '/' . rawurlencode($name);
    $base = pathinfo($name, PATHINFO_FILENAME);
    $thumbName = $base . '.webp';
    $thumbPath = $thumbDir . '/' . $thumbName;
    $thumbUrl = $webDir . '/thumbs/' . rawurlencode($thumbName);
    $fullSrcPath = $storageDir . '/' . $name;
    if (!file_exists($thumbPath)) {
        @create_thumb($fullSrcPath, $thumbPath);
    }
    if (file_exists($thumbPath)) {
        $out[] = ['src' => $srcUrl, 'thumb' => $thumbUrl];
    } else {
        $out[] = $srcUrl;
    }
}
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
