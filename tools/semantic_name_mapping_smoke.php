<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function semantic_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ' . $message . PHP_EOL;
}

function semantic_config(array $base, string $preset): array {
    $config = $base;
    $config['theme']['mode'] = $preset === 'custom' ? 'custom' : 'preset';
    $config['theme']['theme_preset'] = $preset;
    $config['wedding']['bride_name'] = 'FARAH FULL NAME';
    $config['wedding']['groom_name'] = 'GROOM FULL NAME';
    $config['wedding']['bride_nickname'] = 'FARAH';
    $config['wedding']['groom_nickname'] = 'GROOM';
    $config['site']['title'] = 'FARAH FULL NAME & GROOM FULL NAME — Undangan';
    return $config;
}

$base = load_config();
$shared = [
    'presetKey' => 'custom',
    'heroText' => $base['wedding']['opening_text'] ?? '',
    'guestFallback' => 'Bapak/Ibu/Saudara/i',
    'guestName' => '',
    'countdownTarget' => $base['schedule']['countdown_target'] ?? '',
    'calendarLink' => build_google_calendar_link($base),
    'calendarDownloadName' => 'Undangan',
    'whatsappLink' => build_whatsapp_link($base),
    'musicSrc' => '',
    'bgHero' => '',
    'sectionStyles' => [],
    'brideParents' => '',
    'groomParents' => '',
    'siteTitle' => $base['site']['title'] ?? '',
    'weddingTitle' => $base['wedding']['title'] ?? '',
];

$expectedShortSlots = [
    'dewankl' => ['FARAH &amp; GROOM'],
    'rainier' => ['id="event-title">FARAH &amp; GROOM'],
    'archak' => ['<h1>FARAH &amp; GROOM</h1>'],
    'parang' => ['id="parang-hero-title" class="parang-hero-title">FARAH', '<h2 class="parang-hero-title">GROOM</h2>'],
    'pawiwahan' => ['<h2 class="title">FARAH &amp; GROOM</h2>'],
    'shubh-vivah' => ['id="shubh-title"><span>FARAH</span><em>dan</em><span>GROOM</span>'],
    'yami-buzzy' => ['id="yami-welcome-title">FARAH &amp; GROOM', '<h1>FARAH <span>&amp;</span> GROOM</h1>'],
    'custom' => ['class="brand" href="#hero">FARAH &amp; GROOM', '<h1>FARAH &amp; GROOM</h1>'],
];

$expectedFormalSlots = [
    'dewankl' => ['FARAH FULL NAME &amp; GROOM FULL NAME'],
    'rainier' => ['FARAH FULL NAME &amp; GROOM FULL NAME'],
    'archak' => ['FARAH FULL NAME &amp; GROOM FULL NAME'],
    'parang' => ['<h3>GROOM FULL NAME</h3>', '<h3>FARAH FULL NAME</h3>'],
    'pawiwahan' => ['<h1 class="display-12 text-center">GROOM FULL NAME</h1>', '<h1 class="display-12 text-center">FARAH FULL NAME</h1>'],
    'shubh-vivah' => ['© ' . date('Y') . ' FARAH FULL NAME &amp; GROOM FULL NAME'],
    'yami-buzzy' => ['<h3>FARAH FULL NAME</h3>', '<h3>GROOM FULL NAME</h3>'],
    'custom' => ['<p>FARAH FULL NAME &amp; GROOM FULL NAME</p>'],
];

foreach ($expectedShortSlots as $preset => $shortMarkers) {
    $config = semantic_config($base, $preset);
    $shared['presetKey'] = $preset;
    $html = render_theme_layout($config, $shared);
    semantic_assert($html !== '', "{$preset} renders non-empty output");
    foreach ($shortMarkers as $marker) {
        semantic_assert(strpos($html, $marker) !== false, "{$preset} short visual slot uses nickname marker");
    }
    foreach ($expectedFormalSlots[$preset] as $marker) {
        semantic_assert(strpos($html, $marker) !== false, "{$preset} formal slot uses full-name marker");
    }
    semantic_assert(strpos($html, 'FARAH FULL NAME') !== false, "{$preset} retains bride full name somewhere in formal output");
    semantic_assert(strpos($html, 'GROOM FULL NAME') !== false, "{$preset} retains groom full name somewhere in formal output");
}

$missingNickname = semantic_config($base, 'custom');
$missingNickname['wedding']['bride_nickname'] = '';
$missingNickname['wedding']['groom_nickname'] = '';
$names = theme_semantic_names($missingNickname);
semantic_assert($names['bride_nickname'] === 'FARAH FULL NAME', 'empty bride nickname falls back to bride full name');
semantic_assert($names['groom_nickname'] === 'GROOM FULL NAME', 'empty groom nickname falls back to groom full name');

$missingFullName = semantic_config($base, 'custom');
$missingFullName['wedding']['bride_name'] = '';
$missingFullName['wedding']['groom_name'] = '';
$names = theme_semantic_names($missingFullName);
semantic_assert($names['bride_full_name'] === 'FARAH', 'empty bride full name falls back to bride nickname');
semantic_assert($names['groom_full_name'] === 'GROOM', 'empty groom full name falls back to groom nickname');
$shared['presetKey'] = 'custom';
$html = render_theme_layout($missingFullName, $shared);
semantic_assert(strpos($html, 'FARAH') !== false && strpos($html, 'GROOM') !== false, 'formal output remains non-empty when full names are absent');

$schema = file_get_contents(dirname(__DIR__) . '/database/migrations/001_multi_tenant.sql');
semantic_assert(strpos($schema, 'bride_nickname') === false && strpos($schema, 'groom_nickname') === false, 'tenant schema remains unchanged and no new name fields are introduced');

echo "SUMMARY: semantic name mapping smoke passed\n";
