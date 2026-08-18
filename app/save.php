<?php
// Load consolidated canonical config
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/theme-contract.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
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
    if (empty($_SESSION['admin'])) {
        respond(false, 'Akses ditolak.');
    }
    $config = load_config();
    if ($action === 'upload_groom_photo') {
        if (empty($_FILES['groom_photo']['name'])) respond(false, 'File foto Mempelai Pria (groom) tidak ditemukan.');
        $result = upload_file($_FILES['groom_photo'], UPLOADS_COVER_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
        if (!empty($result['error'])) respond(false, $result['error']);
        $config['media']['groom_photo'] = relative_path($result['path']);
        save_config($config);
        respond(true, 'Foto Mempelai Pria (Groom) berhasil diunggah.', ['path' => $config['media']['groom_photo']]);
    } elseif ($action === 'upload_bride_photo') {
        if (empty($_FILES['bride_photo']['name'])) respond(false, 'File foto Mempelai Wanita (bride) tidak ditemukan.');
        $result = upload_file($_FILES['bride_photo'], UPLOADS_COVER_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
        if (!empty($result['error'])) respond(false, $result['error']);
        $config['media']['bride_photo'] = relative_path($result['path']);
        save_config($config);
        respond(true, 'Foto Mempelai Wanita (Bride) berhasil diunggah.', ['path' => $config['media']['bride_photo']]);
    } elseif ($action === 'upload_couple_photo') {
        if (empty($_FILES['couple_photo']['name'])) respond(false, 'File foto Pasangan (couple) tidak ditemukan.');
        $result = upload_file($_FILES['couple_photo'], UPLOADS_COVER_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
        if (!empty($result['error'])) respond(false, $result['error']);
        $config['media']['couple_photo'] = relative_path($result['path']);
        save_config($config);
        respond(true, 'Foto Pasangan berhasil diunggah.', ['path' => $config['media']['couple_photo']]);
    } elseif ($action === 'save_theme_options') {
        $presetKey = trim((string)($_POST['preset_key'] ?? ($config['theme']['theme_preset'] ?? 'dewankl')));
        if ($presetKey !== '') {
            if (!isset($config['theme_options'][$presetKey])) {
                $config['theme_options'][$presetKey] = [];
            }
            $presetRegistry = theme_registry()[$presetKey] ?? [];
            $presetSchema = $presetRegistry['schema'] ?? [];

            foreach ($presetSchema as $schemaKey => $schemaDef) {
                if (($schemaDef['type'] ?? '') === 'image') {
                    $fileKey = 'theme_opts_file_' . $schemaKey;
                    if (isset($_FILES[$fileKey]) && !empty($_FILES[$fileKey]['name'])) {
                        $uploadRes = upload_file($_FILES[$fileKey], UPLOADS_COVER_DIR, ALLOWED_IMAGE_TYPES, MAX_UPLOAD_SIZE);
                        if (empty($uploadRes['error'])) {
                            $config['theme_options'][$presetKey][$schemaKey] = relative_path($uploadRes['path']);
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

                    if ($fieldType === 'image' && trim($strVal) === '' && !empty($config['theme_options'][$presetKey][$optKey])) {
                        continue;
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
            save_config($config);
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
    $db = new SQLite3(DB_PATH, SQLITE3_OPEN_READWRITE);
    $stmt = $db->prepare('INSERT INTO tamu (nama,status,ucapan,visible) VALUES (:nama,:status,:ucapan,1)');
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
