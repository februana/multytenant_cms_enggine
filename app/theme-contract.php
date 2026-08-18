<?php
/**
 * Theme contracts.
 *
 * Built-in themes own their composition and section identifiers. The CMS keeps
 * shared data/services in the core, while this contract describes which parts
 * of that data a renderer is allowed to consume.
 */

if (!function_exists('theme_contract_registry')) {
    function theme_contract_registry(): array {
        return [
            'dewankl' => [
                'id' => 'dewankl',
                'label' => 'DewanaKL',
                'source' => 'https://github.com/dewanakl/undangan',
                'capabilities' => ['wedding', 'parents', 'schedule', 'countdown', 'gallery', 'story', 'music', 'gift', 'maps', 'rsvp', 'messages', 'seo', 'whatsapp'],
                'sections' => [
                    ['id' => 'opening', 'label' => 'Opening / Home', 'title' => 'Opening', 'subtitle' => '', 'legacy_ids' => ['hero', 'undangan']],
                    ['id' => 'couple', 'label' => 'Bride & Groom', 'title' => 'Bride & Groom', 'subtitle' => '', 'legacy_ids' => ['bride_groom', 'couple', 'parents']],
                    ['id' => 'quote', 'label' => 'Quote', 'title' => 'Quote', 'subtitle' => '', 'legacy_ids' => ['quote', 'cerita']],
                    ['id' => 'story', 'label' => 'Love Story', 'title' => 'Love Story', 'subtitle' => '', 'legacy_ids' => ['story', 'cerita']],
                    ['id' => 'event', 'label' => 'Event', 'title' => 'Event', 'subtitle' => '', 'legacy_ids' => ['event', 'acara']],
                    ['id' => 'gallery', 'label' => 'Gallery', 'title' => 'Gallery', 'subtitle' => '', 'legacy_ids' => ['gallery', 'galeri']],
                    ['id' => 'location', 'label' => 'Location', 'title' => 'Location', 'subtitle' => '', 'legacy_ids' => ['location', 'lokasi']],
                    ['id' => 'gift', 'label' => 'Gift', 'title' => 'Gift', 'subtitle' => '', 'legacy_ids' => ['gift', 'amplop']],
                    ['id' => 'rsvp', 'label' => 'RSVP', 'title' => 'RSVP', 'subtitle' => '', 'legacy_ids' => ['rsvp']],
                    ['id' => 'wishes', 'label' => 'Guest Wishes', 'title' => 'Guest Wishes', 'subtitle' => '', 'legacy_ids' => ['messages']],
                    ['id' => 'music', 'label' => 'Music', 'title' => 'Music', 'subtitle' => '', 'legacy_ids' => ['music']],
                ],
                'admin_capabilities' => ['wedding', 'parents', 'schedule', 'gallery', 'story', 'music', 'gift', 'maps', 'rsvp', 'messages', 'seo', 'whatsapp'],
                'assets' => ['bootstrap', 'fontawesome', 'aos', 'theme-script'],
            ],
            'elix' => [
                'id' => 'elix',
                'label' => 'Elix',
                'source' => 'https://github.com/elix-stack/wedding-invitation-1',
                'capabilities' => ['wedding', 'parents', 'schedule', 'countdown', 'gallery', 'story', 'music', 'gift', 'maps', 'rsvp', 'messages', 'seo', 'whatsapp'],
                'sections' => [
                    ['id' => 'cover', 'label' => 'Cover', 'title' => 'Cover', 'subtitle' => '', 'legacy_ids' => ['hero']],
                    ['id' => 'introduction', 'label' => 'Introduction', 'title' => 'Introduction', 'subtitle' => '', 'legacy_ids' => ['undangan', 'bride_groom']],
                    ['id' => 'quote', 'label' => 'Quote', 'title' => 'Quote', 'subtitle' => '', 'legacy_ids' => ['quote']],
                    ['id' => 'timeline', 'label' => 'Timeline', 'title' => 'Timeline', 'subtitle' => '', 'legacy_ids' => ['acara', 'story', 'cerita']],
                    ['id' => 'venue', 'label' => 'Venue', 'title' => 'Venue', 'subtitle' => '', 'legacy_ids' => ['lokasi']],
                    ['id' => 'gallery', 'label' => 'Gallery', 'title' => 'Gallery', 'subtitle' => '', 'legacy_ids' => ['gallery', 'galeri']],
                    ['id' => 'wishes', 'label' => 'Wishes', 'title' => 'Wishes', 'subtitle' => '', 'legacy_ids' => ['messages']],
                    ['id' => 'gift', 'label' => 'Gift', 'title' => 'Gift', 'subtitle' => '', 'legacy_ids' => ['gift', 'amplop']],
                    ['id' => 'rsvp', 'label' => 'RSVP', 'title' => 'RSVP', 'subtitle' => '', 'legacy_ids' => ['rsvp']],
                    ['id' => 'music', 'label' => 'Music', 'title' => 'Music', 'subtitle' => '', 'legacy_ids' => ['music']],
                ],
                'admin_capabilities' => ['wedding', 'parents', 'schedule', 'gallery', 'story', 'music', 'gift', 'maps', 'rsvp', 'messages', 'seo', 'whatsapp'],
                'assets' => ['bootstrap', 'aos', 'theme-script'],
            ],
            'rainier' => [
                'id' => 'rainier',
                'label' => 'Rainier',
                'source' => 'https://github.com/Rainier-PS/Invitation-Template',
                'capabilities' => ['wedding', 'parents', 'schedule', 'countdown', 'gallery', 'story', 'music', 'gift', 'maps', 'rsvp', 'messages', 'seo', 'whatsapp'],
                'sections' => [
                    ['id' => 'opening', 'label' => 'Opening', 'title' => 'Opening', 'subtitle' => '', 'legacy_ids' => ['hero']],
                    ['id' => 'hero', 'label' => 'Hero', 'title' => 'Hero', 'subtitle' => '', 'legacy_ids' => ['hero']],
                    ['id' => 'couple', 'label' => 'Couple', 'title' => 'Couple', 'subtitle' => '', 'legacy_ids' => ['bride_groom', 'couple', 'parents']],
                    ['id' => 'story', 'label' => 'Story', 'title' => 'Story', 'subtitle' => '', 'legacy_ids' => ['story', 'cerita']],
                    ['id' => 'event', 'label' => 'Event', 'title' => 'Event', 'subtitle' => '', 'legacy_ids' => ['event', 'acara']],
                    ['id' => 'gallery', 'label' => 'Gallery', 'title' => 'Gallery', 'subtitle' => '', 'legacy_ids' => ['gallery', 'galeri']],
                    ['id' => 'location', 'label' => 'Location', 'title' => 'Location', 'subtitle' => '', 'legacy_ids' => ['location', 'lokasi']],
                    ['id' => 'gift', 'label' => 'Gift', 'title' => 'Gift', 'subtitle' => '', 'legacy_ids' => ['gift', 'amplop']],
                    ['id' => 'rsvp', 'label' => 'RSVP', 'title' => 'RSVP', 'subtitle' => '', 'legacy_ids' => ['rsvp']],
                    ['id' => 'wishes', 'label' => 'Wishes', 'title' => 'Wishes', 'subtitle' => '', 'legacy_ids' => ['messages']],
                    ['id' => 'music', 'label' => 'Music', 'title' => 'Music', 'subtitle' => '', 'legacy_ids' => ['music']],
                ],
                'admin_capabilities' => ['wedding', 'parents', 'schedule', 'gallery', 'story', 'music', 'gift', 'maps', 'rsvp', 'messages', 'seo', 'whatsapp'],
                'assets' => ['theme-script'],
            ],
            // Archak is retained as a compatibility adapter for existing installs.
            'archak' => [
                'id' => 'archak',
                'label' => 'Archak',
                'source' => 'CMS legacy preset',
                'capabilities' => ['wedding', 'parents', 'schedule', 'countdown', 'gallery', 'story', 'music', 'gift', 'maps', 'rsvp', 'messages', 'seo', 'whatsapp'],
                'sections' => [
                    ['id' => 'hero', 'label' => 'Hero', 'title' => 'Hero', 'subtitle' => '', 'legacy_ids' => ['hero']],
                    ['id' => 'couple', 'label' => 'Couple', 'title' => 'Couple', 'subtitle' => '', 'legacy_ids' => ['bride_groom', 'couple']],
                    ['id' => 'event', 'label' => 'Event', 'title' => 'Event', 'subtitle' => '', 'legacy_ids' => ['event', 'acara']],
                    ['id' => 'story', 'label' => 'Story', 'title' => 'Story', 'subtitle' => '', 'legacy_ids' => ['story', 'cerita']],
                    ['id' => 'gallery', 'label' => 'Gallery', 'title' => 'Gallery', 'subtitle' => '', 'legacy_ids' => ['gallery', 'galeri']],
                    ['id' => 'location', 'label' => 'Location', 'title' => 'Location', 'subtitle' => '', 'legacy_ids' => ['location', 'lokasi']],
                    ['id' => 'gift', 'label' => 'Gift', 'title' => 'Gift', 'subtitle' => '', 'legacy_ids' => ['gift', 'amplop']],
                    ['id' => 'rsvp', 'label' => 'RSVP', 'title' => 'RSVP', 'subtitle' => '', 'legacy_ids' => ['rsvp']],
                    ['id' => 'wishes', 'label' => 'Wishes', 'title' => 'Wishes', 'subtitle' => '', 'legacy_ids' => ['messages']],
                    ['id' => 'music', 'label' => 'Music', 'title' => 'Music', 'subtitle' => '', 'legacy_ids' => ['music']],
                ],
                'admin_capabilities' => ['wedding', 'parents', 'schedule', 'gallery', 'story', 'music', 'gift', 'maps', 'rsvp', 'messages', 'seo', 'whatsapp'],
                'assets' => ['theme-script'],
            ],
        ];
    }

    function theme_contract_for(string $presetKey): array {
        $registry = theme_contract_registry();
        return $registry[$presetKey] ?? [];
    }

    function theme_contract_sections(string $presetKey): array {
        return array_values((array)(theme_contract_for($presetKey)['sections'] ?? []));
    }

    function theme_contract_capabilities(string $presetKey): array {
        return array_values((array)(theme_contract_for($presetKey)['capabilities'] ?? []));
    }

    function theme_contract_admin_capabilities(string $presetKey): array {
        return array_values((array)(theme_contract_for($presetKey)['admin_capabilities'] ?? []));
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
                'title' => (string)($section['title'] ?? $section['label'] ?? $section['id']),
                'subtitle' => (string)($section['subtitle'] ?? ''),
                'enabled' => true,
                'custom_title' => '',
                'custom_subtitle' => '',
                'order' => $order + 1,
            ];
        }
        return $result;
    }

    function theme_contract_sections_for_config(array $config, string $presetKey): array {
        $defaults = theme_contract_default_sections($presetKey);
        $stored = $config['theme_sections'][$presetKey] ?? null;
        if (!is_array($stored) || !$stored) return $defaults;

        $byId = [];
        foreach ($stored as $section) {
            if (!is_array($section)) continue;
            $id = preg_replace('/[^a-z0-9_-]/i', '', (string)($section['id'] ?? ''));
            if ($id === '' || !theme_contract_has_section($presetKey, $id)) continue;
            $byId[$id] = [
                'id' => $id,
                'title' => (string)($section['title'] ?? ''),
                'subtitle' => (string)($section['subtitle'] ?? ''),
                'enabled' => array_key_exists('enabled', $section) ? !empty($section['enabled']) : true,
                'custom_title' => (string)($section['custom_title'] ?? ''),
                'custom_subtitle' => (string)($section['custom_subtitle'] ?? ''),
                'order' => (int)($section['order'] ?? 0),
            ];
        }
        foreach ($defaults as $default) {
            if (!isset($byId[$default['id']])) $byId[$default['id']] = $default;
        }
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
