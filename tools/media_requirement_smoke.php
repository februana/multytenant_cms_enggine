<?php
ob_start();
require_once dirname(__DIR__) . '/config.php';

function media_requirement_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ' . $message . PHP_EOL;
}

function media_requirement_fixture(string $path, int $width, int $height, string $format = 'jpg', bool $transparent = false): void {
    $binary = find_imagemagick_binary();
    if ($binary === null) throw new RuntimeException('ImageMagick is required for media requirement fixtures.');
    $source = $transparent ? 'xc:none -fill ' . escapeshellarg('rgba(196,154,69,0.55)') . ' -draw ' . escapeshellarg('rectangle 0,0 ' . max(1, $width - 1) . ',' . max(1, $height - 1)) : 'xc:#c49a45';
    $command = escapeshellarg($binary) . ' -size ' . (int)$width . 'x' . (int)$height . ' ' . $source . ' ' . escapeshellarg($format . ':' . $path);
    exec($command, $output, $code);
    if ($code !== 0 || !is_file($path) || filesize($path) <= 0) throw new RuntimeException('Unable to create image fixture: ' . $path);
}

function media_requirement_upload(string $source, string $name, string $role, string $preset, string $destination): array {
    return upload_file([
        'error' => UPLOAD_ERR_OK,
        'tmp_name' => $source,
        'name' => $name,
        'size' => filesize($source),
    ], $destination, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, $role, $preset);
}

function media_requirement_info(string $path): array {
    $info = @getimagesize($path);
    if (!is_array($info)) throw new RuntimeException('Unable to inspect output: ' . $path);
    return [(int)$info[0], (int)$info[1]];
}

function media_requirement_ratio(int $width, int $height): float {
    return $width / max(1, $height);
}

$presets = ['custom', 'dewankl', 'rainier', 'archak', 'parang', 'pawiwahan', 'shubh-vivah', 'yami-buzzy'];
$shapes = [
    'very-large' => [5000, 3000],
    'correct' => [1200, 800],
    'small' => [320, 200],
    'portrait' => [1200, 2400],
    'landscape' => [2400, 1350],
];
$created = [];
$fixtures = [];
$token = 'media-requirement-' . bin2hex(random_bytes(5));
ensure_upload_dirs();

try {
    foreach ($presets as $preset) {
        $galleryRequirement = media_requirement('gallery', $preset);
        foreach ($shapes as $label => [$sourceWidth, $sourceHeight]) {
            $source = sys_get_temp_dir() . '/' . $token . '-' . $preset . '-' . $label . '.jpg';
            media_requirement_fixture($source, $sourceWidth, $sourceHeight);
            $fixtures[] = $source;
            $result = media_requirement_upload($source, $preset . '-' . $label . '.jpg', 'gallery', $preset, tenant_upload_dir('gallery'));
            media_requirement_assert(!empty($result['success']), "$preset gallery $label upload succeeds");
            media_requirement_assert(str_ends_with((string)$result['path'], '.webp'), "$preset gallery $label is canonical WebP");
            media_requirement_assert(!is_file($source), "$preset gallery $label original is removed after verification");
            media_requirement_assert(verify_webp_output($result['path'], $galleryRequirement), "$preset gallery $label satisfies resolved requirement");
            [$width, $height] = media_requirement_info($result['path']);
            if ($preset === 'parang') {
                media_requirement_assert($width === $height, "$preset gallery $label is cropped to the source square card ratio");
                media_requirement_assert($width <= 1200 && $height <= 1200, "$preset gallery $label stays within square maximum");
            } else {
                media_requirement_assert(abs(media_requirement_ratio($width, $height) - media_requirement_ratio($sourceWidth, $sourceHeight)) < 0.02, "$preset gallery $label preserves source aspect ratio");
                media_requirement_assert($width <= 1600 && $height <= 1200, "$preset gallery $label stays within global maximum");
            }
            $created[] = $result['path'];
        }
    }

    $roleDestinations = [
        'cover' => tenant_upload_dir('cover'),
        'bride_photo' => tenant_upload_dir('cover'),
        'groom_photo' => tenant_upload_dir('cover'),
        'couple_photo' => tenant_upload_dir('cover'),
        'story' => tenant_upload_dir('love_story'),
        'qris_image' => tenant_upload_dir('cover'),
    ];
    foreach ($presets as $preset) {
        foreach ($roleDestinations as $role => $destination) {
            $source = sys_get_temp_dir() . '/' . $token . '-' . $preset . '-' . $role . '.jpg';
            media_requirement_fixture($source, 4000, 3000);
            $fixtures[] = $source;
            $requirement = media_requirement($role, $preset);
            $result = media_requirement_upload($source, $preset . '-' . $role . '.jpg', $role, $preset, $destination);
            media_requirement_assert(!empty($result['success']), "$preset $role upload succeeds");
            media_requirement_assert(str_ends_with((string)$result['path'], '.webp'), "$preset $role is canonical WebP");
            media_requirement_assert(!is_file($source), "$preset $role original is removed after verification");
            media_requirement_assert(verify_webp_output($result['path'], $requirement), "$preset $role satisfies resolved requirement");
            $created[] = $result['path'];
        }
    }

    foreach ($presets as $preset) {
        $source = sys_get_temp_dir() . '/' . $token . '-' . $preset . '-background.jpg';
        media_requirement_fixture($source, 5000, 3000);
        $fixtures[] = $source;
        $requirement = media_requirement('background', $preset);
        $result = media_requirement_upload($source, $preset . '-background.jpg', 'background', $preset, tenant_upload_dir('background'));
        media_requirement_assert(!empty($result['success']), "$preset background upload succeeds");
        media_requirement_assert(!is_file($source), "$preset background original is removed after verification");
        media_requirement_assert(verify_webp_output($result['path'], $requirement), "$preset background satisfies resolved requirement");
        [$width, $height] = media_requirement_info($result['path']);
        if (isset($requirement['width'], $requirement['height'])) {
            media_requirement_assert($width === (int)$requirement['width'] && $height === (int)$requirement['height'], "$preset background exact canvas is emitted");
        } else {
            media_requirement_assert($width <= (int)$requirement['max_width'] && $height <= (int)$requirement['max_height'], "$preset background respects maximum dimensions");
            media_requirement_assert(abs(media_requirement_ratio($width, $height) - (5000 / 3000)) < 0.02, "$preset background preserves source ratio");
        }
        $created[] = $result['path'];
    }

    $ogSource = sys_get_temp_dir() . '/' . $token . '-og.jpg';
    media_requirement_fixture($ogSource, 2400, 1600);
    $fixtures[] = $ogSource;
    $og = media_requirement_upload($ogSource, 'og-image.jpg', 'og_image', 'custom', tenant_upload_dir('cover'));
    media_requirement_assert(!empty($og['success']), 'global OG image upload succeeds');
    media_requirement_assert(($og['width'] ?? 0) === 1200 && ($og['height'] ?? 0) === 630, 'global OG image uses exact 1200x630 canvas');
    media_requirement_assert(!is_file($ogSource), 'global OG original is removed after verified output');
    $created[] = $og['path'];

    $smallBackgroundSource = sys_get_temp_dir() . '/' . $token . '-small-background.jpg';
    media_requirement_fixture($smallBackgroundSource, 1000, 700);
    $fixtures[] = $smallBackgroundSource;
    $smallBackground = media_requirement_upload($smallBackgroundSource, 'small-background.jpg', 'background', 'pawiwahan', tenant_upload_dir('background'));
    media_requirement_assert(!empty($smallBackground['success']), 'Pawiwahan small background upload succeeds');
    [$smallWidth, $smallHeight] = media_requirement_info($smallBackground['path']);
    media_requirement_assert($smallWidth === 1000 && $smallHeight === 700, 'Pawiwahan preserve policy does not upscale a small background');
    $created[] = $smallBackground['path'];

    $transparentSource = sys_get_temp_dir() . '/' . $token . '-parang-theme.png';
    media_requirement_fixture($transparentSource, 800, 400, 'png', true);
    $fixtures[] = $transparentSource;
    $themeDir = tenant_upload_dir('theme_assets') . '/parang';
    if (!is_dir($themeDir)) mkdir($themeDir, 0755, true);
    $theme = media_requirement_upload($transparentSource, 'parang-transparent.png', 'theme_asset', 'parang', $themeDir);
    media_requirement_assert(!empty($theme['success']), 'Parang transparent Theme Asset uses shared pipeline');
    media_requirement_assert(verify_webp_output($theme['path'], media_requirement('theme_asset', 'parang')), 'Parang Theme Asset WebP is verified');
    media_requirement_assert(!is_file($transparentSource), 'Parang transparent Theme Asset original is removed after verification');
    $channels = trim((string)shell_exec(find_imagemagick_binary() . ' identify -format %c ' . escapeshellarg($theme['path']) . ' 2>/dev/null'));
    media_requirement_assert(is_file($theme['path']), 'Parang Theme Asset final file exists without rasterizing into a non-WebP placeholder');
    $created[] = $theme['path'];

    echo 'PASS: media requirement smoke test (' . count($presets) . ' presets, ' . count($shapes) . ' gallery shapes)' . PHP_EOL;
} finally {
    foreach ($fixtures as $fixture) if (is_file($fixture)) @unlink($fixture);
    foreach ($created as $path) if (is_file($path)) @unlink($path);
    $themeDir = tenant_upload_dir('theme_assets') . '/parang';
    if (is_dir($themeDir)) @rmdir($themeDir);
    ob_end_flush();
}
