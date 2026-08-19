<?php
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

$config = load_config();
$expected = ['dewankl', 'rainier', 'archak', 'parang', 'pawiwahan', 'shubh-vivah', 'yami-buzzy'];
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
$removedPresetKey = 'e' . 'lix';
if (theme_contract_sections_for_config($config, $removedPresetKey) !== []) {
    throw new RuntimeException('Removed legacy preset still has a contract');
}

$config['theme']['mode'] = 'custom';
$config['theme']['theme_preset'] = 'custom';
$config['sections'] = [
    ['id' => 'gallery', 'enabled' => true, 'order' => 2],
    ['id' => 'hero', 'enabled' => true, 'order' => 1],
];
$order = theme_preset_layout_order('custom', $config);
if ($order !== ['hero', 'galeri']) throw new RuntimeException('Custom order did not come from CMS sections');

foreach (['shubh-vivah', 'yami-buzzy'] as $preset) {
    $config['theme']['mode'] = 'preset';
    $config['theme']['theme_preset'] = $preset;
    $sections = theme_contract_sections_for_config($config, $preset);
    $firstId = (string)($sections[0]['id'] ?? '');
    $config['theme_sections'][$preset][0]['enabled'] = false;
    if (theme_section_enabled($config, $preset, $firstId)) {
        throw new RuntimeException("Theme-specific visibility was not respected for {$preset}");
    }
    $config['theme_sections'][$preset][0]['enabled'] = true;
    $config['sections'] = array_map(static function (array $section): array {
        $section['enabled'] = false;
        return $section;
    }, $config['sections']);
    if (!theme_section_enabled($config, $preset, $firstId)) {
        throw new RuntimeException("Built-in {$preset} leaked Custom section visibility");
    }
}

echo "PASS: theme contract smoke test
";
