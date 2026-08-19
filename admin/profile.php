<?php
require_once __DIR__ . '/../config.php';
init_session();
require_admin();
if (!is_super_admin()) {
    http_response_code(403);
    exit('Akses hanya untuk Super Admin.');
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        try {
            $username = trim((string)($_POST['username'] ?? ''));
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmation = (string)($_POST['new_password_confirmation'] ?? '');
            if ($username === '') throw new RuntimeException('Username wajib diisi.');
            if ($newPassword !== '' && strlen($newPassword) < 8) throw new RuntimeException('Password minimal 8 karakter.');
            if ($newPassword !== $confirmation) throw new RuntimeException('Konfirmasi password tidak sama.');
            if (!update_current_user_username($username)) throw new RuntimeException('Gagal memperbarui username.');
            if ($newPassword !== '' && !update_current_user_password($newPassword)) throw new RuntimeException('Gagal memperbarui password.');
            $message = 'Profil Super Admin berhasil diperbarui.';
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}
$csrf = get_csrf_token();
?><!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Profil Super Admin</title><link rel="stylesheet" href="/admin/style.css"></head>
<body>
<main class="container" style="max-width:720px;margin:32px auto;padding:0 16px">
  <h1>Profil Super Admin</h1>
  <p><a href="/admin/super-admin.php">Kembali ke Super Admin Dashboard</a> · <a href="/admin/?logout=1">Keluar</a></p>
  <?php if ($message !== ''): ?><p class="success"><?= escape_html($message) ?></p><?php endif; ?>
  <?php if ($error !== ''): ?><p class="error"><?= escape_html($error) ?></p><?php endif; ?>
  <section class="card" style="padding:20px">
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= escape_html($csrf) ?>">
      <label>Username Super Admin<br><input name="username" required value="<?= escape_html((string)($_SESSION['username'] ?? '')) ?>"></label><br>
      <label>Password baru<br><input type="password" name="new_password" minlength="8" placeholder="Kosongkan jika tidak berubah"></label><br>
      <label>Konfirmasi password baru<br><input type="password" name="new_password_confirmation" minlength="8"></label><br>
      <button type="submit">Simpan Profil</button>
    </form>
  </section>
</main>
</body>
</html>
