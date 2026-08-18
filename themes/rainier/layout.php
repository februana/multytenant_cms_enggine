<?php
if (!defined('THEME_HELPER_LOADED')) {
    require_once __DIR__ . '/../../app/theme-helper.php';
}
$config = $config ?? [];
$presetKey = 'rainier';
$brideName = escape_html($config['wedding']['bride_name'] ?? '');
$groomName = escape_html($config['wedding']['groom_name'] ?? '');
$siteTitle = escape_html($config['site']['title'] ?? ($brideName . ' & ' . $groomName));
$descriptionRaw = (string)($config['site']['description'] ?? $config['wedding']['opening_text'] ?? '');
$description = escape_html($descriptionRaw);
$openingRaw = (string)($config['wedding']['opening_text'] ?? '');
$openingText = render_preserved_text($openingRaw);
$quoteRaw = (string)($config['wedding']['quote'] ?? '');
$quoteText = render_preserved_text($quoteRaw);
$closingRaw = (string)($config['wedding']['closing_text'] ?? '');
$closingText = render_preserved_text($closingRaw);
$akadDate = (string)($config['schedule']['akad_date'] ?? '');
$akadTime = (string)($config['schedule']['akad_time'] ?? '');
$receptionDate = (string)($config['schedule']['reception_date'] ?? '');
$receptionTime = (string)($config['schedule']['reception_time'] ?? '');
$venue = (string)($config['location']['venue'] ?? '');
$address = (string)($config['location']['address'] ?? '');
$mapsUrl = (string)($config['location']['maps_url'] ?? '');
$coverPath = (string)($config['media']['cover'] ?? '');
$musicPath = (string)($config['media']['music'] ?? '');
$eventTimezone = trim((string)($config['schedule']['timezone'] ?? ''));
$guestName = function_exists('resolve_guest_name') ? resolve_guest_name($config) : '';
$guestLabel = escape_html($guestName !== '' ? $guestName : 'Bapak/Ibu/Saudara/i');
$csrf = function_exists('get_csrf_token') ? get_csrf_token() : '';
$calendarLink = build_google_calendar_link($config);
$stories = $config['love_story']['items'] ?? [];
if (!is_array($stories)) $stories = [];
$quotes = [];
foreach ($stories as $story) {
    if (!is_array($story)) continue;
    $text = (string)($story['description'] ?? $story['text'] ?? '');
    $author = (string)($story['title'] ?? '');
    if (trim($text) !== '') $quotes[] = ['text' => $text, 'author' => $author];
}
if (!$quotes && trim($quoteRaw) !== '') $quotes[] = ['text' => $quoteRaw, 'author' => $brideName . ' & ' . $groomName];
$schedule = [];
if ($akadDate !== '' || $akadTime !== '') $schedule[] = ['time' => trim($akadTime), 'label' => 'Akad Nikah — ' . trim($venue)];
if ($receptionTime !== '') $schedule[] = ['time' => trim($receptionTime), 'label' => 'Resepsi — ' . trim($venue)];
$eventEndTime = ($receptionDate !== '' && $receptionDate === $akadDate && $receptionTime !== '' && $receptionTime > $akadTime) ? $receptionTime : '';
$eventData = [
    'event' => ['title' => html_entity_decode($brideName . ' & ' . $groomName, ENT_QUOTES, 'UTF-8'), 'subtitle' => $openingRaw, 'description' => $descriptionRaw],
    'datetime' => ['date' => $akadDate, 'startTime' => $akadTime, 'endTime' => $eventEndTime, 'timezone' => $eventTimezone, 'allDay' => false],
    'location' => ['name' => $venue, 'address' => $address, 'mapsLink' => $mapsUrl],
    'calendar' => ['enabled' => true, 'providers' => ['google' => true]],
    'schedule' => $schedule,
    'quotes' => $quotes,
    'rsvp' => ['enabled' => theme_section_enabled($config, $presetKey, 'rsvp'), 'provider' => 'cms', 'url' => public_path('save.php')],
    'music' => ['enabled' => $musicPath !== '' && theme_section_enabled($config, $presetKey, 'music'), 'audioUrl' => public_path($musicPath), 'loop' => true, 'volume' => 0.3],
    'meta' => ['countdown' => true, 'simpleMode' => false, 'showSimpleModeToggle' => false],
    'design' => ['accentColor' => $config['theme_options']['rainier']['hero_accent_color'] ?? '#b8655d', 'backgrounds' => $coverPath !== '' ? [public_path($coverPath)] : []],
    'footer' => ['text' => $closingRaw, 'credits' => ['designByLabel' => 'Diselenggarakan oleh', 'copyrightYear' => date('Y'), 'authorName' => html_entity_decode($brideName . ' & ' . $groomName, ENT_QUOTES, 'UTF-8'), 'templateLabel' => 'Templat', 'templateAuthor' => 'Rainier', 'templateLink' => 'https://github.com/Rainier-PS/Invitation-Template', 'repoLink' => 'https://github.com/Rainier-PS/Invitation-Template', 'repoLabel' => 'Templat sumber']],
];
$customCss = function_exists('load_custom_css') ? load_custom_css() : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="<?php echo $description; ?>" />
    <meta name="csrf-token" content="<?php echo escape_html($csrf); ?>" />
    <meta property="og:title" content="<?php echo $siteTitle; ?>" />
    <meta property="og:description" content="<?php echo $description; ?>" />
    <title><?php echo $siteTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo get_theme_asset_url($presetKey, 'original/invite.css'); ?>">
    <?php if ($customCss !== ''): ?><style><?php echo $customCss; ?></style><?php endif; ?>
    <script defer src="<?php echo get_theme_asset_url($presetKey, 'original/invite-1-adapter.js'); ?>"></script>
</head>
<body>
    <button id="simple-mode-toggle" class="accessibility-fab" aria-label="Alihkan Mode Sederhana" data-tooltip="Beralih antara tampilan standar dan mudah dibaca" hidden><svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z" id="standard-view-icon" /><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z" id="simple-view-icon" class="hidden-icon" /></svg></button>
    <button id="audio-control" class="audio-fab" aria-label="Putar Musik" data-tooltip="Nyalakan atau matikan musik latar" hidden><svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M8 5v14l11-7z" id="play-icon" /><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" id="pause-icon" class="hidden-icon" /></svg></button>
    <main id="app" aria-live="polite">
        <?php if (theme_section_enabled($config, $presetKey, 'hero')): ?><section class="hero"><p id="guest-greeting" class="guest-greeting">Kepada Yth. <?php echo $guestLabel; ?></p><h1 id="event-title"><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></h1><p class="subtitle" id="event-subtitle"><?php echo $openingText; ?></p><div class="hero-meta"><?php if (!empty($config['schedule']['countdown_target'])): ?><div id="countdown" class="countdown" aria-live="polite"><span class="countdown-label">Acara dimulai dalam</span><div class="countdown-grid"><div class="countdown-unit"><strong id="cd-days">0</strong><span>Hari</span></div><div class="countdown-unit"><strong id="cd-hours">00</strong><span>Jam</span></div><div class="countdown-unit"><strong id="cd-minutes">00</strong><span>Menit</span></div><div class="countdown-unit"><strong id="cd-seconds">00</strong><span>Detik</span></div></div></div><?php endif; ?><div id="calendar-actions"><a id="google-calendar-link" target="_blank" rel="noopener" href="<?php echo escape_html($calendarLink); ?>">Tambahkan ke Google Kalender</a></div></div><?php if (theme_section_enabled($config, $presetKey, 'rsvp')): ?><a href="#rsvp" class="primary-btn">Konfirmasi Kehadiran</a><?php endif; ?></section><?php endif; ?>
        <?php if (theme_section_enabled($config, $presetKey, 'event_details')): ?><section class="section event-details"><h2>Detail Acara</h2><div class="event-datetime"><div><strong>Tanggal:</strong><time id="event-date"><?php echo $akadDate ? escape_html(date('F j, Y', strtotime($akadDate))) : ''; ?></time></div><div><strong>Waktu:</strong><span id="event-time"><?php echo escape_html($akadTime); ?></span></div></div><p id="event-description"><?php echo $description; ?></p><address class="location"><p id="venue-name"><?php echo escape_html($venue); ?></p><p id="venue-address"><?php echo render_preserved_text($address); ?></p><a id="maps-link" target="_blank" rel="noopener" href="<?php echo escape_html($mapsUrl); ?>">Buka di Google Maps</a></address></section><?php endif; ?>
        <?php if (theme_section_enabled($config, $presetKey, 'schedule')): ?><section class="section" id="schedule-section"><h2>Jadwal</h2><ul id="schedule-list"></ul></section><?php endif; ?>
        <?php if (theme_section_enabled($config, $presetKey, 'quotes')): ?><section class="section" id="quotes-section"><h2>Kata-Kata Inspirasi</h2><div class="quotes-container" id="quotes-container"></div></section><?php endif; ?>
        <?php if (theme_section_enabled($config, $presetKey, 'rsvp')): ?><section class="section" id="rsvp"><h2>Konfirmasi Kehadiran</h2><p>Mohon konfirmasi kehadiran Anda</p><div class="form-embed"></div></section><?php endif; ?>
        <footer><div class="footer-branding"><a id="footer-branding-link" href="https://github.com/Rainier-PS/Invitation-Template" class="low-profile-link" target="_blank" rel="noopener"><img id="footer-logo" src="<?php echo escape_html(public_path($coverPath)); ?>" alt="<?php echo $siteTitle; ?>" class="footer-logo"></a><div class="footer-info"><p class="hosted-by" id="footer-credits-label">Diselenggarakan oleh</p><p class="copyright"><span id="footer-copyright">© <?php echo date('Y'); ?> <?php echo $brideName; ?> &amp; <?php echo $groomName; ?></span><span id="footer-template-container"> | <span id="footer-template-label">Templat</span> <a id="footer-template-link" href="https://github.com/Rainier-PS/Invitation-Template" class="low-profile-link" target="_blank" rel="noopener">Rainier</a></span></p><p id="footer-repo-container" class="repo-info"><a id="footer-repo-link" href="https://github.com/Rainier-PS/Invitation-Template" class="low-profile-link" target="_blank" rel="noopener">Templat sumber</a></p><div class="social-links" id="social-links" hidden><a id="instagram-link" class="social-link" target="_blank" rel="noopener"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg><span id="instagram-label">Ikuti di Instagram</span></a></div></div></div><p id="footer-text"><?php echo $closingText; ?></p></footer>
    </main>
    <script id="event-data" type="application/json"><?php echo json_encode($eventData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
</body>
</html>
