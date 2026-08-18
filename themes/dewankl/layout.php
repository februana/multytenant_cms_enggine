<?php
/**
 * DewanaKL Theme - Complete Frontend Template
 * 
 * Adapted from: https://github.com/dewanakl/undangan
 * 
 * Key characteristics:
 * - Bootstrap 5 based layout
 * - Split desktop/mobile hero (sticky sidebar on desktop)
 * - Bismillah opening with Arabic font
 * - Couple section with circular photos and love animations
 * - Wave separators between sections
 * - Carousel gallery
 * - Bottom sticky navbar for mobile
 * - Dark/Light theme toggle support
 * - Loading page with progress bar
 * - RSVP form with comments/ucapan
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
$address = nl2br(escape_html($config['location']['address'] ?? ''));
$mapsUrl = escape_html($config['location']['maps_url'] ?? '');
$mapsEmbed = escape_html($config['location']['maps_embed'] ?? '');

// Gift
$giftBank = escape_html($config['gift']['bank'] ?? '');
$giftAccount = escape_html($config['gift']['account_number'] ?? '');
$giftHolder = escape_html($config['gift']['account_holder'] ?? '');
$giftEwalletLabel = escape_html($config['gift']['e_wallet_label'] ?? '');
$giftEwalletNumber = escape_html($config['gift']['e_wallet_number'] ?? '');

// Media
$coverPath = $config['media']['cover'] ?? '';
$bridePhoto = !empty($config['media']['bride_photo']) ? $config['media']['bride_photo'] : $coverPath;
$groomPhoto = !empty($config['media']['groom_photo']) ? $config['media']['groom_photo'] : $coverPath;
$couplePhoto = !empty($config['media']['couple_photo']) ? $config['media']['couple_photo'] : $coverPath;
$musicSrc = trim((string)($config['media']['music'] ?? ''));
$videoPath = trim((string)($config['media']['love_story_video'] ?? ''));
$mediaReferenceAvailable = static function (string $path): bool {
    if ($path === '') return false;
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string)(parse_url($path, PHP_URL_SCHEME) ?? ''));
        return in_array($scheme, ['http', 'https'], true);
    }
    $normalized = function_exists('normalize_media_relative_path') ? normalize_media_relative_path($path) : ltrim(str_replace('\\', '/', $path), '/');
    return $normalized !== null && is_file(ROOT_DIR . '/' . $normalized);
};
$videoExtension = strtolower(pathinfo((string)(parse_url($videoPath, PHP_URL_PATH) ?? $videoPath), PATHINFO_EXTENSION));
$videoEnabled = in_array($videoExtension, ['mp4', 'webm', 'ogg'], true) && $mediaReferenceAvailable($videoPath);

// WhatsApp
$whatsappLink = build_whatsapp_link($config);
$calendarLink = build_google_calendar_link($config);
$calendarDownloadName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $config['site']['title'] ?? 'Undangan');

// Section visibility helpers
$isMusicEnabled = (bool)(function_exists('get_theme_option') ? get_theme_option($config, 'dewankl', 'enable_music', true) : ($config['theme_options']['dewankl']['enable_music'] ?? true)) && $mediaReferenceAvailable($musicSrc);
$isGalleryEnabled = theme_section_enabled($config, 'dewankl', 'gallery');
$isStoryEnabled = theme_section_enabled($config, 'dewankl', 'love_story');
$isRsvpEnabled = theme_section_enabled($config, 'dewankl', 'comment');
$isMessagesEnabled = theme_section_enabled($config, 'dewankl', 'comment');
$isGiftEnabled = theme_section_enabled($config, 'dewankl', 'love_gift');
$isMapsEnabled = theme_section_enabled($config, 'dewankl', 'wedding_date');

// Format dates
$akadDateFormatted = $akadDate ? date('l, j F Y', strtotime($akadDate)) : '';
$receptionDateFormatted = $receptionDate ? date('l, j F Y', strtotime($receptionDate)) : '';

// Preset options (theme_options)
$showBismillah = function_exists('get_theme_option') ? (bool)get_theme_option($config, 'dewankl', 'show_bismillah', true) : ($config['theme_options']['dewankl']['show_bismillah'] ?? true);
$enableConfetti = (function_exists('get_theme_option') ? get_theme_option($config, 'dewankl', 'enable_confetti', true) : ($config['theme_options']['dewankl']['enable_confetti'] ?? true)) ? 'true' : 'false';
$enableMouseAnimation = function_exists('get_theme_option') ? (bool)get_theme_option($config, 'dewankl', 'enable_mouse_animation', true) : ($config['theme_options']['dewankl']['enable_mouse_animation'] ?? true);
$visuals = function_exists('theme_visual_values_for_config') ? theme_visual_values_for_config($config, 'dewankl') : [];
$dewanklHeroPath = (string)($visuals['hero_background'] ?? '') ?: $coverPath;
$dewanklAccent = (string)($visuals['accent_color'] ?? '#7b4a3a');
$dewanklHeadingFont = (string)($visuals['heading_font'] ?? 'Sacramento, cursive');
$dewanklBodyFont = (string)($visuals['body_font'] ?? 'Inter, sans-serif');
$dewanklOverlay = (float)($visuals['hero_overlay'] ?? '0.30');
$dewanklVisualStyle = '<style id="cms-dewanakl-visual">:root{--cms-dewana-accent:' . $dewanklAccent . ';--cms-dewana-heading:' . $dewanklHeadingFont . ';--cms-dewana-body:' . $dewanklBodyFont . ';--cms-dewana-overlay:' . $dewanklOverlay . '}body{font-family:var(--cms-dewana-body)}.font-esthetic{font-family:var(--cms-dewana-heading)!important}.btn-primary{background-color:var(--cms-dewana-accent)!important;border-color:var(--cms-dewana-accent)!important}.btn-outline-auto{color:var(--cms-dewana-accent)!important;border-color:var(--cms-dewana-accent)!important}#home .bg-overlay-auto,#welcome .bg-overlay-auto{background-color:rgba(0,0,0,var(--cms-dewana-overlay))!important}</style>';
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="auto">
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
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo escape_html(public_path($coverPath)); ?>">
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sacramento&family=Noto+Naskh+Arabic&display=swap">

    <!-- Original DewanaKL animation/media dependencies; loaded only for this preset. -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <script defer src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <?php if ($enableConfetti === 'true'): ?><script defer src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.js"></script><?php endif; ?>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.1.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo escape_html(get_theme_asset_url('dewankl', 'original/common.css')); ?>">
    <link rel="stylesheet" href="<?php echo escape_html(get_theme_asset_url('dewankl', 'original/guest.css')); ?>">
    <link rel="stylesheet" href="<?php echo escape_html(get_theme_asset_url('dewankl', 'original/animation.css')); ?>">
    <link rel="stylesheet" href="<?php echo escape_html(get_theme_asset_url('dewankl', 'fidelity-adapter.css')); ?>">
    <?php echo $dewanklVisualStyle; ?>
    
    <!-- Custom CSS Override -->
    <?php if (trim(load_custom_css()) !== ''): ?>
    <link rel="stylesheet" href="custom.css">
    <?php endif; ?>

    <!-- Global Config Injection -->
    <script>
        window.WeddingConfig = <?php echo json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
</head>

<body data-key="" data-url="" data-audio="<?php echo escape_html($musicSrc); ?>" data-confetti="<?php echo $enableConfetti; ?>" data-time="<?php echo escape_html($countdownTarget); ?>">
    
    <!-- Root Invitation -->
    <div class="row m-0 p-0 opacity-0" id="root">
        
        <!-- Desktop mode (sticky sidebar) -->
        <div class="sticky-top vh-100 d-none d-sm-block col-sm-5 col-md-6 col-lg-7 col-xl-8 col-xxl-9 overflow-y-hidden m-0 p-0">
            <div class="position-relative bg-white-black d-flex justify-content-center align-items-center vh-100">
                <div class="d-flex position-absolute w-100 h-100">
                    <div class="position-relative overflow-hidden vw-100">
                        <div class="position-absolute h-100 w-100 slide-desktop" style="opacity: 0;">
                            <img src="<?php echo escape_html(theme_visual_public_path($dewanklHeroPath)); ?>" alt="latar belakang" class="bg-cover-home" style="opacity: 30%;">
                        </div>
                    </div>
                </div>
                
                <div class="text-center p-4 bg-overlay-auto rounded-5">
                    <h2 class="font-esthetic mb-4" style="font-size: 2rem;"><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></h2>
                    <p class="m-0" style="font-size: 1rem;"><?php echo $akadDateFormatted; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Smartphone mode -->
        <div class="col-sm-7 col-md-6 col-lg-5 col-xl-4 col-xxl-3 m-0 p-0">
            <main data-bs-spy="scroll" data-bs-target="#navbar-menu" data-bs-root-margin="25% 0% 0% 0%" data-bs-smooth-scroll="true" tabindex="0">
                
                <!-- Home Section -->
                <section id="home" class="bg-light-dark position-relative overflow-hidden p-0 m-0">
                    <img src="<?php echo escape_html(theme_visual_public_path($dewanklHeroPath)); ?>" alt="latar belakang" class="position-absolute opacity-25 top-50 start-50 translate-middle bg-cover-home">
                    
                    <div class="position-relative text-center bg-overlay-auto" style="background-color: unset;">
                        <h1 class="font-esthetic pt-5 pb-4 fw-medium" style="font-size: 2.25rem;">Undangan Pernikahan</h1>
                        
                        <img src="<?php echo escape_html(public_path($coverPath)); ?>" alt="sampul" class="img-center-crop rounded-circle border border-3 border-light shadow my-4 mx-auto">
                        
                        <h2 class="font-esthetic my-4" style="font-size: 2.25rem;"><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></h2>
                        <p class="my-2" style="font-size: 1.25rem;"><?php echo $akadDateFormatted; ?></p>
                        
                        <button class="btn btn-outline-auto btn-sm shadow rounded-pill px-3 py-1" style="font-size: 0.825rem;" onclick="window.open('<?php echo $calendarLink; ?>', '_blank')">
                            <i class="fa-solid fa-calendar-check me-2"></i>Simpan ke Google Kalender
                        </button>
                        
                        <?php if ($enableMouseAnimation): ?>
                        <div class="d-flex justify-content-center align-items-center mt-4 mb-2">
                            <div class="mouse-animation border border-secondary border-2 rounded-5 px-2 py-1 opacity-50">
                                <div class="scroll-animation rounded-4 bg-secondary"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <p class="pb-4 m-0 text-secondary" style="font-size: 0.825rem;">Gulir ke Bawah</p>
                    </div>
                </section>
                
                <!-- Wave Separator -->
                <div class="svg-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="color-theme-svg no-gap-bottom">
                        <path fill="currentColor" fill-opacity="1" d="M0,160L48,144C96,128,192,96,288,106.7C384,117,480,171,576,165.3C672,160,768,96,864,96C960,96,1056,160,1152,154.7C1248,149,1344,75,1392,37.3L1440,0L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                    </svg>
                </div>
                
                <!-- Bride & Groom Section -->
                <section class="bg-white-black text-center" id="bride">
                    <?php if ($showBismillah): ?>
                    <h2 class="font-arabic py-4 m-0" style="font-size: 2rem;">بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ</h2>
                    <?php endif; ?>
                    <h2 class="font-esthetic py-4 m-0" style="font-size: 2rem;">Assalamualaikum Warahmatullahi Wabarakatuh</h2>
                    <p class="pb-4 px-2 m-0" style="font-size: 0.95rem;">Tanpa mengurangi rasa hormat, kami mengundang Anda untuk berkenan menghadiri acara pernikahan kami:</p>
                    
                    <div class="overflow-x-hidden pb-4">
                        <!-- Groom -->
                        <div class="position-relative">
                            <div class="position-absolute" style="top: 0%; right: 5%;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="opacity-50" data-time="500" data-class="animate-love" viewBox="0 0 16 16">
                                    <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143q.09.083.176.171a3 3 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15"/>
                                </svg>
                            </div>
                            
                            <div data-aos="fade-right" data-aos-duration="2000" class="pb-1">
                                <img src="<?php echo escape_html(public_path($groomPhoto)); ?>" alt="mempelai pria" class="img-center-crop rounded-circle border border-3 border-light shadow my-4 mx-auto">
                                <h2 class="font-esthetic m-0" style="font-size: 2.125rem;"><?php echo $groomName; ?></h2>
                                <p class="mt-3 mb-1" style="font-size: 1.25rem;">Putra dari</p>
                                <p class="mb-0" style="font-size: 0.95rem;"><?php echo $groomFather; ?></p>
                                <p class="mb-0" style="font-size: 0.95rem;">dan</p>
                                <p class="mb-0" style="font-size: 0.95rem;"><?php echo $groomMother; ?></p>
                            </div>
                            
                            <div class="position-absolute" style="top: 90%; left: 5%;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="opacity-50" data-time="2000" data-class="animate-love" viewBox="0 0 16 16">
                                    <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143q.09.083.176.171a3 3 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15"/>
                                </svg>
                            </div>
                        </div>
                        
                        <h2 class="font-esthetic mt-4" style="font-size: 4.5rem;">&amp;</h2>
                        
                        <!-- Bride -->
                        <div class="position-relative">
                            <div class="position-absolute" style="top: 0%; right: 5%;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="opacity-50" data-time="3000" data-class="animate-love" viewBox="0 0 16 16">
                                    <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143q.09.083.176.171a3 3 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15"/>
                                </svg>
                            </div>
                            
                            <div data-aos="fade-left" data-aos-duration="2000" class="pb-1">
                                <img src="<?php echo escape_html(public_path($bridePhoto)); ?>" alt="mempelai wanita" class="img-center-crop rounded-circle border border-3 border-light shadow my-4 mx-auto">
                                <h2 class="font-esthetic m-0" style="font-size: 2.125rem;"><?php echo $brideName; ?></h2>
                                <p class="mt-3 mb-1" style="font-size: 1.25rem;">Putri dari</p>
                                <p class="mb-0" style="font-size: 0.95rem;"><?php echo $brideFather; ?></p>
                                <p class="mb-0" style="font-size: 0.95rem;">dan</p>
                                <p class="mb-0" style="font-size: 0.95rem;"><?php echo $brideMother; ?></p>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Wave Separator -->
                <div class="svg-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="color-theme-svg no-gap-bottom">
                        <path fill="currentColor" fill-opacity="1" d="M0,224L34.3,234.7C68.6,245,137,267,206,266.7C274.3,267,343,245,411,234.7C480,224,549,224,617,213.3C685.7,203,754,181,823,197.3C891.4,213,960,267,1029,266.7C1097.1,267,1166,213,1234,192C1302.9,171,1371,181,1406,186.7L1440,192L1440,320L1405.7,320C1371.4,320,1303,320,1234,320C1165.7,320,1097,320,1029,320C960,320,891,320,823,320C754.3,320,686,320,617,320C548.6,320,480,320,411,320C342.9,320,274,320,206,320C137.1,320,69,320,34,320L0,320Z"></path>
                    </svg>
                </div>
                
                <?php if ($isStoryEnabled): ?>
                <!-- Love Story: original DewanaKL boundary -->
                <section class="bg-light-dark pt-2 pb-4">
                    <div class="container"><div class="bg-theme-auto rounded-5 shadow p-3">
                        <h2 class="font-esthetic text-center py-2 mb-2" style="font-size: 2.125rem;">Kisah Cinta</h2>
                        <?php if ($videoEnabled): ?>
                        <div id="video-love-stroy" class="position-relative rounded-4 mb-2 pb-0" data-src="<?php echo escape_html(filter_var($videoPath, FILTER_VALIDATE_URL) ? $videoPath : public_path($videoPath)); ?>" data-vid-class="w-100 rounded-4 shadow-sm m-0 p-0">
                            <video class="w-100 rounded-4 shadow-sm m-0 p-0" src="<?php echo escape_html(filter_var($videoPath, FILTER_VALIDATE_URL) ? $videoPath : public_path($videoPath)); ?>" controls playsinline preload="metadata" muted></video>
                        </div>
                        <?php endif; ?>
                        <div class="overflow-y-scroll overflow-x-hidden p-2 with-scrollbar" style="height: 15rem;">
                            <?php foreach (array_values((array)$config['love_story']['items'] ?? []) as $index => $story): ?>
                            <div class="row"><div class="col-auto position-relative"><p class="position-relative d-flex justify-content-center align-items-center bg-theme-auto border border-secondary border-2 opacity-100 rounded-circle m-0 p-0 z-1" style="width: 2rem; height: 2rem;"><?php echo $index + 1; ?></p><hr class="position-absolute top-0 start-50 translate-middle-x border border-secondary h-100 z-0 opacity-100 m-0 rounded-4 shadow-none"></div><div class="col mt-1 mb-3 ps-0"><p class="fw-bold mb-2"><?php echo escape_html($story['title'] ?? 'Cerita Kami'); ?></p><p class="small mb-0"><?php echo nl2br(escape_html($story['description'] ?? '')); ?></p></div></div>
                            <?php endforeach; ?>
                        </div>
                    </div></div>
                </section>
                <?php endif; ?>

                <!-- Wedding Date / Countdown Section -->
                <?php if (theme_section_enabled($config, 'dewankl', 'wedding_date')): ?>
                <section class="bg-light-dark py-5" id="wedding-date">
                    <div class="container">
                        <div class="border rounded-5 shadow p-3">
                            <h2 class="font-esthetic text-center py-2 m-0" style="font-size: 2.25rem;">Kami Akan Menikah</h2>
                            
                            <!-- Countdown -->
                            <div class="d-flex justify-content-center flex-wrap gap-3 my-4" id="countdown" data-countdown="<?php echo escape_html($countdownTarget); ?>">
                                <div class="text-center">
                                    <p class="m-0" style="font-size: 1.25rem;" id="days">0</p><small class="ms-1 me-0 my-0 p-0 d-inline">Hari</small>
                                </div>
                                <div class="text-center">
                                    <p class="m-0" style="font-size: 1.25rem;" id="hours">0</p><small class="ms-1 me-0 my-0 p-0 d-inline">Jam</small>
                                </div>
                                <div class="text-center">
                                    <p class="m-0" style="font-size: 1.25rem;" id="minutes">0</p><small class="ms-1 me-0 my-0 p-0 d-inline">Menit</small>
                                </div>
                                <div class="text-center">
                                    <p class="m-0" style="font-size: 1.25rem;" id="seconds">0</p><small class="ms-1 me-0 my-0 p-0 d-inline">Detik</small>
                                </div>
                            </div>
                            
                            <p class="py-2 m-0" style="font-size: 0.95rem;">Dengan memohon rahmat dan ridho Allah Subhanahu Wa Ta'ala, insyaAllah kami akan menyelenggarakan acara:</p>
                            
                            <!-- Akad -->
                            <div class="overflow-x-hidden">
                                <div class="py-2" data-aos="fade-right" data-aos-duration="1500">
                                    <h2 class="font-esthetic m-0 py-2" style="font-size: 2rem;">Akad Nikah</h2>
                                    <p style="font-size: 0.95rem;"><?php echo $akadDateFormatted; ?> | Pukul <?php echo escape_html($akadTime); ?> WIB</p>
                                </div>
                                
                                <!-- Reception -->
                                <div class="py-2" data-aos="fade-left" data-aos-duration="1500">
                                    <h2 class="font-esthetic m-0 py-2" style="font-size: 2rem;">Resepsi</h2>
                                    <p style="font-size: 0.95rem;"><?php echo $receptionDateFormatted; ?> | Pukul <?php echo escape_html($receptionTime); ?> WIB - Selesai</p>
                                </div>
                            </div>
                            
                            <!-- Dresscode -->
                            <?php if (!empty($config['dresscode']['enabled'])): ?>
                            <p class="py-2 m-0" style="font-size: 0.95rem;">Demi kehangatan bersama, kami memohon kesediaan Anda untuk mengenakan dress code berikut:</p>
                            
                            <div class="py-2" data-aos="fade-down" data-aos-duration="1500">
                                <div class="d-flex justify-content-center align-items-center mb-3">
                                    <div class="shadow rounded-circle border border-secondary" style="width: 3rem; height: 3rem; background-color: white;"></div>
                                    <div class="shadow rounded-circle border border-secondary" style="width: 3rem; height: 3rem; background-color: aquamarine; margin-left: -1rem;"></div>
                                    <div class="shadow rounded-circle border border-secondary" style="width: 3rem; height: 3rem; background-color: limegreen; margin-left: -1rem;"></div>
                                </div>
                                <p style="font-size: 0.95rem;"><?php echo escape_html($config['dresscode']['color'] ?? 'Putih / Pastel'); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Location Button -->
                            <?php if ($isMapsEnabled): ?>
                            <div class="py-2" data-aos="fade-down" data-aos-duration="1500">
                                <a href="<?php echo $mapsUrl; ?>" target="_blank" class="btn btn-outline-auto btn-sm rounded-pill shadow mb-2 px-3">
                                    <i class="fa-solid fa-map-location-dot me-2"></i>Lihat Google Maps
                                </a>
                                <small class="d-block my-1"><?php echo $address; ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Gallery Section -->
                <?php if ($isGalleryEnabled): ?>
                <section class="bg-white-black pb-5 pt-3" id="gallery">
                    <div class="container"><div class="border rounded-5 shadow p-3">
                        <h2 class="font-esthetic text-center py-2 m-0" style="font-size: 2.25rem;">Galeri</h2>
                        <?php $galleryImages = []; foreach ((array)$config['gallery']['items'] as $item) { $galleryImages[] = is_array($item) ? ($item['path'] ?? $item['src'] ?? '') : (string)$item; } while (count($galleryImages) < 6) $galleryImages[] = $coverPath; ?>
                        <?php foreach ([['carousel-image-one', 0], ['carousel-image-two', 3]] as [$carouselId, $offset]): ?>
                        <div id="<?php echo $carouselId; ?>" data-aos="fade-up" data-aos-duration="1500" class="carousel slide mt-4" data-bs-ride="carousel">
                            <div class="carousel-indicators"><button type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Geser 1"></button><button type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide-to="1" aria-label="Geser 2"></button><button type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide-to="2" aria-label="Geser 3"></button></div>
                            <div class="carousel-inner rounded-4">
                                <?php for ($slide = 0; $slide < 3; $slide++): $image = $galleryImages[$offset + $slide] ?: $coverPath; ?><div class="carousel-item<?php echo $slide === 0 ? ' active' : ''; ?>"><img src="<?php echo escape_html(public_path($image)); ?>" data-src="<?php echo escape_html(public_path($image)); ?>" alt="gambar <?php echo $offset + $slide + 1; ?>" class="d-block img-fluid cursor-pointer" onclick="undangan.guest.modal(this)"></div><?php endfor; ?>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Sebelumnya</span></button>
                            <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Berikutnya</span></button>
                        </div>
                        <?php endforeach; ?>
                    </div></div>
                </section>
                <?php endif; ?>
                
                <!-- Hadiah Pernikahan Section -->
                <?php if ($isGiftEnabled): ?>
                <section class="bg-light-dark pb-3">
                    <div class="container text-center">
                        <h2 class="font-esthetic pt-3 mb-4" style="font-size: 2.25rem;">Hadiah Pernikahan</h2>
                        <p class="mb-1" style="font-size: 0.95rem;">Dengan hormat, bagi Anda yang ingin memberikan tanda kasih kepada kami, dapat melalui:</p>
                        
                        <!-- Bank Transfer -->
                        <div class="bg-theme-auto rounded-4 shadow p-3 mx-4 mt-4 text-start" data-aos="fade-up" data-aos-duration="2500">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                            <p class="d-inline">Transfer</p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <p class="m-0 p-0" style="font-size: 0.95rem;"><i class="fa-regular fa-user fa-sm me-1"></i><?php echo $giftHolder; ?></p>
                                <button class="btn btn-outline-auto btn-sm shadow-sm rounded-4 py-0" style="font-size: 0.75rem;" data-bs-toggle="collapse" data-bs-target="#collapseTf">
                                    <i class="fa-solid fa-circle-info fa-sm me-1"></i>Info
                                </button>
                            </div>
                            
                            <div class="collapse" id="collapseTf">
                                <hr class="my-2 py-1">
                                <p class="m-0" style="font-size: 0.9rem;"><i class="fa-solid fa-building-columns me-1"></i><?php echo $giftBank; ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <p class="m-0 p-0" style="font-size: 0.85rem;"><i class="fa-solid fa-credit-card me-1"></i><?php echo $giftAccount; ?></p>
                                    <button class="btn btn-outline-auto btn-sm shadow-sm rounded-4 py-0 amplop-copy-btn" style="font-size: 0.75rem;" data-account="<?php echo $giftAccount; ?>">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- E-Wallet -->
                        <div class="bg-theme-auto rounded-4 shadow p-3 mx-4 mt-4 text-start" data-aos="fade-up" data-aos-duration="2500">
                            <i class="fa-solid fa-qrcode fa-lg"></i>
                            <p class="d-inline">E-Wallet</p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <p class="m-0 p-0" style="font-size: 0.95rem;"><i class="fa-regular fa-user fa-sm me-1"></i><?php echo $giftHolder; ?></p>
                                <button class="btn btn-outline-auto btn-sm shadow-sm rounded-4 py-0" style="font-size: 0.75rem;" data-bs-toggle="collapse" data-bs-target="#collapseEwallet">
                                    <i class="fa-solid fa-circle-info fa-sm me-1"></i>Info
                                </button>
                            </div>
                            
                            <div class="collapse" id="collapseEwallet">
                                <hr class="my-2 py-1">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <p class="m-0 p-0" style="font-size: 0.85rem;"><i class="fa-solid fa-phone-volume me-1"></i><?php echo $giftEwalletLabel; ?></p>
                                    <button class="btn btn-outline-auto btn-sm shadow-sm rounded-4 py-0 amplop-copy-btn" style="font-size: 0.75rem;" data-account="<?php echo $giftEwalletNumber; ?>">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                                <p class="m-0 p-0" style="font-size: 0.85rem;"><?php echo $giftEwalletNumber; ?></p>
                            </div>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- RSVP / Comments Section -->
                <?php if ($isRsvpEnabled): ?>
                <section class="bg-light-dark my-0 pb-0 pt-3" id="comment">
                    <div class="container">
                        <div class="border rounded-5 shadow p-3 mb-2">
                            <h2 class="font-esthetic text-center mt-2 mb-4" style="font-size: 2.25rem;">Ucapan &amp; Doa</h2>
                            
                            <form id="rsvpForm" class="rsvp-form">
                                <input type="hidden" name="csrf_token" id="csrfToken" />
                                
                                <div class="mb-3">
                                    <label for="form-name" class="form-label my-1"><i class="fa-solid fa-person me-2"></i>Nama</label>
                                    <input dir="auto" type="text" class="form-control shadow-sm rounded-4" id="form-name" name="nama" minlength="2" maxlength="50" placeholder="Isikan Nama Anda" autocomplete="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="form-presence" class="form-label my-1"><i class="fa-solid fa-person-circle-question me-2"></i>Presensi</label>
                                    <select class="form-select shadow-sm rounded-4" id="form-presence" name="status" autocomplete="off" required>
                                        <option value="Hadir">&#9989; Hadir</option>
                                        <option value="Tidak Hadir">&#10060; Berhalangan</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="form-comment" class="form-label my-1"><i class="fa-solid fa-comment me-2"></i>Ucapan &amp; Doa</label>
                                    <textarea dir="auto" class="form-control shadow-sm rounded-4" id="form-comment" name="ucapan" rows="4" minlength="1" maxlength="1000" placeholder="Tulis Ucapan dan Doa" autocomplete="off"></textarea>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-4 shadow m-1">
                                        <i class="fa-solid fa-paper-plane me-2"></i>Kirim
                                    </button>
                                </div>
                                
                                <p id="formMessage" class="form-message text-center mt-3" role="status" aria-live="polite"></p>
                            </form>
                            
                            <!-- Messages List -->
                            <?php if ($isMessagesEnabled): ?>
                            <div id="messages" class="py-3 messages"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- End Of Invitation -->
                <section class="bg-white-black py-2 no-gap-bottom">
                    <div class="container text-center">
                        <p class="pb-2 pt-4" style="font-size: 0.95rem;"><?php echo $closingText; ?></p>
                        
                        <h2 class="font-esthetic" style="font-size: 2rem;">Wassalamualaikum Warahmatullahi Wabarakatuh</h2>
                        <h2 class="font-arabic pt-4" style="font-size: 2rem;">اَلْحَمْدُ لِلّٰهِ رَبِّ الْعٰلَمِيْنَۙ</h2>
                        
                        <hr class="my-3">
                        
                        <div class="row align-items-center justify-content-between flex-column pb-3">
                            <div class="col-auto">
                                <small>Dibuat dengan <i class="fa-solid fa-heart mx-1"></i> oleh DewanaKL</small>
                            </div>
                        </div>
                    </div>
                </section>
                
            </main>
            
            <!-- Bottom Navbar -->
            <nav class="navbar navbar-expand sticky-bottom rounded-top-4 border-top p-0" id="navbar-menu">
                <ul class="navbar-nav nav-justified w-100 align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">
                            <i class="fa-solid fa-house"></i>
                            <span class="d-block" style="font-size: 0.7rem;">Beranda</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#bride">
                            <i class="fa-solid fa-user-group"></i>
                            <span class="d-block" style="font-size: 0.7rem;">Mempelai</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#wedding-date">
                            <i class="fa-solid fa-calendar-check"></i>
                            <span class="d-block" style="font-size: 0.7rem;">Tanggal</span>
                        </a>
                    </li>
                    <?php if ($isGalleryEnabled): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#gallery">
                            <i class="fa-solid fa-images"></i>
                            <span class="d-block" style="font-size: 0.7rem;">Galeri</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($isRsvpEnabled): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#comment">
                            <i class="fa-solid fa-comments"></i>
                            <span class="d-block" style="font-size: 0.7rem;">Ucapan</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
    
    <!-- Music Button -->
    <?php if ($isMusicEnabled): ?>
    <div class="d-flex position-fixed" style="bottom: 10vh; right: 2vh; z-index: 1030;">
        <button type="button" id="button-music" class="btn bg-light-dark border btn-sm rounded-circle d-none btn-transparent shadow-sm mt-3">
            <i class="fa-solid fa-circle-pause spin-button"></i>
        </button>
    </div>
    <audio id="backgroundMusic" src="<?php echo escape_html($musicSrc); ?>" preload="auto" loop></audio>
    <?php endif; ?>
    
    <!-- Welcome Page -->
    <div class="loading-page bg-white-black" id="welcome" style="opacity: 0;">
        <div class="d-flex justify-content-center align-items-center vh-100 overflow-y-auto">
            <div class="d-flex flex-column text-center">
                <h2 class="font-esthetic mb-4" style="font-size: 2.25rem;">Pernikahan Kami</h2>
                
                <img src="<?php echo escape_html(public_path($coverPath)); ?>" alt="latar belakang" class="img-center-crop rounded-circle border border-3 border-light shadow mb-4 mx-auto">
                
                <h2 class="font-esthetic mb-4" style="font-size: 2.25rem;"><?php echo $brideName; ?> &amp; <?php echo $groomName; ?></h2>
                <div id="guest-name" data-message="Kepada Yth Bapak/Ibu/Saudara/i"></div>
                
                <button type="button" class="btn btn-light shadow rounded-4 mt-3 mx-auto" id="openInvitationBtn">
                    <i class="fa-solid fa-envelope-open fa-bounce me-2"></i>Buka Undangan
                </button>
            </div>
        </div>
    </div>
    
    <!-- Loading Page -->
    <div class="loading-page bg-white-black" id="loading" style="opacity: 1;">
        <div class="d-flex justify-content-center align-items-center vh-100 overflow-y-auto">
            <div class="d-flex flex-column width-loading text-center">
                <img src="<?php echo escape_html(public_path($coverPath)); ?>" fetchpriority="high" class="img-fluid mb-3 mx-auto object-fit-cover opacity-0" alt="ikon" style="width: 3.5rem; height: 3.5rem;">
                <div class="progress" role="progressbar" style="height: 0.5rem;" aria-label="bilah kemajuan">
                    <div class="progress-bar" id="progress-bar" style="width: 0%"></div>
                </div>
                <small class="d-none mt-1 text-theme-auto" id="progress-info" style="font-size: 0.8rem;">Memulai aplikasi...</small>
                <noscript>
                    <small class="mt-1 text-danger">Maaf, undangan ini memerlukan JavaScript</small>
                </noscript>
            </div>
        </div>
        <div class="text-center position-fixed w-100" style="bottom: 8%; left: 0;">
            <div class="d-flex flex-column">
                <small class="text-secondary">from</small>
                <small class="text-theme-auto"><i class="fa-brands fa-github me-1"></i>dewanakl</small>
            </div>
        </div>
    </div>
    
    <!-- Modal Image -->
    <div class="modal fade" id="modal-image" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border border-0">
                <div class="modal-body p-0">
                    <div class="d-flex position-absolute top-0 end-0">
                        <a class="btn d-flex justify-content-center align-items-center bg-overlay-auto p-2 m-1 rounded-circle border shadow-sm z-1" role="button" target="_blank" href="#" id="button-modal-click">
                            <i class="fa-solid fa-arrow-up-right-from-square" style="width: 1em !important;"></i>
                        </a>
                        <button class="btn d-flex justify-content-center align-items-center bg-overlay-auto p-2 m-1 rounded-circle border shadow-sm z-1" id="button-modal-download">
                            <i class="fa-solid fa-download" style="width: 1em !important;"></i>
                        </button>
                        <button class="btn d-flex justify-content-center align-items-center bg-overlay-auto p-2 m-1 rounded-circle border shadow-sm z-1" data-bs-dismiss="modal">
                            <i class="fa-solid fa-circle-xmark" style="width: 1em !important;"></i>
                        </button>
                    </div>
                    
                    <img src="" class="img-fluid w-100 rounded-4 cursor-pointer" alt="gambar" id="show-modal-image">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Dependencies JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Theme JS -->
    <script src="<?php echo escape_html(get_theme_asset_url('dewankl', 'script.js')); ?>"></script>
    

</body>
</html>
