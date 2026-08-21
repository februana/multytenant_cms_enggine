<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function credit_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ' . $message . PHP_EOL;
}

$base = load_config();
$sourceMarkers = [
    'dewankl' => ['Dibuat dengan', 'DewanaKL'],
    'archak' => ['Dibuat oleh', '@NathArchak'],
    'parang' => ['Manten Jawi'],
    'pawiwahan' => ['DE Juna', 'Presentation adapted from the Pawiwahan source template.'],
    'rainier' => ['Templat sumber', 'Rainier'],
    'shubh-vivah' => [],
    'yami-buzzy' => [],
    'custom' => [],
];

foreach (array_keys($sourceMarkers) as $preset) {
    $config = $base;
    $config['theme']['mode'] = $preset === 'custom' ? 'custom' : 'preset';
    $config['theme']['theme_preset'] = $preset;
    $shared = [
        'presetKey' => $preset,
        'heroText' => (string)($config['wedding']['opening_text'] ?? ''),
        'guestFallback' => 'Bapak/Ibu/Saudara/i',
        'guestName' => '',
        'countdownTarget' => (string)($config['schedule']['countdown_target'] ?? ''),
        'calendarLink' => build_google_calendar_link($config),
        'calendarDownloadName' => 'Undangan',
        'whatsappLink' => build_whatsapp_link($config),
        'musicSrc' => '',
        'bgHero' => '',
        'sectionStyles' => [],
        'brideParents' => '',
        'groomParents' => '',
        'siteTitle' => (string)($config['site']['title'] ?? ''),
        'weddingTitle' => (string)($config['wedding']['title'] ?? ''),
    ];
    $raw = render_theme_layout($config, $shared);
    $final = finalize_theme_output($raw, $config);
    credit_assert(substr_count($final, 'id="cms-credit"') === 1, "$preset has one CMS credit");
    $expectedOwnershipCredit = $preset === 'parang' ? 'Dibuat oleh <strong>Febru &amp; Andi</strong>' : 'CMS by <strong>Febru &amp; Andi</strong>';
    credit_assert(substr_count($final, $expectedOwnershipCredit) === 1, "$preset renders the correct Febru & Andi credit");
    foreach ($sourceMarkers[$preset] as $marker) {
        credit_assert(str_contains($final, $marker), "$preset preserves source credit: {$marker}");
    }
}

$double = append_cms_credit(append_cms_credit('<footer>Source credit</footer>', 'parang'), 'parang');
credit_assert(substr_count($double, 'id="cms-credit"') === 1, 'CMS credit insertion is idempotent');
credit_assert(str_contains($double, 'Dibuat oleh <strong>Febru &amp; Andi</strong>'), 'Parang ownership credit remains idempotent');

echo "PASS: credit attribution smoke test" . PHP_EOL;
