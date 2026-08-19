<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/theme-contract.php';
require_once dirname(__DIR__) . '/app/theme-helper.php';
require_once dirname(__DIR__) . '/app/theme-renderer.php';

function audit_message(string $status, string $message): void { echo $status . ': ' . $message . PHP_EOL; }
function audit_render_config(string $preset): array {
    $config = config_defaults();
    $config['theme']['mode'] = $preset === 'custom' ? 'custom' : 'preset';
    $config['theme']['theme_preset'] = $preset;
    return $config;
}
function audit_shared(string $preset): array {
    return ['presetKey' => $preset, 'heroText' => 'Audit', 'guestFallback' => 'Bapak/Ibu/Saudara/i', 'guestName' => 'Nama Tamu Audit', 'countdownTarget' => '', 'calendarLink' => '#calendar', 'calendarDownloadName' => 'Undangan', 'whatsappLink' => '#whatsapp', 'musicSrc' => '', 'bgHero' => '', 'sectionStyles' => ['', '', ''], 'brideParents' => '', 'groomParents' => '', 'siteTitle' => 'Audit', 'weddingTitle' => 'Audit'];
}
$failed = 0;
$presets = theme_builtin_preset_keys();
foreach ($presets as $preset) {
    $registry = theme_registry()[$preset] ?? null;
    $contract = theme_contract_for($preset);
    $layout = dirname(__DIR__) . '/themes/' . $preset . '/layout.php';
    if (!is_array($registry)) { audit_message('FAIL', "$preset missing registry"); $failed = 1; continue; }
    if (!is_array($contract) || empty($contract['sections'])) { audit_message('FAIL', "$preset missing contract sections"); $failed = 1; }
    if (!is_file($layout)) { audit_message('FAIL', "$preset missing layout"); $failed = 1; continue; }
    $html = render_theme_layout(audit_render_config($preset), audit_shared($preset));
    preg_match_all('/\bid=["\']([^"\']+)["\']/', $html, $idMatches);
    $idCounts = array_count_values($idMatches[1] ?? []);
    foreach ($idCounts as $id => $count) if ($count > 1) { audit_message('FAIL', "$preset duplicate DOM id={$id} count={$count}"); $failed = 1; }
    preg_match_all('/href=["\']#([^"\']+)["\']/', $html, $anchorMatches);
    $ids = array_fill_keys($idMatches[1] ?? [], true);
    foreach (array_unique($anchorMatches[1] ?? []) as $anchor) if (!isset($ids[$anchor])) { audit_message('FAIL', "$preset broken anchor #{$anchor}"); $failed = 1; }
    foreach ((array)($contract['sections'] ?? []) as $section) {
        $domId = trim((string)($section['dom_id'] ?? ''));
        if ($domId !== '' && !isset($ids[$domId])) { audit_message('FAIL', "$preset contract dom_id={$domId} not rendered"); $failed = 1; }
    }
    $schema = theme_visual_capabilities_for_config(audit_render_config($preset), $preset);
    foreach ($schema as $key => $definition) if (!isset($definition['type'], $definition['label'], $definition['default'])) { audit_message('FAIL', "$preset visual capability {$key} incomplete schema"); $failed = 1; }
    audit_message('PASS', "$preset registry/contract/layout/IDs/anchors/visual schema checked");
}
$auditCustom = render_theme_layout(audit_render_config('custom'), audit_shared('custom'));
preg_match_all('/\bid=["\']([^"\']+)["\']/', $auditCustom, $customIds);
$customCounts = array_count_values($customIds[1] ?? []);
foreach ($customCounts as $id => $count) if ($count > 1) { audit_message('FAIL', "custom duplicate DOM id={$id} count={$count}"); $failed = 1; }
audit_message('PASS', 'custom renderer duplicate ID check completed');
exit($failed);
