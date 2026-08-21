<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

ob_start();

function semantic_assert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function semantic_contains(string $html, string $needle, string $context): void {
    semantic_assert(strpos($html, $needle) !== false, "Missing {$needle} in {$context}");
}

$base = load_config();
$base['wedding']['bride_name'] = 'FEBRUANA';
$base['wedding']['groom_name'] = 'ANDI MUHAMAD BASUKI';
$base['wedding']['bride_nickname'] = 'Febru';
$base['wedding']['groom_nickname'] = 'Andi';

$semantic = theme_semantic_names($base);
semantic_assert($semantic['bride_full_name'] === 'FEBRUANA', 'Bride full-name mapping failed');
semantic_assert($semantic['groom_full_name'] === 'ANDI MUHAMAD BASUKI', 'Groom full-name mapping failed');
semantic_assert($semantic['bride_nickname'] === 'Febru', 'Bride nickname mapping failed');
semantic_assert($semantic['groom_nickname'] === 'Andi', 'Groom nickname mapping failed');

$missingNickname = $base;
$missingNickname['wedding']['bride_nickname'] = '';
$missingNickname['wedding']['groom_nickname'] = '';
$fallback = theme_semantic_names($missingNickname);
semantic_assert($fallback['bride_nickname'] === 'FEBRUANA', 'Empty bride nickname must fall back to full name');
semantic_assert($fallback['groom_nickname'] === 'ANDI MUHAMAD BASUKI', 'Empty groom nickname must fall back to full name');

$missingFullName = $base;
$missingFullName['wedding']['bride_name'] = '';
$missingFullName['wedding']['groom_name'] = '';
$fallbackFull = theme_semantic_names($missingFullName);
semantic_assert($fallbackFull['bride_full_name'] === 'Febru', 'Empty bride full name must fall back to nickname');
semantic_assert($fallbackFull['groom_full_name'] === 'Andi', 'Empty groom full name must fall back to nickname');

$baselineConfigJson = json_encode($base, JSON_THROW_ON_ERROR);

$shared = [
    'presetKey' => 'dewankl',
    'heroText' => $base['wedding']['opening_text'] ?? '',
    'guestFallback' => 'Bapak/Ibu/Saudara/i',
    'countdownTarget' => $base['schedule']['countdown_target'] ?? '',
    'calendarLink' => build_google_calendar_link($base),
    'calendarDownloadName' => 'Undangan',
    'whatsappLink' => build_whatsapp_link($base),
    'musicSrc' => $base['media']['music'] ?? '',
    'bgHero' => '',
    'sectionStyles' => [],
    'brideParents' => '',
    'groomParents' => '',
];

$heroMarkers = [
    'custom' => 'class="brand"',
    'dewankl' => 'id="welcome"',
    'shubh-vivah' => 'id="shubh-title"',
    'yami-buzzy' => 'id="yami-welcome-title"',
    'rainier' => 'id="event-title"',
    'archak' => 'class="home hz-margin"',
    'parang' => 'id="parang-hero-title"',
    'pawiwahan' => 'id="welcomeModal"',
];

foreach ($heroMarkers as $preset => $heroMarker) {
    $config = $base;
    $config['theme']['mode'] = $preset === 'custom' ? 'custom' : 'preset';
    $config['theme']['theme_preset'] = $preset;
    $shared['presetKey'] = $preset;
    $html = render_theme_layout($config, $shared);
    semantic_contains($html, $heroMarker, $preset);
    $heroPosition = strpos($html, $heroMarker);
    $nicknamePosition = strpos($html, 'Febru', $heroPosition);
    semantic_assert($nicknamePosition !== false && $nicknamePosition >= $heroPosition, "Nickname is not rendered in hero/welcome for {$preset}");
    semantic_contains($html, 'FEBRUANA', $preset);
    semantic_contains($html, 'ANDI MUHAMAD BASUKI', $preset);
    echo "PASS: {$preset} nickname hero/welcome + full-name formal output\n";
}

semantic_assert(json_encode($base, JSON_THROW_ON_ERROR) === $baselineConfigJson, 'Rendering must not mutate tenant config/schema');
echo "PASS: semantic name mapping fallback, schema immutability, and all preset render contracts\n";
ob_end_flush();
