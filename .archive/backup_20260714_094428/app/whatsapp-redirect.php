<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success'=>true, 'url'=>'https://wa.me/' . preg_replace('/\D+/', '', WHATSAPP_NUMBER) . '?text=' . rawurlencode(WHATSAPP_MESSAGE), 'timestamp'=>time()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
