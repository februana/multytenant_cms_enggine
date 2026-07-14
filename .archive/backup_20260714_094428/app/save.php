<?php
require_once __DIR__ . '/config.php';
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
    $db = new SQLite3(DB_PATH, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    $db->exec('CREATE TABLE IF NOT EXISTS tamu (id INTEGER PRIMARY KEY AUTOINCREMENT, nama TEXT NOT NULL, status TEXT NOT NULL, ucapan TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
    $checkCol = $db->querySingle("SELECT 1 FROM pragma_table_info('tamu') WHERE name='visible'");
    if (!$checkCol) {
        @$db->exec('ALTER TABLE tamu ADD COLUMN visible INTEGER DEFAULT 1');
    }
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
