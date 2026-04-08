<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Facades;

use Illuminate\Support\Facades\Facade;
use LumenSistemas\Inter\Inter as InterManager;

/**
 * @method static \LumenSistemas\Inter\Resources\BillingResource billing()
 * @method static \LumenSistemas\Inter\Resources\BillingWebhookResource billingWebhook()
 * @method static InterManager client(string $clientId, string $clientSecret, string $certificate, string $privateKey)
 *
 * @see InterManager
 */
class Inter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return InterManager::class;
    }
}
