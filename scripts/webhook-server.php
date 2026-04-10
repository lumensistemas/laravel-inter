<?php

/**
 * Inter Webhook Receiver
 *
 * A lightweight PHP built-in server script that records incoming webhook
 * payloads to disk for inspection during development.
 *
 * Usage:
 *   php -S 0.0.0.0:8008 scripts/webhook-server.php
 *
 * Endpoints:
 *   POST   /           — Record an incoming Inter webhook payload (responds 200)
 *   GET    /events     — Return all recorded payloads as a JSON array
 *   DELETE /events     — Clear all recorded payloads (returns 204)
 *   GET    /health     — Returns {"ok":true} for liveness checks
 *
 * Payloads are stored as individual JSON files under scripts/logs/webhooks/.
 */

declare(strict_types=1);

// ──────────────────────────────────────────────────────────────
// Bootstrap
// ──────────────────────────────────────────────────────────────

$storageDir = __DIR__.'/logs/webhooks';

if (! is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

function respond(int $status, mixed $body = null): never
{
    http_response_code($status);
    header('Content-Type: application/json');

    if ($body !== null) {
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    exit;
}

/** @return list<array<string, mixed>> */
function readEvents(string $dir): array
{
    $files = glob($dir.'/*.json');

    if ($files === false || $files === []) {
        return [];
    }

    sort($files);

    $events = [];

    foreach ($files as $file) {
        $content = file_get_contents($file);

        if ($content === false) {
            continue;
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($content, true);

        if (is_array($decoded)) {
            $events[] = $decoded;
        }
    }

    return $events;
}

function clearEvents(string $dir): void
{
    $files = glob($dir.'/*.json');

    if ($files === false) {
        return;
    }

    foreach ($files as $file) {
        unlink($file);
    }
}

// ──────────────────────────────────────────────────────────────
// Routing
// ──────────────────────────────────────────────────────────────

// Health check
if ($path === '/health') {
    respond(200, ['ok' => true]);
}

// Event log
if ($path === '/events') {
    if ($method === 'GET') {
        respond(200, readEvents($storageDir));
    }

    if ($method === 'DELETE') {
        clearEvents($storageDir);
        respond(204);
    }

    respond(405, ['error' => 'Method not allowed']);
}

// Incoming webhook (POST to any path)
if ($method === 'POST') {
    $body = file_get_contents('php://input');

    if ($body === false || $body === '') {
        respond(400, ['error' => 'Empty request body']);
    }

    /** @var array<string, mixed>|null $decoded */
    $decoded = json_decode($body, true);

    if (! is_array($decoded)) {
        respond(400, ['error' => 'Request body is not valid JSON']);
    }

    $envelope = [
        'received_at' => date('c'),
        'method' => $method,
        'path' => $path,
        'headers' => getallheaders(),
        'payload' => $decoded,
    ];

    $filename = sprintf(
        '%s/%s_%s.json',
        $storageDir,
        number_format(microtime(true), 6, '.', ''),
        bin2hex(random_bytes(4)),
    );

    file_put_contents($filename, json_encode($envelope, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Log to stdout
    $timestamp = date('Y-m-d H:i:s');
    fwrite(STDERR, "\n[{$timestamp}] {$method} {$path}\n");
    fwrite(STDERR, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");

    respond(200, ['received' => true]);
}

// Fallback (GET /, expose health probes, etc.)
respond(200, ['ok' => true]);
