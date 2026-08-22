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
    if ($preset === 'custom' && (strpos($html, 'id="musicBtn"') !== false || strpos($html, 'id="backgroundMusic"') !== false)) {
        throw new RuntimeException('Custom Mode must not render an audio control when music is empty');
    }
    foreach ($required as $marker) {
        if (strpos($html, $marker) === false) throw new RuntimeException("Missing {$marker} in {$preset}");
    }
    if ($preset === 'pawiwahan' && substr_count($html, 'id="home"') !== 1) {
        throw new RuntimeException('Pawiwahan must expose one canonical #home anchor');
    }
    if (isset($greetingSentinels[$preset]) && strpos($html, $greetingSentinels[$preset]) === false) {
        throw new RuntimeException("Configured greeting did not render in {$preset}");
    }
    if ($preset === 'shubh-vivah') {
        if (strpos($html, 'source-background.mp3') === false) throw new RuntimeException('Shubh Vivah must use bundled source music when CMS music is empty');
        if (strpos($html, 'id="shubh-rsvp-form"') === false || strpos($html, "fetch('save.php'") === false) throw new RuntimeException('Shubh Vivah RSVP must use the CMS save endpoint');
    }
    if ($preset === 'yami-buzzy') {
        if (strpos($html, 'id="yami-rsvp-form"') === false || strpos($html, "fetch('save.php'") === false) throw new RuntimeException('Yami Buzzy RSVP must use the CMS save endpoint');
    }
    if (in_array($preset, ['dewankl', 'shubh-vivah', 'yami-buzzy', 'rainier', 'parang', 'pawiwahan'], true)) {
        $audioConfig = $config;
        $audioConfig['media']['music'] = 'https://cdn.example.test/canonical.mp3';
        $audioHtml = render_theme_layout($audioConfig, $shared);
        if (strpos($audioHtml, 'canonical.mp3') === false) throw new RuntimeException("Canonical music reference did not reach {$preset} renderer");
        if ($preset === 'custom' && (strpos($audioHtml, 'id="musicBtn"') === false || strpos($audioHtml, 'id="backgroundMusic"') === false)) {
            throw new RuntimeException('Custom Mode audio control/element is incomplete');
        }
        if ($preset === 'dewankl' && (strpos($audioHtml, 'id="button-music"') === false || strpos($audioHtml, 'id="backgroundMusic"') === false)) {
            throw new RuntimeException('DewanaKL audio control/element is incomplete');
        }
    }
    echo "PASS: {$preset} rendered (" . strlen($html) . " bytes)\n";
}
ob_end_flush();
