<?php
require_once __DIR__ . '/../config.php';
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

function escape_html(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
            case 'save_whatsapp':
                $config['whatsapp']['phone'] = trim((string)($_POST['whatsapp_phone'] ?? '')) ?: $config['whatsapp']['phone'];
                $config['whatsapp']['message'] = trim((string)($_POST['whatsapp_message'] ?? '')) ?: $config['whatsapp']['message'];
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
$invitationPreview = build_invitation_preview_url($config);
$siteUrl = trim($config['site']['url']);
$coverPreview = $config['media']['cover'] ?: 'uploads/cover/cover.jpg';
$ogPreview = $config['site']['open_graph_image'];
$backgroundHeroPreview = $config['media']['background_hero'];
$backgroundSectionPreviews = [
    $config['media']['background_sections'][0] ?? '',
    $config['media']['background_sections'][1] ?? '',
    $config['media']['background_sections'][2] ?? ''
];
$qrisPreview = $config['gift']['qris_image'];

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
                        <a href="#dashboard">Dashboard</a>
                        <a href="#wedding">Wedding Information</a>
                        <a href="#parents">Parents</a>
                        <a href="#schedule">Schedule</a>
                        <a href="#countdown">Countdown</a>
                        <a href="#gallery">Gallery</a>
                        <a href="#cover">Cover</a>
                        <a href="#background">Background</a>
                        <a href="#music">Music</a>
                        <a href="#gift">Gift</a>
                        <a href="#maps">Maps</a>
                        <a href="#seo">SEO</a>
                        <a href="#whatsapp">WhatsApp</a>
                        <a href="#guest-links">Guest Links</a>
                        <a href="#rsvp">RSVP</a>
                        <a href="#backup">Backup</a>
                        <a href="#settings">Settings</a>
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
                        <h2>Wedding Information</h2>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_wedding">
                            <div class="form-grid">
                                <div class="form-row"><label>Bride Name</label><input type="text" name="bride_name" value="<?php echo escape_html($config['wedding']['bride_name']); ?>" required></div>
                                <div class="form-row"><label>Groom Name</label><input type="text" name="groom_name" value="<?php echo escape_html($config['wedding']['groom_name']); ?>" required></div>
                                <div class="form-row"><label>Wedding Title</label><input type="text" name="title" value="<?php echo escape_html($config['wedding']['title']); ?>"></div>
                                <div class="form-row"><label>Opening Text</label><textarea name="opening_text"><?php echo escape_html($config['wedding']['opening_text']); ?></textarea></div>
                                <div class="form-row"><label>Closing Text</label><textarea name="closing_text"><?php echo escape_html($config['wedding']['closing_text']); ?></textarea></div>
                                <div class="form-row"><label>Quote</label><textarea name="quote"><?php echo escape_html($config['wedding']['quote']); ?></textarea></div>
                                <div class="form-row"><label>Bride Nickname</label><input type="text" name="bride_nickname" value="<?php echo escape_html($config['wedding']['bride_nickname']); ?>"></div>
                                <div class="form-row"><label>Groom Nickname</label><input type="text" name="groom_nickname" value="<?php echo escape_html($config['wedding']['groom_nickname']); ?>"></div>
                            </div>
                            <button type="submit">Simpan Wedding Information</button>
                        </form>
                    </section>

                    <section id="parents" class="card panel-section">
                        <h2>Parents</h2>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_parents">
                            <div class="form-grid">
                                <div class="form-row"><label>Bride Father</label><input type="text" name="bride_father" value="<?php echo escape_html($config['parents']['bride_father']); ?>"></div>
                                <div class="form-row"><label>Bride Mother</label><input type="text" name="bride_mother" value="<?php echo escape_html($config['parents']['bride_mother']); ?>"></div>
                                <div class="form-row"><label>Groom Father</label><input type="text" name="groom_father" value="<?php echo escape_html($config['parents']['groom_father']); ?>"></div>
                                <div class="form-row"><label>Groom Mother</label><input type="text" name="groom_mother" value="<?php echo escape_html($config['parents']['groom_mother']); ?>"></div>
                            </div>
                            <button type="submit">Simpan Parents</button>
                        </form>
                    </section>

                    <section id="schedule" class="card panel-section">
                        <h2>Schedule</h2>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_schedule">
                            <div class="form-grid">
                                <div class="form-row"><label>Akad Date</label><input type="date" name="akad_date" value="<?php echo escape_html($config['schedule']['akad_date']); ?>" required></div>
                                <div class="form-row"><label>Akad Time</label><input type="time" name="akad_time" value="<?php echo escape_html($config['schedule']['akad_time']); ?>" required></div>
                                <div class="form-row"><label>Reception Date</label><input type="date" name="reception_date" value="<?php echo escape_html($config['schedule']['reception_date']); ?>" required></div>
                                <div class="form-row"><label>Reception Time</label><input type="time" name="reception_time" value="<?php echo escape_html($config['schedule']['reception_time']); ?>" required></div>
                                <div class="form-row"><label>Timezone</label><input type="text" name="timezone" value="<?php echo escape_html($config['schedule']['timezone']); ?>" required></div>
                                <div class="form-row" style="grid-column:span 2;"><label>Google Calendar Link</label><input type="url" name="google_calendar_link" value="<?php echo escape_html($config['schedule']['google_calendar_link']); ?>"></div>
                            </div>
                            <button type="submit">Simpan Schedule</button>
                        </form>
                    </section>

                    <section id="countdown" class="card panel-section">
                        <h2>Countdown</h2>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="save_schedule">
                            <div class="form-grid">
                                <div class="form-row" style="grid-column:span 2;"><label>Countdown Target</label><input type="text" name="countdown_target" value="<?php echo escape_html($config['schedule']['countdown_target']); ?>" placeholder="2026-12-29T09:00:00+07:00"></div>
                            </div>
                            <p style="font-size:0.95rem;color:#5c4c32;">Countdown akan otomatis diperbarui saat tanggal akad, jam, atau zona waktu diubah.</p>
                            <button type="submit">Simpan Countdown</button>
                        </form>
                    </section>

                    <section id="gallery" class="card panel-section">
                        <h2>Gallery</h2>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="upload_gallery">
                            <div class="form-row"><label>Upload Multiple Images</label><input type="file" name="gallery_files[]" accept="image/*" multiple></div>
                            <button type="submit">Unggah Galeri</button>
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
                        <h2>Cover</h2>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="upload_cover">
                            <div class="form-row"><label>Upload Cover Image</label><input type="file" name="cover_image" accept="image/*" data-preview-target="#coverPreviewImg"></div>
                            <button type="submit">Unggah Cover</button>
                        </form>
                        <?php if ($coverPreview): ?>
                            <div class="image-preview"><img id="coverPreviewImg" src="/<?php echo escape_html($coverPreview); ?>" alt="Cover preview"></div>
                        <?php else: ?>
                            <div class="image-preview"><img id="coverPreviewImg" alt="Cover preview" style="display:none;"></div>
                        <?php endif; ?>
                    </section>

                    <section id="background" class="card panel-section">
                        <h2>Background</h2>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html(get_csrf_token()); ?>">
                            <input type="hidden" name="action" value="upload_background">
                            <div class="form-row"><label>Hero Background</label><input type="file" name="background_hero" accept="image/*" data-preview-target="#backgroundHeroPreviewImg"></div>
                            <div class="form-row"><label>Section Background 1</label><input type="file" name="background_section_1" accept="image/*" data-preview-target="#backgroundSection1PreviewImg"></div>
                            <div class="form-row"><label>Section Background 2</label><input type="file" name="background_section_2" accept="image/*" data-preview-target="#backgroundSection2PreviewImg"></div>
                            <div class="form-row"><label>Section Background 3</label><input type="file" name="background_section_3" accept="image/*" data-preview-target="#backgroundSection3PreviewImg"></div>
                            <button type="submit">Unggah Background</button>
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
                        <h2>Guest Link Generator</h2>
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
</body>
</html>
