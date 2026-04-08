<?php

/**
 * Sandbox script for testing Inter API calls interactively.
 *
 * Usage:
 *   php tests/sandbox.php
 *
 * Requires a .env file at the project root with INTER_* credentials.
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
use LumenSistemas\Inter\Resources\BillingResource;
use LumenSistemas\Inter\Resources\BillingWebhookResource;

// ──────────────────────────────────────────────────────────────
// Load .env
// ──────────────────────────────────────────────────────────────

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// ──────────────────────────────────────────────────────────────
// Bootstrap minimal Laravel container (required by Http/Cache facades)
// ──────────────────────────────────────────────────────────────

$app = new Illuminate\Foundation\Application(dirname(__DIR__));
Facade::setFacadeApplication($app);
Cache::swap(new CacheRepository(new ArrayStore()));
Http::swap(new Illuminate\Http\Client\Factory());

$clientId = $_ENV['INTER_CLIENT_ID'] ?? '';
$clientSecret = $_ENV['INTER_CLIENT_SECRET'] ?? '';
$certificate = $_ENV['INTER_CERTIFICATE'] ?? '';
$privateKey = $_ENV['INTER_PRIVATE_KEY'] ?? '';
$contaCorrente = $_ENV['INTER_CONTA_CORRENTE'] ?? '';

if ($clientId === '' || $clientSecret === '' || $certificate === '' || $privateKey === '') {
    echo "Missing INTER_* credentials in .env file.\n";
    echo "Copy .env.example to .env and fill in your sandbox credentials.\n";
    exit(1);
}

// ──────────────────────────────────────────────────────────────
// Bootstrap
// ──────────────────────────────────────────────────────────────

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

$billing = new BillingResource($client);
$billingWebhook = new BillingWebhookResource($client);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

function dump_response(string $label, mixed $data): void
{
    echo "\n── {$label} ".str_repeat('─', max(1, 60 - mb_strlen($label)))."\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
}

// ──────────────────────────────────────────────────────────────
// Playground — uncomment/edit the sections you want to test
// ──────────────────────────────────────────────────────────────

// --- Create a billing ---
// try {
//     $response = $billing->create(
//         seuNumero: 'SNDB-'.time(),
//         valorNominal: 10.50,
//         dataVencimento: date('Y-m-d', strtotime('+30 days')),
//         numDiasAgenda: 30,
//         pagador: [
//             'cpfCnpj' => '12345678901234',
//             'tipoPessoa' => 'JURIDICA',
//             'nome' => 'Empresa Teste',
//             'endereco' => 'Rua Teste',
//             'numero' => '123',
//             'bairro' => 'Centro',
//             'cidade' => 'Curitiba',
//             'uf' => 'PR',
//             'cep' => '80000000',
//         ],
//     );
//     dump_response('Create Billing', $response->data);
// } catch (InterException $e) {
//     dump_response('Create Billing Error', [
//         'message' => $e->getMessage(),
//         'violacoes' => $e->violacoes,
//     ]);
//     exit(1);
// }

// --- Find a billing ---
// $response = $billing->find('040a5a28-2841-4cd0-a80c-5dd89661624d');
// dump_response('Find Billing', $response->data);

// --- List billings ---
// $response = $billing->list(
//     dataInicial: date('Y-m-d', strtotime('-30 days')),
//     dataFinal: date('Y-m-d'),
// );
// dump_response('List Billings', [
//     'totalPaginas' => $response->totalPaginas,
//     'totalElementos' => $response->totalElementos,
//     'items' => $response->data,
// ]);

// --- Summary ---
// $response = $billing->summary(
//     dataInicial: date('Y-m-d', strtotime('-30 days')),
//     dataFinal: date('Y-m-d'),
// );
// dump_response('Summary', $response->data);

// --- Cancel a billing ---
// $response = $billing->cancel('PASTE-CODIGO-SOLICITACAO-HERE', 'A PEDIDO DO CLIENTE');
// dump_response('Cancel', $response->data);

// --- Get PDF ---
// $response = $billing->pdf('040a5a28-2841-4cd0-a80c-5dd89661624d');
// file_put_contents(__DIR__.'/temp/billing.pdf', base64_decode($response->data['pdf']));
// dump_response('PDF saved', ['file' => __DIR__.'/temp/billing.pdf']);

// --- Paginate all ---
// foreach ($billing->all(['dataInicial' => date('Y-m-d', strtotime('-30 days')), 'dataFinal' => date('Y-m-d')], itensPorPagina: 2) as $i => $item) {
//     dump_response("Item #{$i}", $item);
//     if ($i >= 4) break;
// }

// ──────────────────────────────────────────────────────────────
// Billing Webhook
// ──────────────────────────────────────────────────────────────

// --- Create/update webhook ---
// $response = $billingWebhook->create('https://example.com/webhooks/inter/billing');
// dump_response('Create Webhook', $response->data);

// --- Retrieve webhook ---
// $response = $billingWebhook->retrieve();
// dump_response('Retrieve Webhook', $response->data);

// --- Delete webhook ---
// $response = $billingWebhook->delete();
// dump_response('Delete Webhook', $response->data);

// --- Retrieve callbacks ---
// $response = $billingWebhook->callbacks(
//     dataHoraInicio: date('c', strtotime('-30 days')),
//     dataHoraFim: date('c'),
// );
// dump_response('Webhook Callbacks', [
//     'totalPaginas' => $response->totalPaginas,
//     'totalElementos' => $response->totalElementos,
//     'items' => $response->data,
// ]);

echo "\nSandbox ready. Uncomment the sections you want to test.\n";
