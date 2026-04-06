<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use LumenSistemas\Inter\Enums\Environment;
use LumenSistemas\Inter\Exceptions\AuthenticationException;
use LumenSistemas\Inter\Exceptions\ForbiddenException;
use LumenSistemas\Inter\Exceptions\InterException;
use LumenSistemas\Inter\Exceptions\NotFoundException;
use LumenSistemas\Inter\Exceptions\RateLimitException;
use LumenSistemas\Inter\Exceptions\ServerException;
use LumenSistemas\Inter\Exceptions\ValidationException;
use LumenSistemas\Inter\Http\InterClient;
use LumenSistemas\Inter\Http\TokenManager;

function createTokenManagerForClient(): TokenManager
{
    return new TokenManager(
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        certificate: '/path/to/cert.crt',
        privateKey: '/path/to/key.key',
        environment: Environment::Sandbox,
        scopes: 'boleto-cobranca.read',
        cachePrefix: 'inter_token',
    );
}

function createInterClient(?TokenManager $tokenManager = null): InterClient
{
    return new InterClient(
        tokenManager: $tokenManager ?? createTokenManagerForClient(),
        environment: Environment::Sandbox,
        certificate: '/path/to/cert.crt',
        privateKey: '/path/to/key.key',
        timeout: 30,
        retry: 1,
        userAgent: 'test-agent',
    );
}

beforeEach(function (): void {
    Cache::flush();
    // Pre-cache token so TokenManager never hits the OAuth endpoint
    $key = 'inter_token:'.hash('sha256', 'test-client-id');
    Cache::put($key, 'fake-token', 3600);
    Http::preventStrayRequests();
});

describe('successful requests', function (): void {
    it('sends GET request with query params', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response(['key' => 'value']),
        ]);

        $response = createInterClient()->get('/test', ['foo' => 'bar']);

        expect($response->data)->toBe(['key' => 'value']);
        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/test')
            && $request->header('Authorization')[0] === 'Bearer fake-token'
            && $request->header('User-Agent')[0] === 'test-agent');
    });

    it('sends POST request with json body', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response(['id' => '123']),
        ]);

        $response = createInterClient()->post('/test', ['name' => 'foo']);

        expect($response->data)->toBe(['id' => '123']);
        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request['name'] === 'foo');
    });

    it('sends PUT request', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response(['ok' => true]),
        ]);

        $response = createInterClient()->put('/test', ['data' => 'value']);

        expect($response->data)->toBe(['ok' => true]);
        Http::assertSent(fn ($request): bool => $request->method() === 'PUT');
    });

    it('sends PATCH request', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response(['patched' => true]),
        ]);

        $response = createInterClient()->patch('/test', ['field' => 'new']);

        expect($response->data)->toBe(['patched' => true]);
        Http::assertSent(fn ($request): bool => $request->method() === 'PATCH');
    });

    it('sends DELETE request', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response([]),
        ]);

        $response = createInterClient()->delete('/test');

        expect($response->data)->toBe([]);
        Http::assertSent(fn ($request): bool => $request->method() === 'DELETE');
    });
});

describe('list with pagination', function (): void {
    it('parses paginated response', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response([
                'cobrancas' => [
                    ['seuNumero' => 'INV-001'],
                    ['seuNumero' => 'INV-002'],
                ],
                'totalPaginas' => 3,
                'totalElementos' => 25,
                'numeroDeElementos' => 2,
                'ultimaPagina' => false,
                'primeiraPagina' => true,
                'tamanhoPagina' => 10,
            ]),
        ]);

        $response = createInterClient()->list('/cobranca/v3/cobrancas', 'cobrancas');

        expect($response->data)->toHaveCount(2)
            ->and($response->data[0]['seuNumero'])->toBe('INV-001')
            ->and($response->totalPaginas)->toBe(3)
            ->and($response->totalElementos)->toBe(25)
            ->and($response->numeroDeElementos)->toBe(2)
            ->and($response->ultimaPagina)->toBeFalse()
            ->and($response->primeiraPagina)->toBeTrue()
            ->and($response->tamanhoPagina)->toBe(10);
    });

    it('returns empty data when collection key is missing', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response([
                'totalPaginas' => 0,
                'totalElementos' => 0,
                'ultimaPagina' => true,
                'primeiraPagina' => true,
            ]),
        ]);

        $response = createInterClient()->list('/test', 'items');

        expect($response->data)->toBe([])
            ->and($response->ultimaPagina)->toBeTrue();
    });
});

describe('error handling', function (): void {
    it('throws ValidationException on 400', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response([
                'title' => 'Bad Request',
                'detail' => 'Invalid field value',
                'violacoes' => [
                    ['razao' => 'invalid', 'propriedade' => 'valorNominal', 'valor' => '-1'],
                ],
            ], 400),
        ]);

        try {
            createInterClient()->post('/test');
        } catch (ValidationException $e) {
            expect($e->getMessage())->toBe('Invalid field value')
                ->and($e->getCode())->toBe(400)
                ->and($e->violacoes)->toHaveCount(1)
                ->and($e->violacoes[0]['propriedade'])->toBe('valorNominal');

            return;
        }

        test()->fail('Expected ValidationException was not thrown');
    });

    it('throws AuthenticationException on 401 and invalidates cached token', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response([
                'detail' => 'Token expired',
            ], 401),
        ]);

        $cacheKey = 'inter_token:'.hash('sha256', 'test-client-id');

        expect(Cache::has($cacheKey))->toBeTrue();

        try {
            createInterClient()->get('/test');
        } catch (AuthenticationException $e) {
            expect($e->getMessage())->toBe('Token expired')
                ->and($e->getCode())->toBe(401)
                ->and(Cache::has($cacheKey))->toBeFalse();

            return;
        }

        test()->fail('Expected AuthenticationException was not thrown');
    });

    it('throws ForbiddenException on 403', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response([
                'detail' => 'Insufficient scope',
            ], 403),
        ]);

        createInterClient()->get('/test');
    })->throws(ForbiddenException::class, 'Insufficient scope');

    it('throws NotFoundException on 404', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response([
                'detail' => 'Billing not found',
            ], 404),
        ]);

        createInterClient()->get('/test/abc');
    })->throws(NotFoundException::class, 'Billing not found');

    it('throws RateLimitException on 429 with retryAfter', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response(
                ['detail' => 'Too many requests'],
                429,
                ['Retry-After' => '60'],
            ),
        ]);

        try {
            createInterClient()->get('/test');
        } catch (RateLimitException $e) {
            expect($e->getMessage())->toBe('Too many requests')
                ->and($e->getCode())->toBe(429)
                ->and($e->retryAfter)->toBe(60);

            return;
        }

        test()->fail('Expected RateLimitException was not thrown');
    });

    it('throws ServerException on 500', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response([
                'detail' => 'Internal server error',
            ], 500),
        ]);

        createInterClient()->get('/test');
    })->throws(ServerException::class, 'Internal server error');

    it('throws InterException on unexpected status codes', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response([
                'detail' => 'Teapot',
            ], 418),
        ]);

        createInterClient()->get('/test');
    })->throws(InterException::class, 'Teapot');

    it('falls back to title when detail is missing', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response([
                'title' => 'Not Found',
            ], 404),
        ]);

        createInterClient()->get('/test');
    })->throws(NotFoundException::class, 'Not Found');

    it('uses Unknown error when no message fields exist', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/*' => Http::response([], 400),
        ]);

        createInterClient()->post('/test');
    })->throws(ValidationException::class, 'Unknown error');
});
