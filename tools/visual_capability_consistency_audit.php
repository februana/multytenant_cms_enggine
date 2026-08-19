<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/theme-helper.php';

$failures = 0;
$assert = static function (bool $condition, string $message) use (&$failures): void {
    echo ($condition ? 'PASS: ' : 'FAIL: ') . $message . PHP_EOL;
    if (!$condition) $failures++;
};

$root = dirname(__DIR__);
$registry = theme_registry();
$presets = theme_builtin_preset_keys();
$helperSource = (string)file_get_contents($root . '/app/theme-helper.php');
$indexSource = (string)file_get_contents($root . '/index.php');
$visualMapVars = [
    'dewankl' => ['accent_color' => '--cms-dewana-accent', 'heading_color' => '--cms-dewana-heading-color', 'text_color' => '--cms-dewana-text', 'muted_color' => '--cms-dewana-muted', 'link_color' => '--cms-dewana-link'],
    'rainier' => ['accent_color' => '--primary', 'heading_color' => '--rainier-heading-color', 'text_color' => '--text', 'muted_color' => '--muted', 'link_color' => '--rainier-link'],
    'archak' => ['accent_color' => '--cms-archak-accent', 'heading_color' => '--cms-archak-heading-color', 'text_color' => '--cms-archak-text', 'muted_color' => '--cms-archak-muted', 'link_color' => '--cms-archak-link'],
    'parang' => ['accent_color' => '--parang-gold', 'heading_color' => '--parang-heading-color', 'text_color' => '--parang-text', 'muted_color' => '--parang-muted', 'link_color' => '--parang-link'],
    'pawiwahan' => ['accent_color' => '--pawiwahan-accent', 'heading_color' => '--pawiwahan-heading-color', 'text_color' => '--pawiwahan-text', 'muted_color' => '--pawiwahan-muted', 'link_color' => '--pawiwahan-link'],
    'shubh-vivah' => ['accent_color' => '--shubh-accent', 'heading_color' => '--shubh-heading-color', 'text_color' => '--shubh-ink', 'muted_color' => '--shubh-muted', 'link_color' => '--shubh-link'],
    'yami-buzzy' => ['accent_color' => '--yami-accent', 'heading_color' => '--yami-heading-color', 'text_color' => '--yami-ink', 'muted_color' => '--yami-muted', 'link_color' => '--yami-link'],
];

foreach ($presets as $presetKey) {
    $preset = $registry[$presetKey] ?? null;
    $assert(is_array($preset), "$presetKey exists in registry");
    if (!is_array($preset)) continue;
    $schema = (array)($preset['visual_capabilities'] ?? []);
    $layoutPath = $root . '/themes/' . $presetKey . '/layout.php';
    $layout = is_file($layoutPath) ? (string)file_get_contents($layoutPath) : '';
    $css = '';
    foreach (glob($root . '/themes/' . $presetKey . '/*.css') ?: [] as $cssPath) $css .= (string)file_get_contents($cssPath);
    $renderSource = $layout . $css;
    foreach (['accent_color', 'heading_color', 'text_color', 'muted_color', 'link_color', 'heading_font', 'body_font'] as $key) {
        $assert(isset($schema[$key]), "$presetKey declares visual capability $key");
    }
    foreach (['heading_font', 'body_font'] as $key) {
        $options = (array)($schema[$key]['options'] ?? []);
        $assert(count($options) >= 10, "$presetKey has at least 10 options for $key");
        $assert(array_key_exists((string)($schema[$key]['default'] ?? ''), $options), "$presetKey default $key is selectable");
    }
    foreach (['accent_color', 'heading_color', 'text_color', 'muted_color', 'link_color'] as $key) {
        $palette = (array)($schema[$key]['palette'] ?? []);
        $assert(count($palette) >= 10, "$presetKey has a named palette for $key");
        $default = strtolower((string)($schema[$key]['default'] ?? ''));
        $assert($default !== '' && array_key_exists($default, $palette), "$presetKey default $key exists in palette");
    }
    $assert(str_contains($layout, 'theme_google_font_stylesheet_url()'), "$presetKey loads the shared font library");
    foreach ($schema as $key => $definition) {
        if (($definition['type'] ?? '') === 'image') {
            $assert(str_contains($layout, $key), "$presetKey image capability $key reaches layout");
            $assert(str_contains($helperSource, $key . ':'), "preview map mentions image capability $presetKey.$key");
        }
        if (str_starts_with((string)$key, 'section_background_')) {
            $assert(str_contains($layout, $key), "$presetKey section background $key reaches layout");
            $assert(str_contains($helperSource, $key . ':'), "preview map mentions section background $presetKey.$key");
        }
    }
    foreach (($visualMapVars[$presetKey] ?? []) as $key => $variable) {
        $assert(str_contains($helperSource, $key . ': ' . "'" . $variable . "'") || str_contains($helperSource, $key . ": '" . $variable . "'"), "preview map contains $presetKey.$key");
        $assert(str_contains($renderSource, $variable), "$presetKey renderer consumes $variable");
    }
}

$customSchema = theme_visual_capabilities_for_config(config_defaults(), 'custom');
foreach (['heading_color', 'text_color', 'muted_color', 'link_color', 'heading_font', 'body_font'] as $key) {
    $assert(isset($customSchema[$key]), "custom declares visual capability $key");
}
$assert(count((array)($customSchema['heading_font']['options'] ?? [])) >= 10, 'custom has extended heading font catalog');
$assert(count((array)($customSchema['body_font']['options'] ?? [])) >= 10, 'custom has extended body font catalog');
$assert(str_contains($indexSource, "theme_google_font_stylesheet_url()"), 'custom renderer uses shared font stylesheet helper');

if ($failures > 0) {
    echo "FAIL: visual capability consistency audit ($failures failures)" . PHP_EOL;
    exit(1);
}
echo 'PASS: visual capability consistency audit' . PHP_EOL;
