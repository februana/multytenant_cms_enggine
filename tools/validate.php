<?php
$root = dirname(__DIR__);
$errors = [];
$warnings = [];

function add_error(string $message): void {
    global $errors;
    $errors[] = $message;
}

function add_warning(string $message): void {
    global $warnings;
    $warnings[] = $message;
}

$requiredFiles = [
    $root . '/config.php',
    $root . '/index.php',
    $root . '/admin/index.php',
    $root . '/admin/app.js',
    $root . '/admin/qr.php',
    $root . '/app/gallery.php',
    $root . '/app/love-story.php',
    $root . '/save.php',
    $root . '/messages.php',
    $root . '/gallery.php',
    $root . '/event.ics.php',
    $root . '/media.php',
    $root . '/style.css',
    $root . '/script.js',
];

foreach ($requiredFiles as $file) {
    if (!is_file($file)) {
        add_error('Missing required file: ' . str_replace($root . '/', '', $file));
    }
}

require_once $root . '/config.php';
$defaults = config_defaults();
// Runtime settings are tenant-scoped in SQLite; defaults provide the source contract for static validation.
$config = $defaults;

$authSource = (string)@file_get_contents($root . '/config.php');
$adminSource = (string)@file_get_contents($root . '/admin/index.php');
$saveSource = (string)@file_get_contents($root . '/app/save.php');
$superAdminSource = (string)@file_get_contents($root . '/admin/super-admin.php');
$profileSource = (string)@file_get_contents($root . '/admin/profile.php');
$migrationSource = (string)@file_get_contents($root . '/database/migrations/001_multi_tenant.sql');
foreach ([
    'current_admin_user_record' => $authSource,
    'admin_action_is_authorized' => $authSource,
    'session user revalidation' => $authSource,
    'admin session result gate' => $adminSource,
    'AJAX action gate' => $saveSource,
    'Super Admin current password gate' => $superAdminSource,
    'profile current password gate' => $profileSource,
    'audit log migration' => $migrationSource,
] as $label => $source) {
    $needle = match ($label) {
        'current_admin_user_record' => 'function current_admin_user_record',
        'admin_action_is_authorized' => 'function admin_action_is_authorized',
        'session user revalidation' => 'current_admin_user_record();',
        'admin session result gate' => 'if (!$adminSessionValid):',
        'AJAX action gate' => 'admin_action_is_authorized($action',
        'Super Admin current password gate' => 'verify_current_admin_password',
        'profile current password gate' => 'name="current_password"',
        'audit log migration' => 'CREATE TABLE IF NOT EXISTS audit_logs',
    };
    if ($source === '' || !str_contains($source, $needle)) add_error("Auth regression: {$label} is missing.");
}
foreach ([$adminSource, $superAdminSource, $profileSource] as $source) {
    if (str_contains($source, 'logout=1')) add_error('Auth regression: state-changing GET logout remains in an admin page.');
}
if (!str_contains($adminSource, "is_super_admin() && \$globalAdminCapabilityEnabled('backup')")) add_error('Auth regression: backup menu is not Super Admin-only.');
if (str_contains($superAdminSource, 'decrypt_visible_password')) add_error('Auth regression: Super Admin page renders stored tenant passwords.');

$requiredRootKeys = ['site', 'wedding', 'parents', 'schedule', 'location', 'media', 'gallery', 'gift', 'whatsapp', 'admin', 'theme', 'sections', 'love_story'];
if (isset($config) && is_array($config)) {
    foreach ($requiredRootKeys as $key) {
        if (!array_key_exists($key, $config)) {
            add_warning("Missing expected config key: {$key}");
        }
    }
}

if (isset($config['sections']) && is_array($config['sections'])) {
    foreach ($config['sections'] as $section) {
        if (!is_array($section)) {
            add_warning('A default section entry is not an object.');
            continue;
        }
        $id = trim((string)($section['id'] ?? ''));
        if ($id === '') {
            add_warning('A section entry is missing an id.');
            continue;
        }
        if (!array_key_exists('enabled', $section)) {
            add_warning("Section {$id} is missing enabled flag.");
        }
    }
}

$rendererSource = (@file_get_contents($root . '/index.php') ?: '') . "\n" . (@file_get_contents($root . '/app/theme-renderer.php') ?: '');
if (trim($rendererSource) === '') {
    add_error('Unable to read index.php or app/theme-renderer.php for renderer contract validation.');
} else {
    $hasDynamicCheck = (strpos($rendererSource, 'is_section_enabled($config, $sectionId)') !== false);
    preg_match_all('/is_section_enabled\s*\(\s*\$config\s*,\s*[\'\"]([^\'\"]+)[\'\"]\s*\)/', $rendererSource, $matches);
    $rendererIds = [];
    foreach ($matches[1] as $id) {
        $rendererIds[] = normalize_section_id((string)$id);
    }
    $rendererIds = array_values(array_unique($rendererIds));

    if (isset($config['sections']) && is_array($config['sections'])) {
        foreach ($config['sections'] as $section) {
            if (!is_array($section)) {
                continue;
            }
            $id = normalize_section_id((string)($section['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $knownRendererIds = ['hero', 'guest_intro', 'undangan', 'countdown', 'cerita', 'galeri', 'acara', 'lokasi', 'amplop', 'rsvp', 'messages', 'music', 'footer', 'bride_groom'];
            if (!$hasDynamicCheck && in_array($id, $knownRendererIds, true) && !in_array($id, $rendererIds, true)) {
                add_warning("Renderer contract is missing the visibility check for section {$id}.");
            }
        }
    }
}

$previewSource = @file_get_contents($root . '/admin/app.js');
if ($previewSource === false) {
    add_error('Unable to read admin/app.js for preview contract validation.');
} else {
    if (preg_match('/const previewFieldNames = \[(.*?)\];/s', $previewSource, $match) !== 1) {
        add_warning('Preview field list not found in admin/app.js.');
    } else {
        preg_match_all("/'([^']+)'/", $match[1], $matches);
        $previewKeys = array_values(array_unique($matches[1]));
        $allowedThemeKeys = array_keys($defaults['theme']);
        foreach ($previewKeys as $key) {
            if ($key === 'theme_preset' || $key === 'buttons_mobile_layout') {
                continue;
            }
            if (!in_array($key, $allowedThemeKeys, true)) {
                add_warning("Preview key '{$key}' is not part of the known theme contract.");
            }
        }
    }
}

$mediaFields = [
    'media.cover',
    'media.background_hero',
    'media.music',
    'gift.qris_image',
    'site.open_graph_image',
];

foreach ($mediaFields as $path) {
    $value = null;
    $segments = explode('.', $path);
    $cursor = $config ?? [];
    foreach ($segments as $segment) {
        if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
            $value = '';
            break;
        }
        $value = $cursor[$segment];
        $cursor = $value;
    }
    if (!is_string($value) || trim($value) === '') {
        continue;
    }
    $full = $root . '/' . ltrim($value, '/');
    if (!file_exists($full)) {
        add_warning("Configured media reference does not exist: {$path} => {$value}");
    }
}

if (isset($config['media']['background_sections']) && is_array($config['media']['background_sections'])) {
    foreach ($config['media']['background_sections'] as $index => $value) {
        if (!is_string($value) || trim($value) === '') {
            continue;
        }
        $full = $root . '/' . ltrim($value, '/');
        if (!file_exists($full)) {
            add_warning("Configured section background reference does not exist at index {$index}: {$value}");
        }
    }
}

$legacyRootFrontend = [
    $root . '/app/style.css',
    $root . '/app/script.js',
];
foreach ($legacyRootFrontend as $candidate) {
    if (is_file($candidate)) {
        add_warning('Duplicate frontend asset detected: ' . str_replace($root . '/', '', $candidate));
    }
}

$duplicateMediaPipeline = [
    $root . '/app/media.php',
    $root . '/app/storage.php',
    $root . '/app/upload.php',
];
foreach ($duplicateMediaPipeline as $candidate) {
    if (is_file($candidate)) {
        add_warning('Alternative media pipeline file detected: ' . str_replace($root . '/', '', $candidate));
    }
}

if (!empty($warnings)) {
    foreach ($warnings as $warning) {
        echo "WARN: {$warning}\n";
    }
}

if (!empty($errors)) {
    echo "FAIL\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "PASS: CMS-first contract validation succeeded.\n";
exit(0);
