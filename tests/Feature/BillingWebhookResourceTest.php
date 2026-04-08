<?php

declare(strict_types=1);

use LumenSistemas\Inter\Contracts\InterClientInterface;
use LumenSistemas\Inter\Http\PaginatedResponse;
use LumenSistemas\Inter\Http\Response;
use LumenSistemas\Inter\Resources\BillingWebhookResource;

function createBillingWebhookResource(?InterClientInterface $client = null): BillingWebhookResource
{
    $client ??= Mockery::mock(InterClientInterface::class);

    return new BillingWebhookResource($client);
}

describe('create', function (): void {
    it('sends PUT with webhook URL', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('put')
            ->once()
            ->withArgs(fn (string $path, array $data): bool => $path === '/cobranca/v3/cobrancas/webhook'
                && $data['webhookUrl'] === 'https://example.com/webhook')
            ->andReturn(new Response([]));

        $response = createBillingWebhookResource($client)->create('https://example.com/webhook');

        expect($response->data)->toBeEmpty();
    });
});

describe('retrieve', function (): void {
    it('sends GET to webhook endpoint', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/cobranca/v3/cobrancas/webhook')
            ->andReturn(new Response([
                'webhookUrl' => 'https://example.com/webhook',
                'criacao' => '2026-04-01T00:00:00Z',
            ]));

        $response = createBillingWebhookResource($client)->retrieve();

        expect($response->data['webhookUrl'])->toBe('https://example.com/webhook')
            ->and($response->data)->toHaveKey('criacao');
    });
});

describe('delete', function (): void {
    it('sends DELETE to webhook endpoint', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('delete')
            ->once()
            ->with('/cobranca/v3/cobrancas/webhook')
            ->andReturn(new Response([]));

        $response = createBillingWebhookResource($client)->delete();

        expect($response->data)->toBeEmpty();
    });
});

describe('callbacks', function (): void {
    it('sends GET with date range', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('list')
            ->once()
            ->withArgs(fn (string $path, string $key, array $query): bool => $path === '/cobranca/v3/cobrancas/webhook/callbacks'
                && $key === 'data'
                && $query['dataHoraInicio'] === '2026-04-01T00:00:00Z'
                && $query['dataHoraFim'] === '2026-04-30T23:59:59Z')
            ->andReturn(new PaginatedResponse(
                data: [
                    [
                        'webhookUrl' => 'https://example.com/webhook',
                        'numeroTentativa' => 1,
                        'sucesso' => true,
                        'httpStatus' => 200,
                    ],
                ],
                totalPaginas: 1,
                totalElementos: 1,
                numeroDeElementos: 1,
                ultimaPagina: true,
                primeiraPagina: true,
                tamanhoPagina: 10,
            ));

        $response = createBillingWebhookResource($client)->callbacks(
            dataHoraInicio: '2026-04-01T00:00:00Z',
            dataHoraFim: '2026-04-30T23:59:59Z',
        );

        expect($response)->toHaveCount(1)
            ->and($response->data[0]['sucesso'])->toBeTrue();
    });

    it('passes optional filters', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('list')
            ->once()
            ->withArgs(fn (string $path, string $key, array $query): bool => $query['codigoSolicitacao'] === 'abc-123'
                && $query['pagina'] === 0
                && $query['itensPorPagina'] === 5)
            ->andReturn(new PaginatedResponse(
                data: [],
                totalPaginas: 0,
                totalElementos: 0,
                numeroDeElementos: 0,
                ultimaPagina: true,
                primeiraPagina: true,
                tamanhoPagina: 5,
            ));

        createBillingWebhookResource($client)->callbacks(
            dataHoraInicio: '2026-04-01T00:00:00Z',
            dataHoraFim: '2026-04-30T23:59:59Z',
            pagina: 0,
            itensPorPagina: 5,
            codigoSolicitacao: 'abc-123',
        );
    });

    it('excludes null filters', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('list')
            ->once()
            ->withArgs(fn (string $path, string $key, array $query): bool => count($query) === 2)
            ->andReturn(new PaginatedResponse(
                data: [],
                totalPaginas: 0,
                totalElementos: 0,
                numeroDeElementos: 0,
                ultimaPagina: true,
                primeiraPagina: true,
                tamanhoPagina: 10,
            ));

        createBillingWebhookResource($client)->callbacks(
            dataHoraInicio: '2026-04-01T00:00:00Z',
            dataHoraFim: '2026-04-30T23:59:59Z',
        );
    });
});
