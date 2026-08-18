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
    ['preset' => 'dewankl', 'section' => 'gallery', 'marker' => 'id="gallery"'],
    ['preset' => 'dewankl', 'section' => 'wedding_date', 'marker' => 'id="wedding-date"'],
    ['preset' => 'dewankl', 'section' => 'comment', 'marker' => 'id="comment"'],
    ['preset' => 'elix', 'section' => 'gallery', 'marker' => '<section id="gallery"'],
    ['preset' => 'elix', 'section' => 'story', 'marker' => '<section id="story"'],
    ['preset' => 'elix', 'section' => 'rsvp', 'marker' => '<section id="rsvp"'],
    ['preset' => 'elix', 'section' => 'gifts', 'marker' => '<section id="gifts"'],
    ['preset' => 'rainier', 'section' => 'schedule', 'marker' => 'id="schedule-section"'],
    ['preset' => 'rainier', 'section' => 'quotes', 'marker' => 'id="quotes-section"'],
    ['preset' => 'rainier', 'section' => 'rsvp', 'marker' => 'id="rsvp"'],
    ['preset' => 'archak', 'section' => 'story', 'marker' => 'id="story"'],
    ['preset' => 'archak', 'section' => 'gallery', 'marker' => 'class="gallery hz-margin'],
    ['preset' => 'archak', 'section' => 'stay', 'marker' => 'id="stay"'],
    ['preset' => 'archak', 'section' => 'registry', 'marker' => 'id="registry"'],
    ['preset' => 'pawiwahan', 'section' => 'gallery', 'marker' => 'id="galeri"'],
    ['preset' => 'pawiwahan', 'section' => 'messages', 'marker' => 'id="pesan"'],
    ['preset' => 'pawiwahan', 'section' => 'event', 'marker' => 'id="calender"'],
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

echo 'PASS: disabled behavior matrix (' . count($cases) . " cases)\n";
ob_end_flush();
?>
