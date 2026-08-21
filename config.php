<?php
// Basic runtime hardening: do not show PHP errors to users
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

// Load optional environment files (first /var/www/private/.env, then local .env)
function load_dotenv_file(string $path): void {
    if (!is_readable($path)) return;
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $v = trim($v, "\"'");
        if ($k === '') continue;
        if (getenv($k) === false) putenv("{$k}={$v}");
        if (!array_key_exists($k, $_ENV)) $_ENV[$k] = $v;
    }
}

load_dotenv_file('/var/www/private/.env');
load_dotenv_file(__DIR__ . '/.env');

// Safe file locations
if (!defined('ROOT_DIR')) define('ROOT_DIR', __DIR__);
if (!defined('UPLOADS_DIR')) define('UPLOADS_DIR', ROOT_DIR . '/uploads');
if (!defined('UPLOADS_COVER_DIR')) define('UPLOADS_COVER_DIR', UPLOADS_DIR . '/cover');
if (!defined('UPLOADS_MUSIC_DIR')) define('UPLOADS_MUSIC_DIR', UPLOADS_DIR . '/music');
if (!defined('UPLOADS_GALLERY_DIR')) define('UPLOADS_GALLERY_DIR', UPLOADS_DIR . '/gallery');
if (!defined('UPLOADS_BACKGROUND_DIR')) define('UPLOADS_BACKGROUND_DIR', UPLOADS_DIR . '/background');
if (!defined('UPLOADS_LOVE_STORY_DIR')) define('UPLOADS_LOVE_STORY_DIR', UPLOADS_DIR . '/love-story');
if (!defined('UPLOADS_THEME_ASSETS_DIR')) define('UPLOADS_THEME_ASSETS_DIR', UPLOADS_DIR . '/theme-assets');

// Security defaults
if (!defined('MAX_UPLOAD_SIZE')) define('MAX_UPLOAD_SIZE', (int) (getenv('MAX_UPLOAD_SIZE') ?: 10 * 1024 * 1024));
if (!defined('MAX_MUSIC_UPLOAD_SIZE')) define('MAX_MUSIC_UPLOAD_SIZE', (int) (getenv('MAX_MUSIC_UPLOAD_SIZE') ?: 50 * 1024 * 1024));
if (!defined('MAX_VIDEO_UPLOAD_SIZE')) define('MAX_VIDEO_UPLOAD_SIZE', (int) (getenv('MAX_VIDEO_UPLOAD_SIZE') ?: 512 * 1024 * 1024));
if (!defined('WEBP_QUALITY')) define('WEBP_QUALITY', max(60, min(95, (int) (getenv('WEBP_QUALITY') ?: 82))));
if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', (int) (getenv('SESSION_TIMEOUT') ?: 3600));
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', array_map('strtolower', (array) (getenv('ALLOWED_IMAGE_TYPES') ? explode(',', getenv('ALLOWED_IMAGE_TYPES')) : ['jpg','jpeg','png','webp'])));
if (!defined('ALLOWED_AUDIO_TYPES')) define('ALLOWED_AUDIO_TYPES', ['mp3','ogg','wav']);
if (!defined('ALLOWED_VIDEO_TYPES')) define('ALLOWED_VIDEO_TYPES', ['mp4']);

// Database path resolution
if (getenv('UNDANGAN_DB_PATH')) {
    $dbPath = getenv('UNDANGAN_DB_PATH');
} elseif (is_readable('/var/www/private/database.sqlite')) {
    $dbPath = '/var/www/private/database.sqlite';
} else {
    $dbPath = ROOT_DIR . '/database.sqlite';
}
if (!defined('DB_PATH')) define('DB_PATH', $dbPath);

/**
 * Multi-tenant runtime helpers. All tenant selection is server-side and is
 * derived from the normalized Host header or the authenticated session.
 */
function normalize_tenant_domain(string $host): string {
    $host = strtolower(trim($host));
    if ($host === '') return '';
    if (str_starts_with($host, '[')) {
        $host = trim((string)preg_replace('/^\\[([^\\]]+)\\](?::\\d+)?$/', '$1', $host));
    } else {
        $host = preg_replace('/:\\d+$/', '', $host) ?? $host;
    }
    return rtrim($host, '.');
}

function request_host(): string {
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '' && PHP_SAPI === 'cli') {
        $host = (string)(getenv('UNDANGAN_MAIN_DOMAIN') ?: getenv('MAIN_DOMAIN') ?: 'localhost');
    }
    return normalize_tenant_domain($host);
}

function password_encryption_key(): string {
    $configured = trim((string)(getenv('UNDANGAN_PASSWORD_KEY') ?: ''));
    if ($configured === '') {
        error_log('UNDANGAN_PASSWORD_KEY is not configured; visible passwords cannot be decrypted.');
        return '';
    }
    return hash('sha256', $configured, true);
}

function encrypt_visible_password(string $password): string {
    $key = password_encryption_key();
    if ($key === '') return '';
    $cipher = 'aes-256-gcm';
    $ivLength = openssl_cipher_iv_length($cipher);
    $iv = random_bytes($ivLength);
    $tag = '';
    $ciphertext = openssl_encrypt($password, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($ciphertext === false || $tag === '') return '';
    // Versioned, parseable format: gcm:base64(iv)::base64(tag)::base64(ciphertext).
    return 'gcm:' . base64_encode($iv) . '::' . base64_encode($tag) . '::' . base64_encode($ciphertext);
}

function decrypt_visible_password(?string $encoded): string {
    $encoded = trim((string)$encoded);
    $key = password_encryption_key();
    if ($encoded === '' || $key === '') return '';
    if (str_starts_with($encoded, 'gcm:')) {
        $parts = explode('::', substr($encoded, 4), 3);
        if (count($parts) !== 3) return '';
        [$ivEncoded, $tagEncoded, $ciphertextEncoded] = $parts;
        $iv = base64_decode($ivEncoded, true);
        $tag = base64_decode($tagEncoded, true);
        $ciphertext = base64_decode($ciphertextEncoded, true);
        if ($iv === false || $tag === false || $ciphertext === false || $tag === '') return '';
        $password = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '');
        return $password === false ? '' : $password;
    }
    // Legacy CBC records remain readable long enough for deploy/migrate.php to re-encrypt them.
    $raw = base64_decode($encoded, true);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    if ($raw === false || strlen($raw) <= $ivLength) return '';
    $iv = substr($raw, 0, $ivLength);
    $ciphertext = substr($raw, $ivLength);
    $password = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return $password === false ? '' : $password;
}

function generate_random_password(int $length = 12): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $result = '';
    $max = strlen($alphabet) - 1;
    for ($i = 0; $i < max(1, $length); $i++) $result .= $alphabet[random_int(0, $max)];
    return $result;
}

function is_valid_tenant_domain(string $domain): bool {
    $domain = normalize_tenant_domain($domain);
    return $domain !== '' && (bool)preg_match('/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\\.)+[a-z]{2,}$/', $domain);
}

function tenant_admin_username_for_domain(string $domain): string {
    $label = explode('.', normalize_tenant_domain($domain))[0] ?? 'tenant';
    $label = preg_replace('/[^a-z0-9_-]/i', '-', $label) ?: 'tenant';
    return substr('admin-' . strtolower(trim($label, '-_')), 0, 48);
}

function tenant_database(bool $readOnly = false): SQLite3 {
    $flags = $readOnly ? SQLITE3_OPEN_READONLY : (SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    $db = @new SQLite3(DB_PATH, $flags);
    $db->busyTimeout(5000);
    if (!$readOnly) $db->exec('PRAGMA foreign_keys = ON');
    return $db;
}

function tenant_from_domain(SQLite3 $db, string $domain, bool $activeOnly = true): ?array {
    $sql = 'SELECT id, domain, status, created_at FROM tenants WHERE domain = :domain';
    if ($activeOnly) $sql .= " AND status = 'active'";
    $sql .= ' LIMIT 1';
    $stmt = @$db->prepare($sql);
    if (!$stmt) return null;
    $stmt->bindValue(':domain', normalize_tenant_domain($domain), SQLITE3_TEXT);
    $result = @$stmt->execute();
    $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;
    return is_array($row) ? $row : null;
}

function tenant_auto_provision_enabled(): bool {
    $configured = getenv('UNDANGAN_AUTO_PROVISION');
    return $configured === false || trim((string)$configured) !== '0';
}

function cloudflare_tunnel_ingress_is_validated(): bool {
    $remoteAddress = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if (!in_array($remoteAddress, ['127.0.0.1', '::1'], true)) return false;
    $cfRay = trim((string)($_SERVER['HTTP_CF_RAY'] ?? ''));
    $cfConnectingIp = trim((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
    return $cfRay !== '' && filter_var($cfConnectingIp, FILTER_VALIDATE_IP) !== false;
}

function provision_tenant_from_validated_ingress(string $domain): ?array {
    $domain = normalize_tenant_domain($domain);
    if (!is_valid_tenant_domain($domain) || !cloudflare_tunnel_ingress_is_validated()) return null;
    $db = null;
    $createdDirectories = [];
    try {
        $db = tenant_database(false);
        $db->exec('BEGIN IMMEDIATE');
        $existing = tenant_from_domain($db, $domain, false);
        if (is_array($existing)) {
            $db->exec('ROLLBACK');
            return ($existing['status'] ?? '') === 'active' ? $existing : null;
        }
        $insertTenant = $db->prepare("INSERT INTO tenants (domain, status) VALUES (:domain, 'active')");
        if (!$insertTenant) throw new RuntimeException('Tenant provisioning prepare failed.');
        $insertTenant->bindValue(':domain', $domain, SQLITE3_TEXT);
        if (!$insertTenant->execute()) throw new RuntimeException('Tenant provisioning insert failed.');
        $tenantId = (int)$db->lastInsertRowID();
        if ($tenantId < 1) throw new RuntimeException('Tenant provisioning returned an invalid tenant ID.');
        ensure_tenant_seed($db, $tenantId);
        $tenantPassword = generate_random_password(12);
        $visiblePassword = encrypt_visible_password($tenantPassword);
        if ($visiblePassword === '') throw new RuntimeException('UNDANGAN_PASSWORD_KEY is required for tenant provisioning.');
        $tenantAdmin = $db->prepare("INSERT INTO users (tenant_id, username, password_hash, visible_password, role) VALUES (:tenant_id, :username, :password_hash, :visible_password, 'tenant_admin')");
        if (!$tenantAdmin) throw new RuntimeException('Tenant Admin provisioning prepare failed.');
        $tenantAdmin->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
        $tenantAdmin->bindValue(':username', tenant_admin_username_for_domain($domain), SQLITE3_TEXT);
        $tenantAdmin->bindValue(':password_hash', password_hash($tenantPassword, PASSWORD_DEFAULT), SQLITE3_TEXT);
        $tenantAdmin->bindValue(':visible_password', $visiblePassword, SQLITE3_TEXT);
        if (!$tenantAdmin->execute()) throw new RuntimeException('Tenant Admin provisioning insert failed.');
        $tenant = ['id' => $tenantId, 'domain' => $domain, 'status' => 'active', 'created_at' => gmdate('Y-m-d H:i:s')];
        $tenantRoot = tenant_upload_root($tenant);
        if ($tenantRoot === '') throw new RuntimeException('Tenant upload root initialization returned an empty path.');
        if (!is_dir($tenantRoot)) $createdDirectories[] = $tenantRoot;
        foreach (['cover', 'music', 'gallery', 'background', 'love_story', 'theme_assets'] as $kind) {
            $directory = tenant_upload_dir($kind, $tenant);
            if ($directory === '') throw new RuntimeException('Tenant media directory initialization returned an empty path.');
            if (!is_dir($directory)) {
                if (!@mkdir($directory, 0755, true) && !is_dir($directory)) {
                    throw new RuntimeException('Tenant media directory initialization failed: ' . $directory);
                }
                $createdDirectories[] = $directory;
            }
        }
        if (!is_dir($tenantRoot)) throw new RuntimeException('Tenant upload root initialization failed for tenant ' . $tenantId);
        if (!$db->exec('COMMIT')) throw new RuntimeException('Tenant provisioning commit failed.');
        $db->close();
        $db = null;
        error_log('Auto-provisioned tenant ' . $domain . ' with Tenant Admin ' . tenant_admin_username_for_domain($domain) . '.');
        return $tenant;
    } catch (Throwable $e) {
        if ($db instanceof SQLite3) @$db->exec('ROLLBACK');
        if ($db instanceof SQLite3) @$db->close();
        foreach (array_reverse($createdDirectories) as $directory) {
            if (is_dir($directory)) @rmdir($directory);
        }
        error_log('Tenant auto-provisioning failed: ' . $e->getMessage());
        return null;
    }
}

function current_tenant(bool $required = true): ?array {
    static $resolved = false;
    static $tenant = null;
    if ($resolved) {
        if ($required && $tenant === null) tenant_http_error(404, 'Domain tidak terdaftar.');
        return $tenant;
    }
    $resolved = true;
    $domain = request_host();
    try {
        $db = tenant_database(true);
        $tenant = tenant_from_domain($db, $domain, true);
        if ($tenant === null && tenant_auto_provision_enabled()) {
            $knownTenant = tenant_from_domain($db, $domain, false);
            if ($knownTenant === null) {
                if (!cloudflare_tunnel_ingress_is_validated()) {
                    $db->close();
                    $db = null;
                    tenant_http_error(403, 'Auto-provisioning hanya diizinkan melalui Cloudflare Tunnel lokal.');
                }
                $db->close();
                $db = null;
                $tenant = is_valid_tenant_domain($domain) ? provision_tenant_from_validated_ingress($domain) : null;
            }
        }
        if ($db instanceof SQLite3) $db->close();
    } catch (Throwable $e) {
        if (is_file(DB_PATH)) error_log('Tenant resolution failed: ' . $e->getMessage());
        $tenant = null;
    }
    if ($required && $tenant === null) tenant_http_error(404, 'Domain tidak terdaftar atau sedang ditangguhkan.');
    return $tenant;
}

function tenant_http_error(int $status, string $message): void {
    if (PHP_SAPI !== 'cli') {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo $message;
    exit;
}

function current_tenant_id(): int {
    $tenant = current_tenant(true);
    return (int)$tenant['id'];
}

function tenant_upload_root(?array $tenant = null): string {
    $tenant = $tenant ?? current_tenant(false);
    if (!is_array($tenant) || (int)($tenant['id'] ?? 0) < 1) return '';
    return ROOT_DIR . '/uploads/tenant_' . (int)$tenant['id'];
}

function tenant_upload_dir(string $kind, ?array $tenant = null): string {
    $root = tenant_upload_root($tenant);
    if ($root === '') return '';
    $folders = [
        'cover' => 'cover', 'music' => 'music', 'gallery' => 'gallery',
        'background' => 'background', 'love_story' => 'love-story',
        'video' => 'love-story', 'theme_assets' => 'theme-assets'
    ];
    return $root . '/' . ($folders[$kind] ?? 'misc');
}

function current_user_role(): string {
    init_session();
    return (string)($_SESSION['role'] ?? '');
}

function is_super_admin(): bool {
    return current_user_role() === 'super_admin';
}

function current_admin_user_record(): ?array {
    init_session();
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId < 1) return null;
    try {
        $db = tenant_database(true);
        $stmt = $db->prepare('SELECT id, tenant_id, username, password_hash, role FROM users WHERE id = :user_id LIMIT 1');
        if (!$stmt) { $db->close(); return null; }
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        $db->close();
        return is_array($row) ? $row : null;
    } catch (Throwable $e) {
        error_log('Current admin lookup failed: ' . $e->getMessage());
        return null;
    }
}

function verify_current_admin_password(string $password): bool {
    if ($password === '') return false;
    $user = current_admin_user_record();
    return is_array($user) && password_verify($password, (string)($user['password_hash'] ?? ''));
}

function admin_password_policy_error(string $password): ?string {
    if (strlen($password) < 12) return 'Password minimal 12 karakter.';
    if (strlen($password) > 128) return 'Password maksimal 128 karakter.';
    return null;
}

function rotate_admin_session(): void {
    init_session();
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['last_activity'] = time();
}

function destroy_admin_session(): void {
    init_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies') && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? false);
    }
    session_destroy();
}

function admin_action_is_authorized(string $action, ?string $presetKey = null): bool {
    if (!session_admin_is_valid()) return false;
    $allowedActions = [
        'upload_media_library', 'use_media_library_asset', 'clear_media_target', 'rename_media_file',
        'replace_media_file', 'set_media_default', 'delete_media_file',
        'save_wedding', 'save_parents', 'save_schedule', 'save_location',
        'save_gift', 'save_dresscode', 'save_whatsapp', 'save_theme_options',
        'save_sections', 'save_custom_css', 'save_preset', 'save_theme',
        'save_love_story', 'upload_bride_photo', 'upload_groom_photo',
        'upload_couple_photo', 'save_guest_link', 'delete_guest_link',
        'save_seo', 'save_settings', 'upload_cover', 'upload_music',
        'upload_background', 'upload_qris', 'upload_og_image', 'upload_gallery',
        'delete_gallery_item', 'set_gallery_cover', 'save_gallery_order'
    ];
    if (!in_array($action, $allowedActions, true)) return false;
    if ($action === 'save_theme_options') {
        $presetKey = trim((string)$presetKey);
        if ($presetKey === 'custom') return true;
        if ($presetKey === '' || !function_exists('theme_registry')) return false;
        return array_key_exists($presetKey, theme_registry());
    }
    return true;
}

function audit_log(string $action, ?int $targetTenantId = null, array $metadata = []): void {
    init_session();
    $actorUserId = (int)($_SESSION['user_id'] ?? 0);
    if ($actorUserId < 1 || $action === '') return;
    $metadataJson = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($metadataJson === false) $metadataJson = '{}';
    try {
        $db = tenant_database(false);
        $stmt = $db->prepare('INSERT INTO audit_logs (actor_user_id, actor_role, actor_tenant_id, target_tenant_id, action, metadata_json, ip_address, created_at) VALUES (:actor_user_id, :actor_role, :actor_tenant_id, :target_tenant_id, :action, :metadata_json, :ip_address, CURRENT_TIMESTAMP)');
        if (!$stmt) { $db->close(); return; }
        $actorTenantId = (string)($_SESSION['role'] ?? '') === 'tenant_admin' ? (int)($_SESSION['tenant_id'] ?? 0) : null;
        $stmt->bindValue(':actor_user_id', $actorUserId, SQLITE3_INTEGER);
        $stmt->bindValue(':actor_role', (string)($_SESSION['role'] ?? ''), SQLITE3_TEXT);
        if ($actorTenantId === null) $stmt->bindValue(':actor_tenant_id', null, SQLITE3_NULL); else $stmt->bindValue(':actor_tenant_id', $actorTenantId, SQLITE3_INTEGER);
        if ($targetTenantId === null) $stmt->bindValue(':target_tenant_id', null, SQLITE3_NULL); else $stmt->bindValue(':target_tenant_id', $targetTenantId, SQLITE3_INTEGER);
        $stmt->bindValue(':action', $action, SQLITE3_TEXT);
        $stmt->bindValue(':metadata_json', $metadataJson, SQLITE3_TEXT);
        $stmt->bindValue(':ip_address', substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64), SQLITE3_TEXT);
        $stmt->execute();
        $db->close();
    } catch (Throwable $e) {
        error_log('Audit log write failed: ' . $e->getMessage());
    }
}

function tenant_query_scope(string $column = 'tenant_id'): array {
    if (is_super_admin()) return ['', []];
    return [" AND {$column} = :current_tenant_id", [':current_tenant_id' => current_tenant_id()]];
}

function tenant_default_config_from_legacy(): array {
    return function_exists('config_defaults') ? config_defaults() : [];
}

function ensure_tenant_seed(SQLite3 $db, int $tenantId): void {
    $stmt = $db->prepare('SELECT 1 FROM tenant_configs WHERE tenant_id = :tenant_id LIMIT 1');
    $stmt->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
    if ($stmt->execute()->fetchArray(SQLITE3_NUM)) return;
    $config = config_defaults();
    $json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) $json = '{}';
    $insert = $db->prepare('INSERT OR IGNORE INTO tenant_configs (tenant_id, config_json, custom_css, event_ics) VALUES (:tenant_id, :config_json, :custom_css, :event_ics)');
    $insert->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
    $insert->bindValue(':config_json', $json, SQLITE3_TEXT);
    $insert->bindValue(':custom_css', '', SQLITE3_TEXT);
    $insert->bindValue(':event_ics', build_event_ics($config), SQLITE3_TEXT);
    $insert->execute();
}

// Security headers
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

function config_defaults(): array {
    return [
        'site' => [
            'url' => '',
            'title' => 'Undangan Pernikahan Febru & Andi',
            'description' => 'Dengan memohon rahmat dan ridha Allah SWT, kami bermaksud mengundang Bapak/Ibu/Saudara/i untuk hadir dan memberikan doa restu pada acara pernikahan kami.',
            'keywords' => 'undangan pernikahan, wedding invitation, FEBRUANA, ANDI MUHAMAD BASUKI, Febru, Andi',
            'open_graph_title' => 'Undangan Pernikahan Febru & Andi',
            'open_graph_description' => 'Dengan memohon rahmat dan ridha Allah SWT, kami bermaksud mengundang Bapak/Ibu/Saudara/i untuk hadir dan memberikan doa restu pada acara pernikahan kami.',
            'twitter_card' => 'summary_large_image',
            // Optional user-provided media; clean installs start without sample files.
            'open_graph_image' => '',
            'schema' => json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Event',
                'name' => 'Undangan Pernikahan Febru & Andi',
                'startDate' => '2026-12-29T09:00:00+07:00',
                'location' => [
                    '@type' => 'Place',
                    'name' => 'PFR2+G9H Asinan',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => 'Kabupaten Semarang',
                        'addressRegion' => 'Jawa Tengah',
                        'addressCountry' => 'ID'
                    ]
                ],
                'description' => 'Dengan memohon rahmat dan ridha Allah SWT, kami bermaksud mengundang Bapak/Ibu/Saudara/i untuk hadir dan memberikan doa restu pada acara pernikahan kami.',
                'eventStatus' => 'https://schema.org/EventScheduled',
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode'
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ],
        'wedding' => [
            'bride_name' => 'FEBRUANA',
            'groom_name' => 'ANDI MUHAMAD BASUKI',
            'title' => 'Undangan Pernikahan Febru & Andi',
            'opening_text' => 'Dengan memohon rahmat dan ridha Allah SWT, kami bermaksud mengundang Bapak/Ibu/Saudara/i untuk hadir dan memberikan doa restu pada acara pernikahan kami.',
            'closing_text' => 'Kehadiran dan doa restu Bapak/Ibu/Saudara/i merupakan kebahagiaan dan hadiah terindah bagi kami. Terima kasih atas perhatian, kasih sayang, dan doa yang diberikan. Semoga Allah SWT membalas kebaikan Anda dengan keberkahan.',
            'quote' => "وَمِنْ اٰيٰتِهٖٓ اَنْ خَلَقَ لَكُمْ مِّنْ اَنْفُسِكُمْ اَزْوَاجًا لِّتَسْكُنُوْٓا اِلَيْهَا وَجَعَلَ بَيْنَكُمْ مَّوَدَّةً وَّرَحْمَةًۗ اِنَّ فِيْ ذٰلِكَ لَاٰيٰتٍ لِّقَوْمٍ يَّتَفَكَّرُوْنَ ۝٢١\n\n“Di antara tanda-tanda kebesaran-Nya ialah Dia menciptakan pasangan-pasangan untukmu agar kamu merasa tenteram kepadanya. Dia menjadikan di antaramu rasa cinta dan kasih sayang. Sungguh, pada yang demikian itu benar-benar terdapat tanda-tanda kebesaran Allah bagi kaum yang berpikir.” (QS. Ar-Rum: 21)",
            'bride_nickname' => 'Febru',
            'groom_nickname' => 'Andi'
        ],
        'parents' => [
            'bride_father' => 'Ayah Februana',
            'bride_mother' => 'Ibu Februana',
            'groom_father' => 'Ayah Andi Muhamad Basuki',
            'groom_mother' => 'Ibu Andi Muhamad Basuki'
        ],
        'schedule' => [
            'akad_date' => '2026-12-29',
            'akad_time' => '09:00',
            'reception_date' => '2026-12-29',
            'reception_time' => '11:00',
            'timezone' => 'Asia/Jakarta',
            'google_calendar_link' => 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=Undangan+Pernikahan+Febru+%26+Andi&dates=20261229T090000/20261229T110000&details=Dengan+memohon+rahmat+dan+ridha+Allah+SWT%2C+kami+bermaksud+mengundang+Bapak%2FIbu%2FSaudara%2Fi+untuk+hadir+dan+memberikan+doa+restu+pada+acara+pernikahan+kami.&location=PFR2%2BG9H+Asinan%2C+Kabupaten+Semarang%2C+Jawa+Tengah&ctz=Asia%2FJakarta',
            'countdown_target' => '2026-12-29T09:00:00+07:00'
        ],
        'location' => [
            'venue' => 'PFR2+G9H Asinan',
            'address' => 'PFR2+G9H Asinan, Kabupaten Semarang, Jawa Tengah',
            'maps_url' => 'https://www.google.com/maps/search/?api=1&query=-7.2586798,110.4509814',
            'maps_embed' => 'https://maps.google.com/maps?q=-7.2586798,110.4509814&z=17&output=embed'
        ],
        'media' => [
            'cover' => '',
            'bride_photo' => '',
            'groom_photo' => '',
            'couple_photo' => '',
            'music' => '',
                'background_hero' => '',
                'love_story_video' => '',
                'background_sections' => []
        ],
        'gallery' => [
            'cover' => '',
            'items' => []
        ],
        'gift' => [
            'bank' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Andi',
            'qris_image' => '',
            'e_wallet_label' => 'GoPay',
            'e_wallet_number' => '+6285162909164'
        ],
        'whatsapp' => [
            'phone' => '+6285162909164',
            'message' => 'Assalamu\'alaikum, saya ingin mengonfirmasi kehadiran pada pernikahan Febru & Andi.'
        ],
        'admin' => [
            'username' => 'admin',
            'password_hash' => ''
        ],
        'theme' => [
            'mode' => 'preset',
            'theme_preset' => 'dewankl',
            'primary_color' => '#c84c47',
            'secondary_color' => '#f0c2a1',
            'accent_color' => '#f0c2a1',
            'background_color' => '#fff8f2',
            'paper_color' => '#ffffff',
            'muted_color' => '#806f66',
            'text_color' => '#2f2424',
            'link_color' => '#c84c47',
            'button_style' => 'rounded',
            'border_radius' => '28px',
            'shadow' => '0 22px 60px rgba(73,45,34,.14)',
            'container_width' => '1200px',
            'section_spacing' => '80px',
            'heading_font' => 'Playfair Display, serif',
            'body_font' => 'Lato, sans-serif',
            'font_size_base' => '16px',
            'animation_enabled' => true,
            'navbar_style' => 'transparent',
            'card_style' => 'elevated',
            'footer_style' => 'centered',
            // Hero settings - Desktop
            'hero_height' => '100vh',
            'hero_vertical_alignment' => 'center',
            'hero_content_width' => '900px',
            'hero_image_fit' => 'cover',
            'hero_image_position' => 'center',
            // Hero settings - Mobile
            'mobile_hero_height' => '85vh',
            'mobile_hero_vertical_alignment' => 'center',
            'mobile_hero_content_width' => '100%',
            'mobile_hero_image_fit' => 'cover',
            'mobile_hero_image_position' => 'center top'
        ],
        'buttons' => [
            'mobile_layout' => '2-columns'
        ],
        'sections' => [
            [
                'id' => 'hero',
                'title' => 'Hero',
                'subtitle' => '',
                'enabled' => true,
                'order' => 1,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'guest_intro',
                'title' => 'Kepada Yth.',
                'subtitle' => '',
                'enabled' => true,
                'order' => 2,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'undangan',
                'title' => 'Undangan Pernikahan',
                'subtitle' => 'Dengan memohon rahmat dan ridha Allah SWT, kami bermaksud mengundang Bapak/Ibu/Saudara/i untuk hadir dan memberikan doa restu pada acara pernikahan kami.',
                'enabled' => true,
                'order' => 3,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'bride_groom',
                'title' => 'Bride & Groom',
                'subtitle' => 'Mempelai',
                'enabled' => true,
                'order' => 4,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'countdown',
                'title' => 'Countdown',
                'subtitle' => 'Menuju Hari Bahagia',
                'enabled' => true,
                'order' => 5,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'cerita',
                'title' => 'Love Story',
                'subtitle' => 'Cerita Kami',
                'enabled' => true,
                'order' => 6,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'galeri',
                'title' => 'Gallery',
                'subtitle' => 'Galeri Foto',
                'enabled' => true,
                'order' => 7,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'acara',
                'title' => 'Events',
                'subtitle' => 'Acara',
                'enabled' => true,
                'order' => 8,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'lokasi',
                'title' => 'Location',
                'subtitle' => 'Lokasi Acara',
                'enabled' => true,
                'order' => 9,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'rsvp',
                'title' => 'RSVP',
                'subtitle' => 'Konfirmasi Kehadiran',
                'enabled' => true,
                'order' => 10,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'amplop',
                'title' => 'Gift',
                'subtitle' => 'Ucapan & Hadiah',
                'enabled' => true,
                'order' => 11,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'messages',
                'title' => 'Guest Wishes',
                'subtitle' => 'Ucapan Tamu',
                'enabled' => true,
                'order' => 12,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'music',
                'title' => 'Music',
                'subtitle' => 'Musik Latar',
                'enabled' => true,
                'order' => 13,
                'custom_title' => '',
                'custom_subtitle' => ''
            ],
            [
                'id' => 'footer',
                'title' => 'Footer',
                'subtitle' => '',
                'enabled' => true,
                'order' => 14,
                'custom_title' => '',
                'custom_subtitle' => ''
            ]
        ],
        'love_story' => [
            'items' => []
        ],
        'dresscode' => [
            'enabled' => true,
            'title' => 'Dresscode',
            'color' => 'Putih / Pastel',
            'rule' => 'Rapi dan sopan',
            'description' => 'Kenakan busana terbaikmu untuk momen spesial.'
        ],
        'theme_options' => [
            'dewankl' => [
                'show_bismillah' => true,
                'enable_confetti' => true,
                'enable_mouse_animation' => true,
                'enable_music' => true,
                'welcome_note' => '',
                "opening_greeting" => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ\nAssalamu’alaikum Warahmatullahi Wabarakatuh"
            ],
            'shubh-vivah' => [
                "opening_greeting" => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ\nAssalamu’alaikum Warahmatullahi Wabarakatuh"
            ],
            'yami-buzzy' => [
                "opening_greeting" => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ\nAssalamu’alaikum Warahmatullahi Wabarakatuh"
            ],
            'rainier' => [
                'glass_opacity' => '0.85',
                'show_bismillah' => true,
                'hero_accent_color' => '#b8655d',
                'quote_note' => '',
                "opening_greeting" => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ\nAssalamu’alaikum Warahmatullahi Wabarakatuh"
            ],
            'archak' => [
                'enable_parallax' => true,
                'enable_preloader' => true,
                'divider_style' => 'ornament',
                'header_badge_image' => '',
                'archak_welcome_msg' => '',
                "opening_greeting" => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ\nAssalamu’alaikum Warahmatullahi Wabarakatuh"
            ],
            'parang' => [
                "opening_greeting" => "بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ\nAssalamu’alaikum Warahmatullahi Wabarakatuh"
            ],
            'pawiwahan' => [
                'opening_greeting' => 'OM Swastiastu'
            ]
        ],
        'theme_visuals' => [
            'dewankl' => [],
            'shubh-vivah' => [],
            'yami-buzzy' => [],
            'rainier' => [],
            'archak' => [],
            'parang' => [],
            'pawiwahan' => [],
            'custom' => []
        ],
        'custom_css' => ''
    ];
}


function theme_color_palette(string $role = 'text'): array {
    return [
        '#1f2937' => 'Arang',
        '#1d1b22' => 'Hitam Rainier',
        '#211b0e' => 'Cokelat Parang',
        '#211d1a' => 'Cokelat Archak',
        '#2a1f1c' => 'Cokelat Dewana',
        '#2f2424' => 'Cokelat Teks',
        '#343039' => 'Abu Yami',
        '#392521' => 'Cokelat Kopi',
        '#334155' => 'Slate',
        '#374151' => 'Abu Tua',
        '#5b4636' => 'Cokelat Kayu',
        '#6b4f4f' => 'Mawar Tua',
        '#6e5a52' => 'Taupe Hangat',
        '#6e7c63' => 'Sage',
        '#7a8c7e' => 'Sage Rainier',
        '#7b4a3a' => 'Terracotta',
        '#7b5902' => 'Emas Tua',
        '#806f66' => 'Taupe',
        '#82727a' => 'Mauve Grey',
        '#8c5a4d' => 'Cokelat Rose',
        '#8ca77b' => 'Sage Muda',
        '#8f4756' => 'Burgundy',
        '#a24747' => 'Merah Bata',
        '#ad7c69' => 'Clay',
        '#b8655d' => 'Rose Terracotta',
        '#c49a45' => 'Emas',
        '#c84c47' => 'Coral',
        '#d77fa1' => 'Pink Mawar',
        '#372d36' => 'Ungu Pawiwahan',
        '#4f453e' => 'Taupe Parang',
        '#5c5255' => 'Abu Rainier',
        '#5d5350' => 'Abu Archak',
        '#6d5a62' => 'Mauve Pawiwahan',
        '#85665f' => 'Taupe Shubh',
        '#8b4f70' => 'Plum Pawiwahan',
        '#ec7272' => 'Coral Pawiwahan',
        '#f2e4d3' => 'Ivory Dewana',
        '#f6efe7' => 'Ivory',
        '#fff8f2' => 'Cream',
        '#ffffff' => 'Putih',
        '#000000' => 'Hitam'
    ];
}

function theme_font_catalog(string $type = 'all'): array {
    $heading = [
        'Playfair Display, serif' => 'Playfair Display',
        'Cormorant Garamond, serif' => 'Cormorant Garamond',
        'Bodoni Moda, serif' => 'Bodoni Moda',
        'DM Serif Display, serif' => 'DM Serif Display',
        'Fraunces, serif' => 'Fraunces',
        'Lora, serif' => 'Lora',
        'Libre Baskerville, serif' => 'Libre Baskerville',
        'Cinzel, serif' => 'Cinzel',
        'Great Vibes, cursive' => 'Great Vibes',
        'Dancing Script, cursive' => 'Dancing Script',
        'Sacramento, cursive' => 'Sacramento',
        'Beau Rivage, cursive' => 'Beau Rivage',
        'Tangerine, cursive' => 'Tangerine',
        'Caveat, cursive' => 'Caveat',
        'Gilroy, Arial, sans-serif' => 'Gilroy (lokal)',
        'Libre Caslon Text, serif' => 'Libre Caslon Text (source)',
        'Georgia, serif' => 'Georgia',
        'system-ui, sans-serif' => 'System UI'
    ];
    $body = [
        'Lato, sans-serif' => 'Lato',
        'Josefin Sans, sans-serif' => 'Josefin Sans',
        'Inter, sans-serif' => 'Inter',
        'Work Sans, sans-serif' => 'Work Sans',
        'Poppins, sans-serif' => 'Poppins',
        'DM Sans, sans-serif' => 'DM Sans',
        'Nunito Sans, sans-serif' => 'Nunito Sans',
        'Montserrat, sans-serif' => 'Montserrat',
        'Open Sans, sans-serif' => 'Open Sans',
        'Plus Jakarta Sans, sans-serif' => 'Plus Jakarta Sans',
        'Outfit, sans-serif' => 'Outfit',
        'Arvo, Georgia, serif' => 'Arvo',
        'Quicksand, sans-serif' => 'Quicksand',
        'Manrope, sans-serif' => 'Manrope',
        'Raleway, sans-serif' => 'Raleway',
        'Merriweather Sans, sans-serif' => 'Merriweather Sans',
        'Gilroy, Arial, sans-serif' => 'Gilroy (lokal)',
        'Segoe UI, sans-serif' => 'Segoe UI',
        'Libre Baskerville, serif' => 'Libre Baskerville',
        'Arial, sans-serif' => 'Arial',
        'system-ui, sans-serif' => 'System UI'
    ];
    return match ($type) {
        'heading' => $heading,
        'body' => $body,
        default => array_replace($body, $heading)
    };
}

function theme_google_font_stylesheet_url(): string {
    return 'https://fonts.googleapis.com/css2?family=Arvo:wght@400;700&family=Beau+Rivage&family=Bodoni+Moda:opsz,wght@6..96,400;6..96,600;6..96,700&family=Caveat:wght@400;600;700&family=Cinzel:wght@400;500;600;700&family=Cormorant+Garamond:wght@400;500;600;700&family=Dancing+Script:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Great+Vibes&family=Inter:wght@400;500;600;700&family=Josefin+Sans:wght@400;500;600;700&family=Lato:wght@400;700&family=Libre+Baskerville:wght@400;700&family=Libre+Caslon+Text:ital,wght@0,400;0,700;1,400&family=Lora:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&family=Merriweather+Sans:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&family=Noto+Naskh+Arabic:wght@400;700&family=Nunito+Sans:wght@400;500;600;700&family=Open+Sans:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&family=Playfair+Display:opsz,wght@5..120,400;5..120,500;5..120,600;5..120,700&family=Quicksand:wght@400;500;600;700&family=Raleway:wght@400;500;600;700&family=Sacramento&family=Tangerine:wght@400;700&family=Work+Sans:wght@400;500;600;700&display=swap';
}

function theme_common_visual_capabilities(array $values, array $existing = []): array {
    $default = static function (string $key, string $fallback) use ($values, $existing): string {
        $existingDefault = $existing[$key]['default'] ?? null;
        if (is_scalar($existingDefault) && trim((string)$existingDefault) !== '') return (string)$existingDefault;
        return (string)($values[$key] ?? $fallback);
    };
    $text = $default('text_color', '#2f2424');
    $heading = $default('heading_color', $text);
    $muted = $default('muted_color', '#806f66');
    $link = $default('link_color', (string)($values['accent_color'] ?? '#c84c47'));
    $accent = $default('accent_color', $link);
    $palette = theme_color_palette();
    return [
        'accent_color' => ['type' => 'color', 'label' => 'Warna Aksen', 'description' => 'Warna tombol, badge, ikon, dan elemen interaktif.', 'default' => $accent, 'palette' => $palette],
        'heading_color' => ['type' => 'color', 'label' => 'Warna Judul', 'description' => 'Warna nama pasangan dan judul section.', 'default' => $heading, 'palette' => $palette],
        'text_color' => ['type' => 'color', 'label' => 'Warna Teks', 'description' => 'Warna teks utama, isi undangan, dan detail acara.', 'default' => $text, 'palette' => $palette],
        'muted_color' => ['type' => 'color', 'label' => 'Warna Teks Sekunder', 'description' => 'Warna teks kecil, label, keterangan, dan metadata.', 'default' => $muted, 'palette' => $palette],
        'link_color' => ['type' => 'color', 'label' => 'Warna Tautan', 'description' => 'Warna tautan, navigasi, dan aksi sekunder.', 'default' => $link, 'palette' => $palette],
        'heading_font' => ['type' => 'font', 'label' => 'Font Judul', 'description' => 'Font untuk nama pasangan, heading, dan judul section.', 'default' => $default('heading_font', 'Playfair Display, serif'), 'options' => theme_font_catalog('heading')],
        'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font mudah dibaca untuk isi undangan, jadwal, navigasi, dan form.', 'default' => $default('body_font', 'Lato, sans-serif'), 'options' => theme_font_catalog('body')]
    ];
}

function theme_registry(): array {
    $registry = [
        'elegant' => [
            'id' => 'elegant',
            'name' => 'Elegant',
            'label' => 'Elegant',
            'description' => 'Palet hangat dan klasik untuk undangan yang lembut.',
            'version' => '1.0.0',
            'author' => 'Februana Studio',
            'source' => 'Built-in CMS preset',
            'license' => 'Project-internal',
            'category' => 'classic',
            'values' => [
                'primary_color' => '#c84c47',
                'secondary_color' => '#f0c2a1',
                'accent_color' => '#f0c2a1',
                'background_color' => '#fff8f2',
                'paper_color' => '#ffffff',
                'muted_color' => '#806f66',
                'text_color' => '#2f2424',
                'link_color' => '#c84c47',
                'button_style' => 'rounded',
                'border_radius' => '28px',
                'shadow' => '0 22px 60px rgba(73,45,34,.14)',
                'container_width' => '1200px',
                'section_spacing' => '80px',
                'heading_font' => 'Playfair Display, serif',
                'body_font' => 'Lato, sans-serif',
                'font_size_base' => '16px'
            ]
        ],
        'dark' => [
            'id' => 'dark',
            'name' => 'Dark',
            'label' => 'Dark',
            'description' => 'Kontras gelap modern dengan aksen emas.',
            'version' => '1.0.0',
            'author' => 'Februana Studio',
            'source' => 'Built-in CMS preset',
            'license' => 'Project-internal',
            'category' => 'modern',
            'values' => [
                'primary_color' => '#d6a85f',
                'secondary_color' => '#2b2430',
                'accent_color' => '#f4d28f',
                'background_color' => '#151217',
                'paper_color' => '#211b26',
                'muted_color' => '#cbbfb4',
                'text_color' => '#f6efe7',
                'link_color' => '#f4d28f',
                'button_style' => 'pill',
                'border_radius' => '22px',
                'shadow' => '0 24px 70px rgba(0,0,0,.38)',
                'container_width' => '1160px',
                'section_spacing' => '84px',
                'heading_font' => 'Cormorant Garamond, serif',
                'body_font' => 'Inter, sans-serif',
                'font_size_base' => '16px'
            ]
        ],
        'floral' => [
            'id' => 'floral',
            'name' => 'Floral',
            'label' => 'Floral',
            'description' => 'Nuansa bunga pastel dengan aksen hijau sage.',
            'version' => '1.0.0',
            'author' => 'Februana Studio',
            'source' => 'Built-in CMS preset',
            'license' => 'Project-internal',
            'category' => 'romantic',
            'values' => [
                'primary_color' => '#a45c68',
                'secondary_color' => '#e8b7c2',
                'accent_color' => '#8ca77b',
                'background_color' => '#fff7f8',
                'paper_color' => '#ffffff',
                'muted_color' => '#846d73',
                'text_color' => '#3d3034',
                'link_color' => '#8f4756',
                'button_style' => 'rounded',
                'border_radius' => '34px',
                'shadow' => '0 20px 55px rgba(164,92,104,.18)',
                'container_width' => '1180px',
                'section_spacing' => '88px',
                'heading_font' => 'Great Vibes, cursive',
                'body_font' => 'Lato, sans-serif',
                'font_size_base' => '16px'
            ]
        ],
        'minimal' => [
            'id' => 'minimal',
            'name' => 'Minimal',
            'label' => 'Minimal',
            'description' => 'Tampilan bersih monokrom dengan aksen sederhana.',
            'version' => '1.0.0',
            'author' => 'Februana Studio',
            'source' => 'Built-in CMS preset',
            'license' => 'Project-internal',
            'category' => 'minimal',
            'values' => [
                'primary_color' => '#1f2937',
                'secondary_color' => '#e5e7eb',
                'accent_color' => '#9ca3af',
                'background_color' => '#fafafa',
                'paper_color' => '#ffffff',
                'muted_color' => '#6b7280',
                'text_color' => '#111827',
                'link_color' => '#374151',
                'button_style' => 'square',
                'border_radius' => '12px',
                'shadow' => '0 16px 42px rgba(17,24,39,.10)',
                'container_width' => '1100px',
                'section_spacing' => '72px',
                'heading_font' => 'Inter, sans-serif',
                'body_font' => 'Inter, sans-serif',
                'font_size_base' => '16px'
            ]
        ],
        'dewankl' => [
            'id' => 'dewankl',
            'name' => 'DewanaKL',
            'label' => 'DewanaKL',
            'description' => 'Layout modern berkelas dengan fokus pada hero yang lebih ekspresif dan kartu informasi yang lebih terstruktur.',
            'version' => '1.0.0',
            'author' => 'DewanaKL adaptation',
            'source' => 'dewanakl/undangan',
            'license' => 'MIT (adapted in-project; source design reviewed as inspiration only)',
            'category' => 'modern',
            'values' => [
                'primary_color' => '#7b4a3a',
                'secondary_color' => '#d9c0a3',
                'accent_color' => '#f2e4d3',
                'background_color' => '#f5efe8',
                'paper_color' => '#fcf8f3',
                'muted_color' => '#6e5a52',
                'text_color' => '#2a1f1c',
                'link_color' => '#7b4a3a',
                'button_style' => 'pill',
                'border_radius' => '18px',
                'shadow' => '0 18px 44px rgba(57,34,28,.12)',
                'container_width' => '1200px',
                'section_spacing' => '100px',
                'heading_font' => 'Cormorant Garamond, serif',
                'body_font' => 'Inter, sans-serif',
                'font_size_base' => '16px'
            ],
            'schema' => [
                'show_bismillah' => [
                    'type' => 'boolean',
                    'label' => 'Tampilkan Bismillah',
                    'description' => 'Tampilkan lafaz Bismillah di bagian atas pembuka.',
                    'default' => true
                ],
                'enable_confetti' => [
                    'type' => 'boolean',
                    'label' => 'Efek Confetti',
                    'description' => 'Aktifkan animasi efek serpihan confetti saat tombol buka undangan diklik.',
                    'default' => true
                ],
                'enable_mouse_animation' => [
                    'type' => 'boolean',
                    'label' => 'Animasi Kursor Mouse',
                    'description' => 'Aktifkan animasi jejak kursor mouse.',
                    'default' => true
                ],
                'enable_music' => [
                    'type' => 'boolean',
                    'label' => 'Musik Latar',
                    'description' => 'Tampilkan kontrol dan audio musik latar pada template asli DewanaKL.',
                    'default' => true
                ],
                'welcome_note' => [
                    'type' => 'textarea',
                    'label' => 'Pesan Sambutan Khusus',
                    'description' => 'Teks ucapan atau pesan sambutan tambahan (dukungan multi-baris).',
                    'default' => ''
                ],
                'opening_greeting' => [
                    'type' => 'textarea',
                    'label' => 'Salam Pembuka',
                    'description' => 'Teks salam pembuka yang tampil di awal preset. Bisa diganti dengan Bismillah, Assalamualaikum, OM Swastiastu, atau teks lain.',
                    'default' => 'Assalamualaikum Warahmatullahi Wabarakatuh'
                ]
            ],
            'capabilities' => [
                'content' => ['wedding', 'schedule', 'countdown', 'gallery', 'music', 'parents', 'gift', 'maps', 'seo', 'whatsapp', 'rsvp', 'sections'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation'],
            'visual_capabilities' => [
                'accent_color' => ['type' => 'color', 'label' => 'Warna Aksen', 'description' => 'Warna aksen tombol dan elemen interaktif DewanaKL.', 'default' => '#7b4a3a'],
                'heading_font' => ['type' => 'font', 'label' => 'Font Judul', 'description' => 'Font dekoratif untuk heading pendek dan nama pasangan.', 'default' => 'Sacramento, cursive', 'options' => ['Sacramento, cursive' => 'Sacramento', 'Georgia, serif' => 'Georgia']],
                'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font readable untuk detail acara dan form.', 'default' => 'Josefin Sans, sans-serif', 'options' => ['Josefin Sans, sans-serif' => 'Josefin Sans', 'system-ui, sans-serif' => 'System UI', 'Arial, sans-serif' => 'Arial', 'Georgia, serif' => 'Georgia']],
                'hero_background' => ['type' => 'image', 'label' => 'Latar Hero', 'description' => 'Path media atau URL. Kosongkan untuk memakai cover.', 'default' => ''],
                'welcome_background' => ['type' => 'image', 'label' => 'Latar Pembuka', 'description' => 'Opsional. Pilih gambar dari Pengelola Media; kosongkan untuk mempertahankan default warm ivory DewanaKL.', 'default' => ''],
                'section_background_home' => ['type' => 'image', 'label' => 'Latar Beranda', 'description' => 'Opsional. Berlaku hanya untuk section Home DewanaKL dan tidak menjadi item Gallery.', 'default' => ''],
                'section_background_bride' => ['type' => 'image', 'label' => 'Latar Mempelai', 'description' => 'Opsional. Berlaku hanya untuk section Bride & Groom DewanaKL dan tidak menjadi item Gallery.', 'default' => ''],
                'section_background_wedding_date' => ['type' => 'image', 'label' => 'Latar Tanggal Acara', 'description' => 'Gambar latar untuk bagian tanggal acara. Kosongkan untuk memakai tampilan bawaan.', 'default' => ''],
                'section_background_gallery' => ['type' => 'image', 'label' => 'Latar Galeri', 'description' => 'Gambar latar untuk bagian galeri foto.', 'default' => ''],
                'section_background_love_gift' => ['type' => 'image', 'label' => 'Latar Hadiah', 'description' => 'Gambar latar untuk bagian tanda kasih atau hadiah.', 'default' => ''],
                'section_background_comment' => ['type' => 'image', 'label' => 'Latar Ucapan', 'description' => 'Gambar latar untuk bagian ucapan dan konfirmasi kehadiran.', 'default' => ''],
                'hero_overlay' => ['type' => 'range', 'label' => 'Lapisan Gelap Pembuka', 'description' => 'Kekuatan lapisan gelap pada latar hero.', 'default' => '0.30', 'min' => '0', 'max' => '0.85', 'step' => '0.05'],
            ]
        ],
        'shubh-vivah' => [
            'id' => 'shubh-vivah',
            'name' => 'Shubh Vivah',
            'label' => 'Shubh Vivah',
            'description' => 'Undangan berbentuk kartu digital yang hangat dengan ornamen dekoratif, tipografi script, dan alur pembuka yang ringkas.',
            'version' => '1.0.0',
            'author' => 'Vinit Shahdeo source adaptation',
            'source' => 'https://github.com/vinitshahdeo/wedding-website',
            'license' => 'MIT; source adapted to project CMS contract',
            'category' => 'invitation-card',
            'values' => [
                'primary_color' => '#a24747', 'secondary_color' => '#f3d9d0', 'accent_color' => '#a24747',
                'background_color' => '#fbf0e9', 'paper_color' => '#fffaf5', 'muted_color' => '#85665f',
                'text_color' => '#392521', 'link_color' => '#a24747', 'button_style' => 'pill',
                'border_radius' => '0', 'shadow' => '0 24px 80px rgba(92,48,42,.16)', 'container_width' => '1040px',
                'section_spacing' => '90px', 'heading_font' => 'Dancing Script, cursive', 'body_font' => 'Arvo, Georgia, serif', 'font_size_base' => '16px'
            ],
            'schema' => [
                'opening_greeting' => ['type' => 'textarea', 'label' => 'Salam Pembuka', 'description' => 'Teks salam yang tampil pada kartu undangan.', 'default' => 'Bismillahirrahmanirrahim']
            ],
            'capabilities' => [
                'content' => ['wedding', 'schedule', 'countdown', 'gallery', 'music', 'maps', 'seo', 'whatsapp', 'rsvp', 'messages', 'guest_name', 'media', 'calendar', 'sections'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation'],
            'visual_capabilities' => [
                'accent_color' => ['type' => 'color', 'label' => 'Warna Aksen', 'description' => 'Warna tombol dan aksen kartu Shubh Vivah.', 'default' => '#a24747'],
                'heading_font' => ['type' => 'font', 'label' => 'Font Judul', 'description' => 'Font script untuk identitas pasangan dan heading.', 'default' => 'Dancing Script, cursive', 'options' => ['Dancing Script, cursive' => 'Dancing Script', 'Georgia, serif' => 'Georgia']],
                'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font isi yang terinspirasi dari source template.', 'default' => 'Arvo, Georgia, serif', 'options' => ['Arvo, Georgia, serif' => 'Arvo', 'system-ui, sans-serif' => 'System UI', 'Arial, sans-serif' => 'Arial']],
                'hero_background' => ['type' => 'image', 'label' => 'Latar Kartu Undangan', 'description' => 'Pilih media canonical untuk latar kartu; kosongkan untuk memakai artwork source.', 'default' => ''],
                'section_background_home' => ['type' => 'image', 'label' => 'Latar Beranda', 'description' => 'Gambar latar untuk area pembuka di belakang kartu; kosongkan untuk memakai gradasi bawaan.', 'default' => ''],
                'hero_overlay' => ['type' => 'range', 'label' => 'Lapisan Warna Kartu', 'description' => 'Atur gelap-terang lapisan warna di atas kartu pembuka.', 'default' => '0.10', 'min' => '0', 'max' => '0.60', 'step' => '0.05'],
                'section_background_event' => ['type' => 'image', 'label' => 'Latar Acara', 'description' => 'Gambar latar untuk detail acara dan lokasi.', 'default' => ''],
                'section_background_gallery' => ['type' => 'image', 'label' => 'Latar Galeri', 'description' => 'Gambar latar untuk galeri foto.', 'default' => ''],
                'section_background_rsvp' => ['type' => 'image', 'label' => 'Latar Konfirmasi Kehadiran', 'description' => 'Gambar latar untuk formulir konfirmasi kehadiran.', 'default' => ''],
                'ornament_left' => ['type' => 'image', 'label' => 'Ornamen Kiri', 'description' => 'Media canonical opsional untuk ornamen sudut kiri.', 'default' => ''],
                'ornament_right' => ['type' => 'image', 'label' => 'Ornamen Kanan', 'description' => 'Media canonical opsional untuk ornamen sudut kanan.', 'default' => ''],
            ]
        ],
        'yami-buzzy' => [
            'id' => 'yami-buzzy',
            'name' => 'Yami Buzzy',
            'label' => 'Yami Buzzy',
            'description' => 'Undangan editorial modern dengan welcome modal, countdown, kisah cinta, galeri, video, hadiah, dan RSVP.',
            'version' => '1.0.0',
            'author' => 'Tynab source adaptation',
            'source' => 'https://github.com/Tynab/Yami-Buzzy',
            'license' => 'Source repository license; adapted to project CMS contract',
            'category' => 'editorial-storytelling',
            'values' => [
                'primary_color' => '#ad7c69', 'secondary_color' => '#f3e8e2', 'accent_color' => '#ad7c69',
                'background_color' => '#fffdfb', 'paper_color' => '#ffffff', 'muted_color' => '#82727a',
                'text_color' => '#343039', 'link_color' => '#ad7c69', 'button_style' => 'square',
                'border_radius' => '0', 'shadow' => '0 18px 50px rgba(53,38,35,.12)', 'container_width' => '1120px',
                'section_spacing' => '110px', 'heading_font' => 'Gilroy, Arial, sans-serif', 'body_font' => 'Gilroy, Arial, sans-serif', 'font_size_base' => '16px'
            ],
            'schema' => [
                'opening_greeting' => ['type' => 'textarea', 'label' => 'Salam Pembuka', 'description' => 'Teks salam yang tampil pada welcome modal dan hero.', 'default' => 'Bismillahirrahmanirrahim']
            ],
            'capabilities' => [
                'content' => ['wedding', 'parents', 'schedule', 'countdown', 'gallery', 'music', 'gift', 'maps', 'seo', 'whatsapp', 'rsvp', 'messages', 'story', 'guest_name', 'media', 'calendar', 'sections'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation'],
            'visual_capabilities' => [
                'accent_color' => ['type' => 'color', 'label' => 'Warna Aksen', 'description' => 'Warna aksen tombol, link, dan timeline Yami Buzzy.', 'default' => '#ad7c69'],
                'heading_font' => ['type' => 'font', 'label' => 'Font Judul', 'description' => 'Font display untuk judul dan nama pasangan.', 'default' => 'Gilroy, Arial, sans-serif', 'options' => ['Gilroy, Arial, sans-serif' => 'Gilroy', 'Georgia, serif' => 'Georgia']],
                'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font isi untuk detail undangan.', 'default' => 'Gilroy, Arial, sans-serif', 'options' => ['Gilroy, Arial, sans-serif' => 'Gilroy', 'system-ui, sans-serif' => 'System UI', 'Arial, sans-serif' => 'Arial']],
                'hero_background' => ['type' => 'image', 'label' => 'Latar Hero', 'description' => 'Media canonical untuk latar hero utama.', 'default' => ''],
                'welcome_background' => ['type' => 'image', 'label' => 'Latar Welcome', 'description' => 'Media canonical untuk welcome modal; kosongkan untuk memakai latar hero.', 'default' => ''],
                'section_background_home' => ['type' => 'image', 'label' => 'Latar Beranda', 'description' => 'Media canonical opsional untuk section Beranda.', 'default' => ''],
                'section_background_couple' => ['type' => 'image', 'label' => 'Latar Mempelai', 'description' => 'Media canonical opsional untuk section Mempelai.', 'default' => ''],
                'section_background_event' => ['type' => 'image', 'label' => 'Latar Acara', 'description' => 'Gambar latar untuk detail acara dan lokasi.', 'default' => ''],
                'section_background_story' => ['type' => 'image', 'label' => 'Latar Kisah Kami', 'description' => 'Gambar latar untuk bagian cerita perjalanan pasangan.', 'default' => ''],
                'section_background_gallery' => ['type' => 'image', 'label' => 'Latar Galeri', 'description' => 'Gambar latar untuk kumpulan foto.', 'default' => ''],
                'section_background_video' => ['type' => 'image', 'label' => 'Latar Video', 'description' => 'Gambar latar untuk bagian video.', 'default' => ''],
                'section_background_gift' => ['type' => 'image', 'label' => 'Latar Hadiah', 'description' => 'Gambar latar untuk bagian tanda kasih atau hadiah.', 'default' => ''],
                'section_background_invitation' => ['type' => 'image', 'label' => 'Latar Lokasi', 'description' => 'Gambar latar untuk detail undangan dan lokasi acara.', 'default' => ''],
                'section_background_rsvp' => ['type' => 'image', 'label' => 'Latar Konfirmasi Kehadiran', 'description' => 'Gambar latar untuk formulir konfirmasi kehadiran.', 'default' => ''],
                'section_background_closing' => ['type' => 'image', 'label' => 'Latar Penutup', 'description' => 'Gambar latar untuk bagian ucapan terima kasih.', 'default' => ''],
                'hero_overlay' => ['type' => 'range', 'label' => 'Lapisan Gelap Pembuka', 'description' => 'Kekuatan overlay gelap pada latar hero.', 'default' => '0.28', 'min' => '0', 'max' => '0.75', 'step' => '0.05'],
            ]
        ],
        'rainier' => [
            'id' => 'rainier',
            'name' => 'Rainier',
            'label' => 'Rainier',
            'description' => 'Layout editorial modern dengan fokus pada hero split, ruang yang lebih dramatis, dan panel informasi yang terstruktur seperti karya seni.',
            'version' => '1.0.0',
            'author' => 'Rainier adaptation',
            'source' => 'Rainier-PS/Invitation-Template',
            'license' => 'Unverified external source; adapted in-project without copying source code',
            'category' => 'editorial-modern',
            'values' => [
                'primary_color' => '#b8655d',
                'secondary_color' => '#e6d7cd',
                'accent_color' => '#7a8c7e',
                'background_color' => '#f9f4f1',
                'paper_color' => '#fffdfb',
                'muted_color' => '#5c5255',
                'text_color' => '#1d1b22',
                'link_color' => '#b8655d',
                'button_style' => 'pill',
                'border_radius' => '20px',
                'shadow' => '0 18px 40px rgba(23,20,28,.12)',
                'container_width' => '1200px',
                'section_spacing' => '92px',
                'heading_font' => 'Georgia, serif',
                'body_font' => 'Segoe UI, sans-serif',
                'font_size_base' => '16px'
            ],
            'schema' => [
                'glass_opacity' => [
                    'type' => 'text',
                    'label' => 'Kejelasan Panel Kaca',
                    'description' => 'Tingkat transparansi panel kaca (contoh: 0.85).',
                    'default' => '0.85'
                ],
                'show_bismillah' => [
                    'type' => 'boolean',
                    'label' => 'Tampilkan Bismillah',
                    'description' => 'Tampilkan lafaz Bismillah di awal.',
                    'default' => true
                ],
                'hero_accent_color' => [
                    'type' => 'color',
                    'label' => 'Warna Aksen Kustom Hero',
                    'description' => 'Aksen warna khusus pada hero Rainier.',
                    'default' => '#b8655d'
                ],
                'quote_note' => [
                    'type' => 'textarea',
                    'label' => 'Catatan Editorial Quote',
                    'description' => 'Teks kutipan/catatan tambahan berformat multi-baris.',
                    'default' => ''
                ],
                'opening_greeting' => [
                    'type' => 'textarea',
                    'label' => 'Salam Pembuka',
                    'description' => 'Teks salam pembuka yang tampil di awal preset.',
                    'default' => 'Bismillahirrahmanirrahim'
                ]
            ],
            'capabilities' => [
                'content' => ['wedding', 'schedule', 'countdown', 'gallery', 'music', 'parents', 'gift', 'maps', 'seo', 'whatsapp', 'rsvp', 'sections'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation'],
            'visual_capabilities' => [
                'accent_color' => ['type' => 'color', 'label' => 'Warna Aksen', 'description' => 'Warna aksen Rainier.', 'default' => '#b8655d'],
                'heading_font' => ['type' => 'font', 'label' => 'Font Judul', 'description' => 'Font heading editorial Rainier.', 'default' => 'Cormorant Garamond, serif', 'options' => ['Cormorant Garamond, serif' => 'Cormorant Garamond', 'Georgia, serif' => 'Georgia']],
                'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font isi Rainier.', 'default' => 'Outfit, sans-serif', 'options' => ['Outfit, sans-serif' => 'Outfit', 'system-ui, sans-serif' => 'System UI', 'Arial, sans-serif' => 'Arial']],
                'hero_background' => ['type' => 'image', 'label' => 'Latar Pembuka', 'description' => 'Gambar utama pada bagian pembuka. Kosongkan untuk memakai cover bawaan.', 'default' => ''],
                'section_background_event_details' => ['type' => 'image', 'label' => 'Latar Detail Acara', 'description' => 'Gambar latar untuk tanggal, waktu, dan lokasi acara.', 'default' => ''],
                'section_background_schedule' => ['type' => 'image', 'label' => 'Latar Jadwal', 'description' => 'Gambar latar untuk rangkaian jadwal acara.', 'default' => ''],
                'section_background_quotes' => ['type' => 'image', 'label' => 'Latar Kata-Kata', 'description' => 'Gambar latar untuk kutipan dan cerita singkat.', 'default' => ''],
                'section_background_rsvp' => ['type' => 'image', 'label' => 'Latar Konfirmasi Kehadiran', 'description' => 'Gambar latar untuk bagian konfirmasi kehadiran.', 'default' => ''],
                'glass_opacity' => ['type' => 'range', 'label' => 'Kejelasan Panel Kaca', 'description' => 'Transparansi panel kaca Rainier.', 'default' => '0.40', 'min' => '0.20', 'max' => '0.90', 'step' => '0.05'],
            ]
        ],
        'parang' => [
            'id' => 'parang',
            'name' => 'Parang',
            'label' => 'Parang',
            'description' => 'Nuansa Manten Jawi dengan pola parang, ornamen gunungan, panel cream-gold, dan navigasi editorial responsif.',
            'version' => '1.0.0',
            'author' => 'Parang adaptation',
            'source' => 'User-provided HTML design reference',
            'license' => 'User-provided design reference; assets retained as supplied',
            'category' => 'javanese-heritage',
            'values' => [
                'primary_color' => '#221002',
                'secondary_color' => '#7b5902',
                'accent_color' => '#C49A45',
                'background_color' => '#fff8f2',
                'paper_color' => '#F0E3CE',
                'muted_color' => '#4f453e',
                'text_color' => '#211b0e',
                'link_color' => '#7b5902',
                'button_style' => 'square',
                'border_radius' => '12px',
                'shadow' => '0 24px 48px rgba(34,16,2,.20)',
                'container_width' => '1200px',
                'section_spacing' => '80px',
                'heading_font' => 'Libre Caslon Text, serif',
                'body_font' => 'Manrope, sans-serif',
                'font_size_base' => '16px'
            ],
            'schema' => [
                'opening_greeting' => [
                    'type' => 'textarea',
                    'label' => 'Salam Pembuka',
                    'description' => 'Teks salam pembuka yang tampil di awal preset.',
                    'default' => 'Bismillahirrahmanirrahim'
                ]
            ],
            'capabilities' => [
                'content' => ['wedding', 'parents', 'schedule', 'countdown', 'gallery', 'music', 'gift', 'maps', 'rsvp', 'story', 'guest_name', 'media', 'seo', 'whatsapp', 'calendar', 'sections'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation'],
            'visual_capabilities' => [
                'accent_color' => ['type' => 'color', 'label' => 'Aksen Emas', 'description' => 'Aksen emas untuk border, tombol, dan ornamen Parang.', 'default' => '#C49A45'],
                'heading_font' => ['type' => 'font', 'label' => 'Font Judul', 'description' => 'Font editorial Libre Caslon Text untuk identitas Manten Jawi.', 'default' => 'Libre Caslon Text, serif', 'options' => ['Libre Caslon Text, serif' => 'Libre Caslon Text', 'Georgia, serif' => 'Georgia']],
                'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font Manrope untuk isi, navigasi, form, dan detail acara.', 'default' => 'Manrope, sans-serif', 'options' => ['Manrope, sans-serif' => 'Manrope', 'system-ui, sans-serif' => 'System UI', 'Arial, sans-serif' => 'Arial']],
                'hero_background' => ['type' => 'image', 'label' => 'Pola Latar Parang', 'description' => 'Gambar pola utama halaman. Kosongkan untuk memakai pola bawaan.', 'default' => 'themes/parang/assets/parang-pattern.webp'],
                'section_background_home' => ['type' => 'image', 'label' => 'Latar Beranda', 'description' => 'Gambar latar tambahan untuk bagian pembuka.', 'default' => ''],
                'section_background_gallery' => ['type' => 'image', 'label' => 'Latar Galeri', 'description' => 'Gambar latar tambahan untuk bagian galeri.', 'default' => ''],
                'section_background_location' => ['type' => 'image', 'label' => 'Latar Lokasi', 'description' => 'Gambar latar tambahan untuk bagian lokasi acara.', 'default' => ''],
                'ornament_left' => ['type' => 'image', 'label' => 'Wayang Pria Kiri', 'description' => 'Asset Wayang pria di sisi kiri Hero card. Kosongkan untuk memakai asset bawaan Parang.', 'default' => 'themes/parang/assets/wayang-pria.png'],
                'ornament_right' => ['type' => 'image', 'label' => 'Wayang Wanita Kanan', 'description' => 'Asset Wayang wanita di sisi kanan Hero card. Kosongkan untuk memakai asset bawaan Parang.', 'default' => 'themes/parang/assets/wayang-wanita.png'],
                'ornament_top' => ['type' => 'image', 'label' => 'Gunungan Hero', 'description' => 'Asset Gunungan di atas Hero card. Kosongkan untuk memakai asset bawaan Parang.', 'default' => 'themes/parang/assets/gunungan-hero.png'],
                'ornament_top_width' => ['type' => 'range', 'label' => 'Lebar Gunungan Hero', 'description' => 'Atur lebar Gunungan Hero tanpa mengubah tinggi kotaknya.', 'default' => '192', 'min' => '128', 'max' => '320', 'step' => '1'],
                'ornament_top_offset_y' => ['type' => 'range', 'label' => 'Posisi Vertikal Gunungan Hero', 'description' => 'Geser Gunungan Hero ke atas atau bawah dalam piksel.', 'default' => '-128', 'min' => '-240', 'max' => '40', 'step' => '1'],
                'ornament_side_offset_x' => ['type' => 'range', 'label' => 'Jarak Wayang dari Hero card', 'description' => 'Atur jarak horizontal Wayang agar tampak memegang sisi Hero card.', 'default' => '-360', 'min' => '-640', 'max' => '40', 'step' => '1'],
                'ornament_side_offset_y' => ['type' => 'range', 'label' => 'Posisi Vertikal Wayang', 'description' => 'Atur posisi vertikal kedua ornamen Wayang dalam persen.', 'default' => '50', 'min' => '0', 'max' => '100', 'step' => '1'],
                'ornament_side_height_ratio' => ['type' => 'range', 'label' => 'Ketinggian Wayang', 'description' => 'Atur ketinggian Wayang sebagai persentase tinggi Hero card.', 'default' => '70', 'min' => '45', 'max' => '85', 'step' => '1'],
                'ornament_side_size' => ['type' => 'range', 'label' => 'Batas Lebar Wayang', 'description' => 'Atur batas lebar Wayang; tinggi visual tetap mengikuti sekitar 70% Hero card.', 'default' => '480', 'min' => '224', 'max' => '640', 'step' => '1']
            ]
        ],
        'pawiwahan' => [
            'id' => 'pawiwahan',
            'name' => 'Pawiwahan',
            'label' => 'Pawiwahan',
            'description' => 'Template Pawiwahan Bali berbasis thema-1 dengan navbar Bootstrap, carousel, modal pembuka, countdown jQuery, galeri, lokasi, hadiah, dan pesan.',
            'version' => '1.0.0',
            'author' => 'DE Juna adaptation',
            'source' => 'parta99/pawiwahan',
            'license' => 'MIT (Copyright (c) 2021 DE Juna; adapted in-project)',
            'category' => 'balinese-classic',
            'values' => [
                'primary_color' => '#d77fa1',
                'secondary_color' => '#c996cc',
                'accent_color' => '#ec7272',
                'background_color' => '#f7edf2',
                'paper_color' => '#ffffff',
                'muted_color' => '#6d5a62',
                'text_color' => '#372d36',
                'link_color' => '#8b4f70',
                'button_style' => 'rounded',
                'border_radius' => '6px',
                'shadow' => '5px 5px 10px #caced1,-5px -5px 10px white',
                'container_width' => '1140px',
                'section_spacing' => '56px',
                'heading_font' => 'Tangerine, cursive',
                'body_font' => 'Raleway, sans-serif',
                'font_size_base' => '16px'
            ],
            'schema' => [
                'hero_background' => ['type' => 'image', 'label' => 'Latar Hero Pawiwahan', 'description' => 'Path media atau URL. Kosongkan untuk memakai source default Pawiwahan.', 'default' => ''],
                'show_protocol' => ['type' => 'boolean', 'label' => 'Tampilkan Catatan Acara', 'description' => 'Tampilkan catatan sumber yang kompatibel dengan bagian protokol Pawiwahan.', 'default' => true],
                'opening_greeting' => [
                    'type' => 'textarea',
                    'label' => 'Salam Pembuka',
                    'description' => 'Teks salam pembuka yang tampil di bagian awal Pawiwahan.',
                    'default' => 'OM Swastiastu'
                ]
            ],
            'capabilities' => [
                'content' => ['wedding', 'parents', 'schedule', 'countdown', 'gallery', 'music', 'gift', 'maps', 'rsvp', 'messages', 'guest_name', 'media', 'seo', 'whatsapp', 'calendar'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'carousel', 'navigation', 'countdown', 'modal', 'footer', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'carousel', 'navigation', 'countdown', 'modal', 'footer', 'animation'],
            'visual_capabilities' => [
                'accent_color' => ['type' => 'color', 'label' => 'Aksen Pawiwahan', 'description' => 'Aksen tombol dan elemen interaktif Pawiwahan.', 'default' => '#ec7272'],
                'heading_font' => ['type' => 'font', 'label' => 'Font Judul', 'description' => 'Font display sumber Pawiwahan.', 'default' => 'Tangerine, cursive', 'options' => ['Tangerine, cursive' => 'Tangerine', 'Beau Rivage, cursive' => 'Beau Rivage', 'Georgia, serif' => 'Georgia']],
                'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font isi sumber Pawiwahan.', 'default' => 'Raleway, sans-serif', 'options' => ['Raleway, sans-serif' => 'Raleway', 'system-ui, sans-serif' => 'System UI', 'Arial, sans-serif' => 'Arial']],
                'hero_background' => ['type' => 'image', 'label' => 'Latar Pembuka', 'description' => 'Gambar utama pada pembuka undangan. Kosongkan untuk memakai foto bawaan.', 'default' => 'themes/pawiwahan/assets/hero-source.jpg'],
                'welcome_background' => ['type' => 'image', 'label' => 'Latar Sampul Pembuka', 'description' => 'Gambar untuk layar pembuka sebelum undangan dibuka.', 'default' => ''],
                'section_background_gallery' => ['type' => 'image', 'label' => 'Latar Galeri', 'description' => 'Gambar latar untuk bagian galeri foto.', 'default' => ''],
                'section_background_location' => ['type' => 'image', 'label' => 'Latar Lokasi', 'description' => 'Gambar latar untuk bagian lokasi acara.', 'default' => ''],
                'section_background_gift' => ['type' => 'image', 'label' => 'Latar Hadiah', 'description' => 'Gambar latar untuk bagian tanda kasih.', 'default' => ''],
                'section_background_messages' => ['type' => 'image', 'label' => 'Latar Ucapan', 'description' => 'Gambar latar untuk bagian pesan dan doa.', 'default' => '']
            ]
        ],
        'archak' => [
            'id' => 'archak',
            'name' => 'Archak',
            'label' => 'Archak',
            'description' => 'Layout modern dengan hero dua kolom, detail ornamental yang lebih terstruktur, dan panel informasi yang terasa seperti editorial wedding brief.',
            'version' => '1.0.0',
            'author' => 'Archak adaptation',
            'source' => 'archakNath/wedding-invitation-website',
            'license' => 'Unverified external source; adapted in-project without copying source code',
            'category' => 'editorial-luxury',
            'values' => [
                'primary_color' => '#8c5a4d',
                'secondary_color' => '#f0d8c4',
                'accent_color' => '#6e7c63',
                'background_color' => '#f6f1eb',
                'paper_color' => '#fffdfb',
                'muted_color' => '#5d5350',
                'text_color' => '#211d1a',
                'link_color' => '#8c5a4d',
                'button_style' => 'rounded',
                'border_radius' => '18px',
                'shadow' => '0 18px 46px rgba(30,25,24,.09)',
                'container_width' => '1200px',
                'section_spacing' => '94px',
                'heading_font' => 'Georgia, serif',
                'body_font' => 'Segoe UI, sans-serif',
                'font_size_base' => '16px'
            ],
            'schema' => [
                'enable_parallax' => [
                    'type' => 'boolean',
                    'label' => 'Efek Parallax',
                    'description' => 'Aktifkan pergerakan efek parallax pada background.',
                    'default' => true
                ],
                'enable_preloader' => [
                    'type' => 'boolean',
                    'label' => 'Tampilkan Layar Pembuka',
                    'description' => 'Tampilkan animasi pemuatan layar pembuka.',
                    'default' => true
                ],
                'divider_style' => [
                    'type' => 'select',
                    'label' => 'Gaya Pembatas (Divider)',
                    'description' => 'Bentuk gaya ornamen pembatas antarseksi.',
                    'options' => [
                        'ornament' => 'Ornamen Mewah',
                        'line' => 'Garis Minimalis',
                        'none' => 'Tanpa Pembatas'
                    ],
                    'default' => 'ornament'
                ],
                'header_badge_image' => [
                    'type' => 'image',
                    'label' => 'Lencana/Emblem Header',
                    'description' => 'Pilih/unggah gambar emblem khusus untuk seksi header.',
                    'default' => ''
                ],
                'archak_welcome_msg' => [
                    'type' => 'textarea',
                    'label' => 'Pesan Sambutan Archak',
                    'description' => 'Pesan ucapan multi-baris bergaya editorial Archak.',
                    'default' => ''
                ],
                'opening_greeting' => [
                    'type' => 'textarea',
                    'label' => 'Salam Pembuka',
                    'description' => 'Teks salam pembuka yang tampil di awal preset.',
                    'default' => 'Bismillahirrahmanirrahim'
                ]
            ],
            'capabilities' => [
                'content' => ['wedding', 'schedule', 'countdown', 'gallery', 'music', 'parents', 'gift', 'maps', 'seo', 'whatsapp', 'rsvp', 'sections'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'navigation', 'footer', 'spacing', 'animation'],
            'visual_capabilities' => [
                'accent_color' => ['type' => 'color', 'label' => 'Warna Aksen', 'description' => 'Warna aksen Archak.', 'default' => '#8c5a4d'],
                'heading_font' => ['type' => 'font', 'label' => 'Font Judul', 'description' => 'Font heading identitas Archak.', 'default' => 'Cinzel, serif', 'options' => ['Cinzel, serif' => 'Cinzel', 'Georgia, serif' => 'Georgia']],
                'body_font' => ['type' => 'font', 'label' => 'Font Isi', 'description' => 'Font isi readable Archak.', 'default' => 'Quicksand, sans-serif', 'options' => ['Quicksand, sans-serif' => 'Quicksand', 'system-ui, sans-serif' => 'System UI', 'Arial, sans-serif' => 'Arial']],
                'hero_background' => ['type' => 'image', 'label' => 'Latar Pembuka', 'description' => 'Gambar utama pada bagian pembuka. Kosongkan untuk memakai foto pasangan.', 'default' => ''],
                'section_background_timeline' => ['type' => 'image', 'label' => 'Latar Rangkaian Acara', 'description' => 'Gambar latar untuk bagian rangkaian acara.', 'default' => ''],
                'section_background_gallery' => ['type' => 'image', 'label' => 'Latar Galeri', 'description' => 'Gambar latar untuk bagian galeri foto.', 'default' => ''],
                'section_background_stay' => ['type' => 'image', 'label' => 'Latar Perjalanan dan Penginapan', 'description' => 'Gambar latar untuk informasi perjalanan dan tempat menginap.', 'default' => ''],
                'section_background_registry' => ['type' => 'image', 'label' => 'Latar Janji dan Hadiah', 'description' => 'Gambar latar untuk bagian janji dan informasi hadiah.', 'default' => ''],
                'header_badge' => ['type' => 'image', 'label' => 'Emblem Header', 'description' => 'Gambar emblem kecil di bagian atas undangan. Kosongkan untuk memakai bawaan.', 'default' => ''],
                'hero_title_scale' => ['type' => 'range', 'label' => 'Skala Judul Hero', 'description' => 'Skala nama pasangan di hero.', 'default' => '1', 'min' => '0.85', 'max' => '1.10', 'step' => '0.05'],
            ]
                ]
    ];
    foreach ($registry as &$preset) {
        if (!isset($preset['values']) || !is_array($preset['values'])) continue;
        $presetKey = (string)($preset['id'] ?? '');
        $existingVisualCapabilities = (array)($preset['visual_capabilities'] ?? []);
        $preset['visual_capabilities'] = array_merge(
            $existingVisualCapabilities,
            theme_common_visual_capabilities($preset['values'], $existingVisualCapabilities)
        );
    }
    unset($preset);
    return $registry;
}
function theme_builtin_preset_keys(): array {
    return ['dewankl', 'rainier', 'archak', 'parang', 'pawiwahan', 'shubh-vivah', 'yami-buzzy'];
}

function theme_presets(): array {
    $registry = array_intersect_key(theme_registry(), array_flip(theme_builtin_preset_keys()));
    foreach ($registry as $key => $preset) {
        $registry[$key] = array_replace([
            'label' => $preset['name'] ?? ucfirst((string)$key),
            'description' => 'Preset tema dengan gaya ' . ($preset['category'] ?? 'standar'),
        ], $preset);
    }
    return $registry;
}

function get_theme_mode(array $config = []): string {
    $mode = trim((string)($config['theme']['mode'] ?? ''));
    if ($mode === 'preset' || $mode === 'custom') {
        return $mode;
    }

    $preset = trim((string)($config['theme']['theme_preset'] ?? ''));
    if ($preset === 'custom' || $preset === '') {
        return 'custom';
    }

    return 'preset';
}

function get_active_theme_meta(array $config = []): array {
    $mode = get_theme_mode($config);
    if ($mode === 'custom') {
        return [
            'id' => 'custom',
            'name' => 'Custom',
            'label' => 'Custom',
            'description' => 'Tema kustom berdasarkan parameter manual.',
            'version' => '1.0.0',
            'author' => 'Admin',
            'source' => 'CMS manual override',
            'license' => 'Project-internal',
            'category' => 'custom',
            'capabilities' => [
                'content' => ['wedding', 'schedule', 'countdown', 'gallery', 'music', 'parents', 'gift', 'maps', 'seo', 'whatsapp', 'rsvp', 'sections'],
                'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'gallery_layout', 'navigation', 'footer', 'spacing', 'animation']
            ],
            'presentation' => ['colors', 'typography', 'hero', 'background', 'cards', 'gallery_layout', 'navigation', 'footer', 'spacing', 'animation']
        ];
    }

    $selectedPreset = trim((string)($config['theme']['theme_preset'] ?? 'dewankl')) ?: 'dewankl';
    $registry = theme_registry();
    if (isset($registry[$selectedPreset])) {
        return $registry[$selectedPreset];
    }
    return [
        'id' => 'custom',
        'name' => 'Custom',
        'label' => 'Custom',
        'description' => 'Tema kustom berdasarkan parameter manual.',
        'version' => '1.0.0',
        'author' => 'Admin',
        'source' => 'CMS manual override',
        'license' => 'Project-internal',
        'category' => 'custom'
    ];
}

function resolve_theme_preset(array $theme, string $presetKey): array {
    $presets = theme_presets();
    if (!isset($presets[$presetKey])) {
        return $theme;
    }
    return array_replace($theme, $presets[$presetKey]['values'], ['theme_preset' => $presetKey]);
}

function apply_theme_preset(array $theme, string $presetKey): array {
    return resolve_theme_preset($theme, $presetKey);
}

/** Resolve the CMS-native Custom theme state without losing legacy configs. */
function theme_custom_config(array $config): array {
    $defaults = (array)(config_defaults()['theme'] ?? []);
    $saved = $config['theme_custom'] ?? null;
    if (!is_array($saved) || $saved === []) {
        $saved = get_theme_mode($config) === 'custom' ? (array)($config['theme'] ?? []) : $defaults;
    }
    $theme = array_replace($defaults, $saved);
    $theme['mode'] = 'custom';
    $theme['theme_preset'] = 'custom';
    return $theme;
}

function ensure_upload_dirs(): void {
    $tenantRoot = tenant_upload_root();
    if ($tenantRoot === '') return;
    foreach (['cover', 'music', 'gallery', 'background', 'love_story', 'theme_assets'] as $kind) {
        $dir = tenant_upload_dir($kind);
        if ($dir !== '' && !is_dir($dir)) @mkdir($dir, 0755, true);
    }
}

function load_config(): array {
    $defaults = config_defaults();
    $tenantConfigRaw = null;
    $tenant = current_tenant(false);
    if (is_array($tenant)) {
        try {
            $db = tenant_database(true);
            $stmt = $db->prepare('SELECT config_json FROM tenant_configs WHERE tenant_id = :tenant_id LIMIT 1');
            $stmt->bindValue(':tenant_id', (int)$tenant['id'], SQLITE3_INTEGER);
            $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            if (is_array($row) && isset($row['config_json'])) $tenantConfigRaw = (string)$row['config_json'];
            $db->close();
        } catch (Throwable $e) {
            error_log('Tenant config read failed: ' . $e->getMessage());
        }
    }
    if ($tenantConfigRaw === null) {
        if (function_exists('theme_contract_registry')) {
            $defaults['theme_sections'] = [];
            foreach (array_keys(theme_contract_registry()) as $presetKey) {
                $defaults['theme_sections'][$presetKey] = theme_contract_default_sections($presetKey);
            }
        }
        return $defaults;
    }
    $raw = $tenantConfigRaw;
    $config = json_decode($raw, true);
    if (!is_array($config)) {
        return $defaults;
    }
    $config = array_replace_recursive($defaults, $config);
    if (!is_array($config['gallery']['items'])) {
        $config['gallery']['items'] = [];
    }
    if (!is_array($config['media']['background_sections'])) {
        $config['media']['background_sections'] = [];
    }
    if (!is_array($config['love_story']['items'])) {
        $config['love_story']['items'] = [];
    }
    if (!is_array($config['sections'])) {
        $config['sections'] = $defaults['sections'];
    } else {
        foreach ($config['sections'] as &$section) {
            if (!is_array($section)) {
                continue;
            }
            $section['id'] = normalize_section_id((string)($section['id'] ?? ''));
            if (!isset($section['custom_title'])) {
                $section['custom_title'] = '';
            }
            if (!isset($section['custom_subtitle'])) {
                $section['custom_subtitle'] = '';
            }
        }
        unset($section);
    }
    if (empty($config['theme']['theme_preset'])) {
        $config['theme']['theme_preset'] = 'dewankl';
    }
    if (empty($config['theme']['mode'])) {
        $config['theme']['mode'] = get_theme_mode($config);
    }
    if (!in_array($config['theme']['mode'] ?? '', ['preset', 'custom'], true)) {
        $config['theme']['mode'] = get_theme_mode($config);
    }
    // Preserve a dedicated Custom snapshot while keeping old config.json files valid.
    if (!is_array($config['theme_custom'] ?? null) || $config['theme_custom'] === []) {
        $config['theme_custom'] = theme_custom_config($config);
    }
    // Ensure hero settings exist for backward compatibility
    if (!isset($config['theme']['hero_height'])) {
        $config['theme']['hero_height'] = '100vh';
    }
    if (!isset($config['theme']['hero_vertical_alignment'])) {
        $config['theme']['hero_vertical_alignment'] = 'center';
    }
    if (!isset($config['theme']['hero_content_width'])) {
        $config['theme']['hero_content_width'] = '900px';
    }
    if (!isset($config['theme']['hero_image_fit'])) {
        $config['theme']['hero_image_fit'] = 'cover';
    }
    if (!isset($config['theme']['hero_image_position'])) {
        $config['theme']['hero_image_position'] = 'center';
    }
    if (!isset($config['theme']['mobile_hero_height'])) {
        $config['theme']['mobile_hero_height'] = '85vh';
    }
    if (!isset($config['theme']['mobile_hero_vertical_alignment'])) {
        $config['theme']['mobile_hero_vertical_alignment'] = 'center';
    }
    if (!isset($config['theme']['mobile_hero_content_width'])) {
        $config['theme']['mobile_hero_content_width'] = '100%';
    }
    if (!isset($config['theme']['mobile_hero_image_fit'])) {
        $config['theme']['mobile_hero_image_fit'] = 'cover';
    }
    if (!isset($config['theme']['mobile_hero_image_position'])) {
        $config['theme']['mobile_hero_image_position'] = 'center top';
    }
    // Ensure buttons settings exist for backward compatibility
    if (!isset($config['buttons']['mobile_layout'])) {
        $config['buttons']['mobile_layout'] = '2-columns';
    }
    if (!is_array($config['theme_options'] ?? null)) {
        $config['theme_options'] = $defaults['theme_options'];
    } else {
        $config['theme_options'] = array_replace_recursive($defaults['theme_options'], $config['theme_options']);
    }

    // Built-in themes keep their own section contract. The legacy global
    // `sections` array remains untouched for Custom mode and old data.
    if (!is_array($config['theme_sections'] ?? null)) {
        $config['theme_sections'] = [];
    }
    if (function_exists('theme_contract_registry')) {
        foreach (array_keys(theme_contract_registry()) as $presetKey) {
            if (!isset($config['theme_sections'][$presetKey]) || !is_array($config['theme_sections'][$presetKey])) {
                $config['theme_sections'][$presetKey] = theme_contract_default_sections($presetKey);
            }
        }
    }
    if (empty($config['schedule']['countdown_target'])) {
        $config['schedule']['countdown_target'] = compute_countdown_target($config['schedule']);
    }
    if (empty($config['story']) && !empty($config['love_story']['items'])) {
        $config['story'] = array_map(function($item) {
            return [
                'date' => $item['event_date'] ?? ($item['date'] ?? ''),
                'title' => $item['title'] ?? '',
                'description' => $item['description'] ?? '',
            ];
        }, $config['love_story']['items']);
    }
    return $config;
}

function compute_countdown_target(array $schedule): string {
    $date = trim((string)($schedule['akad_date'] ?? ''));
    $time = trim((string)($schedule['akad_time'] ?? ''));
    $timezone = trim((string)($schedule['timezone'] ?? 'Asia/Jakarta')) ?: 'Asia/Jakarta';
    if ($date === '' || $time === '') {
        return '';
    }
    try {
        $dt = new DateTimeImmutable($date . ' ' . $time, new DateTimeZone($timezone));
        return $dt->format('Y-m-d\TH:i:sP');
    } catch (Throwable $e) {
        return '';
    }
}

function save_config(array $config): bool {
    $config = array_replace_recursive(config_defaults(), $config);
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) return false;
    $tenant = current_tenant(false);
    if (!is_array($tenant)) return false;
    try {
        $db = tenant_database(false);
        $stmt = $db->prepare("INSERT INTO tenant_configs (tenant_id, config_json, custom_css, event_ics, updated_at) VALUES (:tenant_id, :config_json, :custom_css, :event_ics, CURRENT_TIMESTAMP) ON CONFLICT(tenant_id) DO UPDATE SET config_json = excluded.config_json, custom_css = excluded.custom_css, event_ics = excluded.event_ics, updated_at = CURRENT_TIMESTAMP");
        $stmt->bindValue(':tenant_id', (int)$tenant['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':config_json', $json, SQLITE3_TEXT);
        $stmt->bindValue(':custom_css', (string)($config['custom_css'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':event_ics', build_event_ics($config), SQLITE3_TEXT);
        $ok = (bool)$stmt->execute();
        $db->close();
        return $ok;
    } catch (Throwable $e) {
        error_log('Tenant config save failed: ' . $e->getMessage());
        return false;
    }
}

function load_guest_links(): array {
    $tenant = current_tenant(false);
    if (is_array($tenant)) {
        try {
            $db = tenant_database(true);
            $stmt = $db->prepare('SELECT guest_name, invitation_url, created_at FROM guest_links WHERE tenant_id = :tenant_id ORDER BY id DESC');
            $stmt->bindValue(':tenant_id', (int)$tenant['id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $rows = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $rows[] = $row;
            $db->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Tenant guest-link read failed: ' . $e->getMessage());
        }
    }
    return [];
}

function load_custom_css(): string {
    $tenant = current_tenant(false);
    if (is_array($tenant)) {
        try {
            $db = tenant_database(true);
            $stmt = $db->prepare('SELECT custom_css FROM tenant_configs WHERE tenant_id = :tenant_id LIMIT 1');
            $stmt->bindValue(':tenant_id', (int)$tenant['id'], SQLITE3_INTEGER);
            $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            $db->close();
            if (is_array($row)) return (string)($row['custom_css'] ?? '');
        } catch (Throwable $e) { error_log('Tenant CSS read failed: ' . $e->getMessage()); }
    }
    return '';
}

function validate_custom_css(string $css): array {
    $css = str_replace("\0", '', $css);
    if (strlen($css) > 100000) {
        return ['valid' => false, 'message' => 'Custom CSS terlalu besar (maks 100 KB).'];
    }
    $forbiddenPatterns = [
        '/<\s*\/?\s*script\b/i' => 'Tag script tidak diizinkan.',
        '/<\?php|\?>/i' => 'Kode PHP tidak diizinkan.',
        '/<\s*\/?\s*[a-z][^>]*>/i' => 'Tag HTML tidak diizinkan.',
        '/javascript\s*:/i' => 'URL javascript: tidak diizinkan.',
        '/\bon[a-z]+\s*=/i' => 'Inline event handler tidak diizinkan.',
        '/expression\s*\(/i' => 'CSS expression tidak diizinkan.',
        '/@import\b/i' => '@import tidak diizinkan pada Custom CSS.'
    ];
    foreach ($forbiddenPatterns as $pattern => $message) {
        if (preg_match($pattern, $css)) {
            return ['valid' => false, 'message' => $message];
        }
    }

    $withoutComments = preg_replace('#/\*.*?\*/#s', '', $css) ?? $css;
    $withoutStrings = preg_replace('/(["\']).*?\1/s', '', $withoutComments) ?? $withoutComments;
    $balance = 0;
    $parenBalance = 0;
    $bracketBalance = 0;
    $length = strlen($withoutStrings);
    for ($i = 0; $i < $length; $i++) {
        $char = $withoutStrings[$i];
        if ($char === '{') $balance++;
        if ($char === '}') $balance--;
        if ($char === '(') $parenBalance++;
        if ($char === ')') $parenBalance--;
        if ($char === '[') $bracketBalance++;
        if ($char === ']') $bracketBalance--;
        if ($balance < 0 || $parenBalance < 0 || $bracketBalance < 0) {
            return ['valid' => false, 'message' => 'Struktur CSS tidak valid.'];
        }
    }
    if ($balance !== 0 || $parenBalance !== 0 || $bracketBalance !== 0) {
        return ['valid' => false, 'message' => 'Kurung CSS tidak seimbang.'];
    }
    if (trim($css) !== '' && !preg_match('/[{}:;]/', $css)) {
        return ['valid' => false, 'message' => 'Custom CSS harus berisi aturan CSS yang valid.'];
    }
    return ['valid' => true, 'message' => ''];
}

function save_custom_css(string $css): bool {
    $tenant = current_tenant(false);
    if (!is_array($tenant)) return false;
    if (is_array($tenant)) {
        try {
            $db = tenant_database(false);
            $stmt = $db->prepare('UPDATE tenant_configs SET custom_css = :custom_css, updated_at = CURRENT_TIMESTAMP WHERE tenant_id = :tenant_id');
            $stmt->bindValue(':custom_css', $css, SQLITE3_TEXT);
            $stmt->bindValue(':tenant_id', (int)$tenant['id'], SQLITE3_INTEGER);
            $stmt->execute();
            $db->close();
        } catch (Throwable $e) { error_log('Tenant CSS save failed: ' . $e->getMessage()); return false; }
    }
    return true;
}

function save_guest_links(array $links): bool {
    $data = array_values($links);
    $tenant = current_tenant(false);
    if (!is_array($tenant)) return false;
    if (is_array($tenant)) {
        try {
            $db = tenant_database(false);
            $tenantId = (int)$tenant['id'];
            $delete = $db->prepare('DELETE FROM guest_links WHERE tenant_id = :tenant_id');
            $delete->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
            $delete->execute();
            foreach ($data as $item) {
                if (!is_array($item)) continue;
                $insert = $db->prepare('INSERT INTO guest_links (tenant_id, guest_name, invitation_url, created_at) VALUES (:tenant_id, :guest_name, :invitation_url, :created_at)');
                $insert->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
                $insert->bindValue(':guest_name', trim((string)($item['guest_name'] ?? '')), SQLITE3_TEXT);
                $insert->bindValue(':invitation_url', trim((string)($item['invitation_url'] ?? '')), SQLITE3_TEXT);
                $insert->bindValue(':created_at', trim((string)($item['created_at'] ?? gmdate('c'))), SQLITE3_TEXT);
                $insert->execute();
            }
            $db->close();
        } catch (Throwable $e) { error_log('Tenant guest-link save failed: ' . $e->getMessage()); return false; }
    }
    return true;
}

function get_safe_config(array $config): array {
    unset($config['admin']['password_hash']);
    return $config;
}

function get_config_value(array $config, string $path, $default = '') {
    $keys = explode('.', $path);
    $value = $config;
    foreach ($keys as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return $default;
        }
        $value = $value[$key];
    }
    return $value;
}

function get_theme_option(array $config, string $presetKey, string $optionKey, $default = null) {
    return $config['theme_options'][$presetKey][$optionKey] ?? $default;
}

function set_theme_option(array &$config, string $presetKey, string $optionKey, $value): void {
    if (!isset($config['theme_options'][$presetKey])) {
        $config['theme_options'][$presetKey] = [];
    }
    $config['theme_options'][$presetKey][$optionKey] = $value;
}

function normalize_section_id(string $id): string {
    $id = strtolower(trim($id));
    $aliases = [
        'bride_groom' => 'bride_groom',
        'couple' => 'bride_groom',
        'events' => 'acara',
        'schedule' => 'acara',
        'acara' => 'acara',
        'love_story' => 'cerita',
        'story' => 'cerita',
        'cerita' => 'cerita',
        'gallery' => 'galeri',
        'galeri' => 'galeri',
        'location' => 'lokasi',
        'lokasi' => 'lokasi',
        'gift' => 'amplop',
        'amplop' => 'amplop',
        'guest_wishes' => 'messages',
        'messages' => 'messages',
        'guest_intro' => 'guest_intro',
        'guest-intro' => 'guest_intro',
        'undangan' => 'undangan',
        'opening' => 'undangan',
        'music' => 'music',
        'musik' => 'music',
        'rsvp' => 'rsvp',
        'hero' => 'hero',
        'countdown' => 'countdown',
        'footer' => 'footer'
    ];
    return $aliases[$id] ?? $id;
}

function build_full_url(string $path): string {
    $path = ltrim($path, '/');
    $config = load_config();
    return rtrim($config['site']['url'] ?? config_defaults()['site']['url'], '/') . '/' . $path;
}

function normalize_path(string $path): string {
    $path = trim($path);
    $path = str_replace(['\\', '../', './'], ['', '', ''], $path);
    return $path;
}

function generate_safe_filename(string $originalName, string $prefix = ''): string {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '') {
        $extension = 'dat';
    }
    $random = bin2hex(random_bytes(8));
    $prefix = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$prefix);
    if ($prefix !== '') {
        $prefix .= '-';
    }
    return $prefix . $random . '.' . $extension;
}

function normalize_image_orientation(string $sourcePath): ?string {
    $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return null;
    }

    $orientation = null;
    if (function_exists('exif_read_data') && in_array($ext, ['jpg', 'jpeg'], true)) {
        $exif = @exif_read_data($sourcePath, 'IFD0');
        if (is_array($exif) && isset($exif['Orientation'])) {
            $orientation = (int)$exif['Orientation'];
        }
    }

    if ($orientation === null || $orientation === 1) {
        return null;
    }

    if (!extension_loaded('gd')) {
        return null;
    }

    $image = @imagecreatefromstring(@file_get_contents($sourcePath));
    if (!$image) {
        return null;
    }

    $rotated = $image;
    switch ($orientation) {
        case 3:
            $rotated = imagerotate($image, 180, 0);
            break;
        case 6:
            $rotated = imagerotate($image, -90, 0);
            break;
        case 8:
            $rotated = imagerotate($image, 90, 0);
            break;
    }

    if ($rotated !== false) {
        $tmp = $sourcePath . '.orient.tmp';
        $ok = imagejpeg($rotated, $tmp, 90);
        imagedestroy($rotated);
        imagedestroy($image);
        if ($ok) {
            @rename($tmp, $sourcePath);
            return $sourcePath;
        }
        @unlink($tmp);
    }

    imagedestroy($image);
    return null;
}

function find_imagemagick_binary(): ?string {
    $candidates = ['magick', 'convert'];
    foreach ($candidates as $binary) {
        $output = [];
        @exec('command -v ' . escapeshellarg($binary) . ' 2>/dev/null', $output, $code);
        if ($code === 0 && !empty($output[0])) {
            return trim((string) $output[0]);
        }
    }
    return null;
}

function find_ffmpeg_binary(): ?string {
    $output = [];
    @exec('command -v ffmpeg 2>/dev/null', $output, $code);
    return $code === 0 && !empty($output[0]) ? trim((string)$output[0]) : null;
}

function find_ffprobe_binary(): ?string {
    $output = [];
    @exec('command -v ffprobe 2>/dev/null', $output, $code);
    return $code === 0 && !empty($output[0]) ? trim((string)$output[0]) : null;
}

function media_processing_temp_path(string $directory, string $extension): string {
    $extension = ltrim(strtolower(trim($extension)), '.');
    if ($extension === '') $extension = 'tmp';
    return rtrim($directory, '/\\') . '/.' . bin2hex(random_bytes(12)) . '.tmp.' . $extension;
}

function video_processing_temp_path(string $directory): string {
    return media_processing_temp_path($directory, 'mp4');
}

function audio_processing_temp_path(string $directory): string {
    return media_processing_temp_path($directory, 'mp3');
}

function probe_video_stream(string $path, bool $audio = false): ?array {
    $binary = find_ffprobe_binary();
    if ($binary === null || !is_file($path)) return null;
    $selector = $audio ? 'a:0' : 'v:0';
    $entries = $audio ? 'codec_type,codec_name,sample_rate,channels,duration' : 'codec_type,codec_name,width,height,pix_fmt,r_frame_rate,duration';
    $command = escapeshellarg($binary) . ' -v error -select_streams ' . escapeshellarg($selector) . ' -show_entries stream=' . escapeshellarg($entries) . ' -of json ' . escapeshellarg($path) . ' 2>/dev/null';
    $output = [];
    @exec($command, $output, $code);
    if ($code !== 0 || !$output) return null;
    $decoded = json_decode(implode('', $output), true);
    return is_array($decoded) && isset($decoded['streams'][0]) && is_array($decoded['streams'][0]) ? $decoded['streams'][0] : null;
}

function video_stream_fps(array $stream): float {
    $rate = trim((string)($stream['r_frame_rate'] ?? ''));
    if ($rate === '' || !str_contains($rate, '/')) return 0.0;
    [$numerator, $denominator] = array_map('floatval', explode('/', $rate, 2));
    return $denominator > 0 ? $numerator / $denominator : 0.0;
}

function verify_mp4_output(string $path, array $requirement = []): bool {
    if (!is_file($path) || filesize($path) <= 0 || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'mp4') return false;
    $mime = safe_image_mime($path);
    if (!in_array($mime, ['video/mp4', 'application/mp4', 'application/octet-stream'], true)) return false;
    $video = probe_video_stream($path);
    if ($video === null || ($video['codec_name'] ?? '') !== (string)($requirement['video_codec'] ?? 'libx264')) return false;
    $width = (int)($video['width'] ?? 0);
    $height = (int)($video['height'] ?? 0);
    if ($width < 2 || $height < 2) return false;
    if (isset($requirement['max_width']) && $width > (int)$requirement['max_width']) return false;
    if (isset($requirement['max_height']) && $height > (int)$requirement['max_height']) return false;
    if (($video['pix_fmt'] ?? '') !== (string)($requirement['pixel_format'] ?? 'yuv420p')) return false;
    $fps = video_stream_fps($video);
    if (isset($requirement['max_fps']) && $fps > (float)$requirement['max_fps'] + 0.05) return false;
    if ((float)($video['duration'] ?? 0) <= 0.05) return false;
    return true;
}

function verify_mp3_output(string $path, array $requirement = []): bool {
    if (!is_file($path) || filesize($path) <= 0 || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'mp3') return false;
    $mime = safe_image_mime($path);
    if (!in_array($mime, ['audio/mpeg', 'audio/mp3', 'application/octet-stream'], true)) return false;
    $audio = probe_video_stream($path, true);
    if ($audio === null || ($audio['codec_name'] ?? '') !== (string)($requirement['audio_codec'] ?? 'mp3')) return false;
    if (isset($requirement['sample_rate']) && (int)($audio['sample_rate'] ?? 0) !== (int)$requirement['sample_rate']) return false;
    if (isset($requirement['channels']) && (int)($audio['channels'] ?? 0) !== (int)$requirement['channels']) return false;
    return (float)($audio['duration'] ?? 0) > 0.05;
}

function run_ffmpeg_command(array $parts): array {
    $command = implode(' ', $parts);
    $process = @proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) return ['success' => false, 'stderr' => 'Unable to start FFmpeg process.', 'exit_code' => -1];
    fclose($pipes[0]);
    stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    return ['success' => $exitCode === 0, 'stderr' => (string)$stderr, 'exit_code' => $exitCode];
}

function process_audio_to_mp3(string $sourcePath, string $destinationDir, string $role = 'music', ?string $preset = null, string $originalName = ''): array {
    if (!is_file($sourcePath) || !is_readable($sourcePath)) return ['success' => false, 'error' => 'Sumber audio tidak dapat dibaca.'];
    $requirement = media_requirement($role, $preset);
    $binary = find_ffmpeg_binary();
    if ($binary === null || find_ffprobe_binary() === null) return ['success' => false, 'error' => 'FFmpeg dan FFprobe diperlukan untuk memproses musik.'];
    if (!is_dir($destinationDir) && !@mkdir($destinationDir, 0755, true)) return ['success' => false, 'error' => 'Gagal membuat direktori penyimpanan musik.'];
    $safeBase = preg_replace('/[^A-Za-z0-9_-]+/', '-', pathinfo($originalName !== '' ? $originalName : basename($sourcePath), PATHINFO_FILENAME));
    $safeBase = trim((string)$safeBase, '-_');
    if ($safeBase === '') $safeBase = 'music';
    $finalName = $safeBase . '-' . bin2hex(random_bytes(8)) . '.mp3';
    $finalPath = rtrim($destinationDir, '/\\') . '/' . $finalName;
    $temporaryPath = audio_processing_temp_path($destinationDir);
    $parts = [
        escapeshellarg($binary), '-hide_banner', '-loglevel', 'error', '-y',
        '-i', escapeshellarg($sourcePath), '-map', escapeshellarg('0:a:0'), '-vn', '-sn', '-dn',
        '-map_metadata', '-1', '-c:a', escapeshellarg((string)($requirement['audio_encoder'] ?? 'libmp3lame')),
        '-b:a', escapeshellarg((string)($requirement['audio_bitrate'] ?? '160k')),
        '-ar', (string)(int)($requirement['sample_rate'] ?? 44100), '-ac', (string)(int)($requirement['channels'] ?? 2),
        escapeshellarg($temporaryPath)
    ];
    $run = run_ffmpeg_command($parts);
    $processed = !empty($run['success']) && verify_mp3_output($temporaryPath, $requirement);
    if (!$processed || !@rename($temporaryPath, $finalPath) || !verify_mp3_output($finalPath, $requirement)) {
        if (!empty($run['stderr'])) error_log('MediaProcessor FFmpeg audio failed: ' . trim((string)$run['stderr']));
        @unlink($temporaryPath);
        @unlink($finalPath);
        return ['success' => false, 'error' => 'Gagal memproses musik menjadi MP3 terverifikasi.'];
    }
    $audio = probe_video_stream($finalPath, true);
    return ['success' => true, 'path' => $finalPath, 'name' => $finalName, 'mime' => 'audio/mpeg', 'duration' => (float)($audio['duration'] ?? 0), 'requirement' => $requirement];
}

function process_video_to_mp4(string $sourcePath, string $destinationDir, string $role = 'love_story_video', ?string $preset = null, string $originalName = ''): array {
    if (!is_file($sourcePath) || !is_readable($sourcePath)) return ['success' => false, 'error' => 'Sumber video tidak dapat dibaca.'];
    $requirement = media_requirement($role, $preset);
    $binary = find_ffmpeg_binary();
    if ($binary === null || find_ffprobe_binary() === null) return ['success' => false, 'error' => 'FFmpeg dan FFprobe diperlukan untuk memproses Video Cerita.'];
    if (!is_dir($destinationDir) && !@mkdir($destinationDir, 0755, true)) return ['success' => false, 'error' => 'Gagal membuat direktori penyimpanan video.'];
    $safeBase = preg_replace('/[^A-Za-z0-9_-]+/', '-', pathinfo($originalName !== '' ? $originalName : basename($sourcePath), PATHINFO_FILENAME));
    $safeBase = trim((string)$safeBase, '-_');
    if ($safeBase === '') $safeBase = 'love-story-video';
    $finalName = $safeBase . '-' . bin2hex(random_bytes(8)) . '.mp4';
    $finalPath = rtrim($destinationDir, '/\\') . '/' . $finalName;
    $temporaryPath = video_processing_temp_path($destinationDir);
    $vf = "scale='min(" . (int)($requirement['max_width'] ?? 1280) . ",iw)':'min(" . (int)($requirement['max_height'] ?? 720) . ",ih)':force_original_aspect_ratio=decrease:force_divisible_by=2";
    $parts = [
        escapeshellarg($binary), '-hide_banner', '-loglevel', 'error', '-y',
        '-i', escapeshellarg($sourcePath), '-map', escapeshellarg('0:v:0'), '-map', escapeshellarg('0:a:0?'),
        '-vf', escapeshellarg($vf), '-c:v', escapeshellarg((string)($requirement['video_encoder'] ?? 'libx264')),
        '-preset', escapeshellarg((string)($requirement['preset'] ?? 'veryfast')), '-crf', (string)(int)($requirement['crf'] ?? 25),
        '-pix_fmt', escapeshellarg((string)($requirement['pixel_format'] ?? 'yuv420p')), '-r', (string)(int)($requirement['max_fps'] ?? 30),
        '-c:a', escapeshellarg((string)($requirement['audio_codec'] ?? 'aac')), '-b:a', escapeshellarg((string)($requirement['audio_bitrate'] ?? '128k')),
        '-movflags', '+faststart', '-sn', '-dn', escapeshellarg($temporaryPath)
    ];
    $run = run_ffmpeg_command($parts);
    $processed = !empty($run['success']) && verify_mp4_output($temporaryPath, $requirement);
    if (!$processed || !@rename($temporaryPath, $finalPath) || !verify_mp4_output($finalPath, $requirement)) {
        if (!empty($run['stderr'])) error_log('MediaProcessor FFmpeg failed: ' . trim((string)$run['stderr']));
        @unlink($temporaryPath);
        @unlink($finalPath);
        return ['success' => false, 'error' => 'Gagal memproses video menjadi MP4 terverifikasi.'];
    }
    $video = probe_video_stream($finalPath);
    return ['success' => true, 'path' => $finalPath, 'name' => $finalName, 'mime' => 'video/mp4', 'width' => (int)($video['width'] ?? 0), 'height' => (int)($video['height'] ?? 0), 'duration' => (float)($video['duration'] ?? 0), 'requirement' => $requirement];
}

function media_role_alias(string $role): string {
    $role = strtolower(trim($role));
    $aliases = [
        'hero' => 'background',
        'hero_background' => 'background',
        'background_hero' => 'background',
        'background_section' => 'background',
        'background_sections' => 'background',
        'bride' => 'bride_photo',
        'groom' => 'groom_photo',
        'couple' => 'couple_photo',
        'video' => 'love_story_video',
        'love_story_video' => 'love_story_video',
        'invitation_video' => 'love_story_video',
        'love_story' => 'story',
        'love-story' => 'story',
        'gift' => 'qris_image',
        'qris' => 'qris_image',
        'og' => 'og_image',
        'theme' => 'theme_asset',
        'theme_assets' => 'theme_asset',
    ];
    return $aliases[$role] ?? ($role !== '' ? $role : 'generic');
}

/**
 * Declarative output policy for every upload role. The global catalog is
 * intentionally conservative: maximum/preserve roles never upscale, while
 * exact canvas roles are used only where the presentation contract requires it.
 * Preset overrides are sparse and evidence-backed; the renderer consumes only
 * the final URL and never contains media-processing logic.
 */
function media_requirements(): array {
    return [
        'global' => [
            'generic' => ['max_width' => 2400, 'max_height' => 1600, 'fit' => 'preserve', 'upscale' => false],
            'cover' => ['max_width' => 1600, 'max_height' => 1200, 'fit' => 'preserve', 'upscale' => false],
            'background' => ['max_width' => 2400, 'max_height' => 1600, 'fit' => 'preserve', 'crop' => false, 'upscale' => false],
            'bride_photo' => ['max_width' => 1600, 'max_height' => 1600, 'fit' => 'preserve', 'upscale' => false],
            'groom_photo' => ['max_width' => 1600, 'max_height' => 1600, 'fit' => 'preserve', 'upscale' => false],
            'couple_photo' => ['max_width' => 1800, 'max_height' => 1200, 'fit' => 'preserve', 'upscale' => false],
            'gallery' => ['max_width' => 1600, 'max_height' => 1200, 'fit' => 'preserve', 'upscale' => false],
            'story' => ['max_width' => 1200, 'max_height' => 900, 'fit' => 'preserve', 'upscale' => false],
            'qris_image' => ['max_width' => 1200, 'max_height' => 1200, 'fit' => 'preserve', 'upscale' => false],
            'og_image' => ['width' => 1200, 'height' => 630, 'fit' => 'cover', 'crop' => true, 'upscale' => true],
            'theme_asset' => ['max_width' => 2400, 'max_height' => 1600, 'fit' => 'preserve', 'upscale' => false, 'preserve_alpha' => true],
            'music' => ['audio_codec' => 'mp3', 'audio_encoder' => 'libmp3lame', 'audio_bitrate' => '160k', 'sample_rate' => 44100, 'channels' => 2],
            'love_story_video' => ['max_width' => 1280, 'max_height' => 720, 'fit' => 'contain', 'video_codec' => 'h264', 'video_encoder' => 'libx264', 'audio_codec' => 'aac', 'pixel_format' => 'yuv420p', 'max_fps' => 30, 'crf' => 25, 'audio_bitrate' => '128k', 'preset' => 'veryfast'],
        ],
        'presets' => [
            'parang' => [
                'gallery' => [
                    'max_width' => 1200,
                    'max_height' => 1200,
                    'fit' => 'cover',
                    'aspect_width' => 1,
                    'aspect_height' => 1,
                    'crop' => true,
                    'upscale' => false,
                    'source_evidence' => 'themes/parang/style.css: .parang-gallery-item aspect-ratio:1 and img object-fit:cover',
                ],
                'theme_asset' => [
                    'fit' => 'preserve',
                    'upscale' => false,
                    'preserve_alpha' => true,
                    'source_evidence' => 'themes/parang/assets: gunungan/wayang/pattern are decorative assets, not wedding photos',
                ],
            ],
            'pawiwahan' => [
                'background' => [
                    'width' => null,
                    'height' => null,
                    'max_width' => 1600,
                    'max_height' => 2400,
                    'fit' => 'preserve',
                    'crop' => false,
                    'upscale' => false,
                    'source_evidence' => 'themes/pawiwahan/assets/css/pawiwahan.css: .hero background-size:cover, source hero 640x960',
                ],
                'cover' => [
                    'max_width' => 1600,
                    'max_height' => 2400,
                    'fit' => 'preserve',
                    'upscale' => false,
                    'source_evidence' => 'themes/pawiwahan/style.css: carousel media max-height with object-fit:cover and portrait source fallback',
                ],
            ],
            'dewankl' => [
                'cover' => [
                    'max_width' => 1600,
                    'max_height' => 1600,
                    'fit' => 'preserve',
                    'upscale' => false,
                    'source_evidence' => 'themes/dewankl/style.css: .img-center-crop width/height 13rem and object-fit:cover',
                ],
            ],
        ],
    ];
}
function media_requirement(string $role, ?string $preset = null): array {
    $role = media_role_alias($role);
    $catalog = media_requirements();
    $requirement = $catalog['global'][$role] ?? $catalog['global']['generic'];
    $preset = strtolower(trim((string)$preset));
    $override = $preset !== '' ? ($catalog['presets'][$preset][$role] ?? []) : [];
    return array_replace($requirement, $override);
}


function media_storage_roots(): array {
    return array_values(array_filter([
        tenant_upload_dir('cover'), tenant_upload_dir('background'), tenant_upload_dir('gallery'),
        tenant_upload_dir('love_story'), tenant_upload_dir('music'), tenant_upload_dir('theme_assets')
    ]));
}

function media_path_is_safe_storage(string $relativePath): bool {
    $normalized = normalize_media_relative_path($relativePath);
    if ($normalized === null) return false;
    $fullPath = ROOT_DIR . '/' . $normalized;
    $realPath = realpath($fullPath);
    if ($realPath === false) return false;
    foreach (media_storage_roots() as $root) {
        $realRoot = realpath($root);
        if ($realRoot !== false && str_starts_with($realPath, $realRoot . DIRECTORY_SEPARATOR)) return true;
    }
    return false;
}

function tenant_media_reference_is_safe(string $relativePath): bool {
    return media_path_is_safe_storage($relativePath);
}

function verify_webp_output(string $path, array $requirement = []): bool {
    if (!is_file($path) || filesize($path) <= 0) return false;
    $mime = safe_image_mime($path);
    if ($mime !== 'image/webp') return false;
    $info = @getimagesize($path);
    if (!is_array($info) || (int)($info[0] ?? 0) <= 0 || (int)($info[1] ?? 0) <= 0) return false;
    if (isset($requirement['width']) && (int)$info[0] !== (int)$requirement['width']) return false;
    if (isset($requirement['height']) && (int)$info[1] !== (int)$requirement['height']) return false;
    if (isset($requirement['max_width']) && (int)$info[0] > (int)$requirement['max_width']) return false;
    if (isset($requirement['max_height']) && (int)$info[1] > (int)$requirement['max_height']) return false;
    if (isset($requirement['aspect_width'], $requirement['aspect_height'])) {
        $expectedRatio = (float)$requirement['aspect_width'] / max(1, (float)$requirement['aspect_height']);
        $actualRatio = (float)$info[0] / max(1, (float)$info[1]);
        if (abs($actualRatio - $expectedRatio) > 0.01) return false;
    }
    return true;
}

function image_processing_temp_path(string $directory, string $suffix = '.webp.tmp'): string {
    return rtrim($directory, '/\\') . '/.' . bin2hex(random_bytes(12)) . $suffix;
}

function process_image_to_webp(string $sourcePath, string $destinationDir, string $role = 'generic', ?string $preset = null, string $originalName = ''): array {
    if (!is_file($sourcePath) || !is_readable($sourcePath)) {
        return ['success' => false, 'error' => 'Sumber gambar tidak dapat dibaca.'];
    }
    $requirement = media_requirement($role, $preset);
    if (!is_dir($destinationDir) && !@mkdir($destinationDir, 0755, true)) {
        return ['success' => false, 'error' => 'Gagal membuat direktori penyimpanan.'];
    }
    $safeBase = preg_replace('/[^A-Za-z0-9_-]+/', '-', pathinfo($originalName !== '' ? $originalName : basename($sourcePath), PATHINFO_FILENAME));
    $safeBase = trim((string)$safeBase, '-_');
    if ($safeBase === '') $safeBase = 'media';
    $finalName = $safeBase . '-' . bin2hex(random_bytes(8)) . '.webp';
    $finalPath = rtrim($destinationDir, '/\\') . '/' . $finalName;
    $temporaryPath = image_processing_temp_path($destinationDir);
    $binary = find_imagemagick_binary();
    $processed = false;

    if ($binary !== null) {
        $parts = [escapeshellarg($binary), escapeshellarg($sourcePath), '-auto-orient'];
        $fit = (string)($requirement['fit'] ?? 'preserve');
        if ($fit === 'cover' && isset($requirement['width'], $requirement['height'])) {
            $parts[] = '-resize';
            $parts[] = escapeshellarg((int)$requirement['width'] . 'x' . (int)$requirement['height'] . '^');
            $parts[] = '-gravity';
            $parts[] = 'center';
            $parts[] = '-extent';
            $parts[] = escapeshellarg((int)$requirement['width'] . 'x' . (int)$requirement['height']);
        } elseif ($fit === 'cover' && isset($requirement['aspect_width'], $requirement['aspect_height'], $requirement['max_width'], $requirement['max_height'])) {
            $parts[] = '-resize';
            $parts[] = escapeshellarg((int)$requirement['max_width'] . 'x' . (int)$requirement['max_height'] . '^>');
            $parts[] = '-gravity';
            $parts[] = 'center';
            $parts[] = '-crop';
            $parts[] = escapeshellarg((int)$requirement['aspect_width'] . ':' . (int)$requirement['aspect_height']);
            $parts[] = '+repage';
        } elseif (isset($requirement['max_width'], $requirement['max_height'])) {
            $parts[] = '-resize';
            $parts[] = escapeshellarg((int)$requirement['max_width'] . 'x' . (int)$requirement['max_height'] . '>');
        }
        $parts[] = '-strip';
        $parts[] = '-quality';
        $parts[] = (string)WEBP_QUALITY;
        $parts[] = 'webp:' . escapeshellarg($temporaryPath);
        $command = implode(' ', $parts);
        $process = @proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (is_resource($process)) {
            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
            $processed = $exitCode === 0 && verify_webp_output($temporaryPath, $requirement);
            if (!$processed && $stderr !== '') error_log('MediaProcessor ImageMagick failed: ' . trim($stderr));
        }
    }

    if (!$processed && extension_loaded('gd') && function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
        $data = @file_get_contents($sourcePath);
        $image = $data === false ? false : @imagecreatefromstring($data);
        if ($image !== false) {
            $sourceWidth = imagesx($image);
            $sourceHeight = imagesy($image);
            $targetWidth = $sourceWidth;
            $targetHeight = $sourceHeight;
            $sourceX = 0;
            $sourceY = 0;
            $sourceCropWidth = $sourceWidth;
            $sourceCropHeight = $sourceHeight;
            if (isset($requirement['aspect_width'], $requirement['aspect_height'], $requirement['max_width'], $requirement['max_height'])) {
                $expectedRatio = (float)$requirement['aspect_width'] / max(1, (float)$requirement['aspect_height']);
                $sourceRatio = $sourceWidth / max(1, $sourceHeight);
                if ($sourceRatio > $expectedRatio) {
                    $sourceCropWidth = max(1, (int)round($sourceHeight * $expectedRatio));
                    $sourceX = (int)floor(($sourceWidth - $sourceCropWidth) / 2);
                } elseif ($sourceRatio < $expectedRatio) {
                    $sourceCropHeight = max(1, (int)round($sourceWidth / $expectedRatio));
                    $sourceY = (int)floor(($sourceHeight - $sourceCropHeight) / 2);
                }
                $scale = min(1, (int)$requirement['max_width'] / max(1, $sourceCropWidth), (int)$requirement['max_height'] / max(1, $sourceCropHeight));
                $targetWidth = max(1, (int)round($sourceCropWidth * $scale));
                $targetHeight = max(1, (int)round($sourceCropHeight * $scale));
            } elseif (isset($requirement['max_width'], $requirement['max_height'])) {
                $scale = min(1, (int)$requirement['max_width'] / max(1, $sourceWidth), (int)$requirement['max_height'] / max(1, $sourceHeight));
                $targetWidth = max(1, (int)round($sourceWidth * $scale));
                $targetHeight = max(1, (int)round($sourceHeight * $scale));
            }
            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
            imagecopyresampled($canvas, $image, 0, 0, $sourceX, $sourceY, $targetWidth, $targetHeight, $sourceCropWidth, $sourceCropHeight);
            $processed = @imagewebp($canvas, $temporaryPath, WEBP_QUALITY);
            imagedestroy($canvas);
            imagedestroy($image);
            $processed = $processed && verify_webp_output($temporaryPath, $requirement);
        }
    }

    if (!$processed || !@rename($temporaryPath, $finalPath) || !verify_webp_output($finalPath, $requirement)) {
        @unlink($temporaryPath);
        @unlink($finalPath);
        return ['success' => false, 'error' => 'Gagal memproses gambar menjadi WebP terverifikasi.'];
    }
    return ['success' => true, 'path' => $finalPath, 'name' => $finalName, 'mime' => 'image/webp', 'width' => @getimagesize($finalPath)[0] ?? null, 'height' => @getimagesize($finalPath)[1] ?? null, 'requirement' => $requirement];
}

/** Backward-compatible wrapper; new upload flows use process_image_to_webp(). */
function process_uploaded_image(string $sourcePath): bool {
    $result = process_image_to_webp($sourcePath, dirname($sourcePath), 'generic', null, basename($sourcePath));
    if (empty($result['success'])) return false;
    if (($result['path'] ?? '') !== $sourcePath && is_file($sourcePath)) @unlink($sourcePath);
    return true;
}

function authenticate_user(string $username, string $password): ?array {
    $username = trim($username);
    if ($username === '' || $password === '') return null;
    try {
        $tenant = current_tenant(false);
        if (!is_array($tenant)) return null;
        $db = tenant_database(true);
        $stmt = $db->prepare('SELECT id, tenant_id, username, password_hash, role FROM users WHERE username = :username AND (tenant_id = :tenant_id OR tenant_id IS NULL) ORDER BY tenant_id IS NOT NULL DESC LIMIT 2');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':tenant_id', (int)$tenant['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!password_verify($password, (string)($row['password_hash'] ?? ''))) continue;
            if (($row['role'] ?? '') === 'tenant_admin' && (int)$row['tenant_id'] !== (int)$tenant['id']) continue;
            if (($row['role'] ?? '') === 'super_admin' && $row['tenant_id'] !== null) continue;
            $db->close();
            return ['id' => (int)$row['id'], 'tenant_id' => $row['tenant_id'] === null ? null : (int)$row['tenant_id'], 'username' => (string)$row['username'], 'role' => (string)$row['role'], 'domain' => (string)$tenant['domain']];
        }
        $db->close();
    } catch (Throwable $e) {
        error_log('User authentication failed: ' . $e->getMessage());
    }
    return null;
}

function session_admin_is_valid(): bool {
    init_session();
    $sessionRole = (string)($_SESSION['role'] ?? '');
    if (empty($_SESSION['admin']) || !in_array($sessionRole, ['super_admin', 'tenant_admin'], true)) return false;
    $tenant = current_tenant(false);
    if (!is_array($tenant)) return false;
    $user = current_admin_user_record();
    if (!is_array($user) || (int)($user['id'] ?? 0) !== (int)($_SESSION['user_id'] ?? 0)) return false;
    if ((string)($user['role'] ?? '') !== $sessionRole || (string)($user['username'] ?? '') !== (string)($_SESSION['username'] ?? '')) return false;
    if ($sessionRole === 'tenant_admin') {
        if ((int)($user['tenant_id'] ?? 0) !== (int)$tenant['id'] || (int)($_SESSION['tenant_id'] ?? 0) !== (int)$tenant['id']) return false;
    } elseif ($user['tenant_id'] !== null) {
        return false;
    }
    return true;
}

function update_current_user_username(string $username): bool {
    init_session();
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $username = trim($username);
    if ($userId < 1 || $username === '' || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{2,63}$/', $username)) return false;
    $currentUser = current_admin_user_record();
    if (!is_array($currentUser)) return false;
    try {
        $db = tenant_database(false);
        $conflict = $db->prepare('SELECT 1 FROM users WHERE username = :username AND id <> :user_id AND ((tenant_id IS NULL AND :tenant_is_null = 1) OR tenant_id = :tenant_id) LIMIT 1');
        $tenantId = $currentUser['tenant_id'] === null ? 0 : (int)$currentUser['tenant_id'];
        $conflict->bindValue(':username', $username, SQLITE3_TEXT);
        $conflict->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $conflict->bindValue(':tenant_is_null', $currentUser['tenant_id'] === null ? 1 : 0, SQLITE3_INTEGER);
        $conflict->bindValue(':tenant_id', $tenantId, SQLITE3_INTEGER);
        if ($conflict->execute()->fetchArray(SQLITE3_NUM)) { $db->close(); return false; }
        $stmt = $db->prepare('UPDATE users SET username = :username WHERE id = :user_id');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $ok = (bool)$stmt->execute();
        $db->close();
        if ($ok) $_SESSION['username'] = $username;
        return $ok;
    } catch (Throwable $e) {
        error_log('Admin username update failed: ' . $e->getMessage());
        return false;
    }
}

function update_current_user_password(string $password): bool {
    init_session();
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId < 1 || $password === '' || admin_password_policy_error($password) !== null) return false;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $visiblePassword = encrypt_visible_password($password);
    if ($visiblePassword === '') return false;
    try {
        $db = tenant_database(false);
        $stmt = $db->prepare('UPDATE users SET password_hash = :password_hash, visible_password = :visible_password WHERE id = :user_id');
        $stmt->bindValue(':password_hash', $hash, SQLITE3_TEXT);
        $stmt->bindValue(':visible_password', $visiblePassword, SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $ok = (bool)$stmt->execute();
        $db->close();
        if ($ok) rotate_admin_session();
        return $ok;
    } catch (Throwable $e) {
        error_log('Admin password update failed: ' . $e->getMessage());
        return false;
    }
}

function set_admin_password(string $password, array &$config): void {
    if ($password === '') return;
    $config['admin']['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    if (!update_current_user_password($password)) {
        throw new RuntimeException('Gagal menyimpan password akun pada database.');
    }
}

function init_session(): void {
    $secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!headers_sent()) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $secureFlag,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        // A late session call cannot emit a cookie. Suppress the PHP warning so
        // render-only CLI audits and already-buffered pages do not crash.
        @session_start();
    }
    if (!empty($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > SESSION_TIMEOUT) {
        $_SESSION = [];
        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? false);
        }
        session_destroy();
        @session_start();
    }
}

function require_admin(): void {
    init_session();
    if (!session_admin_is_valid()) {
        $_SESSION = [];
        header('Location: /admin');
        exit;
    }
    $_SESSION['last_activity'] = time();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function get_csrf_token(): string {
    init_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(?string $token): bool {
    init_session();
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function safe_image_mime(string $path): ?string {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? @finfo_file($finfo, $path) : null;
    if ($finfo) finfo_close($finfo);
    return $mime ?? null;
}

function tenant_destination_is_safe(string $destinationDir): bool {
    $tenantRoot = tenant_upload_root();
    if ($tenantRoot === '') return false;
    $tenantRootReal = realpath($tenantRoot);
    if ($tenantRootReal === false) $tenantRootReal = rtrim($tenantRoot, '/\\\\');
    $destinationReal = realpath($destinationDir);
    if ($destinationReal === false) {
        $parentReal = realpath(dirname($destinationDir));
        if ($parentReal === false) return false;
        $destinationReal = rtrim($parentReal, '/\\\\') . '/' . basename($destinationDir);
    }
    return $destinationReal === $tenantRootReal || str_starts_with($destinationReal, $tenantRootReal . DIRECTORY_SEPARATOR);
}

function upload_file(array $file, string $destinationDir, array $allowedExtensions, int $maxSize, string $role = 'generic', ?string $preset = null): array {
    if (!tenant_destination_is_safe($destinationDir)) {
        return ['success' => false, 'error' => 'Direktori upload bukan milik tenant aktif.'];
    }
    if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Gagal mengunggah file.'];
    }
    $temporarySource = (string)($file['tmp_name'] ?? '');
    $isCliFixture = PHP_SAPI === 'cli' && is_file($temporarySource);
    if (!is_uploaded_file($temporarySource) && !$isCliFixture) {
        return ['success' => false, 'error' => 'Sumber file tidak valid.'];
    }
    if ((int)($file['size'] ?? 0) > $maxSize) {
        return ['success' => false, 'error' => 'Ukuran file terlalu besar.'];
    }
    $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedExtensions = array_map('strtolower', $allowedExtensions);
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['success' => false, 'error' => 'Format file tidak diizinkan.'];
    }
    $mime = safe_image_mime($temporarySource);
    if ($mime === null) {
        return ['success' => false, 'error' => 'Tipe file tidak dapat diverifikasi.'];
    }
    $isImage = in_array($extension, ALLOWED_IMAGE_TYPES, true);
    $isAudio = in_array($extension, ALLOWED_AUDIO_TYPES, true);
    $isVideo = in_array($extension, ALLOWED_VIDEO_TYPES, true);
    if ($isImage && stripos($mime, 'image/') !== 0) {
        return ['success' => false, 'error' => 'Tipe file bukan gambar.'];
    }
    if ($isAudio && stripos($mime, 'audio/') !== 0 && $mime !== 'application/ogg') {
        return ['success' => false, 'error' => 'Tipe file bukan audio.'];
    }
    if ($isVideo && stripos($mime, 'video/') !== 0) {
        return ['success' => false, 'error' => 'Tipe file bukan video.'];
    }
    if (!is_dir($destinationDir) && !@mkdir($destinationDir, 0755, true)) {
        return ['success' => false, 'error' => 'Gagal membuat direktori penyimpanan.'];
    }
    if (!tenant_destination_is_safe($destinationDir)) {
        return ['success' => false, 'error' => 'Direktori upload bukan milik tenant aktif.'];
    }

    if ($isImage) {
        $result = process_image_to_webp($temporarySource, $destinationDir, $role, $preset, (string)$file['name']);
        if (empty($result['success'])) return $result;
        // The source is a PHP upload temporary file (or a CLI fixture). Remove
        // it only after the verified final WebP has been atomically installed.
        if (is_file($temporarySource) && $temporarySource !== $result['path']) @unlink($temporarySource);
        $result['extension'] = 'webp';
        $result['original_extension'] = $extension;
        return $result;
    }

    if ($isAudio) {
        $result = process_audio_to_mp3($temporarySource, $destinationDir, $role, $preset, (string)$file['name']);
        if (empty($result['success'])) return $result;
        if (is_file($temporarySource) && $temporarySource !== $result['path']) @unlink($temporarySource);
        $result['extension'] = 'mp3';
        $result['original_extension'] = $extension;
        return $result;
    }

    if ($isVideo) {
        $result = process_video_to_mp4($temporarySource, $destinationDir, $role, $preset, (string)$file['name']);
        if (empty($result['success'])) return $result;
        if (is_file($temporarySource) && $temporarySource !== $result['path']) @unlink($temporarySource);
        $result['extension'] = 'mp4';
        $result['original_extension'] = $extension;
        return $result;
    }

    $safeName = generate_safe_filename((string)$file['name']);
    $dest = rtrim($destinationDir, '/\\') . '/' . $safeName;
    if (!@move_uploaded_file($temporarySource, $dest) && !($isCliFixture && @rename($temporarySource, $dest))) {
        return ['success' => false, 'error' => 'Gagal menyimpan file.'];
    }
    if (!is_file($dest) || filesize($dest) <= 0) {
        @unlink($dest);
        return ['success' => false, 'error' => 'File tersimpan tidak valid.'];
    }
    return ['success' => true, 'path' => $dest, 'name' => $safeName, 'extension' => $extension, 'mime' => $mime];
}

function public_path(string $path): string {
    $path = trim($path);
    // Empty optional media must not become `/`, which would request the page itself.
    if ($path === '') return 'data:,';
    $normalized = ltrim(str_replace('\\', '/', $path), '/');
    // Every upload URL is tenant media. Do not let stale legacy paths or a
    // malformed cross-tenant reference reach the rendered HTML.
    if (str_starts_with($normalized, 'uploads/') && !media_path_is_safe_storage($normalized)) return 'data:,';
    return '/' . $normalized;
}

function relative_path(string $path): string {
    $path = str_replace('\\', '/', $path);
    return ltrim(preg_replace('#^' . preg_quote(ROOT_DIR, '#') . '/#', '', $path), '/');
}

function get_gallery_items(array $config): array {
    ensure_upload_dirs();
    $items = [];
    $configured = [];
    foreach ($config['gallery']['items'] as $item) {
        if (empty($item['filename'])) {
            continue;
        }
        $configured[$item['filename']] = [
            'filename' => $item['filename'],
            'order' => isset($item['order']) ? (int)$item['order'] : 9999
        ];
    }
    $files = [];
    foreach (glob(tenant_upload_dir('gallery') . '/*') ?: [] as $filePath) {
        if (!is_file($filePath)) {
            continue;
        }
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_IMAGE_TYPES, true)) {
            continue;
        }
        $filename = relative_path($filePath);
        if (!isset($configured[$filename])) {
            continue;
        }
        $files[$filename] = $configured[$filename];
    }
    foreach ($configured as $item) {
        if (!isset($files[$item['filename']])) {
            continue;
        }
        $items[] = $item;
    }
    usort($items, function ($a, $b) {
        return $a['order'] <=> $b['order'];
    });
    return $items;
}

function delete_uploaded_asset(string $relativePath): bool {
    $normalized = trim((string) $relativePath);
    $normalized = str_replace('\\', '/', $normalized);
    $normalized = ltrim($normalized, '/');
    if ($normalized === '' || str_contains($normalized, '..')) {
        return false;
    }
    $fullPath = ROOT_DIR . '/' . $normalized;
    if (!is_file($fullPath)) {
        return false;
    }
    $allowedRoots = media_storage_roots();
    $realPath = realpath($fullPath);
    foreach ($allowedRoots as $root) {
        $realRoot = realpath($root);
        if ($realRoot !== false && $realPath !== false && str_starts_with($realPath, $realRoot . DIRECTORY_SEPARATOR)) {
            return @unlink($fullPath);
        }
    }
    return false;
}

function normalize_media_relative_path(string $relativePath): ?string {
    $normalized = trim((string) $relativePath);
    $normalized = str_replace('\\', '/', $normalized);
    $normalized = ltrim($normalized, '/');
    if ($normalized === '' || str_contains($normalized, '..')) {
        return null;
    }
    return $normalized;
}

function detect_media_usage(array $config, string $relativePath): array {
    $normalized = normalize_media_relative_path($relativePath);
    if ($normalized === null) {
        return [];
    }
    $usage = [];
    $checks = [
        'Cover' => $config['media']['cover'] ?? '',
        'Bride Photo' => $config['media']['bride_photo'] ?? '',
        'Groom Photo' => $config['media']['groom_photo'] ?? '',
        'Couple Photo' => $config['media']['couple_photo'] ?? '',
        'Hero Background' => $config['media']['background_hero'] ?? '',
        'Open Graph Image' => $config['site']['open_graph_image'] ?? '',
        'Music' => $config['media']['music'] ?? '',
        'Love Story Video' => $config['media']['love_story_video'] ?? '',
        'Legacy Video' => $config['media']['video'] ?? '',
        'Legacy Invitation Video' => $config['media']['invitation_video'] ?? '',
        'QR Gift' => $config['gift']['qris_image'] ?? '',
    ];
    foreach ($checks as $label => $value) {
        if ($value !== '' && normalize_media_relative_path($value) === $normalized) {
            $usage[] = $label;
        }
    }
    foreach (($config['media']['background_sections'] ?? []) as $index => $value) {
        if ($value !== '' && normalize_media_relative_path($value) === $normalized) {
            $usage[] = 'Background #' . ($index + 1);
        }
    }
    foreach (($config['gallery']['items'] ?? []) as $item) {
        $filename = (string)($item['filename'] ?? '');
        if ($filename !== '' && normalize_media_relative_path($filename) === $normalized) {
            $usage[] = 'Gallery';
        }
    }
    foreach (($config['love_story']['items'] ?? []) as $item) {
        $image = (string)($item['image'] ?? '');
        if ($image !== '' && (media_reference_matches($image, $normalized) || basename($image) === basename($normalized))) {
            $usage[] = 'Love Story';
        }
    }
    foreach (($config['theme_visuals'] ?? []) as $presetKey => $visualOverrides) {
        foreach ((array)$visualOverrides as $visualKey => $visualValue) {
            if ((string)$visualValue !== '' && normalize_media_relative_path((string)$visualValue) === $normalized) {
                $usage[] = 'Visual ' . ucfirst((string)$presetKey) . ' / ' . str_replace('_', ' ', (string)$visualKey);
            }
        }
    }
    foreach (($config['theme_options'] ?? []) as $presetKey => $options) {
        foreach ((array)$options as $optionKey => $optionValue) {
            if ((string)$optionValue !== '' && normalize_media_relative_path((string)$optionValue) === $normalized) {
                $usage[] = 'Theme Asset ' . ucfirst((string)$presetKey) . ' / ' . str_replace('_', ' ', (string)$optionKey);
            }
        }
    }
    $usage = array_values(array_unique($usage));
    return $usage;
}

function list_media_library(array $options = []): array {
    $search = strtolower(trim((string)($options['search'] ?? '')));
    $typeFilter = strtolower(trim((string)($options['type'] ?? 'all')));
    $groupFilter = strtolower(trim((string)($options['group'] ?? 'all')));
    $config = load_config();
    $groups = [
        'cover' => ['dir' => tenant_upload_dir('cover'), 'label' => 'Cover', 'allowed' => ALLOWED_IMAGE_TYPES],
        'background' => ['dir' => tenant_upload_dir('background'), 'label' => 'Background', 'allowed' => ALLOWED_IMAGE_TYPES],
        'gallery' => ['dir' => tenant_upload_dir('gallery'), 'label' => 'Gallery', 'allowed' => ALLOWED_IMAGE_TYPES],
        'love_story' => ['dir' => tenant_upload_dir('love_story'), 'label' => 'Love Story', 'allowed' => ALLOWED_IMAGE_TYPES],
        'video' => ['dir' => tenant_upload_dir('love_story'), 'label' => 'Video Cerita', 'allowed' => ALLOWED_VIDEO_TYPES],
        'theme_assets' => ['dir' => tenant_upload_dir('theme_assets'), 'label' => 'Theme Assets', 'allowed' => ALLOWED_IMAGE_TYPES],
        'music' => ['dir' => tenant_upload_dir('music'), 'label' => 'Music', 'allowed' => ALLOWED_AUDIO_TYPES],
    ];

    $items = [];
    foreach ($groups as $groupKey => $group) {
        if (!is_dir($group['dir']) || ($groupFilter !== 'all' && $groupKey !== $groupFilter)) {
            continue;
        }
        $files = [];
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($group['dir'], FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile()) $files[] = $fileInfo->getPathname();
            }
        } catch (Throwable $e) {
            $files = [];
        }
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($files as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $isAudio = in_array($ext, ALLOWED_AUDIO_TYPES, true);
            $isVideo = in_array($ext, ALLOWED_VIDEO_TYPES, true);
            if (!in_array($ext, $group['allowed'], true) || (!$isAudio && !$isVideo && $ext !== 'webp')) {
                continue;
            }
            $path = relative_path($filePath);
            $name = basename($filePath);
            if ($search !== '' && stripos($name, $search) === false && stripos($path, $search) === false) {
                continue;
            }
            $mediaType = $isAudio ? 'audio' : ($isVideo ? 'video' : 'image');
            if ($typeFilter !== 'all' && $typeFilter !== $mediaType) {
                continue;
            }
            $usage = detect_media_usage($config, $path);
            $dimensions = null;
            if ($mediaType === 'image') {
                $imageInfo = @getimagesize($filePath);
                if (is_array($imageInfo)) {
                    $dimensions = $imageInfo[0] . ' × ' . $imageInfo[1];
                }
            }
            $items[] = [
                'group' => $groupKey,
                'label' => $group['label'],
                'type' => $mediaType,
                'path' => $path,
                'name' => $name,
                'size' => filesize($filePath),
                'mime' => safe_image_mime($filePath) ?: mime_content_type($filePath),
                'dimensions' => $dimensions,
                'created_at' => filemtime($filePath),
                'used_by' => $usage,
                'is_used' => !empty($usage),
                'status' => empty($usage) ? 'Unused' : 'Used',
            ];
        }
    }

    usort($items, function (array $a, array $b): int {
        $groupOrder = ['cover' => 1, 'background' => 2, 'gallery' => 3, 'love_story' => 4, 'video' => 5, 'theme_assets' => 6, 'music' => 7];
        $groupDiff = ($groupOrder[$a['group']] ?? 99) <=> ($groupOrder[$b['group']] ?? 99);
        return $groupDiff !== 0 ? $groupDiff : strcmp($a['name'], $b['name']);
    });

    return $items;
}

function rename_uploaded_asset(string $relativePath, string $newName): array {
    $normalized = normalize_media_relative_path($relativePath);
    if ($normalized === null) {
        return ['success' => false, 'error' => 'Path media tidak valid.'];
    }
    $fullPath = ROOT_DIR . '/' . $normalized;
    if (!is_file($fullPath) || !media_path_is_safe_storage($normalized)) {
        return ['success' => false, 'error' => 'File media tidak ditemukan.'];
    }
    $safeName = trim((string)$newName);
    if ($safeName === '') {
        return ['success' => false, 'error' => 'Nama file baru wajib diisi.'];
    }
    $originalExt = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $newBaseName = preg_replace('/[^A-Za-z0-9_.-]+/', '-', $safeName);
    $newBaseName = trim($newBaseName, '.-_ ');
    if ($newBaseName === '') {
        return ['success' => false, 'error' => 'Nama file baru tidak valid.'];
    }
    if (!str_ends_with(strtolower($newBaseName), '.' . $originalExt) && $originalExt !== '') {
        $newBaseName .= '.' . $originalExt;
    }
    $targetDir = dirname($fullPath);
    $targetPath = $targetDir . '/' . $newBaseName;
    if ($targetPath === $fullPath) {
        return ['success' => true, 'path' => $normalized];
    }
    if (file_exists($targetPath)) {
        return ['success' => false, 'error' => 'Nama file sudah dipakai.'];
    }
    if (!@rename($fullPath, $targetPath)) {
        return ['success' => false, 'error' => 'Gagal mengganti nama file.'];
    }
    return ['success' => true, 'path' => relative_path($targetPath)];
}

function replace_uploaded_asset(string $relativePath, array $file, ?string $role = null, ?string $preset = null): array {
    $normalized = normalize_media_relative_path($relativePath);
    if ($normalized === null || !media_path_is_safe_storage($normalized)) {
        return ['success' => false, 'error' => 'Path media tidak valid.'];
    }
    $fullPath = ROOT_DIR . '/' . $normalized;
    if (!is_file($fullPath)) return ['success' => false, 'error' => 'File media tidak ditemukan.'];

    $directory = dirname($fullPath);
    $basename = str_replace('\\', '/', $normalized);
    $parts = explode('/', $basename);
    $uploadsIndex = array_search('uploads', $parts, true);
    $folder = $uploadsIndex === false ? '' : (string)($parts[$uploadsIndex + 2] ?? '');
    if ($role === null) {
        $role = match ($folder) {
            'gallery' => 'gallery',
            'background' => 'background',
            'love-story' => in_array(strtolower(pathinfo($basename, PATHINFO_EXTENSION)), ALLOWED_VIDEO_TYPES, true) ? 'love_story_video' : 'story',
            'theme-assets' => 'theme_asset',
            'music' => 'music',
            'cover' => 'cover',
            default => 'generic',
        };
    }
    if ($preset === null && $folder === 'theme-assets' && $uploadsIndex !== false) {
        $preset = $parts[$uploadsIndex + 3] ?? null;
    }
    $canonicalRole = media_role_alias($role);
    $isMusic = $canonicalRole === 'music';
    $isVideo = $canonicalRole === 'love_story_video';
    $allowed = $isMusic ? ALLOWED_AUDIO_TYPES : ($isVideo ? ALLOWED_VIDEO_TYPES : ALLOWED_IMAGE_TYPES);
    $maxSize = $isMusic ? MAX_MUSIC_UPLOAD_SIZE : ($isVideo ? MAX_VIDEO_UPLOAD_SIZE : MAX_UPLOAD_SIZE);
    $result = upload_file($file, $directory, $allowed, $maxSize, $role, $preset);
    if (empty($result['success'])) return ['success' => false, 'error' => $result['error'] ?? 'Gagal memproses file pengganti.'];
    $newPath = relative_path($result['path']);
    return ['success' => true, 'path' => $newPath, 'old_path' => $normalized, 'mime' => $result['mime'] ?? '', 'width' => $result['width'] ?? null, 'height' => $result['height'] ?? null];
}

function media_reference_matches(string $value, string $target): bool {
    $value = trim($value);
    $target = trim($target);
    if ($value === '' || $target === '') return false;
    $normalizedValue = normalize_media_relative_path($value);
    $normalizedTarget = normalize_media_relative_path($target);
    if ($normalizedValue !== null && $normalizedTarget !== null && $normalizedValue === $normalizedTarget) return true;
    return basename($value) === basename($target) && str_contains(str_replace('\\', '/', $value), dirname(str_replace('\\', '/', $target)));
}

function replace_media_references(array &$config, string $oldPath, string $newPath): void {
    $replace = static function (&$value) use ($oldPath, $newPath): void {
        if (is_string($value) && media_reference_matches($value, $oldPath)) $value = $newPath;
    };
    foreach (['cover', 'bride_photo', 'groom_photo', 'couple_photo', 'music', 'love_story_video', 'video', 'invitation_video', 'background_hero'] as $key) {
        if (isset($config['media'][$key])) $replace($config['media'][$key]);
    }
    foreach (($config['media']['background_sections'] ?? []) as $index => &$value) $replace($value);
    unset($value);
    if (isset($config['gift']['qris_image'])) $replace($config['gift']['qris_image']);
    if (isset($config['site']['open_graph_image'])) $replace($config['site']['open_graph_image']);
    foreach (($config['gallery']['items'] ?? []) as &$item) {
        if (isset($item['filename'])) $replace($item['filename']);
    }
    unset($item);
    foreach (($config['love_story']['items'] ?? []) as &$item) {
        if (isset($item['image'])) $replace($item['image']);
    }
    unset($item);
    if (isset($config['theme_visuals']) && is_array($config['theme_visuals'])) {
        foreach ($config['theme_visuals'] as &$visuals) {
            if (!is_array($visuals)) continue;
            foreach ($visuals as &$value) $replace($value);
            unset($value);
        }
        unset($visuals);
    }
    if (isset($config['theme_options']) && is_array($config['theme_options'])) {
        foreach ($config['theme_options'] as &$options) {
            if (!is_array($options)) continue;
            foreach ($options as &$value) $replace($value);
            unset($value);
        }
        unset($options);
    }
}

function clear_media_references(array &$config, string $oldPath): void {
    $normalized = normalize_media_relative_path($oldPath);
    if ($normalized === null) return;
    $clear = static function (&$value) use ($normalized): void {
        if (media_reference_matches((string)$value, $normalized)) $value = '';
    };
    foreach (['cover', 'bride_photo', 'groom_photo', 'couple_photo', 'music', 'love_story_video', 'video', 'invitation_video', 'background_hero'] as $key) {
        if (array_key_exists($key, $config['media'] ?? [])) $clear($config['media'][$key]);
    }
    foreach (($config['media']['background_sections'] ?? []) as $index => $value) {
        if (media_reference_matches((string)$value, $normalized)) $config['media']['background_sections'][$index] = '';
    }
    $clear($config['gift']['qris_image']);
    $clear($config['site']['open_graph_image']);
    if (media_reference_matches((string)($config['gallery']['cover'] ?? ''), $normalized)) $config['gallery']['cover'] = '';
    if (isset($config['gallery']['items']) && is_array($config['gallery']['items'])) {
        $config['gallery']['items'] = array_values(array_filter($config['gallery']['items'], static fn($item): bool => is_array($item) && !media_reference_matches((string)($item['filename'] ?? ''), $normalized)));
    }
    if (isset($config['love_story']['items']) && is_array($config['love_story']['items'])) {
        foreach ($config['love_story']['items'] as $index => $item) {
            if (media_reference_matches((string)($item['image'] ?? ''), $normalized)) $config['love_story']['items'][$index]['image'] = '';
        }
    }
    foreach (($config['theme_visuals'] ?? []) as $presetKey => $visualOverrides) {
        foreach ((array)$visualOverrides as $visualKey => $visualValue) {
            if (media_reference_matches((string)$visualValue, $normalized)) $config['theme_visuals'][$presetKey][$visualKey] = '';
        }
    }
    foreach (($config['theme_options'] ?? []) as $presetKey => $options) {
        foreach ((array)$options as $optionKey => $optionValue) {
            if (media_reference_matches((string)$optionValue, $normalized)) $config['theme_options'][$presetKey][$optionKey] = '';
        }
    }
}

function cleanup_replaced_media(string $oldPath, array $config): bool {
    $normalized = normalize_media_relative_path($oldPath);
    if ($normalized === null || !media_path_is_safe_storage($normalized)) return false;
    if (!empty(detect_media_usage($config, $normalized))) return false;
    return delete_uploaded_asset($normalized);
}

function build_event_ics(array $config): string {
    $schedule = (array)($config['schedule'] ?? []);
    $location = (array)($config['location'] ?? []);
    $wedding = (array)($config['wedding'] ?? []);
    $site = (array)($config['site'] ?? []);
    $title = (string)($wedding['title'] ?? '') ?: ((string)($wedding['bride_name'] ?? '') . ' & ' . (string)($wedding['groom_name'] ?? ''));
    $description = (string)($wedding['opening_text'] ?? '') ?: (string)($site['description'] ?? '');
    $timezone = trim((string)($schedule['timezone'] ?? 'Asia/Jakarta')) ?: 'Asia/Jakarta';
    try {
        $zone = new DateTimeZone($timezone);
    } catch (Throwable $e) {
        $timezone = 'Asia/Jakarta';
        $zone = new DateTimeZone($timezone);
    }
    $start = date_create_from_format('Y-m-d H:i', (string)($schedule['akad_date'] ?? '') . ' ' . (string)($schedule['akad_time'] ?? ''), $zone);
    $end = $start ? (clone $start)->modify('+2 hours') : null;
    $dtstart = $start ? $start->format('Ymd\\THis') : '20261229T090000';
    $dtend = $end ? $end->format('Ymd\\THis') : '20261229T110000';
    $locationText = (string)($location['venue'] ?? '') ?: (string)($location['address'] ?? '');
    return implode("\\r\\n", [
        'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Undangan CMS//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
        'BEGIN:VEVENT', 'DTSTAMP:' . gmdate('Ymd\\THis') . 'Z', 'DTSTART;TZID=' . $timezone . ':' . $dtstart,
        'DTEND;TZID=' . $timezone . ':' . $dtend, 'SUMMARY:' . escape_ics_value($title),
        'DESCRIPTION:' . escape_ics_value($description), 'LOCATION:' . escape_ics_value($locationText),
        'ORGANIZER:MAILTO:no-reply@example.com', 'END:VEVENT', 'END:VCALENDAR'
    ]);
}

function write_event_ics(array $config): void {
    // Legacy API retained for deployment tooling; runtime persistence is tenant_configs.
    $tenant = current_tenant(false);
    if (!is_array($tenant)) return;
    $db = tenant_database(false);
    $stmt = $db->prepare('UPDATE tenant_configs SET event_ics = :event_ics, updated_at = CURRENT_TIMESTAMP WHERE tenant_id = :tenant_id');
    $stmt->bindValue(':event_ics', build_event_ics($config), SQLITE3_TEXT);
    $stmt->bindValue(':tenant_id', (int)$tenant['id'], SQLITE3_INTEGER);
    $stmt->execute();
    $db->close();
}

function escape_ics_value(string $value): string {
    return str_replace(["\\", '\n', ';', ','], ['\\\\', '\\n', '\\;', '\\,'], $value);
}

function build_whatsapp_link(array $config): string {
    $phone = preg_replace('/[^0-9+]/', '', $config['whatsapp']['phone'] ?? '');
    $message = $config['whatsapp']['message'] ?? '';
    if ($phone === '') {
        return '#';
    }
    $phone = preg_replace('/^\+/', '', $phone);
    return 'https://wa.me/' . rawurlencode($phone) . '?text=' . rawurlencode($message);
}

function build_google_calendar_link(array $config): string {
    $stored = trim((string)($config['schedule']['google_calendar_link'] ?? ''));
    if ($stored !== '' && !str_starts_with($stored, 'https://calendar.google.com/calendar/render?action=TEMPLATE')) return $stored;
    $schedule = (array)($config['schedule'] ?? []);
    $wedding = (array)($config['wedding'] ?? []);
    $location = (array)($config['location'] ?? []);
    $title = trim((string)($wedding['title'] ?? ($config['site']['title'] ?? 'Undangan Pernikahan')));
    if ($title === '') $title = trim((string)($wedding['bride_nickname'] ?? '') . ' & ' . (string)($wedding['groom_nickname'] ?? ''));
    $details = trim((string)($wedding['opening_text'] ?? ($config['site']['description'] ?? '')));
    $akadDate = trim((string)($schedule['akad_date'] ?? ''));
    $akadTime = trim((string)($schedule['akad_time'] ?? ''));
    $receptionDate = trim((string)($schedule['reception_date'] ?? $akadDate));
    $receptionTime = trim((string)($schedule['reception_time'] ?? $akadTime));
    $start = $akadDate !== '' ? date('Ymd\\THis', strtotime($akadDate . ' ' . ($akadTime !== '' ? $akadTime : '00:00'))) : '';
    $end = $receptionDate !== '' ? date('Ymd\\THis', strtotime($receptionDate . ' ' . ($receptionTime !== '' ? $receptionTime : ($akadTime !== '' ? $akadTime : '00:00')))) : $start;
    $params = [
        'action' => 'TEMPLATE',
        'text' => $title,
        'dates' => $start . '/' . $end,
        'details' => $details,
        'location' => trim((string)($location['address'] ?? $location['venue'] ?? '')),
        'ctz' => trim((string)($schedule['timezone'] ?? 'Asia/Jakarta')) ?: 'Asia/Jakarta'
    ];
    return 'https://calendar.google.com/calendar/render?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

// Minimal exception handler to avoid leaking internals
set_exception_handler(function ($e) {
    error_log('Unhandled exception: ' . $e->getMessage());
    http_response_code(500);
    if (php_sapi_name() !== 'cli') {
        $headers = headers_list();
        $isJson = false;
        foreach ($headers as $h) {
            if (stripos($h, 'Content-Type:') === 0 && stripos($h, 'application/json') !== false) {
                $isJson = true;
                break;
            }
        }
        if ($isJson) {
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        } else {
            echo 'Internal server error';
        }
    }
    exit;
});
?>
