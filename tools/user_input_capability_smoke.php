<?php
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function input_capability_assert(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$base = config_defaults();
$expectedRoles = [
    'dewankl' => ['cover', 'bride_photo', 'groom_photo', 'couple_photo', 'love_story_video'],
    'yami-buzzy' => ['bride_photo', 'groom_photo', 'couple_photo', 'love_story_video'],
    'shubh-vivah' => [],
    'rainier' => [],
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
input_capability_assert(strpos($adminSource, '<option value="video">Video Cerita</option>') !== false, 'Media Manager video folder is missing');
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
    'dewankl' => ['qris' => 'uploads/cover/dewana-qris.webp'],
    'yami-buzzy' => ['qris' => 'uploads/cover/yami-qris.webp'],
    'archak' => ['qris' => 'uploads/cover/archak-qris.webp'],
    'parang' => ['qris' => 'uploads/cover/parang-qris.webp'],
    'pawiwahan' => ['qris' => 'uploads/cover/pawiwahan-qris.webp'],
];
foreach ($renderProbes as $preset => $probeData) {
    $config = $base;
    $config['theme']['mode'] = 'preset';
    $config['theme']['theme_preset'] = $preset;
    $config['gift']['qris_image'] = $probeData['qris'];
    $config['media']['love_story_video'] = 'uploads/love-story/input-probe.mp4';
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

$videoPath = 'uploads/love-story/reference-video.mp4';
$usageConfig = $base;
$usageConfig['media']['love_story_video'] = $videoPath;
input_capability_assert(in_array('Love Story Video', detect_media_usage($usageConfig, $videoPath), true), 'Video usage is not detected');
$replaced = $usageConfig;
replace_media_references($replaced, $videoPath, 'uploads/love-story/replaced-video.mp4');
input_capability_assert(($replaced['media']['love_story_video'] ?? '') === 'uploads/love-story/replaced-video.mp4', 'Video reference replacement failed');
clear_media_references($replaced, 'uploads/love-story/replaced-video.mp4');
input_capability_assert(($replaced['media']['love_story_video'] ?? '') === '', 'Video reference clearing failed');

echo "PASS: user input capability contract, Admin paths, dresscode, QRIS, video renderer, and media lifecycle\n";
