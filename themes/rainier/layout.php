<?php
/**
 * Rainier Theme - Complete Frontend Template
 * Glassmorphism modern design with elegant typography
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
$address = escape_html($config['location']['address'] ?? '');
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
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="<?php echo get_theme_asset_url('rainier', 'style.css'); ?>">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS Override -->
    <?php $customCss = load_custom_css(); if (!empty($customCss)): ?>
    <style><?php echo $customCss; ?></style>
    <?php endif; ?>

    <!-- Global Config Injection -->
    <script>
        window.WeddingConfig = <?php echo json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
</head>
<body class="rainier-theme">
    <!-- Loading Screen -->
    <div id="loading-screen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p class="loading-text">Loading Invitation...</p>
        </div>
    </div>
    
    <!-- Welcome Overlay -->
    <div id="welcome-overlay" class="welcome-overlay">
        <div class="overlay-background"></div>
        <div class="overlay-content glass-panel" data-aos="fade-up" data-aos-duration="1200">
            <div class="bismillah">
                <span class="arabic-text">بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ</span>
            </div>
            <h2 class="overlay-title">The Wedding of</h2>
            <h1 class="overlay-couple-names">
                <span class="name-first"><?php echo $brideNickname; ?></span>
                <span class="name-ampersand">&</span>
                <span class="name-second"><?php echo $groomNickname; ?></span>
            </h1>
            <p class="overlay-date"><?php echo $akadDateFormatted; ?></p>
            <?php if (!empty($guestName)): ?>
            <p class="guest-greeting">
                Kepada Yth.<br>
                <strong><?php echo $guestName; ?></strong>
            </p>
            <?php endif; ?>
            <button id="open-invitation" class="btn-open-invitation">
                <span class="btn-text">Buka Undangan</span>
                <svg class="btn-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Main Content -->
    <main id="main-content" class="main-content hidden">
        <!-- Navbar -->
        <nav id="navbar" class="navbar-glass">
            <div class="navbar-container">
                <div class="navbar-brand">
                    <span class="brand-initials"><?php echo substr($brideNickname, 0, 1) . '&' . substr($groomNickname, 0, 1); ?></span>
                </div>
                <ul class="navbar-menu">
                    <li><a href="#home" class="nav-link">Home</a></li>
                    <li><a href="#couple" class="nav-link">Couple</a></li>
                    <li><a href="#event" class="nav-link">Event</a></li>
                    <?php if ($isGalleryEnabled): ?>
                    <li><a href="#gallery" class="nav-link">Gallery</a></li>
                    <?php endif; ?>
                    <?php if ($isMapsEnabled): ?>
                    <li><a href="#location" class="nav-link">Location</a></li>
                    <?php endif; ?>
                    <?php if ($isRsvpEnabled): ?>
                    <li><a href="#rsvp" class="nav-link">RSVP</a></li>
                    <?php endif; ?>
                </ul>
                <button class="navbar-toggle" aria-label="Toggle navigation">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
            </div>
        </nav>
        
        <!-- Hero Section -->
        <section id="home" class="hero-section">
            <div class="hero-background" style="background-image: url('<?php echo escape_html(public_path($heroBg)); ?>');"></div>
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <div class="hero-glass-panel glass-panel" data-aos="fade-up" data-aos-duration="1500">
                    <p class="hero-eyebrow">We're Getting Married</p>
                    <h1 class="hero-title">
                        <span class="hero-bride"><?php echo $brideName; ?></span>
                        <span class="hero-ampersand">&</span>
                        <span class="hero-groom"><?php echo $groomName; ?></span>
                    </h1>
                    <p class="hero-quote"><?php echo $openingText; ?></p>
                    <div class="hero-date-display">
                        <span class="date-day" id="heroDay"><?php echo $akadDate ? date('d', strtotime($akadDate)) : '00'; ?></span>
                        <span class="date-month"><?php echo $akadDate ? date('M', strtotime($akadDate)) : 'Jan'; ?></span>
                        <span class="date-year"><?php echo $akadDate ? date('Y', strtotime($akadDate)) : '2024'; ?></span>
                    </div>
                    <div class="hero-actions">
                        <a href="#event" class="btn btn-primary">View Event</a>
                        <a href="<?php echo escape_html($calendarLink); ?>" target="_blank" rel="noopener" class="btn btn-secondary">Save the Date</a>
                    </div>
                </div>
            </div>
            <div class="scroll-indicator">
                <span>Scroll Down</span>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12l7 7 7-7"/>
                </svg>
            </div>
        </section>
        
        <!-- Couple Section -->
        <?php if ($isCoupleEnabled): ?>
        <section id="couple" class="section couple-section">
            <div class="section-container">
                <div class="section-header" data-aos="fade-down">
                    <p class="section-label">Love Story</p>
                    <h2 class="section-title">Happy Couple</h2>
                </div>
                
                <div class="couple-grid">
                    <div class="couple-card glass-panel" data-aos="fade-right" data-aos-delay="100">
                        <div class="couple-image-wrapper">
                            <img src="<?php echo escape_html(public_path($bridePhoto)); ?>" alt="<?php echo $brideName; ?>" class="couple-image" loading="lazy">
                        </div>
                        <h3 class="couple-name"><?php echo $brideName; ?></h3>
                        <p class="couple-nickname"><?php echo $brideNickname; ?></p>
                        <?php if ($isParentsEnabled): ?>
                        <p class="couple-parents">
                            Putri dari<br>
                            <span><?php echo $brideFather; ?></span> & <span><?php echo $brideMother; ?></span>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="couple-separator" data-aos="zoom-in">
                        <div class="separator-ornament">&</div>
                    </div>
                    
                    <div class="couple-card glass-panel" data-aos="fade-left" data-aos-delay="200">
                        <div class="couple-image-wrapper">
                            <img src="<?php echo escape_html(public_path($groomPhoto)); ?>" alt="<?php echo $groomName; ?>" class="couple-image" loading="lazy">
                        </div>
                        <h3 class="couple-name"><?php echo $groomName; ?></h3>
                        <p class="couple-nickname"><?php echo $groomNickname; ?></p>
                        <?php if ($isParentsEnabled): ?>
                        <p class="couple-parents">
                            Putra dari<br>
                            <span><?php echo $groomFather; ?></span> & <span><?php echo $groomMother; ?></span>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($quote)): ?>
                <div class="couple-quote glass-panel" data-aos="fade-up">
                    <blockquote><?php echo $quote; ?></blockquote>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Event Section -->
        <?php if ($isEventEnabled): ?>
        <section id="event" class="section event-section">
            <div class="section-container">
                <div class="section-header" data-aos="fade-down">
                    <p class="section-label">Celebration</p>
                    <h2 class="section-title">Wedding Event</h2>
                </div>
                
                <div class="event-grid">
                    <!-- Akad Card -->
                    <div class="event-card glass-panel" data-aos="flip-left">
                        <div class="event-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <h3 class="event-title">Akad Nikah</h3>
                        <p class="event-date"><?php echo $akadDateFormatted; ?></p>
                        <p class="event-time"><?php echo escape_html($akadTime); ?> WIB</p>
                        <p class="event-location"><?php echo $venue; ?></p>
                        <p class="event-address"><?php echo $address; ?></p>
                        <div class="event-actions">
                            <a href="<?php echo escape_html($calendarLink); ?>" target="_blank" class="btn btn-small">Calendar</a>
                            <a href="<?php echo escape_html($whatsappLink); ?>" target="_blank" class="btn btn-small btn-outline">Contact</a>
                        </div>
                    </div>
                    
                    <!-- Reception Card -->
                    <div class="event-card glass-panel" data-aos="flip-right">
                        <div class="event-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                            </svg>
                        </div>
                        <h3 class="event-title">Resepsi</h3>
                        <p class="event-date"><?php echo $receptionDateFormatted; ?></p>
                        <p class="event-time"><?php echo escape_html($receptionTime); ?> WIB - Selesai</p>
                        <p class="event-location"><?php echo $venue; ?></p>
                        <p class="event-address"><?php echo $address; ?></p>
                        <div class="event-actions">
                            <a href="<?php echo escape_html($calendarLink); ?>" target="_blank" class="btn btn-small">Calendar</a>
                            <a href="<?php echo escape_html($whatsappLink); ?>" target="_blank" class="btn btn-small btn-outline">Contact</a>
                        </div>
                    </div>
                </div>
                
                <!-- Dresscode -->
                <?php if (!empty($config['dresscode']['enabled'])): ?>
                <div class="dresscode-card glass-panel" data-aos="fade-up">
                    <h3><?php echo escape_html($dresscodeTitle); ?></h3>
                    <p class="dresscode-color"><?php echo escape_html($dresscodeColor); ?></p>
                    <p class="dresscode-rule"><?php echo escape_html($dresscodeRule); ?></p>
                    <p class="dresscode-desc"><?php echo escape_html($dresscodeDescription); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Countdown Section -->
        <section class="section countdown-section">
            <div class="section-container">
                <div class="section-header" data-aos="fade-down">
                    <p class="section-label">Counting Down</p>
                    <h2 class="section-title">To The Big Day</h2>
                </div>
                
                <div class="countdown-container glass-panel" data-aos="zoom-in">
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
        </section>
        
        <!-- Gallery Section -->
        <?php if ($isGalleryEnabled): ?>
        <section id="gallery" class="section gallery-section">
            <div class="section-container">
                <div class="section-header" data-aos="fade-down">
                    <p class="section-label">Memories</p>
                    <h2 class="section-title">Our Gallery</h2>
                </div>
                
                <button type="button" id="loadGalleryBtn" class="btn btn-load-gallery" style="display:none;">Load Gallery</button>
                
                <div id="galleryGrid" class="gallery-grid">
                    <p class="loading">Loading gallery...</p>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Location Section -->
        <?php if ($isMapsEnabled): ?>
        <section id="location" class="section location-section">
            <div class="section-container">
                <div class="section-header" data-aos="fade-down">
                    <p class="section-label">Venue</p>
                    <h2 class="section-title">Event Location</h2>
                </div>
                
                <div class="location-grid">
                    <div class="location-info glass-panel" data-aos="fade-right">
                        <h3>Venue Details</h3>
                        <p class="venue-name"><?php echo $venue; ?></p>
                        <p class="venue-address"><?php echo $address; ?></p>
                        <a href="<?php echo escape_html($mapsUrl); ?>" target="_blank" class="btn btn-primary">Get Directions</a>
                    </div>
                    
                    <div class="location-map glass-panel" data-aos="fade-left">
                        <iframe src="<?php echo escape_html($mapsEmbed); ?>" title="Location Map" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Gift Section -->
        <?php if ($isGiftEnabled): ?>
        <section id="gift" class="section gift-section">
            <div class="section-container">
                <div class="section-header" data-aos="fade-down">
                    <p class="section-label">Wedding Gift</p>
                    <h2 class="section-title">With Love</h2>
                </div>
                
                <div class="gift-grid">
                    <!-- Bank Transfer -->
                    <div class="gift-card glass-panel" data-aos="fade-up">
                        <h3>For <?php echo $brideName; ?></h3>
                        <div class="gift-details">
                            <div class="gift-row">
                                <span class="gift-label">Bank:</span>
                                <span class="gift-value"><?php echo $giftBank; ?></span>
                            </div>
                            <div class="gift-row">
                                <span class="gift-label">Account:</span>
                                <span class="gift-value account-number" data-account="<?php echo $giftAccount; ?>"><?php echo $giftAccount; ?></span>
                            </div>
                            <div class="gift-row">
                                <span class="gift-label">Holder:</span>
                                <span class="gift-value"><?php echo $giftHolder; ?></span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-copy" data-account="<?php echo $giftAccount; ?>">Copy Account</button>
                        <p class="copy-feedback" style="display:none;">✓ Copied!</p>
                    </div>
                    
                    <!-- E-Wallet -->
                    <div class="gift-card glass-panel" data-aos="fade-up" data-aos-delay="100">
                        <h3>For <?php echo $groomName; ?></h3>
                        <div class="gift-details">
                            <div class="gift-row">
                                <span class="gift-label">E-Wallet:</span>
                                <span class="gift-value"><?php echo $giftEwalletLabel; ?></span>
                            </div>
                            <div class="gift-row">
                                <span class="gift-label">Number:</span>
                                <span class="gift-value account-number" data-account="<?php echo $giftEwalletNumber; ?>"><?php echo $giftEwalletNumber; ?></span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-copy" data-account="<?php echo $giftEwalletNumber; ?>">Copy Number</button>
                        <p class="copy-feedback" style="display:none;">✓ Copied!</p>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- RSVP Section -->
        <?php if ($isRsvpEnabled): ?>
        <section id="rsvp" class="section rsvp-section">
            <div class="section-container">
                <div class="section-header" data-aos="fade-down">
                    <p class="section-label">Confirmation</p>
                    <h2 class="section-title">RSVP</h2>
                </div>
                
                <form id="rsvpForm" class="rsvp-form glass-panel" data-aos="fade-up">
                    <input type="hidden" name="csrf_token" id="csrfToken">
                    
                    <div class="form-group">
                        <label for="nama">Your Name</label>
                        <input type="text" id="nama" name="nama" placeholder="Enter your name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Attendance</label>
                        <select id="status" name="status" required>
                            <option value="Hadir">Will Attend</option>
                            <option value="Tidak Hadir">Cannot Attend</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ucapan">Message & Wishes</label>
                        <textarea id="ucapan" name="ucapan" rows="4" placeholder="Write your wishes..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">Send RSVP</button>
                    <p id="formMessage" class="form-message" role="status" aria-live="polite"></p>
                </form>
                
                <?php if (is_section_enabled($config, 'messages')): ?>
                <div id="messages" class="messages-container"></div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Closing Section -->
        <section class="section closing-section">
            <div class="section-container">
                <div class="closing-content glass-panel" data-aos="fade-up">
                    <div class="bismillah">
                        <span class="arabic-text">بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ</span>
                    </div>
                    <p class="closing-text"><?php echo $closingText; ?></p>
                    <p class="closing-signature">
                        <?php echo $brideNickname; ?> & <?php echo $groomNickname; ?>
                    </p>
                </div>
            </div>
        </section>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <p>Made with ❤️ for <?php echo $brideNickname; ?> & <?php echo $groomNickname; ?></p>
            </div>
        </footer>
        
        <!-- Music Control -->
        <?php if ($isMusicEnabled): ?>
        <button id="music-control" class="music-control" type="button" aria-label="Toggle music">
            <svg class="music-icon-playing" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
            </svg>
            <svg class="music-icon-paused" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                <rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>
            </svg>
        </button>
        <audio id="bg-music" loop>
            <source src="<?php echo escape_html(public_path($musicSrc)); ?>" type="audio/mp3">
        </audio>
        <?php endif; ?>
    </main>
    
    <!-- Gallery Modal -->
    <div id="galleryModal" class="modal">
        <span class="modal-close">&times;</span>
        <img class="modal-content" id="modalImg">
        <div class="modal-caption" id="modalCaption"></div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>
    
    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="<?php echo get_theme_asset_url('rainier', 'script.js'); ?>"></script>
</body>
</html>
