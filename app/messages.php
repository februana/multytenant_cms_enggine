<?php
// Load proposed consolidated config if present, otherwise fallback to existing config
if (is_readable(__DIR__ . '/config.proposed.php')) {
    require_once __DIR__ . '/config.proposed.php';
} else {
    require_once __DIR__ . '/config.php';
}
header('Content-Type: application/json; charset=utf-8');
if (!is_readable(DB_PATH)) { echo json_encode([], JSON_UNESCAPED_UNICODE); exit; }
try {
    $db = new SQLite3(DB_PATH, SQLITE3_OPEN_READONLY);
    $checkCol = $db->querySingle("SELECT 1 FROM pragma_table_info('tamu') WHERE name='visible'");
    $visibleClause = $checkCol ? 'WHERE visible = 1' : '';
    $result = $db->query("SELECT nama,status,ucapan,created_at FROM tamu $visibleClause ORDER BY id DESC LIMIT 50");
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) $rows[] = $row;
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('messages.php error: ' . $e->getMessage());
    echo json_encode([], JSON_UNESCAPED_UNICODE);
}
?>
