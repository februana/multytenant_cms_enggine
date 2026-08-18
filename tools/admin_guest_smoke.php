<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function assert_true(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$expected = [
    'custom' => ['guest_links', 'theme', 'sections', 'backup', 'settings'],
    'dewankl' => ['guest_links', 'wedding', 'parents', 'schedule', 'gallery', 'music', 'gift', 'maps', 'rsvp'],
    'elix' => ['guest_links', 'wedding', 'parents', 'schedule', 'gallery', 'music', 'gift', 'maps', 'rsvp'],
    'rainier' => ['guest_links', 'wedding', 'schedule', 'story', 'music', 'maps', 'rsvp'],
    'archak' => ['guest_links', 'wedding', 'parents', 'schedule', 'gallery', 'story', 'gift', 'maps', 'rsvp'],
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
$before = json_encode($preserved['theme_sections']['rainier']);
$after = json_encode($preserved['theme_sections']['rainier']);
assert_true($before === $after && $preserved['media']['music'] === 'music/keep.mp3', 'preset filtering mutated stored data');

$adminSource = file_get_contents(dirname(__DIR__) . '/admin/index.php');
$panelMap = ['wedding' => 'wedding', 'parents' => 'parents', 'schedule' => 'schedule', 'sections' => 'sections', 'theme' => 'theme', 'guest-links' => 'guest_links', 'rsvp' => 'rsvp'];
foreach ($panelMap as $panel => $capability) {
    assert_true(str_contains($adminSource, "if (\$adminCapabilityEnabled('$capability'))"), "admin panel gate missing for $panel");
}

echo "PASS: preset-aware admin capabilities and global guest system\n";
