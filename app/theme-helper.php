<?php
/**
 * Theme Helper Functions
 *
 * Shared utilities for all theme templates and presentation output.
 */

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/theme-contract.php';

if (!defined('THEME_HELPER_LOADED')) {
    define('THEME_HELPER_LOADED', true);
}

/** Escape HTML output. */
function escape_html(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Render user-entered text safely while preserving Unicode, spaces, and line breaks.
 * This helper intentionally performs no translation, correction, trimming, or rewriting.
 */
function render_preserved_text($value): string {
    return nl2br(escape_html((string)$value), false);
}

/**
 * Normalize only the platform line ending for a user text input.
 * Leading/trailing spaces and all Unicode characters remain unchanged.
 */
function preserve_text_input($value, string $fallback = ''): string {
    $text = str_replace(["\r\n", "\r"], "\n", (string)$value);
    return trim($text) === '' ? $fallback : $text;
}

/**
 * Resolve existing wedding names by presentation role without mutating tenant data.
 * Welcome/hero slots use nicknames; post-opening formal slots use full names.
 */
function theme_semantic_names(array $config): array {
    $wedding = is_array($config['wedding'] ?? null) ? $config['wedding'] : [];
    $brideFull = trim((string)($wedding['bride_name'] ?? ''));
    $groomFull = trim((string)($wedding['groom_name'] ?? ''));
    $brideNick = trim((string)($wedding['bride_nickname'] ?? ''));
    $groomNick = trim((string)($wedding['groom_nickname'] ?? ''));

    $brideFull = $brideFull !== '' ? $brideFull : $brideNick;
    $groomFull = $groomFull !== '' ? $groomFull : $groomNick;
    $brideNick = $brideNick !== '' ? $brideNick : $brideFull;
    $groomNick = $groomNick !== '' ? $groomNick : $groomFull;

    return [
        'bride_full_name' => $brideFull !== '' ? $brideFull : 'Mempelai Wanita',
        'groom_full_name' => $groomFull !== '' ? $groomFull : 'Mempelai Pria',
        'bride_nickname' => $brideNick !== '' ? $brideNick : ($brideFull !== '' ? $brideFull : 'Mempelai Wanita'),
        'groom_nickname' => $groomNick !== '' ? $groomNick : ($groomFull !== '' ? $groomFull : 'Mempelai Pria'),
    ];
}

/**
 * Resolve the short religious/opening greeting shown by each built-in preset.
 * Existing config files receive the preset default through config normalization;
 * non-empty admin values always win and preserve user-entered Unicode/newlines.
 */
function theme_opening_greeting(array $config, string $presetKey): string {
    $defaults = [
        'dewankl' => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ\nAssalamu’alaikum Warahmatullahi Wabarakatuh",
        'shubh-vivah' => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ\nAssalamu’alaikum Warahmatullahi Wabarakatuh",
        'yami-buzzy' => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ\nAssalamu’alaikum Warahmatullahi Wabarakatuh",
        'rainier' => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ\nAssalamu’alaikum Warahmatullahi Wabarakatuh",
        'archak' => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ\nAssalamu’alaikum Warahmatullahi Wabarakatuh",
        'parang' => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ\nAssalamu’alaikum Warahmatullahi Wabarakatuh",
        'pawiwahan' => 'OM Swastiastu',
        'custom' => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ\nAssalamu’alaikum Warahmatullahi Wabarakatuh",
    ];
    $fallback = $defaults[$presetKey] ?? $defaults['custom'];
    $configured = function_exists('get_theme_option')
        ? get_theme_option($config, $presetKey, 'opening_greeting', $fallback)
        : ($config['theme_options'][$presetKey]['opening_greeting'] ?? $fallback);
    $configured = (string)$configured;
    return trim($configured) === '' ? $fallback : $configured;
}

/**
 * Normalize the public guest query value without trusting it as HTML.
 * The guest link format remains compatible with the existing `?to=` flow.
 */
function normalize_guest_name(string $value): string {
    $value = trim($value);
    if ($value === '') return '';
    if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) return '';
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    if (function_exists('mb_substr')) return mb_substr($value, 0, 120, 'UTF-8');
    return substr($value, 0, 120);
}

/** Resolve the global personalized guest identity for the active frontend. */
function resolve_guest_name(array $config = []): string {
    $queryValue = $_GET['to'] ?? ($_GET['guest'] ?? ($_GET['name'] ?? ($_GET['n'] ?? '')));
    return normalize_guest_name((string)$queryValue);
}

/** Resolve visible admin controls without collapsing global and theme-specific capabilities. */
function theme_admin_capabilities_for_config(array $config): array {
    $mode = function_exists('get_theme_mode') ? get_theme_mode($config) : 'custom';
    $global = function_exists('theme_contract_global_admin_capabilities')
        ? theme_contract_global_admin_capabilities()
        : ['preset_selector', 'guest_links'];
    $specific = $mode === 'custom'
        ? ['wedding', 'parents', 'schedule', 'countdown', 'sections', 'theme', 'custom_css', 'media', 'story', 'gallery', 'cover', 'background', 'music', 'gift', 'dresscode', 'maps', 'seo', 'whatsapp', 'rsvp', 'backup', 'settings']
        : (function_exists('theme_contract_admin_capabilities') ? theme_contract_admin_capabilities(resolve_theme_preset_key($config)) : []);
    return array_values(array_unique(array_merge($global, $specific)));
}

/** Resolve visual capability metadata for the active preset. */
function theme_visual_capabilities_for_config(array $config, ?string $presetKey = null): array {
    $mode = function_exists('get_theme_mode') ? get_theme_mode($config) : 'custom';
    $presetKey = $presetKey ?: ($mode === 'custom' ? 'custom' : resolve_theme_preset_key($config));
    if ($presetKey === 'custom') {
        return [
            'accent_color' => ['type' => 'color', 'label' => 'Warna Aksen', 'description' => 'Warna utama untuk tombol, tautan, dan detail aksen.', 'default' => '#c84c47', 'palette' => theme_color_palette()],
            'background_color' => ['type' => 'color', 'label' => 'Warna Latar', 'description' => 'Warna dasar halaman ketika tidak ada gambar latar.', 'default' => '#fff8f2', 'palette' => theme_color_palette()],
            'paper_color' => ['type' => 'color', 'label' => 'Warna Permukaan', 'description' => 'Warna kartu dan permukaan konten.', 'default' => '#ffffff', 'palette' => theme_color_palette()],
            'text_color' => ['type' => 'color', 'label' => 'Warna Teks', 'description' => 'Warna teks utama.', 'default' => '#2f2424', 'palette' => theme_color_palette()],
            'heading_color' => ['type' => 'color', 'label' => 'Warna Judul', 'description' => 'Warna nama pasangan dan judul section.', 'default' => '#2f2424', 'palette' => theme_color_palette()],
            'muted_color' => ['type' => 'color', 'label' => 'Warna Teks Sekunder', 'description' => 'Warna label, keterangan, dan metadata.', 'default' => '#806f66', 'palette' => theme_color_palette()],
            'link_color' => ['type' => 'color', 'label' => 'Warna Tautan', 'description' => 'Warna tautan dan aksi sekunder.', 'default' => '#c84c47', 'palette' => theme_color_palette()],
            'heading_font' => ['type' => 'font', 'label' => 'Font Judul', 'description' => 'Font untuk judul dan heading.', 'default' => 'Playfair Display, serif', 'options' => theme_font_catalog('heading')],
            'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font yang mudah dibaca untuk isi, jadwal, dan form.', 'default' => 'Lato, sans-serif', 'options' => theme_font_catalog('body')],
            'hero_background' => ['type' => 'image', 'label' => 'Latar Hero', 'description' => 'Path media atau URL gambar hero. Kosongkan untuk memakai fallback cover.', 'default' => ''],
            'hero_overlay' => ['type' => 'range', 'label' => 'Overlay Hero', 'description' => 'Kekuatan overlay gelap di atas gambar hero.', 'default' => '0.45', 'min' => '0', 'max' => '0.85', 'step' => '0.05'],
            'hero_title_scale' => ['type' => 'range', 'label' => 'Skala Judul Hero', 'description' => 'Skala relatif judul utama hero.', 'default' => '1', 'min' => '0.85', 'max' => '1.2', 'step' => '0.05'],
            'section_background_home' => ['type' => 'image', 'label' => 'Latar Beranda', 'description' => 'Gambar latar untuk bagian pembuka atau undangan.', 'default' => ''],
            'section_background_event' => ['type' => 'image', 'label' => 'Latar Acara', 'description' => 'Gambar latar untuk jadwal dan detail acara.', 'default' => ''],
            'section_background_story' => ['type' => 'image', 'label' => 'Latar Cerita', 'description' => 'Gambar latar untuk cerita perjalanan cinta.', 'default' => ''],
            'section_background_gallery' => ['type' => 'image', 'label' => 'Latar Galeri', 'description' => 'Gambar latar untuk galeri foto.', 'default' => ''],
            'section_background_location' => ['type' => 'image', 'label' => 'Latar Lokasi', 'description' => 'Gambar latar untuk alamat dan peta acara.', 'default' => ''],
            'section_background_gift' => ['type' => 'image', 'label' => 'Latar Hadiah', 'description' => 'Gambar latar untuk amplop digital atau hadiah.', 'default' => ''],
            'section_background_rsvp' => ['type' => 'image', 'label' => 'Latar Konfirmasi Kehadiran', 'description' => 'Gambar latar untuk formulir konfirmasi kehadiran.', 'default' => ''],
        ];
    }
    $registry = function_exists('theme_registry') ? theme_registry() : [];
    return (array)($registry[$presetKey]['visual_capabilities'] ?? []);
}

/** Resolve per-preset visual defaults and stored overrides without deleting hidden values. */
function theme_visual_values_for_config(array $config, ?string $presetKey = null): array {
    $mode = function_exists('get_theme_mode') ? get_theme_mode($config) : 'custom';
    $presetKey = $presetKey ?: ($mode === 'custom' ? 'custom' : resolve_theme_preset_key($config));
    $schema = theme_visual_capabilities_for_config($config, $presetKey);
    $defaults = [];
    foreach ($schema as $key => $definition) {
        if (array_key_exists('default', $definition)) $defaults[$key] = $definition['default'];
    }
    $stored = $config['theme_visuals'][$presetKey] ?? [];
    if ($presetKey === 'rainier' && is_array($stored)) {
        if (!array_key_exists('accent_color', $stored) && !empty($config['theme_options']['rainier']['hero_accent_color'])) {
            $defaults['accent_color'] = (string)$config['theme_options']['rainier']['hero_accent_color'];
        }
        if (!array_key_exists('glass_opacity', $stored) && isset($config['theme_options']['rainier']['glass_opacity'])) {
            $defaults['glass_opacity'] = (string)$config['theme_options']['rainier']['glass_opacity'];
        }
    }
    $resolved = $defaults;
    if (is_array($stored)) {
        foreach ($stored as $key => $value) {
            if (!isset($schema[$key])) continue;
            $validated = validate_theme_visual_value($value, $schema[$key]);
            if ($validated !== null) $resolved[$key] = $validated;
        }
    }
    return $resolved;
}

/** Clear only the selected preset's visual overrides; hidden preset values remain untouched. */
function reset_theme_visual_overrides(array &$config, string $presetKey): void {
    $presetKey = trim($presetKey);
    if ($presetKey === '') return;
    if (!isset($config['theme_visuals']) || !is_array($config['theme_visuals'])) {
        $config['theme_visuals'] = [];
    }
    $config['theme_visuals'][$presetKey] = [];
}

/** Clear only one visual override, preserving every other setting for the preset. */
function reset_theme_visual_override(array &$config, string $presetKey, string $visualKey): void {
    $presetKey = trim($presetKey);
    $visualKey = trim($visualKey);
    if ($presetKey === '' || $visualKey === '') return;
    if (!isset($config['theme_visuals'][$presetKey]) || !is_array($config['theme_visuals'][$presetKey])) return;
    unset($config['theme_visuals'][$presetKey][$visualKey]);
}

/** Build the CMS-native Custom adapter without changing its section markup. */
function theme_custom_visual_style(array $config): string {
    $visuals = theme_visual_values_for_config($config, 'custom');
    $accent = (string)($visuals['accent_color'] ?? '#c84c47');
    $background = (string)($visuals['background_color'] ?? '#fff8f2');
    $paper = (string)($visuals['paper_color'] ?? '#ffffff');
    $text = (string)($visuals['text_color'] ?? '#2f2424');
    $headingColor = (string)($visuals['heading_color'] ?? $text);
    $muted = (string)($visuals['muted_color'] ?? '#806f66');
    $link = (string)($visuals['link_color'] ?? $accent);
    $heading = (string)($visuals['heading_font'] ?? 'Playfair Display, serif');
    $body = (string)($visuals['body_font'] ?? 'Lato, sans-serif');
    $overlay = (float)($visuals['hero_overlay'] ?? '0.45');
    $titleScale = (float)($visuals['hero_title_scale'] ?? '1');
    $heroPath = (string)($visuals['hero_background'] ?? '');
    $heroRule = $heroPath !== '' ? '#hero{--hero-bg:' . theme_visual_css_url($heroPath) . '!important;}' : '';
    $overlayStart = 'rgba(22,12,10,' . $overlay . ')';
    $overlayMid = 'rgba(40,20,18,' . min(0.95, $overlay + 0.10) . ')';
    $overlayEnd = 'rgba(55,28,24,' . min(1.0, $overlay + 0.25) . ')';
    return '<style id="cms-custom-visual">:root{--primary:' . $accent . ';--accent:' . $accent . ';--link:' . $link . ';--heading-color:' . $headingColor . ';--muted:' . $muted . ';--bg:' . $background . ';--paper:' . $paper . ';--paper-solid:' . $paper . ';--text:' . $text . ';--font-heading:' . $heading . ';--font-body:' . $body . ';--hero-title-scale:' . $titleScale . ';--hero-overlay-start:' . $overlayStart . ';--hero-overlay-mid:' . $overlayMid . ';--hero-overlay-end:' . $overlayEnd . ';}' . $heroRule . '</style>';
}

/** Validate one visual value against its preset-declared schema. */
function theme_visual_public_path(string $path): string {
    $path = trim($path);
    if ($path === '') return 'data:,';
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string)(parse_url($path, PHP_URL_SCHEME) ?? ''));
        if (in_array($scheme, ['http', 'https'], true)) return $path;
    }
    return public_path($path);
}

function theme_visual_css_url(string $path): string {
    $url = theme_visual_public_path($path);
    $url = str_replace(['\\', '"', '(', ')'], ['\\\\', '\\"', '\\(', '\\)'], $url);
    return 'url("' . $url . '")';
}

/** Accept only external HTTP(S) images or existing images from the canonical uploads library. */
function theme_visual_image_reference_is_canonical(string $value): bool {
    $value = trim($value);
    if ($value === '') return true;
    if (filter_var($value, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string)(parse_url($value, PHP_URL_SCHEME) ?? ''));
        return in_array($scheme, ['http', 'https'], true);
    }
    $normalized = normalize_media_relative_path($value);
    if ($normalized === null || !media_path_is_safe_storage($normalized)) return false;
    $absolute = ROOT_DIR . '/' . $normalized;
    if (!is_file($absolute)) return false;
    $extension = strtolower((string)pathinfo($absolute, PATHINFO_EXTENSION));
    return $extension === 'webp' && safe_image_mime($absolute) === 'image/webp';
}

/**
 * Return every user-facing media target that can be assigned from the File Manager.
 * The target list is derived from the active preset schema so new source-backed
 * image capabilities cannot silently exist only in the frontend/theme editor.
 */
function media_manager_target_definitions(array $config, ?string $presetKey = null): array {
    $presetKey = $presetKey ?: resolve_theme_preset_key($config);
    $targets = [
        'media.cover' => ['label' => 'Cover / foto utama', 'type' => 'image', 'fallback' => 'Bawaan preset'],
        'media.bride_photo' => ['label' => 'Foto mempelai wanita', 'type' => 'image', 'fallback' => 'Kosongkan untuk bawaan preset'],
        'media.groom_photo' => ['label' => 'Foto mempelai pria', 'type' => 'image', 'fallback' => 'Kosongkan untuk bawaan preset'],
        'media.couple_photo' => ['label' => 'Foto pasangan', 'type' => 'image', 'fallback' => 'Kosongkan untuk bawaan preset'],
        'media.music' => ['label' => 'Musik latar', 'type' => 'audio', 'fallback' => 'Musik bawaan preset'],
        'media.love_story_video' => ['label' => 'Video cerita cinta', 'type' => 'video', 'fallback' => 'Kosongkan untuk bawaan preset'],
        'media.background_hero' => ['label' => 'Latar hero / pembuka', 'type' => 'image', 'fallback' => 'Latar bawaan preset'],
        'media.background_sections.0' => ['label' => 'Latar section 1', 'type' => 'image', 'fallback' => 'Latar bawaan preset'],
        'media.background_sections.1' => ['label' => 'Latar section 2', 'type' => 'image', 'fallback' => 'Latar bawaan preset'],
        'media.background_sections.2' => ['label' => 'Latar section 3', 'type' => 'image', 'fallback' => 'Latar bawaan preset'],
        'gift.qris_image' => ['label' => 'QRIS / hadiah digital', 'type' => 'image', 'fallback' => 'QRIS tidak ditampilkan'],
        'site.open_graph_image' => ['label' => 'Gambar Open Graph', 'type' => 'image', 'fallback' => 'Gambar sosial bawaan'],
    ];
    $visualSchema = theme_visual_capabilities_for_config($config, $presetKey);
    foreach ($visualSchema as $visualKey => $definition) {
        if (($definition['type'] ?? '') !== 'image') continue;
        $target = 'theme_visuals.' . $presetKey . '.' . $visualKey;
        $targets[$target] = [
            'label' => 'Tampilan ' . ($definition['label'] ?? ucwords(str_replace('_', ' ', $visualKey))),
            'type' => 'image',
            'fallback' => 'Bawaan ' . ($presetKey === 'custom' ? 'CMS' : $presetKey),
        ];
    }
    $presetSchema = (array)(theme_registry()[$presetKey]['schema'] ?? []);
    foreach ($presetSchema as $optionKey => $definition) {
        if (($definition['type'] ?? '') !== 'image') continue;
        $target = 'theme_options.' . $presetKey . '.' . $optionKey;
        $targets[$target] = [
            'label' => 'Aset ' . ($definition['label'] ?? ucwords(str_replace('_', ' ', $optionKey))),
            'type' => 'image',
            'fallback' => 'Bawaan ' . ($presetKey === 'custom' ? 'CMS' : $presetKey),
        ];
    }
    return $targets;
}

/**
 * Filter only the targets supported by the active CMS/preset capabilities.
 * This is a presentation filter; the write path validates the same contract
 * server-side through media_manager_set_target().
 */
function media_manager_target_is_visible(array $config, string $target, array $definition, ?string $presetKey = null): bool {
    $presetKey = $presetKey ?: resolve_theme_preset_key($config);
    $adminCapabilities = function_exists('theme_admin_capabilities_for_config')
        ? theme_admin_capabilities_for_config($config)
        : [];
    $hasAdminCapability = static fn(string $capability): bool => in_array($capability, $adminCapabilities, true);
    $mediaRoles = function_exists('theme_contract_media_roles')
        ? theme_contract_media_roles($presetKey)
        : [];
    $hasMediaRole = static fn(string $role): bool => in_array($role, $mediaRoles, true);

    if (str_starts_with($target, 'theme_visuals.' . $presetKey . '.')) {
        $visualKey = substr($target, strlen('theme_visuals.' . $presetKey . '.'));
        $schema = theme_visual_capabilities_for_config($config, $presetKey);
        return isset($schema[$visualKey]) && (($schema[$visualKey]['type'] ?? '') === 'image');
    }
    if (str_starts_with($target, 'theme_options.' . $presetKey . '.')) {
        $optionKey = substr($target, strlen('theme_options.' . $presetKey . '.'));
        $schema = (array)(theme_registry()[$presetKey]['schema'] ?? []);
        return isset($schema[$optionKey]) && (($schema[$optionKey]['type'] ?? '') === 'image');
    }

    return match ($target) {
        'media.cover' => $hasMediaRole('cover'),
        'media.bride_photo' => $hasMediaRole('bride_photo'),
        'media.groom_photo' => $hasMediaRole('groom_photo'),
        'media.couple_photo' => $hasMediaRole('couple_photo'),
        'media.love_story_video' => $hasMediaRole('love_story_video'),
        'media.music' => $hasAdminCapability('music'),
        'media.background_hero', 'media.background_sections.0', 'media.background_sections.1', 'media.background_sections.2' => $hasAdminCapability('background') || in_array('background', (array)(theme_contract_presentation_capabilities($presetKey)), true),
        'gift.qris_image' => $hasAdminCapability('gift'),
        'site.open_graph_image' => $hasAdminCapability('seo'),
        default => false,
    };
}

function media_manager_visible_target_definitions(array $config, ?string $presetKey = null): array {
    $presetKey = $presetKey ?: resolve_theme_preset_key($config);
    $targets = media_manager_target_definitions($config, $presetKey);
    return array_filter(
        $targets,
        static fn(array $definition, string $target): bool => media_manager_target_is_visible($config, $target, $definition, $presetKey),
        ARRAY_FILTER_USE_BOTH
    );
}

function media_manager_target_value(array $config, string $target): string {
    $value = $config;
    foreach (explode('.', trim($target, '.')) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) return '';
        $value = $value[$part];
    }
    return is_scalar($value) ? (string)$value : '';
}

function media_manager_set_target(array &$config, string $target, string $value): bool {
    $targetDefinitions = media_manager_visible_target_definitions($config);
    if (!isset($targetDefinitions[$target])) return false;
    $definition = $targetDefinitions[$target];
    if ($value !== '') {
        if (($definition['type'] ?? 'image') === 'image') {
            if (!theme_visual_image_reference_is_canonical($value)) return false;
        } elseif (!tenant_media_reference_is_safe($value)) {
            return false;
        }
    }
    $parts = explode('.', trim($target, '.'));
    if (!$parts || in_array('', $parts, true)) return false;
    $cursor =& $config;
    $last = array_pop($parts);
    foreach ($parts as $part) {
        if (!isset($cursor[$part]) || !is_array($cursor[$part])) $cursor[$part] = [];
        $cursor =& $cursor[$part];
    }
    $cursor[$last] = $value;
    return true;
}

function media_manager_clear_target(array &$config, string $target): bool {
    return media_manager_set_target($config, $target, '');
}

function validate_theme_visual_value($value, array $definition) {
    $type = (string)($definition['type'] ?? 'text');
    if (is_array($value)) return null;
    $value = str_replace(["\r\n", "\r"], "\n", (string)$value);
    if ($type === 'color') {
        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value) ? strtolower($value) : null;
    }
    if ($type === 'range' || $type === 'number') {
        if (!is_numeric($value)) return null;
        $number = (float)$value;
        $min = isset($definition['min']) ? (float)$definition['min'] : -INF;
        $max = isset($definition['max']) ? (float)$definition['max'] : INF;
        if ($number < $min || $number > $max) return null;
        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.') ?: '0';
    }
    if (!empty($definition['options']) && !array_key_exists($value, (array)$definition['options'])) return null;
    if ($type === 'image' && $value !== '') {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string)(parse_url($value, PHP_URL_SCHEME) ?? ''));
            if (!in_array($scheme, ['http', 'https'], true)) return null;
        } elseif (normalize_media_relative_path($value) === null) {
            return null;
        }
    }
    return $value;
}

/**
 * Switch the active presentation mode without resetting unrelated CMS data.
 * The built-in renderer owns its own template values; this helper only changes
 * the global selector state and deliberately preserves every other config key.
 */
function switch_active_theme_preset_config(array $config, string $selectedPreset): ?array {
    $selectedPreset = trim($selectedPreset);
    if ($selectedPreset === '') return null;
    if (!array_key_exists('theme_custom', $config) || !is_array($config['theme_custom']) || get_theme_mode($config) === 'custom') {
        $config['theme_custom'] = theme_custom_config($config);
    }
    if ($selectedPreset === 'custom') {
        $config['theme'] = theme_custom_config($config);
        return $config;
    }
    if (!array_key_exists($selectedPreset, theme_presets())) return null;
    $config['theme']['mode'] = 'preset';
    $config['theme']['theme_preset'] = $selectedPreset;
    return $config;
}

/** Build a safe personalized invitation URL using the existing `?to=` convention. */
function build_guest_invitation_url(string $baseUrl, string $guestName): string {
    $name = normalize_guest_name($guestName);
    $base = trim($baseUrl);
    if ($name === '' || $base === '') return '';
    if ($base[0] === '/') {
        if (str_starts_with($base, '//')) return '';
    } else {
        $parts = parse_url($base);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || empty($parts['host'])) return '';
    }
    $normalizedBase = rtrim($base, '/');
    if ($normalizedBase === '' && $base[0] === '/') $normalizedBase = '/';
    if (!str_contains($normalizedBase, '?') && $normalizedBase !== '/' && !str_ends_with($normalizedBase, '/')) $normalizedBase .= '/';
    $separator = str_contains($normalizedBase, '?') ? '&' : '?';
    return $normalizedBase . $separator . 'to=' . rawurlencode($name);
}

/** Get theme asset URL. */
function get_theme_asset_url(string $themeKey, string $filename): string {
    return '/themes/' . preg_replace('/[^a-z0-9_-]/i', '', $themeKey) . '/' . $filename;
}

/** Get a configured section entry by normalized ID. */
function get_section_entry(array $config, string $sectionId): ?array {
    $targetId = normalize_section_id($sectionId);
    if (!isset($config['sections']) || !is_array($config['sections'])) return null;
    foreach ($config['sections'] as $section) {
        if (normalize_section_id((string)($section['id'] ?? '')) === $targetId) {
            return is_array($section) ? $section : null;
        }
    }
    return null;
}

/** Check if a section is enabled. */
function is_section_enabled(array $config, string $sectionId): bool {
    $section = get_section_entry($config, $sectionId);
    return $section === null ? true : !empty($section['enabled']);
}

/** Resolve a section title. */
function get_section_title(array $config, string $sectionId, string $defaultTitle): string {
    $section = get_section_entry($config, $sectionId);
    if ($section === null) return $defaultTitle;
    return !empty($section['custom_title']) ? (string)$section['custom_title'] : (string)($section['title'] ?? $defaultTitle);
}

/** Resolve a section subtitle. */
function get_section_subtitle(array $config, string $sectionId, string $defaultSubtitle): string {
    $section = get_section_entry($config, $sectionId);
    if ($section === null) return $defaultSubtitle;
    return !empty($section['custom_subtitle']) ? (string)$section['custom_subtitle'] : (string)($section['subtitle'] ?? $defaultSubtitle);
}

/** Resolve platform attribution metadata without reading tenant-editable configuration. */
function cms_attribution_metadata(string $presetKey): array {
    if (function_exists('theme_contract_attribution')) {
        return theme_contract_attribution($presetKey);
    }
    return [
        'creator' => null,
        'cms_credit' => ['name' => 'Febru & Andi', 'role' => 'CMS Designer & Adapter'],
        'source_credit_present' => false,
        'same_creator_as_cms' => false,
    ];
}

/** Render the public attribution block for the active preset exactly once. */
function cms_attribution_markup(string $presetKey): string {
    $metadata = cms_attribution_metadata($presetKey);
    $creator = is_array($metadata['creator'] ?? null) ? $metadata['creator'] : null;
    $cmsCredit = is_array($metadata['cms_credit'] ?? null) ? $metadata['cms_credit'] : [];
    $creatorName = trim((string)($creator['name'] ?? ''));
    $cmsName = trim((string)($cmsCredit['name'] ?? 'Febru & Andi'));
    $sameCreator = !empty($metadata['same_creator_as_cms']) || ($creatorName !== '' && strcasecmp($creatorName, $cmsName) === 0);
    $lines = [];

    $linkedName = static function (string $name, string $url): string {
        $nameHtml = escape_html($name);
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) return $nameHtml;
        $scheme = strtolower((string)(parse_url($url, PHP_URL_SCHEME) ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) return $nameHtml;
        return '<a href="' . escape_html($url) . '" target="_blank" rel="noopener noreferrer">' . $nameHtml . '</a>';
    };

    if ($sameCreator && $creatorName !== '') {
        $lines[] = '<span class="cms-attribution__line cms-attribution__line--owner">Didesain oleh <strong>' . $linkedName($creatorName, (string)($creator['url'] ?? '')) . '</strong></span>';
    } else {
        if ($creatorName !== '' && empty($metadata['source_credit_present'])) {
            $lines[] = '<span class="cms-attribution__line cms-attribution__line--creator">Dibuat dengan hati oleh <strong>' . $linkedName($creatorName, (string)($creator['url'] ?? '')) . '</strong></span>';
        }
        if ($cmsName !== '') {
            $lines[] = '<span class="cms-attribution__line cms-attribution__line--cms">CMS didesain oleh <strong>' . escape_html($cmsName) . '</strong></span>';
        }
    }

    if ($lines === []) return '';
    return '<div id="cms-attribution" class="cms-attribution" data-platform-attribution="1" data-preset="' . escape_html($presetKey) . '" aria-label="Atribusi desain">' . implode('', $lines) . '</div>';
}

/** Render a small responsive style block while leaving each preset's visual language in control. */
function cms_attribution_style(): string {
    return '<style id="cms-attribution-style">.cms-attribution{box-sizing:border-box;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.16rem;width:100%;max-width:100%;margin:1rem auto 0;padding:.55rem 1rem .2rem;color:inherit;font:inherit;font-size:clamp(.68rem,1.4vw,.78rem);line-height:1.45;text-align:center;opacity:.78;overflow-wrap:anywhere;position:relative;z-index:2}.cms-attribution__line{display:block;max-width:100%}.cms-attribution strong{font-weight:650}.cms-attribution a{color:inherit;text-decoration:underline;text-decoration-thickness:1px;text-underline-offset:.16em}.cms-attribution a:hover{opacity:.86}@media (max-width:576px){.cms-attribution{margin-top:.8rem;padding:.5rem .75rem .15rem;font-size:.7rem;line-height:1.4}}@media (prefers-reduced-motion:reduce){.cms-attribution *{transition:none!important}}</style>';
}

/** Insert platform attribution before the public footer closes, idempotently. */
function append_cms_attribution(string $html, string $presetKey): string {
    if (stripos($html, 'id="cms-attribution"') !== false) return $html;
    $markup = cms_attribution_markup($presetKey);
    if ($markup === '') return $html;
    if (stripos($html, '</footer>') !== false) {
        return preg_replace('/<\\/footer>/i', $markup . '</footer>', $html, 1) ?? $html;
    }
    return preg_replace('/<\\/body>/i', $markup . '</body>', $html, 1) ?? ($html . $markup);
}

/**
 * Preserve site-level SEO schema and the existing CMS theme live-preview bridge
 * without making index.php a second frontend renderer.
 */
function finalize_theme_output(string $html, array $config): string {
    $schema = trim((string)($config['site']['schema'] ?? ''));
    if ($schema !== '' && stripos($html, 'application/ld+json') === false) {
        $schemaBlock = "\n<script type=\"application/ld+json\">\n" . $schema . "\n</script>\n";
        $html = preg_replace('/<\/head>/i', $schemaBlock . '</head>', $html, 1) ?? $html;
    }

    $activePresetKey = resolve_theme_preset_key($config);
    if (stripos($html, 'id="cms-attribution-style"') === false) {
        $html = preg_replace('/<\\/head>/i', cms_attribution_style() . '</head>', $html, 1) ?? $html;
    }
    $html = append_cms_attribution($html, $activePresetKey);

    $previewScript = <<<'HTML'
<script>
(function () {
  const variableMap = {
    primary_color: '--primary', secondary_color: '--secondary', accent_color: '--accent',
    background_color: '--bg', paper_color: '--paper', muted_color: '--muted',
    text_color: '--text', link_color: '--link', shadow: '--shadow', border_radius: '--radius',
    container_width: '--container-width', section_spacing: '--section-spacing',
    heading_font: '--font-heading', body_font: '--font-body', font_size_base: '--font-size-base'
  };
  const classValuesFor = function (theme) {
    return {
      'theme-button-': theme.button_style || 'rounded',
      'theme-navbar-': theme.navbar_style || 'transparent',
      'theme-card-': theme.card_style || 'elevated',
      'theme-footer-': theme.footer_style || 'centered',
      'theme-animation-': theme.animation_enabled ? 'on' : 'off'
    };
  };
  const visualMap = {
    'shubh-vivah': {accent_color: '--shubh-accent', heading_color: '--shubh-heading-color', text_color: '--shubh-ink', muted_color: '--shubh-muted', link_color: '--shubh-link', heading_font: '--shubh-heading', body_font: '--shubh-body', hero_overlay: '--shubh-overlay', hero_background: '--shubh-hero-bg', section_background_home: '--shubh-home-bg', section_background_event: '--shubh-event-bg', section_background_gallery: '--shubh-gallery-bg', section_background_rsvp: '--shubh-rsvp-bg', ornament_left: '--shubh-left', ornament_right: '--shubh-right'},
    'yami-buzzy': {accent_color: '--yami-accent', heading_color: '--yami-heading-color', text_color: '--yami-ink', muted_color: '--yami-muted', link_color: '--yami-link', heading_font: '--yami-heading', body_font: '--yami-body', hero_overlay: '--yami-overlay', hero_background: '--yami-hero-bg', welcome_background: '--yami-welcome-bg', section_background_home: '--yami-home-bg', section_background_couple: '--yami-couple-bg', section_background_event: '--yami-event-bg', section_background_story: '--yami-story-bg', section_background_gallery: '--yami-gallery-bg', section_background_video: '--yami-video-bg', section_background_gift: '--yami-gift-bg', section_background_invitation: '--yami-invitation-bg', section_background_rsvp: '--yami-rsvp-bg', section_background_closing: '--yami-closing-bg'},
    rainier: {accent_color: '--primary', heading_color: '--rainier-heading-color', text_color: '--text', muted_color: '--muted', link_color: '--rainier-link', heading_font: '--font-heading', body_font: '--font-body', glass_opacity: '--cms-rainier-glass-opacity', hero_background: '--cms-rainier-hero-bg', section_background_event_details: '--cms-rainier-event-bg', section_background_schedule: '--cms-rainier-schedule-bg', section_background_quotes: '--cms-rainier-quotes-bg', section_background_rsvp: '--cms-rainier-rsvp-bg'},
    archak: {accent_color: '--cms-archak-accent', heading_color: '--cms-archak-heading-color', text_color: '--cms-archak-text', muted_color: '--cms-archak-muted', link_color: '--cms-archak-link', heading_font: '--cms-archak-heading', body_font: '--cms-archak-body', hero_title_scale: '--cms-archak-title-scale', hero_background: '--cms-archak-hero-bg', section_background_timeline: '--cms-archak-timeline-bg', section_background_gallery: '--cms-archak-gallery-bg', section_background_stay: '--cms-archak-stay-bg', section_background_registry: '--cms-archak-registry-bg', header_badge: '--cms-archak-badge'},
    parang: {accent_color: '--parang-gold', heading_color: '--parang-heading-color', text_color: '--parang-text', muted_color: '--parang-muted', link_color: '--parang-link', heading_font: '--parang-heading', body_font: '--parang-body', hero_background: '--cms-parang-bg', section_background_home: '--cms-parang-home-bg', section_background_gallery: '--cms-parang-gallery-bg', section_background_location: '--cms-parang-location-bg', ornament_left: '--cms-parang-left', ornament_right: '--cms-parang-right', ornament_top: '--cms-parang-top', ornament_top_offset_y: '--cms-parang-top-offset-y', ornament_side_offset_x: '--cms-parang-side-offset-x', ornament_side_offset_y: '--cms-parang-side-offset-y', ornament_side_size: '--cms-parang-side-size'},
    pawiwahan: {accent_color: '--pawiwahan-accent', heading_color: '--pawiwahan-heading-color', text_color: '--pawiwahan-text', muted_color: '--pawiwahan-muted', link_color: '--pawiwahan-link', heading_font: '--pawiwahan-heading', body_font: '--pawiwahan-body', hero_background: '--pawiwahan-hero-bg', welcome_background: '--pawiwahan-welcome-bg', section_background_gallery: '--pawiwahan-gallery-bg', section_background_location: '--pawiwahan-location-bg', section_background_gift: '--pawiwahan-gift-bg', section_background_messages: '--pawiwahan-messages-bg'},
    dewankl: {accent_color: '--cms-dewana-accent', heading_color: '--cms-dewana-heading-color', text_color: '--cms-dewana-text', muted_color: '--cms-dewana-muted', link_color: '--cms-dewana-link', heading_font: '--cms-dewana-heading', body_font: '--cms-dewana-body', hero_overlay: '--cms-dewana-overlay', hero_background: '--cms-dewana-hero-bg', welcome_background: '--cms-dewana-welcome-bg', section_background_home: '--cms-dewana-home-bg', section_background_bride: '--cms-dewana-bride-bg', section_background_wedding_date: '--cms-dewana-date-bg', section_background_gallery: '--cms-dewana-gallery-bg', section_background_love_gift: '--cms-dewana-gift-bg', section_background_comment: '--cms-dewana-comment-bg'},
    custom: {accent_color: '--accent', background_color: '--bg', paper_color: '--paper', heading_color: '--heading-color', text_color: '--text', muted_color: '--muted', link_color: '--link', heading_font: '--font-heading', body_font: '--font-body', hero_overlay: '--hero-overlay', hero_title_scale: '--hero-title-scale', hero_background: '--hero-bg', section_background_home: '--custom-home-bg', section_background_event: '--custom-event-bg', section_background_story: '--custom-story-bg', section_background_gallery: '--custom-gallery-bg', section_background_location: '--custom-location-bg', section_background_gift: '--custom-gift-bg', section_background_rsvp: '--custom-rsvp-bg'}
  };
  const applyVisualPreview = function (theme) {
    const preset = theme.theme_preset || 'custom';
    const values = theme.visuals || {};
    const mapping = visualMap[preset] || visualMap.custom;
    Object.keys(mapping).forEach(function (key) {
      if (values[key] === undefined || values[key] === '') return;
      let value = values[key];
      const imageKeys = Object.keys(mapping).filter(function (key) { return key === 'hero_background' || key === 'welcome_background' || key.indexOf('section_background_') === 0 || key === 'ornament_left' || key === 'ornament_right' || key === 'ornament_top' || key === 'header_badge'; });
      if (imageKeys.indexOf(key) !== -1 && !/^https?:\\/\\//i.test(value) && value.charAt(0) !== '/') value = '/' + value;
      if (imageKeys.indexOf(key) !== -1) value = 'url("' + value.replace(/"/g, '\\\\"') + '")';
      document.documentElement.style.setProperty(mapping[key], value);
    });
    if (preset === 'rainier' && values.glass_opacity !== undefined) {
      const opacity = Math.min(0.9, Math.max(0.2, Number(values.glass_opacity) || 0.4));
      document.documentElement.style.setProperty('--glass-bg', 'rgba(0, 0, 0, ' + opacity + ')');
    }
    if (preset === 'custom' && values.hero_overlay !== undefined) {
      const overlay = Math.min(0.85, Math.max(0, Number(values.hero_overlay) || 0.45));
      document.documentElement.style.setProperty('--hero-overlay-start', 'rgba(22, 12, 10, ' + overlay + ')');
      document.documentElement.style.setProperty('--hero-overlay-mid', 'rgba(40, 20, 18, ' + Math.min(0.95, overlay + 0.10) + ')');
      document.documentElement.style.setProperty('--hero-overlay-end', 'rgba(55, 28, 24, ' + Math.min(1, overlay + 0.25) + ')');
    }
    if (values.hero_background) {
      const raw = values.hero_background;
      const url = /^https?:\\/\\//i.test(raw) || raw.charAt(0) === '/' ? raw : '/' + raw;
      const hero = document.querySelector('.hero, .hero-archak, .hero-section, #hero, .parang-main');
      if (hero) {
        hero.style.setProperty('--cms-preview-hero-background', 'url("' + url.replace(/"/g, '\\\\"') + '")');
        if (preset === 'rainier' || preset === 'custom') hero.style.backgroundImage = 'url("' + url.replace(/"/g, '\\\\"') + '")';
        if (preset === 'parang') document.querySelectorAll('.parang-bg').forEach(function (layer) { layer.style.setProperty('--cms-parang-bg', 'url("' + url.replace(/"/g, '\\\\"') + '")'); });
      }
      if (preset === 'dewankl') document.querySelectorAll('img.bg-cover-home').forEach(function (image) { image.src = url; });
      if (preset === 'rainier') document.querySelectorAll('.hero-background, .hero-slide').forEach(function (layer) { layer.style.backgroundImage = 'url("' + url.replace(/"/g, '\\\\"') + '")'; });
    }
    if (preset === 'archak' && values.header_badge) {
      const badgeUrl = /^https?:\\/\\//i.test(values.header_badge) || values.header_badge.charAt(0) === '/' ? values.header_badge : '/' + values.header_badge;
      document.querySelectorAll('.archak-header-badge').forEach(function (image) { image.src = badgeUrl; });
    }
    if (preset === 'custom') {
      const sectionMap = {section_background_home: '#undangan', section_background_event: '#acara, #countdown-section', section_background_story: '#cerita', section_background_gallery: '#galeri', section_background_location: '#lokasi', section_background_gift: '#amplop', section_background_rsvp: '#rsvp'};
      Object.keys(sectionMap).forEach(function (key) { if (!values[key]) return; const sectionUrl = values[key].charAt(0) === '/' ? values[key] : '/' + values[key]; document.querySelectorAll(sectionMap[key]).forEach(function (section) { section.style.backgroundImage = 'linear-gradient(rgba(255,250,245,.78),rgba(255,250,245,.88)),url("' + sectionUrl.replace(/"/g, '\\\\"') + '")'; section.style.backgroundSize = 'cover'; section.style.backgroundPosition = 'center'; }); });
    }
    if (preset === 'parang') {
      if (values.ornament_left) { const leftUrl = values.ornament_left.charAt(0) === '/' ? values.ornament_left : '/' + values.ornament_left; document.querySelectorAll('.parang-ornament-left').forEach(function (image) { image.src = leftUrl; }); }
      if (values.ornament_right) { const rightUrl = values.ornament_right.charAt(0) === '/' ? values.ornament_right : '/' + values.ornament_right; document.querySelectorAll('.parang-ornament-right').forEach(function (image) { image.src = rightUrl; }); }
      if (values.ornament_top) { const topUrl = values.ornament_top.charAt(0) === '/' ? values.ornament_top : '/' + values.ornament_top; document.querySelectorAll('.parang-ornament-top').forEach(function (image) { image.src = topUrl; }); }
      if (values.ornament_top_offset_y !== undefined) { document.documentElement.style.setProperty('--cms-parang-top-offset-y', Math.min(40, Math.max(-240, Number(values.ornament_top_offset_y) || -128)) + 'px'); }
      if (values.ornament_side_offset_x !== undefined) { document.documentElement.style.setProperty('--cms-parang-side-offset-x', Math.min(40, Math.max(-240, Number(values.ornament_side_offset_x) || -128)) + 'px'); }
      if (values.ornament_side_offset_y !== undefined) { document.documentElement.style.setProperty('--cms-parang-side-offset-y', Math.min(100, Math.max(0, Number(values.ornament_side_offset_y) || 50)) + '%'); }
      if (values.ornament_side_size !== undefined) { document.documentElement.style.setProperty('--cms-parang-side-size', Math.min(480, Math.max(96, Number(values.ornament_side_size) || 256)) + 'px'); }
    }
  };
  window.addEventListener('message', function (event) {
    if (event.origin !== window.location.origin || !event.data || event.data.type !== 'theme-preview:update') return;
    const theme = event.data.theme || {};
    applyVisualPreview(theme);
    Object.keys(variableMap).forEach(function (key) {
      if (theme[key] !== undefined && theme[key] !== '') {
        document.documentElement.style.setProperty(variableMap[key], theme[key]);
        if (key === 'paper_color') document.documentElement.style.setProperty('--paper-solid', theme[key]);
      }
    });

    const classValues = classValuesFor(theme);
    Object.keys(classValues).forEach(function (prefix) {
      Array.from(document.body.classList).forEach(function (name) {
        if (name.indexOf(prefix) === 0) document.body.classList.remove(name);
      });
      document.body.classList.add(prefix + classValues[prefix]);
    });

    const heroSection = document.getElementById('hero');
    if (heroSection) {
      const heroFit = theme.hero_image_fit || 'cover';
      heroSection.style.backgroundSize = heroFit === 'contain' ? 'contain' : (heroFit === 'auto' ? 'auto' : 'cover');
      heroSection.style.backgroundPosition = theme.hero_image_position || 'center';
      heroSection.style.backgroundRepeat = 'no-repeat';
    }

    const mobileLayoutRaw = theme.buttons && theme.buttons.mobile_layout ? theme.buttons.mobile_layout : theme.mobile_layout;
    const mobileLayout = mobileLayoutRaw === 'horizontal' || mobileLayoutRaw === '2-columns' ? 'row' : 'column';
    document.documentElement.style.setProperty('--buttons-mobile-layout', mobileLayout);
  });
})();
</script>
HTML;
    if (stripos($html, 'theme-preview:update') === false) {
        $html = preg_replace('/<\/body>/i', $previewScript . "\n</body>", $html, 1) ?? $html;
    }
    return $html;
}
