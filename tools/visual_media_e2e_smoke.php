<?php
ob_start();
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function media_e2e_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ' . $message . PHP_EOL;
}

$probePath = 'uploads/background/visual-media-e2e-probe.png';
$probeAbsolute = ROOT_DIR . '/' . $probePath;
file_put_contents($probeAbsolute, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
media_e2e_assert(theme_visual_image_reference_is_canonical($probePath), 'E2E probe is accepted by canonical media validation');

$shared = [
    'presetKey' => 'elix',
    'heroText' => 'E2E visual media probe',
    'guestFallback' => 'Bapak/Ibu/Saudara/i',
    'guestName' => '',
    'countdownTarget' => '',
    'calendarLink' => '#calendar',
    'calendarDownloadName' => 'E2E',
    'whatsappLink' => '#whatsapp',
    'musicSrc' => '',
    'bgHero' => '',
    'sectionStyles' => ['', '', ''],
    'brideParents' => '',
    'groomParents' => '',
    'siteTitle' => 'E2E',
    'weddingTitle' => 'E2E',
];

$config = load_config();
foreach (['dewankl', 'elix', 'rainier', 'archak', 'parang', 'custom'] as $preset) {
    $config['theme_visuals'][$preset]['hero_background'] = $probePath;
}
media_e2e_assert(save_config($config), 'Visual media references save through production config persistence');
$reloaded = load_config();
foreach (['dewankl', 'elix', 'rainier', 'archak', 'parang', 'custom'] as $preset) {
    media_e2e_assert(($reloaded['theme_visuals'][$preset]['hero_background'] ?? '') === $probePath, "{$preset} media reference survives reload");
    if ($preset === 'custom') {
        media_e2e_assert(str_contains(theme_custom_visual_style($reloaded), '/uploads/background/visual-media-e2e-probe.png'), 'Custom production adapter includes persisted media URL');
        continue;
    }
    $probeConfig = $reloaded;
    $probeConfig['theme']['mode'] = 'preset';
    $probeConfig['theme']['theme_preset'] = $preset;
    $probeShared = $shared;
    $probeShared['presetKey'] = $preset;
    $html = render_theme_layout($probeConfig, $probeShared);
    media_e2e_assert(str_contains($html, 'visual-media-e2e-probe.png'), ucfirst($preset) . ' production renderer includes persisted media URL');
}

$resetConfig = $reloaded;
reset_theme_visual_overrides($resetConfig, 'rainier');
media_e2e_assert(save_config($resetConfig), 'Reset Rainier persists through production config path');
$afterReset = load_config();
media_e2e_assert(($afterReset['theme_visuals']['rainier'] ?? []) === [], 'Reset clears Rainier visual overrides');
foreach (['dewankl', 'elix', 'archak', 'parang', 'custom'] as $preset) {
    media_e2e_assert(($afterReset['theme_visuals'][$preset]['hero_background'] ?? '') === $probePath, "Reset Rainier preserves {$preset} media reference");
}
$resetConfig['theme_visuals']['elix'] = [];
$resetConfig['theme_visuals']['dewankl'] = [];
$resetConfig['theme_visuals']['archak'] = [];
$resetConfig['theme_visuals']['parang'] = [];
$resetConfig['theme_visuals']['custom'] = [];
save_config($resetConfig);
$final = load_config();
media_e2e_assert(($final['theme_visuals']['elix'] ?? []) === [], 'Clearing Elix restores source-default state');
media_e2e_assert(str_contains(render_theme_layout(array_replace_recursive($final, ['theme' => ['mode' => 'preset', 'theme_preset' => 'elix']]), $shared), 'prewed1.jpg'), 'Elix reset returns to source background');
$parangFinalShared = array_replace($shared, ['presetKey' => 'parang']);
$parangFinalHtml = render_theme_layout(array_replace_recursive($final, ['theme' => ['mode' => 'preset', 'theme_preset' => 'parang']]), $parangFinalShared);
media_e2e_assert(str_contains($parangFinalHtml, '/themes/parang/assets/parang-pattern.webp'), 'Parang reset returns to local source background');
media_e2e_assert(!str_contains($parangFinalHtml, 'googleusercontent.com'), 'Parang normal render has no external image dependency');

@unlink($probeAbsolute);
@unlink(CONFIG_FILE);
@unlink(EVENT_ICS_FILE);
echo "PASS: visual media E2E smoke test\n";
ob_end_flush();
