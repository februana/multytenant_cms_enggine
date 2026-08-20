<?php
ob_start();
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function media_e2e_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ' . $message . PHP_EOL;
}

$tenant = current_tenant(false);
if (!is_array($tenant)) {
    echo "SKIP: visual media E2E requires a migrated active tenant\n";
    ob_end_flush();
    exit(0);
}
$probeAbsolute = tenant_upload_dir('background') . '/visual-media-e2e-probe.webp';
$probePath = relative_path($probeAbsolute);
@mkdir(dirname($probeAbsolute), 0755, true);
copy(ROOT_DIR . '/themes/parang/assets/parang-pattern.webp', $probeAbsolute);
media_e2e_assert(theme_visual_image_reference_is_canonical($probePath), 'E2E probe is accepted by canonical media validation');

$shared = [
    'presetKey' => 'shubh-vivah',
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
foreach (['dewankl', 'rainier', 'archak', 'parang', 'pawiwahan', 'shubh-vivah', 'yami-buzzy', 'custom'] as $preset) {
    $config['theme_visuals'][$preset]['hero_background'] = $probePath;
}
$config['theme_visuals']['dewankl']['welcome_background'] = $probePath;
$config['theme_visuals']['dewankl']['section_background_home'] = $probePath;
$config['theme_visuals']['dewankl']['section_background_bride'] = $probePath;
$config['theme_visuals']['dewankl']['section_background_wedding_date'] = $probePath;
$config['theme_visuals']['dewankl']['section_background_gallery'] = $probePath;
$config['theme_visuals']['rainier']['section_background_event_details'] = $probePath;
$config['theme_visuals']['archak']['section_background_timeline'] = $probePath;
$config['theme_visuals']['parang']['section_background_gallery'] = $probePath;
$config['theme_visuals']['pawiwahan']['section_background_messages'] = $probePath;
$config['theme_visuals']['shubh-vivah']['section_background_event'] = $probePath;
$config['theme_visuals']['yami-buzzy']['section_background_story'] = $probePath;
media_e2e_assert(save_config($config), 'Visual media references save through production config persistence');
$reloaded = load_config();
foreach (['dewankl', 'rainier', 'archak', 'parang', 'pawiwahan', 'shubh-vivah', 'yami-buzzy', 'custom'] as $preset) {
    media_e2e_assert(($reloaded['theme_visuals'][$preset]['hero_background'] ?? '') === $probePath, "{$preset} media reference survives reload");
    if ($preset === 'dewankl') {
        foreach (['welcome_background', 'section_background_home', 'section_background_bride', 'section_background_wedding_date', 'section_background_gallery'] as $backgroundKey) {
            media_e2e_assert(($reloaded['theme_visuals']['dewankl'][$backgroundKey] ?? '') === $probePath, "DewanaKL {$backgroundKey} survives reload");
        }
    }
    if ($preset === 'rainier') media_e2e_assert(($reloaded['theme_visuals']['rainier']['section_background_event_details'] ?? '') === $probePath, 'Rainier section background survives reload');
    if ($preset === 'archak') media_e2e_assert(($reloaded['theme_visuals']['archak']['section_background_timeline'] ?? '') === $probePath, 'Archak section background survives reload');
    if ($preset === 'parang') media_e2e_assert(($reloaded['theme_visuals']['parang']['section_background_gallery'] ?? '') === $probePath, 'Parang section background survives reload');
    if ($preset === 'pawiwahan') media_e2e_assert(($reloaded['theme_visuals']['pawiwahan']['section_background_messages'] ?? '') === $probePath, 'Pawiwahan section background survives reload');
    if ($preset === 'shubh-vivah') media_e2e_assert(($reloaded['theme_visuals']['shubh-vivah']['section_background_event'] ?? '') === $probePath, 'Shubh Vivah section background survives reload');
    if ($preset === 'yami-buzzy') media_e2e_assert(($reloaded['theme_visuals']['yami-buzzy']['section_background_story'] ?? '') === $probePath, 'Yami Buzzy section background survives reload');
    if ($preset === 'custom') {
        media_e2e_assert(str_contains(theme_custom_visual_style($reloaded), 'visual-media-e2e-probe.webp'), 'Custom production adapter includes persisted media URL');
        continue;
    }
    $probeConfig = $reloaded;
    $probeConfig['theme']['mode'] = 'preset';
    $probeConfig['theme']['theme_preset'] = $preset;
    $probeShared = $shared;
    $probeShared['presetKey'] = $preset;
    $html = render_theme_layout($probeConfig, $probeShared);
    media_e2e_assert(str_contains($html, 'visual-media-e2e-probe.webp'), ucfirst($preset) . ' production renderer includes persisted media URL');
    if ($preset === 'dewankl') {
        media_e2e_assert(substr_count($html, 'visual-media-e2e-probe.webp') >= 5, 'DewanaKL renderer includes hero, welcome, and section media references independently');
        media_e2e_assert(!str_contains($html, 'id="gallery" style="--cms-dewana-section-bg'), 'DewanaKL background override does not become a Gallery assignment');
    }
}

$resetConfig = $reloaded;
reset_theme_visual_overrides($resetConfig, 'rainier');
media_e2e_assert(save_config($resetConfig), 'Reset Rainier persists through production config path');
$afterReset = load_config();
media_e2e_assert(($afterReset['theme_visuals']['rainier'] ?? []) === [], 'Reset clears Rainier visual overrides');
foreach (['dewankl', 'archak', 'parang', 'pawiwahan', 'shubh-vivah', 'yami-buzzy', 'custom'] as $preset) {
    media_e2e_assert(($afterReset['theme_visuals'][$preset]['hero_background'] ?? '') === $probePath, "Reset Rainier preserves {$preset} media reference");
}
$resetConfig['theme_visuals']['shubh-vivah'] = [];
$resetConfig['theme_visuals']['yami-buzzy'] = [];
$resetConfig['theme_visuals']['dewankl'] = [];
$resetConfig['theme_visuals']['archak'] = [];
$resetConfig['theme_visuals']['parang'] = [];
$resetConfig['theme_visuals']['pawiwahan'] = [];
$resetConfig['theme_visuals']['custom'] = [];
save_config($resetConfig);
$final = load_config();
media_e2e_assert(($final['theme_visuals']['shubh-vivah'] ?? []) === [], 'Clearing Shubh Vivah restores source-default state');
media_e2e_assert(str_contains(render_theme_layout(array_replace_recursive($final, ['theme' => ['mode' => 'preset', 'theme_preset' => 'shubh-vivah']]), $shared), 'source-wedding-card.png'), 'Shubh Vivah reset returns to source background');
$parangFinalShared = array_replace($shared, ['presetKey' => 'parang']);
$parangFinalHtml = render_theme_layout(array_replace_recursive($final, ['theme' => ['mode' => 'preset', 'theme_preset' => 'parang']]), $parangFinalShared);
media_e2e_assert(str_contains($parangFinalHtml, '/themes/parang/assets/parang-pattern.webp'), 'Parang reset returns to local source background');
media_e2e_assert(!str_contains($parangFinalHtml, 'googleusercontent.com'), 'Parang normal render has no external image dependency');
$pawiwahanFinalShared = array_replace($shared, ['presetKey' => 'pawiwahan']);
$pawiwahanFinalHtml = render_theme_layout(array_replace_recursive($final, ['theme' => ['mode' => 'preset', 'theme_preset' => 'pawiwahan']]), $pawiwahanFinalShared);
media_e2e_assert(str_contains($pawiwahanFinalHtml, '/themes/pawiwahan/assets/hero-source.jpg'), 'Pawiwahan reset returns to local source background');

@unlink($probeAbsolute);
echo "PASS: visual media E2E smoke test\n";
ob_end_flush();
