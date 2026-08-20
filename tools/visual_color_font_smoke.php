<?php
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

$failures = 0;
$assert = static function (bool $condition, string $message) use (&$failures): void {
    echo ($condition ? 'PASS: ' : 'FAIL: ') . $message . PHP_EOL;
    if (!$condition) $failures++;
};
$base = config_defaults();
current_tenant(true);
$tenantBackgroundDir = tenant_upload_dir('background');
@mkdir($tenantBackgroundDir, 0755, true);
$tenantSectionPath = static function (string $preset) use ($tenantBackgroundDir): string {
    $absolute = $tenantBackgroundDir . '/' . $preset . '-section.webp';
    if (!is_file($absolute)) @touch($absolute);
    return relative_path($absolute);
};
$bridge = [
    'dewankl' => ['heading_color' => '--cms-dewana-heading-color', 'text_color' => '--cms-dewana-text', 'muted_color' => '--cms-dewana-muted', 'link_color' => '--cms-dewana-link'],
    'rainier' => ['heading_color' => '--rainier-heading-color', 'text_color' => '--text', 'muted_color' => '--muted', 'link_color' => '--rainier-link'],
    'archak' => ['heading_color' => '--cms-archak-heading-color', 'text_color' => '--cms-archak-text', 'muted_color' => '--cms-archak-muted', 'link_color' => '--cms-archak-link'],
    'parang' => ['heading_color' => '--parang-heading-color', 'text_color' => '--parang-text', 'muted_color' => '--parang-muted', 'link_color' => '--parang-link'],
    'pawiwahan' => ['heading_color' => '--pawiwahan-heading-color', 'text_color' => '--pawiwahan-text', 'muted_color' => '--pawiwahan-muted', 'link_color' => '--pawiwahan-link'],
    'shubh-vivah' => ['heading_color' => '--shubh-heading-color', 'text_color' => '--shubh-ink', 'muted_color' => '--shubh-muted', 'link_color' => '--shubh-link'],
    'yami-buzzy' => ['heading_color' => '--yami-heading-color', 'text_color' => '--yami-ink', 'muted_color' => '--yami-muted', 'link_color' => '--yami-link'],
];
$colors = ['heading_color' => '#123456', 'text_color' => '#234567', 'muted_color' => '#345678', 'link_color' => '#456789'];
foreach (theme_builtin_preset_keys() as $preset) {
    $config = $base;
    $config['theme']['mode'] = 'preset';
    $config['theme']['theme_preset'] = $preset;
    $schema = theme_visual_capabilities_for_config($config, $preset);
    $visuals = $colors;
    $visuals['accent_color'] = '#56789a';
    $visuals['heading_font'] = 'Bodoni Moda, serif';
    $visuals['body_font'] = 'DM Sans, sans-serif';
    foreach ($schema as $key => $definition) {
        if (str_starts_with((string)$key, 'section_background_')) {
            $visuals[$key] = $tenantSectionPath($preset);
            break;
        }
    }
    $config['theme_visuals'][$preset] = $visuals;
    $shared = ['presetKey' => $preset, 'heroText' => $config['wedding']['opening_text'], 'guestFallback' => 'Bapak/Ibu/Saudara/i', 'countdownTarget' => $config['schedule']['countdown_target'], 'calendarLink' => build_google_calendar_link($config), 'calendarDownloadName' => 'Undangan', 'whatsappLink' => build_whatsapp_link($config), 'musicSrc' => '', 'bgHero' => '', 'sectionStyles' => ['', '', ''], 'brideParents' => '', 'groomParents' => '', 'siteTitle' => $config['site']['title'], 'weddingTitle' => $config['wedding']['title']];
    $html = render_theme_layout($config, $shared);
    $assert(str_contains($html, 'theme_google_font_stylesheet_url') === false, "$preset emits a resolved font stylesheet URL, not PHP source");
    $assert(str_contains($html, 'Bodoni+Moda') || str_contains($html, 'Bodoni%20Moda'), "$preset render includes shared font library");
    foreach ($bridge[$preset] as $key => $variable) {
        $hasBridge = str_contains($html, $variable . ':' . $colors[$key]) || str_contains($html, $variable . ': ' . $colors[$key]);
        $assert($hasBridge, "$preset render bridges $key");
    }
    $sectionKeys = array_keys(array_filter($schema, static fn($definition, $key) => str_starts_with((string)$key, 'section_background_'), ARRAY_FILTER_USE_BOTH));
    if ($sectionKeys) {
        $sectionKey = $sectionKeys[0];
        $path = $visuals[$sectionKey];
        $assert(str_contains($html, $path), "$preset render emits selected $sectionKey media");
    }
}
if ($failures > 0) {
    echo "FAIL: visual color/font smoke test ($failures failures)" . PHP_EOL;
    exit(1);
}
echo 'PASS: visual color/font smoke test' . PHP_EOL;
