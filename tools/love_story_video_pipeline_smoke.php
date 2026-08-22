<?php
declare(strict_types=1);

require_once __DIR__ . '/tenant_smoke_fixture.php';
$fixture = tenant_smoke_bootstrap('love-story-video');
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function love_story_video_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ' . $message . PHP_EOL;
}

function love_story_video_fixture(string $path): void {
    $ffmpeg = find_ffmpeg_binary();
    if ($ffmpeg === null) throw new RuntimeException('FFmpeg is required for video pipeline smoke.');
    $command = escapeshellarg($ffmpeg) . ' -hide_banner -loglevel error -y'
        . ' -f lavfi -i ' . escapeshellarg('testsrc=size=1920x1080:rate=60')
        . ' -f lavfi -i ' . escapeshellarg('sine=frequency=440:sample_rate=48000')
        . ' -t 2 -shortest -c:v libx264 -pix_fmt yuv444p -c:a aac -b:a 192k -movflags +faststart '
        . escapeshellarg($path);
    exec($command, $output, $code);
    if ($code !== 0 || !is_file($path) || filesize($path) <= 0) throw new RuntimeException('Unable to create video fixture.');
}

$created = [];
$fixtures = [];
ensure_upload_dirs();
try {
    $source = sys_get_temp_dir() . '/love-story-video-source-' . bin2hex(random_bytes(5)) . '.mp4';
    love_story_video_fixture($source);
    $fixtures[] = $source;
    $result = upload_file([
        'error' => UPLOAD_ERR_OK,
        'tmp_name' => $source,
        'name' => 'love-story-source.mp4',
        'size' => filesize($source),
    ], tenant_upload_dir('love_story'), ALLOWED_VIDEO_TYPES, MAX_VIDEO_UPLOAD_SIZE, 'love_story_video', 'dewankl');
    love_story_video_assert(!empty($result['success']), 'video upload transcodes successfully' . ((string)($result['error'] ?? '') !== '' ? ': ' . (string)$result['error'] : ''));
    love_story_video_assert(str_ends_with((string)$result['path'], '.mp4'), 'video output uses canonical MP4 extension');
    love_story_video_assert(!is_file($source), 'video source is removed only after verified output');
    $created[] = $result['path'];
    $requirement = media_requirement('love_story_video', 'dewankl');
    $video = probe_video_stream($result['path']);
    $audio = probe_video_stream($result['path'], true);
    love_story_video_assert(verify_mp4_output($result['path'], $requirement), 'video output passes MP4, codec, pixel-format, dimension, and FPS verification');
    love_story_video_assert(($video['codec_name'] ?? '') === 'h264', 'video output uses H.264 metadata codec');
    love_story_video_assert(($audio['codec_name'] ?? '') === 'aac', 'video output includes AAC audio when source has audio');
    love_story_video_assert((int)($video['width'] ?? 0) <= 1280 && (int)($video['height'] ?? 0) <= 720, 'video output respects bounded target dimensions');
    $videoRelativePath = relative_path($result['path']);
    love_story_video_assert(str_contains($videoRelativePath, 'uploads/tenant_' . $fixture['tenant_id'] . '/love-story/'), 'video output is tenant-scoped');
    $persistConfig = config_defaults();
    $persistConfig['theme']['mode'] = 'preset';
    $persistConfig['theme']['theme_preset'] = 'dewankl';
    $persistConfig['media']['love_story_video'] = $videoRelativePath;
    love_story_video_assert(save_config($persistConfig), 'video reference persists through canonical tenant config');
    $reloadedConfig = load_config();
    love_story_video_assert(($reloadedConfig['media']['love_story_video'] ?? '') === $videoRelativePath, 'persisted video reference reloads unchanged');
    $videoInventory = list_media_library(['group' => 'video', 'type' => 'video']);
    love_story_video_assert(in_array($videoRelativePath, array_column($videoInventory, 'path'), true), 'File Manager inventory lists the canonical Love Story video');

    $renderConfig = config_defaults();
    $renderConfig['theme']['mode'] = 'custom';
    $renderConfig['media']['love_story_video'] = relative_path($result['path']);
    $shared = [
        'presetKey' => 'custom', 'guestFallback' => 'Bapak/Ibu/Saudara/i', 'guestName' => '',
        'calendarLink' => '', 'whatsappLink' => '', 'countdownTarget' => '', 'sectionStyles' => [],
        'brideParents' => '', 'groomParents' => '', 'siteTitle' => '', 'weddingTitle' => '',
    ];
    $customStory = render_shared_section_block($renderConfig, 'cerita', $shared);
    love_story_video_assert(str_contains($customStory, 'class="love-story-video"'), 'Custom Mode emits optional Love Story video block');
    love_story_video_assert(str_contains($customStory, relative_path($result['path'])), 'Custom Mode emits the tenant-safe canonical video URL');

    $yamiConfig = config_defaults();
    $yamiConfig['theme']['mode'] = 'preset';
    $yamiConfig['theme']['theme_preset'] = 'yami-buzzy';
    $yamiConfig['media']['love_story_video'] = relative_path($result['path']);
    $yamiHtml = render_theme_layout($yamiConfig, array_replace($shared, ['presetKey' => 'yami-buzzy']));
    love_story_video_assert(str_contains($yamiHtml, relative_path($result['path'])), 'Yami Buzzy emits the configured Love Story video');

    $parangConfig = config_defaults();
    $parangConfig['theme']['mode'] = 'preset';
    $parangConfig['theme']['theme_preset'] = 'parang';
    $parangConfig['media']['love_story_video'] = relative_path($result['path']);
    love_story_video_assert(!theme_contract_has_media_role('parang', 'love_story_video'), 'Parang remains video-disabled until its contract and renderer are extended');
    $visibleTargets = media_manager_visible_target_definitions($parangConfig, 'parang');
    love_story_video_assert(!isset($visibleTargets['media.love_story_video']), 'Parang File Manager hides unsupported video assignment target');

    $replacementSource = sys_get_temp_dir() . '/love-story-video-replacement-' . bin2hex(random_bytes(5)) . '.mp4';
    love_story_video_fixture($replacementSource);
    $fixtures[] = $replacementSource;
    $replacement = replace_uploaded_asset($videoRelativePath, ['error' => UPLOAD_ERR_OK, 'tmp_name' => $replacementSource, 'name' => 'replacement.mp4', 'size' => filesize($replacementSource)], 'love_story_video', 'dewankl');
    love_story_video_assert(!empty($replacement['success']) && str_ends_with((string)($replacement['path'] ?? ''), '.mp4'), 'File Manager video replacement uses canonical processor');
    love_story_video_assert(!is_file($replacementSource), 'replacement source is removed after verified processing');
    if (!empty($replacement['path'])) $created[] = ROOT_DIR . '/' . ltrim((string)$replacement['path'], '/');

    $broken = sys_get_temp_dir() . '/love-story-video-broken-' . bin2hex(random_bytes(5)) . '.mp4';
    file_put_contents($broken, 'broken video payload');
    $fixtures[] = $broken;
    $rejectedUpload = upload_file(['error' => UPLOAD_ERR_OK, 'tmp_name' => $broken, 'name' => 'broken.mp4', 'size' => filesize($broken)], tenant_upload_dir('love_story'), ALLOWED_VIDEO_TYPES, MAX_VIDEO_UPLOAD_SIZE, 'love_story_video', 'dewankl');
    love_story_video_assert(empty($rejectedUpload['success']), 'invalid video MIME is rejected before processing');
    love_story_video_assert(is_file($broken), 'invalid video source is retained');
    $failed = process_video_to_mp4($broken, tenant_upload_dir('love_story'), 'love_story_video', 'dewankl', 'broken.mp4');
    love_story_video_assert(empty($failed['success']), 'FFmpeg processing failure is reported');
    love_story_video_assert(is_file($broken), 'video source is retained after processing failure');
    love_story_video_assert(count(glob(tenant_upload_dir('love_story') . '/.*.tmp.mp4') ?: []) === 0, 'video processing failure leaves no temporary canonical file');

    $zeroVideoOutput = tenant_upload_dir('love_story') . '/zero-output.mp4';
    file_put_contents($zeroVideoOutput, '');
    love_story_video_assert(!verify_mp4_output($zeroVideoOutput, media_requirement('love_story_video', 'dewankl')), 'zero-byte MP4 output is rejected by verifier');
    @unlink($zeroVideoOutput);
    $corruptVideoOutput = tenant_upload_dir('love_story') . '/corrupt-output.mp4';
    file_put_contents($corruptVideoOutput, 'corrupt mp4 output');
    love_story_video_assert(!verify_mp4_output($corruptVideoOutput, media_requirement('love_story_video', 'dewankl')), 'corrupt MP4 output is rejected by verifier');
    @unlink($corruptVideoOutput);

    $invalidDestinationSource = sys_get_temp_dir() . '/love-story-video-invalid-destination-' . bin2hex(random_bytes(5)) . '.mp4';
    love_story_video_fixture($invalidDestinationSource);
    $fixtures[] = $invalidDestinationSource;
    $invalidDestination = upload_file(['error' => UPLOAD_ERR_OK, 'tmp_name' => $invalidDestinationSource, 'name' => 'invalid-destination.mp4', 'size' => filesize($invalidDestinationSource)], sys_get_temp_dir(), ALLOWED_VIDEO_TYPES, MAX_VIDEO_UPLOAD_SIZE, 'love_story_video', 'dewankl');
    love_story_video_assert(empty($invalidDestination['success']), 'video destination outside active tenant is rejected');
    love_story_video_assert(is_file($invalidDestinationSource), 'invalid tenant destination preserves video source');

    $oversized = sys_get_temp_dir() . '/love-story-video-oversized-' . bin2hex(random_bytes(5)) . '.mp4';
    love_story_video_fixture($oversized);
    $fixtures[] = $oversized;
    $oversizedUpload = upload_file(['error' => UPLOAD_ERR_OK, 'tmp_name' => $oversized, 'name' => 'oversized.mp4', 'size' => MAX_VIDEO_UPLOAD_SIZE + 1], tenant_upload_dir('love_story'), ALLOWED_VIDEO_TYPES, MAX_VIDEO_UPLOAD_SIZE, 'love_story_video', 'dewankl');
    love_story_video_assert(empty($oversizedUpload['success']), 'oversized video upload is rejected');
    love_story_video_assert(is_file($oversized), 'oversized video source is retained');

    love_story_video_assert(MAX_UPLOAD_SIZE === 10 * 1024 * 1024, 'default image upload limit is 10MB');
    love_story_video_assert(MAX_MUSIC_UPLOAD_SIZE === 50 * 1024 * 1024, 'default music upload limit is 50MB');
    love_story_video_assert(MAX_VIDEO_UPLOAD_SIZE === 512 * 1024 * 1024, 'default video upload limit is 512MB');
    echo "PASS: Love Story video pipeline smoke test\n";
} finally {
    foreach ($fixtures as $path) if (is_file($path)) @unlink($path);
    foreach ($created as $path) if (is_file($path)) @unlink($path);
}
