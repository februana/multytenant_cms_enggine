<?php
// Render-only runtime paths. The application keeps its existing defaults for
// Ubuntu/Lightsail; Docker injects these constants before config.php loads.

$renderDataDir = getenv('UNDANGAN_DATA_DIR') ?: '/var/data';

if (!defined('UPLOADS_DIR')) {
    define('UPLOADS_DIR', rtrim($renderDataDir, '/') . '/uploads');
}
if (!defined('CONFIG_FILE')) {
    define('CONFIG_FILE', rtrim($renderDataDir, '/') . '/config.json');
}
if (!defined('CUSTOM_CSS_FILE')) {
    define('CUSTOM_CSS_FILE', rtrim($renderDataDir, '/') . '/custom.css');
}
if (!defined('GUEST_LINKS_FILE')) {
    define('GUEST_LINKS_FILE', rtrim($renderDataDir, '/') . '/guest-links.json');
}
if (!defined('DB_PATH')) {
    define('DB_PATH', getenv('UNDANGAN_DB_PATH') ?: (rtrim($renderDataDir, '/') . '/database.sqlite'));
}
