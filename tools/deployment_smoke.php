<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$runtime = sys_get_temp_dir() . '/wedding-cms-runtime-' . bin2hex(random_bytes(5));
mkdir($runtime, 0775, true);
register_shutdown_function(static function () use ($runtime): void {
    if (is_dir($runtime)) {
        exec('rm -rf -- ' . escapeshellarg($runtime));
    }
});

putenv('UNDANGAN_DB_PATH=' . $runtime . '/database.sqlite');
require_once $root . '/config.php';

function assert_true(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$defaults = config_defaults();
assert_true(($defaults['media']['cover'] ?? null) === '', 'cover default must be empty');
assert_true(($defaults['media']['music'] ?? null) === '', 'music default must be empty');
assert_true(($defaults['site']['open_graph_image'] ?? null) === '', 'Open Graph default must be empty');
assert_true(public_path('') === 'data:,', 'empty public path must be request-free');
assert_true(!defined('CONFIG_FILE') && !defined('GUEST_LINKS_FILE') && !defined('EVENT_ICS_FILE'), 'global runtime file constants are removed');
assert_true(is_file($root . '/deploy/migrate.php'), 'standalone database migration exists');
$loaded = load_config();
assert_true(($loaded['media']['cover'] ?? null) === '', 'loaded cover default must remain empty');
assert_true(($loaded['media']['music'] ?? null) === '', 'loaded music default must remain empty');

$contract = $root . '/deploy/runtime-directories.sh';
assert_true(is_file($contract), 'shared runtime directory contract exists');
assert_true(is_file($root . '/tools/deployment_backup_restore_smoke.sh'), 'backup/restore/update fixture smoke test exists');
$bootstrapRoot = $runtime . '/app';
$command = 'sh -c ' . escapeshellarg('. ' . escapeshellarg($contract) . '; ensure_runtime_directories ' . escapeshellarg($bootstrapRoot));
exec($command, $output, $exitCode);
assert_true($exitCode === 0, 'shared runtime directory contract executes successfully');
assert_true(is_dir($bootstrapRoot . '/uploads'), 'shared uploads root created');
$requiredBootstrapReferences = [
    $root . '/deploy/install.sh',
    $root . '/deploy/update.sh',
    $root . '/deploy/health-check.sh',
    $root . '/docker/entrypoint.sh',
];
foreach ($requiredBootstrapReferences as $script) {
    $source = file_get_contents($script);
    assert_true(is_string($source) && str_contains($source, 'runtime-directories.sh'), 'deployment path references shared runtime contract: ' . basename($script));
}
$installer = (string) file_get_contents($root . '/deploy/install.sh');
assert_true(str_contains($installer, 'apache2 apache2-utils php-fpm'), 'native installer installs Apache and PHP-FPM foundation packages');
assert_true(str_contains($installer, 'composer install --no-dev'), 'native installer installs Composer dependencies from the lock file');
assert_true(str_contains($installer, 'start_php_fpm_and_detect_socket'), 'native installer detects an active PHP-FPM socket');
assert_true(str_contains($installer, 'apache2ctl configtest'), 'native installer gates activation on Apache configtest');
assert_true(str_contains($installer, 'APACHE_WEBDAV_ENABLE'), 'native installer keeps WebDAV explicitly operator-controlled');
assert_true(str_contains($installer, 'ffmpeg'), 'native installer installs FFmpeg for Love Story video processing');
assert_true(!str_contains($installer, 'nginx'), 'native installer does not introduce an Nginx deployment path');
$updater = (string) file_get_contents($root . '/deploy/update.sh');
assert_true(str_contains($updater, 'multytenant_cms_enggine.git'), 'updater defaults to the multi-tenant repository');

$dockerfile = (string) file_get_contents($root . '/Dockerfile');
assert_true(str_contains($dockerfile, 'HEALTHCHECK'), 'Dockerfile declares an HTTP healthcheck');
assert_true(str_contains($dockerfile, 'http://127.0.0.1/'), 'Dockerfile healthcheck targets the local frontend');
assert_true(str_contains($dockerfile, 'already builds pdo_sqlite'), 'Dockerfile documents SQLite extensions supplied by the base image');
assert_true(!str_contains($dockerfile, 'docker-php-ext-install pdo_sqlite'), 'Dockerfile does not recompile PDO SQLite');
assert_true(!str_contains($dockerfile, 'docker-php-ext-install sqlite3'), 'Dockerfile does not recompile SQLite3');
assert_true(str_contains($dockerfile, 'ffmpeg'), 'Dockerfile installs FFmpeg for Love Story video processing');
$healthCheck = (string) file_get_contents($root . '/deploy/health-check.sh');
assert_true(str_contains($healthCheck, 'ffprobe'), 'health-check verifies FFprobe availability');
$compose = (string) file_get_contents($root . '/docker-compose.yml');
assert_true(str_contains($compose, 'healthcheck:'), 'Compose declares a service healthcheck');
assert_true(str_contains($compose, 'wedding_backups:'), 'Compose persists backup artifacts');
assert_true(str_contains($compose, 'wedding_webdav:'), 'Compose persists WebDAV data');
$entrypoint = (string) file_get_contents($root . '/docker/entrypoint.sh');
assert_true(str_contains($entrypoint, 'escape_sed_replacement'), 'Docker entrypoint escapes environment substitutions');
assert_true(str_contains($entrypoint, 'chmod 600 "${APP_DIR}/.env"'), 'Docker entrypoint protects .env permissions');
$dockerignore = (string) file_get_contents($root . '/.dockerignore');
assert_true(str_contains($dockerignore, '!.env.example'), 'Docker build keeps the environment template for bootstrap');
assert_true(str_contains($dockerignore, '*.sqlite'), 'Docker build excludes local SQLite runtime data');

foreach (glob($bootstrapRoot . '/uploads/*') ?: [] as $path) {
    if (is_dir($path)) @rmdir($path);
}
@rmdir($bootstrapRoot . '/uploads');
@rmdir($bootstrapRoot . '/backups');
@rmdir($bootstrapRoot . '/webdav');
@rmdir($bootstrapRoot);

if (is_dir($bootstrapRoot)) {
    throw new RuntimeException('deployment smoke fixture cleanup failed: ' . $bootstrapRoot);
}

echo "PASS: deployment defaults, runtime data paths, asset bootstrap, and optional media guards\n";
