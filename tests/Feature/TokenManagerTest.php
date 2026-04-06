<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use LumenSistemas\Inter\Enums\Environment;
use LumenSistemas\Inter\Exceptions\AuthenticationException;
use LumenSistemas\Inter\Http\TokenManager;

function createTokenManager(
    string $clientId = 'test-client-id',
    string $clientSecret = 'test-client-secret',
    Environment $environment = Environment::Sandbox,
    string $scopes = 'boleto-cobranca.read boleto-cobranca.write',
): TokenManager {
    return new TokenManager(
        clientId: $clientId,
        clientSecret: $clientSecret,
        certificate: '/path/to/cert.crt',
        privateKey: '/path/to/key.key',
        environment: $environment,
        scopes: $scopes,
        cachePrefix: 'inter_token',
    );
}

beforeEach(function (): void {
    Cache::flush();
    Http::preventStrayRequests();
});

describe('getToken', function (): void {
    it('requests a new token when cache is empty', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/oauth/v2/token' => Http::response([
                'access_token' => 'fresh-token-123',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => 'boleto-cobranca.read',
            ]),
        ]);

        $token = createTokenManager()->getToken();

        expect($token)->toBe('fresh-token-123');
    });

    it('returns cached token on subsequent calls', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/oauth/v2/token' => Http::response([
                'access_token' => 'cached-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
        ]);

        $manager = createTokenManager();
        $first = $manager->getToken();
        $second = $manager->getToken();

        expect($first)->toBe('cached-token');
        expect($second)->toBe('cached-token');
        Http::assertSentCount(1);
    });

    it('caches token with TTL based on expires_in minus buffer', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/oauth/v2/token' => Http::response([
                'access_token' => 'token',
                'expires_in' => 1800,
            ]),
        ]);

        Cache::spy();

        $manager = createTokenManager();
        $manager->getToken();

        Cache::shouldHaveReceived('put')
            ->withArgs(fn (string $key, string $value, int $ttl): bool => $ttl === 1700 && $value === 'token');
    });

    it('sends correct form data and uses mTLS options', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/oauth/v2/token' => Http::response([
                'access_token' => 'token',
                'expires_in' => 3600,
            ]),
        ]);

        createTokenManager()->getToken();

        Http::assertSent(fn ($request): bool => $request->url() === 'https://cdpj-sandbox.partners.uatinter.co/oauth/v2/token'
            && $request['client_id'] === 'test-client-id'
            && $request['client_secret'] === 'test-client-secret'
            && $request['grant_type'] === 'client_credentials'
            && $request['scope'] === 'boleto-cobranca.read boleto-cobranca.write');
    });

    it('uses production base url when environment is production', function (): void {
        Http::fake([
            'cdpj.partners.bancointer.com.br/oauth/v2/token' => Http::response([
                'access_token' => 'prod-token',
                'expires_in' => 3600,
            ]),
        ]);

        $token = createTokenManager(environment: Environment::Production)->getToken();

        expect($token)->toBe('prod-token');
    });

    it('throws AuthenticationException on failed response', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/oauth/v2/token' => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed',
            ], 401),
        ]);

        createTokenManager()->getToken();
    })->throws(AuthenticationException::class, 'Client authentication failed');

    it('throws AuthenticationException when access_token is missing', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/oauth/v2/token' => Http::response([
                'token_type' => 'Bearer',
            ]),
        ]);

        createTokenManager()->getToken();
    })->throws(AuthenticationException::class, 'Invalid token response from Banco Inter');

    it('throws AuthenticationException when access_token is empty', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/oauth/v2/token' => Http::response([
                'access_token' => '',
                'expires_in' => 3600,
            ]),
        ]);

        createTokenManager()->getToken();
    })->throws(AuthenticationException::class, 'Invalid token response from Banco Inter');
});

describe('invalidate', function (): void {
    it('clears the cached token', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/oauth/v2/token' => Http::sequence()
                ->push(['access_token' => 'first-token', 'expires_in' => 3600])
                ->push(['access_token' => 'second-token', 'expires_in' => 3600]),
        ]);

        $manager = createTokenManager();

        expect($manager->getToken())->toBe('first-token');

        $manager->invalidate();

        expect($manager->getToken())->toBe('second-token');
        Http::assertSentCount(2);
    });
});

describe('cache isolation', function (): void {
    it('uses different cache keys per client_id', function (): void {
        Http::fake([
            'cdpj-sandbox.partners.uatinter.co/oauth/v2/token' => Http::sequence()
                ->push(['access_token' => 'token-a', 'expires_in' => 3600])
                ->push(['access_token' => 'token-b', 'expires_in' => 3600]),
        ]);

        $managerA = createTokenManager(clientId: 'client-a');
        $managerB = createTokenManager(clientId: 'client-b');

        expect($managerA->getToken())->toBe('token-a');
        expect($managerB->getToken())->toBe('token-b');
    });
});
