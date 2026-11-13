<?php
declare(strict_types=1);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$candidate = realpath(__DIR__ . '/' . ltrim($path, '/'));
$inside = $candidate !== false && str_starts_with($candidate, __DIR__ . DIRECTORY_SEPARATOR);
$upload = str_starts_with($path, '/uploads/');
if ($inside && is_file($candidate) && preg_match('/\.(?:css|js|png|jpg|jpeg|webp|gif|svg|ico|woff2?|mp3|m4a|ogg|wav|webm|mp4|pdf)$/i', $candidate)
    && (!$upload || !preg_match('/\.(?:svg|js|css)$/i', $candidate))) return false;
if (preg_match('#(?:^|/)\.|\.(?:env|sql|ini|log|bak|php)$#i', $path)) {
    http_response_code(404); exit('Not found');
}
require __DIR__ . '/index.php';
