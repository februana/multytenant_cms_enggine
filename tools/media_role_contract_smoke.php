<?php
declare(strict_types=1);

$runtime = sys_get_temp_dir() . '/multytenant-media-role-' . bin2hex(random_bytes(6));
@mkdir($runtime, 0700, true);
putenv('UNDANGAN_DB_PATH=' . $runtime . '/database.sqlite');
putenv('UNDANGAN_MAIN_DOMAIN=tenant-a.example.test');
register_shutdown_function(static function () use ($runtime): void {
    if (is_dir($runtime)) exec('rm -rf -- ' . escapeshellarg($runtime));
});

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function media_role_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$_SERVER['HTTP_HOST'] = 'tenant-a.example.test';
$db = tenant_database(false);
$db->exec("CREATE TABLE tenants (id INTEGER PRIMARY KEY AUTOINCREMENT, domain TEXT NOT NULL UNIQUE COLLATE NOCASE, status TEXT NOT NULL DEFAULT 'active', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
$db->exec("CREATE TABLE tenant_configs (tenant_id INTEGER PRIMARY KEY, config_json TEXT NOT NULL, custom_css TEXT NOT NULL DEFAULT '', event_ics TEXT NOT NULL DEFAULT '', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
$db->exec("INSERT INTO tenants (domain, status) VALUES ('tenant-a.example.test', 'active')");
$tenantId = (int)$db->lastInsertRowID();
$db->exec("INSERT INTO tenant_configs (tenant_id, config_json) VALUES (" . $tenantId . ", '{}')");
$db->close();

$expectedRoles = [
    'custom' => ['cover', 'bride_photo', 'groom_photo', 'couple_photo', 'love_story_video'],
    'dewankl' => ['cover', 'bride_photo', 'groom_photo', 'couple_photo', 'love_story_video'],
    'shubh-vivah' => [],
    'yami-buzzy' => ['bride_photo', 'groom_photo', 'couple_photo', 'love_story_video'],
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
    media_role_assert(str_contains($adminSource, 'in_array(\'' . $role . '\', $themeMediaRoles, true)'), "Admin role gate missing for $role");
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

$mediaPath = static fn(string $name): string => 'uploads/tenant_' . $tenantId . '/cover/' . $name;
$renderMedia = [
    'dewankl' => ['bride_photo' => $mediaPath('dewana-bride-role.webp'), 'groom_photo' => $mediaPath('dewana-groom-role.webp'), 'couple_photo' => $mediaPath('dewana-couple-role.webp')],
    'yami-buzzy' => ['bride_photo' => $mediaPath('yami-bride-role.webp'), 'groom_photo' => $mediaPath('yami-groom-role.webp')],
    'archak' => ['bride_photo' => $mediaPath('archak-bride-role.webp'), 'groom_photo' => $mediaPath('archak-groom-role.webp'), 'couple_photo' => $mediaPath('archak-couple-role.webp')],
    'parang' => ['bride_photo' => $mediaPath('parang-bride-role.webp'), 'groom_photo' => $mediaPath('parang-groom-role.webp')],
    'pawiwahan' => ['bride_photo' => $mediaPath('pawiwahan-bride-role.webp'), 'groom_photo' => $mediaPath('pawiwahan-groom-role.webp')],
];
$coverDir = tenant_upload_dir('cover');
@mkdir($coverDir, 0755, true);
foreach ($renderMedia as $media) foreach ($media as $path) @touch(ROOT_DIR . '/' . $path);

foreach ($renderMedia as $preset => $media) {
    $config = $base;
    $config['theme']['mode'] = 'preset';
    $config['theme']['theme_preset'] = $preset;
    $config['media'] = array_replace($config['media'], $media);
    $shared['presetKey'] = $preset;
    $html = render_theme_layout($config, $shared);
    media_role_assert($html !== '', "$preset media probe rendered empty HTML");
    foreach ($media as $path) media_role_assert(str_contains($html, $path), "$preset renderer does not emit $path");
}

$yamiBase = $base;
$yamiBase['theme']['mode'] = 'preset';
$yamiBase['theme']['theme_preset'] = 'yami-buzzy';
$yamiBase['media']['couple_photo'] = $mediaPath('yami-couple-fallback.webp');
$yamiBase['media']['bride_photo'] = '';
$yamiBase['media']['groom_photo'] = '';
@touch(ROOT_DIR . '/' . $yamiBase['media']['couple_photo']);
$shared['presetKey'] = 'yami-buzzy';
$yamiFallbackHtml = render_theme_layout($yamiBase, $shared);
media_role_assert(substr_count($yamiFallbackHtml, $yamiBase['media']['couple_photo']) >= 2, 'Yami Buzzy couple photo does not fall back to both avatars');

echo "PASS: media role contract, Admin gates, tenant-aware renderer usage, and Yami fallback\n";
