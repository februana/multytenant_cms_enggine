<?php
require_once __DIR__ . '/config.php';

// Minimal session security (not logging in required to export in admin view)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

header('Content-Type: text/csv; charset=utf-8-sig');
header('Content-Disposition: attachment; filename="rsvp-undangan-andi-februana.csv"');
echo "\xEF\xBB\xBF";
$output = fopen('php://output', 'w');
fputcsv($output, ['nama', 'status', 'ucapan', 'waktu'], ',');

function csv_safe($value) {
    // Prevent Excel/CSV injection by prefixing dangerous leading chars
    if ($value === null) return '';
    $v = (string)$value;
    if ($v === '') return '';
    if (in_array($v[0], ['=', '+', '-', '@'], true)) return "'" . $v;
    return $v;
}

try {
    if (!is_readable(DB_PATH)) { exit; }
    $db = new SQLite3(DB_PATH, SQLITE3_OPEN_READONLY);
    $result = $db->query('SELECT nama, status, ucapan, created_at FROM tamu WHERE visible = 1 ORDER BY id DESC');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        fputcsv($output, [
            csv_safe($row['nama'] ?? ''),
            csv_safe($row['status'] ?? ''),
            csv_safe($row['ucapan'] ?? ''),
            csv_safe($row['created_at'] ?? '')
        ], ',');
    }
    $db->close();
} catch (Throwable $e) {
    error_log('Export RSVP error: ' . $e->getMessage());
}
fclose($output);
exit;
?>
