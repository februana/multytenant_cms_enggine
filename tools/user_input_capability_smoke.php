<?php
require_once dirname(__DIR__) . '/tools/tenant_smoke_fixture.php';
tenant_smoke_bootstrap('user-input-capability');
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function input_capability_assert(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$base = config_defaults();
$currentTenant = current_tenant(true);
$tenantCoverDir = tenant_upload_dir('cover');
$tenantLoveStoryDir = tenant_upload_dir('love_story');
@mkdir($tenantCoverDir, 0755, true);
@mkdir($tenantLoveStoryDir, 0755, true);
$tenantQrisPath = static function (string $name) use ($tenantCoverDir): string {
    $absolute = $tenantCoverDir . '/' . $name;
    if (!is_file($absolute)) @touch($absolute);
    return relative_path($absolute);
};
$tenantVideoPath = static function (string $name) use ($tenantLoveStoryDir): string {
    $absolute = $tenantLoveStoryDir . '/' . $name;
    if (!is_file($absolute)) @touch($absolute);
    return relative_path($absolute);
};
$expectedRoles = [
    'dewankl' => ['cover', 'bride_photo', 'groom_photo', 'couple_photo', 'love_story_video'],
    'yami-buzzy' => ['bride_photo', 'groom_photo', 'couple_photo', 'love_story_video'],
    'shubh-vivah' => ['cover'],
    'rainier' => ['cover'],
    'archak' => ['cover', 'bride_photo', 'groom_photo', 'couple_photo'],
    'parang' => ['bride_photo', 'groom_photo'],
    'pawiwahan' => ['cover', 'bride_photo', 'groom_photo'],
];
foreach ($expectedRoles as $preset => $roles) {
    input_capability_assert(theme_contract_media_roles($preset) === $roles, "Media role contract mismatch for {$preset}");
}

foreach (['dewankl', 'yami-buzzy', 'parang'] as $preset) {
    $probe = $base;
    $probe['theme']['mode'] = 'preset';
    $probe['theme']['theme_preset'] = $preset;
    input_capability_assert(in_array('dresscode', theme_admin_capabilities_for_config($probe), true), "Dresscode Admin capability missing for {$preset}");
}
foreach (['shubh-vivah', 'rainier', 'pawiwahan'] as $preset) {
    $probe = $base;
    $probe['theme']['mode'] = 'preset';
    $probe['theme']['theme_preset'] = $preset;
    input_capability_assert(!in_array('dresscode', theme_admin_capabilities_for_config($probe), true), "Unsupported dresscode Admin capability exposed for {$preset}");
}

$adminSource = file_get_contents(dirname(__DIR__) . '/admin/index.php');
input_capability_assert(strpos($adminSource, 'name="qris_image"') !== false, 'Admin QRIS upload field is missing');
input_capability_assert(strpos($adminSource, 'name="dresscode_color"') !== false, 'Admin dresscode input is missing');
input_capability_assert(strpos($adminSource, 'value="video">Video Cerita</option>') !== false, 'Media Manager video folder label is missing');
input_capability_assert(strpos($adminSource, "in_array('love_story_video', \$themeMediaRoles, true)") !== false, 'Media Manager video folder is not contract-gated');
input_capability_assert(strpos($adminSource, 'media.love_story_video') !== false, 'Media Manager video assignment target is missing');

$shared = [
    'presetKey' => 'dewankl',
    'heroText' => $base['wedding']['opening_text'] ?? '',
    'guestFallback' => 'Bapak/Ibu/Saudara/i',
    'guestName' => '',
    'countdownTarget' => $base['schedule']['countdown_target'] ?? '',
    'calendarLink' => build_google_calendar_link($base),
    'calendarDownloadName' => 'Undangan',
    'whatsappLink' => build_whatsapp_link($base),
    'musicSrc' => '',
    'bgHero' => '',
    'sectionStyles' => ['', '', ''],
    'brideParents' => '',
    'groomParents' => '',
    'siteTitle' => $base['site']['title'] ?? '',
    'weddingTitle' => $base['wedding']['title'] ?? '',
];

$renderProbes = [
    'dewankl' => ['qris' => $tenantQrisPath('dewana-qris.webp')],
    'yami-buzzy' => ['qris' => $tenantQrisPath('yami-qris.webp')],
    'archak' => ['qris' => $tenantQrisPath('archak-qris.webp')],
    'parang' => ['qris' => $tenantQrisPath('parang-qris.webp')],
    'pawiwahan' => ['qris' => $tenantQrisPath('pawiwahan-qris.webp')],
];
foreach ($renderProbes as $preset => $probeData) {
    $config = $base;
    $config['theme']['mode'] = 'preset';
    $config['theme']['theme_preset'] = $preset;
    $config['gift']['qris_image'] = $probeData['qris'];
    $config['media']['love_story_video'] = $tenantVideoPath('input-probe.mp4');
    if ($preset === 'yami-buzzy') {
        $config['dresscode'] = [
            'enabled' => true,
            'title' => 'Busana Keluarga',
            'color' => 'Hijau Sage',
            'rule' => 'Batik atau kain bernuansa lembut',
            'description' => 'Mohon menyesuaikan dengan kenyamanan dan suasana acara.'
        ];
    }
    $shared['presetKey'] = $preset;
    $html = render_theme_layout($config, $shared);
    input_capability_assert(strpos($html, $probeData['qris']) !== false, "QRIS probe did not render for {$preset}");
    if ($preset === 'yami-buzzy') {
        foreach (['Busana Keluarga', 'Hijau Sage', 'Batik atau kain bernuansa lembut', 'Mohon menyesuaikan'] as $marker) {
            input_capability_assert(strpos($html, $marker) !== false, "Configured dresscode marker missing in Yami Buzzy: {$marker}");
        }
        input_capability_assert(strpos($html, 'Sambutan Hangat') === false, 'Yami Buzzy still renders hardcoded dresscode agenda');
        input_capability_assert(strpos($html, 'input-probe.mp4') !== false, 'Yami Buzzy video probe did not render');
    }
    if ($preset === 'dewankl') input_capability_assert(strpos($html, 'input-probe.mp4') !== false, 'DewanaKL video probe did not render');
}

$customConfig = $base;
$customConfig['theme']['mode'] = 'custom';
$customConfig['theme']['theme_preset'] = 'custom';
$customConfig['media']['love_story_video'] = $tenantVideoPath('custom-input-probe.mp4');
$shared['presetKey'] = 'custom';
$customHtml = render_theme_layout($customConfig, $shared);
input_capability_assert(strpos($customHtml, 'custom-input-probe.mp4') !== false, 'Custom Mode video probe did not render');

$videoPath = $tenantVideoPath('reference-video.mp4');
$usageConfig = $base;
$usageConfig['media']['love_story_video'] = $videoPath;
input_capability_assert(in_array('Love Story Video', detect_media_usage($usageConfig, $videoPath), true), 'Video usage is not detected');
$replaced = $usageConfig;
$replacedVideoPath = $tenantVideoPath('replaced-video.mp4');
replace_media_references($replaced, $videoPath, $replacedVideoPath);
input_capability_assert(($replaced['media']['love_story_video'] ?? '') === $replacedVideoPath, 'Video reference replacement failed');
clear_media_references($replaced, $replacedVideoPath);
input_capability_assert(($replaced['media']['love_story_video'] ?? '') === '', 'Video reference clearing failed');

echo "PASS: user input capability contract, Admin paths, dresscode, QRIS, video renderer, and media lifecycle\n";
