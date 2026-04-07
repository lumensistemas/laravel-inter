<?php

declare(strict_types=1);

namespace LumenSistemas\Inter;

use LumenSistemas\Inter\Contracts\InterClientInterface;
use LumenSistemas\Inter\Enums\Environment;
use LumenSistemas\Inter\Http\InterClient;
use LumenSistemas\Inter\Http\TokenManager;
use LumenSistemas\Inter\Resources\BillingResource;

class Inter
{
    private ?BillingResource $billingResource = null;

    public function __construct(
        private readonly InterClientInterface $client,
    ) {}

    /**
     * Create a new Inter instance with different credentials (multi-tenancy).
     *
     * ```php
     * $tenant = Inter::client(
     *     clientId: $tenant->inter_client_id,
     *     clientSecret: $tenant->inter_client_secret,
     *     certificate: $tenant->inter_certificate_path,
     *     privateKey: $tenant->inter_private_key_path,
     * );
     * $tenant->billing()->create(...);
     * ```
     */
    public function client(
        string $clientId,
        string $clientSecret,
        string $certificate,
        string $privateKey,
        Environment $environment = Environment::Sandbox,
        string $scopes = '',
        string $contaCorrente = '',
        int $timeout = 30,
        int $retry = 3,
        string $userAgent = 'laravel-inter',
        string $cachePrefix = 'inter_token',
    ): self {
        $tokenManager = new TokenManager(
            clientId: $clientId,
            clientSecret: $clientSecret,
            certificate: $certificate,
            privateKey: $privateKey,
            environment: $environment,
            scopes: $scopes,
            cachePrefix: $cachePrefix,
        );

        $interClient = new InterClient(
            tokenManager: $tokenManager,
            environment: $environment,
            certificate: $certificate,
            privateKey: $privateKey,
            timeout: $timeout,
            retry: $retry,
            userAgent: $userAgent,
            contaCorrente: $contaCorrente,
        );

        return new self($interClient);
    }

    public function billing(): BillingResource
    {
        return $this->billingResource ??= new BillingResource($this->client);
    }
}
