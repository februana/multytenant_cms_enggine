<?php
require_once __DIR__ . '/config.php';

// Secure session cookie params
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

// Session timeout enforcement
if (!empty($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > SESSION_TIMEOUT) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? false);
    }
    session_destroy();
}

// Login handler
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

// Logout handler
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

// Admin-only content
if (!empty($_SESSION['admin'])) {
    $_SESSION['last_activity'] = time();

    // Ensure CSRF token exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Handle moderasi actions (Hide/Show/Delete message)
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
                        if ($stmt->execute()) {
                            $_POST = []; // clear form to avoid resubmit
                        }
                    } elseif ($action === 'show') {
                        $stmt = $db->prepare('UPDATE tamu SET visible = 1 WHERE id = :id');
                        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                        if ($stmt->execute()) {
                            $_POST = [];
                        }
                    } elseif ($action === 'delete') {
                        $stmt = $db->prepare('DELETE FROM tamu WHERE id = :id');
                        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                        if ($stmt->execute()) {
                            $_POST = [];
                        }
                    }
                    $db->close();
                } catch (Throwable $e) {
                    error_log('Admin moderasi error: ' . $e->getMessage());
                    $error = 'Gagal memproses aksi.';
                }
            }
        }
    }

    // Load RSVP data and statistics
    try {
        if (!is_readable(DB_PATH)) {
            $error = 'Database tidak ditemukan.';
        } else {
            $db = new SQLite3(DB_PATH, SQLITE3_OPEN_READONLY);
            
            // Ensure visible column exists (for backward compatibility)
            $checkCol = $db->querySingle("SELECT 1 FROM pragma_table_info('tamu') WHERE name='visible'");
            $visibleClause = $checkCol ? 'visible' : '1 as visible';
            
            // Get statistics
            $statsResult = $db->query('SELECT COUNT(*) as total, SUM(CASE WHEN status="Hadir" THEN 1 ELSE 0 END) as hadir, SUM(CASE WHEN status="Tidak Hadir" THEN 1 ELSE 0 END) as tidak_hadir, SUM(CASE WHEN ucapan IS NOT NULL AND ucapan != "" THEN 1 ELSE 0 END) as messages FROM tamu');
            if ($statsRow = $statsResult->fetchArray(SQLITE3_ASSOC)) {
                $stats = [
                    'total' => (int)($statsRow['total'] ?? 0),
                    'hadir' => (int)($statsRow['hadir'] ?? 0),
                    'tidak_hadir' => (int)($statsRow['tidak_hadir'] ?? 0),
                    'messages' => (int)($statsRow['messages'] ?? 0)
                ];
            }

            // Get RSVP data (latest first)
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
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin RSVP - Andi & Februana</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; background:#f5f1eb; color:#333; }
        .admin-container { max-width:1200px; margin:0 auto; padding:20px; }
        .admin-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; background:#fff; padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
        .admin-header h1 { font-size:28px; color:#333; }
        .admin-header a { color:#d4a574; text-decoration:none; padding:10px 15px; border-radius:6px; background:#f9f6f0; }
        .admin-header a:hover { background:#e8dfd5; }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:15px; margin-bottom:30px; }
        .stat-card { background:#fff; padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); text-align:center; }
        .stat-card .number { font-size:32px; font-weight:bold; color:#d4a574; }
        .stat-card .label { font-size:14px; color:#999; margin-top:5px; }
        .error { background:#fee; color:#c33; padding:12px; border-radius:6px; margin-bottom:20px; }
        .login-form { max-width:320px; background:#fff; padding:30px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin:50px auto; }
        .login-form h2 { margin-bottom:20px; text-align:center; }
        .login-form input { width:100%; padding:12px; margin:10px 0; border:1px solid #ddd; border-radius:6px; font-size:14px; }
        .login-form button { width:100%; padding:12px; margin-top:10px; background:#d4a574; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:500; }
        .login-form button:hover { background:#c59564; }
        .table-wrapper { background:#fff; padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        th { background:#f9f6f0; padding:12px; text-align:left; font-weight:600; color:#666; border-bottom:2px solid #eee; }
        td { padding:12px; border-bottom:1px solid #eee; }
        tr:hover { background:#fafaf8; }
        .actions { display:flex; gap:5px; flex-wrap:wrap; }
        .action-btn { padding:6px 12px; border:none; border-radius:4px; cursor:pointer; font-size:12px; text-decoration:none; display:inline-block; }
        .hide-btn { background:#ffd700; color:#333; }
        .hide-btn:hover { background:#ffc700; }
        .show-btn { background:#90ee90; color:#333; }
        .show-btn:hover { background:#80de80; }
        .delete-btn { background:#ff6b6b; color:#fff; }
        .delete-btn:hover { background:#ff5252; }
        .visible-badge { display:inline-block; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:500; }
        .visible-badge.yes { background:#e8f5e9; color:#2e7d32; }
        .visible-badge.no { background:#ffebee; color:#c62828; }
        .export-section { margin:20px 0; }
        .export-btn { background:#4CAF50; color:#fff; padding:10px 20px; border:none; border-radius:6px; cursor:pointer; text-decoration:none; display:inline-block; }
        .export-btn:hover { background:#45a049; }
        .modal { display:none; position:fixed; z-index:1; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.4); }
        .modal.show { display:block; }
        .modal-content { background-color:#fefefe; margin:15% auto; padding:20px; border:1px solid #888; border-radius:6px; width:80%; max-width:300px; }
        .modal-content h3 { margin-bottom:15px; }
        .modal-actions { display:flex; gap:10px; justify-content:flex-end; }
        .modal-actions button { padding:8px 15px; border:none; border-radius:4px; cursor:pointer; }
        .modal-actions .confirm { background:#ff6b6b; color:#fff; }
        .modal-actions .cancel { background:#ccc; }
    </style>
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

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number"><?php echo $stats['total']; ?></div>
                    <div class="label">Total RSVP</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $stats['hadir']; ?></div>
                    <div class="label">Hadir</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $stats['tidak_hadir']; ?></div>
                    <div class="label">Tidak Hadir</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $stats['messages']; ?></div>
                    <div class="label">Ucapan/Pesan</div>
                </div>
            </div>

            <div class="export-section">
                <a href="export-rsvp.php" class="export-btn" download>Download CSV</a>
            </div>

            <div class="table-wrapper">
                <h2 style="margin-bottom:20px;">Daftar RSVP (Terbaru Dulu)</h2>
                <?php if (empty($rsvpData)): ?>
                    <p style="color:#999; text-align:center; padding:40px;">Belum ada data RSVP.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width:20%;">Nama</th>
                                <th style="width:15%;">Status</th>
                                <th style="width:35%;">Ucapan</th>
                                <th style="width:15%;">Waktu</th>
                                <th style="width:10%;">Visibilitas</th>
                                <th style="width:5%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rsvpData as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['status']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($row['ucapan'] ?? '', 0, 60) . (strlen($row['ucapan'] ?? '') > 60 ? '...' : '')); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at']))); ?></td>
                                    <td>
                                        <span class="visible-badge <?php echo ($row['visible'] ?? 1) == 1 ? 'yes' : 'no'; ?>">
                                            <?php echo ($row['visible'] ?? 1) == 1 ? 'Tampil' : 'Tersembunyi'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <?php if (($row['visible'] ?? 1) == 1): ?>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="action" value="hide">
                                                    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                                    <button type="submit" class="action-btn hide-btn">Sembunyikan</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="action" value="show">
                                                    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                                    <button type="submit" class="action-btn show-btn">Tampilkan</button>
                                                </form>
                                            <?php endif; ?>
                                            <button type="button" class="action-btn delete-btn" onclick="confirmDelete(<?php echo (int)$row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama'])); ?>')">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <h3>Hapus Ucapan?</h3>
                    <p id="deleteName"></p>
                    <p style="color:#999; font-size:12px; margin-top:10px;">Aksi ini tidak dapat dibatalkan.</p>
                    <div class="modal-actions">
                        <button class="cancel" onclick="closeDeleteModal()">Batal</button>
                        <button class="confirm" onclick="submitDelete()">Hapus</button>
                    </div>
                </div>
            </div>

            <script>
                let deleteId = null;
                function confirmDelete(id, name) {
                    deleteId = id;
                    document.getElementById('deleteName').textContent = name;
                    document.getElementById('deleteModal').classList.add('show');
                }
                function closeDeleteModal() {
                    document.getElementById('deleteModal').classList.remove('show');
                    deleteId = null;
                }
                function submitDelete() {
                    if (deleteId) {
                        const form = document.createElement('form');
                        form.method = 'post';
                        form.innerHTML = '<input type="hidden" name="csrf_token" value="' + <?php echo json_encode($_SESSION['csrf_token']); ?> + '">' +
                                        '<input type="hidden" name="action" value="delete">' +
                                        '<input type="hidden" name="id" value="' + deleteId + '">';
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
                window.onclick = function(event) {
                    const modal = document.getElementById('deleteModal');
                    if (event.target == modal) {
                        closeDeleteModal();
                    }
                }
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
