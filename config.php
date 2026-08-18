<?php
// Basic runtime hardening: do not show PHP errors to users
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

// Load optional environment files (first /var/www/private/.env, then local .env)
function load_dotenv_file(string $path): void {
    if (!is_readable($path)) return;
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $v = trim($v, "\"'");
        if ($k === '') continue;
        if (getenv($k) === false) putenv("{$k}={$v}");
        if (!array_key_exists($k, $_ENV)) $_ENV[$k] = $v;
    }
}

load_dotenv_file('/var/www/private/.env');
load_dotenv_file(__DIR__ . '/.env');

// Safe file locations
if (!defined('ROOT_DIR')) define('ROOT_DIR', __DIR__);
if (!defined('UPLOADS_DIR')) define('UPLOADS_DIR', ROOT_DIR . '/uploads');
if (!defined('UPLOADS_COVER_DIR')) define('UPLOADS_COVER_DIR', UPLOADS_DIR . '/cover');
if (!defined('UPLOADS_MUSIC_DIR')) define('UPLOADS_MUSIC_DIR', UPLOADS_DIR . '/music');
if (!defined('UPLOADS_GALLERY_DIR')) define('UPLOADS_GALLERY_DIR', UPLOADS_DIR . '/gallery');
if (!defined('UPLOADS_BACKGROUND_DIR')) define('UPLOADS_BACKGROUND_DIR', UPLOADS_DIR . '/background');
if (!defined('UPLOADS_LOVE_STORY_DIR')) define('UPLOADS_LOVE_STORY_DIR', UPLOADS_DIR . '/love-story');
if (!defined('UPLOADS_THEME_ASSETS_DIR')) define('UPLOADS_THEME_ASSETS_DIR', UPLOADS_DIR . '/theme-assets');
$runtimeDataDir = trim((string)(getenv('UNDANGAN_DATA_DIR') ?: ''));
if ($runtimeDataDir === '') $runtimeDataDir = ROOT_DIR;
if (!defined('RUNTIME_DATA_DIR')) define('RUNTIME_DATA_DIR', rtrim($runtimeDataDir, '/'));
if (!defined('CONFIG_FILE')) define('CONFIG_FILE', RUNTIME_DATA_DIR . '/config.json');
if (!defined('CUSTOM_CSS_FILE')) define('CUSTOM_CSS_FILE', RUNTIME_DATA_DIR . '/custom.css');
if (!defined('EVENT_ICS_FILE')) define('EVENT_ICS_FILE', RUNTIME_DATA_DIR . '/event.ics');

// Security defaults
if (!defined('MAX_UPLOAD_SIZE')) define('MAX_UPLOAD_SIZE', (int) (getenv('MAX_UPLOAD_SIZE') ?: 5 * 1024 * 1024));
if (!defined('MAX_MUSIC_UPLOAD_SIZE')) define('MAX_MUSIC_UPLOAD_SIZE', (int) (getenv('MAX_MUSIC_UPLOAD_SIZE') ?: 15 * 1024 * 1024));
if (!defined('WEBP_QUALITY')) define('WEBP_QUALITY', max(60, min(95, (int) (getenv('WEBP_QUALITY') ?: 82))));
if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', (int) (getenv('SESSION_TIMEOUT') ?: 3600));
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', array_map('strtolower', (array) (getenv('ALLOWED_IMAGE_TYPES') ? explode(',', getenv('ALLOWED_IMAGE_TYPES')) : ['jpg','jpeg','png','webp'])));
if (!defined('ALLOWED_AUDIO_TYPES')) define('ALLOWED_AUDIO_TYPES', ['mp3','ogg','wav']);
if (!defined('ALLOWED_VIDEO_TYPES')) define('ALLOWED_VIDEO_TYPES', ['mp4']);

// Database path resolution
if (getenv('UNDANGAN_DB_PATH')) {
    $dbPath = getenv('UNDANGAN_DB_PATH');
} elseif (is_readable('/var/www/private/database.sqlite')) {
    $dbPath = '/var/www/private/database.sqlite';
} else {
    $dbPath = RUNTIME_DATA_DIR . '/database.sqlite';
}
if (!defined('DB_PATH')) define('DB_PATH', $dbPath);
if (!defined('GUEST_LINKS_FILE')) define('GUEST_LINKS_FILE', RUNTIME_DATA_DIR . '/guest-links.json');

// Security headers
function send_security_header(string $name, string $value): void {
    $exists = false;
    foreach (headers_list() as $h) {
        if (stripos($h, $name . ':') === 0) { $exists = true; break; }
    }
    if (!$exists) header("{$name}: {$value}");
}

send_security_header('X-Content-Type-Options', 'nosniff');
send_security_header('X-Frame-Options', 'SAMEORIGIN');
send_security_header('Referrer-Policy', 'strict-origin-when-cross-origin');
send_security_header('Permissions-Policy', 'microphone=(), camera=(), geolocation=()');

function config_defaults(): array {
    return [
        'site' => [
            'url' => '',
            'title' => 'Undangan Pernikahan Andi & Februana',
            'description' => 'Mohon doa restu dan kehadiran Bapak/Ibu/Saudara/i di hari spesial kami.',
            'keywords' => 'undangan pernikahan, wedding invitation, Andi, Februana',
            'open_graph_title' => 'Undangan Pernikahan Andi & Februana',
            'open_graph_description' => 'Mohon doa restu dan kehadiran Bapak/Ibu/Saudara/i di hari spesial kami.',
            'twitter_card' => 'summary_large_image',
            // Optional user-provided media; clean installs start without sample files.
            'open_graph_image' => '',
            'schema' => json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Event',
                'name' => 'Undangan Pernikahan Andi & Februana',
                'startDate' => '2026-12-29T09:00:00+07:00',
                'location' => [
                    '@type' => 'Place',
                    'name' => 'PFR2+G9H Asinan',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => 'Kabupaten Semarang',
                        'addressRegion' => 'Jawa Tengah',
                        'addressCountry' => 'ID'
                    ]
                ],
                'description' => 'Mohon doa restu dan kehadiran Bapak/Ibu/Saudara/i di hari spesial kami.',
                'eventStatus' => 'https://schema.org/EventScheduled',
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode'
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ],
        'wedding' => [
            'bride_name' => 'Februana',
            'groom_name' => 'Andi',
            'title' => 'Undangan Pernikahan Andi & Februana',
            'opening_text' => 'Mohon doa restu dan kehadiran Bapak/Ibu/Saudara/i di hari spesial kami.',
            'closing_text' => 'Kami sangat menghargai kehadiran dan doa restu Bapak/Ibu/Saudara/i agar hari ini menjadi lebih berkah.',
            'quote' => 'Dengan memohon rahmat Allah SWT, kami mengundang Anda untuk hadir pada hari istimewa kami.',
            'bride_nickname' => 'Februana',
            'groom_nickname' => 'Andi'
        ],
        'parents' => [
            'bride_father' => 'Ayah Februana',
            'bride_mother' => 'Ibu Februana',
            'groom_father' => 'Ayah Andi',
            'groom_mother' => 'Ibu Andi'
        ],
        'schedule' => [
            'akad_date' => '2026-12-29',
            'akad_time' => '09:00',
            'reception_date' => '2026-12-29',
            'reception_time' => '11:00',
            'timezone' => 'Asia/Jakarta',
            'google_calendar_link' => 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=Undangan+Pernikahan+Andi+%26+Februana&dates=20261229T090000/20261229T110000&details=Mohon+doa+restu+dan+kehadiran+Bapak%2FIbu%2FSaudara%2Fi+di+hari+spesial+kami.&location=PFR2%2BG9H+Asinan%2C+Kabupaten+Semarang%2C+Jawa+Tengah&ctz=Asia%2FJakarta',
            'countdown_target' => '2026-12-29T09:00:00+07:00'
        ],
        'location' => [
            'venue' => 'PFR2+G9H Asinan',
            'address' => 'PFR2+G9H Asinan, Kabupaten Semarang, Jawa Tengah',
            'maps_url' => 'https://www.google.com/maps/search/?api=1&query=-7.2586798,110.4509814',
            'maps_embed' => 'https://maps.google.com/maps?q=-7.2586798,110.4509814&z=17&output=embed'
        ],
        'media' => [
            'cover' => '',
            'bride_photo' => '',
            'groom_photo' => '',
            'couple_photo' => '',
            'music' => '',
                'background_hero' => '',
                'love_story_video' => '',
                'background_sections' => []
        ],
        'gallery' => [
            'cover' => '',
            'items' => []
        ],
        'gift' => [
            'bank' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Andi',
            'qris_image' => '',
            'e_wallet_label' => 'GoPay',
            'e_wallet_number' => '+6285162909164'
        ],
        'whatsapp' => [
            'phone' => '+6285162909164',
            'message' => 'Assalamu\'alaikum Andi & Februana, saya ingin mengonfirmasi kehadiran untuk acara pernikahan.'
        ],
        'admin' => [
            'username' => 'admin',
            'password_hash' => ''
        ],
        'theme' => [
            'mode' => 'preset',
            'theme_preset' => 'dewankl',
            'primary_color' => '#c84c47',
            'secondary_color' => '#f0c2a1',
            'accent_color' => '#f0c2a1',
            'background_color' => '#fff8f2',
            'paper_color' => '#ffffff',
            'muted_color' => '#806f66',
            'text_color' => '#2f2424',
            'link_color' => '#c84c47',
            'button_style' => 'rounded',
            'border_radius' => '28px',
            'shadow' => '0 22px 60px rgba(73,45,34,.14)',
            'container_width' => '1200px',
            'section_spacing' => '80px',
            'heading_font' => 'Playfair Display, serif',
            'body_font' => 'Lato, sans-serif',
            'font_size_base' => '16px',
            'animation_enabled' => true,
            'navbar_style' => 'transparent',
            'card_style' => 'elevated',
            'footer_style' => 'centered',
            // Hero settings - Desktop
            'hero_height' => '100vh',
            'hero_vertical_alignment' => 'center',
            'hero_content_width' => '900px',
            'hero_image_fit' => 'cover',
            'hero_image_position' => 'center',
            // Hero settings - Mobile
            'mobile_hero_height' => '85vh',
            'mobile_hero_vertical_alignment' => 'center',
            'mobile_hero_content_width' => '100%',
            'mobile_hero_image_fit' => 'cover',
            'mobile_hero_image_position' => 'center top'
        ],
        'buttons' => [
            'mobile_layout' => '2-columns'
        ],
        'sections' => [
            [
                'id' => 'hero',
                'title' => 'Hero',
                'subtitle' => '',
                'enabled' => true,
                'order' => 1,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'guest_intro',
                'title' => 'Kepada Yth.',
                'subtitle' => '',
                'enabled' => true,
                'order' => 2,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'undangan',
                'title' => 'Undangan Pernikahan',
                'subtitle' => 'Dengan memohon rahmat Allah SWT, kami mengundang Anda untuk hadir pada hari istimewa kami.',
                'enabled' => true,
                'order' => 3,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'bride_groom',
                'title' => 'Bride & Groom',
                'subtitle' => 'Mempelai',
                'enabled' => true,
                'order' => 4,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'countdown',
                'title' => 'Countdown',
                'subtitle' => 'Menuju Hari Bahagia',
                'enabled' => true,
                'order' => 5,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'cerita',
                'title' => 'Love Story',
                'subtitle' => 'Cerita Kami',
                'enabled' => true,
                'order' => 6,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'galeri',
                'title' => 'Gallery',
                'subtitle' => 'Galeri Foto',
                'enabled' => true,
                'order' => 7,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'acara',
                'title' => 'Events',
                'subtitle' => 'Acara',
                'enabled' => true,
                'order' => 8,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'lokasi',
                'title' => 'Location',
                'subtitle' => 'Lokasi Acara',
                'enabled' => true,
                'order' => 9,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'rsvp',
                'title' => 'RSVP',
                'subtitle' => 'Konfirmasi Kehadiran',
                'enabled' => true,
                'order' => 10,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'amplop',
                'title' => 'Gift',
                'subtitle' => 'Ucapan & Hadiah',
                'enabled' => true,
                'order' => 11,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'messages',
                'title' => 'Guest Wishes',
                'subtitle' => 'Ucapan Tamu',
                'enabled' => true,
                'order' => 12,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'music',
                'title' => 'Music',
                'subtitle' => 'Musik Latar',
                'enabled' => true,
                'order' => 13,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'footer',
                'title' => 'Footer',
                'subtitle' => '',
                'enabled' => true,
                'order' => 14,
                'custom_title' => '',
                'custom_subtitle' => ''
            ]
        ],
        'love_story' => [
            'items' => []
        ],
        'dresscode' => [
            'enabled' => true,
            'title' => 'Dresscode',
            'color' => 'Putih / Pastel',
            'rule' => 'Rapi dan sopan',
            'description' => 'Kenakan busana terbaikmu untuk momen spesial.'
        ],
        'theme_options' => [
            'dewankl' => [
                'show_bismillah' => true,
                'enable_confetti' => true,
                'enable_mouse_animation' => true,
                'enable_music' => true,
                'welcome_note' => ''
            ],
            'elix' => [
                'timeline_style' => 'vertical',
                'show_countdown_circle' => true,
                'header_greeting' => ''
            ],
            'rainier' => [
                'glass_opacity' => '0.85',
                'show_bismillah' => true,
                'hero_accent_color' => '#b8655d',
                'quote_note' => ''
            ],
            'archak' => [
                'enable_parallax' => true,
                'enable_preloader' => true,
                'divider_style' => 'ornament',
                'header_badge_image' => '',
                'archak_welcome_msg' => ''
            ],
            'parang' => []
        ],
        'theme_visuals' => [
            'dewankl' => [],
            'elix' => [],
            'rainier' => [],
            'archak' => [],
            'parang' => [],
            'custom' => []
        ],
        'custom_css' => ''
    ];
}


function theme_registry(): array {
    return [
        'elegant' => [
            'id' => 'elegant',
            'name' => 'Elegant',
            'label' => 'Elegant',
            'description' => 'Palet hangat dan klasik untuk undangan yang lembut.',
            'version' => '1.0.0',
            'author' => 'Februana Studio',
            'source' => 'Built-in CMS preset',
            'license' => 'Project-internal',
            'category' => 'classic',
            'values' => [
                'primary_color' => '#c84c47',
                'secondary_color' => '#f0c2a1',
                'accent_color' => '#f0c2a1',
                'background_color' => '#fff8f2',
                'paper_color' => '#ffffff',
                'muted_color' => '#806f66',
                'text_color' => '#2f2424',
                'link_color' => '#c84c47',
                'button_style' => 'rounded',
                'border_radius' => '28px',
                'shadow' => '0 22px 60px rgba(73,45,34,.14)',
                'container_width' => '1200px',
                'section_spacing' => '80px',
                'heading_font' => 'Playfair Display, serif',
                'body_font' => 'Lato, sans-serif',
                'font_size_base' => '16px'
            ]
        ],
        'dark' => [
            'id' => 'dark',
            'name' => 'Dark',
            'label' => 'Dark',
            'description' => 'Kontras gelap modern dengan aksen emas.',
            'version' => '1.0.0',
            'author' => 'Februana Studio',
            'source' => 'Built-in CMS preset',
            'license' => 'Project-internal',
            'category' => 'modern',
            'values' => [
                'primary_color' => '#d6a85f',
                'secondary_color' => '#2b2430',
                'accent_color' => '#f4d28f',
                'background_color' => '#151217',
                'paper_color' => '#211b26',
                'muted_color' => '#cbbfb4',
                'text_color' => '#f6efe7',
                'link_color' => '#f4d28f',
                'button_style' => 'pill',
                'border_radius' => '22px',
                'shadow' => '0 24px 70px rgba(0,0,0,.38)',
                'container_width' => '1160px',
                'section_spacing' => '84px',
                'heading_font' => 'Cormorant Garamond, serif',
                'body_font' => 'Inter, sans-serif',
                'font_size_base' => '16px'
            ]
        ],
        'floral' => [
            'id' => 'floral',
            'name' => 'Floral',
            'label' => 'Floral',
            'description' => 'Nuansa bunga pastel dengan aksen hijau sage.',
            'version' => '1.0.0',
            'author' => 'Februana Studio',
            'source' => 'Built-in CMS preset',
            'license' => 'Project-internal',
            'category' => 'romantic',
            'values' => [
                'primary_color' => '#a45c68',
                'secondary_color' => '#e8b7c2',
                'accent_color' => '#8ca77b',
                'background_color' => '#fff7f8',
                'paper_color' => '#ffffff',
                'muted_color' => '#846d73',
                'text_color' => '#3d3034',
                'link_color' => '#8f4756',
                'button_style' => 'rounded',
                'border_radius' => '34px',
                'shadow' => '0 20px 55px rgba(164,92,104,.18)',
                'container_width' => '1180px',
                'section_spacing' => '88px',
                'heading_font' => 'Great Vibes, cursive',
                'body_font' => 'Lato, sans-serif',
                'font_size_base' => '16px'
            ]
        ],
        'minimal' => [
            'id' => 'minimal',
            'name' => 'Minimal',
            'label' => 'Minimal',
            'description' => 'Tampilan bersih monokrom dengan aksen sederhana.',
            'version' => '1.0.0',
            'author' => 'Februana Studio',
            'source' => 'Built-in CMS preset',
            'license' => 'Project-internal',
            'category' => 'minimal',
            'values' => [
                'primary_color' => '#1f2937',
                'secondary_color' => '#e5e7eb',
                'accent_color' => '#9ca3af',
                'background_color' => '#fafafa',
                'paper_color' => '#ffffff',
                'muted_color' => '#6b7280',
                'text_color' => '#111827',
                'link_color' => '#374151',
                'button_style' => 'square',
                'border_radius' => '12px',
                'shadow' => '0 16px 42px rgba(17,24,39,.10)',
                'container_width' => '1100px',
                'section_spacing' => '72px',
                'heading_font' => 'Inter, sans-serif',
                'body_font' => 'Inter, sans-serif',
                'font_size_base' => '16px'
            ]
        ],
        'dewankl' => [
            'id' => 'dewankl',
            'name' => 'DewanaKL',
            'label' => 'DewanaKL',
            'description' => 'Layout modern berkelas dengan fokus pada hero yang lebih ekspresif dan kartu informasi yang lebih terstruktur.',
            'version' => '1.0.0',
            'author' => 'DewanaKL adaptation',
            'source' => 'dewanakl/undangan',
            'license' => 'MIT (adapted in-project; source design reviewed as inspiration only)',
            'category' => 'modern',
            'values' => [
                'primary_color' => '#7b4a3a',
                'secondary_color' => '#d9c0a3',
                'accent_color' => '#f2e4d3',
                'background_color' => '#f5efe8',
                'paper_color' => '#fcf8f3',
                'muted_color' => '#6e5a52',
                'text_color' => '#2a1f1c',
                'link_color' => '#7b4a3a',
                'button_style' => 'pill',
                'border_radius' => '18px',
                'shadow' => '0 18px 44px rgba(57,34,28,.12)',
                'container_width' => '1200px',
                'section_spacing' => '100px',
                'heading_font' => 'Cormorant Garamond, serif',
                'body_font' => 'Inter, sans-serif',
                'font_size_base' => '16px'
            ],
            'schema' => [
                'show_bismillah' => [
                    'type' => 'boolean',
                    'label' => 'Tampilkan Bismillah',
                    'description' => 'Tampilkan lafaz Bismillah di bagian atas pembuka.',
                    'default' => true
                ],
                'enable_confetti' => [
                    'type' => 'boolean',
                    'label' => 'Efek Confetti',
                    'description' => 'Aktifkan animasi efek serpihan confetti saat tombol buka undangan diklik.',
                    'default' => true
                ],
                'enable_mouse_animation' => [
                    'type' => 'boolean',
                    'label' => 'Animasi Kursor Mouse',
                    'description' => 'Aktifkan animasi jejak kursor mouse.',
                    'default' => true
                ],
                'enable_music' => [
                    'type' => 'boolean',
                    'label' => 'Musik Latar',
                    'description' => 'Tampilkan kontrol dan audio musik latar pada template asli DewanaKL.',
                    'default' => true
                ],
                'welcome_note' => [
                    'type' => 'textarea',
                    'label' => 'Pesan Sambutan Khusus',
                    'description' => 'Teks ucapan atau pesan sambutan tambahan (dukungan multi-baris).',
                    'default' => ''
                ]
            ],
            'capabilities' => [
                'content' => ['wedding', 'schedule', 'countdown', 'gallery', 'music', 'parents', 'gift', 'maps', 'seo', 'whatsapp', 'rsvp', 'sections'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation'],
            'visual_capabilities' => [
                'accent_color' => ['type' => 'color', 'label' => 'Warna Aksen', 'description' => 'Warna aksen tombol dan elemen interaktif DewanaKL.', 'default' => '#7b4a3a'],
                'heading_font' => ['type' => 'font', 'label' => 'Font Judul', 'description' => 'Font dekoratif untuk heading pendek dan nama pasangan.', 'default' => 'Sacramento, cursive', 'options' => ['Sacramento, cursive' => 'Sacramento', 'Georgia, serif' => 'Georgia']],
                'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font readable untuk detail acara dan form.', 'default' => 'Josefin Sans, sans-serif', 'options' => ['Josefin Sans, sans-serif' => 'Josefin Sans', 'system-ui, sans-serif' => 'System UI', 'Arial, sans-serif' => 'Arial', 'Georgia, serif' => 'Georgia']],
                'hero_background' => ['type' => 'image', 'label' => 'Latar Hero', 'description' => 'Path media atau URL. Kosongkan untuk memakai cover.', 'default' => ''],
                'hero_overlay' => ['type' => 'range', 'label' => 'Overlay Hero', 'description' => 'Kekuatan lapisan gelap pada latar hero.', 'default' => '0.30', 'min' => '0', 'max' => '0.85', 'step' => '0.05'],
            ]
        ],
        'elix' => [
            'id' => 'elix',
            'name' => 'Elix',
            'label' => 'Elix',
            'description' => 'Layout modern airy dengan hero yang lebih terbuka, tulisan elegan, dan tata letak kartu yang lebih segar.',
            'version' => '1.0.0',
            'author' => 'Elix adaptation',
            'source' => 'elix-stack/wedding-invitation-1',
            'license' => 'Unverified external source; adapted in-project without copying source code',
            'category' => 'airy-modern',
            'values' => [
                'primary_color' => '#d97774',
                'secondary_color' => '#f4d9c6',
                'accent_color' => '#a8c7b6',
                'background_color' => '#f9f5f2',
                'paper_color' => '#fffefb',
                'muted_color' => '#6d5b56',
                'text_color' => '#2b1f1d',
                'link_color' => '#c26d62',
                'button_style' => 'rounded',
                'border_radius' => '22px',
                'shadow' => '0 18px 42px rgba(81, 58, 50, .12)',
                'container_width' => '1180px',
                'section_spacing' => '96px',
                'heading_font' => 'Georgia, serif',
                'body_font' => 'Segoe UI, sans-serif',
                'font_size_base' => '16px'
            ],
            'schema' => [
                'timeline_style' => [
                    'type' => 'select',
                    'label' => 'Gaya Timeline',
                    'description' => 'Pilih tampilan layout untuk bagian cerita cinta.',
                    'options' => [
                        'vertical' => 'Vertikal',
                        'horizontal' => 'Horizontal'
                    ],
                    'default' => 'vertical'
                ],
                'show_countdown_circle' => [
                    'type' => 'boolean',
                    'label' => 'Hitung Mundur Melingkar',
                    'description' => 'Tampilkan animasi hitung mundur dalam desain lingkaran.',
                    'default' => true
                ],
                'header_greeting' => [
                    'type' => 'textarea',
                    'label' => 'Teks Catatan Header',
                    'description' => 'Pesan ucapan atau catatan tajuk multi-baris di bagian atas.',
                    'default' => ''
                ]
            ],
            'capabilities' => [
                'content' => ['wedding', 'schedule', 'countdown', 'gallery', 'music', 'parents', 'gift', 'maps', 'seo', 'whatsapp', 'rsvp', 'sections'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation'],
'visual_capabilities' => [
                'accent_color' => ['type' => 'color', 'label' => 'Warna Aksen', 'description' => 'Warna aksen tombol, link, dan detail Elix.', 'default' => '#f14e95'],
                'heading_font' => ['type' => 'font', 'label' => 'Font Display', 'description' => 'Font display brush untuk nama dan heading pendek.', 'default' => 'Pacifico, cursive', 'options' => ['Pacifico, cursive' => 'Pacifico', 'Georgia, serif' => 'Georgia']],
                'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font isi readable untuk informasi dan form.', 'default' => 'Work Sans, sans-serif', 'options' => ['Work Sans, sans-serif' => 'Work Sans', 'system-ui, sans-serif' => 'System UI', 'Arial, sans-serif' => 'Arial']],
                'hero_background' => ['type' => 'image', 'label' => 'Latar Hero', 'description' => 'Path media atau URL. Kosongkan untuk memakai source default.', 'default' => ''],
                'hero_overlay' => ['type' => 'range', 'label' => 'Overlay Hero', 'description' => 'Kekuatan overlay pada foto hero Elix.', 'default' => '0.45', 'min' => '0', 'max' => '0.85', 'step' => '0.05'],
                'countdown_scale' => ['type' => 'range', 'label' => 'Skala Hitung Mundur', 'description' => 'Skala visual countdown tanpa menghapus countdown.', 'default' => '0.65', 'min' => '0.60', 'max' => '1.00', 'step' => '0.05'],
            ]
        ],
        'rainier' => [
            'id' => 'rainier',
            'name' => 'Rainier',
            'label' => 'Rainier',
            'description' => 'Layout editorial modern dengan fokus pada hero split, ruang yang lebih dramatis, dan panel informasi yang terstruktur seperti karya seni.',
            'version' => '1.0.0',
            'author' => 'Rainier adaptation',
            'source' => 'Rainier-PS/Invitation-Template',
            'license' => 'Unverified external source; adapted in-project without copying source code',
            'category' => 'editorial-modern',
            'values' => [
                'primary_color' => '#b8655d',
                'secondary_color' => '#e6d7cd',
                'accent_color' => '#7a8c7e',
                'background_color' => '#f9f4f1',
                'paper_color' => '#fffdfb',
                'muted_color' => '#5c5255',
                'text_color' => '#1d1b22',
                'link_color' => '#b8655d',
                'button_style' => 'pill',
                'border_radius' => '20px',
                'shadow' => '0 18px 40px rgba(23,20,28,.12)',
                'container_width' => '1200px',
                'section_spacing' => '92px',
                'heading_font' => 'Georgia, serif',
                'body_font' => 'Segoe UI, sans-serif',
                'font_size_base' => '16px'
            ],
            'schema' => [
                'glass_opacity' => [
                    'type' => 'text',
                    'label' => 'Opasitas Glassmorphism',
                    'description' => 'Tingkat transparansi panel kaca (contoh: 0.85).',
                    'default' => '0.85'
                ],
                'show_bismillah' => [
                    'type' => 'boolean',
                    'label' => 'Tampilkan Bismillah',
                    'description' => 'Tampilkan lafaz Bismillah di awal.',
                    'default' => true
                ],
                'hero_accent_color' => [
                    'type' => 'color',
                    'label' => 'Warna Aksen Kustom Hero',
                    'description' => 'Aksen warna khusus pada hero Rainier.',
                    'default' => '#b8655d'
                ],
                'quote_note' => [
                    'type' => 'textarea',
                    'label' => 'Catatan Editorial Quote',
                    'description' => 'Teks kutipan/catatan tambahan berformat multi-baris.',
                    'default' => ''
                ]
            ],
            'capabilities' => [
                'content' => ['wedding', 'schedule', 'countdown', 'gallery', 'music', 'parents', 'gift', 'maps', 'seo', 'whatsapp', 'rsvp', 'sections'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation'],
            'visual_capabilities' => [
                'accent_color' => ['type' => 'color', 'label' => 'Warna Aksen', 'description' => 'Warna aksen Rainier.', 'default' => '#b8655d'],
                'heading_font' => ['type' => 'font', 'label' => 'Font Display', 'description' => 'Font heading editorial Rainier.', 'default' => 'Cormorant Garamond, serif', 'options' => ['Cormorant Garamond, serif' => 'Cormorant Garamond', 'Georgia, serif' => 'Georgia']],
                'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font isi Rainier.', 'default' => 'Outfit, sans-serif', 'options' => ['Outfit, sans-serif' => 'Outfit', 'system-ui, sans-serif' => 'System UI', 'Arial, sans-serif' => 'Arial']],
                'hero_background' => ['type' => 'image', 'label' => 'Latar Hero', 'description' => 'Path media atau URL. Kosongkan untuk memakai cover/source default.', 'default' => ''],
                'glass_opacity' => ['type' => 'range', 'label' => 'Opasitas Panel Kaca', 'description' => 'Transparansi panel kaca Rainier.', 'default' => '0.40', 'min' => '0.20', 'max' => '0.90', 'step' => '0.05'],
            ]
        ],
        'parang' => [
            'id' => 'parang',
            'name' => 'Parang',
            'label' => 'Parang',
            'description' => 'Nuansa Manten Jawi dengan pola parang, ornamen gunungan, panel cream-gold, dan navigasi editorial responsif.',
            'version' => '1.0.0',
            'author' => 'Parang adaptation',
            'source' => 'User-provided HTML design reference',
            'license' => 'User-provided design reference; assets retained as supplied',
            'category' => 'javanese-heritage',
            'values' => [
                'primary_color' => '#221002',
                'secondary_color' => '#7b5902',
                'accent_color' => '#C49A45',
                'background_color' => '#fff8f2',
                'paper_color' => '#F0E3CE',
                'muted_color' => '#4f453e',
                'text_color' => '#211b0e',
                'link_color' => '#7b5902',
                'button_style' => 'square',
                'border_radius' => '12px',
                'shadow' => '0 24px 48px rgba(34,16,2,.20)',
                'container_width' => '1200px',
                'section_spacing' => '80px',
                'heading_font' => 'Libre Caslon Text, serif',
                'body_font' => 'Manrope, sans-serif',
                'font_size_base' => '16px'
            ],
            'schema' => [],
            'capabilities' => [
                'content' => ['wedding', 'parents', 'schedule', 'countdown', 'gallery', 'music', 'gift', 'maps', 'rsvp', 'story', 'guest_name', 'media', 'seo', 'whatsapp', 'calendar', 'sections'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation'],
            'visual_capabilities' => [
                'accent_color' => ['type' => 'color', 'label' => 'Aksen Emas', 'description' => 'Aksen emas untuk border, tombol, dan ornamen Parang.', 'default' => '#C49A45'],
                'heading_font' => ['type' => 'font', 'label' => 'Font Heading', 'description' => 'Font editorial Libre Caslon Text untuk identitas Manten Jawi.', 'default' => 'Libre Caslon Text, serif', 'options' => ['Libre Caslon Text, serif' => 'Libre Caslon Text', 'Georgia, serif' => 'Georgia']],
                'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font Manrope untuk isi, navigasi, form, dan detail acara.', 'default' => 'Manrope, sans-serif', 'options' => ['Manrope, sans-serif' => 'Manrope', 'system-ui, sans-serif' => 'System UI', 'Arial, sans-serif' => 'Arial']],
                'hero_background' => ['type' => 'image', 'label' => 'Pola Parang', 'description' => 'Asset background parang yang digunakan desain terlampir.', 'default' => 'themes/parang/assets/parang-pattern.webp']
            ]
        ],
        'pawiwahan' => [
            'id' => 'pawiwahan',
            'name' => 'Pawiwahan',
            'label' => 'Pawiwahan',
            'description' => 'Template Pawiwahan Bali berbasis thema-1 dengan navbar Bootstrap, carousel, modal pembuka, countdown jQuery, galeri, lokasi, hadiah, dan pesan.',
            'version' => '1.0.0',
            'author' => 'DE Juna adaptation',
            'source' => 'parta99/pawiwahan',
            'license' => 'MIT (Copyright (c) 2021 DE Juna; adapted in-project)',
            'category' => 'balinese-classic',
            'values' => [
                'primary_color' => '#d77fa1',
                'secondary_color' => '#c996cc',
                'accent_color' => '#ec7272',
                'background_color' => '#f7edf2',
                'paper_color' => '#ffffff',
                'muted_color' => '#6d5a62',
                'text_color' => '#372d36',
                'link_color' => '#8b4f70',
                'button_style' => 'rounded',
                'border_radius' => '6px',
                'shadow' => '5px 5px 10px #caced1,-5px -5px 10px white',
                'container_width' => '1140px',
                'section_spacing' => '56px',
                'heading_font' => 'Tangerine, cursive',
                'body_font' => 'Raleway, sans-serif',
                'font_size_base' => '16px'
            ],
            'schema' => [
                'hero_background' => ['type' => 'image', 'label' => 'Latar Hero Pawiwahan', 'description' => 'Path media atau URL. Kosongkan untuk memakai source default Pawiwahan.', 'default' => ''],
                'show_protocol' => ['type' => 'boolean', 'label' => 'Tampilkan Catatan Acara', 'description' => 'Tampilkan catatan sumber yang kompatibel dengan bagian protokol Pawiwahan.', 'default' => true]
            ],
            'capabilities' => [
                'content' => ['wedding', 'parents', 'schedule', 'countdown', 'gallery', 'music', 'gift', 'maps', 'rsvp', 'messages', 'guest_name', 'media', 'seo', 'whatsapp', 'calendar'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'carousel', 'navigation', 'countdown', 'modal', 'footer', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'carousel', 'navigation', 'countdown', 'modal', 'footer', 'animation'],
            'visual_capabilities' => [
                'accent_color' => ['type' => 'color', 'label' => 'Aksen Pawiwahan', 'description' => 'Aksen tombol dan elemen interaktif Pawiwahan.', 'default' => '#ec7272'],
                'heading_font' => ['type' => 'font', 'label' => 'Font Judul', 'description' => 'Font display sumber Pawiwahan.', 'default' => 'Tangerine, cursive', 'options' => ['Tangerine, cursive' => 'Tangerine', 'Beau Rivage, cursive' => 'Beau Rivage', 'Georgia, serif' => 'Georgia']],
                'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font isi sumber Pawiwahan.', 'default' => 'Raleway, sans-serif', 'options' => ['Raleway, sans-serif' => 'Raleway', 'system-ui, sans-serif' => 'System UI', 'Arial, sans-serif' => 'Arial']],
                'hero_background' => ['type' => 'image', 'label' => 'Latar Hero', 'description' => 'Path media atau URL. Kosongkan untuk memakai foto source yang dipertahankan lokal.', 'default' => 'themes/pawiwahan/assets/hero-source.jpg']
            ]
        ],
        'archak' => [
            'id' => 'archak',
            'name' => 'Archak',
            'label' => 'Archak',
            'description' => 'Layout modern dengan hero dua kolom, detail ornamental yang lebih terstruktur, dan panel informasi yang terasa seperti editorial wedding brief.',
            'version' => '1.0.0',
            'author' => 'Archak adaptation',
            'source' => 'archakNath/wedding-invitation-website',
            'license' => 'Unverified external source; adapted in-project without copying source code',
            'category' => 'editorial-luxury',
            'values' => [
                'primary_color' => '#8c5a4d',
                'secondary_color' => '#f0d8c4',
                'accent_color' => '#6e7c63',
                'background_color' => '#f6f1eb',
                'paper_color' => '#fffdfb',
                'muted_color' => '#5d5350',
                'text_color' => '#211d1a',
                'link_color' => '#8c5a4d',
                'button_style' => 'rounded',
                'border_radius' => '18px',
                'shadow' => '0 18px 46px rgba(30,25,24,.09)',
                'container_width' => '1200px',
                'section_spacing' => '94px',
                'heading_font' => 'Georgia, serif',
                'body_font' => 'Segoe UI, sans-serif',
                'font_size_base' => '16px'
            ],
            'schema' => [
                'enable_parallax' => [
                    'type' => 'boolean',
                    'label' => 'Efek Parallax',
                    'description' => 'Aktifkan pergerakan efek parallax pada background.',
                    'default' => true
                ],
                'enable_preloader' => [
                    'type' => 'boolean',
                    'label' => 'Tampilkan Preloader',
                    'description' => 'Tampilkan animasi pemuatan layar pembuka.',
                    'default' => true
                ],
                'divider_style' => [
                    'type' => 'select',
                    'label' => 'Gaya Pembatas (Divider)',
                    'description' => 'Bentuk gaya ornamen pembatas antarseksi.',
                    'options' => [
                        'ornament' => 'Ornamen Mewah',
                        'line' => 'Garis Minimalis',
                        'none' => 'Tanpa Pembatas'
                    ],
                    'default' => 'ornament'
                ],
                'header_badge_image' => [
                    'type' => 'image',
                    'label' => 'Lencana/Emblem Header',
                    'description' => 'Pilih/unggah gambar emblem khusus untuk seksi header.',
                    'default' => ''
                ],
                'archak_welcome_msg' => [
                    'type' => 'textarea',
                    'label' => 'Pesan Sambutan Archak',
                    'description' => 'Pesan ucapan multi-baris bergaya editorial Archak.',
                    'default' => ''
                ]
            ],
            'capabilities' => [
                'content' => ['wedding', 'schedule', 'countdown', 'gallery', 'music', 'parents', 'gift', 'maps', 'seo', 'whatsapp', 'rsvp', 'sections'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation'],
            'visual_capabilities' => [
                'accent_color' => ['type' => 'color', 'label' => 'Warna Aksen', 'description' => 'Warna aksen Archak.', 'default' => '#8c5a4d'],
                'heading_font' => ['type' => 'font', 'label' => 'Font Heading', 'description' => 'Font heading identitas Archak.', 'default' => 'Cinzel, serif', 'options' => ['Cinzel, serif' => 'Cinzel', 'Georgia, serif' => 'Georgia']],
                'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font isi readable Archak.', 'default' => 'Quicksand, sans-serif', 'options' => ['Quicksand, sans-serif' => 'Quicksand', 'system-ui, sans-serif' => 'System UI', 'Arial, sans-serif' => 'Arial']],
                'hero_background' => ['type' => 'image', 'label' => 'Latar Hero', 'description' => 'Path media atau URL. Kosongkan untuk memakai foto pasangan.', 'default' => ''],
                'hero_title_scale' => ['type' => 'range', 'label' => 'Skala Judul Hero', 'description' => 'Skala nama pasangan di hero.', 'default' => '1', 'min' => '0.85', 'max' => '1.10', 'step' => '0.05'],
            ]
        ]
    ];
}

function theme_builtin_preset_keys(): array {
    return ['dewankl', 'elix', 'rainier', 'archak', 'parang', 'pawiwahan'];
}

function theme_presets(): array {
    $registry = array_intersect_key(theme_registry(), array_flip(theme_builtin_preset_keys()));
    foreach ($registry as $key => $preset) {
        $registry[$key] = array_replace([
            'label' => $preset['name'] ?? ucfirst((string)$key),
            'description' => 'Preset tema dengan gaya ' . ($preset['category'] ?? 'standar'),
        ], $preset);
    }
    return $registry;
}

function get_theme_mode(array $config = []): string {
    $mode = trim((string)($config['theme']['mode'] ?? ''));
    if ($mode === 'preset' || $mode === 'custom') {
        return $mode;
    }

    $preset = trim((string)($config['theme']['theme_preset'] ?? ''));
    if ($preset === 'custom' || $preset === '') {
        return 'custom';
    }

    return 'preset';
}

function get_active_theme_meta(array $config = []): array {
    $mode = get_theme_mode($config);
    if ($mode === 'custom') {
        return [
            'id' => 'custom',
            'name' => 'Custom',
            'label' => 'Custom',
            'description' => 'Tema kustom berdasarkan parameter manual.',
            'version' => '1.0.0',
            'author' => 'Admin',
            'source' => 'CMS manual override',
            'license' => 'Project-internal',
            'category' => 'custom',
            'capabilities' => [
                'content' => ['wedding', 'schedule', 'countdown', 'gallery', 'music', 'parents', 'gift', 'maps', 'seo', 'whatsapp', 'rsvp', 'sections'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'gallery_layout', 'navigation', 'footer', 'spacing', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'gallery_layout', 'navigation', 'footer', 'spacing', 'animation']
        ];
    }

    $selectedPreset = trim((string)($config['theme']['theme_preset'] ?? 'dewankl')) ?: 'dewankl';
    $registry = theme_registry();
    if (isset($registry[$selectedPreset])) {
        return $registry[$selectedPreset];
    }
    return [
        'id' => 'custom',
        'name' => 'Custom',
        'label' => 'Custom',
        'description' => 'Tema kustom berdasarkan parameter manual.',
        'version' => '1.0.0',
        'author' => 'Admin',
        'source' => 'CMS manual override',
        'license' => 'Project-internal',
        'category' => 'custom'
    ];
}

function resolve_theme_preset(array $theme, string $presetKey): array {
    $presets = theme_presets();
    if (!isset($presets[$presetKey])) {
        return $theme;
    }
    return array_replace($theme, $presets[$presetKey]['values'], ['theme_preset' => $presetKey]);
}

function apply_theme_preset(array $theme, string $presetKey): array {
    return resolve_theme_preset($theme, $presetKey);
}

/** Resolve the CMS-native Custom theme state without losing legacy configs. */
function theme_custom_config(array $config): array {
    $defaults = (array)(config_defaults()['theme'] ?? []);
    $saved = $config['theme_custom'] ?? null;
    if (!is_array($saved) || $saved === []) {
        $saved = get_theme_mode($config) === 'custom' ? (array)($config['theme'] ?? []) : $defaults;
    }
    $theme = array_replace($defaults, $saved);
    $theme['mode'] = 'custom';
    $theme['theme_preset'] = 'custom';
    return $theme;
}

function ensure_upload_dirs(): void {
    foreach ([UPLOADS_DIR, UPLOADS_COVER_DIR, UPLOADS_MUSIC_DIR, UPLOADS_GALLERY_DIR, UPLOADS_BACKGROUND_DIR, UPLOADS_LOVE_STORY_DIR, UPLOADS_THEME_ASSETS_DIR] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}

function init_database(string $dbPath = DB_PATH): bool {
    try {
        $db = new SQLite3($dbPath, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $db->exec('CREATE TABLE IF NOT EXISTS tamu (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nama TEXT NOT NULL,
            status TEXT NOT NULL,
            ucapan TEXT,
            visible INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
        $checkCol = $db->querySingle("SELECT 1 FROM pragma_table_info('tamu') WHERE name='visible'");
        if (!$checkCol) {
            @$db->exec('ALTER TABLE tamu ADD COLUMN visible INTEGER DEFAULT 1');
        }
        $db->close();
        return true;
    } catch (Throwable $e) {
        error_log('Database initialization failed: ' . $e->getMessage());
        return false;
    }
}

function load_config(): array {
    ensure_upload_dirs();
    $defaults = config_defaults();
    if (!is_readable(CONFIG_FILE)) {
        if (function_exists('theme_contract_registry')) {
            $defaults['theme_sections'] = [];
            foreach (array_keys(theme_contract_registry()) as $presetKey) {
                $defaults['theme_sections'][$presetKey] = theme_contract_default_sections($presetKey);
            }
        }
        save_config($defaults);
        return $defaults;
    }
    $raw = @file_get_contents(CONFIG_FILE);
    if ($raw === false) {
        return $defaults;
    }
    $config = json_decode($raw, true);
    if (!is_array($config)) {
        return $defaults;
    }
    $config = array_replace_recursive($defaults, $config);
    if (!is_array($config['gallery']['items'])) {
        $config['gallery']['items'] = [];
    }
    if (!is_array($config['media']['background_sections'])) {
        $config['media']['background_sections'] = [];
    }
    if (!is_array($config['love_story']['items'])) {
        $config['love_story']['items'] = [];
    }
    if (!is_array($config['sections'])) {
        $config['sections'] = $defaults['sections'];
    } else {
        foreach ($config['sections'] as &$section) {
            if (!is_array($section)) {
                continue;
            }
            $section['id'] = normalize_section_id((string)($section['id'] ?? ''));
            if (!isset($section['custom_title'])) {
                $section['custom_title'] = '';
            }
            if (!isset($section['custom_subtitle'])) {
                $section['custom_subtitle'] = '';
            }
        }
        unset($section);
    }
    if (empty($config['theme']['theme_preset'])) {
        $config['theme']['theme_preset'] = 'dewankl';
    }
    if (empty($config['theme']['mode'])) {
        $config['theme']['mode'] = get_theme_mode($config);
    }
    if (!in_array($config['theme']['mode'] ?? '', ['preset', 'custom'], true)) {
        $config['theme']['mode'] = get_theme_mode($config);
    }
    // Preserve a dedicated Custom snapshot while keeping old config.json files valid.
    if (!is_array($config['theme_custom'] ?? null) || $config['theme_custom'] === []) {
        $config['theme_custom'] = theme_custom_config($config);
    }
    // Ensure hero settings exist for backward compatibility
    if (!isset($config['theme']['hero_height'])) {
        $config['theme']['hero_height'] = '100vh';
    }
    if (!isset($config['theme']['hero_vertical_alignment'])) {
        $config['theme']['hero_vertical_alignment'] = 'center';
    }
    if (!isset($config['theme']['hero_content_width'])) {
        $config['theme']['hero_content_width'] = '900px';
    }
    if (!isset($config['theme']['hero_image_fit'])) {
        $config['theme']['hero_image_fit'] = 'cover';
    }
    if (!isset($config['theme']['hero_image_position'])) {
        $config['theme']['hero_image_position'] = 'center';
    }
    if (!isset($config['theme']['mobile_hero_height'])) {
        $config['theme']['mobile_hero_height'] = '85vh';
    }
    if (!isset($config['theme']['mobile_hero_vertical_alignment'])) {
        $config['theme']['mobile_hero_vertical_alignment'] = 'center';
    }
    if (!isset($config['theme']['mobile_hero_content_width'])) {
        $config['theme']['mobile_hero_content_width'] = '100%';
    }
    if (!isset($config['theme']['mobile_hero_image_fit'])) {
        $config['theme']['mobile_hero_image_fit'] = 'cover';
    }
    if (!isset($config['theme']['mobile_hero_image_position'])) {
        $config['theme']['mobile_hero_image_position'] = 'center top';
    }
    // Ensure buttons settings exist for backward compatibility
    if (!isset($config['buttons']['mobile_layout'])) {
        $config['buttons']['mobile_layout'] = '2-columns';
    }
    if (!is_array($config['theme_options'] ?? null)) {
        $config['theme_options'] = $defaults['theme_options'];
    } else {
        $config['theme_options'] = array_replace_recursive($defaults['theme_options'], $config['theme_options']);
    }

    // Built-in themes keep their own section contract. The legacy global
    // `sections` array remains untouched for Custom mode and old data.
    if (!is_array($config['theme_sections'] ?? null)) {
        $config['theme_sections'] = [];
    }
    if (function_exists('theme_contract_registry')) {
        foreach (array_keys(theme_contract_registry()) as $presetKey) {
            if (!isset($config['theme_sections'][$presetKey]) || !is_array($config['theme_sections'][$presetKey])) {
                $config['theme_sections'][$presetKey] = theme_contract_default_sections($presetKey);
            }
        }
    }
    if (empty($config['schedule']['countdown_target'])) {
        $config['schedule']['countdown_target'] = compute_countdown_target($config['schedule']);
    }
    if (!is_file(EVENT_ICS_FILE)) {
        write_event_ics($config);
    }
    if (empty($config['story']) && !empty($config['love_story']['items'])) {
        $config['story'] = array_map(function($item) {
            return [
                'date' => $item['event_date'] ?? ($item['date'] ?? ''),
                'title' => $item['title'] ?? '',
                'description' => $item['description'] ?? '',
            ];
        }, $config['love_story']['items']);
    }
    return $config;
}

function compute_countdown_target(array $schedule): string {
    $date = trim((string)($schedule['akad_date'] ?? ''));
    $time = trim((string)($schedule['akad_time'] ?? ''));
    $timezone = trim((string)($schedule['timezone'] ?? 'Asia/Jakarta')) ?: 'Asia/Jakarta';
    if ($date === '' || $time === '') {
        return '';
    }
    try {
        $dt = new DateTimeImmutable($date . ' ' . $time, new DateTimeZone($timezone));
        return $dt->format('Y-m-d\TH:i:sP');
    } catch (Throwable $e) {
        return '';
    }
}

function save_config(array $config): bool {
    ensure_upload_dirs();
    $config = array_replace_recursive(config_defaults(), $config);
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    $tmp = CONFIG_FILE . '.tmp';
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, CONFIG_FILE)) {
        @unlink($tmp);
        return false;
    }
    write_event_ics($config);
    return true;
}

function load_guest_links(): array {
    if (!is_readable(GUEST_LINKS_FILE)) {
        return [];
    }
    $raw = @file_get_contents(GUEST_LINKS_FILE);
    if ($raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }
    $result = [];
    foreach ($data as $item) {
        if (!is_array($item)) {
            continue;
        }
        if (!isset($item['guest_name'], $item['invitation_url'], $item['created_at'])) {
            continue;
        }
        $result[] = [
            'guest_name' => trim((string)$item['guest_name']),
            'invitation_url' => trim((string)$item['invitation_url']),
            'created_at' => trim((string)$item['created_at'])
        ];
    }
    return $result;
}

function load_custom_css(): string {
    if (!is_readable(CUSTOM_CSS_FILE)) {
        $config = load_config();
        return (string)($config['custom_css'] ?? '');
    }
    $css = @file_get_contents(CUSTOM_CSS_FILE);
    return $css === false ? '' : $css;
}

function validate_custom_css(string $css): array {
    $css = str_replace("\0", '', $css);
    if (strlen($css) > 100000) {
        return ['valid' => false, 'message' => 'Custom CSS terlalu besar (maks 100 KB).'];
    }
    $forbiddenPatterns = [
        '/<\s*\/?\s*script\b/i' => 'Tag script tidak diizinkan.',
        '/<\?php|\?>/i' => 'Kode PHP tidak diizinkan.',
        '/<\s*\/?\s*[a-z][^>]*>/i' => 'Tag HTML tidak diizinkan.',
        '/javascript\s*:/i' => 'URL javascript: tidak diizinkan.',
        '/\bon[a-z]+\s*=/i' => 'Inline event handler tidak diizinkan.',
        '/expression\s*\(/i' => 'CSS expression tidak diizinkan.',
        '/@import\b/i' => '@import tidak diizinkan pada Custom CSS.'
    ];
    foreach ($forbiddenPatterns as $pattern => $message) {
        if (preg_match($pattern, $css)) {
            return ['valid' => false, 'message' => $message];
        }
    }

    $withoutComments = preg_replace('#/\*.*?\*/#s', '', $css) ?? $css;
    $withoutStrings = preg_replace('/(["\']).*?\1/s', '', $withoutComments) ?? $withoutComments;
    $balance = 0;
    $parenBalance = 0;
    $bracketBalance = 0;
    $length = strlen($withoutStrings);
    for ($i = 0; $i < $length; $i++) {
        $char = $withoutStrings[$i];
        if ($char === '{') $balance++;
        if ($char === '}') $balance--;
        if ($char === '(') $parenBalance++;
        if ($char === ')') $parenBalance--;
        if ($char === '[') $bracketBalance++;
        if ($char === ']') $bracketBalance--;
        if ($balance < 0 || $parenBalance < 0 || $bracketBalance < 0) {
            return ['valid' => false, 'message' => 'Struktur CSS tidak valid.'];
        }
    }
    if ($balance !== 0 || $parenBalance !== 0 || $bracketBalance !== 0) {
        return ['valid' => false, 'message' => 'Kurung CSS tidak seimbang.'];
    }
    if (trim($css) !== '' && !preg_match('/[{}:;]/', $css)) {
        return ['valid' => false, 'message' => 'Custom CSS harus berisi aturan CSS yang valid.'];
    }
    return ['valid' => true, 'message' => ''];
}

function save_custom_css(string $css): bool {
    $tmp = CUSTOM_CSS_FILE . '.tmp';
    if (@file_put_contents($tmp, $css, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, CUSTOM_CSS_FILE)) {
        @unlink($tmp);
        return false;
    }
    @chmod(CUSTOM_CSS_FILE, 0644);
    return true;
}

function save_guest_links(array $links): bool {
    $data = array_values($links);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    $tmp = GUEST_LINKS_FILE . '.tmp';
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, GUEST_LINKS_FILE)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function get_safe_config(array $config): array {
    unset($config['admin']['password_hash']);
    return $config;
}

function get_config_value(array $config, string $path, $default = '') {
    $keys = explode('.', $path);
    $value = $config;
    foreach ($keys as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return $default;
        }
        $value = $value[$key];
    }
    return $value;
}

function get_theme_option(array $config, string $presetKey, string $optionKey, $default = null) {
    return $config['theme_options'][$presetKey][$optionKey] ?? $default;
}

function set_theme_option(array &$config, string $presetKey, string $optionKey, $value): void {
    if (!isset($config['theme_options'][$presetKey])) {
        $config['theme_options'][$presetKey] = [];
    }
    $config['theme_options'][$presetKey][$optionKey] = $value;
}

function normalize_section_id(string $id): string {
    $id = strtolower(trim($id));
    $aliases = [
        'bride_groom' => 'bride_groom',
        'couple' => 'bride_groom',
        'events' => 'acara',
        'schedule' => 'acara',
        'acara' => 'acara',
        'love_story' => 'cerita',
        'story' => 'cerita',
        'cerita' => 'cerita',
        'gallery' => 'galeri',
        'galeri' => 'galeri',
        'location' => 'lokasi',
        'lokasi' => 'lokasi',
        'gift' => 'amplop',
        'amplop' => 'amplop',
        'guest_wishes' => 'messages',
        'messages' => 'messages',
        'guest_intro' => 'guest_intro',
        'guest-intro' => 'guest_intro',
        'undangan' => 'undangan',
        'opening' => 'undangan',
        'music' => 'music',
        'musik' => 'music',
        'rsvp' => 'rsvp',
        'hero' => 'hero',
        'countdown' => 'countdown',
        'footer' => 'footer'
    ];
    return $aliases[$id] ?? $id;
}

function build_full_url(string $path): string {
    $path = ltrim($path, '/');
    $config = load_config();
    return rtrim($config['site']['url'] ?? config_defaults()['site']['url'], '/') . '/' . $path;
}

function normalize_path(string $path): string {
    $path = trim($path);
    $path = str_replace(['\\', '../', './'], ['', '', ''], $path);
    return $path;
}

function generate_safe_filename(string $originalName, string $prefix = ''): string {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '') {
        $extension = 'dat';
    }
    $random = bin2hex(random_bytes(8));
    $prefix = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$prefix);
    if ($prefix !== '') {
        $prefix .= '-';
    }
    return $prefix . $random . '.' . $extension;
}

function normalize_image_orientation(string $sourcePath): ?string {
    $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return null;
    }

    $orientation = null;
    if (function_exists('exif_read_data') && in_array($ext, ['jpg', 'jpeg'], true)) {
        $exif = @exif_read_data($sourcePath, 'IFD0');
        if (is_array($exif) && isset($exif['Orientation'])) {
            $orientation = (int)$exif['Orientation'];
        }
    }

    if ($orientation === null || $orientation === 1) {
        return null;
    }

    if (!extension_loaded('gd')) {
        return null;
    }

    $image = @imagecreatefromstring(@file_get_contents($sourcePath));
    if (!$image) {
        return null;
    }

    $rotated = $image;
    switch ($orientation) {
        case 3:
            $rotated = imagerotate($image, 180, 0);
            break;
        case 6:
            $rotated = imagerotate($image, -90, 0);
            break;
        case 8:
            $rotated = imagerotate($image, 90, 0);
            break;
    }

    if ($rotated !== false) {
        $tmp = $sourcePath . '.orient.tmp';
        $ok = imagejpeg($rotated, $tmp, 90);
        imagedestroy($rotated);
        imagedestroy($image);
        if ($ok) {
            @rename($tmp, $sourcePath);
            return $sourcePath;
        }
        @unlink($tmp);
    }

    imagedestroy($image);
    return null;
}

function find_imagemagick_binary(): ?string {
    $candidates = ['magick', 'convert'];
    foreach ($candidates as $binary) {
        $output = [];
        @exec('command -v ' . escapeshellarg($binary) . ' 2>/dev/null', $output, $code);
        if ($code === 0 && !empty($output[0])) {
            return trim((string) $output[0]);
        }
    }
    return null;
}

function media_role_alias(string $role): string {
    $role = strtolower(trim($role));
    $aliases = [
        'hero' => 'background',
        'hero_background' => 'background',
        'background_hero' => 'background',
        'background_section' => 'background',
        'background_sections' => 'background',
        'bride' => 'bride_photo',
        'groom' => 'groom_photo',
        'couple' => 'couple_photo',
        'love_story' => 'story',
        'love-story' => 'story',
        'gift' => 'qris_image',
        'qris' => 'qris_image',
        'og' => 'og_image',
        'theme' => 'theme_asset',
        'theme_assets' => 'theme_asset',
    ];
    return $aliases[$role] ?? ($role !== '' ? $role : 'generic');
}

/**
 * Declarative output policy for every upload role. These are maximum dimensions
 * unless an exact width/height pair is declared with fit=cover. Values are
 * derived from the built-in CSS display contracts: hero/background surfaces
 * are 16:9, profile/circular photos are kept natural and bounded, galleries
 * retain their aspect ratio, and story/tender assets are not unnecessarily
 * enlarged. The renderer remains independent from this processor.
 */
function media_requirements(): array {
    return [
        'generic' => ['max_width' => 2400, 'max_height' => 1600, 'fit' => 'preserve'],
        'cover' => ['max_width' => 1600, 'max_height' => 1200, 'fit' => 'preserve'],
        'background' => ['width' => 1920, 'height' => 1080, 'fit' => 'cover'],
        'bride_photo' => ['max_width' => 1600, 'max_height' => 1600, 'fit' => 'preserve'],
        'groom_photo' => ['max_width' => 1600, 'max_height' => 1600, 'fit' => 'preserve'],
        'couple_photo' => ['max_width' => 1800, 'max_height' => 1200, 'fit' => 'preserve'],
        'gallery' => ['max_width' => 1600, 'max_height' => 1200, 'fit' => 'preserve'],
        'story' => ['max_width' => 1200, 'max_height' => 900, 'fit' => 'preserve'],
        'qris_image' => ['max_width' => 1200, 'max_height' => 1200, 'fit' => 'preserve'],
        'og_image' => ['width' => 1200, 'height' => 630, 'fit' => 'cover'],
        'theme_asset' => ['max_width' => 2400, 'max_height' => 1600, 'fit' => 'preserve'],
    ];
}

function media_requirement(string $role, ?string $preset = null): array {
    $role = media_role_alias($role);
    $requirements = media_requirements();
    $requirement = $requirements[$role] ?? $requirements['generic'];
    $preset = strtolower(trim((string)$preset));

    // Preset overrides remain sparse by design: only a preset that proves a
    // distinct visual contract should override a global media role.
    $presetOverrides = [
        'parang' => [],
    ];
    if ($preset !== '' && isset($presetOverrides[$preset][$role])) {
        $requirement = array_replace($requirement, $presetOverrides[$preset][$role]);
    }
    return $requirement;
}

function media_storage_roots(): array {
    return [
        UPLOADS_COVER_DIR,
        UPLOADS_BACKGROUND_DIR,
        UPLOADS_GALLERY_DIR,
        UPLOADS_LOVE_STORY_DIR,
        UPLOADS_MUSIC_DIR,
        UPLOADS_THEME_ASSETS_DIR,
    ];
}

function media_path_is_safe_storage(string $relativePath): bool {
    $normalized = normalize_media_relative_path($relativePath);
    if ($normalized === null) return false;
    $fullPath = ROOT_DIR . '/' . $normalized;
    $realPath = realpath($fullPath);
    if ($realPath === false) return false;
    foreach (media_storage_roots() as $root) {
        $realRoot = realpath($root);
        if ($realRoot !== false && str_starts_with($realPath, $realRoot . DIRECTORY_SEPARATOR)) return true;
    }
    return false;
}

function verify_webp_output(string $path, array $requirement = []): bool {
    if (!is_file($path) || filesize($path) <= 0) return false;
    $mime = safe_image_mime($path);
    if ($mime !== 'image/webp') return false;
    $info = @getimagesize($path);
    if (!is_array($info) || (int)($info[0] ?? 0) <= 0 || (int)($info[1] ?? 0) <= 0) return false;
    if (isset($requirement['width']) && (int)$info[0] !== (int)$requirement['width']) return false;
    if (isset($requirement['height']) && (int)$info[1] !== (int)$requirement['height']) return false;
    if (isset($requirement['max_width']) && (int)$info[0] > (int)$requirement['max_width']) return false;
    if (isset($requirement['max_height']) && (int)$info[1] > (int)$requirement['max_height']) return false;
    return true;
}

function image_processing_temp_path(string $directory, string $suffix = '.webp.tmp'): string {
    return rtrim($directory, '/\\') . '/.' . bin2hex(random_bytes(12)) . $suffix;
}

function process_image_to_webp(string $sourcePath, string $destinationDir, string $role = 'generic', ?string $preset = null, string $originalName = ''): array {
    if (!is_file($sourcePath) || !is_readable($sourcePath)) {
        return ['success' => false, 'error' => 'Sumber gambar tidak dapat dibaca.'];
    }
    $requirement = media_requirement($role, $preset);
    if (!is_dir($destinationDir) && !@mkdir($destinationDir, 0755, true)) {
        return ['success' => false, 'error' => 'Gagal membuat direktori penyimpanan.'];
    }
    $safeBase = preg_replace('/[^A-Za-z0-9_-]+/', '-', pathinfo($originalName !== '' ? $originalName : basename($sourcePath), PATHINFO_FILENAME));
    $safeBase = trim((string)$safeBase, '-_');
    if ($safeBase === '') $safeBase = 'media';
    $finalName = $safeBase . '-' . bin2hex(random_bytes(8)) . '.webp';
    $finalPath = rtrim($destinationDir, '/\\') . '/' . $finalName;
    $temporaryPath = image_processing_temp_path($destinationDir);
    $binary = find_imagemagick_binary();
    $processed = false;

    if ($binary !== null) {
        $parts = [escapeshellarg($binary), escapeshellarg($sourcePath), '-auto-orient'];
        $fit = (string)($requirement['fit'] ?? 'preserve');
        if ($fit === 'cover' && isset($requirement['width'], $requirement['height'])) {
            $parts[] = '-resize';
            $parts[] = escapeshellarg((int)$requirement['width'] . 'x' . (int)$requirement['height'] . '^');
            $parts[] = '-gravity';
            $parts[] = 'center';
            $parts[] = '-extent';
            $parts[] = escapeshellarg((int)$requirement['width'] . 'x' . (int)$requirement['height']);
        } elseif (isset($requirement['max_width'], $requirement['max_height'])) {
            $parts[] = '-resize';
            $parts[] = escapeshellarg((int)$requirement['max_width'] . 'x' . (int)$requirement['max_height'] . '>');
        }
        $parts[] = '-strip';
        $parts[] = '-quality';
        $parts[] = (string)WEBP_QUALITY;
        $parts[] = 'webp:' . escapeshellarg($temporaryPath);
        $command = implode(' ', $parts);
        $process = @proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (is_resource($process)) {
            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
            $processed = $exitCode === 0 && verify_webp_output($temporaryPath, $requirement);
            if (!$processed && $stderr !== '') error_log('MediaProcessor ImageMagick failed: ' . trim($stderr));
        }
    }

    if (!$processed && extension_loaded('gd') && function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
        $data = @file_get_contents($sourcePath);
        $image = $data === false ? false : @imagecreatefromstring($data);
        if ($image !== false) {
            $sourceWidth = imagesx($image);
            $sourceHeight = imagesy($image);
            $targetWidth = $sourceWidth;
            $targetHeight = $sourceHeight;
            if (isset($requirement['max_width'], $requirement['max_height'])) {
                $scale = min(1, (int)$requirement['max_width'] / max(1, $sourceWidth), (int)$requirement['max_height'] / max(1, $sourceHeight));
                $targetWidth = max(1, (int)round($sourceWidth * $scale));
                $targetHeight = max(1, (int)round($sourceHeight * $scale));
            }
            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
            imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
            $processed = @imagewebp($canvas, $temporaryPath, WEBP_QUALITY);
            imagedestroy($canvas);
            imagedestroy($image);
            $processed = $processed && verify_webp_output($temporaryPath, $requirement);
        }
    }

    if (!$processed || !@rename($temporaryPath, $finalPath) || !verify_webp_output($finalPath, $requirement)) {
        @unlink($temporaryPath);
        @unlink($finalPath);
        return ['success' => false, 'error' => 'Gagal memproses gambar menjadi WebP terverifikasi.'];
    }
    return ['success' => true, 'path' => $finalPath, 'name' => $finalName, 'mime' => 'image/webp', 'width' => @getimagesize($finalPath)[0] ?? null, 'height' => @getimagesize($finalPath)[1] ?? null, 'requirement' => $requirement];
}

/** Backward-compatible wrapper; new upload flows use process_image_to_webp(). */
function process_uploaded_image(string $sourcePath): bool {
    $result = process_image_to_webp($sourcePath, dirname($sourcePath), 'generic', null, basename($sourcePath));
    if (empty($result['success'])) return false;
    if (($result['path'] ?? '') !== $sourcePath && is_file($sourcePath)) @unlink($sourcePath);
    return true;
}

function verify_admin_password(string $password, array $config): bool {
    $password = trim($password);
    if ($password === '') {
        return false;
    }
    
    // Priority 1: Check config.json password_hash
    $hash = $config['admin']['password_hash'] ?? '';
    if ($hash !== '') {
        return password_verify($password, $hash);
    }
    
    // Priority 2: Check .env credentials (ADMIN_USER + ADMIN_PASS)
    $envUser = getenv('ADMIN_USER');
    $envPass = getenv('ADMIN_PASS');
    
    // Reject login if no credentials configured
    if ($envPass === '' || $envPass === false) {
        return false;
    }
    
    $expectedUser = $envUser ?: 'admin';
    return hash_equals($expectedUser, $config['admin']['username'] ?? $expectedUser) && hash_equals($envPass, $password);
}

function set_admin_password(string $password, array &$config): void {
    if ($password === '') {
        return;
    }
    $config['admin']['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
}

function init_session(): void {
    $secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secureFlag,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
    if (!empty($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > SESSION_TIMEOUT) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? false);
        }
        session_destroy();
        session_start();
    }
}

function require_admin(): void {
    init_session();
    if (empty($_SESSION['admin'])) {
        header('Location: /admin');
        exit;
    }
    $_SESSION['last_activity'] = time();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function get_csrf_token(): string {
    init_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(?string $token): bool {
    init_session();
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function safe_image_mime(string $path): ?string {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? @finfo_file($finfo, $path) : null;
    if ($finfo) finfo_close($finfo);
    return $mime ?? null;
}

function upload_file(array $file, string $destinationDir, array $allowedExtensions, int $maxSize, string $role = 'generic', ?string $preset = null): array {
    if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Gagal mengunggah file.'];
    }
    $temporarySource = (string)($file['tmp_name'] ?? '');
    $isCliFixture = PHP_SAPI === 'cli' && is_file($temporarySource);
    if (!is_uploaded_file($temporarySource) && !$isCliFixture) {
        return ['success' => false, 'error' => 'Sumber file tidak valid.'];
    }
    if ((int)($file['size'] ?? 0) > $maxSize) {
        return ['success' => false, 'error' => 'Ukuran file terlalu besar.'];
    }
    $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedExtensions = array_map('strtolower', $allowedExtensions);
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['success' => false, 'error' => 'Format file tidak diizinkan.'];
    }
    $mime = safe_image_mime($temporarySource);
    if ($mime === null) {
        return ['success' => false, 'error' => 'Tipe file tidak dapat diverifikasi.'];
    }
    $isImage = in_array($extension, ALLOWED_IMAGE_TYPES, true);
    $isAudio = in_array($extension, ALLOWED_AUDIO_TYPES, true);
    if ($isImage && stripos($mime, 'image/') !== 0) {
        return ['success' => false, 'error' => 'Tipe file bukan gambar.'];
    }
    if ($isAudio && stripos($mime, 'audio/') !== 0 && $mime !== 'application/ogg') {
        return ['success' => false, 'error' => 'Tipe file bukan audio.'];
    }
    if (!is_dir($destinationDir) && !@mkdir($destinationDir, 0755, true)) {
        return ['success' => false, 'error' => 'Gagal membuat direktori penyimpanan.'];
    }

    if ($isImage) {
        $result = process_image_to_webp($temporarySource, $destinationDir, $role, $preset, (string)$file['name']);
        if (empty($result['success'])) return $result;
        // The source is a PHP upload temporary file (or a CLI fixture). Remove
        // it only after the verified final WebP has been atomically installed.
        if (is_file($temporarySource) && $temporarySource !== $result['path']) @unlink($temporarySource);
        $result['extension'] = 'webp';
        $result['original_extension'] = $extension;
        return $result;
    }

    $safeName = generate_safe_filename((string)$file['name']);
    $dest = rtrim($destinationDir, '/\\') . '/' . $safeName;
    if (!@move_uploaded_file($temporarySource, $dest) && !($isCliFixture && @rename($temporarySource, $dest))) {
        return ['success' => false, 'error' => 'Gagal menyimpan file.'];
    }
    if (!is_file($dest) || filesize($dest) <= 0) {
        @unlink($dest);
        return ['success' => false, 'error' => 'File tersimpan tidak valid.'];
    }
    return ['success' => true, 'path' => $dest, 'name' => $safeName, 'extension' => $extension, 'mime' => $mime];
}

function public_path(string $path): string {
    $path = trim($path);
    // Empty optional media must not become `/`, which would request the page itself.
    if ($path === '') return 'data:,';
    return '/' . ltrim(str_replace('\\', '/', $path), '/');
}

function relative_path(string $path): string {
    $path = str_replace('\\', '/', $path);
    return ltrim(preg_replace('#^' . preg_quote(ROOT_DIR, '#') . '/#', '', $path), '/');
}

function get_gallery_items(array $config): array {
    ensure_upload_dirs();
    $items = [];
    $configured = [];
    foreach ($config['gallery']['items'] as $item) {
        if (empty($item['filename'])) {
            continue;
        }
        $configured[$item['filename']] = [
            'filename' => $item['filename'],
            'order' => isset($item['order']) ? (int)$item['order'] : 9999
        ];
    }
    $files = [];
    foreach (glob(UPLOADS_GALLERY_DIR . '/*') ?: [] as $filePath) {
        if (!is_file($filePath)) {
            continue;
        }
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_IMAGE_TYPES, true)) {
            continue;
        }
        $filename = relative_path($filePath);
        if (!isset($configured[$filename])) {
            continue;
        }
        $files[$filename] = $configured[$filename];
    }
    foreach ($configured as $item) {
        if (!isset($files[$item['filename']])) {
            continue;
        }
        $items[] = $item;
    }
    usort($items, function ($a, $b) {
        return $a['order'] <=> $b['order'];
    });
    return $items;
}

function delete_uploaded_asset(string $relativePath): bool {
    $normalized = trim((string) $relativePath);
    $normalized = str_replace('\\', '/', $normalized);
    $normalized = ltrim($normalized, '/');
    if ($normalized === '' || str_contains($normalized, '..')) {
        return false;
    }
    $fullPath = ROOT_DIR . '/' . $normalized;
    if (!is_file($fullPath)) {
        return false;
    }
    $allowedRoots = media_storage_roots();
    $realPath = realpath($fullPath);
    foreach ($allowedRoots as $root) {
        $realRoot = realpath($root);
        if ($realRoot !== false && $realPath !== false && str_starts_with($realPath, $realRoot . DIRECTORY_SEPARATOR)) {
            return @unlink($fullPath);
        }
    }
    return false;
}

function normalize_media_relative_path(string $relativePath): ?string {
    $normalized = trim((string) $relativePath);
    $normalized = str_replace('\\', '/', $normalized);
    $normalized = ltrim($normalized, '/');
    if ($normalized === '' || str_contains($normalized, '..')) {
        return null;
    }
    return $normalized;
}

function detect_media_usage(array $config, string $relativePath): array {
    $normalized = normalize_media_relative_path($relativePath);
    if ($normalized === null) {
        return [];
    }
    $usage = [];
    $checks = [
        'Cover' => $config['media']['cover'] ?? '',
        'Bride Photo' => $config['media']['bride_photo'] ?? '',
        'Groom Photo' => $config['media']['groom_photo'] ?? '',
        'Couple Photo' => $config['media']['couple_photo'] ?? '',
        'Hero Background' => $config['media']['background_hero'] ?? '',
        'Open Graph Image' => $config['site']['open_graph_image'] ?? '',
        'Music' => $config['media']['music'] ?? '',
        'QR Gift' => $config['gift']['qris_image'] ?? '',
    ];
    foreach ($checks as $label => $value) {
        if ($value !== '' && normalize_media_relative_path($value) === $normalized) {
            $usage[] = $label;
        }
    }
    foreach (($config['media']['background_sections'] ?? []) as $index => $value) {
        if ($value !== '' && normalize_media_relative_path($value) === $normalized) {
            $usage[] = 'Background #' . ($index + 1);
        }
    }
    foreach (($config['gallery']['items'] ?? []) as $item) {
        $filename = (string)($item['filename'] ?? '');
        if ($filename !== '' && normalize_media_relative_path($filename) === $normalized) {
            $usage[] = 'Gallery';
        }
    }
    foreach (($config['love_story']['items'] ?? []) as $item) {
        $image = (string)($item['image'] ?? '');
        if ($image !== '' && (media_reference_matches($image, $normalized) || basename($image) === basename($normalized))) {
            $usage[] = 'Love Story';
        }
    }
    foreach (($config['theme_visuals'] ?? []) as $presetKey => $visualOverrides) {
        foreach ((array)$visualOverrides as $visualKey => $visualValue) {
            if ((string)$visualValue !== '' && normalize_media_relative_path((string)$visualValue) === $normalized) {
                $usage[] = 'Visual ' . ucfirst((string)$presetKey) . ' / ' . str_replace('_', ' ', (string)$visualKey);
            }
        }
    }
    foreach (($config['theme_options'] ?? []) as $presetKey => $options) {
        foreach ((array)$options as $optionKey => $optionValue) {
            if ((string)$optionValue !== '' && normalize_media_relative_path((string)$optionValue) === $normalized) {
                $usage[] = 'Theme Asset ' . ucfirst((string)$presetKey) . ' / ' . str_replace('_', ' ', (string)$optionKey);
            }
        }
    }
    $usage = array_values(array_unique($usage));
    return $usage;
}

function list_media_library(array $options = []): array {
    $search = strtolower(trim((string)($options['search'] ?? '')));
    $typeFilter = strtolower(trim((string)($options['type'] ?? 'all')));
    $groupFilter = strtolower(trim((string)($options['group'] ?? 'all')));
    $config = load_config();
    $groups = [
        'cover' => ['dir' => UPLOADS_COVER_DIR, 'label' => 'Cover', 'allowed' => ALLOWED_IMAGE_TYPES],
        'background' => ['dir' => UPLOADS_BACKGROUND_DIR, 'label' => 'Background', 'allowed' => ALLOWED_IMAGE_TYPES],
        'gallery' => ['dir' => UPLOADS_GALLERY_DIR, 'label' => 'Gallery', 'allowed' => ALLOWED_IMAGE_TYPES],
        'love_story' => ['dir' => UPLOADS_LOVE_STORY_DIR, 'label' => 'Love Story', 'allowed' => ALLOWED_IMAGE_TYPES],
        'theme_assets' => ['dir' => UPLOADS_THEME_ASSETS_DIR, 'label' => 'Theme Assets', 'allowed' => ALLOWED_IMAGE_TYPES],
        'music' => ['dir' => UPLOADS_MUSIC_DIR, 'label' => 'Music', 'allowed' => ALLOWED_AUDIO_TYPES],
    ];

    $items = [];
    foreach ($groups as $groupKey => $group) {
        if (!is_dir($group['dir']) || ($groupFilter !== 'all' && $groupKey !== $groupFilter)) {
            continue;
        }
        $files = glob($group['dir'] . '/*') ?: [];
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($files as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $isAudio = in_array($ext, ALLOWED_AUDIO_TYPES, true);
            if (!in_array($ext, $group['allowed'], true) || (!$isAudio && $ext !== 'webp')) {
                continue;
            }
            $path = relative_path($filePath);
            $name = basename($filePath);
            if ($search !== '' && stripos($name, $search) === false && stripos($path, $search) === false) {
                continue;
            }
            $mediaType = in_array($ext, ALLOWED_AUDIO_TYPES, true) ? 'audio' : 'image';
            if ($typeFilter !== 'all' && $typeFilter !== $mediaType) {
                continue;
            }
            $usage = detect_media_usage($config, $path);
            $dimensions = null;
            if ($mediaType === 'image') {
                $imageInfo = @getimagesize($filePath);
                if (is_array($imageInfo)) {
                    $dimensions = $imageInfo[0] . ' × ' . $imageInfo[1];
                }
            }
            $items[] = [
                'group' => $groupKey,
                'label' => $group['label'],
                'type' => $mediaType,
                'path' => $path,
                'name' => $name,
                'size' => filesize($filePath),
                'mime' => safe_image_mime($filePath) ?: mime_content_type($filePath),
                'dimensions' => $dimensions,
                'created_at' => filemtime($filePath),
                'used_by' => $usage,
                'is_used' => !empty($usage),
                'status' => empty($usage) ? 'Unused' : 'Used',
            ];
        }
    }

    usort($items, function (array $a, array $b): int {
        $groupOrder = ['cover' => 1, 'background' => 2, 'gallery' => 3, 'love_story' => 4, 'theme_assets' => 5, 'music' => 6];
        $groupDiff = ($groupOrder[$a['group']] ?? 99) <=> ($groupOrder[$b['group']] ?? 99);
        return $groupDiff !== 0 ? $groupDiff : strcmp($a['name'], $b['name']);
    });

    return $items;
}

function rename_uploaded_asset(string $relativePath, string $newName): array {
    $normalized = normalize_media_relative_path($relativePath);
    if ($normalized === null) {
        return ['success' => false, 'error' => 'Path media tidak valid.'];
    }
    $fullPath = ROOT_DIR . '/' . $normalized;
    if (!is_file($fullPath) || !media_path_is_safe_storage($normalized)) {
        return ['success' => false, 'error' => 'File media tidak ditemukan.'];
    }
    $safeName = trim((string)$newName);
    if ($safeName === '') {
        return ['success' => false, 'error' => 'Nama file baru wajib diisi.'];
    }
    $originalExt = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $newBaseName = preg_replace('/[^A-Za-z0-9_.-]+/', '-', $safeName);
    $newBaseName = trim($newBaseName, '.-_ ');
    if ($newBaseName === '') {
        return ['success' => false, 'error' => 'Nama file baru tidak valid.'];
    }
    if (!str_ends_with(strtolower($newBaseName), '.' . $originalExt) && $originalExt !== '') {
        $newBaseName .= '.' . $originalExt;
    }
    $targetDir = dirname($fullPath);
    $targetPath = $targetDir . '/' . $newBaseName;
    if ($targetPath === $fullPath) {
        return ['success' => true, 'path' => $normalized];
    }
    if (file_exists($targetPath)) {
        return ['success' => false, 'error' => 'Nama file sudah dipakai.'];
    }
    if (!@rename($fullPath, $targetPath)) {
        return ['success' => false, 'error' => 'Gagal mengganti nama file.'];
    }
    return ['success' => true, 'path' => relative_path($targetPath)];
}

function replace_uploaded_asset(string $relativePath, array $file, ?string $role = null, ?string $preset = null): array {
    $normalized = normalize_media_relative_path($relativePath);
    if ($normalized === null || !media_path_is_safe_storage($normalized)) {
        return ['success' => false, 'error' => 'Path media tidak valid.'];
    }
    $fullPath = ROOT_DIR . '/' . $normalized;
    if (!is_file($fullPath)) return ['success' => false, 'error' => 'File media tidak ditemukan.'];

    $directory = dirname($fullPath);
    $basename = str_replace('\\', '/', $normalized);
    if ($role === null) {
        if (str_contains($basename, 'uploads/gallery/')) $role = 'gallery';
        elseif (str_contains($basename, 'uploads/background/')) $role = 'background';
        elseif (str_contains($basename, 'uploads/love-story/')) $role = 'story';
        elseif (str_contains($basename, 'uploads/theme-assets/')) $role = 'theme_asset';
        elseif (str_contains($basename, 'uploads/music/')) $role = 'music';
        elseif (str_contains($basename, 'uploads/cover/')) $role = 'cover';
        else $role = 'generic';
    }
    if ($preset === null && str_contains($basename, 'uploads/theme-assets/')) {
        $parts = explode('/', $basename);
        $preset = $parts[2] ?? null;
    }
    $isMusic = media_role_alias($role) === 'music';
    $allowed = $isMusic ? ALLOWED_AUDIO_TYPES : ALLOWED_IMAGE_TYPES;
    $maxSize = $isMusic ? MAX_MUSIC_UPLOAD_SIZE : MAX_UPLOAD_SIZE;
    $result = upload_file($file, $directory, $allowed, $maxSize, $role, $preset);
    if (empty($result['success'])) return ['success' => false, 'error' => $result['error'] ?? 'Gagal memproses file pengganti.'];
    $newPath = relative_path($result['path']);
    return ['success' => true, 'path' => $newPath, 'old_path' => $normalized, 'mime' => $result['mime'] ?? '', 'width' => $result['width'] ?? null, 'height' => $result['height'] ?? null];
}

function media_reference_matches(string $value, string $target): bool {
    $value = trim($value);
    $target = trim($target);
    if ($value === '' || $target === '') return false;
    $normalizedValue = normalize_media_relative_path($value);
    $normalizedTarget = normalize_media_relative_path($target);
    if ($normalizedValue !== null && $normalizedTarget !== null && $normalizedValue === $normalizedTarget) return true;
    return basename($value) === basename($target) && str_contains(str_replace('\\', '/', $value), dirname(str_replace('\\', '/', $target)));
}

function replace_media_references(array &$config, string $oldPath, string $newPath): void {
    $replace = static function (&$value) use ($oldPath, $newPath): void {
        if (is_string($value) && media_reference_matches($value, $oldPath)) $value = $newPath;
    };
    foreach (['cover', 'bride_photo', 'groom_photo', 'couple_photo', 'music', 'background_hero'] as $key) {
        if (isset($config['media'][$key])) $replace($config['media'][$key]);
    }
    foreach (($config['media']['background_sections'] ?? []) as $index => &$value) $replace($value);
    unset($value);
    if (isset($config['gift']['qris_image'])) $replace($config['gift']['qris_image']);
    if (isset($config['site']['open_graph_image'])) $replace($config['site']['open_graph_image']);
    foreach (($config['gallery']['items'] ?? []) as &$item) {
        if (isset($item['filename'])) $replace($item['filename']);
    }
    unset($item);
    foreach (($config['love_story']['items'] ?? []) as &$item) {
        if (isset($item['image'])) $replace($item['image']);
    }
    unset($item);
    foreach (($config['theme_visuals'] ?? []) as &$visuals) {
        foreach ((array)$visuals as &$value) $replace($value);
        unset($value);
    }
    unset($visuals);
    foreach (($config['theme_options'] ?? []) as &$options) {
        foreach ((array)$options as &$value) $replace($value);
        unset($value);
    }
    unset($options);
}

function cleanup_replaced_media(string $oldPath, array $config): bool {
    $normalized = normalize_media_relative_path($oldPath);
    if ($normalized === null || !media_path_is_safe_storage($normalized)) return false;
    if (!empty(detect_media_usage($config, $normalized))) return false;
    return delete_uploaded_asset($normalized);
}

function write_event_ics(array $config): void {
    $schedule = $config['schedule'];
    $location = $config['location'];
    $title = $config['wedding']['title'] ?: ($config['wedding']['bride_name'] . ' & ' . $config['wedding']['groom_name']);
    $description = $config['wedding']['opening_text'] ?: $config['site']['description'];
    $start = date_create_from_format('Y-m-d H:i', $schedule['akad_date'] . ' ' . $schedule['akad_time'], new DateTimeZone($schedule['timezone']));
    $end = clone $start;
    if ($end) {
        $end->modify('+2 hours');
    }
    $dtstart = $start ? $start->format('Ymd\THis') : '20261229T090000';
    $dtend = $end ? $end->format('Ymd\THis') : '20261229T110000';
    $tz = $schedule['timezone'] ?: 'Asia/Jakarta';
    $organizer = 'MAILTO:no-reply@example.com';
    $locationText = $location['venue'] ?: $location['address'];
    $ics = implode("\r\n", [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Undangan CMS//EN',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'DTSTAMP:' . gmdate('Ymd\THis') . 'Z',
        'DTSTART;TZID=' . $tz . ':' . $dtstart,
        'DTEND;TZID=' . $tz . ':' . $dtend,
        'SUMMARY:' . escape_ics_value($title),
        'DESCRIPTION:' . escape_ics_value($description),
        'LOCATION:' . escape_ics_value($locationText),
        'ORGANIZER:' . $organizer,
        'END:VEVENT',
        'END:VCALENDAR'
    ]);
    @file_put_contents(EVENT_ICS_FILE, $ics, LOCK_EX);
}

function escape_ics_value(string $value): string {
    return str_replace(["\\", '\n', ';', ','], ['\\\\', '\\n', '\\;', '\\,'], $value);
}

function build_whatsapp_link(array $config): string {
    $phone = preg_replace('/[^0-9+]/', '', $config['whatsapp']['phone'] ?? '');
    $message = $config['whatsapp']['message'] ?? '';
    if ($phone === '') {
        return '#';
    }
    $phone = preg_replace('/^\+/', '', $phone);
    return 'https://wa.me/' . rawurlencode($phone) . '?text=' . rawurlencode($message);
}

function build_google_calendar_link(array $config): string {
    return $config['schedule']['google_calendar_link'] ?: '';
}

// Minimal exception handler to avoid leaking internals
set_exception_handler(function ($e) {
    error_log('Unhandled exception: ' . $e->getMessage());
    http_response_code(500);
    if (php_sapi_name() !== 'cli') {
        $headers = headers_list();
        $isJson = false;
        foreach ($headers as $h) {
            if (stripos($h, 'Content-Type:') === 0 && stripos($h, 'application/json') !== false) {
                $isJson = true;
                break;
            }
        }
        if ($isJson) {
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        } else {
            echo 'Internal server error';
        }
    }
    exit;
});
?>
