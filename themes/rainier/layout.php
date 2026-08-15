<?php
/**
 * Rainier Theme - Complete Frontend Template
 */
if (!defined('THEME_HELPER_LOADED')) { require_once __DIR__ . '/../../app/theme-helper.php'; }
$config = $config ?? [];
$brideName = escape_html($config['wedding']['bride_name'] ?? '');
$groomName = escape_html($config['wedding']['groom_name'] ?? '');
$brideNickname = escape_html($config['wedding']['bride_nickname'] ?? '');
$groomNickname = escape_html($config['wedding']['groom_nickname'] ?? '');
$openingText = nl2br(escape_html($config['wedding']['opening_text'] ?? ''));
$quote = nl2br(escape_html($config['wedding']['quote'] ?? ''));
$closingText = nl2br(escape_html($config['wedding']['closing_text'] ?? ''));
$brideFather = escape_html($config['parents']['bride_father'] ?? '');
$brideMother = escape_html($config['parents']['bride_mother'] ?? '');
$groomFather = escape_html($config['parents']['groom_father'] ?? '');
$groomMother = escape_html($config['parents']['groom_mother'] ?? '');
$akadDate = $config['schedule']['akad_date'] ?? '';
$akadTime = $config['schedule']['akad_time'] ?? '';
$receptionDate = $config['schedule']['reception_date'] ?? '';
$receptionTime = $config['schedule']['reception_time'] ?? '';
$venue = escape_html($config['location']['venue'] ?? '');
$address = escape_html($config['location']['address'] ?? '');
$mapsUrl = escape_html($config['location']['maps_url'] ?? '');
$mapsEmbed = escape_html($config['location']['maps_embed'] ?? '');
$giftBank = escape_html($config['gift']['bank'] ?? '');
$giftAccount = escape_html($config['gift']['account_number'] ?? '');
$giftHolder = escape_html($config['gift']['account_holder'] ?? '');
$giftEwalletLabel = escape_html($config['gift']['e_wallet_label'] ?? '');
$giftEwalletNumber = escape_html($config['gift']['e_wallet_number'] ?? '');
$coverPath = $config['media']['cover'] ?? 'uploads/cover/cover.jpg';
$musicSrc = $config['media']['music'] ?? 'music/lagu.mp3';
$whatsappLink = build_whatsapp_link($config);
$calendarLink = build_google_calendar_link($config);
$calendarDownloadName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $config['site']['title'] ?? 'Undangan');
$isMusicEnabled = is_section_enabled($config, 'music');
$isGalleryEnabled = is_section_enabled($config, 'gallery');
$isRsvpEnabled = is_section_enabled($config, 'rsvp');
$isGiftEnabled = is_section_enabled($config, 'gift');
$isMapsEnabled = is_section_enabled($config, 'lokasi');
$isCoupleEnabled = is_section_enabled($config, 'couple');
$isEventEnabled = is_section_enabled($config, 'event');
$akadDateFormatted = $akadDate ? date('l, j F Y', strtotime($akadDate)) : '';
$receptionDateFormatted = $receptionDate ? date('l, j F Y', strtotime($receptionDate)) : '';
$guestName = isset($_GET['to']) ? escape_html($_GET['to']) : '';
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?php echo escape_html($config['site']['title'] ?? 'Undangan Pernikahan'); ?></title><meta name="description" content="<?php echo escape_html($config['site']['description'] ?? ''); ?>"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="<?php echo get_theme_asset_url('rainier', 'style.css'); ?>"><link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet"><?php $customCss = load_custom_css(); if (!empty($customCss)): ?><style><?php echo $customCss; ?></style><?php endif; ?></head><body class="rainier-theme"><div id="loading-screen" class="loading-screen"><div class="loading-content"><div class="loading-spinner"></div><p class="loading-text">Loading Invitation...</p></div></div><div id="welcome-overlay" class="welcome-overlay"><div class="overlay-background"></div><div class="overlay-content glass-panel"><div class="bismillah"><span class="arabic-text">بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ</span></div><h2 class="overlay-title">The Wedding of</h2><h1 class="overlay-couple-names"><span class="name-first"><?php echo $brideNickname; ?></span><span class="name-ampersand">&</span><span class="name-second"><?php echo $groomNickname; ?></span></h1><p class="overlay-date"><?php echo $akadDateFormatted; ?></p><?php if (!empty($guestName)): ?><p class="guest-greeting">Kepada Yth.<br><strong><?php echo $guestName; ?></strong></p><?php endif; ?><button id="open-invitation" class="btn-open-invitation"><span class="btn-text">Buka Undangan</span><svg class="btn-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></button></div></div><main id="main-content" class="main-content hidden"><nav id="navbar" class="navbar-glass"><div class="navbar-container"><div class="navbar-brand"><span class="brand-initials"><?php echo substr($brideNickname, 0, 1) . '&' . substr($groomNickname, 0, 1); ?></span></div><ul class="navbar-menu"><li><a href="#home" class="nav-link">Home</a></li><li><a href="#couple" class="nav-link">Couple</a></li><li><a href="#event" class="nav-link">Event</a></li><?php if ($isGalleryEnabled): ?><li><a href="#gallery" class="nav-link">Gallery</a></li><?php endif; ?><?php if ($isMapsEnabled): ?><li><a href="#location" class="nav-link">Location</a></li><?php endif; ?><?php if ($isRsvpEnabled): ?><li><a href="#rsvp" class="nav-link">RSVP</a></li><?php endif; ?></ul><button class="navbar-toggle" aria-label="Toggle navigation"><span class="hamburger-line"></span><span class="hamburger-line"></span><span class="hamburger-line"></span></button></div></nav>
