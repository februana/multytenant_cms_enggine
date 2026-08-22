<?php
if (!defined('THEME_HELPER_LOADED')) {
    require_once __DIR__ . '/../../app/theme-helper.php';
}

$config = $config ?? [];
$presetKey = 'parang';
$visuals = function_exists('theme_visual_values_for_config') ? theme_visual_values_for_config($config, $presetKey) : [];
$sourcePattern = get_theme_asset_url($presetKey, 'assets/parang-pattern.webp');
$sourceGunungan = get_theme_asset_url($presetKey, 'assets/gunungan.webp');
$sourceGununganHero = get_theme_asset_url($presetKey, 'assets/gunungan-hero.png');
$sourceWayang = get_theme_asset_url($presetKey, 'assets/wayang.webp');
$sourceWayangLeft = get_theme_asset_url($presetKey, 'assets/wayang-pria.png');
$sourceWayangRight = get_theme_asset_url($presetKey, 'assets/wayang-wanita.png');
$staticPortraitFallback = $sourceGunungan;

$visualBackground = trim((string)($visuals['hero_background'] ?? '')) ?: $sourcePattern;
$backgroundCss = theme_visual_css_url($visualBackground);
$assetUrl = static function (string $configured, string $fallback): string {
    $configured = trim($configured);
    if ($configured === '') return $fallback;
    return function_exists('theme_visual_public_path') ? theme_visual_public_path($configured) : public_path($configured);
};
$sectionCss = static function (string $key) use ($visuals): string {
    $path = trim((string)($visuals[$key] ?? ''));
    return $path === '' ? 'none' : theme_visual_css_url(theme_visual_public_path($path));
};
$parangHomeBg = $sectionCss('section_background_home');
$parangGalleryBg = $sectionCss('section_background_gallery');
$parangLocationBg = $sectionCss('section_background_location');
$parangLeft = $assetUrl((string)($visuals['ornament_left'] ?? ''), $sourceWayangLeft);
$parangRight = $assetUrl((string)($visuals['ornament_right'] ?? ''), $sourceWayangRight);
$parangTop = $assetUrl((string)($visuals['ornament_top'] ?? ''), $sourceGununganHero);
$parangTopWidth = escape_html((string)($visuals['ornament_top_width'] ?? '192'));
$parangTopOffsetY = escape_html((string)($visuals['ornament_top_offset_y'] ?? '-128'));
$parangSideOffsetX = escape_html((string)($visuals['ornament_side_offset_x'] ?? '-360'));
$parangSideOffsetY = escape_html((string)($visuals['ornament_side_offset_y'] ?? '50'));
$parangSideHeightRatio = (float)($visuals['ornament_side_height_ratio'] ?? '70');
$parangSideHeightFactor = escape_html(number_format($parangSideHeightRatio / 100, 4, '.', ''));
$parangSideSize = escape_html((string)($visuals['ornament_side_size'] ?? '480'));
$accent = escape_html((string)($visuals['accent_color'] ?? '#C49A45'));
$headingColor = escape_html((string)($visuals['heading_color'] ?? '#211b0e'));
$textColor = escape_html((string)($visuals['text_color'] ?? '#211b0e'));
$mutedColor = escape_html((string)($visuals['muted_color'] ?? '#4f453e'));
$linkColor = escape_html((string)($visuals['link_color'] ?? '#7b5902'));
$headingFont = escape_html((string)($visuals['heading_font'] ?? 'Libre Caslon Text, serif'));
$bodyFont = escape_html((string)($visuals['body_font'] ?? 'Manrope, sans-serif'));

$groomPhoto = $assetUrl((string)($config['media']['groom_photo'] ?? ''), $staticPortraitFallback);
$bridePhoto = $assetUrl((string)($config['media']['bride_photo'] ?? ''), $staticPortraitFallback);
$qrisPath = trim((string)($config['gift']['qris_image'] ?? ''));
$qrisUrl = $qrisPath !== '' ? $assetUrl($qrisPath, '') : '';
$siteTitle = escape_html((string)($config['site']['title'] ?? 'Undangan Pernikahan'));
$description = escape_html((string)($config['site']['description'] ?? $config['wedding']['opening_text'] ?? ''));
$semanticNames = theme_semantic_names($config);
$brideNameRaw = $semanticNames['bride_full_name'];
$groomNameRaw = $semanticNames['groom_full_name'];
$brideName = escape_html($brideNameRaw);
$groomName = escape_html($groomNameRaw);
$brideNickname = escape_html($semanticNames['bride_nickname']);
$groomNickname = escape_html($semanticNames['groom_nickname']);
$openingText = render_preserved_text((string)($config['wedding']['opening_text'] ?? ''));
$openingGreeting = render_preserved_text(theme_opening_greeting($config, 'parang'));
$quoteText = render_preserved_text((string)($config['wedding']['quote'] ?? ''));
$closingText = render_preserved_text((string)($config['wedding']['closing_text'] ?? ''));
$guestName = function_exists('resolve_guest_name') ? resolve_guest_name($config) : '';
$guestLabel = escape_html($guestName !== '' ? $guestName : 'Bapak/Ibu/Saudara/i');
$akadDate = (string)($config['schedule']['akad_date'] ?? '');
$akadTime = (string)($config['schedule']['akad_time'] ?? '');
$receptionDate = (string)($config['schedule']['reception_date'] ?? '');
$receptionTime = (string)($config['schedule']['reception_time'] ?? '');
$venue = escape_html((string)($config['location']['venue'] ?? ''));
$address = render_preserved_text((string)($config['location']['address'] ?? ''));
$mapsUrl = escape_html((string)($config['location']['maps_url'] ?? ''));
$mapsEmbed = escape_html((string)($config['location']['maps_embed'] ?? ''));
$calendarLink = escape_html(build_google_calendar_link($config));
$csrf = function_exists('get_csrf_token') ? get_csrf_token() : '';
$musicPath = trim((string)($config['media']['music'] ?? ''));
$musicUrl = $musicPath !== '' ? escape_html(public_path($musicPath)) : '';

$monthNames = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$formatDate = static function (string $date) use ($monthNames): string {
    $timestamp = $date !== '' ? strtotime($date) : false;
    if ($timestamp === false) return '';
    return date('j', $timestamp) . ' ' . ($monthNames[(int)date('n', $timestamp)] ?? date('F', $timestamp)) . ' ' . date('Y', $timestamp);
};
$akadDateDisplay = escape_html($formatDate($akadDate));
$receptionDateDisplay = escape_html($formatDate($receptionDate));
$heroDateDisplay = escape_html($formatDate($akadDate ?: $receptionDate));

$stories = $config['love_story']['items'] ?? [];
if (!is_array($stories)) $stories = [];
$galleryItems = $config['gallery']['items'] ?? [];
if (!is_array($galleryItems)) $galleryItems = [];
$storyMarkup = '';
foreach ($stories as $story) {
    if (!is_array($story)) continue;
    $storyTitle = trim((string)($story['title'] ?? $story['date'] ?? ''));
    $storyDescription = trim((string)($story['description'] ?? $story['text'] ?? ''));
    if ($storyTitle === '' && $storyDescription === '') continue;
    $storyMarkup .= '<article class="parang-story-item parang-reveal"><h3>' . escape_html($storyTitle !== '' ? $storyTitle : 'Kisah Kami') . '</h3><p>' . render_preserved_text($storyDescription) . '</p></article>';
}
if ($storyMarkup === '' && trim((string)($config['wedding']['quote'] ?? '')) !== '') {
    $storyMarkup = '<article class="parang-story-item parang-reveal"><h3>Perjalanan Kami</h3><p>' . $quoteText . '</p></article>';
}
$galleryMarkup = '';
foreach ($galleryItems as $index => $item) {
    $path = is_array($item) ? (string)($item['path'] ?? $item['src'] ?? $item['url'] ?? '') : (string)$item;
    if (trim($path) === '') continue;
    $galleryUrl = escape_html($assetUrl($path, ''));
    $galleryMarkup .= '<a class="parang-gallery-item parang-reveal" href="' . $galleryUrl . '" target="_blank" rel="noopener"><img src="' . $galleryUrl . '" alt="Foto galeri ' . (int)($index + 1) . '" loading="lazy" decoding="async"></a>';
}
if ($galleryMarkup === '') $galleryMarkup = '<p class="parang-empty">Belum ada foto galeri yang ditambahkan.</p>';

$sectionEnabled = static function (string $id) use ($config, $presetKey): bool {
    return theme_section_enabled($config, $presetKey, $id);
};
$navItems = [
    ['id' => 'hero', 'href' => '#beranda', 'icon' => 'home', 'label' => 'Beranda'],
    ['id' => 'couple', 'href' => '#mempelai', 'icon' => 'favorite', 'label' => 'Mempelai'],
    ['id' => 'event', 'href' => '#acara', 'icon' => 'event', 'label' => 'Acara'],
    ['id' => 'story', 'href' => '#cerita', 'icon' => 'history_edu', 'label' => 'Cerita'],
    ['id' => 'gallery', 'href' => '#galeri', 'icon' => 'photo_library', 'label' => 'Galeri'],
    ['id' => 'location', 'href' => '#lokasi', 'icon' => 'location_on', 'label' => 'Lokasi'],
    ['id' => 'gift', 'href' => '#hadiah', 'icon' => 'card_giftcard', 'label' => 'Hadiah'],
    ['id' => 'rsvp', 'href' => '#ucapan', 'icon' => 'chat', 'label' => 'Ucapan'],
];
$customCss = function_exists('load_custom_css') ? load_custom_css() : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo $description; ?>">
    <meta name="csrf-token" content="<?php echo escape_html($csrf); ?>">
    <meta property="og:title" content="<?php echo $siteTitle; ?>">
    <meta property="og:description" content="<?php echo $description; ?>">
    <title><?php echo $siteTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?php echo escape_html(theme_google_font_stylesheet_url()); ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo escape_html(get_theme_asset_url($presetKey, 'style.css')); ?>">
    <style id="cms-parang-visual">:root{--parang-heading:<?php echo $headingFont; ?>;--parang-body:<?php echo $bodyFont; ?>;--parang-heading-color:<?php echo $headingColor; ?>;--parang-text:<?php echo $textColor; ?>;--parang-muted:<?php echo $mutedColor; ?>;--parang-link:<?php echo $linkColor; ?>;--parang-gold:<?php echo $accent; ?>;--cms-parang-bg:<?php echo $backgroundCss; ?>;--cms-parang-home-bg:<?php echo $parangHomeBg; ?>;--cms-parang-gallery-bg:<?php echo $parangGalleryBg; ?>;--cms-parang-location-bg:<?php echo $parangLocationBg; ?>;--cms-parang-left:<?php echo theme_visual_css_url($parangLeft); ?>;--cms-parang-right:<?php echo theme_visual_css_url($parangRight); ?>;--cms-parang-top:<?php echo theme_visual_css_url($parangTop); ?>;--cms-parang-top-width:<?php echo $parangTopWidth; ?>px;--cms-parang-top-offset-y:<?php echo $parangTopOffsetY; ?>px;--cms-parang-side-offset-x:<?php echo $parangSideOffsetX; ?>px;--cms-parang-side-offset-y:<?php echo $parangSideOffsetY; ?>%;--cms-parang-side-height-ratio:<?php echo escape_html((string)$parangSideHeightRatio); ?>%;--cms-parang-side-height-factor:<?php echo $parangSideHeightFactor; ?>;--cms-parang-side-size:<?php echo $parangSideSize; ?>px}#beranda,#galeri,#lokasi{background-size:cover;background-position:center;background-repeat:no-repeat}#beranda{background-image:linear-gradient(rgba(255,248,242,.78),rgba(240,227,206,.86)),var(--cms-parang-home-bg)}#galeri{background-image:linear-gradient(rgba(255,248,242,.82),rgba(240,227,206,.88)),var(--cms-parang-gallery-bg)}#lokasi{background-image:linear-gradient(rgba(255,248,242,.82),rgba(240,227,206,.88)),var(--cms-parang-location-bg)}.parang-ornament-left{content:var(--cms-parang-left)}.parang-ornament-right{content:var(--cms-parang-right)}#cms-parang-root h1,#cms-parang-root h2,#cms-parang-root h3{color:var(--parang-heading-color)}#cms-parang-root a{color:var(--parang-link)}#cms-parang-root .parang-nav-cta,#cms-parang-root .parang-primary-button,#cms-parang-root .parang-copy-button,#cms-parang-root .parang-rsvp-form button{color:var(--parang-cream)}</style>
    <?php if ($customCss !== ''): ?><style><?php echo $customCss; ?></style><?php endif; ?>
</head>
<body data-countdown-target="<?php echo escape_html((string)($config['schedule']['countdown_target'] ?? '')); ?>">
<div id="cms-parang-root">
    <aside class="parang-desktop-nav" aria-label="Navigasi undangan">
        <div class="parang-brand">
            <img src="<?php echo escape_html($sourceGunungan); ?>" alt="Ornamen Gunungan">
            <h1><?php echo $brideNickname; ?> &amp; <?php echo $groomNickname; ?></h1>
            <p><?php echo $heroDateDisplay; ?></p>
        </div>
        <nav class="parang-nav-list">
            <?php foreach ($navItems as $index => $item): if (!$sectionEnabled($item['id'])) continue; ?>
                <a class="parang-nav-link<?php echo $index === 0 ? ' is-active' : ''; ?>" href="<?php echo escape_html($item['href']); ?>"><span class="parang-icon" aria-hidden="true"><?php echo escape_html($item['icon']); ?></span><span><?php echo escape_html($item['label']); ?></span></a>
            <?php endforeach; ?>
        </nav>
        <?php if ($sectionEnabled('rsvp')): ?><a class="parang-nav-cta" href="#ucapan">RSVP Sekarang</a><?php endif; ?>
    </aside>

    <header class="parang-mobile-bar">
        <button type="button" id="parang-mobile-menu" aria-label="Buka navigasi"><span class="parang-icon" aria-hidden="true">menu</span></button>
        <h1><?php echo $brideNickname; ?> &amp; <?php echo $groomNickname; ?></h1>
        <?php if ($musicUrl !== ''): ?><button type="button" id="parang-music-toggle" aria-label="Putar musik"><span class="parang-icon" aria-hidden="true">music_note</span></button><?php else: ?><span aria-hidden="true"></span><?php endif; ?>
    </header>

    <main class="parang-main parang-bg">
        <div class="parang-ground"></div>
        <?php if ($sectionEnabled('hero')): ?>
        <section id="beranda" class="parang-section parang-hero" aria-labelledby="parang-hero-title">
            <div class="parang-hero-card">
                <img class="parang-ornament parang-ornament-top" src="<?php echo escape_html($parangTop); ?>" alt="Ornamen Gunungan">
                <img class="parang-ornament parang-ornament-side parang-ornament-left" src="<?php echo escape_html($parangLeft); ?>" alt="Ornamen wayang" aria-hidden="true">
                <img class="parang-ornament parang-ornament-side parang-ornament-right" src="<?php echo escape_html($parangRight); ?>" alt="" aria-hidden="true">
                <p class="parang-opening-greeting"><?php echo $openingGreeting; ?></p>
                <p class="parang-label">Kepada Yth.</p>
                <p class="parang-hero-guest">Bapak/Ibu/Saudara/i</p>
                <p class="parang-hero-guest-name"><?php echo $guestLabel; ?></p>
                <p class="parang-label">Pernikahan Kami</p>
                <h2 id="parang-hero-title" class="parang-hero-title"><?php echo $brideNickname; ?></h2>
                <p class="parang-hero-amp" aria-hidden="true">&amp;</p>
                <h2 class="parang-hero-title"><?php echo $groomNickname; ?></h2>
                <p class="parang-hero-date"><?php echo $heroDateDisplay !== '' ? $heroDateDisplay : 'Hari Bahagia Kami'; ?></p>
                <a class="parang-primary-button" href="<?php echo $sectionEnabled('couple') ? '#mempelai' : '#acara'; ?>"><span>Buka Undangan</span><span class="parang-icon" aria-hidden="true">arrow_downward</span></a>
                <span class="parang-icon" aria-hidden="true" style="margin-top:2rem;color:var(--parang-secondary)">expand_more</span>
            </div>
        </section>
        <?php endif; ?>

        <div class="parang-divider"><span></span><img src="<?php echo escape_html($sourceGunungan); ?>" alt="Ornamen pemisah"><span></span></div>

        <?php if ($sectionEnabled('couple')): ?>
        <section id="mempelai" class="parang-section parang-couple-section" aria-labelledby="parang-couple-title">
            <h2 id="parang-couple-title" class="parang-section-title">Mempelai</h2>
            <div class="parang-couple-grid">
                <article class="parang-couple-card parang-reveal">
                    <div class="parang-portrait"><img src="<?php echo escape_html($groomPhoto); ?>" alt="Foto <?php echo $groomName; ?>" loading="lazy"></div>
                    <h3><?php echo $groomName; ?></h3>
                    <p>Putra Pertama dari</p>
                    <p><strong><?php echo escape_html((string)($config['parents']['groom_father'] ?? '')); ?> &amp; <?php echo escape_html((string)($config['parents']['groom_mother'] ?? '')); ?></strong></p>
                    <a class="parang-social-button" href="#ucapan" aria-label="Doa untuk <?php echo $groomName; ?>"><span class="parang-icon" aria-hidden="true">favorite</span></a>
                </article>
                <article class="parang-couple-card parang-reveal">
                    <div class="parang-portrait"><img src="<?php echo escape_html($bridePhoto); ?>" alt="Foto <?php echo $brideName; ?>" loading="lazy"></div>
                    <h3><?php echo $brideName; ?></h3>
                    <p>Putri Pertama dari</p>
                    <p><strong><?php echo escape_html((string)($config['parents']['bride_father'] ?? '')); ?> &amp; <?php echo escape_html((string)($config['parents']['bride_mother'] ?? '')); ?></strong></p>
                    <a class="parang-social-button" href="#ucapan" aria-label="Doa untuk <?php echo $brideName; ?>"><span class="parang-icon" aria-hidden="true">favorite</span></a>
                </article>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($sectionEnabled('event')): ?>
        <section id="acara" class="parang-section parang-content-section">
            <div class="parang-content-frame">
                <h2 class="parang-section-title">Acara Pernikahan</h2>
                <p class="parang-lead"><?php echo $openingText; ?></p>
                <div class="parang-event-grid">
                    <article class="parang-card parang-reveal"><h3>Akad Nikah</h3><p><?php echo $akadDateDisplay; ?></p><p><?php echo escape_html($akadTime); ?> WIB</p><p><?php echo $venue; ?></p></article>
                    <article class="parang-card parang-reveal"><h3>Resepsi</h3><p><?php echo $receptionDateDisplay; ?></p><p><?php echo escape_html($receptionTime); ?> WIB</p><p><?php echo $venue; ?></p></article>
                    <?php if (!empty($config['dresscode']['enabled'])): ?><article class="parang-card parang-reveal"><h3><?php echo escape_html((string)($config['dresscode']['title'] ?? 'Dresscode')); ?></h3><p><?php echo render_preserved_text((string)($config['dresscode']['color'] ?? 'Putih / Pastel')); ?></p><p><?php echo render_preserved_text((string)($config['dresscode']['rule'] ?? 'Rapi dan sopan')); ?></p></article><?php endif; ?>
                </div>
                <div class="parang-countdown" aria-live="polite"><p class="parang-label">Menuju Hari Bahagia</p><div class="parang-countdown-grid"><div><strong id="parang-days">00</strong><span>Hari</span></div><div><strong id="parang-hours">00</strong><span>Jam</span></div><div><strong id="parang-minutes">00</strong><span>Menit</span></div><div><strong id="parang-seconds">00</strong><span>Detik</span></div></div></div>
                <p style="text-align:center;margin:1.5rem 0 0"><a href="<?php echo $calendarLink; ?>" target="_blank" rel="noopener">Simpan ke Google Kalender</a></p>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($sectionEnabled('story')): ?>
        <section id="cerita" class="parang-section parang-content-section">
            <div class="parang-content-frame">
                <h2 class="parang-section-title">Cerita Kami</h2>
                <div class="parang-story-list"><?php echo $storyMarkup; ?></div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($sectionEnabled('gallery')): ?>
        <section id="galeri" class="parang-section parang-content-section">
            <div class="parang-content-frame">
                <h2 class="parang-section-title">Galeri</h2>
                <p class="parang-lead">Beberapa momen indah kami dalam perjalanan sebelum hari pernikahan.</p>
                <div class="parang-gallery-grid"><?php echo $galleryMarkup; ?></div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($sectionEnabled('location')): ?>
        <section id="lokasi" class="parang-section parang-content-section">
            <div class="parang-content-frame">
                <h2 class="parang-section-title">Lokasi Acara</h2>
                <div class="parang-info-grid">
                    <article class="parang-card"><h3><?php echo $venue; ?></h3><p><?php echo $address; ?></p><p><a href="<?php echo $mapsUrl; ?>" target="_blank" rel="noopener">Buka di Google Maps</a></p></article>
                    <?php if ($mapsEmbed !== ''): ?><div class="parang-map-wrap"><iframe src="<?php echo $mapsEmbed; ?>" title="Lokasi acara" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div><?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($sectionEnabled('gift')): ?>
        <section id="hadiah" class="parang-section parang-content-section">
            <div class="parang-content-frame">
                <h2 class="parang-section-title">Hadiah Pernikahan</h2>
                <p class="parang-lead">Doa dan kehadiran Anda adalah hadiah terindah. Jika berkenan, berikut informasi tanda kasih digital.</p>
                <div class="parang-info-grid">
                    <article class="parang-card"><h3>Rekening</h3><p><?php echo escape_html((string)($config['gift']['bank'] ?? '')); ?></p><p class="parang-copy-value"><?php echo escape_html((string)($config['gift']['account_number'] ?? '')); ?></p><button type="button" class="parang-copy-button" data-copy="<?php echo escape_html((string)($config['gift']['account_number'] ?? '')); ?>">Salin Nomor</button></article>
                    <article class="parang-card"><h3><?php echo escape_html((string)($config['gift']['e_wallet_label'] ?? 'E-Wallet')); ?></h3><p class="parang-copy-value"><?php echo escape_html((string)($config['gift']['e_wallet_number'] ?? '')); ?></p><button type="button" class="parang-copy-button" data-copy="<?php echo escape_html((string)($config['gift']['e_wallet_number'] ?? '')); ?>">Salin Nomor</button></article>
                    <?php if ($qrisUrl !== ''): ?><article class="parang-card parang-qris-card"><h3>QRIS</h3><img src="<?php echo escape_html($qrisUrl); ?>" alt="QRIS untuk tanda kasih" loading="lazy" decoding="async" style="display:block;width:min(100%,220px);aspect-ratio:1/1;object-fit:contain;background:#fff;padding:10px;margin:0 auto;border-radius:10px;"></article><?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($sectionEnabled('rsvp')): ?>
        <section id="ucapan" class="parang-section parang-content-section">
            <div class="parang-content-frame">
                <h2 class="parang-section-title">Ucapan &amp; Konfirmasi</h2>
                <p class="parang-lead">Mohon konfirmasi kehadiran dan tuliskan doa untuk kami.</p>
                <form id="parang-rsvp-form" class="parang-rsvp-form">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_html($csrf); ?>">
                    <label>Nama<input type="text" name="nama" placeholder="Nama Anda" required></label>
                    <label>Kehadiran<select name="status" required><option value="Hadir">Hadir</option><option value="Tidak Hadir">Tidak Hadir</option></select></label>
                    <label>Ucapan<textarea name="ucapan" rows="4" placeholder="Tulis ucapan dan doa"></textarea></label>
                    <input type="text" name="website" autocomplete="off" tabindex="-1" aria-hidden="true" style="display:none">
                    <button type="submit">Kirim Konfirmasi Kehadiran</button>
                    <p id="parang-form-message" class="parang-form-message" role="status" aria-live="polite"></p>
                </form>
            </div>
        </section>
        <?php endif; ?>

        <div class="parang-divider"><span></span><img src="<?php echo escape_html($sourceGunungan); ?>" alt="Ornamen pemisah"><span></span></div>

        <?php if ($sectionEnabled('footer')): ?>
        <footer class="parang-footer">
            <div class="parang-footer-inner"><img src="<?php echo escape_html($sourceGunungan); ?>" alt="Gunungan"><h2><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></h2><p><?php echo $heroDateDisplay; ?></p><p>© <?php echo date('Y'); ?> <?php echo $brideName; ?> &amp; <?php echo $groomName; ?>. Manten Jawi.</p></div>
        </footer>
        <?php endif; ?>
    </main>

    <nav class="parang-mobile-nav" aria-label="Navigasi cepat">
        <?php foreach ([['location', '#lokasi', 'location_on', 'Lokasi'], ['gift', '#hadiah', 'card_giftcard', 'Hadiah'], ['rsvp', '#ucapan', 'chat', 'Ucapan']] as $item): if (!$sectionEnabled($item[0])) continue; ?>
            <a class="parang-mobile-link" href="<?php echo $item[1]; ?>"><span class="parang-icon" aria-hidden="true"><?php echo $item[2]; ?></span><span><?php echo $item[3]; ?></span></a>
        <?php endforeach; ?>
        <?php if ($sectionEnabled('rsvp')): ?><a class="parang-mobile-link is-rsvp" href="#ucapan"><span class="parang-icon" aria-hidden="true">how_to_reg</span><span>RSVP</span></a><?php endif; ?>
    </nav>
    <?php if ($musicUrl !== ''): ?><audio id="parang-background-music" src="<?php echo $musicUrl; ?>" loop preload="none"></audio><?php endif; ?>
</div>
    <script>
    (function () {
        function syncParangHeroCardHeight() {
            var card = document.querySelector('.parang-hero-card');
            if (!card) return;
            document.documentElement.style.setProperty('--cms-parang-hero-card-height', card.getBoundingClientRect().height + 'px');
        }
        if (window.ResizeObserver) {
            var observer = new ResizeObserver(syncParangHeroCardHeight);
            var card = document.querySelector('.parang-hero-card');
            if (card) observer.observe(card);
        }
        window.addEventListener('load', syncParangHeroCardHeight, {once: true});
        window.addEventListener('resize', syncParangHeroCardHeight, {passive: true});
        syncParangHeroCardHeight();
    }());
    </script>
    <script src="<?php echo escape_html(get_theme_asset_url($presetKey, 'script.js')); ?>" defer></script>
</body>
</html>
