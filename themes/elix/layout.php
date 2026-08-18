<?php
if (!defined('THEME_HELPER_LOADED')) {
    require_once __DIR__ . '/../../app/theme-helper.php';
}

$config = $config ?? [];
$presetKey = 'elix';
$brideName = escape_html($config['wedding']['bride_name'] ?? '');
$groomName = escape_html($config['wedding']['groom_name'] ?? '');
$bridePhoto = !empty($config['media']['bride_photo']) ? $config['media']['bride_photo'] : ($config['media']['cover'] ?? '');
$groomPhoto = !empty($config['media']['groom_photo']) ? $config['media']['groom_photo'] : ($config['media']['cover'] ?? '');
$coverPath = $config['media']['cover'] ?? '';
$akadDate = $config['schedule']['akad_date'] ?? '';
$akadTime = $config['schedule']['akad_time'] ?? '';
$receptionDate = $config['schedule']['reception_date'] ?? $akadDate;
$receptionTime = $config['schedule']['reception_time'] ?? '';
$venue = escape_html($config['location']['venue'] ?? '');
$address = nl2br(escape_html($config['location']['address'] ?? ''));
$mapsUrl = escape_html($config['location']['maps_url'] ?? '');
$mapsEmbed = escape_html($config['location']['maps_embed'] ?? '');
$musicSrc = $config['media']['music'] ?? '';
$guestName = function_exists('resolve_guest_name') ? resolve_guest_name($config) : '';
$guestLabel = escape_html($guestName !== '' ? $guestName : 'Bapak/Ibu/Saudara/i');
$quote = nl2br(escape_html($config['wedding']['quote'] ?? ''));
$openingText = nl2br(escape_html($config['wedding']['opening_text'] ?? ''));
$calendarLink = escape_html(build_google_calendar_link($config));
$whatsappLink = escape_html(build_whatsapp_link($config));
$calendarTarget = strtotime((string)($config['schedule']['countdown_target'] ?? '')) ?: time();
$csrf = function_exists('get_csrf_token') ? get_csrf_token() : '';
$galleryItems = function_exists('get_gallery_items') ? get_gallery_items($config) : [];
$stories = $config['love_story']['items'] ?? [];
if (!is_array($stories) || !$stories) {
    $stories = [
        ['title' => 'Awal Pertemuan', 'date' => $akadDate, 'description' => $openingText, 'image' => $bridePhoto],
        ['title' => 'Menuju Hari Bahagia', 'date' => $receptionDate, 'description' => $quote, 'image' => $groomPhoto],
    ];
}
$customCss = function_exists('load_custom_css') ? load_custom_css() : '';
$visuals = function_exists('theme_visual_values_for_config') ? theme_visual_values_for_config($config, 'elix') : [];
$elixAccent = (string)($visuals['accent_color'] ?? '#f14e95');
$elixHeadingFont = (string)($visuals['heading_font'] ?? 'Pacifico, cursive');
$elixBodyFont = (string)($visuals['body_font'] ?? 'Work Sans, sans-serif');
$elixHeroPath = (string)($visuals['hero_background'] ?? '');
$elixHeroImage = $elixHeroPath !== '' ? theme_visual_css_url($elixHeroPath) : 'url("/themes/elix/img/prewed1.jpg")';
$elixOverlay = (float)($visuals['hero_overlay'] ?? '0.45');
$elixCountdownScale = (float)($visuals['countdown_scale'] ?? '0.65');
$elixVisualStyle = '<style id="cms-elix-visual">:root{--cms-elix-accent:' . $elixAccent . ';--cms-elix-heading:' . $elixHeadingFont . ';--cms-elix-body:' . $elixBodyFont . ';--cms-elix-overlay:' . $elixOverlay . ';--cms-elix-countdown-scale:' . $elixCountdownScale . ';--cms-elix-hero-bg:' . $elixHeroImage . '}body{font-family:var(--cms-elix-body)}.hero h1,.home h2,.home .couple h3,.info h2,.story h2,.gallery h2,.rsvp h2,.gifts h2{font-family:var(--cms-elix-heading)}#hero{display:flex;justify-content:center;align-items:center;text-align:center;box-sizing:border-box}#hero>main{display:flex;flex-direction:column;align-items:center;width:min(100%,56rem);margin:0 auto;text-align:center}#hero>main>h4,#hero>main>h1,#hero>main>p{width:100%;text-align:center}#hero>main>#countdown{width:100%;display:flex;justify-content:center;align-items:center;text-align:center}#hero>main>a{display:inline-block;align-self:center;margin-right:auto;margin-left:auto}.hero,.hero h1,.hero h4,.hero p{color:#fff}.hero::before{background-image:linear-gradient(rgba(0,0,0,var(--cms-elix-overlay)),rgba(0,0,0,var(--cms-elix-overlay))),var(--cms-elix-hero-bg)}.hero a,.home h2,.home .couple h3,.info h2,.story h2,.gallery h2,.rsvp h2,.gifts h2{color:var(--cms-elix-accent)}.simply-countdown-circle{transform:scale(var(--cms-elix-countdown-scale));transform-origin:center}@media(max-width:576px){.hero h1{font-size:clamp(2.15rem,12vw,4rem);line-height:1.05}.hero{padding-top:4rem;padding-bottom:3rem}.hero p{max-width:32rem;margin-left:auto;margin-right:auto}.simply-countdown-circle{gap:.75rem;margin-top:1rem!important;margin-bottom:.75rem!important}.simply-countdown-circle>.simply-section{width:88px;height:88px;padding:.75rem}.simply-countdown-circle .simply-amount{font-size:1.35rem}}</style>';
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo escape_html($config['site']['title'] ?? 'Undangan Pernikahan 1'); ?></title>
    <meta name="description" content="<?php echo escape_html($config['site']['description'] ?? ''); ?>" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69fIUzI2PVdRZhwQ+dy64/BUtbMJ1WmZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Sacramento&family=Work+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo get_theme_asset_url($presetKey, 'original-style.css'); ?>" />
    <link rel="stylesheet" href="<?php echo get_theme_asset_url($presetKey, 'fidelity-adapter.css'); ?>" />
    <?php echo $elixVisualStyle; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo get_theme_asset_url($presetKey, 'countdown/circle.css'); ?>" />
    <?php if ($customCss !== ''): ?><style><?php echo $customCss; ?></style><?php endif; ?>
    <script src="<?php echo get_theme_asset_url($presetKey, 'countdown/simplyCountdown.umd.js'); ?>"></script>
  </head>
  <body>
    <?php if (theme_section_enabled($config, $presetKey, 'hero')): ?>
    <section id="hero" class="hero w-100 h-100 p-3 mx-auto text-center d-flex justify-content-center align-items-center text-white">
      <main>
        <h4>Kepada Yth.<span><?php echo $guestLabel; ?>, </span></h4>
        <h1><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></h1>
        <p><?php echo $openingText ?: 'Akan melangsungkan resepsi pernikahan dalam:'; ?></p>
        <?php if (!empty($config['schedule']['countdown_target'])): ?><div id="countdown" class="countdown simply-countdown-circle mt-4 mb-4"></div><?php endif; ?>
        <a href="#home" class="btn mt-4" btn-lg onClick="enableScroll()">Lihat Undangan</a>
      </main>
    </section>
    <?php endif; ?>

    <nav class="navbar navbar-expand-md bg-transparent sticky-top mynavbar">
      <div class="container">
        <a class="navbar-brand" href="#home"><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Alihkan navigasi"><span class="navbar-toggler-icon"></span></button>
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
          <div class="offcanvas-header"><h5 class="offcanvas-title" id="offcanvasNavbarLabel"><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button></div>
          <div class="offcanvas-body"><div class="navbar-nav ms-auto">
            <?php if (theme_section_enabled($config, $presetKey, 'home')): ?><a class="nav-link" href="#home">Beranda</a><?php endif; ?>
            <?php if (theme_section_enabled($config, $presetKey, 'info')): ?><a class="nav-link" href="#info">Informasi</a><?php endif; ?>
            <?php if (theme_section_enabled($config, $presetKey, 'story')): ?><a class="nav-link" href="#story">Kisah</a><?php endif; ?>
            <?php if (theme_section_enabled($config, $presetKey, 'gallery')): ?><a class="nav-link" href="#gallery">Galeri</a><?php endif; ?>
            <?php if (theme_section_enabled($config, $presetKey, 'rsvp')): ?><a class="nav-link" href="#rsvp">Konfirmasi Kehadiran</a><?php endif; ?>
            <?php if (theme_section_enabled($config, $presetKey, 'gifts')): ?><a class="nav-link" href="#gifts">Hadiah</a><?php endif; ?>
          </div></div>
        </div>
      </div>
    </nav>

    <?php if (theme_section_enabled($config, $presetKey, 'home')): ?>
    <section id="home" class="home"><div class="container"><div class="row justify-content-center"><div class="col-md-8 text-center"><h2>Acara Pernikahan</h2><h3><?php echo $akadDate ? escape_html(date('j F Y', strtotime($akadDate))) : ''; ?> di <?php echo $venue; ?></h3><p><?php echo $openingText; ?></p></div><div class="row couple"><div class="col-lg-6"><div class="row"><div class="col-8 text-end"><h3><?php echo $groomName; ?></h3><p><?php echo $quote; ?></p><p>Putra dari <?php echo escape_html($config['parents']['groom_father'] ?? ''); ?><br>dan<br><?php echo escape_html($config['parents']['groom_mother'] ?? ''); ?></p></div><div class="col-4"><img src="<?php echo escape_html(public_path($groomPhoto)); ?>" alt="pengantinPria" class="img-responsive rounded-circle"></div></div></div><span class="heart"><i class="bi bi-heart-fill"></i></span><div class="col-lg-6"><div class="row"><div class="col-4"><img src="<?php echo escape_html(public_path($bridePhoto)); ?>" alt="pengantinWanita" class="img-responsive rounded-circle"></div><div class="col-8"><h3><?php echo $brideName; ?></h3><p><?php echo $quote; ?></p><p>Putri dari <?php echo escape_html($config['parents']['bride_father'] ?? ''); ?><br>dan<br><?php echo escape_html($config['parents']['bride_mother'] ?? ''); ?></p></div></div></div></div></div></div></section>
    <?php endif; ?>

    <?php if (theme_section_enabled($config, $presetKey, 'info')): ?>
    <section id="info" class="info"><div class="container"><div class="row justify-content-center"><div class="col-md-8 col-10 text-center"><h2>Informasi Acara</h2><p class="alamat">Alamat: <br><?php echo $venue; ?><br><?php echo $address; ?></p><?php if ($mapsEmbed !== ''): ?><iframe src="<?php echo $mapsEmbed; ?>" width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe><?php endif; ?><a href="<?php echo $mapsUrl; ?>" target="_blank" rel="noopener" class="btn btn-light btn-sm my-4">Klik untuk membuka peta</a><p class="description">Diharapkan untuk tidak salah alamat dan tanggal. Manakala tiba di tujuan namun tidak ada tanda-tanda sedang dilangsungkan pernikahan, boleh jadi Anda salah jadwal, atau salah tempat.</p></div></div><div class="row justify-content-center mt-4"><div class="col-md-5 col-10"><div class="card text-center text-bg-light mb-5"><div class="card-header">Akad Nikah</div><div class="card-body"><div class="row justify-content-center"><div class="col-md-6"><i class="bi bi-clock d-block"></i><span><?php echo escape_html($akadTime); ?></span></div><div class="col-md-6"><i class="bi bi-calendar3 d-block"></i><span><?php echo $akadDate ? escape_html(date('l, j F Y', strtotime($akadDate))) : ''; ?></span></div></div></div><div class="card-footer">Saat acara akad diharapkan kondusif untuk menjaga kekhidmatan dan kekhusyukan seluruh prosesi.</div></div></div><div class="col-md-5 col-10"><div class="card text-center text-bg-light"><div class="card-header">Resepsi</div><div class="card-body"><div class="row justify-content-center"><div class="col-md-6"><i class="bi bi-clock d-block"></i><span><?php echo escape_html($receptionTime); ?></span></div><div class="col-md-6"><i class="bi bi-calendar3 d-block"></i><span><?php echo $receptionDate ? escape_html(date('l, j F Y', strtotime($receptionDate))) : ''; ?></span></div></div></div><div class="card-footer">Merupakan kehormatan dan kebahagiaan bagi kami apabila Anda berkenan hadir dan memberikan doa restu.</div></div></div></div></div></section>
    <?php endif; ?>

    <?php if (theme_section_enabled($config, $presetKey, 'story')): ?>
    <section id="story" class="story"><div class="container"><div class="row justify-content-center"><div class="col-md-8 col-10 text-center"><span>Bagaimana Cinta Kami Bersemi</span><h2>Cerita Kami</h2><p><?php echo $quote; ?></p></div></div><div class="row"><div class="col"><ul class="timeline"><?php foreach (array_slice($stories, 0, 6) as $index => $story): ?><li<?php echo $index % 2 === 1 ? ' class="timeline-inverted"' : ''; ?>><div class="timeline-image" style="background-image:url('<?php echo escape_html(public_path($story['image'] ?? $coverPath)); ?>');"></div><div class="timeline-panel"><div class="timeline-heading"><h3><?php echo escape_html($story['title'] ?? 'Cerita Kami'); ?></h3><span><?php echo escape_html($story['date'] ?? ''); ?></span></div><div class="timeline-body"><p><?php echo nl2br(escape_html($story['description'] ?? '')); ?></p></div></div></li><?php endforeach; ?></ul></div></div></div></section>
    <?php endif; ?>

    <?php if (theme_section_enabled($config, $presetKey, 'gallery')): ?>
    <section id="gallery" class="gallery"><div class="container"><div class="row justify-content-center"><div class="col-md-8 col-10 text-center"><span>Memori Perjalanan Kami</span><h2>Galeri Foto</h2><p>Momen-momen indah kami.</p></div><div class="row row-cols-lg-4 row-cols-md-3 row-cols-sm-2 row-cols-1 justify-content-center"><?php foreach (array_slice((array)$galleryItems, 0, 12) as $item): $image = is_array($item) ? ($item['path'] ?? $item['src'] ?? '') : (string)$item; if ($image === '') continue; ?><div class="col mt-3"><a href="<?php echo escape_html(public_path($image)); ?>" data-toggle="lightbox" data-caption="<?php echo $brideName; ?> &amp; <?php echo $groomName; ?>" data-galery="mygallery"><img src="<?php echo escape_html(public_path($image)); ?>" alt="<?php echo $brideName; ?> &amp; <?php echo $groomName; ?>" class="img-fluid w-100 rounded"></a></div><?php endforeach; ?></div></div></div></section>
    <?php endif; ?>

    <?php if (theme_section_enabled($config, $presetKey, 'rsvp')): ?>
    <section id="rsvp" class="rsvp"><div class="container"><div class="row justify-content-center"><div class="col-md-8 col-10 text-center"><h2>Konfirmasi Kehadiran</h2><p>Isi form di bawah ini untuk melakukan konfirmasi kehadiran.</p></div><form class="row row-cols-md-auto g-3 align-items-center justify-content-center" method="POST" action="<?php echo escape_html(public_path('save.php')); ?>" id="my-form"><input type="hidden" name="csrf_token" value="<?php echo escape_html($csrf); ?>"><input type="text" name="website" tabindex="-1" autocomplete="off" style="display:none"><div class="col-12"><div class="mb-3"><label for="nama" class="form-label">Nama</label><input type="text" class="form-control" id="nama" name="nama" maxlength="80"></div></div><div class="col-12"><div class="mb-3"><label for="jumlah" class="form-label">Jumlah keluarga hadir</label><input type="number" class="form-control" id="jumlah" name="jumlah" min="1" max="5" value="1"></div></div><div class="col-12"><div class="mb-3"><label for="status" class="form-label">Konfirmasi</label><select name="status" id="status" class="form-select"><option selected>Pilih salah satu</option><option value="Hadir">Hadir</option><option value="Tidak Hadir">Tidak Hadir</option></select></div></div><div class="col-12"><div class="mb-3"><label for="ucapan" class="form-label">Ucapan</label><textarea class="form-control" id="ucapan" name="ucapan" maxlength="500"></textarea></div></div><div class="col-12" style="margin-top:30px"><button class="btn btn-primary" type="submit">Kirim</button></div></form><p id="rsvp-message" class="text-center mt-3" role="status" aria-live="polite"></p></div></div></section>
    <?php endif; ?>

    <?php if (theme_section_enabled($config, $presetKey, 'gifts')): ?><section id="gifts" class="gifts"><div class="container"><div class="row justify-content-center"><div class="col-md-8 col-10 text-center"><span>ungkapan tanda kasih</span><h2>Kirim Hadiah</h2><p>Doa restu Anda merupakan hadiah terindah bagi kami.</p></div></div><div class="row justify-content-center text-center"><div class="col-md-6"><ul class="list-group"><li class="list-group-item"><div class="fw-bold"><?php echo escape_html($config['gift']['bank'] ?? ''); ?></div><?php echo escape_html($config['gift']['account_number'] ?? ''); ?> - <?php echo $brideName; ?></li><?php if (!empty($config['gift']['qris_image'])): ?><li class="list-group-item"><div class="fw-bold">QRIS</div><img src="<?php echo escape_html(public_path($config['gift']['qris_image'])); ?>" alt="QRIS" class="img-fluid" width="180"></li><?php endif; ?></ul></div></div></div></section><?php endif; ?>

    <footer><div class="container"><div class="row"><div class="col text-center"><small class="block">&copy;<?php echo date('Y'); ?> <?php echo $brideName; ?> &amp; <?php echo $groomName; ?>. Hak Cipta Dilindungi.</small><small class="block">Didesain oleh: <a href="https://github.com/elix-stack/wedding-invitation-1">Elix</a></small><ul class="mt-3"><li><a href="#"><i class="bi bi-instagram"></i></a></li><li><a href="#"><i class="bi bi-youtube"></i></a></li><li><a href="#"><i class="bi bi-twitter"></i></a></li><li><a href="#"><i class="bi bi-facebook"></i></a></li><li><a href="#"><i class="bi bi-tiktok"></i></a></li></ul></div></div></div></footer>
    <?php if (!empty($musicSrc)): ?><div id="audio-container"><audio id="backSong" autoplay loop preload="metadata" aria-label="Musik latar"><source src="<?php echo escape_html(public_path($musicSrc)); ?>" type="audio/mp3"></audio><div class="audio-icon-wrapper" style="display:none;" role="status" aria-live="polite"><i class="bi bi-disc"></i><span class="visually-hidden">Klik Lihat Undangan untuk memutar musik</span></div></div><?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bs5-lightbox@1.8.5/dist/index.bundle.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        if (window.simplyCountdown && document.querySelector('#countdown')) simplyCountdown('#countdown', {year:<?php echo (int)date('Y', $calendarTarget); ?>,month:<?php echo (int)date('n', $calendarTarget); ?>,day:<?php echo (int)date('j', $calendarTarget); ?>,hours:<?php echo (int)date('G', $calendarTarget); ?>,minutes:<?php echo (int)date('i', $calendarTarget); ?>,seconds:<?php echo (int)date('s', $calendarTarget); ?>,words:{days:{root:'Hari',lambda:(r,n)=>r},hours:{root:'Jam',lambda:(r,n)=>r},minutes:{root:'Menit',lambda:(r,n)=>r},seconds:{root:'Detik',lambda:(r,n)=>r}},plural:true,inline:false,refresh:1000,sectionClass:'simply-section',amountClass:'simply-amount',wordClass:'simply-word',zeroPad:false});
        const form = document.getElementById('my-form');
        const feedback = document.getElementById('rsvp-message');
        if (form) form.addEventListener('submit', async function (event) {
          event.preventDefault();
          const submit = form.querySelector('button[type="submit"]');
          if (submit) submit.disabled = true;
          if (feedback) feedback.textContent = 'Mengirim...';
          try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form)});
            const raw = await response.text();
            let result;
            try { result = raw ? JSON.parse(raw) : {}; } catch (parseError) { throw new Error('Respons server bukan JSON yang valid.'); }
            if (!response.ok || result.success !== true) {
              if (feedback) { feedback.textContent = result.message || `Konfirmasi kehadiran gagal (${response.status}).`; feedback.className = 'text-center mt-3 text-danger'; }
              return;
            }
            if (feedback) { feedback.textContent = result.message || 'Konfirmasi kehadiran berhasil dikirim.'; feedback.className = 'text-center mt-3 text-success'; }
            form.reset();
          } catch (error) {
            console.error('[Elix] submission failed', error);
            if (feedback) { feedback.textContent = 'Tidak dapat mengirim konfirmasi kehadiran. Periksa koneksi lalu coba lagi.'; feedback.className = 'text-center mt-3 text-danger'; }
          } finally {
            if (submit) submit.disabled = false;
          }
        });
        const guest = new URLSearchParams(window.location.search).get('n') || new URLSearchParams(window.location.search).get('to') || '';
        const nameInput = document.getElementById('nama');
        if (nameInput && guest) nameInput.value = guest;
      });
      function enableScroll(){
        document.documentElement.style.scrollBehavior='smooth';
        const audio=document.getElementById('backSong');
        if(!audio) return;
        const playback=audio.play();
        if(playback && typeof playback.catch === 'function') playback.catch(function(){
          const notice=document.querySelector('.audio-icon-wrapper');
          if(notice){ notice.style.display='block'; notice.title='Klik kembali untuk memutar musik'; }
        });
      }
    </script>
  </body>
</html>
