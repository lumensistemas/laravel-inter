<?php

declare(strict_types=1);

namespace LumenSistemas\Inter\Enums;

enum Environment: string
{
    case Production = 'production';
    case Sandbox = 'sandbox';

    public function baseUrl(): string
    {
        return match ($this) {
            self::Production => 'https://cdpj.partners.bancointer.com.br',
            self::Sandbox => 'https://cdpj-sandbox.partners.uatinter.co',
        };
    }
}
