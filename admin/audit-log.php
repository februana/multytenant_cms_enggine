<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/theme-helper.php';
init_session();
require_admin();
if (!is_super_admin()) {
    http_response_code(403);
    exit('Akses hanya untuk Super Admin.');
}

$tenantFilter = max(0, (int)($_GET['tenant_id'] ?? 0));
$actionFilter = trim((string)($_GET['action'] ?? ''));
$logs = [];
$error = '';
try {
    $db = tenant_database(true);
    $where = [];
    $params = [];
    if ($tenantFilter > 0) {
        $where[] = 'a.target_tenant_id = :tenant_id';
        $params[':tenant_id'] = $tenantFilter;
    }
    if ($actionFilter !== '') {
        $where[] = 'a.action = :action';
        $params[':action'] = $actionFilter;
    }
    $sql = 'SELECT a.id, a.actor_user_id, a.actor_role, a.actor_tenant_id, a.target_tenant_id, a.action, a.metadata_json, a.ip_address, a.created_at, t.domain AS target_domain FROM audit_logs a LEFT JOIN tenants t ON t.id = a.target_tenant_id';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY a.id DESC LIMIT 200';
    $stmt = $db->prepare($sql);
    if (!$stmt) throw new RuntimeException('Log keamanan belum tersedia. Jalankan migrasi database terbaru.');
    foreach ($params as $key => $value) $stmt->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) $logs[] = $row;
    $db->close();
} catch (Throwable $e) {
    $error = 'Log keamanan belum dapat dibaca. Jalankan migrasi terbaru jika tabel belum tersedia.';
    error_log('Audit log page failed: ' . $e->getMessage());
}

$csrf = get_csrf_token();
?><!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Log Keamanan</title><link rel="stylesheet" href="/admin/style.css"></head>
<body>
<main class="container" style="max-width:1200px;margin:32px auto;padding:0 16px">
  <h1>Log Keamanan</h1>
  <p><a href="/admin/super-admin.php">Kembali ke Pengelola Tenant</a> · <a href="/admin/profile.php">Profil Super Admin</a> · <a href="/admin">CMS Tenant</a></p>
  <p>Halaman ini menampilkan maksimal 200 operasi istimewa terbaru. Password dan secret tidak disimpan di metadata log.</p>
  <?php if ($error !== ''): ?><p class="error"><?= escape_html($error) ?></p><?php endif; ?>
  <section class="card" style="padding:20px;margin:20px 0">
    <form method="get" class="form-grid">
      <div class="form-row"><label>ID Tenant<br><input type="number" min="0" name="tenant_id" value="<?= $tenantFilter > 0 ? (int)$tenantFilter : '' ?>"></label></div>
      <div class="form-row"><label>Jenis Operasi<br><input name="action" value="<?= escape_html($actionFilter) ?>" placeholder="contoh: tenant_status_changed"></label></div>
      <div class="form-row" style="align-self:end"><button type="submit">Filter</button> <a class="button" href="/admin/audit-log.php">Reset</a></div>
    </form>
  </section>
  <section class="card" style="padding:20px;overflow:auto">
    <table><thead><tr><th>Waktu UTC</th><th>Operasi</th><th>Pelaku</th><th>Target Tenant</th><th>IP</th><th>Metadata</th></tr></thead><tbody>
    <?php if (!$logs): ?><tr><td colspan="6">Belum ada log yang cocok.</td></tr><?php endif; ?>
    <?php foreach ($logs as $log): ?>
      <tr>
        <td><?= escape_html((string)$log['created_at']) ?></td>
        <td><code><?= escape_html((string)$log['action']) ?></code></td>
        <td><?= escape_html((string)$log['actor_role']) ?> #<?= (int)$log['actor_user_id'] ?></td>
        <td><?= $log['target_tenant_id'] === null ? '—' : escape_html((string)($log['target_domain'] ?? 'Tenant #' . $log['target_tenant_id'])) ?></td>
        <td><?= escape_html((string)$log['ip_address']) ?></td>
        <td><code><?= escape_html((string)$log['metadata_json']) ?></code></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  </section>
</main>
</body>
</html>
