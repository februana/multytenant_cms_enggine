<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function media_role_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$expectedRoles = [
    'custom' => ['cover', 'bride_photo', 'groom_photo', 'couple_photo'],
    'dewankl' => ['cover', 'bride_photo', 'groom_photo'],
    'shubh-vivah' => [],
    'yami-buzzy' => ['bride_photo', 'groom_photo', 'couple_photo'],
    'rainier' => [],
    'archak' => ['cover', 'bride_photo', 'groom_photo', 'couple_photo'],
    'parang' => ['bride_photo', 'groom_photo'],
    'pawiwahan' => ['cover', 'bride_photo', 'groom_photo'],
];

foreach ($expectedRoles as $preset => $roles) {
    media_role_assert(theme_contract_media_roles($preset) === $roles, "$preset media role contract mismatch");
}

$adminSource = (string)file_get_contents(dirname(__DIR__) . '/admin/index.php');
media_role_assert(str_contains($adminSource, '$themeMediaRoles ='), 'Admin does not resolve the active media role contract');
media_role_assert(str_contains($adminSource, 'if (!empty($themeMediaRoles))'), 'Admin media role panel gate is missing');
foreach (['cover', 'bride_photo', 'groom_photo', 'couple_photo'] as $role) {
    media_role_assert(str_contains($adminSource, "in_array('$role', \$themeMediaRoles, true)"), "Admin role gate missing for $role");
}

$base = load_config();
$shared = [
    'presetKey' => 'dewankl',
    'heroText' => $base['wedding']['opening_text'] ?? '',
    'guestFallback' => 'Bapak/Ibu/Saudara/i',
    'countdownTarget' => $base['schedule']['countdown_target'] ?? '',
    'calendarLink' => build_google_calendar_link($base),
    'calendarDownloadName' => 'Undangan',
    'whatsappLink' => build_whatsapp_link($base),
    'musicSrc' => '',
    'bgHero' => '',
    'sectionStyles' => ['', '', ''],
    'brideParents' => '',
    'groomParents' => '',
    'siteTitle' => $base['site']['title'] ?? '',
    'weddingTitle' => $base['wedding']['title'] ?? '',
];

$renderMedia = [
    'dewankl' => ['bride_photo' => 'uploads/cover/dewana-bride-role.webp', 'groom_photo' => 'uploads/cover/dewana-groom-role.webp'],
    'yami-buzzy' => ['bride_photo' => 'uploads/cover/yami-bride-role.webp', 'groom_photo' => 'uploads/cover/yami-groom-role.webp'],
    'archak' => ['bride_photo' => 'uploads/cover/archak-bride-role.webp', 'groom_photo' => 'uploads/cover/archak-groom-role.webp', 'couple_photo' => 'uploads/cover/archak-couple-role.webp'],
    'parang' => ['bride_photo' => 'uploads/cover/parang-bride-role.webp', 'groom_photo' => 'uploads/cover/parang-groom-role.webp'],
    'pawiwahan' => ['bride_photo' => 'uploads/cover/pawiwahan-bride-role.webp', 'groom_photo' => 'uploads/cover/pawiwahan-groom-role.webp'],
];

foreach ($renderMedia as $preset => $media) {
    $config = $base;
    $config['theme']['mode'] = 'preset';
    $config['theme']['theme_preset'] = $preset;
    $config['media'] = array_replace($config['media'], $media);
    $shared['presetKey'] = $preset;
    $html = render_theme_layout($config, $shared);
    media_role_assert($html !== '', "$preset media probe rendered empty HTML");
    foreach ($media as $path) {
        media_role_assert(str_contains($html, $path), "$preset renderer does not emit $path");
    }
}

$yamiBase = $base;
$yamiBase['theme']['mode'] = 'preset';
$yamiBase['theme']['theme_preset'] = 'yami-buzzy';
$yamiBase['media']['couple_photo'] = 'uploads/cover/yami-couple-fallback.webp';
$yamiBase['media']['bride_photo'] = '';
$yamiBase['media']['groom_photo'] = '';
$shared['presetKey'] = 'yami-buzzy';
$yamiFallbackHtml = render_theme_layout($yamiBase, $shared);
media_role_assert(substr_count($yamiFallbackHtml, 'uploads/cover/yami-couple-fallback.webp') >= 2, 'Yami Buzzy couple photo does not fall back to both avatars');

echo "PASS: media role contract, Admin gates, renderer usage, and Yami fallback\n";
