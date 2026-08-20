<?php
declare(strict_types=1);

$runtime = sys_get_temp_dir() . '/multytenant-auth-' . bin2hex(random_bytes(6));
@mkdir($runtime, 0700, true);
putenv('UNDANGAN_DB_PATH=' . $runtime . '/database.sqlite');
putenv('UNDANGAN_MAIN_DOMAIN=tenant-a.example.test');
putenv('UNDANGAN_PASSWORD_KEY=auth-smoke-key');
register_shutdown_function(static function () use ($runtime): void {
    if (is_dir($runtime)) exec('rm -rf -- ' . escapeshellarg($runtime));
});

require_once dirname(__DIR__) . '/config.php';

function smoke_assert(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this smoke test from CLI.\n");
    exit(2);
}

$_SERVER['HTTP_HOST'] = 'tenant-a.example.test';
init_session();
$db = tenant_database(false);
$db->exec("CREATE TABLE IF NOT EXISTS tenants (id INTEGER PRIMARY KEY AUTOINCREMENT, domain TEXT NOT NULL UNIQUE COLLATE NOCASE, status TEXT NOT NULL DEFAULT 'active', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NULL, username TEXT NOT NULL, password_hash TEXT NOT NULL, visible_password TEXT NOT NULL DEFAULT '', role TEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
$db->exec("CREATE TABLE IF NOT EXISTS audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, actor_user_id INTEGER NOT NULL, actor_role TEXT NOT NULL, actor_tenant_id INTEGER NULL, target_tenant_id INTEGER NULL, action TEXT NOT NULL, metadata_json TEXT NOT NULL DEFAULT '{}', ip_address TEXT NOT NULL DEFAULT '', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
$db->exec("INSERT INTO tenants (domain, status) VALUES ('tenant-a.example.test', 'active')");
$tenantId = (int)$db->lastInsertRowID();
$hash = password_hash('TenantPassword123!', PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO users (tenant_id, username, password_hash, role) VALUES (:tenant_id, 'tenant-admin', :hash, 'tenant_admin')");
$stmt->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
$stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
$stmt->execute();
$userId = (int)$db->lastInsertRowID();
$db->close();

$_SESSION = [
    'admin' => true,
    'user_id' => $userId,
    'username' => 'tenant-admin',
    'role' => 'tenant_admin',
    'tenant_id' => $tenantId,
    'last_activity' => time(),
    'csrf_token' => bin2hex(random_bytes(32)),
];
smoke_assert(session_admin_is_valid(), 'tenant admin session valid for its active tenant');
smoke_assert(admin_action_is_authorized('save_wedding'), 'known tenant mutation is allowed');
smoke_assert(!admin_action_is_authorized('delete_all_tenants'), 'unknown mutation is denied');
smoke_assert(verify_current_admin_password('TenantPassword123!'), 'current password verification succeeds');
smoke_assert(!verify_current_admin_password('wrong-password'), 'wrong current password is rejected');

$db = tenant_database(false);
$change = $db->prepare("UPDATE users SET role = 'super_admin', tenant_id = NULL WHERE id = :id");
$change->bindValue(':id', $userId, SQLITE3_INTEGER);
$change->execute();
$db->close();
smoke_assert(!session_admin_is_valid(), 'session is invalidated when database role no longer matches session');

$_SESSION['role'] = 'super_admin';
$_SESSION['tenant_id'] = null;
smoke_assert(session_admin_is_valid(), 'super admin session is valid only when database identity matches');
audit_log('auth_smoke_event', $tenantId, ['test' => true]);
$db = tenant_database(true);
$logged = (int)$db->querySingle("SELECT COUNT(*) FROM audit_logs WHERE action = 'auth_smoke_event'");
$db->close();
smoke_assert($logged === 1, 'audit event is stored with target tenant');

echo "AUTH SMOKE PASSED\n";
