<?php
if (!defined('THEME_HELPER_LOADED')) require_once __DIR__ . '/../../app/theme-helper.php';

$config = $config ?? [];
$presetKey = 'yami-buzzy';
$visuals = theme_visual_values_for_config($config, $presetKey);
$sourceAsset = static fn(string $path): string => get_theme_asset_url('yami-buzzy', $path);
$mediaUrl = static function (string $value, string $fallback = ''): string {
    $value = trim($value);
    return $value === '' ? $fallback : theme_visual_public_path($value);
};
$esc = static fn($value): string => escape_html((string)$value);
$brideNameRaw = (string)($config['wedding']['bride_name'] ?? 'Mempelai Wanita');
$groomNameRaw = (string)($config['wedding']['groom_name'] ?? 'Mempelai Pria');
$brideName = $esc($brideNameRaw);
$groomName = $esc($groomNameRaw);
$siteTitle = $esc((string)($config['site']['title'] ?? ($brideNameRaw . ' & ' . $groomNameRaw)));
$description = $esc((string)($config['site']['description'] ?? 'Undangan pernikahan digital'));
$guestName = function_exists('resolve_guest_name') ? resolve_guest_name($config) : '';
$guestLabel = $esc($guestName !== '' ? $guestName : 'Bapak/Ibu/Saudara/i');
$openingGreeting = render_preserved_text(theme_opening_greeting($config, $presetKey));
$openingText = render_preserved_text((string)($config['wedding']['opening_text'] ?? 'Dengan penuh sukacita, kami mengundang Anda untuk hadir di hari bahagia kami.'));
$quoteText = render_preserved_text((string)($config['wedding']['quote'] ?? 'Kehadiran dan doa restu Anda adalah kebahagiaan bagi kami.'));
$closingText = render_preserved_text((string)($config['wedding']['closing_text'] ?? 'Terima kasih telah menjadi bagian dari hari bahagia kami.'));
$venue = $esc($config['location']['venue'] ?? 'Tempat acara');
$address = render_preserved_text((string)($config['location']['address'] ?? ''));
$mapsUrl = $esc($config['location']['maps_url'] ?? '');
$mapsEmbed = $esc($config['location']['maps_embed'] ?? '');
$akadDate = (string)($config['schedule']['akad_date'] ?? '');
$receptionDate = (string)($config['schedule']['reception_date'] ?? '');
$eventDate = $akadDate !== '' ? $akadDate : $receptionDate;
$akadTime = $esc($config['schedule']['akad_time'] ?? '');
$receptionTime = $esc($config['schedule']['reception_time'] ?? '');
$countdownTarget = $esc($config['schedule']['countdown_target'] ?? '');
$csrf = function_exists('get_csrf_token') ? get_csrf_token() : '';
$sectionEnabled = static fn(string $id): bool => theme_section_enabled($config, $presetKey, $id);
$formatDate = static function (string $value): string {
    $timestamp = $value !== '' ? strtotime($value) : false;
    if (!$timestamp) return '';
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return date('j', $timestamp) . ' ' . $months[(int)date('n', $timestamp)] . ' ' . date('Y', $timestamp);
};
$eventDateDisplay = $esc($formatDate($eventDate));
$calendarLink = $esc(build_google_calendar_link($config));
$heroFallback = $sourceAsset('assets/pic/source-hero.webp');
$couplePhotoPath = trim((string)($config['media']['couple_photo'] ?? ''));
$bridePhotoPath = trim((string)($config['media']['bride_photo'] ?? '')) ?: $couplePhotoPath;
$groomPhotoPath = trim((string)($config['media']['groom_photo'] ?? '')) ?: $couplePhotoPath;
$bridePhotoUrl = $bridePhotoPath !== '' ? $mediaUrl($bridePhotoPath) : '';
$groomPhotoUrl = $groomPhotoPath !== '' ? $mediaUrl($groomPhotoPath) : '';
$heroPath = (string)($visuals['hero_background'] ?? '');
$heroUrl = $mediaUrl($heroPath, $mediaUrl((string)($config['media']['cover'] ?? ''), $heroFallback));
$heroCss = theme_visual_css_url($heroUrl);
$welcomePath = (string)($visuals['welcome_background'] ?? '');
$welcomeUrl = $mediaUrl($welcomePath, $heroUrl);
$welcomeCss = theme_visual_css_url($welcomeUrl);
$accent = $esc($visuals['accent_color'] ?? '#ad7c69');
$headingColor = $esc($visuals['heading_color'] ?? '#343039');
$textColor = $esc($visuals['text_color'] ?? '#343039');
$mutedColor = $esc($visuals['muted_color'] ?? '#82727a');
$linkColor = $esc($visuals['link_color'] ?? '#ad7c69');
$headingFont = $esc($visuals['heading_font'] ?? 'Gilroy, Arial, sans-serif');
$bodyFont = $esc($visuals['body_font'] ?? 'Gilroy, Arial, sans-serif');
$overlay = $esc($visuals['hero_overlay'] ?? '0.28');
$sectionCss = static function (string $path): string {
    $path = trim($path);
    return $path === '' ? 'none' : theme_visual_css_url(theme_visual_public_path($path));
};
$coupleCss = $sectionCss((string)($visuals['section_background_couple'] ?? ''));
$eventCss = $sectionCss((string)($visuals['section_background_event'] ?? ''));
$storyCss = $sectionCss((string)($visuals['section_background_story'] ?? ''));
$galleryCss = $sectionCss((string)($visuals['section_background_gallery'] ?? ''));
$videoCss = $sectionCss((string)($visuals['section_background_video'] ?? ''));
$giftCss = $sectionCss((string)($visuals['section_background_gift'] ?? ''));
$invitationCss = $sectionCss((string)($visuals['section_background_invitation'] ?? ''));
$rsvpCss = $sectionCss((string)($visuals['section_background_rsvp'] ?? ''));
$closingCss = $sectionCss((string)($visuals['section_background_closing'] ?? ''));
$homeCss = $sectionCss((string)($visuals['section_background_home'] ?? ''));
$musicPath = trim((string)($config['media']['music'] ?? ''));
$musicUrl = $musicPath !== '' ? $mediaUrl($musicPath) : '';
$videoPath = trim((string)($config['media']['love_story_video'] ?? $config['media']['video'] ?? $config['media']['invitation_video'] ?? ''));
$videoUrl = $videoPath !== '' ? $mediaUrl($videoPath) : '';
$dresscodeEnabled = !empty($config['dresscode']['enabled']);
$dresscodeTitle = $esc((string)($config['dresscode']['title'] ?? 'Dress Code'));
$dresscodeColor = render_preserved_text((string)($config['dresscode']['color'] ?? ''));
$dresscodeRule = render_preserved_text((string)($config['dresscode']['rule'] ?? ''));
$dresscodeDescription = render_preserved_text((string)($config['dresscode']['description'] ?? ''));
$qrisPath = trim((string)($config['gift']['qris_image'] ?? ''));
$qrisUrl = $qrisPath !== '' ? $mediaUrl($qrisPath) : '';
$stories = is_array($config['love_story']['items'] ?? null) ? $config['love_story']['items'] : [];
$galleryUrls = [];
foreach (get_gallery_items($config) as $item) {
    $path = is_array($item) ? (string)($item['filename'] ?? $item['path'] ?? '') : (string)$item;
    if ($path !== '') $galleryUrls[] = $mediaUrl($path);
}
$galleryUrls = array_values(array_filter($galleryUrls));
$customCss = function_exists('load_custom_css') ? load_custom_css() : '';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?php echo $description; ?>">
  <meta name="csrf-token" content="<?php echo $esc($csrf); ?>">
  <meta property="og:title" content="<?php echo $siteTitle; ?>">
  <meta property="og:description" content="<?php echo $description; ?>">
  <meta property="og:image" content="<?php echo $esc($heroUrl); ?>">
  <title><?php echo $siteTitle; ?></title>
  <link rel="icon" href="<?php echo $esc($sourceAsset('assets/pic/favicon.png')); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="<?php echo $esc(theme_google_font_stylesheet_url()); ?>">
  <link rel="stylesheet" href="<?php echo $esc(get_theme_asset_url($presetKey, 'fidelity-adapter.css')); ?>">
  <style id="cms-yami-buzzy-visual">
    :root{--yami-accent:<?php echo $accent; ?>;--yami-heading-color:<?php echo $headingColor; ?>;--yami-ink:<?php echo $textColor; ?>;--yami-muted:<?php echo $mutedColor; ?>;--yami-link:<?php echo $linkColor; ?>;--yami-heading:<?php echo $headingFont; ?>;--yami-body:<?php echo $bodyFont; ?>;--yami-hero-bg:<?php echo $heroCss; ?>;--yami-welcome-bg:<?php echo $welcomeCss; ?>;--yami-home-bg:<?php echo $homeCss; ?>;--yami-couple-bg:<?php echo $coupleCss; ?>;--yami-event-bg:<?php echo $eventCss; ?>;--yami-story-bg:<?php echo $storyCss; ?>;--yami-gallery-bg:<?php echo $galleryCss; ?>;--yami-video-bg:<?php echo $videoCss; ?>;--yami-gift-bg:<?php echo $giftCss; ?>;--yami-invitation-bg:<?php echo $invitationCss; ?>;--yami-rsvp-bg:<?php echo $rsvpCss; ?>;--yami-closing-bg:<?php echo $closingCss; ?>;--yami-overlay:<?php echo $overlay; ?>}
    .yami-hero{background-image:linear-gradient(rgba(35,25,28,var(--yami-overlay)),rgba(35,25,28,calc(var(--yami-overlay) + .18))),var(--yami-hero-bg)}
    .yami-couple{background-image:linear-gradient(135deg,rgba(255,255,255,.82),rgba(247,239,234,.78)),var(--yami-couple-bg)}
    .yami-event{background-image:linear-gradient(rgba(243,232,226,.84),rgba(243,232,226,.84)),var(--yami-event-bg)}
    .yami-story{background-image:linear-gradient(rgba(255,253,251,.88),rgba(255,253,251,.88)),var(--yami-story-bg)}
    .yami-gallery{background-image:linear-gradient(rgba(250,245,241,.86),rgba(250,245,241,.86)),var(--yami-gallery-bg)}
    .yami-video{background-image:linear-gradient(rgba(48,43,47,.82),rgba(48,43,47,.82)),var(--yami-video-bg)}
    .yami-gift{background-image:linear-gradient(rgba(243,232,226,.86),rgba(243,232,226,.86)),var(--yami-gift-bg)}
    .yami-invitation{background-image:linear-gradient(rgba(255,253,251,.86),rgba(255,253,251,.86)),var(--yami-invitation-bg)}
    .yami-rsvp{background-image:linear-gradient(rgba(251,245,241,.86),rgba(251,245,241,.86)),var(--yami-rsvp-bg)}
    .yami-closing{background-image:linear-gradient(rgba(48,34,37,.58),rgba(48,34,37,.72)),var(--yami-closing-bg,var(--yami-welcome-bg))}
    .yami-section-anchor{background-image:linear-gradient(rgba(35,25,28,var(--yami-overlay)),rgba(35,25,28,calc(var(--yami-overlay) + .18))),var(--yami-home-bg),var(--yami-hero-bg)}.yami-header nav a,.yami-nav-mobile a{color:var(--yami-link)}.yami-couple h3,.yami-timeline h3,.yami-location-grid h3,.yami-dress-grid h3{color:var(--yami-heading-color)}
  </style>
  <?php if ($customCss !== ''): ?><style><?php echo $customCss; ?></style><?php endif; ?>
</head>
<body class="yami-buzzy" data-countdown-target="<?php echo $countdownTarget; ?>">
  <div id="yami-welcome-modal" class="yami-welcome" role="dialog" aria-modal="true" aria-labelledby="yami-welcome-title"><div class="yami-welcome-card"><p class="yami-eyebrow">Undangan Pernikahan</p><p class="yami-opening-greeting"><?php echo $openingGreeting; ?></p><h1 id="yami-welcome-title"><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></h1><p>Selamat datang. Terima kasih telah meluangkan waktu untuk merayakan hari istimewa kami.</p><button type="button" id="yami-start-button">Buka Undangan</button></div></div>
  <header class="yami-header"><a class="yami-brand" href="#yami-home" aria-label="Kembali ke beranda"><?php echo $brideName; ?> <span>&amp;</span> <?php echo $groomName; ?></a><nav aria-label="Navigasi undangan"><a href="#yami-home">Beranda</a><?php if ($sectionEnabled('couple')): ?><a href="#yami-couple">Mempelai</a><?php endif; ?><?php if ($sectionEnabled('event')): ?><a href="#yami-event">Acara</a><?php endif; ?><?php if ($sectionEnabled('story')): ?><a href="#yami-story">Kisah</a><?php endif; ?><?php if ($sectionEnabled('gallery')): ?><a href="#yami-gallery">Galeri</a><?php endif; ?><?php if ($sectionEnabled('rsvp')): ?><a href="#yami-rsvp">Konfirmasi Kehadiran</a><?php endif; ?></nav><button class="yami-nav-toggle" type="button" aria-expanded="false" aria-controls="yami-nav-mobile">Menu</button></header>
  <nav id="yami-nav-mobile" class="yami-nav-mobile" aria-label="Navigasi mobile"><a href="#yami-home">Beranda</a><?php if ($sectionEnabled('couple')): ?><a href="#yami-couple">Mempelai</a><?php endif; ?><?php if ($sectionEnabled('event')): ?><a href="#yami-event">Acara</a><?php endif; ?><?php if ($sectionEnabled('story')): ?><a href="#yami-story">Kisah</a><?php endif; ?><?php if ($sectionEnabled('gallery')): ?><a href="#yami-gallery">Galeri</a><?php endif; ?><?php if ($sectionEnabled('rsvp')): ?><a href="#yami-rsvp">Konfirmasi Kehadiran</a><?php endif; ?></nav>
  <?php if ($sectionEnabled('home')): ?><section id="yami-home" class="yami-hero yami-section-anchor" data-section="home"><div class="yami-hero-content"><p class="yami-eyebrow">Kami Akan Menikah</p><p class="yami-guest">Kepada Yth. <strong><?php echo $guestLabel; ?></strong></p><p class="yami-opening-greeting"><?php echo $openingGreeting; ?></p><h1><?php echo $brideName; ?> <span>&amp;</span> <?php echo $groomName; ?></h1><p class="yami-opening"><?php echo $openingText; ?></p><div class="yami-countdown" data-countdown="<?php echo $countdownTarget; ?>"><div><strong data-unit="days">00</strong><span>Hari</span></div><div><strong data-unit="hours">00</strong><span>Jam</span></div><div><strong data-unit="minutes">00</strong><span>Menit</span></div><div><strong data-unit="seconds">00</strong><span>Detik</span></div></div><?php if ($calendarLink !== ''): ?><a class="yami-button" href="<?php echo $calendarLink; ?>" target="_blank" rel="noopener">Simpan ke Kalender</a><?php endif; ?></div></section><?php endif; ?>
  <main>
  <?php if ($sectionEnabled('couple')): ?><section id="yami-couple" class="yami-section yami-couple" data-section="couple"><div class="yami-container"><p class="yami-eyebrow">Tentang Kami</p><h2>Mempelai</h2><div class="yami-couple-grid"><article><div class="yami-avatar yami-avatar-bride"<?php if ($bridePhotoUrl === ''): ?> aria-hidden="true"<?php endif; ?>><?php if ($bridePhotoUrl !== ''): ?><img src="<?php echo $esc($bridePhotoUrl); ?>" alt="Foto <?php echo $brideName; ?>" loading="lazy" decoding="async"><?php else: ?>B<?php endif; ?></div><h3><?php echo $brideName; ?></h3><p>Putri dari <?php echo $esc($config['parents']['bride_father'] ?? ''); ?> &amp; <?php echo $esc($config['parents']['bride_mother'] ?? ''); ?></p></article><div class="yami-heart" aria-hidden="true">♥</div><article><div class="yami-avatar yami-avatar-groom"<?php if ($groomPhotoUrl === ''): ?> aria-hidden="true"<?php endif; ?>><?php if ($groomPhotoUrl !== ''): ?><img src="<?php echo $esc($groomPhotoUrl); ?>" alt="Foto <?php echo $groomName; ?>" loading="lazy" decoding="async"><?php else: ?>G<?php endif; ?></div><h3><?php echo $groomName; ?></h3><p>Putra dari <?php echo $esc($config['parents']['groom_father'] ?? ''); ?> &amp; <?php echo $esc($config['parents']['groom_mother'] ?? ''); ?></p></article></div></div></section><?php endif; ?>
  <?php if ($sectionEnabled('event')): ?><section id="yami-event" class="yami-section yami-event" data-section="event"><div class="yami-container"><p class="yami-eyebrow">Rangkaian Acara</p><h2>Acara Pernikahan</h2><div class="yami-event-grid"><article><span>Akad Nikah</span><strong><?php echo $eventDateDisplay; ?></strong><p><?php echo $akadTime; ?></p><p><?php echo $venue; ?></p></article><article><span>Resepsi</span><strong><?php echo $eventDateDisplay; ?></strong><p><?php echo $receptionTime; ?></p><p><?php echo $address; ?></p></article><article><span>Lokasi</span><strong><?php echo $venue; ?></strong><p><?php echo $address; ?></p><?php if ($mapsUrl !== ''): ?><a href="<?php echo $mapsUrl; ?>" target="_blank" rel="noopener">Buka di Google Maps</a><?php endif; ?></article></div></div></section><?php endif; ?>
  <?php if ($sectionEnabled('dresscode')): ?><section id="yami-dresscode" class="yami-section yami-dresscode" data-section="dresscode"><div class="yami-container"><?php if ($dresscodeEnabled): ?><p class="yami-eyebrow">Pakaian Tamu</p><h2><?php echo $dresscodeTitle; ?></h2><div class="yami-dress-grid"><article><span><?php echo $dresscodeColor; ?></span><h3><?php echo $dresscodeRule; ?></h3><p><?php echo $dresscodeDescription; ?></p></article></div><?php endif; ?></div></section><?php endif; ?>
  <?php if ($sectionEnabled('story')): ?><section id="yami-story" class="yami-section yami-story" data-section="story"><div class="yami-container"><p class="yami-eyebrow">Perjalanan Kami</p><h2>Kisah Cinta</h2><div class="yami-timeline"><?php $storyIndex = 0; foreach ($stories as $story): if (!is_array($story)) continue; $storyIndex++; $storyTitle = $esc($story['title'] ?? 'Momen Istimewa'); $storyDate = $esc($story['date'] ?? $story['year'] ?? ''); $storyText = render_preserved_text((string)($story['description'] ?? $story['text'] ?? '')); ?><article><span><?php echo $storyDate; ?></span><div><h3><?php echo $storyTitle; ?></h3><p><?php echo $storyText; ?></p></div></article><?php endforeach; if ($storyIndex === 0): ?><article><span>♥</span><div><h3>Perjalanan indah bersama</h3><p><?php echo $quoteText; ?></p></div></article><?php endif; ?></div></div></section><?php endif; ?>
  <?php if ($sectionEnabled('gallery')): ?><section id="yami-gallery" class="yami-section yami-gallery" data-section="gallery"><div class="yami-container"><p class="yami-eyebrow">Momen Manis</p><h2>Galeri</h2><?php if (!$galleryUrls): ?><p>Belum ada foto galeri yang ditambahkan.</p><?php else: ?><div class="yami-gallery-grid"><?php foreach ($galleryUrls as $index => $url): ?><a href="<?php echo $esc($url); ?>"><img src="<?php echo $esc($url); ?>" alt="Foto kenangan <?php echo $index + 1; ?>" loading="lazy" decoding="async"></a><?php endforeach; ?></div><?php endif; ?></div></section><?php endif; ?>
  <?php if ($sectionEnabled('video')): ?><section id="yami-video" class="yami-section yami-video" data-section="video"><div class="yami-container"><p class="yami-eyebrow">Cerita dalam Gambar</p><h2>Video</h2><?php if ($videoUrl !== ''): ?><video controls preload="metadata" src="<?php echo $esc($videoUrl); ?>">Peramban Anda tidak mendukung video HTML5.</video><?php else: ?><div class="yami-video-placeholder">Video akan ditampilkan di sini ketika media ditambahkan melalui CMS.</div><?php endif; ?></div></section><?php endif; ?>
  <?php if ($sectionEnabled('gift')): ?><section id="yami-gift" class="yami-section yami-gift" data-section="gift"><div class="yami-container"><p class="yami-eyebrow">Tanda Kasih</p><h2>Hadiah</h2><p>Bagi keluarga dan sahabat yang ingin memberikan tanda kasih, silakan gunakan informasi berikut.</p><div class="yami-gift-card"><strong><?php echo $esc($config['gift']['bank'] ?? ''); ?></strong><span><?php echo $esc($config['gift']['account_number'] ?? ''); ?></span><span><?php echo $esc($config['gift']['account_holder'] ?? ''); ?></span><button type="button" class="yami-copy" data-copy="<?php echo $esc($config['gift']['account_number'] ?? ''); ?>">Salin Nomor Rekening</button><?php if ($qrisUrl !== ''): ?><div class="yami-qris"><span>QRIS</span><img src="<?php echo $esc($qrisUrl); ?>" alt="QRIS untuk tanda kasih" loading="lazy" decoding="async"></div><?php endif; ?></div></div></section><?php endif; ?>
  <?php if ($sectionEnabled('invitation')): ?><section id="yami-invitation" class="yami-section yami-invitation" data-section="invitation"><div class="yami-container"><p class="yami-eyebrow">Detail Undangan</p><h2>Lokasi Acara</h2><div class="yami-location-grid"><div><h3><?php echo $venue; ?></h3><p><?php echo $address; ?></p><p><?php echo $eventDateDisplay; ?> · <?php echo $receptionTime; ?></p><?php if ($mapsUrl !== ''): ?><a class="yami-button" href="<?php echo $mapsUrl; ?>" target="_blank" rel="noopener">Buka di Google Maps</a><?php endif; ?></div><?php if ($mapsEmbed !== ''): ?><iframe src="<?php echo $mapsEmbed; ?>" title="Peta lokasi acara" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe><?php endif; ?></div></div></section><?php endif; ?>
  <?php if ($sectionEnabled('rsvp')): ?><section id="yami-rsvp" class="yami-section yami-rsvp" data-section="rsvp"><div class="yami-container"><p class="yami-eyebrow">Kehadiran Anda</p><h2>RSVP</h2><form id="yami-rsvp-form" class="yami-form"><input type="hidden" name="csrf_token" value="<?php echo $esc($csrf); ?>"><label>Nama<input type="text" name="nama" placeholder="Nama Anda" required maxlength="80"></label><label>Kehadiran<select name="status" required><option value="Hadir">Hadir</option><option value="Tidak Hadir">Tidak Hadir</option></select></label><label>Ucapan<textarea name="ucapan" rows="4" maxlength="500" placeholder="Tulis ucapan dan doa"></textarea></label><input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="display:none"><button type="submit">Kirim Konfirmasi Kehadiran</button><p id="yami-form-message" role="status" aria-live="polite"></p></form></div></section><?php endif; ?>
  <?php if ($sectionEnabled('closing')): ?><section id="yami-closing" class="yami-closing" data-section="closing"><p class="yami-eyebrow">Terima Kasih</p><h2><?php echo $closingText; ?></h2><p><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></p></section><?php endif; ?>
  </main>
  <footer class="yami-footer">© <?php echo date('Y'); ?> · Undangan pernikahan <?php echo $brideName; ?> &amp; <?php echo $groomName; ?></footer>
  <?php if ($musicUrl !== ''): ?><audio id="yami-audio" loop preload="none" src="<?php echo $esc($musicUrl); ?>"></audio><button type="button" class="yami-audio-toggle" id="yami-audio-toggle" aria-label="Putar Musik">♪</button><?php endif; ?>
  <script>
  (()=>{const welcome=document.getElementById('yami-welcome-modal'),start=document.getElementById('yami-start-button');const close=()=>{if(welcome){welcome.classList.add('is-closed');document.body.classList.remove('yami-locked')}};if(welcome){document.body.classList.add('yami-locked');start?.addEventListener('click',()=>{close();const audio=document.getElementById('yami-audio');if(audio)audio.play().catch(()=>{})})}const target=document.querySelector('[data-countdown]')?.dataset.countdown;const values={days:86400,hours:3600,minutes:60,seconds:1};const tick=()=>{const date=target?Date.parse(target):NaN;if(!Number.isFinite(date))return;let left=Math.max(0,Math.floor((date-Date.now())/1000));Object.entries(values).forEach(([key,size])=>{const el=document.querySelector('[data-unit="'+key+'"]');if(el){const mod=key==='days'?Math.floor(left/size):Math.floor(left/size)%(key==='hours'?24:60);el.textContent=String(mod).padStart(2,'0')}})};tick();setInterval(tick,1000);const navToggle=document.querySelector('.yami-nav-toggle'),mobile=document.getElementById('yami-nav-mobile');navToggle?.addEventListener('click',()=>{const open=mobile.classList.toggle('is-open');navToggle.setAttribute('aria-expanded',String(open))});mobile?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{mobile.classList.remove('is-open');navToggle?.setAttribute('aria-expanded','false')}));const audio=document.getElementById('yami-audio'),audioToggle=document.getElementById('yami-audio-toggle');audioToggle?.addEventListener('click',()=>{if(audio.paused){audio.play().catch(()=>{});audioToggle.textContent='Ⅱ'}else{audio.pause();audioToggle.textContent='♪'}});document.querySelectorAll('.yami-copy').forEach(btn=>btn.addEventListener('click',()=>{navigator.clipboard?.writeText(btn.dataset.copy||'').then(()=>{btn.textContent='Nomor tersalin'}).catch(()=>{})}));document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',e=>{e.preventDefault();const out=form.querySelector('[role="status"]');if(!out)return;out.textContent='Mengirim...';fetch('save.php',{method:'POST',body:new FormData(form),credentials:'same-origin'}).then(response=>response.json()).then(data=>{out.textContent=data.message||(data.success?'Terima kasih, konfirmasi berhasil dikirim.':'Gagal mengirim konfirmasi.');if(data.success)form.reset()}).catch(()=>{out.textContent='Gagal mengirim. Silakan coba lagi.'})}));})();
  </script>
</body>
</html>
