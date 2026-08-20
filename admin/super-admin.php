<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/theme-helper.php';
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
            $db = tenant_database(false);
            $action = (string)($_POST['action'] ?? '');
            if (!in_array($action, ['create_tenant', 'set_status', 'reset_tenant_password'], true)) throw new RuntimeException('Aksi Super Admin tidak dikenal.');
            if (!verify_current_admin_password((string)($_POST['current_password'] ?? ''))) throw new RuntimeException('Password Super Admin saat ini tidak benar.');
            if ($action === 'create_tenant') {
                $db->exec('BEGIN IMMEDIATE');
                try {
                    $domain = normalize_tenant_domain((string)($_POST['domain'] ?? ''));
                    if (!is_valid_tenant_domain($domain)) throw new RuntimeException('Domain tidak valid.');
                    $stmt = $db->prepare("INSERT INTO tenants (domain, status) VALUES (:domain, 'active')");
                    $stmt->bindValue(':domain', $domain, SQLITE3_TEXT);
                    if (!$stmt->execute()) throw new RuntimeException('Domain sudah terdaftar atau gagal disimpan.');
                    $tenantId = (int)$db->lastInsertRowID();
                    ensure_tenant_seed($db, $tenantId);
                    $tenantUsername = trim((string)($_POST['tenant_username'] ?? '')) ?: tenant_admin_username_for_domain($domain);
                    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{2,63}$/', $tenantUsername)) throw new RuntimeException('Username Tenant Admin tidak valid.');
                    $tenantPassword = (string)($_POST['tenant_password'] ?? '');
                    if ($tenantPassword === '') $tenantPassword = generate_random_password(16);
                    $passwordError = admin_password_policy_error($tenantPassword);
                    if ($passwordError !== null) throw new RuntimeException($passwordError);
                    $visiblePassword = encrypt_visible_password($tenantPassword);
                    if ($visiblePassword === '') throw new RuntimeException('UNDANGAN_PASSWORD_KEY belum dikonfigurasi.');
                    $user = $db->prepare("INSERT INTO users (tenant_id, username, password_hash, visible_password, role) VALUES (:tenant_id, :username, :password_hash, :visible_password, 'tenant_admin')");
                    $user->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
                    $user->bindValue(':username', $tenantUsername, SQLITE3_TEXT);
                    $user->bindValue(':password_hash', password_hash($tenantPassword, PASSWORD_DEFAULT), SQLITE3_TEXT);
                    $user->bindValue(':visible_password', $visiblePassword, SQLITE3_TEXT);
                    if (!$user->execute()) throw new RuntimeException('Gagal membuat akun Tenant Admin.');
                    $db->exec('COMMIT');
                    audit_log('tenant_created', $tenantId, ['domain' => $domain, 'tenant_admin_username' => $tenantUsername]);
                    $message = 'Tenant berhasil dibuat. Simpan credential Tenant Admin yang ditampilkan sekali ini: ' . $tenantUsername . ' / ' . $tenantPassword;
                } catch (Throwable $e) {
                    $db->exec('ROLLBACK');
                    throw $e;
                }
            } elseif ($action === 'set_status') {
                $tenantId = (int)($_POST['tenant_id'] ?? 0);
                $status = (string)($_POST['status'] ?? '');
                if (!in_array($status, ['active', 'suspended'], true) || $tenantId < 1) throw new RuntimeException('Status tenant tidak valid.');
                $stmt = $db->prepare('UPDATE tenants SET status = :status WHERE id = :tenant_id');
                $stmt->bindValue(':status', $status, SQLITE3_TEXT);
                $stmt->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
                if (!$stmt->execute() || $db->changes() !== 1) throw new RuntimeException('Tenant tidak ditemukan atau status tidak berubah.');
                audit_log('tenant_status_changed', $tenantId, ['status' => $status]);
                $message = 'Status tenant diperbarui.';
            } elseif ($action === 'reset_tenant_password') {
                $tenantId = (int)($_POST['tenant_id'] ?? 0);
                $userId = (int)($_POST['user_id'] ?? 0);
                $newPassword = (string)($_POST['new_password'] ?? '');
                if ($tenantId < 1 || $userId < 1) throw new RuntimeException('Tenant atau user tidak valid.');
                if ($newPassword === '') $newPassword = generate_random_password(16);
                $passwordError = admin_password_policy_error($newPassword);
                if ($passwordError !== null) throw new RuntimeException($passwordError);
                $visiblePassword = encrypt_visible_password($newPassword);
                if ($visiblePassword === '') throw new RuntimeException('UNDANGAN_PASSWORD_KEY belum dikonfigurasi.');
                $stmt = $db->prepare("UPDATE users SET password_hash = :password_hash, visible_password = :visible_password WHERE id = :user_id AND tenant_id = :tenant_id AND role = 'tenant_admin'");
                $stmt->bindValue(':password_hash', password_hash($newPassword, PASSWORD_DEFAULT), SQLITE3_TEXT);
                $stmt->bindValue(':visible_password', $visiblePassword, SQLITE3_TEXT);
                $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
                $stmt->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
                if (!$stmt->execute() || $db->changes() !== 1) throw new RuntimeException('Tenant Admin tidak ditemukan atau password gagal diperbarui.');
                audit_log('tenant_admin_password_reset', $tenantId, ['user_id' => $userId]);
                $message = 'Password Tenant Admin diperbarui. Simpan password baru ini karena hanya ditampilkan sekali: ' . $newPassword;
            }
            $db->close();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$db = tenant_database(true);
$tenants = [];
$result = $db->query("SELECT t.id, t.domain, t.status, t.created_at,
    (SELECT COUNT(*) FROM users u WHERE u.tenant_id = t.id) AS user_count,
    (SELECT COUNT(*) FROM tamu g WHERE g.tenant_id = t.id) AS guest_count
    FROM tenants t ORDER BY t.id");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $row['tenant_admins'] = [];
    $adminStmt = $db->prepare("SELECT id, username FROM users WHERE tenant_id = :tenant_id AND role = 'tenant_admin' ORDER BY id");
    $adminStmt->bindValue(':tenant_id', (int)$row['id'], SQLITE3_INTEGER);
    $adminResult = $adminStmt->execute();
    while ($admin = $adminResult->fetchArray(SQLITE3_ASSOC)) $row['tenant_admins'][] = $admin;
    $tenants[] = $row;
}
$db->close();
$csrf = get_csrf_token();
?><!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Super Admin — Tenant</title><link rel="stylesheet" href="/admin/style.css"></head>
<body>
<main class="container" style="max-width:1100px;margin:32px auto;padding:0 16px">
  <h1>Panel Pengelola Semua Tenant</h1>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap"><a href="/admin">Kembali ke CMS tenant</a> · <a href="/admin/profile.php">Profil Super Admin</a> · <a href="/admin/audit-log.php">Log Keamanan</a> · <form method="post" action="/admin" style="display:inline"><input type="hidden" name="csrf_token" value="<?= escape_html($csrf) ?>"><button type="submit" name="logout" value="1" class="link-button">Keluar</button></form></div>
  <?php if ($message !== ''): ?><p class="success"><?= escape_html($message) ?></p><?php endif; ?>
  <?php if ($error !== ''): ?><p class="error"><?= escape_html($error) ?></p><?php endif; ?>
  <section class="card" style="padding:20px;margin:20px 0">
    <h2>Tambah Tenant</h2>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= escape_html($csrf) ?>">
      <input type="hidden" name="action" value="create_tenant">
      <label>Domain tenant<br><input name="domain" required placeholder="couple-a.com"></label><br>
      <label>Username Tenant Admin<br><input name="tenant_username" minlength="3" maxlength="64" placeholder="Kosongkan untuk dibuat dari domain"></label><br>
      <label>Password Tenant Admin <small>(minimal 12 karakter; kosongkan untuk password acak)</small><br><input type="password" name="tenant_password" minlength="12" maxlength="128" autocomplete="new-password"></label><br>
      <label>Password Super Admin saat ini<br><input type="password" name="current_password" autocomplete="current-password" required></label><br>
      <button type="submit">Buat Tenant</button>
    </form>
  </section>
  <section class="card" style="padding:20px">
    <h2>Tenant Terdaftar</h2>
    <table><thead><tr><th>Domain</th><th>Status</th><th>Tenant Admin / Password</th><th>User</th><th>RSVP</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach ($tenants as $tenant): ?>
      <tr>
        <td><?= escape_html($tenant['domain']) ?></td><td><?= escape_html($tenant['status']) ?></td><td><?php if (empty($tenant['tenant_admins'])): ?>—<?php else: ?><?php foreach ($tenant['tenant_admins'] as $tenantAdmin): ?><div style="margin-bottom:12px"><strong><?= escape_html((string)$tenantAdmin['username']) ?></strong><br><small>Password tersimpan aman; tidak ditampilkan di daftar.</small><form method="post" style="margin-top:4px"><input type="hidden" name="csrf_token" value="<?= escape_html($csrf) ?>"><input type="hidden" name="action" value="reset_tenant_password"><input type="hidden" name="tenant_id" value="<?= (int)$tenant['id'] ?>"><input type="hidden" name="user_id" value="<?= (int)$tenantAdmin['id'] ?>"><input type="password" name="new_password" minlength="12" maxlength="128" autocomplete="new-password" placeholder="Kosongkan = acak"><input type="password" name="current_password" autocomplete="current-password" placeholder="Password Super Admin" required><button type="submit">Reset Password</button></form></div><?php endforeach; ?><?php endif; ?></td><td><?= (int)$tenant['user_count'] ?></td><td><?= (int)$tenant['guest_count'] ?></td>
        <td><form method="post"><input type="hidden" name="csrf_token" value="<?= escape_html($csrf) ?>"><input type="hidden" name="action" value="set_status"><input type="hidden" name="tenant_id" value="<?= (int)$tenant['id'] ?>"><input type="hidden" name="status" value="<?= $tenant['status'] === 'active' ? 'suspended' : 'active' ?>"><input type="password" name="current_password" autocomplete="current-password" placeholder="Password saat ini" required><button type="submit"><?= $tenant['status'] === 'active' ? 'Tangguhkan' : 'Aktifkan' ?></button></form></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  </section>
</main>
</body>
</html>
