<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/theme-helper.php';
require_once __DIR__ . '/app/theme-renderer.php';

$config = load_config();

$siteTitle = $config['site']['title'] ?? 'Undangan Pernikahan';
$weddingTitle = $config['wedding']['title'] ?? $siteTitle;
$heroText = $config['wedding']['opening_text'] ?? '';
$guestFallback = 'Bapak/Ibu/Saudara/i';
$guestName = resolve_guest_name($config);

$akadDate = $config['schedule']['akad_date'] ?? '';
$akadTime = $config['schedule']['akad_time'] ?? '';

$calendarLink = build_google_calendar_link($config);
$calendarDownloadName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $siteTitle) ?: 'Undangan';
$whatsappLink = build_whatsapp_link($config);
$musicSrc = $config['media']['music'] ?? 'music/lagu.mp3';

$coverPath = $config['media']['cover'] ?? 'uploads/cover/cover.jpg';
$heroBackground = $config['media']['background_hero'] ?? '';
$heroBackground = $heroBackground !== '' ? $heroBackground : $coverPath;

$theme = $config['theme'] ?? [];
$themeHeroHeight = trim((string)($theme['hero_height'] ?? '')) ?: 'calc(100vh - 80px)';
$themeHeroVAlign = trim((string)($theme['hero_vertical_alignment'] ?? '')) ?: 'center';
$themeHeroContentWidth = trim((string)($theme['hero_content_width'] ?? '')) ?: '900px';
$heroImageFit = trim((string)($theme['hero_image_fit'] ?? '')) ?: 'cover';
$heroImagePosition = trim((string)($theme['hero_image_position'] ?? '')) ?: 'center';
$heroBgSize = $heroImageFit === 'contain' ? 'contain' : ($heroImageFit === 'auto' ? 'auto' : 'cover');
$heroBgRepeat = 'no-repeat';
$heroOverlayStart = trim((string)($theme['hero_overlay_start'] ?? '')) ?: 'rgba(22,12,10,.45)';
$heroOverlayMid = trim((string)($theme['hero_overlay_mid'] ?? '')) ?: 'rgba(40,20,18,.55)';
$heroOverlayEnd = trim((string)($theme['hero_overlay_end'] ?? '')) ?: 'rgba(55,28,24,.72)';
$themeMobileHeroHeight = trim((string)($theme['mobile_hero_height'] ?? '')) ?: '82vh';
$themeMobileHeroVAlign = trim((string)($theme['mobile_hero_vertical_alignment'] ?? '')) ?: 'center';
$themeMobileHeroContentWidth = trim((string)($theme['mobile_hero_content_width'] ?? '')) ?: '100%';
$themeMobileHeroImageFit = trim((string)($theme['mobile_hero_image_fit'] ?? '')) ?: 'cover';
$themeMobileHeroImagePosition = trim((string)($theme['mobile_hero_image_position'] ?? '')) ?: 'center top';
$buttonsMobileLayoutRaw = trim((string)($config['buttons']['mobile_layout'] ?? '')) ?: 'column';
$buttonsMobileLayout = match ($buttonsMobileLayoutRaw) {
    'horizontal', '2-columns' => 'row',
    '1-column' => 'column',
    default => 'column',
};

$bgHero = 'style="--hero-bg:url(\'' . escape_html(public_path($heroBackground)) . '\');--hero-height:' . escape_html($themeHeroHeight) . ';--hero-v-align:' . escape_html($themeHeroVAlign) . ';--hero-content-width:' . escape_html($themeHeroContentWidth) . ';--hero-image-fit:' . escape_html($heroBgSize) . ';--hero-image-position:' . escape_html($heroImagePosition) . ';--hero-bg-repeat:' . escape_html($heroBgRepeat) . ';--hero-overlay-start:' . escape_html($heroOverlayStart) . ';--hero-overlay-mid:' . escape_html($heroOverlayMid) . ';--hero-overlay-end:' . escape_html($heroOverlayEnd) . ';--mobile-hero-height:' . escape_html($themeMobileHeroHeight) . ';--mobile-hero-v-align:' . escape_html($themeMobileHeroVAlign) . ';--mobile-hero-content-width:' . escape_html($themeMobileHeroContentWidth) . ';--mobile-hero-image-fit:' . escape_html($themeMobileHeroImageFit) . ';--mobile-hero-image-position:' . escape_html($themeMobileHeroImagePosition) . ';--buttons-mobile-layout:' . escape_html($buttonsMobileLayout) . ';"';

$sectionBackgrounds = $config['media']['background_sections'] ?? [];
$sectionBackgroundSize = $heroBgSize;
$sectionBackgroundPosition = $heroImagePosition;
$sectionBackgroundRepeat = $heroBgRepeat;
$sectionStyles = [
    !empty($sectionBackgrounds[0]) ? 'style="background-image:url(\'' . escape_html(public_path($sectionBackgrounds[0])) . '\');background-size:' . escape_html($sectionBackgroundSize) . ';background-position:' . escape_html($sectionBackgroundPosition) . ';background-repeat:' . escape_html($sectionBackgroundRepeat) . ';"' : '',
    !empty($sectionBackgrounds[1]) ? 'style="background-image:url(\'' . escape_html(public_path($sectionBackgrounds[1])) . '\');background-size:' . escape_html($sectionBackgroundSize) . ';background-position:' . escape_html($sectionBackgroundPosition) . ';background-repeat:' . escape_html($sectionBackgroundRepeat) . ';"' : '',
    !empty($sectionBackgrounds[2]) ? 'style="background-image:url(\'' . escape_html(public_path($sectionBackgrounds[2])) . '\');background-size:' . escape_html($sectionBackgroundSize) . ';background-position:' . escape_html($sectionBackgroundPosition) . ';background-repeat:' . escape_html($sectionBackgroundRepeat) . ';"' : '',
];

$countdownTarget = $config['schedule']['countdown_target'] ?? ($akadDate && $akadTime ? $akadDate . 'T' . $akadTime . '+07:00' : '');
$brideParents = trim(escape_html(($config['parents']['bride_father'] ?? '') . ' & ' . ($config['parents']['bride_mother'] ?? '')));
$groomParents = trim(escape_html(($config['parents']['groom_father'] ?? '') . ' & ' . ($config['parents']['groom_mother'] ?? '')));

$activeThemePreset = resolve_theme_preset_key($config);
$themePageShared = [
    'presetKey' => $activeThemePreset,
    'heroText' => $heroText,
    'guestFallback' => $guestFallback,
    'guestName' => $guestName,
    'countdownTarget' => $countdownTarget,
    'calendarLink' => $calendarLink,
    'calendarDownloadName' => $calendarDownloadName,
    'whatsappLink' => $whatsappLink,
    'musicSrc' => $musicSrc,
    'bgHero' => $bgHero,
    'sectionStyles' => $sectionStyles,
    'brideParents' => $brideParents,
    'groomParents' => $groomParents,
    'siteTitle' => $siteTitle,
    'weddingTitle' => $weddingTitle,
];

$renderedTheme = render_theme_layout($config, $themePageShared);
if (resolve_theme_mode($config) === 'custom') {
    $customCss = load_custom_css();
    $customCssBlock = $customCss !== '' ? "\n<style>" . $customCss . "</style>" : '';
    $customDocument = '<!DOCTYPE html>\n<html lang="id">\n<head>\n<meta charset="utf-8">\n<meta name="viewport" content="width=device-width, initial-scale=1">\n<title>' . escape_html($siteTitle) . '</title>\n<link rel="preconnect" href="https://fonts.googleapis.com">\n<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>\n<link href="https://fonts.googleapis.com/css2?family=Allura&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">\n<link rel="stylesheet" href="/style.css">' . $customCssBlock . '\n</head>\n<body>\n' . $renderedTheme . '\n<script src="/script.js" defer></script>\n</body>\n</html>';
    $renderedTheme = str_replace('\\n', "\n", $customDocument);
}
echo finalize_theme_output($renderedTheme, $config);
