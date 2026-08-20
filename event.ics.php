<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$currentTenant = current_tenant(true);
$config = load_config();
$ics = build_event_ics($config);
$filename = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string)($config['site']['title'] ?? 'Undangan')) ?: 'Undangan';
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '.ics"');
echo $ics;
