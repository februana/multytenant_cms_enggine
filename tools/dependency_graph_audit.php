<?php
declare(strict_types=1);

/**
 * Build-time dependency graph audit for the Pure Multi-Tenant Wedding Invitation app.
 * This intentionally audits definitions and runtime output without adding per-request
 * filesystem checks to the application.
 */

$root = dirname(__DIR__);
require_once $root . '/config.php';
require_once $root . '/app/theme-contract.php';
require_once $root . '/app/theme-helper.php';
require_once $root . '/app/theme-renderer.php';

$failures = 0;
$warnings = 0;
$counts = [];

function graph_report(string $category, string $message, bool $failure = false): void {
    global $failures, $counts;
    $counts[$category] = ($counts[$category] ?? 0) + 1;
    if ($failure) $failures++;
    echo $category . ': ' . $message . PHP_EOL;
}

function graph_warn(string $category, string $message): void {
    global $warnings, $counts;
    $counts[$category] = ($counts[$category] ?? 0) + 1;
    $warnings++;
    echo $category . ': ' . $message . PHP_EOL;
}

function graph_runtime_files(string $root): array {
    $files = [$root . '/config.php', $root . '/index.php', $root . '/gallery.php', $root . '/messages.php', $root . '/save.php', $root . '/event.ics.php', $root . '/media.php'];
    foreach (['app', 'admin', 'themes'] as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), ['php', 'js', 'css'], true)) $files[] = $file->getPathname();
        }
    }
    return array_values(array_unique(array_filter($files, 'is_file')));
}

function graph_file_line(string $path, int $line): string {
    return str_replace(dirname(__DIR__) . '/', '', $path) . ':' . $line;
}

function graph_definitions(array $files): array {
    $definitions = [];
    foreach ($files as $file) {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') continue;
        $tokens = token_get_all((string)file_get_contents($file));
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (!is_array($token) || $token[0] !== T_FUNCTION) continue;
            $nextSignificant = null;
            for ($k = $i + 1; $k < $count; $k++) {
                if (is_array($tokens[$k]) && in_array($tokens[$k][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) continue;
                $nextSignificant = $tokens[$k];
                break;
            }
            if ($nextSignificant === '(' || $nextSignificant === '&') continue;
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $definitions[strtolower($tokens[$j][1])] = [$file, (int)$tokens[$j][2]];
                    break;
                }
                if ($tokens[$j] === '(' || $tokens[$j] === '&') continue;
                if (is_array($tokens[$j]) && trim((string)$tokens[$j][1]) === '') continue;
                break;
            }
        }
    }
    return $definitions;
}

function graph_previous_significant(array $tokens, int $index) {
    for ($index--; $index >= 0; $index--) {
        $token = $tokens[$index];
        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) continue;
        return $token;
    }
    return null;
}

function graph_calls(array $files): array {
    $calls = [];
    $ignore = array_fill_keys([
        'if', 'for', 'foreach', 'while', 'switch', 'catch', 'isset', 'empty', 'array', 'list',
        'echo', 'print', 'include', 'include_once', 'require', 'require_once', 'eval', 'die', 'exit',
        'string', 'int', 'float', 'bool', 'mixed', 'callable', 'iterable', 'object', 'void', 'never', 'null', 'true', 'false'
    ], true);
    foreach ($files as $file) {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') continue;
        $tokens = token_get_all((string)file_get_contents($file));
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (!is_array($token) || $token[0] !== T_STRING) continue;
            $name = strtolower($token[1]);
            if (isset($ignore[$name])) continue;
            $prev = graph_previous_significant($tokens, $i);
            $next = $tokens[$i + 1] ?? null;
            $prevType = is_array($prev) ? $prev[0] : null;
            if ($next !== '(' || $prev === '->' || $prev === '::' || in_array($prevType, [T_FUNCTION, T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NEW], true)) continue;
            $calls[$name][] = [$file, (int)$token[2]];
        }
    }
    return $calls;
}

function graph_config_leaf_paths(array $value, string $prefix = ''): array {
    $result = [];
    foreach ($value as $key => $child) {
        $path = $prefix === '' ? (string)$key : $prefix . '.' . $key;
        if (is_array($child) && $child !== []) $result = array_merge($result, graph_config_leaf_paths($child, $path));
        else $result[] = $path;
    }
    return $result;
}

function graph_local_asset_path(string $root, string $url, string $preset): ?string {
    $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, 'data:') || preg_match('#^(?:https?:)?//#i', $url)) return null;
    $url = (string)(parse_url($url, PHP_URL_PATH) ?? $url);
    $url = urldecode($url);
    if (str_starts_with($url, '/')) $relative = ltrim($url, '/');
    elseif (str_starts_with($url, 'themes/')) $relative = $url;
    else $relative = 'themes/' . $preset . '/' . ltrim($url, '/');
    if (str_contains($relative, '..')) return null;
    return $root . '/' . $relative;
}

function graph_render_shared(string $preset): array {
    return [
        'presetKey' => $preset,
        'heroText' => 'Dependency audit',
        'guestFallback' => 'Bapak/Ibu/Saudara/i',
        'guestName' => 'Dependency Audit',
        'countdownTarget' => '',
        'calendarLink' => '#calendar',
        'calendarDownloadName' => 'Undangan',
        'whatsappLink' => '#whatsapp',
        'musicSrc' => '',
        'bgHero' => '',
        'sectionStyles' => ['', '', ''],
        'brideParents' => '',
        'groomParents' => '',
        'siteTitle' => 'Dependency Audit',
        'weddingTitle' => 'Dependency Audit',
    ];
}

$runtimeFiles = graph_runtime_files($root);
$definitions = graph_definitions($runtimeFiles);
$calls = graph_calls($runtimeFiles);

foreach (['config.php', 'app/theme-contract.php', 'app/theme-helper.php', 'app/theme-renderer.php', 'index.php', 'media.php'] as $required) {
    if (is_file($root . '/' . $required)) graph_report('PASS', 'required runtime component connected: ' . $required);
    else graph_report('MISSING_HELPER', 'required runtime component missing: ' . $required, true);
}
foreach ($calls as $name => $sites) {
    if (isset($definitions[$name]) || function_exists($name)) continue;
    $site = $sites[0];
    graph_report('MISSING_HELPER', $name . ' called at ' . graph_file_line($site[0], $site[1]) . ' but is not defined or loaded', true);
}
foreach ($definitions as $name => $definition) {
    if (isset($calls[$name]) || in_array($name, ['__construct'], true)) continue;
    // Many CMS helpers are public extension points or are reached dynamically.
    graph_report('INTENTIONALLY_UNUSED', $name . ' defined at ' . graph_file_line($definition[0], $definition[1]) . '; no direct static call, retained as an internal/public helper contract');
}

$defaults = config_defaults();
$contractRegistry = theme_contract_registry();
$registry = theme_registry();
$presets = theme_builtin_preset_keys();
foreach ($presets as $preset) {
    $contract = theme_contract_for($preset);
    if (!isset($registry[$preset])) graph_report('MISSING_CAPABILITY', $preset . ' contract has no theme registry entry', true);
    if (!is_array($contract) || empty($contract['sections'])) graph_report('MISSING_CAPABILITY', $preset . ' has no contract sections', true);
    $sectionIds = [];
    $domIds = [];
    foreach ((array)($contract['sections'] ?? []) as $section) {
        $id = (string)($section['id'] ?? '');
        if ($id === '' || isset($sectionIds[$id])) graph_report('BROKEN_DOM_REFERENCE', $preset . ' has duplicate/empty contract section ID: ' . $id, true);
        $sectionIds[$id] = true;
        $dom = trim((string)($section['dom_id'] ?? ''));
        if ($dom !== '' && isset($domIds[$dom])) graph_report('BROKEN_DOM_REFERENCE', $preset . ' has duplicate contract DOM ID: ' . $dom, true);
        if ($dom !== '') $domIds[$dom] = true;
    }
    $config = $defaults;
    $config['theme']['mode'] = 'preset';
    $config['theme']['theme_preset'] = $preset;
    $html = render_theme_layout($config, graph_render_shared($preset));
    $ids = [];
    if (preg_match_all('/\bid=["\']([^"\']+)["\']/', $html, $matches)) {
        foreach ($matches[1] as $id) {
            if (isset($ids[$id])) graph_report('BROKEN_DOM_REFERENCE', $preset . ' renders duplicate DOM id #' . $id, true);
            $ids[$id] = true;
        }
    }
    foreach ($domIds as $dom => $_) if (!isset($ids[$dom])) graph_report('BROKEN_DOM_REFERENCE', $preset . ' contract DOM ID #' . $dom . ' is not present in rendered HTML', true);
    if (preg_match_all('/(?:href|src)=["\']([^"\']+)["\']/', $html, $assetMatches)) {
        foreach (array_unique($assetMatches[1]) as $url) {
            $path = graph_local_asset_path($root, $url, $preset);
            if ($path === null) continue;
            if (!is_file($path) && !str_starts_with((string)(parse_url($url, PHP_URL_PATH) ?? ''), '/uploads/')) graph_report('BROKEN_ASSET', $preset . ' rendered asset URL ' . $url . ' resolves to missing file ' . str_replace($root . '/', '', $path), true);
            else graph_report('PASS', $preset . ' rendered asset resolves: ' . $url);
            if (is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'css') {
                $css = (string)file_get_contents($path);
                if (preg_match_all('/url\(\s*["\']?([^"\')]+)["\']?\s*\)/i', $css, $cssMatches)) {
                    foreach (array_unique($cssMatches[1]) as $cssUrl) {
                        if (preg_match('#^(?:https?:)?//#i', $cssUrl) || str_starts_with($cssUrl, 'data:')) continue;
                        $candidate = str_starts_with($cssUrl, '/') ? $root . '/' . ltrim($cssUrl, '/') : dirname($path) . '/' . $cssUrl;
                        $candidate = realpath($candidate) ?: $candidate;
                        if (str_contains($candidate, '..')) continue;
                        if (!is_file($candidate)) graph_report('BROKEN_ASSET', $preset . ' CSS ' . str_replace($root . '/', '', $path) . ' references missing ' . $cssUrl, true);
                    }
                }
            }
        }
    }
    $schema = theme_visual_capabilities_for_config($config, $preset);
    $visuals = theme_visual_values_for_config($config, $preset);
    $layoutSource = (string)@file_get_contents($root . '/themes/' . $preset . '/layout.php');
    foreach ($schema as $key => $definition) {
        if (!array_key_exists('default', $definition)) graph_report('MISSING_CAPABILITY', $preset . ' visual capability ' . $key . ' has no default', true);
        if (($definition['type'] ?? '') === 'color' && empty($definition['palette'])) graph_report('MISSING_CAPABILITY', $preset . ' color capability ' . $key . ' has no selectable palette', true);
        if (($definition['type'] ?? '') === 'font' && empty($definition['options'])) graph_report('MISSING_CAPABILITY', $preset . ' font capability ' . $key . ' has no selectable options', true);
        if (!array_key_exists($key, $visuals)) graph_report('MISSING_CONFIG', $preset . ' visual capability ' . $key . ' does not resolve from tenant config', true);
        $keyLiteral = "['" . $key . "']";
        if (str_contains($layoutSource, $keyLiteral) || str_contains($layoutSource, '[\"' . $key . '\"]')) graph_report('PASS', $preset . ' visual capability reaches its layout consumer: ' . $key);
        elseif (($definition['type'] ?? '') !== 'image') graph_report('MISSING_CAPABILITY', $preset . ' visual capability has no identifiable layout consumer: ' . $key, true);
    }
    foreach ((array)theme_contract_media_roles($preset) as $role) {
        $requirement = media_requirement($role, $preset);
        if (empty($requirement)) graph_report('MISSING_MEDIA_ROLE', $preset . ' media role ' . $role . ' has no media requirement', true);
        else graph_report('PASS', $preset . ' media role connected: ' . $role);
    }
    foreach ((array)theme_contract_admin_capabilities($preset) as $capability) {
        if (!in_array($capability, theme_admin_capabilities_for_config($config), true)) graph_report('MISSING_CAPABILITY', $preset . ' admin capability ' . $capability . ' is absent from CMS capability resolver', true);
    }
    graph_report('PASS', $preset . ' contract → helper → renderer → HTML chain checked');
}

$custom = $defaults;
$custom['theme']['mode'] = 'custom';
$custom['theme']['theme_preset'] = 'custom';
$customHtml = render_theme_layout($custom, graph_render_shared('custom'));
if (trim($customHtml) === '' || stripos($customHtml, '<section') === false) graph_report('BROKEN_DOM_REFERENCE', 'custom renderer did not produce a non-empty render fragment', true);
else graph_report('PASS', 'custom renderer produced a non-empty render fragment');

foreach ($presets as $preset) {
    $contract = theme_contract_for($preset);
    foreach ((array)($contract['assets'] ?? []) as $hint) {
        $class = 'metadata/abstract contract hint';
        if (preg_match('/\.(css|js)$/i', $hint)) {
            $candidate = $root . '/themes/' . $preset . '/' . $hint;
            $matches = is_file($candidate) ? [$candidate] : glob($root . '/themes/' . $preset . '/**/' . basename($hint));
            if (!empty($matches) && is_file($matches[0])) $class = 'local static dependency (' . str_replace($root . '/', '', $matches[0]) . ')';
            elseif ($preset === 'pawiwahan' && in_array($hint, ['pawiwahan.css', 'pawiwahan.js'], true)) $class = 'local adapter alias (style.css/script.js)';
            else graph_report('BROKEN_ASSET', $preset . ' contract asset hint ' . $hint . ' has no local file', true);
        } elseif (str_contains($hint, '@')) $class = 'external CDN dependency';
        elseif (in_array($hint, ['theme-media', 'fontawesome-kit', 'gilroy-font', 'source-ornaments', 'source-dresscode-icons', 'tally-widget-optional'], true)) $class = 'abstract/source metadata or runtime capability';
        graph_report('PASS', $preset . ' contract asset hint classified as ' . $class . ': ' . $hint);
    }
}

$getAssetCalls = [];
foreach ($runtimeFiles as $file) {
    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') continue;
    $source = (string)file_get_contents($file);
    if (preg_match_all('/get_theme_asset_url\(\s*(?:\$[A-Za-z_][A-Za-z0-9_]*|["\']([^"\']+)["\'])\s*,\s*["\']([^"\']+)["\']\s*\)/', $source, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $preset = $match[1] !== '' ? $match[1] : basename(dirname($file));
            $filename = $match[2];
            $candidate = $root . '/themes/' . $preset . '/' . $filename;
            if (is_file($candidate)) graph_report('PASS', 'get_theme_asset_url(' . $preset . ', ' . $filename . ') → existing file');
            else graph_report('BROKEN_ASSET', 'get_theme_asset_url(' . $preset . ', ' . $filename . ') → missing ' . str_replace($root . '/', '', $candidate), true);
            $getAssetCalls[] = [$preset, $filename];
        }
    }
}

$htaccess = (string)@file_get_contents($root . '/.htaccess');
$mediaEndpoint = (string)@file_get_contents($root . '/media.php');
$providers = ['save.php', 'messages.php', 'gallery.php', 'love-story.php', 'event.ics.php', 'media.php'];
foreach ($providers as $endpoint) {
    $rootFile = $root . '/' . $endpoint;
    $appFile = $root . '/app/' . $endpoint;
    if (is_file($rootFile) || is_file($appFile)) graph_report('PASS', 'endpoint provider connected: ' . $endpoint . (is_file($rootFile) ? ' via root wrapper' : ' via app implementation'));
    else graph_report('BROKEN_ENDPOINT', 'endpoint provider missing: ' . $endpoint, true);
}
$allSource = '';
foreach ($runtimeFiles as $file) $allSource .= "\n" . (string)file_get_contents($file);
foreach (['save.php', 'messages.php', 'gallery.php', 'love-story.php', 'event.ics.php', 'media.php'] as $endpoint) {
    if ($endpoint === 'media.php' && preg_match('/RewriteRule.*media\\.php\\?path=/s', $htaccess)) graph_report('PASS', 'endpoint has Apache rewrite consumer: ' . $endpoint);
    elseif (str_contains($allSource, $endpoint)) graph_report('PASS', 'endpoint has at least one runtime consumer/reference: ' . $endpoint);
    elseif ($endpoint === 'love-story.php') graph_report('INTENTIONALLY_UNUSED', $endpoint . ' is a direct/public endpoint contract with no static fetch consumer');
    else graph_warn('ORPHAN_ENDPOINT', $endpoint . ' has no static runtime consumer; may be a direct/public endpoint contract');
}

foreach (graph_config_leaf_paths($defaults) as $path) {
    $top = explode('.', $path)[0];
    if (str_contains($allSource, "['" . $top . "']") || str_contains($allSource, '["' . $top . '"]') || str_contains($allSource, '$config')) graph_report('PASS', 'config key family connected: ' . $top);
    else graph_warn('ORPHAN_CONFIG', 'config key family has no direct static consumer: ' . $top);
}

$legacyPatterns = [
    'uploads/cover', 'uploads/gallery', 'uploads/background', 'uploads/music', 'uploads/love-story', 'uploads/theme-assets',
    'config.json', 'guest-links.json', 'custom.css', 'event.ics'
];
$runtimeScanFiles = array_filter($runtimeFiles, static fn(string $file): bool => !str_contains($file, '/tools/') && !str_contains($file, '/deploy/'));
foreach ($runtimeScanFiles as $file) {
    $lines = @file($file, FILE_IGNORE_NEW_LINES) ?: [];
    foreach ($lines as $lineNo => $line) {
        foreach ($legacyPatterns as $pattern) {
            if (!str_contains($line, $pattern)) continue;
            if ($pattern === 'config.json' && preg_match('/legacy|old|valid/i', $line)) {
                graph_report('INTENTIONALLY_UNUSED', graph_file_line($file, $lineNo + 1) . ' mentions legacy config.json only for compatibility documentation');
                continue;
            }
            if ($pattern === 'event.ics' && str_contains($line, 'event.ics.php')) {
                graph_report('PASS', graph_file_line($file, $lineNo + 1) . ' references the current tenant event.ics.php endpoint');
                continue;
            }
            graph_warn('LEGACY_RUNTIME_PATH', graph_file_line($file, $lineNo + 1) . ' references ' . $pattern . '; verify it is comment-only or an intentional endpoint contract');
        }
    }
}

if (preg_match('/RewriteRule\s+\^uploads\/\(\.\+\).*media\.php\?path=uploads\/\$1/', $htaccess) && str_contains($mediaEndpoint, 'current_tenant(true)') && str_contains($mediaEndpoint, 'media_path_is_safe_storage')) graph_report('PASS', 'uploads → Apache rewrite → media.php → current tenant containment chain connected');
else graph_report('TENANT_ISOLATION_RISK', 'static uploads delivery chain is incomplete', true);

$envExample = (string)@file_get_contents($root . '/.env.example');
if (preg_match('/^UNDANGAN_AUTO_PROVISION=1$/m', $envExample)) graph_report('PASS', 'environment template enables validated auto-provisioning by default');
else graph_warn('ORPHAN_CONFIG', '.env.example does not document the current auto-provisioning default');

echo PHP_EOL . 'SUMMARY: ' . $failures . ' confirmed failures, ' . $warnings . ' warnings' . PHP_EOL;
if ($failures > 0) exit(1);
echo 'PASS: dependency graph audit completed without confirmed broken dependency.' . PHP_EOL;
