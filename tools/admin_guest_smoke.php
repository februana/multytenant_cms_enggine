<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function assert_true(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$expected = [
    'custom' => ['preset_selector', 'guest_links', 'settings', 'backup', 'theme', 'sections'],
    'dewankl' => ['preset_selector', 'guest_links', 'settings', 'backup', 'theme', 'wedding', 'parents', 'schedule', 'gallery', 'music', 'gift', 'maps', 'rsvp', 'dresscode'],
    'shubh-vivah' => ['preset_selector', 'guest_links', 'settings', 'backup', 'theme', 'wedding', 'schedule', 'gallery', 'music', 'maps', 'rsvp', 'messages', 'media', 'seo', 'whatsapp'],
    'yami-buzzy' => ['preset_selector', 'guest_links', 'settings', 'backup', 'theme', 'wedding', 'parents', 'schedule', 'gallery', 'story', 'music', 'gift', 'maps', 'rsvp', 'messages', 'media', 'seo', 'whatsapp', 'dresscode'],
    'rainier' => ['preset_selector', 'guest_links', 'settings', 'backup', 'theme', 'wedding', 'schedule', 'story', 'music', 'maps', 'rsvp'],
    'archak' => ['preset_selector', 'guest_links', 'settings', 'backup', 'theme', 'wedding', 'parents', 'schedule', 'gallery', 'story', 'gift', 'maps', 'rsvp'],
    'parang' => ['preset_selector', 'guest_links', 'settings', 'backup', 'theme', 'wedding', 'parents', 'schedule', 'gallery', 'story', 'music', 'gift', 'maps', 'rsvp', 'dresscode'],
    'pawiwahan' => ['preset_selector', 'guest_links', 'settings', 'backup', 'theme', 'wedding', 'parents', 'schedule', 'gallery', 'music', 'gift', 'maps', 'rsvp', 'messages'],
];
$forbidden = [
    'rainier' => ['parents', 'gallery', 'gift', 'dresscode', 'sections'],
    'archak' => ['music', 'dresscode', 'sections'],
    'dewankl' => ['sections', 'custom_css', 'cover', 'background'],
];
$expectedMediaRoles = [
    'custom' => ['cover', 'bride_photo', 'groom_photo', 'couple_photo', 'love_story_video'],
    'dewankl' => ['cover', 'bride_photo', 'groom_photo', 'couple_photo', 'love_story_video'],
    'shubh-vivah' => [],
    'yami-buzzy' => ['bride_photo', 'groom_photo', 'couple_photo', 'love_story_video'],
    'rainier' => [],
    'archak' => ['cover', 'bride_photo', 'groom_photo', 'couple_photo'],
    'parang' => ['bride_photo', 'groom_photo'],
    'pawiwahan' => ['cover', 'bride_photo', 'groom_photo'],
];
$base = load_config();
foreach ($expected as $preset => $required) {
    $config = $base;
    $config['theme']['mode'] = $preset === 'custom' ? 'custom' : 'preset';
    $config['theme']['theme_preset'] = $preset;
    $caps = theme_admin_capabilities_for_config($config);
    foreach ($required as $capability) assert_true(in_array($capability, $caps, true), "$preset missing $capability");
    foreach ($forbidden[$preset] ?? [] as $capability) assert_true(!in_array($capability, $caps, true), "$preset exposes forbidden $capability");
    assert_true(theme_contract_media_roles($preset) === ($expectedMediaRoles[$preset] ?? []), "$preset media role mapping mismatch");
}

assert_true(theme_contract_global_admin_capabilities() === ['preset_selector', 'guest_links', 'settings', 'backup', 'theme'], 'global admin capability contract changed unexpectedly');
assert_true(config_defaults()['site']['url'] === '', 'clean-install site URL must be explicitly unconfigured');
assert_true(build_guest_invitation_url('https://test.example.id', 'Budi') === 'https://test.example.id/?to=Budi', 'configured site origin was not used');
assert_true(build_guest_invitation_url('', 'Budi') === '', 'missing site origin must not generate a guest URL');
assert_true(!str_starts_with(build_guest_invitation_url('https://test.example.id', 'Budi'), 'https://example.com/'), 'guest URL silently uses example.com');

assert_true(build_guest_invitation_url('https://example.com', 'Andi & <script>') === 'https://example.com/?to=Andi%20%26%20%3Cscript%3E', 'guest URL encoding failed');
assert_true(build_guest_invitation_url('/', 'Andi') === '/?to=Andi', 'root guest URL failed');
assert_true(build_guest_invitation_url('javascript:alert(1)', 'Andi') === '', 'unsafe guest URL base accepted');
assert_true(normalize_guest_name("  Andi\n\t  ") === 'Andi', 'guest whitespace normalization failed');
$normalizedLongGuest = normalize_guest_name(str_repeat('A', 200));
$normalizedLongGuestLength = function_exists('mb_strlen') ? mb_strlen($normalizedLongGuest) : strlen($normalizedLongGuest);
assert_true($normalizedLongGuestLength === 120, 'guest length limit failed');

$_GET['to'] = 'Budi%20%26%20Sari';
assert_true(resolve_guest_name($base) === 'Budi%20%26%20Sari', 'query resolver unexpectedly double-decodes or trusts malformed data');
$_GET['to'] = 'Budi & Sari';
assert_true(resolve_guest_name($base) === 'Budi & Sari', 'query resolver failed normal name');
unset($_GET['to']);

$preserved = $base;
$preserved['site']['url'] = 'https://test.example.id';
$preserved['theme_sections']['rainier'][0]['enabled'] = false;
$preserved['theme_sections']['rainier'][0]['custom_title'] = 'Stored Rainier title';
$preserved['media']['music'] = 'music/keep.mp3';
$preserved['wedding']['bride_name'] = 'Stored Bride';
$before = $preserved;
foreach (['mode', 'theme_preset'] as $key) unset($before['theme'][$key]);
$before = json_encode($before, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$sequence = ['custom', 'dewankl', 'rainier', 'archak', 'parang', 'pawiwahan', 'shubh-vivah', 'yami-buzzy', 'custom'];
foreach ($sequence as $preset) {
    $switched = switch_active_theme_preset_config($preserved, $preset);
    assert_true(is_array($switched), "switch failed for $preset");
    assert_true(($switched['theme']['theme_preset'] ?? '') === $preset, "selector state mismatch for $preset");
    assert_true(($switched['theme']['mode'] ?? '') === ($preset === 'custom' ? 'custom' : 'preset'), "mode mismatch for $preset");
    assert_true(($switched['site']['url'] ?? '') === 'https://test.example.id', "site URL changed for $preset");
    assert_true(build_guest_invitation_url((string)$switched['site']['url'], 'Budi') === 'https://test.example.id/?to=Budi', "guest URL origin changed for $preset");
    $after = $switched;
    foreach (['mode', 'theme_preset'] as $key) unset($after['theme'][$key]);
    assert_true(json_encode($after, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) === $before, "switch mutated stored data for $preset");
    $preserved = $switched;
}

$adminSource = file_get_contents(dirname(__DIR__) . '/admin/index.php');
$appSource = file_get_contents(dirname(__DIR__) . '/admin/app.js');
assert_true(is_string($appSource), 'admin guest-link script is unreadable');
assert_true(!str_contains($appSource, 'return window.location.origin;'), 'guest link script silently falls back to browser origin');
assert_true(str_contains($appSource, "Konfigurasikan Site URL di Pengaturan terlebih dahulu."), 'guest link missing-origin message is missing');
assert_true(str_contains($adminSource, 'name="action" value="save_settings"'), 'settings save action missing');
assert_true(str_contains($adminSource, '$themeMediaRoles ='), 'active media role resolver missing');
assert_true(str_contains($adminSource, 'if (!empty($themeMediaRoles))'), 'media role panel gate missing');
assert_true(str_contains($adminSource, "in_array('bride_photo', \$themeMediaRoles, true)"), 'bride photo Admin role gate missing');
assert_true(str_contains($adminSource, "in_array('groom_photo', \$themeMediaRoles, true)"), 'groom photo Admin role gate missing');
assert_true(str_contains($adminSource, "in_array('couple_photo', \$themeMediaRoles, true)"), 'couple photo Admin role gate missing');
$presetPanelMap = ['wedding' => 'wedding', 'parents' => 'parents', 'schedule' => 'schedule', 'sections' => 'sections', 'rsvp' => 'rsvp'];
foreach ($presetPanelMap as $panel => $capability) {
    assert_true(str_contains($adminSource, "if (\$adminCapabilityEnabled('$capability'))"), "admin preset panel gate missing for $panel");
}
$globalPanelMap = ['theme' => 'theme', 'guest-links' => 'guest_links'];
foreach ($globalPanelMap as $panel => $capability) {
    assert_true(str_contains($adminSource, "if (\$globalAdminCapabilityEnabled('$capability'))"), "admin global panel gate missing for $panel");
    assert_true(!str_contains($adminSource, "if (\$adminCapabilityEnabled('$capability'))"), "admin global panel remains preset-filtered for $panel");
}
$selectorPosition = strpos($adminSource, 'id="preset-selector"');
$themeGatePosition = strpos($adminSource, "if (\$globalAdminCapabilityEnabled('theme'))", $selectorPosition);
$guestLinksPosition = strpos($adminSource, 'id="guest-links"');
$guestLinksGatePosition = strpos($adminSource, "if (\$globalAdminCapabilityEnabled('guest_links'))", 0);
assert_true($selectorPosition !== false, 'global preset selector panel missing');
assert_true($themeGatePosition !== false && $selectorPosition < $themeGatePosition, 'preset selector is nested inside theme-specific gate');
assert_true($guestLinksPosition !== false && $guestLinksGatePosition !== false && $guestLinksGatePosition < $guestLinksPosition, 'global guest link panel gate missing before panel');
assert_true(str_contains($adminSource, "\$globalAdminCapabilityEnabled('preset_selector')"), 'preset selector does not use global capability gate');
assert_true(str_contains($adminSource, "\$globalAdminCapabilityEnabled('settings')"), 'settings does not use global capability gate');
assert_true(str_contains($adminSource, "\$globalAdminCapabilityEnabled('backup')"), 'backup does not use global capability gate');
assert_true(!str_contains($adminSource, "\$adminCapabilityEnabled('settings')"), 'settings remains incorrectly theme-filtered');
assert_true(!str_contains($adminSource, "\$adminCapabilityEnabled('backup')"), 'backup remains incorrectly theme-filtered');
assert_true(str_contains($adminSource, 'Site URL belum dikonfigurasi.'), 'missing-origin admin warning is missing');
assert_true(str_contains($adminSource, 'name="action" value="save_preset"'), 'global preset selector save action missing');
assert_true(!str_contains($adminSource, "\$adminCapabilityEnabled('preset_selector')"), 'preset selector incorrectly uses theme capability gate');
$yamiSource = (string)file_get_contents(dirname(__DIR__) . '/themes/yami-buzzy/layout.php');
assert_true(str_contains($yamiSource, '$bridePhotoUrl'), 'Yami Buzzy bride photo bridge missing');
assert_true(str_contains($yamiSource, '$groomPhotoUrl'), 'Yami Buzzy groom photo bridge missing');
assert_true(str_contains($yamiSource, '$couplePhotoPath'), 'Yami Buzzy couple photo fallback missing');
foreach (theme_builtin_preset_keys() as $presetKey) {
    $presetMeta = theme_registry()[$presetKey] ?? [];
    assert_true(($presetMeta['schema']['opening_greeting']['type'] ?? '') === 'textarea', "$presetKey opening greeting schema missing");
    assert_true(trim((string)(config_defaults()['theme_options'][$presetKey]['opening_greeting'] ?? '')) !== '', "$presetKey opening greeting default missing");
}
assert_true(str_contains($adminSource, 'name="theme_opts[') && str_contains($adminSource, "\$fieldType === 'textarea'"), 'admin Theme Options textarea renderer is missing');

// The selector and visual editor are global, while the full legacy manual editor remains Custom-only.
assert_true(str_contains($adminSource, "data-visual-schemas="), 'visual schema payload missing');
assert_true(str_contains($adminSource, 'data-visual-values='), 'visual values payload missing');

echo "PASS: global preset selector, preset-aware admin capabilities, guest system, switching, and persistence\n";
