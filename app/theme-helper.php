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

/**
 * Switch the active presentation mode without resetting unrelated CMS data.
 * The built-in renderer owns its own template values; this helper only changes
 * the global selector state and deliberately preserves every other config key.
 */
function switch_active_theme_preset_config(array $config, string $selectedPreset): ?array {
    $selectedPreset = trim($selectedPreset);
    if ($selectedPreset === 'custom') {
        $config['theme']['mode'] = 'custom';
        $config['theme']['theme_preset'] = 'custom';
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
  window.addEventListener('message', function (event) {
    if (event.origin !== window.location.origin || !event.data || event.data.type !== 'theme-preview:update') return;
    const theme = event.data.theme || {};
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
