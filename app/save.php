<?php
// Load consolidated canonical config
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/theme-contract.php';
init_session();
header('Content-Type: application/json; charset=utf-8');
$tenant = current_tenant(false);
if (!is_array($tenant)) respond(false, 'Domain tidak terdaftar atau sedang ditangguhkan.');
function respond($success, $message = '', $extra = []) {
    echo json_encode(array_merge(['success' => (bool)$success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_csrf'])) {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    respond(true, '', ['csrf_token'=>$_SESSION['csrf_token']]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'Metode tidak valid.');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) respond(false, 'Token CSRF tidak valid.');

// Handle admin photo upload actions if posted to save.php
$action = trim((string)($_POST['action'] ?? ''));
if ($action !== '') {
    if (!session_admin_is_valid()) {
        respond(false, 'Akses ditolak atau sesi tidak berlaku untuk domain ini.');
    }
    $config = load_config();
    $pendingMediaCleanup = [];
    $queueMediaCleanup = static function (string $oldPath, string $newPath) use (&$pendingMediaCleanup): void {
        if (trim($oldPath) !== '' && trim($oldPath) !== trim($newPath)) $pendingMediaCleanup[] = [$oldPath, $newPath];
    };
    if ($action === 'upload_groom_photo') {
        if (empty($_FILES['groom_photo']['name'])) respond(false, 'File foto Mempelai Pria (groom) tidak ditemukan.');
        $result = upload_file($_FILES['groom_photo'], UPLOADS_COVER_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'groom_photo', $config['theme']['theme_preset'] ?? null);
        if (!empty($result['error'])) respond(false, $result['error']);
        $newPath = relative_path($result['path']);
        $queueMediaCleanup((string)($config['media']['groom_photo'] ?? ''), $newPath);
        $config['media']['groom_photo'] = $newPath;
        if (!save_config($config)) respond(false, 'Gagal menyimpan konfigurasi media.');
        $config = load_config();
        foreach ($pendingMediaCleanup as [$oldMediaPath, $newMediaPath]) cleanup_replaced_media($oldMediaPath, $config);
        respond(true, 'Foto Mempelai Pria (Groom) berhasil diunggah.', ['path' => $config['media']['groom_photo']]);
    } elseif ($action === 'upload_bride_photo') {
        if (empty($_FILES['bride_photo']['name'])) respond(false, 'File foto Mempelai Wanita (bride) tidak ditemukan.');
        $result = upload_file($_FILES['bride_photo'], UPLOADS_COVER_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'bride_photo', $config['theme']['theme_preset'] ?? null);
        if (!empty($result['error'])) respond(false, $result['error']);
        $newPath = relative_path($result['path']);
        $queueMediaCleanup((string)($config['media']['bride_photo'] ?? ''), $newPath);
        $config['media']['bride_photo'] = $newPath;
        if (!save_config($config)) respond(false, 'Gagal menyimpan konfigurasi media.');
        $config = load_config();
        foreach ($pendingMediaCleanup as [$oldMediaPath, $newMediaPath]) cleanup_replaced_media($oldMediaPath, $config);
        respond(true, 'Foto Mempelai Wanita (Bride) berhasil diunggah.', ['path' => $config['media']['bride_photo']]);
    } elseif ($action === 'upload_couple_photo') {
        if (empty($_FILES['couple_photo']['name'])) respond(false, 'File foto Pasangan (couple) tidak ditemukan.');
        $result = upload_file($_FILES['couple_photo'], UPLOADS_COVER_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'couple_photo', $config['theme']['theme_preset'] ?? null);
        if (!empty($result['error'])) respond(false, $result['error']);
        $newPath = relative_path($result['path']);
        $queueMediaCleanup((string)($config['media']['couple_photo'] ?? ''), $newPath);
        $config['media']['couple_photo'] = $newPath;
        if (!save_config($config)) respond(false, 'Gagal menyimpan konfigurasi media.');
        $config = load_config();
        foreach ($pendingMediaCleanup as [$oldMediaPath, $newMediaPath]) cleanup_replaced_media($oldMediaPath, $config);
        respond(true, 'Foto Pasangan berhasil diunggah.', ['path' => $config['media']['couple_photo']]);
    } elseif ($action === 'save_theme_options') {
        $presetKey = trim((string)($_POST['preset_key'] ?? ($config['theme']['theme_preset'] ?? 'dewankl')));
        if ($presetKey !== '') {
            if (!isset($config['theme_options'][$presetKey])) {
                $config['theme_options'][$presetKey] = [];
            }
            $presetRegistry = theme_registry()[$presetKey] ?? [];
            $presetSchema = $presetRegistry['schema'] ?? [];

            $uploadedThemeOptionKeys = [];
            foreach ($presetSchema as $schemaKey => $schemaDef) {
                if (($schemaDef['type'] ?? '') === 'image') {
                    $fileKey = 'theme_opts_file_' . $schemaKey;
                    if (isset($_FILES[$fileKey]) && !empty($_FILES[$fileKey]['name'])) {
                        $themeAssetPreset = preg_replace('/[^a-z0-9_-]/i', '', $presetKey) ?: 'custom';
                        $themeAssetDir = UPLOADS_THEME_ASSETS_DIR . '/' . $themeAssetPreset;
                        $previousThemeAsset = (string)($config['theme_options'][$presetKey][$schemaKey] ?? '');
                        $uploadRes = upload_file($_FILES[$fileKey], $themeAssetDir, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE, 'theme_asset', $presetKey);
                        if (empty($uploadRes['error'])) {
                            $newThemeAsset = relative_path($uploadRes['path']);
                            $config['theme_options'][$presetKey][$schemaKey] = $newThemeAsset;
                            $uploadedThemeOptionKeys[$schemaKey] = true;
                            $queueMediaCleanup($previousThemeAsset, $newThemeAsset);
                        }
                    }
                }
            }

            if (isset($_POST['theme_opts']) && is_array($_POST['theme_opts'])) {
                foreach ($_POST['theme_opts'] as $optKey => $optVal) {
                    $optKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $optKey);
                    if ($optKey === '') continue;
                    if (is_array($optVal)) continue;

                    $fieldType = $presetSchema[$optKey]['type'] ?? '';
                    $strVal = str_replace("\r\n", "\n", (string)$optVal);

                    if ($fieldType === 'image') {
                        if (isset($uploadedThemeOptionKeys[$optKey])) continue;
                        if (trim($strVal) === '' && !empty($config['theme_options'][$presetKey][$optKey])) continue;
                        if (trim($strVal) !== '' && !theme_visual_image_reference_is_canonical($strVal)) continue;
                    }

                    if ($optVal === '1' || $optVal === 'true') {
                        $config['theme_options'][$presetKey][$optKey] = true;
                    } elseif ($optVal === '0' || $optVal === 'false') {
                        $config['theme_options'][$presetKey][$optKey] = false;
                    } else {
                        $config['theme_options'][$presetKey][$optKey] = $strVal;
                    }
                }
            }
            if (!save_config($config)) respond(false, 'Gagal menyimpan opsi preset.');
            $config = load_config();
            foreach ($pendingMediaCleanup as [$oldMediaPath, $newMediaPath]) cleanup_replaced_media($oldMediaPath, $config);
            respond(true, 'Opsi preset berhasil disimpan.', ['theme_options' => $config['theme_options'][$presetKey]]);
        }
        respond(false, 'Preset key tidak valid.');
    }
}

if (!empty($_SESSION['last_submit']) && (time() - (int)$_SESSION['last_submit']) < 10) {
    respond(false, 'Tolong tunggu beberapa detik sebelum mengirim lagi.');
}

$website = trim($_POST['website'] ?? '');
if ($website !== '') {
    respond(true, 'Terima kasih.');
}

$nama = trim((string)($_POST['nama'] ?? ''));
$status = trim((string)($_POST['status'] ?? ''));
$ucapan = trim((string)($_POST['ucapan'] ?? ''));

if ($nama === '') respond(false, 'Nama tidak boleh kosong.');
if (mb_strlen($nama) > 80) respond(false, 'Nama terlalu panjang (maks 80 karakter).');
if (mb_strlen($ucapan) > 500) respond(false, 'Ucapan terlalu panjang (maks 500 karakter).');
if (!in_array($status, ['Hadir','Tidak Hadir'], true)) respond(false, 'Status tidak valid.');

try {
    init_database();
    $db = tenant_database(false);
    $stmt = $db->prepare('INSERT INTO tamu (tenant_id,nama,status,ucapan,visible) VALUES (:tenant_id,:nama,:status,:ucapan,1)');
    $stmt->bindValue(':tenant_id', (int)$tenant['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':nama', $nama, SQLITE3_TEXT);
    $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    $stmt->bindValue(':ucapan', $ucapan, SQLITE3_TEXT);
    $res = $stmt->execute();
    if ($res === false) {
        respond(false, 'Gagal menyimpan data.');
    }
    $_SESSION['last_submit'] = time();
    respond(true, 'Terima kasih, RSVP berhasil dikirim.');
} catch (Throwable $e) {
    error_log('RSVP save error: ' . $e->getMessage());
    respond(false, 'Gagal menyimpan data.');
}
?>
