<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$runtime = sys_get_temp_dir() . '/multytenant-media-manager-' . bin2hex(random_bytes(6));
@mkdir($runtime, 0700, true);
putenv('UNDANGAN_DB_PATH=' . $runtime . '/database.sqlite');
putenv('UNDANGAN_MAIN_DOMAIN=media-manager.example.test');
$_SERVER['HTTP_HOST'] = 'media-manager.example.test';
register_shutdown_function(static function () use ($runtime): void {
    if (is_dir($runtime)) exec('rm -rf -- ' . escapeshellarg($runtime));
});

require_once $root . '/config.php';
require_once $root . '/app/theme-helper.php';
require_once $root . '/app/theme-renderer.php';

function media_manager_assert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ' . $message . PHP_EOL;
}

$db = tenant_database(false);
$db->exec(file_get_contents($root . '/database/migrations/001_multi_tenant.sql'));
$tenant = $db->prepare("INSERT INTO tenants (domain, status) VALUES ('media-manager.example.test', 'active')");
$tenant->execute();
$tenantId = (int)$db->lastInsertRowID();
$seed = $db->prepare('INSERT INTO tenant_configs (tenant_id, config_json) VALUES (:tenant_id, :config_json)');
$seed->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
$seed->bindValue(':config_json', '{}', SQLITE3_TEXT);
$seed->execute();
$db->close();

$config = load_config();
$sourcePresetKeys = ['dewankl', 'shubh-vivah', 'yami-buzzy', 'rainier', 'parang', 'pawiwahan', 'archak'];
$globalRoleTargets = [
    'media.cover' => 'cover',
    'media.bride_photo' => 'bride_photo',
    'media.groom_photo' => 'groom_photo',
    'media.couple_photo' => 'couple_photo',
    'media.love_story_video' => 'love_story_video',
];
foreach ($sourcePresetKeys as $presetKey) {
    $presetConfig = $config;
    $presetConfig['theme']['mode'] = 'preset';
    $presetConfig['theme']['theme_preset'] = $presetKey;
    $schema = theme_visual_capabilities_for_config($presetConfig, $presetKey);
    $allTargets = media_manager_target_definitions($presetConfig, $presetKey);
    $visibleTargets = media_manager_visible_target_definitions($presetConfig, $presetKey);
    foreach ($schema as $visualKey => $definition) {
        if (($definition['type'] ?? '') !== 'image') continue;
        $target = 'theme_visuals.' . $presetKey . '.' . $visualKey;
        media_manager_assert(isset($allTargets[$target]), "$presetKey retains visual asset target $visualKey internally");
        media_manager_assert(isset($visibleTargets[$target]), "$presetKey exposes schema visual target $visualKey");
    }
    foreach ((array)(theme_registry()[$presetKey]['schema'] ?? []) as $optionKey => $definition) {
        if (($definition['type'] ?? '') !== 'image') continue;
        $target = 'theme_options.' . $presetKey . '.' . $optionKey;
        media_manager_assert(isset($allTargets[$target]), "$presetKey retains image option $optionKey internally");
        media_manager_assert(isset($visibleTargets[$target]), "$presetKey exposes image option $optionKey");
    }
    $roles = theme_contract_media_roles($presetKey);
    foreach ($globalRoleTargets as $target => $role) {
        media_manager_assert(isset($visibleTargets[$target]) === in_array($role, $roles, true), "$presetKey filters $target by its media role");
    }
    $adminCapabilities = theme_admin_capabilities_for_config($presetConfig);
    media_manager_assert(isset($visibleTargets['media.music']) === in_array('music', $adminCapabilities, true), "$presetKey filters music by admin capability");
    media_manager_assert(isset($visibleTargets['gift.qris_image']) === in_array('gift', $adminCapabilities, true), "$presetKey filters QRIS by Gift capability");
    media_manager_assert(isset($visibleTargets['site.open_graph_image']) === in_array('seo', $adminCapabilities, true), "$presetKey filters Open Graph by SEO capability");
    $otherPreset = $presetKey === 'dewankl' ? 'rainier' : 'dewankl';
    media_manager_assert(!isset($visibleTargets['theme_visuals.' . $otherPreset . '.hero_background']), "$presetKey hides another preset's visual target");
}

$customConfig = $config;
$customConfig['theme']['mode'] = 'custom';
$customConfig['theme']['theme_preset'] = 'custom';
$customVisibleTargets = media_manager_visible_target_definitions($customConfig, 'custom');
foreach (['media.cover', 'media.bride_photo', 'media.groom_photo', 'media.couple_photo', 'media.love_story_video', 'media.music', 'gift.qris_image', 'site.open_graph_image', 'theme_visuals.custom.hero_background'] as $target) {
    media_manager_assert(isset($customVisibleTargets[$target]), "Custom mode retains $target capability");
}
media_manager_assert(!media_manager_set_target($customConfig, 'media.invalid_target', 'uploads/not-allowed.webp'), 'Invalid media target cannot be written');
media_manager_assert(!media_manager_set_target($customConfig, 'theme_visuals.rainier.hero_background', 'uploads/not-allowed.webp'), 'Inactive preset visual target cannot be written');

ensure_upload_dirs();
$probeSuffix = bin2hex(random_bytes(4));
$assetPath = tenant_upload_dir('theme_assets') . '/archak/media-manager-badge-' . $probeSuffix . '.webp';
@mkdir(dirname($assetPath), 0755, true);
copy($root . '/themes/parang/assets/parang-pattern.webp', $assetPath);
$relativeAsset = relative_path($assetPath);
media_manager_assert(media_path_is_safe_storage($relativeAsset), 'Nested preset asset remains tenant-safe');

$config['theme']['theme_preset'] = 'archak';
$config['theme']['mode'] = 'preset';
media_manager_assert(media_manager_set_target($config, 'theme_options.archak.header_badge_image', $relativeAsset), 'Media Manager can apply nested preset asset to theme option');
media_manager_assert(save_config($config), 'Applied asset persists to tenant config');
$reloaded = load_config();
media_manager_assert(($reloaded['theme_options']['archak']['header_badge_image'] ?? '') === $relativeAsset, 'Applied preset asset reloads from tenant config');
$reloaded['media']['bride_photo'] = $relativeAsset;
$reloaded['media']['groom_photo'] = $relativeAsset;
$reloaded['media']['couple_photo'] = $relativeAsset;
$reloaded['gift']['qris_image'] = $relativeAsset;
$reloaded['site']['open_graph_image'] = $relativeAsset;
$renameResult = rename_uploaded_asset($relativeAsset, 'media-manager-renamed-' . $probeSuffix . '.webp');
media_manager_assert(!empty($renameResult['success']), 'Media Manager can rename a nested preset asset');
$renamedReference = (string)$renameResult['path'];
replace_media_references($reloaded, $relativeAsset, $renamedReference);
$renamedReferences = [
    'bride_photo' => $reloaded['media']['bride_photo'],
    'groom_photo' => $reloaded['media']['groom_photo'],
    'couple_photo' => $reloaded['media']['couple_photo'],
    'qris_image' => $reloaded['gift']['qris_image'],
    'open_graph_image' => $reloaded['site']['open_graph_image'],
    'theme_option_header_badge' => $reloaded['theme_options']['archak']['header_badge_image'],
];
foreach ($renamedReferences as $label => $reference) {
    media_manager_assert($reference === $renamedReference, "Rename propagation updates $label");
}
$relativeAsset = $renamedReference;
$items = list_media_library(['type' => 'image']);
$found = false;
foreach ($items as $item) {
    if (($item['path'] ?? '') === $relativeAsset) { $found = true; break; }
}
media_manager_assert($found, 'Media Manager lists nested theme asset files');

media_manager_assert(media_manager_clear_target($reloaded, 'theme_options.archak.header_badge_image'), 'Media Manager can clear preset asset reference');
media_manager_assert(save_config($reloaded), 'Cleared asset reference persists to tenant config');
media_manager_assert(is_file(ROOT_DIR . '/' . $relativeAsset), 'Clearing a target does not delete the physical asset');
media_manager_assert(delete_uploaded_asset($relativeAsset), 'Media Manager deletes the asset after references are cleared');
media_manager_assert(!is_file(ROOT_DIR . '/' . $relativeAsset), 'Deleted asset is removed from tenant storage');

$adminSource = (string)file_get_contents($root . '/admin/index.php');
media_manager_assert(str_contains($adminSource, 'media_manager_visible_target_definitions'), 'Admin derives visible media targets from the preset contract');
media_manager_assert(str_contains($adminSource, 'clear_media_target'), 'Admin exposes target reset action');
media_manager_assert(str_contains($adminSource, 'Atur penggunaan di preset aktif'), 'Admin exposes detailed per-asset target controls');
foreach (['bride_photo', 'groom_photo', 'couple_photo', 'qris', 'open_graph'] as $uploadTarget) {
    media_manager_assert(str_contains($adminSource, 'value="' . $uploadTarget . '"'), "Admin exposes direct upload target $uploadTarget");
}

echo "PASS: media manager target, nested asset inventory, apply/reset, tenant persistence, and delete flow\n";
