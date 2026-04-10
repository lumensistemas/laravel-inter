<?php

/**
 * Register the billing webhook URL with Inter's API.
 *
 * Usage:
 *   php scripts/register-webhook.php [url]
 *
 * If no URL is provided, uses TEST_EXPOSE_URL from .env.
 * After registering, retrieves the webhook config to confirm.
 */

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Dotenv\Dotenv;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use LumenSistemas\Inter\Enums\Environment;
use LumenSistemas\Inter\Exceptions\InterException;
use LumenSistemas\Inter\Http\InterClient;
use LumenSistemas\Inter\Http\TokenManager;
use LumenSistemas\Inter\Resources\BillingWebhookResource;

// ── Load .env ───────────────────────────────────────────────

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// ── Bootstrap facades ───────────────────────────────────────

$app = new Illuminate\Foundation\Application(dirname(__DIR__));
Facade::setFacadeApplication($app);
Cache::swap(new CacheRepository(new ArrayStore()));
Http::swap(new Illuminate\Http\Client\Factory());

// ── Resolve webhook URL ─────────────────────────────────────

$webhookUrl = $argv[1] ?? $_ENV['TEST_EXPOSE_URL'] ?? '';

if ($webhookUrl === '') {
    echo "Usage: php scripts/register-webhook.php [url]\n";
    echo "Or set TEST_EXPOSE_URL in .env\n";
    exit(1);
}

// Append /webhook path if it's just a base URL
if (!str_contains($webhookUrl, '/webhook')) {
    $webhookUrl = mb_rtrim($webhookUrl, '/').'/webhook';
}

// ── Build client ────────────────────────────────────────────

$clientId = $_ENV['INTER_CLIENT_ID'] ?? '';
$clientSecret = $_ENV['INTER_CLIENT_SECRET'] ?? '';
$certificate = $_ENV['INTER_CERTIFICATE'] ?? '';
$privateKey = $_ENV['INTER_PRIVATE_KEY'] ?? '';
$contaCorrente = $_ENV['INTER_CONTA_CORRENTE'] ?? '';

if ($clientId === '' || $clientSecret === '' || $certificate === '' || $privateKey === '') {
    echo "Missing INTER_* credentials in .env\n";
    exit(1);
}

$environment = Environment::Sandbox;

$tokenManager = new TokenManager(
    clientId: $clientId,
    clientSecret: $clientSecret,
    certificate: $certificate,
    privateKey: $privateKey,
    environment: $environment,
    scopes: 'boleto-cobranca.read boleto-cobranca.write',
    cachePrefix: 'inter_sandbox',
);

$client = new InterClient(
    tokenManager: $tokenManager,
    environment: $environment,
    certificate: $certificate,
    privateKey: $privateKey,
    timeout: 30,
    retry: 3,
    userAgent: 'laravel-inter-sandbox',
    contaCorrente: $contaCorrente,
);

$webhook = new BillingWebhookResource($client);

// ── Register ────────────────────────────────────────────────

echo "Registering webhook: {$webhookUrl}\n";

try {
    $webhook->create($webhookUrl);
    echo "OK\n\n";
} catch (InterException $e) {
    echo "FAILED: {$e->getMessage()}\n";
    if ($e->violacoes !== []) {
        echo json_encode($e->violacoes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
    }
    exit(1);
}

// ── Confirm ─────────────────────────────────────────────────

echo "Retrieving webhook config...\n";

try {
    $response = $webhook->retrieve();
    echo json_encode($response->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
} catch (InterException $e) {
    echo "Could not retrieve: {$e->getMessage()}\n";
}
