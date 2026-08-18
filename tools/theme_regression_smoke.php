<?php
ob_start();
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';
$base = load_config();
$shared = ['presetKey' => 'custom', 'heroText' => $base['wedding']['opening_text'] ?? '', 'guestFallback' => 'Bapak/Ibu/Saudara/i', 'countdownTarget' => $base['schedule']['countdown_target'] ?? '', 'calendarLink' => build_google_calendar_link($base), 'calendarDownloadName' => 'Undangan', 'whatsappLink' => build_whatsapp_link($base), 'musicSrc' => $base['media']['music'] ?? '', 'bgHero' => '', 'sectionStyles' => ['', '', ''], 'brideParents' => '', 'groomParents' => '', 'siteTitle' => $base['site']['title'] ?? '', 'weddingTitle' => $base['wedding']['title'] ?? ''];
$sequence = ['custom', 'dewankl', 'elix', 'rainier', 'archak', 'parang', 'pawiwahan', 'custom'];
$markers = [
    'custom' => ['hero', 'rsvp'],
    'dewankl' => ['id="root"', 'id="wedding-date"', 'id="comment"'],
    'elix' => ['id="hero"', 'id="info"', 'id="gifts"'],
    'rainier' => ['id="app"', 'id="event-title"', 'id="schedule-section"'],
    'archak' => ['id="story"', 'id="stay"', 'id="registry"', 'id="parallax1"'],
    'parang' => ['id="cms-parang-root"', 'id="beranda"', 'id="mempelai"'],
    'pawiwahan' => ['id="home"', 'id="carouselExampleCaptions"', 'id="welcomeModal"'],
];
foreach ($sequence as $preset) {
    $config = $base;
    $config['theme']['mode'] = $preset === 'custom' ? 'custom' : 'preset';
    $config['theme']['theme_preset'] = $preset;
    $shared['presetKey'] = $preset;
    $html = render_theme_layout($config, $shared);
    foreach ($markers[$preset] as $marker) if (strpos($html, $marker) === false) throw new RuntimeException("Missing {$marker} after switching to {$preset}");
    if ($preset === 'elix') {
        $hasConfiguredMusic = trim((string)($config['media']['music'] ?? '')) !== '';
        $hasAudioContainer = strpos($html, 'id="audio-container"') !== false;
        if ($hasConfiguredMusic !== $hasAudioContainer) throw new RuntimeException('Elix audio container does not follow optional music configuration');
    }
    if ($preset === 'rainier' && preg_match('/\baos\b/i', $html)) throw new RuntimeException('Rainier leaked AOS dependency');
    if ($preset === 'custom' && strpos($html, 'id="app"') !== false) throw new RuntimeException('Custom leaked Rainier app marker');
    if ($preset !== 'archak' && strpos($html, 'id="registry"') !== false) throw new RuntimeException("{$preset} leaked Archak registry marker");
    if ($preset !== 'parang' && strpos($html, 'id="cms-parang-root"') !== false) throw new RuntimeException("{$preset} leaked Parang marker");
    if ($preset !== 'pawiwahan' && strpos($html, 'id="carouselExampleCaptions"') !== false) throw new RuntimeException("{$preset} leaked Pawiwahan marker");
    echo "PASS: switch -> {$preset}\n";
}
ob_end_flush();
