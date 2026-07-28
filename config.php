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
if (!defined('CONFIG_FILE')) define('CONFIG_FILE', ROOT_DIR . '/config.json');

// Security defaults
if (!defined('MAX_UPLOAD_SIZE')) define('MAX_UPLOAD_SIZE', (int) (getenv('MAX_UPLOAD_SIZE') ?: 5 * 1024 * 1024));
if (!defined('MAX_MUSIC_UPLOAD_SIZE')) define('MAX_MUSIC_UPLOAD_SIZE', (int) (getenv('MAX_MUSIC_UPLOAD_SIZE') ?: 15 * 1024 * 1024));
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
    $dbPath = ROOT_DIR . '/database.sqlite';
}
if (!defined('DB_PATH')) define('DB_PATH', $dbPath);
if (!defined('GUEST_LINKS_FILE')) define('GUEST_LINKS_FILE', ROOT_DIR . '/guest-links.json');

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
            'url' => 'https://example.com',
            'title' => 'Undangan Pernikahan Andi & Februana',
            'description' => 'Mohon doa restu dan kehadiran Bapak/Ibu/Saudara/i di hari spesial kami.',
            'keywords' => 'undangan pernikahan, wedding invitation, Andi, Februana',
            'open_graph_title' => 'Undangan Pernikahan Andi & Februana',
            'open_graph_description' => 'Mohon doa restu dan kehadiran Bapak/Ibu/Saudara/i di hari spesial kami.',
            'twitter_card' => 'summary_large_image',
            'open_graph_image' => 'uploads/cover/cover.jpg',
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
            'bride_name' => 'Andi',
            'groom_name' => 'Februana',
            'title' => 'Undangan Pernikahan Andi & Februana',
            'opening_text' => 'Mohon doa restu dan kehadiran Bapak/Ibu/Saudara/i di hari spesial kami.',
            'closing_text' => 'Kami sangat menghargai kehadiran dan doa restu Bapak/Ibu/Saudara/i agar hari ini menjadi lebih berkah.',
            'quote' => 'Dengan memohon rahmat Allah SWT, kami mengundang Anda untuk hadir pada hari istimewa kami.',
            'bride_nickname' => 'Andi',
            'groom_nickname' => 'Februana'
        ],
        'parents' => [
            'bride_father' => 'Ayah Andi',
            'bride_mother' => 'Ibu Andi',
            'groom_father' => 'Ayah Februana',
            'groom_mother' => 'Ibu Februana'
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
            'cover' => 'uploads/cover/cover.jpg',
            'music' => 'music/lagu.mp3',
            'background_hero' => '',
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
            'primary_color' => '#c84c47',
            'secondary_color' => '#f0c2a1',
            'accent_color' => '#f0c2a1',
            'background_color' => '#fff8f2',
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
            'footer_style' => 'centered'
        ],
        'sections' => [
            [
                'id' => 'hero',
                'title' => 'Hero',
                'subtitle' => '',
                'enabled' => true,
                'order' => 1
            ],
            [
                'id' => 'bride_groom',
                'title' => 'Bride & Groom',
                'subtitle' => 'Mempelai',
                'enabled' => true,
                'order' => 2
            ],
            [
                'id' => 'countdown',
                'title' => 'Countdown',
                'subtitle' => 'Menuju Hari Bahagia',
                'enabled' => true,
                'order' => 3
            ],
            [
                'id' => 'love_story',
                'title' => 'Love Story',
                'subtitle' => 'Cerita Kami',
                'enabled' => true,
                'order' => 4
            ],
            [
                'id' => 'gallery',
                'title' => 'Gallery',
                'subtitle' => 'Galeri Foto',
                'enabled' => true,
                'order' => 5
            ],
            [
                'id' => 'events',
                'title' => 'Events',
                'subtitle' => 'Acara',
                'enabled' => true,
                'order' => 6
            ],
            [
                'id' => 'location',
                'title' => 'Location',
                'subtitle' => 'Lokasi Acara',
                'enabled' => true,
                'order' => 7
            ],
            [
                'id' => 'rsvp',
                'title' => 'RSVP',
                'subtitle' => 'Konfirmasi Kehadiran',
                'enabled' => true,
                'order' => 8
            ],
            [
                'id' => 'gift',
                'title' => 'Gift',
                'subtitle' => 'Ucapan & Hadiah',
                'enabled' => true,
                'order' => 9
            ],
            [
                'id' => 'guest_wishes',
                'title' => 'Guest Wishes',
                'subtitle' => 'Ucapan Tamu',
                'enabled' => true,
                'order' => 10
            ],
            [
                'id' => 'footer',
                'title' => 'Footer',
                'subtitle' => '',
                'enabled' => true,
                'order' => 11
            ]
        ],
        'love_story' => [
            'items' => []
        ],
        'custom_css' => ''
    ];
}

function ensure_upload_dirs(): void {
    foreach ([UPLOADS_DIR, UPLOADS_COVER_DIR, UPLOADS_MUSIC_DIR, UPLOADS_GALLERY_DIR, UPLOADS_BACKGROUND_DIR] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}

function load_config(): array {
    ensure_upload_dirs();
    $defaults = config_defaults();
    if (!is_readable(CONFIG_FILE)) {
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
    }
    if (empty($config['schedule']['countdown_target'])) {
        $config['schedule']['countdown_target'] = compute_countdown_target($config['schedule']);
    }
    if (!is_file(ROOT_DIR . '/event.ics')) {
        write_event_ics($config);
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
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureFlag,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    if (session_status() !== PHP_SESSION_ACTIVE) {
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

function upload_file(array $file, string $destinationDir, array $allowedExtensions, int $maxSize): array {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Gagal mengunggah file.'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['error' => 'Sumber file tidak valid.'];
    }
    if ($file['size'] > $maxSize) {
        return ['error' => 'Ukuran file terlalu besar.'];
    }
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['error' => 'Format file tidak diizinkan.'];
    }
    $mime = safe_image_mime($file['tmp_name']);
    if ($mime === null) {
        return ['error' => 'Tipe file tidak dapat diverifikasi.'];
    }
    if (in_array($extension, ALLOWED_IMAGE_TYPES, true)) {
        if (stripos($mime, 'image/') !== 0) {
            return ['error' => 'Tipe file bukan gambar.'];
        }
    }
    if (in_array($extension, ALLOWED_AUDIO_TYPES, true)) {
        if (stripos($mime, 'audio/') !== 0) {
            return ['error' => 'Tipe file bukan audio.'];
        }
    }
    if (!is_dir($destinationDir) && !@mkdir($destinationDir, 0755, true)) {
        return ['error' => 'Gagal membuat direktori penyimpanan.'];
    }
    $safeName = generate_safe_filename($file['name']);
    $dest = $destinationDir . '/' . $safeName;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['error' => 'Gagal menyimpan file.'];
    }
    return ['path' => $dest, 'name' => $safeName, 'extension' => $extension, 'mime' => $mime];
}

function public_path(string $path): string {
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
            $configured[$filename] = ['filename' => $filename, 'order' => time() + rand(1, 1000)];
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
    @file_put_contents(ROOT_DIR . '/event.ics', $ics, LOCK_EX);
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
