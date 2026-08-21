<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function parang_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

function parang_contains(string $html, string $needle, string $message): void {
    parang_assert(strpos($html, $needle) !== false, $message . ": {$needle}");
}

$config = load_config();
$config['theme']['mode'] = 'preset';
$config['theme']['theme_preset'] = 'parang';
$config['theme_visuals']['parang'] = [];
$baseline = json_encode($config, JSON_THROW_ON_ERROR);

$defaults = theme_visual_values_for_config($config, 'parang');
parang_assert(($defaults['ornament_top'] ?? '') === 'themes/parang/assets/gunungan-hero.png', 'Parang top ornament default must use the supplied Gunungan asset');
parang_assert((string)($defaults['ornament_top_width'] ?? '') === '192', 'Parang top ornament default width must preserve source composition');
parang_assert((string)($defaults['ornament_top_offset_y'] ?? '') === '-128', 'Parang top ornament default Y offset must preserve source placement');
parang_assert((string)($defaults['ornament_side_offset_x'] ?? '') === '-360', 'Parang side ornament default X offset must place figures outside the Hero card');
parang_assert((string)($defaults['ornament_side_offset_y'] ?? '') === '50', 'Parang side ornament default Y position must preserve source placement');
parang_assert((string)($defaults['ornament_side_height_ratio'] ?? '') === '70', 'Parang side ornament default height must be 70% of Hero card');
parang_assert((string)($defaults['ornament_side_size'] ?? '') === '480', 'Parang side ornament default width cap must fit supplied assets');

$targets = media_manager_visible_target_definitions($config, 'parang');
parang_assert(isset($targets['theme_visuals.parang.ornament_top']), 'Parang top ornament must be visible in Media Manager targets');
parang_assert(isset($targets['theme_visuals.parang.ornament_left']), 'Parang left ornament must remain visible in Media Manager targets');
parang_assert(isset($targets['theme_visuals.parang.ornament_right']), 'Parang right ornament must remain visible in Media Manager targets');

$shared = [
    'presetKey' => 'parang',
    'heroText' => $config['wedding']['opening_text'] ?? '',
    'guestFallback' => 'Bapak/Ibu/Saudara/i',
    'countdownTarget' => $config['schedule']['countdown_target'] ?? '',
    'calendarLink' => build_google_calendar_link($config),
    'calendarDownloadName' => 'Undangan',
    'whatsappLink' => build_whatsapp_link($config),
    'musicSrc' => $config['media']['music'] ?? '',
    'bgHero' => '',
    'sectionStyles' => [],
    'brideParents' => '',
    'groomParents' => '',
];

$defaultHtml = render_theme_layout($config, $shared);
parang_contains($defaultHtml, 'src="/themes/parang/assets/gunungan-hero.png" alt="Ornamen Gunungan"', 'Default supplied Hero Gunungan missing');
$parangCss = file_get_contents(dirname(__DIR__) . '/themes/parang/style.css');
parang_assert(is_string($parangCss) && strpos($parangCss, '--parang-ornament-filter') !== false, 'Parang ornament color filter variable missing');
parang_assert(strpos($parangCss, 'filter: var(--parang-ornament-filter)') !== false, 'Parang ornament CSS filter binding missing');
parang_contains($defaultHtml, '--cms-parang-top-width:192px', 'Default top width missing');
parang_contains($defaultHtml, '--cms-parang-top-offset-y:-128px', 'Default top coordinate missing');
parang_contains($defaultHtml, '--cms-parang-side-offset-x:-360px', 'Default side X coordinate missing');
parang_contains($defaultHtml, '--cms-parang-side-offset-y:50%', 'Default side Y coordinate missing');
parang_contains($defaultHtml, '--cms-parang-side-height-ratio:70%', 'Default side height ratio missing');
parang_contains($defaultHtml, '--cms-parang-side-height-factor:0.7000', 'Default side height factor missing');
parang_contains($defaultHtml, '--cms-parang-side-size:480px', 'Default side width cap missing');
parang_contains($defaultHtml, 'ResizeObserver', 'Hero-card height synchronization missing');

$config['theme_visuals']['parang'] = [
    'ornament_top' => 'themes/parang/assets/wayang-pria.png',
    'ornament_left' => 'themes/parang/assets/wayang-wanita.png',
    'ornament_right' => 'themes/parang/assets/gunungan-hero.png',
    'ornament_top_width' => '240',
    'ornament_top_offset_y' => '-72',
    'ornament_side_offset_x' => '-420',
    'ornament_side_offset_y' => '42',
    'ornament_side_height_ratio' => '72',
    'ornament_side_size' => '260',
];
$customBaseline = json_encode($config, JSON_THROW_ON_ERROR);
$customHtml = render_theme_layout($config, $shared);
parang_contains($customHtml, 'src="/themes/parang/assets/wayang-pria.png" alt="Ornamen Gunungan"', 'Custom top asset missing');
parang_contains($customHtml, 'src="/themes/parang/assets/wayang-wanita.png" alt="Ornamen wayang"', 'Custom left Wayang asset missing');
parang_contains($customHtml, 'src="/themes/parang/assets/gunungan-hero.png" alt=""', 'Custom right asset missing');
parang_contains($customHtml, '--cms-parang-top-width:240px', 'Custom top width missing');
parang_contains($customHtml, '--cms-parang-top-offset-y:-72px', 'Custom top coordinate missing');
parang_contains($customHtml, '--cms-parang-side-offset-x:-420px', 'Custom side X coordinate missing');
parang_contains($customHtml, '--cms-parang-side-offset-y:42%', 'Custom side Y coordinate missing');
parang_contains($customHtml, '--cms-parang-side-height-ratio:72%', 'Custom side height ratio missing');
parang_contains($customHtml, '--cms-parang-side-height-factor:0.7200', 'Custom side height factor missing');
parang_contains($customHtml, '--cms-parang-side-size:260px', 'Custom side width missing');

$resetConfig = $config;
reset_theme_visual_overrides($resetConfig, 'parang');
$resetValues = theme_visual_values_for_config($resetConfig, 'parang');
parang_assert(($resetValues['ornament_top'] ?? '') === 'themes/parang/assets/gunungan-hero.png', 'Reset must restore supplied Gunungan fallback');
parang_assert(($resetValues['ornament_left'] ?? '') === 'themes/parang/assets/wayang-pria.png', 'Reset must restore supplied male Wayang fallback');
parang_assert(($resetValues['ornament_right'] ?? '') === 'themes/parang/assets/wayang-wanita.png', 'Reset must restore supplied female Wayang fallback');
parang_assert((string)($resetValues['ornament_top_width'] ?? '') === '192', 'Reset must restore top width default');
parang_assert((string)($resetValues['ornament_top_offset_y'] ?? '') === '-128', 'Reset must restore top coordinate default');
parang_assert((string)($resetValues['ornament_side_height_ratio'] ?? '') === '70', 'Reset must restore 70% side height default');
parang_assert((string)($resetValues['ornament_side_offset_x'] ?? '') === '-360', 'Reset must restore side outside offset default');
parang_assert((string)($resetValues['ornament_side_size'] ?? '') === '480', 'Reset must restore side width cap default');

parang_assert(json_encode($config, JSON_THROW_ON_ERROR) === $customBaseline, 'Parang render smoke must not mutate tenant config');
echo "PASS: Parang source defaults, admin targets, custom asset coordinates, reset fallback, and immutability\n";
