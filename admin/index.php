<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/theme-helper.php';
require_once __DIR__ . '/../app/theme-renderer.php';
init_session();
$config = load_config();
$error = '';
$success = '';
$activeTab = 'dashboard';

if (isset($_GET['tab'])) {
    $activeTab = preg_replace('/[^a-z0-9_-]/i', '', $_GET['tab']);
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? false);
    }
    session_destroy();
    header('Location: /admin');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = trim((string)($_POST['password'] ?? ''));
    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif ($username !== $config['admin']['username'] && $username !== (getenv('ADMIN_USER') ?: 'admin')) {
        $error = 'Username atau password salah.';
    } elseif (!verify_admin_password($password, $config)) {
        $error = 'Username atau password salah.';
    } else {
        $_SESSION['admin'] = true;
        $_SESSION['last_activity'] = time();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        session_regenerate_id(true);
        header('Location: /admin');
        exit;
    }
}

if (!empty($_SESSION['admin'])) {
    $_SESSION['last_activity'] = time();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function build_invitation_preview_url(array $config): string {
    $siteUrl = trim($config['site']['url']);
    if ($siteUrl === '') {
        return '/?to=Bapak%20Ahmad';
    }
    return rtrim($siteUrl, '/') . '/?to=Bapak%20Ahmad';
}

if (!empty($_SESSION['admin']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $saveConfig = true;
        switch ($_POST['action']) {
            case 'upload_media_library':
                $targetFolder = trim((string)($_POST['media_dir'] ?? ''));
                $allowedByFolder = [
                    'cover' => ALLOWED_IMAGE_TYPES,
                    'background' => ALLOWED_IMAGE_TYPES,
                    'gallery' => ALLOWED_IMAGE_TYPES,
                    'love_story' => ALLOWED_IMAGE_TYPES,
                    'music' => ALLOWED_AUDIO_TYPES,
                ];
                $folderMap = [
                    'cover' => UPLOADS_COVER_DIR,
                    'background' => UPLOADS_BACKGROUND_DIR,
                    'gallery' => UPLOADS_GALLERY_DIR,
                    'love_story' => UPLOADS_LOVE_STORY_DIR,
                    'music' => UPLOADS_MUSIC_DIR,
                ];
                $destination = $folderMap[$targetFolder] ?? '';
                $allowed = $allowedByFolder[$targetFolder] ?? [];
                if ($destination === '' || empty($_FILES['media_file']['name'])) {
                    $error = 'Folder atau file media tidak valid.';
                    break;
                }
                $result = upload_file($_FILES['media_file'], $destination, $allowed, $targetFolder === 'music' ? MAX_MUSIC_UPLOAD_SIZE : MAX_UPLOAD_SIZE);
                if (!empty($result['error'])) {
                    $error = $result['error'];
                } else {
                    $success = 'File media berhasil diunggah.';
                }
                break;
            case 'use_media_library_asset':
                $target = trim((string)($_POST['media_target'] ?? ''));
                $path = trim((string)($_POST['media_path'] ?? ''));
                if ($path === '') {
                    $error = 'Pilih asset media yang akan dipakai.';
                    break;
                }
                switch ($target) {
                    case 'cover':
                        $config['media']['cover'] = $path;
                        break;
                    case 'groom_photo':
                        $config['media']['groom_photo'] = $path;
                        break;
                    case 'bride_photo':
                        $config['media']['bride_photo'] = $path;
                        break;
                    case 'couple_photo':
                        $config['media']['couple_photo'] = $path;
                        break;
                    case 'background_hero':
                        $config['media']['background_hero'] = $path;
                        break;
                    case 'background_section_1':
                        $config['media']['background_sections'][0] = $path;
                        break;
                    case 'background_section_2':
                        $config['media']['background_sections'][1] = $path;
                        break;
                    case 'background_section_3':
                        $config['media']['background_sections'][2] = $path;
                        break;
                    case 'gallery_item':
                        $config['gallery']['items'][] = ['filename' => $path, 'order' => time() + count($config['gallery']['items'] ?? [])];
                        break;
                    default:
                        $error = 'Target media tidak valid.';
                        break 2;
                }
                $success = 'Asset media berhasil dipakai dari File Manager.';
                break;
            case 'rename_media_file':
                $mediaPath = trim((string)($_POST['media_path'] ?? ''));
                $newName = trim((string)($_POST['new_name'] ?? ''));
                $response = rename_uploaded_asset($mediaPath, $newName);
                if (!$response['success']) {
                    $error = $response['error'];
                    break;
                }
                $config['media']['cover'] = $config['media']['cover'] === $mediaPath ? $response['path'] : $config['media']['cover'];
                $config['media']['background_hero'] = $config['media']['background_hero'] === $mediaPath ? $response['path'] : $config['media']['background_hero'];
                foreach (range(0, 2) as $index) {
                    if (($config['media']['background_sections'][$index] ?? '') === $mediaPath) {
                        $config['media']['background_sections'][$index] = $response['path'];
                    }
                }
                foreach (($config['gallery']['items'] ?? []) as $index => $item) {
                    if (($item['filename'] ?? '') === $mediaPath) {
                        $config['gallery']['items'][$index]['filename'] = $response['path'];
                    }
                }
                $success = 'Nama file media berhasil diubah.';
                break;
            case 'replace_media_file':
                $mediaPath = trim((string)($_POST['media_path'] ?? ''));
                $file = $_FILES['replacement_file'] ?? null;
                if ($mediaPath === '' || !is_array($file) || empty($file['name'])) {
                    $error = 'File pengganti tidak valid.';
                    break;
                }
                $response = replace_uploaded_asset($mediaPath, $file);
                if (!$response['success']) {
                    $error = $response['error'];
                    break;
                }
                $success = 'File media berhasil diganti.';
                break;
            case 'set_media_default':
                $mediaKey = trim((string)($_POST['media_key'] ?? ''));
                $mediaValue = trim((string)($_POST['media_value'] ?? ''));
                if ($mediaKey === '' || $mediaValue === '') {
                    $error = 'Tentukan media yang ingin dipasang sebagai default.';
                    break;
                }
                switch ($mediaKey) {
                    case 'media.cover':
                        $config['media']['cover'] = $mediaValue;
                        break;
                    case 'media.bride_photo':
                        $config['media']['bride_photo'] = $mediaValue;
                        break;
                    case 'media.groom_photo':
                        $config['media']['groom_photo'] = $mediaValue;
                        break;
                    case 'media.couple_photo':
                        $config['media']['couple_photo'] = $mediaValue;
                        break;
                    case 'media.music':
                        $config['media']['music'] = $mediaValue;
                        break;
                    case 'media.background_hero':
                        $config['media']['background_hero'] = $mediaValue;
                        break;
                    case 'media.background_sections.0':
                        $config['media']['background_sections'][0] = $mediaValue;
                        break;
                    case 'media.background_sections.1':
                        $config['media']['background_sections'][1] = $mediaValue;
                        break;
                    case 'media.background_sections.2':
                        $config['media']['background_sections'][2] = $mediaValue;
                        break;
                    case 'gift.qris_image':
                        $config['gift']['qris_image'] = $mediaValue;
                        break;
                    default:
                        $error = 'Target media tidak dikenal.';
                        break 2;
                }
                $success = 'File default media berhasil diperbarui.';
                break;
            case 'delete_media_file':
                $mediaPath = trim((string)($_POST['media_path'] ?? ''));
                if ($mediaPath === '') {
                    $error = 'File media tidak valid.';
                    break;
                }
                $usage = detect_media_usage($config, $mediaPath);
                if (!empty($usage)) {
                    $error = 'Asset sedang digunakan di: ' . implode(', ', $usage) . '. Hapus referensi dahulu.';
                    break;
                }
                if (!delete_uploaded_asset($mediaPath)) {
                    $error = 'Gagal menghapus file media.';
                    break;
                }
                $success = 'File media berhasil dihapus.';
                break;
            case 'save_wedding':
                $config['wedding']['bride_name'] = trim((string)($_POST['bride_name'] ?? '')) ?: $config['wedding']['bride_name'];
                $config['wedding']['groom_name'] = trim((string)($_POST['groom_name'] ?? '')) ?: $config['wedding']['groom_name'];
                $config['wedding']['title'] = trim((string)($_POST['title'] ?? '')) ?: $config['wedding']['title'];
                $config['wedding']['opening_text'] = trim((string)($_POST['opening_text'] ?? '')) ?: $config['wedding']['opening_text'];
                $config['wedding']['closing_text'] = trim((string)($_POST['closing_text'] ?? '')) ?: $config['wedding']['closing_text'];
                $config['wedding']['quote'] = trim((string)($_POST['quote'] ?? '')) ?: $config['wedding']['quote'];
                $config['wedding']['bride_nickname'] = trim((string)($_POST['bride_nickname'] ?? '')) ?: $config['wedding']['bride_nickname'];
                $config['wedding']['groom_nickname'] = trim((string)($_POST['groom_nickname'] ?? '')) ?: $config['wedding']['groom_nickname'];
                break;
            case 'save_parents':
                $config['parents']['bride_father'] = trim((string)($_POST['bride_father'] ?? '')) ?: $config['parents']['bride_father'];
                $config['parents']['bride_mother'] = trim((string)($_POST['bride_mother'] ?? '')) ?: $config['parents']['bride_mother'];
                $config['parents']['groom_father'] = trim((string)($_POST['groom_father'] ?? '')) ?: $config['parents']['groom_father'];
                $config['parents']['groom_mother'] = trim((string)($_POST['groom_mother'] ?? '')) ?: $config['parents']['groom_mother'];
                break;
            case 'save_schedule':
                $oldAkadDate = $config['schedule']['akad_date'] ?? '';
                $oldAkadTime = $config['schedule']['akad_time'] ?? '';
                $oldTimezone = $config['schedule']['timezone'] ?? '';
                $oldCountdownTarget = $config['schedule']['countdown_target'] ?? '';
                $config['schedule']['akad_date'] = trim((string)($_POST['akad_date'] ?? '')) ?: $config['schedule']['akad_date'];
                $config['schedule']['akad_time'] = trim((string)($_POST['akad_time'] ?? '')) ?: $config['schedule']['akad_time'];
                $config['schedule']['reception_date'] = trim((string)($_POST['reception_date'] ?? '')) ?: $config['schedule']['reception_date'];
                $config['schedule']['reception_time'] = trim((string)($_POST['reception_time'] ?? '')) ?: $config['schedule']['reception_time'];
                $config['schedule']['timezone'] = trim((string)($_POST['timezone'] ?? '')) ?: $config['schedule']['timezone'];
                $config['schedule']['google_calendar_link'] = trim((string)($_POST['google_calendar_link'] ?? '')) ?: $config['schedule']['google_calendar_link'];
                $newDefaultTarget = compute_countdown_target($config['schedule']);
                $target = trim((string)($_POST['countdown_target'] ?? ''));
                $scheduleChanged = ($config['schedule']['akad_date'] !== $oldAkadDate || $config['schedule']['akad_time'] !== $oldAkadTime || $config['schedule']['timezone'] !== $oldTimezone);
                if ($target !== '') {
                    $config['schedule']['countdown_target'] = $target;
                } elseif ($scheduleChanged || $oldCountdownTarget === '' || $oldCountdownTarget === compute_countdown_target(['akad_date' => $oldAkadDate, 'akad_time' => $oldAkadTime, 'timezone' => $oldTimezone])) {
                    $config['schedule']['countdown_target'] = $newDefaultTarget;
                }
                break;
            case 'save_location':
                $config['location']['venue'] = trim((string)($_POST['venue'] ?? '')) ?: $config['location']['venue'];
                $config['location']['address'] = trim((string)($_POST['address'] ?? '')) ?: $config['location']['address'];
                $config['location']['maps_url'] = trim((string)($_POST['maps_url'] ?? '')) ?: $config['location']['maps_url'];
                $config['location']['maps_embed'] = trim((string)($_POST['maps_embed'] ?? '')) ?: $config['location']['maps_embed'];
                break;
            case 'save_gift':
                $config['gift']['bank'] = trim((string)($_POST['bank'] ?? '')) ?: $config['gift']['bank'];
                $config['gift']['account_number'] = trim((string)($_POST['account_number'] ?? '')) ?: $config['gift']['account_number'];
                $config['gift']['account_holder'] = trim((string)($_POST['account_holder'] ?? '')) ?: $config['gift']['account_holder'];
                $config['gift']['e_wallet_label'] = trim((string)($_POST['e_wallet_label'] ?? '')) ?: $config['gift']['e_wallet_label'];
                $config['gift']['e_wallet_number'] = trim((string)($_POST['e_wallet_number'] ?? '')) ?: $config['gift']['e_wallet_number'];
                break;
            case 'save_dresscode':
                $config['dresscode']['enabled'] = !empty($_POST['dresscode_enabled']);
                $config['dresscode']['title'] = trim((string)($_POST['dresscode_title'] ?? '')) ?: $config['dresscode']['title'];
                $config['dresscode']['color'] = trim((string)($_POST['dresscode_color'] ?? '')) ?: $config['dresscode']['color'];
                $config['dresscode']['rule'] = trim((string)($_POST['dresscode_rule'] ?? '')) ?: $config['dresscode']['rule'];
                $config['dresscode']['description'] = trim((string)($_POST['dresscode_description'] ?? '')) ?: $config['dresscode']['description'];
                break;
            case 'save_whatsapp':
                $config['whatsapp']['phone'] = trim((string)($_POST['whatsapp_phone'] ?? '')) ?: $config['whatsapp']['phone'];
                $config['whatsapp']['message'] = trim((string)($_POST['whatsapp_message'] ?? '')) ?: $config['whatsapp']['message'];
                break;
            case 'save_theme_options':
                $presetKey = trim((string)($_POST['preset_key'] ?? ($config['theme']['theme_preset'] ?? 'dewankl')));
                if ($presetKey !== '') {
                    if (!isset($config['theme_options'][$presetKey])) {
                        $config['theme_options'][$presetKey] = [];
                    }
                    if (isset($_POST['theme_opts']) && is_array($_POST['theme_opts'])) {
                        foreach ($_POST['theme_opts'] as $optKey => $optVal) {
                            $optKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $optKey);
                            if ($optKey === '') continue;
                            if (is_array($optVal)) continue;
                            if ($optVal === '1' || $optVal === 'true') {
                                $config['theme_options'][$presetKey][$optKey] = true;
                            } elseif ($optVal === '0' || $optVal === 'false') {
                                $config['theme_options'][$presetKey][$optKey] = false;
                            } else {
                                $config['theme_options'][$presetKey][$optKey] = trim((string)$optVal);
                            }
                        }
                    }
                }
                break;
            case 'save_sections':
                $newSections = $_POST['sections'] ?? [];
                if (is_array($newSections) && !empty($newSections)) {
                    $updatedSections = [];
                    foreach ($newSections as $index => $sectionData) {
                        $sectionId = normalize_section_id(trim((string)($sectionData['id'] ?? '')));
                        if ($sectionId === '') continue;
                        
                        $originalSection = null;
                        foreach ($config['sections'] as $origSec) {
                            if (normalize_section_id((string)($origSec['id'] ?? '')) === $sectionId) {
                                $originalSection = $origSec;
                                break;
                            }
                        }
                        
                        $updatedSections[] = [
                            'id' => $sectionId,
                            'title' => $originalSection['title'] ?? '',
                            'subtitle' => $originalSection['subtitle'] ?? '',
                            'enabled' => !empty($sectionData['enabled']),
                            'custom_title' => trim((string)($sectionData['custom_title'] ?? '')),
                            'custom_subtitle' => trim((string)($sectionData['custom_subtitle'] ?? '')),
                            'order' => (int)($sectionData['order'] ?? 0)
                        ];
                    }
                    usort($updatedSections, function($a, $b) {
                        return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
                    });
                    $config['sections'] = $updatedSections;
                }
                break;
            case 'save_custom_css':
                $customCss = (string)($_POST['custom_css'] ?? '');
                $validation = validate_custom_css($customCss);
                $saveConfig = false;
                if (empty($validation['valid'])) {
                    $error = $validation['message'] ?: 'Custom CSS tidak valid.';
                } elseif (!save_custom_css($customCss)) {
                    $error = 'Gagal menyimpan Custom CSS.';
                } else {
                    $success = 'Custom CSS berhasil disimpan.';
                }
                break;
            case 'save_theme':
                $selectedPreset = trim((string)($_POST['theme_preset'] ?? ($config['theme']['theme_preset'] ?? 'elegant')));
                $config['theme']['mode'] = ($selectedPreset === 'custom') ? 'custom' : 'preset';
                if ($selectedPreset !== 'custom' && array_key_exists($selectedPreset, theme_presets())) {
                    $config['theme'] = apply_theme_preset($config['theme'], $selectedPreset);
                    // After applying preset, set theme_preset to the selected value but keep manual edits possible
                    $config['theme']['theme_preset'] = $selectedPreset;
                    // Also update hero settings from POST if provided (allow override after preset)
                    if (isset($_POST['hero_height'])) {
                        $config['theme']['hero_height'] = trim((string)$_POST['hero_height']) ?: ($config['theme']['hero_height'] ?? '100vh');
                    }
                    if (isset($_POST['hero_vertical_alignment'])) {
                        $config['theme']['hero_vertical_alignment'] = trim((string)$_POST['hero_vertical_alignment']) ?: ($config['theme']['hero_vertical_alignment'] ?? 'center');
                    }
                    if (isset($_POST['hero_content_width'])) {
                        $config['theme']['hero_content_width'] = trim((string)$_POST['hero_content_width']) ?: ($config['theme']['hero_content_width'] ?? '900px');
                    }
                    if (isset($_POST['hero_image_fit'])) {
                        $config['theme']['hero_image_fit'] = trim((string)$_POST['hero_image_fit']) ?: ($config['theme']['hero_image_fit'] ?? 'cover');
                    }
                    if (isset($_POST['hero_image_position'])) {
                        $config['theme']['hero_image_position'] = trim((string)$_POST['hero_image_position']) ?: ($config['theme']['hero_image_position'] ?? 'center');
                    }
                    if (isset($_POST['mobile_hero_height'])) {
                        $config['theme']['mobile_hero_height'] = trim((string)$_POST['mobile_hero_height']) ?: ($config['theme']['mobile_hero_height'] ?? '85vh');
                    }
                    if (isset($_POST['mobile_hero_vertical_alignment'])) {
                        $config['theme']['mobile_hero_vertical_alignment'] = trim((string)$_POST['mobile_hero_vertical_alignment']) ?: ($config['theme']['mobile_hero_vertical_alignment'] ?? 'center');
                    }
                    if (isset($_POST['mobile_hero_content_width'])) {
                        $config['theme']['mobile_hero_content_width'] = trim((string)$_POST['mobile_hero_content_width']) ?: ($config['theme']['mobile_hero_content_width'] ?? '100%');
                    }
                    if (isset($_POST['mobile_hero_image_fit'])) {
                        $config['theme']['mobile_hero_image_fit'] = trim((string)$_POST['mobile_hero_image_fit']) ?: ($config['theme']['mobile_hero_image_fit'] ?? 'cover');
                    }
                    if (isset($_POST['mobile_hero_image_position'])) {
                        $config['theme']['mobile_hero_image_position'] = trim((string)$_POST['mobile_hero_image_position']) ?: ($config['theme']['mobile_hero_image_position'] ?? 'center top');
                    }
                    if (isset($_POST['buttons_mobile_layout'])) {
                        $config['buttons']['mobile_layout'] = trim((string)$_POST['buttons_mobile_layout']) ?: ($config['buttons']['mobile_layout'] ?? '2-columns');
                    }
                    break;
                }
                $config['theme']['mode'] = 'custom';
                $config['theme']['theme_preset'] = 'custom';
                $config['theme']['primary_color'] = trim((string)($_POST['primary_color'] ?? '')) ?: $config['theme']['primary_color'];
                $config['theme']['secondary_color'] = trim((string)($_POST['secondary_color'] ?? '')) ?: $config['theme']['secondary_color'];
                $config['theme']['accent_color'] = trim((string)($_POST['accent_color'] ?? '')) ?: $config['theme']['accent_color'];
                $config['theme']['background_color'] = trim((string)($_POST['background_color'] ?? '')) ?: $config['theme']['background_color'];
                $config['theme']['paper_color'] = trim((string)($_POST['paper_color'] ?? '')) ?: ($config['theme']['paper_color'] ?? '#ffffff');
                $config['theme']['muted_color'] = trim((string)($_POST['muted_color'] ?? '')) ?: ($config['theme']['muted_color'] ?? '#806f66');
                $config['theme']['text_color'] = trim((string)($_POST['text_color'] ?? '')) ?: $config['theme']['text_color'];
                $config['theme']['link_color'] = trim((string)($_POST['link_color'] ?? '')) ?: $config['theme']['link_color'];
                $config['theme']['heading_font'] = trim((string)($_POST['heading_font'] ?? '')) ?: $config['theme']['heading_font'];
                $config['theme']['body_font'] = trim((string)($_POST['body_font'] ?? '')) ?: $config['theme']['body_font'];
                $config['theme']['font_size_base'] = trim((string)($_POST['font_size_base'] ?? '')) ?: $config['theme']['font_size_base'];
                $config['theme']['container_width'] = trim((string)($_POST['container_width'] ?? '')) ?: $config['theme']['container_width'];
                $config['theme']['section_spacing'] = trim((string)($_POST['section_spacing'] ?? '')) ?: $config['theme']['section_spacing'];
                $config['theme']['border_radius'] = trim((string)($_POST['border_radius'] ?? '')) ?: $config['theme']['border_radius'];
                $config['theme']['shadow'] = trim((string)($_POST['shadow'] ?? '')) ?: $config['theme']['shadow'];
                $config['theme']['button_style'] = trim((string)($_POST['button_style'] ?? '')) ?: $config['theme']['button_style'];
                $config['theme']['navbar_style'] = trim((string)($_POST['navbar_style'] ?? '')) ?: $config['theme']['navbar_style'];
                $config['theme']['card_style'] = trim((string)($_POST['card_style'] ?? '')) ?: $config['theme']['card_style'];
                $config['theme']['footer_style'] = trim((string)($_POST['footer_style'] ?? '')) ?: $config['theme']['footer_style'];
                $config['theme']['animation_enabled'] = !empty($_POST['animation_enabled']);
                // Desktop hero settings
                $config['theme']['hero_height'] = trim((string)($_POST['hero_height'] ?? '')) ?: ($config['theme']['hero_height'] ?? '100vh');
                $config['theme']['hero_vertical_alignment'] = trim((string)($_POST['hero_vertical_alignment'] ?? '')) ?: ($config['theme']['hero_vertical_alignment'] ?? 'center');
                $config['theme']['hero_content_width'] = trim((string)($_POST['hero_content_width'] ?? '')) ?: ($config['theme']['hero_content_width'] ?? '900px');
                $config['theme']['hero_image_fit'] = trim((string)($_POST['hero_image_fit'] ?? '')) ?: ($config['theme']['hero_image_fit'] ?? 'cover');
                $config['theme']['hero_image_position'] = trim((string)($_POST['hero_image_position'] ?? '')) ?: ($config['theme']['hero_image_position'] ?? 'center');
                // Mobile hero settings
                $config['theme']['mobile_hero_height'] = trim((string)($_POST['mobile_hero_height'] ?? '')) ?: ($config['theme']['mobile_hero_height'] ?? '85vh');
                $config['theme']['mobile_hero_vertical_alignment'] = trim((string)($_POST['mobile_hero_vertical_alignment'] ?? '')) ?: ($config['theme']['mobile_hero_vertical_alignment'] ?? 'center');
                $config['theme']['mobile_hero_content_width'] = trim((string)($_POST['mobile_hero_content_width'] ?? '')) ?: ($config['theme']['mobile_hero_content_width'] ?? '100%');
                $config['theme']['mobile_hero_image_fit'] = trim((string)($_POST['mobile_hero_image_fit'] ?? '')) ?: ($config['theme']['mobile_hero_image_fit'] ?? 'cover');
                $config['theme']['mobile_hero_image_position'] = trim((string)($_POST['mobile_hero_image_position'] ?? '')) ?: ($config['theme']['mobile_hero_image_position'] ?? 'center top');
                // Mobile button layout
                $config['buttons']['mobile_layout'] = trim((string)($_POST['buttons_mobile_layout'] ?? '')) ?: ($config['buttons']['mobile_layout'] ?? '2-columns');
                break;
            case 'save_love_story':
                // Handle love story CRUD operations
                $action = $_POST['story_action'] ?? '';
                if ($action === 'add') {
                    $newItem = [
                        'id' => uniqid('story_', true),
                        'title' => trim((string)($_POST['title'] ?? '')),
                        'subtitle' => trim((string)($_POST['subtitle'] ?? '')),
                        'description' => trim((string)($_POST['description'] ?? '')),
                        'event_date' => trim((string)($_POST['event_date'] ?? '')),
                        'image' => '',
                        'image_alt' => trim((string)($_POST['image_alt'] ?? '')),
                        'image_caption' => trim((string)($_POST['image_caption'] ?? '')),
                        'icon' => trim((string)($_POST['icon'] ?? '')),
                        'enabled' => !empty($_POST['enabled']),
                        'order' => count($config['love_story']['items']) + 1
                    ];
                    $config['love_story']['items'][] = $newItem;
                } elseif ($action === 'edit') {
                    $storyId = $_POST['story_id'] ?? '';
                    foreach ($config['love_story']['items'] as &$item) {
                        if (($item['id'] ?? '') === $storyId) {
                            $item['title'] = trim((string)($_POST['title'] ?? $item['title']));
                            $item['subtitle'] = trim((string)($_POST['subtitle'] ?? $item['subtitle']));
                            $item['description'] = trim((string)($_POST['description'] ?? $item['description']));
                            $item['event_date'] = trim((string)($_POST['event_date'] ?? $item['event_date']));
                            $item['image_alt'] = trim((string)($_POST['image_alt'] ?? $item['image_alt']));
                            $item['image_caption'] = trim((string)($_POST['image_caption'] ?? $item['image_caption']));
                            $item['icon'] = trim((string)($_POST['icon'] ?? $item['icon']));
                            $item['enabled'] = !empty($_POST['enabled']);
                            break;
                        }
                    }
                } elseif ($action === 'delete') {
                    $storyId = $_POST['story_id'] ?? '';
                    $config['love_story']['items'] = array_values(array_filter(
                        $config['love_story']['items'],
                        fn($item) => ($item['id'] ?? '') !== $storyId
                    ));
                } elseif ($action === 'reorder') {
                    $orderArray = json_decode($_POST['order_array'] ?? '[]', true);
                    if (is_array($orderArray)) {
                        $orderedItems = [];
                        foreach ($orderArray as $index => $storyId) {
                            foreach ($config['love_story']['items'] as $item) {
                                if (($item['id'] ?? '') === $storyId) {
                                    $item['order'] = $index + 1;
                                    $orderedItems[] = $item;
                                    break;
                                }
                            }
                        }
                        // Add any items not in the order array (new items)
                        foreach ($config['love_story']['items'] as $item) {
                            if (!in_array($item['id'] ?? '', $orderArray, true)) {
                                $orderedItems[] = $item;
                            }
                        }
                        $config['love_story']['items'] = $orderedItems;
                    }
                }
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = upload_file($_FILES['image'], 'image', UPLOADS_LOVE_STORY_DIR);
                    if ($uploadResult['success']) {
                        $uploadedPath = $uploadResult['path'];
                        $storyId = $_POST['story_id'] ?? ($_POST['new_story_temp_id'] ?? '');
                        if (!empty($storyId) && $action === 'edit') {
                            foreach ($config['love_story']['items'] as &$item) {
                                if (($item['id'] ?? '') === $storyId) {
                                    $item['image'] = basename($uploadedPath);
                                    break;
                                }
                            }
                        } elseif ($action === 'add' && !empty($config['love_story']['items'])) {
                            // Set image for the last added item
                            $lastIndex = count($config['love_story']['items']) - 1;
                            $config['love_story']['items'][$lastIndex]['image'] = basename($uploadedPath);
                        }
                    }
                }
                break;
            case 'upload_bride_photo':
                if (!empty($_FILES['bride_photo']['name'])) {
                    $result = upload_file($_FILES['bride_photo'], UPLOADS_COVER_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
                    if (!empty($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $config['media']['bride_photo'] = relative_path($result['path']);
                    }
                }
                break;
            case 'upload_groom_photo':
                if (!empty($_FILES['groom_photo']['name'])) {
                    $result = upload_file($_FILES['groom_photo'], UPLOADS_COVER_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
                    if (!empty($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $config['media']['groom_photo'] = relative_path($result['path']);
                    }
                }
                break;
            case 'upload_couple_photo':
                if (!empty($_FILES['couple_photo']['name'])) {
                    $result = upload_file($_FILES['couple_photo'], UPLOADS_COVER_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
                    if (!empty($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $config['media']['couple_photo'] = relative_path($result['path']);
                    }
                }
                break;
            case 'save_guest_link':
                $saveConfig = false;
                $guestName = trim((string)($_POST['guest_name'] ?? ''));
                $baseUrl = trim((string)($_POST['base_url'] ?? $config['site']['url'] ?? ''));
                if ($guestName === '') {
                    $error = 'Nama tamu wajib diisi.';
                    break;
                }
                if ($baseUrl === '') {
                    $error = 'Site URL belum dikonfigurasi.';
                    break;
                }
                $baseUrl = rtrim($baseUrl, '/') . '/';
                $invitationUrl = $baseUrl . '?to=' . rawurlencode($guestName);
                $guestLinks = load_guest_links();
                array_unshift($guestLinks, [
                    'guest_name' => $guestName,
                    'invitation_url' => $invitationUrl,
                    'created_at' => gmdate('c')
                ]);
                if (!save_guest_links($guestLinks)) {
                    $error = 'Gagal menyimpan link tamu.';
                } else {
                    $success = 'Link tamu berhasil disimpan.';
                }
                break;
            case 'delete_guest_link':
                $saveConfig = false;
                $deleteIndex = filter_var($_POST['delete_index'] ?? '', FILTER_VALIDATE_INT);
                if ($deleteIndex === false || $deleteIndex < 0) {
                    $error = 'Link tidak valid.';
                    break;
                }
                $guestLinks = load_guest_links();
                if (!isset($guestLinks[$deleteIndex])) {
                    $error = 'Link tamu tidak ditemukan.';
                    break;
                }
                array_splice($guestLinks, $deleteIndex, 1);
                if (!save_guest_links($guestLinks)) {
                    $error = 'Gagal menghapus link tamu.';
                } else {
                    $success = 'Link tamu berhasil dihapus.';
                }
                break;
            case 'save_seo':
                $config['site']['title'] = trim((string)($_POST['seo_title'] ?? '')) ?: $config['site']['title'];
                $config['site']['description'] = trim((string)($_POST['seo_description'] ?? '')) ?: $config['site']['description'];
                $config['site']['keywords'] = trim((string)($_POST['seo_keywords'] ?? '')) ?: $config['site']['keywords'];
                $config['site']['open_graph_title'] = trim((string)($_POST['og_title'] ?? '')) ?: $config['site']['open_graph_title'];
                $config['site']['open_graph_description'] = trim((string)($_POST['og_description'] ?? '')) ?: $config['site']['open_graph_description'];
                $config['site']['twitter_card'] = trim((string)($_POST['twitter_card'] ?? '')) ?: $config['site']['twitter_card'];
                $config['site']['schema'] = trim((string)($_POST['schema_json'] ?? '')) ?: $config['site']['schema'];
                break;
            case 'save_settings':
                $config['site']['url'] = trim((string)($_POST['site_url'] ?? '')) ?: $config['site']['url'];
                $config['admin']['username'] = trim((string)($_POST['admin_username'] ?? '')) ?: $config['admin']['username'];
                $newPassword = trim((string)($_POST['admin_password'] ?? ''));
                if ($newPassword !== '') {
                    set_admin_password($newPassword, $config);
                }
                break;
            case 'upload_cover':
                if (!empty($_FILES['cover_image']['name'])) {
                    $result = upload_file($_FILES['cover_image'], UPLOADS_COVER_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
                    if (!empty($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $config['media']['cover'] = relative_path($result['path']);
                    }
                }
                break;
            case 'upload_music':
                if (!empty($_FILES['music_file']['name'])) {
                    $result = upload_file($_FILES['music_file'], UPLOADS_MUSIC_DIR, ALLOWED_AUDIO_TYPES, MAX_MUSIC_UPLOAD_SIZE);
                    if (!empty($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $config['media']['music'] = relative_path($result['path']);
                    }
                }
                break;
            case 'upload_background':
                if (!empty($_FILES['background_hero']['name'])) {
                    $result = upload_file($_FILES['background_hero'], UPLOADS_BACKGROUND_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
                    if (!empty($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $config['media']['background_hero'] = relative_path($result['path']);
                    }
                }
                for ($i = 1; $i <= 3; $i++) {
                    $field = 'background_section_' . $i;
                    if (!empty($_FILES[$field]['name'])) {
                        $result = upload_file($_FILES[$field], UPLOADS_BACKGROUND_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
                        if (!empty($result['error'])) {
                            $error = $result['error'];
                            break;
                        }
                        $config['media']['background_sections'][$i - 1] = relative_path($result['path']);
                    }
                }
                break;
            case 'upload_qris':
                if (!empty($_FILES['qris_image']['name'])) {
                    $result = upload_file($_FILES['qris_image'], UPLOADS_COVER_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
                    if (!empty($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $config['gift']['qris_image'] = relative_path($result['path']);
                    }
                }
                break;
            case 'upload_og_image':
                if (!empty($_FILES['og_image']['name'])) {
                    $result = upload_file($_FILES['og_image'], UPLOADS_COVER_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
                    if (!empty($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $config['site']['open_graph_image'] = relative_path($result['path']);
                    }
                }
                break;
            case 'upload_gallery':
                if (!empty($_FILES['gallery_files']['name'])) {
                    $files = $_FILES['gallery_files'];
                    foreach ($files['name'] as $index => $originalName) {
                        if (!isset($files['tmp_name'][$index]) || $files['error'][$index] !== UPLOAD_ERR_OK) {
                            continue;
                        }
                        $file = [
                            'name' => $originalName,
                            'tmp_name' => $files['tmp_name'][$index],
                            'error' => $files['error'][$index],
                            'size' => $files['size'][$index]
                        ];
                        $result = upload_file($file, UPLOADS_GALLERY_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
                        if (!empty($result['error'])) {
                            $error = $result['error'];
                            break;
                        }
                        $config['gallery']['items'][] = ['filename' => relative_path($result['path']), 'order' => time() + $index];
                    }
                }
                break;
            case 'delete_gallery_item':
                $filename = trim((string)($_POST['gallery_filename'] ?? ''));
                if ($filename !== '') {
                    $path = ROOT_DIR . '/' . ltrim($filename, '/');
                    if (is_file($path) && strpos(realpath($path), realpath(UPLOADS_GALLERY_DIR)) === 0) {
                        @unlink($path);
                    }
                    foreach ($config['gallery']['items'] as $index => $item) {
                        if (($item['filename'] ?? '') === $filename) {
                            unset($config['gallery']['items'][$index]);
                        }
                    }
                    if (($config['gallery']['cover'] ?? '') === $filename) {
                        $config['gallery']['cover'] = '';
                    }
                    $config['gallery']['items'] = array_values($config['gallery']['items']);
                }
                break;
            case 'set_gallery_cover':
                $filename = trim((string)($_POST['gallery_filename'] ?? ''));
                if ($filename !== '') {
                    $config['gallery']['cover'] = $filename;
                }
                break;
            case 'save_gallery_order':
                $orders = $_POST['gallery_order'] ?? [];
                if (is_array($orders)) {
                    foreach ($config['gallery']['items'] as $index => $item) {
                        $file = $item['filename'] ?? '';
                        if ($file !== '' && isset($orders[$file])) {
                            $config['gallery']['items'][$index]['order'] = (int)$orders[$file];
                        }
                    }
                }
                $selectedCover = trim((string)($_POST['gallery_cover'] ?? ''));
                if ($selectedCover !== '') {
                    $config['gallery']['cover'] = $selectedCover;
                }
                break;
            default:
                $error = 'Aksi tidak dikenal.';
        }
        if ($error === '') {
            if (!empty($saveConfig)) {
                if (!save_config($config)) {
                    $error = 'Gagal menyimpan konfigurasi.';
                } else {
                    $success = 'Pengaturan berhasil disimpan.';
                    $config = load_config();
                }
            }
        }
    }
}

$guestLinks = load_guest_links();
$galleryItems = get_gallery_items($config);
$mediaSearch = strtolower(trim((string)($_GET['media_search'] ?? '')));
$mediaType = strtolower(trim((string)($_GET['media_type'] ?? 'all')));
$mediaLibrary = list_media_library(['search' => $mediaSearch, 'type' => $mediaType]);
$invitationPreview = build_invitation_preview_url($config);
$siteUrl = trim($config['site']['url']);
$coverPreview = $config['media']['cover'] ?: 'uploads/cover/cover.jpg';
$bridePhotoPreview = $config['media']['bride_photo'] ?? '';
$groomPhotoPreview = $config['media']['groom_photo'] ?? '';
$couplePhotoPreview = $config['media']['couple_photo'] ?? '';
$ogPreview = $config['site']['open_graph_image'];
$backgroundHeroPreview = $config['media']['background_hero'];
$backgroundSectionPreviews = [
    $config['media']['background_sections'][0] ?? '',
    $config['media']['background_sections'][1] ?? '',
    $config['media']['background_sections'][2] ?? ''
];
$qrisPreview = $config['gift']['qris_image'];
$customCss = load_custom_css();
$themePresetPreviewData = [];
foreach (theme_presets() as $presetKey => $preset) {
    $themePresetPreviewData[$presetKey] = $preset['values'] ?? [];
}
$themeRegistry = theme_registry();
$themeMode = get_theme_mode($config);
$themeMeta = get_active_theme_meta($config);
$themePresentationCaps = theme_presentation_capabilities($config);
$themePreviewConfig = $config['theme'] ?? [];
// Ensure hero settings are included in preview config for backward compatibility
if (!isset($themePreviewConfig['hero_height'])) {
    $themePreviewConfig['hero_height'] = '100vh';
}
if (!isset($themePreviewConfig['hero_vertical_alignment'])) {
    $themePreviewConfig['hero_vertical_alignment'] = 'center';
}
if (!isset($themePreviewConfig['hero_content_width'])) {
    $themePreviewConfig['hero_content_width'] = '900px';
}
if (!isset($themePreviewConfig['hero_image_fit'])) {
    $themePreviewConfig['hero_image_fit'] = 'cover';
}
if (!isset($themePreviewConfig['hero_image_position'])) {
    $themePreviewConfig['hero_image_position'] = 'center';
}
if (!isset($themePreviewConfig['mobile_hero_height'])) {
    $themePreviewConfig['mobile_hero_height'] = '85vh';
}
if (!isset($themePreviewConfig['mobile_hero_vertical_alignment'])) {
    $themePreviewConfig['mobile_hero_vertical_alignment'] = 'center';
}
if (!isset($themePreviewConfig['mobile_hero_content_width'])) {
    $themePreviewConfig['mobile_hero_content_width'] = '100%';
}
if (!isset($themePreviewConfig['mobile_hero_image_fit'])) {
    $themePreviewConfig['mobile_hero_image_fit'] = 'cover';
}
if (!isset($themePreviewConfig['mobile_hero_image_position'])) {
    $themePreviewConfig['mobile_hero_image_position'] = 'center top';
}
// Ensure buttons settings are included in preview config
if (!isset($themePreviewConfig['buttons'])) {
    $themePreviewConfig['buttons'] = [];
}
if (!isset($themePreviewConfig['buttons']['mobile_layout'])) {
    $themePreviewConfig['buttons']['mobile_layout'] = '2-columns';
}


?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Wedding CMS</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="container">
        <?php if (empty($_SESSION['admin'])): ?>
            <div class="card" style="max-width:420px;margin:80px auto;">
                <h2>Login Admin</h2>
                <?php if ($error): ?><div class="error"><?php echo escape_html($error); ?></div><?php endif; ?>
                <form method="post">
                    <div class="form-row"><label>Username</label><input type="text" name="username" required></div>
                    <div class="form-row"><label>Password</label><input type="password" name="password" required></div>
                    <button type="submit" name="login">Masuk</button>
                </form>
            </div>
        <?php else: ?>
            <div class="header">
                <div>
                    <h1>Dashboard Wedding CMS</h1>
                    <p style="margin:4px 0;color:#6b5b45;">Kelola undangan tanpa mengedit kode.</p>
                </div>
                <a href="?logout=1">Logout</a>
            </div>
            <?php if ($success): ?><div class="notice"><?php echo escape_html($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="error"><?php echo escape_html($error); ?></div><?php endif; ?>

            <div class="layout">
                <aside class="sidebar">
                    <nav>
                        <a href="#dashboard">Dasbor</a>
                        <a href="#wedding">Informasi Pernikahan</a>
                        <a href="#parents">Orang Tua</a>
                        <a href="#schedule">Jadwal</a>
                        <a href="#countdown">Hitung Mundur</a>
                        <a href="#sections">Bagian Website</a>
                        <a href="#theme">Tema & Tampilan</a>
                        <a href="#custom-css">CSS Khusus</a>
                        <a href="#file-manager">Kelola Media</a>
                        <a href="#love-story">Cerita Cinta</a>
                        <a href="#gallery">Galeri</a>
                        <a href="#cover">Cover</a>
                        <a href="#background">Background</a>
                        <a href="#music">Musik</a>
                        <a href="#gift">Hadiah</a>
                        <a href="#dresscode">Dresscode</a>
                        <a href="#maps">Lokasi</a>
                        <a href="#seo">SEO</a>
                        <a href="#whatsapp">WhatsApp</a>
                        <a href="#guest-links">Link Tamu</a>
                        <a href="#rsvp">RSVP</a>
                        <a href="#backup">Backup</a>
                        <a href="#settings">Pengaturan</a>
                    </nav>
                </aside>

                <main class="content">
                    <section id="dashboard" class="card panel-section">
                        <h2>Ringkasan</h2>
                        <p>Undangan Anda dapat dikelola melalui panel ini. Setiap perubahan otomatis akan mempengaruhi halaman utama tanpa perlu edit HTML, CSS, JS, atau PHP.</p>
                        <div class="form-row">
                            <label>Contoh tautan personalisasi</label>
                            <input type="text" readonly value="<?php echo escape_html($invitationPreview); ?>" id="invitationLink" style="background:#f7f3ed;color:#333;" />
                            <button type="button" class="button small-button" id="copyInvitationLink">Salin Tautan</button>
                        </div>
                    </section>

                    <section id="wedding" class="card panel-section">
                        <h2>Informasi Pernikahan</h2>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_wedding">
                            <div class="form-grid">
                                <div class="form-row"><label>Nama Mempelai Wanita</label><input type="text" name="bride_name" value="<?php echo escape_html($config['wedding']['bride_name']); ?>" required></div>
                                <div class="form-row"><label>Nama Mempelai Pria</label><input type="text" name="groom_name" value="<?php echo escape_html($config['wedding']['groom_name']); ?>" required></div>
                                <div class="form-row"><label>Judul Undangan</label><input type="text" name="title" value="<?php echo escape_html($config['wedding']['title']); ?>"></div>
                                <div class="form-row"><label>Text Pembuka</label><textarea name="opening_text"><?php echo escape_html($config['wedding']['opening_text']); ?></textarea></div>
                                <div class="form-row"><label>Text Penutup</label><textarea name="closing_text"><?php echo escape_html($config['wedding']['closing_text']); ?></textarea></div>
                                <div class="form-row"><label>Quote</label><textarea name="quote"><?php echo escape_html($config['wedding']['quote']); ?></textarea></div>
                                <div class="form-row"><label>Nama Panggilan Mempelai Wanita</label><input type="text" name="bride_nickname" value="<?php echo escape_html($config['wedding']['bride_nickname']); ?>"></div>
                                <div class="form-row"><label>Nama Panggilan Mempelai Pria</label><input type="text" name="groom_nickname" value="<?php echo escape_html($config['wedding']['groom_nickname']); ?>"></div>
                            </div>
                            <button type="submit">Simpan Informasi Pernikahan</button>
                        </form>
                    </section>

                    <section id="parents" class="card panel-section">
                        <h2>Orang Tua</h2>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_parents">
                            <div class="form-grid">
                                <div class="form-row"><label>Ayah Mempelai Wanita</label><input type="text" name="bride_father" value="<?php echo escape_html($config['parents']['bride_father']); ?>"></div>
                                <div class="form-row"><label>Ibu Mempelai Wanita</label><input type="text" name="bride_mother" value="<?php echo escape_html($config['parents']['bride_mother']); ?>"></div>
                                <div class="form-row"><label>Ayah Mempelai Pria</label><input type="text" name="groom_father" value="<?php echo escape_html($config['parents']['groom_father']); ?>"></div>
                                <div class="form-row"><label>Ibu Mempelai Pria</label><input type="text" name="groom_mother" value="<?php echo escape_html($config['parents']['groom_mother']); ?>"></div>
                            </div>
                            <button type="submit">Simpan Orang Tua</button>
                        </form>
                    </section>

                    <section id="schedule" class="card panel-section">
                        <h2>Jadwal</h2>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_schedule">
                            <div class="form-grid">
                                <div class="form-row"><label>Tanggal Akad</label><input type="date" name="akad_date" value="<?php echo escape_html($config['schedule']['akad_date']); ?>" required></div>
                                <div class="form-row"><label>Jam Akad</label><input type="time" name="akad_time" value="<?php echo escape_html($config['schedule']['akad_time']); ?>" required></div>
                                <div class="form-row"><label>Tanggal Resepsi</label><input type="date" name="reception_date" value="<?php echo escape_html($config['schedule']['reception_date']); ?>" required></div>
                                <div class="form-row"><label>Jam Resepsi</label><input type="time" name="reception_time" value="<?php echo escape_html($config['schedule']['reception_time']); ?>" required></div>
                                <div class="form-row"><label>Zona Waktu</label><input type="text" name="timezone" value="<?php echo escape_html($config['schedule']['timezone']); ?>" required></div>
                                <div class="form-row" style="grid-column:span 2;"><label>Tautan Kalender Google</label><input type="url" name="google_calendar_link" value="<?php echo escape_html($config['schedule']['google_calendar_link']); ?>"></div>
                            </div>
                            <button type="submit">Simpan Jadwal</button>
                        </form>
                    </section>

                    <section id="countdown" class="card panel-section">
                        <h2>Hitung Mundur</h2>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_schedule">
                            <div class="form-grid">
                                <div class="form-row" style="grid-column:span 2;"><label>Target Hitung Mundur</label><input type="text" name="countdown_target" value="<?php echo escape_html($config['schedule']['countdown_target']); ?>" placeholder="2026-12-29T09:00:00+07:00"></div>
                            </div>
                            <p style="font-size:0.95rem;color:#5c4c32;">Hitung mundur akan otomatis diperbarui saat tanggal akad, jam, atau zona waktu diubah.</p>
                            <button type="submit">Simpan Hitung Mundur</button>
                        </form>
                    </section>

                    <section id="sections" class="card panel-section">
                        <h2>Sections</h2>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_sections">
                            <p style="margin-bottom:1rem;color:#5c4c32;">Kelola tampilan bagian website. Aktifkan/nonaktifkan, ubah urutan, atau sesuaikan judul setiap bagian.</p>
                            <div id="sections-list">
                                <?php 
                                $sections = $config['sections'] ?? [];
                                usort($sections, function($a, $b) {
                                    return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
                                });
                                foreach ($sections as $index => $section): 
                                ?>
                                <div class="section-item" style="background:#f7f3ed;padding:1rem;margin-bottom:1rem;border-radius:8px;">
                                    <input type="hidden" name="sections[<?php echo $index; ?>][id]" value="<?php echo escape_html($section['id']); ?>">
                                    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:0.5rem;">
                                        <span style="font-weight:bold;color:#c84c47;"><?php echo escape_html($section['title']); ?></span>
                                        <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.9rem;">
                                            <input type="checkbox" name="sections[<?php echo $index; ?>][enabled]" value="1" <?php echo !empty($section['enabled']) ? 'checked' : ''; ?>>
                                            Enabled
                                        </label>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:1rem;">
                                        <div class="form-row"><label>Custom Title</label><input type="text" name="sections[<?php echo $index; ?>][custom_title]" value="<?php echo escape_html($section['custom_title'] ?? ''); ?>" placeholder="Default: <?php echo escape_html($section['title']); ?>"></div>
                                        <div class="form-row"><label>Custom Subtitle</label><input type="text" name="sections[<?php echo $index; ?>][custom_subtitle]" value="<?php echo escape_html($section['custom_subtitle'] ?? ''); ?>" placeholder="Default: <?php echo escape_html($section['subtitle'] ?? ''); ?>"></div>
                                        <div class="form-row"><label>Order</label><input type="number" name="sections[<?php echo $index; ?>][order]" value="<?php echo escape_html((string)($section['order'] ?? $index)); ?>" style="width:80px;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit">Simpan Sections</button>
                        </form>
                    </section>

                    <section id="theme" class="card panel-section">
                        <h2>Tema & Tampilan</h2>
                        <p class="section-description">Ubah gaya undangan dan lihat hasilnya langsung di preview sebelum menyimpan.</p>
                        <div class="theme-editor-layout">
                        <form method="post" id="themeSettingsForm" data-saved-theme='<?php echo escape_html(json_encode($themePreviewConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>' data-theme-presets='<?php echo escape_html(json_encode($themePresetPreviewData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>'>
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_theme">
                            
                            <h3 style="margin:1.5rem 0 1rem;color:#c84c47;">Theme Preset</h3>
                            <div class="form-row">
                                <label>Mode Tema</label>
                                <select name="theme_mode" id="themeModeSelect">
                                    <option value="preset" <?php echo $themeMode === 'preset' ? 'selected' : ''; ?>>Preset</option>
                                    <option value="custom" <?php echo $themeMode === 'custom' ? 'selected' : ''; ?>>Custom</option>
                                </select>
                                <small>Pilihan mode menentukan apakah presentasi dikendalikan oleh preset yang dipilih atau manual editor di bawah ini.</small>
                            </div>
                            <div class="form-row">
                                <label>Preset Tema</label>
                                <select name="theme_preset">
                                    <?php foreach (theme_presets() as $presetKey => $preset): ?>
                                        <option value="<?php echo escape_html($presetKey); ?>" <?php echo ($config['theme']['theme_preset'] ?? 'elegant') === $presetKey ? 'selected' : ''; ?>><?php echo escape_html($preset['label'] ?? $presetKey); ?> • <?php echo escape_html($preset['category'] ?? 'theme'); ?> • v<?php echo escape_html($preset['version'] ?? '1.0.0'); ?> • <?php echo escape_html($preset['description']); ?></option>
                                    <?php endforeach; ?>
                                    <option value="custom" <?php echo ($config['theme']['theme_preset'] ?? '') === 'custom' ? 'selected' : ''; ?>>Custom - Gunakan nilai manual di bawah</option>
                                </select>
                                <small>Preset mengubah variabel CSS tema. Setiap preset mencatat metadata seperti kategori, versi, sumber, dan kompatibilitas untuk ekspansi tema di masa depan.</small>
                            </div>
                            <?php if ($themeMode === 'preset'): ?>
                                <div class="notice" style="margin:0.5rem 0 1rem;">Presentasi dikendalikan oleh preset yang dipilih. Kontrol manual tema disembunyikan agar tidak bertentangan dengan preset aktif.</div>
                            <?php endif; ?>

                            <?php if ($themeMode === 'custom'): ?>
                            <h3 style="margin:1.5rem 0 1rem;color:#c84c47;">Colors</h3>
                            <div class="form-grid">
                                <div class="form-row"><label>Primary Color</label><input type="color" name="primary_color" value="<?php echo escape_html($config['theme']['primary_color']); ?>" style="width:100%;height:40px;"></div>
                                <div class="form-row"><label>Secondary Color</label><input type="color" name="secondary_color" value="<?php echo escape_html($config['theme']['secondary_color']); ?>" style="width:100%;height:40px;"></div>
                                <div class="form-row"><label>Accent Color</label><input type="color" name="accent_color" value="<?php echo escape_html($config['theme']['accent_color']); ?>" style="width:100%;height:40px;"></div>
                                <div class="form-row"><label>Background Color</label><input type="color" name="background_color" value="<?php echo escape_html($config['theme']['background_color']); ?>" style="width:100%;height:40px;"></div>
                                <div class="form-row"><label>Paper Color</label><input type="color" name="paper_color" value="<?php echo escape_html($config['theme']['paper_color'] ?? '#ffffff'); ?>" style="width:100%;height:40px;"></div>
                                <div class="form-row"><label>Muted Color</label><input type="color" name="muted_color" value="<?php echo escape_html($config['theme']['muted_color'] ?? '#806f66'); ?>" style="width:100%;height:40px;"></div>
                                <div class="form-row"><label>Text Color</label><input type="color" name="text_color" value="<?php echo escape_html($config['theme']['text_color']); ?>" style="width:100%;height:40px;"></div>
                                <div class="form-row"><label>Link Color</label><input type="color" name="link_color" value="<?php echo escape_html($config['theme']['link_color']); ?>" style="width:100%;height:40px;"></div>
                            </div>

                            <h3 style="margin:1.5rem 0 1rem;color:#c84c47;">Typography</h3>
                            <div class="form-grid">
                                <div class="form-row"><label>Heading Font</label><input type="text" name="heading_font" value="<?php echo escape_html($config['theme']['heading_font']); ?>" placeholder="e.g., Playfair Display, serif"></div>
                                <div class="form-row"><label>Body Font</label><input type="text" name="body_font" value="<?php echo escape_html($config['theme']['body_font']); ?>" placeholder="e.g., Lato, sans-serif"></div>
                                <div class="form-row"><label>Base Font Size</label><input type="text" name="font_size_base" value="<?php echo escape_html($config['theme']['font_size_base']); ?>" placeholder="e.g., 16px"></div>
                            </div>

                            <h3 style="margin:1.5rem 0 1rem;color:#c84c47;">Spacing & Layout</h3>
                            <div class="form-grid">
                                <div class="form-row"><label>Container Width</label><input type="text" name="container_width" value="<?php echo escape_html($config['theme']['container_width']); ?>" placeholder="e.g., 1200px"></div>
                                <div class="form-row"><label>Section Spacing</label><input type="text" name="section_spacing" value="<?php echo escape_html($config['theme']['section_spacing']); ?>" placeholder="e.g., 80px"></div>
                                <div class="form-row"><label>Border Radius</label><input type="text" name="border_radius" value="<?php echo escape_html($config['theme']['border_radius']); ?>" placeholder="e.g., 28px"></div>
                                <div class="form-row"><label>Shadow</label><input type="text" name="shadow" value="<?php echo escape_html($config['theme']['shadow']); ?>" placeholder="e.g., 0 22px 60px rgba(73,45,34,.14)"></div>
                            </div>

                            <h3 style="margin:1.5rem 0 1rem;color:#c84c47;">Styles</h3>
                            <div class="form-grid">
                                <div class="form-row">
                                    <label>Button Style</label>
                                    <select name="button_style">
                                        <option value="rounded" <?php echo $config['theme']['button_style'] === 'rounded' ? 'selected' : ''; ?>>Rounded</option>
                                        <option value="square" <?php echo $config['theme']['button_style'] === 'square' ? 'selected' : ''; ?>>Square</option>
                                        <option value="pill" <?php echo $config['theme']['button_style'] === 'pill' ? 'selected' : ''; ?>>Pill</option>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label>Navbar Style</label>
                                    <select name="navbar_style">
                                        <option value="transparent" <?php echo $config['theme']['navbar_style'] === 'transparent' ? 'selected' : ''; ?>>Transparent</option>
                                        <option value="sticky" <?php echo $config['theme']['navbar_style'] === 'sticky' ? 'selected' : ''; ?>>Sticky</option>
                                        <option value="solid" <?php echo $config['theme']['navbar_style'] === 'solid' ? 'selected' : ''; ?>>Solid</option>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label>Card Style</label>
                                    <select name="card_style">
                                        <option value="elevated" <?php echo $config['theme']['card_style'] === 'elevated' ? 'selected' : ''; ?>>Elevated</option>
                                        <option value="flat" <?php echo $config['theme']['card_style'] === 'flat' ? 'selected' : ''; ?>>Flat</option>
                                        <option value="outlined" <?php echo $config['theme']['card_style'] === 'outlined' ? 'selected' : ''; ?>>Outlined</option>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label>Footer Style</label>
                                    <select name="footer_style">
                                        <option value="centered" <?php echo $config['theme']['footer_style'] === 'centered' ? 'selected' : ''; ?>>Centered</option>
                                        <option value="left" <?php echo $config['theme']['footer_style'] === 'left' ? 'selected' : ''; ?>>Left</option>
                                        <option value="right" <?php echo $config['theme']['footer_style'] === 'right' ? 'selected' : ''; ?>>Right</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row" style="margin-top:1rem;">
                                <label style="display:flex;align-items:center;gap:0.5rem;">
                                    <input type="checkbox" name="animation_enabled" value="1" <?php echo !empty($config['theme']['animation_enabled']) ? 'checked' : ''; ?>>
                                    Enable Animations
                                </label>
                            </div>

                            <h3 style="margin:1.5rem 0 1rem;color:#c84c47;">Hero Image Settings</h3>
                            
                            <h4 style="margin:1rem 0 0.5rem;font-size:0.95rem;color:#666;">Desktop</h4>
                            <div class="form-grid">
                                <div class="form-row">
                                    <label>Hero Height</label>
                                    <select name="hero_height">
                                        <option value="80vh" <?php echo ($config['theme']['hero_height'] ?? '100vh') === '80vh' ? 'selected' : ''; ?>>80vh</option>
                                        <option value="90vh" <?php echo ($config['theme']['hero_height'] ?? '100vh') === '90vh' ? 'selected' : ''; ?>>90vh</option>
                                        <option value="100vh" <?php echo ($config['theme']['hero_height'] ?? '100vh') === '100vh' ? 'selected' : ''; ?>>100vh (Default)</option>
                                        <option value="110vh" <?php echo ($config['theme']['hero_height'] ?? '100vh') === '110vh' ? 'selected' : ''; ?>>110vh</option>
                                        <option value="120vh" <?php echo ($config['theme']['hero_height'] ?? '100vh') === '120vh' ? 'selected' : ''; ?>>120vh</option>
                                    </select>
                                    <small>Tinggi section hero untuk desktop.</small>
                                </div>
                                <div class="form-row">
                                    <label>Vertical Alignment</label>
                                    <select name="hero_vertical_alignment">
                                        <option value="flex-start" <?php echo ($config['theme']['hero_vertical_alignment'] ?? 'center') === 'flex-start' ? 'selected' : ''; ?>>Top</option>
                                        <option value="center" <?php echo ($config['theme']['hero_vertical_alignment'] ?? 'center') === 'center' ? 'selected' : ''; ?>>Center (Default)</option>
                                        <option value="flex-end" <?php echo ($config['theme']['hero_vertical_alignment'] ?? 'center') === 'flex-end' ? 'selected' : ''; ?>>Bottom</option>
                                    </select>
                                    <small>Posisi vertikal konten hero.</small>
                                </div>
                                <div class="form-row">
                                    <label>Content Width</label>
                                    <select name="hero_content_width">
                                        <option value="700px" <?php echo ($config['theme']['hero_content_width'] ?? '900px') === '700px' ? 'selected' : ''; ?>>700px</option>
                                        <option value="800px" <?php echo ($config['theme']['hero_content_width'] ?? '900px') === '800px' ? 'selected' : ''; ?>>800px</option>
                                        <option value="900px" <?php echo ($config['theme']['hero_content_width'] ?? '900px') === '900px' ? 'selected' : ''; ?>>900px (Default)</option>
                                        <option value="1000px" <?php echo ($config['theme']['hero_content_width'] ?? '900px') === '1000px' ? 'selected' : ''; ?>>1000px</option>
                                        <option value="100%" <?php echo ($config['theme']['hero_content_width'] ?? '900px') === '100%' ? 'selected' : ''; ?>>100%</option>
                                    </select>
                                    <small>Lebar maksimal konten hero.</small>
                                </div>
                                <div class="form-row">
                                    <label>Hero Image Fit</label>
                                    <select name="hero_image_fit">
                                        <option value="cover" <?php echo ($config['theme']['hero_image_fit'] ?? 'cover') === 'cover' ? 'selected' : ''; ?>>Cover (Default - memenuhi area)</option>
                                        <option value="contain" <?php echo ($config['theme']['hero_image_fit'] ?? 'cover') === 'contain' ? 'selected' : ''; ?>>Contain (tampilkan seluruh gambar)</option>
                                        <option value="auto" <?php echo ($config['theme']['hero_image_fit'] ?? 'cover') === 'auto' ? 'selected' : ''; ?>>Auto (ukuran asli)</option>
                                    </select>
                                    <small>Pilih bagaimana gambar background hero ditampilkan.</small>
                                </div>
                                <div class="form-row">
                                    <label>Hero Image Position</label>
                                    <select name="hero_image_position">
                                        <option value="center" <?php echo ($config['theme']['hero_image_position'] ?? 'center') === 'center' ? 'selected' : ''; ?>>Center</option>
                                        <option value="top" <?php echo ($config['theme']['hero_image_position'] ?? 'center') === 'top' ? 'selected' : ''; ?>>Top</option>
                                        <option value="bottom" <?php echo ($config['theme']['hero_image_position'] ?? 'center') === 'bottom' ? 'selected' : ''; ?>>Bottom</option>
                                        <option value="left" <?php echo ($config['theme']['hero_image_position'] ?? 'center') === 'left' ? 'selected' : ''; ?>>Left</option>
                                        <option value="right" <?php echo ($config['theme']['hero_image_position'] ?? 'center') === 'right' ? 'selected' : ''; ?>>Right</option>
                                    </select>
                                    <small>Posisi fokus gambar pada background hero.</small>
                                </div>
                            </div>
                            
                            <h4 style="margin:1.5rem 0 0.5rem;font-size:0.95rem;color:#666;">Mobile</h4>
                            <div class="form-grid">
                                <div class="form-row">
                                    <label>Hero Height</label>
                                    <select name="mobile_hero_height">
                                        <option value="70vh" <?php echo ($config['theme']['mobile_hero_height'] ?? '85vh') === '70vh' ? 'selected' : ''; ?>>70vh</option>
                                        <option value="80vh" <?php echo ($config['theme']['mobile_hero_height'] ?? '85vh') === '80vh' ? 'selected' : ''; ?>>80vh</option>
                                        <option value="85vh" <?php echo ($config['theme']['mobile_hero_height'] ?? '85vh') === '85vh' ? 'selected' : ''; ?>>85vh (Default)</option>
                                        <option value="90vh" <?php echo ($config['theme']['mobile_hero_height'] ?? '85vh') === '90vh' ? 'selected' : ''; ?>>90vh</option>
                                        <option value="100vh" <?php echo ($config['theme']['mobile_hero_height'] ?? '85vh') === '100vh' ? 'selected' : ''; ?>>100vh</option>
                                    </select>
                                    <small>Tinggi section hero untuk mobile.</small>
                                </div>
                                <div class="form-row">
                                    <label>Vertical Alignment</label>
                                    <select name="mobile_hero_vertical_alignment">
                                        <option value="flex-start" <?php echo ($config['theme']['mobile_hero_vertical_alignment'] ?? 'center') === 'flex-start' ? 'selected' : ''; ?>>Top</option>
                                        <option value="center" <?php echo ($config['theme']['mobile_hero_vertical_alignment'] ?? 'center') === 'center' ? 'selected' : ''; ?>>Center (Default)</option>
                                        <option value="flex-end" <?php echo ($config['theme']['mobile_hero_vertical_alignment'] ?? 'center') === 'flex-end' ? 'selected' : ''; ?>>Bottom</option>
                                    </select>
                                    <small>Posisi vertikal konten hero untuk mobile.</small>
                                </div>
                                <div class="form-row">
                                    <label>Content Width</label>
                                    <select name="mobile_hero_content_width">
                                        <option value="100%" <?php echo ($config['theme']['mobile_hero_content_width'] ?? '100%') === '100%' ? 'selected' : ''; ?>>100% (Default)</option>
                                        <option value="90%" <?php echo ($config['theme']['mobile_hero_content_width'] ?? '100%') === '90%' ? 'selected' : ''; ?>>90%</option>
                                        <option value="85%" <?php echo ($config['theme']['mobile_hero_content_width'] ?? '100%') === '85%' ? 'selected' : ''; ?>>85%</option>
                                        <option value="80%" <?php echo ($config['theme']['mobile_hero_content_width'] ?? '100%') === '80%' ? 'selected' : ''; ?>>80%</option>
                                        <option value="400px" <?php echo ($config['theme']['mobile_hero_content_width'] ?? '100%') === '400px' ? 'selected' : ''; ?>>400px</option>
                                    </select>
                                    <small>Lebar maksimal konten hero untuk mobile.</small>
                                </div>
                                <div class="form-row">
                                    <label>Hero Image Fit</label>
                                    <select name="mobile_hero_image_fit">
                                        <option value="cover" <?php echo ($config['theme']['mobile_hero_image_fit'] ?? 'cover') === 'cover' ? 'selected' : ''; ?>>Cover</option>
                                        <option value="contain" <?php echo ($config['theme']['mobile_hero_image_fit'] ?? 'cover') === 'contain' ? 'selected' : ''; ?>>Contain</option>
                                        <option value="auto" <?php echo ($config['theme']['mobile_hero_image_fit'] ?? 'cover') === 'auto' ? 'selected' : ''; ?>>Auto</option>
                                    </select>
                                    <small>Background size untuk mobile.</small>
                                </div>
                                <div class="form-row" style="grid-column: 1 / -1;">
                                    <label>Hero Image Position</label>
                                    <select name="mobile_hero_image_position">
                                        <optgroup label="Top">
                                            <option value="left top" <?php echo ($config['theme']['mobile_hero_image_position'] ?? 'center top') === 'left top' ? 'selected' : ''; ?>>Top Left</option>
                                            <option value="center top" <?php echo ($config['theme']['mobile_hero_image_position'] ?? 'center top') === 'center top' ? 'selected' : ''; ?>>Top Center</option>
                                            <option value="right top" <?php echo ($config['theme']['mobile_hero_image_position'] ?? 'center top') === 'right top' ? 'selected' : ''; ?>>Top Right</option>
                                        </optgroup>
                                        <optgroup label="Center">
                                            <option value="left center" <?php echo ($config['theme']['mobile_hero_image_position'] ?? 'center top') === 'left center' ? 'selected' : ''; ?>>Center Left</option>
                                            <option value="center center" <?php echo ($config['theme']['mobile_hero_image_position'] ?? 'center top') === 'center center' ? 'selected' : ''; ?>>Center</option>
                                            <option value="right center" <?php echo ($config['theme']['mobile_hero_image_position'] ?? 'center top') === 'right center' ? 'selected' : ''; ?>>Center Right</option>
                                        </optgroup>
                                        <optgroup label="Bottom">
                                            <option value="left bottom" <?php echo ($config['theme']['mobile_hero_image_position'] ?? 'center top') === 'left bottom' ? 'selected' : ''; ?>>Bottom Left</option>
                                            <option value="center bottom" <?php echo ($config['theme']['mobile_hero_image_position'] ?? 'center top') === 'center bottom' ? 'selected' : ''; ?>>Bottom Center</option>
                                            <option value="right bottom" <?php echo ($config['theme']['mobile_hero_image_position'] ?? 'center top') === 'right bottom' ? 'selected' : ''; ?>>Bottom Right</option>
                                        </optgroup>
                                    </select>
                                    <small>Posisi fokus gambar untuk mobile.</small>
                                </div>
                                <div class="form-row" style="grid-column: 1 / -1;">
                                    <label>Mobile Button Layout</label>
                                    <select name="buttons_mobile_layout">
                                        <option value="horizontal" <?php echo ($config['buttons']['mobile_layout'] ?? '2-columns') === 'horizontal' ? 'selected' : ''; ?>>Horizontal (side by side)</option>
                                        <option value="2-columns" <?php echo ($config['buttons']['mobile_layout'] ?? '2-columns') === '2-columns' ? 'selected' : ''; ?>>2 Columns (Default)</option>
                                        <option value="1-column" <?php echo ($config['buttons']['mobile_layout'] ?? '2-columns') === '1-column' ? 'selected' : ''; ?>>1 Column (stacked)</option>
                                    </select>
                                    <small>Layout tombol CTA untuk mobile.</small>
                                </div>
                            </div>

                            <?php endif; ?>

                            <div class="theme-actions">
                                <button type="submit">Save</button>
                                <button type="button" class="button small-button" id="themePreviewReset">Reset</button>
                                <button type="button" class="button small-button" id="themePreviewCancel">Cancel Preview</button>
                            </div>
                        </form>

                        <h3 style="margin:2rem 0 1rem;color:#c84c47;">Opsi Khusus Preset Active (Theme Options)</h3>
                        <form method="post" style="margin-bottom:2rem;">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_theme_options">
                            <input type="hidden" name="preset_key" value="<?php echo escape_html($config['theme']['theme_preset'] ?? 'dewankl'); ?>">
                            <p style="font-size:0.9rem;color:#6d5148;">Pengaturan unik untuk preset <strong><?php echo escape_html($config['theme']['theme_preset'] ?? 'dewankl'); ?></strong>:</p>
                            <div class="form-grid">
                                <?php
                                $activePresetKey = $config['theme']['theme_preset'] ?? 'dewankl';
                                $activeOpts = $config['theme_options'][$activePresetKey] ?? [];
                                foreach ($activeOpts as $optKey => $optVal):
                                ?>
                                    <div class="form-row">
                                        <label><?php echo escape_html(ucwords(str_replace('_', ' ', $optKey))); ?></label>
                                        <?php if (is_bool($optVal)): ?>
                                            <select name="theme_opts[<?php echo escape_html($optKey); ?>]">
                                                <option value="1" <?php echo $optVal ? 'selected' : ''; ?>>Aktif (True)</option>
                                                <option value="0" <?php echo !$optVal ? 'selected' : ''; ?>>Nonaktif (False)</option>
                                            </select>
                                        <?php else: ?>
                                            <input type="text" name="theme_opts[<?php echo escape_html($optKey); ?>]" value="<?php echo escape_html((string)$optVal); ?>">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" style="margin-top:1rem;">Simpan Opsi Preset</button>
                        </form>
                        <aside class="theme-preview-panel" aria-label="Live theme preview">
                            <div class="theme-preview-panel__header">
                                <strong>Live Preview</strong>
                                <span>Perubahan sementara, belum tersimpan.</span>
                            </div>
                            <iframe id="themePreviewFrame" src="/?theme_preview=1" title="Preview tema undangan" loading="lazy"></iframe>
                        </aside>
                        </div>
                    </section>


                    <section id="custom-css" class="card panel-section">
                        <h2>CSS Khusus</h2>
                        <p class="section-description">Tambahkan sentuhan CSS tambahan untuk detail tampilan yang tidak tersedia di pengaturan tema.</p>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_custom_css">
                            <div class="form-row">
                                <label>CSS Editor</label>
                                <textarea name="custom_css" class="code-editor" rows="18" spellcheck="false" placeholder=".hero { background: linear-gradient(...); }"><?php echo escape_html($customCss); ?></textarea>
                                <small>Hanya CSS valid yang diterima. HTML, JavaScript, PHP, tag HTML, URL javascript:, dan inline event handler akan ditolak.</small>
                            </div>
                            <button type="submit">Simpan Custom CSS</button>
                        </form>
                    </section>

                    <section id="file-manager" class="card panel-section">
                        <h2>Kelola Media</h2>
                        <p class="section-description">Upload, pilih, dan kelola semua media yang dipakai undangan: cover, background, galeri, cerita, dan musik.</p>

                        <form method="post" enctype="multipart/form-data" style="margin-bottom: 20px;">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="upload_media_library">
                            <div class="form-grid">
                                <div class="form-row">
                                    <label>Upload ke folder</label>
                                    <select name="media_dir">
                                        <option value="cover">Cover</option>
                                        <option value="background">Background</option>
                                        <option value="gallery">Gallery</option>
                                        <option value="love_story">Love Story</option>
                                        <option value="music">Music</option>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label>Pilih file</label>
                                    <input type="file" name="media_file" accept="image/*,audio/*">
                                </div>
                            </div>
                            <button type="submit">Unggah File</button>
                        </form>

                        <form method="get" style="display:flex;gap:10px;align-items:end;margin-bottom:16px;flex-wrap:wrap;">
                            <div class="form-row" style="min-width:220px;">
                                <label>Pencarian</label>
                                <input type="text" name="media_search" value="<?php echo escape_html((string)($_GET['media_search'] ?? '')); ?>">
                            </div>
                            <div class="form-row" style="min-width:180px;">
                                <label>Filter tipe</label>
                                <select name="media_type">
                                    <option value="all" <?php echo (($_GET['media_type'] ?? 'all') === 'all') ? 'selected' : ''; ?>>Semua</option>
                                    <option value="image" <?php echo (($_GET['media_type'] ?? '') === 'image') ? 'selected' : ''; ?>>Gambar</option>
                                    <option value="audio" <?php echo (($_GET['media_type'] ?? '') === 'audio') ? 'selected' : ''; ?>>Audio</option>
                                </select>
                            </div>
                            <button type="submit" class="small-button">Cari</button>
                        </form>

                        <?php if (empty($mediaLibrary)): ?>
                            <p>Belum ada file media yang diunggah.</p>
                        <?php else: ?>
                            <div class="file-manager-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;">
                                <?php foreach ($mediaLibrary as $item): ?>
                                    <div class="file-manager-item" style="border:1px solid #e8ddcf;background:#fffaf4;border-radius:12px;padding:12px;display:flex;flex-direction:column;gap:10px;">
                                        <?php if ($item['type'] === 'image'): ?>
                                            <img src="/<?php echo escape_html($item['path']); ?>" alt="<?php echo escape_html($item['name']); ?>" style="width:100%;height:130px;object-fit:cover;border-radius:8px;display:block;">
                                        <?php else: ?>
                                            <div style="width:100%;height:130px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:#f3ece3;color:#6d5148;font-size:2rem;">🎵</div>
                                        <?php endif; ?>
                                        <div style="font-size:0.82rem;color:#665846;">
                                            <strong style="display:block;color:#372d28;word-break:break-word;"><?php echo escape_html($item['name']); ?></strong>
                                            <span><?php echo escape_html($item['label']); ?></span>
                                            <span style="display:block; margin-top:2px;"><?php echo escape_html($item['mime']); ?></span>
                                            <span style="display:block; margin-top:2px;"><?php echo escape_html($item['dimensions'] ?: number_format($item['size'] / 1024, 1) . ' KB'); ?></span>
                                            <span style="display:block; margin-top:2px;">Status: <?php echo escape_html($item['status']); ?></span>
                                            <?php if ($item['is_used']): ?>
                                                <span style="display:block; margin-top:2px;">Used by: <?php echo escape_html(implode(', ', $item['used_by'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                            <a href="/<?php echo escape_html($item['path']); ?>" target="_blank" rel="noopener" class="small-button" style="display:inline-flex;align-items:center;justify-content:center;">Preview</a>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                                <input type="hidden" name="action" value="set_media_default">
                                                <input type="hidden" name="media_key" value="media.cover">
                                                <input type="hidden" name="media_value" value="<?php echo escape_html($item['path']); ?>">
                                                <button type="submit" class="small-button">Set Cover</button>
                                            </form>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                                <input type="hidden" name="action" value="set_media_default">
                                                <input type="hidden" name="media_key" value="media.groom_photo">
                                                <input type="hidden" name="media_value" value="<?php echo escape_html($item['path']); ?>">
                                                <button type="submit" class="small-button">Set Groom</button>
                                            </form>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                                <input type="hidden" name="action" value="set_media_default">
                                                <input type="hidden" name="media_key" value="media.bride_photo">
                                                <input type="hidden" name="media_value" value="<?php echo escape_html($item['path']); ?>">
                                                <button type="submit" class="small-button">Set Bride</button>
                                            </form>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                                <input type="hidden" name="action" value="set_media_default">
                                                <input type="hidden" name="media_key" value="media.couple_photo">
                                                <input type="hidden" name="media_value" value="<?php echo escape_html($item['path']); ?>">
                                                <button type="submit" class="small-button">Set Couple</button>
                                            </form>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                                <input type="hidden" name="action" value="set_media_default">
                                                <input type="hidden" name="media_key" value="media.background_hero">
                                                <input type="hidden" name="media_value" value="<?php echo escape_html($item['path']); ?>">
                                                <button type="submit" class="small-button">Set Hero</button>
                                            </form>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                                <input type="hidden" name="action" value="set_media_default">
                                                <input type="hidden" name="media_key" value="media.music">
                                                <input type="hidden" name="media_value" value="<?php echo escape_html($item['path']); ?>">
                                                <button type="submit" class="small-button">Set Music</button>
                                            </form>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                                <input type="hidden" name="action" value="set_media_default">
                                                <input type="hidden" name="media_key" value="gift.qris_image">
                                                <input type="hidden" name="media_value" value="<?php echo escape_html($item['path']); ?>">
                                                <button type="submit" class="small-button">Set QR</button>
                                            </form>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                                <input type="hidden" name="action" value="rename_media_file">
                                                <input type="hidden" name="media_path" value="<?php echo escape_html($item['path']); ?>">
                                                <input type="text" name="new_name" value="<?php echo escape_html($item['name']); ?>" style="width:130px;">
                                                <button type="submit" class="small-button">Rename</button>
                                            </form>
                                            <form method="post" enctype="multipart/form-data" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                                <input type="hidden" name="action" value="replace_media_file">
                                                <input type="hidden" name="media_path" value="<?php echo escape_html($item['path']); ?>">
                                                <input type="file" name="replacement_file" style="max-width:140px;">
                                                <button type="submit" class="small-button">Replace</button>
                                            </form>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                                <input type="hidden" name="action" value="delete_media_file">
                                                <input type="hidden" name="media_path" value="<?php echo escape_html($item['path']); ?>">
                                                <button type="submit" class="small-button" style="background:#a14a45;color:white;" <?php echo $item['is_used'] ? 'disabled title="Asset masih digunakan"' : ''; ?>>Hapus</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section id="love-story" class="card panel-section">
                        <h2>Love Story</h2>
                        <p class="section-description">Kelola cerita perjalanan cinta Anda dengan timeline yang indah.</p>
                        
                        <!-- Add/Edit Form -->
                        <form method="post" enctype="multipart/form-data" class="love-story-form">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_love_story">
                            <input type="hidden" name="story_action" id="storyAction" value="add">
                            <input type="hidden" name="story_id" id="storyId" value="">
                            
                            <div class="form-row">
                                <label>Judul *</label>
                                <input type="text" name="title" id="storyTitle" required placeholder="Contoh: Pertama Bertemu">
                            </div>
                            
                            <div class="form-row">
                                <label>Subtitle (Opsional)</label>
                                <input type="text" name="subtitle" id="storySubtitle" placeholder="Contoh: Awal dari segalanya">
                            </div>
                            
                            <div class="form-row">
                                <label>Tanggal Acara</label>
                                <input type="date" name="event_date" id="storyEventDate">
                            </div>
                            
                            <div class="form-row">
                                <label>Deskripsi</label>
                                <textarea name="description" id="storyDescription" rows="4" placeholder="Ceritakan momen istimewa ini..."></textarea>
                            </div>
                            
                            <div class="form-row">
                                <label>Upload Gambar</label>
                                <input type="file" name="image" id="storyImage" accept="image/*" data-preview-target="#storyImagePreview">
                                <div id="storyImagePreviewContainer" class="image-preview" style="display:none;">
                                    <img id="storyImagePreview" src="" alt="Preview gambar">
                                    <button type="button" class="small-button" onclick="document.getElementById('storyImagePreviewContainer').style.display='none'; document.getElementById('storyImage').value='';">Hapus Gambar</button>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <label>Alt Text Gambar</label>
                                <input type="text" name="image_alt" id="storyImageAlt" placeholder="Deskripsi untuk aksesibilitas">
                            </div>
                            
                            <div class="form-row">
                                <label>Keterangan Gambar</label>
                                <input type="text" name="image_caption" id="storyImageCaption" placeholder="Keterangan di bawah gambar">
                            </div>
                            
                            <div class="form-row">
                                <label>Icon (Opsional)</label>
                                <input type="text" name="icon" id="storyIcon" placeholder="Contoh: heart, ring, date">
                            </div>
                            
                            <div class="form-row checkbox-row">
                                <label>
                                    <input type="checkbox" name="enabled" id="storyEnabled" checked>
                                    Aktifkan cerita ini
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" id="saveStoryBtn">Tambah Cerita</button>
                                <button type="button" id="cancelEditBtn" style="display:none;" onclick="resetStoryForm()">Batal Edit</button>
                            </div>
                        </form>
                        
                        <!-- Stories List -->
                        <div class="love-stories-list" style="margin-top:30px;">
                            <h3>Daftar Cerita</h3>
                            <?php 
                            $loveStoryItems = $config['love_story']['items'] ?? [];
                            usort($loveStoryItems, function($a, $b) {
                                return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
                            });
                            ?>
                            <?php if (empty($loveStoryItems)): ?>
                                <p>Belum ada cerita. Tambahkan cerita pertama Anda!</p>
                            <?php else: ?>
                                <form method="post" class="love-story-order-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="save_love_story">
                                    <input type="hidden" name="story_action" value="reorder">
                                    <input type="hidden" name="order_array" id="orderArray" value="">
                                    
                                    <div class="sortable-list" id="loveStoriesSortable">
                                        <?php foreach ($loveStoryItems as $item): ?>
                                            <div class="sortable-item" data-id="<?php echo escape_html($item['id'] ?? ''); ?>">
                                                <div class="sortable-handle">☰</div>
                                                <div class="sortable-content">
                                                    <strong><?php echo escape_html($item['title'] ?? 'Untitled'); ?></strong>
                                                    <?php if (!empty($item['event_date'])): ?>
                                                        <span style="color:var(--muted); margin-left:10px;"><?php echo escape_html($item['event_date']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="uploads/love-story/<?php echo escape_html(basename($item['image'])); ?>" alt="Preview" style="width:60px; height:40px; object-fit:cover; border-radius:4px; margin-left:10px; vertical-align:middle;">
                                                    <?php endif; ?>
                                                    <span style="margin-left:10px; color:<?php echo !empty($item['enabled']) ? 'green' : 'red'; ?>;">
                                                        <?php echo !empty($item['enabled']) ? '✓ Aktif' : '✗ Nonaktif'; ?>
                                                    </span>
                                                </div>
                                                <div class="sortable-actions">
                                                    <button type="button" class="small-button edit-story-btn" 
                                                            data-id="<?php echo escape_html($item['id'] ?? ''); ?>"
                                                            data-title="<?php echo escape_html($item['title'] ?? ''); ?>"
                                                            data-subtitle="<?php echo escape_html($item['subtitle'] ?? ''); ?>"
                                                            data-description="<?php echo escape_html($item['description'] ?? ''); ?>"
                                                            data-event-date="<?php echo escape_html($item['event_date'] ?? ''); ?>"
                                                            data-image="<?php echo escape_html($item['image'] ?? ''); ?>"
                                                            data-image-alt="<?php echo escape_html($item['image_alt'] ?? ''); ?>"
                                                            data-image-caption="<?php echo escape_html($item['image_caption'] ?? ''); ?>"
                                                            data-icon="<?php echo escape_html($item['icon'] ?? ''); ?>"
                                                            data-enabled="<?php echo !empty($item['enabled']) ? '1' : '0'; ?>">Edit</button>
                                                    <button type="button" class="small-button delete-story-btn" 
                                                            data-id="<?php echo escape_html($item['id'] ?? ''); ?>"
                                                            data-title="<?php echo escape_html($item['title'] ?? ''); ?>">Hapus</button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="submit" class="save-order-btn" style="margin-top:15px;">Simpan Urutan</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section id="gallery" class="card panel-section">
                        <h2>Gallery</h2>
                        <form method="post" enctype="multipart/form-data" style="margin-bottom:16px;">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="upload_gallery">
                            <div class="form-row"><label>Upload Multiple Images</label><input type="file" name="gallery_files[]" accept="image/*" multiple></div>
                            <button type="submit">Unggah Galeri</button>
                        </form>
                        <form method="post" style="margin-bottom:16px;">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="use_media_library_asset">
                            <input type="hidden" name="media_target" value="gallery_item">
                            <div class="form-row">
                                <label>Pilih dari File Manager</label>
                                <select name="media_path">
                                    <option value="">-- pilih asset --</option>
                                    <?php foreach ($mediaLibrary as $libraryItem): ?>
                                        <?php if ($libraryItem['type'] !== 'image') continue; ?>
                                        <option value="<?php echo escape_html($libraryItem['path']); ?>"><?php echo escape_html($libraryItem['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit">Gunakan Asset Gallery</button>
                        </form>
                        <form method="post" class="gallery-order-form">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_gallery_order">
                            <div class="gallery-grid">
                                <?php if (empty($galleryItems)): ?>
                                    <p>Tidak ada foto di galeri.</p>
                                <?php else: ?>
                                    <?php foreach ($galleryItems as $item): ?>
                                        <?php $basename = basename($item['filename']); ?>
                                        <div class="gallery-card">
                                            <img src="/<?php echo escape_html($item['filename']); ?>" alt="<?php echo escape_html($basename); ?>">
                                            <div class="form-row"><label>Order</label><input type="number" name="gallery_order[<?php echo escape_html($item['filename']); ?>]" value="<?php echo escape_html((string)($item['order'] ?? 0)); ?>"></div>
                                            <div class="form-row"><label>Cover</label><input type="radio" name="gallery_cover" value="<?php echo escape_html($item['filename']); ?>" <?php echo $config['gallery']['cover'] === $item['filename'] ? 'checked' : ''; ?>></div>
                                            <button type="button" class="small-button gallery-delete-button" data-filename="<?php echo escape_html($item['filename']); ?>">Hapus</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($galleryItems)): ?><button type="submit">Simpan Urutan Galeri</button><?php endif; ?>
                        </form>
                    </section>

                    <section id="cover" class="card panel-section">
                        <h2>Cover & Photos</h2>
                        <div style="margin-bottom: 24px;">
                            <h3>Cover Image</h3>
                            <form method="post" enctype="multipart/form-data" style="margin-bottom:16px;">
                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                <input type="hidden" name="action" value="upload_cover">
                                <div class="form-row"><label>Upload Cover Image</label><input type="file" name="cover_image" accept="image/*" data-preview-target="#coverPreviewImg"></div>
                                <button type="submit">Unggah Cover</button>
                            </form>
                            <form method="post" style="margin-bottom:16px;">
                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                <input type="hidden" name="action" value="use_media_library_asset">
                                <input type="hidden" name="media_target" value="cover">
                                <div class="form-row">
                                    <label>Pilih dari File Manager</label>
                                    <select name="media_path">
                                        <option value="">-- pilih asset --</option>
                                        <?php foreach ($mediaLibrary as $libraryItem): ?>
                                            <?php if ($libraryItem['type'] !== 'image') continue; ?>
                                            <option value="<?php echo escape_html($libraryItem['path']); ?>"><?php echo escape_html($libraryItem['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit">Gunakan Asset Cover</button>
                            </form>
                            <?php if ($coverPreview): ?>
                                <div class="image-preview"><img id="coverPreviewImg" src="/<?php echo escape_html($coverPreview); ?>" alt="Cover preview"></div>
                            <?php else: ?>
                                <div class="image-preview"><img id="coverPreviewImg" alt="Cover preview" style="display:none;"></div>
                            <?php endif; ?>
                        </div>

                        <div style="margin-bottom: 24px;">
                            <h3>Foto Mempelai Pria (Groom Photo)</h3>
                            <form method="post" enctype="multipart/form-data" style="margin-bottom:16px;">
                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                <input type="hidden" name="action" value="upload_groom_photo">
                                <div class="form-row"><label>Upload Groom Photo</label><input type="file" name="groom_photo" accept="image/*" data-preview-target="#groomPreviewImg"></div>
                                <button type="submit">Unggah Foto Groom</button>
                            </form>
                            <form method="post" style="margin-bottom:16px;">
                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                <input type="hidden" name="action" value="use_media_library_asset">
                                <input type="hidden" name="media_target" value="groom_photo">
                                <div class="form-row">
                                    <label>Pilih dari File Manager</label>
                                    <select name="media_path">
                                        <option value="">-- pilih asset --</option>
                                        <?php foreach ($mediaLibrary as $libraryItem): ?>
                                            <?php if ($libraryItem['type'] !== 'image') continue; ?>
                                            <option value="<?php echo escape_html($libraryItem['path']); ?>"><?php echo escape_html($libraryItem['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit">Gunakan Asset Groom</button>
                            </form>
                            <?php if ($groomPhotoPreview): ?>
                                <div class="image-preview"><img id="groomPreviewImg" src="/<?php echo escape_html($groomPhotoPreview); ?>" alt="Groom photo preview"></div>
                            <?php else: ?>
                                <div class="image-preview"><img id="groomPreviewImg" alt="Groom photo preview" style="display:none;"></div>
                            <?php endif; ?>
                        </div>

                        <div style="margin-bottom: 24px;">
                            <h3>Foto Mempelai Wanita (Bride Photo)</h3>
                            <form method="post" enctype="multipart/form-data" style="margin-bottom:16px;">
                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                <input type="hidden" name="action" value="upload_bride_photo">
                                <div class="form-row"><label>Upload Bride Photo</label><input type="file" name="bride_photo" accept="image/*" data-preview-target="#bridePreviewImg"></div>
                                <button type="submit">Unggah Foto Bride</button>
                            </form>
                            <form method="post" style="margin-bottom:16px;">
                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                <input type="hidden" name="action" value="use_media_library_asset">
                                <input type="hidden" name="media_target" value="bride_photo">
                                <div class="form-row">
                                    <label>Pilih dari File Manager</label>
                                    <select name="media_path">
                                        <option value="">-- pilih asset --</option>
                                        <?php foreach ($mediaLibrary as $libraryItem): ?>
                                            <?php if ($libraryItem['type'] !== 'image') continue; ?>
                                            <option value="<?php echo escape_html($libraryItem['path']); ?>"><?php echo escape_html($libraryItem['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit">Gunakan Asset Bride</button>
                            </form>
                            <?php if ($bridePhotoPreview): ?>
                                <div class="image-preview"><img id="bridePreviewImg" src="/<?php echo escape_html($bridePhotoPreview); ?>" alt="Bride photo preview"></div>
                            <?php else: ?>
                                <div class="image-preview"><img id="bridePreviewImg" alt="Bride photo preview" style="display:none;"></div>
                            <?php endif; ?>
                        </div>

                        <div style="margin-bottom: 24px;">
                            <h3>Foto Pasangan (Couple Photo)</h3>
                            <form method="post" enctype="multipart/form-data" style="margin-bottom:16px;">
                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                <input type="hidden" name="action" value="upload_couple_photo">
                                <div class="form-row"><label>Upload Couple Photo</label><input type="file" name="couple_photo" accept="image/*" data-preview-target="#couplePreviewImg"></div>
                                <button type="submit">Unggah Foto Couple</button>
                            </form>
                            <form method="post" style="margin-bottom:16px;">
                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                <input type="hidden" name="action" value="use_media_library_asset">
                                <input type="hidden" name="media_target" value="couple_photo">
                                <div class="form-row">
                                    <label>Pilih dari File Manager</label>
                                    <select name="media_path">
                                        <option value="">-- pilih asset --</option>
                                        <?php foreach ($mediaLibrary as $libraryItem): ?>
                                            <?php if ($libraryItem['type'] !== 'image') continue; ?>
                                            <option value="<?php echo escape_html($libraryItem['path']); ?>"><?php echo escape_html($libraryItem['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit">Gunakan Asset Couple</button>
                            </form>
                            <?php if ($couplePhotoPreview): ?>
                                <div class="image-preview"><img id="couplePreviewImg" src="/<?php echo escape_html($couplePhotoPreview); ?>" alt="Couple photo preview"></div>
                            <?php else: ?>
                                <div class="image-preview"><img id="couplePreviewImg" alt="Couple photo preview" style="display:none;"></div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section id="background" class="card panel-section">
                        <h2>Background</h2>
                        <form method="post" enctype="multipart/form-data" style="margin-bottom:16px;">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="upload_background">
                            <div class="form-row"><label>Hero Background</label><input type="file" name="background_hero" accept="image/*" data-preview-target="#backgroundHeroPreviewImg"></div>
                            <div class="form-row"><label>Section Background 1</label><input type="file" name="background_section_1" accept="image/*" data-preview-target="#backgroundSection1PreviewImg"></div>
                            <div class="form-row"><label>Section Background 2</label><input type="file" name="background_section_2" accept="image/*" data-preview-target="#backgroundSection2PreviewImg"></div>
                            <div class="form-row"><label>Section Background 3</label><input type="file" name="background_section_3" accept="image/*" data-preview-target="#backgroundSection3PreviewImg"></div>
                            <button type="submit">Unggah Background</button>
                        </form>
                        <form method="post" style="margin-bottom:16px;">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="use_media_library_asset">
                            <input type="hidden" name="media_target" value="background_hero">
                            <div class="form-row">
                                <label>Hero Background dari File Manager</label>
                                <select name="media_path">
                                    <option value="">-- pilih asset --</option>
                                    <?php foreach ($mediaLibrary as $libraryItem): ?>
                                        <?php if ($libraryItem['type'] !== 'image') continue; ?>
                                        <option value="<?php echo escape_html($libraryItem['path']); ?>"><?php echo escape_html($libraryItem['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit">Gunakan Hero Background</button>
                        </form>
                        <form method="post" style="margin-bottom:16px;">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="use_media_library_asset">
                            <input type="hidden" name="media_target" value="background_section_1">
                            <div class="form-row">
                                <label>Section Background 1 dari File Manager</label>
                                <select name="media_path">
                                    <option value="">-- pilih asset --</option>
                                    <?php foreach ($mediaLibrary as $libraryItem): ?>
                                        <?php if ($libraryItem['type'] !== 'image') continue; ?>
                                        <option value="<?php echo escape_html($libraryItem['path']); ?>"><?php echo escape_html($libraryItem['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit">Gunakan Background 1</button>
                        </form>
                        <form method="post" style="margin-bottom:16px;">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="use_media_library_asset">
                            <input type="hidden" name="media_target" value="background_section_2">
                            <div class="form-row">
                                <label>Section Background 2 dari File Manager</label>
                                <select name="media_path">
                                    <option value="">-- pilih asset --</option>
                                    <?php foreach ($mediaLibrary as $libraryItem): ?>
                                        <?php if ($libraryItem['type'] !== 'image') continue; ?>
                                        <option value="<?php echo escape_html($libraryItem['path']); ?>"><?php echo escape_html($libraryItem['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit">Gunakan Background 2</button>
                        </form>
                        <form method="post" style="margin-bottom:16px;">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="use_media_library_asset">
                            <input type="hidden" name="media_target" value="background_section_3">
                            <div class="form-row">
                                <label>Section Background 3 dari File Manager</label>
                                <select name="media_path">
                                    <option value="">-- pilih asset --</option>
                                    <?php foreach ($mediaLibrary as $libraryItem): ?>
                                        <?php if ($libraryItem['type'] !== 'image') continue; ?>
                                        <option value="<?php echo escape_html($libraryItem['path']); ?>"><?php echo escape_html($libraryItem['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit">Gunakan Background 3</button>
                        </form>
                        <?php if ($backgroundHeroPreview): ?>
                            <div class="image-preview"><img id="backgroundHeroPreviewImg" src="/<?php echo escape_html($backgroundHeroPreview); ?>" alt="Hero background preview"></div>
                        <?php else: ?>
                            <div class="image-preview"><img id="backgroundHeroPreviewImg" alt="Hero background preview" style="display:none;"></div>
                        <?php endif; ?>
                        <?php foreach ($backgroundSectionPreviews as $index => $preview): ?>
                            <div class="image-preview" style="margin-top:12px;"><img id="backgroundSection<?php echo $index + 1; ?>PreviewImg" <?php if ($preview): ?>src="/<?php echo escape_html($preview); ?>"<?php else: ?>alt="Section background <?php echo $index + 1; ?> preview" style="display:none;"<?php endif; ?>></div>
                        <?php endforeach; ?>
                    </section>

                    <section id="music" class="card panel-section">
                        <h2>Music</h2>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="upload_music">
                            <div class="form-row"><label>Upload Audio (mp3, ogg, wav)</label><input type="file" name="music_file" accept="audio/*"></div>
                            <button type="submit">Unggah Musik</button>
                        </form>
                        <div class="form-row"><label>Current Music File</label><input type="text" readonly value="<?php echo escape_html($config['media']['music']); ?>"></div>
                    </section>

                    <section id="gift" class="card panel-section">
                        <h2>Gift</h2>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_gift">
                            <div class="form-grid">
                                <div class="form-row"><label>Bank</label><input type="text" name="bank" value="<?php echo escape_html($config['gift']['bank']); ?>"></div>
                                <div class="form-row"><label>Account Number</label><input type="text" name="account_number" value="<?php echo escape_html($config['gift']['account_number']); ?>"></div>
                                <div class="form-row"><label>Account Holder</label><input type="text" name="account_holder" value="<?php echo escape_html($config['gift']['account_holder']); ?>"></div>
                                <div class="form-row"><label>E-Wallet Label</label><input type="text" name="e_wallet_label" value="<?php echo escape_html($config['gift']['e_wallet_label']); ?>"></div>
                                <div class="form-row"><label>E-Wallet Number</label><input type="text" name="e_wallet_number" value="<?php echo escape_html($config['gift']['e_wallet_number']); ?>"></div>
                            </div>
                            <button type="submit">Simpan Gift</button>
                        </form>
                        <form method="post" enctype="multipart/form-data" style="margin-top:16px;">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="upload_qris">
                            <div class="form-row"><label>Upload QRIS Image</label><input type="file" name="qris_image" accept="image/*" data-preview-target="#qrisPreviewImg"></div>
                            <button type="submit">Unggah QRIS</button>
                        </form>
                        <?php if ($qrisPreview): ?>
                            <div class="image-preview"><img id="qrisPreviewImg" src="/<?php echo escape_html($qrisPreview); ?>" alt="QRIS preview"></div>
                        <?php else: ?>
                            <div class="image-preview"><img id="qrisPreviewImg" alt="QRIS preview" style="display:none;"></div>
                        <?php endif; ?>
                    </section>

                    <section id="dresscode" class="card panel-section">
                        <h2>Dresscode</h2>
                        <p class="section-description">Kelola aturan berpakaian untuk acara pernikahan Anda.</p>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_dresscode">
                            
                            <div class="form-row checkbox-row">
                                <label>
                                    <input type="checkbox" name="dresscode_enabled" <?php echo !empty($config['dresscode']['enabled']) ? 'checked' : ''; ?>>
                                    Aktifkan Dresscode
                                </label>
                            </div>
                            
                            <div class="form-row">
                                <label>Judul</label>
                                <input type="text" name="dresscode_title" value="<?php echo escape_html($config['dresscode']['title']); ?>" placeholder="Contoh: Dresscode">
                            </div>
                            
                            <div class="form-row">
                                <label>Warna/Pakaian</label>
                                <input type="text" name="dresscode_color" value="<?php echo escape_html($config['dresscode']['color']); ?>" placeholder="Contoh: Putih / Pastel">
                            </div>
                            
                            <div class="form-row">
                                <label>Aturan Berpakaian</label>
                                <input type="text" name="dresscode_rule" value="<?php echo escape_html($config['dresscode']['rule']); ?>" placeholder="Contoh: Rapi dan sopan">
                            </div>
                            
                            <div class="form-row">
                                <label>Deskripsi Tambahan</label>
                                <textarea name="dresscode_description" rows="3" placeholder="Kenakan busana terbaikmu untuk momen spesial."><?php echo escape_html($config['dresscode']['description']); ?></textarea>
                            </div>
                            
                            <button type="submit">Simpan Dresscode</button>
                        </form>
                    </section>

                    <section id="maps" class="card panel-section">
                        <h2>Maps</h2>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_location">
                            <div class="form-grid">
                                <div class="form-row"><label>Venue</label><input type="text" name="venue" value="<?php echo escape_html($config['location']['venue']); ?>"></div>
                                <div class="form-row"><label>Address</label><textarea name="address"><?php echo escape_html($config['location']['address']); ?></textarea></div>
                                <div class="form-row" style="grid-column:span 2;"><label>Google Maps URL</label><input type="url" name="maps_url" value="<?php echo escape_html($config['location']['maps_url']); ?>"></div>
                                <div class="form-row" style="grid-column:span 2;"><label>Google Maps Embed URL</label><input type="url" name="maps_embed" value="<?php echo escape_html($config['location']['maps_embed']); ?>"></div>
                            </div>
                            <button type="submit">Simpan Maps</button>
                        </form>
                    </section>

                    <section id="seo" class="card panel-section">
                        <h2>SEO</h2>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_seo">
                            <div class="form-grid">
                                <div class="form-row"><label>Title</label><input type="text" name="seo_title" value="<?php echo escape_html($config['site']['title']); ?>"></div>
                                <div class="form-row"><label>Description</label><textarea name="seo_description"><?php echo escape_html($config['site']['description']); ?></textarea></div>
                                <div class="form-row" style="grid-column:span 2;"><label>Keywords</label><input type="text" name="seo_keywords" value="<?php echo escape_html($config['site']['keywords']); ?>"></div>
                                <div class="form-row"><label>Open Graph Title</label><input type="text" name="og_title" value="<?php echo escape_html($config['site']['open_graph_title']); ?>"></div>
                                <div class="form-row"><label>Open Graph Description</label><textarea name="og_description"><?php echo escape_html($config['site']['open_graph_description']); ?></textarea></div>
                                <div class="form-row" style="grid-column:span 2;"><label>Twitter Card</label><input type="text" name="twitter_card" value="<?php echo escape_html($config['site']['twitter_card']); ?>"></div>
                                <div class="form-row" style="grid-column:span 2;"><label>Schema JSON-LD</label><textarea name="schema_json"><?php echo escape_html($config['site']['schema']); ?></textarea></div>
                            </div>
                            <button type="submit">Simpan SEO</button>
                        </form>
                        <form method="post" enctype="multipart/form-data" style="margin-top:16px;">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="upload_og_image">
                            <div class="form-row"><label>Upload Open Graph Image</label><input type="file" name="og_image" accept="image/*" data-preview-target="#ogPreviewImg"></div>
                            <button type="submit">Unggah OG Image</button>
                        </form>
                        <?php if ($ogPreview): ?>
                            <div class="image-preview"><img id="ogPreviewImg" src="/<?php echo escape_html($ogPreview); ?>" alt="Open Graph preview"></div>
                        <?php else: ?>
                            <div class="image-preview"><img id="ogPreviewImg" alt="Open Graph preview" style="display:none;"></div>
                        <?php endif; ?>
                    </section>

                    <section id="whatsapp" class="card panel-section">
                        <h2>WhatsApp</h2>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_whatsapp">
                            <div class="form-grid">
                                <div class="form-row"><label>Phone Number</label><input type="text" name="whatsapp_phone" value="<?php echo escape_html($config['whatsapp']['phone']); ?>"></div>
                                <div class="form-row" style="grid-column:span 2;"><label>Default Invitation Message</label><textarea name="whatsapp_message"><?php echo escape_html($config['whatsapp']['message']); ?></textarea></div>
                            </div>
                            <button type="submit">Simpan WhatsApp</button>
                        </form>
                        <div class="form-row" style="margin-top:16px;"><label>Personalized Link</label><input type="text" readonly value="<?php echo escape_html($invitationPreview); ?>"></div>
                    </section>

                    <section id="guest-links" class="card panel-section">
                        <h2>Link Tamu</h2>
                        <div class="form-grid">
                            <div class="form-row"><label>Guest Name</label><input type="text" id="guestNameInput" placeholder="Nama tamu" autocomplete="off"></div>
                            <div class="form-row"><label>Invitation URL</label><input type="text" id="guestLinkOutput" readonly placeholder="Generated invitation URL"></div>
                        </div>
                        <div class="form-row" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
                            <button type="button" class="button" id="generateGuestLinkBtn">Generate Link</button>
                            <button type="button" class="button small-button" id="copyGuestLinkBtn">Copy Link</button>
                            <button type="button" class="button small-button" id="sendGuestLinkWhatsappBtn">WhatsApp</button>
                            <button type="button" class="button small-button" id="saveGuestLinkBtn">Save Link</button>
                        </div>
                        <div class="guest-link-actions" style="margin-top:18px;display:grid;gap:14px;">
                            <div class="form-row"><label>QR Code Preview</label><img id="guestLinkQrPreview" src="" alt="QR Code Preview" style="max-width:240px;border-radius:16px;border:1px solid #ddd;background:#fff;display:none;" loading="lazy"></div>
                            <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
                                <a id="downloadGuestLinkQrBtn" class="button small-button" href="#" download="guest-invitation-qr.png" style="display:none;">Download QR Code</a>
                                <span id="guestLinkStatus" style="color:#5c4c32;font-size:0.95rem;"></span>
                            </div>
                        </div>
                        <div class="form-row" style="margin-top:24px;"><label>Search Saved Links</label><input type="text" id="guestLinkSearch" placeholder="Cari nama tamu atau URL"></div>
                        <div class="table-wrapper" style="margin-top:14px;">
                            <table id="guestLinksTable">
                                <thead>
                                    <tr><th>Guest Name</th><th>Created At</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($guestLinks)): ?>
                                        <tr><td colspan="3">Belum ada link tamu yang disimpan.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($guestLinks as $index => $link): ?>
                                            <tr data-guest-name="<?php echo escape_html($link['guest_name']); ?>" data-invitation-url="<?php echo escape_html($link['invitation_url']); ?>">
                                                <td><?php echo escape_html($link['guest_name']); ?><br><small><?php echo escape_html($link['invitation_url']); ?></small></td>
                                                <td><?php echo escape_html($link['created_at']); ?></td>
                                                <td>
                                                    <button type="button" class="small-button guest-link-copy" data-url="<?php echo escape_html($link['invitation_url']); ?>">Copy</button>
                                                    <button type="button" class="small-button guest-link-whatsapp" data-url="<?php echo escape_html($link['invitation_url']); ?>">WA</button>
                                                    <button type="button" class="small-button guest-link-qr" data-url="<?php echo escape_html($link['invitation_url']); ?>">QR</button>
                                                    <form method="post" style="display:inline-block;margin:0;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                                        <input type="hidden" name="action" value="delete_guest_link">
                                                        <input type="hidden" name="delete_index" value="<?php echo escape_html((string)$index); ?>">
                                                        <button type="submit" class="small-button" style="background:#d9534f;color:#fff;">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <input type="hidden" id="guestLinkBaseUrl" value="<?php echo escape_html($siteUrl ?: rtrim($config['site']['url'] ?? '', '/')); ?>">
                        <input type="hidden" id="guestLinkWhatsappPhone" value="<?php echo escape_html($config['whatsapp']['phone']); ?>">
                        <input type="hidden" id="guestLinkWhatsappMessage" value="<?php echo escape_html($config['whatsapp']['message']); ?>">
                    </section>

                    <section id="rsvp" class="card panel-section">
                        <h2>RSVP</h2>
                        <p>Kelola data RSVP langsung dari database. Gunakan <strong>Export CSV</strong> untuk laporan cepat.</p>
                        <a href="/export-rsvp.php" class="button" style="display:inline-block; margin-bottom:16px;">Download CSV</a>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr><th>Nama</th><th>Status</th><th>Ucapan</th><th>Waktu</th><th>Aksi</th></tr>
                                </thead>
                                <tbody>
                                <?php
                                try {
                                    if (is_readable(DB_PATH)) {
                                        $db = new SQLite3(DB_PATH, SQLITE3_OPEN_READONLY);
                                        $result = $db->query('SELECT id, nama, status, ucapan, created_at, visible FROM tamu ORDER BY id DESC LIMIT 50');
                                        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                            $visible = $row['visible'] ? 'Yes' : 'No';
                                            echo '<tr>';
                                            echo '<td>' . escape_html($row['nama']) . '</td>';
                                            echo '<td>' . escape_html($row['status']) . '</td>';
                                            echo '<td>' . escape_html($row['ucapan']) . '</td>';
                                            echo '<td>' . escape_html($row['created_at']) . '</td>';
                                            echo '<td><span class="status-badge">' . escape_html($visible) . '</span></td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="5">Database tidak ditemukan.</td></tr>';
                                    }
                                } catch (Throwable $e) {
                                    echo '<tr><td colspan="5">Gagal memuat RSVP.</td></tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section id="backup" class="card panel-section">
                        <h2>Backup</h2>
                        <p>Backup konfigurasi, database RSVP, dan semua media upload.</p>
                        <a class="button" href="/admin/backup.php">Download Backup ZIP</a>
                        <div style="margin-top:18px;">
                            <form method="post" enctype="multipart/form-data" action="/admin/restore.php">
                                <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                                <label>Restore dari ZIP backup</label>
                                <input type="file" name="restore_file" accept=".zip" required>
                                <button type="submit">Restore Backup</button>
                            </form>
                        </div>
                    </section>

                    <section id="settings" class="card panel-section">
                        <h2>Settings</h2>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_settings">
                            <div class="form-grid">
                                <div class="form-row"><label>Site URL</label><input type="url" name="site_url" value="<?php echo escape_html($siteUrl); ?>"></div>
                                <div class="form-row"><label>Admin Username</label><input type="text" name="admin_username" value="<?php echo escape_html($config['admin']['username']); ?>"></div>
                                <div class="form-row"><label>Admin Password</label><input type="password" name="admin_password" placeholder="Kosongkan jika tidak ingin mengganti"></div>
                            </div>
                            <button type="submit">Simpan Settings</button>
                        </form>
                    </section>
                </main>
            </div>
        <?php endif; ?>
    </div>
    <script src="app.js"></script>
    <script>
    // Love Story Management Functions
    function resetStoryForm() {
        document.getElementById('storyAction').value = 'add';
        document.getElementById('storyId').value = '';
        document.getElementById('storyTitle').value = '';
        document.getElementById('storySubtitle').value = '';
        document.getElementById('storyEventDate').value = '';
        document.getElementById('storyDescription').value = '';
        document.getElementById('storyImage').value = '';
        document.getElementById('storyImageAlt').value = '';
        document.getElementById('storyImageCaption').value = '';
        document.getElementById('storyIcon').value = '';
        document.getElementById('storyEnabled').checked = true;
        document.getElementById('saveStoryBtn').textContent = 'Tambah Cerita';
        document.getElementById('cancelEditBtn').style.display = 'none';
        document.getElementById('storyImagePreviewContainer').style.display = 'none';
        document.getElementById('storyImagePreview').src = '';
    }

    // Edit button handlers
    document.querySelectorAll('.edit-story-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            const subtitle = this.getAttribute('data-subtitle');
            const description = this.getAttribute('data-description');
            const eventDate = this.getAttribute('data-event-date');
            const image = this.getAttribute('data-image');
            const imageAlt = this.getAttribute('data-image-alt');
            const imageCaption = this.getAttribute('data-image-caption');
            const icon = this.getAttribute('data-icon');
            const enabled = this.getAttribute('data-enabled') === '1';

            document.getElementById('storyAction').value = 'edit';
            document.getElementById('storyId').value = id;
            document.getElementById('storyTitle').value = title;
            document.getElementById('storySubtitle').value = subtitle;
            document.getElementById('storyDescription').value = description;
            document.getElementById('storyEventDate').value = eventDate;
            document.getElementById('storyImageAlt').value = imageAlt;
            document.getElementById('storyImageCaption').value = imageCaption;
            document.getElementById('storyIcon').value = icon;
            document.getElementById('storyEnabled').checked = enabled;
            document.getElementById('saveStoryBtn').textContent = 'Simpan Perubahan';
            document.getElementById('cancelEditBtn').style.display = 'inline-block';

            if (image) {
                document.getElementById('storyImagePreview').src = 'uploads/love-story/' + image;
                document.getElementById('storyImagePreviewContainer').style.display = 'block';
            }

            // Scroll to form
            document.querySelector('.love-story-form').scrollIntoView({ behavior: 'smooth' });
        });
    });

    // Delete button handlers
    document.querySelectorAll('.delete-story-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            if (confirm('Apakah Anda yakin ingin menghapus cerita "' + title + '"?')) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = '<input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">' +
                    '<input type="hidden" name="action" value="save_love_story">' +
                    '<input type="hidden" name="story_action" value="delete">' +
                    '<input type="hidden" name="story_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    // Image preview handler
    document.getElementById('storyImage').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('storyImagePreview').src = e.target.result;
                document.getElementById('storyImagePreviewContainer').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // Sortable functionality for love stories
    (function() {
        const sortableList = document.getElementById('loveStoriesSortable');
        if (!sortableList) return;

        let draggedItem = null;

        sortableList.addEventListener('dragstart', function(e) {
            if (e.target.classList.contains('sortable-item')) {
                draggedItem = e.target;
                setTimeout(function() { e.target.style.opacity = '0.5'; }, 0);
            }
        });

        sortableList.addEventListener('dragend', function(e) {
            if (e.target.classList.contains('sortable-item')) {
                e.target.style.opacity = '1';
                draggedItem = null;
            }
        });

        sortableList.addEventListener('dragover', function(e) {
            e.preventDefault();
            const afterElement = getDragAfterElement(sortableList, e.clientY);
            if (afterElement == null) {
                sortableList.appendChild(draggedItem);
            } else {
                sortableList.insertBefore(draggedItem, afterElement);
            }
        });

        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.sortable-item:not(.dragging)')];
            return draggableElements.reduce(function(closest, child) {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        // Save order on form submit
        const orderForm = document.querySelector('.love-story-order-form');
        if (orderForm) {
            orderForm.addEventListener('submit', function(e) {
                const items = sortableList.querySelectorAll('.sortable-item');
                const orderArray = [];
                items.forEach(function(item) {
                    orderArray.push(item.getAttribute('data-id'));
                });
                document.getElementById('orderArray').value = JSON.stringify(orderArray);
            });
        }
    })();
    </script>
</body>
</html>
