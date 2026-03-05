<?php
declare(strict_types=1);

/**
 * IndexNow key endpoint.
 * Accessed via rewrite: /{key}.txt -> indexnow-key.php?key={key}
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/models/Setting.php';

$requestedKey = trim((string) ($_GET['key'] ?? ''));
if ($requestedKey === '' || preg_match('/^[A-Za-z0-9_-]{8,128}$/', $requestedKey) !== 1) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not Found';
    exit;
}

$configuredKey = trim((string) (Setting::get('indexnow_key') ?? ''));
if ($configuredKey === '' || !hash_equals($configuredKey, $requestedKey)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not Found';
    exit;
}

http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=300');
echo $configuredKey;
