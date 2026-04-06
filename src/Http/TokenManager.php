<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Http;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use LumenSistemas\Inter\Enums\Environment;
use LumenSistemas\Inter\Exceptions\AuthenticationException;

final readonly class TokenManager
{
    private const int TTL_BUFFER = 100;

    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $certificate,
        private string $privateKey,
        private Environment $environment,
        private string $scopes,
        private string $cachePrefix,
    ) {}

    public function getToken(): string
    {
        $key = $this->cacheKey();

        $token = Cache::get($key);

        if (is_string($token)) {
            return $token;
        }

        [$token, $ttl] = $this->requestToken();

        Cache::put($key, $token, max(0, $ttl - self::TTL_BUFFER));

        return $token;
    }

    public function invalidate(): void
    {
        Cache::forget($this->cacheKey());
    }

    /**
     * @return array{string, int}
     */
    private function requestToken(): array
    {
        $response = Http::asForm()
            ->withOptions([
                'cert' => $this->certificate,
                'ssl_key' => $this->privateKey,
            ])
            ->post($this->environment->baseUrl().'/oauth/v2/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
                'scope' => $this->scopes,
            ]);

        if ($response->failed()) {
            /** @var string $errorDescription */
            $errorDescription = $response->json('error_description', 'Failed to obtain access token');

            throw new AuthenticationException(
                message: $errorDescription,
                code: $response->status(),
            );
        }

        $token = $response->json('access_token');

        if (!is_string($token) || $token === '') {
            throw new AuthenticationException(
                message: 'Invalid token response from Banco Inter',
                code: $response->status(),
            );
        }

        /** @var int $expiresIn */
        $expiresIn = $response->json('expires_in', 3600);

        return [$token, $expiresIn];
    }

    private function cacheKey(): string
    {
        return $this->cachePrefix.':'.hash('sha256', $this->clientId);
    }
}
