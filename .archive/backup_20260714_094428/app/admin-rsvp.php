<?php
require_once __DIR__ . '/config.php';

$secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (bool)$secureFlag,
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$error = '';
$stats = ['total' => 0, 'hadir' => 0, 'tidak_hadir' => 0, 'messages' => 0];
$rsvpData = [];

if (!empty($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > SESSION_TIMEOUT) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? false);
    }
    session_destroy();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (hash_equals(ADMIN_USER, (string)($_POST['username'] ?? '')) && hash_equals(ADMIN_PASS, (string)($_POST['password'] ?? ''))) {
        $_SESSION['admin'] = true;
        $_SESSION['last_activity'] = time();
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        session_regenerate_id(true);
    } else {
        $error = 'Username atau password salah.';
    }
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? false);
    }
    session_destroy();
    header('Location: admin-rsvp.php');
    exit;
}

if (!empty($_SESSION['admin'])) {
    $_SESSION['last_activity'] = time();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $error = 'Token CSRF tidak valid.';
        } else {
            $action = (string)($_POST['action'] ?? '');
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0 && in_array($action, ['hide', 'show', 'delete'], true)) {
                try {
                    $db = new SQLite3(DB_PATH, SQLITE3_OPEN_READWRITE);
                    if ($action === 'hide') {
                        $stmt = $db->prepare('UPDATE tamu SET visible = 0 WHERE id = :id');
                        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                        if ($stmt->execute()) { $_POST = []; }
                    } elseif ($action === 'show') {
                        $stmt = $db->prepare('UPDATE tamu SET visible = 1 WHERE id = :id');
                        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                        if ($stmt->execute()) { $_POST = []; }
                    } elseif ($action === 'delete') {
                        $stmt = $db->prepare('DELETE FROM tamu WHERE id = :id');
                        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                        if ($stmt->execute()) { $_POST = []; }
                    }
                    $db->close();
                } catch (Throwable $e) {
                    error_log('Admin moderasi error: ' . $e->getMessage());
                    $error = 'Gagal memproses aksi.';
                }
            }
        }
    }

    try {
        if (!is_readable(DB_PATH)) {
            $error = 'Database tidak ditemukan.';
        } else {
            $db = new SQLite3(DB_PATH, SQLITE3_OPEN_READONLY);
            $checkCol = $db->querySingle("SELECT 1 FROM pragma_table_info('tamu') WHERE name='visible'");
            $visibleClause = $checkCol ? 'visible' : '1 as visible';
            $statsResult = $db->query('SELECT COUNT(*) as total, SUM(CASE WHEN status="Hadir" THEN 1 ELSE 0 END) as hadir, SUM(CASE WHEN status="Tidak Hadir" THEN 1 ELSE 0 END) as tidak_hadir, SUM(CASE WHEN ucapan IS NOT NULL AND ucapan != "" THEN 1 ELSE 0 END) as messages FROM tamu');
            if ($statsRow = $statsResult->fetchArray(SQLITE3_ASSOC)) {
                $stats = [
                    'total' => (int)($statsRow['total'] ?? 0),
                    'hadir' => (int)($statsRow['hadir'] ?? 0),
                    'tidak_hadir' => (int)($statsRow['tidak_hadir'] ?? 0),
                    'messages' => (int)($statsRow['messages'] ?? 0)
                ];
            }
            $result = $db->query("SELECT id, nama, status, ucapan, created_at, $visibleClause FROM tamu ORDER BY id DESC");
            $rsvpData = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $rsvpData[] = $row;
            }
            $db->close();
        }
    } catch (Throwable $e) {
        error_log('Admin RSVP load error: ' . $e->getMessage());
        $error = 'Gagal memuat data RSVP.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin RSVP - Andi & Februana</title>
</head>
<body>
    <div class="admin-container">
        <?php if (empty($_SESSION['admin'])): ?>
            <div class="login-form">
                <h2>Login Admin</h2>
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button name="login" type="submit">Login</button>
                </form>
            </div>
        <?php else: ?>
            <div class="admin-header">
                <div>
                    <h1>Dashboard RSVP</h1>
                    <p style="color:#999; margin-top:5px;">Andi & Februana - Manajemen Undangan</p>
                </div>
                <a href="?logout=1">Logout</a>
            </div>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <!-- content omitted in repo copy: UI remains in original file when deploying -->
        <?php endif; ?>
    </div>
</body>
</html>
