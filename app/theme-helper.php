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
 * Resolve the short religious/opening greeting shown by each built-in preset.
 * Existing config files receive the preset default through config normalization;
 * non-empty admin values always win and preserve user-entered Unicode/newlines.
 */
function theme_opening_greeting(array $config, string $presetKey): string {
    $defaults = [
        'dewankl' => 'Assalamualaikum Warahmatullahi Wabarakatuh',
        'shubh-vivah' => 'Bismillahirrahmanirrahim',
        'yami-buzzy' => 'Bismillahirrahmanirrahim',
        'rainier' => 'Bismillahirrahmanirrahim',
        'archak' => 'Bismillahirrahmanirrahim',
        'parang' => 'Bismillahirrahmanirrahim',
        'pawiwahan' => 'OM Swastiastu',
        'custom' => 'Bismillahirrahmanirrahim',
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
            'accent_color' => ['type' => 'color', 'label' => 'Warna Aksen', 'description' => 'Warna utama untuk tombol, tautan, dan detail aksen.', 'default' => '#c84c47'],
            'background_color' => ['type' => 'color', 'label' => 'Warna Latar', 'description' => 'Warna dasar halaman ketika tidak ada gambar latar.', 'default' => '#fff8f2'],
            'paper_color' => ['type' => 'color', 'label' => 'Warna Permukaan', 'description' => 'Warna kartu dan permukaan konten.', 'default' => '#ffffff'],
            'text_color' => ['type' => 'color', 'label' => 'Warna Teks', 'description' => 'Warna teks utama.', 'default' => '#2f2424'],
            'heading_font' => ['type' => 'font', 'label' => 'Font Judul', 'description' => 'Font untuk judul dan heading.', 'default' => 'Playfair Display, serif', 'options' => ['Playfair Display, serif' => 'Playfair Display', 'Cormorant Garamond, serif' => 'Cormorant Garamond', 'Georgia, serif' => 'Georgia', 'Great Vibes, cursive' => 'Great Vibes']],
            'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font yang mudah dibaca untuk isi, jadwal, dan form.', 'default' => 'Lato, sans-serif', 'options' => ['Lato, sans-serif' => 'Lato', 'Inter, sans-serif' => 'Inter', 'Work Sans, sans-serif' => 'Work Sans', 'Poppins, sans-serif' => 'Poppins']],
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
    $heading = (string)($visuals['heading_font'] ?? 'Playfair Display, serif');
    $body = (string)($visuals['body_font'] ?? 'Lato, sans-serif');
    $overlay = (float)($visuals['hero_overlay'] ?? '0.45');
    $titleScale = (float)($visuals['hero_title_scale'] ?? '1');
    $heroPath = (string)($visuals['hero_background'] ?? '');
    $heroRule = $heroPath !== '' ? '#hero{--hero-bg:' . theme_visual_css_url($heroPath) . '!important;}' : '';
    $overlayStart = 'rgba(22,12,10,' . $overlay . ')';
    $overlayMid = 'rgba(40,20,18,' . min(0.95, $overlay + 0.10) . ')';
    $overlayEnd = 'rgba(55,28,24,' . min(1.0, $overlay + 0.25) . ')';
    return '<style id="cms-custom-visual">:root{--primary:' . $accent . ';--accent:' . $accent . ';--link:' . $accent . ';--bg:' . $background . ';--paper:' . $paper . ';--paper-solid:' . $paper . ';--text:' . $text . ';--font-heading:' . $heading . ';--font-body:' . $body . ';--hero-title-scale:' . $titleScale . ';--hero-overlay-start:' . $overlayStart . ';--hero-overlay-mid:' . $overlayMid . ';--hero-overlay-end:' . $overlayEnd . ';}' . $heroRule . '</style>';
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
    if ($normalized === null || !str_starts_with($normalized, 'uploads/')) return false;
    $absolute = ROOT_DIR . '/' . $normalized;
    if (!is_file($absolute)) return false;
    $extension = strtolower((string)pathinfo($absolute, PATHINFO_EXTENSION));
    return $extension === 'webp' && safe_image_mime($absolute) === 'image/webp';
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
    'shubh-vivah': {accent_color: '--shubh-accent', heading_font: '--shubh-heading', body_font: '--shubh-body', hero_overlay: '--shubh-overlay', hero_background: '--shubh-hero-bg', section_background_event: '--shubh-event-bg', section_background_gallery: '--shubh-gallery-bg', section_background_rsvp: '--shubh-rsvp-bg', ornament_left: '--shubh-left', ornament_right: '--shubh-right'},
    'yami-buzzy': {accent_color: '--yami-accent', heading_font: '--yami-heading', body_font: '--yami-body', hero_overlay: '--yami-overlay', hero_background: '--yami-hero-bg', welcome_background: '--yami-welcome-bg', section_background_home: '--yami-home-bg', section_background_couple: '--yami-couple-bg', section_background_event: '--yami-event-bg', section_background_story: '--yami-story-bg', section_background_gallery: '--yami-gallery-bg', section_background_video: '--yami-video-bg', section_background_gift: '--yami-gift-bg', section_background_invitation: '--yami-invitation-bg', section_background_rsvp: '--yami-rsvp-bg', section_background_closing: '--yami-closing-bg'},
    rainier: {accent_color: '--primary', heading_font: '--font-heading', body_font: '--font-body', glass_opacity: '--cms-rainier-glass-opacity', hero_background: '--cms-rainier-hero-bg', section_background_event_details: '--cms-rainier-event-bg', section_background_schedule: '--cms-rainier-schedule-bg', section_background_quotes: '--cms-rainier-quotes-bg', section_background_rsvp: '--cms-rainier-rsvp-bg'},
    archak: {accent_color: '--cms-archak-accent', heading_font: '--cms-archak-heading', body_font: '--cms-archak-body', hero_title_scale: '--cms-archak-title-scale', hero_background: '--cms-archak-hero-bg', section_background_timeline: '--cms-archak-timeline-bg', section_background_gallery: '--cms-archak-gallery-bg', section_background_stay: '--cms-archak-stay-bg', section_background_registry: '--cms-archak-registry-bg', header_badge: '--cms-archak-badge'},
    parang: {accent_color: '--parang-gold', heading_font: '--parang-heading', body_font: '--parang-body', hero_background: '--cms-parang-bg', section_background_home: '--cms-parang-home-bg', section_background_gallery: '--cms-parang-gallery-bg', section_background_location: '--cms-parang-location-bg', ornament_left: '--cms-parang-left', ornament_right: '--cms-parang-right'},
    pawiwahan: {accent_color: '--pawiwahan-accent', heading_font: '--pawiwahan-heading', body_font: '--pawiwahan-body', hero_background: '--pawiwahan-hero-bg', welcome_background: '--pawiwahan-welcome-bg', section_background_gallery: '--pawiwahan-gallery-bg', section_background_location: '--pawiwahan-location-bg', section_background_gift: '--pawiwahan-gift-bg', section_background_messages: '--pawiwahan-messages-bg'},
    dewankl: {accent_color: '--cms-dewana-accent', heading_font: '--cms-dewana-heading', body_font: '--cms-dewana-body', hero_overlay: '--cms-dewana-overlay', hero_background: '--cms-dewana-hero-bg', welcome_background: '--cms-dewana-welcome-bg', section_background_home: '--cms-dewana-home-bg', section_background_bride: '--cms-dewana-bride-bg', section_background_wedding_date: '--cms-dewana-date-bg', section_background_gallery: '--cms-dewana-gallery-bg', section_background_love_gift: '--cms-dewana-gift-bg', section_background_comment: '--cms-dewana-comment-bg'},
    custom: {accent_color: '--primary', background_color: '--bg', paper_color: '--paper', text_color: '--text', heading_font: '--font-heading', body_font: '--font-body', hero_overlay: '--hero-overlay', hero_title_scale: '--hero-title-scale', hero_background: '--hero-bg', section_background_home: '--custom-home-bg', section_background_event: '--custom-event-bg', section_background_story: '--custom-story-bg', section_background_gallery: '--custom-gallery-bg', section_background_location: '--custom-location-bg', section_background_gift: '--custom-gift-bg', section_background_rsvp: '--custom-rsvp-bg'}
  };
  const applyVisualPreview = function (theme) {
    const preset = theme.theme_preset || 'custom';
    const values = theme.visuals || {};
    const mapping = visualMap[preset] || visualMap.custom;
    Object.keys(mapping).forEach(function (key) {
      if (values[key] === undefined || values[key] === '') return;
      let value = values[key];
      const imageKeys = Object.keys(mapping).filter(function (key) { return key === 'hero_background' || key === 'welcome_background' || key.indexOf('section_background_') === 0 || key.indexOf('ornament_') === 0 || key === 'header_badge'; });
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
