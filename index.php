<?php
require_once __DIR__ . '/config.php';
$config = load_config();

function escape_html(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$siteTitle = $config['site']['title'];
$siteDescription = $config['site']['description'];
$ogTitle = $config['site']['open_graph_title'];
$ogDescription = $config['site']['open_graph_description'];
$ogImage = $config['site']['open_graph_image'];
$schema = $config['site']['schema'];
$customCss = load_custom_css();
$weddingTitle = $config['wedding']['title'];
$heroText = $config['wedding']['opening_text'];
$guestFallback = 'Bapak/Ibu/Saudara/i';
$akadDate = $config['schedule']['akad_date'];
$akadTime = $config['schedule']['akad_time'];
$receptionDate = $config['schedule']['reception_date'];
$receptionTime = $config['schedule']['reception_time'];
$locationAddress = $config['location']['address'];
$mapsUrl = $config['location']['maps_url'];
$mapsEmbed = $config['location']['maps_embed'];
$venue = $config['location']['venue'];
$giftBank = $config['gift']['bank'];
$giftAccount = $config['gift']['account_number'];
$giftHolder = $config['gift']['account_holder'];
$giftEwalletLabel = $config['gift']['e_wallet_label'];
$giftEwalletNumber = $config['gift']['e_wallet_number'];
$whatsappLink = build_whatsapp_link($config);
$calendarLink = build_google_calendar_link($config);
$musicSrc = $config['media']['music'] ?: 'music/lagu.mp3';
$coverPath = $config['media']['cover'] ?: 'uploads/cover/cover.jpg';
$ogImage = $ogImage ?: $coverPath;
$heroBackground = $config['media']['background_hero'] ?: $coverPath;
$heroImageFit = $config['theme']['hero_image_fit'] ?? 'cover';
$heroImagePosition = $config['theme']['hero_image_position'] ?? 'center';
$heroBgSize = $heroImageFit === 'contain' ? 'contain' : ($heroImageFit === 'auto' ? 'auto' : 'cover');
$heroBgRepeat = $heroImageFit !== 'cover' ? 'no-repeat' : 'repeat';
$bgHero = $heroBackground ? 'style="background-image:url(' . escape_html($heroBackground) . ');background-size:' . $heroBgSize . ';background-position:' . escape_html($heroImagePosition) . ';background-repeat:' . $heroBgRepeat . ';"' : '';
$sectionBackgrounds = [
    $config['media']['background_sections'][0] ?? '',
    $config['media']['background_sections'][1] ?? '',
    $config['media']['background_sections'][2] ?? ''
];
$sectionStyles = [
    $sectionBackgrounds[0] ? 'style="background-image:url(' . escape_html($sectionBackgrounds[0]) . ');background-size:cover;background-position:center;"' : '',
    $sectionBackgrounds[1] ? 'style="background-image:url(' . escape_html($sectionBackgrounds[1]) . ');background-size:cover;background-position:center;"' : '',
    $sectionBackgrounds[2] ? 'style="background-image:url(' . escape_html($sectionBackgrounds[2]) . ');background-size:cover;background-position:center;"' : ''
];
$qrData = rawurlencode($mapsUrl ?: 'https://www.google.com/maps');
$calendarDownloadName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $siteTitle) ?: 'Undangan';
$countdownTarget = $config['schedule']['countdown_target'] ?: ($akadDate . 'T' . $akadTime . '+07:00');
$brideParents = trim(escape_html($config['parents']['bride_father'] . ' & ' . $config['parents']['bride_mother']));
$groomParents = trim(escape_html($config['parents']['groom_father'] . ' & ' . $config['parents']['groom_mother']));
$themeClasses = [
    'theme-button-' . preg_replace('/[^a-z0-9_-]/i', '', (string)($config['theme']['button_style'] ?? 'rounded')),
    'theme-navbar-' . preg_replace('/[^a-z0-9_-]/i', '', (string)($config['theme']['navbar_style'] ?? 'transparent')),
    'theme-card-' . preg_replace('/[^a-z0-9_-]/i', '', (string)($config['theme']['card_style'] ?? 'elevated')),
    'theme-footer-' . preg_replace('/[^a-z0-9_-]/i', '', (string)($config['theme']['footer_style'] ?? 'centered')),
    empty($config['theme']['animation_enabled']) ? 'theme-animation-off' : 'theme-animation-on'
];

// If the site is loaded through index.html URL, keep it visible but serve dynamic PHP.
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="<?php echo escape_html($siteDescription); ?>" />
  <meta name="keywords" content="<?php echo escape_html($config['site']['keywords']); ?>" />
  <meta property="og:title" content="<?php echo escape_html($ogTitle); ?>" />
  <meta property="og:description" content="<?php echo escape_html($ogDescription); ?>" />
  <?php if ($ogImage): ?><meta property="og:image" content="<?php echo escape_html($ogImage); ?>" /><?php endif; ?>
  <meta name="twitter:card" content="<?php echo escape_html($config['site']['twitter_card']); ?>" />
  <title><?php echo escape_html($siteTitle); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <?php
  // Generate Google Fonts URL from theme settings
  $fontFamilies = [];
  if (!empty($config['theme']['heading_font'])) {
      $fontFamilies[] = urlencode(str_replace(' ', '+', explode(',', $config['theme']['heading_font'])[0]));
  }
  if (!empty($config['theme']['body_font'])) {
      $fontFamilies[] = urlencode(str_replace(' ', '+', explode(',', $config['theme']['body_font'])[0]));
  }
  if (empty($fontFamilies)) {
      $fontFamilies = ['Allura', 'Inter'];
  }
  $fontsUrl = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', array_unique($fontFamilies)) . '&display=swap';
  ?>
  <link href="<?php echo escape_html($fontsUrl); ?>" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <?php
  // Generate CSS variables from theme settings. Presets are stored as resolved
  // values in config, so custom CSS can still load afterwards and override them.
  $theme = $config['theme'] ?? [];
  ?>
  <style id="theme-variables">
    :root {
      --primary: <?php echo escape_html($theme['primary_color'] ?? '#c84c47'); ?>;
      --secondary: <?php echo escape_html($theme['secondary_color'] ?? '#f0c2a1'); ?>;
      --accent: <?php echo escape_html($theme['accent_color'] ?? '#f0c2a1'); ?>;
      --bg: <?php echo escape_html($theme['background_color'] ?? '#fff8f2'); ?>;
      --paper: <?php echo escape_html($theme['paper_color'] ?? '#ffffff'); ?>;
      --paper-solid: <?php echo escape_html($theme['paper_color'] ?? '#ffffff'); ?>;
      --muted: <?php echo escape_html($theme['muted_color'] ?? '#806f66'); ?>;
      --text: <?php echo escape_html($theme['text_color'] ?? '#2f2424'); ?>;
      --link: <?php echo escape_html($theme['link_color'] ?? '#c84c47'); ?>;
      --shadow: <?php echo escape_html($theme['shadow'] ?? '0 22px 60px rgba(73,45,34,.14)'); ?>;
      --radius: <?php echo escape_html($theme['border_radius'] ?? '28px'); ?>;
      --container-width: <?php echo escape_html($theme['container_width'] ?? '1200px'); ?>;
      --section-spacing: <?php echo escape_html($theme['section_spacing'] ?? '80px'); ?>;
      --font-heading: <?php echo escape_html($theme['heading_font'] ?? 'Playfair Display, serif'); ?>;
      --font-body: <?php echo escape_html($theme['body_font'] ?? 'Lato, sans-serif'); ?>;
      --font-size-base: <?php echo escape_html($theme['font_size_base'] ?? '16px'); ?>;
    }
    body {
      font-family: var(--font-body);
      font-size: var(--font-size-base);
      color: var(--text);
      background: var(--bg);
    }
    h1, h2, h3, h4, h5, h6, .label, .eyebrow {
      font-family: var(--font-heading);
    }
    a {
      color: var(--link);
    }
  </style>
  <?php if (trim($customCss) !== '' && file_exists(CUSTOM_CSS_FILE)): ?>
  <link rel="stylesheet" href="custom.css" />
  <?php endif; ?>
  <script type="application/ld+json">
  <?php echo $schema; ?>
  </script>
</head>
<body class="<?php echo escape_html(implode(' ', $themeClasses)); ?>">
  <header class="topbar">
    <a class="brand" href="#hero"><?php echo escape_html($config['wedding']['bride_name']) . ' &amp; ' . escape_html($config['wedding']['groom_name']); ?></a>
    <nav>
      <a href="#undangan">Undangan</a>
      <a href="#cerita">Cerita</a>
      <a href="#galeri">Galeri</a>
      <a href="#acara">Acara</a>
      <a href="#lokasi">Lokasi</a>
      <a href="#rsvp">RSVP</a>
    </nav>
    <button type="button" id="dataSaverBtn" class="data-saver-btn" title="Hemat data: matikan musik auto & gallery lazy load">📊 Mode Hemat</button>
  </header>

  <?php
  // Section visibility helper function
  function is_section_enabled($config, $sectionId) {
      if (!isset($config['sections']) || !is_array($config['sections'])) {
          return true; // Default: all sections enabled for backward compatibility
      }
      foreach ($config['sections'] as $section) {
          if (($section['id'] ?? '') === $sectionId) {
              return !empty($section['enabled']);
          }
      }
      return true; // Unknown sections default to enabled
  }
  
  function get_section_title($config, $sectionId, $defaultTitle) {
      if (!isset($config['sections']) || !is_array($config['sections'])) {
          return $defaultTitle;
      }
      foreach ($config['sections'] as $section) {
          if (($section['id'] ?? '') === $sectionId) {
              return !empty($section['custom_title']) ? $section['custom_title'] : $defaultTitle;
          }
      }
      return $defaultTitle;
  }
  
  function get_section_subtitle($config, $sectionId, $defaultSubtitle) {
      if (!isset($config['sections']) || !is_array($config['sections'])) {
          return $defaultSubtitle;
      }
      foreach ($config['sections'] as $section) {
          if (($section['id'] ?? '') === $sectionId) {
              return !empty($section['custom_subtitle']) ? $section['custom_subtitle'] : $defaultSubtitle;
          }
      }
      return $defaultSubtitle;
  }
  ?>

  <main>
    <?php if (is_section_enabled($config, 'hero')): ?>
    <section id="hero" class="hero" <?php echo $bgHero; ?>>
      <div class="hero-card">
        <p class="eyebrow">Kami Akan Menikah</p>
        <h1><?php echo escape_html($config['wedding']['bride_name']); ?> &amp; <?php echo escape_html($config['wedding']['groom_name']); ?></h1>
        <p class="hero-text"><?php echo escape_html($heroText); ?></p>
        <p class="hero-subtitle"><?php echo escape_html($config['wedding']['bride_nickname']) . ' &amp; ' . escape_html($config['wedding']['groom_nickname']); ?></p>
        <p class="hero-parents">Putra dari <?php echo $brideParents; ?> dan Putri dari <?php echo $groomParents; ?>.</p>

        <div class="hero-actions">
          <button type="button" id="openInvitationBtn">Buka Undangan</button>
          <a class="calendar-btn" href="<?php echo escape_html($calendarLink); ?>" target="_blank" rel="noreferrer noopener">Tambah ke Kalender</a>
          <a class="calendar-btn" href="event.ics" download="<?php echo escape_html($calendarDownloadName); ?>.ics" title="Unduh file kalender (.ics)">Unduh Kalender</a>
          <a class="whatsapp-btn" href="<?php echo escape_html($whatsappLink); ?>" target="_blank" rel="noopener noreferrer">Hubungi WA</a>
        </div>

        <div id="countdown" class="countdown" data-countdown="<?php echo escape_html($countdownTarget); ?>" aria-label="Hitung mundur acara">
          <div><strong>00</strong><span>Hari</span></div>
          <div><strong>00</strong><span>Jam</span></div>
          <div><strong>00</strong><span>Menit</span></div>
          <div><strong>00</strong><span>Detik</span></div>
        </div>

        <button class="music-btn" type="button" id="musicBtn">Putar Musik</button>
      </div>
    </section>
    <?php endif; ?>

    <section class="section intro-section" style="background:#f9f6f0;padding:40px 20px">
      <div class="section-head">
        <p class="label">Kepada Yth.</p>
        <h2 id="guestNameDisplay"><?php echo escape_html($guestFallback); ?></h2>
      </div>
    </section>

    <?php if (is_section_enabled($config, 'undangan')): ?>
    <section id="undangan" class="section intro-section" <?php echo $sectionStyles[0]; ?>>
      <div class="section-head">
        <p class="label"><?php echo escape_html(get_section_title($config, 'undangan', 'Undangan Pernikahan')); ?></p>
        <h2><?php echo nl2br(escape_html(get_section_subtitle($config, 'undangan', $config['wedding']['quote']))); ?></h2>
        <p><?php echo nl2br(escape_html($config['wedding']['opening_text'])); ?></p>
      </div>
      <div class="cards-grid">
        <article class="card">
          <h3>Akad Nikah</h3>
          <p><?php echo date('j F Y', strtotime($akadDate)); ?></p>
          <p><?php echo escape_html($akadTime); ?> WIB</p>
          <p><?php echo escape_html($locationAddress); ?></p>
        </article>
        <article class="card">
          <h3>Resepsi</h3>
          <p><?php echo date('j F Y', strtotime($receptionDate)); ?></p>
          <p><?php echo escape_html($receptionTime); ?> WIB - Selesai</p>
          <p><?php echo escape_html($locationAddress); ?></p>
        </article>
        <article class="card">
          <h3>Dresscode</h3>
          <p>Putih / Pastel</p>
          <p>Rapi dan sopan</p>
          <p>Kenakan busana terbaikmu untuk momen spesial.</p>
        </article>
      </div>
    </section>
    <?php endif; ?>

    <?php if (is_section_enabled($config, 'cerita')): ?>
    <section id="cerita" class="section panel" <?php echo $sectionStyles[1]; ?>>
      <p class="label"><?php echo escape_html(get_section_title($config, 'cerita', 'Cerita Kami')); ?></p>
      <h2><?php echo escape_html(get_section_subtitle($config, 'cerita', 'Perjalanan indah bersama')); ?></h2>
      <div id="loveStoryContainer">
        <p class="loading">Memuat cerita...</p>
      </div>
    </section>
    <?php endif; ?>

    <?php if (is_section_enabled($config, 'galeri')): ?>
    <section id="galeri" class="section panel" <?php echo $sectionStyles[2]; ?>>
      <div class="section-head left">
        <p class="label"><?php echo escape_html(get_section_title($config, 'galeri', 'Galeri')); ?></p>
        <h2><?php echo escape_html(get_section_subtitle($config, 'galeri', 'Prewedding Kami')); ?></h2>
        <p>Beberapa momen indah kami dalam perjalanan sebelum hari pernikahan.</p>
      </div>
      <button type="button" id="loadGalleryBtn" class="load-gallery-btn" style="display:none; margin:20px auto; padding:10px 20px; background:#d4a574; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:500;">Muat Galeri</button>
      <div id="galleryGrid" class="gallery-grid">
        <p class="loading">Memuat galeri...</p>
      </div>
    </section>
    <?php endif; ?>

    <?php if (is_section_enabled($config, 'acara')): ?>
    <section id="acara" class="section">
      <div class="section-head">
        <p class="label"><?php echo escape_html(get_section_title($config, 'acara', 'Jadwal Acara')); ?></p>
        <h2><?php echo escape_html(get_section_subtitle($config, 'acara', 'Rangkaian Acara')); ?></h2>
      </div>
      <div class="timeline">
        <div class="timeline-item">
          <span><?php echo escape_html($akadTime); ?> WIB</span>
          <h3>Akad Nikah</h3>
          <p>Prosesi akad nikah keluarga.</p>
        </div>
        <div class="timeline-item">
          <span><?php echo escape_html($receptionTime); ?> WIB</span>
          <h3>Resepsi</h3>
          <p>Ramahan dan doa bersama tamu undangan.</p>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php if (is_section_enabled($config, 'lokasi')): ?>
    <section id="lokasi" class="section panel">
      <div class="section-head left">
        <p class="label"><?php echo escape_html(get_section_title($config, 'lokasi', 'Lokasi')); ?></p>
        <h2><?php echo escape_html(get_section_subtitle($config, 'lokasi', 'Tempat Acara')); ?></h2>
      </div>
      <div class="location-grid">
        <div class="card">
          <h3>Alamat</h3>
          <p><?php echo escape_html($venue); ?></p>
          <p><?php echo escape_html($locationAddress); ?></p>
          <p><a href="<?php echo escape_html($mapsUrl); ?>" target="_blank" rel="noopener noreferrer">Buka di Google Maps</a></p>
        </div>
        <div class="card">
          <h3>QR Lokasi</h3>
          <p class="location-qr">
            <strong>Scan untuk arah</strong><br />
            <img id="qrLokasiImg" src="https://api.qrserver.com/v1/create-qr-code/?size=210x210&data=<?php echo $qrData; ?>" alt="QR kode lokasi pernikahan" loading="lazy" decoding="async" />
          </p>
        </div>
        <div class="map-wrap">
          <iframe src="<?php echo escape_html($mapsEmbed); ?>" title="Lokasi acara" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          <div class="map-footnote">Titik lokasi tepat: <?php echo escape_html($mapsUrl); ?></div>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php if (is_section_enabled($config, 'amplop')): ?>
    <section id="amplop" class="section panel">
      <div class="section-head left">
        <p class="label"><?php echo escape_html(get_section_title($config, 'amplop', 'Amplop Digital')); ?></p>
        <h2><?php echo escape_html(get_section_subtitle($config, 'amplop', 'Tanda Terima Kasih')); ?></h2>
        <p>Jika ingin memberikan amplop digital, berikut data rekening:</p>
      </div>
      <div class="amplop-container">
        <div class="amplop-card">
          <div class="amplop-header">Untuk <?php echo escape_html($config['wedding']['bride_name']); ?></div>
          <div class="amplop-item">
            <label>Bank:</label>
            <span><?php echo escape_html($giftBank); ?></span>
          </div>
          <div class="amplop-item">
            <label>Nomor Rekening:</label>
            <span class="amplop-number" data-account="<?php echo escape_html($giftAccount); ?>"><?php echo escape_html($giftAccount); ?></span>
          </div>
          <button type="button" class="amplop-copy-btn" data-account="<?php echo escape_html($giftAccount); ?>">Salin Nomor</button>
          <p class="amplop-feedback" style="display:none;color:#4CAF50;font-size:12px;margin-top:8px;">✓ Nomor berhasil disalin</p>
        </div>
        <div class="amplop-card">
          <div class="amplop-header">Untuk <?php echo escape_html($config['wedding']['groom_name']); ?></div>
          <div class="amplop-item">
            <label>E-Wallet:</label>
            <span><?php echo escape_html($giftEwalletLabel); ?></span>
          </div>
          <div class="amplop-item">
            <label>Nomor Telepon:</label>
            <span class="amplop-number" data-account="<?php echo escape_html($giftEwalletNumber); ?>"><?php echo escape_html($giftEwalletNumber); ?></span>
          </div>
          <button type="button" class="amplop-copy-btn" data-account="<?php echo escape_html($giftEwalletNumber); ?>">Salin Nomor</button>
          <p class="amplop-feedback" style="display:none;color:#4CAF50;font-size:12px;margin-top:8px;">✓ Nomor berhasil disalin</p>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php if (is_section_enabled($config, 'rsvp')): ?>
    <section id="rsvp" class="section panel">
      <div class="section-head left">
        <p class="label"><?php echo escape_html(get_section_title($config, 'rsvp', 'RSVP')); ?></p>
        <h2><?php echo escape_html(get_section_subtitle($config, 'rsvp', 'Konfirmasi Kehadiran')); ?></h2>
      </div>
      <form id="rsvpForm" class="rsvp-form">
        <input type="hidden" name="csrf_token" id="csrfToken" />
        <label>Nama<input type="text" name="nama" placeholder="Nama Anda" required /></label>
        <label>Kehadiran
          <select name="status" required>
            <option value="Hadir">Hadir</option>
            <option value="Tidak Hadir">Tidak Hadir</option>
          </select>
        </label>
        <label>Ucapan<textarea name="ucapan" rows="4" placeholder="Tulis ucapan dan doa"></textarea></label>
        <input type="text" name="website" autocomplete="off" tabindex="-1" aria-hidden="true" style="display:none">
        <button type="submit">Kirim RSVP</button>
        <p id="formMessage" class="form-message" role="status" aria-live="polite"></p>
      </form>
      <div id="messages" class="messages"></div>
    </section>
    <?php endif; ?>
  </main>

  <div id="lightbox" class="lightbox" style="display:none;">
    <div class="lightbox-container">
      <button type="button" class="lightbox-close" title="Tutup (Esc)">&times;</button>
      <img id="lightboxImage" src="" alt="Foto galeri" class="lightbox-image" loading="lazy" decoding="async" />
    </div>
  </div>

  <audio id="backgroundMusic" src="<?php echo escape_html($musicSrc); ?>" preload="auto" loop></audio>
  <script src="script.js" defer></script>

  <script>
  (function() {
    const variableMap = {
      primary_color: '--primary',
      secondary_color: '--secondary',
      accent_color: '--accent',
      background_color: '--bg',
      paper_color: '--paper',
      muted_color: '--muted',
      text_color: '--text',
      link_color: '--link',
      shadow: '--shadow',
      border_radius: '--radius',
      container_width: '--container-width',
      section_spacing: '--section-spacing',
      heading_font: '--font-heading',
      body_font: '--font-body',
      font_size_base: '--font-size-base'
    };
    const styleClassPrefixes = ['theme-button-', 'theme-navbar-', 'theme-card-', 'theme-footer-', 'theme-animation-'];

    function replaceThemeClass(prefix, value) {
      document.body.classList.forEach(function(className) {
        if (className.indexOf(prefix) === 0) {
          document.body.classList.remove(className);
        }
      });
      if (value) {
        document.body.classList.add(prefix + value);
      }
    }

    window.addEventListener('message', function(event) {
      if (event.origin !== window.location.origin || !event.data || event.data.type !== 'theme-preview:update') {
        return;
      }
      const theme = event.data.theme || {};
      Object.keys(variableMap).forEach(function(key) {
        if (theme[key] !== undefined && theme[key] !== '') {
          document.documentElement.style.setProperty(variableMap[key], theme[key]);
          if (key === 'paper_color') {
            document.documentElement.style.setProperty('--paper-solid', theme[key]);
          }
        }
      });
      replaceThemeClass('theme-button-', theme.button_style || 'rounded');
      replaceThemeClass('theme-navbar-', theme.navbar_style || 'transparent');
      replaceThemeClass('theme-card-', theme.card_style || 'elevated');
      replaceThemeClass('theme-footer-', theme.footer_style || 'centered');
      replaceThemeClass('theme-animation-', theme.animation_enabled ? 'on' : 'off');

      // Update hero background dynamically for live preview
      const heroSection = document.getElementById('hero');
      if (heroSection) {
        var heroFit = theme.hero_image_fit || 'cover';
        var heroPosition = theme.hero_image_position || 'center';
        var bgSize = heroFit === 'contain' ? 'contain' : (heroFit === 'auto' ? 'auto' : 'cover');
        var bgRepeat = heroFit !== 'cover' ? 'no-repeat' : 'repeat';
        heroSection.style.backgroundSize = bgSize;
        heroSection.style.backgroundPosition = heroPosition;
        heroSection.style.backgroundRepeat = bgRepeat;
      }
    });
  })();
  </script>
  <script>
  // Load Love Story dynamically
  (function() {
    const container = document.getElementById('loveStoryContainer');
    if (!container) return;

    fetch('app/love-story.php')
      .then(function(response) {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
      })
      .then(function(stories) {
        if (!stories || stories.length === 0) {
          container.innerHTML = '<p>Belum ada cerita cinta yang ditambahkan.</p>';
          return;
        }

        let html = '<div class="love-story-timeline">';
        stories.forEach(function(story, index) {
          html += '<div class="love-story-item">';
          if (story.icon) {
            html += '<div class="love-story-icon">' + escapeHtml(story.icon) + '</div>';
          }
          if (story.image) {
            html += '<div class="love-story-image"><img src="' + escapeHtml(story.image) + '" alt="' + escapeHtml(story.image_alt || '') + '"></div>';
            if (story.image_caption) {
              html += '<p class="love-story-caption">' + escapeHtml(story.image_caption) + '</p>';
            }
          }
          html += '<div class="love-story-content">';
          if (story.event_date) {
            html += '<p class="love-story-date">' + escapeHtml(formatDate(story.event_date)) + '</p>';
          }
          if (story.title) {
            html += '<h3>' + escapeHtml(story.title) + '</h3>';
          }
          if (story.subtitle) {
            html += '<p class="love-story-subtitle">' + escapeHtml(story.subtitle) + '</p>';
          }
          if (story.description) {
            html += '<p class="love-story-description">' + escapeHtml(story.description) + '</p>';
          }
          html += '</div></div>';
        });
        html += '</div>';
        container.innerHTML = html;
      })
      .catch(function(error) {
        console.error('Error loading love story:', error);
        container.innerHTML = '<p>Gagal memuat cerita. Silakan coba lagi nanti.</p>';
      });

    function escapeHtml(text) {
      if (!text) return '';
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function formatDate(dateStr) {
      const date = new Date(dateStr);
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      return date.toLocaleDateString('id-ID', options);
    }
  })();
  </script>
</body>
</html>
