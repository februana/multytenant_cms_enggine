<?php
declare(strict_types=1);
ob_start();

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function pawiwahan_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    $GLOBALS['pawiwahan_passes'][] = $message;
}
$GLOBALS['pawiwahan_passes'] = [];

$root = dirname(__DIR__);
$config = config_defaults();
$config['theme']['mode'] = 'preset';
$config['theme']['theme_preset'] = 'pawiwahan';
$config['wedding']['bride_name'] = 'Ayu';
$config['wedding']['groom_name'] = 'Bagus';
$config['wedding']['opening_text'] = "English opening\n你好 / 안녕하세요 / مرحباً";
$config['theme_options']['pawiwahan']['opening_greeting'] = "OM Swastiastu\nSalam Pawiwahan";
$config['parents']['bride_father'] = 'Bapak Suryanto';
$config['parents']['bride_mother'] = 'Ibu Sari';
$config['parents']['groom_father'] = 'Bapak Wayan';
$config['parents']['groom_mother'] = 'Ibu Made';
$config['schedule']['countdown_target'] = '2030-09-09T10:00:00+07:00';
$config['theme_visuals']['pawiwahan']['hero_background'] = 'uploads/background/pawiwahan-hero.webp';

$contract = theme_contract_for('pawiwahan');
pawiwahan_assert(($contract['source_revision'] ?? '') === '957b3f3', 'contract records the audited Pawiwahan source revision');
pawiwahan_assert(in_array('messages', $contract['data_capabilities'] ?? [], true), 'contract declares messages capability');
pawiwahan_assert(!in_array('story', $contract['admin_capabilities'] ?? [], true), 'contract does not invent a Story admin panel');
pawiwahan_assert(theme_builtin_preset_keys() === ['dewankl', 'rainier', 'archak', 'parang', 'pawiwahan', 'shubh-vivah', 'yami-buzzy'], 'Pawiwahan is a renderer-backed built-in preset');
pawiwahan_assert(is_file($root . '/themes/pawiwahan/original/index.html'), 'original Pawiwahan HTML is retained for provenance');
pawiwahan_assert(is_file($root . '/themes/pawiwahan/assets/css/pawiwahan.css'), 'original Pawiwahan CSS is retained');
pawiwahan_assert(is_file($root . '/themes/pawiwahan/assets/js/pawiwahan-source.js'), 'original Pawiwahan JavaScript is retained');
pawiwahan_assert(is_file($root . '/themes/pawiwahan/assets/images/ornam/Asset5.png'), 'source ornament is locally available');

$_GET['to'] = 'I Gede & Kumala';
$shared = [
    'presetKey' => 'pawiwahan',
    'heroText' => $config['wedding']['opening_text'],
    'guestFallback' => 'Bapak/Ibu/Saudara/i',
    'countdownTarget' => $config['schedule']['countdown_target'],
    'calendarLink' => build_google_calendar_link($config),
    'calendarDownloadName' => 'Undangan',
    'whatsappLink' => build_whatsapp_link($config),
    'musicSrc' => '',
    'bgHero' => '',
    'sectionStyles' => ['', '', ''],
    'brideParents' => '',
    'groomParents' => '',
    'siteTitle' => 'Pawiwahan Smoke',
    'weddingTitle' => 'Ayu & Bagus',
];
$html = render_theme_layout($config, $shared);
unset($_GET['to']);
pawiwahan_assert(str_contains($html, 'Ayu') && str_contains($html, 'Bagus'), 'CMS wedding names reach Pawiwahan source boundaries');
pawiwahan_assert(str_contains($html, 'I Gede &amp; Kumala'), 'global guest resolver reaches the welcome modal safely');
pawiwahan_assert(str_contains($html, 'English opening') && str_contains($html, '你好'), 'multilingual opening text remains preserved');
pawiwahan_assert(str_contains($html, 'OM Swastiastu') && str_contains($html, 'Salam Pawiwahan'), 'admin-configured Pawiwahan greeting preserves Unicode and line breaks');
pawiwahan_assert(str_contains($html, 'id="pawiwahan-gift-trigger"') && str_contains($html, 'id="pawiwahanGiftModal"'), 'Pawiwahan Angpau trigger and modal use stable CMS IDs');
pawiwahan_assert(str_contains($html, 'data-copy=') && str_contains($html, 'id="pawiwahanGiftCopyStatus"'), 'Pawiwahan account copy control remains connected to status feedback');
pawiwahan_assert(str_contains($html, 'uploads/background/pawiwahan-hero.webp'), 'CMS visual hero override wins over source fallback');
pawiwahan_assert(str_contains($html, 'id="carouselExampleCaptions"') && str_contains($html, 'id="welcomeModal"'), 'source carousel and welcome modal boundaries remain intact');
pawiwahan_assert(str_contains($html, 'id="hitungmundur"') && str_contains($html, 'data-pawiwahan-countdown-target'), 'countdown data bridge reaches the source lifecycle');
$adapterScript = file_get_contents($root . '/themes/pawiwahan/script.js');
pawiwahan_assert(is_string($adapterScript) && str_contains($adapterScript, 'initGiftModal') && str_contains($adapterScript, 'setGiftModalFallback'), 'Pawiwahan Angpau has Bootstrap and native modal fallback wiring');
pawiwahan_assert(str_contains($html, 'id="pawiwahan-rsvp-form"') && is_string($adapterScript) && str_contains($adapterScript, "fetch('save.php'"), 'RSVP uses the existing CMS backend');
pawiwahan_assert(theme_opening_greeting($config, 'pawiwahan') === "OM Swastiastu\nSalam Pawiwahan", 'opening greeting resolver returns the saved Pawiwahan value');
pawiwahan_assert(!str_contains($html, 'firebase') && !str_contains($html, 'VITE_API_URL') && !str_contains($html, 'axios'), 'Pawiwahan adapter does not import source Firebase/Vue backend coupling');
pawiwahan_assert(!str_contains($html, 'CalumScott') && !str_contains($html, 'firebasestorage.googleapis.com'), 'demo music and Firebase sample media are not rendered as clean defaults');
pawiwahan_assert(str_contains($html, 'href="https://github.com/parta99/pawiwahan"'), 'source attribution remains in the rendered footer');

$sourceHtml = file_get_contents($root . '/themes/pawiwahan/original/index.html');
pawiwahan_assert(is_string($sourceHtml) && str_contains($sourceHtml, 'carouselExampleControls'), 'retained source HTML preserves original gallery carousel markup');

foreach ($GLOBALS['pawiwahan_passes'] as $message) echo 'PASS: ' . $message . PHP_EOL;
echo "PASS: Pawiwahan focused smoke test\n";
ob_end_flush();
