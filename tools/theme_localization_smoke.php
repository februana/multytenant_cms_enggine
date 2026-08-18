<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$read = static function (string $relative) use ($root): string {
    $path = $root . '/' . ltrim($relative, '/');
    $content = file_get_contents($path);
    if ($content === false) throw new RuntimeException("Unable to read {$relative}");
    return $content;
};

$expected = [
    'themes/dewankl/layout.php' => [
        'Simpan ke Google Kalender', 'Gulir ke Bawah', 'Hadiah Pernikahan', 'Beranda', 'Memulai aplikasi...',
    ],
    'themes/elix/layout.php' => [
        'Beranda', 'Informasi', 'Kisah', 'Galeri', 'Hadiah', 'Hak Cipta Dilindungi.', 'Hari', 'Jam', 'Menit', 'Detik',
    ],
    'themes/rainier/layout.php' => [
        'Acara dimulai dalam', 'Tambahkan ke Google Kalender', 'Detail Acara', 'Jadwal', 'Kata-Kata Inspirasi',
        'Mohon konfirmasi kehadiran Anda', 'Diselenggarakan oleh', 'Templat sumber',
    ],
    'themes/rainier/original/invite-1-adapter.js' => [
        'Nama Anda', 'Kehadiran', 'Akan Hadir', 'Tidak Dapat Hadir', 'Kirim Konfirmasi Kehadiran',
        'Tidak dapat memuat detail acara', 'Putar Musik', 'Jeda Musik',
    ],
    'themes/archak/layout.php' => [
        'KISAH KAMI', 'PERJALANAN &amp; TEMPAT MENGINAP', 'JANJI', 'Kita Akan Menikah',
        'Merayakan Cinta Kami', 'Perjalanan &amp; Tempat Menginap', 'Sampai Jumpa!', 'Hubungi Kami',
    ],
    'themes/parang/layout.php' => [
        'Kepada Yth.', 'Pernikahan Kami', 'Mempelai', 'Acara Pernikahan', 'Cerita Kami',
        'Galeri', 'Lokasi Acara', 'Hadiah Pernikahan', 'Kirim Konfirmasi Kehadiran', 'Manten Jawi',
    ],
    'app/theme-renderer.php' => [
        'Konfirmasi Kehadiran', 'Kirim Konfirmasi Kehadiran', 'Hemat data: matikan musik otomatis',
    ],
];

foreach ($expected as $relative => $needles) {
    $source = $read($relative);
    foreach ($needles as $needle) {
        $assert(strpos($source, $needle) !== false, "{$relative} contains Indonesian UI: {$needle}");
    }
}

$forbiddenVisibleEnglish = [
    'Event starts in', 'Add to Google Calendar', 'Event Details', 'Open in Google Maps',
    'Words of Inspiration', 'Please confirm your attendance', 'Your Name', 'Will Attend',
    'Cannot Attend', 'Send RSVP', 'OUR STORY', 'TRAVEL &amp; STAY', 'PROMISES',
    'We\'re getting married', 'Travel and Stay', 'Hope to See You!', 'Save Google Calendar',
    'Scroll Down', 'The Wedding Of', 'Booting application...', 'All Rights Reserved.',
    'Follow on Instagram', 'Hosted by', 'Source template', 'Toggle Simple Mode',
    'Play Music', 'Pause Music', 'Unable to load event details', 'Please check back later.',
    '>RSVP</a>', '>Kirim RSVP</button>', 'gallery lazy load',
];

$activeGuestSources = [
    $read('themes/dewankl/layout.php'),
    $read('themes/dewankl/script.js'),
    $read('themes/elix/layout.php'),
    $read('themes/rainier/layout.php'),
    $read('themes/rainier/original/invite-1-adapter.js'),
    $read('themes/archak/layout.php'),
    $read('themes/parang/layout.php'),
    $read('app/theme-renderer.php'),
];
foreach ($forbiddenVisibleEnglish as $needle) {
    foreach ($activeGuestSources as $index => $source) {
        $assert(strpos($source, $needle) === false, "active guest source {$index} has untranslated visible text: {$needle}");
    }
}

$admin = $read('admin/index.php');
$contract = $read('app/theme-contract.php');
$assert(strpos($contract, "'preset_selector'") !== false, 'preset selector remains a global capability');
$assert(strpos($contract, "'guest_links'") !== false, 'guest links remains a global capability');
$assert(strpos($admin, "globalAdminCapabilityEnabled('preset_selector')") !== false, 'admin selector uses the global capability gate');
$assert(strpos($admin, 'id="preset-selector"') !== false, 'admin contains the global preset selector panel');
$assert(strpos($admin, 'name="action" value="save_preset"') !== false, 'admin selector keeps save_preset action');

if ($failures) {
    foreach ($failures as $failure) echo "FAIL: {$failure}\n";
    exit(1);
}

echo "PASS: Indonesian static guest UI, global preset selector, and source-template boundaries are covered\n";
