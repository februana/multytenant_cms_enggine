<?php
if (!defined('THEME_HELPER_LOADED')) require_once __DIR__ . '/../../app/theme-helper.php';

$config = $config ?? [];
$presetKey = 'pawiwahan';
$visuals = theme_visual_values_for_config($config, $presetKey);
$assetUrl = static function (string $value, string $fallback = ''): string {
    $value = trim($value);
    if ($value === '') return $fallback;
    return theme_visual_public_path($value);
};
$sourceAsset = static function (string $path): string {
    return get_theme_asset_url('pawiwahan', $path);
};
$sourceHero = $sourceAsset('assets/hero-source.jpg');
$heroConfigured = trim((string)($visuals['hero_background'] ?? ''));
$heroUrl = $assetUrl($heroConfigured, $sourceHero);
$heroCss = theme_visual_css_url($heroUrl);
$welcomePath = trim((string)($visuals['welcome_background'] ?? ''));
$welcomeUrl = $assetUrl($welcomePath, $heroUrl);
$welcomeCss = theme_visual_css_url($welcomeUrl);
$sectionCss = static function (string $key) use ($visuals): string {
    $path = trim((string)($visuals[$key] ?? ''));
    return $path === '' ? 'none' : theme_visual_css_url(theme_visual_public_path($path));
};
$pawiwahanGalleryBg = $sectionCss('section_background_gallery');
$pawiwahanLocationBg = $sectionCss('section_background_location');
$pawiwahanGiftBg = $sectionCss('section_background_gift');
$pawiwahanMessagesBg = $sectionCss('section_background_messages');
$coverUrl = $assetUrl((string)($config['media']['cover'] ?? $config['media']['background_hero'] ?? ''), $heroUrl);
$brideUrl = $assetUrl((string)($config['media']['bride_photo'] ?? ''), $sourceHero);
$groomUrl = $assetUrl((string)($config['media']['groom_photo'] ?? ''), $sourceHero);
$guestName = function_exists('resolve_guest_name') ? resolve_guest_name($config) : '';
$guestLabel = escape_html($guestName !== '' ? $guestName : 'Bapak/Ibu/Saudara/i');
$brideName = escape_html((string)($config['wedding']['bride_name'] ?? 'Mempelai Wanita'));
$groomName = escape_html((string)($config['wedding']['groom_name'] ?? 'Mempelai Pria'));
$siteTitle = escape_html((string)($config['site']['title'] ?? 'Undangan Pawiwahan'));
$description = escape_html((string)($config['site']['description'] ?? $config['wedding']['opening_text'] ?? 'Undangan pernikahan Pawiwahan'));
$openingText = render_preserved_text((string)($config['wedding']['opening_text'] ?? 'Atas Asung Kertha Wara Nugraha Ida Sang Hyang Widhi Wasa/Tuhan Yang Maha Esa, kami bermaksud menyelenggarakan upacara Pawiwahan putra-putri kami.'));
$openingGreeting = render_preserved_text(theme_opening_greeting($config, 'pawiwahan'));
$quoteText = render_preserved_text((string)($config['wedding']['quote'] ?? 'Atas Kehadiran serta Do’a Restunya, kami sekeluarga mengucapkan terima kasih.'));
$closingText = render_preserved_text((string)($config['wedding']['closing_text'] ?? 'Om Shanti, Shanti, Shanti Om'));
$venue = escape_html((string)($config['location']['venue'] ?? ''));
$address = render_preserved_text((string)($config['location']['address'] ?? ''));
$mapsUrl = escape_html((string)($config['location']['maps_url'] ?? ''));
$mapsEmbed = escape_html((string)($config['location']['maps_embed'] ?? ''));
$akadDate = (string)($config['schedule']['akad_date'] ?? '');
$receptionDate = (string)($config['schedule']['reception_date'] ?? '');
$eventDate = $akadDate ?: $receptionDate;
$eventTime = escape_html((string)($config['schedule']['akad_time'] ?? $config['schedule']['reception_time'] ?? ''));
$receptionTime = escape_html((string)($config['schedule']['reception_time'] ?? ''));
$countdownTarget = escape_html((string)($config['schedule']['countdown_target'] ?? ''));
$formatDate = static function (string $value): string {
    $timestamp = $value !== '' ? strtotime($value) : false;
    return $timestamp ? date('l, j F Y', $timestamp) : '';
};
$eventDateDisplay = escape_html($formatDate($eventDate));
$calendarLink = escape_html(build_google_calendar_link($config));
$musicPath = trim((string)($config['media']['music'] ?? ''));
$musicUrl = $musicPath !== '' ? escape_html(public_path($musicPath)) : '';
$csrf = function_exists('get_csrf_token') ? get_csrf_token() : '';
$sectionEnabled = static function (string $sectionId) use ($config, $presetKey): bool {
    return theme_section_enabled($config, $presetKey, $sectionId);
};
$parent = static function (string $key) use ($config): string {
    return escape_html((string)($config['parents'][$key] ?? ''));
};
$galleryUrls = [];
foreach (get_gallery_items($config) as $item) {
    $path = is_array($item) ? (string)($item['filename'] ?? $item['path'] ?? '') : (string)$item;
    if ($path !== '') $galleryUrls[] = $assetUrl($path);
}
$galleryUrls = array_values(array_filter($galleryUrls));
$carouselGroups = array_chunk($galleryUrls, 3);
if (!$carouselGroups) $carouselGroups = [[]];
$customCss = function_exists('load_custom_css') ? load_custom_css() : '';
$accent = escape_html((string)($visuals['accent_color'] ?? '#d77fa1'));
$headingFont = escape_html((string)($visuals['heading_font'] ?? 'Tangerine, cursive'));
$bodyFont = escape_html((string)($visuals['body_font'] ?? 'Raleway, sans-serif'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?php echo $description; ?>">
  <meta name="csrf-token" content="<?php echo escape_html($csrf); ?>">
  <meta property="og:title" content="<?php echo $siteTitle; ?>">
  <meta property="og:description" content="<?php echo $description; ?>">
  <meta property="og:image" content="<?php echo escape_html($coverUrl); ?>">
  <link rel="manifest" href="<?php echo escape_html($sourceAsset('assets/images/icon/site.webmanifest')); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Beau+Rivage&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Tangerine&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
  <link rel="stylesheet" href="<?php echo escape_html(get_theme_asset_url($presetKey, 'style.css')); ?>">
  <style id="cms-pawiwahan-visual">
    :root { --pawiwahan-accent: <?php echo $accent; ?>; --pawiwahan-heading: <?php echo $headingFont; ?>; --pawiwahan-body: <?php echo $bodyFont; ?>; --pawiwahan-hero-bg: <?php echo $heroCss; ?>; --pawiwahan-welcome-bg: <?php echo $welcomeCss; ?>; --pawiwahan-gallery-bg: <?php echo $pawiwahanGalleryBg; ?>; --pawiwahan-location-bg: <?php echo $pawiwahanLocationBg; ?>; --pawiwahan-gift-bg: <?php echo $pawiwahanGiftBg; ?>; --pawiwahan-messages-bg: <?php echo $pawiwahanMessagesBg; ?>; }
    .pawiwahan-cover-override { background-image: linear-gradient(rgba(153,110,109,.65), rgba(153,110,109,.3)), var(--pawiwahan-hero-bg) !important; }
    #welcomeModal .hero { background-image: linear-gradient(rgba(153,110,109,.65), rgba(153,110,109,.3)), var(--pawiwahan-welcome-bg) !important; }
    #galeri,#lokasi,#gift,#pesan { background-size:cover; background-position:center; background-repeat:no-repeat; }
    #galeri { background-image:linear-gradient(rgba(255,255,255,.82),rgba(255,255,255,.88)),var(--pawiwahan-gallery-bg); }
    #lokasi { background-image:linear-gradient(rgba(255,255,255,.82),rgba(255,255,255,.88)),var(--pawiwahan-location-bg); }
    #gift { background-image:linear-gradient(rgba(255,255,255,.82),rgba(255,255,255,.88)),var(--pawiwahan-gift-bg); }
    #pesan { background-image:linear-gradient(rgba(255,255,255,.82),rgba(255,255,255,.88)),var(--pawiwahan-messages-bg); }
    h1, .parag { font-family: var(--pawiwahan-heading); }
    body { font-family: var(--pawiwahan-body); }
  </style>
  <?php if ($customCss !== ''): ?><style><?php echo $customCss; ?></style><?php endif; ?>
  <title><?php echo $siteTitle; ?></title>
</head>
<body id="home" data-pawiwahan-root="1" data-pawiwahan-countdown-target="<?php echo $countdownTarget; ?>">
<header>
  <nav class="navbar navbar-expand-lg navbar-dark shadow-sm fixed-top">
    <div class="container">
      <a class="navbar-brand" href="#home">Pawiwahan</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <?php foreach ([['home', '#home', 'Home'], ['about', '#about', 'About'], ['gallery', '#galeri', 'Galeri'], ['location', '#lokasi', 'Lokasi Upacara'], ['messages', '#pesan', 'Pesan & Doa']] as $nav): if (!$sectionEnabled($nav[0])) continue; ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo $nav[1]; ?>"><?php echo $nav[2]; ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </nav>
</header>
<?php if ($sectionEnabled('home')): ?>
<div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-indicators"><button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button><button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1" aria-label="Slide 2"></button><button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2" aria-label="Slide 3"></button></div>
  <div class="carousel-inner">
    <?php foreach ([$coverUrl, $brideUrl, $groomUrl] as $index => $image): ?><div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>"><img src="<?php echo escape_html($image); ?>" class="d-block w-100 pawiwahan-media-cover" alt="Pawiwahan slide <?php echo $index + 1; ?>"><div class="carousel-caption d-md-block text-info"><h5>Pawiwahan</h5><p><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></p></div></div><?php endforeach; ?>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button>
  <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>
</div>
<?php endif; ?>
<main>
<?php if ($sectionEnabled('about')): ?><section class="card-a" id="about"><div class="container"><div class="row text-center mb-3"><div class="col mt-3"><h2><?php echo $openingGreeting; ?></h2></div></div><div class="row justify-content-center fs-5 text-center"><div class="col-md-6"><p class="fst-italic"><?php echo $openingText; ?></p></div></div></div></section><?php endif; ?>
<?php if ($sectionEnabled('couple')): ?>
<section class="jumbotron text-center card mpl" id="mpl"><div class="container"><div class="row justify-content-around"><div class="col-md-4 mb-3"><img src="<?php echo escape_html($groomUrl); ?>" width="200" alt="Foto <?php echo $groomName; ?>" class="rounded-circle img-thumbnail"><h1 class="display-12 text-center"><?php echo $groomName; ?></h1><h2 class="fs-5">Putra dari pasangan</h2><h2 class="fs-5"><?php echo $parent('groom_father'); ?> &amp; <?php echo $parent('groom_mother'); ?></h2></div></div></div></section>
<section class="jumbotron-h text-center"><div class="container"><div class="row justify-content-around"><div class="col-md-4"><i class="bi bi-heart-fill text-pink-400" style="font-size:5rem"></i></div></div></div></section>
<section class="jumbotron text-center card" id="mpw"><div class="container"><div class="row justify-content-around"><div class="col-md-4"><img src="<?php echo escape_html($brideUrl); ?>" width="200" alt="Foto <?php echo $brideName; ?>" class="rounded-circle img-thumbnail"><h1 class="display-12 text-center"><?php echo $brideName; ?></h1><h2 class="fs-5">Putri dari pasangan</h2><h2 class="fs-5"><?php echo $parent('bride_father'); ?> &amp; <?php echo $parent('bride_mother'); ?></h2></div></div></div></section>
<?php endif; ?>
<?php if ($sectionEnabled('event')): ?>
<section class="jumbotron text-center" id="calender"><div class="container"><div class="row justify-content-center text-center"><div class="col-md-6 mt-3"><p class="fs-5">Merupakan suatu kehormatan dan kebahagiaan bagi kami sekeluarga, apabila Bapak/Ibu/Saudara/i berkenan hadir dan memberikan doa restu pada:</p><i class="bi bi-calendar-heart" style="font-size:4rem"></i><p class="fs-5"><?php echo $eventDateDisplay; ?></p><p class="fs-5"><?php echo $eventTime; ?><?php echo $receptionTime !== '' ? ' — ' . $receptionTime : ''; ?></p><p class="fs-5"><?php echo $venue; ?><br><?php echo $address; ?></p><?php if ($mapsUrl !== ''): ?><a href="<?php echo $mapsUrl; ?>" target="_blank" rel="noopener" class="btn btn-outline-success fw-bold"><i class="bi bi-geo-alt-fill" style="font-size:1rem"></i> Google Maps</a><?php endif; ?></div></div><div class="row justify-content-around text-center"><div class="col-md-6 mt-3" id="countdown"><ul id="hitungmundur"><li><span class="days">00</span><p class="days_text">Hari</p></li><li class="separator">:</li><li><span class="hours">00</span><p class="hours_text">Jam</p></li><li class="separator">:</li><li><span class="minutes">00</span><p class="minutes_text">Menit</p></li><li class="separator">:</li><li><span class="seconds">00</span><p class="seconds_text">Detik</p></li></ul><p id="pawiwahan-countdown-notice" class="small"></p></div></div><p><a href="<?php echo $calendarLink; ?>" target="_blank" rel="noopener">Tambahkan ke kalender</a></p></div></section>
<?php endif; ?>
<?php if ($sectionEnabled('protocol')): ?><section class="jumbotron text-center" id="protokol"><div class="container"><div class="row justify-content-around fs-5"><div class="col-md-6 mb-3"><div class="alert alert-danger" role="alert">Mohon hadir dengan tertib dan mengikuti ketentuan acara yang disampaikan oleh keluarga.</div></div></div></div></section><?php endif; ?>
<?php if ($sectionEnabled('gallery')): ?>
<section class="card" id="galeri"><div class="container col-md-6"><div class="row text-center mb-3"><div class="col"><h2>Our Stories</h2></div></div><?php if (!$galleryUrls): ?><p class="pawiwahan-gallery-empty text-center">Belum ada foto galeri yang ditambahkan.</p><?php else: foreach ($carouselGroups as $groupIndex => $group): ?><div class="row" style="display:block"><div class="col-md mb-3"><div class="card-"><div id="carouselExampleControls<?php echo $groupIndex; ?>" class="carousel slide" data-bs-ride="carousel"><div class="carousel-inner"><?php foreach ($group as $imageIndex => $image): ?><div class="carousel-item <?php echo $imageIndex === 0 ? 'active' : ''; ?>"><img src="<?php echo escape_html($image); ?>" class="d-block w-100" alt="Foto galeri <?php echo $groupIndex + 1; ?>-<?php echo $imageIndex + 1; ?>" loading="lazy"></div><?php endforeach; ?></div><button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleControls<?php echo $groupIndex; ?>" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button><button class="carousel-control-next" type="button" data-bs-target="#carouselExampleControls<?php echo $groupIndex; ?>" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button></div></div></div></div><?php endforeach; endif; ?></div></section>
<?php endif; ?>
<?php if ($sectionEnabled('location')): ?><section id="lokasi" class="card"><div class="container col-md-6"><div class="row text-center mb-3 mt-3"><div class="col"><h2>Lokasi Upacara</h2></div></div><?php if ($mapsEmbed !== ''): ?><div class="ratio ratio-1x1"><iframe src="<?php echo $mapsEmbed; ?>" title="Lokasi upacara" loading="lazy"></iframe></div><?php endif; ?><div class="row text-center mb-3 mt-3"><div class="col"><h5 class="card-title"><?php echo $venue; ?></h5><p><?php echo $address; ?></p><?php if ($mapsUrl !== ''): ?><a href="<?php echo $mapsUrl; ?>" target="_blank" rel="noopener" class="btn btn-outline-success"><i class="bi bi-geo-alt-fill" style="font-size:1rem"></i> View Google Maps</a><?php endif; ?></div></div></div></section><?php endif; ?>
<?php if ($sectionEnabled('gift')): ?>
<section id="gift" class="card"><div class="container col-md-6"><div class="row text-center mb-3 mt-3"><div class="col"><h2>Angpao cashless</h2><p>Bagi keluarga dan sahabat yang ingin memberikan hadiah, silakan klik tombol di bawah. Terima kasih.</p></div></div><div class="row text-center mb-3 mt-0"><div class="col"><button id="pawiwahan-gift-trigger" type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#pawiwahanGiftModal" aria-controls="pawiwahanGiftModal" aria-expanded="false"><i class="bi bi-gift-fill"></i> Angpao cashless</button></div></div></div></section>
<div class="modal fade" id="pawiwahanGiftModal" tabindex="-1" aria-labelledby="pawiwahanGiftModalLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="pawiwahanGiftModalLabel">Jika memberi adalah ungkapan tanda kasih Anda, Anda dapat mengirim amplop digital secara transfer pada akun di bawah ini.</h5></div><div class="modal-body"><p><?php echo escape_html((string)($config['gift']['bank'] ?? '')); ?> — <?php echo escape_html((string)($config['gift']['account_holder'] ?? '')); ?></p><div class="pawiwahan-gift-account"><div class="pawiwahan-gift-number" id="pawiwahanGiftNumber"><?php echo escape_html((string)($config['gift']['account_number'] ?? '')); ?></div><button id="pawiwahanGiftCopy" type="button" class="material-symbols-outlined" data-copy="<?php echo escape_html((string)($config['gift']['account_number'] ?? '')); ?>" aria-label="Salin nomor rekening"><i class="bi bi-clipboard-check"></i></button><span id="pawiwahanGiftCopyStatus" class="popuptext" role="status" aria-live="polite"></span></div><p><?php echo escape_html((string)($config['gift']['e_wallet_label'] ?? '')); ?>: <?php echo escape_html((string)($config['gift']['e_wallet_number'] ?? '')); ?></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
<?php endif; ?>
<?php if ($sectionEnabled('messages')): ?>
<section id="pesan" class="card"><div class="container col-md-6"><div class="row text-center mb-3 mt-3"><div class="col"><h2>Pesan &amp; Doa</h2></div></div><div class="col-md"><div class="card-body"><h5 class="card-title fst-italic">Doa restu Anda merupakan karunia yang sangat berarti bagi kami.</h5><?php if ($sectionEnabled('messages')): ?><form id="pawiwahan-rsvp-form" class="pawiwahan-rsvp-form"><input type="hidden" name="csrf_token" value="<?php echo escape_html($csrf); ?>"><label>Nama<input type="text" name="nama" required maxlength="80"></label><label>Kehadiran<select name="status" required><option value="Hadir">Hadir</option><option value="Tidak Hadir">Tidak Hadir</option></select></label><label>Pesan<textarea name="ucapan" rows="4" maxlength="500"></textarea></label><input type="text" name="website" autocomplete="off" tabindex="-1" aria-hidden="true" style="display:none"><button type="submit" class="btn btn-outline-success">Kirim Pesan</button><p id="pawiwahan-form-message" role="status" aria-live="polite"></p></form><?php endif; ?></div></div></div></section>
<?php endif; ?>
</main>
<footer><nav class="footer"><div class="container-fluid">&copy; <?php echo date('Y'); ?> Created with <span aria-hidden="true">♥</span> by <a href="https://github.com/parta99/pawiwahan" class="text-white fw-bold" style="text-decoration:none">DE Juna</a><div class="pawiwahan-source-credit">Presentation adapted from the Pawiwahan source template.</div></div></nav></footer>
<div id="button"></div>
<?php if ($musicUrl !== ''): ?><audio autoplay id="my_audio" loop="loop"><source src="<?php echo $musicUrl; ?>" type="audio/mpeg">Your browser does not support audio.</audio><div class="float-container"><input type="checkbox" class="playpause-chk-icon" id="chkbx2" onchange="toggleAudio(this)"><label for="chkbx2"></label></div><?php endif; ?>
<div id="welcomeModal" class="modal1"><div class="hero pawiwahan-cover-override" style="height:100vh"><div class="container1"><h1 class="subtitle">Pawiwahan</h1><h2 class="title"><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></h2><p class="card-text">Yth. Bapak/Ibu/Saudara/i</p><?php if ($guestName !== ''): ?><h3 id="to" class="pawiwahan-guest-name"><?php echo $guestLabel; ?></h3><?php else: ?><h3 id="yth" class="pawiwahan-guest-name"></h3><?php endif; ?><h1 id="invite" style="font-size:x-small;padding-left:20px;padding-right:20px">Tanpa mengurangi rasa hormat, kami turut mengundang Anda untuk hadir pada acara pernikahan kami.</h1><button style="color:#e8ebee;box-sizing:border-box;box-shadow:4px 4px 11px #caced1,-4px -4px 11px white;font-weight:bold;padding:8px;background-color:#ec7272;border:0;border-radius:5px" onclick="closeWelcomeModal()"><i class="bi bi-envelope-heart"></i> Buka Undangan</button></div></div></div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo escape_html(get_theme_asset_url($presetKey, 'assets/js/jquery.countdown.min.js')); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-gtEjrD/SeCtmISJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous"></script>
<script src="<?php echo escape_html(get_theme_asset_url($presetKey, 'script.js')); ?>"></script>
</body>
</html>
