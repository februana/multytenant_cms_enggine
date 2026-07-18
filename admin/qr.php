<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$data = trim((string)($_GET['data'] ?? $_GET['text'] ?? ''));

if ($data === '') {
    http_response_code(400);
    exit('Missing QR data');
}

$options = new QROptions([
    // output PNG
    'outputType' => QRCode::OUTPUT_IMAGE_PNG,

    // return raw binary instead of base64 data URI
    'outputBase64' => false,

    // Error correction level H
    'eccLevel' => QRCode::ECC_H,

    // ukuran QR
    'scale' => 10,

    // pinggir putih
    'addQuietzone' => true,

    // transparansi
    'imageTransparent' => false,

    // warna hitam putih standar
    'bgColor' => [255,255,255],
    'fgColor' => [0,0,0],
]);

$png = (new QRCode($options))->render($data);

// Clear any output buffers to ensure no extra output before PNG bytes
ob_end_clean();

header('Content-Type: image/png');
header('Content-Length: '.strlen($png));
header('Cache-Control: public, max-age=86400');

echo $png;
exit;
