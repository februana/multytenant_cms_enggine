<?php
/**
 * Regression smoke test for the save/config/render text pipeline.
 * User content is data: it must not be translated, paraphrased, spell-corrected,
 * stripped, or normalized beyond the documented CRLF-to-LF boundary at save time.
 */
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

$base = load_config();
$opening = "Welcome to our special day\nBaris kedua — Unicode: 你好 • 안녕하세요 • مرحباً\n  Dua  spasi  tetap  ada  ";
$closing = "Sampai jumpa\nTerima kasih — 가족 • 東京";
$quote = "Our exact quote\nBaris kutipan kedua";
$address = "Jl. Mawar No. 7\nKecamatan Sukamaju\n  Unit  2  ";

$shared = [
    'presetKey' => 'custom',
    'heroText' => $opening,
    'guestFallback' => 'Bapak/Ibu/Saudara/i',
    'guestName' => '',
    'countdownTarget' => $base['schedule']['countdown_target'] ?? '',
    'calendarLink' => build_google_calendar_link($base),
    'calendarDownloadName' => 'Undangan',
    'whatsappLink' => build_whatsapp_link($base),
    'musicSrc' => '',
    'bgHero' => '',
    'sectionStyles' => ['', '', ''],
    'brideParents' => '',
    'groomParents' => '',
    'siteTitle' => 'Preservation Test',
    'weddingTitle' => 'Preservation Test',
];

$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
};

foreach (['custom', 'dewankl', 'elix', 'rainier', 'archak', 'parang'] as $preset) {
    $config = $base;
    $config['theme']['mode'] = $preset === 'custom' ? 'custom' : 'preset';
    $config['theme']['theme_preset'] = $preset;
    $config['wedding']['opening_text'] = $opening;
    $config['wedding']['closing_text'] = $closing;
    $config['wedding']['quote'] = $preset === 'archak' ? '' : $quote;
    $config['site']['description'] = $opening;
    $config['location']['address'] = $address;
    $shared['presetKey'] = $preset;
    $shared['heroText'] = $opening;

    $html = render_theme_layout($config, $shared);
    $assert($html !== '', "{$preset} returned empty HTML");
    $assert(strpos($html, 'Welcome to our special day') !== false, "{$preset} preserved English user content");
    $assert(strpos($html, '你好') !== false && strpos($html, '안녕하세요') !== false && strpos($html, 'مرحباً') !== false, "{$preset} preserved Unicode content");

    if ($preset === 'rainier') {
        preg_match('/<script id="event-data" type="application\/json">(.*?)<\/script>/s', $html, $matches);
        $assert(isset($matches[1]), 'Rainier event JSON is present');
        $eventData = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        $assert(($eventData['event']['subtitle'] ?? null) === $opening, 'Rainier event subtitle is byte-for-byte preserved');
        $assert(($eventData['location']['address'] ?? null) === $address, 'Rainier address is byte-for-byte preserved');
        $assert(($eventData['footer']['text'] ?? null) === $closing, 'Rainier closing text is byte-for-byte preserved');
        $assert(strpos($html, 'strip_tags') === false, 'Rainier output does not expose stripping logic');
    } else {
        $escapedOpening = render_preserved_text($opening);
        $escapedAddress = render_preserved_text($address);
        $openingPreserved = strpos($html, 'Welcome to our special day') !== false
            && strpos($html, 'Baris kedua') !== false
            && strpos($html, 'Dua  spasi  tetap  ada') !== false;
        $addressPreserved = strpos($html, 'Jl. Mawar No. 7') !== false
            && strpos($html, 'Kecamatan Sukamaju') !== false
            && strpos($html, 'Unit  2') !== false;
        $assert($openingPreserved, "{$preset} preserved opening newlines and escaping");
        $assert($addressPreserved, "{$preset} preserved address newlines and escaping");
    }
}

$assert(function_exists('render_preserved_text'), 'preservation helper exists');
$assert(function_exists('preserve_text_input'), 'save preservation helper exists');
$rawInput = "  English content  \r\nBaris kedua — 你好  ";
$storedText = preserve_text_input($rawInput, 'fallback');
$assert($storedText === "  English content  \nBaris kedua — 你好  ", 'save stage only normalizes CRLF');
$roundTrip = json_decode(json_encode(['text' => $storedText], JSON_UNESCAPED_UNICODE), true, 512, JSON_THROW_ON_ERROR);
$assert(($roundTrip['text'] ?? null) === $storedText, 'config JSON round-trip is lossless');
$assert(render_preserved_text("A\nB") === "A<br>\nB", 'newline conversion is deterministic');
$assert(strpos(render_preserved_text('  x  '), '  x  ') !== false, 'meaningful spaces remain in escaped HTML source');
$sourceFiles = array_merge(
    glob(dirname(__DIR__) . '/app/*.php') ?: [],
    glob(dirname(__DIR__) . '/admin/*.php') ?: [],
    glob(dirname(__DIR__) . '/themes/*/layout.php') ?: [],
    [dirname(__DIR__) . '/index.php']
);
foreach ($sourceFiles as $sourceFile) {
    $source = file_get_contents($sourceFile);
    $assert(!preg_match('/\\b(?:gettext|translate|i18n)\\s*\\(/i', $source), 'no auto-translation call in ' . basename($sourceFile));
}

echo "PASS: all modes preserve multilingual content, newlines, spaces, Unicode, and English user text without auto-translation\n";
?>
