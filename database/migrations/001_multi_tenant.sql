-- Pure multi-tenant shared-schema migration for SQLite.
-- Runtime bootstrap in config.php additionally rebuilds legacy tamu when SQLite
-- cannot add a foreign key with ALTER TABLE.

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS tenants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    domain TEXT NOT NULL UNIQUE COLLATE NOCASE,
    status TEXT NOT NULL DEFAULT 'active'
        CHECK (status IN ('active', 'suspended')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER NULL REFERENCES tenants(id) ON DELETE CASCADE,
    username TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    visible_password TEXT NOT NULL DEFAULT '',
    role TEXT NOT NULL
        CHECK (role IN ('super_admin', 'tenant_admin')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (tenant_id, username)
);
CREATE INDEX IF NOT EXISTS idx_users_tenant_id ON users(tenant_id);

CREATE TABLE IF NOT EXISTS tenant_configs (
    tenant_id INTEGER PRIMARY KEY REFERENCES tenants(id) ON DELETE CASCADE,
    config_json TEXT NOT NULL,
    custom_css TEXT NOT NULL DEFAULT '',
    event_ics TEXT NOT NULL DEFAULT '',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS guest_links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    guest_name TEXT NOT NULL,
    invitation_url TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_guest_links_tenant_id ON guest_links(tenant_id);

-- Existing tamu data must be assigned to the main tenant before this final form
-- is created. The PHP bootstrap performs that assignment transactionally.
CREATE TABLE IF NOT EXISTS tamu (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    nama TEXT NOT NULL,
    status TEXT NOT NULL,
    ucapan TEXT,
    visible INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_tamu_tenant_id ON tamu(tenant_id);

-- Main-domain seed examples. Replace values before executing manually.
-- INSERT INTO tenants (domain, status) VALUES ('example.com', 'active');
-- INSERT INTO users (tenant_id, username, password_hash, role)
-- VALUES (NULL, 'admin', '<PASSWORD_HASH>', 'super_admin');
