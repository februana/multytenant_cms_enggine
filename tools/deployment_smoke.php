<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$runtime = sys_get_temp_dir() . '/wedding-cms-runtime-' . bin2hex(random_bytes(5));
mkdir($runtime, 0775, true);
register_shutdown_function(static function () use ($runtime): void {
    foreach (glob($runtime . '/*') ?: [] as $file) {
        if (is_file($file) || is_link($file)) @unlink($file);
    }
    @rmdir($runtime);
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

echo "PASS: deployment defaults, runtime data paths, and optional media guards\n";
