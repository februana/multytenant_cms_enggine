<?php
ob_start();
require_once dirname(__DIR__) . '/config.php';

function media_pipeline_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ' . $message . PHP_EOL;
}

function media_pipeline_fixture(string $path, int $width, int $height, string $format = 'jpg'): void {
    $binary = find_imagemagick_binary();
    if ($binary === null) throw new RuntimeException('ImageMagick is required for media pipeline smoke fixtures.');
    $command = escapeshellarg($binary) . ' -size ' . (int)$width . 'x' . (int)$height . ' xc:#c49a45 ' . escapeshellarg($format . ':' . $path);
    exec($command, $output, $code);
    if ($code !== 0 || !is_file($path) || filesize($path) <= 0) throw new RuntimeException('Unable to create image fixture.');
}

function media_pipeline_upload(string $source, string $name, string $destination, string $role, ?string $preset = null): array {
    return upload_file([
        'error' => UPLOAD_ERR_OK,
        'tmp_name' => $source,
        'name' => $name,
        'size' => filesize($source),
    ], $destination, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, $role, $preset);
}

$created = [];
$fixtures = [];
$token = 'media-pipeline-' . bin2hex(random_bytes(5));
$coverDir = tenant_upload_dir('cover');
$backgroundDir = tenant_upload_dir('background');
$galleryDir = tenant_upload_dir('gallery');
$storyDir = tenant_upload_dir('love_story');
$themeDir = tenant_upload_dir('theme_assets') . '/parang';
ensure_upload_dirs();

try {
    foreach ([$themeDir] as $dir) if (!is_dir($dir)) mkdir($dir, 0755, true);

    $coverSource = sys_get_temp_dir() . '/' . $token . '-cover.jpg';
    media_pipeline_fixture($coverSource, 4000, 3000, 'jpg');
    $fixtures[] = $coverSource;
    $cover = media_pipeline_upload($coverSource, 'cover.jpg', $coverDir, 'cover', 'parang');
    media_pipeline_assert(!empty($cover['success']), 'cover upload processes successfully');
    media_pipeline_assert(str_ends_with($cover['path'], '.webp'), 'cover reference is canonical WebP');
    media_pipeline_assert(!is_file($coverSource), 'cover original is removed after verified output');
    media_pipeline_assert(verify_webp_output($cover['path'], media_requirement('cover', 'parang')), 'cover output verifies MIME, bytes, and bounded dimensions');
    $created[] = $cover['path'];

    $backgroundSource = sys_get_temp_dir() . '/' . $token . '-background.jpg';
    media_pipeline_fixture($backgroundSource, 5000, 3000, 'jpg');
    $fixtures[] = $backgroundSource;
    $background = media_pipeline_upload($backgroundSource, 'background.jpg', $backgroundDir, 'background', 'parang');
    media_pipeline_assert(!empty($background['success']), 'background upload processes successfully');
    media_pipeline_assert(($background['width'] ?? 0) <= 2400 && ($background['height'] ?? 0) <= 1600, 'Parang background respects the global bounded-preserve requirement');
    media_pipeline_assert(abs((($background['width'] ?? 0) / max(1, ($background['height'] ?? 0))) - (5000 / 3000)) < 0.02, 'Parang background preserves source aspect ratio');
    media_pipeline_assert(!is_file($backgroundSource), 'background original is removed after verified output');
    $created[] = $background['path'];

    foreach ([
        ['bride', 'bride.jpg', 'bride_photo', $coverDir],
        ['groom', 'groom.jpg', 'groom_photo', $coverDir],
        ['gallery', 'gallery.jpg', 'gallery', $galleryDir],
        ['story', 'story.jpg', 'story', $storyDir],
    ] as [$label, $name, $role, $destination]) {
        $source = sys_get_temp_dir() . '/' . $token . '-' . $label . '.jpg';
        media_pipeline_fixture($source, 2400, 1600, 'jpg');
        $fixtures[] = $source;
        $result = media_pipeline_upload($source, $name, $destination, $role, 'parang');
        media_pipeline_assert(!empty($result['success']), $label . ' upload processes successfully');
        media_pipeline_assert(str_ends_with($result['path'], '.webp'), $label . ' reference is canonical WebP');
        media_pipeline_assert(!is_file($source), $label . ' original is removed after verified output');
        media_pipeline_assert(verify_webp_output($result['path'], media_requirement($role, 'parang')), $label . ' output dimensions and MIME verify');
        $created[] = $result['path'];
        if ($label === 'gallery') $galleryResult = $result;
    }

    $galleryConfig = config_defaults();
    $galleryConfig['gallery']['items'] = [['filename' => relative_path($galleryResult['path']), 'order' => 1]];
    $galleryItems = get_gallery_items($galleryConfig);
    media_pipeline_assert(count($galleryItems) === 1 && $galleryItems[0]['filename'] === relative_path($galleryResult['path']), 'Gallery membership remains explicit after processing');

    $themeSource = sys_get_temp_dir() . '/' . $token . '-gunungan.jpg';
    media_pipeline_fixture($themeSource, 2400, 1600, 'jpg');
    $fixtures[] = $themeSource;
    $themeAsset = media_pipeline_upload($themeSource, 'parang-gunungan.jpg', $themeDir, 'theme_asset', 'parang');
    media_pipeline_assert(!empty($themeAsset['success']), 'Parang theme asset processes through the shared pipeline');
    media_pipeline_assert(str_contains(relative_path($themeAsset['path']), '/theme-assets/parang/'), 'Parang theme asset uses the preset-scoped theme-assets directory');
    media_pipeline_assert(str_ends_with($themeAsset['path'], '.webp') && !is_file($themeSource), 'Parang theme asset leaves only the final WebP');
    $themeOnlyConfig = config_defaults();
    $themeOnlyConfig['gallery']['items'] = [];
    media_pipeline_assert(get_gallery_items($themeOnlyConfig) === [], 'Theme asset does not become Gallery automatically');
    $created[] = $themeAsset['path'];

    $invalidSource = sys_get_temp_dir() . '/' . $token . '-invalid.jpg';
    file_put_contents($invalidSource, 'not an image');
    $fixtures[] = $invalidSource;
    $invalid = media_pipeline_upload($invalidSource, 'invalid.jpg', $coverDir, 'cover', 'parang');
    media_pipeline_assert(empty($invalid['success']), 'invalid image is rejected safely');
    media_pipeline_assert(is_file($invalidSource), 'invalid source remains available for failure reporting');

    $failureSource = sys_get_temp_dir() . '/' . $token . '-failure.jpg';
    file_put_contents($failureSource, 'broken image payload');
    $fixtures[] = $failureSource;
    $failure = media_pipeline_upload($failureSource, 'failure.jpg', $backgroundDir, 'background', 'parang');
    media_pipeline_assert(empty($failure['success']), 'processing failure is reported');
    media_pipeline_assert(is_file($failureSource), 'processing failure preserves the temporary original');
    media_pipeline_assert(count(glob($backgroundDir . '/.' . '*.webp.tmp') ?: []) === 0, 'processing failure leaves no temporary WebP in canonical storage');

    $oldSource = sys_get_temp_dir() . '/' . $token . '-old.png';
    media_pipeline_fixture($oldSource, 800, 600, 'png');
    $oldAsset = media_pipeline_upload($oldSource, 'old-cover.png', $coverDir, 'cover', 'parang');
    media_pipeline_assert(!empty($oldAsset['success']), 'old cover fixture is canonicalized');
    $created[] = $oldAsset['path'];
    $replacementSource = sys_get_temp_dir() . '/' . $token . '-replacement.jpg';
    media_pipeline_fixture($replacementSource, 1000, 700, 'jpg');
    $fixtures[] = $replacementSource;
    $replacement = replace_uploaded_asset(relative_path($oldAsset['path']), ['error' => UPLOAD_ERR_OK, 'tmp_name' => $replacementSource, 'name' => 'new-cover.jpg', 'size' => filesize($replacementSource)]);
    media_pipeline_assert(!empty($replacement['success']), 'replacement produces a new verified canonical WebP');
    media_pipeline_assert(is_file($oldAsset['path']), 'old asset remains until reference update and save succeed');
    $replacementConfig = config_defaults();
    $replacementConfig['media']['cover'] = $replacement['old_path'];
    replace_media_references($replacementConfig, $replacement['old_path'], $replacement['path']);
    media_pipeline_assert(($replacementConfig['media']['cover'] ?? '') === $replacement['path'], 'replacement updates the media reference before cleanup');
    media_pipeline_assert(cleanup_replaced_media($replacement['old_path'], $replacementConfig), 'old replacement asset is removed after reference update');
    $created[] = ROOT_DIR . '/' . $replacement['path'];
    media_pipeline_assert(!is_file(ROOT_DIR . '/' . $replacement['old_path']), 'old replacement asset no longer remains after safe cleanup');

    $smallSource = sys_get_temp_dir() . '/' . $token . '-small.jpg';
    media_pipeline_fixture($smallSource, 320, 200, 'jpg');
    $small = media_pipeline_upload($smallSource, 'small-gallery.jpg', $galleryDir, 'gallery', 'parang');
    media_pipeline_assert(!empty($small['success']), 'small gallery image processes successfully');
    media_pipeline_assert(($small['width'] ?? 0) <= 320 && ($small['height'] ?? 0) <= 200, 'small gallery image is not unnecessarily upscaled');
    $created[] = $small['path'];

    $legacyGallery = $galleryDir . '/' . $token . '-legacy.jpg';
    media_pipeline_fixture($legacyGallery, 640, 480, 'jpg');
    $libraryItems = list_media_library(['group' => 'gallery', 'type' => 'image']);
    media_pipeline_assert(!in_array(relative_path($legacyGallery), array_column($libraryItems, 'path'), true), 'File Manager does not present legacy source images as canonical assets');
    @unlink($legacyGallery);

    $galleryEndpointSource = file_get_contents(ROOT_DIR . '/app/gallery.php');
    media_pipeline_assert(is_string($galleryEndpointSource) && !str_contains($galleryEndpointSource, 'create_thumb') && !str_contains($galleryEndpointSource, 'uploads/gallery/thumbs'), 'Gallery endpoint does not create persistent derivative thumbnails');
    $backupSource = file_get_contents(ROOT_DIR . '/admin/backup.php');
    $restoreSource = file_get_contents(ROOT_DIR . '/admin/restore.php');
    media_pipeline_assert(is_string($backupSource) && str_contains($backupSource, 'UPLOADS_DIR'), 'Backup includes the canonical uploads tree');
    media_pipeline_assert(is_string($restoreSource) && str_contains($restoreSource, 'str_starts_with($entry,'), 'Restore accepts canonical uploads including Theme Assets');

    echo "PASS: media pipeline smoke test" . PHP_EOL;
} finally {
    foreach ($fixtures as $fixture) if (is_file($fixture)) @unlink($fixture);
    foreach ($created as $path) if (is_file($path)) @unlink($path);
    if (is_dir($themeDir)) {
        foreach (glob($themeDir . '/*') ?: [] as $path) if (is_file($path) && str_contains(basename($path), $token)) @unlink($path);
        @rmdir($themeDir);
    }
    ob_end_flush();
}
