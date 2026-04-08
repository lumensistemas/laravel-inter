<?php

declare(strict_types=1);

use LumenSistemas\Inter\Enums\Environment;
use LumenSistemas\Inter\Http\InterClient;
use LumenSistemas\Inter\Http\TokenManager;
use LumenSistemas\Inter\Resources\BillingResource;

function interCredentials(): array
{
    return [
        'client_id' => env('INTER_CLIENT_ID', ''),
        'client_secret' => env('INTER_CLIENT_SECRET', ''),
        'certificate' => env('INTER_CERTIFICATE', ''),
        'private_key' => env('INTER_PRIVATE_KEY', ''),
        'conta_corrente' => env('INTER_CONTA_CORRENTE', ''),
    ];
}

function hasCredentials(): bool
{
    $creds = interCredentials();

    return $creds['client_id'] !== ''
        && $creds['client_secret'] !== ''
        && $creds['certificate'] !== ''
        && $creds['private_key'] !== '';
}

function createRealBillingResource(): BillingResource
{
    $creds = interCredentials();
    $environment = Environment::Sandbox;

    $tokenManager = new TokenManager(
        clientId: $creds['client_id'],
        clientSecret: $creds['client_secret'],
        certificate: $creds['certificate'],
        privateKey: $creds['private_key'],
        environment: $environment,
        scopes: 'boleto-cobranca.read boleto-cobranca.write',
        cachePrefix: 'inter_integration_test',
    );

    $client = new InterClient(
        tokenManager: $tokenManager,
        environment: $environment,
        certificate: $creds['certificate'],
        privateKey: $creds['private_key'],
        timeout: 30,
        retry: 3,
        userAgent: 'laravel-inter-integration-test',
        contaCorrente: $creds['conta_corrente'],
    );

    return new BillingResource($client);
}

beforeEach(function (): void {
    if (!hasCredentials()) {
        $this->markTestSkipped('Integration tests require INTER_* env credentials.');
    }
});

describe('billing integration', function (): void {
    it('creates a billing and retrieves it', function (): void {
        $billing = createRealBillingResource();

        // Create
        $response = $billing->create(
            seuNumero: fake()->lexify('TEST-##########'),
            valorNominal: fake()->randomFloat(2, 1, 100),
            dataVencimento: fake()->dateTimeBetween('+1 day', '+45 days')->format('Y-m-d'),
            numDiasAgenda: 30,
            pagador: [
                'cpfCnpj' => fake('pt_BR')->cnpj(false),
                'tipoPessoa' => 'JURIDICA',
                'nome' => 'Teste Integração',
                'endereco' => 'Rua Teste',
                'numero' => '123',
                'bairro' => 'Centro',
                'cidade' => 'Curitiba',
                'uf' => 'PR',
                'cep' => '80000000',
            ],
        );

        expect($response->data)->toHaveKey('codigoSolicitacao');

        $codigoSolicitacao = $response->data['codigoSolicitacao'];
        expect($codigoSolicitacao)->toBeString()->not->toBeEmpty();

        // Find
        $found = $billing->find($codigoSolicitacao);

        expect($found->data)->toHaveKey('cobranca')
            ->and($found->data)->toHaveKey('boleto')
            ->and($found->data)->toHaveKey('pix')
            ->and($found->data['cobranca']['codigoSolicitacao'])->toBe($codigoSolicitacao);
    });

    it('lists billings within a date range', function (): void {
        $billing = createRealBillingResource();

        $response = $billing->list(
            dataInicial: date('Y-m-d', strtotime('-30 days')),
            dataFinal: date('Y-m-d'),
        );

        expect($response->totalPaginas)->toBeGreaterThanOrEqual(0)
            ->and($response->data)->toBeArray();
    });

    it('retrieves billing summary', function (): void {
        $billing = createRealBillingResource();

        $response = $billing->summary(
            dataInicial: date('Y-m-d', strtotime('-30 days')),
            dataFinal: date('Y-m-d'),
        );

        expect($response->data)->toBeArray();
    });

    it('creates and cancels a billing', function (): void {
        $billing = createRealBillingResource();

        $response = $billing->create(
            seuNumero: fake()->lexify('CANCEL-########'),
            valorNominal: fake()->randomFloat(2, 1, 100),
            dataVencimento: fake()->dateTimeBetween('+1 day', '+45 days')->format('Y-m-d'),
            numDiasAgenda: 0,
            pagador: [
                'cpfCnpj' => fake('pt_BR')->cnpj(false),
                'tipoPessoa' => 'JURIDICA',
                'nome' => 'Teste Cancelamento',
                'endereco' => 'Rua Teste',
                'numero' => '456',
                'bairro' => 'Centro',
                'cidade' => 'Curitiba',
                'uf' => 'PR',
                'cep' => '80000000',
            ],
        );

        expect($response->data)->toHaveKey('codigoSolicitacao');

        $codigoSolicitacao = $response->data['codigoSolicitacao'];
        expect($codigoSolicitacao)->toBeString()->not->toBeEmpty();

        $cancelResponse = $billing->cancel($codigoSolicitacao, 'A PEDIDO DO CLIENTE');

        expect($cancelResponse->data)->toBeEmpty();
    })->only();

    it('retrieves billing PDF', function (): void {
        $billing = createRealBillingResource();

        // Create a billing to get its PDF
        $response = $billing->create(
            seuNumero: fake()->lexify('PDF-###########'),
            valorNominal: fake()->randomFloat(2, 1, 100),
            dataVencimento: fake()->dateTimeBetween('+1 day', '+45 days')->format('Y-m-d'),
            numDiasAgenda: 30,
            pagador: [
                'cpfCnpj' => fake('pt_BR')->cnpj(false),
                'tipoPessoa' => 'JURIDICA',
                'nome' => 'Teste PDF',
                'endereco' => 'Rua Teste',
                'numero' => '789',
                'bairro' => 'Centro',
                'cidade' => 'Curitiba',
                'uf' => 'PR',
                'cep' => '80000000',
            ],
        );

        $codigoSolicitacao = $response->data['codigoSolicitacao'];

        $pdf = $billing->pdf($codigoSolicitacao);

        expect($pdf->data)->toHaveKey('pdf')
            ->and($pdf->data['pdf'])->toBeString()->not->toBeEmpty();

        file_put_contents(__DIR__.'/../temp/billing_'.$codigoSolicitacao.'.pdf', base64_decode((string) $pdf->data['pdf']));
    });

    it('paginates through all billings', function (): void {
        $billing = createRealBillingResource();

        $count = 0;

        foreach ($billing->all(['dataInicial' => date('Y-m-d', strtotime('-30 days')), 'dataFinal' => date('Y-m-d')], itensPorPagina: 2) as $item) {
            expect($item)->toBeArray();
            ++$count;

            if ($count >= 5) {
                break; // Don't iterate forever
            }
        }

        expect($count)->toBeGreaterThanOrEqual(0);
    });
});
