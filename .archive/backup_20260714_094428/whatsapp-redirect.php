<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
$config = load_config();
$url = build_whatsapp_link($config);
echo json_encode(['success' => true, 'url' => $url, 'timestamp' => time()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
