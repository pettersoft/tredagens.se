<?php
// Issues a fresh ALTCHA proof-of-work challenge for the contact form.
require_once __DIR__ . '/lib/altcha.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

try {
    echo json_encode(altcha_create_challenge(), JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('altcha-challenge: ' . $e->getMessage());
    http_response_code(503);
    echo '{"error":"unavailable"}';
}
