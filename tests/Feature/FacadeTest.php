<?php

declare(strict_types=1);

use LumenSistemas\Inter\Facades\Inter;
use LumenSistemas\Inter\Inter as InterManager;
use LumenSistemas\Inter\InterServiceProvider;
use LumenSistemas\Inter\Resources\BillingResource;

beforeEach(function (): void {
    $this->app->register(InterServiceProvider::class);
});

describe('facade', function (): void {
    it('resolves to Inter manager', function (): void {
        expect(Inter::getFacadeRoot())->toBeInstanceOf(InterManager::class);
    });

    it('proxies billing() to the manager', function (): void {
        expect(Inter::billing())->toBeInstanceOf(BillingResource::class);
    });
});
