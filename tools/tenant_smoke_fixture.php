<?php
declare(strict_types=1);

/**
 * Bootstrap one isolated tenant for CLI smoke tests.
 * The fixture uses a temporary SQLite database but the application still writes
 * media under its canonical ROOT_DIR/uploads/tenant_<id> namespace so the real
 * tenant path-safety code is exercised.
 */
function tenant_smoke_bootstrap(string $prefix = 'tenant-smoke'): array {
    $token = preg_replace('/[^a-z0-9-]+/i', '-', strtolower($prefix . '-' . bin2hex(random_bytes(6))));
    $runtimeDb = sys_get_temp_dir() . '/' . $token . '.sqlite';
    $domain = $token . '.test';
    $tenantId = 800000 + random_int(1, 100000);

    putenv('UNDANGAN_DB_PATH=' . $runtimeDb);
    putenv('UNDANGAN_MAIN_DOMAIN=' . $domain);
    $_SERVER['HTTP_HOST'] = $domain;

    require_once dirname(__DIR__) . '/config.php';
    $db = new SQLite3($runtimeDb);
    $migration = file_get_contents(dirname(__DIR__) . '/database/migrations/001_multi_tenant.sql');
    if (!is_string($migration) || !$db->exec($migration)) {
        throw new RuntimeException('Tenant smoke migration failed: ' . $db->lastErrorMsg());
    }
    $tenant = $db->prepare('INSERT INTO tenants (id, domain, status) VALUES (:id, :domain, \'active\')');
    $tenant->bindValue(':id', $tenantId, SQLITE3_INTEGER);
    $tenant->bindValue(':domain', $domain, SQLITE3_TEXT);
    if (!$tenant->execute()) {
        throw new RuntimeException('Tenant smoke tenant insert failed: ' . $db->lastErrorMsg());
    }
    $configJson = json_encode(config_defaults(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $config = $db->prepare('INSERT INTO tenant_configs (tenant_id, config_json) VALUES (:tenant_id, :config_json)');
    $config->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
    $config->bindValue(':config_json', is_string($configJson) ? $configJson : '{}', SQLITE3_TEXT);
    $config->execute();
    $db->close();

    $root = dirname(__DIR__) . '/uploads/tenant_' . $tenantId;
    register_shutdown_function(static function () use ($runtimeDb, $root): void {
        if (is_dir($root)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $item) {
                $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            }
            @rmdir($root);
        }
        @unlink($runtimeDb);
    });

    return ['db' => $runtimeDb, 'domain' => $domain, 'tenant_id' => $tenantId, 'root' => $root];
}
