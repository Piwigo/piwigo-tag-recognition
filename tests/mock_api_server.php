<?php

/**
 * Minimal HTTP server used by the PHPUnit test suite.
 *
 * Each test writes its desired OpenAI-style JSON response to
 * tests/fixtures/current_response.json before making an HTTP call.
 * This script reads that file and echoes it back for any request path,
 * so curl in the production code sees a valid server response.
 *
 * Run via: php -S 127.0.0.1:17890 tests/mock_api_server.php
 */

$fixture = __DIR__ . '/fixtures/current_response.json';

if (!file_exists($fixture)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'mock_api_server: no fixture file at ' . $fixture]);
    exit;
}

$body = file_get_contents($fixture);
if ($body === false) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'mock_api_server: cannot read fixture file']);
    exit;
}

http_response_code(200);
header('Content-Type: application/json');
echo $body;
