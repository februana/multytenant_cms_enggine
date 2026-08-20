<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function migration_table_has_column(SQLite3 $db, string $table, string $column): bool {
    $stmt = $db->prepare("SELECT 1 FROM pragma_table_info(:table) WHERE name = :column LIMIT 1");
    $stmt->bindValue(':table', $table, SQLITE3_TEXT);
    $stmt->bindValue(':column', $column, SQLITE3_TEXT);
    return (bool)$stmt->execute()->fetchArray(SQLITE3_NUM);
}

function migration_rebuild_tamu(SQLite3 $db, int $defaultTenantId): void {
    if (!migration_table_has_column($db, 'tamu', 'tenant_id')) {
        $db->exec('ALTER TABLE tamu ADD COLUMN tenant_id INTEGER');
    }
    if (!migration_table_has_column($db, 'tamu', 'visible')) {
        $db->exec('ALTER TABLE tamu ADD COLUMN visible INTEGER DEFAULT 1');
    }
    $db->exec('UPDATE tamu SET tenant_id = ' . (int)$defaultTenantId . ' WHERE tenant_id IS NULL');
    $foreignKeys = [];
    $result = $db->query('PRAGMA foreign_key_list(tamu)');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) $foreignKeys[] = $row;
    foreach ($foreignKeys as $row) {
        if (($row['from'] ?? '') === 'tenant_id' && ($row['table'] ?? '') === 'tenants') {
            $db->exec('CREATE INDEX IF NOT EXISTS idx_tamu_tenant_id ON tamu(tenant_id)');
            return;
        }
    }
    $db->exec('CREATE TABLE tamu_new (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL REFERENCES tenants(id) ON DELETE CASCADE, nama TEXT NOT NULL, status TEXT NOT NULL, ucapan TEXT, visible INTEGER DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
    $db->exec('INSERT INTO tamu_new (id, tenant_id, nama, status, ucapan, visible, created_at) SELECT id, COALESCE(tenant_id, ' . (int)$defaultTenantId . '), nama, status, ucapan, COALESCE(visible, 1), COALESCE(created_at, CURRENT_TIMESTAMP) FROM tamu');
    $db->exec('DROP TABLE tamu');
    $db->exec('ALTER TABLE tamu_new RENAME TO tamu');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_tamu_tenant_id ON tamu(tenant_id)');
}

function migration_build_config(): array {
    $defaults = config_defaults();
    $legacyPath = ROOT_DIR . '/config.json';
    if (!is_readable($legacyPath)) return $defaults;
    $decoded = json_decode((string)file_get_contents($legacyPath), true);
    return is_array($decoded) ? array_replace_recursive($defaults, $decoded) : $defaults;
}

function migration_read_legacy_text(string $path): string {
    return is_readable($path) ? (string)file_get_contents($path) : '';
}

function migration_upgrade_visible_passwords(SQLite3 $db): void {
    $result = $db->query("SELECT id, visible_password FROM users WHERE visible_password <> ''");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $encoded = trim((string)($row['visible_password'] ?? ''));
        if ($encoded === '' || str_starts_with($encoded, 'gcm:')) continue;
        $plain = decrypt_visible_password($encoded);
        if ($plain === '') {
            throw new RuntimeException('Unable to decrypt a legacy visible password; verify UNDANGAN_PASSWORD_KEY before migrating.');
        }
        $upgraded = encrypt_visible_password($plain);
        if ($upgraded === '' || !str_starts_with($upgraded, 'gcm:')) {
            throw new RuntimeException('Unable to upgrade a legacy visible password to AES-256-GCM.');
        }
        $update = $db->prepare('UPDATE users SET visible_password = :visible_password WHERE id = :id');
        $update->bindValue(':visible_password', $upgraded, SQLITE3_TEXT);
        $update->bindValue(':id', (int)$row['id'], SQLITE3_INTEGER);
        if (!$update->execute()) throw new RuntimeException('Unable to persist upgraded visible password.');
    }
}

try {
    $db = new SQLite3(DB_PATH, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    $db->busyTimeout(10000);
    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec('BEGIN IMMEDIATE');

    $db->exec("CREATE TABLE IF NOT EXISTS tenants (id INTEGER PRIMARY KEY AUTOINCREMENT, domain TEXT NOT NULL UNIQUE COLLATE NOCASE, status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'suspended')), created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NULL REFERENCES tenants(id) ON DELETE CASCADE, username TEXT NOT NULL, password_hash TEXT NOT NULL, visible_password TEXT NOT NULL DEFAULT '', role TEXT NOT NULL CHECK (role IN ('super_admin', 'tenant_admin')), created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE (tenant_id, username))");
    if (!migration_table_has_column($db, 'users', 'visible_password')) $db->exec("ALTER TABLE users ADD COLUMN visible_password TEXT NOT NULL DEFAULT ''");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_users_tenant_id ON users(tenant_id)');
    $db->exec("CREATE TABLE IF NOT EXISTS tenant_configs (tenant_id INTEGER PRIMARY KEY REFERENCES tenants(id) ON DELETE CASCADE, config_json TEXT NOT NULL, custom_css TEXT NOT NULL DEFAULT '', event_ics TEXT NOT NULL DEFAULT '', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS guest_links (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL REFERENCES tenants(id) ON DELETE CASCADE, guest_name TEXT NOT NULL, invitation_url TEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_guest_links_tenant_id ON guest_links(tenant_id)');

    $hasTamu = (bool)$db->querySingle("SELECT 1 FROM sqlite_master WHERE type='table' AND name='tamu'");
    if (!$hasTamu) $db->exec('CREATE TABLE tamu (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL REFERENCES tenants(id) ON DELETE CASCADE, nama TEXT NOT NULL, status TEXT NOT NULL, ucapan TEXT, visible INTEGER DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');

    $mainDomain = normalize_tenant_domain((string)(getenv('UNDANGAN_MAIN_DOMAIN') ?: getenv('MAIN_DOMAIN') ?: ''));
    if (!is_valid_tenant_domain($mainDomain)) {
        throw new RuntimeException('UNDANGAN_MAIN_DOMAIN must be a valid FQDN before migrating legacy data.');
    }
    $mainTenantStmt = $db->prepare('INSERT OR IGNORE INTO tenants (domain, status) VALUES (:domain, \'active\')');
    $mainTenantStmt->bindValue(':domain', $mainDomain, SQLITE3_TEXT);
    $mainTenantStmt->execute();
    $mainTenantLookup = $db->prepare('SELECT id FROM tenants WHERE domain = :domain LIMIT 1');
    $mainTenantLookup->bindValue(':domain', $mainDomain, SQLITE3_TEXT);
    $mainTenantRow = $mainTenantLookup->execute()->fetchArray(SQLITE3_ASSOC);
    $defaultTenantId = (int)($mainTenantRow['id'] ?? 0);
    if ($defaultTenantId < 1) throw new RuntimeException('Main Domain tenant could not be resolved.');
    if ($hasTamu) migration_rebuild_tamu($db, $defaultTenantId);
    else $db->exec('CREATE INDEX IF NOT EXISTS idx_tamu_tenant_id ON tamu(tenant_id)');

    $config = migration_build_config();
    $configJson = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($configJson === false) throw new RuntimeException('Unable to encode tenant config.');
    $css = migration_read_legacy_text(ROOT_DIR . '/custom.css');
    $ics = migration_read_legacy_text(ROOT_DIR . '/event.ics');
    $tenantRows = $db->query('SELECT id FROM tenants ORDER BY id');
    while ($tenant = $tenantRows->fetchArray(SQLITE3_ASSOC)) {
        $seed = $db->prepare('INSERT OR IGNORE INTO tenant_configs (tenant_id, config_json, custom_css, event_ics) VALUES (:tenant_id, :config_json, :custom_css, :event_ics)');
        $seed->bindValue(':tenant_id', (int)$tenant['id'], SQLITE3_INTEGER);
        $seed->bindValue(':config_json', $configJson, SQLITE3_TEXT);
        $seed->bindValue(':custom_css', $css, SQLITE3_TEXT);
        $seed->bindValue(':event_ics', $ics !== '' ? $ics : build_event_ics($config), SQLITE3_TEXT);
        $seed->execute();
    }

    $linksPath = ROOT_DIR . '/guest-links.json';
    if ((int)$db->querySingle('SELECT COUNT(*) FROM guest_links WHERE tenant_id = ' . $defaultTenantId) === 0 && is_readable($linksPath)) {
        $links = json_decode((string)file_get_contents($linksPath), true);
        foreach (is_array($links) ? $links : [] as $link) {
            if (!is_array($link)) continue;
            $insert = $db->prepare('INSERT INTO guest_links (tenant_id, guest_name, invitation_url, created_at) VALUES (:tenant_id, :guest_name, :invitation_url, :created_at)');
            $insert->bindValue(':tenant_id', $defaultTenantId, SQLITE3_INTEGER);
            $insert->bindValue(':guest_name', trim((string)($link['guest_name'] ?? '')), SQLITE3_TEXT);
            $insert->bindValue(':invitation_url', trim((string)($link['invitation_url'] ?? '')), SQLITE3_TEXT);
            $insert->bindValue(':created_at', trim((string)($link['created_at'] ?? gmdate('c'))), SQLITE3_TEXT);
            $insert->execute();
        }
    }

    $superCount = (int)$db->querySingle("SELECT COUNT(*) FROM users WHERE role = 'super_admin' AND tenant_id IS NULL");
    if ($superCount === 0) {
        $legacy = $config['admin'] ?? [];
        $username = trim((string)(getenv('ADMIN_USER') ?: ($legacy['username'] ?? 'admin'))) ?: 'admin';
        $plain = (string)(getenv('ADMIN_PASS') ?: '');
        $hash = trim((string)($legacy['password_hash'] ?? ''));
        if ($hash === '' && $plain !== '') $hash = password_hash($plain, PASSWORD_DEFAULT);
        if ($hash === '') throw new RuntimeException('ADMIN_PASS or legacy admin.password_hash is required to seed Super Admin.');
        $user = $db->prepare("INSERT INTO users (tenant_id, username, password_hash, visible_password, role) VALUES (NULL, :username, :hash, :visible, 'super_admin')");
        $user->bindValue(':username', $username, SQLITE3_TEXT);
        $user->bindValue(':hash', $hash, SQLITE3_TEXT);
        $visiblePassword = $plain !== '' ? encrypt_visible_password($plain) : '';
        if ($plain !== '' && ($visiblePassword === '' || !str_starts_with($visiblePassword, 'gcm:'))) throw new RuntimeException('Unable to encrypt Super Admin password with AES-256-GCM.');
        $user->bindValue(':visible', $visiblePassword, SQLITE3_TEXT);
        if (!$user->execute()) throw new RuntimeException('Unable to seed Super Admin.');
    }

    migration_upgrade_visible_passwords($db);
    $db->exec('COMMIT');
    $db->close();
    echo "Database migration completed.\n";
} catch (Throwable $e) {
    if (isset($db) && $db instanceof SQLite3) @$db->exec('ROLLBACK');
    fwrite(STDERR, 'Database migration failed: ' . $e->getMessage() . "\n");
    exit(1);
}
