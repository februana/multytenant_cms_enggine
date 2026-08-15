<?php
/**
 * Theme Helper Functions
 *
 * Shared utilities for all theme templates.
 */

define('THEME_HELPER_LOADED', true);

/**
 * Escape HTML output.
 */
function escape_html(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Get public path for asset.
 */
function public_path(string $path): string {
    if (empty($path)) {
        return '';
    }
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    return '/' . ltrim($path, '/');
}

/**
 * Get theme asset URL.
 */
function get_theme_asset_url(string $themeKey, string $filename): string {
    return '/themes/' . preg_replace('/[^a-z0-9_-]/i', '', $themeKey) . '/' . $filename;
}

/**
 * Normalize section ID.
 */
function normalize_section_id(string $id): string {
    return strtolower(trim(preg_replace('/[^a-z0-9_-]/i', '', $id)));
}

/**
 * Get a configured section entry by normalized ID.
 */
function get_section_entry(array $config, string $sectionId): ?array {
    $targetId = normalize_section_id($sectionId);
    if (!isset($config['sections']) || !is_array($config['sections'])) {
        return null;
    }

    foreach ($config['sections'] as $section) {
        if (normalize_section_id((string)($section['id'] ?? '')) === $targetId) {
            return is_array($section) ? $section : null;
        }
    }

    return null;
}

/**
 * Check if a section is enabled.
 */
function is_section_enabled(array $config, string $sectionId): bool {
    $section = get_section_entry($config, $sectionId);
    if ($section === null) {
        return true;
    }

    return !empty($section['enabled']);
}

/**
 * Resolve a section title with CMS customization support.
 */
function get_section_title(array $config, string $sectionId, string $defaultTitle): string {
    $section = get_section_entry($config, $sectionId);
    if ($section === null) {
        return $defaultTitle;
    }

    return !empty($section['custom_title'])
        ? (string)$section['custom_title']
        : (string)($section['title'] ?? $defaultTitle);
}

/**
 * Resolve a section subtitle with CMS customization support.
 */
function get_section_subtitle(array $config, string $sectionId, string $defaultSubtitle): string {
    $section = get_section_entry($config, $sectionId);
    if ($section === null) {
        return $defaultSubtitle;
    }

    return !empty($section['custom_subtitle'])
        ? (string)$section['custom_subtitle']
        : (string)($section['subtitle'] ?? $defaultSubtitle);
}

/**
 * Build WhatsApp link.
 */
function build_whatsapp_link(array $config): string {
    $phone = $config['whatsapp']['phone'] ?? '';
    $message = $config['whatsapp']['message'] ?? '';

    if (empty($phone)) {
        return '#';
    }

    $phone = preg_replace('/[^0-9]/', '', $phone);
    $message = urlencode($message);

    return 'https://wa.me/' . $phone . '?text=' . $message;
}

/**
 * Build Google Calendar link.
 */
function build_google_calendar_link(array $config): string {
    $title = urlencode($config['site']['title'] ?? 'Undangan Pernikahan');
    $dates = '';

    if (!empty($config['schedule']['akad_date']) && !empty($config['schedule']['akad_time'])) {
        $startDate = str_replace('-', '', $config['schedule']['akad_date']);
        $startTime = str_replace(':', '', $config['schedule']['akad_time']);
        $dates = $startDate . 'T' . $startTime . '00/' . $startDate . 'T' . date('Hi', strtotime($config['schedule']['akad_time']) + 7200) . '00';
    }

    $details = urlencode($config['wedding']['opening_text'] ?? '');
    $location = urlencode($config['location']['address'] ?? '');
    $ctz = $config['schedule']['timezone'] ?? 'Asia/Jakarta';

    return 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . $title . '&dates=' . $dates . '&details=' . $details . '&location=' . $location . '&ctz=' . $ctz;
}

/**
 * Load custom CSS from the canonical runtime file.
 */
function load_custom_css(): string {
    $customCssFile = __DIR__ . '/../custom.css';
    if (file_exists($customCssFile)) {
        return file_get_contents($customCssFile);
    }

    return '';
}
