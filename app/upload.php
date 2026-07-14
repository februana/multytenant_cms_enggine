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
    } else $error = 'Username atau password salah.';
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? false);
    }
    session_destroy();
    header('Location: upload.php');
    exit;
}

$galleryFiles = [];
$coverNames = ['cover.webp','cover.jpg','cover.jpeg'];
$storageDir = __DIR__ . '/uploads';
$legacyGallery = __DIR__ . '/gallery';
if (!is_dir($storageDir) && !is_dir($legacyGallery)) {
    mkdir($storageDir, 0755, true);
}
if (is_dir($storageDir)) {
    $galleryDir = $storageDir;
    $publicDir = 'uploads';
} else {
    $galleryDir = $legacyGallery;
    $publicDir = 'gallery';
}

if (!empty($_SESSION['admin'])) {
    $_SESSION['last_activity'] = time();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file']) && isset($_POST['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $error = 'Token CSRF tidak valid.';
        } else {
            $filename = (string)($_POST['delete_file'] ?? '');
            if ($filename === '' || basename($filename) !== $filename) {
                $error = 'Nama file tidak valid.';
            } else {
                if (in_array(strtolower($filename), $coverNames, true)) {
                    $error = 'Tidak bisa menghapus file cover.';
                } else {
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if (!in_array($ext, ALLOWED_IMAGE_TYPES, true)) {
                        $error = 'Format file tidak diizinkan untuk dihapus.';
                    } else {
                        $filepath = $galleryDir . '/' . $filename;
                        $realpath = realpath($filepath);
                        $trustedDir = realpath($galleryDir);
                        if ($realpath && $trustedDir && strpos($realpath, $trustedDir) === 0 && is_file($realpath)) {
                            if (@unlink($realpath)) {
                                $thumbPath = $trustedDir . '/thumbs/' . $filename;
                                if (is_file($thumbPath)) @unlink($thumbPath);
                                $_POST = [];
                            } else {
                                $error = 'Gagal menghapus file.';
                            }
                        } else {
                            $error = 'File tidak ditemukan atau akses ditolak.';
                        }
                    }
                }
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
        $file = $_FILES['photo'];
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Gagal mengunggah file.';
        } elseif (!is_uploaded_file($file['tmp_name'])) {
            $error = 'Sumber file tidak valid.';
        } elseif ($file['size'] > MAX_UPLOAD_SIZE) {
            $error = 'Ukuran file terlalu besar.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_IMAGE_TYPES, true)) {
                $error = 'Format gambar tidak diizinkan.';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
                if ($finfo) finfo_close($finfo);
                if (stripos($mime, 'image/') !== 0) {
                    $error = 'Tipe file bukan gambar.';
                } elseif (!@getimagesize($file['tmp_name'])) {
                    $error = 'File bukan gambar yang valid.';
                } else {
                    if (!is_dir($galleryDir)) mkdir($galleryDir, 0755, true);
                    $safeName = bin2hex(random_bytes(12)) . '.' . $ext;
                    $dest = $galleryDir . '/' . $safeName;
                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $error = 'Gagal menyimpan file.';
                    }
                }
            }
        }
    }

    if (is_dir($galleryDir)) {
        foreach (scandir($galleryDir) ?: [] as $f) {
            if ($f === '.' || $f === '..' || $f === 'thumbs') continue;
            $path = $galleryDir . '/' . $f;
            if (!is_file($path)) continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_IMAGE_TYPES, true)) continue;
            if (!in_array(strtolower($f), $coverNames, true)) {
                $galleryFiles[] = ['name' => $f, 'mtime' => filemtime($path) ?: 0];
            }
        }
        usort($galleryFiles, function($a, $b){ return $b['mtime'] <=> $a['mtime']; });
    }
}

?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Upload Galeri - Andi & Februana</title>
    <style>/* omitted for brevity in repo copy; keep CSS in app/style.css in deployment */</style>
</head>
<body>
    <div class="container">
        <?php if (empty($_SESSION['admin'])): ?>
            <div class="login-form">
                <h2>Login Admin</h2>
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="form-group">
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" name="login">Login</button>
                </form>
            </div>
        <?php else: ?>
            <div class="header">
                <div>
                    <h1>Upload Galeri</h1>
                    <p style="color:#999; margin-top:5px;">Andi & Februana</p>
                </div>
                <a href="?logout=1">Logout</a>
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-section">
                <h2>Upload Foto Baru</h2>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Pilih Gambar</label>
                        <input type="file" name="photo" accept="image/*" required>
                    </div>
                    <button type="submit">Upload Foto</button>
                </form>
            </div>

            <div class="form-section">
                <h2>Galeri Foto (<?php echo count($galleryFiles); ?> foto)</h2>
                <?php if (empty($galleryFiles)): ?>
                    <div class="empty-gallery">
                        <p>Belum ada foto di galeri.</p>
                    </div>
                <?php else: ?>
                    <div class="gallery-grid">
                        <?php foreach ($galleryFiles as $item): ?>
                            <div class="gallery-item">
                                <img src="<?php echo htmlspecialchars($publicDir . '/' . rawurlencode($item['name'])); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                                <div class="gallery-item-info">
                                    <div class="gallery-item-name"><?php echo htmlspecialchars(substr($item['name'], 0, 20)); ?></div>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="delete_file" value="<?php echo htmlspecialchars($item['name']); ?>">
                                        <button type="submit" class="gallery-item-delete" onclick="return confirm('Hapus foto ini?')">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
