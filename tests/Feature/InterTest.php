<?php

declare(strict_types=1);

use LumenSistemas\Inter\Contracts\InterClientInterface;
use LumenSistemas\Inter\Inter;
use LumenSistemas\Inter\Resources\BillingResource;

describe('billing', function (): void {
    it('returns a BillingResource instance', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $inter = new Inter($client);

        expect($inter->billing())->toBeInstanceOf(BillingResource::class);
    });

    it('returns the same BillingResource instance (lazy-loaded)', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $inter = new Inter($client);

        $first = $inter->billing();
        $second = $inter->billing();

        expect($first)->toBe($second);
    });
});

describe('client (multi-tenancy)', function (): void {
    it('returns a new Inter instance', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $inter = new Inter($client);

        $tenant = $inter->client(
            clientId: 'tenant-id',
            clientSecret: 'tenant-secret',
            certificate: '/path/to/cert.crt',
            privateKey: '/path/to/key.key',
        );

        expect($tenant)->toBeInstanceOf(Inter::class)
            ->and($tenant)->not->toBe($inter);
    });

    it('creates isolated instances per tenant', function (): void {
        $client = Mockery::mock(InterClientInterface::class);
        $inter = new Inter($client);

        $tenantA = $inter->client(
            clientId: 'tenant-a',
            clientSecret: 'secret-a',
            certificate: '/path/a.crt',
            privateKey: '/path/a.key',
        );

        $tenantB = $inter->client(
            clientId: 'tenant-b',
            clientSecret: 'secret-b',
            certificate: '/path/b.crt',
            privateKey: '/path/b.key',
        );

        expect($tenantA)->not->toBe($tenantB)
            ->and($tenantA->billing())->not->toBe($tenantB->billing());
    });
});
