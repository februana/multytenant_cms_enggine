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
    'elix' => ['id="hero"', 'offcanvas'],
    'rainier' => ['id="app"', 'id="event-title"', 'id="schedule-section"'],
    'archak' => ['id="story"', 'id="registry"', 'id="home-img-lg"'],
];

foreach ($markers as $preset => $required) {
    $config = $base;
    $config['theme']['mode'] = $preset === 'custom' ? 'custom' : 'preset';
    $config['theme']['theme_preset'] = $preset;
    $shared['presetKey'] = $preset;
    $html = render_theme_layout($config, $shared);
    if ($html === '') throw new RuntimeException("Empty render for {$preset}");
    foreach ($required as $marker) {
        if (strpos($html, $marker) === false) throw new RuntimeException("Missing {$marker} in {$preset}");
    }
    echo "PASS: {$preset} rendered (" . strlen($html) . " bytes)\n";
}
ob_end_flush();
