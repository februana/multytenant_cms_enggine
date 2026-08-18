<?php
/**
 * Theme contracts.
 *
 * A built-in theme contract describes the original template boundary. It does
 * not turn every CMS data capability into a presentation section. The renderer
 * owns the original DOM; this file only provides the adapter vocabulary and
 * safe admin/runtime capability metadata.
 */

if (!function_exists('theme_contract_registry')) {
    function theme_contract_registry(): array {
        return [
            'dewankl' => [
                'id' => 'dewankl',
                'label' => 'DewanaKL',
                'source' => 'https://github.com/dewanakl/undangan',
                'source_revision' => '99e7c2d',
                'data_capabilities' => [
                    'wedding', 'parents', 'schedule', 'countdown', 'gallery', 'music',
                    'gift', 'maps', 'rsvp', 'messages', 'story', 'guest_name', 'media',
                    'seo', 'whatsapp', 'calendar'
                ],
                'presentation_capabilities' => [
                    'welcome_overlay', 'loading_screen', 'split_desktop_mobile',
                    'love_story_video', 'gallery_carousel', 'love_gift_cards',
                    'comment_feed', 'image_modal', 'bottom_navigation'
                ],
                'sections' => [
                    ['id' => 'home', 'dom_id' => 'home', 'label' => 'Home', 'title' => 'Home', 'embedded_capabilities' => ['wedding', 'calendar', 'guest_name']],
                    ['id' => 'bride', 'dom_id' => 'bride', 'label' => 'Bride & Groom', 'title' => 'Bride & Groom', 'embedded_capabilities' => ['parents']],
                    ['id' => 'love_story', 'dom_id' => null, 'label' => 'Love Story', 'title' => 'Love Story', 'embedded_capabilities' => ['story', 'love_story_video']],
                    ['id' => 'wedding_date', 'dom_id' => 'wedding-date', 'label' => 'Wedding Date', 'title' => 'Wedding Date', 'embedded_capabilities' => ['schedule', 'countdown', 'maps']],
                    ['id' => 'gallery', 'dom_id' => 'gallery', 'label' => 'Gallery', 'title' => 'Gallery', 'embedded_capabilities' => ['gallery']],
                    ['id' => 'love_gift', 'dom_id' => null, 'label' => 'Love Gift', 'title' => 'Love Gift', 'embedded_capabilities' => ['gift']],
                    ['id' => 'comment', 'dom_id' => 'comment', 'label' => 'Comment', 'title' => 'Comment', 'embedded_capabilities' => ['rsvp', 'messages']],
                ],
                'admin_capabilities' => ['wedding', 'parents', 'schedule', 'gallery', 'story', 'music', 'gift', 'maps', 'rsvp', 'messages', 'media', 'seo', 'whatsapp'],
                'assets' => [
                    'bootstrap@5.3.8', 'fontawesome@7.1.0', 'guest.css', 'guest.js',
                    'common.css', 'animation.css', 'theme-media'
                ],
            ],
            'elix' => [
                'id' => 'elix',
                'label' => 'Elix',
                'source' => 'https://github.com/elix-stack/wedding-invitation-1',
                'source_revision' => '1ac2394',
                'data_capabilities' => [
                    'wedding', 'parents', 'schedule', 'countdown', 'gallery', 'music',
                    'gift', 'maps', 'rsvp', 'messages', 'story', 'guest_name', 'media',
                    'seo', 'whatsapp', 'calendar'
                ],
                'presentation_capabilities' => [
                    'hero_cover', 'offcanvas_navigation', 'countdown_circle',
                    'timeline', 'gallery_lightbox', 'disqus_comments', 'gifts', 'audio_player'
                ],
                'sections' => [
                    ['id' => 'hero', 'dom_id' => 'hero', 'label' => 'Hero', 'title' => 'Hero', 'embedded_capabilities' => ['guest_name', 'countdown']],
                    ['id' => 'home', 'dom_id' => 'home', 'label' => 'Home', 'title' => 'Home', 'embedded_capabilities' => ['wedding', 'parents']],
                    ['id' => 'info', 'dom_id' => 'info', 'label' => 'Info', 'title' => 'Info', 'embedded_capabilities' => ['schedule', 'maps']],
                    ['id' => 'story', 'dom_id' => 'story', 'label' => 'Story', 'title' => 'Story', 'embedded_capabilities' => ['story']],
                    ['id' => 'gallery', 'dom_id' => 'gallery', 'label' => 'Gallery', 'title' => 'Gallery', 'embedded_capabilities' => ['gallery']],
                    ['id' => 'rsvp', 'dom_id' => 'rsvp', 'label' => 'RSVP', 'title' => 'RSVP', 'embedded_capabilities' => ['rsvp', 'messages']],
                    ['id' => 'gifts', 'dom_id' => 'gifts', 'label' => 'Gifts', 'title' => 'Gifts', 'embedded_capabilities' => ['gift']],
                ],
                'admin_capabilities' => ['wedding', 'parents', 'schedule', 'gallery', 'story', 'music', 'gift', 'maps', 'rsvp', 'messages', 'media', 'seo', 'whatsapp'],
                'assets' => [
                    'bootstrap@5.3.5', 'bootstrap-icons@1.11.3', 'pacifico-sacramento-work-sans',
                    'simply-countdown', 'countdown/circle.css', 'bs5-lightbox', 'theme-media'
                ],
            ],
            'rainier' => [
                'id' => 'rainier',
                'label' => 'Rainier',
                'source' => 'https://github.com/Rainier-PS/Invitation-Template',
                'source_revision' => '443a04f',
                'data_capabilities' => [
                    'wedding', 'schedule', 'countdown', 'maps', 'rsvp', 'messages',
                    'guest_name', 'calendar', 'seo', 'music', 'media'
                ],
                'presentation_capabilities' => [
                    'hero', 'event_details', 'schedule_list', 'quotes_list',
                    'rsvp_embed', 'footer_branding', 'simple_mode', 'audio_control'
                ],
                'sections' => [
                    ['id' => 'hero', 'dom_id' => null, 'label' => 'Hero', 'title' => 'Hero', 'embedded_capabilities' => ['wedding', 'countdown', 'calendar', 'guest_name']],
                    ['id' => 'event_details', 'dom_id' => null, 'label' => 'Event Details', 'title' => 'Event Details', 'embedded_capabilities' => ['schedule', 'maps']],
                    ['id' => 'schedule', 'dom_id' => 'schedule-section', 'label' => 'Schedule', 'title' => 'Schedule', 'embedded_capabilities' => ['schedule']],
                    ['id' => 'quotes', 'dom_id' => 'quotes-section', 'label' => 'Quotes', 'title' => 'Words of Inspiration', 'embedded_capabilities' => ['story']],
                    ['id' => 'rsvp', 'dom_id' => 'rsvp', 'label' => 'RSVP', 'title' => 'RSVP', 'embedded_capabilities' => ['rsvp']],
                    ['id' => 'footer', 'dom_id' => null, 'label' => 'Footer', 'title' => 'Footer', 'embedded_capabilities' => ['seo']],
                ],
                'admin_capabilities' => ['wedding', 'schedule', 'story', 'rsvp', 'music', 'maps', 'media', 'seo', 'whatsapp'],
                'assets' => ['cormorant-garamond-outfit', 'invite.css', 'invite-1.js', 'tally-widget-optional'],
            ],
            'archak' => [
                'id' => 'archak',
                'label' => 'Archak',
                'source' => 'https://github.com/archakNath/wedding-invitation-website',
                'source_revision' => '1b54902',
                'data_capabilities' => [
                    'wedding', 'parents', 'schedule', 'maps', 'rsvp', 'guest_name',
                    'gallery', 'story', 'gift', 'media', 'seo', 'whatsapp'
                ],
                'presentation_capabilities' => [
                    'responsive_nav', 'parallax_home', 'timeline', 'gallery_triptych',
                    'quote', 'travel_stay', 'registry', 'parting_message'
                ],
                'sections' => [
                    ['id' => 'home', 'dom_id' => null, 'label' => 'Home', 'title' => 'Home', 'embedded_capabilities' => ['wedding', 'schedule', 'rsvp']],
                    ['id' => 'timeline', 'dom_id' => null, 'label' => 'Timeline', 'title' => 'Timeline', 'embedded_capabilities' => ['schedule']],
                    ['id' => 'story', 'dom_id' => 'story', 'label' => 'Story', 'title' => 'Story', 'embedded_capabilities' => ['story']],
                    ['id' => 'gallery', 'dom_id' => null, 'label' => 'Gallery', 'title' => 'Gallery', 'embedded_capabilities' => ['gallery']],
                    ['id' => 'quote', 'dom_id' => null, 'label' => 'Quote', 'title' => 'Quote', 'embedded_capabilities' => ['wedding']],
                    ['id' => 'stay', 'dom_id' => 'stay', 'label' => 'Travel & Stay', 'title' => 'Travel & Stay', 'embedded_capabilities' => ['maps']],
                    ['id' => 'registry', 'dom_id' => 'registry', 'label' => 'Promises', 'title' => 'Promises', 'embedded_capabilities' => ['gift']],
                    ['id' => 'parting', 'dom_id' => null, 'label' => 'Parting Message', 'title' => 'Parting Message', 'embedded_capabilities' => ['rsvp']],
                    ['id' => 'footer', 'dom_id' => null, 'label' => 'Footer', 'title' => 'Footer', 'embedded_capabilities' => ['seo']],
                ],
                'admin_capabilities' => ['wedding', 'parents', 'schedule', 'gallery', 'story', 'gift', 'maps', 'rsvp', 'media', 'seo', 'whatsapp'],
                'assets' => ['fontawesome-kit', 'style.css', 'main.js', 'theme-media'],
            ],
        ];
    }

    function theme_contract_for(string $presetKey): array {
        return theme_contract_registry()[$presetKey] ?? [];
    }

    function theme_contract_sections(string $presetKey): array {
        return array_values((array)(theme_contract_for($presetKey)['sections'] ?? []));
    }

    function theme_contract_capabilities(string $presetKey): array {
        return array_values((array)(theme_contract_for($presetKey)['data_capabilities'] ?? []));
    }

    function theme_contract_data_capabilities(string $presetKey): array {
        return theme_contract_capabilities($presetKey);
    }

    function theme_contract_presentation_capabilities(string $presetKey): array {
        return array_values((array)(theme_contract_for($presetKey)['presentation_capabilities'] ?? []));
    }

    function theme_contract_admin_capabilities(string $presetKey): array {
        return array_values((array)(theme_contract_for($presetKey)['admin_capabilities'] ?? []));
    }

    function theme_contract_assets(string $presetKey): array {
        return array_values((array)(theme_contract_for($presetKey)['assets'] ?? []));
    }

    function theme_contract_has_section(string $presetKey, string $sectionId): bool {
        foreach (theme_contract_sections($presetKey) as $section) {
            if (($section['id'] ?? '') === $sectionId) return true;
        }
        return false;
    }

    function theme_contract_section(string $presetKey, string $sectionId): ?array {
        foreach (theme_contract_sections($presetKey) as $section) {
            if (($section['id'] ?? '') === $sectionId) return $section;
        }
        return null;
    }

    function theme_contract_default_sections(string $presetKey): array {
        $result = [];
        foreach (theme_contract_sections($presetKey) as $order => $section) {
            $result[] = [
                'id' => (string)$section['id'],
                'dom_id' => $section['dom_id'] ?? null,
                'title' => (string)($section['title'] ?? $section['label'] ?? $section['id']),
                'subtitle' => (string)($section['subtitle'] ?? ''),
                'enabled' => true,
                'custom_title' => '',
                'custom_subtitle' => '',
                // Administrative display only. Built-in renderers never sort DOM by this value.
                'order' => $order + 1,
            ];
        }
        return $result;
    }

    function theme_contract_sections_for_config(array $config, string $presetKey): array {
        $defaults = theme_contract_default_sections($presetKey);
        $stored = $config['theme_sections'][$presetKey] ?? null;
        $legacyMap = [
            'dewankl' => ['hero' => 'home', 'story' => 'love_story', 'gift' => 'love_gift', 'location' => 'wedding_date', 'wishes' => 'comment'],
            'elix' => ['couple' => 'home', 'event' => 'info', 'timeline' => 'story', 'location' => 'info', 'gift' => 'gifts', 'wishes' => 'rsvp'],
            'rainier' => ['couple' => 'event_details', 'event' => 'event_details', 'countdown' => 'hero', 'story' => 'quotes', 'location' => 'event_details', 'wishes' => 'rsvp'],
            'archak' => ['event' => 'timeline', 'story' => 'story', 'gift' => 'registry', 'location' => 'stay'],
        ][$presetKey] ?? [];
        if (!is_array($stored) || !$stored) return $defaults;

        $byId = [];
        foreach ($stored as $section) {
            if (!is_array($section)) continue;
            $rawId = preg_replace('/[^a-z0-9_-]/i', '', (string)($section['id'] ?? ''));
            $id = $legacyMap[$rawId] ?? $rawId;
            if ($id === '' || !theme_contract_has_section($presetKey, $id)) continue;
            $default = theme_contract_section($presetKey, $id) ?? [];
            $byId[$id] = [
                'id' => $id,
                'dom_id' => $default['dom_id'] ?? null,
                'title' => (string)($default['title'] ?? $section['title'] ?? $id),
                'subtitle' => (string)($default['subtitle'] ?? $section['subtitle'] ?? ''),
                'enabled' => array_key_exists('enabled', $section) ? !empty($section['enabled']) : true,
                'custom_title' => (string)($section['custom_title'] ?? ''),
                'custom_subtitle' => (string)($section['custom_subtitle'] ?? ''),
                'order' => (int)($section['order'] ?? 0),
            ];
        }
        foreach ($defaults as $default) {
            if (!isset($byId[$default['id']])) $byId[$default['id']] = $default;
        }
        // Ordering is only for the admin list. A built-in renderer uses its source order.
        $result = array_values($byId);
        usort($result, static fn(array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        return $result;
    }

    function theme_section_entry(array $config, string $presetKey, string $sectionId): ?array {
        foreach (theme_contract_sections_for_config($config, $presetKey) as $section) {
            if (($section['id'] ?? '') === $sectionId) return $section;
        }
        return null;
    }

    function theme_section_enabled(array $config, string $presetKey, string $sectionId): bool {
        $entry = theme_section_entry($config, $presetKey, $sectionId);
        return $entry === null ? false : !empty($entry['enabled']);
    }

    function theme_section_title(array $config, string $presetKey, string $sectionId, string $fallback): string {
        $entry = theme_section_entry($config, $presetKey, $sectionId);
        if (!$entry) return $fallback;
        return trim((string)($entry['custom_title'] ?? '')) ?: trim((string)($entry['title'] ?? '')) ?: $fallback;
    }

    function theme_section_subtitle(array $config, string $presetKey, string $sectionId, string $fallback): string {
        $entry = theme_section_entry($config, $presetKey, $sectionId);
        if (!$entry) return $fallback;
        return trim((string)($entry['custom_subtitle'] ?? '')) ?: trim((string)($entry['subtitle'] ?? '')) ?: $fallback;
    }

    function theme_sections_for_admin(array $config, string $presetKey): array {
        return theme_contract_sections_for_config($config, $presetKey);
    }
}
