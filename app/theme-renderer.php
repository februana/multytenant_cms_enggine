<?php
/**
 * Theme renderer abstraction for the CMS-first wedding invitation app.
 *
 * This keeps the existing config/data contract intact while allowing a preset
 * to choose a different layout structure without duplicating the backend or
 * data model.
 */

function resolve_theme_mode(array $config): string {
    $mode = trim((string)($config['theme']['mode'] ?? ''));
    if ($mode === 'preset' || $mode === 'custom') {
        return $mode;
    }

    $presetKey = trim((string)($config['theme']['theme_preset'] ?? ''));
    return $presetKey !== '' ? 'preset' : 'custom';
}

function resolve_theme_preset_key(array $config): string {
    $mode = resolve_theme_mode($config);
    if ($mode === 'custom') {
        return 'custom';
    }

    $presetKey = trim((string)($config['theme']['theme_preset'] ?? ''));
    $allowed = ['dewankl', 'elix', 'rainier', 'archak', 'parang', 'pawiwahan'];
    if (in_array($presetKey, $allowed, true)) {
        return $presetKey;
    }

    return 'dewankl';
}

function theme_capabilities_for(string $presetKey): array {
    if ($presetKey === 'custom') {
        return [
            'content' => ['wedding', 'schedule', 'countdown', 'gallery', 'music', 'gift', 'maps', 'parents', 'rsvp', 'seo', 'whatsapp', 'sections'],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'gallery_layout', 'navigation', 'footer', 'spacing', 'animation'],
        ];
    }

    $capabilities = function_exists('theme_contract_capabilities') ? theme_contract_capabilities($presetKey) : [];
    return [
        'content' => $capabilities,
        'presentation' => function_exists('theme_presentation_capabilities') ? theme_presentation_capabilities(['theme' => ['mode' => 'preset', 'theme_preset' => $presetKey]]) : [],
    ];
}

function theme_preset_layout_order(string $presetKey, ?array $config = null): array {
    // Built-in themes render their own order inside themes/<preset>/layout.php.
    // This order is only used by the CMS-native Custom renderer.
    if ($presetKey === 'custom' && is_array($config)) {
        $sections = (array)($config['sections'] ?? []);
        usort($sections, static fn(array $a, array $b): int => (int)($a['order'] ?? 0) <=> (int)($b['order'] ?? 0));
        return array_values(array_filter(array_map(static fn($section): string => normalize_section_id((string)($section['id'] ?? '')), $sections)));
    }

    // A built-in preset owns its order in themes/<preset>/layout.php. Do not
    // manufacture a universal fallback order for built-in themes.
    return [];
}

function theme_presentation_capabilities(array $config): array {
    $themeMode = resolve_theme_mode($config);
    if ($themeMode === 'custom') {
        return ['colors', 'typography', 'hero', 'background', 'cards', 'gallery_layout', 'navigation', 'footer', 'spacing', 'animation'];
    }

    $presetKey = resolve_theme_preset_key($config);
    $meta = get_active_theme_meta($config);
    if (!empty($meta['presentation']) && is_array($meta['presentation'])) {
        return array_values($meta['presentation']);
    }
    return ['colors', 'typography', 'hero', 'background', 'cards', 'gallery_layout', 'navigation', 'footer', 'spacing', 'animation'];
}

function theme_supports_presentation(array $config, string $capability): bool {
    $caps = theme_presentation_capabilities($config);
    return in_array($capability, $caps, true);
}

function render_theme_header(array $config, string $presetKey): string {
    $brand = escape_html($config['wedding']['bride_name']) . ' &amp; ' . escape_html($config['wedding']['groom_name']);

    switch ($presetKey) {
        case 'elix':
            return '<header class="topbar topbar-elix"><div class="brand-wrap"><a class="brand" href="#hero">' . $brand . '</a></div><nav><a href="#undangan">Undangan</a><a href="#cerita">Cerita</a><a href="#galeri">Galeri</a><a href="#acara">Acara</a><a href="#lokasi">Lokasi</a><a href="#rsvp">Konfirmasi Kehadiran</a></nav><button type="button" id="dataSaverBtn" class="data-saver-btn" title="Hemat data: matikan musik otomatis & pemuatan galeri bertahap">📊 Mode Hemat</button></header>';
        case 'rainier':
            return '<header class="topbar topbar-rainier"><div class="brand-wrap"><a class="brand" href="#hero">' . $brand . '</a></div><nav><a href="#undangan">Undangan</a><a href="#acara">Acara</a><a href="#cerita">Cerita</a><a href="#galeri">Galeri</a><a href="#lokasi">Lokasi</a></nav><button type="button" id="dataSaverBtn" class="data-saver-btn" title="Hemat data: matikan musik otomatis & pemuatan galeri bertahap">📊 Mode Hemat</button></header>';
        case 'archak':
            return '<header class="topbar topbar-archak"><div class="brand-wrap"><a class="brand" href="#hero">' . $brand . '</a></div><nav><a href="#hero">Beranda</a><a href="#acara">Jadwal</a><a href="#cerita">Kisah</a><a href="#galeri">Galeri</a><a href="#rsvp">Konfirmasi Kehadiran</a></nav><button type="button" id="dataSaverBtn" class="data-saver-btn" title="Hemat data: matikan musik otomatis & pemuatan galeri bertahap">📊 Mode Hemat</button></header>';
        case 'dewankl':
        default:
            return '<header class="topbar"><a class="brand" href="#hero">' . $brand . '</a><nav><a href="#undangan">Undangan</a><a href="#cerita">Cerita</a><a href="#galeri">Galeri</a><a href="#acara">Acara</a><a href="#lokasi">Lokasi</a><a href="#rsvp">Konfirmasi Kehadiran</a></nav><button type="button" id="dataSaverBtn" class="data-saver-btn" title="Hemat data: matikan musik otomatis & pemuatan galeri bertahap">📊 Mode Hemat</button></header>';
    }
}

function render_theme_footer(array $config): string {
    $brand = escape_html($config['wedding']['bride_name']) . ' &amp; ' . escape_html($config['wedding']['groom_name']);
    return '<footer class="site-footer"><div class="footer-inner"><p>Terima kasih atas doa dan restu.</p><p>' . $brand . '</p></div></footer>';
}

function render_shared_section_block(array $config, string $sectionId, array $shared): string {
    $sectionPrefix = 'theme-section ' . $sectionId;
    $musicEnabled = is_section_enabled($config, 'music');
    $guestFallback = $shared['guestFallback'];
    $guestName = normalize_guest_name((string)($shared['guestName'] ?? ''));
    $guestDisplay = $guestName !== '' ? $guestName : $guestFallback;
    $calendarLink = $shared['calendarLink'];
    $whatsappLink = $shared['whatsappLink'];
    $countdownTarget = $shared['countdownTarget'];
    $sectionStyles = $shared['sectionStyles'];
    $dresscodeTitle = (string)($config['dresscode']['title'] ?? 'Dresscode');
    $dresscodeTitle = $dresscodeTitle === '' ? 'Dresscode' : $dresscodeTitle;
    $dresscodeColor = (string)($config['dresscode']['color'] ?? 'Putih / Pastel');
    $dresscodeColor = $dresscodeColor === '' ? 'Putih / Pastel' : $dresscodeColor;
    $dresscodeRule = (string)($config['dresscode']['rule'] ?? 'Rapi dan sopan');
    $dresscodeRule = $dresscodeRule === '' ? 'Rapi dan sopan' : $dresscodeRule;
    $dresscodeDescription = (string)($config['dresscode']['description'] ?? 'Kenakan busana terbaikmu untuk momen spesial.');
    $dresscodeDescription = $dresscodeDescription === '' ? 'Kenakan busana terbaikmu untuk momen spesial.' : $dresscodeDescription;

    switch ($sectionId) {
        case 'countdown':
            return '<section id="countdown-section" class="section countdown-section"><div class="section-head"><p class="label">Menuju Hari Bahagia</p><h2>Hitung Mundur Pernikahan</h2></div><div id="countdown" class="countdown" data-countdown="' . escape_html($countdownTarget) . '" aria-label="Hitung mundur acara"><div><strong>00</strong><span>Hari</span></div><div><strong>00</strong><span>Jam</span></div><div><strong>00</strong><span>Menit</span></div><div><strong>00</strong><span>Detik</span></div></div></section>';
        case 'guest_intro':
            return '<section id="guest-intro" class="section intro-section" style="background:transparent;padding:60px 20px 40px"><div class="invitation-frame"><div class="ornament-corner top-left"></div><div class="ornament-corner top-right"></div><div class="ornament-corner bottom-left"></div><div class="ornament-corner bottom-right"></div><div class="section-head"><p class="label">' . escape_html(get_section_title($config, 'guest_intro', 'Kepada Yth.')) . '</p><h2 id="guestNameDisplay">' . escape_html($guestDisplay) . '</h2></div></div></section>';
        case 'undangan':
            return '<section id="undangan" class="section intro-section" ' . ($sectionStyles[0] ?? '') . '><div class="invitation-frame"><div class="ornament-corner top-left"></div><div class="ornament-corner top-right"></div><div class="ornament-corner bottom-left"></div><div class="ornament-corner bottom-right"></div><div class="section-head"><p class="label">' . escape_html(get_section_title($config, 'undangan', 'Undangan Pernikahan')) . '</p><h2>' . nl2br(escape_html(get_section_subtitle($config, 'undangan', $config['wedding']['quote']))) . '</h2></div><p style="max-width:680px;margin:0 auto 34px;font-size:1.15rem;line-height:1.9;text-align:center;color:var(--muted);white-space:pre-line">' . nl2br(escape_html($config['wedding']['opening_text'])) . '</p></div></section>';
        case 'acara':
            $akadDate = $config['schedule']['akad_date'];
            $akadTime = $config['schedule']['akad_time'];
            $receptionDate = $config['schedule']['reception_date'];
            $receptionTime = $config['schedule']['reception_time'];
            $locationAddress = $config['location']['address'];
            $cards = '<article class="card"><h3>Akad Nikah</h3><p>' . date('j F Y', strtotime($akadDate)) . '</p><p>' . escape_html($akadTime) . ' WIB</p><p>' . render_preserved_text($locationAddress) . '</p></article><article class="card"><h3>Resepsi</h3><p>' . date('j F Y', strtotime($receptionDate)) . '</p><p>' . escape_html($receptionTime) . ' WIB - Selesai</p><p>' . render_preserved_text($locationAddress) . '</p></article>';
            if (!empty($config['dresscode']['enabled'])) {
                $cards .= '<article class="card"><h3>' . escape_html($dresscodeTitle) . '</h3><p>' . render_preserved_text($dresscodeColor) . '</p><p>' . render_preserved_text($dresscodeRule) . '</p><p>' . render_preserved_text($dresscodeDescription) . '</p></article>';
            }
            return '<section id="acara" class="section panel"><div class="invitation-frame"><div class="ornament-corner top-left"></div><div class="ornament-corner top-right"></div><div class="ornament-corner bottom-left"></div><div class="ornament-corner bottom-right"></div><div class="section-head"><p class="label">' . escape_html(get_section_title($config, 'acara', 'Jadwal Acara')) . '</p><h2>' . escape_html(get_section_subtitle($config, 'acara', 'Rangkaian Acara')) . '</h2></div><div class="cards-grid">' . $cards . '</div></div></section>';
        case 'cerita':
            return '<section id="cerita" class="section intro-section" ' . ($sectionStyles[1] ?? '') . '><div class="invitation-frame"><div class="ornament-corner top-left"></div><div class="ornament-corner top-right"></div><div class="ornament-corner bottom-left"></div><div class="ornament-corner bottom-right"></div><div class="section-head"><p class="label">' . escape_html(get_section_title($config, 'cerita', 'Cerita Kami')) . '</p><h2>' . escape_html(get_section_subtitle($config, 'cerita', 'Perjalanan indah bersama')) . '</h2></div><div id="loveStoryContainer"><p class="loading">Memuat cerita...</p></div></div></section>';
        case 'galeri':
            return '<section id="galeri" class="section intro-section" ' . ($sectionStyles[2] ?? '') . '><div class="invitation-frame"><div class="ornament-corner top-left"></div><div class="ornament-corner top-right"></div><div class="ornament-corner bottom-left"></div><div class="ornament-corner bottom-right"></div><div class="section-head left"><p class="label">' . escape_html(get_section_title($config, 'galeri', 'Galeri')) . '</p><h2>' . escape_html(get_section_subtitle($config, 'galeri', 'Prewedding Kami')) . '</h2><p style="max-width:680px;margin:0 auto 34px;font-size:1.15rem;line-height:1.9;text-align:center;color:var(--muted)">Beberapa momen indah kami dalam perjalanan sebelum hari pernikahan.</p></div><button type="button" id="loadGalleryBtn" class="load-gallery-btn" style="display:none; margin:20px auto; padding:10px 20px; background:#d4a574; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:500;">Muat Galeri</button><div id="galleryGrid" class="gallery-grid"><p class="loading">Memuat galeri...</p></div></div></section>';
        case 'lokasi':
            $locationAddress = $config['location']['address'];
            $mapsUrl = $config['location']['maps_url'];
            $mapsEmbed = $config['location']['maps_embed'];
            $venue = $config['location']['venue'];
            $qrData = rawurlencode($mapsUrl ?: 'https://www.google.com/maps');
            $qrUrl = escape_html(public_path('/admin/qr.php?data=' . $qrData));
            return '<section id="lokasi" class="section panel"><div class="invitation-frame"><div class="ornament-corner top-left"></div><div class="ornament-corner top-right"></div><div class="ornament-corner bottom-left"></div><div class="ornament-corner bottom-right"></div><div class="section-head left"><p class="label">' . escape_html(get_section_title($config, 'lokasi', 'Lokasi')) . '</p><h2>' . escape_html(get_section_subtitle($config, 'lokasi', 'Tempat Acara')) . '</h2></div><div class="location-grid"><div class="card"><h3>Alamat</h3><p>' . escape_html($venue) . '</p><p>' . render_preserved_text($locationAddress) . '</p><p><a href="' . escape_html($mapsUrl) . '" target="_blank" rel="noopener noreferrer">Buka di Google Maps</a></p></div><div class="card"><h3>QR Lokasi</h3><p class="location-qr"><strong>Scan untuk arah</strong><br /><img id="qrLokasiImg" src="' . $qrUrl . '" alt="QR kode lokasi pernikahan" loading="lazy" decoding="async" /></p></div><div class="map-wrap"><iframe src="' . escape_html($mapsEmbed) . '" title="Lokasi acara" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe><div class="map-footnote">Titik lokasi tepat: ' . escape_html($mapsUrl) . '</div></div></div></div></section>';
        case 'amplop':
            $giftBank = $config['gift']['bank'];
            $giftAccount = $config['gift']['account_number'];
            $giftEwalletLabel = $config['gift']['e_wallet_label'];
            $giftEwalletNumber = $config['gift']['e_wallet_number'];
            return '<section id="amplop" class="section panel"><div class="invitation-frame"><div class="ornament-corner top-left"></div><div class="ornament-corner top-right"></div><div class="ornament-corner bottom-left"></div><div class="ornament-corner bottom-right"></div><div class="section-head left"><p class="label">' . escape_html(get_section_title($config, 'amplop', 'Amplop Digital')) . '</p><h2>' . escape_html(get_section_subtitle($config, 'amplop', 'Tanda Terima Kasih')) . '</h2><p>Jika ingin memberikan amplop digital, berikut data rekening:</p></div><div class="amplop-container"><div class="amplop-card"><div class="amplop-header">Untuk ' . escape_html($config['wedding']['bride_name']) . '</div><div class="amplop-item"><label>Bank:</label><span>' . escape_html($giftBank) . '</span></div><div class="amplop-item"><label>Nomor Rekening:</label><span class="amplop-number" data-account="' . escape_html($giftAccount) . '">' . escape_html($giftAccount) . '</span></div><button type="button" class="amplop-copy-btn" data-account="' . escape_html($giftAccount) . '">Salin Nomor</button><p class="amplop-feedback" style="display:none;color:#4CAF50;font-size:12px;margin-top:8px;">✓ Nomor berhasil disalin</p></div><div class="amplop-card"><div class="amplop-header">Untuk ' . escape_html($config['wedding']['groom_name']) . '</div><div class="amplop-item"><label>E-Wallet:</label><span>' . escape_html($giftEwalletLabel) . '</span></div><div class="amplop-item"><label>Nomor Telepon:</label><span class="amplop-number" data-account="' . escape_html($giftEwalletNumber) . '">' . escape_html($giftEwalletNumber) . '</span></div><button type="button" class="amplop-copy-btn" data-account="' . escape_html($giftEwalletNumber) . '">Salin Nomor</button><p class="amplop-feedback" style="display:none;color:#4CAF50;font-size:12px;margin-top:8px;">✓ Nomor berhasil disalin</p></div></div></section>';
        case 'rsvp':
            return '<section id="rsvp" class="section panel"><div class="invitation-frame"><div class="ornament-corner top-left"></div><div class="ornament-corner top-right"></div><div class="ornament-corner bottom-left"></div><div class="ornament-corner bottom-right"></div><div class="section-head left"><p class="label">' . escape_html(get_section_title($config, 'rsvp', 'Konfirmasi Kehadiran')) . '</p><h2>' . escape_html(get_section_subtitle($config, 'rsvp', 'Konfirmasi Kehadiran')) . '</h2></div><form id="rsvpForm" class="rsvp-form"><input type="hidden" name="csrf_token" id="csrfToken" /><label>Nama<input type="text" name="nama" placeholder="Nama Anda" required /></label><label>Kehadiran<select name="status" required><option value="Hadir">Hadir</option><option value="Tidak Hadir">Tidak Hadir</option></select></label><label>Ucapan<textarea name="ucapan" rows="4" placeholder="Tulis ucapan dan doa"></textarea></label><input type="text" name="website" autocomplete="off" tabindex="-1" aria-hidden="true" style="display:none"><button type="submit">Kirim Konfirmasi Kehadiran</button><p id="formMessage" class="form-message" role="status" aria-live="polite"></p></form>' . (is_section_enabled($config, 'messages') ? '<div id="messages" class="messages"></div>' : '') . '</div></section>';
        case 'hero':
            return render_theme_hero_markup($config, $shared['presetKey'], $shared['bgHero'], $shared['heroText'], $shared['brideParents'], $shared['groomParents'], $shared['calendarLink'], $shared['whatsappLink'], $shared['musicSrc'], $shared['calendarDownloadName'], $shared['guestFallback']);
        default:
            return '';
    }
}

function render_theme_layout(array $config, array $shared): string {
    $presetKey = resolve_theme_preset_key($config);
    
    // Try to load theme layout from themes/<preset>/layout.php
    $layoutFile = __DIR__ . '/../themes/' . $presetKey . '/layout.php';
    
    if ($presetKey !== 'custom' && file_exists($layoutFile)) {
        // Load the theme layout template. Built-in themes own their complete
        // document and must not fall through to the CMS section renderer.
        ob_start();
        include $layoutFile;
        return ob_get_clean();
    }

    if ($presetKey !== 'custom') {
        // A missing built-in layout is a deployment defect, not a reason to
        // silently replace the theme with the CMS-native universal structure.
        error_log('Built-in theme layout missing: ' . $presetKey);
        return '<!-- Built-in theme layout unavailable: ' . escape_html($presetKey) . ' -->';
    }

    // Only Custom mode uses the CMS-native inline renderer.
    $parts = [];
    $themeOrder = theme_preset_layout_order($presetKey, $config);
    $parts[] = render_theme_header($config, $presetKey);
    foreach ($themeOrder as $sectionId) {
        if ($sectionId === 'hero' && is_section_enabled($config, 'hero')) {
            $parts[] = render_shared_section_block($config, 'hero', $shared);
            continue;
        }
        if ($sectionId === 'footer') {
            continue;
        }
        if (is_section_enabled($config, $sectionId)) {
            $parts[] = render_shared_section_block($config, $sectionId, $shared);
        }
    }
    $parts[] = render_theme_footer($config);
    return implode("\n", $parts);
}

function render_theme_hero_markup(array $config, string $presetKey, string $bgHero, string $heroText, string $brideParents, string $groomParents, string $calendarLink, string $whatsappLink, string $musicSrc, string $calendarDownloadName, string $guestFallback): string {
    $themeMode = resolve_theme_mode($config);
    $altTitle = $config['wedding']['bride_name'] . ' & ' . $config['wedding']['groom_name'];
    $shared = [
        'bgHero' => $bgHero,
        'heroText' => $heroText,
        'brideParents' => $brideParents,
        'groomParents' => $groomParents,
        'calendarLink' => $calendarLink,
        'whatsappLink' => $whatsappLink,
        'musicSrc' => $musicSrc,
        'calendarDownloadName' => $calendarDownloadName,
        'guestFallback' => $guestFallback,
        'altTitle' => $altTitle,
    ];

    switch ($presetKey) {
        case 'elix':
            return render_elix_hero($config, $shared);
        case 'rainier':
            return render_rainier_hero($config, $shared);
        case 'archak':
            return render_archak_hero($config, $shared);
        case 'dewankl':
        default:
            return render_dewankl_hero($config, $shared);
    }
}

function render_dewankl_hero(array $config, array $shared): string {
    $themeMode = resolve_theme_mode($config);
    $musicButton = is_section_enabled($config, 'music') ? '<button class="music-btn" type="button" id="musicBtn">Putar Musik</button>' : '';
    $guestName = normalize_guest_name((string)($shared['guestName'] ?? ''));
    $guestLabel = escape_html($guestName !== '' ? $guestName : ($shared['guestFallback'] ?? 'Bapak/Ibu/Saudara/i'));
    return '<section id="hero" class="hero" ' . $shared['bgHero'] . '>
      <div class="hero-card hero-card--classic">
        <p class="eyebrow">Kami Akan Menikah</p>
        <p id="guest-greeting" class="hero-guest">Kepada Yth. ' . $guestLabel . '</p>
        <h1>' . escape_html($config['wedding']['bride_name']) . ' &amp; ' . escape_html($config['wedding']['groom_name']) . '</h1>
        <p class="hero-text">' . render_preserved_text($shared['heroText']) . '</p>
        <p class="hero-subtitle">' . escape_html($config['wedding']['bride_nickname']) . ' &amp; ' . escape_html($config['wedding']['groom_nickname']) . '</p>
        <p class="hero-parents">Putra dari ' . $shared['groomParents'] . ' dan Putri dari ' . $shared['brideParents'] . '.</p>
        <div class="hero-actions">
          <button type="button" id="openInvitationBtn">Buka Undangan</button>
          <a class="calendar-btn" href="' . escape_html($shared['calendarLink']) . '" target="_blank" rel="noreferrer noopener">Tambah ke Kalender</a>
          <a class="calendar-btn" href="event.ics" download="' . escape_html($shared['calendarDownloadName']) . '.ics" title="Unduh file kalender (.ics)">Unduh Kalender</a>
          <a class="whatsapp-btn" href="' . escape_html($shared['whatsappLink']) . '" target="_blank" rel="noopener noreferrer">Hubungi WA</a>
        </div>
        ' . $musicButton . '
      </div>
    </section>';
}

function render_elix_hero(array $config, array $shared): string {
    $musicButton = is_section_enabled($config, 'music') ? '<button class="music-btn" type="button" id="musicBtn">Putar Musik</button>' : '';
    return '<section id="hero" class="hero hero-elix" ' . $shared['bgHero'] . '>
      <div class="hero-card hero-card--soft">
        <p class="eyebrow">Kami Akan Menikah</p>
        <h1>' . escape_html($config['wedding']['bride_name']) . ' &amp; ' . escape_html($config['wedding']['groom_name']) . '</h1>
        <p class="hero-text">' . render_preserved_text($shared['heroText']) . '</p>
        <p class="hero-subtitle">' . escape_html($config['wedding']['bride_nickname']) . ' &amp; ' . escape_html($config['wedding']['groom_nickname']) . '</p>
        <div class="hero-actions hero-actions--compact">
          <button type="button" id="openInvitationBtn">Buka Undangan</button>
          <a class="calendar-btn" href="' . escape_html($shared['calendarLink']) . '" target="_blank" rel="noreferrer noopener">Tambah ke Kalender</a>
          <a class="whatsapp-btn" href="' . escape_html($shared['whatsappLink']) . '" target="_blank" rel="noopener noreferrer">Hubungi WA</a>
        </div>
        ' . $musicButton . '
      </div>
    </section>';
}

function render_rainier_hero(array $config, array $shared): string {
    $musicButton = is_section_enabled($config, 'music') ? '<button class="music-btn" type="button" id="musicBtn">Putar Musik</button>' : '';
    return '<section id="hero" class="hero hero-rainier" ' . $shared['bgHero'] . '>
      <div class="hero-card hero-card--editorial">
        <p class="eyebrow">Kami Akan Menikah</p>
        <h1>' . escape_html($config['wedding']['bride_name']) . ' &amp; ' . escape_html($config['wedding']['groom_name']) . '</h1>
        <p class="hero-text">' . render_preserved_text($shared['heroText']) . '</p>
        <div class="hero-meta-row">
          <span>' . escape_html($config['wedding']['bride_nickname']) . '</span>
          <span class="hero-love-mark">&amp;</span>
          <span>' . escape_html($config['wedding']['groom_nickname']) . '</span>
        </div>
        <div class="hero-actions hero-actions--split">
          <button type="button" id="openInvitationBtn">Buka Undangan</button>
          <a class="calendar-btn" href="' . escape_html($shared['calendarLink']) . '" target="_blank" rel="noreferrer noopener">Tambah ke Kalender</a>
          <a class="whatsapp-btn" href="' . escape_html($shared['whatsappLink']) . '" target="_blank" rel="noopener noreferrer">Hubungi WA</a>
        </div>
        ' . $musicButton . '
      </div>
    </section>';
}

function render_archak_hero(array $config, array $shared): string {
    $musicButton = is_section_enabled($config, 'music') ? '<button class="music-btn" type="button" id="musicBtn">Putar Musik</button>' : '';
    return '<section id="hero" class="hero hero-archak" ' . $shared['bgHero'] . '>
      <div class="hero-card hero-card--split">
        <div class="hero-card__text">
          <p class="eyebrow">Kami Akan Menikah</p>
          <h1>' . escape_html($config['wedding']['bride_name']) . ' &amp; ' . escape_html($config['wedding']['groom_name']) . '</h1>
          <p class="hero-text">' . render_preserved_text($shared['heroText']) . '</p>
        </div>
        <div class="hero-card__meta">
          <p class="hero-subtitle">' . escape_html($config['wedding']['bride_nickname']) . ' &amp; ' . escape_html($config['wedding']['groom_nickname']) . '</p>
          <p class="hero-parents">Putra dari ' . $shared['groomParents'] . ' dan Putri dari ' . $shared['brideParents'] . '.</p>
          <div class="hero-actions hero-actions--stack">
            <button type="button" id="openInvitationBtn">Buka Undangan</button>
            <a class="calendar-btn" href="' . escape_html($shared['calendarLink']) . '" target="_blank" rel="noreferrer noopener">Tambah ke Kalender</a>
            <a class="whatsapp-btn" href="' . escape_html($shared['whatsappLink']) . '" target="_blank" rel="noopener noreferrer">Hubungi WA</a>
          </div>
          ' . $musicButton . '
        </div>
      </div>
    </section>';
}
