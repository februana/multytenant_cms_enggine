<?php
// app/config.php - copied and adapted from original config.php
// This config prefers an environment-provided DB path or a repository-local storage directory.
// It is safe for repository layout; sensitive .env files should live outside webroot (e.g. /var/www/private/.env).

// Minimal runtime hardening
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

function load_dotenv_file(string $path): void {
    if (!is_readable($path)) return;
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $v = trim($v, '"\'');
        if ($k === '') continue;
        if (getenv($k) === false) putenv("{$k}={$v}");
        if (!array_key_exists($k, $_ENV)) $_ENV[$k] = $v;
    }
}

// Load common locations but do not assume presence
load_dotenv_file('/var/www/private/.env');
load_dotenv_file(__DIR__ . '/../.env');

// WhatsApp Settings
define('WHATSAPP_NUMBER', getenv('WHATSAPP_NUMBER') ?: '6285162909164');
define('WHATSAPP_MESSAGE', getenv('WHATSAPP_MESSAGE') ?: 'Assalamu\'alaikum Andi & Februana, saya ingin mengonfirmasi kehadiran untuk acara pernikahan.');

// Admin Settings
define('ADMIN_USER', getenv('ADMIN_USER') ?: 'admin');
$adminPass = getenv('ADMIN_PASS');
if ($adminPass === false || $adminPass === '') {
    error_log('ADMIN_PASS is not configured in environment');
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        echo 'Server configuration error.';
    }
    exit(1);
}
define('ADMIN_PASS', $adminPass);

// Security Settings
define('MAX_UPLOAD_SIZE', (int) (getenv('MAX_UPLOAD_SIZE') ?: 5 * 1024 * 1024)); // 5MB default
define('SESSION_TIMEOUT', (int) (getenv('SESSION_TIMEOUT') ?: 3600));
define('ALLOWED_IMAGE_TYPES', array_map('strtolower', (array) (getenv('ALLOWED_IMAGE_TYPES') ? explode(',', getenv('ALLOWED_IMAGE_TYPES')) : ['jpg','jpeg','png','gif','webp'])));

// Database path resolution (safe defaults for repo layout)
// Priority:
// 1) UNDANGAN_DB_PATH env var
// 2) /var/www/private/database.sqlite (recommended, outside webroot)
// 3) repo storage/data/database.sqlite (not committed to git)
if (getenv('UNDANGAN_DB_PATH')) {
    $dbPath = getenv('UNDANGAN_DB_PATH');
} elseif (is_readable('/var/www/private/database.sqlite')) {
    $dbPath = '/var/www/private/database.sqlite';
} else {
    $dbPath = __DIR__ . '/storage/data/database.sqlite';
}
if (!defined('DB_PATH')) define('DB_PATH', $dbPath);

// Security headers helper
function send_security_header(string $name, string $value): void {
    $exists = false;
    foreach (headers_list() as $h) {
        if (stripos($h, $name . ':') === 0) { $exists = true; break; }
    }
    if (!$exists) header("{$name}: {$value}");
}

send_security_header('X-Content-Type-Options', 'nosniff');
send_security_header('X-Frame-Options', 'SAMEORIGIN');
send_security_header('Referrer-Policy', 'strict-origin-when-cross-origin');
send_security_header('Permissions-Policy', 'microphone=(), camera=(), geolocation=()');

// Minimal exception handler
set_exception_handler(function ($e) {
    error_log('Unhandled exception: ' . $e->getMessage());
    http_response_code(500);
    if (php_sapi_name() !== 'cli') {
        $headers = headers_list();
        $isJson = false;
        foreach ($headers as $h) { if (stripos($h, 'Content-Type:') === 0 && stripos($h, 'application/json') !== false) $isJson = true; }
        if ($isJson) echo json_encode(['success' => false, 'message' => 'Internal server error']);
        else echo 'Internal server error';
    }
    exit;
});

?>
