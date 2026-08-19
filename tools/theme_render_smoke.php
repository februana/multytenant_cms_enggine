<?php
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

ob_start();
$base = load_config();
$shared = [
    'presetKey' => 'dewankl',
    'heroText' => $base['wedding']['opening_text'] ?? '',
    'guestFallback' => 'Bapak/Ibu/Saudara/i',
    'countdownTarget' => $base['schedule']['countdown_target'] ?? '',
    'calendarLink' => build_google_calendar_link($base),
    'calendarDownloadName' => 'Undangan',
    'whatsappLink' => build_whatsapp_link($base),
    'musicSrc' => $base['media']['music'] ?? '',
    'bgHero' => '',
    'sectionStyles' => ['', '', ''],
    'brideParents' => '',
    'groomParents' => '',
    'siteTitle' => $base['site']['title'] ?? '',
    'weddingTitle' => $base['wedding']['title'] ?? '',
];

$markers = [
    'custom' => ['hero', 'rsvp'],
    'dewankl' => ['id="home"', 'data-bs-spy="scroll"'],
    'shubh-vivah' => ['id="shubh-home"', 'Buka Undangan', 'id="shubh-countdown"'],
    'yami-buzzy' => ['id="yami-home"', 'id="yami-welcome-modal"', 'Buka Undangan'],
    'rainier' => ['id="app"', 'id="event-title"', 'id="schedule-section"'],
    'archak' => ['id="story"', 'id="registry"', 'id="home-img-lg"'],
    'parang' => ['id="cms-parang-root"', 'id="beranda"', 'parang-bg'],
    'pawiwahan' => ['id="home"', 'id="carouselExampleCaptions"', 'id="welcomeModal"', 'id="hitungmundur"', 'id="galeri"'],
];

$greetingSentinels = [
    'dewankl' => 'Salam Dewankl',
    'shubh-vivah' => 'Salam Shubh Vivah',
    'yami-buzzy' => 'Salam Yami Buzzy',
    'rainier' => 'Salam Rainier',
    'archak' => 'Salam Archak',
    'parang' => 'Salam Parang',
    'pawiwahan' => 'Salam Pawiwahan',
];

foreach ($markers as $preset => $required) {
    $config = $base;
    $config['theme']['mode'] = $preset === 'custom' ? 'custom' : 'preset';
    $config['theme']['theme_preset'] = $preset;
    if (isset($greetingSentinels[$preset])) {
        $config['theme_options'][$preset]['opening_greeting'] = $greetingSentinels[$preset];
    }
    $shared['presetKey'] = $preset;
    $html = render_theme_layout($config, $shared);
    if ($html === '') throw new RuntimeException("Empty render for {$preset}");
    foreach ($required as $marker) {
        if (strpos($html, $marker) === false) throw new RuntimeException("Missing {$marker} in {$preset}");
    }
    if ($preset === 'pawiwahan' && substr_count($html, 'id="home"') !== 1) {
        throw new RuntimeException('Pawiwahan must expose one canonical #home anchor');
    }
    if (isset($greetingSentinels[$preset]) && strpos($html, $greetingSentinels[$preset]) === false) {
        throw new RuntimeException("Configured greeting did not render in {$preset}");
    }
    echo "PASS: {$preset} rendered (" . strlen($html) . " bytes)\n";
}
ob_end_flush();
