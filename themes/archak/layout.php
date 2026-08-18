<?php
if (!defined('THEME_HELPER_LOADED')) {
    require_once __DIR__ . '/../../app/theme-helper.php';
}
$config = $config ?? [];
$presetKey = 'archak';
$brideName = escape_html($config['wedding']['bride_name'] ?? '');
$groomName = escape_html($config['wedding']['groom_name'] ?? '');
$guestName = function_exists('resolve_guest_name') ? resolve_guest_name($config) : '';
$guestLabel = escape_html($guestName !== '' ? $guestName : 'Bapak/Ibu/Saudara/i');
$brideNickname = escape_html($config['wedding']['bride_nickname'] ?? $brideName);
$groomNickname = escape_html($config['wedding']['groom_nickname'] ?? $groomName);
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
$closingText = nl2br(escape_html($config['wedding']['closing_text'] ?? ''));
$whatsappLink = escape_html(build_whatsapp_link($config));
$galleryItems = function_exists('get_gallery_items') ? get_gallery_items($config) : [];
$galleryUrls = [];
foreach (array_slice((array)$galleryItems, 0, 3) as $item) {
    $path = is_array($item) ? ($item['path'] ?? $item['src'] ?? '') : (string)$item;
    if ($path !== '') $galleryUrls[] = public_path($path);
}
while (count($galleryUrls) < 3) $galleryUrls[] = public_path($couplePhoto);
$stories = $config['love_story']['items'] ?? [];
if (!is_array($stories) || !$stories) $stories = [['title' => 'Kisah Kami', 'date' => $akadDate, 'description' => (string)($config['wedding']['opening_text'] ?? '')]];
$customCss = function_exists('load_custom_css') ? load_custom_css() : '';
$visuals = function_exists('theme_visual_values_for_config') ? theme_visual_values_for_config($config, 'archak') : [];
$archakAccent = (string)($visuals['accent_color'] ?? '#8c5a4d');
$archakHeadingFont = (string)($visuals['heading_font'] ?? 'Cinzel, serif');
$archakBodyFont = (string)($visuals['body_font'] ?? 'Quicksand, sans-serif');
$archakHeroPath = (string)($visuals['hero_background'] ?? '') ?: $couplePhoto;
$archakTitleScale = (float)($visuals['hero_title_scale'] ?? '1');
$archakHeroImage = theme_visual_css_url($archakHeroPath);
$heroStyle = 'background-image: var(--cms-archak-hero-bg);';
$archakVisualStyle = '<style id="cms-archak-visual">:root{--cms-archak-accent:' . $archakAccent . ';--cms-archak-heading:' . $archakHeadingFont . ';--cms-archak-body:' . $archakBodyFont . ';--cms-archak-title-scale:' . $archakTitleScale . ';--cms-archak-hero-bg:' . $archakHeroImage . '}body *{font-family:var(--cms-archak-body)}h1,h2,h3,.logo,.checkbtn{font-family:var(--cms-archak-heading)}.huge-btn,.links{color:var(--cms-archak-accent);border-color:var(--cms-archak-accent)}nav .logo{color:var(--cms-archak-accent)}.home h1{font-size:calc(var(--h1-size) * var(--cms-archak-title-scale))}@media(max-width:576px){.home h1{font-size:clamp(2.15rem,11vw,4rem);line-height:1.05;overflow-wrap:anywhere}}</style>';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape_html($config['site']['title'] ?? 'Undangan Pernikahan'); ?></title>
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
        <label class="logo"><?php echo substr($brideNickname, 0, 1); ?>&amp;<?php echo substr($groomNickname, 0, 1); ?></label>
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
        <h1><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></h1>
        <div class="container-out"><div class="container-in text"><?php echo $akadDate ? escape_html(date('l, M. j, Y', strtotime($akadDate))) : ''; ?><br><?php echo $venue; ?><button class="huge-btn" type="button" onclick="window.open('<?php echo $whatsappLink; ?>','_blank')">Konfirmasi Kehadiran</button></div><div class="home-img" id="home-img-lg" style="<?php echo $heroStyle; ?>"></div></div>
    </div>
    <div class="home-img home-img-sm" style="<?php echo $heroStyle; ?>"></div>
    <?php endif; ?>

    <?php if (theme_section_enabled($config, $presetKey, 'timeline')): ?>
    <div class="timeline hz-margin reveal"><h2>Merayakan Cinta Kami <br>Bersama Orang-Orang Tersayang</h2><div class="timeline-container"><div class="timeline-img" style="background-image:url('<?php echo escape_html(public_path($bridePhoto)); ?>');"></div><div class="timings"><h3>01.&nbsp; &nbsp; Akad Nikah</h3><div class="text"><?php echo $akadDate ? escape_html(date('l, M. j, Y', strtotime($akadDate))) : ''; ?><br><?php echo $venue; ?></div><h3>02.&nbsp; &nbsp; Resepsi</h3><div class="text"><?php echo $receptionDate ? escape_html(date('l, M. j, Y', strtotime($receptionDate))) : ''; ?><br><?php echo $venue; ?></div></div></div></div>
    <?php endif; ?>

    <div class="v-reposition-container"><div class="h-reposition-container">
        <?php if (theme_section_enabled($config, $presetKey, 'story')): ?><div id="story" class="hz-margin"><h3 class="reveal">Bukan Sekadar <br>Kisah Cinta Biasa</h3><p class="text reveal"><?php echo $quote ?: $openingText; ?></p></div><?php endif; ?>
        <?php if (theme_section_enabled($config, $presetKey, 'gallery')): ?><div class="gallery hz-margin reveal"><div class="gallery-img" style="background-image:url('<?php echo escape_html($galleryUrls[0]); ?>');"></div><div class="gallery-img" style="background-image:url('<?php echo escape_html($galleryUrls[1]); ?>');"></div><div class="gallery-img" style="background-image:url('<?php echo escape_html($galleryUrls[2]); ?>');"></div></div><?php endif; ?>
        <?php if (theme_section_enabled($config, $presetKey, 'quote')): ?><div class="quote reveal"><h1>“<?php echo $quote ?: 'Bagi dunia, kamu adalah satu orang, tetapi bagi seseorang, kamu adalah dunia'; ?>”.</h1><div class="author text"><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></div></div><?php endif; ?>
        <div class="hands" id="parallax1" style="background-image:url('<?php echo escape_html(public_path($couplePhoto)); ?>');"></div>
        <?php if (theme_section_enabled($config, $presetKey, 'stay')): ?><div id="stay"><h2 class="reveal">Perjalanan &amp; Tempat Menginap</h2><div class="stay-container reveal"><div class="stay-item"><h3>01. <br>Cara Menuju Lokasi</h3><div class="text">Petunjuk mudah <br>menuju lokasi</div><a class="text links" href="<?php echo $mapsUrl; ?>" target="_blank">PETA &amp; DETAIL</a></div><div class="stay-item"><h3>02. <br>Saat Tiba</h3><div class="text">Beberapa hal menarik di sekitar <br>area ini</div><a class="text links" href="<?php echo $mapsUrl; ?>" target="_blank">REKOMENDASI KAMI</a></div><div class="stay-item"><h3>03. <br>Tempat Menginap</h3><div class="text"><?php echo $venue; ?><br><?php echo $address; ?></div><a class="text links" href="<?php echo $mapsUrl; ?>" target="_blank">PILIHAN PENGINAPAN</a></div></div></div><?php endif; ?>
        <?php if (theme_section_enabled($config, $presetKey, 'registry')): ?><div id="registry"><div class="registry-container reveal"><h3>Janji</h3><div class="text"><?php echo $closingText ?: 'Bersama, kita akan menjaga rumah dan menjadi kekuatan serta keberanian bagi satu sama lain.'; ?><br><br><strong><?php echo escape_html($config['gift']['bank'] ?? ''); ?>:</strong> <?php echo escape_html($config['gift']['account_number'] ?? ''); ?> — <?php echo escape_html($config['gift']['account_holder'] ?? ''); ?><br><strong><?php echo escape_html($config['gift']['e_wallet_label'] ?? ''); ?>:</strong> <?php echo escape_html($config['gift']['e_wallet_number'] ?? ''); ?></div><button class="huge-btn text" type="button" onclick="window.open('<?php echo $whatsappLink; ?>','_blank')">DOAKAN KAMI</button></div><div class="registry-img registry-img-lg" id="parallax2" style="background-image:url('<?php echo escape_html(public_path($groomPhoto)); ?>');"></div></div><div class="registry-img registry-img-sm" style="background-image:url('<?php echo escape_html(public_path($groomPhoto)); ?>');"></div><?php endif; ?>
        <?php if (theme_section_enabled($config, $presetKey, 'parting')): ?><div class="parting-message reveal"><h1>Sampai Jumpa!</h1><button class="huge-btn" type="button" onclick="window.open('<?php echo $whatsappLink; ?>','_blank')">Konfirmasi Kehadiran</button></div><?php endif; ?>
        <footer><h2>Hubungi Kami</h2><h2><?php echo $brideName; ?>: <?php echo escape_html($config['whatsapp']['phone'] ?? ''); ?></h2><h2><?php echo $groomName; ?>: <?php echo escape_html($config['whatsapp']['phone'] ?? ''); ?></h2><div class="text">© <?php echo date('Y'); ?> untuk <?php echo $brideName; ?> dan <?php echo $groomName; ?>. Dibuat oleh <a href="https://twitter.com/NathArchak" class="text">@NathArchak</a></div></footer>
    </div></div>
    <script src="<?php echo get_theme_asset_url($presetKey, 'original/main.js'); ?>"></script>
</body>
</html>
