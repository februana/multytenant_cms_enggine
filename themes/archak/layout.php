<?php
/**
 * Archak Theme - Complete Frontend Template
 * Modern split-layout design with parallax effects and reveal animations
 * Reference: https://github.com/archakNath/wedding-invitation-website
 */
if (!defined('THEME_HELPER_LOADED')) { 
    require_once __DIR__ . '/../../app/theme-helper.php'; 
}

$config = $config ?? [];

// Data extraction
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
$address = nl2br(escape_html($config['location']['address'] ?? ''));
$mapsUrl = escape_html($config['location']['maps_url'] ?? '');
$mapsEmbed = escape_html($config['location']['maps_embed'] ?? '');
$giftBank = escape_html($config['gift']['bank'] ?? '');
$giftAccount = escape_html($config['gift']['account_number'] ?? '');
$giftHolder = escape_html($config['gift']['account_holder'] ?? '');
$giftEwalletLabel = escape_html($config['gift']['e_wallet_label'] ?? '');
$giftEwalletNumber = escape_html($config['gift']['e_wallet_number'] ?? '');
$coverPath = $config['media']['cover'] ?? 'uploads/cover/cover.jpg';
$bridePhoto = !empty($config['media']['bride_photo']) ? $config['media']['bride_photo'] : $coverPath;
$groomPhoto = !empty($config['media']['groom_photo']) ? $config['media']['groom_photo'] : $coverPath;
$couplePhoto = !empty($config['media']['couple_photo']) ? $config['media']['couple_photo'] : $coverPath;
$musicSrc = $config['media']['music'] ?? 'music/lagu.mp3';
$heroBg = !empty($config['media']['background_hero']) ? $config['media']['background_hero'] : $couplePhoto;

$whatsappLink = build_whatsapp_link($config);
$calendarLink = build_google_calendar_link($config);
$calendarDownloadName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $config['site']['title'] ?? 'Undangan');

// Section visibility
$isMusicEnabled = is_section_enabled($config, 'music');
$isGalleryEnabled = is_section_enabled($config, 'gallery');
$isRsvpEnabled = is_section_enabled($config, 'rsvp');
$isGiftEnabled = is_section_enabled($config, 'gift');
$isMapsEnabled = is_section_enabled($config, 'lokasi');
$isCoupleEnabled = is_section_enabled($config, 'couple');
$isEventEnabled = is_section_enabled($config, 'event');
$isStoryEnabled = is_section_enabled($config, 'story');
$isParentsEnabled = is_section_enabled($config, 'parents');

// Formatted dates
$akadDateFormatted = $akadDate ? date('l, j F Y', strtotime($akadDate)) : '';
$receptionDateFormatted = $receptionDate ? date('l, j F Y', strtotime($receptionDate)) : '';
$countdownTarget = $config['schedule']['countdown_target'] ?? ($akadDate . 'T' . $akadTime . '+07:00');

// Guest name from URL
$guestName = isset($_GET['to']) ? escape_html($_GET['to']) : '';
$guestFallback = 'Bapak/Ibu/Saudara/i';

// Dresscode
$dresscodeTitle = trim((string)($config['dresscode']['title'] ?? 'Dresscode')) ?: 'Dresscode';
$dresscodeColor = trim((string)($config['dresscode']['color'] ?? 'Putih / Pastel')) ?: 'Putih / Pastel';
$dresscodeRule = trim((string)($config['dresscode']['rule'] ?? 'Rapi dan sopan')) ?: 'Rapi dan sopan';
$dresscodeDescription = trim((string)($config['dresscode']['description'] ?? 'Kenakan busana terbaikmu untuk momen spesial.')) ?: 'Kenakan busana terbaikmu untuk momen spesial.';

// Story data
$stories = !empty($config['story']['items']) ? $config['story']['items'] : (!empty($config['love_story']['items']) ? $config['love_story']['items'] : ($config['story'] ?? []));

// Preset options (theme_options)
$enableParallax = function_exists('get_theme_option') ? (bool)get_theme_option($config, 'archak', 'enable_parallax', true) : ($config['theme_options']['archak']['enable_parallax'] ?? true);
$enablePreloader = function_exists('get_theme_option') ? (bool)get_theme_option($config, 'archak', 'enable_preloader', true) : ($config['theme_options']['archak']['enable_preloader'] ?? true);
$dividerStyle = function_exists('get_theme_option') ? (string)get_theme_option($config, 'archak', 'divider_style', 'ornament') : ($config['theme_options']['archak']['divider_style'] ?? 'ornament');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo escape_html($config['site']['title'] ?? 'Undangan Pernikahan'); ?></title>
    <meta name="description" content="<?php echo escape_html($config['site']['description'] ?? ''); ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="<?php echo get_theme_asset_url('archak', 'style.css'); ?>">
    
    <!-- Custom CSS Override -->
    <?php $customCss = load_custom_css(); if (!empty($customCss)): ?>
    <style><?php echo $customCss; ?></style>
    <?php endif; ?>
</head>
<body class="archak-theme">
    <!-- Preloader -->
    <?php if ($enablePreloader): ?>
    <div id="preloader" class="preloader">
        <div class="preloader-content">
            <div class="preloader-names">
                <span class="name-b"><?php echo $brideNickname; ?></span>
                <span class="preloader-amp">&</span>
                <span class="name-g"><?php echo $groomNickname; ?></span>
            </div>
            <div class="preloader-bar"></div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Navigation -->
    <nav id="navbar" class="navbar-archak">
        <div class="nav-container">
            <a href="#home" class="nav-logo">
                <span class="logo-text"><?php echo $brideNickname; ?> & <?php echo $groomNickname; ?></span>
            </a>
            <ul class="nav-menu">
                <li><a href="#home" class="nav-link">Home</a></li>
                <li><a href="#couple" class="nav-link">Couple</a></li>
                <li><a href="#schedule" class="nav-link">Schedule</a></li>
                <?php if ($isStoryEnabled): ?>
                <li><a href="#story" class="nav-link">Our Story</a></li>
                <?php endif; ?>
                <?php if ($isGalleryEnabled): ?>
                <li><a href="#gallery" class="nav-link">Gallery</a></li>
                <?php endif; ?>
                <li><a href="#rsvp" class="nav-link">RSVP</a></li>
            </ul>
            <button class="nav-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section id="home" class="hero-archak">
        <div class="<?php echo $enableParallax ? 'hero-bg-parallax' : 'hero-bg-static'; ?>" style="background-image: url('<?php echo escape_html(public_path($heroBg)); ?>');"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-split">
                <div class="hero-text-side" data-reveal="left">
                    <p class="hero-intro">We're Getting Married</p>
                    <?php if (!empty($guestName)): ?>
                    <p class="hero-guest">Kepada Yth. <strong><?php echo $guestName; ?></strong></p>
                    <?php endif; ?>
                    <h1 class="hero-title">
                        <span class="hero-bride"><?php echo $brideName; ?></span>
                        <span class="hero-connector">&</span>
                        <span class="hero-groom"><?php echo $groomName; ?></span>
                    </h1>
                    <p class="hero-quote"><?php echo $openingText; ?></p>
                    <div class="hero-date-badge">
                        <span class="badge-day"><?php echo $akadDate ? date('d', strtotime($akadDate)) : '00'; ?></span>
                        <span class="badge-month"><?php echo $akadDate ? date('M', strtotime($akadDate)) : date('M'); ?></span>
                        <span class="badge-year"><?php echo $akadDate ? date('Y', strtotime($akadDate)) : date('Y'); ?></span>
                    </div>
                </div>
                <div class="hero-image-side" data-reveal="right">
                    <div class="image-frame">
                        <img src="<?php echo escape_html(public_path($coverPath)); ?>" alt="<?php echo $brideName; ?> & <?php echo $groomName; ?>" class="hero-main-image">
                    </div>
                </div>
            </div>
            <a href="#couple" class="scroll-down-btn">
                <span>Scroll Down</span>
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12l7 7 7-7"/>
                </svg>
            </a>
        </div>
    </section>
    
    <!-- Couple Section -->
    <?php if ($isCoupleEnabled): ?>
    <section id="couple" class="section couple-section-archak">
        <div class="container">
            <div class="section-header text-center" data-fade-up>
                <p class="section-subtitle">Happy Couple</p>
                <h2 class="section-title">Meet The Bride & Groom</h2>
                <div class="divider-ornament">
                    <svg width="60" height="20" viewBox="0 0 60 20">
                        <path d="M0 10 Q15 20 30 10 T60 10" stroke="currentColor" fill="none" stroke-width="2"/>
                    </svg>
                </div>
            </div>
            
            <div class="couple-split-wrapper">
                <div class="couple-person bride" data-slide-left>
                    <div class="person-image-wrapper">
                        <img src="<?php echo escape_html(public_path($bridePhoto)); ?>" alt="<?php echo $brideName; ?>" class="person-image">
                    </div>
                    <h3 class="person-name"><?php echo $brideName; ?></h3>
                    <p class="person-nickname"><?php echo $brideNickname; ?></p>
                    <?php if ($isParentsEnabled): ?>
                    <p class="person-parents">
                        Daughter of<br>
                        <strong><?php echo $brideFather; ?></strong> & <strong><?php echo $brideMother; ?></strong>
                    </p>
                    <?php endif; ?>
                    <div class="social-links">
                        <a href="#" class="social-link instagram"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
                    </div>
                </div>
                
                <div class="couple-separator-archak" data-zoom>
                    <div class="separator-symbol">&</div>
                </div>
                
                <div class="couple-person groom" data-slide-right>
                    <div class="person-image-wrapper">
                        <img src="<?php echo escape_html(public_path($groomPhoto)); ?>" alt="<?php echo $groomName; ?>" class="person-image">
                    </div>
                    <h3 class="person-name"><?php echo $groomName; ?></h3>
                    <p class="person-nickname"><?php echo $groomNickname; ?></p>
                    <?php if ($isParentsEnabled): ?>
                    <p class="person-parents">
                        Son of<br>
                        <strong><?php echo $groomFather; ?></strong> & <strong><?php echo $groomMother; ?></strong>
                    </p>
                    <?php endif; ?>
                    <div class="social-links">
                        <a href="#" class="social-link instagram"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($quote)): ?>
            <div class="couple-quote-archak" data-fade-up>
                <blockquote><?php echo $quote; ?></blockquote>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Schedule/Event Section -->
    <?php if ($isEventEnabled): ?>
    <section id="schedule" class="section schedule-section-archak">
        <div class="container">
            <div class="section-header text-center" data-fade-up>
                <p class="section-subtitle">Wedding Event</p>
                <h2 class="section-title">Save The Date</h2>
                <div class="divider-ornament">
                    <svg width="60" height="20" viewBox="0 0 60 20">
                        <path d="M0 10 Q15 20 30 10 T60 10" stroke="currentColor" fill="none" stroke-width="2"/>
                    </svg>
                </div>
            </div>
            
            <div class="schedule-grid">
                <!-- Akad Card -->
                <div class="schedule-card" data-flip>
                    <div class="card-icon">
                        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h3 class="card-title">Akad Nikah</h3>
                    <p class="card-date"><?php echo $akadDateFormatted; ?></p>
                    <p class="card-time"><?php echo escape_html($akadTime); ?> WIB</p>
                    <p class="card-venue"><?php echo $venue; ?></p>
                    <p class="card-address"><?php echo $address; ?></p>
                    <div class="card-actions">
                        <a href="<?php echo escape_html($calendarLink); ?>" target="_blank" class="btn btn-calendar">Add to Calendar</a>
                    </div>
                </div>
                
                <!-- Reception Card -->
                <div class="schedule-card" data-flip data-delay="200">
                    <div class="card-icon">
                        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    <h3 class="card-title">Wedding Reception</h3>
                    <p class="card-date"><?php echo $receptionDateFormatted; ?></p>
                    <p class="card-time"><?php echo escape_html($receptionTime); ?> WIB - Selesai</p>
                    <p class="card-venue"><?php echo $venue; ?></p>
                    <p class="card-address"><?php echo $address; ?></p>
                    <div class="card-actions">
                        <a href="<?php echo escape_html($calendarLink); ?>" target="_blank" class="btn btn-calendar">Add to Calendar</a>
                    </div>
                </div>
            </div>
            
            <!-- Countdown -->
            <div class="countdown-wrapper" data-fade-up>
                <h3 class="countdown-title">Countdown to Our Special Day</h3>
                <div class="countdown-display" data-countdown-target="<?php echo escape_html($countdownTarget); ?>">
                    <div class="countdown-item">
                        <span class="countdown-number" id="cd-days">00</span>
                        <span class="countdown-label">Days</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="cd-hours">00</span>
                        <span class="countdown-label">Hours</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="cd-minutes">00</span>
                        <span class="countdown-label">Minutes</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="cd-seconds">00</span>
                        <span class="countdown-label">Seconds</span>
                    </div>
                </div>
            </div>
            
            <!-- Dresscode -->
            <?php if (!empty($config['dresscode']['enabled'])): ?>
            <div class="dresscode-box" data-fade-up>
                <h4><?php echo escape_html($dresscodeTitle); ?></h4>
                <p class="dresscode-detail"><?php echo escape_html($dresscodeColor); ?></p>
                <p class="dresscode-note"><?php echo escape_html($dresscodeRule); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Our Story Section -->
    <?php if ($isStoryEnabled && !empty($stories)): ?>
    <section id="story" class="section story-section-archak">
        <div class="container">
            <div class="section-header text-center" data-fade-up>
                <p class="section-subtitle">Love Story</p>
                <h2 class="section-title">How We Met</h2>
                <div class="divider-ornament">
                    <svg width="60" height="20" viewBox="0 0 60 20">
                        <path d="M0 10 Q15 20 30 10 T60 10" stroke="currentColor" fill="none" stroke-width="2"/>
                    </svg>
                </div>
            </div>
            
            <div class="story-timeline">
                <?php foreach ($stories as $index => $story): ?>
                <div class="timeline-item <?php echo $index % 2 === 0 ? 'left' : 'right'; ?>" data-fade-up>
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="timeline-date"><?php echo escape_html($story['date'] ?? ''); ?></span>
                        <h4 class="timeline-title"><?php echo escape_html($story['title'] ?? ''); ?></h4>
                        <p class="timeline-description"><?php echo nl2br(escape_html($story['description'] ?? '')); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Gallery Section -->
    <?php if ($isGalleryEnabled): ?>
    <section id="gallery" class="section gallery-section-archak">
        <div class="container">
            <div class="section-header text-center" data-fade-up>
                <p class="section-subtitle">Memories</p>
                <h2 class="section-title">Photo Gallery</h2>
                <div class="divider-ornament">
                    <svg width="60" height="20" viewBox="0 0 60 20">
                        <path d="M0 10 Q15 20 30 10 T60 10" stroke="currentColor" fill="none" stroke-width="2"/>
                    </svg>
                </div>
            </div>
            
            <button type="button" id="loadGalleryBtn" class="btn btn-load" style="display:none;">Load Gallery</button>
            
            <div id="galleryGrid" class="gallery-masonry">
                <p class="loading">Loading photos...</p>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Location Section -->
    <?php if ($isMapsEnabled): ?>
    <section id="location" class="section location-section-archak">
        <div class="container">
            <div class="section-header text-center" data-fade-up>
                <p class="section-subtitle">Venue</p>
                <h2 class="section-title">Event Location</h2>
                <div class="divider-ornament">
                    <svg width="60" height="20" viewBox="0 0 60 20">
                        <path d="M0 10 Q15 20 30 10 T60 10" stroke="currentColor" fill="none" stroke-width="2"/>
                    </svg>
                </div>
            </div>
            
            <div class="location-wrapper">
                <div class="location-details" data-slide-right>
                    <h3><?php echo $venue; ?></h3>
                    <p class="location-address"><?php echo $address; ?></p>
                    <a href="<?php echo escape_html($mapsUrl); ?>" target="_blank" class="btn btn-directions">Get Directions</a>
                </div>
                <div class="location-map-wrapper" data-slide-left>
                    <iframe src="<?php echo escape_html($mapsEmbed); ?>" title="Location Map" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Gift Section -->
    <?php if ($isGiftEnabled): ?>
    <section id="gift" class="section gift-section-archak">
        <div class="container">
            <div class="section-header text-center" data-fade-up>
                <p class="section-subtitle">Wedding Gift</p>
                <h2 class="section-title">With Love</h2>
                <div class="divider-ornament">
                    <svg width="60" height="20" viewBox="0 0 60 20">
                        <path d="M0 10 Q15 20 30 10 T60 10" stroke="currentColor" fill="none" stroke-width="2"/>
                    </svg>
                </div>
            </div>
            
            <div class="gift-cards-wrapper">
                <div class="gift-card-archak" data-fade-up>
                    <h4>For <?php echo $brideName; ?></h4>
                    <div class="gift-info">
                        <div class="info-row">
                            <span class="info-label">Bank:</span>
                            <span class="info-value"><?php echo $giftBank; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Account No:</span>
                            <span class="info-value account-num" data-account="<?php echo $giftAccount; ?>"><?php echo $giftAccount; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Account Holder:</span>
                            <span class="info-value"><?php echo $giftHolder; ?></span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-copy-account" data-account="<?php echo $giftAccount; ?>">Copy Account Number</button>
                    <span class="copy-success" style="display:none;">✓ Copied!</span>
                </div>
                
                <div class="gift-card-archak" data-fade-up data-delay="100">
                    <h4>For <?php echo $groomName; ?></h4>
                    <div class="gift-info">
                        <div class="info-row">
                            <span class="info-label">E-Wallet:</span>
                            <span class="info-value"><?php echo $giftEwalletLabel; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone Number:</span>
                            <span class="info-value account-num" data-account="<?php echo $giftEwalletNumber; ?>"><?php echo $giftEwalletNumber; ?></span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-copy-account" data-account="<?php echo $giftEwalletNumber; ?>">Copy Number</button>
                    <span class="copy-success" style="display:none;">✓ Copied!</span>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- RSVP Section -->
    <?php if ($isRsvpEnabled): ?>
    <section id="rsvp" class="section rsvp-section-archak">
        <div class="container">
            <div class="section-header text-center" data-fade-up>
                <p class="section-subtitle">Confirmation</p>
                <h2 class="section-title">RSVP</h2>
                <div class="divider-ornament">
                    <svg width="60" height="20" viewBox="0 0 60 20">
                        <path d="M0 10 Q15 20 30 10 T60 10" stroke="currentColor" fill="none" stroke-width="2"/>
                    </svg>
                </div>
            </div>
            
            <form id="rsvpForm" class="rsvp-form-archak" data-fade-up>
                <input type="hidden" name="csrf_token" id="csrfToken">
                
                <div class="form-group">
                    <label for="nama">Your Name</label>
                    <input type="text" id="nama" name="nama" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Will you attend?</label>
                    <select id="status" name="status" required>
                        <option value="">Select option</option>
                        <option value="Hadir">Yes, I will attend</option>
                        <option value="Tidak Hadir">Sorry, cannot attend</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ucapan">Message & Wishes</label>
                    <textarea id="ucapan" name="ucapan" rows="5" placeholder="Write your wishes for the couple..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-submit">Send RSVP</button>
                <p id="formMessage" class="form-message" role="status" aria-live="polite"></p>
            </form>
            
            <?php if (is_section_enabled($config, 'messages')): ?>
            <div id="messages" class="wishes-container"></div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Closing Section -->
    <section class="section closing-section-archak">
        <div class="container">
            <div class="closing-wrapper" data-fade-up>
                <div class="bismillah">
                    <span class="arabic-text">بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ</span>
                </div>
                <p class="closing-message"><?php echo $closingText; ?></p>
                <p class="closing-signature">
                    <?php echo $brideNickname; ?> & <?php echo $groomNickname; ?>
                </p>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer-archak">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> <?php echo $brideNickname; ?> & <?php echo $groomNickname; ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Music Control -->
    <?php if ($isMusicEnabled): ?>
    <button id="music-control" class="music-float-btn" type="button" aria-label="Toggle music">
        <svg class="icon-play" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M8 5v14l11-7z"/>
        </svg>
        <svg class="icon-pause" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
        </svg>
    </button>
    <audio id="bg-music" loop>
        <source src="<?php echo escape_html(public_path($musicSrc)); ?>" type="audio/mp3">
    </audio>
    <?php endif; ?>
    
    <!-- Gallery Lightbox Modal -->
    <div id="galleryModal" class="lightbox-modal">
        <span class="lightbox-close">&times;</span>
        <span class="lightbox-prev">&#10094;</span>
        <span class="lightbox-next">&#10095;</span>
        <img class="lightbox-content" id="lightboxImg">
        <div class="lightbox-caption" id="lightboxCaption"></div>
    </div>
    
    <!-- Scripts -->
    <script src="<?php echo get_theme_asset_url('archak', 'script.js'); ?>"></script>
</body>
</html>
