<?php
require_once __DIR__ . '/config.php';

// Secure session cookie params for upload/admin area
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

// session timeout enforcement
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

if (!empty($_SESSION['admin'])) {
    $_SESSION['last_activity'] = time();
    
    // Ensure CSRF token exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Handle file deletion with CSRF protection
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file']) && isset($_POST['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $error = 'Token CSRF tidak valid.';
        } else {
            $filename = (string)($_POST['delete_file'] ?? '');
            if ($filename === '' || basename($filename) !== $filename) {
                $error = 'Nama file tidak valid.';
            } else {
                // Check if it's a cover file
                if (in_array(strtolower($filename), $coverNames, true)) {
                    $error = 'Tidak bisa menghapus file cover.';
                } else {
                    // Validate extension
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if (!in_array($ext, ALLOWED_IMAGE_TYPES, true)) {
                        $error = 'Format file tidak diizinkan untuk dihapus.';
                    } else {
                        $filepath = __DIR__ . '/gallery/' . $filename;
                        // Use realpath to prevent path traversal
                        $realpath = realpath($filepath);
                        $galleryDir = realpath(__DIR__ . '/gallery');
                        
                        if ($realpath && $galleryDir && strpos($realpath, $galleryDir) === 0 && is_file($realpath)) {
                            if (@unlink($realpath)) {
                                // Try to delete corresponding thumbnail
                                $thumbPath = $galleryDir . '/thumbs/' . $filename;
                                if (is_file($thumbPath)) @unlink($thumbPath);
                                $_POST = []; // clear form
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

    // Handle photo upload
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
                    if (!is_dir(__DIR__ . '/gallery')) mkdir(__DIR__ . '/gallery', 0755, true);
                    $safeName = bin2hex(random_bytes(12)) . '.' . $ext;
                    $dest = __DIR__ . '/gallery/' . $safeName;
                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $error = 'Gagal menyimpan file.';
                    }
                }
            }
        }
    }

    // Load gallery files
    $galleryDir = __DIR__ . '/gallery';
    if (is_dir($galleryDir)) {
        foreach (scandir($galleryDir) ?: [] as $f) {
            if ($f === '.' || $f === '..' || $f === 'thumbs') continue;
            $path = $galleryDir . '/' . $f;
            if (!is_file($path)) continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_IMAGE_TYPES, true)) continue;
            // Skip cover files
            if (!in_array(strtolower($f), $coverNames, true)) {
                $galleryFiles[] = ['name' => $f, 'mtime' => filemtime($path) ?: 0];
            }
        }
        // Sort by newest first
        usort($galleryFiles, function($a, $b){ return $b['mtime'] <=> $a['mtime']; });
    }
}

?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Upload Galeri - Andi & Februana</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; background:#f5f1eb; color:#333; }
        .container { max-width:1000px; margin:0 auto; padding:20px; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
        .header h1 { font-size:28px; }
        .header a { color:#d4a574; text-decoration:none; padding:10px 15px; background:#f9f6f0; border-radius:6px; }
        .header a:hover { background:#e8dfd5; }
        .login-form { max-width:320px; background:#fff; padding:30px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin:50px auto; }
        .login-form input { width:100%; padding:12px; margin:8px 0; border:1px solid #ddd; border-radius:6px; }
        .login-form button { width:100%; padding:12px; margin-top:10px; background:#d4a574; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:500; }
        .login-form button:hover { background:#c59564; }
        .error { background:#fee; color:#c33; padding:12px; border-radius:6px; margin-bottom:20px; }
        .form-section { background:#fff; padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin-bottom:30px; }
        .form-section h2 { font-size:18px; margin-bottom:15px; }
        .form-group { margin-bottom:15px; }
        .form-group label { display:block; margin-bottom:5px; font-weight:500; }
        .form-group input { width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; }
        .form-group button { background:#d4a574; color:#fff; padding:10px 20px; border:none; border-radius:6px; cursor:pointer; font-weight:500; }
        .form-group button:hover { background:#c59564; }
        .gallery-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:15px; }
        .gallery-item { background:#f9f6f0; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1); transition:transform 0.2s; }
        .gallery-item:hover { transform:translateY(-2px); }
        .gallery-item img { width:100%; height:120px; object-fit:cover; }
        .gallery-item-info { padding:10px; }
        .gallery-item-name { font-size:12px; color:#666; margin-bottom:8px; word-break:break-all; }
        .gallery-item-delete { width:100%; padding:6px; background:#ff6b6b; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:12px; }
        .gallery-item-delete:hover { background:#ff5252; }
        .empty-gallery { text-align:center; color:#999; padding:40px; }
    </style>
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
                                <img src="gallery/<?php echo rawurlencode($item['name']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
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
