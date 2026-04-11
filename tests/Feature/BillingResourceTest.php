<?php

declare(strict_types=1);

use LumenSistemas\Inter\Contracts\InterClientInterface;
use LumenSistemas\Inter\Http\PaginatedResponse;
use LumenSistemas\Inter\Http\Response;
use LumenSistemas\Inter\Resources\BillingResource;

function createBillingResource(?InterClientInterface $client = null): BillingResource
{
    $client ??= Mockery::mock(InterClientInterface::class);

    return new BillingResource($client);
}

describe('create', function (): void {
    it('sends a POST with required fields', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('post')
            ->once()
            ->withArgs(fn (string $path, array $data): bool => $path === '/cobranca/v3/cobrancas'
                && $data['seuNumero'] === 'INV-001'
                && $data['valorNominal'] === 150.00
                && $data['dataVencimento'] === '2026-05-01'
                && $data['numDiasAgenda'] === 30
                && $data['pagador']['cpfCnpj'] === '12345678901')
            ->andReturn(new Response(['codigoSolicitacao' => 'abc-123']));

        $response = createBillingResource($client)->create(
            seuNumero: 'INV-001',
            valorNominal: 150.00,
            dataVencimento: '2026-05-01',
            numDiasAgenda: 30,
            pagador: ['cpfCnpj' => '12345678901', 'nome' => 'John Doe'],
        );

        expect($response->data['codigoSolicitacao'])->toBe('abc-123');
    });

    it('includes optional fields when provided', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('post')
            ->once()
            ->withArgs(fn (string $path, array $data): bool => isset($data['desconto'], $data['multa'], $data['mora'], $data['mensagem'])
                && $data['desconto']['codigo'] === 'VALORFIXODATAINFORMADA'
                && $data['multa']['codigo'] === 'PERCENTUAL'
                && $data['mora']['codigo'] === 'TAXAMENSAL'
                && $data['mensagem']['linha1'] === 'Obrigado')
            ->andReturn(new Response(['codigoSolicitacao' => 'def-456']));

        createBillingResource($client)->create(
            seuNumero: 'INV-002',
            valorNominal: 200.00,
            dataVencimento: '2026-06-01',
            numDiasAgenda: 15,
            pagador: ['cpfCnpj' => '98765432100', 'nome' => 'Jane Doe'],
            desconto: ['codigo' => 'VALORFIXODATAINFORMADA', 'valor' => 10.00],
            multa: ['codigo' => 'PERCENTUAL', 'taxa' => 2.0],
            mora: ['codigo' => 'TAXAMENSAL', 'taxa' => 1.0],
            mensagem: ['linha1' => 'Obrigado'],
        );
    });

    it('excludes null optional fields from request', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('post')
            ->once()
            ->withArgs(fn (string $path, array $data): bool => !array_key_exists('desconto', $data)
                && !array_key_exists('multa', $data)
                && !array_key_exists('mora', $data)
                && !array_key_exists('mensagem', $data)
                && !array_key_exists('beneficiarioFinal', $data)
                && !array_key_exists('formasRecebimento', $data))
            ->andReturn(new Response(['codigoSolicitacao' => 'ghi-789']));

        createBillingResource($client)->create(
            seuNumero: 'INV-003',
            valorNominal: 100.00,
            dataVencimento: '2026-07-01',
            numDiasAgenda: 10,
            pagador: ['cpfCnpj' => '11111111111'],
        );
    });
});

describe('find', function (): void {
    it('sends GET to the correct path', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/cobranca/v3/cobrancas/abc-123-def')
            ->andReturn(new Response([
                'cobranca' => ['codigoSolicitacao' => 'abc-123-def', 'situacao' => 'A_RECEBER'],
                'boleto' => ['nossoNumero' => '00001', 'codigoBarras' => '12345'],
                'pix' => ['txid' => 'pix-123', 'pixCopiaECola' => '00020126...'],
            ]));

        $response = createBillingResource($client)->find('abc-123-def');

        expect($response->data['cobranca']['situacao'])->toBe('A_RECEBER')
            ->and($response->data['boleto']['nossoNumero'])->toBe('00001')
            ->and($response->data['pix']['txid'])->toBe('pix-123');
    });
});

describe('update', function (): void {
    it('sends PATCH with both fields', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('patch')
            ->once()
            ->withArgs(fn (string $path, array $data): bool => $path === '/cobranca/v3/cobrancas/abc-123'
                && $data['dataVencimento'] === '2026-06-15'
                && $data['valorNominal'] === 200.00)
            ->andReturn(new Response([]));

        createBillingResource($client)->update(
            codigoSolicitacao: 'abc-123',
            dataVencimento: '2026-06-15',
            valorNominal: 200.00,
        );
    });

    it('sends PATCH with only dataVencimento', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('patch')
            ->once()
            ->withArgs(fn (string $path, array $data): bool => $path === '/cobranca/v3/cobrancas/abc-123'
                && $data['dataVencimento'] === '2026-06-15'
                && !array_key_exists('valorNominal', $data))
            ->andReturn(new Response([]));

        createBillingResource($client)->update(
            codigoSolicitacao: 'abc-123',
            dataVencimento: '2026-06-15',
        );
    });

    it('sends PATCH with only valorNominal', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('patch')
            ->once()
            ->withArgs(fn (string $path, array $data): bool => $path === '/cobranca/v3/cobrancas/abc-123'
                && $data['valorNominal'] === 200.00
                && !array_key_exists('dataVencimento', $data))
            ->andReturn(new Response([]));

        createBillingResource($client)->update(
            codigoSolicitacao: 'abc-123',
            valorNominal: 200.00,
        );
    });
});

describe('updateStatus', function (): void {
    it('sends GET to the edicao endpoint', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/cobranca/v3/cobrancas/edicao/edit-456-def')
            ->andReturn(new Response(['status' => 'SUCESSO']));

        $response = createBillingResource($client)->updateStatus('edit-456-def');

        expect($response->data['status'])->toBe('SUCESSO');
    });
});

describe('list', function (): void {
    it('sends GET with required date range', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('list')
            ->once()
            ->withArgs(fn (string $path, string $key, array $query): bool => $path === '/cobranca/v3/cobrancas'
                && $key === 'cobrancas'
                && $query['dataInicial'] === '2026-04-01'
                && $query['dataFinal'] === '2026-04-30')
            ->andReturn(new PaginatedResponse(
                data: [['seuNumero' => 'INV-001']],
                totalPaginas: 1,
                totalElementos: 1,
                numeroDeElementos: 1,
                ultimaPagina: true,
                primeiraPagina: true,
                tamanhoPagina: 10,
            ));

        $response = createBillingResource($client)->list(
            dataInicial: '2026-04-01',
            dataFinal: '2026-04-30',
        );

        expect($response)->toHaveCount(1)
            ->and($response->totalElementos)->toBe(1)
            ->and($response->ultimaPagina)->toBeTrue();
    });

    it('passes optional filters and sorting', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('list')
            ->once()
            ->withArgs(fn (string $path, string $key, array $query): bool => $query['situacao'] === 'A_RECEBER'
                && $query['filtrarDataPor'] === 'VENCIMENTO'
                && $query['ordenarPor'] === 'DATA_VENCIMENTO'
                && $query['tipoOrdenacao'] === 'DESC')
            ->andReturn(new PaginatedResponse(
                data: [],
                totalPaginas: 0,
                totalElementos: 0,
                numeroDeElementos: 0,
                ultimaPagina: true,
                primeiraPagina: true,
                tamanhoPagina: 10,
            ));

        createBillingResource($client)->list(
            dataInicial: '2026-04-01',
            dataFinal: '2026-04-30',
            situacao: 'A_RECEBER',
            filtrarDataPor: 'VENCIMENTO',
            ordenarPor: 'DATA_VENCIMENTO',
            tipoOrdenacao: 'DESC',
        );
    });

    it('excludes null filters from query', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('list')
            ->once()
            ->withArgs(fn (string $path, string $key, array $query): bool => count($query) === 2
                && isset($query['dataInicial'], $query['dataFinal']))
            ->andReturn(new PaginatedResponse(
                data: [],
                totalPaginas: 0,
                totalElementos: 0,
                numeroDeElementos: 0,
                ultimaPagina: true,
                primeiraPagina: true,
                tamanhoPagina: 10,
            ));

        createBillingResource($client)->list(
            dataInicial: '2026-04-01',
            dataFinal: '2026-04-30',
        );
    });
});

describe('all (pagination)', function (): void {
    it('iterates through all pages', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('list')
            ->once()
            ->withArgs(fn (string $p, string $k, array $q): bool => $q['paginaAtual'] === 0)
            ->andReturn(new PaginatedResponse(
                data: [['seuNumero' => 'INV-001'], ['seuNumero' => 'INV-002']],
                totalPaginas: 2,
                totalElementos: 3,
                numeroDeElementos: 2,
                ultimaPagina: false,
                primeiraPagina: true,
                tamanhoPagina: 2,
            ));
        $client->shouldReceive('list')
            ->once()
            ->withArgs(fn (string $p, string $k, array $q): bool => $q['paginaAtual'] === 1)
            ->andReturn(new PaginatedResponse(
                data: [['seuNumero' => 'INV-003']],
                totalPaginas: 2,
                totalElementos: 3,
                numeroDeElementos: 1,
                ultimaPagina: true,
                primeiraPagina: false,
                tamanhoPagina: 2,
            ));

        $items = iterator_to_array(createBillingResource($client)->all(
            query: ['dataInicial' => '2026-04-01', 'dataFinal' => '2026-04-30'],
            itensPorPagina: 2,
        ));

        expect($items)->toHaveCount(3)
            ->and($items[0]['seuNumero'])->toBe('INV-001')
            ->and($items[2]['seuNumero'])->toBe('INV-003');
    });

    it('stops on single page', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('list')
            ->once()
            ->andReturn(new PaginatedResponse(
                data: [['seuNumero' => 'ONLY']],
                totalPaginas: 1,
                totalElementos: 1,
                numeroDeElementos: 1,
                ultimaPagina: true,
                primeiraPagina: true,
                tamanhoPagina: 100,
            ));

        $items = iterator_to_array(createBillingResource($client)->all());

        expect($items)->toHaveCount(1);
    });
});

describe('pdf', function (): void {
    it('sends GET to the pdf endpoint', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/cobranca/v3/cobrancas/abc-123/pdf')
            ->andReturn(new Response(['pdf' => 'JVBERi0xLjQ=']));

        $response = createBillingResource($client)->pdf('abc-123');

        expect($response->data['pdf'])->toBe('JVBERi0xLjQ=');
    });
});

describe('cancel', function (): void {
    it('sends POST with cancellation reason', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('post')
            ->once()
            ->withArgs(fn (string $path, array $data): bool => $path === '/cobranca/v3/cobrancas/abc-123/cancelar'
                && $data['motivoCancelamento'] === 'APEDIDODOCLIENTE')
            ->andReturn(new Response([]));

        $response = createBillingResource($client)->cancel('abc-123', 'APEDIDODOCLIENTE');

        expect($response->data)->toBe([]);
    });
});

describe('summary', function (): void {
    it('sends GET with date range and returns summary items', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('get')
            ->once()
            ->withArgs(fn (string $path, array $query): bool => $path === '/cobranca/v3/cobrancas/sumario'
                && $query['dataInicial'] === '2026-04-01'
                && $query['dataFinal'] === '2026-04-30')
            ->andReturn(new Response([
                ['situacao' => 'A_RECEBER', 'quantidade' => 5, 'valor' => 1500.00],
                ['situacao' => 'RECEBIDO', 'quantidade' => 3, 'valor' => 900.00],
            ]));

        $response = createBillingResource($client)->summary(
            dataInicial: '2026-04-01',
            dataFinal: '2026-04-30',
        );

        expect($response->data)->toHaveCount(2);
    });

    it('excludes null filters', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $client->shouldReceive('get')
            ->once()
            ->withArgs(fn (string $path, array $query): bool => count($query) === 2)
            ->andReturn(new Response([]));

        createBillingResource($client)->summary(
            dataInicial: '2026-04-01',
            dataFinal: '2026-04-30',
        );
    });
});
