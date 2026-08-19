<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function delete_fallback_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ' . $message . PHP_EOL;
}

$probePath = 'uploads/background/delete-fallback-probe-' . getmypid() . '.webp';
$probeAbsolute = ROOT_DIR . '/' . $probePath;
copy(ROOT_DIR . '/themes/parang/assets/parang-pattern.webp', $probeAbsolute);
$config = config_defaults();
$config['media']['cover'] = $probePath;
$config['media']['background_hero'] = $probePath;
$config['media']['background_sections'][0] = $probePath;
$config['gallery']['items'][] = ['filename' => $probePath, 'order' => 1];
$config['gallery']['cover'] = $probePath;
$config['theme_visuals']['yami-buzzy']['hero_background'] = $probePath;
$config['theme_options']['archak']['header_badge_image'] = $probePath;
$usage = detect_media_usage($config, $probePath);
delete_fallback_assert(count($usage) >= 6, 'Media usage detects all CMS references before forced delete');
clear_media_references($config, $probePath);
delete_fallback_assert(detect_media_usage($config, $probePath) === [], 'Forced delete clears media, gallery, visual, and theme option references');
delete_fallback_assert(($config['gallery']['cover'] ?? '') === '', 'Forced delete clears gallery cover reference');
delete_fallback_assert(delete_uploaded_asset($probePath), 'Canonical media file can be deleted after references are cleared');
delete_fallback_assert(!is_file($probeAbsolute), 'Deleted media file no longer exists on disk');

$base = config_defaults();
$shared = ['presetKey' => 'yami-buzzy', 'heroText' => 'Fallback', 'guestFallback' => 'Bapak/Ibu/Saudara/i', 'guestName' => '', 'countdownTarget' => '', 'calendarLink' => '#calendar', 'calendarDownloadName' => 'Undangan', 'whatsappLink' => '#whatsapp', 'musicSrc' => '', 'bgHero' => '', 'sectionStyles' => ['', '', ''], 'brideParents' => '', 'groomParents' => '', 'siteTitle' => 'Fallback', 'weddingTitle' => 'Fallback'];
$base['theme']['mode'] = 'preset';
$base['theme']['theme_preset'] = 'yami-buzzy';
$yamiHtml = render_theme_layout($base, $shared);
delete_fallback_assert(str_contains($yamiHtml, 'assets/pic/source-hero.webp'), 'Yami Buzzy empty custom background falls back to bundled source hero');
$base['theme']['theme_preset'] = 'shubh-vivah';
$shared['presetKey'] = 'shubh-vivah';
$shubhHtml = render_theme_layout($base, $shared);
delete_fallback_assert(str_contains($shubhHtml, 'source-wedding-card.png'), 'Shubh Vivah empty custom background falls back to bundled source card');

echo "PASS: media delete and source fallback smoke test\n";
