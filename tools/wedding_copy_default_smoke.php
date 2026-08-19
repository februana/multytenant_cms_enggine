<?php
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';
ob_start();

$failures = 0;
$assert = static function (bool $condition, string $message) use (&$failures): void {
    echo ($condition ? 'PASS: ' : 'FAIL: ') . $message . PHP_EOL;
    if (!$condition) $failures++;
};
$defaults = config_defaults();
$wedding = $defaults['wedding'];
$assert(($wedding['bride_name'] ?? '') === 'FEBRUANA', 'default bride name is FEBRUANA');
$assert(($wedding['groom_name'] ?? '') === 'ANDI MUHAMAD BASUKI', 'default groom name is ANDI MUHAMAD BASUKI');
$assert(($wedding['bride_nickname'] ?? '') === 'Febru', 'default bride nickname is Febru');
$assert(($wedding['groom_nickname'] ?? '') === 'Andi', 'default groom nickname is Andi');
$assert(str_contains((string)$wedding['quote'], 'وَمِنْ اٰيٰتِهٖٓ'), 'default quote includes Arabic Ar-Rum 21');
$assert(str_contains((string)$wedding['quote'], 'QS. Ar-Rum: 21'), 'default quote includes Indonesian reference');
$assert(str_contains((string)$wedding['opening_text'], 'rahmat dan ridha Allah SWT'), 'default opening uses Indonesian Islamic invitation wording');
$assert(str_contains((string)$wedding['closing_text'], 'hadiah terindah'), 'default closing includes doa restu sentiment');
$defaultCalendar = rawurldecode(build_google_calendar_link($defaults));
$assert(str_contains($defaultCalendar, 'Febru & Andi') && str_contains($defaultCalendar, 'rahmat dan ridha'), 'default calendar metadata follows new title and opening');
$calendarOverride = $defaults;
$calendarOverride['wedding']['title'] = 'Undangan Sari & Bima';
$calendarOverride['wedding']['opening_text'] = 'Opening custom untuk Sari dan Bima.';
$overrideCalendar = rawurldecode(build_google_calendar_link($calendarOverride));
$assert(str_contains($overrideCalendar, 'Sari & Bima') && str_contains($overrideCalendar, 'Opening custom'), 'calendar metadata follows admin wedding override');
$arabicGreeting = "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ";
foreach (['dewankl', 'shubh-vivah', 'yami-buzzy', 'rainier', 'archak', 'parang', 'custom'] as $preset) {
    $greeting = theme_opening_greeting($defaults, $preset);
    $assert(str_contains($greeting, $arabicGreeting) && str_contains($greeting, 'Assalamu’alaikum'), "$preset default greeting includes Arabic and salam");
}
$assert(theme_opening_greeting($defaults, 'pawiwahan') === 'OM Swastiastu', 'Pawiwahan preserves source greeting fallback');
foreach (['bride_name', 'groom_name', 'title', 'opening_text', 'closing_text', 'quote', 'bride_nickname', 'groom_nickname'] as $key) {
    $assert(preserve_text_input('', (string)$wedding[$key]) === (string)$wedding[$key], "empty admin input restores default wedding.$key");
}
$adminSource = (string)file_get_contents(dirname(__DIR__) . '/admin/index.php');
$assert(str_contains($adminSource, '$defaultWedding = config_defaults()[\'wedding\'];'), 'admin save_wedding uses config default fallback');
$active = json_decode((string)file_get_contents(CONFIG_FILE), true);
$assert(is_array($active) && ($active['wedding']['bride_name'] ?? '') === 'FEBRUANA', 'active config uses default bride name');
$assert(is_array($active) && ($active['wedding']['groom_name'] ?? '') === 'ANDI MUHAMAD BASUKI', 'active config uses default groom name');
$assert(is_array($active) && str_contains((string)($active['wedding']['quote'] ?? ''), 'وَمِنْ اٰيٰتِهٖٓ'), 'active config stores Arabic quote');
foreach (theme_builtin_preset_keys() as $preset) {
    $config = $defaults;
    $config['theme']['mode'] = 'preset';
    $config['theme']['theme_preset'] = $preset;
    $shared = ['presetKey' => $preset, 'heroText' => $wedding['opening_text'], 'guestFallback' => 'Bapak/Ibu/Saudara/i', 'countdownTarget' => $config['schedule']['countdown_target'], 'calendarLink' => build_google_calendar_link($config), 'calendarDownloadName' => 'Undangan', 'whatsappLink' => build_whatsapp_link($config), 'musicSrc' => '', 'bgHero' => '', 'sectionStyles' => ['', '', ''], 'brideParents' => '', 'groomParents' => '', 'siteTitle' => $config['site']['title'], 'weddingTitle' => $wedding['title']];
    $html = render_theme_layout($config, $shared);
    $assert(str_contains($html, 'FEBRUANA') && str_contains($html, 'ANDI MUHAMAD BASUKI'), "$preset renders official couple names");
    $assert(str_contains($html, 'وَمِنْ') || str_contains($html, 'Assalamu') || str_contains($html, 'OM Swastiastu'), "$preset renders Arabic or localized greeting content");
}
if ($failures > 0) {
    echo "FAIL: wedding copy default smoke test ($failures failures)" . PHP_EOL;
    ob_end_flush();
    exit(1);
}
echo 'PASS: wedding copy default smoke test' . PHP_EOL;
ob_end_flush();
