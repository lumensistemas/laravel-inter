<?php

declare(strict_types=1);

use LumenSistemas\Inter\Contracts\InterClientInterface;
use LumenSistemas\Inter\Http\InterClient;
use LumenSistemas\Inter\Inter;
use LumenSistemas\Inter\InterServiceProvider;

beforeEach(function (): void {
    $this->app->register(InterServiceProvider::class);
});

describe('service provider', function (): void {
    it('merges config', function (): void {
        /** @var Illuminate\Config\Repository $config */
        $config = $this->app->make('config');

        expect($config->get('inter'))->toBeArray()
            ->toHaveKeys([
                'client_id',
                'client_secret',
                'certificate',
                'private_key',
                'conta_corrente',
                'environment',
                'scopes',
                'timeout',
                'retry',
                'user_agent',
                'cache_prefix',
                'webhook_token',
            ])
            ->and($config->get('inter.timeout'))->toBeInt()
            ->and($config->get('inter.retry'))->toBeInt();
    });

    it('registers InterClientInterface as singleton', function (): void {
        $first = $this->app->make(InterClientInterface::class);
        $second = $this->app->make(InterClientInterface::class);

        expect($first)->toBeInstanceOf(InterClient::class)
            ->and($first)->toBe($second);
    });

    it('registers Inter as singleton', function (): void {
        $first = $this->app->make(Inter::class);
        $second = $this->app->make(Inter::class);

        expect($first)->toBeInstanceOf(Inter::class)
            ->and($first)->toBe($second);
    });
});
