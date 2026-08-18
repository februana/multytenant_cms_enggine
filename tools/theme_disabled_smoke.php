<?php
ob_start();
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

$base = load_config();
$shared = [
    'presetKey' => 'custom',
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

$cases = [
    ['preset' => 'dewankl', 'section' => 'gallery', 'marker' => '<section class="bg-white-black pb-5 pt-3" id="gallery"'],
    ['preset' => 'elix', 'section' => 'gallery', 'marker' => '<section id="gallery"'],
    ['preset' => 'rainier', 'section' => 'schedule', 'marker' => 'id="schedule-section"'],
    ['preset' => 'archak', 'section' => 'registry', 'marker' => 'id="registry"'],
];

foreach ($cases as $case) {
    $config = $base;
    $config['theme']['mode'] = 'preset';
    $config['theme']['theme_preset'] = $case['preset'];
    $sections = theme_contract_sections_for_config($config, $case['preset']);
    foreach ($sections as &$section) {
        if (($section['id'] ?? '') === $case['section']) $section['enabled'] = false;
    }
    unset($section);
    $config['theme_sections'][$case['preset']] = $sections;
    $shared['presetKey'] = $case['preset'];
    $html = render_theme_layout($config, $shared);
    if (strpos($html, $case['marker']) !== false) {
        throw new RuntimeException("Disabled {$case['preset']}:{$case['section']} still rendered");
    }
    echo "PASS: disabled {$case['preset']}:{$case['section']} removed its presentation boundary\n";
}

echo "PASS: disabled behavior smoke test\n";
ob_end_flush();
