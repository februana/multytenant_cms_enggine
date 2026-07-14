<?php
require_once __DIR__ . '/config.php';

// Session security
$secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (bool)$secureFlag,
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Session timeout enforcement
if (!empty($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > SESSION_TIMEOUT) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? false);
    }
    session_destroy();
}

// Check admin session
if (empty($_SESSION['admin'])) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Akses ditolak.';
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8-sig');
header('Content-Disposition: attachment; filename="rsvp-undangan-andi-februana.csv"');

// UTF-8 BOM for Excel compatibility
echo "\xEF\xBB\xBF";

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, ['nama', 'status', 'ucapan', 'waktu'], ',');

// Stream data
try {
    if (!is_readable(DB_PATH)) {
        exit;
    }
    
    $db = new SQLite3(DB_PATH, SQLITE3_OPEN_READONLY);
    $result = $db->query('SELECT nama, status, ucapan, created_at FROM tamu WHERE visible = 1 ORDER BY id DESC');
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        fputcsv($output, [
            $row['nama'] ?? '',
            $row['status'] ?? '',
            $row['ucapan'] ?? '',
            $row['created_at'] ?? ''
        ], ',');
    }
    
    $db->close();
} catch (Throwable $e) {
    error_log('Export RSVP error: ' . $e->getMessage());
}

fclose($output);
exit;
?>
