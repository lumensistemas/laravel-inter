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

        expect($config->get('inter.client_id'))->toBe('')
            ->and($config->get('inter.environment'))->toBe('sandbox')
            ->and($config->get('inter.timeout'))->toBe(30)
            ->and($config->get('inter.cache_prefix'))->toBe('inter_token')
            ->and($config->get('inter.conta_corrente'))->toBe('');
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
