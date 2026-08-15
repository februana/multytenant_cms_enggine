<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/theme-renderer.php';
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
$dresscodeTitle = trim((string)($config['dresscode']['title'] ?? 'Dresscode')) ?: 'Dresscode';
$dresscodeColor = trim((string)($config['dresscode']['color'] ?? 'Putih / Pastel')) ?: 'Putih / Pastel';
$dresscodeRule = trim((string)($config['dresscode']['rule'] ?? 'Rapi dan sopan')) ?: 'Rapi dan sopan';
$dresscodeDescription = trim((string)($config['dresscode']['description'] ?? 'Kenakan busana terbaikmu untuk momen spesial.')) ?: 'Kenakan busana terbaikmu untuk momen spesial.';
$whatsappLink = build_whatsapp_link($config);
$calendarLink = build_google_calendar_link($config);
$musicSrc = $config['media']['music'] ?: 'music/lagu.mp3';
$coverPath = $config['media']['cover'] ?: 'uploads/cover/cover.jpg';
$ogImage = $ogImage ?: $coverPath;
$heroBackground = $config['media']['background_hero'] ?: $coverPath;

// Hero theme settings with defaults for backward compatibility
$themeHeroHeight = trim((string)($config['theme']['hero_height'] ?? '')) ?: 'calc(100vh - 80px)';
$themeHeroVAlign = trim((string)($config['theme']['hero_vertical_alignment'] ?? '')) ?: 'center';
$themeHeroContentWidth = trim((string)($config['theme']['hero_content_width'] ?? '')) ?: '900px';
$heroImageFit = trim((string)($config['theme']['hero_image_fit'] ?? '')) ?: 'cover';
$heroImagePosition = trim((string)($config['theme']['hero_image_position'] ?? '')) ?: 'center';
$heroBgSize = $heroImageFit === 'contain' ? 'contain' : ($heroImageFit === 'auto' ? 'auto' : 'cover');
$heroBgRepeat = 'no-repeat';
$heroOverlayStart = trim((string)($config['theme']['hero_overlay_start'] ?? '')) ?: 'rgba(22,12,10,.45)';
$heroOverlayMid = trim((string)($config['theme']['hero_overlay_mid'] ?? '')) ?: 'rgba(40,20,18,.55)';
$heroOverlayEnd = trim((string)($config['theme']['hero_overlay_end'] ?? '')) ?: 'rgba(55,28,24,.72)';

$themeMobileHeroHeight = trim((string)($config['theme']['mobile_hero_height'] ?? '')) ?: '82vh';
$themeMobileHeroVAlign = trim((string)($config['theme']['mobile_hero_vertical_alignment'] ?? '')) ?: 'center';
$themeMobileHeroContentWidth = trim((string)($config['theme']['mobile_hero_content_width'] ?? '')) ?: '100%';
$themeMobileHeroImageFit = trim((string)($config['theme']['mobile_hero_image_fit'] ?? '')) ?: 'cover';
$themeMobileHeroImagePosition = trim((string)($config['theme']['mobile_hero_image_position'] ?? '')) ?: 'center top';

$buttonsMobileLayoutRaw = trim((string)($config['buttons']['mobile_layout'] ?? '')) ?: 'column';
$buttonsMobileLayout = match ($buttonsMobileLayoutRaw) {
    'horizontal', '2-columns' => 'row',
    '1-column' => 'column',
    default => 'column'
};

$bgHero = $heroBackground ? 'style="--hero-bg:url(\'' . escape_html(public_path($heroBackground)) . '\');--hero-height:' . escape_html($themeHeroHeight) . ';--hero-v-align:' . escape_html($themeHeroVAlign) . ';--hero-content-width:' . escape_html($themeHeroContentWidth) . ';--hero-image-fit:' . escape_html($heroBgSize) . ';--hero-image-position:' . escape_html($heroImagePosition) . ';--hero-bg-repeat:' . escape_html($heroBgRepeat) . ';--hero-overlay-start:' . escape_html($heroOverlayStart) . ';--hero-overlay-mid:' . escape_html($heroOverlayMid) . ';--hero-overlay-end:' . escape_html($heroOverlayEnd) . ';--mobile-hero-height:' . escape_html($themeMobileHeroHeight) . ';--mobile-hero-v-align:' . escape_html($themeMobileHeroVAlign) . ';--mobile-hero-content-width:' . escape_html($themeMobileHeroContentWidth) . ';--mobile-hero-image-fit:' . escape_html($themeMobileHeroImageFit) . ';--mobile-hero-image-position:' . escape_html($themeMobileHeroImagePosition) . ';--buttons-mobile-layout:' . escape_html($buttonsMobileLayout) . ';"' : '';
$sectionBackgrounds = [
    $config['media']['background_sections'][0] ?? '',
    $config['media']['background_sections'][1] ?? '',
    $config['media']['background_sections'][2] ?? ''
];
$sectionBackgroundSize = $heroBgSize;
$sectionBackgroundPosition = $heroImagePosition;
$sectionBackgroundRepeat = $heroBgRepeat;
$sectionStyles = [
    $sectionBackgrounds[0] ? 'style="background-image:url(\'' . escape_html(public_path($sectionBackgrounds[0])) . '\');background-size:' . escape_html($sectionBackgroundSize) . ';background-position:' . escape_html($sectionBackgroundPosition) . ';background-repeat:' . escape_html($sectionBackgroundRepeat) . ';"' : '',
    $sectionBackgrounds[1] ? 'style="background-image:url(\'' . escape_html(public_path($sectionBackgrounds[1])) . '\');background-size:' . escape_html($sectionBackgroundSize) . ';background-position:' . escape_html($sectionBackgroundPosition) . ';background-repeat:' . escape_html($sectionBackgroundRepeat) . ';"' : '',
    $sectionBackgrounds[2] ? 'style="background-image:url(\'' . escape_html(public_path($sectionBackgrounds[2])) . '\');background-size:' . escape_html($sectionBackgroundSize) . ';background-position:' . escape_html($sectionBackgroundPosition) . ';background-repeat:' . escape_html($sectionBackgroundRepeat) . ';"' : ''
];
$qrData = rawurlencode($mapsUrl ?: 'https://www.google.com/maps');
$calendarDownloadName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $siteTitle) ?: 'Undangan';
$countdownTarget = $config['schedule']['countdown_target'] ?: ($akadDate . 'T' . $akadTime . '+07:00');
$brideParents = trim(escape_html($config['parents']['bride_father'] . ' & ' . $config['parents']['bride_mother']));
$groomParents = trim(escape_html($config['parents']['groom_father'] . ' & ' . $config['parents']['groom_mother']));
$selectedThemeKey = preg_replace('/[^a-z0-9_-]/i', '', (string)($config['theme']['theme_preset'] ?? 'elegant'));
$themeClasses = [
    'theme-button-' . preg_replace('/[^a-z0-9_-]/i', '', (string)($config['theme']['button_style'] ?? 'rounded')),
    'theme-navbar-' . preg_replace('/[^a-z0-9_-]/i', '', (string)($config['theme']['navbar_style'] ?? 'transparent')),
    'theme-card-' . preg_replace('/[^a-z0-9_-]/i', '', (string)($config['theme']['card_style'] ?? 'elevated')),
    'theme-footer-' . preg_replace('/[^a-z0-9_-]/i', '', (string)($config['theme']['footer_style'] ?? 'centered')),
    empty($config['theme']['animation_enabled']) ? 'theme-animation-off' : 'theme-animation-on'
];
if ($selectedThemeKey !== '' && $selectedThemeKey !== 'custom') {
    $themeClasses[] = 'theme-' . $selectedThemeKey;
}

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
  function get_section_entry($config, $sectionId) {
      $targetId = normalize_section_id((string)$sectionId);
      if (!isset($config['sections']) || !is_array($config['sections'])) {
          return null;
      }
      foreach ($config['sections'] as $section) {
          if (normalize_section_id((string)($section['id'] ?? '')) === $targetId) {
              return $section;
          }
      }
      return null;
  }

  function is_section_enabled($config, $sectionId) {
      $section = get_section_entry($config, $sectionId);
      if ($section === null) {
          return true;
      }
      return !empty($section['enabled']);
  }
  
  function get_section_title($config, $sectionId, $defaultTitle) {
      $section = get_section_entry($config, $sectionId);
      if ($section === null) {
          return $defaultTitle;
      }
      return !empty($section['custom_title']) ? $section['custom_title'] : ($section['title'] ?? $defaultTitle);
  }
  
  function get_section_subtitle($config, $sectionId, $defaultSubtitle) {
      $section = get_section_entry($config, $sectionId);
      if ($section === null) {
          return $defaultSubtitle;
      }
      return !empty($section['custom_subtitle']) ? $section['custom_subtitle'] : ($section['subtitle'] ?? $defaultSubtitle);
  }
  ?>

  <?php
  $activeThemePreset = resolve_theme_preset_key($config);
  $themePageShared = [
      'presetKey' => $activeThemePreset,
      'heroText' => $heroText,
      'guestFallback' => $guestFallback,
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
  echo render_theme_layout($config, $themePageShared);
  ?>

  <div id="lightbox" class="lightbox" style="display:none;">
    <div class="lightbox-container">
      <button type="button" class="lightbox-close" title="Tutup (Esc)">&times;</button>
      <img id="lightboxImage" src="" alt="Foto galeri" class="lightbox-image" loading="lazy" decoding="async" />
    </div>
  </div>

  <?php if (is_section_enabled($config, 'music')): ?>
  <audio id="backgroundMusic" src="<?php echo escape_html($musicSrc); ?>" preload="auto" loop></audio>
  <?php endif; ?>
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
        var bgRepeat = 'no-repeat';
        heroSection.style.backgroundSize = bgSize;
        heroSection.style.backgroundPosition = heroPosition;
        heroSection.style.backgroundRepeat = bgRepeat;
      }

      const mobileLayoutRaw = (theme.buttons && theme.buttons.mobile_layout) ? theme.buttons.mobile_layout : 'column';
      const mobileLayout = mobileLayoutRaw === 'horizontal' || mobileLayoutRaw === '2-columns' ? 'row' : 'column';
      document.documentElement.style.setProperty('--buttons-mobile-layout', mobileLayout);
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
