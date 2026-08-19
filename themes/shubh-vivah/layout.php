<?php
if (!defined('THEME_HELPER_LOADED')) require_once __DIR__ . '/../../app/theme-helper.php';

$config = $config ?? [];
$presetKey = 'shubh-vivah';
$visuals = theme_visual_values_for_config($config, $presetKey);
$sourceAsset = static fn(string $path): string => get_theme_asset_url('shubh-vivah', $path);
$mediaUrl = static function (string $value, string $fallback = ''): string {
    $value = trim($value);
    return $value === '' ? $fallback : theme_visual_public_path($value);
};
$brideNameRaw = (string)($config['wedding']['bride_name'] ?? 'Mempelai Wanita');
$groomNameRaw = (string)($config['wedding']['groom_name'] ?? 'Mempelai Pria');
$brideName = escape_html($brideNameRaw);
$groomName = escape_html($groomNameRaw);
$siteTitle = escape_html((string)($config['site']['title'] ?? ($brideNameRaw . ' & ' . $groomNameRaw)));
$description = escape_html((string)($config['site']['description'] ?? 'Undangan pernikahan digital'));
$guestName = function_exists('resolve_guest_name') ? resolve_guest_name($config) : '';
$guestLabel = escape_html($guestName !== '' ? $guestName : 'Bapak/Ibu/Saudara/i');
$openingGreeting = render_preserved_text(theme_opening_greeting($config, $presetKey));
$openingText = render_preserved_text((string)($config['wedding']['opening_text'] ?? 'Dengan penuh sukacita, kami mengundang Anda untuk hadir dan memberikan doa restu.'));
$quoteText = render_preserved_text((string)($config['wedding']['quote'] ?? 'Kehadiran dan doa restu Anda merupakan kebahagiaan bagi kami.'));
$closingText = render_preserved_text((string)($config['wedding']['closing_text'] ?? 'Terima kasih atas doa dan restu yang diberikan.'));
$venue = escape_html((string)($config['location']['venue'] ?? 'Tempat acara'));
$address = render_preserved_text((string)($config['location']['address'] ?? ''));
$mapsUrl = escape_html((string)($config['location']['maps_url'] ?? ''));
$akadDate = (string)($config['schedule']['akad_date'] ?? '');
$receptionDate = (string)($config['schedule']['reception_date'] ?? '');
$eventDate = $akadDate !== '' ? $akadDate : $receptionDate;
$akadTime = escape_html((string)($config['schedule']['akad_time'] ?? ''));
$receptionTime = escape_html((string)($config['schedule']['reception_time'] ?? ''));
$countdownTarget = escape_html((string)($config['schedule']['countdown_target'] ?? ''));
$csrf = function_exists('get_csrf_token') ? get_csrf_token() : '';
$sectionEnabled = static fn(string $id): bool => theme_section_enabled($config, $presetKey, $id);
$formatDate = static function (string $value): string {
    $timestamp = $value !== '' ? strtotime($value) : false;
    if (!$timestamp) return '';
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return date('j', $timestamp) . ' ' . $months[(int)date('n', $timestamp)] . ' ' . date('Y', $timestamp);
};
$eventDateDisplay = escape_html($formatDate($eventDate));
$calendarLink = escape_html(build_google_calendar_link($config));
$heroFallback = $sourceAsset('assets/img/source-wedding-card.png');
$heroPath = (string)($visuals['hero_background'] ?? '');
$heroUrl = $mediaUrl($heroPath, $heroFallback);
$heroCss = theme_visual_css_url($heroUrl);
$leftOrnament = $mediaUrl((string)($visuals['ornament_left'] ?? ''), $sourceAsset('assets/img/source-left.png'));
$rightOrnament = $mediaUrl((string)($visuals['ornament_right'] ?? ''), $sourceAsset('assets/img/source-right.png'));
$leftCss = theme_visual_css_url($leftOrnament);
$rightCss = theme_visual_css_url($rightOrnament);
$accent = escape_html((string)($visuals['accent_color'] ?? '#a24747'));
$headingColor = escape_html((string)($visuals['heading_color'] ?? '#392521'));
$textColor = escape_html((string)($visuals['text_color'] ?? '#392521'));
$mutedColor = escape_html((string)($visuals['muted_color'] ?? '#85665f'));
$linkColor = escape_html((string)($visuals['link_color'] ?? '#a24747'));
$headingFont = escape_html((string)($visuals['heading_font'] ?? 'Dancing Script, cursive'));
$bodyFont = escape_html((string)($visuals['body_font'] ?? 'Arvo, Georgia, serif'));
$overlay = escape_html((string)($visuals['hero_overlay'] ?? '0.10'));
$sectionCss = static function (string $key) use ($visuals): string {
    $path = trim((string)($visuals[$key] ?? ''));
    return $path === '' ? 'none' : theme_visual_css_url(theme_visual_public_path($path));
};
$homeBg = $sectionCss('section_background_home');
$eventBg = $sectionCss('section_background_event');
$galleryBg = $sectionCss('section_background_gallery');
$rsvpBg = $sectionCss('section_background_rsvp');
$galleryUrls = [];
foreach (get_gallery_items($config) as $item) {
    $path = is_array($item) ? (string)($item['filename'] ?? $item['path'] ?? '') : (string)$item;
    if ($path !== '') $galleryUrls[] = $mediaUrl($path);
}
$galleryUrls = array_values(array_filter($galleryUrls));
$musicPath = trim((string)($config['media']['music'] ?? ''));
$musicUrl = $musicPath !== '' ? $mediaUrl($musicPath) : $sourceAsset('assets/audio/source-background.mp3');
$customCss = function_exists('load_custom_css') ? load_custom_css() : '';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?php echo $description; ?>">
  <meta name="csrf-token" content="<?php echo escape_html($csrf); ?>">
  <meta property="og:title" content="<?php echo $siteTitle; ?>">
  <meta property="og:description" content="<?php echo $description; ?>">
  <meta property="og:image" content="<?php echo escape_html($heroUrl); ?>">
  <title><?php echo $siteTitle; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="<?php echo escape_html(theme_google_font_stylesheet_url()); ?>" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo escape_html(get_theme_asset_url($presetKey, 'fidelity-adapter.css')); ?>">
  <style id="cms-shubh-vivah-visual">
    :root{--shubh-accent:<?php echo $accent; ?>;--shubh-heading-color:<?php echo $headingColor; ?>;--shubh-ink:<?php echo $textColor; ?>;--shubh-muted:<?php echo $mutedColor; ?>;--shubh-link:<?php echo $linkColor; ?>;--shubh-heading:<?php echo $headingFont; ?>;--shubh-body:<?php echo $bodyFont; ?>;--shubh-hero-bg:<?php echo $heroCss; ?>;--shubh-left:<?php echo $leftCss; ?>;--shubh-right:<?php echo $rightCss; ?>;--shubh-home-bg:<?php echo $homeBg; ?>;--shubh-event-bg:<?php echo $eventBg; ?>;--shubh-gallery-bg:<?php echo $galleryBg; ?>;--shubh-rsvp-bg:<?php echo $rsvpBg; ?>;--shubh-overlay:<?php echo $overlay; ?>}
    .shubh-home{background-image:linear-gradient(rgba(255,250,245,.76),rgba(255,250,245,.86)),var(--shubh-home-bg);background-size:cover;background-position:center}.shubh-event,.shubh-gallery,.shubh-rsvp{background-size:cover;background-position:center;background-repeat:no-repeat}.shubh-event{background-image:linear-gradient(rgba(255,250,245,.86),rgba(255,250,245,.90)),var(--shubh-event-bg)}.shubh-gallery{background-image:linear-gradient(rgba(255,250,245,.86),rgba(255,250,245,.90)),var(--shubh-gallery-bg)}.shubh-rsvp{background-image:linear-gradient(rgba(255,250,245,.86),rgba(255,250,245,.90)),var(--shubh-rsvp-bg)}
  </style>
  <?php if ($customCss !== ''): ?><style><?php echo $customCss; ?></style><?php endif; ?>
</head>
<body class="shubh-vivah" data-countdown-target="<?php echo $countdownTarget; ?>">
  <div class="shubh-decoration shubh-decoration-left" aria-hidden="true"></div>
  <div class="shubh-decoration shubh-decoration-right" aria-hidden="true"></div>
  <?php if ($sectionEnabled('home')): ?>
  <main id="shubh-home" class="shubh-home" data-section="home">
    <section class="shubh-card shubh-card-hero" aria-labelledby="shubh-title">
      <p class="shubh-greeting"><?php echo $openingGreeting; ?></p>
      <p class="shubh-kicker">Kami Mengundang Anda</p>
      <h1 id="shubh-title"><span><?php echo $brideName; ?></span><em>dan</em><span><?php echo $groomName; ?></span></h1>
      <p class="shubh-quote"><?php echo $quoteText; ?></p>
      <div class="shubh-guest"><span>Kepada Yth.</span><strong><?php echo $guestLabel; ?></strong></div>
      <p class="shubh-date-place"><span><?php echo $eventDateDisplay; ?></span><?php echo $venue !== '' ? ' · ' . $venue : ''; ?></p>
      <div id="shubh-countdown" class="shubh-countdown" data-countdown="<?php echo $countdownTarget; ?>" aria-label="Hitung mundur acara">
        <div><strong data-unit="days">00</strong><span>Hari</span></div><i>:</i><div><strong data-unit="hours">00</strong><span>Jam</span></div><i>:</i><div><strong data-unit="minutes">00</strong><span>Menit</span></div><i>:</i><div><strong data-unit="seconds">00</strong><span>Detik</span></div>
      </div>
      <a class="shubh-cta" href="#shubh-event">Buka Undangan</a>
      <?php if ($musicUrl !== ''): ?><button type="button" class="shubh-music" id="shubhMusicButton" aria-pressed="false">Putar Musik</button><audio id="shubhMusic" loop preload="none"><source src="<?php echo escape_html($musicUrl); ?>" type="audio/mpeg"></audio><?php endif; ?>
    </section>
    <p class="shubh-blessing"><?php echo $openingText; ?></p>
  </main>
  <?php endif; ?>
  <div class="shubh-sections">
    <?php if ($sectionEnabled('event')): ?><section id="shubh-event" class="shubh-section shubh-event" data-section="event"><div class="shubh-section-inner"><p class="shubh-section-label">Menuju Hari Bahagia</p><h2>Acara Pernikahan</h2><div class="shubh-event-grid"><article><span>Akad Nikah</span><strong><?php echo $eventDateDisplay; ?></strong><p><?php echo $akadTime; ?></p></article><article><span>Resepsi</span><strong><?php echo $eventDateDisplay; ?></strong><p><?php echo $receptionTime; ?></p></article><article><span>Lokasi Acara</span><strong><?php echo $venue; ?></strong><p><?php echo $address; ?></p><?php if ($mapsUrl !== ''): ?><a href="<?php echo $mapsUrl; ?>" target="_blank" rel="noopener">Buka di Google Maps</a><?php endif; ?></article></div><?php if ($calendarLink !== ''): ?><a class="shubh-secondary-cta" href="<?php echo $calendarLink; ?>" target="_blank" rel="noopener">Tambahkan ke Kalender</a><?php endif; ?></div></section><?php endif; ?>
    <?php if ($sectionEnabled('gallery')): ?><section id="shubh-gallery" class="shubh-section shubh-gallery" data-section="gallery"><div class="shubh-section-inner"><p class="shubh-section-label">Momen Bahagia</p><h2>Galeri</h2><?php if (!$galleryUrls): ?><p class="shubh-empty">Belum ada foto galeri yang ditambahkan.</p><?php else: ?><div class="shubh-gallery-grid"><?php foreach ($galleryUrls as $index => $url): ?><figure><img src="<?php echo escape_html($url); ?>" alt="Foto galeri <?php echo $index + 1; ?>" loading="lazy" decoding="async"></figure><?php endforeach; ?></div><?php endif; ?></div></section><?php endif; ?>
    <?php if ($sectionEnabled('rsvp')): ?><section id="shubh-rsvp" class="shubh-section shubh-rsvp" data-section="rsvp"><div class="shubh-section-inner"><p class="shubh-section-label">Kehadiran Anda</p><h2>Konfirmasi Kehadiran</h2><p><?php echo $quoteText; ?></p><form id="shubh-rsvp-form" class="shubh-form"><input type="hidden" name="csrf_token" value="<?php echo escape_html($csrf); ?>"><label>Nama<input type="text" name="nama" placeholder="Nama Anda" required maxlength="80"></label><label>Kehadiran<select name="status" required><option value="Hadir">Hadir</option><option value="Tidak Hadir">Tidak Hadir</option></select></label><label>Ucapan<textarea name="ucapan" rows="4" maxlength="500" placeholder="Tulis ucapan dan doa"></textarea></label><input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="display:none"><button type="submit">Kirim Konfirmasi</button><p id="shubh-form-message" role="status" aria-live="polite"></p></form></div></section><?php endif; ?>
  </div>
  <footer class="shubh-footer"><p><?php echo $closingText; ?></p><p>© <?php echo date('Y'); ?> <?php echo $brideName; ?> &amp; <?php echo $groomName; ?></p></footer>
  <script>
  (()=>{const body=document.body;const target=document.querySelector('[data-countdown]')?.dataset.countdown;const units={days:86400,hours:3600,minutes:60,seconds:1};const tick=()=>{const t=target?Date.parse(target):NaN;if(!Number.isFinite(t))return;let remaining=Math.max(0,Math.floor((t-Date.now())/1000));Object.entries(units).forEach(([key,size])=>{const el=document.querySelector('[data-unit="'+key+'"]');if(el){const value=key==='days'?Math.floor(remaining/size):Math.floor(remaining/size)% (key==='hours'?24:key==='minutes'?60:60);el.textContent=String(value).padStart(2,'0');}})};tick();window.setInterval(tick,1000);const button=document.getElementById('shubhMusicButton');const audio=document.getElementById('shubhMusic');if(button&&audio){button.addEventListener('click',()=>{if(audio.paused){audio.play().then(()=>{button.textContent='Jeda Musik';button.setAttribute('aria-pressed','true')}).catch(()=>{});}else{audio.pause();button.textContent='Putar Musik';button.setAttribute('aria-pressed','false')}})};document.querySelectorAll('a[href^="#"]').forEach(a=>a.addEventListener('click',e=>{const el=document.querySelector(a.getAttribute('href'));if(el){e.preventDefault();el.scrollIntoView({behavior:'smooth',block:'start'})}}));const form=document.getElementById('shubh-rsvp-form');const message=document.getElementById('shubh-form-message');if(form&&message){form.addEventListener('submit',e=>{e.preventDefault();message.textContent='Mengirim...';fetch('save.php',{method:'POST',body:new FormData(form),credentials:'same-origin'}).then(response=>response.json()).then(data=>{message.textContent=data.message||(data.success?'Terima kasih, konfirmasi berhasil dikirim.':'Gagal mengirim konfirmasi.');if(data.success)form.reset()}).catch(()=>{message.textContent='Gagal mengirim. Silakan coba lagi.'})})}})();
  </script>
</body>
</html>
