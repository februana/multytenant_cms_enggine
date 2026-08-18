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

putenv('UNDANGAN_DATA_DIR=' . $runtime);
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
assert_true(CONFIG_FILE === $runtime . '/config.json', 'config path must follow runtime data directory');
assert_true(GUEST_LINKS_FILE === $runtime . '/guest-links.json', 'guest-link path must follow runtime data directory');
assert_true(EVENT_ICS_FILE === $runtime . '/event.ics', 'ICS path must follow runtime data directory');

file_put_contents(CONFIG_FILE, json_encode($defaults, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
$loaded = load_config();
assert_true(($loaded['media']['cover'] ?? null) === '', 'loaded cover default must remain empty');
assert_true(($loaded['media']['music'] ?? null) === '', 'loaded music default must remain empty');

$contract = $root . '/deploy/runtime-directories.sh';
assert_true(is_file($contract), 'shared runtime directory contract exists');
$bootstrapRoot = $runtime . '/app';
$command = 'sh -c ' . escapeshellarg('. ' . escapeshellarg($contract) . '; ensure_runtime_directories ' . escapeshellarg($bootstrapRoot));
exec($command, $output, $exitCode);
assert_true($exitCode === 0, 'shared runtime directory contract executes successfully');
foreach (['cover', 'music', 'gallery', 'background', 'love-story', 'theme-assets'] as $directory) {
    assert_true(is_dir($bootstrapRoot . '/uploads/' . $directory), 'runtime directory created: uploads/' . $directory);
}
foreach (['dewankl', 'elix', 'rainier', 'archak', 'parang', 'pawiwahan', 'custom'] as $preset) {
    assert_true(is_dir($bootstrapRoot . '/uploads/theme-assets/' . $preset), 'preset Theme Assets directory created: ' . $preset);
}
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

foreach (glob($bootstrapRoot . '/uploads/theme-assets/*') ?: [] as $path) {
    if (is_dir($path)) @rmdir($path);
}
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
