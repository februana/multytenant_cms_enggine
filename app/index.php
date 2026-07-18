<?php
// Load consolidated app config
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
$heroStyle = $heroBackground ? 'style="--hero-bg:url(&quot;' . escape_html($heroBackground) . '&quot;);"' : '';
$sectionBackgrounds = [
    $config['media']['background_sections'][0] ?? '',
    $config['media']['background_sections'][1] ?? '',
    $config['media']['background_sections'][2] ?? ''
];
$sectionStyles = [
    $sectionBackgrounds[0] ? 'style="--section-bg:url(&quot;' . escape_html($sectionBackgrounds[0]) . '&quot;);"' : '',
    $sectionBackgrounds[1] ? 'style="--section-bg:url(&quot;' . escape_html($sectionBackgrounds[1]) . '&quot;);"' : '',
    $sectionBackgrounds[2] ? 'style="--section-bg:url(&quot;' . escape_html($sectionBackgrounds[2]) . '&quot;);"' : ''
];
$qrisImage = $config['gift']['qris_image'] ?? '';
$qrData = rawurlencode($mapsUrl ?: 'https://www.google.com/maps');
$calendarDownloadName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $siteTitle) ?: 'Undangan';
$countdownTarget = $config['schedule']['countdown_target'] ?: ($akadDate . 'T' . $akadTime . '+07:00');
$brideParents = trim(escape_html($config['parents']['bride_father'] . ' & ' . $config['parents']['bride_mother']));
$groomParents = trim(escape_html($config['parents']['groom_father'] . ' & ' . $config['parents']['groom_mother']));

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
  <link href="https://fonts.googleapis.com/css2?family=Allura&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/app/style.css" />
  <script type="application/ld+json">
  <?php echo $schema; ?>
  </script>
</head>
<body>
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

  <main>
    <section id="hero" class="hero" <?php echo $heroStyle; ?>>
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

    <section class="section intro-section">
      <div class="section-head">
        <p class="label">Kepada Yth.</p>
        <h2 id="guestNameDisplay"><?php echo escape_html($guestFallback); ?></h2>
      </div>
    </section>

    <section id="undangan" class="section intro-section" <?php echo $sectionStyles[0]; ?>>
      <div class="section-head">
        <p class="label">Undangan Pernikahan</p>
        <h2><?php echo escape_html($config['wedding']['quote']); ?></h2>
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

    <section id="cerita" class="section panel" <?php echo $sectionStyles[1]; ?>>
      <p class="label">Cerita Kami</p>
      <h2>Perjalanan indah bersama</h2>
      <p><?php echo nl2br(escape_html($config['wedding']['opening_text'])); ?></p>
      <p><?php echo nl2br(escape_html($config['wedding']['closing_text'])); ?></p>
    </section>

    <section id="galeri" class="section panel" <?php echo $sectionStyles[2]; ?>>
      <div class="section-head left">
        <p class="label">Galeri</p>
        <h2>Prewedding Kami</h2>
        <p>Beberapa momen indah kami dalam perjalanan sebelum hari pernikahan.</p>
      </div>
      <button type="button" id="loadGalleryBtn" class="load-gallery-btn">Muat Galeri</button>
      <div id="galleryGrid" class="gallery-grid">
        <p class="loading">Memuat galeri...</p>
      </div>
    </section>

    <section id="acara" class="section">
      <div class="section-head">
        <p class="label">Jadwal Acara</p>
        <h2>Rangkaian Acara</h2>
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

    <section id="lokasi" class="section panel">
      <div class="section-head left">
        <p class="label">Lokasi</p>
        <h2>Tempat Acara</h2>
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

    <section id="amplop" class="section panel">
      <div class="section-head left">
        <p class="label">Amplop Digital</p>
        <h2>Tanda Terima Kasih</h2>
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
          <p class="amplop-feedback">✓ Nomor berhasil disalin</p>
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
          <p class="amplop-feedback">✓ Nomor berhasil disalin</p>
        </div>
        <?php if ($qrisImage): ?>
          <div class="amplop-card amplop-qris-card">
            <div class="amplop-header">QRIS</div>
            <div class="qris-image-wrap"><img src="<?php echo escape_html($qrisImage); ?>" alt="QRIS pembayaran" loading="lazy" decoding="async"></div>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section id="rsvp" class="section panel">
      <div class="section-head left">
        <p class="label">RSVP</p>
        <h2>Konfirmasi Kehadiran</h2>
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
  </main>

  <div id="lightbox" class="lightbox">
    <div class="lightbox-container">
      <button type="button" class="lightbox-close" title="Tutup (Esc)">&times;</button>
      <img id="lightboxImage" src="" alt="Foto galeri" class="lightbox-image" loading="lazy" decoding="async" />
    </div>
  </div>

  <audio id="backgroundMusic" src="<?php echo escape_html($musicSrc); ?>" preload="auto" loop></audio>
  <script src="/app/script.js" defer></script>
</body>
</html>
