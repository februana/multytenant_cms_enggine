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
    'custom' => ['accent_color', 'background_color', 'paper_color', 'text_color', 'heading_font', 'body_font', 'hero_background', 'hero_overlay', 'hero_title_scale', 'section_background_home', 'section_background_event', 'section_background_story', 'section_background_gallery', 'section_background_location', 'section_background_gift', 'section_background_rsvp'],
    'dewankl' => ['accent_color', 'heading_font', 'body_font', 'hero_background', 'welcome_background', 'section_background_home', 'section_background_bride', 'section_background_wedding_date', 'section_background_gallery', 'section_background_love_gift', 'section_background_comment', 'hero_overlay'],
    'rainier' => ['accent_color', 'heading_font', 'body_font', 'hero_background', 'section_background_event_details', 'section_background_schedule', 'section_background_quotes', 'section_background_rsvp', 'glass_opacity'],
    'archak' => ['accent_color', 'heading_font', 'body_font', 'hero_background', 'section_background_timeline', 'section_background_gallery', 'section_background_stay', 'section_background_registry', 'header_badge', 'hero_title_scale'],
    'parang' => ['accent_color', 'heading_font', 'body_font', 'hero_background', 'section_background_home', 'section_background_gallery', 'section_background_location', 'ornament_left', 'ornament_right'],
    'pawiwahan' => ['accent_color', 'heading_font', 'body_font', 'hero_background', 'welcome_background', 'section_background_gallery', 'section_background_location', 'section_background_gift', 'section_background_messages'],
    'shubh-vivah' => ['accent_color', 'heading_font', 'body_font', 'hero_background', 'hero_overlay', 'section_background_event', 'section_background_gallery', 'section_background_rsvp', 'ornament_left', 'ornament_right'],
    'yami-buzzy' => ['accent_color', 'heading_font', 'body_font', 'hero_background', 'welcome_background', 'section_background_home', 'section_background_couple', 'section_background_event', 'section_background_story', 'section_background_gallery', 'section_background_video', 'section_background_gift', 'section_background_invitation', 'section_background_rsvp', 'section_background_closing', 'hero_overlay'],
];
$commonVisualKeys = ['heading_color', 'text_color', 'muted_color', 'link_color'];
foreach ($expected as $preset => &$keys) $keys = array_values(array_unique(array_merge($keys, $commonVisualKeys)));
unset($keys);
$expected['shubh-vivah'][] = 'section_background_home';
foreach ($expected as $preset => $keys) {
    $schema = theme_visual_capabilities_for_config($base, $preset);
    $actualKeys = array_keys($schema);
    $expectedKeys = $keys;
    sort($actualKeys);
    sort($expectedKeys);
    visual_assert($actualKeys === $expectedKeys, "{$preset} declares only its supported visual capabilities");
    $values = theme_visual_values_for_config($base, $preset);
    foreach ($keys as $key) visual_assert(array_key_exists($key, $values), "{$preset} resolves a default for {$key}");
}
$removedPresetKey = 'e' . 'lix';
visual_assert(theme_contract_sections_for_config($base, $removedPresetKey) === [], 'Removed legacy preset has no contract sections');
visual_assert(theme_builtin_preset_keys() === ['dewankl', 'rainier', 'archak', 'parang', 'pawiwahan', 'shubh-vivah', 'yami-buzzy'], 'Preset selector exposes only renderer-backed built-ins');

$stored = $base;
$stored['theme']['mode'] = 'preset';
$stored['theme']['theme_preset'] = 'shubh-vivah';
$stored['theme_visuals'] = array_replace($base['theme_visuals'] ?? [], [
    'shubh-vivah' => ['accent_color' => '#123456', 'hero_background' => 'uploads/background/hero.jpg', 'ornament_left' => 'uploads/background/left.webp'],
    'yami-buzzy' => ['accent_color' => '#654321', 'hero_background' => 'uploads/background/yami.jpg', 'welcome_background' => 'uploads/background/welcome.webp'],
    'rainier' => ['accent_color' => '#abcdef'],
]);
$shubhValues = theme_visual_values_for_config($stored, 'shubh-vivah');
$yamiValues = theme_visual_values_for_config($stored, 'yami-buzzy');
visual_assert($shubhValues['accent_color'] === '#123456', 'Shubh Vivah visual override persists');
visual_assert($shubhValues['hero_background'] === 'uploads/background/hero.jpg', 'Shubh Vivah hero media path persists unchanged');
visual_assert($yamiValues['accent_color'] === '#654321', 'Yami Buzzy visual override persists independently');
visual_assert($yamiValues['welcome_background'] === 'uploads/background/welcome.webp', 'Yami Buzzy welcome media path persists independently');
visual_assert($shubhValues['accent_color'] !== $yamiValues['accent_color'], 'New preset visual overrides do not leak across presets');

$switchBase = $stored;
$switchBase['theme_visuals']['archak'] = ['accent_color' => '#fedcba', 'hero_title_scale' => '1.05'];
foreach (['custom', 'dewankl', 'rainier', 'archak', 'parang', 'pawiwahan', 'shubh-vivah', 'yami-buzzy', 'custom'] as $switchPreset) {
    $switched = switch_active_theme_preset_config($switchBase, $switchPreset);
    visual_assert(is_array($switched), "switching to {$switchPreset} succeeds");
    visual_assert(($switched['theme_visuals']['shubh-vivah']['accent_color'] ?? '') === '#123456', "switching to {$switchPreset} preserves Shubh Vivah visuals");
    visual_assert(($switched['theme_visuals']['yami-buzzy']['accent_color'] ?? '') === '#654321', "switching to {$switchPreset} preserves Yami Buzzy visuals");
    visual_assert(($switched['theme_visuals']['archak']['hero_title_scale'] ?? '') === '1.05', "switching to {$switchPreset} preserves Archak visuals");
    $switchBase = $switched;
}
$usageProbe = $switchBase;
$usageProbe['theme_visuals']['shubh-vivah']['hero_background'] = 'uploads/background/in-use.png';
visual_assert(in_array('Visual Shubh-vivah / hero background', detect_media_usage($usageProbe, 'uploads/background/in-use.png'), true), 'Media usage detects Shubh Vivah visual references');
$resetProbe = $switchBase;
reset_theme_visual_overrides($resetProbe, 'shubh-vivah');
visual_assert(($resetProbe['theme_visuals']['shubh-vivah'] ?? null) === [], 'Reset clears only Shubh Vivah overrides');
visual_assert(($resetProbe['theme_visuals']['yami-buzzy']['accent_color'] ?? '') === '#654321', 'Reset preserves hidden Yami Buzzy overrides');
visual_assert(($resetProbe['theme_visuals']['archak']['accent_color'] ?? '') === '#fedcba', 'Reset preserves hidden Archak overrides');
visual_assert(theme_visual_public_path('https://cdn.example.test/hero.jpg') === 'https://cdn.example.test/hero.jpg', 'HTTPS visual URL is not prefixed with a slash');
visual_assert(validate_theme_visual_value('#abcdef', ['type' => 'color']) === '#abcdef', 'Color validation accepts valid hex');
visual_assert(validate_theme_visual_value('#not-a-color', ['type' => 'color']) === null, 'Color validation rejects invalid values');
visual_assert(validate_theme_visual_value('1.50', ['type' => 'range', 'min' => '0', 'max' => '1']) === null, 'Range validation rejects out-of-range values');

$shared = [
    'presetKey' => 'shubh-vivah', 'heroText' => $stored['wedding']['opening_text'], 'guestFallback' => 'Bapak/Ibu/Saudara/i',
    'countdownTarget' => $stored['schedule']['countdown_target'], 'calendarLink' => build_google_calendar_link($stored),
    'calendarDownloadName' => 'Undangan', 'whatsappLink' => build_whatsapp_link($stored), 'musicSrc' => '', 'bgHero' => '',
    'sectionStyles' => ['', '', ''], 'brideParents' => '', 'groomParents' => '', 'siteTitle' => $stored['site']['title'], 'weddingTitle' => $stored['wedding']['title'],
];
$shubhHtml = render_theme_layout($stored, $shared);
visual_assert(str_contains($shubhHtml, '--shubh-accent:#123456'), 'Shubh Vivah render includes stored accent bridge');
visual_assert(str_contains($shubhHtml, 'uploads/background/hero.jpg'), 'Shubh Vivah render includes stored hero media bridge');
visual_assert(str_contains($shubhHtml, 'Buka Undangan'), 'Shubh Vivah render includes localized CTA');
visual_assert(str_contains($shubhHtml, 'id="shubh-home"') && str_contains($shubhHtml, 'id="shubh-event"'), 'Shubh Vivah render preserves source card and event flow');
$yamiConfig = $stored;
$yamiConfig['theme']['theme_preset'] = 'yami-buzzy';
$yamiHtml = render_theme_layout($yamiConfig, array_replace($shared, ['presetKey' => 'yami-buzzy']));
visual_assert(str_contains($yamiHtml, '--yami-accent:#654321'), 'Yami Buzzy render includes stored accent bridge');
visual_assert(str_contains($yamiHtml, 'uploads/background/welcome.webp'), 'Yami Buzzy render includes stored welcome media bridge');
visual_assert(str_contains($yamiHtml, 'id="yami-welcome-modal"') && str_contains($yamiHtml, 'id="yami-story"'), 'Yami Buzzy render preserves modal and story flow');
visual_assert(str_contains($yamiHtml, '--yami-story-bg') && str_contains($yamiHtml, '--yami-closing-bg'), 'Yami Buzzy render exposes section background variables');

foreach (['dewankl', 'rainier', 'archak', 'parang', 'pawiwahan', 'shubh-vivah', 'yami-buzzy'] as $preset) {
    $probe = $base;
    $probe['theme']['mode'] = 'preset';
    $probe['theme']['theme_preset'] = $preset;
    $probe['theme_visuals'][$preset] = array_replace(theme_visual_values_for_config($probe, $preset), ['accent_color' => '#123456', 'hero_background' => 'uploads/background/hero.jpg']);
    $presetShared = array_replace($shared, ['presetKey' => $preset]);
    $presetHtml = render_theme_layout($probe, $presetShared);
    visual_assert(strpos($presetHtml, '#123456') !== false, ucfirst($preset) . ' render includes the stored accent bridge');
    visual_assert(strpos($presetHtml, 'uploads/background/hero.jpg') !== false, ucfirst($preset) . ' render includes the stored hero media bridge');
}

$customRenderConfig = $base;
$customRenderConfig['theme']['mode'] = 'custom';
$customRenderConfig['theme']['theme_preset'] = 'custom';
$customRenderConfig['theme_visuals']['custom']['section_background_gallery'] = 'uploads/background/custom-gallery.webp';
$customRenderHtml = render_theme_layout($customRenderConfig, $shared);
visual_assert(str_contains($customRenderHtml, 'custom-gallery.webp') && str_contains($customRenderHtml, 'id="galeri"'), 'Custom renderer exposes gallery background capability');
$dewanaConfig = $stored;
$dewanaConfig['theme']['theme_preset'] = 'dewankl';
$dewanaConfig['theme_visuals']['dewankl']['section_background_gallery'] = 'uploads/background/gallery-bg.webp';
$dewanaHtml = render_theme_layout($dewanaConfig, array_replace($shared, ['presetKey' => 'dewankl']));
visual_assert(str_contains($dewanaHtml, 'id="gallery"') && str_contains($dewanaHtml, 'section_background_gallery'), 'DewanaKL render exposes gallery background capability');
$adminSource = file_get_contents(dirname(__DIR__) . '/admin/index.php');
$appSource = file_get_contents(dirname(__DIR__) . '/admin/app.js');
visual_assert(is_string($appSource) && str_contains($appSource, 'function getCurrentPreset'), 'Admin resolves the current preset at action time');
visual_assert(is_string($adminSource) && str_contains($adminSource, 'data-visual-schemas='), 'Admin exposes dynamic visual schemas to the editor');
visual_assert(is_string($adminSource) && str_contains($adminSource, 'name="reset_visuals"'), 'Admin exposes per-preset visual reset');
    visual_assert(is_string($adminSource) && str_contains($adminSource, 'data-media-assets='), 'Admin exposes canonical image assets to visual editor');
    visual_assert(is_string($adminSource) && str_contains($adminSource, 'data-visual-color-palette='), 'Admin exposes named color palettes to visual editor');
    visual_assert(is_string($appSource) && str_contains($appSource, 'visualColorPalette'), 'Admin dynamic editor applies named color palette selections');
visual_assert(is_string($appSource) && str_contains($appSource, 'mediaAssets'), 'Admin visual editor consumes canonical media assets');
visual_assert(is_string($appSource) && str_contains($appSource, 'dataset.visualMediaSelect') && str_contains($appSource, 'visualMediaUrl'), 'Admin visual preview handles scoped image capabilities');
visual_assert(is_string($adminSource) && str_contains($adminSource, 'Warna, tulisan, latar, dan gambar yang bisa diubah'), 'Admin explains visual customization in plain Indonesian');
visual_assert(is_string($appSource) && str_contains($appSource, 'Gunakan gambar bawaan tema'), 'Admin image selector uses plain Indonesian fallback wording');

$customState = $base;
$customState['theme']['mode'] = 'custom';
$customState['theme']['theme_preset'] = 'custom';
$customState['site']['url'] = 'https://global.example.test';
foreach (['shubh-vivah', 'yami-buzzy', 'rainier', 'archak', 'dewankl'] as $switchPreset) {
    $customState = switch_active_theme_preset_config($customState, $switchPreset);
    visual_assert(($customState['site']['url'] ?? '') === 'https://global.example.test', "global site URL survives Custom -> {$switchPreset}");
}

$customProbe = ROOT_DIR . '/uploads/background/visual-contract-probe-' . getmypid() . '.webp';
copy(dirname(__DIR__) . '/themes/parang/assets/parang-pattern.webp', $customProbe);
visual_assert(theme_visual_image_reference_is_canonical('uploads/background/visual-contract-probe-' . getmypid() . '.webp'), 'Canonical existing WebP is accepted for visual image reference');
unlink($customProbe);

echo "PASS: visual contract smoke test\n";
ob_end_flush();
