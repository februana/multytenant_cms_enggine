<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function assert_true(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$expected = [
    'custom' => ['preset_selector', 'guest_links', 'theme', 'sections', 'backup', 'settings'],
    'dewankl' => ['preset_selector', 'guest_links', 'wedding', 'parents', 'schedule', 'gallery', 'music', 'gift', 'maps', 'rsvp'],
    'elix' => ['preset_selector', 'guest_links', 'wedding', 'parents', 'schedule', 'gallery', 'music', 'gift', 'maps', 'rsvp'],
    'rainier' => ['preset_selector', 'guest_links', 'wedding', 'schedule', 'story', 'music', 'maps', 'rsvp'],
    'archak' => ['preset_selector', 'guest_links', 'wedding', 'parents', 'schedule', 'gallery', 'story', 'gift', 'maps', 'rsvp'],
];
$forbidden = [
    'rainier' => ['parents', 'gallery', 'gift', 'dresscode', 'theme', 'sections', 'backup', 'settings'],
    'archak' => ['music', 'dresscode', 'theme', 'sections', 'backup', 'settings'],
    'dewankl' => ['theme', 'sections', 'custom_css', 'dresscode', 'cover', 'background', 'backup', 'settings'],
];
$base = load_config();
foreach ($expected as $preset => $required) {
    $config = $base;
    $config['theme']['mode'] = $preset === 'custom' ? 'custom' : 'preset';
    $config['theme']['theme_preset'] = $preset;
    $caps = theme_admin_capabilities_for_config($config);
    foreach ($required as $capability) assert_true(in_array($capability, $caps, true), "$preset missing $capability");
    foreach ($forbidden[$preset] ?? [] as $capability) assert_true(!in_array($capability, $caps, true), "$preset exposes forbidden $capability");
}

assert_true(theme_contract_global_admin_capabilities() === ['preset_selector', 'guest_links'], 'global admin capability contract changed unexpectedly');

assert_true(build_guest_invitation_url('https://example.com', 'Andi & <script>') === 'https://example.com/?to=Andi%20%26%20%3Cscript%3E', 'guest URL encoding failed');
assert_true(build_guest_invitation_url('/', 'Andi') === '/?to=Andi', 'root guest URL failed');
assert_true(build_guest_invitation_url('javascript:alert(1)', 'Andi') === '', 'unsafe guest URL base accepted');
assert_true(normalize_guest_name("  Andi\n\t  ") === 'Andi', 'guest whitespace normalization failed');
assert_true(mb_strlen(normalize_guest_name(str_repeat('A', 200))) === 120, 'guest length limit failed');

$_GET['to'] = 'Budi%20%26%20Sari';
assert_true(resolve_guest_name($base) === 'Budi%20%26%20Sari', 'query resolver unexpectedly double-decodes or trusts malformed data');
$_GET['to'] = 'Budi & Sari';
assert_true(resolve_guest_name($base) === 'Budi & Sari', 'query resolver failed normal name');
unset($_GET['to']);

$preserved = $base;
$preserved['theme_sections']['rainier'][0]['enabled'] = false;
$preserved['theme_sections']['rainier'][0]['custom_title'] = 'Stored Rainier title';
$preserved['media']['music'] = 'music/keep.mp3';
$preserved['wedding']['bride_name'] = 'Stored Bride';
$before = $preserved;
foreach (['mode', 'theme_preset'] as $key) unset($before['theme'][$key]);
$before = json_encode($before, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$sequence = ['custom', 'dewankl', 'elix', 'rainier', 'archak', 'custom'];
foreach ($sequence as $preset) {
    $switched = switch_active_theme_preset_config($preserved, $preset);
    assert_true(is_array($switched), "switch failed for $preset");
    assert_true(($switched['theme']['theme_preset'] ?? '') === $preset, "selector state mismatch for $preset");
    assert_true(($switched['theme']['mode'] ?? '') === ($preset === 'custom' ? 'custom' : 'preset'), "mode mismatch for $preset");
    $after = $switched;
    foreach (['mode', 'theme_preset'] as $key) unset($after['theme'][$key]);
    assert_true(json_encode($after, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) === $before, "switch mutated stored data for $preset");
    $preserved = $switched;
}

$adminSource = file_get_contents(dirname(__DIR__) . '/admin/index.php');
$panelMap = ['wedding' => 'wedding', 'parents' => 'parents', 'schedule' => 'schedule', 'sections' => 'sections', 'theme' => 'theme', 'guest-links' => 'guest_links', 'rsvp' => 'rsvp'];
foreach ($panelMap as $panel => $capability) {
    assert_true(str_contains($adminSource, "if (\$adminCapabilityEnabled('$capability'))"), "admin panel gate missing for $panel");
}
$selectorPosition = strpos($adminSource, 'id="preset-selector"');
$themeGatePosition = strpos($adminSource, "if (\$adminCapabilityEnabled('theme'))", $selectorPosition);
assert_true($selectorPosition !== false, 'global preset selector panel missing');
assert_true($themeGatePosition !== false && $selectorPosition < $themeGatePosition, 'preset selector is nested inside theme-specific gate');
assert_true(str_contains($adminSource, "\$globalAdminCapabilityEnabled('preset_selector')"), 'preset selector does not use global capability gate');
assert_true(str_contains($adminSource, 'name="action" value="save_preset"'), 'global preset selector save action missing');
assert_true(!str_contains($adminSource, "\$adminCapabilityEnabled('preset_selector')"), 'preset selector incorrectly uses theme capability gate');

// The selector is global, while the manual theme editor remains Custom-only for built-ins.
assert_true(str_contains($adminSource, "if (\$themeMode === 'custom')"), 'Custom-only theme editor gate missing');

echo "PASS: global preset selector, preset-aware admin capabilities, guest system, switching, and persistence\n";
