<?php
/**
 * Elix Theme - Complete Frontend Template
 * 
 * Adapted from: https://github.com/elix-stack/wedding-invitation-1
 * 
 * Key characteristics:
 * - Modern minimalist design
 * - Timeline vertical layout for events
 * - Hero with circular countdown overlay
 * - Navbar offcanvas on mobile
 * - Clean typography with ample whitespace
 * - Subtle animations and transitions
 * - Card-based sections
 */

if (!defined('THEME_HELPER_LOADED')) {
    require_once __DIR__ . '/../../app/theme-helper.php';
}

$config = $config ?? [];
$shared = $shared ?? [];

// Extract data from config
$brideName = escape_html($config['wedding']['bride_name'] ?? '');
$groomName = escape_html($config['wedding']['groom_name'] ?? '');
$brideNickname = escape_html($config['wedding']['bride_nickname'] ?? '');
$groomNickname = escape_html($config['wedding']['groom_nickname'] ?? '');
$openingText = nl2br(escape_html($config['wedding']['opening_text'] ?? ''));
$quote = nl2br(escape_html($config['wedding']['quote'] ?? ''));
$closingText = nl2br(escape_html($config['wedding']['closing_text'] ?? ''));

// Parents
$brideFather = escape_html($config['parents']['bride_father'] ?? '');
$brideMother = escape_html($config['parents']['bride_mother'] ?? '');
$groomFather = escape_html($config['parents']['groom_father'] ?? '');
$groomMother = escape_html($config['parents']['groom_mother'] ?? '');

// Schedule
$akadDate = $config['schedule']['akad_date'] ?? '';
$akadTime = $config['schedule']['akad_time'] ?? '';
$receptionDate = $config['schedule']['reception_date'] ?? '';
$receptionTime = $config['schedule']['reception_time'] ?? '';
$countdownTarget = $config['schedule']['countdown_target'] ?? '';

// Location
$venue = escape_html($config['location']['venue'] ?? '');
$address = escape_html($config['location']['address'] ?? '');
$mapsUrl = escape_html($config['location']['maps_url'] ?? '');
$mapsEmbed = escape_html($config['location']['maps_embed'] ?? '');

// Gift
$giftBank = escape_html($config['gift']['bank'] ?? '');
$giftAccount = escape_html($config['gift']['account_number'] ?? '');
$giftHolder = escape_html($config['gift']['account_holder'] ?? '');
$giftEwalletLabel = escape_html($config['gift']['e_wallet_label'] ?? '');
$giftEwalletNumber = escape_html($config['gift']['e_wallet_number'] ?? '');

// Media
$coverPath = $config['media']['cover'] ?? 'uploads/cover/cover.jpg';
$bridePhoto = !empty($config['media']['bride_photo']) ? $config['media']['bride_photo'] : $coverPath;
$groomPhoto = !empty($config['media']['groom_photo']) ? $config['media']['groom_photo'] : $coverPath;
$couplePhoto = !empty($config['media']['couple_photo']) ? $config['media']['couple_photo'] : $coverPath;
$musicSrc = $config['media']['music'] ?? 'music/lagu.mp3';

// WhatsApp
$whatsappLink = build_whatsapp_link($config);
$calendarLink = build_google_calendar_link($config);
$calendarDownloadName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $config['site']['title'] ?? 'Undangan');

// Section visibility helpers
$isMusicEnabled = is_section_enabled($config, 'music');
$isGalleryEnabled = is_section_enabled($config, 'gallery');
$isStoryEnabled = is_section_enabled($config, 'cerita');
$isRsvpEnabled = is_section_enabled($config, 'rsvp');
$isMessagesEnabled = is_section_enabled($config, 'messages');
$isGiftEnabled = is_section_enabled($config, 'gift');
$isMapsEnabled = is_section_enabled($config, 'lokasi');

// Format dates
$akadDateFormatted = $akadDate ? date('l, j F Y', strtotime($akadDate)) : '';
$receptionDateFormatted = $receptionDate ? date('l, j F Y', strtotime($receptionDate)) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo escape_html($config['site']['title'] ?? 'Undangan Pernikahan'); ?></title>
    
    <!-- SEO -->
    <meta name="description" content="<?php echo escape_html($config['site']['description'] ?? ''); ?>">
    <meta name="keywords" content="<?php echo escape_html($config['site']['keywords'] ?? ''); ?>">
    <meta property="og:title" content="<?php echo escape_html($config['site']['open_graph_title'] ?? ''); ?>">
    <meta property="og:description" content="<?php echo escape_html($config['site']['open_graph_description'] ?? ''); ?>">
    <?php if (!empty($config['site']['open_graph_image'])): ?>
    <meta property="og:image" content="<?php echo escape_html(public_path($config['site']['open_graph_image'])); ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    
    <!-- Appearance -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?php echo escape_html($config['site']['title'] ?? ''); ?>">
    <meta name="theme-color" content="#f5f5f5">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo escape_html(public_path($coverPath)); ?>">
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    
    <!-- AOS Animation -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="<?php echo escape_html(get_theme_asset_url('elix', 'style.css')); ?>">
    
    <!-- Custom CSS Override -->
    <?php if (trim(load_custom_css()) !== ''): ?>
    <link rel="stylesheet" href="custom.css">
    <?php endif; ?>
</head>

<body data-key="" data-url="" data-audio="<?php echo escape_html($musicSrc); ?>" data-confetti="true" data-time="<?php echo escape_html($countdownTarget); ?>">
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-light" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    
    <!-- Welcome Overlay -->
    <div class="welcome-overlay" id="welcomeOverlay">
        <div class="welcome-content text-center">
            <img src="<?php echo escape_html(public_path($coverPath)); ?>" alt="cover" class="welcome-img rounded-circle mb-4">
            <h2 class="welcome-title font-primary"><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></h2>
            <p class="welcome-subtitle">Kepada Yth.<br>Bapak/Ibu/Saudara/i</p>
            <button type="button" class="btn btn-open-invitation mt-4" onclick="openInvitation()">
                <i class="fas fa-envelope-open me-2"></i>Buka Undangan
            </button>
        </div>
    </div>
    
    <!-- Music Control -->
    <?php if ($isMusicEnabled): ?>
    <div class="music-control" id="musicControl" style="display:none;">
        <button type="button" class="music-btn" id="musicBtn" onclick="toggleMusic()">
            <i class="fas fa-compact-disc"></i>
        </button>
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent" style="display:none;">
        
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg fixed-top navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand font-primary fw-bold" href="#hero">
                    <?php echo $brideName; ?> &amp; <?php echo $groomName; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="offcanvas offcanvas-end" tabindex="-1" id="navbarNav">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title font-primary">Menu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item"><a class="nav-link" href="#hero">Home</a></li>
                            <li class="nav-item"><a class="nav-link" href="#couple">Mempelai</a></li>
                            <li class="nav-item"><a class="nav-link" href="#event">Acara</a></li>
                            <?php if ($isStoryEnabled): ?><li class="nav-item"><a class="nav-link" href="#story">Cerita</a></li><?php endif; ?>
                            <?php if ($isGalleryEnabled): ?><li class="nav-item"><a class="nav-link" href="#gallery">Galeri</a></li><?php endif; ?>
                            <?php if ($isMapsEnabled): ?><li class="nav-item"><a class="nav-link" href="#location">Lokasi</a></li><?php endif; ?>
                            <?php if ($isGiftEnabled): ?><li class="nav-item"><a class="nav-link" href="#gift">Gift</a></li><?php endif; ?>
                            <?php if ($isRsvpEnabled): ?><li class="nav-item"><a class="nav-link" href="#rsvp">RSVP</a></li><?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Hero Section -->
        <section id="hero" class="hero-section d-flex align-items-center justify-content-center">
            <div class="hero-bg" style="background-image: url('<?php echo escape_html(public_path($coverPath)); ?>');"></div>
            <div class="hero-overlay"></div>
            <div class="container position-relative text-center text-white">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <p class="hero-eyebrow mb-3" data-aos="fade-down">We're Getting Married</p>
                        <h1 class="hero-title font-primary mb-4" data-aos="fade-up" data-aos-delay="100">
                            <?php echo $brideName; ?> <span class="hero-ampersand">&</span> <?php echo $groomName; ?>
                        </h1>
                        <p class="hero-date mb-4" data-aos="fade-up" data-aos-delay="200">
                            <?php echo $akadDateFormatted; ?>
                        </p>
                        
                        <!-- Countdown Circle -->
                        <div class="countdown-circle mx-auto mb-4" data-aos="zoom-in" data-aos-delay="300">
                            <div class="countdown-inner">
                                <div class="countdown-item">
                                    <span class="countdown-number" id="days">00</span>
                                    <span class="countdown-label">Days</span>
                                </div>
                                <div class="countdown-item">
                                    <span class="countdown-number" id="hours">00</span>
                                    <span class="countdown-label">Hours</span>
                                </div>
                                <div class="countdown-item">
                                    <span class="countdown-number" id="minutes">00</span>
                                    <span class="countdown-label">Minutes</span>
                                </div>
                                <div class="countdown-item">
                                    <span class="countdown-number" id="seconds">00</span>
                                    <span class="countdown-label">Seconds</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="hero-actions mt-4" data-aos="fade-up" data-aos-delay="400">
                            <a href="<?php echo $calendarLink; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-calendar me-2">
                                <i class="fas fa-calendar-plus me-2"></i>Save the Date
                            </a>
                            <a href="<?php echo $whatsappLink; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp">
                                <i class="fab fa-whatsapp me-2"></i>WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Couple Section -->
        <section id="couple" class="couple-section py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center">
                        <h2 class="section-title font-primary mb-3" data-aos="fade-up">The Happy Couple</h2>
                        <p class="section-subtitle text-muted mb-5" data-aos="fade-up" data-aos-delay="100">
                            Dengan memohon rahmat dan ridho Allah SWT, kami bermaksud menyelenggarakan pernikahan putra-putri kami:
                        </p>
                    </div>
                </div>
                
                <div class="row align-items-center justify-content-center g-5">
                    <!-- Groom -->
                    <div class="col-md-5 text-center" data-aos="fade-right">
                        <div class="couple-card">
                            <img src="<?php echo escape_html(public_path($coverPath)); ?>" alt="<?php echo $groomName; ?>" class="couple-img rounded-circle mb-4">
                            <h3 class="couple-name font-primary"><?php echo $groomName; ?></h3>
                            <p class="couple-nickname"><?php echo $groomNickname; ?></p>
                            <p class="couple-parents">
                                Putra dari<br>
                                <strong><?php echo $groomFather; ?></strong><br>
                                dan<br>
                                <strong><?php echo $groomMother; ?></strong>
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-md-2 text-center" data-aos="zoom-in">
                        <div class="heart-divider">
                            <i class="fas fa-heart text-danger"></i>
                        </div>
                    </div>
                    
                    <!-- Bride -->
                    <div class="col-md-5 text-center" data-aos="fade-left">
                        <div class="couple-card">
                            <img src="<?php echo escape_html(public_path($coverPath)); ?>" alt="<?php echo $brideName; ?>" class="couple-img rounded-circle mb-4">
                            <h3 class="couple-name font-primary"><?php echo $brideName; ?></h3>
                            <p class="couple-nickname"><?php echo $brideNickname; ?></p>
                            <p class="couple-parents">
                                Putri dari<br>
                                <strong><?php echo $brideFather; ?></strong><br>
                                dan<br>
                                <strong><?php echo $brideMother; ?></strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Event Section (Timeline) -->
        <section id="event" class="event-section py-5 bg-light">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center">
                        <h2 class="section-title font-primary mb-3" data-aos="fade-up">Wedding Event</h2>
                        <p class="section-subtitle text-muted mb-5" data-aos="fade-up" data-aos-delay="100">
                            Kami mengundang Anda untuk merayakan cinta kami
                        </p>
                    </div>
                </div>
                
                <div class="timeline">
                    <!-- Akad -->
                    <div class="timeline-item" data-aos="fade-up">
                        <div class="timeline-marker">
                            <i class="fas fa-ring"></i>
                        </div>
                        <div class="timeline-content card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h3 class="timeline-title font-primary mb-3">Akad Nikah</h3>
                                <div class="timeline-details">
                                    <p class="mb-2"><i class="far fa-calendar-alt me-2"></i><?php echo $akadDateFormatted; ?></p>
                                    <p class="mb-2"><i class="far fa-clock me-2"></i><?php echo escape_html($akadTime); ?> WIB</p>
                                    <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo $venue; ?></p>
                                    <p class="text-muted small"><?php echo $address; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reception -->
                    <div class="timeline-item" data-aos="fade-up" data-aos-delay="100">
                        <div class="timeline-marker">
                            <i class="fas fa-glass-cheers"></i>
                        </div>
                        <div class="timeline-content card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h3 class="timeline-title font-primary mb-3">Resepsi Pernikahan</h3>
                                <div class="timeline-details">
                                    <p class="mb-2"><i class="far fa-calendar-alt me-2"></i><?php echo $receptionDateFormatted; ?></p>
                                    <p class="mb-2"><i class="far fa-clock me-2"></i><?php echo escape_html($receptionTime); ?> WIB - Selesai</p>
                                    <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo $venue; ?></p>
                                    <p class="text-muted small"><?php echo $address; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <?php if ($isStoryEnabled && !empty($config['story'])): ?>
        <!-- Story Section -->
        <section id="story" class="story-section py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center">
                        <h2 class="section-title font-primary mb-3" data-aos="fade-up">Our Love Story</h2>
                        <p class="section-subtitle text-muted mb-5" data-aos="fade-up" data-aos-delay="100">
                            Perjalanan cinta kami hingga hari ini
                        </p>
                    </div>
                </div>
                
                <div class="story-timeline">
                    <?php foreach (($config['story'] ?? []) as $index => $story): ?>
                    <div class="story-item <?php echo $index % 2 === 0 ? 'left' : 'right'; ?>" data-aos="fade-<?php echo $index % 2 === 0 ? 'right' : 'left'; ?>">
                        <div class="story-content card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h4 class="story-date font-primary"><?php echo escape_html($story['date'] ?? ''); ?></h4>
                                <h5 class="story-title"><?php echo escape_html($story['title'] ?? ''); ?></h5>
                                <p class="story-description text-muted"><?php echo nl2br(escape_html($story['description'] ?? '')); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <?php if ($isGalleryEnabled): ?>
        <!-- Gallery Section -->
        <section id="gallery" class="gallery-section py-5 bg-light">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center">
                        <h2 class="section-title font-primary mb-3" data-aos="fade-up">Our Gallery</h2>
                        <p class="section-subtitle text-muted mb-5" data-aos="fade-up" data-aos-delay="100">
                            Momen-momen indah dalam perjalanan kami
                        </p>
                    </div>
                </div>
                
                <div class="gallery-grid" id="galleryGrid">
                    <p class="text-center text-muted">Memuat galeri...</p>
                </div>
                
                <div class="text-center mt-4">
                    <button type="button" id="loadGalleryBtn" class="btn btn-primary" style="display:none;" onclick="loadGallery()">
                        <i class="fas fa-images me-2"></i>Muat Galeri
                    </button>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <?php if ($isMapsEnabled): ?>
        <!-- Location Section -->
        <section id="location" class="location-section py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center">
                        <h2 class="section-title font-primary mb-3" data-aos="fade-up">Event Location</h2>
                        <p class="section-subtitle text-muted mb-5" data-aos="fade-up" data-aos-delay="100">
                            Kami tunggu kehadiran Anda di hari spesial kami
                        </p>
                    </div>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6" data-aos="fade-right">
                        <div class="location-card card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <h4 class="mb-3 font-primary"><?php echo $venue; ?></h4>
                                <p class="text-muted mb-3"><?php echo $address; ?></p>
                                <a href="<?php echo $mapsUrl; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                                    <i class="fas fa-map-marked-alt me-2"></i>Buka Google Maps
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6" data-aos="fade-left">
                        <div class="map-container rounded overflow-hidden shadow-sm">
                            <iframe src="<?php echo $mapsEmbed; ?>" width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <?php if ($isGiftEnabled): ?>
        <!-- Gift Section -->
        <section id="gift" class="gift-section py-5 bg-light">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center">
                        <h2 class="section-title font-primary mb-3" data-aos="fade-up">Wedding Gift</h2>
                        <p class="section-subtitle text-muted mb-5" data-aos="fade-up" data-aos-delay="100">
                            Doa restu Anda merupakan karunia yang sangat berarti bagi kami
                        </p>
                    </div>
                </div>
                
                <div class="row g-4 justify-content-center">
                    <!-- Bank Transfer -->
                    <div class="col-md-5" data-aos="fade-up">
                        <div class="gift-card card border-0 shadow-sm text-center h-100">
                            <div class="card-body p-4">
                                <div class="gift-icon mb-3">
                                    <i class="fas fa-university fa-2x text-primary"></i>
                                </div>
                                <h4 class="gift-bank font-primary mb-3"><?php echo $giftBank; ?></h4>
                                <p class="gift-account display-6 font-monospace"><?php echo $giftAccount; ?></p>
                                <p class="gift-holder text-muted"><?php echo $giftHolder; ?></p>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-3" onclick="copyToClipboard('<?php echo $giftAccount; ?>', this)">
                                    <i class="fas fa-copy me-2"></i>Salin Nomor Rekening
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- E-Wallet -->
                    <div class="col-md-5" data-aos="fade-up" data-aos-delay="100">
                        <div class="gift-card card border-0 shadow-sm text-center h-100">
                            <div class="card-body p-4">
                                <div class="gift-icon mb-3">
                                    <i class="fas fa-wallet fa-2x text-success"></i>
                                </div>
                                <h4 class="gift-bank font-primary mb-3"><?php echo $giftEwalletLabel; ?></h4>
                                <p class="gift-account display-6 font-monospace"><?php echo $giftEwalletNumber; ?></p>
                                <button type="button" class="btn btn-outline-success btn-sm mt-3" onclick="copyToClipboard('<?php echo $giftEwalletNumber; ?>', this)">
                                    <i class="fas fa-copy me-2"></i>Salin Nomor
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <?php if ($isRsvpEnabled): ?>
        <!-- RSVP Section -->
        <section id="rsvp" class="rsvp-section py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center">
                        <h2 class="section-title font-primary mb-3" data-aos="fade-up">RSVP</h2>
                        <p class="section-subtitle text-muted mb-5" data-aos="fade-up" data-aos-delay="100">
                            Konfirmasi kehadiran Anda
                        </p>
                    </div>
                </div>
                
                <div class="row justify-content-center">
                    <div class="col-lg-6" data-aos="fade-up">
                        <div class="rsvp-form card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <form id="rsvpForm" method="POST" action="save.php">
                                    <div class="mb-3">
                                        <label for="guestName" class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control" id="guestName" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="guestAttendance" class="form-label">Konfirmasi Kehadiran</label>
                                        <select class="form-select" id="guestAttendance" name="attendance" required>
                                            <option value="">Pilih...</option>
                                            <option value="Hadir">Hadir</option>
                                            <option value="Tidak Hadir">Tidak Hadir</option>
                                            <option value="Ragu-ragu">Ragu-ragu</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="guestMessage" class="form-label">Ucapan & Doa</label>
                                        <textarea class="form-control" id="guestMessage" name="message" rows="4" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Kirim Konfirmasi
                                    </button>
                                </form>
                                
                                <div id="rsvpSuccess" class="alert alert-success mt-3" style="display:none;">
                                    <i class="fas fa-check-circle me-2"></i>Terima kasih! Konfirmasi Anda telah terkirim.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Footer -->
        <footer class="footer-section py-4 bg-dark text-white text-center">
            <div class="container">
                <p class="mb-2 font-primary"><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></p>
                <p class="small text-muted mb-0">Terima kasih atas doa dan restu Anda</p>
            </div>
        </footer>
        
    </div>
    
    <!-- Audio Element -->
    <?php if ($isMusicEnabled): ?>
    <audio id="audio" loop>
        <source src="<?php echo escape_html(public_path($musicSrc)); ?>" type="audio/mp3">
        Your browser does not support the audio element.
    </audio>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    
    <!-- Theme JS -->
    <script src="<?php echo escape_html(get_theme_asset_url('elix', 'script.js')); ?>"></script>
    
    <!-- Custom JS Override -->
    <?php if (file_exists(__DIR__ . '/../../custom.js')): ?>
    <script src="custom.js"></script>
    <?php endif; ?>
    
</body>
</html>
