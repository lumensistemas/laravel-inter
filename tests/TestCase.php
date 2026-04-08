<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Tests;

use Dotenv\Dotenv;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->loadEnvFile();

        parent::setUp();
    }

    private function loadEnvFile(): void
    {
        $envFile = dirname(__DIR__).'/.env';

        if (!file_exists($envFile)) {
            return;
        }

        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->safeLoad();
    }
}
