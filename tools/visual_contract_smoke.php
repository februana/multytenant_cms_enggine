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
    'parang' => ['accent_color', 'heading_font', 'body_font', 'hero_background'],
    'pawiwahan' => ['accent_color', 'heading_font', 'body_font', 'hero_background'],
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
$stored['theme_visuals'] = array_replace($base['theme_visuals'] ?? [], [
    'elix' => ['accent_color' => '#123456', 'hero_background' => 'uploads/background/hero.jpg', 'countdown_scale' => '0.66'],
    'rainier' => ['accent_color' => '#654321'],
]);
$stored['theme_custom'] = theme_custom_config($stored);
$elixValues = theme_visual_values_for_config($stored, 'elix');
$rainierValues = theme_visual_values_for_config($stored, 'rainier');
visual_assert($elixValues['accent_color'] === '#123456', 'Elix visual override persists');
visual_assert($elixValues['hero_background'] === 'uploads/background/hero.jpg', 'Elix media path persists unchanged');
visual_assert($elixValues['countdown_scale'] === '0.66', 'Elix range override persists');
visual_assert($rainierValues['accent_color'] === '#654321', 'Rainier override is stored independently');
visual_assert($rainierValues['accent_color'] !== $elixValues['accent_color'], 'Visual overrides do not leak across presets');

$switchBase = $stored;
$switchBase['theme_visuals']['archak'] = ['accent_color' => '#abcdef', 'hero_title_scale' => '1.05'];
foreach (['custom', 'dewankl', 'elix', 'rainier', 'archak', 'parang', 'pawiwahan', 'custom'] as $switchPreset) {
    $switched = switch_active_theme_preset_config($switchBase, $switchPreset);
    visual_assert(is_array($switched), "switching to {$switchPreset} succeeds");
    visual_assert(($switched['theme_visuals']['elix']['accent_color'] ?? '') === '#123456', "switching to {$switchPreset} preserves Elix visuals");
    visual_assert(($switched['theme_visuals']['archak']['hero_title_scale'] ?? '') === '1.05', "switching to {$switchPreset} preserves Archak visuals");
    $switchBase = $switched;
}
$usageProbe = $switchBase;
$usageProbe['theme_visuals']['elix']['hero_background'] = 'uploads/background/in-use.png';
visual_assert(in_array('Visual Elix / hero background', detect_media_usage($usageProbe, 'uploads/background/in-use.png'), true), 'Media usage detects visual preset references');
$resetProbe = $switchBase;
reset_theme_visual_overrides($resetProbe, 'elix');
visual_assert(($resetProbe['theme_visuals']['elix'] ?? null) === [], 'reset_visuals clears only the active Elix overrides');
visual_assert(($resetProbe['theme_visuals']['rainier']['accent_color'] ?? '') === '#654321', 'reset_visuals preserves hidden Rainier overrides');
visual_assert(($resetProbe['theme_visuals']['archak']['accent_color'] ?? '') === '#abcdef', 'reset_visuals preserves hidden Archak overrides');
visual_assert(theme_visual_public_path('https://cdn.example.test/hero.jpg') === 'https://cdn.example.test/hero.jpg', 'HTTPS visual URL is not prefixed with a slash');
visual_assert(validate_theme_visual_value('#abcdef', ['type' => 'color']) === '#abcdef', 'Color validation accepts valid hex');
visual_assert(validate_theme_visual_value('#not-a-color', ['type' => 'color']) === null, 'Color validation rejects invalid values');
visual_assert(validate_theme_visual_value('1.50', ['type' => 'range', 'min' => '0', 'max' => '1']) === null, 'Range validation rejects out-of-range values');
visual_assert(validate_theme_visual_value('Pacifico, cursive', theme_visual_capabilities_for_config($stored, 'archak')['heading_font']) === null, 'Unsupported Archak font is rejected');
visual_assert(!array_key_exists('glass_opacity', theme_visual_values_for_config($stored, 'archak')), 'Unsupported visual capability is not resolved for Archak');

$customState = $base;
$customState['theme']['mode'] = 'custom';
$customState['theme']['theme_preset'] = 'custom';
$customState['theme']['accent_color'] = '#112233';
$customState['theme']['heading_font'] = 'Cormorant Garamond, serif';
$customState['site']['url'] = 'https://global.example.test';
$customState['theme_custom'] = theme_custom_config($customState);
foreach (['elix', 'rainier', 'archak', 'dewankl'] as $switchPreset) {
    $customState = switch_active_theme_preset_config($customState, $switchPreset);
    visual_assert(($customState['site']['url'] ?? '') === 'https://global.example.test', "global site URL survives Custom -> {$switchPreset}");
}
$customState = switch_active_theme_preset_config($customState, 'custom');
visual_assert(($customState['theme']['accent_color'] ?? '') === '#112233', 'Custom accent survives switching away and back');
visual_assert(($customState['theme']['heading_font'] ?? '') === 'Cormorant Garamond, serif', 'Custom font survives switching away and back');

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
visual_assert(strpos($html, '.hero,.hero h1,.hero h4,.hero p{color:#fff}') !== false, 'Elix hero maintains readable contrast over dark backgrounds');
visual_assert(strpos($html, '#hero{display:flex;justify-content:center;align-items:center;text-align:center;box-sizing:border-box}') !== false, 'Elix hero restores the source flex-centered root relationship');
visual_assert(strpos($html, '#hero>main{display:flex;flex-direction:column;align-items:center;width:min(100%,56rem);margin:0 auto;text-align:center}') !== false, 'Elix hero restores a centered column content relationship');
visual_assert(strpos($html, '#hero>main>#countdown{width:100%;display:flex;justify-content:center;align-items:center;text-align:center}') !== false, 'Elix countdown remains in the centered hero flow');
visual_assert(strpos($html, '#hero>main>a{display:inline-block;align-self:center;margin-right:auto;margin-left:auto}') !== false, 'Elix CTA is structurally centered below the countdown');
visual_assert(is_file(dirname(__DIR__) . '/themes/elix/img/prewed1.jpg'), 'Elix source hero asset is bundled locally');
visual_assert(is_file(dirname(__DIR__) . '/themes/elix/img/floraPattern1.png'), 'Elix source home pattern is bundled locally');
visual_assert(strpos($html, '/themes/elix/img/prewed1.jpg') !== false || strpos($html, 'uploads/background/hero.jpg') !== false, 'Elix render has a valid local source or CMS hero background');
$dewanklSourceConfig = $base;
$dewanklSourceConfig['theme']['mode'] = 'preset';
$dewanklSourceConfig['theme']['theme_preset'] = 'dewankl';
$dewanklDefaults = theme_visual_values_for_config($dewanklSourceConfig, 'dewankl');
visual_assert(($dewanklDefaults['body_font'] ?? '') === 'Josefin Sans, sans-serif', 'DewanaKL body font default matches the source template');
$dewanklHtml = render_theme_layout($dewanklSourceConfig, array_replace($shared, ['presetKey' => 'dewankl']));
visual_assert(strpos($dewanklHtml, '--cms-dewana-body:Josefin Sans, sans-serif') !== false, 'DewanaKL source body font reaches the adapter by default');
visual_assert(strpos($dewanklHtml, 'body{font-family:var(--cms-dewana-body)!important}') !== false, 'DewanaKL CMS body font can override the source !important rule');
visual_assert(is_file(dirname(__DIR__) . '/themes/dewankl/assets/placeholder.webp'), 'DewanaKL source placeholder is bundled locally');
visual_assert(is_file(dirname(__DIR__) . '/themes/dewankl/assets/icon-192x192.png'), 'DewanaKL source loading icon is bundled locally');
visual_assert(strpos($dewanklHtml, '/themes/dewankl/assets/placeholder.webp') !== false, 'DewanaKL blank media uses the local placeholder fallback');
visual_assert(strpos($dewanklHtml, '/themes/dewankl/assets/icon-192x192.png') !== false, 'DewanaKL loading/icon paths use the local source asset');
$dewanklOverride = $dewanklSourceConfig;
$dewanklOverride['theme_visuals']['dewankl']['body_font'] = 'Arial, sans-serif';
$dewanklOverrideHtml = render_theme_layout($dewanklOverride, array_replace($shared, ['presetKey' => 'dewankl']));
visual_assert(strpos($dewanklOverrideHtml, '--cms-dewana-body:Arial, sans-serif') !== false, 'DewanaKL body font override persists independently');

$adminSource = file_get_contents(dirname(__DIR__) . '/admin/index.php');
$appSource = file_get_contents(dirname(__DIR__) . '/admin/app.js');
visual_assert(is_string($appSource) && !str_contains($appSource, 'const activeVisualPreset'), 'Admin does not cache the initial active visual preset');
visual_assert(is_string($appSource) && str_contains($appSource, 'function getCurrentPreset'), 'Admin resolves the current preset at action time');
visual_assert(is_string($appSource) && str_contains($appSource, 'visualFields.querySelectorAll'), 'Admin collects only the current visual panel inputs');
visual_assert(is_string($appSource) && str_contains($appSource, 'globalThemePreset?.addEventListener'), 'Global preset selector updates the visual editor immediately');
visual_assert(is_string($adminSource) && str_contains($adminSource, 'class="visual-capability-panel"'), 'Admin renders the preset visual capability panel');
visual_assert(is_string($adminSource) && str_contains($adminSource, 'data-visual-schemas='), 'Admin exposes dynamic visual schemas to the editor');
visual_assert(is_string($adminSource) && str_contains($adminSource, 'name="reset_visuals"'), 'Admin exposes per-preset visual reset');
visual_assert(is_string($adminSource) && str_contains($adminSource, 'data-media-assets='), 'Admin exposes canonical image assets to visual editor');
visual_assert(is_string($adminSource) && !str_contains($adminSource, 'name="visual_file_'), 'Admin visual editor does not create a duplicate uploader');
visual_assert(is_string($appSource) && str_contains($appSource, 'mediaAssets'), 'Admin visual editor consumes canonical media assets');
visual_assert(is_string($appSource) && str_contains($appSource, 'input.dataset.visualMediaSelect'), 'Admin image capability uses a media selector');
visual_assert(is_string($appSource) && str_contains($appSource, 'label.htmlFor = id'), 'Dynamic visual fields associate labels with controls');
visual_assert(is_string($appSource) && !str_contains($appSource, 'innerHTML'), 'Dynamic visual editor avoids raw HTML injection');
visual_assert(is_string($adminSource) && str_contains($adminSource, 'title="Preview tema undangan"'), 'Theme preview iframe has an accessible title');
visual_assert(is_string($adminSource) && str_contains($adminSource, 'aria-label="Ukuran preview"'), 'Preview viewport controls have an accessible group label');
visual_assert(is_string($appSource) && str_contains($appSource, 'setPreviewViewport'), 'Admin editor exposes responsive preview viewport controls');
visual_assert(theme_builtin_preset_keys() === ['dewankl', 'elix', 'rainier', 'archak', 'parang', 'pawiwahan'], 'Preset selector exposes only renderer-backed built-ins');
$mediaProbeName = 'uploads/background/visual-contract-probe-' . getmypid() . '.webp';
$mediaProbePath = ROOT_DIR . '/' . $mediaProbeName;
copy(dirname(__DIR__) . '/themes/parang/assets/parang-pattern.webp', $mediaProbePath);
visual_assert(theme_visual_image_reference_is_canonical($mediaProbeName), 'Canonical existing WebP is accepted for visual image reference');
visual_assert(!theme_visual_image_reference_is_canonical('uploads/background/not-present.webp'), 'Missing canonical image is rejected');
visual_assert(theme_visual_image_reference_is_canonical('https://cdn.example.test/hero.jpg'), 'HTTPS image URL remains accepted');
unlink($mediaProbePath);

$archakSourceConfig = $base;
$archakSourceConfig['theme']['mode'] = 'preset';
$archakSourceConfig['theme']['theme_preset'] = 'archak';
$archakSourceHtml = render_theme_layout($archakSourceConfig, array_replace($shared, ['presetKey' => 'archak']));
visual_assert(!str_contains($archakSourceHtml, "background-image:url('/');"), 'Archak blank CMS media does not emit an empty root background override');
visual_assert(!str_contains($archakSourceHtml, 'style="background-image:url(\'/\');"'), 'Archak blank CMS media preserves source CSS background fallbacks');

$rainierSourceConfig = $base;
$rainierSourceConfig['theme']['mode'] = 'preset';
$rainierSourceConfig['theme']['theme_preset'] = 'rainier';
$rainierSourceHtml = render_theme_layout($rainierSourceConfig, array_replace($shared, ['presetKey' => 'rainier']));
visual_assert(str_contains($rainierSourceHtml, 'images.unsplash.com/photo-1514876246314-d9a231ea21db'), 'Rainier blank CMS media restores the source hero image set');
visual_assert(str_contains($rainierSourceHtml, 'Rainier%20Logo-Primary.svg'), 'Rainier blank CMS media restores the source footer logo');
$rainierOverrideConfig = $rainierSourceConfig;
$rainierOverrideConfig['theme_visuals']['rainier']['hero_background'] = 'uploads/background/rainier-hero.png';
$rainierOverrideHtml = render_theme_layout($rainierOverrideConfig, array_replace($shared, ['presetKey' => 'rainier']));
visual_assert(str_contains($rainierOverrideHtml, 'uploads/background/rainier-hero.png'), 'Rainier CMS hero override reaches the dynamic design payload');

foreach (['dewankl', 'rainier', 'archak', 'parang', 'pawiwahan'] as $preset) {
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

$customRenderConfig = $base;
$customRenderConfig['theme']['mode'] = 'custom';
$customRenderConfig['theme']['theme_preset'] = 'custom';
$customRenderConfig['theme_visuals']['custom'] = [
    'accent_color' => '#102030',
    'background_color' => '#fefefe',
    'paper_color' => '#fafafa',
    'text_color' => '#203040',
    'heading_font' => 'Georgia, serif',
    'body_font' => 'Inter, sans-serif',
    'hero_background' => 'uploads/background/custom.jpg',
    'hero_overlay' => '0.20',
    'hero_title_scale' => '1.10',
];
$customRender = render_theme_layout($customRenderConfig, $shared);
$customAdapter = theme_custom_visual_style($customRenderConfig);
visual_assert(str_contains($customAdapter, '--primary:#102030'), 'Custom accent reaches the production adapter');
visual_assert(str_contains($customAdapter, 'custom.jpg'), 'Custom background reaches the production adapter');
visual_assert(str_contains($customAdapter, '--hero-title-scale:1.1'), 'Custom title scale reaches the production adapter');
visual_assert(str_contains($customAdapter, '--font-heading:Georgia, serif'), 'Custom heading font reaches the production adapter');
visual_assert(str_contains($customRender, 'id="hero"') || str_contains($customRender, 'theme-section hero'), 'Custom renderer preserves the native hero markup');
$parangConfig = $base;
$parangConfig['theme']['mode'] = 'preset';
$parangConfig['theme']['theme_preset'] = 'parang';
$parangValues = theme_visual_values_for_config($parangConfig, 'parang');
visual_assert(($parangValues['hero_background'] ?? '') !== '', 'Parang resolves the supplied source background by default');
$parangHtml = render_theme_layout($parangConfig, array_replace($shared, ['presetKey' => 'parang']));
visual_assert(str_contains($parangHtml, 'id="cms-parang-root"'), 'Parang render preserves its native root');
visual_assert(str_contains($parangHtml, '/themes/parang/assets/parang-pattern.webp'), 'Parang render retains the supplied local parang background asset');
$pawiwahanConfig = $base;
$pawiwahanConfig['theme']['mode'] = 'preset';
$pawiwahanConfig['theme']['theme_preset'] = 'pawiwahan';
$pawiwahanValues = theme_visual_values_for_config($pawiwahanConfig, 'pawiwahan');
visual_assert(($pawiwahanValues['hero_background'] ?? '') !== '', 'Pawiwahan resolves a source hero background fallback');
$pawiwahanHtml = render_theme_layout($pawiwahanConfig, array_replace($shared, ['presetKey' => 'pawiwahan']));
visual_assert(str_contains($pawiwahanHtml, 'id="carouselExampleCaptions"'), 'Pawiwahan render preserves the source carousel root');
visual_assert(str_contains($pawiwahanHtml, 'id="welcomeModal"'), 'Pawiwahan render preserves the source welcome modal');
visual_assert(str_contains($pawiwahanHtml, 'id="hitungmundur"'), 'Pawiwahan render preserves the source countdown root');
visual_assert(str_contains($pawiwahanHtml, '/themes/pawiwahan/assets/hero-source.jpg'), 'Pawiwahan render uses a local non-user source fallback');
visual_assert(is_file(dirname(__DIR__) . '/themes/pawiwahan/assets/images/ornam/Asset5.png'), 'Pawiwahan source ornament is retained locally');

echo "PASS: visual contract smoke test\n";
ob_end_flush();
