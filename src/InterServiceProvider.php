<?php

declare(strict_types=1);

namespace LumenSistemas\Inter;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use LumenSistemas\Inter\Contracts\InterClientInterface;
use LumenSistemas\Inter\Enums\Environment;
use LumenSistemas\Inter\Http\InterClient;
use LumenSistemas\Inter\Http\TokenManager;
use Override;

class InterServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/inter.php', 'inter');

        $this->app->singleton(InterClientInterface::class, function (Application $app): InterClientInterface {
            /** @var \Illuminate\Config\Repository $repository */
            $repository = $app->make('config');

            /** @var array{client_id: string, client_secret: string, certificate: string, private_key: string, conta_corrente: string, environment: string, scopes: string, timeout: int, retry: int, user_agent: string, cache_prefix: string, webhook_token: string} $config */
            $config = $repository->get('inter');

            $environment = Environment::from($config['environment']);

            $tokenManager = new TokenManager(
                clientId: $config['client_id'],
                clientSecret: $config['client_secret'],
                certificate: $config['certificate'],
                privateKey: $config['private_key'],
                environment: $environment,
                scopes: $config['scopes'],
                cachePrefix: $config['cache_prefix'],
            );

            return new InterClient(
                tokenManager: $tokenManager,
                environment: $environment,
                certificate: $config['certificate'],
                privateKey: $config['private_key'],
                timeout: $config['timeout'],
                retry: $config['retry'],
                userAgent: $config['user_agent'],
                contaCorrente: $config['conta_corrente'],
            );
        });

        $this->app->singleton(Inter::class, fn (Application $app): Inter => new Inter($app->make(InterClientInterface::class)));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/inter.php' => $this->app->configPath('inter.php'),
            ], 'inter-config');
        }
    }
}
