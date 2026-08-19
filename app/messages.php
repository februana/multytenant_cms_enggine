<?php
// Load consolidated canonical config
require_once dirname(__DIR__) . '/config.php';
header('Content-Type: application/json; charset=utf-8');
try {
    init_database();
    $tenant = current_tenant(false);
    if (!is_array($tenant)) {
        http_response_code(404);
        echo json_encode(['error' => 'Domain tidak terdaftar atau sedang ditangguhkan.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $db = tenant_database(true);
    $tableExists = $db->querySingle("SELECT 1 FROM sqlite_master WHERE type='table' AND name='tamu'");
    if (!$tableExists) {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $checkCol = table_has_column($db, 'tamu', 'visible');
    $where = $checkCol ? 'tenant_id = :tenant_id AND visible = 1' : 'tenant_id = :tenant_id';
    $stmt = $db->prepare("SELECT nama,status,ucapan,created_at FROM tamu WHERE $where ORDER BY id DESC LIMIT 50");
    $stmt->bindValue(':tenant_id', (int)$tenant['id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $rows = [];
    if ($result) {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $rows[] = $row;
    }
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('messages.php error: ' . $e->getMessage());
    echo json_encode([], JSON_UNESCAPED_UNICODE);
}
?>
