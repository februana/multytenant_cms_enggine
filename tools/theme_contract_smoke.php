<?php
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

$config = load_config();
$expected = ['dewankl', 'elix', 'rainier', 'archak'];
foreach ($expected as $preset) {
    $sections = theme_contract_sections_for_config($config, $preset);
    if (!$sections) throw new RuntimeException("No sections for {$preset}");
    if (!theme_section_enabled($config, $preset, (string)$sections[0]['id'])) {
        throw new RuntimeException("First section disabled unexpectedly for {$preset}");
    }
    if (theme_preset_layout_order($preset, $config) !== []) {
        throw new RuntimeException("Built-in {$preset} leaked a CMS order");
    }
}

$config['theme']['mode'] = 'custom';
$config['theme']['theme_preset'] = 'custom';
$config['sections'] = [
    ['id' => 'gallery', 'enabled' => true, 'order' => 2],
    ['id' => 'hero', 'enabled' => true, 'order' => 1],
];
$order = theme_preset_layout_order('custom', $config);
if ($order !== ['hero', 'galeri']) throw new RuntimeException('Custom order did not come from CMS sections');

$config['theme']['mode'] = 'preset';
$config['theme']['theme_preset'] = 'elix';
$config['theme_sections']['elix'][0]['enabled'] = false;
if (theme_section_enabled($config, 'elix', (string)$config['theme_sections']['elix'][0]['id'])) {
    throw new RuntimeException('Theme-specific visibility was not respected');
}

$config['theme_sections']['elix'][0]['enabled'] = true;
$config['sections'] = array_map(static function (array $section): array {
    $section['enabled'] = false;
    return $section;
}, $config['sections']);
if (!theme_section_enabled($config, 'elix', (string)$config['theme_sections']['elix'][0]['id'])) {
    throw new RuntimeException('Built-in theme leaked Custom section visibility');
}

echo "PASS: theme contract smoke test\n";
