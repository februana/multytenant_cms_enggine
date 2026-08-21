<?php
if (!defined('THEME_HELPER_LOADED')) {
    require_once __DIR__ . '/../../app/theme-helper.php';
}
$config = $config ?? [];
$presetKey = 'archak';
$semanticNames = theme_semantic_names($config);
$brideName = escape_html($semanticNames['bride_full_name']);
$groomName = escape_html($semanticNames['groom_full_name']);
$guestName = function_exists('resolve_guest_name') ? resolve_guest_name($config) : '';
$guestLabel = escape_html($guestName !== '' ? $guestName : 'Bapak/Ibu/Saudara/i');
$brideNickname = escape_html($semanticNames['bride_nickname']);
$groomNickname = escape_html($semanticNames['groom_nickname']);
$bridePhoto = !empty($config['media']['bride_photo']) ? $config['media']['bride_photo'] : ($config['media']['cover'] ?? '');
$groomPhoto = !empty($config['media']['groom_photo']) ? $config['media']['groom_photo'] : ($config['media']['cover'] ?? '');
$couplePhoto = !empty($config['media']['couple_photo']) ? $config['media']['couple_photo'] : ($config['media']['cover'] ?? '');
$akadDate = (string)($config['schedule']['akad_date'] ?? '');
$akadTime = (string)($config['schedule']['akad_time'] ?? '');
$receptionDate = (string)($config['schedule']['reception_date'] ?? $akadDate);
$receptionTime = (string)($config['schedule']['reception_time'] ?? '');
$venue = escape_html($config['location']['venue'] ?? '');
$address = nl2br(escape_html($config['location']['address'] ?? ''));
$mapsUrl = escape_html($config['location']['maps_url'] ?? '');
$quote = nl2br(escape_html($config['wedding']['quote'] ?? ''));
$openingText = nl2br(escape_html($config['wedding']['opening_text'] ?? ''));
$openingGreeting = render_preserved_text(theme_opening_greeting($config, 'archak'));
$closingText = nl2br(escape_html($config['wedding']['closing_text'] ?? ''));
$qrisPath = trim((string)($config['gift']['qris_image'] ?? ''));
$qrisUrl = $qrisPath !== '' ? public_path($qrisPath) : '';
$whatsappLink = escape_html(build_whatsapp_link($config));
$galleryItems = function_exists('get_gallery_items') ? get_gallery_items($config) : [];
$galleryUrls = [];
foreach (array_slice((array)$galleryItems, 0, 3) as $item) {
    $path = is_array($item) ? ($item['path'] ?? $item['src'] ?? '') : (string)$item;
    $galleryUrls[] = $path !== '' ? public_path($path) : '';
}
while (count($galleryUrls) < 3) $galleryUrls[] = $couplePhoto !== '' ? public_path($couplePhoto) : '';
$stories = $config['love_story']['items'] ?? [];
if (!is_array($stories) || !$stories) $stories = [['title' => 'Kisah Kami', 'date' => $akadDate, 'description' => (string)($config['wedding']['opening_text'] ?? '')]];
$customCss = function_exists('load_custom_css') ? load_custom_css() : '';
$visuals = function_exists('theme_visual_values_for_config') ? theme_visual_values_for_config($config, 'archak') : [];
$archakAccent = (string)($visuals['accent_color'] ?? '#8c5a4d');
$archakHeadingColor = (string)($visuals['heading_color'] ?? '#211d1a');
$archakTextColor = (string)($visuals['text_color'] ?? '#211d1a');
$archakMutedColor = (string)($visuals['muted_color'] ?? '#5d5350');
$archakLinkColor = (string)($visuals['link_color'] ?? '#8c5a4d');
$archakHeadingFont = (string)($visuals['heading_font'] ?? 'Cinzel, serif');
$archakBodyFont = (string)($visuals['body_font'] ?? 'Quicksand, sans-serif');
$archakHeroPath = (string)($visuals['hero_background'] ?? '') ?: $couplePhoto;
$archakTitleScale = (float)($visuals['hero_title_scale'] ?? '1');
$archakHeroImage = $archakHeroPath !== '' ? theme_visual_css_url($archakHeroPath) : '';
$archakSectionCss = static function (string $key) use ($visuals): string {
    $path = trim((string)($visuals[$key] ?? ''));
    return $path === '' ? 'none' : theme_visual_css_url(theme_visual_public_path($path));
};
$archakTimelineBg = $archakSectionCss('section_background_timeline');
$archakGalleryBg = $archakSectionCss('section_background_gallery');
$archakStayBg = $archakSectionCss('section_background_stay');
$archakRegistryBg = $archakSectionCss('section_background_registry');
$archakBadgePath = trim((string)($visuals['header_badge'] ?? ''));
if ($archakBadgePath === '') $archakBadgePath = trim((string)($config['theme_options']['archak']['header_badge_image'] ?? ''));
$archakBadgeUrl = $archakBadgePath !== '' ? theme_visual_public_path($archakBadgePath) : '';
$heroStyle = $archakHeroImage !== '' ? 'background-image: var(--cms-archak-hero-bg);' : '';
$archakVisualStyle = '<style id="cms-archak-visual">:root{--cms-archak-accent:' . $archakAccent . ';--cms-archak-heading-color:' . $archakHeadingColor . ';--cms-archak-text:' . $archakTextColor . ';--cms-archak-muted:' . $archakMutedColor . ';--cms-archak-link:' . $archakLinkColor . ';--cms-archak-heading:' . $archakHeadingFont . ';--cms-archak-body:' . $archakBodyFont . ';--cms-archak-title-scale:' . $archakTitleScale . ';--cms-archak-hero-bg:' . $archakHeroImage . ';--cms-archak-timeline-bg:' . $archakTimelineBg . ';--cms-archak-gallery-bg:' . $archakGalleryBg . ';--cms-archak-stay-bg:' . $archakStayBg . ';--cms-archak-registry-bg:' . $archakRegistryBg . ';--cms-archak-badge:' . ($archakBadgeUrl !== '' ? theme_visual_css_url($archakBadgeUrl) : 'none') . '}body,body p,body li,body a,body button,body input,body select,body textarea,body .text,body .guest-greeting{font-family:var(--cms-archak-body)}body{color:var(--cms-archak-text)}body h1,body h2,body h3,.logo,.checkbtn{font-family:var(--cms-archak-heading);color:var(--cms-archak-heading-color)}body .text{color:var(--cms-archak-text)}body .guest-greeting,body small{color:var(--cms-archak-muted)}body a:not(.huge-btn){color:var(--cms-archak-link)}body .fa,body .fas,body .far,body .fab,body [class^="fa-"],body [class*=" fa-"]{font-family:"Font Awesome 6 Free"!important}body .fab,body [class^="fab"],body [class*=" fab"]{font-family:"Font Awesome 6 Brands"!important}.huge-btn,.links{color:var(--cms-archak-accent);border-color:var(--cms-archak-accent)}nav .logo{color:var(--cms-archak-accent)}.home h1{font-size:calc(var(--h1-size) * var(--cms-archak-title-scale))}.timeline,#story,#stay,#registry{background-size:cover;background-position:center;background-repeat:no-repeat}.timeline{background-image:linear-gradient(rgba(246,241,235,.86),rgba(246,241,235,.86)),var(--cms-archak-timeline-bg)}#story{background-image:linear-gradient(rgba(246,241,235,.86),rgba(246,241,235,.86)),var(--cms-archak-gallery-bg)}#stay{background-image:linear-gradient(rgba(246,241,235,.86),rgba(246,241,235,.86)),var(--cms-archak-stay-bg)}#registry{background-image:linear-gradient(rgba(246,241,235,.86),rgba(246,241,235,.86)),var(--cms-archak-registry-bg)}.archak-header-badge{width:42px;height:42px;object-fit:contain;margin-left:12px;vertical-align:middle}@media(max-width:1000px){.v-reposition-container,.h-reposition-container{width:100%;max-width:100%;box-sizing:border-box}.gallery-img{width:100%;max-width:100%;margin-left:0;margin-right:0;position:relative;left:0}}@media(max-width:576px){.home h1{font-size:clamp(2.15rem,11vw,4rem);line-height:1.05;overflow-wrap:anywhere}}</style>';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape_html($config['site']['title'] ?? 'Undangan Pernikahan'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?php echo escape_html(theme_google_font_stylesheet_url()); ?>">
    <link rel="stylesheet" href="<?php echo get_theme_asset_url($presetKey, 'original/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo get_theme_asset_url($presetKey, 'fidelity-adapter.css'); ?>">
    <?php echo $archakVisualStyle; ?>
    <script src="https://kit.fontawesome.com/7b12bcc245.js" crossorigin="anonymous"></script>
    <?php if ($customCss !== ''): ?><style><?php echo $customCss; ?></style><?php endif; ?>
</head>
<body>
    <nav>
        <input type="checkbox" id="check">
        <label for="check" class="checkbtn"><i class="fas fa-bars"></i></label>
        <label class="logo"><?php echo substr($brideNickname, 0, 1); ?>&amp;<?php echo substr($groomNickname, 0, 1); ?></label><?php if ($archakBadgeUrl !== ''): ?><img class="archak-header-badge" src="<?php echo escape_html($archakBadgeUrl); ?>" alt="Emblem undangan" loading="lazy"><?php endif; ?>
        <ul class="text">
            <?php if (theme_section_enabled($config, $presetKey, 'story')): ?><li><a href="#story" onclick="myFunction()">KISAH KAMI</a></li><?php endif; ?>
            <?php if (theme_section_enabled($config, $presetKey, 'stay')): ?><li><a href="#stay" onclick="myFunction()">PERJALANAN &amp; TEMPAT MENGINAP</a></li><?php endif; ?>
            <?php if (theme_section_enabled($config, $presetKey, 'registry')): ?><li><a href="#registry" onclick="myFunction()">JANJI</a></li><?php endif; ?>
        </ul>
    </nav>

    <?php if (theme_section_enabled($config, $presetKey, 'home')): ?>
    <div class="home hz-margin">
        <h3>Kita Akan Menikah</h3>
        <p id="guest-greeting" class="guest-greeting">Kepada <?php echo $guestLabel; ?></p>
        <p class="opening-greeting"><?php echo $openingGreeting; ?></p>
        <h1><?php echo $brideNickname; ?> &amp; <?php echo $groomNickname; ?></h1>
        <div class="container-out"><div class="container-in text"><?php echo $akadDate ? escape_html(date('l, M. j, Y', strtotime($akadDate))) : ''; ?><br><?php echo $venue; ?><button class="huge-btn" type="button" onclick="window.open('<?php echo $whatsappLink; ?>','_blank')">Konfirmasi Kehadiran</button></div><div class="home-img" id="home-img-lg"<?php if ($heroStyle !== ''): ?> style="<?php echo $heroStyle; ?>"<?php endif; ?>></div></div>
    </div>
    <div class="home-img home-img-sm"<?php if ($heroStyle !== ''): ?> style="<?php echo $heroStyle; ?>"<?php endif; ?>></div>
    <?php endif; ?>

    <?php if (theme_section_enabled($config, $presetKey, 'timeline')): ?>
    <div class="timeline hz-margin reveal"><h2>Merayakan Cinta Kami <br>Bersama Orang-Orang Tersayang</h2><div class="timeline-container"><div class="timeline-img"<?php if ($bridePhoto !== ''): ?> style="background-image:url('<?php echo escape_html(public_path($bridePhoto)); ?>');"<?php endif; ?>></div><div class="timings"><h3>01.&nbsp; &nbsp; Akad Nikah</h3><div class="text"><?php echo $akadDate ? escape_html(date('l, M. j, Y', strtotime($akadDate))) : ''; ?><br><?php echo $venue; ?></div><h3>02.&nbsp; &nbsp; Resepsi</h3><div class="text"><?php echo $receptionDate ? escape_html(date('l, M. j, Y', strtotime($receptionDate))) : ''; ?><br><?php echo $venue; ?></div></div></div></div>
    <?php endif; ?>

    <div class="v-reposition-container"><div class="h-reposition-container">
        <?php if (theme_section_enabled($config, $presetKey, 'story')): ?><div id="story" class="hz-margin"><h3 class="reveal">Bukan Sekadar <br>Kisah Cinta Biasa</h3><p class="text reveal"><?php echo $quote ?: $openingText; ?></p></div><?php endif; ?>
        <?php if (theme_section_enabled($config, $presetKey, 'gallery')): ?><div class="gallery hz-margin reveal"><div class="gallery-img"<?php if ($galleryUrls[0] !== ''): ?> style="background-image:url('<?php echo escape_html($galleryUrls[0]); ?>');"<?php endif; ?>></div><div class="gallery-img"<?php if ($galleryUrls[1] !== ''): ?> style="background-image:url('<?php echo escape_html($galleryUrls[1]); ?>');"<?php endif; ?>></div><div class="gallery-img"<?php if ($galleryUrls[2] !== ''): ?> style="background-image:url('<?php echo escape_html($galleryUrls[2]); ?>');"<?php endif; ?>></div></div><?php endif; ?>
        <?php if (theme_section_enabled($config, $presetKey, 'quote')): ?><div class="quote reveal"><h1>“<?php echo $quote ?: 'Bagi dunia, kamu adalah satu orang, tetapi bagi seseorang, kamu adalah dunia'; ?>”.</h1><div class="author text"><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></div></div><?php endif; ?>
        <div class="hands" id="parallax1"<?php if ($couplePhoto !== ''): ?> style="background-image:url('<?php echo escape_html(public_path($couplePhoto)); ?>');"<?php endif; ?>></div>
        <?php if (theme_section_enabled($config, $presetKey, 'stay')): ?><div id="stay"><h2 class="reveal">Perjalanan &amp; Tempat Menginap</h2><div class="stay-container reveal"><div class="stay-item"><h3>01. <br>Cara Menuju Lokasi</h3><div class="text">Petunjuk mudah <br>menuju lokasi</div><a class="text links" href="<?php echo $mapsUrl; ?>" target="_blank">PETA &amp; DETAIL</a></div><div class="stay-item"><h3>02. <br>Saat Tiba</h3><div class="text">Beberapa hal menarik di sekitar <br>area ini</div><a class="text links" href="<?php echo $mapsUrl; ?>" target="_blank">REKOMENDASI KAMI</a></div><div class="stay-item"><h3>03. <br>Tempat Menginap</h3><div class="text"><?php echo $venue; ?><br><?php echo $address; ?></div><a class="text links" href="<?php echo $mapsUrl; ?>" target="_blank">PILIHAN PENGINAPAN</a></div></div></div><?php endif; ?>
        <?php if (theme_section_enabled($config, $presetKey, 'registry')): ?><div id="registry"><div class="registry-container reveal"><h3>Janji</h3><div class="text"><?php echo $closingText ?: 'Bersama, kita akan menjaga rumah dan menjadi kekuatan serta keberanian bagi satu sama lain.'; ?><br><br><strong><?php echo escape_html($config['gift']['bank'] ?? ''); ?>:</strong> <?php echo escape_html($config['gift']['account_number'] ?? ''); ?> — <?php echo escape_html($config['gift']['account_holder'] ?? ''); ?><br><strong><?php echo escape_html($config['gift']['e_wallet_label'] ?? ''); ?>:</strong> <?php echo escape_html($config['gift']['e_wallet_number'] ?? ''); ?><?php if ($qrisUrl !== ''): ?><br><br><strong>QRIS:</strong><br><img src="<?php echo escape_html($qrisUrl); ?>" alt="QRIS untuk tanda kasih" loading="lazy" decoding="async" style="display:block;width:min(100%,180px);aspect-ratio:1/1;object-fit:contain;background:#fff;padding:10px;margin:12px 0;border-radius:10px;"><?php endif; ?></div><button class="huge-btn text" type="button" onclick="window.open('<?php echo $whatsappLink; ?>','_blank')">DOAKAN KAMI</button></div><div class="registry-img registry-img-lg" id="parallax2"<?php if ($groomPhoto !== ''): ?> style="background-image:url('<?php echo escape_html(public_path($groomPhoto)); ?>');"<?php endif; ?>></div></div><div class="registry-img registry-img-sm"<?php if ($groomPhoto !== ''): ?> style="background-image:url('<?php echo escape_html(public_path($groomPhoto)); ?>');"<?php endif; ?>></div><?php endif; ?>
        <?php if (theme_section_enabled($config, $presetKey, 'parting')): ?><div class="parting-message reveal"><h1>Sampai Jumpa!</h1><button class="huge-btn" type="button" onclick="window.open('<?php echo $whatsappLink; ?>','_blank')">Konfirmasi Kehadiran</button></div><?php endif; ?>
        <footer><h2>Hubungi Kami</h2><h2><?php echo $brideName; ?>: <?php echo escape_html($config['whatsapp']['phone'] ?? ''); ?></h2><h2><?php echo $groomName; ?>: <?php echo escape_html($config['whatsapp']['phone'] ?? ''); ?></h2><div class="text">© <?php echo date('Y'); ?> untuk <?php echo $brideName; ?> dan <?php echo $groomName; ?>. Dibuat oleh <a href="https://twitter.com/NathArchak" class="text">@NathArchak</a></div></footer>
    </div></div>
    <script src="<?php echo get_theme_asset_url($presetKey, 'original/main.js'); ?>"></script>
</body>
</html>
