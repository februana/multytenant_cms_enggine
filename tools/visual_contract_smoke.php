<?php
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';
ob_start();

function visual_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ' . $message . PHP_EOL;
}

$base = config_defaults();
$expected = [
    'dewankl' => ['accent_color', 'heading_font', 'body_font', 'hero_background', 'hero_overlay'],
    'elix' => ['accent_color', 'heading_font', 'body_font', 'hero_background', 'hero_overlay', 'countdown_scale'],
    'rainier' => ['accent_color', 'heading_font', 'body_font', 'hero_background', 'glass_opacity'],
    'archak' => ['accent_color', 'heading_font', 'body_font', 'hero_background', 'hero_title_scale'],
];

foreach ($expected as $preset => $keys) {
    $schema = theme_visual_capabilities_for_config($base, $preset);
    visual_assert(array_keys($schema) === $keys, "{$preset} declares only its supported visual capabilities");
    $values = theme_visual_values_for_config($base, $preset);
    foreach ($keys as $key) visual_assert(array_key_exists($key, $values), "{$preset} resolves a default for {$key}");
}

$rainierLegacy = $base;
$rainierLegacy['theme_options']['rainier']['hero_accent_color'] = '#123456';
$rainierLegacy['theme_options']['rainier']['glass_opacity'] = '0.67';
$legacyValues = theme_visual_values_for_config($rainierLegacy, 'rainier');
visual_assert($legacyValues['accent_color'] === '#123456', 'Rainier legacy accent remains compatible');
visual_assert($legacyValues['glass_opacity'] === '0.67', 'Rainier legacy glass opacity remains compatible');

$stored = $base;
$stored['theme']['mode'] = 'preset';
$stored['theme']['theme_preset'] = 'elix';
$stored['theme_visuals'] = [
    'elix' => ['accent_color' => '#123456', 'hero_background' => 'uploads/background/hero.jpg', 'countdown_scale' => '0.66'],
    'rainier' => ['accent_color' => '#654321'],
];
$elixValues = theme_visual_values_for_config($stored, 'elix');
$rainierValues = theme_visual_values_for_config($stored, 'rainier');
visual_assert($elixValues['accent_color'] === '#123456', 'Elix visual override persists');
visual_assert($elixValues['hero_background'] === 'uploads/background/hero.jpg', 'Elix media path persists unchanged');
visual_assert($elixValues['countdown_scale'] === '0.66', 'Elix range override persists');
visual_assert($rainierValues['accent_color'] === '#654321', 'Rainier override is stored independently');
visual_assert($rainierValues['accent_color'] !== $elixValues['accent_color'], 'Visual overrides do not leak across presets');

$switchBase = $stored;
$switchBase['theme_visuals']['archak'] = ['accent_color' => '#abcdef', 'hero_title_scale' => '1.05'];
foreach (['custom', 'dewankl', 'elix', 'rainier', 'archak', 'custom'] as $switchPreset) {
    $switched = switch_active_theme_preset_config($switchBase, $switchPreset);
    visual_assert(is_array($switched), "switching to {$switchPreset} succeeds");
    visual_assert(($switched['theme_visuals']['elix']['accent_color'] ?? '') === '#123456', "switching to {$switchPreset} preserves Elix visuals");
    visual_assert(($switched['theme_visuals']['archak']['hero_title_scale'] ?? '') === '1.05', "switching to {$switchPreset} preserves Archak visuals");
    $switchBase = $switched;
}
$resetProbe = $switchBase;
reset_theme_visual_overrides($resetProbe, 'elix');
visual_assert(($resetProbe['theme_visuals']['elix'] ?? null) === [], 'reset_visuals clears only the active Elix overrides');
visual_assert(($resetProbe['theme_visuals']['rainier']['accent_color'] ?? '') === '#654321', 'reset_visuals preserves hidden Rainier overrides');
visual_assert(($resetProbe['theme_visuals']['archak']['accent_color'] ?? '') === '#abcdef', 'reset_visuals preserves hidden Archak overrides');
visual_assert(theme_visual_public_path('https://cdn.example.test/hero.jpg') === 'https://cdn.example.test/hero.jpg', 'HTTPS visual URL is not prefixed with a slash');
visual_assert(validate_theme_visual_value('#abcdef', ['type' => 'color']) === '#abcdef', 'Color validation accepts valid hex');
visual_assert(validate_theme_visual_value('#not-a-color', ['type' => 'color']) === null, 'Color validation rejects invalid values');
visual_assert(validate_theme_visual_value('1.50', ['type' => 'range', 'min' => '0', 'max' => '1']) === null, 'Range validation rejects out-of-range values');

$shared = [
    'presetKey' => 'elix',
    'heroText' => $stored['wedding']['opening_text'],
    'guestFallback' => 'Bapak/Ibu/Saudara/i',
    'countdownTarget' => $stored['schedule']['countdown_target'],
    'calendarLink' => build_google_calendar_link($stored),
    'calendarDownloadName' => 'Undangan',
    'whatsappLink' => build_whatsapp_link($stored),
    'musicSrc' => '',
    'bgHero' => '',
    'sectionStyles' => ['', '', ''],
    'brideParents' => '',
    'groomParents' => '',
    'siteTitle' => $stored['site']['title'],
    'weddingTitle' => $stored['wedding']['title'],
];
$html = render_theme_layout($stored, $shared);
visual_assert(strpos($html, '--cms-elix-accent:#123456') !== false, 'Elix render includes the stored accent bridge');
visual_assert(strpos($html, 'uploads/background/hero.jpg') !== false, 'Elix render includes the stored hero media bridge');
visual_assert(strpos($html, '--cms-elix-countdown-scale:0.66') !== false, 'Elix render includes the stored countdown scale bridge');

$adminSource = file_get_contents(dirname(__DIR__) . '/admin/index.php');
$appSource = file_get_contents(dirname(__DIR__) . '/admin/app.js');
visual_assert(is_string($adminSource) && str_contains($adminSource, 'class="visual-capability-panel"'), 'Admin renders the preset visual capability panel');
visual_assert(is_string($adminSource) && str_contains($adminSource, 'data-visual-schemas='), 'Admin exposes dynamic visual schemas to the editor');
visual_assert(is_string($adminSource) && str_contains($adminSource, 'name="reset_visuals"'), 'Admin exposes per-preset visual reset');
visual_assert(is_string($appSource) && str_contains($appSource, 'setPreviewViewport'), 'Admin editor exposes responsive preview viewport controls');

foreach (['dewankl', 'rainier', 'archak'] as $preset) {
    $probe = $stored;
    $probe['theme']['mode'] = 'preset';
    $probe['theme']['theme_preset'] = $preset;
    $probe['theme_visuals'][$preset] = array_replace(
        theme_visual_values_for_config($probe, $preset),
        ['accent_color' => '#123456', 'hero_background' => 'uploads/background/hero.jpg']
    );
    $presetShared = $shared;
    $presetShared['presetKey'] = $preset;
    $presetHtml = render_theme_layout($probe, $presetShared);
    visual_assert(strpos($presetHtml, '#123456') !== false, ucfirst($preset) . ' render includes the stored accent bridge');
    visual_assert(strpos($presetHtml, 'uploads/background/hero.jpg') !== false, ucfirst($preset) . ' render includes the stored hero media bridge');
}

echo "PASS: visual contract smoke test\n";
ob_end_flush();
