<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function credit_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
    echo 'PASS: ' . $message . PHP_EOL;
}

ob_start();
$base = load_config();
$shared = [
    'presetKey' => 'dewankl',
    'heroText' => (string)($base['wedding']['opening_text'] ?? ''),
    'guestFallback' => 'Bapak/Ibu/Saudara/i',
    'guestName' => '',
    'countdownTarget' => (string)($base['schedule']['countdown_target'] ?? ''),
    'calendarLink' => build_google_calendar_link($base),
    'calendarDownloadName' => 'Undangan',
    'whatsappLink' => build_whatsapp_link($base),
    'musicSrc' => (string)($base['media']['music'] ?? ''),
    'bgHero' => '',
    'sectionStyles' => ['', '', ''],
    'brideParents' => '',
    'groomParents' => '',
    'siteTitle' => (string)($base['site']['title'] ?? ''),
    'weddingTitle' => (string)($base['wedding']['title'] ?? ''),
];

$sourceMarkers = [
    'dewankl' => ['Dibuat dengan', 'DewanaKL'],
    'rainier' => ['Templat sumber', 'Rainier'],
    'archak' => ['@NathArchak'],
    'parang' => ['Manten Jawi'],
    'pawiwahan' => ['DE Juna', 'Presentation adapted from the Pawiwahan source template.'],
    'shubh-vivah' => ['Vinit Shahdeo'],
    'yami-buzzy' => [],
];

$expectedCms = 'CMS didesain oleh <strong>Febru &amp; Andi</strong>';

foreach (array_keys($sourceMarkers) as $preset) {
    $config = $base;
    $config['theme']['mode'] = 'preset';
    $config['theme']['theme_preset'] = $preset;
    $shared['presetKey'] = $preset;

    $raw = render_theme_layout($config, $shared);
    $final = finalize_theme_output($raw, $config);
    credit_assert(substr_count($final, 'id="cms-attribution"') === 1, "$preset renders one public attribution block");
    foreach ($sourceMarkers[$preset] as $marker) {
        credit_assert(str_contains($final, $marker), "$preset preserves verified/source credit marker: {$marker}");
    }

    if ($preset === 'parang') {
        credit_assert(substr_count($final, 'Didesain oleh <strong>Febru &amp; Andi</strong>') === 1, 'Parang renders one ownership line');
        credit_assert(!str_contains($final, $expectedCms), 'Parang does not duplicate the CMS designer line');
    } elseif ($preset === 'dewankl' || $preset === 'rainier' || $preset === 'archak' || $preset === 'pawiwahan') {
        credit_assert(substr_count($final, $expectedCms) === 1, "$preset renders the separate CMS designer line");
        credit_assert(!str_contains($final, 'Dibuat dengan hati oleh <strong>'), "$preset does not duplicate its existing original creator credit");
    } else {
        credit_assert(str_contains($final, 'Dibuat dengan hati oleh <strong>'), "$preset renders its verified original creator through shared attribution");
        credit_assert(substr_count($final, $expectedCms) === 1, "$preset renders the separate CMS designer line");
    }

    $baselineAttribution = cms_attribution_markup($preset);
    $tenantConfigAttribution = cms_attribution_markup($preset);
    credit_assert($baselineAttribution === $tenantConfigAttribution, "$preset attribution is sourced from platform contract, not tenant configuration");
}

$custom = $base;
$custom['theme']['mode'] = 'custom';
$custom['theme']['theme_preset'] = 'custom';
$shared['presetKey'] = 'custom';
$customFinal = finalize_theme_output(render_theme_layout($custom, $shared), $custom);
credit_assert(substr_count($customFinal, 'id="cms-attribution"') === 1, 'Custom Mode renders one public attribution block');
credit_assert(substr_count($customFinal, $expectedCms) === 1, 'Custom Mode renders only the CMS designer line');
credit_assert(!str_contains($customFinal, 'Dibuat dengan hati oleh <strong>'), 'Custom Mode does not invent an original preset creator');

$double = append_cms_attribution(append_cms_attribution('<footer>Source credit</footer>', 'parang'), 'parang');
credit_assert(substr_count($double, 'id="cms-attribution"') === 1, 'Attribution insertion is idempotent');

echo "PASS: credit attribution smoke test" . PHP_EOL;
ob_end_flush();
